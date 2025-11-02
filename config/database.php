<?php
    declare(strict_types=1);

    /**
     * -------------------------------------------------------------
     * Database Configuration
     * -------------------------------------------------------------
     * Ishmael supports multiple database engines via adapters:
     * SQLite, MySQL, PostgreSQL (more can be added easily).
     */

    return [
        'default' => env('DB_CONNECTION', 'sqlite'),

        'connections' => [
            'sqlite' => [
                'driver'   => 'sqlite',
                'database' => env('DB_DATABASE', base_path('storage/database.sqlite')),
            ],

            'mysql' => [
                'driver'   => 'mysql',
                'host'     => env('DB_HOST', '127.0.0.1'),
                'port'     => env('DB_PORT', 3306),
                'database' => env('DB_DATABASE', 'ishmael'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset'  => 'utf8mb4',
                'collation'=> 'utf8mb4_unicode_ci',
            ],

            'pgsql' => [
                'driver'   => 'pgsql',
                'host'     => env('DB_HOST', '127.0.0.1'),
                'port'     => env('DB_PORT', 5432),
                'database' => env('DB_DATABASE', 'ishmael'),
                'username' => env('DB_USERNAME', 'postgres'),
                'password' => env('DB_PASSWORD', ''),
                'charset'  => 'utf8',
            ],
        ],
    ];
