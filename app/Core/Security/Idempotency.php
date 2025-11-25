<?php
declare(strict_types=1);

namespace Ishmael\Core\Security;

/**
 * Simple idempotency token helper for HTML form submissions.
 *
 * Tokens are stored in the session under a namespaced array with timestamps and
 * consumed exactly once. Expired tokens are cleaned opportunistically.
 */
final class Idempotency
{
    private const SESSION_KEY = '_idem.tokens';
    private const DEFAULT_TTL = 1800; // 30 minutes
    private const INPUT_NAME = 'idem_token';

    /** Generate a one-time token and store it with timestamp in session. */
    public static function mint(): string
    {
        $mgr = app('session');
        if ($mgr === null) {
            // Fall back to native session as a last resort for dev ergonomics
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
            $bucket =& $_SESSION[self::SESSION_KEY];
            if (!is_array($bucket)) { $bucket = []; }
            $token = self::randomToken();
            $bucket[$token] = time();
            return $token;
        }

        /** @var array<string,int> $bucket */
        $bucket = (array) $mgr->get(self::SESSION_KEY, []);
        $token = self::randomToken();
        $bucket[$token] = time();
        $mgr->put(self::SESSION_KEY, $bucket);
        return $token;
    }

    /**
     * Consume a token if present and not expired. Returns true on first valid use.
     * Cleans up expired tokens opportunistically.
     */
    public static function consume(?string $token, int $ttlSeconds = self::DEFAULT_TTL): bool
    {
        $token = (string) ($token ?? '');
        if ($token === '') {
            return false;
        }
        $now = time();
        $cutoff = $now - max(1, $ttlSeconds);

        $mgr = app('session');
        if ($mgr === null) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
            $bucket =& $_SESSION[self::SESSION_KEY];
            if (!is_array($bucket)) { $bucket = []; }
            // Cleanup
            foreach ($bucket as $t => $ts) {
                if (!is_int($ts) || $ts < $cutoff) { unset($bucket[$t]); }
            }
            if (!array_key_exists($token, $bucket)) {
                return false;
            }
            $ts = (int) $bucket[$token];
            if ($ts < $cutoff) { unset($bucket[$token]); return false; }
            unset($bucket[$token]);
            return true;
        }

        /** @var array<string,int> $bucket */
        $bucket = (array) $mgr->get(self::SESSION_KEY, []);
        // Cleanup expired
        foreach ($bucket as $t => $ts) {
            if (!is_int($ts) || $ts < $cutoff) { unset($bucket[$t]); }
        }
        if (!array_key_exists($token, $bucket)) {
            $mgr->put(self::SESSION_KEY, $bucket);
            return false;
        }
        $ts = (int) $bucket[$token];
        if ($ts < $cutoff) {
            unset($bucket[$token]);
            $mgr->put(self::SESSION_KEY, $bucket);
            return false;
        }
        unset($bucket[$token]);
        $mgr->put(self::SESSION_KEY, $bucket);
        return true;
    }

    /** Get conventional input name for forms. */
    public static function inputName(): string
    {
        return self::INPUT_NAME;
    }

    /** Convenience: return a hidden field HTML element with a freshly minted token. */
    public static function field(): string
    {
        $name = htmlspecialchars(self::INPUT_NAME, ENT_QUOTES, 'UTF-8');
        $token = htmlspecialchars(self::mint(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="' . $name . '" value="' . $token . '">';
    }

    private static function randomToken(): string
    {
        $bytes = random_bytes(16);
        $b64 = base64_encode($bytes);
        return rtrim(strtr($b64, '+/', '-_'), '=');
    }
}
