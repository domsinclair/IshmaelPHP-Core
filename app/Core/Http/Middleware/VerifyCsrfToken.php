<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Security\CsrfTokenManager;

/**
 * VerifyCsrfToken middleware enforces CSRF protection on state-changing requests.
 *
 * Behavior:
 * - Skips verification for configured methods (GET/HEAD/OPTIONS) and matching URIs.
 * - Accepts token via configurable headers (X-CSRF-Token, X-XSRF-Token),
 *   a hidden form field (default _token), or a query parameter of the same name.
 * - Returns a JSON error with status 419 when the client prefers JSON; otherwise
 *   returns a minimal HTML response with the same status.
 */
final class VerifyCsrfToken
{
    /** @var array<string,mixed> */
    private array $config;

    /**
     * @param array<string,mixed> $override Optional configuration override for testing or per-route tuning.
     */
    public function __construct(array $override = [])
    {
        $base = (array) (config('security.csrf') ?? []);
        $this->config = array_replace_recursive($base, $override);
    }

    /**
     * Middleware entrypoint.
     *
     * @param Request $req
     * @param Response $res
     * @param callable $next function(Request, Response): Response
     * @return Response
     */
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        if (!($this->config['enabled'] ?? true)) {
            return $next($req, $res);
        }

        $method = strtoupper($req->getMethod());
        $path = $req->getPath();

        // Method exemptions
        $exceptMethods = array_map('strtoupper', (array)($this->config['except_methods'] ?? ['GET', 'HEAD', 'OPTIONS']));
        if (in_array($method, $exceptMethods, true)) {
            return $next($req, $res);
        }

        // URI exemptions
        $exceptUris = (array)($this->config['except_uris'] ?? []);
        foreach ($exceptUris as $pattern) {
            if ($this->pathMatches((string)$pattern, $path)) {
                return $next($req, $res);
            }
        }

        $fieldName = (string)($this->config['field_name'] ?? '_token');
        $headers = (array)($this->config['header_names'] ?? ['X-CSRF-Token', 'X-XSRF-Token']);

        // Sources: headers, body field, query
        $presented = null;
        foreach ($headers as $h) {
            $val = $req->getHeader((string)$h);
            if (is_string($val) && $val !== '') {
                $presented = $val;
                break;
            }
        }
        if ($presented === null) {
            $body = $req->getParsedBody();
            $presented = isset($body[$fieldName]) ? (string)$body[$fieldName] : null;
        }
        if ($presented === null) {
            $query = $req->getQueryParams();
            $presented = isset($query[$fieldName]) ? (string)$query[$fieldName] : null;
        }

        $manager = new CsrfTokenManager();
        if (!$manager->validateToken($presented)) {
            return $this->failedResponse($req);
        }

        return $next($req, $res);
    }

    /**
     * Pattern matcher supporting '*' wildcards and prefix patterns.
     */
    private function pathMatches(string $pattern, string $path): bool
    {
        if ($pattern === '') {
            return false;
        }
        // If pattern ends with '*', treat as prefix
        if (str_ends_with($pattern, '*')) {
            $prefix = rtrim($pattern, '*');
            return str_starts_with($path, rtrim($prefix, '/'));
        }
        // Exact match
        return rtrim($pattern, '/') === rtrim($path, '/');
    }

    /**
     * Build a JSON or HTML failure response based on Accept headers.
     */
    private function failedResponse(Request $req): Response
    {
        $failure = (array)($this->config['failure'] ?? []);
        $status = (int)($failure['status'] ?? 419);
        $message = (string)($failure['message'] ?? 'CSRF token mismatch.');
        $code = (string)($failure['code'] ?? 'csrf_mismatch');

        $accept = (string)($req->getHeader('Accept') ?? '');
        $isJsonPreferred = str_contains(strtolower($accept), 'application/json')
            || strtolower((string)$req->getHeader('X-Requested-With')) === 'xmlhttprequest'
            || str_contains(strtolower((string)$req->getHeader('Content-Type')), 'application/json');

        if ($isJsonPreferred) {
            return Response::json(['error' => $message, 'code' => $code], $status);
        }
        $html = '<h1>Page Expired</h1><p>' . htmlspecialchars($message) . '</p>';
        return Response::html($html, $status);
    }
}
