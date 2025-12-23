<?php

declare(strict_types=1);

use Ishmael\Core\ModuleManager;
use Ishmael\Core\Router;
use Ishmael\Core\Http\Response;
use PHPUnit\Framework\TestCase;

final class HerdHostRoutingTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetModules();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'Ishmael.test';
// case-insensitive match scenario
    }

    protected function tearDown(): void
    {
        $this->resetModules();
        unset($_SERVER['HTTP_HOST']);
    }

    private function resetModules(): void
    {
        $ref = new ReflectionClass(ModuleManager::class);
        $prop = $ref->getProperty('modules');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    public function testRootRouteResolvesOnHerdHost(): void
    {
        // Define a dummy controller class in a unique Modules namespace to avoid cross-test collisions
        if (!class_exists('Modules\\HerdTest\\Controllers\\RootController')) {
            eval('namespace Modules\\HerdTest\\Controllers; class RootController { public function index() { echo "ROOT"; }}');
        }

        ModuleManager::$modules['HerdTest'] = [
            'name' => 'HerdTest',
            'path' => sys_get_temp_dir(),
            'routes' => [ '^$' => 'RootController@index' ],
            'routeClosure' => null,
        ];
        $router = new Router();
        ob_start();
        $router->dispatch('/');
        $out = ob_get_clean();
        $this->assertSame('ROOT', $out);
    }
}
