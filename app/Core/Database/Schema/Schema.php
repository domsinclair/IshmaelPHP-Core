<?php

declare(strict_types=1);

namespace Ishmael\Core\Database\Schema;

use Ishmael\Core\Database;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;

/**
 * Schema facade
 *
 * Minimal static facade to create and modify tables within migrations using a small
 * Blueprint. This avoids coupling migration authors to low-level adapter calls while
 * keeping behavior explicit and conservative.
 */
final class Schema
{
    /**
     * Create a new table using a Blueprint callback.
     *
     * @param string $table Table name to create.
     * @param callable(Blueprint):void $callback Callback that receives a Blueprint to define columns and indexes.
     * @return void
     */
    public static function create(string $table, callable $callback): void
    {
        $adapter = Database::adapter();
        $bp = new Blueprint($table);
        $callback($bp);
        $def = $bp->toTableDefinition();
        $adapter->createTable($def);
    }

    /**
     * Modify an existing table by adding any columns defined in the Blueprint that
     * are not already present. Index additions may be added in a future iteration.
     *
     * This method is intentionally conservative: it does not alter or drop existing
     * columns to avoid destructive changes in simple migrations.
     *
     * @param string $table Existing table name.
     * @param callable(Blueprint):void $callback Callback to define desired additions.
     * @return void
     */
    public static function table(string $table, callable $callback): void
    {
        $adapter = Database::adapter();
        $bp = new Blueprint($table);
        $callback($bp);
        $def = $bp->toTableDefinition();
        foreach ($def->columns as $col) {
            if (!$adapter->columnExists($table, $col->name)) {
                $adapter->addColumn($table, $col);
            }
        }
        // Index handling is skipped for now to avoid duplicate errors across engines.
    }

    /**
     * Drop a table if it exists.
     *
     * @param string $table Table name to drop.
     * @return void
     */
    public static function dropIfExists(string $table): void
    {
        $adapter = Database::adapter();
        if ($adapter->tableExists($table)) {
            $adapter->dropTable($table);
        }
    }
}
