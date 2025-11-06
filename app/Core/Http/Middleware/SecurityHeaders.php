<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * SecurityHeaders middleware applies a curated set of HTTP response security headers
 * with sane defaults and configuration overrides.
 *
 * Headers applied by default:
 * - X-Frame-Options: SAMEORIGIN
 * - X-Content-Type-Options: nosniff
 * - Referrer-Policy: no-referrer-when-downgrade
 * - Content-Security-Policy: basic self-only default with frame-ancestors 'self'
 * - Strict-Transport-Security (HSTS): disabled by default; enable via config for HTTPS
 * - Permissions-Policy: optional (empty by default)
 *
 * Configuration source: config('security.headers'). You can also use the static factory
 * helpers to provide per-route overrides: SecurityHeaders::with([...]) or
 * SecurityHeaders::disabled() to skip applying headers for a specific route.
 */
final class SecurityHeaders
{
    /** @var array<string,mixed> */
    private array $config;

    /** @var bool */
    private bool $disabled = false;

    /**
     * @param array<string,mixed> $overrides Optional overrides for this instance only.
     */
    public function __construct(array $overrides = [])
    {
        /** @var array<string,mixed> $base */
        $base = (array) (\config('security.headers') ?? []);
        $this->config = array_replace_recursive($this->defaultHeadersConfig(), $base, $overrides);
        if (isset($overrides['enabled'])) {
            $this->disabled = !(bool)$overrides['enabled'];
        }
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
        if ((bool)($this->config['enabled'] ?? true) === false || $this->disabled) {
            return $next($req, $res);
        }

        // Execute downstream first so we set headers on the final response
        $response = $next($req, $res);

        // Apply configured headers
        $this->applyHeaders($req, $response);

        return $response;
    }

    /**
     * Factory helper to create a middleware callable with per-route overrides.
     *
     * @param array<string,mixed> $overrides
     * @return callable(Request, Response, callable): Response
     */
    public static function with(array $overrides): callable
    {
        return function(Request $req, Response $res, callable $next) use ($overrides): Response {
            $mw = new self($overrides);
            return $mw($req, $res, $next);
        };
    }

    /**
     * Factory helper that returns a middleware callable which is disabled and no-ops.
     * Useful for route-level opt-out when the middleware is applied globally.
     *
     * @return callable(Request, Response, callable): Response
     */
    public static function disabled(): callable
    {
        return self::with(['enabled' => false]);
    }

    /** Apply headers to the Response and emit via native header() for SAPI parity. */
    private function applyHeaders(Request $req, Response $response): void
    {
        // X-Frame-Options
        $xfo = (string)($this->config['x_frame_options'] ?? 'SAMEORIGIN');
        if ($xfo !== '') {
            $this->setHeader($response, 'X-Frame-Options', $xfo);
        }

        // X-Content-Type-Options
        $xcto = (string)($this->config['x_content_type_options'] ?? 'nosniff');
        if ($xcto !== '') {
            $this->setHeader($response, 'X-Content-Type-Options', $xcto);
        }

        // Referrer-Policy
        $ref = (string)($this->config['referrer_policy'] ?? 'no-referrer-when-downgrade');
        if ($ref !== '') {
            $this->setHeader($response, 'Referrer-Policy', $ref);
        }

        // Permissions-Policy (optional)
        $perm = (string)($this->config['permissions_policy'] ?? '');
        if ($perm !== '') {
            $this->setHeader($response, 'Permissions-Policy', $perm);
        }

        // Content-Security-Policy
        $csp = (string)($this->config['content_security_policy'] ?? "default-src 'self'; frame-ancestors 'self'");
        if ($csp !== '') {
            $this->setHeader($response, 'Content-Security-Policy', $csp);
        }

        // Strict-Transport-Security (HSTS)
        $hstsCfg = (array)($this->config['hsts'] ?? []);
        $hstsEnabled = (bool)($hstsCfg['enabled'] ?? false);
        if ($hstsEnabled) {
            // Only set on HTTPS unless force is true
            $onlyHttps = (bool)($hstsCfg['only_https'] ?? true);
            $isHttps = $this->isHttps($req);
            if (!$onlyHttps || $isHttps) {
                $maxAge = (int)($hstsCfg['max_age'] ?? 15552000); // 180 days
                $include = (bool)($hstsCfg['include_subdomains'] ?? false);
                $preload = (bool)($hstsCfg['preload'] ?? false);
                $parts = ['max-age=' . max(0, $maxAge)];
                if ($include) { $parts[] = 'includeSubDomains'; }
                if ($preload) { $parts[] = 'preload'; }
                $this->setHeader($response, 'Strict-Transport-Security', implode('; ', $parts));
            }
        }
    }

    private function isHttps(Request $req): bool
    {
        $proto = strtolower((string)($req->getHeader('X-Forwarded-Proto') ?? ''));
        if ($proto === 'https') { return true; }
        $server = $_SERVER;
        if ((isset($server['HTTPS']) && ($server['HTTPS'] === 'on' || $server['HTTPS'] === '1'))
            || (isset($server['SERVER_PORT']) && (string)$server['SERVER_PORT'] === '443')) {
            return true;
        }
        return false;
    }

    private function setHeader(Response $response, string $name, string $value): void
    {
        $response->header($name, $value);
        if (!headers_sent()) {
            header($name . ': ' . $value, true);
        }
    }

    /**
     * Default config for security headers (used when config/security.php lacks values).
     *
     * @return array<string,mixed>
     */
    private function defaultHeadersConfig(): array
    {
        return [
            'enabled' => true,
            'x_frame_options' => 'SAMEORIGIN',
            'x_content_type_options' => 'nosniff',
            'referrer_policy' => 'no-referrer-when-downgrade',
            'permissions_policy' => '', // e.g., "camera=(), microphone=(), geolocation=()"
            'content_security_policy' => "default-src 'self'; frame-ancestors 'self'",
            'hsts' => [
                'enabled' => false,
                'only_https' => true,
                'max_age' => 15552000, // 180 days
                'include_subdomains' => false,
                'preload' => false,
            ],
        ];
    }
}
