<?php

declare(strict_types=1);

namespace Ishmael\Core\Database\Migrations;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Migrator
 *
 * Thin programmatic entrypoint to run database migrations without a CLI.
 * Wraps MigrationRunner and exposes a small, callable API suitable for
 * application bootstrap, tests, and small scripts.
 */
final class Migrator
{
    private MigrationRunner $runner;
/**
     * Construct a Migrator.
     *
     * @param DatabaseAdapterInterface $adapter Connected database adapter instance.
     * @param LoggerInterface|null $logger Optional PSR-3 logger to pass to the runner.
     */
    public function __construct(DatabaseAdapterInterface $adapter, ?LoggerInterface $logger = null)
    {
        $this->runner = new MigrationRunner($adapter, $logger);
    }

    /**
     * Apply pending migrations.
     *
     * When $module is null, runs migrations for all discovered modules. When $steps > 0 and a module
     * is provided, applies at most $steps pending migrations for that module. When $pretend is true,
     * the method only logs what would be executed without applying changes.
     *
     * @param string|null $module Module name to target or null to process all modules.
     * @param int $steps Limit the number of migrations for a specific module (0 = all pending).
     * @param bool $pretend If true, do not apply changes; only log planned operations.
     * @return void
     */
    /**
     * Apply pending migrations.
     *
     * @param string|null $module Module name to target or null to process all modules.
     * @param int $steps Limit the number of migrations for a specific module (0 = all pending).
     * @param bool $pretend If true, do not apply changes; only log planned operations.
     * @param bool $force Override checksum drift guard and proceed when true.
     * @return void
     */
    public function migrate(?string $module = null, int $steps = 0, bool $pretend = false, bool $force = false): void
    {
        $this->runner->migrate($module, $steps, $pretend, $force);
    }

    /**
     * Roll back migrations.
     *
     * When a module is provided, rolls back the last $steps migrations for that module.
     * When module is null, rolls back the latest batch across all modules.
     *
     * @param string|null $module Module name to target, or null to roll back the latest batch globally.
     * @param int $steps Number of migrations to roll back for the given module (default 1).
     * @return void
     */
    public function rollback(?string $module = null, int $steps = 1): void
    {
        $this->runner->rollback($module, $steps);
    }

    /**
     * Reset all applied migrations.
     *
     * When $module is provided, resets only that module; otherwise resets all modules.
     *
     * @param string|null $module Module name to target, or null for all modules.
     * @return void
     */
    public function reset(?string $module = null): void
    {
        $this->runner->reset($module);
    }

    /**
     * Get migration status information.
     *
     * @param string|null $module Module to query, or null for a global snapshot.
     * @return array<mixed> Status data as returned by MigrationRunner::status().
     */
    public function status(?string $module = null): array
    {
        return $this->runner->status($module);
    }
}
