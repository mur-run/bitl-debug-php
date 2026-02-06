<?php

namespace BitL\Debug;

/**
 * Error and Exception Handler for BitL
 */
class ErrorHandler
{
    protected static bool $registered = false;
    protected static $previousErrorHandler = null;
    protected static $previousExceptionHandler = null;

    /**
     * Register error and exception handlers.
     */
    public static function register(): void
    {
        if (static::$registered) {
            return;
        }

        static::$previousErrorHandler = set_error_handler([static::class, 'handleError']);
        static::$previousExceptionHandler = set_exception_handler([static::class, 'handleException']);

        static::$registered = true;
    }

    /**
     * Unregister handlers and restore previous ones.
     */
    public static function unregister(): void
    {
        if (! static::$registered) {
            return;
        }

        if (static::$previousErrorHandler !== null) {
            set_error_handler(static::$previousErrorHandler);
        } else {
            restore_error_handler();
        }

        if (static::$previousExceptionHandler !== null) {
            set_exception_handler(static::$previousExceptionHandler);
        } else {
            restore_exception_handler();
        }

        static::$registered = false;
    }

    /**
     * Handle PHP errors.
     */
    public static function handleError(
        int $errno,
        string $errstr,
        string $errfile = '',
        int $errline = 0
    ): bool {
        // Don't report suppressed errors
        if (! (error_reporting() & $errno)) {
            return false;
        }

        $level = static::errorLevelToString($errno);
        
        // Send to BitL as warning (non-fatal) or error (fatal)
        if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            BitL::error(new \ErrorException($errstr, 0, $errno, $errfile, $errline));
        } else {
            BitL::warning("[{$level}] {$errstr}", $errfile, $errline);
        }

        // Call previous handler if exists
        if (static::$previousErrorHandler !== null) {
            return call_user_func(
                static::$previousErrorHandler,
                $errno,
                $errstr,
                $errfile,
                $errline
            );
        }

        return false; // Let PHP handle it normally
    }

    /**
     * Handle uncaught exceptions.
     */
    public static function handleException(\Throwable $e): void
    {
        BitL::error($e);

        // Call previous handler if exists
        if (static::$previousExceptionHandler !== null) {
            call_user_func(static::$previousExceptionHandler, $e);
        }
    }

    /**
     * Convert error level to string.
     */
    protected static function errorLevelToString(int $level): string
    {
        return match ($level) {
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
            default => 'Unknown Error',
        };
    }
}
