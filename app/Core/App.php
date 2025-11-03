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
        try {
            $this->router?->dispatch($uri);
        } catch (\Throwable $e) {
            Logger::error('Kernel handle exception: ' . $e->getMessage());
            http_response_code(500);
            echo '<h1>Internal Server Error</h1>';
            if (($this->config['debug'] ?? false) === true) {
                echo '<pre>' . htmlspecialchars((string)$e) . '</pre>';
            }
        }
        $body = ob_get_clean();
        // Ensure output buffer is balanced in case of errors
        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        $status = http_response_code() ?: 200;
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
