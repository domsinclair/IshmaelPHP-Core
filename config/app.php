<?php
    declare(strict_types=1);

    /**
     * -------------------------------------------------------------
     * Application Configuration
     * -------------------------------------------------------------
     * This file controls global app settings such as environment,
     * debug mode, URLs, and module discovery.
     * Values fall back to .env but can be overridden here.
     */

    return [
        'name'      => env('APP_NAME', 'Ishmael'),
        'env'       => env('APP_ENV', 'development'),
        'debug'     => env('APP_DEBUG', true),
        'url'       => env('APP_URL', 'http://ishmaelphp.test'),

        // Routing options
        'routing' => [
            // If true, requests to <appName>.test are treated as base (Laravel Herd style)
            'herd_base' => env('ROUTING_HERD_BASE', true),
        ],

        // The default module and controller to load if no route is matched
        'default_module'     => 'HelloWorld',
        'default_controller' => 'Home',
        'default_action'     => 'index',

        // Timezone and locale
        'timezone' => 'UTC',
        'locale'   => 'en',

        // Directories for auto-discovery (modules, commands, etc.)
        'paths' => [
            'modules'  => base_path('modules'),
            'storage'  => base_path('storage'),
            'logs'     => base_path('storage/logs'),
            'cache'    => base_path('storage/cache'),
        ],

        // HTTP pipeline configuration
        'http' => [
            // Global middleware stack (callables or invokable class names)
            'middleware' => [
                // Example: Ishmael\Core\Http\Middleware\RequestIdMiddleware::class,
                // Example: Ishmael\Core\Http\Middleware\CorsMiddleware::class,
                // Example: Ishmael\Core\Http\Middleware\JsonBodyParserMiddleware::class,
                // Enable sessions globally
                Ishmael\Core\Http\Middleware\StartSessionMiddleware::class,
                // Enable CSRF protection for state-changing requests by default
                Ishmael\Core\Http\Middleware\VerifyCsrfToken::class,
                // Apply standard Security Headers (CSP, XFO, XCTO, HSTS via config/security.php):
                // Ishmael\Core\Http\Middleware\SecurityHeaders::class,
                // To enable authentication primitives, bind RememberMe first to wire services and cookie handling:
                // Ishmael\Core\Http\Middleware\RememberMeMiddleware::class,
                // Then protect routes with Authenticate::class and gate guest-only with Guest::class
                // Ishmael\Core\Http\Middleware\Authenticate::class,
                // Ishmael\Core\Http\Middleware\Guest::class,
            ],
        ],
    ];
