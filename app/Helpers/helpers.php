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
            // Determine base in a robust, environment-aware way
            $base = null;

            // 1) Explicit constant takes precedence
            if (defined('ISH_APP_BASE') && ISH_APP_BASE) {
                $base = (string) ISH_APP_BASE;
            }

            // 2) Environment/server variable fallback (allows phpunit.xml <server> config)
            if ($base === null) {
                $envBase = $_SERVER['ISH_APP_BASE'] ?? getenv('ISH_APP_BASE') ?: null;
                if ($envBase) {
                    // Resolve relative paths against current working directory
                    $base = realpath($envBase) ?: realpath(getcwd() . DIRECTORY_SEPARATOR . $envBase) ?: $envBase;
                }
            }

            // 3) In test mode, prefer repository core path if present (avoids vendor-shadow issues)
            if ($base === null && (($_SERVER['ISH_TESTING'] ?? null) === '1')) {
                $candidate = getcwd() . DIRECTORY_SEPARATOR . 'IshmaelPHP-Core';
                if (is_dir($candidate)) {
                    $base = realpath($candidate) ?: $candidate;
                }
            }

            // 4) Fallback to path relative to this helpers.php file (works for both core and vendor install)
            if ($base === null) {
                $base = dirname(__DIR__, 2);
            }

            return $base . ($path ? DIRECTORY_SEPARATOR . $path : '');
        }
    }

    if (!function_exists('storage_path')) {
        function storage_path(string $path = ''): string
        {
            $base = base_path('storage');
            if ($path === '') {
                return $base;
            }
            // Normalize provided subpath for Windows/Linux and avoid duplicate separators
            $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($path, "\\/"));
            return $base . DIRECTORY_SEPARATOR . $normalized;
        }
    }

    /**
     * Simple JSON-safe logger.
     */
    if (!function_exists('log_message')) {
        function log_message(string $level, string $message): void
        {
            $logDir = storage_path('logs');
            $createdDir = true;
            if (!is_dir($logDir)) {
                $createdDir = @mkdir($logDir, 0777, true);
            }

            $logFile = $logDir . DIRECTORY_SEPARATOR . 'ishmael.log';
            // Ensure the file exists before attempting to append (test relies on existence)
            $touched = @touch($logFile);
            $touchErr = $touched ? null : (function_exists('error_get_last') ? error_get_last() : null);
            clearstatcache(true, $logFile);

            $timestamp = date('Y-m-d H:i:s');
            $entry = sprintf("[%s] %s: %s\n", $timestamp, strtoupper($level), $message);

            // Append atomically; this will create the file if missing in most environments
            $written = @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
            $writeErr = (is_int($written)) ? null : (function_exists('error_get_last') ? error_get_last() : null);

            // In test mode, drop a tiny probe with diagnostics to help identify path resolution issues
            if (($_SERVER['ISH_TESTING'] ?? null) === '1') {
                $probe = $logDir . DIRECTORY_SEPARATOR . '__log_probe.txt';
                $diag = [
                    'base_path()' => base_path(),
                    'logDir' => $logDir,
                    'logFile' => $logFile,
                    'createdDir' => $createdDir ? 'yes' : 'no',
                    'touched' => $touched ? 'yes' : 'no',
                    'touchError' => $touchErr ? ($touchErr['message'] ?? json_encode($touchErr)) : null,
                    'writtenBytes' => is_int($written) ? (string)$written : 'false',
                    'writeError' => $writeErr ? ($writeErr['message'] ?? json_encode($writeErr)) : null,
                    'existsAfter' => file_exists($logFile) ? 'yes' : 'no',
                ];
                @file_put_contents($probe, json_encode($diag, JSON_PRETTY_PRINT));
            }
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

            // Allow bootstrap to provide a preloaded config repository
            $preloaded = app('config_repo');
            if (is_array($preloaded)) {
                // Look up directly from merged repository
                [$file, $item] = array_pad(explode('.', $key, 2), 2, null);
                if (!$file) {
                    return $default;
                }
                if (array_key_exists($file, $preloaded)) {
                    return $item ? ($preloaded[$file][$item] ?? $default) : $preloaded[$file];
                }
                return $default;
            }

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
     * Global session helper.
     * - session(): returns SessionManager instance
     * - session('key', $default): get value
     * - session(['key' => 'value']): set value(s)
     *
     * @return mixed
     */
    if (!function_exists('session')) {
        function session($key = null, $default = null)
        {
            /** @var \Ishmael\Core\Session\SessionManager|null $mgr */
            $mgr = app('session');
            if ($mgr === null) {
                return null;
            }
            if ($key === null) {
                return $mgr;
            }
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    $mgr->put((string)$k, $v);
                }
                return null;
            }
            return $mgr->get((string)$key, $default);
        }
    }

    /**
     * Flash message helper: flash('key', 'value') to store for next request; flash('key') to read current.
     */
    if (!function_exists('flash')) {
        function flash(string $key, mixed $value = null): mixed
        {
            /** @var \Ishmael\Core\Session\SessionManager|null $mgr */
            $mgr = app('session');
            if ($mgr === null) {
                return null;
            }
            if (func_num_args() === 1) {
                return $mgr->getFlash($key);
            }
            $mgr->flash($key, $value);
            return null;
        }
    }

    /**
     * back() helper returns the Referer header or a safe fallback.
     */
    if (!function_exists('back')) {
        function back(string $fallback = '/'): string
        {
            $ref = $_SERVER['HTTP_REFERER'] ?? '';
            if (is_string($ref) && $ref !== '') {
                return $ref;
            }
            return $fallback;
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

    /**
     * HTML-escape a value for safe output in views.
     *
     * @param mixed $value Value to escape
     * @return string Escaped string
     */
    if (!function_exists('e')) {
        function e(mixed $value): string
        {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Generate a URL for a named route.
     * Thin wrapper around Router::url() for convenience in views.
     *
     * @param string $name Route name
     * @param array<string,mixed> $params Path parameters
     * @param array<string,mixed> $query Query parameters (optional)
     * @param bool $absolute Include scheme/host when true (default false)
     * @return string URL
     */
    if (!function_exists('route')) {
        function route(string $name, array $params = [], array $query = [], bool $absolute = false): string
        {
            return \Ishmael\Core\Router::url($name, $params, $query, $absolute);
        }
    }

    /**
     * Return the current CSRF token for this session (generating one if missing).
     * Requires StartSessionMiddleware to be active in the pipeline or a session
     * manager to be registered manually via app('session').
     *
     * @return string CSRF token string
     */
    if (!function_exists('csrfToken')) {
        function csrfToken(): string
        {
            $manager = new \Ishmael\Core\Security\CsrfTokenManager();
            return $manager->getToken();
        }
    }

    /**
     * Render a hidden input field carrying the CSRF token for HTML forms.
     *
     * @return string HTML input element markup
     */
    if (!function_exists('csrfField')) {
        function csrfField(): string
        {
            $cfg = (array)(config('security.csrf') ?? []);
            $name = (string)($cfg['field_name'] ?? '_token');
            $token = csrfToken();
            $nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $tokenEsc = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
            return '<input type="hidden" name="' . $nameEsc . '" value="' . $tokenEsc . '">';
        }
    }

    /**
     * Render a <meta name="csrf-token" content="..."> tag for use by JavaScript/XHR.
     *
     * @return string HTML meta element markup
     */
    if (!function_exists('csrfMeta')) {
        function csrfMeta(): string
        {
            $token = csrfToken();
            $tokenEsc = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
            return '<meta name="csrf-token" content="' . $tokenEsc . '">';
        }
    }

    /**
     * Tiny flash message helper using PHP sessions.
     *
     * Behavior:
     * - Setter: flash('key', 'value') stores a one-time message in $_SESSION['flash'].
     * - Getter: flash('key') returns and clears the message if present, otherwise null.
     * - Array setters are allowed to set multiple keys at once.
     *
     * @param string $key Flash key (e.g., 'success', 'error')
     * @param mixed $value Optional value to set; when null acts as getter.
     * @return mixed|null Returns value on get; null if absent.
     */
    if (!function_exists('flash')) {
        function flash(string $key, $value = null)
        {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
            if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
                $_SESSION['flash'] = [];
            }
            if ($value !== null) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $_SESSION['flash'][(string)$k] = $v;
                    }
                    return null;
                }
                $_SESSION['flash'][$key] = $value;
                return null;
            }
            $val = $_SESSION['flash'][$key] ?? null;
            if (array_key_exists($key, $_SESSION['flash'])) {
                unset($_SESSION['flash'][$key]);
            }
            return $val;
        }
    }

    /**
     * Resolve the password hasher service.
     * @return \Ishmael\Core\Auth\HasherInterface
     */
    if (!function_exists('hasher')) {
        function hasher(): \Ishmael\Core\Auth\HasherInterface
        {
            $h = app('hasher');
            if ($h instanceof \Ishmael\Core\Auth\HasherInterface) {
                return $h;
            }
            $h = new \Ishmael\Core\Auth\PhpPasswordHasher();
            app(['hasher' => $h]);
            return $h;
        }
    }

    /**
     * Resolve the authentication manager.
     * @return \Ishmael\Core\Auth\AuthManager
     */
    if (!function_exists('auth')) {
        function auth(): \Ishmael\Core\Auth\AuthManager
        {
            $a = app('auth');
            if ($a instanceof \Ishmael\Core\Auth\AuthManager) {
                return $a;
            }
            // Ensure user provider as well
            if (!(app('user_provider') instanceof \Ishmael\Core\Auth\UserProviderInterface)) {
                app(['user_provider' => new \Ishmael\Core\Auth\DatabaseUserProvider()]);
            }
            $a = new \Ishmael\Core\Auth\AuthManager();
            app(['auth' => $a]);
            return $a;
        }
    }

    /**
     * Validate the current request input using the Validator and return sanitized data.
     * Throws ValidationException on failure.
     *
     * @param array<string,string|array<int,string>> $rules
     * @return array<string,mixed>
     * @throws \Ishmael\Core\Validation\ValidationException
     */
    if (!function_exists('validate')) {
        function validate(array $rules, ?\Ishmael\Core\Http\Request $request = null): array
        {
            $v = new \Ishmael\Core\Validation\Validator();
            return $v->validateRequest($rules, $request);
        }
    }

    /**
     * Resolve the authorization gate service.
     * @return \Ishmael\Core\Authz\Gate
     */
    if (!function_exists('gate')) {
        function gate(): \Ishmael\Core\Authz\Gate
        {
            $g = app('gate');
            if ($g instanceof \Ishmael\Core\Authz\Gate) {
                return $g;
            }
            $g = new \Ishmael\Core\Authz\Gate();
            app(['gate' => $g]);
            return $g;
        }
    }

    /**
     * Authorize an ability for the current user, throwing on denial.
     * @throws \Ishmael\Core\Authz\AuthorizationException
     */
    if (!function_exists('authorize')) {
        function authorize(string $ability, mixed $resource = null, string $message = 'Forbidden'): void
        {
            gate()->authorize($ability, $resource, $message);
        }
    }


    /**
     * Cache helper returns the CacheManager singleton.
     */
    if (!function_exists('cache')) {
        function cache(): \Ishmael\Core\Cache\CacheManager
        {
            return \Ishmael\Core\Cache\CacheManager::instance();
        }
    }
