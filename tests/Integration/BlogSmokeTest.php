<?php

declare(strict_types=1);

namespace Tests\Integration;

final class BlogSmokeTest extends CliTestCase
{
    private string $moduleName;
    private string $moduleDir;
    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'Blog' . substr(bin2hex(random_bytes(2)), 0, 4);
        $this->moduleDir = $this->appRoot . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $this->moduleName;
        if (is_dir($this->moduleDir)) {
            $this->rrmdir($this->moduleDir);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->moduleDir)) {
            $this->rrmdir($this->moduleDir);
        }
        parent::tearDown();
    }

    public function testBlogScaffoldSequence(): void
    {
        $bin = $this->repoRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ish';
// 1) Create module
        $r1 = $this->runPhpScript($bin, ['make:module', $this->moduleName], $this->appRoot);
        $this->assertSame(0, $r1['exit'], 'make:module failed: ' . $r1['err']);
// 2) Create Resource Post
        $r2 = $this->runPhpScript($bin, ['make:resource', $this->moduleName, 'Post'], $this->appRoot);
        $this->assertSame(0, $r2['exit'], 'make:resource failed: ' . $r2['err']);
// Assertions: routes.php updated, controller created, views present
        $routes = $this->moduleDir . DIRECTORY_SEPARATOR . 'routes.php';
        $this->assertFileExists($routes);
        $routesBody = file_get_contents($routes) ?: '';
        $this->assertStringContainsString('PostController', $routesBody);
        $controller = $this->moduleDir . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'PostController.php';
        $this->assertFileExists($controller);
        $vcDir = $this->moduleDir . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'posts';
        $this->assertDirectoryExists($vcDir);
        foreach (['index.php','show.php','create.php','edit.php','_form.php'] as $vf) {
            $this->assertFileExists($vcDir . DIRECTORY_SEPARATOR . $vf);
        }
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
