<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\App;
use Ishmael\Core\Http\Request;
use Ishmael\Core\ModuleManager;
use PHPUnit\Framework\TestCase;

final class ErrorHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetModules();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        putenv('APP_DEBUG=true');
    }

    protected function tearDown(): void
    {
        $this->resetModules();
        putenv('APP_DEBUG');
    }

    private function resetModules(): void
    {
        $ref = new ReflectionClass(ModuleManager::class);
        $prop = $ref->getProperty('modules');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    public function testUnhandledExceptionResultsIn500AndDebugOutput(): void
    {
        if (!class_exists('Modules\\Err\\Controllers\\BoomController')) {
            eval('namespace Modules\\Err\\Controllers; class BoomController { public function crash() { throw new \RuntimeException("Kaboom"); } }');
        }
        $_SERVER['REQUEST_URI'] = '/Err/Boom/crash';
        $app = new App();
        $request = Request::fromGlobals();
        $response = $app->handle($request);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Internal Server Error', $response->getBody());
        $this->assertStringContainsString('Kaboom', $response->getBody());
    }

    public function testUnknownRouteResultsIn404(): void
    {
        // Point to a non-existent controller/action
        $_SERVER['REQUEST_URI'] = '/NoSuch/Controller/action';
        $app = new App();
        $request = Request::fromGlobals();
        $response = $app->handle($request);
        $this->assertSame(404, $response->getStatusCode());
    }
}
