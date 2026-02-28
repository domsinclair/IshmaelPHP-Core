<?php

declare(strict_types=1);

namespace Ishmael\Core;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Events\Core\ApplicationBooted;
use Ishmael\Core\Events\Core\RequestFailed;

/**
 * Kernel v1 - tiny application wrapper responsible for bootstrapping
 * and handling a request using the current Router.
 */
final class App
{
    private bool $booted = false;
    private ?Router $router = null;
    private array $config = [];
    /**
     * Boot the application (idempotent).
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Ensure helpers available (composer autoload already includes them via files autoload)
        if (!\function_exists('load_env')) {
            require_once dirname(__DIR__) . '/Helpers/helpers.php';
        }

        // Load environment and core config
        load_env();
        if (empty($this->config)) {
            $this->config = require base_path('config/app.php');
        }
// Normalize debug flag to boolean in case env returns string values
        if (array_key_exists('debug', $this->config)) {
            $this->config['debug'] = filter_var((string)$this->config['debug'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($this->config['debug'] === null) {
                $this->config['debug'] = (bool)$this->config['debug'];
            }
        }
        $logging = require base_path('config/logging.php');
// Initialize logger with a safe default if config incomplete
        $loggerCfg = $logging['channels']['single'] ?? ['path' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_logs' . DIRECTORY_SEPARATOR . 'app.log'];
        Logger::init($loggerCfg);
        Logger::info('Kernel booting');

        // Prepare config repo if not set
        if (!app('config_repo')) {
            app(['config_repo' => $this->config]);
        }

        // Discover modules once
        $modulesPath = $this->config['paths']['modules'] ?? base_path('modules');
        ModuleManager::discover($modulesPath);

        // Initialize container and register module services
        $container = new Container();

        // Initialize Event Bus and register in container
        $dispatcher = new \Ishmael\Core\Events\Dispatcher($container);
        \Ishmael\Core\Event::setInstance($dispatcher);
        $container->bind(\Ishmael\Core\Events\EventBusInterface::class, $dispatcher);

        $bootStart = defined('ISH_START') ? ISH_START : microtime(true);

        foreach (ModuleManager::$modules as $module) {
            $services = $module['manifest']['services'] ?? [];
            foreach ($services as $abstract => $concrete) {
                $container->bind($abstract, $concrete);
            }

            // Register event listeners from manifest
            $listeners = $module['manifest']['listeners'] ?? [];
            foreach ($listeners as $eventName => $eventListeners) {
                if (is_array($eventListeners)) {
                    foreach ($eventListeners as $listener) {
                        $dispatcher->subscribe($eventName, $listener);
                    }
                } else {
                    $dispatcher->subscribe($eventName, $eventListeners);
                }
            }
        }

        // Prepare router
        $this->router = new Router();
        $this->router->setContainer($container);
// Set active router for static facade usage in route files
        Router::setActive($this->router);
// Apply global middleware from config if provided
        $httpCfg = $this->config['http'] ?? [];
        $globalStack = $httpCfg['middleware'] ?? null;
        if (is_array($globalStack) && !empty($globalStack)) {
            $this->router->setGlobalMiddleware($globalStack);
        }

        $this->booted = true;
        $bootTime = (microtime(true) - $bootStart) * 1000;
        Event::dispatch(new ApplicationBooted($bootTime));
    }

    /**
     * Handle a request and return a response.
     */
    public function handle(Request $request): Response
    {
        $this->boot();
// Dispatch via the existing Router which echoes output.
        // Capture output and status code to build a Response value object.
        $uri = $request->getUri();
        $level = ob_get_level();
        ob_start();
        $thrown = null;
        try {
            $this->router?->dispatch($uri);
        } catch (\Throwable $e) {
            $thrown = $e;
            Event::dispatch(new RequestFailed($e, $request));
            Logger::error('Kernel handle exception: ' . $e->getMessage());
            // Build an error response
            $debug = ($this->config['debug'] ?? false) === true;
            $err = Response::fromThrowable($e, $debug);
            // Use the error body to avoid duplication or mixed content.
            // We set it here, and it will be captured by the while-loop below if needed,
            // but since we want to DISCARD the existing buffer, we'll handle it carefully.
            $status = $err->getStatusCode();
            $body = $err->getBody();

            // Clean up any nested buffers before returning the error response
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            return new Response($body, $status);
        }
        $body = ob_get_clean();
// Ensure output buffer is balanced in case of errors
        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        // Prefer status from Router's last Response when available (CLI-safe)
        $status = 200;
        if ($thrown instanceof \Throwable) {
            $status = 500;
        } elseif ($this->router) {
            $last = $this->router->getLastResponse();
            if ($last instanceof Response) {
                $status = $last->getStatusCode();
            } else {
                $status = http_response_code() ?: 200;
            }
        } else {
            $status = http_response_code() ?: 200;
        }
        return new Response($body, $status);
    }

    /**
     * Termination hook for post-response tasks.
     */
    public function terminate(Request $request, Response $response): void
    {
        Event::dispatch('app.terminate', ['request' => $request, 'response' => $response]);
    }
}
