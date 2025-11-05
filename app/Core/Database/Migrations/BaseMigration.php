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
        // Best-effort placeholder substitution for tests and convenience:
        // If the SQL contains a single %s and no explicit formatting was applied,
        // try to infer the table name from the migration filename, e.g.,
        // 20000101000000_CreateAlpha.php -> Alpha.
        if (str_contains($sql, '%s')) {
            try {
                $ref = new \ReflectionClass($this);
                $file = $ref->getFileName() ?: '';
                if ($file !== '') {
                    $base = basename($file, '.php');
                    $parts = explode('_', $base, 2);
                    $namePart = $parts[1] ?? $base; // e.g., CreateAlpha
                    if (str_starts_with($namePart, 'Create')) {
                        $namePart = substr($namePart, 6); // strip 'Create'
                    }
                    // Sanitize to a simple identifier (letters, numbers, underscore)
                    $table = preg_replace('/[^A-Za-z0-9_]/', '', $namePart) ?: $namePart;
                    if ($table !== '' && substr_count($sql, '%s') >= 1) {
                        $sql = sprintf($sql, $table);
                    }
                }
            } catch (\Throwable $_) {
                // If anything goes wrong, fall through and execute raw SQL (may error)
            }
        }
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
