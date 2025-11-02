<?php
    declare(strict_types=1);

    namespace Ishmael\Core;

    class Router
    {
        public function dispatch(string $uri): void
        {
            $uri = parse_url($uri, PHP_URL_PATH);
            $uri = trim($uri, '/');

            // 1. Check for custom module route definitions first
            foreach (ModuleManager::$modules as $moduleName => $moduleData) {
                foreach ($moduleData['routes'] as $pattern => $handler) {
                    if (preg_match("#^{$pattern}$#", $uri, $matches)) {
                        [$controller, $action] = explode('@', $handler);
                        $this->execute($moduleName, $controller, $action, array_slice($matches, 1));
                        return;
                    }
                }
            }

            // 2. Default convention-based routing
            $parts = $uri ? explode('/', $uri) : [];
            $module = $parts[0] ?? 'HelloWorld';
            $controller = $parts[1] ?? 'Home';
            $action = $parts[2] ?? 'index';
            $params = array_slice($parts, 3);

            $this->execute($module, $controller, $action, $params);
        }

        private function execute(string $module, string $controller, string $action, array $params): void
        {
            // Ensure controller name ends with "Controller"
            if (substr($controller, -10) !== 'Controller') {
                $controller .= 'Controller';
            }

            // Determine fully qualified class name
            if ($module === 'App') {
                $controllerClass = "App\\Controllers\\{$controller}";
            } else {
                $controllerClass = "Modules\\{$module}\\Controllers\\{$controller}";
            }

            // Check controller exists
            if (!class_exists($controllerClass)) {
                http_response_code(404);
                echo "Controller not found: {$controllerClass}";
                return;
            }

            $ctrl = new $controllerClass();

            // Check action exists
            if (!method_exists($ctrl, $action)) {
                http_response_code(404);
                echo "Action not found: {$action}";
                return;
            }

            // Call action
            call_user_func_array([$ctrl, $action], $params);
        }

    }
