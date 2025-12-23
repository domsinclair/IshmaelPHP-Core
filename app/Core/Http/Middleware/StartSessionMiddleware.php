<?php

declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Session\CookieSessionStore;
use Ishmael\Core\Session\DatabaseSessionStore;
use Ishmael\Core\Session\FileSessionStore;
use Ishmael\Core\Session\SessionManager;
use Ishmael\Core\Session\SessionStore;

/**
 * StartSessionMiddleware initializes the session for the current request and
 * persists it at the end of the pipeline if mutated (lazy write). It also
 * advances flash data using next-request semantics.
 */
final class StartSessionMiddleware
{
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        $config = (array) config('session', []);
        $driver = (string) ($config['driver'] ?? 'file');
        $lifetimeMin = (int) ($config['lifetime'] ?? 120);
        $ttl = max(60, $lifetimeMin * 60);
// minimum 60s
        $cookieName = (string) ($config['cookie'] ?? 'ish_session');
        $store = $this->resolveStore($driver, $config, $cookieName);
// Determine session id from cookie (file/db drivers) or generate new
        $sid = $_COOKIE[$cookieName] ?? null;
        if ($driver === 'cookie') {
        // id not used; store carries payload inside cookie
            $sid = $sid && is_string($sid) ? $sid : null;
        } else {
            if (!is_string($sid) || $sid === '') {
                $sid = $store->generateId();
            }
        }

        $manager = new SessionManager($store, is_string($sid) ? $sid : null, $ttl);
// Flash lifecycle: promote next->now
        $manager->advanceFlash();
// Expose via service locator helpers
        app(['session' => $manager]);
// Call downstream
        $response = $next($req, $res);
// Persist lazily if dirty
        $manager->persistIfDirty();
// Set cookie headers as needed
        $this->applyCookie($response, $driver, $manager, $cookieName, $config, $ttl);
        return $response;
    }

    private function resolveStore(string $driver, array $config, string $cookieName): SessionStore
    {
        return match ($driver) {
            'cookie'   => new CookieSessionStore($cookieName, (string) env('APP_KEY', '')),
            'database' => new DatabaseSessionStore('sessions'),
            default    => new FileSessionStore((string) ($config['files'] ?? storage_path('sessions'))),
        };
    }

    private function applyCookie(Response $response, string $driver, SessionManager $manager, string $cookieName, array $config, int $ttl): void
    {
        $path = (string) ($config['path'] ?? '/');
        $domain = (string) ($config['domain'] ?? '');
        $secure = (bool) ($config['secure'] ?? false);
        $httpOnly = (bool) ($config['http_only'] ?? true);
        $sameSite = (string) ($config['same_site'] ?? 'Lax');
        $expires = $ttl > 0 ? (time() + $ttl) : 0;
        if ($driver === 'cookie') {
        // Read encoded cookie value from the store handoff
            $value = $_SERVER['ISH_SESSION_COOKIE_VALUE'] ?? null;
            $delete = ($_SERVER['ISH_SESSION_COOKIE_DELETE'] ?? null) === '1';
            if ($delete) {
                $expires = time() - 3600;
                $value = '';
            }
            if (is_string($value)) {
                $this->setCookieHeader($response, $cookieName, $value, $expires, $path, $domain, $secure, $httpOnly, $sameSite);
            }
        } else {
        // Set/refresh id cookie
            $sid = $manager->getId();
            $this->setCookieHeader($response, $cookieName, $sid, $expires, $path, $domain, $secure, $httpOnly, $sameSite);
        }
    }

    private function setCookieHeader(
        Response $response,
        string $name,
        string $value,
        int $expires,
        string $path,
        string $domain,
        bool $secure,
        bool $httpOnly,
        string $sameSite
    ): void {
        $parts = [
            rawurlencode($name) . '=' . rawurlencode($value),
        ];
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

        $header = 'Set-Cookie: ' . implode('; ', $parts);
// Response does not emit headers directly; store for testing/consumers
        $response->header('Set-Cookie', implode('; ', $parts));
// Also set native header when running under SAPI
        if (!headers_sent()) {
            header($header, false);
        }
    }
}
