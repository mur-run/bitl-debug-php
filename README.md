# BitL Debug

Send PHP errors, dumps, and queries to BitL's debug bar.

## Requirements

- PHP 8.1+
- BitL desktop app running

## Installation

```bash
composer require bitl/debug --dev
```

For Laravel, the service provider is auto-discovered.

## Usage

### Dump Variables

```php
use BitL\Debug\BitL;

// Send a dump to BitL
BitL::dump($user);

// Or use the helper functions
bd($user);           // BitL Dump
bdd($user);          // BitL Dump and Die
bitl_dump($user);    // Explicit function name
```

### Manual Error Reporting

```php
try {
    // ...
} catch (\Exception $e) {
    BitL::error($e);
    throw $e;
}
```

### Manual Query Logging

```php
BitL::query(
    sql: 'SELECT * FROM users WHERE id = ?',
    bindings: [1],
    time: 2.5,  // milliseconds
    connection: 'mysql'
);
```

### Manual Mail Capture

```php
BitL::mail(
    from: 'noreply@example.com',
    to: ['user@example.com'],
    subject: 'Welcome!',
    html: '<h1>Hello World</h1>',
    text: 'Hello World'
);
```

## Laravel Integration

The package auto-discovers in Laravel and automatically:

- Logs all database queries
- Captures outgoing mail

### Exception Capturing (Laravel 11+)

For Laravel 11+, add exception reporting in `bootstrap/app.php`:

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->reportable(function (\Throwable $e) {
        if (class_exists(\BitL\Debug\BitL::class)) {
            \BitL\Debug\BitL::error($e);
        }
        return false;
    });
})
```

### Exception Capturing (Laravel 8-10)

For Laravel 8-10, add to `app/Exceptions/Handler.php`:

```php
public function register(): void
{
    $this->reportable(function (\Throwable $e) {
        \BitL\Debug\BitL::error($e);
        return false;
    });
}
```

### Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=bitl-config
```

Or use environment variables:

```env
BITL_HOST=127.0.0.1
BITL_PORT=8765
BITL_CAPTURE_ERRORS=true
BITL_CAPTURE_QUERIES=true
BITL_CAPTURE_MAIL=true
```

## Non-Laravel Usage

For non-Laravel PHP projects, register the error handler manually:

```php
require 'vendor/autoload.php';

use BitL\Debug\BitL;

// Register error and exception handlers
BitL::register();

// Your code...
```

## Disabling in Production

The Laravel integration automatically disables in non-local environments.

For manual control:

```php
use BitL\Debug\BitL;

if (getenv('APP_ENV') === 'production') {
    BitL::disable();
}
```

## License

MIT
