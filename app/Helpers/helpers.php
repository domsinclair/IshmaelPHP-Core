<?php
    declare(strict_types=1);

    /**
     * -------------------------------------------------------------
     * Ishmael PHP - Core Helpers
     * -------------------------------------------------------------
     * Global helper functions to support framework internals.
     * Includes:
     *   - Path resolution
     *   - Logging
     *   - .env file loading and auto-creation
     *   - Config helpers
     * -------------------------------------------------------------
     */

    /**
     * Resolve base path relative to project root.
     */
    if (!function_exists('base_path')) {
        function base_path(string $path = ''): string
        {
            // Allow applications to override the framework base when installed as a vendor package
            // by defining ISH_APP_BASE in their front controller before including the bootstrap.
            $base = defined('ISH_APP_BASE') && ISH_APP_BASE ? (string)ISH_APP_BASE : dirname(__DIR__, 2);
            return $base . ($path ? DIRECTORY_SEPARATOR . $path : '');
        }
    }

    if (!function_exists('storage_path')) {
        function storage_path(string $path = ''): string
        {
            return base_path('storage') . ($path ? DIRECTORY_SEPARATOR . $path : '');
        }
    }

    /**
     * Simple JSON-safe logger.
     */
    if (!function_exists('log_message')) {
        function log_message(string $level, string $message): void
        {
            $logDir = base_path('storage/logs');
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logFile = $logDir . '/ishmael.log';
            $timestamp = date('Y-m-d H:i:s');
            $entry = sprintf("[%s] %s: %s\n", $timestamp, strtoupper($level), $message);
            file_put_contents($logFile, $entry, FILE_APPEND);
        }
    }

    /**
     * Automatically create a .env file with sensible defaults if missing.
     */
    if (!function_exists('ensure_env_file')) {
        function ensure_env_file(): void
        {
            $envPath = base_path('.env');

            // Already exists — do nothing
            if (file_exists($envPath)) {
                return;
            }

            // Default .env template
            $defaultEnv = <<<ENV
# -------------------------------------------------------------
# Ishmael Environment Configuration
# -------------------------------------------------------------
APP_NAME=Ishmael
APP_ENV=development
APP_DEBUG=true
APP_URL=http://ishmaelphp.test

# Database Configuration
DB_CONNECTION=sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=database.sqlite
DB_USERNAME=root
DB_PASSWORD=

# Logging Configuration
LOG_CHANNEL=stack
LOG_LEVEL=debug

# Created automatically by Ishmael Framework on first run
# -------------------------------------------------------------
ENV;

            // Write the file
            file_put_contents($envPath, $defaultEnv);

            // Ensure log directory exists
            $logDir = base_path('storage/logs');
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $message = "Created default .env file at {$envPath}";
            log_message('info', $message);
        }
    }

    /**
     * Simple service locator for early phases.
     */
    if (!function_exists('app')) {
        /**
         * Resolve or register services.
         * - app(FQCN|string): resolve service by key
         * - app(): returns array of all services (for debugging)
         * - app(['key' => $service, ...]): register one or more services
         * This is a minimal placeholder until a full container arrives.
         * @return mixed
         */
        function app($key = null)
        {
            static $services = [];
            if ($key === null) {
                return $services;
            }
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    $services[(string)$k] = $v;
                }
                return null;
            }
            $id = (string)$key;
            return $services[$id] ?? null;
        }
    }

    /**
     * Load .env file into global environment array.
     */
    if (!function_exists('load_env')) {
        function load_env(): array
        {
            static $env = null;

            // In testing, always reload .env to avoid stale cache between tests
            if (!($_SERVER['ISH_TESTING'] ?? null)) {
                if ($env !== null) {
                    return $env;
                }
            } else {
                $env = null; // force reload
            }

            ensure_env_file(); // ✅ ensure file exists first

            $env = [];
            $envFile = base_path('.env');

            if (!file_exists($envFile)) {
                log_message('error', '.env file missing and could not be created.');
                return [];
            }

            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                [$key, $value] = array_pad(explode('=', $line, 2), 2, null);
                if ($key !== null && $value !== null) {
                    $value = trim($value, "\"'"); // strip quotes
                    $env[$key] = $value;
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }

            return $env;
        }
    }

    /**
     * Retrieve environment variable with optional default.
     */
    if (!function_exists('env')) {
        function env(string $key, mixed $default = null): mixed
        {
            $env = load_env();
            return $env[$key] ?? $default;
        }
    }

    /**
     * Retrieve configuration values.
     */
    if (!function_exists('config')) {
        function config(string $key, mixed $default = null): mixed
        {
            static $configCache = [];

            // Split e.g. 'database.default'
            [$file, $item] = array_pad(explode('.', $key, 2), 2, null);
            if (!$file) {
                return $default;
            }

            if (!isset($configCache[$file])) {
                $path = base_path("config/{$file}.php");
                if (file_exists($path)) {
                    $configCache[$file] = require $path;
                } else {
                    log_message('warning', "Config file not found: {$path}");
                    $configCache[$file] = [];
                }
            }

            return $item ? ($configCache[$file][$item] ?? $default) : $configCache[$file];
        }
    }

    /**
     * Dump and die utility (for development).
     */
    if (!function_exists('dd')) {
        function dd(mixed ...$vars): void
        {
            foreach ($vars as $v) {
                echo "<pre>" . htmlspecialchars(print_r($v, true)) . "</pre>";
            }
            die();
        }
    }
