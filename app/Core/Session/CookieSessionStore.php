<?php
declare(strict_types=1);

namespace Ishmael\Core\Session;

/**
 * CookieSessionStore stores the entire session payload in a single encrypted, HMAC-signed cookie.
 * Suitable for small payloads and stateless deployments. Max size ~4KB.
 */
final class CookieSessionStore implements SessionStore
{
    private string $cookieName;
    private string $appKey;

    /**
     * @param string $cookieName Name of the cookie that holds the session.
     * @param string $appKey Secret application key (32 bytes recommended). May be base64-encoded.
     */
    public function __construct(string $cookieName, string $appKey)
    {
        $this->cookieName = $cookieName;
        $this->appKey = $this->normalizeKey($appKey);
    }

    public function load(string $id): array
    {
        // The $id is not used — cookie contains full payload.
        $raw = $_COOKIE[$this->cookieName] ?? null;
        if (!$raw || !is_string($raw)) {
            return [];
        }
        $decoded = $this->decode($raw);
        if ($decoded === null) {
            return [];
        }
        // TTL check (exp is unix ts)
        $exp = $decoded['exp'] ?? 0;
        if ($exp > 0 && time() > (int)$exp) {
            return [];
        }
        return is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
    }

    public function persist(string $id, array $data, int $ttlSeconds): void
    {
        $payload = [
            'exp'  => $ttlSeconds > 0 ? (time() + $ttlSeconds) : 0,
            'data' => $data,
        ];
        $packed = $this->encode($payload);
        // Actual Set-Cookie header is handled by middleware based on config for flags.
        // Here we only return the value via a superglobal handoff; middleware will read it.
        $_SERVER['ISH_SESSION_COOKIE_VALUE'] = $packed;
    }

    public function destroy(string $id): void
    {
        // Signal deletion via special flag; middleware will clear cookie.
        $_SERVER['ISH_SESSION_COOKIE_DELETE'] = '1';
    }

    public function generateId(): string
    {
        // ID unused with cookie store but kept for interface parity.
        return bin2hex(random_bytes(16));
    }

    private function encode(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $iv = random_bytes(16);
        $cipher = 'AES-256-CBC';
        $ciphertext = openssl_encrypt($json, $cipher, $this->appKey, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new \RuntimeException('Failed to encrypt cookie payload');
        }
        $mac = hash_hmac('sha256', $iv . $ciphertext, $this->appKey, true);
        return rtrim(strtr(base64_encode($iv . $mac . $ciphertext), '+/', '-_'), '=');
    }

    private function decode(string $cookie): ?array
    {
        $bin = base64_decode(strtr($cookie, '-_', '+/'), true);
        if ($bin === false || strlen($bin) < 16 + 32) {
            return null;
        }
        $iv = substr($bin, 0, 16);
        $mac = substr($bin, 16, 32);
        $ciphertext = substr($bin, 48);
        $calc = hash_hmac('sha256', $iv . $ciphertext, $this->appKey, true);
        if (!hash_equals($mac, $calc)) {
            return null;
        }
        $json = openssl_decrypt($ciphertext, 'AES-256-CBC', $this->appKey, OPENSSL_RAW_DATA, $iv);
        if ($json === false) {
            return null;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeKey(string $key): string
    {
        $trim = trim($key);
        if ($trim === '') {
            throw new \InvalidArgumentException('APP_KEY is not set; run ish key:generate');
        }
        // Accept base64-encoded keys (common convention)
        if (str_starts_with($trim, 'base64:')) {
            $trim = substr($trim, 7);
        }
        $bin = base64_decode($trim, true);
        if ($bin !== false && strlen($bin) >= 32) {
            return substr($bin, 0, 32);
        }
        // If not base64, use raw but pad/trim to 32 bytes
        if (strlen($trim) < 32) {
            return str_pad($trim, 32, "\0");
        }
        return substr($trim, 0, 32);
    }
}
