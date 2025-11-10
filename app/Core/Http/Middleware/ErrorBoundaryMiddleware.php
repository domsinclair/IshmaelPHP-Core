<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Logger;

/**
 * ErrorBoundaryMiddleware
 * - Catches uncaught throwables from downstream handlers
 * - Logs with request context
 * - Returns a 500 response with content negotiation (JSON/HTML)
 * - Ensures an X-Correlation-Id header is present (uses app('request_id') when available)
 */
final class ErrorBoundaryMiddleware
{
    /**
     * Middleware signature: function(Request $req, Response $res, callable $next): Response
     */
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        try {
            $out = $next($req, $res);
            return $out instanceof Response ? $out : Response::text((string)$out);
        } catch (\Throwable $e) {
            $accept = $this->negotiate($req->getHeader('Accept'));
            $debug = (bool) (config('app.debug') ?? false);
            $rid = $this->getCorrelationId();

            // Log with context
            $ctx = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'method' => $req->getMethod(),
                'path' => $req->getPath(),
                'request_id' => $rid,
            ];
            Logger::critical('Unhandled exception during request', $ctx);

            if ($accept === 'json') {
                $payload = [
                    'error' => [
                        'id' => $rid,
                        'status' => 500,
                        'title' => 'Internal Server Error',
                        'detail' => $debug ? ($e->getMessage()) : 'An unexpected error occurred',
                    ],
                ];
                if ($debug) {
                    $payload['error']['trace'] = $e->getTrace();
                }
                return Response::json($payload, 500, ['X-Correlation-Id' => $rid]);
            }

            // HTML
            if ($debug) {
                $body = '<h1>Internal Server Error</h1>'
                    . '<p><strong>Correlation Id:</strong> ' . htmlspecialchars($rid) . '</p>'
                    . '<p>' . htmlspecialchars($e->getMessage()) . '</p>'
                    . '<pre>' . htmlspecialchars((string)$e) . '</pre>';
            } else {
                $body = '<h1>Internal Server Error</h1>'
                    . '<p><strong>Correlation Id:</strong> ' . htmlspecialchars($rid) . '</p>'
                    . '<p>Our team has been notified.</p>';
            }
            return Response::html($body, 500, ['X-Correlation-Id' => $rid]);
        }
    }

    private function negotiate(?string $accept): string
    {
        $accept = strtolower(trim((string)$accept));
        if ($accept === '') {
            return 'html';
        }
        if (str_contains($accept, 'application/json') || str_contains($accept, 'json')) {
            return 'json';
        }
        return 'html';
    }

    private function getCorrelationId(): string
    {
        $rid = null;
        if (function_exists('app')) {
            $rid = app('request_id');
        }
        if (is_string($rid) && $rid !== '') {
            return $rid;
        }
        // Fallback to random UUIDv4
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        $hex = bin2hex($data);
        return sprintf('%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
