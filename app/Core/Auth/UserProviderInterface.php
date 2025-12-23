<?php

declare(strict_types=1);

namespace Ishmael\Core\Auth;

/**
 * UserProviderInterface abstracts user retrieval to decouple storage choice.
 */
interface UserProviderInterface
{
    /**
     * Retrieve a user by its unique identifier.
     *
     * @param mixed $id User identifier value
     * @return array<string,mixed>|null User record as an associative array or null when not found
     */
    public function retrieveById(mixed $id): ?array;
/**
     * Retrieve a user by provided credentials (e.g., username/email).
     *
     * @param array<string,mixed> $credentials Key/value credentials
     * @return array<string,mixed>|null
     */
    public function retrieveByCredentials(array $credentials): ?array;
/**
     * Validate a user's credentials using the configured hasher.
     *
     * @param array<string,mixed> $user User record
     * @param array<string,mixed> $credentials Credentials
     * @return bool True when credentials are valid
     */
    public function validateCredentials(array $user, array $credentials): bool;
}
