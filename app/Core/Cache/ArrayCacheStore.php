<?php
declare(strict_types=1);

namespace Ishmael\Core\Cache;

final class ArrayCacheStore implements CacheStore
{
    /** @var array<string, array<string, array{value:mixed, expiresAt:int|null, tags:string[]}>> */
    private array $data = [];

    public function get(string $key, mixed $default = null, string $namespace = 'default'): mixed
    {
        $ns = $this->data[$namespace] ?? null;
        if (!$ns) {
            return $default;
        }
        $entry = $ns[$key] ?? null;
        if (!$entry) {
            return $default;
        }
        if ($entry['expiresAt'] !== null && $entry['expiresAt'] < time()) {
            unset($this->data[$namespace][$key]);
            return $default;
        }
        return $this->deepUnserialize($entry['value']);
    }

    public function set(string $key, mixed $value, ?int $ttlSeconds = null, string $namespace = 'default', array $tags = []): void
    {
        $expires = $ttlSeconds && $ttlSeconds > 0 ? time() + $ttlSeconds : null;
        $this->data[$namespace][$key] = [
            'value' => $this->deepSerialize($value),
            'expiresAt' => $expires,
            'tags' => array_values(array_unique($tags)),
        ];
    }

    public function has(string $key, string $namespace = 'default'): bool
    {
        $ns = $this->data[$namespace] ?? null;
        if (!$ns || !isset($ns[$key])) {
            return false;
        }
        $entry = $ns[$key];
        if ($entry['expiresAt'] !== null && $entry['expiresAt'] < time()) {
            unset($this->data[$namespace][$key]);
            return false;
        }
        return true;
    }

    public function delete(string $key, string $namespace = 'default'): void
    {
        unset($this->data[$namespace][$key]);
    }

    public function clearNamespace(string $namespace): void
    {
        unset($this->data[$namespace]);
    }

    public function clearTag(string $tag, ?string $namespace = null): void
    {
        if ($namespace !== null) {
            $this->clearTagInNamespace($tag, $namespace);
            return;
        }
        foreach (array_keys($this->data) as $ns) {
            $this->clearTagInNamespace($tag, $ns);
        }
    }

    private function clearTagInNamespace(string $tag, string $namespace): void
    {
        if (!isset($this->data[$namespace])) {
            return;
        }
        foreach ($this->data[$namespace] as $key => $entry) {
            if (in_array($tag, $entry['tags'], true)) {
                unset($this->data[$namespace][$key]);
            }
        }
    }

    public function remember(string $key, callable $callback, ?int $ttlSeconds = null, string $namespace = 'default', array $tags = []): mixed
    {
        if ($this->has($key, $namespace)) {
            return $this->get($key, null, $namespace);
        }
        $value = $callback();
        $this->set($key, $value, $ttlSeconds, $namespace, $tags);
        return $value;
    }

    public function purgeExpired(?string $namespace = null): void
    {
        $namespaces = $namespace ? [$namespace] : array_keys($this->data);
        $now = time();
        foreach ($namespaces as $ns) {
            if (!isset($this->data[$ns])) continue;
            foreach ($this->data[$ns] as $key => $entry) {
                if ($entry['expiresAt'] !== null && $entry['expiresAt'] < $now) {
                    unset($this->data[$ns][$key]);
                }
            }
        }
    }

    private function deepSerialize(mixed $value): string
    {
        return serialize($value);
    }

    private function deepUnserialize(string $payload): mixed
    {
        return unserialize($payload, ['allowed_classes' => true]);
    }
}
