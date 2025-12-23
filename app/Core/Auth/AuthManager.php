<?php

declare(strict_types=1);

namespace Ishmael\Core\Auth;

use Ishmael\Core\Session\SessionManager;

/**
 * AuthManager provides a minimal session-backed authentication API with optional
 * remember-me cookie support. It stores only the user id in session by default
 * under the key '_auth.user_id'.
 */
final class AuthManager
{
    public const SESSION_KEY = '_auth.user_id';
    private UserProviderInterface $provider;
    private ?SessionManager $session;
    public function __construct(?UserProviderInterface $provider = null, ?SessionManager $session = null)
    {
        $this->provider = $provider ?? (\app('user_provider') instanceof UserProviderInterface ? \app('user_provider') : new DatabaseUserProvider());
    /** @var SessionManager|null $sess */
        $sess = $session ?? (\app('session'));
        $this->session = $sess;
    }

    /**
     * Resolve the current SessionManager, preferring a fresh lookup from the app
     * container to avoid stale references when the AuthManager was constructed
     * before StartSessionMiddleware bound the session.
     */
    private function resolveSession(): ?SessionManager
    {
        $current = \app('session');
        if ($current instanceof SessionManager) {
        // Cache the resolved session for minor perf without risking staleness
            $this->session = $current;
            return $current;
        }
        return $this->session;
    }

    /** Attempt to authenticate using credentials. */
    public function attempt(array $credentials, bool $remember = false): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);
        if (!$user) {
            return false;
        }
        if (!$this->provider->validateCredentials($user, $credentials)) {
            return false;
        }
        $this->login($user, $remember);
        return true;
    }

    /** Log the given user in by storing id into the session and rotating id. */
    public function login(array $user, bool $remember = false): void
    {
        $sess = $this->resolveSession();
        if ($sess === null) {
            throw new \RuntimeException('Auth requires an active session. Ensure StartSessionMiddleware is enabled.');
        }
        $id = $this->extractUserId($user);
// Session fixation defense: rotate id on privilege change
        $sess->regenerateId();
        $sess->put(self::SESSION_KEY, $id);
// Signal remember-me cookie set if requested; middleware will emit cookie
        if ($remember) {
            $_SERVER['ISH_AUTH_REMEMBER_SET'] = $this->createRememberToken((string)$id);
        }
    }

    /** Logout the current user, clearing session and remember-me cookie. */
    public function logout(): void
    {
        $sess = $this->resolveSession();
        if ($sess !== null) {
            $sess->remove(self::SESSION_KEY);
            $sess->regenerateId();
        }
        $_SERVER['ISH_AUTH_REMEMBER_CLEAR'] = '1';
    }

    /** Whether a user id is present in the session. */
    public function check(): bool
    {
        $sess = $this->resolveSession();
        if ($sess === null) {
            return false;
        }
        return $sess->has(self::SESSION_KEY);
    }

    /** True when no user is authenticated. */
    public function guest(): bool
    {
        return !$this->check();
    }

    /** Get the authenticated user's id or null. */
    public function id(): string|int|null
    {
        $sess = $this->resolveSession();
        if ($sess === null) {
            return null;
        }
        /** @var string|int|null $id */
        $id = $sess->get(self::SESSION_KEY);
        return $id;
    }

    /** Retrieve the full user record via provider using the id from session. */
    public function user(): ?array
    {
        $id = $this->id();
        if ($id === null) {
            return null;
        }
        return $this->provider->retrieveById($id);
    }

    /**
     * Validate and decode a remember-me token. Returns user id on success or null.
     */
    public function validateRememberToken(string $token): ?string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        [$p64, $m64] = $parts;
        $payloadJson = base64_decode(strtr($p64, '-_', '+/'), true);
        $mac = base64_decode(strtr($m64, '-_', '+/'), true);
        if ($payloadJson === false || $mac === false) {
            return null;
        }
        $calc = hash_hmac('sha256', $payloadJson, $this->appKey(), true);
        if (!hash_equals($mac, $calc)) {
            return null;
        }
        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }
        $uid = isset($payload['uid']) ? (string)$payload['uid'] : null;
        $iat = isset($payload['iat']) ? (int)$payload['iat'] : 0;
        $cfg = (array) (\config('auth.remember_me') ?? []);
        $ttlMin = (int) ($cfg['ttl'] ?? 43200);
        $exp = $iat + max(60, $ttlMin * 60);
        if (time() > $exp) {
            return null;
        }
        if (($cfg['bind_user_agent'] ?? true) === true) {
            $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
            $uah = sha1($ua);
            if (!isset($payload['uah']) || !hash_equals((string)$payload['uah'], $uah)) {
                return null;
            }
        }
        return $uid;
    }

    /** Create a signed remember-me token for the given user id. */
    private function createRememberToken(string $userId): string
    {
        $cfg = (array) (\config('auth.remember_me') ?? []);
        $payload = [
            'uid' => $userId,
            'iat' => time(),
        ];
        if (($cfg['bind_user_agent'] ?? true) === true) {
            $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
            $payload['uah'] = sha1($ua);
        }
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $mac = hash_hmac('sha256', (string)$json, $this->appKey(), true);
        $p64 = rtrim(strtr(base64_encode((string)$json), '+/', '-_'), '=');
        $m64 = rtrim(strtr(base64_encode($mac), '+/', '-_'), '=');
        return $p64 . '.' . $m64;
    }

    private function extractUserId(array $user): string|int
    {
        $cfg = (array) (\config('auth.providers.users') ?? []);
        $idCol = (string)($cfg['id_column'] ?? 'id');
        $id = $user[$idCol] ?? null;
        if ($id === null) {
            throw new \InvalidArgumentException('User record does not contain id column: ' . $idCol);
        }
        return is_int($id) ? $id : (string)$id;
    }

    /**
     * Resolve the application key for HMAC signing.
     *
     * Resolution order (to be test-friendly and runtime-safe):
     * 1) $_SERVER['APP_KEY'] (set by test harness or front controller)
     * 2) getenv('APP_KEY')
     * 3) env('APP_KEY') loaded from .env via helpers
     *
     * Accepts the common base64: prefix and returns raw bytes when base64-encoded,
     * otherwise returns the raw string as provided. Throws when the key is empty.
     */
    private function appKey(): string
    {
        $raw = '';
// Prefer superglobal/getenv so tests using putenv() are respected without touching .env cache
        if (isset($_SERVER['APP_KEY']) && is_string($_SERVER['APP_KEY']) && $_SERVER['APP_KEY'] !== '') {
            $raw = (string) $_SERVER['APP_KEY'];
        } elseif (($g = getenv('APP_KEY')) !== false && $g !== '') {
            $raw = (string) $g;
        } else {
            $raw = (string) (\env('APP_KEY', ''));
        }

        $key = trim($raw);
        if ($key === '') {
            throw new \RuntimeException('APP_KEY is not set; run ish key:generate');
        }
        if (str_starts_with($key, 'base64:')) {
            $b64 = (string) substr($key, 7);
            $bin = base64_decode($b64, true);
            if ($bin !== false) {
                return $bin;
            }
        }
        return $key;
    }
}
