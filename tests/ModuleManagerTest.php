<?php
declare(strict_types=1);

use Ishmael\Core\ModuleManager;
use PHPUnit\Framework\TestCase;

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

    public function testDiscoverWithMissingPathDoesNotError(): void
    {
        $nonExistent = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_missing_' . uniqid();
        ModuleManager::discover($nonExistent);
        $this->assertNull(ModuleManager::get('Anything'));
    }
}
