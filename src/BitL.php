<?php

namespace BitL\Debug;

/**
 * BitL Debug Client
 *
 * Sends debug information to the BitL desktop app's debug bar.
 */
class BitL
{
    protected static ?Client $client = null;
    protected static bool $enabled = true;

    /**
     * Get or create the client instance.
     */
    public static function client(): Client
    {
        if (static::$client === null) {
            static::$client = new Client();
        }

        return static::$client;
    }

    /**
     * Set a custom client instance.
     */
    public static function setClient(Client $client): void
    {
        static::$client = $client;
    }

    /**
     * Enable BitL debug integration.
     */
    public static function enable(): void
    {
        static::$enabled = true;
    }

    /**
     * Disable BitL debug integration.
     */
    public static function disable(): void
    {
        static::$enabled = false;
    }

    /**
     * Check if BitL is enabled.
     */
    public static function isEnabled(): bool
    {
        return static::$enabled;
    }

    /**
     * Send an error to BitL.
     */
    public static function error(\Throwable $e, ?string $domain = null): void
    {
        if (! static::$enabled) {
            return;
        }

        static::client()->sendError($e, $domain);
    }

    /**
     * Send a warning to BitL.
     */
    public static function warning(string $message, string $file = '', int $line = 0, ?string $domain = null): void
    {
        if (! static::$enabled) {
            return;
        }

        static::client()->sendWarning($message, $file, $line, $domain);
    }

    /**
     * Send a dump to BitL.
     */
    public static function dump(mixed $value, ?string $domain = null): mixed
    {
        if (! static::$enabled) {
            return $value;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[1] ?? $backtrace[0];

        static::client()->sendDump(
            $value,
            $caller['file'] ?? 'unknown',
            $caller['line'] ?? 0,
            $domain
        );

        return $value;
    }

    /**
     * Send a dump and die.
     */
    public static function dd(mixed ...$values): never
    {
        foreach ($values as $value) {
            static::dump($value);
        }

        exit(1);
    }

    /**
     * Send a query to BitL.
     */
    public static function query(
        string $sql,
        array $bindings = [],
        float $time = 0,
        ?string $connection = null,
        ?string $domain = null
    ): void {
        if (! static::$enabled) {
            return;
        }

        static::client()->sendQuery($sql, $bindings, $time, $connection, $domain);
    }

    /**
     * Send a mail capture to BitL.
     */
    public static function mail(
        string $from,
        array $to,
        string $subject,
        ?string $html = null,
        ?string $text = null,
        array $cc = [],
        array $bcc = []
    ): void {
        if (! static::$enabled) {
            return;
        }

        static::client()->sendMail($from, $to, $subject, $html, $text, $cc, $bcc);
    }

    /**
     * Register error and exception handlers.
     */
    public static function register(): void
    {
        ErrorHandler::register();
    }
}
