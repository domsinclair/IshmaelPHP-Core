<?php
declare(strict_types=1);

use Ishmael\Core\RouteCache;
use Ishmael\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouteCacheTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure deterministic timezone for timestamps
        date_default_timezone_set('UTC');
        // Signal bootstrap to not install global handlers that could interfere
        $_SERVER['ISH_TESTING'] = '1';
    }

    public function testRouteCacheFailsOnClosureMiddleware(): void
    {
        $router = new Router();
        // Add a route with a closure middleware which is not cacheable
        $router->add(['GET'], '/x', 'DummyHandler', [fn() => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Non-cacheable middleware');
        RouteCache::compile($router, __DIR__);
    }

    public function testRouteCacheSucceedsWithClassStringMiddleware(): void
    {
        $router = new Router();
        // Use invokable class string middleware which is cacheable
        $router->add(['GET'], '/ok', 'DummyHandler', [ExampleMiddleware::class]);
        $compiled = RouteCache::compile($router, __DIR__);
        $this->assertArrayHasKey('routes', $compiled);
        $this->assertArrayHasKey('meta', $compiled);
        $this->assertSame('/ok', '/' . trim($compiled['routes'][0]['pattern'] ?? '', '/'));
        $this->assertArrayNotHasKey('warnings', $compiled['meta']);
    }

    public function testForceModeStripsNonCacheableAndAddsWarnings(): void
    {
        $router = new Router();
        $router->add(['GET'], '/warn', 'DummyHandler', [fn() => null]);
        $compiled = RouteCache::compile($router, __DIR__, true);
        $this->assertArrayHasKey('warnings', $compiled['meta']);
        $this->assertSame([], $compiled['routes'][0]['middleware']);
    }
}

// Test double for middleware
final class ExampleMiddleware { public function __invoke($req, $res, $next) { return $next($req, $res); } }
