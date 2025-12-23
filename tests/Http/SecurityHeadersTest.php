<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Http\Middleware\SecurityHeaders;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Router;
use PHPUnit\Framework\TestCase;

final class SecurityHeadersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['ISH_TESTING'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/sec';
        $_POST = [];
        $_GET = [];
        $_COOKIE = [];
        app([]);
    }

    public function testDefaultHeadersApplied(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([new SecurityHeaders()]);
        $router->add(['GET'], 'sec', function ($req, Response $res): Response {

            return Response::text('ok');
        });
        ob_start();
        $router->dispatch('/sec');
        ob_end_clean();
        $headers = Response::getLastHeaders();
        $this->assertArrayHasKey('X-Frame-Options', $headers);
        $this->assertArrayHasKey('X-Content-Type-Options', $headers);
        $this->assertArrayHasKey('Referrer-Policy', $headers);
        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $this->assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
    }

    public function testOverridesWork(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([
            SecurityHeaders::with([
                'x_frame_options' => 'DENY',
                'permissions_policy' => 'geolocation=()'
            ]),
        ]);
        $router->add(['GET'], 'sec2', function ($req, Response $res): Response {

            return Response::text('ok');
        });
        ob_start();
        $router->dispatch('/sec2');
        ob_end_clean();
        $headers = Response::getLastHeaders();
        $this->assertSame('DENY', $headers['X-Frame-Options'] ?? null);
        $this->assertSame('geolocation=()', $headers['Permissions-Policy'] ?? null);
    }
}
