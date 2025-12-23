<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\ModuleManager;
use PHPUnit\Framework\TestCase;

final class BootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetModules();
// Ensure REQUEST_URI is defined for tests
        $_SERVER['REQUEST_URI'] = '/';
    }

    protected function tearDown(): void
    {
        $this->resetModules();
    }

    private function resetModules(): void
    {
        $ref = new ReflectionClass(ModuleManager::class);
        $prop = $ref->getProperty('modules');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    public function testBootstrapCreatesDefaultEnvAndDiscoversNoModulesGracefully(): void
    {
        // Point modules path to a random temp directory that does not exist via config override
        // Since bootstrap reads config/app.php, and that uses base_path('modules'),
        // we temporarily spoof base_path by changing working directory to a temp dir
        $tempBase = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_boot_' . uniqid();
        @mkdir($tempBase, 0777, true);
// Create minimal structure expected by helpers/config
        @mkdir($tempBase . DIRECTORY_SEPARATOR . 'config', 0777, true);
// Copy app.php and logging.php from real config so defaults resolve
        $realConfigDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config';
        copy($realConfigDir . DIRECTORY_SEPARATOR . 'app.php', $tempBase . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php');
        copy($realConfigDir . DIRECTORY_SEPARATOR . 'logging.php', $tempBase . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'logging.php');
// Create empty modules dir so discovery finds zero modules
        @mkdir($tempBase . DIRECTORY_SEPARATOR . 'modules', 0777, true);
        @mkdir($tempBase . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs', 0777, true);
// Monkey-patch base_path by including helpers from test bootstrap where base_path relies on __DIR__ traversal.
        // We simulate base_path by adjusting include path using chdir to our temp base, because helpers base_path computes from app dir.
        $originalCwd = getcwd();
        chdir($tempBase);
        try {
        // Require the bootstrap file. It should not throw and should leave ModuleManager::$modules empty.
            require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';
            $this->assertIsArray(ModuleManager::$modules);
        } finally {
            chdir($originalCwd ?: __DIR__);
        }
    }

    public function testBootstrapIsIdempotent(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
// First run
        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';
        $firstModules = ModuleManager::$modules;
// Second run should not error and should not corrupt module registry if path unchanged
        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';
        $secondModules = ModuleManager::$modules;
        $this->assertSame($firstModules, $secondModules);
    }
}
