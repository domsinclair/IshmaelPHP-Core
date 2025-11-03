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

    $testMode = (($_SERVER['ISH_TESTING'] ?? null) === '1');
    $psrPath = $testMode
        ? (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_logs_tests' . DIRECTORY_SEPARATOR . 'app.psr.log')
        : storage_path('logs/ishmael.log');

    return [
        'default' => env('LOG_CHANNEL', 'stack'),

        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['single'],
            ],

            'single' => [
                'driver' => 'single',
                'path'   => $psrPath,
                'level'  => env('LOG_LEVEL', 'debug'),
                'format' => 'json',
            ],

            'daily' => [
                'driver' => 'daily',
                'path'   => $psrPath,
                'days'   => 7,
                'level'  => env('LOG_LEVEL', 'info'),
            ],

            // Monolog bridge channel. Enable by setting LOG_CHANNEL=monolog
            'monolog' => [
                'driver' => 'monolog',
                // Supported handlers: stream, rotating_file, error_log, syslog
                'handler' => env('MONOLOG_HANDLER', 'stream'),
                // For stream/rotating_file handlers
                'path' => $psrPath,
                // For rotating_file handler
                'days' => 7,
                // Syslog options
                'ident' => env('MONOLOG_SYSLOG_IDENT', 'ishmael'),
                // Minimum level for handler
                'level' => env('LOG_LEVEL', 'debug'),
            ],
        ],
    ];
