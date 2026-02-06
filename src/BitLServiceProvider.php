<?php

namespace BitL\Debug;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Mail\Events\MessageSending;

/**
 * Laravel Service Provider for BitL Debug
 */
class BitLServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Register the BitL client as a singleton
        $this->app->singleton(Client::class, function () {
            return new Client(
                host: config('bitl.host', '127.0.0.1'),
                port: config('bitl.port', 8765),
                timeout: config('bitl.timeout', 1)
            );
        });

        // Use the singleton client
        BitL::setClient($this->app->make(Client::class));
    }

    /**
     * Bootstrap the service provider.
     */
    public function boot(): void
    {
        // Only enable in local environment
        if (! $this->app->isLocal()) {
            BitL::disable();
            return;
        }

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/bitl.php' => config_path('bitl.php'),
        ], 'bitl-config');

        // Register error handler if enabled
        if (config('bitl.capture_errors', true)) {
            $this->registerExceptionReporting();
            // Also register PHP error handler for warnings/notices
            $this->registerErrorHandler();
        }

        // Listen for database queries
        if (config('bitl.capture_queries', true)) {
            $this->listenForQueries();
        }

        // Listen for mail
        if (config('bitl.capture_mail', true)) {
            $this->listenForMail();
        }
    }

    /**
     * Register exception reporting with Laravel's exception handler.
     */
    protected function registerExceptionReporting(): void
    {
        try {
            $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
            
            // Laravel 8-10: Use reportable() on the handler directly
            if (method_exists($handler, 'reportable')) {
                $handler->reportable(function (\Throwable $e) {
                    BitL::error($e);
                    return false; // Don't stop other reporters
                });
            } else {
                // Fallback: register native PHP error handler
                BitL::register();
            }
        } catch (\Throwable) {
            // Fallback: register native PHP error handler
            BitL::register();
        }
    }

    /**
     * Register PHP error handler for warnings/notices.
     */
    protected function registerErrorHandler(): void
    {
        $previousHandler = set_error_handler(function (
            int $errno,
            string $errstr,
            string $errfile = '',
            int $errline = 0
        ) use (&$previousHandler) {
            // Don't report suppressed errors
            if (! (error_reporting() & $errno)) {
                return false;
            }

            $level = match ($errno) {
                E_WARNING, E_USER_WARNING => 'Warning',
                E_NOTICE, E_USER_NOTICE => 'Notice',
                E_DEPRECATED, E_USER_DEPRECATED => 'Deprecated',
                E_STRICT => 'Strict',
                default => 'Error',
            };

            // Send to BitL
            BitL::warning("[{$level}] {$errstr}", $errfile, $errline);

            // Call previous handler if exists
            if ($previousHandler !== null) {
                return $previousHandler($errno, $errstr, $errfile, $errline);
            }

            return false; // Let PHP handle it normally
        });
    }

    /**
     * Listen for database queries.
     */
    protected function listenForQueries(): void
    {
        if (! $this->app->bound('events')) {
            return;
        }

        $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $event) {
            BitL::query(
                sql: $event->sql,
                bindings: $event->bindings,
                time: $event->time,
                connection: $event->connectionName
            );
        });
    }

    /**
     * Listen for outgoing mail.
     */
    protected function listenForMail(): void
    {
        if (! $this->app->bound('events')) {
            return;
        }

        $this->app['events']->listen(MessageSending::class, function (MessageSending $event) {
            $message = $event->message;

            // Get recipients
            $to = $this->extractAddresses($message->getTo());
            $cc = $this->extractAddresses($message->getCc());
            $bcc = $this->extractAddresses($message->getBcc());
            $from = $this->extractAddresses($message->getFrom())[0] ?? 'unknown';

            BitL::mail(
                from: $from,
                to: $to,
                subject: $message->getSubject() ?? '(no subject)',
                html: $message->getHtmlBody(),
                text: $message->getTextBody(),
                cc: $cc,
                bcc: $bcc
            );
        });
    }

    /**
     * Extract email addresses from address list.
     */
    protected function extractAddresses($addresses): array
    {
        if ($addresses === null) {
            return [];
        }

        $result = [];
        foreach ($addresses as $address) {
            $result[] = $address->getAddress();
        }

        return $result;
    }
}
