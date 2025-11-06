<?php
declare(strict_types=1);

namespace Ishmael\Core\Auth;

/**
 * HasherInterface defines password hashing operations decoupled from PHP's
 * concrete password API, so configuration and migrations can change later
 * without touching call sites.
 */
interface HasherInterface
{
    /**
     * Hash a plaintext password using the configured algorithm and options.
     *
     * @param string $plain The plaintext password
     * @return string The encoded hash
     */
    public function hash(string $plain): string;

    /**
     * Verify a plaintext password against a stored hash.
     *
     * @param string $plain The plaintext password
     * @param string $hash The stored hash
     * @return bool True if the password matches the hash
     */
    public function verify(string $plain, string $hash): bool;

    /**
     * Whether a stored hash should be rehashed based on current configuration.
     *
     * @param string $hash The stored hash
     * @return bool True if should be rehashed
     */
    public function needsRehash(string $hash): bool;
}
