<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Packer;
use Ishmael\Core\ModuleManager;
use PHPUnit\Framework\TestCase;

final class PackerTest extends TestCase
{
    /** @var string */
    private string $appRoot;
    protected function setUp(): void
    {
        parent::setUp();
        $this->appRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_app_' . uniqid();
        $this->mkdir($this->appRoot);
    // minimal config dir so packer includes it
        $this->mkdir($this->appRoot . DIRECTORY_SEPARATOR . 'config');
        file_put_contents($this->appRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php', "<?php\nreturn ['name' => 'Test'];\n");
    // modules root
        $this->mkdir($this->appRoot . DIRECTORY_SEPARATOR . 'Modules');
        $this->resetDiscoveredModules();
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->appRoot);
        $this->resetDiscoveredModules();
        parent::tearDown();
    }

    public function testPackerRespectsEnvFilteringInProduction(): void
    {
        // Create three modules: DevOnly (development), SharedOne (shared), ProdOnly (production)
        $this->createExampleModule('DevOnly', 'development');
        $this->createExampleModule('SharedOne', 'shared');
        $this->createExampleModule('ProdOnly', 'production');
// Production, default (dev excluded)
        $packer = new Packer($this->appRoot);
        $packer->configure('production', false, 'webhost', null, true);
        $manifest = $packer->pack();
        $paths = $this->manifestPaths($manifest);
        $this->assertContains('Modules' . DIRECTORY_SEPARATOR . 'SharedOne' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'HelloController.php', $paths);
        $this->assertContains('Modules' . DIRECTORY_SEPARATOR . 'ProdOnly' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'HelloController.php', $paths);
        $this->assertNotContains('Modules' . DIRECTORY_SEPARATOR . 'DevOnly' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'HelloController.php', $paths);
// Production, include-dev flag
        $packer2 = new Packer($this->appRoot);
        $packer2->configure('production', true, 'webhost', null, true);
        $manifest2 = $packer2->pack();
        $paths2 = $this->manifestPaths($manifest2);
        $this->assertContains('Modules' . DIRECTORY_SEPARATOR . 'DevOnly' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'HelloController.php', $paths2);
    }

    public function testPackerIncludesConfigAndOptionalCaches(): void
    {
        $this->createExampleModule('SharedOne', 'shared');
// Add caches
        $cacheDir = $this->appRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';
        $this->mkdir($cacheDir);
        file_put_contents($cacheDir . DIRECTORY_SEPARATOR . 'routes.cache.php', "<?php\nreturn [];\n");
        file_put_contents($cacheDir . DIRECTORY_SEPARATOR . 'modules.cache.json', json_encode([]));
        $packer = new Packer($this->appRoot);
        $packer->configure('development', false, 'webhost', null, true);
        $manifest = $packer->pack();
        $paths = $this->manifestPaths($manifest);
// config is included
        $this->assertContains('config' . DIRECTORY_SEPARATOR . 'app.php', $paths);
// caches are included when present
        $this->assertContains('storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'routes.cache.php', $paths);
        $this->assertContains('storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'modules.cache.json', $paths);
    }

    /**
     * Create a minimal example module with a manifest and exported files.
     * @param string $name Module name (StudlyCase)
     * @param 'development'|'shared'|'production' $env Env type
     * @return void
     */
    private function createExampleModule(string $name, string $env): void
    {
        $moduleDir = $this->appRoot . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $name;
        $this->mkdir($moduleDir);
        $this->mkdir($moduleDir . DIRECTORY_SEPARATOR . 'Controllers');
// exported file
        file_put_contents($moduleDir . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'HelloController.php', "<?php // stub\n");
// routes file (use array for simplicity in this test context)
        file_put_contents($moduleDir . DIRECTORY_SEPARATOR . 'routes.php', "<?php\nreturn [];\n");
// manifest (preferred module.php)
        $manifest = <<<PHP
<?php
return [
  'name' => '{$name}',
  'env' => '{$env}',
  'enabled' => true,
  'routes' => __DIR__ . '/routes.php',
  'export' => ['Controllers', 'routes.php'],
];
PHP;
        file_put_contents($moduleDir . DIRECTORY_SEPARATOR . 'module.php', $manifest);
    }

    /** @return array<int,string> */
    private function manifestPaths(array $manifest): array
    {
        $files = $manifest['files'] ?? [];
        $paths = [];
        foreach ($files as $f) {
            if (is_array($f) && isset($f['path'])) {
                $paths[] = (string)$f['path'];
            }
        }
        sort($paths);
        return $paths;
    }

    private function mkdir(string $path): void
    {
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
        }
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            $p = $file->getPathname();
            if ($file->isDir()) {
                @rmdir($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }

    private function resetDiscoveredModules(): void
    {
        $ref = new ReflectionClass(ModuleManager::class);
        $prop = $ref->getProperty('modules');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }
}
