<?php

declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use DateTimeInterface;
use Ishmael\Core\Http\HttpValidators;
use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * ConditionalRequests middleware handles ETag/If-None-Match and Last-Modified/If-Modified-Since
 * for idempotent requests (GET/HEAD). It can use provided resolvers to compute validators or
 * derive them from the response (e.g., hash of body) when not supplied.
 *
 * Options:
 * - etagResolver: callable(Request, Response): ?string   // Return an ETag value (quoted or not). If null, auto from body.
 * - lastModifiedResolver: callable(Request, Response): ?DateTimeInterface // If null, header not set unless already present.
 * - allowWeak: bool // When true, a weak ETag comparison can satisfy a conditional GET (default true)
 */
final class ConditionalRequests
{
    /** @var callable(Request, Response): (?string)|null */
    private $etagResolver;
/** @var callable(Request, Response): (?DateTimeInterface)|null */
    private $lastModifiedResolver;
    private bool $allowWeak;
/**
     * @param array{etagResolver?:callable|null, lastModifiedResolver?:callable|null, allowWeak?:bool} $options
     */
    public function __construct(array $options = [])
    {
        $this->etagResolver = $options['etagResolver'] ?? null;
        $this->lastModifiedResolver = $options['lastModifiedResolver'] ?? null;
        $this->allowWeak = (bool)($options['allowWeak'] ?? true);
    }

    /**
     * Static factory to create a middleware callable suitable for router configuration.
     * @param array{etagResolver?:callable|null, lastModifiedResolver?:callable|null, allowWeak?:bool} $options
     * @return callable(Request, Response, callable): Response
     */
    public static function with(array $options = []): callable
    {
        $instance = new self($options);
        return [$instance, '__invoke'];
    }

    /**
     * Middleware signature: function(Request $req, Response $res, callable $next): Response
     */
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        $method = strtoupper($req->getMethod());
        if ($method !== 'GET' && $method !== 'HEAD') {
            return $next($req, $res);
        }

        // Run downstream to obtain the tentative response
        $response = $next($req, $res);
// Determine validators: prefer existing headers, else resolvers, else defaults
        $headers = $response->getHeaders();
        $etag = $headers['ETag'] ?? null;
        if ($etag === null) {
            if (is_callable($this->etagResolver)) {
                $candidate = ($this->etagResolver)($req, $response);
                if (is_string($candidate) && $candidate !== '') {
                    $response = $response->withEtag($candidate);
                    $etag = $response->getHeaders()['ETag'] ?? null;
                }
            }
        }
        if ($etag === null) {
// Default: derive from body (strong ETag)
            $response = $response->withEtag(HttpValidators::makeEtag($response->getBody(), false));
            $etag = $response->getHeaders()['ETag'] ?? null;
        }

        $lastMod = $headers['Last-Modified'] ?? null;
        if ($lastMod === null && is_callable($this->lastModifiedResolver)) {
            $lm = ($this->lastModifiedResolver)($req, $response);
            if ($lm instanceof DateTimeInterface) {
                $response = $response->withLastModified($lm);
                $lastMod = $response->getHeaders()['Last-Modified'] ?? null;
            }
        }

        // Evaluate conditions
        $ifNoneMatch = $req->getHeader('If-None-Match');
        $ifModifiedSince = $req->getHeader('If-Modified-Since');
        $etagMatches = false;
        if ($ifNoneMatch !== null && $etag !== null) {
            $clientEtags = HttpValidators::parseIfNoneMatch($ifNoneMatch);
            $etagMatches = HttpValidators::isEtagMatch($clientEtags, $etag, $this->allowWeak);
        }

        $modifiedSince = null;
        if ($ifModifiedSince !== null && $lastMod !== null) {
            $clientDate = HttpValidators::parseHttpDate($ifModifiedSince);
            $serverDate = HttpValidators::parseHttpDate($lastMod);
            if ($clientDate && $serverDate) {
        // Consider not modified when client's timestamp is equal to or after server's Last-Modified.
                // This tolerates client clock being ahead; if the client is behind (even by 1s), treat as modified (200).
                $modifiedSince = ($clientDate->getTimestamp() >= $serverDate->getTimestamp());
            }
        }

        $notModified = false;
        if ($ifNoneMatch !== null) {
        // If-None-Match takes precedence if present
            $notModified = $etagMatches;
        } elseif ($ifModifiedSince !== null) {
            $notModified = ($modifiedSince === true);
        }

        if ($notModified) {
// Build 304 response preserving caching headers
            $preserve = $this->preserveHeaders($response);
            $not = new Response('', 304, $preserve);
            if (method_exists($not, 'refreshLastHeadersSnapshot')) {
                $not->refreshLastHeadersSnapshot();
            }
            return $not;
        }

        // Validators applied on 200
        if (method_exists($response, 'refreshLastHeadersSnapshot')) {
            $response->refreshLastHeadersSnapshot();
        }
        return $response;
    }

    /**
     * Select headers to preserve on a 304 response.
     * RFC7232 allows ETag and Last-Modified, plus any cache validators and Vary/Cache-Control/Expires.
     * @param Response $response
     * @return array<string,string>
     */
    private function preserveHeaders(Response $response): array
    {
        $source = $response->getHeaders();
        $keep = ['ETag', 'Last-Modified', 'Cache-Control', 'Expires', 'Vary'];
        $preserved = [];
        foreach ($keep as $name) {
            if (isset($source[$name])) {
                $preserved[$name] = $source[$name];
            }
        }
        return $preserved;
    }
}
