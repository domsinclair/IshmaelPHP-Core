<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Http\Middleware\VerifyCsrfToken;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Router;
use Ishmael\Core\Session\FileSessionStore;
use Ishmael\Core\Session\SessionManager;
use PHPUnit\Framework\TestCase;

final class VerifyCsrfTokenTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['ISH_TESTING'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        unset($_SERVER['HTTP_X_CSRF_TOKEN'], $_SERVER['HTTP_X_XSRF_TOKEN']);
        $_POST = [];
        $_GET = [];
// Ensure a fresh in-memory session for each test
        $store = new FileSessionStore(storage_path('sessions'));
        $manager = new SessionManager($store, null, 3600);
        app(['session' => $manager]);
    }

    public function testGetRequestPassesWithoutToken(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([VerifyCsrfToken::class]);
        $router->add(['GET'], '/ok', function ($req, Response $res): Response {

            return Response::text('ok');
        });
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/ok';
        ob_start();
        $router->dispatch('/ok');
        $out = ob_get_clean();
        $this->assertSame('ok', $out);
        $this->assertSame(200, http_response_code());
    }

    public function testPostWithoutTokenFailsWithJson419(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([VerifyCsrfToken::class]);
        $router->add(['POST'], '/submit', function ($req, Response $res): Response {

            return Response::text('submitted');
        });
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/submit';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        ob_start();
        $router->dispatch('/submit');
        $out = ob_get_clean();
        $this->assertSame(419, http_response_code());
        $this->assertJson($out);
        $decoded = json_decode($out, true);
        $this->assertSame('csrf_mismatch', $decoded['code'] ?? null);
    }

    public function testPostWithBodyTokenPasses(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([VerifyCsrfToken::class]);
        $router->add(['POST'], '/submit', function ($req, Response $res): Response {

            return Response::text('ok');
        });
// Generate a token using helper
        $token = csrfToken();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/submit';
        $_POST = ['_token' => $token];
        ob_start();
        $router->dispatch('/submit');
        $out = ob_get_clean();
        $this->assertSame('ok', $out);
        $this->assertSame(200, http_response_code());
    }

    public function testPostWithHeaderTokenPasses(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([VerifyCsrfToken::class]);
        $router->add(['POST'], '/submit', function ($req, Response $res): Response {

            return Response::text('ok');
        });
        $token = csrfToken();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/submit';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        ob_start();
        $router->dispatch('/submit');
        $out = ob_get_clean();
        $this->assertSame('ok', $out);
        $this->assertSame(200, http_response_code());
    }

    public function testDoubleSubmitWithSameTokenPasses(): void
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([VerifyCsrfToken::class]);
        $router->add(['POST'], '/submit', function ($req, Response $res): Response {

            return Response::text('ok');
        });
        $token = csrfToken();
// First submit
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/submit';
        $_POST = ['_token' => $token];
        ob_start();
        $router->dispatch('/submit');
        $first = ob_get_clean();
        $this->assertSame('ok', $first);
        $this->assertSame(200, http_response_code());
// Second submit with same token
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/submit';
        $_POST = ['_token' => $token];
        ob_start();
        $router->dispatch('/submit');
        $second = ob_get_clean();
        $this->assertSame('ok', $second);
        $this->assertSame(200, http_response_code());
    }

    public function testExceptUrisBypassVerification(): void
    {
        $router = new Router();
        Router::setActive($router);
// Provide middleware instance with override to skip /skip/*
        $router->setGlobalMiddleware([new VerifyCsrfToken(['except_uris' => ['/skip/*']])]);
        $router->add(['POST'], '/skip/item', function ($req, Response $res): Response {

            return Response::text('ok');
        });
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/skip/item';
        ob_start();
        $router->dispatch('/skip/item');
        $out = ob_get_clean();
        $this->assertSame('ok', $out);
        $this->assertSame(200, http_response_code());
    }

    public function testExceptMethodsBypassVerification(): void
    {
        $router = new Router();
        Router::setActive($router);
// Allow GET (default) so no token required
        $router->setGlobalMiddleware([VerifyCsrfToken::class]);
        $router->add(['GET'], '/free', function ($req, Response $res): Response {

            return Response::text('ok');
        });
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/free';
        ob_start();
        $router->dispatch('/free');
        $out = ob_get_clean();
        $this->assertSame('ok', $out);
        $this->assertSame(200, http_response_code());
    }
}
