<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\ModuleManager;
use Ishmael\Core\Router;
use Ishmael\Core\Http\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetModules();
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

    public function testApiRouteWithParamsAndMiddlewareAndGroups(): void
    {
        $router = new Router();
        Router::setActive($router);
        $mw1 = function ($req, Response $res, callable $next): Response {

            $res2 = $next($req, $res);
            return Response::text($res2->getBody() . '|mw1');
        };
        $mw2 = function ($req, Response $res, callable $next): Response {

            $res2 = $next($req, $res);
            return Response::text($res2->getBody() . '|mw2');
        };
        Router::group(['prefix' => 'api', 'middleware' => [$mw1]], function (Router $r) use ($mw2) {

            $r->add(['GET'], 'v1/users/{id:int}', function ($req, Response $res, array $params): Response {

                return Response::text('user:' . ($params['id'] ?? ''));
            }, [$mw2]);
        });
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        $router->dispatch('/api/v1/users/42');
        $out = ob_get_clean();
        $this->assertSame('user:42|mw2|mw1', $out);
    }

    public function testMiddlewareShortCircuit(): void
    {
        $router = new Router();
        $blocker = function ($req, Response $res, callable $next): Response {

            return Response::text('blocked', 403);
        };
        $never = function ($req, Response $res, callable $next): Response {

            $res2 = $next($req, $res);
            return Response::text($res2->getBody() . '|should-not-see');
        };
        $router->add(['GET'], 'secure', function ($req, Response $res): Response {

            return Response::text('ok');
        }, [$blocker, $never]);
        ob_start();
        $router->dispatch('/secure');
        $out = ob_get_clean();
        $this->assertSame('blocked', $out);
        $this->assertSame(403, http_response_code());
    }

    public function testLegacyArrayRoutesStillWork(): void
    {
        // Define a dummy controller class in Modules namespace
        if (!class_exists('Modules\\X\\Controllers\\HiController')) {
            eval('namespace Modules\\X\\Controllers; class HiController { public function index() { echo "HI"; }}');
        }

        ModuleManager::$modules['X'] = [
            'name' => 'X',
            'path' => sys_get_temp_dir(),
            'routes' => [ '^hi$' => 'HiController@index' ],
            'routeClosure' => null,
        ];
        $router = new Router();
        ob_start();
        $router->dispatch('/hi');
        $out = ob_get_clean();
        $this->assertSame('HI', $out);
    }

    public function testModuleRouteClosureExecutes(): void
    {
        $router = new Router();
        ModuleManager::$modules['Foo'] = [
            'name' => 'Foo',
            'path' => sys_get_temp_dir(),
            'routes' => [],
            'routeClosure' => function (Router $r) {

                $r->add(['GET'], 'ping', function ($req, Response $res): Response {

                    return Response::text('pong');
                });
            },
        ];
        ob_start();
        $router->dispatch('/ping');
        $out = ob_get_clean();
        $this->assertSame('pong', $out);
    }

    public function testConventionFallbackStillWorks(): void
    {
        if (!class_exists('Modules\\Z\\Controllers\\YController')) {
            eval('namespace Modules\\Z\\Controllers; class YController { public function hello() { echo "H"; }}');
        }
        $router = new Router();
        ob_start();
        $router->dispatch('/Z/Y/hello');
        $out = ob_get_clean();
        $this->assertSame('H', $out);
    }
}
