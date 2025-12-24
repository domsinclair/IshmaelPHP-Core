<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Ishmael\Core\Http\Middleware\ConditionalRequests;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Router;
use PHPUnit\Framework\TestCase;

final class ConditionalRequestsTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/cond/etag',
            'ISH_TESTING' => '1',
        ]);
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        unset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE']);
    }

    public function testEtagMatchReturns304AndPreservesHeaders(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([ConditionalRequests::with()]);
        $router->add(['GET'], 'cond/etag', function ($req, Response $res): Response {

            return Response::text('hello')
                ->header('Cache-Control', 'public, max-age=60')
                ->header('Vary', 'Accept');
        });
// First request to get ETag
        ob_start();
        $router->dispatch('/cond/etag');
        ob_end_clean();
        $headers1 = Response::getLastHeaders();
        $this->assertArrayHasKey('ETag', $headers1);
        $etag = $headers1['ETag'];
// Second request with If-None-Match should 304
        $_SERVER['HTTP_IF_NONE_MATCH'] = $etag;
        ob_start();
        $router->dispatch('/cond/etag');
        $out = ob_get_clean();
        $headers2 = Response::getLastHeaders();
        $this->assertSame('', $out);
// 304 body should be empty
        $this->assertSame(304, http_response_code());
        $this->assertSame($etag, $headers2['ETag'] ?? '');
        $this->assertSame('public, max-age=60', $headers2['Cache-Control'] ?? '');
        $this->assertSame('Accept', $headers2['Vary'] ?? '');
    }

    public function testWeakVsStrongSemanticsToggle(): void
    {
        // Route with allowWeak=false
        $_SERVER['REQUEST_URI'] = '/cond/weak/strict';
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([ConditionalRequests::with(['allowWeak' => false])]);
        $router->add(['GET'], 'cond/weak/strict', function ($req, Response $res): Response {

            return Response::text('body');
        });
// Obtain strong ETag
        ob_start();
        $router->dispatch('/cond/weak/strict');
        ob_end_clean();
        $h1 = Response::getLastHeaders();
        $this->assertArrayHasKey('ETag', $h1);
        $strong = $h1['ETag'];
// Send weak tag -> should NOT 304 when allowWeak=false
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/' . $strong;
        ob_start();
        $router->dispatch('/cond/weak/strict');
        $out = ob_get_clean();
        $this->assertSame('body', $out);
        $this->assertSame(200, http_response_code());
// Route with allowWeak=true
        $_SERVER['REQUEST_URI'] = '/cond/weak/allow';
        $router2 = new Router();
        Router::setActive($router2);
        $router2->setGlobalMiddleware([ConditionalRequests::with(['allowWeak' => true])]);
        $router2->add(['GET'], 'cond/weak/allow', function ($req, Response $res): Response {

            return Response::text('same');
        });
        ob_start();
        $router2->dispatch('/cond/weak/allow');
        ob_end_clean();
// Use ETag from first route; both derive from body but different bodies; ensure using the new one
        $h2 = Response::getLastHeaders();
        $strong2 = $h2['ETag'] ?? '';
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/' . $strong2;
        ob_start();
        $router2->dispatch('/cond/weak/allow');
        ob_end_clean();
        $this->assertSame(304, http_response_code());
    }

    public function testLastModifiedWithTimeSkew(): void
    {
        $_SERVER['REQUEST_URI'] = '/cond/lastmod';
        $router = new Router();
        Router::setActive($router);
// Provide lastModified via resolver to ensure deterministic timestamp
        $fixed = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $mw = ConditionalRequests::with([
            'lastModifiedResolver' => function () use ($fixed) {
                return $fixed;
            }
        ]);
        $router->setGlobalMiddleware([$mw]);
        $router->add(['GET'], 'cond/lastmod', function ($req, Response $res): Response {

            return Response::text('content');
        });
// First fetch to get header
        ob_start();
        $router->dispatch('/cond/lastmod');
        ob_end_clean();
        $h = Response::getLastHeaders();
        $last = $h['Last-Modified'] ?? null;
        $this->assertNotNull($last);
// If-Modified-Since equal -> 304
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $last;
        ob_start();
        $router->dispatch('/cond/lastmod');
        ob_end_clean();
        $this->assertSame(304, http_response_code());
// If-Modified-Since one second behind -> 200
        $ts = strtotime($last . ' UTC');
        $behind = gmdate('D, d M Y H:i:s', $ts - 1) . ' GMT';
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $behind;
        ob_start();
        $router->dispatch('/cond/lastmod');
        ob_end_clean();
        $this->assertSame(200, http_response_code());
// If-Modified-Since +1s ahead -> 304 tolerated
        $ahead = gmdate('D, d M Y H:i:s', $ts + 1) . ' GMT';
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $ahead;
        ob_start();
        $router->dispatch('/cond/lastmod');
        ob_end_clean();
        $this->assertSame(304, http_response_code());
    }
}
