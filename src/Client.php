<?php

namespace BitL\Debug;

/**
 * HTTP Client for BitL Debug Server
 */
class Client
{
    protected string $host;
    protected int $port;
    protected int $timeout;

    public function __construct(
        string $host = '127.0.0.1',
        int $port = 8765,
        int $timeout = 1
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    /**
     * Send an error/exception to BitL.
     */
    public function sendError(\Throwable $e, ?string $domain = null): void
    {
        $snippet = $this->getCodeSnippet($e->getFile(), $e->getLine());

        $this->post('/error', [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'snippet' => $snippet,
            'domain' => $domain ?? $this->detectDomain(),
        ]);
    }

    /**
     * Send a warning to BitL.
     */
    public function sendWarning(string $message, string $file, int $line, ?string $domain = null): void
    {
        $this->post('/warning', [
            'level' => 'Warning',
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'domain' => $domain ?? $this->detectDomain(),
        ]);
    }

    /**
     * Send a dump to BitL.
     */
    public function sendDump(mixed $value, string $file, int $line, ?string $domain = null): void
    {
        $this->post('/dump', [
            'file' => $file,
            'line' => $line,
            'content' => $this->formatValue($value),
            'type' => $this->getType($value),
            'domain' => $domain ?? $this->detectDomain(),
        ]);
    }

    /**
     * Send a query to BitL.
     */
    public function sendQuery(
        string $sql,
        array $bindings,
        float $time,
        ?string $connection,
        ?string $domain = null
    ): void {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $caller = $this->findQueryCaller($backtrace);

        $this->post('/query', [
            'sql' => $sql,
            'bindings' => array_map(fn ($b) => (string) $b, $bindings),
            'time' => $time,
            'connection' => $connection ?? 'default',
            'file' => $caller['file'] ?? null,
            'line' => $caller['line'] ?? null,
            'domain' => $domain ?? $this->detectDomain(),
        ]);
    }

    /**
     * Send a mail to BitL.
     */
    public function sendMail(
        string $from,
        array $to,
        string $subject,
        ?string $html,
        ?string $text,
        array $cc,
        array $bcc
    ): void {
        $this->post('/mail', [
            'from' => $from,
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
        ]);
    }

    /**
     * Send a POST request to BitL.
     */
    protected function post(string $endpoint, array $data): void
    {
        try {
            $json = json_encode($data);
            $url = "http://{$this->host}:{$this->port}{$endpoint}";

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n" .
                                "Content-Length: " . strlen($json) . "\r\n",
                    'content' => $json,
                    'timeout' => $this->timeout,
                    'ignore_errors' => true,
                ],
            ]);

            @file_get_contents($url, false, $context);
        } catch (\Throwable) {
            // Silently fail - don't break the app if BitL is not running
        }
    }

    /**
     * Get code snippet around a line.
     */
    protected function getCodeSnippet(string $file, int $line, int $context = 5): array
    {
        if (! file_exists($file) || ! is_readable($file)) {
            return [];
        }

        $lines = file($file);
        $snippet = [];

        $start = max(0, $line - $context - 1);
        $end = min(count($lines), $line + $context);

        for ($i = $start; $i < $end; $i++) {
            $snippet[] = [
                'number' => $i + 1,
                'content' => rtrim($lines[$i] ?? ''),
            ];
        }

        return $snippet;
    }

    /**
     * Format a value for display.
     */
    protected function formatValue(mixed $value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value)) {
            return '"' . $value . '"';
        }

        if (is_array($value) || is_object($value)) {
            return $this->prettyPrint($value);
        }

        return (string) $value;
    }

    /**
     * Pretty print arrays and objects.
     */
    protected function prettyPrint(mixed $value, int $depth = 0): string
    {
        $maxDepth = 10;
        $indent = str_repeat('  ', $depth);

        if ($depth > $maxDepth) {
            return '...';
        }

        if (is_array($value)) {
            if (empty($value)) {
                return '[]';
            }

            $isAssoc = array_keys($value) !== range(0, count($value) - 1);
            $items = [];

            foreach ($value as $k => $v) {
                $key = $isAssoc ? (is_string($k) ? "'{$k}'" : $k) . ' => ' : '';
                $items[] = $indent . '  ' . $key . $this->prettyPrint($v, $depth + 1);
            }

            return "[\n" . implode(",\n", $items) . "\n{$indent}]";
        }

        if (is_object($value)) {
            $class = get_class($value);

            if (method_exists($value, '__debugInfo')) {
                $props = $value->__debugInfo();
            } elseif (method_exists($value, 'toArray')) {
                $props = $value->toArray();
            } else {
                $props = get_object_vars($value);
            }

            if (empty($props)) {
                return "{$class} {}";
            }

            $items = [];
            foreach ($props as $k => $v) {
                $items[] = $indent . '  ' . $k . ': ' . $this->prettyPrint($v, $depth + 1);
            }

            return "{$class} {\n" . implode(",\n", $items) . "\n{$indent}}";
        }

        return $this->formatValue($value);
    }

    /**
     * Get the type of a value.
     */
    protected function getType(mixed $value): string
    {
        if (is_object($value)) {
            return get_class($value);
        }

        return gettype($value);
    }

    /**
     * Detect the current domain from SERVER or app config.
     */
    protected function detectDomain(): ?string
    {
        return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? null;
    }

    /**
     * Find the actual caller of a query (skip framework internals).
     */
    protected function findQueryCaller(array $backtrace): array
    {
        foreach ($backtrace as $frame) {
            $file = $frame['file'] ?? '';

            // Skip vendor files
            if (str_contains($file, '/vendor/')) {
                continue;
            }

            // Skip BitL files
            if (str_contains($file, 'BitL/Debug')) {
                continue;
            }

            return $frame;
        }

        return $backtrace[0] ?? [];
    }
}
