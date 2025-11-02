<?php
    declare(strict_types=1);

    namespace Ishmael\Core;

    /**
     * Base Controller
     * ----------------
     * Provides common functionality for all controllers.
     * Handles view rendering, JSON responses, and shared helpers.
     */
    abstract class Controller
    {
        protected array $data = [];

        /**
         * Render a view file from within the module or app.
         */
        protected function render(string $view, array $vars = []): void
        {
            $moduleName = $this->getModuleName();
            $module = ModuleManager::get($moduleName);

            if (!$module) {
                http_response_code(500);
                echo "Module not found: {$moduleName}";
                return;
            }

            $viewPath = $module['path'] . '/Views/' . $view . '.php';

            if (!file_exists($viewPath)) {
                http_response_code(500);
                echo "View not found: {$viewPath}";
                return;
            }

            extract($vars, EXTR_SKIP);
            include $viewPath;
        }

        /**
         * Alias for render()
         */
        protected function view(string $view, array $vars = []): void
        {
            $this->render($view, $vars);
        }

        /**
         * Return JSON output (for API-style controllers)
         */
        protected function json(array $payload, int $status = 200): void
        {
            http_response_code($status);
            header('Content-Type: application/json');
            echo json_encode($payload, JSON_PRETTY_PRINT);
        }

        /**
         * Automatically determine module name based on namespace.
         */
        protected function getModuleName(): string
        {
            $class = static::class;
            if (preg_match('/^Modules\\\\([^\\\\]+)/', $class, $matches)) {
                return $matches[1];
            }
            return 'App';
        }
    }
