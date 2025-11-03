<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Support\Log;

/**
 * RequestLoggingMiddleware (example)
 * - Logs request start and finish with duration in milliseconds
 * - Off by default; enable in app/router config or per-route
 */
final class RequestLoggingMiddleware
{
    /**
     * Middleware signature: function(Request $req, Response $res, callable $next): Response
     */
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $start = microtime(true);

        Log::info('HTTP request start', [
            'method' => $method,
            'uri' => $uri,
        ]);

        $response = $next($req, $res);

        $durationMs = (int) round((microtime(true) - $start) * 1000);
        Log::info('HTTP request finish', [
            'method' => $method,
            'uri' => $uri,
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
        ]);

        return $response;
    }
}
