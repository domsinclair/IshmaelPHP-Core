<?php

declare(strict_types=1);

namespace Tests\Integration;

final class MakeModuleTest extends CliTestCase
{
    private string $moduleName;
    private string $moduleDir;
    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'Blog' . substr(bin2hex(random_bytes(2)), 0, 4);
        $this->moduleDir = $this->appRoot . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $this->moduleName;
    // Ensure clean
        if (is_dir($this->moduleDir)) {
            $this->rrmdir($this->moduleDir);
        }
    }

    protected function tearDown(): void
    {
        // Cleanup created module
        if (is_dir($this->moduleDir)) {
            $this->rrmdir($this->moduleDir);
        }
        parent::tearDown();
    }

    public function testMakeModuleScaffoldsStructure(): void
    {
        $script = $this->repoRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ish';
        $run = $this->runPhpScript($script, ['make:module', $this->moduleName], $this->appRoot);
        $this->assertSame(0, $run['exit'], 'CLI exited non-zero: ' . $run['err']);
        $expected = [
            $this->moduleDir,
            $this->moduleDir . DIRECTORY_SEPARATOR . 'Controllers',
            $this->moduleDir . DIRECTORY_SEPARATOR . 'Models',
            $this->moduleDir . DIRECTORY_SEPARATOR . 'Views',
            $this->moduleDir . DIRECTORY_SEPARATOR . 'routes.php',
            $this->moduleDir . DIRECTORY_SEPARATOR . 'module.json',
        ];
        foreach ($expected as $path) {
            $this->assertFileExists($path, 'Expected scaffolded path: ' . $path);
        }

        // Check views include layout stub
        $layout = $this->moduleDir . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'layout.php';
        $this->assertFileExists($layout);
        $this->assertStringContainsString('<!doctype html>', file_get_contents($layout) ?: '');
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $it) {
            if ($it === '.' || $it === '..') {
                continue;
            }
            $p = $dir . DIRECTORY_SEPARATOR . $it;
            if (is_dir($p)) {
                $this->rrmdir($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }
}
