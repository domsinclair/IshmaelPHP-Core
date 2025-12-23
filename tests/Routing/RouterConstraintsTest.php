<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Router;
use Ishmael\Core\Http\Response;
use PHPUnit\Framework\TestCase;

final class RouterConstraintsTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testIntAndUuidParamsAreConverted(): void
    {
        $router = new Router();
        Router::setActive($router);
        Router::get('api/items/{id:int}/{uuid:uuid}', function ($req, Response $res, array $params): Response {

            $idType = gettype($params['id']);
            $uuid = $params['uuid'];
            return Response::text($idType . '|' . $uuid);
        });
        ob_start();
        $router->dispatch('/api/items/123/550E8400-E29B-41D4-A716-446655440000');
        $out = ob_get_clean();
        $this->assertSame('integer|550e8400-e29b-41d4-a716-446655440000', $out);
    }

    public function testUnicodeSlugAndPercentEncodedSegment(): void
    {
        $router = new Router();
        Router::setActive($router);
        Router::get('api/tags/{name:slug}', function ($req, Response $res, array $params): Response {

            return Response::text('tag:' . $params['name']);
        });
        $name = rawurlencode('ümlaut');
        ob_start();
        $router->dispatch('/api/tags/' . $name);
        $out = ob_get_clean();
        $this->assertSame('tag:ümlaut', $out);
    }

    public function testTrailingSlashesAreIgnored(): void
    {
        $router = new Router();
        Router::setActive($router);
        Router::get('api/users/{id:int}', function ($req, Response $res, array $params): Response {

            return Response::text('u:' . $params['id']);
        });
        ob_start();
        $router->dispatch('/api/users/42/');
        $out = ob_get_clean();
// Our Router trims slashes, so pattern compiled without trailing slash should still match
        $this->assertSame('u:42', $out);
    }
}
