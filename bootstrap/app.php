<?php
declare(strict_types=1);

use Ishmael\Core\Logger;
use Ishmael\Core\ModuleManager;
use Ishmael\Core\Router;

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
// 3. Configuration
// --------------------------------------------------
$appConfig      = require __DIR__ . '/../config/app.php';
$loggingConfig  = require __DIR__ . '/../config/logging.php';

// --------------------------------------------------
// 4. Logger Init (PSR-3 via LoggerManager)
// --------------------------------------------------
Logger::init($loggingConfig);
Logger::info('Bootstrapping Ishmael (Kernel v1)');

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
    try {
        $router = new Router();
        $router->dispatch($uri);
    } catch (Throwable $e) {
        Logger::error('Unhandled Exception during bootstrap dispatch: ' . $e->getMessage());
        http_response_code(500);
        echo '<h1>Internal Server Error</h1>';
        if (($appConfig['debug'] ?? false) === true) {
            echo '<pre>' . htmlspecialchars((string)$e) . '</pre>';
        }
    }
}
