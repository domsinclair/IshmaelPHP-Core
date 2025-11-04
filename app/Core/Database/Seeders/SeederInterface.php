<?php
declare(strict_types=1);

namespace Ishmael\Core\Database\Seeders;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Psr\Log\LoggerInterface;

/**
 * Interface SeederInterface
 *
 * Contract for all seeders. Seeders should be deterministic and safe to re-run.
 */
interface SeederInterface
{
    /**
     * Execute the seeder. Implementations should be deterministic and re-runnable.
     *
     * @param DatabaseAdapterInterface $adapter Connected database adapter.
     * @param LoggerInterface $logger PSR-3 logger to report progress.
     * @return void
     */
    public function run(DatabaseAdapterInterface $adapter, LoggerInterface $logger): void;

    /**
     * Optional list of seeder classes that this seeder depends on.
     * Returned values should be fully-qualified class names or short class names
     * present within the same discovery set. Runner performs a topological sort.
     *
     * @return string[]
     */
    public function dependsOn(): array;
}
