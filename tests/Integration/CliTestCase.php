<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Base class for CLI integration tests.
 * Provides helpers to run the Ishmael CLI against the real SkeletonApp in a temp-safe way.
 */
abstract class CliTestCase extends TestCase
{
    /** Absolute path to repo root */
    protected string $repoRoot;
/** Absolute path to SkeletonApp */
    protected string $appRoot;
/** Absolute path to Core */
    protected string $coreRoot;
    protected function setUp(): void
    {
        parent::setUp();
        $this->repoRoot = realpath(__DIR__ . '\\..\\..\\..') ?: dirname(__DIR__, 3);
        $this->coreRoot = $this->repoRoot . DIRECTORY_SEPARATOR . 'IshmaelPHP-Core';
        $this->appRoot = $this->repoRoot . DIRECTORY_SEPARATOR . 'SkeletonApp';
        $this->assertDirectoryExists($this->appRoot, 'Expected SkeletonApp to exist for integration tests');
    }

    /**
     * Run a PHP script with arguments.
     * @param string $script Absolute path to a PHP script (e.g., core/bin/ish or repo/bin/ish)
     * @param string[] $args CLI args (without the script)
     * @param string|null $cwd Working directory (defaults to $this->appRoot)
     * @param array<string,string> $env Extra environment variables
     * @return array{exit:int, out:string, err:string}
     */
    protected function runPhpScript(string $script, array $args = [], ?string $cwd = null, array $env = []): array
    {
        $cwd = $cwd ?? $this->appRoot;
        $php = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script);
        foreach ($args as $a) {
            $cmd .= ' ' . escapeshellarg($a);
        }
        $descriptorSpec = [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];
        $procEnv = array_merge($this->safeEnv(), $env);
        $proc = proc_open($cmd, $descriptorSpec, $pipes, $cwd, $procEnv);
        if (!\is_resource($proc)) {
            $this->fail('Failed to start process: ' . $cmd);
        }
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        foreach ($pipes as $p) {
            if (\is_resource($p)) {
                fclose($p);
            }
        }
        $exit = proc_close($proc);
        return ['exit' => (int)$exit, 'out' => (string)$out, 'err' => (string)$err];
    }

    /** Minimal sanitized environment for process execution. */
    private function safeEnv(): array
    {
        $env = [
            'ISH_TESTING' => '1',
            // Force non-interactive
            'CI' => getenv('CI') ?: '0',
            // Ensure temp directory is available for logs
            'TMPDIR' => sys_get_temp_dir(),
            'TEMP' => sys_get_temp_dir(),
            'TMP' => sys_get_temp_dir(),
        ];
        return $env;
    }

    /** Load SkeletonApp SQLite database DSN from config and return absolute file path if using sqlite. */
    protected function sqliteDbPath(): ?string
    {
        $cfg = require $this->appRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        $driver = $cfg['default'] ?? 'sqlite';
        $db = $cfg['connections'][$driver] ?? ($cfg['connections']['sqlite'] ?? null);
        if (!\is_array($db)) {
            return null;
        }
        if (($db['driver'] ?? '') === 'sqlite') {
            $path = (string)($db['database'] ?? '');
            if ($path === ':memory:' || $path === '') {
                return null;
            }
            // Normalize relative paths from app root
            if (!preg_match('~^([a-zA-Z]:\\\\|/)~', $path)) {
                $path = $this->appRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            }
            return $path;
        }
        return null;
    }
}
