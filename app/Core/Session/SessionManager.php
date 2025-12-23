<?php

declare(strict_types=1);

namespace Ishmael\Core\Session;

/**
 * SessionManager coordinates a concrete SessionStore and provides
 * ergonomic helpers including flash data lifecycle and lazy persistence.
 */
final class SessionManager
{
    private SessionStore $store;
    private string $id;
/** @var array<string,mixed> */
    private array $data;
    private bool $dirty = false;
    private int $ttlSeconds;
/**
     * @param SessionStore $store Concrete persistence backend
     * @param string|null $id Existing id to use or null to generate
     * @param int $ttlSeconds Session TTL in seconds
     */
    public function __construct(SessionStore $store, ?string $id, int $ttlSeconds)
    {
        $this->store = $store;
        $this->id = $id ?: $store->generateId();
        $this->ttlSeconds = $ttlSeconds;
        $this->data = $store->load($this->id);
        if (!isset($this->data['_flash'])) {
            $this->data['_flash'] = [
                'now' => [],
                'next' => [],
            ];
        }
    }

    /** Promote next flash to now and clear old now bucket. */
    public function advanceFlash(): void
    {
        $this->data['_flash']['now'] = $this->data['_flash']['next'] ?? [];
        $this->data['_flash']['next'] = [];
        $this->dirty = true;
// flash lifecycle change must be persisted
    }

    public function getId(): string
    {
        return $this->id;
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        return $this->data;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        $this->dirty = true;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
        $this->dirty = true;
    }

    /**
     * Set a flash message to be available for the next request only.
     */
    public function flash(string $key, mixed $value): void
    {
        $this->data['_flash']['next'][$key] = $value;
        $this->dirty = true;
    }

    /**
     * Retrieve a flash value from the current request lifecycle.
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $bucket = $this->data['_flash']['now'] ?? [];
        return $bucket[$key] ?? $default;
    }

    /**
     * Remove current flash value (useful after consumption in views/APIs).
     */
    public function forgetFlash(string $key): void
    {
        if (isset($this->data['_flash']['now'][$key])) {
            unset($this->data['_flash']['now'][$key]);
            $this->dirty = true;
        }
    }

    /** Regenerate the session identifier (session fixation defense). */
    public function regenerateId(): void
    {
        $old = $this->id;
        $this->id = $this->store->generateId();
// Persist under new id and destroy old
        $this->store->persist($this->id, $this->data, $this->ttlSeconds);
        $this->store->destroy($old);
    }

    /** Destroy the session entirely. */
    public function invalidate(): void
    {
        $this->store->destroy($this->id);
        $this->data = ['_flash' => ['now' => [], 'next' => []]];
        $this->id = $this->store->generateId();
        $this->dirty = true;
    }

    /** Persist only if mutated or lifecycle advanced. */
    public function persistIfDirty(): void
    {
        if ($this->dirty) {
            $this->store->persist($this->id, $this->data, $this->ttlSeconds);
            $this->dirty = false;
        }
    }
}
