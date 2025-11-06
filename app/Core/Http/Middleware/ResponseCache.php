<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Cache\CacheManager;
use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * ResponseCache middleware caches full HTTP responses for idempotent requests (GET/HEAD)
 * using a CacheStore backend. It applies conservative safety rules by default and
 * allows per-route overrides via the static factory method with().
 *
 * Safety rules (by default):
 * - Only cache GET and HEAD requests.
 * - Skip when Authorization header present or when session/auth cookies are present.
 * - Skip when query contains no-cache=1 (configurable).
 * - Do not store a response that sets a Set-Cookie header or declares Cache-Control: private|no-store.
 * - Only cache successful 2xx responses.
 *
 * Options supported via constructor or ResponseCache::with():
 * - ttl: int seconds to live (null to use cache.default_ttl)
 * - namespace: string cache namespace (default "http")
 * - vary: string[] of request header names to include in the cache key (default [Accept, Accept-Encoding])
 * - allowAuth: bool allow caching when Authorization or session cookie present (default false)
 * - honorNoCacheQuery: bool when true, skip if query contains no-cache=1 (default true)
 */
final class ResponseCache
{
    /** @var string[] */
    private array $vary;
    private ?int $ttl;
    private string $namespace;
    private bool $allowAuth;
    private bool $honorNoCacheQuery;

    /**
     * @param array{ttl?:int|null, namespace?:string, vary?:array<int,string>, allowAuth?:bool, honorNoCacheQuery?:bool} $options
     */
    public function __construct(array $options = [])
    {
        $this->ttl = array_key_exists('ttl', $options) ? ($options['ttl'] === null ? null : (int)$options['ttl']) : null;
        $this->namespace = (string)($options['namespace'] ?? 'http');
        $this->vary = array_values(array_map([$this, 'normalizeHeaderName'], (array)($options['vary'] ?? ['Accept', 'Accept-Encoding'])));
        $this->allowAuth = (bool)($options['allowAuth'] ?? false);
        $this->honorNoCacheQuery = (bool)($options['honorNoCacheQuery'] ?? true);
    }

    /**
     * Static factory to create a middleware callable suitable for router configuration.
     *
     * Example:
     *   Router::get('/posts', handler, [ResponseCache::with(['ttl' => 60])]);
     *
     * @param array{ttl?:int|null, namespace?:string, vary?:array<int,string>, allowAuth?:bool, honorNoCacheQuery?:bool} $options
     * @return callable(Request, Response, callable): Response
     */
    public static function with(array $options = []): callable
    {
        $instance = new self($options);
        return [$instance, '__invoke'];
    }

    /**
     * Middleware signature: function(Request $req, Response $res, callable $next): Response
     * @param Request $req
     * @param Response $res
     * @param callable $next function(Request, Response): Response
     * @return Response
     */
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        if (!$this->isMethodCacheable($req->getMethod())) {
            return $next($req, $res);
        }
        if ($this->honorNoCacheQuery && $this->hasNoCacheQuery($req)) {
            return $next($req, $res);
        }
        if (!$this->allowAuth && $this->hasAuthOrSession($req)) {
            return $next($req, $res);
        }

        $key = $this->makeCacheKey($req);
        $ns = $this->namespace;

        $cached = cache()->get($key, null, $ns);
        if (is_array($cached) && isset($cached['status'], $cached['headers'], $cached['body'])) {
            $resp = new Response((string)$cached['body'], (int)$cached['status'], (array)$cached['headers']);
            // Explicitly set Age header if stored
            if (isset($cached['stored_at'])) {
                $age = max(0, time() - (int)$cached['stored_at']);
                $resp->header('Age', (string)$age);
            }
            // Standard cache hit header for diagnostics
            $resp->header('X-Cache', 'HIT');
            if (method_exists($resp, 'refreshLastHeadersSnapshot')) {
                $resp->refreshLastHeadersSnapshot();
            }
            return $resp;
        }

        $response = $next($req, $res);

        if ($this->shouldStoreResponse($req, $response)) {
            $record = [
                'status' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => $response->getBody(),
                'stored_at' => time(),
            ];
            $ttl = $this->ttl; // may be null, CacheManager will use default
            cache()->set($key, $record, $ttl, $ns, []);
            // Mark response as MISS for observability
            $response->header('X-Cache', 'MISS');
            if ($ttl !== null) {
                $response->header('Cache-Control', 'public, max-age=' . max(0, (int)$ttl));
            }
            // Return a fresh Response to ensure X-Cache and any Cache-Control are visible to tests and emitters
            $resp = new Response($response->getBody(), $response->getStatusCode(), $response->getHeaders());
            // Reinforce header on the new instance
            $resp->header('X-Cache', 'MISS');
            if ($ttl !== null) {
                $resp->header('Cache-Control', 'public, max-age=' . max(0, (int)$ttl));
            }
            if (method_exists($resp, 'refreshLastHeadersSnapshot')) {
                $resp->refreshLastHeadersSnapshot();
            }
            return $resp;
        } else {
            // Mark on original and return a fresh instance to ensure headers snapshot is updated for tests and SAPI emitters
            $response->header('X-Cache', 'BYPASS');
            $resp = new Response($response->getBody(), $response->getStatusCode(), $response->getHeaders());
            // Re-assert header on the new instance too
            $resp->header('X-Cache', 'BYPASS');
            if (method_exists($resp, 'refreshLastHeadersSnapshot')) {
                $resp->refreshLastHeadersSnapshot();
            }
            return $resp;
        }
    }

    private function isMethodCacheable(string $method): bool
    {
        $m = strtoupper($method);
        return $m === 'GET' || $m === 'HEAD';
    }

    private function hasNoCacheQuery(Request $req): bool
    {
        $q = $req->getQueryParams();
        return isset($q['no-cache']) && (string)$q['no-cache'] !== '0' && $q['no-cache'] !== '';
    }

    private function hasAuthOrSession(Request $req): bool
    {
        // Authorization header
        $authz = (string)($req->getHeader('Authorization') ?? '');
        if ($authz !== '') { return true; }

        // Session cookie
        $sessionCookie = (string)(\config('session.cookie') ?? 'ish_session');
        if (isset($_COOKIE[$sessionCookie]) && (string)$_COOKIE[$sessionCookie] !== '') {
            return true;
        }
        // Remember-me cookie (auth)
        $rememberCookie = (string)(\config('auth.remember_me.cookie') ?? 'ish_remember');
        if (isset($_COOKIE[$rememberCookie]) && (string)$_COOKIE[$rememberCookie] !== '') {
            return true;
        }
        return false;
    }

    /**
     * Build a cache key with method, normalized URL (path + sorted query excluding cache busters),
     * and selected Vary headers.
     */
    private function makeCacheKey(Request $req): string
    {
        $method = strtoupper($req->getMethod());
        $path = $req->getPath();
        $query = $req->getQueryParams();
        // Remove common cache-buster signals
        unset($query['_'], $query['cb']);
        // Sort query for stable keys
        ksort($query);
        $qs = http_build_query($query);
        $urlPart = $path . ($qs !== '' ? '?' . $qs : '');

        $varyValues = [];
        foreach ($this->vary as $name) {
            $varyValues[$name] = (string)($req->getHeader($name) ?? '');
        }
        $varyPart = json_encode($varyValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $raw = $method . ' ' . $urlPart . '|' . $varyPart;
        return 'resp:' . sha1($raw);
    }

    private function shouldStoreResponse(Request $req, Response $response): bool
    {
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return false; // only 2xx
        }
        // Do not store if Set-Cookie is present
        $headers = $response->getHeaders();
        foreach ($headers as $k => $v) {
            if ($this->normalizeHeaderName($k) === 'set-cookie' || $this->normalizeHeaderName($k) === 'set-cookie-auth') {
                return false;
            }
        }
        // Respect Cache-Control on response
        $cc = '';
        foreach ($headers as $k => $v) {
            if ($this->normalizeHeaderName($k) === 'cache-control') { $cc = strtolower($v); break; }
        }
        if ($cc !== '') {
            if (str_contains($cc, 'no-store') || str_contains($cc, 'private')) {
                return false;
            }
        }
        // Skip if request had auth/session and not allowed
        if (!$this->allowAuth && $this->hasAuthOrSession($req)) {
            return false;
        }
        return true;
    }

    private function normalizeHeaderName(string $name): string
    {
        return strtolower(str_replace('_', '-', $name));
    }
}
