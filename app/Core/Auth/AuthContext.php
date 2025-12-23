<?php

declare(strict_types=1);

namespace Ishmael\Core\Auth;

/**
 * AuthContext provides a minimal static context for the current user subject.
 *
 * This is populated by web middleware or job runners when a request/job is handled.
 * The Model layer may query it to populate created_by/updated_by fields when auditing
 * is enabled.
 */
final class AuthContext
{
    /** @var int|string|null */
    private static int|string|null $currentUserId = null;
/**
     * Set the current user id for the duration of the request/job.
     * Accepts int|string user identifiers.
     *
     * @param int|string|null $id
     */
    public static function setCurrentUserId(int|string|null $id): void
    {
        self::$currentUserId = $id;
    }

    /**
     * Retrieve the current user id if available.
     *
     * @return int|string|null
     */
    public static function getCurrentUserId(): int|string|null
    {
        return self::$currentUserId;
    }
}
