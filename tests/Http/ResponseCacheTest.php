<?php

declare(strict_types=1);

use Ishmael\Core\Http\Middleware\ResponseCache;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Router;
use PHPUnit\Framework\TestCase;

final class ResponseCacheTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/cache/test',
            'ISH_TESTING' => '1',
        ]);
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
// Use array cache for tests
        \app(['config.cache.driver' => 'array']);
        // Ensure a clean HTTP cache namespace so first request is a MISS in each test
        if (function_exists('cache')) {
            try {
                cache()->clearNamespace('http');
            } catch (\Throwable $e) {
            /* ignore */
            }
        }
    }

    public function testCachesGetResponseAndHitsOnSecondRequest(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([ResponseCache::with(['ttl' => 60])]);
        $counter = 0;
        $router->add(['GET'], 'cache/test', function ($req, Response $res) use (&$counter): Response {

            $counter++;
            return Response::text('hello:' . $counter);
        });
// First request -> MISS caches
        ob_start();
        $router->dispatch('/cache/test');
        $out1 = ob_get_clean();
        $hdrs1 = Response::getLastHeaders();
        $this->assertSame('hello:1', $out1);
        $this->assertSame('MISS', $hdrs1['X-Cache'] ?? '');
// Second request -> HIT serves cached body
        ob_start();
        $router->dispatch('/cache/test');
        $out2 = ob_get_clean();
        $hdrs2 = Response::getLastHeaders();
        $this->assertSame('hello:1', $out2);
        $this->assertSame('HIT', $hdrs2['X-Cache'] ?? '');
    }

    public function testVaryByAcceptHeaderProducesDifferentEntries(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([ResponseCache::with(['ttl' => 60, 'vary' => ['Accept']])]);
        $router->add(['GET'], 'cache/test', function ($req, Response $res): Response {

            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            return Response::text('accept=' . $accept);
        });
// Request with Accept: text/plain
        $_SERVER['HTTP_ACCEPT'] = 'text/plain';
        ob_start();
        $router->dispatch('/cache/test');
        $out1 = ob_get_clean();
        $this->assertSame('accept=text/plain', $out1);
// Request with Accept: application/json -> different cache key, MISS then HIT
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        ob_start();
        $router->dispatch('/cache/test');
        $out2 = ob_get_clean();
        $hdrs2 = Response::getLastHeaders();
        $this->assertSame('MISS', $hdrs2['X-Cache'] ?? '');
        $this->assertSame('accept=application/json', $out2);
// Second request with same Accept should HIT
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        ob_start();
        $router->dispatch('/cache/test');
        ob_end_clean();
        $hdrs3 = Response::getLastHeaders();
        $this->assertSame('HIT', $hdrs3['X-Cache'] ?? '');
    }

    public function testSkipsOnPostMethod(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([ResponseCache::with(['ttl' => 60])]);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/cache/post';
        $router->add(['POST'], 'cache/post', function ($req, Response $res): Response {

            return Response::text('post');
        });
        ob_start();
        $router->dispatch('/cache/post');
        ob_end_clean();
        $hdrs = Response::getLastHeaders();
        $this->assertArrayNotHasKey('X-Cache', $hdrs);
// middleware returned early
    }

    public function testSkipsWhenAuthorizationHeaderPresentByDefault(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([ResponseCache::with(['ttl' => 60])]);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token';
        $router->add(['GET'], 'cache/test', function ($req, Response $res): Response {

            return Response::text('auth');
        });
        ob_start();
        $router->dispatch('/cache/test');
        ob_end_clean();
        $hdrs = Response::getLastHeaders();
        $this->assertSame('', $hdrs['X-Cache'] ?? '');
    }

    public function testDoesNotStoreWhenSetCookieHeaderPresent(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([ResponseCache::with(['ttl' => 60])]);
        $router->add(['GET'], 'cache/test', function ($req, Response $res): Response {

            return Response::text('cookie')->header('Set-Cookie', 'x=1');
        });
// First request should bypass storing
        ob_start();
        $router->dispatch('/cache/test');
        ob_end_clean();
        $hdrs1 = Response::getLastHeaders();
        $this->assertSame('BYPASS', $hdrs1['X-Cache'] ?? '');
// Second request should not hit cache either
        ob_start();
        $router->dispatch('/cache/test');
        ob_end_clean();
        $hdrs2 = Response::getLastHeaders();
        $this->assertSame('BYPASS', $hdrs2['X-Cache'] ?? '');
    }

    public function testDoesNotStorePrivateResponses(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([ResponseCache::with(['ttl' => 60])]);
        $router->add(['GET'], 'cache/test', function ($req, Response $res): Response {

            return Response::text('private')->header('Cache-Control', 'private, max-age=60');
        });
        ob_start();
        $router->dispatch('/cache/test');
        ob_end_clean();
        $hdrs = Response::getLastHeaders();
        $this->assertSame('BYPASS', $hdrs['X-Cache'] ?? '');
    }

    public function testSkipsWhenSessionCookiePresent(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([ResponseCache::with(['ttl' => 60])]);
        $_COOKIE['ish_session'] = 'abc';
        $router->add(['GET'], 'cache/test', function ($req, Response $res): Response {

            return Response::text('sess');
        });
        ob_start();
        $router->dispatch('/cache/test');
        ob_end_clean();
        $hdrs = Response::getLastHeaders();
        $this->assertSame('', $hdrs['X-Cache'] ?? '');
    }
}
