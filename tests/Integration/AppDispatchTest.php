<?php
declare(strict_types=1);

use Ishmael\Core\App;
use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;
use Ishmael\Core\ModuleManager;
use Ishmael\Core\Router;
use PHPUnit\Framework\TestCase;

final class AppDispatchTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetModules();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'app.test';
        // Ensure no previous headers/status leak between tests
        http_response_code(200);
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

    public function testKernelBootAndDispatchViaRouteClosure(): void
    {
        ModuleManager::$modules['IntMod'] = [
            'name' => 'IntMod',
            'path' => sys_get_temp_dir(),
            'routes' => [],
            'routeClosure' => function(Router $r) {
                $r->get('hello', function($req, Response $res) {
                    return Response::text('hello');
                });
                $mw = function($req, Response $res, callable $next): Response {
                    $r2 = $next($req, $res);
                    return Response::text($r2->getBody() . '|mw');
                };
                $r->group(['prefix' => 'admin', 'middleware' => [$mw]], function(Router $r2) {
                    $r2->get('secure', function($req, Response $res) {
                        return Response::text('secure');
                    });
                });
            },
        ];

        $app = new App();

        // /hello
        $_SERVER['REQUEST_URI'] = '/hello';
        $resp1 = $app->handle(Request::fromGlobals());
        $this->assertSame(200, $resp1->getStatusCode());
        $this->assertSame('hello', $resp1->getBody());

        // /admin/secure (middleware appends marker)
        $_SERVER['REQUEST_URI'] = '/admin/secure';
        $resp2 = $app->handle(Request::fromGlobals());
        $this->assertSame(200, $resp2->getStatusCode());
        $this->assertSame('secure|mw', $resp2->getBody());

        // Unknown -> 404
        $_SERVER['REQUEST_URI'] = '/nope';
        $resp3 = $app->handle(Request::fromGlobals());
        $this->assertSame(404, $resp3->getStatusCode());
    }
}
