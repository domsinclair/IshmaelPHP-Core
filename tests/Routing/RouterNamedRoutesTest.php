<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use InvalidArgumentException;
use Ishmael\Core\Router;
use Ishmael\Core\Http\Response;
use Ishmael\Core\ModuleManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RouterNamedRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['ISH_TESTING'] = '1';
        $this->resetModules();
    }

    protected function tearDown(): void
    {
        $this->resetModules();
        unset($_SERVER['HTTP_HOST'], $_SERVER['HTTPS']);
    }

    private function resetModules(): void
    {
        $ref = new ReflectionClass(ModuleManager::class);
        $prop = $ref->getProperty('modules');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    public function testFluentNamedRouteAndUrlGeneration(): void
    {
        $router = new Router();
        Router::setActive($router);
        Router::get('users/{id:int}/posts/{slug:slug}', function ($req, Response $res) {

            return Response::text('ok');
        })->name('users.posts.show');
        $url = Router::url('users.posts.show', ['id' => 42, 'slug' => 'hello-world']);
        $this->assertSame('/users/42/posts/hello-world', $url);
// unicode value should be encoded
        $url2 = Router::url('users.posts.show', ['id' => 5, 'slug' => 'ümlaut']);
        $this->assertSame('/users/5/posts/' . rawurlencode('ümlaut'), $url2);
    }

    public function testAbsoluteUrlGeneration(): void
    {
        $router = new Router();
        Router::setActive($router);
        Router::get('p/{uuid:uuid}', fn($req, $res) => Response::text('ok'))->name('p.show');
        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['HTTPS'] = 'on';
        $url = Router::url('p.show', ['uuid' => '550e8400-e29b-41d4-a716-446655440000'], [], true);
        $this->assertSame('https://example.test/p/550e8400-e29b-41d4-a716-446655440000', $url);
    }

    public function testMissingParamsThrowHelpfulException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Missing parameters/');
        $router = new Router();
        Router::setActive($router);
        Router::get('x/{a:int}/{b:slug}', fn($req, $res) => Response::text('ok'))->name('route.x');
        Router::url('route.x', ['a' => 1]);
// missing b
    }

    public function testUnknownRouteThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Router::url('no.such.route');
    }
}
