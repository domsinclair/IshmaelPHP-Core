<?php
declare(strict_types=1);

/**
 * -------------------------------------------------------------
 * Session Configuration
 * -------------------------------------------------------------
 * Driver options: file, cookie, database
 */

return [
    // driver: file|cookie|database
    'driver'    => env('SESSION_DRIVER', 'file'),

    // Lifetime in minutes
    'lifetime'  => (int) env('SESSION_LIFETIME', 120),

    // Name of the session cookie
    'cookie'    => env('SESSION_COOKIE', 'ish_session'),

    // Cookie path and domain
    'path'      => env('SESSION_PATH', '/'),
    'domain'    => env('SESSION_DOMAIN', ''),

    // Security flags
    'secure'    => (bool) env('SESSION_SECURE', false),
    'http_only' => (bool) env('SESSION_HTTP_ONLY', true),
    'same_site' => env('SESSION_SAME_SITE', 'Lax'), // Lax|Strict|None

    // Storage path for file sessions
    'files'     => storage_path('sessions'),
];
