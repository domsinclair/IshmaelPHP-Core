<?php

declare(strict_types=1);

namespace Ishmael\Core;

/**
 * RouteCache compiles and loads a deterministic, optimized map of routes for fast production boot.
 *
 * Cache format (PHP file returning array):
 * [
 *   'meta' => [
 *     'hash' => 'sha1-of-route-sources',
 *     'generatedAt' => 'YYYY-mm-dd HH:ii:ss',
 *   ],
 *   'routes' => array<int, array{methods:string[], regex:string, paramNames:string[], paramTypes:string[], handler:mixed, middleware:array, pattern:string, module?:string, name?:string}>
 * ]
 */
final class RouteCache
{
    /**
     * Compute a stable hash across all module route source files.
     *
     * @param string $modulesPath Path to modules directory.
     * @return array{hash:string, sources:array<int, string>} Hash and list of discovered route files used.
     */
    public static function computeSourceHash(string $modulesPath): array
    {
        $files = [];
        if (is_dir($modulesPath)) {
            foreach (glob(rtrim($modulesPath, "\\/") . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $moduleDir) {
                $routes = $moduleDir . DIRECTORY_SEPARATOR . 'routes.php';
                if (is_file($routes)) {
                    $files[] = realpath($routes) ?: $routes;
                }
            }
        }
        sort($files);
        $h = hash_init('sha1');
        foreach ($files as $f) {
            $mtime = @filemtime($f) ?: 0;
            $size  = @filesize($f) ?: 0;
            $data  = @file_get_contents($f) ?: '';
            hash_update($h, $f . "\n" . $mtime . "\n" . $size . "\n" . $data);
        }
        $hash = hash_final($h);
        return ['hash' => $hash, 'sources' => $files];
    }

    /**
     * Determine whether a value is a cacheable callable representation.
     * Only class strings and static callables [ClassName, method] are cacheable.
     */
    private static function isCacheableCallable(mixed $value): bool
    {
        if (is_string($value)) {
// Class string or function name (function names discouraged but cacheable)
            return true;
        }
        if (is_array($value) && count($value) === 2 && is_string($value[0]) && is_string($value[1])) {
// Static callable like [ClassName::class, 'method']
            return true;
        }
        return false;
// closures, objects, bound callables are not cacheable
    }

    /**
     * Validate routes before caching and optionally build warnings.
     *
     * @param array<int,array> $routes
     * @return array{routes: array<int,array>, warnings: string[]}
     */
    private static function validateForCache(array $routes): array
    {
        $warnings = [];
        foreach ($routes as $i => $r) {
        // Middleware entries
            foreach (($r['middleware'] ?? []) as $mwIndex => $mw) {
                if (!self::isCacheableCallable($mw)) {
                    $pattern = '/' . trim((string)($r['pattern'] ?? ''), '/');
                    $module = (string)($r['module'] ?? '');
                    $warnings[] = "Non-cacheable middleware (closure/object) on route {$pattern} [module={$module}] index {$mwIndex}";
                }
            }
            // Handler (if non-string handler is supported by router)
            if (array_key_exists('handler', $r)) {
                $h = $r['handler'];
// Allow array handlers (controller/action strings), but not closures/objects
                $isArrayHandler = is_array($h);
                $isCacheable = $isArrayHandler ? true : self::isCacheableCallable($h);
                if (!$isCacheable) {
                    $pattern = '/' . trim((string)($r['pattern'] ?? ''), '/');
                    $module = (string)($r['module'] ?? '');
                    $warnings[] = "Non-cacheable handler (closure/object) on route {$pattern} [module={$module}]";
                }
            }
        }
        return ['routes' => $routes, 'warnings' => $warnings];
    }

    /**
     * Compile the current Router routes into a cached map with metadata.
     * Strict by default: throws when non-cacheable callables are found.
     *
     * @param Router $router Router instance with routes already registered.
     * @param string $modulesPath Path to modules for source hashing.
     * @param bool $force When true, strip non-cacheable entries and include warnings in meta.
     * @return array{meta:array{hash:string, generatedAt:string, warnings?:array<int,string>}, routes:array}
     */
    public static function compile(Router $router, string $modulesPath, bool $force = false): array
    {
        $routes = $router->exportCompiledMap();
// Validate and optionally sanitize
        $check = self::validateForCache($routes);
        if (!empty($check['warnings']) && !$force) {
            $message = "Cannot cache routes due to non-serializable middleware/handlers:\n - "
                . implode("\n - ", $check['warnings'])
                . "\nUse class-string middleware for cache (e.g., My\\Middleware\\Example::class).";
            throw new \RuntimeException($message);
        }

        if ($force && !empty($check['warnings'])) {
// Strip invalid middleware entries and (if any) disallow non-cacheable handlers by setting to string sentinel
            foreach ($routes as &$r) {
                if (isset($r['middleware']) && is_array($r['middleware'])) {
                    $r['middleware'] = array_values(array_filter($r['middleware'], [self::class, 'isCacheableCallable']));
                }
                if (isset($r['handler']) && !is_string($r['handler']) && !is_array($r['handler'])) {
// Replace with a sentinel that will fail loudly at runtime if ever reached
                    $r['handler'] = '__ISH_NON_CACHEABLE_HANDLER_REMOVED__';
                }
            }
            unset($r);
        }

        $meta = self::computeSourceHash($modulesPath);
        $result = [
            'meta' => [
                'hash' => $meta['hash'],
                'generatedAt' => date('Y-m-d H:i:s'),
            ],
            'routes' => $routes,
        ];
        if ($force && !empty($check['warnings'])) {
            $result['meta']['warnings'] = $check['warnings'];
        }
        return $result;
    }

    /**
     * Persist the compiled cache to storage/cache/routes.php
     *
     * @param array $compiled Compiled structure from compile().
     * @return string Full path to written cache file.
     */
    public static function save(array $compiled): string
    {
        $cacheDir = storage_path('cache');
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }
        $file = $cacheDir . DIRECTORY_SEPARATOR . 'routes.php';
        $export = var_export($compiled, true);
        $php = "<?php\nreturn " . $export . ";\n";
        file_put_contents($file, $php);
        return $file;
    }

    /**
     * Load cached routes file if present.
     *
     * @return array|null Compiled cache array or null when missing or invalid.
     */
    public static function load(): ?array
    {
        $file = storage_path('cache' . DIRECTORY_SEPARATOR . 'routes.php');
        if (!is_file($file)) {
            return null;
        }
        try {
/** @var array $data */
            $data = require $file;
            if (!is_array($data) || !isset($data['routes'], $data['meta'])) {
                return null;
            }
            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Remove the cached routes file if it exists.
     *
     * @return bool True when removed or did not exist, false on failure to remove.
     */
    public static function clear(): bool
    {
        $file = storage_path('cache' . DIRECTORY_SEPARATOR . 'routes.php');
        if (is_file($file)) {
            return @unlink($file);
        }
        return true;
    }

    /**
     * Determine whether the cache is fresh versus current source files.
     *
     * @param array $compiled Loaded compiled array (from load()).
     * @param string $modulesPath Modules directory path.
     * @return bool True when the cache hash matches sources, false when stale.
     */
    public static function isFresh(array $compiled, string $modulesPath): bool
    {
        $current = self::computeSourceHash($modulesPath);
        $cachedHash = (string)($compiled['meta']['hash'] ?? '');
        return $cachedHash !== '' && $cachedHash === $current['hash'];
    }
}
