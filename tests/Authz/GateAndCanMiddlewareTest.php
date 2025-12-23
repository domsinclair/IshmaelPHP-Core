<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Auth\AuthManager;
use Ishmael\Core\Http\Middleware\Can;
use Ishmael\Core\Http\Middleware\StartSessionMiddleware;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Router;
use PHPUnit\Framework\TestCase;

final class GateAndCanMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['ISH_TESTING'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_POST = [];
        $_GET = [];
        $_COOKIE = [];
        app([]);
// Ensure an auth manager and fake user
        app(['auth' => new AuthManager()]);
// Seed a simple user in session manually to avoid DB needs
        app(['session' => new \Ishmael\Core\Session\SessionManager(new \Ishmael\Core\Session\FileSessionStore(storage_path('sessions')), null, 600)]);
        app('session')->put(AuthManager::SESSION_KEY, 1);
        // Bind a simple gate with an ability
        gate()->define('post.view', function (?array $user, mixed $resource): bool {

            // allow when user present or resource marked public
            if ($user !== null) {
                return true;
            }
            return is_array($resource) && (($resource['public'] ?? false) === true);
        });
    }

    public function testGateAllowsDefinedAbility(): void
    {
        $allowed = gate()->allows('post.view', ['id' => 9]);
        $this->assertTrue($allowed);
    }

    public function testCanMiddlewareDeniesWithJson403(): void
    {
        // Simulate guest by clearing auth session
        app('session')->remove(AuthManager::SESSION_KEY);
        $router = new Router();
        Router::setActive($router);
        $router->setGlobalMiddleware([
            StartSessionMiddleware::class,
        ]);
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $router->add(['GET'], 'secret', function ($req, Response $res): Response {

            return Response::text('ok');
        }, [Can::for('post.view')]);
        ob_start();
        $router->dispatch('/secret');
        $out = ob_get_clean();
        $this->assertSame(403, http_response_code());
        $this->assertJson($out);
        $payload = json_decode((string)$out, true);
        $this->assertSame('forbidden', $payload['error'] ?? null);
    }
}
