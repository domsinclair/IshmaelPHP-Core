<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * JsonBodyParserMiddleware
 *
 * When the request Content-Type is application/json (or +json) and the body is non-empty,
 * this middleware decodes the JSON into an associative array and replaces the request's
 * parsed body with the decoded data. If decoding fails, it returns a 400 response.
 */
final class JsonBodyParserMiddleware
{
    /**
     * Invoke middleware.
     */
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        $contentType = strtolower($req->getHeader('Content-Type') ?? '');
        $method = strtoupper($req->getMethod());
        $shouldParse = $contentType !== '' && (str_contains($contentType, 'application/json') || str_contains($contentType, '+json'));
        if ($shouldParse && !in_array($method, ['GET','HEAD'], true)) {
            $raw = $req->getRawBody();
            if ($raw !== '') {
                $data = json_decode($raw, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return Response::json([
                        'error' => 'Invalid JSON',
                        'message' => json_last_error_msg(),
                    ], 400);
                }
                if (is_array($data)) {
                    $req = $req->withParsedBody($data);
                }
            }
        }
        return $next($req, $res);
    }
}
