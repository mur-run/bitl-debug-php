<?php

return [
    /*
    |--------------------------------------------------------------------------
    | BitL Debug Server Host
    |--------------------------------------------------------------------------
    |
    | The hostname or IP address of the BitL debug server.
    |
    */
    'host' => env('BITL_HOST', '127.0.0.1'),

    /*
    |--------------------------------------------------------------------------
    | BitL Debug Server Port
    |--------------------------------------------------------------------------
    |
    | The port number of the BitL debug server.
    |
    */
    'port' => env('BITL_PORT', 8765),

    /*
    |--------------------------------------------------------------------------
    | Connection Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time in seconds to wait for the BitL server to respond.
    | Keep this low to avoid slowing down your application.
    |
    */
    'timeout' => env('BITL_TIMEOUT', 1),

    /*
    |--------------------------------------------------------------------------
    | Capture Errors
    |--------------------------------------------------------------------------
    |
    | Whether to capture PHP errors and exceptions and send them to BitL.
    |
    */
    'capture_errors' => env('BITL_CAPTURE_ERRORS', true),

    /*
    |--------------------------------------------------------------------------
    | Capture Database Queries
    |--------------------------------------------------------------------------
    |
    | Whether to capture database queries and send them to BitL.
    |
    */
    'capture_queries' => env('BITL_CAPTURE_QUERIES', true),

    /*
    |--------------------------------------------------------------------------
    | Capture Mail
    |--------------------------------------------------------------------------
    |
    | Whether to capture outgoing mail and send it to BitL.
    |
    */
    'capture_mail' => env('BITL_CAPTURE_MAIL', true),
];
