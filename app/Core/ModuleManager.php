<?php

    declare(strict_types=1);

namespace Ishmael\Core;

final class ModuleManager
{
    public static array $modules = [];
/**
     * Simple cache payload of last discovery. File path optional.
     * @var string|null
     */
    private static ?string $cachePath = null;
/**
     * Discover all modules within the provided path.
     * Each module must be a directory containing Controllers/, Models/, Views/, etc.
     *
     * Environment-aware behavior (Phase 11 — Milestone 2):
     * - Filters modules by their declared env in manifest (module.php preferred; module.json supported).
     * - APP_ENV=production: include production + shared; exclude development unless ALLOW_DEV_MODULES=true.
     * - APP_ENV=development or testing: include all.
     * - Logs a warning when a development module is present in production without explicit override.
     *
     * @param string $modulesPath Root directory containing module folders.
     * @param array<string,mixed> $options Optional options: [
     *   'appEnv' => 'production'|'development'|'testing',
     *   'appDebug' => bool,
     *   'allowDevModules' => bool,
     *   'useCache' => bool, // attempt to load from cache if available
     *   'cachePath' => string|null, // where to read/write cache JSON
     * ]
     */
    public static function discover(string $modulesPath, array $options = []): void
    {
        $appEnv = ($options['appEnv'] ?? getenv('APP_ENV') ?: 'development');
        $appDebug = (bool)($options['appDebug'] ?? (getenv('APP_DEBUG') ?: false));
        $allowDev = (bool)($options['allowDevModules'] ?? (getenv('ALLOW_DEV_MODULES') ?: false));
        $useCache = (bool)($options['useCache'] ?? false);
        self::$cachePath = $options['cachePath'] ?? null;
        if (!is_dir($modulesPath)) {
            Logger::info("⚠️ Module path not found: {$modulesPath}");
            return;
        }

        // Optional: Attempt to load discovery from cache
        if ($useCache && self::$cachePath && is_file(self::$cachePath)) {
            $cached = json_decode((string)file_get_contents(self::$cachePath), true);
            if (is_array($cached)) {
                self::$modules = $cached;
                Logger::info('✅ Loaded modules from cache (' . basename(self::$cachePath) . ')');
                return;
            }
        }

        foreach (glob($modulesPath . '/*', GLOB_ONLYDIR) as $moduleDir) {
            $moduleName = basename($moduleDir);
            $manifest = self::loadManifest($moduleDir);
            $moduleEnv = $manifest['env'] ?? 'shared';
            $enabled = $manifest['enabled'] ?? true;
            if (!$enabled) {
                Logger::info("⏭️ Skipping disabled module: {$moduleName}");
                continue;
            }

            if (!self::shouldLoad($moduleEnv, (string)$appEnv, $allowDev)) {
                if ($moduleEnv === 'development' && $appEnv === 'production' && !$allowDev) {
                    Logger::info("⚠️ Skipping development module in production without override: {$moduleName}");
                }
                continue;
            }

            [$routes, $routeClosure] = self::loadRoutesInfo($moduleDir);
            self::$modules[$moduleName] = [
                'name'   => $moduleName,
                'path'   => realpath($moduleDir),
                'env'    => $moduleEnv,
                'manifest' => $manifest,
                // Preview for future Event Bus: surface hooks as-is from manifest (no execution yet)
                'hooks'  => isset($manifest['hooks']) && is_array($manifest['hooks']) ? $manifest['hooks'] : [],
                // Preview for future SchemaManager: surface schema pointer/metadata as-is
                'schema' => $manifest['schema'] ?? null,
                'routes' => $routes,
                'routeClosure' => $routeClosure,
            ];
            Logger::info("✅ Discovered module: {$moduleName} (routes: " . count($routes) . ")");
        }

        if (empty(self::$modules)) {
            Logger::info("⚠️ No modules discovered in {$modulesPath}");
        }

        // Optional: write cache snapshot
        if (self::$cachePath) {
            @file_put_contents(self::$cachePath, json_encode(self::$modules, JSON_PRETTY_PRINT));
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
     * Load a module manifest, preferring module.php over module.json.
     * Returns an associative array of manifest values.
     *
     * @param string $moduleDir Module directory path
     * @return array<string,mixed>
     */
    private static function loadManifest(string $moduleDir): array
    {
        $phpManifest = $moduleDir . DIRECTORY_SEPARATOR . 'module.php';
        $jsonManifest = $moduleDir . DIRECTORY_SEPARATOR . 'module.json';
        if (is_file($phpManifest)) {
            $data = require $phpManifest;
            if (is_array($data)) {
                return $data;
            }
            Logger::error("❌ Invalid module.php in {$moduleDir} — it must return an array.");
            return [];
        }

        if (is_file($jsonManifest)) {
            $raw = file_get_contents($jsonManifest);
            $data = $raw !== false ? json_decode($raw, true) : null;
            if (is_array($data)) {
                return $data;
            }
            Logger::error("❌ Invalid module.json in {$moduleDir} — JSON must decode to an object.");
            return [];
        }

        // Default manifest when none found
        return [
            'name' => basename($moduleDir),
            'env' => 'shared',
            'enabled' => true,
        ];
    }

    /**
     * Decide if a module with a given env should load under the current app environment.
     *
     * Rules:
     * - production: allow only production + shared, unless allowDevModules=true which also permits development.
     * - development/testing: allow all envs.
     *
     * @param string $moduleEnv development|shared|production
     * @param string $appEnv production|development|testing
     * @param bool $allowDevModules Whether to include development modules in production
     */
    public static function shouldLoad(string $moduleEnv, string $appEnv, bool $allowDevModules): bool
    {
        $moduleEnv = strtolower($moduleEnv);
        $appEnv = strtolower($appEnv);
        if ($appEnv === 'production') {
            if ($moduleEnv === 'development') {
                return $allowDevModules;
            }
            // shared and production
            return in_array($moduleEnv, ['shared', 'production'], true);
        }

        // development or testing include all
        return in_array($moduleEnv, ['development', 'shared', 'production'], true);
    }

    /**
     * Write a simple JSON cache file of discovered modules.
     * @param string $cachePath Target cache file path.
     */
    public static function writeCache(string $cachePath): void
    {
        self::$cachePath = $cachePath;
        @file_put_contents($cachePath, json_encode(self::$modules, JSON_PRETTY_PRINT));
    }

    /**
     * Clear the modules cache file if it exists.
     * @param string $cachePath Cache file path.
     */
    public static function clearCache(string $cachePath): void
    {
        if (is_file($cachePath)) {
            @unlink($cachePath);
            Logger::info('🧹 Cleared modules cache');
        }
    }

    /**
     * Optional: Get a specific module's info.
     */
    public static function get(string $moduleName): ?array
    {
        return self::$modules[$moduleName] ?? null;
    }
}
