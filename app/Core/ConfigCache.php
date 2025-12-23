<?php

declare(strict_types=1);

namespace Ishmael\Core;

/**
 * ConfigCache compiles and loads a merged configuration repository for fast boot.
 *
 * Cache file format (PHP returning array):
 * [
 *   'meta' => ['hash' => string, 'generatedAt' => string, 'sources' => string[]],
 *   'config' => array<string,array<string,mixed>> // map: filename (without .php) => config array
 * ]
 */
final class ConfigCache
{
    /**
     * Return the default configuration directories in priority order.
     * SkeletonApp overrides Core, and an optional project root /config overrides both if present.
     *
     * @return string[] Absolute paths of existing config directories, high to low priority.
     */
    public static function getDefaultDirs(): array
    {
        $dirs = [];
// Core config directory
        $coreDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config';
        if (is_dir($coreDir)) {
            $dirs[] = $coreDir;
        }
        // SkeletonApp config directory
        $base = function_exists('base_path') ? base_path() : dirname(__DIR__, 3);
        $skeletonDir = $base . DIRECTORY_SEPARATOR . 'SkeletonApp' . DIRECTORY_SEPARATOR . 'config';
        if (is_dir($skeletonDir)) {
            $dirs[] = $skeletonDir;
        }
        // Optional project root /config (for consumers embedding ish separately)
        $rootConfig = $base . DIRECTORY_SEPARATOR . 'config';
        if (is_dir($rootConfig)) {
            $dirs[] = $rootConfig;
        }
        return $dirs;
    }

    /**
     * Compute a stable hash across all provided config directories and files.
     *
     * @param string[] $dirs Directories to include.
     * @return array{hash:string, sources:string[]}
     */
    public static function computeSourceHash(array $dirs): array
    {
        $files = [];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob(rtrim($dir, "\\/") . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
                $files[] = realpath($file) ?: $file;
            }
        }
        sort($files);
        $ctx = hash_init('sha1');
        foreach ($files as $f) {
            $mtime = @filemtime($f) ?: 0;
            $size = @filesize($f) ?: 0;
            $data = @file_get_contents($f) ?: '';
            hash_update($ctx, $f . "\n" . $mtime . "\n" . $size . "\n" . $data);
        }
        return ['hash' => hash_final($ctx), 'sources' => $files];
    }

    /**
     * Compile configuration from multiple directories into a single repository.
     * Later directories override earlier ones on a per-file basis.
     *
     * @param string[] $dirs Directories in low-to-high priority order.
     * @return array{meta:array{hash:string,generatedAt:string,sources:string[]},config:array<string,mixed>}
     */
    public static function compile(array $dirs): array
    {
        // Normalize: ensure unique directories preserving order
        $seen = [];
        $ordered = [];
        foreach ($dirs as $d) {
            $real = realpath($d) ?: $d;
            if (!isset($seen[$real]) && is_dir($real)) {
                $seen[$real] = true;
                $ordered[] = $real;
            }
        }
        // Build map of filename => array provider files in priority order
        $repo = [];
        foreach ($ordered as $dir) {
            foreach (glob(rtrim($dir, "\\/") . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
                $name = basename($file, '.php');
        // Load array from file, silencing errors if any
                $data = require $file;
                if (is_array($data)) {
                    // Merge strategy: later directories override earlier (shallow merge)
                    $repo[$name] = array_replace($repo[$name] ?? [], $data);
                }
            }
        }
        $hashInfo = self::computeSourceHash($ordered);
        return [
            'meta' => [
                'hash' => $hashInfo['hash'],
                'generatedAt' => date('Y-m-d H:i:s'),
                'sources' => $hashInfo['sources'],
            ],
            'config' => $repo,
        ];
    }

    /**
     * Save the compiled cache to disk and return the path.
     * @param array $compiled
     * @return string Path to cache file.
     */
    public static function save(array $compiled): string
    {
        $path = self::cachePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $export = var_export($compiled, true);
        $php = "<?php\nreturn " . $export . ";\n";
        $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
        @file_put_contents($tmp, $php);
        @rename($tmp, $path);
        return $path;
    }

    /**
     * Load the compiled cache file if present.
     * @return array|null
     */
    public static function load(): ?array
    {
        $path = self::cachePath();
        if (!is_file($path)) {
            return null;
        }
        $data = require $path;
        return is_array($data) ? $data : null;
    }

    /**
     * Determine freshness by comparing stored hash with recalculated one for the same sources.
     * @param array $compiled The array returned by load() or compile().
     * @param string[] $dirs Directories to consider for current source state.
     * @return bool True if fresh (hashes match), otherwise false.
     */
    public static function isFresh(array $compiled, array $dirs): bool
    {
        $cur = self::computeSourceHash($dirs);
        $prev = (string)($compiled['meta']['hash'] ?? '');
        return $prev !== '' && hash_equals($prev, $cur['hash']);
    }

    /**
     * Delete the cache file if it exists.
     */
    public static function clear(): bool
    {
        $path = self::cachePath();
        return is_file($path) ? @unlink($path) : true;
    }

    /**
     * Return absolute cache file path.
     */
    public static function cachePath(): string
    {
        $base = function_exists('storage_path')
            ? storage_path('cache')
            : (dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache');
        return rtrim($base, "\\/") . DIRECTORY_SEPARATOR . 'config.cache.php';
    }
}
