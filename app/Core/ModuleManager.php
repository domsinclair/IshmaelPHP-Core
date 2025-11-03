<?php
    declare(strict_types=1);

    namespace Ishmael\Core;

    final class ModuleManager
    {
        public static array $modules = [];

        /**
         * Discover all modules within the provided path.
         * Each module must be a directory containing Controllers/, Models/, Views/, etc.
         */
        public static function discover(string $modulesPath): void
        {
            if (!is_dir($modulesPath)) {
                Logger::info("⚠️ Module path not found: {$modulesPath}");
                return;
            }

            foreach (glob($modulesPath . '/*', GLOB_ONLYDIR) as $moduleDir) {
                $moduleName = basename($moduleDir);
                [$routes, $routeClosure] = self::loadRoutesInfo($moduleDir);

                self::$modules[$moduleName] = [
                    'name'   => $moduleName,
                    'path'   => realpath($moduleDir),
                    'routes' => $routes,
                    'routeClosure' => $routeClosure,
                ];

                Logger::info("✅ Discovered module: {$moduleName} (routes: " . count($routes) . ")");
            }

            if (empty(self::$modules)) {
                Logger::info("⚠️ No modules discovered in {$modulesPath}");
            }
        }

        /**
         * Load the module's route definitions from routes.php, if present.
         * Supports BC array and a Closure that accepts a Router instance.
         * @return array{0: array, 1: ?\Closure}
         */
        private static function loadRoutesInfo(string $moduleDir): array
        {
            $routesFile = $moduleDir . '/routes.php';

            if (file_exists($routesFile)) {
                $result = require $routesFile;

                if (is_array($result)) {
                    return [$result, null];
                }
                if ($result instanceof \Closure) {
                    return [[], $result];
                }

                Logger::error("❌ Invalid routes.php in {$moduleDir} — must return an array or a Closure.");
            }

            return [[], null];
        }

        /**
         * Optional: Get a specific module's info.
         */
        public static function get(string $moduleName): ?array
        {
            return self::$modules[$moduleName] ?? null;
        }
    }
