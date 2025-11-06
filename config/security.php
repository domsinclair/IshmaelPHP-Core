<?php
declare(strict_types=1);

/**
 * -------------------------------------------------------------
 * Security Configuration
 * -------------------------------------------------------------
 * CSRF protection and response security headers.
 */

return [
    'csrf' => [
        // Globally enable/disable CSRF middleware behavior (middleware can still be added/removed)
        'enabled' => true,

        // The name of the hidden form field used to carry the CSRF token
        'field_name' => '_token',

        // Header names that are checked for a CSRF token (first match wins)
        // Common: X-CSRF-Token, X-XSRF-Token
        'header_names' => ['X-CSRF-Token', 'X-XSRF-Token'],

        // Methods that are exempt from CSRF checks (idempotent by convention)
        'except_methods' => ['GET', 'HEAD', 'OPTIONS'],

        // URI patterns to skip (supports '*' wildcard and prefix matching)
        // Examples: '/webhook/*', '/status', '/public/*'
        'except_uris' => [],

        // Error response customization
        'failure' => [
            'status' => 419, // Page Expired (used by several frameworks for CSRF)
            'message' => 'CSRF token mismatch.',
            'code' => 'csrf_mismatch',
        ],

        // Future: rotate token policy (e.g., on login). Placeholder for now.
        'rotate_on' => [],
    ],

    // HTTP security headers applied by SecurityHeaders middleware
    'headers' => [
        'enabled' => true,
        // X-Frame-Options: DENY|SAMEORIGIN|ALLOW-FROM uri (latter deprecated in modern browsers)
        'x_frame_options' => env('SECURITY_XFO', 'SAMEORIGIN'),
        // X-Content-Type-Options: nosniff
        'x_content_type_options' => env('SECURITY_XCTO', 'nosniff'),
        // Referrer-Policy: e.g., no-referrer, strict-origin-when-cross-origin
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'no-referrer-when-downgrade'),
        // Permissions-Policy: e.g., "camera=(), microphone=(), geolocation=()"
        'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY', ''),
        // Content-Security-Policy baseline. Keep conservative by default.
        'content_security_policy' => env('SECURITY_CSP', "default-src 'self'; frame-ancestors 'self'"),
        // HSTS (HTTPS only). Disabled by default; enable when serving via TLS.
        'hsts' => [
            'enabled' => (bool) env('SECURITY_HSTS', false),
            // When true, only emit HSTS on HTTPS requests (recommended behind proxies); set false to force.
            'only_https' => (bool) env('SECURITY_HSTS_ONLY_HTTPS', true),
            'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 15552000),
            'include_subdomains' => (bool) env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', false),
            'preload' => (bool) env('SECURITY_HSTS_PRELOAD', false),
        ],
    ],
];
