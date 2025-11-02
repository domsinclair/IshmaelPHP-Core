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
    ];
