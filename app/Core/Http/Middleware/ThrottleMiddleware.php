<?php

declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * ThrottleMiddleware implements a token bucket rate limiter using the configured CacheStore.
 *
 * Features
 * - Token bucket with configurable capacity and refill schedule
 * - Cache-backed counters so limits persist across PHP processes
 * - Identity scoping: by default uses client IP; can be customized
 * - Jittered reset windows to reduce thundering herd when many keys reset at once
 * - Emits RateLimit-* headers and Retry-After on 429 responses
 *
 * Configuration and options
 * - You can supply a preset name defined in config('rate_limit.presets') via the 'preset' option
 * - Or pass inline options: capacity, refillTokens, refillInterval, namespace, identityResolver
 *
 * Headers (RFC 9298 style):
 * - RateLimit-Limit: total capacity of the bucket
 * - RateLimit-Remaining: tokens left after this request decision
 * - RateLimit-Reset: seconds until the bucket's reset time
 *
 * Usage examples:
 *   Router::get('/api', handler, [ThrottleMiddleware::with(['preset' => 'default'])]);
 *   Router::get('/search', handler, [ThrottleMiddleware::with([
 *       'capacity' => 30,
 *       'refillTokens' => 30,
 *       'refillInterval' => 60,
 *   ])]);
 */
final class ThrottleMiddleware
{
    private int $capacity;
    private int $refillTokens;
    private int $refillInterval;
// seconds
    private string $namespace;
/** @var callable(Request): string */
    private $identityResolver;
    private float $jitterRatio;
/**
     * @param array{preset?:string, capacity?:int, refillTokens?:int, refillInterval?:int, namespace?:string, identityResolver?:callable|null, jitterRatio?:float} $options
     */
    public function __construct(array $options = [])
    {
        $opts = $this->resolveOptions($options);
        $this->capacity = max(1, (int)$opts['capacity']);
        $this->refillTokens = max(1, (int)$opts['refillTokens']);
        $this->refillInterval = max(1, (int)$opts['refillInterval']);
        $this->namespace = (string)$opts['namespace'];
        $this->identityResolver = is_callable($opts['identityResolver'])
            ? $opts['identityResolver']
            : [$this, 'defaultIdentity'];
        $this->jitterRatio = max(0.0, min(1.0, (float)($opts['jitterRatio'] ?? 0.2)));
    }

    /**
     * Static factory for router configuration.
     * @param array<string,mixed> $options
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
        $identity = ($this->identityResolver)($req);
        $routeKey = $this->routeKeyFromRequest($req);
        $bucketKey = 'throttle:' . sha1($routeKey . '|' . $identity);
        $now = time();
        $state = (array) (cache()->get($bucketKey, null, $this->namespace) ?? []);
        $tokens = (int)($state['tokens'] ?? $this->capacity);
        $lastRefill = (int)($state['last_refill'] ?? $now);
        $resetTs = (int)($state['reset_ts'] ?? 0);
// Initialize reset time with jitter if missing or in the past
        if ($resetTs <= $now) {
            $jitter = (int)floor($this->refillInterval * $this->jitterRatio * $this->deterministicJitter($identity));
            $resetTs = $now + $this->refillInterval + $jitter;
        }

        // Calculate how many refill intervals elapsed
        if ($now > $lastRefill) {
            $elapsed = $now - $lastRefill;
            $intervals = intdiv($elapsed, $this->refillInterval);
            if ($intervals > 0) {
                $add = $intervals * $this->refillTokens;
                $tokens = min($this->capacity, $tokens + $add);
                $lastRefill += $intervals * $this->refillInterval;
            }
        }

        $allowed = false;
        if ($tokens > 0) {
            $tokens -= 1;
            $allowed = true;
        }

        // Persist state; choose TTL to at least cover until reset to avoid leaking keys
        $ttl = max(1, $resetTs - $now);
        cache()->set($bucketKey, [
            'tokens' => $tokens,
            'last_refill' => $lastRefill > 0 ? $lastRefill : $now,
            'reset_ts' => $resetTs,
            'capacity' => $this->capacity,
            'refill_tokens' => $this->refillTokens,
            'refill_interval' => $this->refillInterval,
            'identity' => $identity,
            'route' => $routeKey,
        ], $ttl, $this->namespace);
        $remaining = max(0, $tokens);
        $resetIn = max(1, $resetTs - $now);
        if (!$allowed) {
            $tooMany = new Response('Too Many Requests', 429, []);
            $tooMany->header('RateLimit-Limit', (string)$this->capacity)
                ->header('RateLimit-Remaining', (string)$remaining)
                ->header('RateLimit-Reset', (string)$resetIn)
                ->header('Retry-After', (string)$resetIn);
            if (method_exists($tooMany, 'refreshLastHeadersSnapshot')) {
                $tooMany->refreshLastHeadersSnapshot();
            }
            return $tooMany;
        }

        $response = $next($req, $res);
        $response->header('RateLimit-Limit', (string)$this->capacity)
            ->header('RateLimit-Remaining', (string)$remaining)
            ->header('RateLimit-Reset', (string)$resetIn);
        if (method_exists($response, 'refreshLastHeadersSnapshot')) {
            $response->refreshLastHeadersSnapshot();
        }
        return $response;
    }

    /**
     * Resolve options by merging presets and inline overrides.
     * @param array<string,mixed> $options
     * @return array{capacity:int, refillTokens:int, refillInterval:int, namespace:string, identityResolver:callable|null, jitterRatio:float}
     */
    private function resolveOptions(array $options): array
    {
        $presets = (array) (\config('rate_limit.presets') ?? []);
        $presetName = (string)($options['preset'] ?? ($options['name'] ?? 'default'));
        $preset = (array)($presets[$presetName] ?? []);
        $base = [
            'capacity' => 60,
            'refillTokens' => 60,
            'refillInterval' => 60,
            'namespace' => (string)(\config('rate_limit.namespace') ?? 'rate'),
            'identityResolver' => null,
            'jitterRatio' => (float)(\config('rate_limit.jitter_ratio') ?? 0.2),
        ];
        $merged = array_replace($base, $preset, $options);
// Normalize keys that might come from config as strings
        $merged['capacity'] = (int)$merged['capacity'];
        $merged['refillTokens'] = (int)$merged['refillTokens'];
        $merged['refillInterval'] = (int)$merged['refillInterval'];
        $merged['namespace'] = (string)$merged['namespace'];
        $merged['jitterRatio'] = (float)$merged['jitterRatio'];
        return $merged;
    }

    /**
     * Default identity resolver: attempts to extract client IP from headers.
     */
    private function defaultIdentity(Request $req): string
    {
        $ip = $req->getHeader('X-Forwarded-For')
            ?? $req->getHeader('X-Real-IP')
            ?? $req->getHeader('Client-IP')
            ?? 'unknown';
// If multiple IPs in X-Forwarded-For, take the first
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        return $ip;
    }

    /**
     * Compute a deterministic jitter factor in [0,1) based on identity so that the same
     * client gets a stable offset and different clients tend to spread out.
     */
    private function deterministicJitter(string $identity): float
    {
        $hash = substr(sha1($identity), 0, 8);
        $val = hexdec($hash);
// 32-bit
        return fmod($val / (float)0xFFFFFFFF, 1.0);
    }

    /**
     * Build a stable route key from the request method and normalized path.
     */
    private function routeKeyFromRequest(Request $req): string
    {
        $method = strtoupper($req->getMethod());
        $path = $req->getPath();
        return $method . ' ' . $path;
    }
}
