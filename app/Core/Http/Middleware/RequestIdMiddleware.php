<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * RequestIdMiddleware
 * - Accepts incoming X-Request-Id or generates a UUIDv4
 * - Stores it in a global accessor (app('request_id')) for processors to use
 * - Adds X-Request-Id response header
 */
final class RequestIdMiddleware
{
    /**
     * Middleware signature: function(Request $req, Response $res, callable $next): Response
     */
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        $incoming = $req->getHeader('X-Request-Id');
        $requestId = $this->isValidRequestId($incoming) ? (string)$incoming : $this->uuidv4();

        if (function_exists('app')) {
            app(['request_id' => $requestId]);
        }

        $response = $next($req, $res);
        return $response->header('X-Request-Id', $requestId);
    }

    private function isValidRequestId(?string $id): bool
    {
        if (!$id) {
            return false;
        }
        // Allow UUIDs or any non-empty 20-200 char token (to support external IDs)
        $len = strlen($id);
        if ($len < 20 || $len > 200) {
            // If it looks like a UUID, accept even if shorter
            return (bool)preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $id);
        }
        return true;
    }

    private function uuidv4(): string
    {
        $data = random_bytes(16);
        // Set version to 0100
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // Set bits 6-7 to 10
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
