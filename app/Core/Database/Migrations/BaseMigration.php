<?php
declare(strict_types=1);

namespace Ishmael\Core\Database\Migrations;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;

/**
 * BaseMigration provides a minimal contract and helpers for writing migrations.
 *
 * Migrations should extend this class and implement up() and down() methods.
 * Inside migrations you can:
 * - Call $this->sql("RAW SQL");
 * - Use $this->adapter() helpers (createTable, addColumn, addIndex, ...).
 */
abstract class BaseMigration
{
    private DatabaseAdapterInterface $adapter;

    /**
     * Framework-internal: inject the current adapter before execution.
     */
    public function setAdapter(DatabaseAdapterInterface $adapter): void
    {
        $this->adapter = $adapter;
    }

    /**
     * Expose the database adapter to migration implementations.
     */
    protected function adapter(): DatabaseAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Execute a raw SQL statement via the adapter escape hatch.
     */
    protected function sql(string $sql): void
    {
        $this->adapter->runSql($sql);
    }

    /**
     * Apply the migration.
     */
    abstract public function up(): void;

    /**
     * Revert the migration.
     */
    abstract public function down(): void;
}
