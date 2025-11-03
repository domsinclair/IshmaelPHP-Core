<?php
    declare(strict_types=1);

    /**
     * -------------------------------------------------------------
     * Logging Configuration
     * -------------------------------------------------------------
     * Controls how Ishmael writes logs.
     * By default, logs go to storage/logs/ishmael.log
     * with JSON formatting for structured logging.
     */

    return [
        'default' => env('LOG_CHANNEL', 'stack'),

        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['single'],
            ],

            'single' => [
                'driver' => 'single',
                'path'   => storage_path('logs/ishmael.log'),
                'level'  => env('LOG_LEVEL', 'debug'),
                'format' => 'json',
            ],

            'daily' => [
                'driver' => 'daily',
                'path'   => storage_path('logs/ishmael.log'),
                'days'   => 7,
                'level'  => env('LOG_LEVEL', 'info'),
            ],

            // Monolog bridge channel. Enable by setting LOG_CHANNEL=monolog
            'monolog' => [
                'driver' => 'monolog',
                // Supported handlers: stream, rotating_file, error_log, syslog
                'handler' => env('MONOLOG_HANDLER', 'stream'),
                // For stream/rotating_file handlers
                'path' => storage_path('logs/ishmael.log'),
                // For rotating_file handler
                'days' => 7,
                // Syslog options
                'ident' => env('MONOLOG_SYSLOG_IDENT', 'ishmael'),
                // Minimum level for handler
                'level' => env('LOG_LEVEL', 'debug'),
            ],
        ],
    ];
