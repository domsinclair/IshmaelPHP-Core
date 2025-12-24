<?php

declare(strict_types=1);

namespace Ishmael\Core;

use RuntimeException;

/**
 * Packer builds environment-specific deployment bundles.
 *
 * Responsibilities in Phase 11 — Milestone 3:
 * - Scan app Modules and parse manifests via ModuleManager
 * - Select modules based on env and includeDev flag
 * - Plan the bundle: exported files from modules + optional caches/config snapshot
 * - Dry-run output (list what would be included)
 * - Write a manifest (JSON) with checksums; copy files when not in dry-run
 */
final class Packer
{
    /** @var string Absolute path to application root */
    private string $appRoot;
/** @var string production|development|testing */
    private string $env = 'development';
/** @var bool */
    private bool $includeDev = false;
/** @var string webhost|container */
    private string $target = 'webhost';
/** @var string Output directory (absolute or relative to app root) */
    private string $outDir;
/** @var bool */
    private bool $dryRun = false;
/**
     * @param string $appRoot Application root directory.
     */
    public function __construct(string $appRoot)
    {
        $this->appRoot = rtrim($appRoot, '\\/');
        $this->outDir = $this->appRoot . DIRECTORY_SEPARATOR . 'dist';
    }

    /**
     * Configure packing options.
     *
     * @param string $env Environment (production|development|testing)
     * @param bool $includeDev Whether to include development modules in production
     * @param string $target Target runtime (webhost|container)
     * @param string|null $outDir Output directory
     * @param bool $dryRun If true, do not copy files, only print plan and write nothing
     * @return $this
     */
    public function configure(string $env, bool $includeDev, string $target = 'webhost', ?string $outDir = null, bool $dryRun = false): self
    {
        $env = strtolower($env);
        if (!in_array($env, ['production', 'development', 'testing'], true)) {
            throw new RuntimeException('Invalid env: ' . $env);
        }
        $this->env = $env;
        $this->includeDev = $includeDev;
        $this->target = $target;
        if ($outDir !== null) {
            $this->outDir = $this->toAbsolute($outDir);
        }
        $this->dryRun = $dryRun;
        return $this;
    }

    /**
     * Build (or plan) the bundle and return the manifest array.
     *
     * @return array<string,mixed> Manifest including files and checksums
     */
    public function pack(): array
    {
        $modulesDir = $this->appRoot . DIRECTORY_SEPARATOR . 'Modules';
// Discover modules using env-aware rules
        ModuleManager::discover($modulesDir, [
            'appEnv' => $this->env,
            'allowDevModules' => $this->includeDev,
        ]);
        $selected = ModuleManager::$modules;
        /** @var array<string, bool> $files */
        $files = [];
// Gather module exports
        foreach ($selected as $mod) {
            $modPath = (string)($mod['path'] ?? '');
            $manifest = (array)($mod['manifest'] ?? []);
            $exports = $manifest['export'] ?? [];
            if (!is_array($exports) || empty($exports)) {
            // conservative defaults if export not specified
                $exports = ['Controllers', 'Models', 'Views', 'routes.php', 'schema.php', 'module.php', 'module.json'];
            }
            foreach ($exports as $rel) {
                $path = $modPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$rel);
                $this->collectFiles($files, $path);
            }
        }

        // Include caches if present
        $cacheDir = $this->appRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';
        $this->collectIfExists($files, $cacheDir . DIRECTORY_SEPARATOR . 'routes.cache.php');
        $this->collectIfExists($files, $cacheDir . DIRECTORY_SEPARATOR . 'modules.cache.json');
// Include config snapshot (read-only sources)
        $configDir = $this->appRoot . DIRECTORY_SEPARATOR . 'config';
        if (is_dir($configDir)) {
            $this->collectFiles($files, $configDir);
        }

        // Compute checksums
        $manifestFiles = [];
        foreach (array_keys($files) as $abs) {
            $manifestFiles[] = [
                'path' => $this->relativeToApp($abs),
                'checksum' => $this->sha256($abs),
            ];
        }

        $manifest = [
            'generatedAt' => date('c'),
            'env' => $this->env,
            'includeDev' => $this->includeDev,
            'target' => $this->target,
            'files' => $manifestFiles,
        ];
        if ($this->dryRun) {
        // For dry-run we only return the manifest; caller can print it
            return $manifest;
        }

        // Copy files into output bundle
        $bundleDir = $this->createBundleDir();
        foreach (array_keys($files) as $abs) {
            $dest = $bundleDir . DIRECTORY_SEPARATOR . $this->relativeToApp($abs);
            $this->copyFile($abs, $dest);
        }
        // Write manifest.json
        $this->ensureDirectory($bundleDir);
        file_put_contents($bundleDir . DIRECTORY_SEPARATOR . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        return $manifest;
    }

    /**
     * Convert path to absolute using app root as base.
     * @param string $path
     */
    private function toAbsolute(string $path): string
    {
        if (preg_match('/^[A-Za-z]:\\\\|^\\\\\\\\/', $path) === 1) {
            return rtrim($path, '\\/');
        }
        return $this->appRoot . DIRECTORY_SEPARATOR . trim($path, '\\/');
    }

    /**
     * Ensure directory exists.
     * @param string $dir
     * @return void
     */
    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException('Failed to create directory: ' . $dir);
            }
        }
    }

    /**
     * Create the concrete bundle directory inside outDir.
     * @return string Absolute path to bundle directory
     */
    private function createBundleDir(): string
    {
        $suffix = $this->env . '-' . date('Ymd-His');
        $dir = rtrim($this->outDir, '\\/') . DIRECTORY_SEPARATOR . 'bundle-' . $suffix;
        $this->ensureDirectory($dir);
        return $dir;
    }

    /**
     * Accumulate files from a path (file or directory) into $files as a set.
     * @param array<string,bool> $files
     * @param string $path
     * @return void
     */
    private function collectFiles(array &$files, string $path): void
    {
        if (is_file($path)) {
            $files[$path] = true;
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile()) {
                $files[$file->getPathname()] = true;
            }
        }
    }

    /**
     * Collect a file if it exists.
     * @param array<string,bool> $files
     * @param string $path
     * @return void
     */
    private function collectIfExists(array &$files, string $path): void
    {
        if (is_file($path)) {
            $files[$path] = true;
        }
    }

    /**
     * Compute SHA-256 checksum of a file.
     * @param string $absPath
     * @return string
     */
    private function sha256(string $absPath): string
    {
        $hash = hash_file('sha256', $absPath);
        return $hash !== false ? $hash : '';
    }

    /**
     * Copy a single file to destination, ensuring directory exists.
     * @param string $src
     * @param string $dest
     * @return void
     */
    private function copyFile(string $src, string $dest): void
    {
        $dir = dirname($dest);
        $this->ensureDirectory($dir);
        if (!copy($src, $dest)) {
            throw new RuntimeException('Failed to copy file: ' . $src . ' → ' . $dest);
        }
    }

    /**
     * Convert an absolute path to app-root relative path for manifest clarity.
     * @param string $abs
     * @return string
     */
    private function relativeToApp(string $abs): string
    {
        $root = rtrim($this->appRoot, '\\/') . DIRECTORY_SEPARATOR;
        if (str_starts_with($abs, $root)) {
            return substr($abs, strlen($root));
        }
        return $abs;
    }
}
