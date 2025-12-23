<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Database;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Http\Middleware\StartSessionMiddleware;
use Ishmael\Core\Http\Middleware\RememberMeMiddleware;
use Ishmael\Core\Http\Middleware\Authenticate;
use Ishmael\Core\Router;
use PHPUnit\Framework\TestCase;

final class AuthMiddlewareAndManagerTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['ISH_TESTING'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_POST = [];
        $_GET = [];
        $_COOKIE = [];
// Fresh app services between tests
        app([]);
// Minimal APP_KEY for HMACs
        putenv('APP_KEY=base64:' . base64_encode(random_bytes(32)));
// Initialize in-memory sqlite and users table (force isolated fresh connection in tests)
        $config = [
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => ['driver' => 'sqlite', 'database' => ':memory:'],
            ],
        ];
        Database::init($config);
        $ddl = 'CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL)';
        Database::adapter()->runSql($ddl);

        // Clean slate to avoid UNIQUE collisions if a previous run left data in a persistent connection
        try {
            Database::adapter()->execute('DELETE FROM users');
        } catch (Throwable $e) {
        /* ignore if table fresh */
        }

        // Insert one user with known password
        $hash = hasher()->hash('secret123');
        Database::adapter()->execute('INSERT INTO users (email, password) VALUES (:e, :p)', [':e' => 'alice@example.com', ':p' => $hash]);
    }

    protected function tearDown(): void
    {
        // Ensure database/static state does not leak into subsequent tests
        try {
            Database::reset();
        } catch (Throwable $e) {
        /* ignore */
        }
        // Clear app container and superglobals that might influence cookies/auth
        app([]);
        $_COOKIE = [];
        unset($_SERVER['ISH_AUTH_REMEMBER_SET'], $_SERVER['ISH_AUTH_REMEMBER_CLEAR']);
    }

    private function makeRouterWithAuth(): Router
    {
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([
            StartSessionMiddleware::class,
            RememberMeMiddleware::class,
        ]);
        return $router;
    }

    public function testLoginHappyPathAndSessionFixationDefense(): void
    {
        $router = $this->makeRouterWithAuth();
// Login route returns before/after session ids
        $router->add(['POST'], 'login', function ($req, Response $res): Response {

            $sidBefore = app('session')->getId();
            $ok = auth()->attempt([
                'email' => 'alice@example.com',
                'password' => 'secret123',
            ], false);
            $sidAfter = app('session')->getId();
            return Response::json(['ok' => $ok, 'sid_before' => $sidBefore, 'sid_after' => $sidAfter]);
        });
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/login';
        ob_start();
        $router->dispatch('/login');
        $out = ob_get_clean();
        $this->assertNotFalse($out);
        $payload = json_decode((string)$out, true);
        $this->assertIsArray($payload);
        $this->assertTrue($payload['ok']);
        $this->assertNotEmpty($payload['sid_before']);
        $this->assertNotEmpty($payload['sid_after']);
        $this->assertNotSame($payload['sid_before'], $payload['sid_after'], 'Session id should rotate on login');
    }

    public function testInvalidCredentialsDoNotAuthenticate(): void
    {
        $router = $this->makeRouterWithAuth();
        $router->add(['POST'], 'login', function ($req, Response $res): Response {

            $ok = auth()->attempt([
                'email' => 'alice@example.com',
                'password' => 'wrong',
            ], true);
            return Response::json(['ok' => $ok]);
        });
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/login';
        ob_start();
        $router->dispatch('/login');
        $out = ob_get_clean();
        $payload = json_decode((string)$out, true);
        $this->assertIsArray($payload);
        $this->assertFalse($payload['ok']);
        $hdrs = Response::getLastHeaders();
// Remember-me cookie should not be set on failure
        $this->assertArrayNotHasKey('Set-Cookie-Auth', $hdrs);
    }

    public function testRememberMeCookiePersistsLoginAcrossRequests(): void
    {
        $router = $this->makeRouterWithAuth();
        $router->add(['POST'], 'login', function ($req, Response $res): Response {

            $ok = auth()->attempt([
                'email' => 'alice@example.com',
                'password' => 'secret123',
            ], true);
            return Response::json(['ok' => $ok]);
        });
// Perform login with remember
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/login';
        ob_start();
        $router->dispatch('/login');
        ob_end_clean();
        $hdrs = Response::getLastHeaders();
        $this->assertArrayHasKey('Set-Cookie-Auth', $hdrs);
        $cookieHeader = $hdrs['Set-Cookie-Auth'];
        $this->assertStringContainsString('ish_remember=', $cookieHeader);
// Extract token value
        preg_match('/ish_remember=([^;]+)/', $cookieHeader, $m);
        $token = $m[1] ?? '';
        $this->assertNotSame('', $token);
// New request without session but with remember cookie should authenticate
        $_COOKIE = ['ish_remember' => $token];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/protected';
        $router2 = $this->makeRouterWithAuth();
        $router2->add(['GET'], 'protected', function ($req, Response $res): Response {

            if (auth()->check()) {
                return Response::text('ok');
            }
            return Response::text('nope', 401);
        }, [Authenticate::class]);
        ob_start();
        $router2->dispatch('/protected');
        $out = ob_get_clean();
        $this->assertSame('ok', $out);
    }
}
