<?php
declare(strict_types=1);

/**
 * -------------------------------------------------------------
 * Authentication Configuration
 * -------------------------------------------------------------
 */

return [
    'defaults' => [
        'provider' => 'users',
    ],

    'providers' => [
        // Default database-backed provider
        'users' => [
            'driver' => 'database',
            'table' => env('AUTH_USERS_TABLE', 'users'),
            'id_column' => env('AUTH_ID_COLUMN', 'id'),
            'username_column' => env('AUTH_USERNAME_COLUMN', 'email'),
            'password_column' => env('AUTH_PASSWORD_COLUMN', 'password'),
        ],
    ],

    'passwords' => [
        // bcrypt | argon2i | argon2id
        'algo' => env('AUTH_HASH_ALGO', 'bcrypt'),
        'cost' => (int) env('AUTH_BCRYPT_COST', 12),
        'memory_cost' => (int) env('AUTH_ARGON2_MEMORY', 1 << 17),
        'time_cost' => (int) env('AUTH_ARGON2_TIME', 4),
        'threads' => (int) env('AUTH_ARGON2_THREADS', 2),
    ],

    'redirects' => [
        // Used by middleware for HTML flows
        'login' => env('AUTH_LOGIN_PATH', '/login'),
        'home' => env('AUTH_HOME_PATH', '/'),
    ],

    'policies' => [
            // 'App\\Models\\Post' => App\\Policies\\PostPolicy::class,
        ],

        'remember_me' => [
        'enabled' => (bool) env('AUTH_REMEMBER_ENABLED', true),
        'cookie' => env('AUTH_REMEMBER_COOKIE', 'ish_remember'),
        // TTL in minutes
        'ttl' => (int) env('AUTH_REMEMBER_TTL', 43200), // 30 days
        // Bind token to user agent for extra safety
        'bind_user_agent' => (bool) env('AUTH_REMEMBER_BIND_UA', true),
        // Same cookie flags as session by default
        'path' => env('AUTH_REMEMBER_PATH', env('SESSION_PATH', '/')),
        'domain' => env('AUTH_REMEMBER_DOMAIN', env('SESSION_DOMAIN', '')),
        'secure' => (bool) env('AUTH_REMEMBER_SECURE', env('SESSION_SECURE', false)),
        'http_only' => (bool) env('AUTH_REMEMBER_HTTP_ONLY', env('SESSION_HTTP_ONLY', true)),
        'same_site' => env('AUTH_REMEMBER_SAME_SITE', env('SESSION_SAME_SITE', 'Lax')),
    ],
];
