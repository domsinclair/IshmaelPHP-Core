<?php

declare(strict_types=1);

namespace Ishmael\Core\Cache;

/**
 * CacheStore defines a minimal key/value cache with TTL, namespaces, and tags.
 */
interface CacheStore
{
    /**
     * Retrieve a value by key or return default when not found/expired.
     *
     * @param string $key
     * @param mixed $default
     * @param string $namespace
     * @return mixed
     */
    public function get(string $key, mixed $default = null, string $namespace = 'default'): mixed;
/**
     * Store a value with TTL in seconds. TTL null or <=0 means forever (no expiry).
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttlSeconds
     * @param string $namespace
     * @param string[] $tags
     */
    public function set(string $key, mixed $value, ?int $ttlSeconds = null, string $namespace = 'default', array $tags = []): void;
/**
     * Determine if a key exists and is not expired.
     */
    public function has(string $key, string $namespace = 'default'): bool;
/**
     * Delete a key.
     */
    public function delete(string $key, string $namespace = 'default'): void;
/**
     * Clear all keys in a namespace.
     */
    public function clearNamespace(string $namespace): void;
/**
     * Clear all items that have a given tag.
     */
    public function clearTag(string $tag, ?string $namespace = null): void;
/**
     * Remember pattern: return cached value or compute via callback and cache it.
     *
     * @param string $key
     * @param callable $callback
     * @param int|null $ttlSeconds
     * @param string $namespace
     * @param string[] $tags
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttlSeconds = null, string $namespace = 'default', array $tags = []): mixed;
/**
     * Purge expired entries (best-effort, optional for some stores).
     */
    public function purgeExpired(?string $namespace = null): void;
}
