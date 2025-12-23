<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Http\Middleware\HandleValidationExceptions;
use Ishmael\Core\Http\Middleware\StartSessionMiddleware;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Router;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['ISH_TESTING'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $_POST = [];
        $_GET = [];
        $_COOKIE = [];
        app([]);
    }

    public function testJsonValidationFailureReturns422(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([
            StartSessionMiddleware::class,
            HandleValidationExceptions::class,
        ]);
        $router->add(['POST'], 'submit', function ($req, Response $res): Response {

            // Will throw ValidationException on fail
            $data = validate([
                'email' => 'required|email',
                'age' => 'required|int|min:18',
            ], $req);
            return Response::json($data);
        });
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/submit';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_POST = ['email' => 'not-an-email', 'age' => '16'];
        ob_start();
        $router->dispatch('/submit');
        $out = ob_get_clean();
        $this->assertSame(422, http_response_code());
        $this->assertJson($out);
        $payload = json_decode((string)$out, true);
        $this->assertSame('validation_failed', $payload['error'] ?? null);
        $this->assertArrayHasKey('email', $payload['messages'] ?? []);
        $this->assertArrayHasKey('age', $payload['messages'] ?? []);
    }

    public function testHtmlValidationFailureRedirectsAndFlashes(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([
            StartSessionMiddleware::class,
            HandleValidationExceptions::class,
        ]);
        $router->add(['POST'], 'submit', function ($req, Response $res): Response {

            $data = validate([
                'name' => 'required|string|min:3',
            ], $req);
            return Response::text('ok');
        });
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/submit';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $_POST = ['name' => ''];
        ob_start();
        $router->dispatch('/submit');
        ob_end_clean();
// Expect redirect response
        $this->assertSame(302, http_response_code());
        $hdrs = Response::getLastHeaders();
        $this->assertArrayHasKey('Location', $hdrs);
// Flash bag should contain errors and old
        $sess = app('session');
        $all = $sess?->all() ?? [];
        $this->assertArrayHasKey('_flash', $all);
        $this->assertArrayHasKey('next', $all['_flash']);
        $this->assertArrayHasKey('_errors', $all['_flash']['next']);
        $this->assertArrayHasKey('_old', $all['_flash']['next']);
        $this->assertArrayHasKey('name', $all['_flash']['next']['_errors']);
    }
}
