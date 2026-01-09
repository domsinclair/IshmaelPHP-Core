<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\ModuleManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ModuleManagerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetModules();
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

    public function testDiscoverFindsModulesAndLoadsRoutes(): void
    {
        $baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_modules_' . uniqid();
        $fooDir = $baseDir . DIRECTORY_SEPARATOR . 'Foo';
        $barDir = $baseDir . DIRECTORY_SEPARATOR . 'Bar';
        @mkdir($fooDir, 0777, true);
        @mkdir($barDir, 0777, true);
// Create routes.php in Foo only
        $routes = "<?php\nreturn [\n    '/hello' => 'HelloController@index',\n];\n";
        file_put_contents($fooDir . DIRECTORY_SEPARATOR . 'routes.php', $routes);
        ModuleManager::discover($baseDir);
        $foo = ModuleManager::get('Foo');
        $bar = ModuleManager::get('Bar');
        $this->assertIsArray($foo);
        $this->assertIsArray($bar);
        $this->assertSame('Foo', $foo['name']);
        $this->assertIsArray($foo['routes']);
        $this->assertSame(['/hello' => 'HelloController@index'], $foo['routes']);
        $this->assertSame([], $bar['routes']);
// cleanup
        @unlink($fooDir . DIRECTORY_SEPARATOR . 'routes.php');
        @rmdir($fooDir);
        @rmdir($barDir);
        @rmdir($baseDir);
    }

    public function testTopologicalSortOrder(): void
    {
        $baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_modules_sort_' . uniqid();
        $authDir = $baseDir . DIRECTORY_SEPARATOR . 'Auth';
        $coreDir = $baseDir . DIRECTORY_SEPARATOR . 'Core';
        $blogDir = $baseDir . DIRECTORY_SEPARATOR . 'Blog';
        @mkdir($authDir, 0777, true);
        @mkdir($coreDir, 0777, true);
        @mkdir($blogDir, 0777, true);

        // Blog depends on Auth and Core. Auth depends on Core.
        // Expected order: Core, Auth, Blog.
        file_put_contents($blogDir . DIRECTORY_SEPARATOR . 'module.php', "<?php return ['name'=>'Blog', 'dependencies'=>['Auth', 'Core']];");
        file_put_contents($authDir . DIRECTORY_SEPARATOR . 'module.php', "<?php return ['name'=>'Auth', 'dependencies'=>['Core']];");
        file_put_contents($coreDir . DIRECTORY_SEPARATOR . 'module.php', "<?php return ['name'=>'Core', 'dependencies'=>[]];");

        $this->resetModules();
        ModuleManager::discover($baseDir);

        $modules = ModuleManager::$modules;
        $names = array_keys($modules);

        $this->assertSame(['Core', 'Auth', 'Blog'], $names);

        // Cleanup
        @unlink($blogDir . DIRECTORY_SEPARATOR . 'module.php');
        @unlink($authDir . DIRECTORY_SEPARATOR . 'module.php');
        @unlink($coreDir . DIRECTORY_SEPARATOR . 'module.php');
        @rmdir($blogDir);
        @rmdir($authDir);
        @rmdir($coreDir);
        @rmdir($baseDir);
    }

    public function testCircularDependencyThrowsException(): void
    {
        $baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_modules_circ_' . uniqid();
        $aDir = $baseDir . DIRECTORY_SEPARATOR . 'A';
        $bDir = $baseDir . DIRECTORY_SEPARATOR . 'B';
        @mkdir($aDir, 0777, true);
        @mkdir($bDir, 0777, true);

        file_put_contents($aDir . DIRECTORY_SEPARATOR . 'module.php', "<?php return ['name'=>'A', 'dependencies'=>['B']];");
        file_put_contents($bDir . DIRECTORY_SEPARATOR . 'module.php', "<?php return ['name'=>'B', 'dependencies'=>['A']];");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        try {
            ModuleManager::discover($baseDir);
        } finally {
            @unlink($aDir . DIRECTORY_SEPARATOR . 'module.php');
            @unlink($bDir . DIRECTORY_SEPARATOR . 'module.php');
            @rmdir($aDir);
            @rmdir($bDir);
            @rmdir($baseDir);
        }
    }

    public function testEnvironmentSafetyViolationThrowsException(): void
    {
        $baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_modules_safety_' . uniqid();
        $prodDir = $baseDir . DIRECTORY_SEPARATOR . 'ProdModule';
        $devDir = $baseDir . DIRECTORY_SEPARATOR . 'DevModule';
        @mkdir($prodDir, 0777, true);
        @mkdir($devDir, 0777, true);

        file_put_contents($prodDir . DIRECTORY_SEPARATOR . 'module.php', "<?php return ['name'=>'ProdModule', 'env'=>'production', 'dependencies'=>['DevModule']];");
        file_put_contents($devDir . DIRECTORY_SEPARATOR . 'module.php', "<?php return ['name'=>'DevModule', 'env'=>'development'];");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Safety Violation');

        try {
            // Even if we are in development env, if we DISCOVER them, the check should trigger if we don't filter them out.
            // Wait, sortModules checks env mismatch.
            ModuleManager::discover($baseDir, ['appEnv' => 'development']);
        } finally {
            @unlink($prodDir . DIRECTORY_SEPARATOR . 'module.php');
            @unlink($devDir . DIRECTORY_SEPARATOR . 'module.php');
            @rmdir($prodDir);
            @rmdir($devDir);
            @rmdir($baseDir);
        }
    }

    public function testDiscoverWithMissingPathDoesNotError(): void
    {
        $nonExistent = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_missing_' . uniqid();
        ModuleManager::discover($nonExistent);
        $this->assertNull(ModuleManager::get('Anything'));
    }

    public function testShouldLoadTruthTable(): void
    {
        // production app env
        $this->assertTrue(ModuleManager::shouldLoad('production', 'production', false));
        $this->assertTrue(ModuleManager::shouldLoad('shared', 'production', false));
        $this->assertFalse(ModuleManager::shouldLoad('development', 'production', false));
        $this->assertTrue(ModuleManager::shouldLoad('development', 'production', true));
// development app env
        $this->assertTrue(ModuleManager::shouldLoad('production', 'development', false));
        $this->assertTrue(ModuleManager::shouldLoad('shared', 'development', false));
        $this->assertTrue(ModuleManager::shouldLoad('development', 'development', false));
// testing app env
        $this->assertTrue(ModuleManager::shouldLoad('production', 'testing', false));
        $this->assertTrue(ModuleManager::shouldLoad('shared', 'testing', false));
        $this->assertTrue(ModuleManager::shouldLoad('development', 'testing', false));
    }

    public function testDiscoverRespectsEnvFilteringInProduction(): void
    {
        $baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_modules_env_' . uniqid();
        $devDir = $baseDir . DIRECTORY_SEPARATOR . 'DevOnly';
        $sharedDir = $baseDir . DIRECTORY_SEPARATOR . 'SharedOne';
        $prodDir = $baseDir . DIRECTORY_SEPARATOR . 'ProdOnly';
        @mkdir($devDir, 0777, true);
        @mkdir($sharedDir, 0777, true);
        @mkdir($prodDir, 0777, true);
// Create module.php manifests for each
        $devManifest = <<<'PHP'
<?php
return [
  'name' => 'DevOnly',
  'env' => 'development',
  'enabled' => true,
];
PHP;
        $sharedManifest = <<<'PHP'
<?php
return [
  'name' => 'SharedOne',
  'env' => 'shared',
  'enabled' => true,
];
PHP;
        $prodManifest = <<<'PHP'
<?php
return [
  'name' => 'ProdOnly',
  'env' => 'production',
  'enabled' => true,
];
PHP;
        file_put_contents($devDir . DIRECTORY_SEPARATOR . 'module.php', $devManifest);
        file_put_contents($sharedDir . DIRECTORY_SEPARATOR . 'module.php', $sharedManifest);
        file_put_contents($prodDir . DIRECTORY_SEPARATOR . 'module.php', $prodManifest);
// Production without override should include shared and production only
        $this->resetModules();
        ModuleManager::discover($baseDir, ['appEnv' => 'production', 'allowDevModules' => false]);
        $this->assertNotNull(ModuleManager::get('SharedOne'));
        $this->assertNotNull(ModuleManager::get('ProdOnly'));
        $this->assertNull(ModuleManager::get('DevOnly'));
// Production with override should include dev as well
        $this->resetModules();
        ModuleManager::discover($baseDir, ['appEnv' => 'production', 'allowDevModules' => true]);
        $this->assertNotNull(ModuleManager::get('SharedOne'));
        $this->assertNotNull(ModuleManager::get('ProdOnly'));
        $this->assertNotNull(ModuleManager::get('DevOnly'));
// Development env should include all by default
        $this->resetModules();
        ModuleManager::discover($baseDir, ['appEnv' => 'development']);
        $this->assertNotNull(ModuleManager::get('SharedOne'));
        $this->assertNotNull(ModuleManager::get('ProdOnly'));
        $this->assertNotNull(ModuleManager::get('DevOnly'));
// cleanup
        @unlink($devDir . DIRECTORY_SEPARATOR . 'module.php');
        @unlink($sharedDir . DIRECTORY_SEPARATOR . 'module.php');
        @unlink($prodDir . DIRECTORY_SEPARATOR . 'module.php');
        @rmdir($devDir);
        @rmdir($sharedDir);
        @rmdir($prodDir);
        @rmdir($baseDir);
    }

    public function testModuleIntentMetadata(): void
    {
        $baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_modules_intent_' . uniqid();
        $docDir = $baseDir . DIRECTORY_SEPARATOR . 'Docs';
        @mkdir($docDir, 0777, true);

        file_put_contents($docDir . DIRECTORY_SEPARATOR . 'module.php', "<?php return ['name'=>'Docs', 'type'=>'content', 'knowledge'=>true];");

        $this->resetModules();
        ModuleManager::discover($baseDir);

        $doc = ModuleManager::get('Docs');
        $this->assertIsArray($doc);
        $this->assertSame('content', $doc['intent']['type']);
        $this->assertTrue($doc['intent']['knowledge']);
        $this->assertSame('end-user', $doc['intent']['audience']); // default

        // Cleanup
        @unlink($docDir . DIRECTORY_SEPARATOR . 'module.php');
        @rmdir($docDir);
        @rmdir($baseDir);
    }
}
