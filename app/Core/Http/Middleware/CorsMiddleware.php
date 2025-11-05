<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * CorsMiddleware adds CORS headers and can short-circuit preflight OPTIONS requests.
 *
 * Configuration resolution order:
 * - config('cors') if available, else config('app.cors')
 * - Fallback to permissive defaults for development
 *
 * Supported config keys:
 * - enabled (bool): toggle CORS processing (default true)
 * - allow_origin (string): value for Access-Control-Allow-Origin (default '*')
 * - allow_methods (string): comma-separated (default 'GET,POST,PUT,PATCH,DELETE,OPTIONS')
 * - allow_headers (string): comma-separated (default 'Content-Type,Authorization,X-Requested-With')
 * - allow_credentials (bool): include Access-Control-Allow-Credentials: true (default false)
 * - max_age (int): seconds for Access-Control-Max-Age (default 600)
 */
final class CorsMiddleware
{
    /** @var array<string,mixed> */
    private array $config;

    /** @param array<string,mixed> $config */
    public function __construct(array $config = [])
    {
        $this->config = $config ?: $this->loadConfig();
    }

    /**
     * Middleware signature.
     */
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        if (!(bool)($this->config['enabled'] ?? true)) {
            return $next($req, $res);
        }

        $method = strtoupper($req->getMethod());
        $res = $this->applyHeaders($res);

        if ($method === 'OPTIONS') {
            // Preflight: short-circuit with 204 No Content
            return $res->setStatusCode(204);
        }

        $response = $next($req, $res);
        return $this->applyHeaders($response);
    }

    private function applyHeaders(Response $res): Response
    {
        $res = $res->header('Vary', 'Origin');
        $origin = (string)($this->config['allow_origin'] ?? '*');
        $methods = (string)($this->config['allow_methods'] ?? 'GET,POST,PUT,PATCH,DELETE,OPTIONS');
        $headers = (string)($this->config['allow_headers'] ?? 'Content-Type,Authorization,X-Requested-With');
        $res = $res->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Methods', $methods)
            ->header('Access-Control-Allow-Headers', $headers);
        if (!empty($this->config['allow_credentials'])) {
            $res = $res->header('Access-Control-Allow-Credentials', 'true');
        }
        if (!empty($this->config['max_age'])) {
            $res = $res->header('Access-Control-Max-Age', (string)(int)$this->config['max_age']);
        }
        return $res;
    }

    /**
     * @return array<string,mixed>
     */
    private function loadConfig(): array
    {
        $cfg = [];
        if (function_exists('config')) {
            $cfg = (array) (config('cors', []) ?: config('app.cors', []));
        }
        return $cfg + [
            'enabled' => true,
            'allow_origin' => '*',
            'allow_methods' => 'GET,POST,PUT,PATCH,DELETE,OPTIONS',
            'allow_headers' => 'Content-Type,Authorization,X-Requested-With',
            'allow_credentials' => false,
            'max_age' => 600,
        ];
    }
}
