<?php
declare(strict_types=1);

namespace Ishmael\Core;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

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
        $this->config = require base_path('config/app.php');
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

        // Discover modules once
        $modulesPath = $this->config['paths']['modules'] ?? base_path('modules');
        ModuleManager::discover($modulesPath);

        // Prepare router
        $this->router = new Router();
        // Set active router for static facade usage in route files
        Router::setActive($this->router);
        
        // Apply global middleware from config if provided
        $httpCfg = $this->config['http'] ?? [];
        $globalStack = $httpCfg['middleware'] ?? null;
        if (is_array($globalStack) && !empty($globalStack)) {
            $this->router->setGlobalMiddleware($globalStack);
        }

        $this->booted = true;
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
            Logger::error('Kernel handle exception: ' . $e->getMessage());
            // Build an error response instead of echoing directly
            $debug = ($this->config['debug'] ?? false) === true;
            $err = Response::fromThrowable($e, $debug);
            http_response_code($err->getStatusCode());
            echo $err->getBody();
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
        } elseif ($this->router && method_exists($this->router, 'getLastResponse')) {
            $last = $this->router->getLastResponse();
            if ($last instanceof Response) {
                $status = $last->getStatusCode();
            } else {
                $status = http_response_code() ?: 200;
            }
        } else {
            $status = http_response_code() ?: 200;
        }
        return new Response($body ?? '', $status);
    }

    /**
     * Termination hook for post-response tasks (no-op initially).
     */
    public function terminate(Request $request, Response $response): void
    {
        // No-op for now; reserved for logging/cleanup in future phases.
    }
}
