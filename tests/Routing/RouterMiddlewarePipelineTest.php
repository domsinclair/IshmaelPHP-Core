<?php
declare(strict_types=1);

use Ishmael\Core\Router;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Http\Middleware\RequestIdMiddleware;
use Ishmael\Core\Http\Middleware\CorsMiddleware;
use Ishmael\Core\Http\Middleware\JsonBodyParserMiddleware;
use Ishmael\Core\Http\Middleware\MethodOverrideMiddleware;
use PHPUnit\Framework\TestCase;

final class RouterMiddlewarePipelineTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['ISH_TESTING'] = '1';
        $_POST = [];
    }

    public function testGlobalThenGroupThenRouteOrdering(): void
    {
        $router = new Router();
        Router::setActive($router);

        // Define global middlewares
        $g1 = function($req, Response $res, callable $next): Response {
            $r = $next($req, $res);
            return Response::text($r->getBody() . '|g1');
        };
        $g2 = function($req, Response $res, callable $next): Response {
            $r = $next($req, $res);
            return Response::text($r->getBody() . '|g2');
        };
        $router->setGlobalMiddleware([$g1, $g2]);

        $groupMw = function($req, Response $res, callable $next): Response {
            $r = $next($req, $res);
            return Response::text($r->getBody() . '|grp');
        };
        $routeMw = function($req, Response $res, callable $next): Response {
            $r = $next($req, $res);
            return Response::text($r->getBody() . '|rt');
        };

        Router::group(['prefix' => 'api', 'middleware' => [$groupMw]], function(Router $r) use ($routeMw) {
            $r->add(['GET'], 'ping', function($req, Response $res): Response {
                return Response::text('pong');
            }, [$routeMw]);
        });

        ob_start();
        $router->dispatch('/api/ping');
        $out = ob_get_clean();

        $this->assertSame('pong|rt|grp|g2|g1', $out);
    }

    public function testCorsPreflightShortCircuitsWith204(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([CorsMiddleware::class]);
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['REQUEST_URI'] = '/pre';

        $router->add(['OPTIONS','GET'], 'pre', function($req, Response $res): Response {
            return Response::text('should-not-run');
        });

        ob_start();
        $router->dispatch('/pre');
        $out = ob_get_clean();

        $this->assertSame('', $out); // 204 has no body
        $this->assertSame(204, http_response_code());
        $hdrs = Response::getLastHeaders();
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $hdrs);
    }

    public function testJsonBodyParsing(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([JsonBodyParserMiddleware::class]);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/echo';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['ISH_TEST_RAW_BODY'] = json_encode(['x' => 1]);

        $router->add(['POST'], 'echo', function($req, Response $res): Response {
            $data = $req->getParsedBody();
            return Response::json($data);
        });

        ob_start();
        $router->dispatch('/echo');
        $out = ob_get_clean();

        $this->assertSame('{"x":1}', $out);
    }

    public function testMethodOverrideChangesMatchedRoute(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([MethodOverrideMiddleware::class]);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/item/99';
        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'DELETE';

        $router->add(['DELETE'], 'item/{id:int}', function($req, Response $res, array $params): Response {
            return Response::text('deleted:' . $params['id']);
        });

        ob_start();
        $router->dispatch('/item/99');
        $out = ob_get_clean();

        $this->assertSame('deleted:99', $out);
    }

    public function testRequestIdHeaderPreservedThroughPipeline(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([RequestIdMiddleware::class]);

        $router->add(['GET'], 'ping', function($req, Response $res): Response {
            return Response::text('ok');
        });

        ob_start();
        $router->dispatch('/ping');
        $out = ob_get_clean();

        $this->assertSame('ok', $out);
        $hdrs = Response::getLastHeaders();
        $this->assertArrayHasKey('X-Request-Id', $hdrs);
    }
}
