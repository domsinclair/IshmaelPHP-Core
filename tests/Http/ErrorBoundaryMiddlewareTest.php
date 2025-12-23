<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Router;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Http\Middleware\RequestIdMiddleware;
use Ishmael\Core\Http\Middleware\ErrorBoundaryMiddleware;
use PHPUnit\Framework\TestCase;

final class ErrorBoundaryMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('base_path')) {
            require_once __DIR__ . '/../../app/Helpers/helpers.php';
        }
        // Prevent global handlers from attaching in bootstrap
        $_SERVER['ISH_TESTING'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/oops';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        if (function_exists('header_remove')) {
            @header_remove();
        }
    }

    public function testTransformsExceptionToJson500WithCorrelationId(): void
    {
        $router = new Router();
        $mw1 = new RequestIdMiddleware();
        $mw2 = new ErrorBoundaryMiddleware();
        $router->add(['GET'], 'oops', function () {

            throw new RuntimeException('Boom');
        }, [$mw1, $mw2]);
        ob_start();
        $router->dispatch('/oops');
        $out = ob_get_clean();
        $headers = Response::getLastHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertStringContainsString('application/json', $headers['Content-Type']);
        $this->assertArrayHasKey('X-Correlation-Id', $headers);
        $this->assertNotSame('', $headers['X-Correlation-Id']);
        $this->assertIsString($out);
        $decoded = json_decode($out, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertSame(500, $decoded['error']['status'] ?? null);
        $this->assertSame($headers['X-Correlation-Id'], $decoded['error']['id'] ?? null);
    }

    public function testTransformsExceptionToHtmlWhenAcceptHtml(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $router = new Router();
        $mw1 = new RequestIdMiddleware();
        $mw2 = new ErrorBoundaryMiddleware();
        $router->add(['GET'], 'oops2', function () {

            throw new RuntimeException('Bada');
        }, [$mw1, $mw2]);
        ob_start();
        $router->dispatch('/oops2');
        $out = ob_get_clean();
        $headers = Response::getLastHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertStringContainsString('text/html', $headers['Content-Type']);
        $this->assertArrayHasKey('X-Correlation-Id', $headers);
        $this->assertNotSame('', $headers['X-Correlation-Id']);
        $this->assertIsString($out);
        $this->assertStringContainsString('Internal Server Error', $out);
    }
}
