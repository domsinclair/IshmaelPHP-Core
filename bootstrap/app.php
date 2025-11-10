<?php
declare(strict_types=1);

use Ishmael\Core\Logger;
use Ishmael\Core\ModuleManager;
use Ishmael\Core\Router;
use Psr\Log\LogLevel;

// --------------------------------------------------
// 1. Autoloading
// --------------------------------------------------
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// --------------------------------------------------
// 2. Helpers and Environment
// --------------------------------------------------
require_once __DIR__ . '/../app/Helpers/helpers.php';

// Load .env (idempotent via helpers)
load_env();

// --------------------------------------------------
// 3. Configuration (with config cache support)
// --------------------------------------------------
// Try to load merged config repository from cache
$debugFromEnv = (bool) (env('APP_DEBUG', true));
$dirs = \Ishmael\Core\ConfigCache::getDefaultDirs();
$compiledConfig = \Ishmael\Core\ConfigCache::load();
if (is_array($compiledConfig)) {
    $fresh = \Ishmael\Core\ConfigCache::isFresh($compiledConfig, $dirs);
    if ($fresh || !$debugFromEnv) {
        // Preload merged repository for helpers->config()
        app(['config_repo' => (array)($compiledConfig['config'] ?? [])]);
    }
}
// Fallback: helpers->config() will read individual files on demand if repo not set
$appConfig      = (array) (config('app') ?? (require __DIR__ . '/../config/app.php'));
$loggingConfig  = (array) (config('logging') ?? (require __DIR__ . '/../config/logging.php'));

// --------------------------------------------------
// 4. Logger Init (PSR-3 via LoggerManager)
// --------------------------------------------------
Logger::init($loggingConfig);
Logger::info('Bootstrapping Ishmael (Kernel v1)');

// --------------------------------------------------
// 4.1 Global Error/Exception/Shutdown Handlers (idempotent)
// --------------------------------------------------
$ishTesting = $_SERVER['ISH_TESTING'] ?? null;
if (!defined('ISH_ERROR_HANDLERS_REGISTERED') && !$ishTesting) {
    define('ISH_ERROR_HANDLERS_REGISTERED', true);

    // preserve existing handlers to respect test environments
    $previousErrorHandler = set_error_handler(function (int $errno, string $errstr, ?string $errfile = null, ?int $errline = null) use ($appConfig, &$previousErrorHandler): bool {
        // Respect error_reporting mask
        if (!(error_reporting() & $errno)) {
            return false; // allow PHP internal handling
        }

        $level = match (true) {
            ($errno & (E_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)) === $errno => LogLevel::ERROR,
            ($errno & (E_WARNING | E_USER_WARNING)) === $errno => LogLevel::WARNING,
            ($errno & (E_NOTICE | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED | E_STRICT)) === $errno => LogLevel::NOTICE,
            default => LogLevel::ERROR,
        };

        $context = [
            'type' => $errno,
            'file' => $errfile,
            'line' => $errline,
        ];
        // lightweight backtrace for debugging mode
        if (($appConfig['debug'] ?? false) === true) {
            $context['trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        Logger::log($level, $errstr, $context);

        // Chain to previous handler if it exists
        if (is_callable($previousErrorHandler)) {
            return (bool) $previousErrorHandler($errno, $errstr, (string)$errfile, (int)$errline);
        }
        // return false to allow PHP internal handler, but we already logged
        return false;
    });

    $previousExceptionHandler = set_exception_handler(function (Throwable $e) use ($appConfig, &$previousExceptionHandler): void {
        // Log as critical with stack trace and correlation id if available
        $rid = null;
        if (function_exists('app')) { $rid = app('request_id'); }
        if (!is_string($rid) || $rid === '') { $rid = bin2hex(random_bytes(8)); }
        $context = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
            'request_id' => $rid,
        ];
        Logger::log(LogLevel::CRITICAL, 'Uncaught exception: ' . $e->getMessage(), $context);

        // Render response based on debug flag with simple content negotiation (CLI-safe)
        if (php_sapi_name() !== 'cli') {
            http_response_code(500);
            $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? 'text/html'));
            $debug = ($appConfig['debug'] ?? false) === true;
            if (str_contains($accept, 'application/json')) {
                $payload = [
                    'error' => [
                        'id' => $rid,
                        'status' => 500,
                        'title' => 'Internal Server Error',
                        'detail' => $debug ? $e->getMessage() : 'An unexpected error occurred',
                    ],
                ];
                if ($debug) { $payload['error']['trace'] = $e->getTrace(); }
                header('Content-Type: application/json; charset=UTF-8', true);
                header('X-Correlation-Id: ' . $rid, true);
                echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                header('Content-Type: text/html; charset=UTF-8', true);
                header('X-Correlation-Id: ' . $rid, true);
                if ($debug) {
                    echo '<h1>Uncaught Exception</h1>';
                    echo '<p><strong>Correlation Id:</strong> ' . htmlspecialchars($rid) . '</p>';
                    echo '<pre>' . htmlspecialchars((string)$e) . '</pre>';
                } else {
                    echo '<h1>Internal Server Error</h1>';
                    echo '<p><strong>Correlation Id:</strong> ' . htmlspecialchars($rid) . '</p>';
                }
            }
        }

        // Chain to previous handler if any
        if (is_callable($previousExceptionHandler)) {
            $previousExceptionHandler($e);
        }
    });

    register_shutdown_function(function () use ($appConfig) {
        $error = error_get_last();
        if ($error === null) {
            return;
        }
        $fatalTypes = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;
        if (($error['type'] & $fatalTypes) !== 0) {
            Logger::log(LogLevel::CRITICAL, 'Fatal error: ' . $error['message'], [
                'type' => $error['type'] ?? null,
                'file' => $error['file'] ?? null,
                'line' => $error['line'] ?? null,
            ]);
            if (php_sapi_name() !== 'cli') {
                // Avoid sending output if headers already sent; best effort simple output
                if (!headers_sent()) {
                    http_response_code(500);
                }
                if (($appConfig['debug'] ?? false) === true) {
                    echo '<h1>Fatal Error</h1>';
                    echo '<pre>' . htmlspecialchars($error['message'] . "\n\n" . ($error['file'] ?? '') . ':' . ($error['line'] ?? '')) . '</pre>';
                } else {
                    echo '<h1>Internal Server Error</h1>';
                }
            }
        }
    });
}

// --------------------------------------------------
// 5. Module Discovery
// --------------------------------------------------
$modulesPath = $appConfig['paths']['modules'] ?? (function () {
    // Fallback to base_path('modules') if config structure changes
    if (function_exists('base_path')) {
        return base_path('modules');
    }
    return __DIR__ . '/../modules';
})();
ModuleManager::discover($modulesPath);

// --------------------------------------------------
// 5.1 Route cache warm/load (Phase-6 Milestone D)
// --------------------------------------------------
$router = new Router();
$cached = \Ishmael\Core\RouteCache::load();
if ($cached !== null) {
    // Validate freshness in dev; in production we trust the cache
    $isDebug = (bool)($appConfig['debug'] ?? false);
    $fresh = \Ishmael\Core\RouteCache::isFresh($cached, $modulesPath);
    if ($fresh || !$isDebug) {
        $router->loadCompiledMap($cached['routes']);
        \Ishmael\Core\Logger::info('Using cached routes' . ($fresh ? '' : ' (stale but used in production)'));
    } else {
        \Ishmael\Core\Logger::info('Route cache is stale; ignoring in debug mode.');
    }
}

// --------------------------------------------------
// 6. Dispatch Current Request
// --------------------------------------------------
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// If root, route to default module/controller/action using config defaults
if ($uri === '/' || $uri === '') {
    $defaultModule     = $appConfig['default_module'] ?? 'HelloWorld';
    $defaultController = $appConfig['default_controller'] ?? 'Home';
    $defaultAction     = $appConfig['default_action'] ?? 'index';
    $uri = "/{$defaultModule}/{$defaultController}/{$defaultAction}";
}

// Allow consumers to opt-in to "bootstrap only" mode by defining ISH_BOOTSTRAP_ONLY
if (!defined('ISH_BOOTSTRAP_ONLY') || ISH_BOOTSTRAP_ONLY !== true) {
    // If we did not load from cache, build routes now
    $router->buildRoutes();
    // Let exceptions bubble to global handler
    $router->dispatch($uri);
}
