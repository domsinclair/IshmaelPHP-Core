<?php

declare(strict_types=1);

namespace Ishmael\Core\Security;

use RuntimeException;

/**
 * CsrfTokenManager is responsible for generating, storing, and validating
 * anti-CSRF tokens bound to the current user session. It relies on the
 * SessionManager exposed via app('session').
 */
final class CsrfTokenManager
{
    /** @var string Session key where the token is stored */
    public const SESSION_KEY_TOKEN = '_csrf.token';
/** @var string Session key where the last rotation timestamp is stored */
    public const SESSION_KEY_ROTATED_AT = '_csrf.rotated_at';
/**
     * Return the current CSRF token for this session, generating one if missing.
     */
    public function getToken(): string
    {
        $mgr = app('session');
        if ($mgr === null) {
            throw new RuntimeException('CSRF requires an active session. Ensure StartSessionMiddleware is enabled.');
        }
        $token = (string) $mgr->get(self::SESSION_KEY_TOKEN, '');
        if ($token === '') {
            $token = $this->generateRandomToken();
            $mgr->put(self::SESSION_KEY_TOKEN, $token);
            $mgr->put(self::SESSION_KEY_ROTATED_AT, time());
        }
        return $token;
    }

    /**
     * Regenerate the CSRF token and return the new value.
     */
    public function regenerateToken(): string
    {
        $mgr = app('session');
        if ($mgr === null) {
            throw new RuntimeException('CSRF requires an active session. Ensure StartSessionMiddleware is enabled.');
        }
        $token = $this->generateRandomToken();
        $mgr->put(self::SESSION_KEY_TOKEN, $token);
        $mgr->put(self::SESSION_KEY_ROTATED_AT, time());
        return $token;
    }

    /**
     * Validate a presented token against the session token using
     * a timing-safe comparison.
     *
     * @param string|null $presented The token provided by the client.
     * @return bool True when the token matches the session token.
     */
    public function validateToken(?string $presented): bool
    {
        if ($presented === null || $presented === '') {
            return false;
        }
        $expected = $this->getToken();
        return $this->hashEquals($expected, $presented);
    }

    /**
     * Rotate token based on app policy (placeholder for future hooks like login).
     */
    public function rotateOnPrivilegeChange(): void
    {
        $this->regenerateToken();
    }

    /** Generate a URL-safe base64 token. */
    private function generateRandomToken(): string
    {
        $bytes = random_bytes(32);
        $b64 = base64_encode($bytes);
// URL-safe variant without padding
        return rtrim(strtr($b64, '+/', '-_'), '=');
    }

    /**
     * Timing-safe string comparison.
     */
    private function hashEquals(string $a, string $b): bool
    {
        if (function_exists('hash_equals')) {
            return hash_equals($a, $b);
        }
        // Fallback (PHP >=5.6 has hash_equals, but keep a constant-time-ish alternative)
        $len = strlen($a);
        if ($len !== strlen($b)) {
            return false;
        }
        $res = 0;
        for ($i = 0; $i < $len; $i++) {
            $res |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $res === 0;
    }
}
