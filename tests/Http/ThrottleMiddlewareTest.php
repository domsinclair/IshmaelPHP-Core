<?php
declare(strict_types=1);

use Ishmael\Core\Router;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Http\Middleware\ThrottleMiddleware;
use PHPUnit\Framework\TestCase;

final class ThrottleMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('base_path')) {
            require_once __DIR__ . '/../../app/Helpers/helpers.php';
        }
        // Basic server setup
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/throttle';
        $_SERVER['HTTP_X_REAL_IP'] = '203.0.113.5';
        // Ensure config for rate limit is loaded by including file directly if config helper not wired to auto-load
        // Not necessary here because ThrottleMiddleware provides defaults.
    }

    public function testAllowsWithinCapacityAndThenBlocks(): void
    {
        // Use a very small window to keep the test fast
        $mw = new ThrottleMiddleware([
            'capacity' => 3,
            'refillTokens' => 3,
            'refillInterval' => 60,
            'namespace' => 'rate_test_' . uniqid('', true),
        ]);

        $router = new Router();
        $router->add(['GET'], 'throttle', function($req, Response $res): Response {
            return Response::text('ok');
        }, [$mw]);

        // First 3 should pass
        for ($i = 0; $i < 3; $i++) {
            ob_start();
            $router->dispatch('/throttle');
            ob_end_clean();
            $headers = Response::getLastHeaders();
            $this->assertArrayHasKey('RateLimit-Remaining', $headers);
            $this->assertArrayHasKey('RateLimit-Limit', $headers);
            $this->assertArrayHasKey('RateLimit-Reset', $headers);
            $this->assertArrayNotHasKey('Retry-After', $headers);
        }

        // Fourth should be 429
        ob_start();
        $router->dispatch('/throttle');
        ob_end_clean();
        $headers = Response::getLastHeaders();
        $this->assertArrayHasKey('Retry-After', $headers);
        // We can't easily read status from headers; assert body and that remaining is 0 or small
        $this->assertSame('0', (string)$headers['RateLimit-Remaining']);
    }

    public function testHeadersContainLimitRemainingAndReset(): void
    {
        $mw = new ThrottleMiddleware([
            'capacity' => 2,
            'refillTokens' => 2,
            'refillInterval' => 10,
            'namespace' => 'rate_test_' . uniqid('', true),
        ]);
        $router = new Router();
        $router->add(['GET'], 'throttle', function($req, Response $res): Response {
            return Response::text('ok');
        }, [$mw]);

        ob_start();
        $router->dispatch('/throttle');
        ob_end_clean();
        $h1 = Response::getLastHeaders();
        $this->assertSame('2', (string)$h1['RateLimit-Limit']);
        $this->assertSame('1', (string)$h1['RateLimit-Remaining']);
        $this->assertTrue(((int)$h1['RateLimit-Reset']) >= 1);

        // Next request should reduce remaining further
        ob_start();
        $router->dispatch('/throttle');
        ob_end_clean();
        $h2 = Response::getLastHeaders();
        $this->assertSame('0', (string)$h2['RateLimit-Remaining']);
    }
}
