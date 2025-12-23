<?php

declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Auth\AuthManager;
use Ishmael\Core\Auth\DatabaseUserProvider;
use Ishmael\Core\Auth\PhpPasswordHasher;
use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * RememberMeMiddleware wires the Auth services, restores authentication from a
 * remember-me cookie when the session is missing, and emits/clears the cookie
 * when the AuthManager signals a change.
 */
final class RememberMeMiddleware
{
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        // Ensure core auth services are bound for downstream usage
        if (!\app('hasher')) {
            \app(['hasher' => new PhpPasswordHasher()]);
        }
        if (!\app('user_provider')) {
            \app(['user_provider' => new DatabaseUserProvider()]);
        }
        if (!\app('auth')) {
            \app(['auth' => new AuthManager()]);
        }

        /** @var AuthManager $auth */
        $auth = \app('auth');
// Restore from remember-me if enabled and not already authenticated
        $cfg = (array) (\config('auth.remember_me') ?? []);
        $enabled = (bool) ($cfg['enabled'] ?? true);
        $cookieName = (string) ($cfg['cookie'] ?? 'ish_remember');
        if ($enabled && $auth->guest()) {
            $token = $_COOKIE[$cookieName] ?? '';
            if (is_string($token) && $token !== '') {
                $uid = $auth->validateRememberToken($token);
                if ($uid !== null) {
                    // Load user from provider and login without reissuing remember token
                    $user = (\app('user_provider'))->retrieveById($uid);
                    if (is_array($user)) {
                        $auth->login($user, false);
                    }
                }
            }
        }

        // Proceed
        $response = $next($req, $res);
// Apply cookie changes signaled by AuthManager
        $this->applyRememberCookie($response, $cfg);
        return $response;
    }

    /**
     * @param array<string,mixed> $cfg
     */
    private function applyRememberCookie(Response $response, array $cfg): void
    {
        $enabled = (bool) ($cfg['enabled'] ?? true);
        if (!$enabled) {
            return;
        }

        $cookieName = (string) ($cfg['cookie'] ?? 'ish_remember');
        $path = (string) ($cfg['path'] ?? '/');
        $domain = (string) ($cfg['domain'] ?? '');
        $secure = (bool) ($cfg['secure'] ?? false);
        $httpOnly = (bool) ($cfg['http_only'] ?? true);
        $sameSite = (string) ($cfg['same_site'] ?? 'Lax');
        $ttlMin = (int) ($cfg['ttl'] ?? 43200);
        $expires = time() + max(60, $ttlMin * 60);
        $set = $_SERVER['ISH_AUTH_REMEMBER_SET'] ?? null;
        $clear = ($_SERVER['ISH_AUTH_REMEMBER_CLEAR'] ?? null) === '1';
        if ($set !== null || $clear) {
            $value = is_string($set) ? $set : '';
            if ($clear) {
                $expires = time() - 3600;
                $value = '';
            }
            $headerValue = $this->buildCookie($cookieName, $value, $expires, $path, $domain, $secure, $httpOnly, $sameSite);
        // Emit via native header for runtime
            if (!headers_sent()) {
                header('Set-Cookie: ' . $headerValue, false);
            }
            // For tests, expose a dedicated header slot (Response cannot store multiple Set-Cookie reliably)
            $response->header('Set-Cookie-Auth', $headerValue);
        }
    }

    private function buildCookie(
        string $name,
        string $value,
        int $expires,
        string $path,
        string $domain,
        bool $secure,
        bool $httpOnly,
        string $sameSite
    ): string {
        $parts = [rawurlencode($name) . '=' . rawurlencode($value)];
        if ($expires > 0) {
            $parts[] = 'Expires=' . gmdate('D, d-M-Y H:i:s T', $expires);
            $parts[] = 'Max-Age=' . max(0, $expires - time());
        }
        $parts[] = 'Path=' . ($path ?: '/');
        if ($domain !== '') {
            $parts[] = 'Domain=' . $domain;
        }
        if ($secure) {
            $parts[] = 'Secure';
        }
        if ($httpOnly) {
            $parts[] = 'HttpOnly';
        }
        if ($sameSite) {
            $parts[] = 'SameSite=' . $sameSite;
        }
        return implode('; ', $parts);
    }
}
