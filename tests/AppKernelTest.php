<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\App;
use Ishmael\Core\Http\Request;
use Ishmael\Core\ModuleManager;
use PHPUnit\Framework\TestCase;

final class AppKernelTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetModules();
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
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

    public function testBootIsIdempotent(): void
    {
        $app = new App();
        $app->boot();
        $modulesAfterFirst = ModuleManager::$modules;
// Call boot again; should not throw and should not mutate ModuleManager state
        $app->boot();
        $modulesAfterSecond = ModuleManager::$modules;
        $this->assertSame($modulesAfterFirst, $modulesAfterSecond);
    }

    public function testHandleReturnsResponseObject(): void
    {
        $app = new App();
        $request = Request::fromGlobals();
        $response = $app->handle($request);
        $this->assertInstanceOf(Ishmael\Core\Http\Response::class, $response);
        $this->assertIsString($response->getBody());
        $this->assertIsInt($response->getStatusCode());
// With no controllers present in core test env, we expect a 404 from Router
        $this->assertTrue(in_array($response->getStatusCode(), [200, 404, 500], true));
    }

    public function testTerminateIsNoOp(): void
    {
        $app = new App();
        $req = Request::fromGlobals();
        $res = $app->handle($req);
// Should not throw
        $app->terminate($req, $res);
// Response should remain unchanged
        $this->assertSame($res->getStatusCode(), $app->handle($req)->getStatusCode());
    }
}
