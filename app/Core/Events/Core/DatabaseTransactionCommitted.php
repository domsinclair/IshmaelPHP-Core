<?php
declare(strict_types=1);

namespace Ishmael\Core\Events\Core;

/**
 * Emitted when a database transaction is successfully committed.
 */
final class DatabaseTransactionCommitted
{
    public function __construct(
        public readonly string $connection = 'default'
    ) {}
}
