<?php

declare(strict_types=1);

namespace Ishmael\Core\Session;

/**
 * SessionStore defines the minimal contract for session persistence backends.
 * Implementations must be safe across concurrent requests on Windows/macOS/Linux.
 */
interface SessionStore
{
    /**
     * Load the session data for a given session id.
     * Implementations should return an empty array if the session does not exist or is expired.
     *
     * @param string $id Session identifier
     * @return array<string,mixed> Decoded session payload
     */
    public function load(string $id): array;
/**
     * Persist the session data at the current id and update expiry if applicable.
     *
     * @param string $id Session identifier
     * @param array<string,mixed> $data Session payload
     * @param int $ttlSeconds Time to live in seconds
     */
    public function persist(string $id, array $data, int $ttlSeconds): void;
/**
     * Destroy/remove a session by id.
     *
     * @param string $id Session identifier
     */
    public function destroy(string $id): void;
/**
     * Generate a new cryptographically secure random session identifier.
     */
    public function generateId(): string;
}
