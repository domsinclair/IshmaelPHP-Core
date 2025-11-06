<?php
declare(strict_types=1);

namespace Ishmael\Core\Cache;

use PDO;
use PDOException;

final class DatabaseCacheStore implements CacheStore
{
    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo, string $table = 'cache')
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->init();
    }

    private function init(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            namespace TEXT NOT NULL,
            key TEXT NOT NULL,
            value BLOB NOT NULL,
            expires_at INTEGER NULL,
            tags TEXT NULL,
            PRIMARY KEY(namespace, key)
        )";
        $this->pdo->exec($sql);
    }

    public function get(string $key, mixed $default = null, string $namespace = 'default'): mixed
    {
        $stmt = $this->pdo->prepare("SELECT value, expires_at FROM {$this->table} WHERE namespace = :ns AND key = :k LIMIT 1");
        $stmt->execute([':ns' => $namespace, ':k' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return $default;
        $expires = $row['expires_at'] !== null ? (int)$row['expires_at'] : null;
        if ($expires !== null && $expires < time()) {
            $this->delete($key, $namespace);
            return $default;
        }
        return $this->unserializeValue((string)$row['value']);
    }

    public function set(string $key, mixed $value, ?int $ttlSeconds = null, string $namespace = 'default', array $tags = []): void
    {
        $expires = $ttlSeconds && $ttlSeconds > 0 ? time() + $ttlSeconds : null;
        $payload = $this->serializeValue($value);
        $tagJson = json_encode(array_values(array_unique($tags)));
        $sql = "INSERT INTO {$this->table} (namespace, key, value, expires_at, tags)
                VALUES (:ns, :k, :v, :e, :t)
                ON CONFLICT(namespace, key) DO UPDATE SET value = excluded.value, expires_at = excluded.expires_at, tags = excluded.tags";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':ns' => $namespace, ':k' => $key, ':v' => $payload, ':e' => $expires, ':t' => $tagJson]);
    }

    public function has(string $key, string $namespace = 'default'): bool
    {
        $stmt = $this->pdo->prepare("SELECT expires_at FROM {$this->table} WHERE namespace = :ns AND key = :k LIMIT 1");
        $stmt->execute([':ns' => $namespace, ':k' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        $expires = $row['expires_at'] !== null ? (int)$row['expires_at'] : null;
        if ($expires !== null && $expires < time()) {
            $this->delete($key, $namespace);
            return false;
        }
        return true;
    }

    public function delete(string $key, string $namespace = 'default'): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE namespace = :ns AND key = :k");
        $stmt->execute([':ns' => $namespace, ':k' => $key]);
    }

    public function clearNamespace(string $namespace): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE namespace = :ns");
        $stmt->execute([':ns' => $namespace]);
    }

    public function clearTag(string $tag, ?string $namespace = null): void
    {
        if ($namespace !== null) {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE namespace = :ns AND tags LIKE :tag");
            $stmt->execute([':ns' => $namespace, ':tag' => '%' . $this->likeTag($tag) . '%']);
            return;
        }
        $this->pdo->exec("DELETE FROM {$this->table} WHERE tags LIKE '%" . $this->likeTag($tag) . "%'");
    }

    private function likeTag(string $tag): string
    {
        // tags stored as JSON array; simple LIKE match is OK for basic tags
        return '"' . str_replace(['"'], ['\\"'], $tag) . '"';
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
        if ($namespace !== null) {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE namespace = :ns AND expires_at IS NOT NULL AND expires_at < :now");
            $stmt->execute([':ns' => $namespace, ':now' => time()]);
            return;
        }
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE expires_at IS NOT NULL AND expires_at < :now");
        $stmt->execute([':now' => time()]);
    }

    private function serializeValue(mixed $value): string
    {
        return base64_encode(serialize($value));
    }

    private function unserializeValue(string $payload): mixed
    {
        $raw = base64_decode($payload, true);
        if ($raw === false) return null;
        return unserialize($raw, ['allowed_classes' => true]);
    }
}
