<?php
    declare(strict_types=1);

    use Ishmael\Core\{
        Logger,
        Database,
        ModuleManager,
        Router
    };

// --------------------------------------------------
// 1. Autoloading
// --------------------------------------------------
    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
    $coreAutoload     = __DIR__ . '/../core/Autoloader.php';

    if (file_exists($composerAutoload)) {
        require $composerAutoload;
    } elseif (file_exists($coreAutoload)) {
        require $coreAutoload;
    } else {
        die('Autoloader not found. Please run `composer install`.');
    }

// --------------------------------------------------
// 2. Helpers and Config
// --------------------------------------------------
    require __DIR__ . '/../core/Helpers/helpers.php';

    $appConfig      = require __DIR__ . '/../config/app.php';
    $databaseConfig = require __DIR__ . '/../config/database.php';
    $loggingConfig  = require __DIR__ . '/../config/logging.php';

// --------------------------------------------------
// 3. Core Initialisation
// --------------------------------------------------
    Logger::init($loggingConfig);
    Logger::info("🚀 Ishmael starting up...");

    Database::init($databaseConfig);
    Logger::info("✅ Database initialised");

    $modulesPath = base_path('modules');
    ModuleManager::discover($modulesPath);
    Logger::info("✅ Modules discovered: " . count(ModuleManager::$modules));

// --------------------------------------------------
// 4. Handle root / redirect to default home route
// --------------------------------------------------
    $uri = $_SERVER['REQUEST_URI'];

// If visiting root, redirect internally to default module/controller/action
    if ($uri === '/' || $uri === '') {
        $defaultModule     = $appConfig['default_module'] ?? 'HelloWorld';
        $defaultController = $appConfig['default_controller'] ?? 'HomeController';
        $defaultAction     = $appConfig['default_action'] ?? 'index';

        // Build internal URI for the router
        $uri = "/{$defaultModule}/{$defaultController}/{$defaultAction}";
    }

// --------------------------------------------------
// 5. Routing
// --------------------------------------------------
    try {
        $router = new Router();
        $router->dispatch($uri);
    } catch (Throwable $e) {
        Logger::error('Unhandled Exception: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        http_response_code(500);
        echo "<h1>Internal Server Error</h1>";
        if ($appConfig['debug'] ?? false) {
            echo "<pre>{$e}</pre>";
        }
    }

// --------------------------------------------------
// 6. End of Request
// --------------------------------------------------
    Logger::info("🏁 Request finished");
