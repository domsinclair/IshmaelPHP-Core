<?php
declare(strict_types=1);

namespace Ishmael\Core\Cache;

final class FileCacheStore implements CacheStore
{
    private string $baseDir;
    private string $prefix;

    public function __construct(string $baseDir, string $prefix = '')
    {
        $this->baseDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDir), "\\/");
        $this->prefix = $prefix !== '' ? preg_replace('/[^A-Za-z0-9:_-]/', '_', $prefix) : '';
    }

    public function get(string $key, mixed $default = null, string $namespace = 'default'): mixed
    {
        $path = $this->pathFor($namespace, $key);
        if (!is_file($path)) {
            return $default;
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return $default;
        }
        $entry = @json_decode($raw, true);
        if (!is_array($entry)) {
            // Corrupted; remove
            @unlink($path);
            return $default;
        }
        $expiresAt = $entry['expiresAt'] ?? null;
        if ($expiresAt !== null && $expiresAt < time()) {
            @unlink($path);
            return $default;
        }
        return $this->unserializeValue($entry['value'] ?? null);
    }

    public function set(string $key, mixed $value, ?int $ttlSeconds = null, string $namespace = 'default', array $tags = []): void
    {
        $dir = $this->dirFor($namespace);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $path = $this->pathFor($namespace, $key);
        $expiresAt = $ttlSeconds && $ttlSeconds > 0 ? time() + $ttlSeconds : null;
        $payload = json_encode([
            'expiresAt' => $expiresAt,
            'tags' => array_values(array_unique($tags)),
            'value' => $this->serializeValue($value),
        ], JSON_UNESCAPED_SLASHES);
        // Atomic write via temp file rename
        $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
        @file_put_contents($tmp, $payload);
        @rename($tmp, $path);
    }

    public function has(string $key, string $namespace = 'default'): bool
    {
        $path = $this->pathFor($namespace, $key);
        if (!is_file($path)) return false;
        $raw = @file_get_contents($path);
        if ($raw === false) return false;
        $entry = @json_decode($raw, true);
        if (!is_array($entry)) return false;
        $expiresAt = $entry['expiresAt'] ?? null;
        if ($expiresAt !== null && $expiresAt < time()) {
            @unlink($path);
            return false;
        }
        return true;
    }

    public function delete(string $key, string $namespace = 'default'): void
    {
        $path = $this->pathFor($namespace, $key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function clearNamespace(string $namespace): void
    {
        $dir = $this->dirFor($namespace);
        if (!is_dir($dir)) return;
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.cache.json') ?: [] as $file) {
            @unlink($file);
        }
    }

    public function clearTag(string $tag, ?string $namespace = null): void
    {
        $namespaces = $namespace ? [$namespace] : $this->allNamespaces();
        foreach ($namespaces as $ns) {
            $dir = $this->dirFor($ns);
            if (!is_dir($dir)) continue;
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.cache.json') ?: [] as $file) {
                $raw = @file_get_contents($file);
                if ($raw === false) continue;
                $entry = @json_decode($raw, true);
                if (!is_array($entry)) { @unlink($file); continue; }
                $tags = $entry['tags'] ?? [];
                if (is_array($tags) && in_array($tag, $tags, true)) {
                    @unlink($file);
                }
            }
        }
    }

    public function remember(string $key, callable $callback, ?int $ttlSeconds = null, string $namespace = 'default', array $tags = []): mixed
    {
        $existing = $this->get($key, null, $namespace);
        if ($existing !== null) return $existing;
        $value = $callback();
        $this->set($key, $value, $ttlSeconds, $namespace, $tags);
        return $value;
    }

    public function purgeExpired(?string $namespace = null): void
    {
        $namespaces = $namespace ? [$namespace] : $this->allNamespaces();
        $now = time();
        foreach ($namespaces as $ns) {
            $dir = $this->dirFor($ns);
            if (!is_dir($dir)) continue;
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.cache.json') ?: [] as $file) {
                $raw = @file_get_contents($file);
                if ($raw === false) { @unlink($file); continue; }
                $entry = @json_decode($raw, true);
                $expiresAt = is_array($entry) ? ($entry['expiresAt'] ?? null) : null;
                if ($expiresAt !== null && $expiresAt < $now) {
                    @unlink($file);
                }
            }
        }
    }

    private function serializeValue(mixed $value): string
    {
        return base64_encode(serialize($value));
    }

    private function unserializeValue(?string $payload): mixed
    {
        if ($payload === null) return null;
        $raw = base64_decode($payload, true);
        if ($raw === false) return null;
        return unserialize($raw, ['allowed_classes' => true]);
    }

    private function safeNamespace(string $namespace): string
    {
        $ns = str_replace(['/', '\\', '..'], '_', $namespace);
        return $this->prefix !== '' ? $this->prefix . '_' . $ns : $ns;
    }

    private function fileKey(string $key): string
    {
        // Normalize windows path quirks and build a safe filename
        $k = str_replace(['\\', '/'], '__', $key);
        $k = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$k);
        if (strlen($k) > 200) {
            $k = substr($k, 0, 50) . '_' . sha1($k);
        }
        return $k . '.cache.json';
    }

    private function dirFor(string $namespace): string
    {
        $dir = $this->baseDir . DIRECTORY_SEPARATOR . $this->safeNamespace($namespace);
        return $dir;
    }

    private function pathFor(string $namespace, string $key): string
    {
        return $this->dirFor($namespace) . DIRECTORY_SEPARATOR . $this->fileKey($key);
    }

    /**
     * Discover existing namespaces as directory names
     * @return string[]
     */
    private function allNamespaces(): array
    {
        $ret = [];
        if (!is_dir($this->baseDir)) return $ret;
        foreach (glob($this->baseDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $dir) {
            $ret[] = basename($dir);
        }
        return $ret;
    }
}
