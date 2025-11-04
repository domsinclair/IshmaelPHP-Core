<?php
declare(strict_types=1);

namespace Ishmael\Core\Database;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\Database\Schema\TableDefinition;
use Ishmael\Core\Database\Schema\ColumnDefinition;
use Ishmael\Core\Database\Schema\IndexDefinition;
use Ishmael\Core\Database\Schema\SchemaDiff;
use Ishmael\Core\Logger;
use Psr\Log\LoggerInterface;

/**
 * SchemaManager (lean, explicit)
 *
 * - Reads schema metadata from module schema.php files or model static metadata.
 * - Performs conservative diffs and applies only safe operations automatically.
 * - For unsafe changes (type change, nullable flip, drop column), it refuses
 *   to proceed and instructs the developer to write an explicit migration.
 */
final class SchemaManager
{
    private DatabaseAdapterInterface $adapter;
    private LoggerInterface $logger;

    /**
     * Construct a SchemaManager.
     *
     * @param DatabaseAdapterInterface $adapter Database adapter to inspect/apply schema changes.
     * @param LoggerInterface|null $logger PSR-3 logger; defaults to framework logger.
     */
    public function __construct(DatabaseAdapterInterface $adapter, ?LoggerInterface $logger = null)
    {
        $this->adapter = $adapter;
        $this->logger = $logger ?? $this->resolveLogger();
    }

    /**
     * Apply a module's schema.php definitions using conservative synchronization.
     *
     * Expects a file at "$modulePath/Database/schema.php" returning:
     *   - array<TableDefinition>|array<string,TableDefinition>
     *
     * @throws \RuntimeException on unsafe diffs.
     */
    public function applyModuleSchema(string $modulePath): void
    {
        $schemaFile = rtrim($modulePath, "\\/ ") . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'schema.php';
        if (!is_file($schemaFile)) {
            $this->logger->debug('No module schema file found; nothing to apply', ['modulePath' => $modulePath]);
            return;
        }
        $defs = require $schemaFile;
        if (!is_array($defs)) {
            throw new \RuntimeException("Module schema file must return an array of TableDefinition instances: {$schemaFile}");
        }

        // Normalize to a flat list of TableDefinition
        $tables = [];
        foreach ($defs as $k => $v) {
            if ($v instanceof TableDefinition) {
                $tables[] = $v;
            } elseif (is_array($v) && isset($v['name'])) {
                // allow associative arrays: convert to TableDefinition
                $tables[] = $this->arrayToTableDefinition($v);
            } elseif ($k instanceof TableDefinition) {
                $tables[] = $k; // unlikely, but be permissive
            }
        }
        $this->synchronize($tables);
    }

    /**
     * Compute a conservative diff between desired definition and the current database.
     *
     * - Create table if it does not exist.
     * - Add new columns and indexes.
     * - Detect unsafe changes (type/nullable flips, drops) when possible and mark as unsafe.
     */
    public function diff(string $table, TableDefinition $desired): SchemaDiff
    {
        $diff = new SchemaDiff($table);

        if (!$this->adapter->tableExists($table)) {
            $diff->createTable = true;
            // When creating a table, we can include all columns and indexes as safe ops.
            $diff->addColumns = $desired->columns;
            $diff->addIndexes = $desired->indexes;
            $this->logger->debug('Table does not exist; planning CREATE TABLE', ['table' => $table]);
            return $diff;
        }

        // Table exists: conservative comparison
        $current = null;
        try {
            $current = $this->adapter->getTableDefinition($table);
        } catch (\Throwable $e) {
            // Some adapters may not support full introspection yet; continue conservatively.
            $this->logger->debug('Adapter lacks full introspection; proceeding conservatively (additions only)', [
                'table' => $table,
                'adapter' => get_class($this->adapter),
            ]);
        }

        // Index existing columns by name for quick lookup
        $existingCols = [];
        if ($current instanceof TableDefinition) {
            foreach ($current->columns as $c) {
                if ($c instanceof ColumnDefinition) {
                    $existingCols[strtolower($c->name)] = $c;
                }
            }
        }

        foreach ($desired->columns as $col) {
            if (!$col instanceof ColumnDefinition) continue;
            $nameKey = strtolower($col->name);

            // If adapter can only tell column existence, prefer that
            $exists = $this->adapter->columnExists($table, $col->name);
            if (!$exists) {
                $diff->addColumns[] = $col; // safe addition
                continue;
            }

            // Column exists; if we have current definition, detect unsafe changes
            if (isset($existingCols[$nameKey])) {
                $curr = $existingCols[$nameKey];
                // Unsafe: type change
                if (strtoupper($curr->type) !== strtoupper($col->type)) {
                    $diff->addUnsafe("Column '{$col->name}': type change from {$curr->type} to {$col->type} requires explicit migration.");
                }
                // Unsafe: nullable flip
                if ((bool)$curr->nullable !== (bool)$col->nullable) {
                    $from = $curr->nullable ? 'NULL' : 'NOT NULL';
                    $to = $col->nullable ? 'NULL' : 'NOT NULL';
                    $diff->addUnsafe("Column '{$col->name}': nullable change from {$from} to {$to} requires explicit migration.");
                }
                // Default changes are treated as unsafe by default (could be safe but be explicit)
                if (($curr->default ?? null) !== ($col->default ?? null)) {
                    $diff->addUnsafe("Column '{$col->name}': default change requires explicit migration.");
                }
            } else {
                // Column exists (per adapter) but not present in introspection list — stay conservative
                $this->logger->debug('Column exists but missing from introspection list; skipping unsafe checks', [
                    'table' => $table,
                    'column' => $col->name,
                ]);
            }
        }

        // Indexes: we will attempt to add missing by name only; detecting type/columns changes is unsafe
        $existingIdx = [];
        if ($current instanceof TableDefinition) {
            foreach ($current->indexes as $i) {
                if ($i instanceof IndexDefinition) {
                    $existingIdx[strtolower($i->name)] = $i;
                }
            }
        }
        foreach ($desired->indexes as $idx) {
            if (!$idx instanceof IndexDefinition) continue;
            $key = strtolower($idx->name);
            if (!isset($existingIdx[$key])) {
                $diff->addIndexes[] = $idx; // consider safe to create
            } else {
                // If present but different columns/type, require migration
                $curr = $existingIdx[$key];
                if ($curr instanceof IndexDefinition) {
                    if ($curr->type !== $idx->type || $curr->columns !== $idx->columns) {
                        $diff->addUnsafe("Index '{$idx->name}': change detected; write an explicit migration to modify indexes.");
                    }
                }
            }
        }

        $this->logger->debug('Computed schema diff', ['table' => $table, 'diff' => $diff->toArray()]);
        return $diff;
    }

    /**
     * Apply safe diffs for a list of table definitions. Unsafe diffs cause an exception with guidance.
     *
     * @param array<int,TableDefinition> $defs
     * @throws \RuntimeException when unsafe changes are detected.
     */
    public function synchronize(array $defs): void
    {
        $supportsTx = $this->adapter->supportsTransactionalDdl();
        $inTx = false;
        try {
            if ($supportsTx) {
                $this->adapter->beginTransaction();
                $inTx = true;
            }

            foreach ($defs as $def) {
                if (!$def instanceof TableDefinition) { continue; }
                $table = $def->name;
                $diff = $this->diff($table, $def);

                if (!$diff->isSafe()) {
                    $this->logger->warning('Unsafe schema changes detected. Aborting.', [
                        'table' => $table,
                        'unsafe' => $diff->unsafeChanges,
                    ]);
                    throw new \RuntimeException(
                        "Unsafe schema changes for table '{$table}'. Write an explicit migration. Issues: "
                        . implode(' | ', $diff->unsafeChanges)
                    );
                }

                if ($diff->createTable) {
                    $this->logger->info('Creating table', ['table' => $table]);
                    $this->adapter->createTable($def);
                }
                foreach ($diff->addColumns as $col) {
                    $this->logger->info('Adding column', ['table' => $table, 'column' => $col->name]);
                    $this->adapter->addColumn($table, $col);
                }
                foreach ($diff->addIndexes as $idx) {
                    $this->logger->info('Adding index', ['table' => $table, 'index' => $idx->name]);
                    $this->adapter->addIndex($table, $idx);
                }
            }

            if ($inTx) {
                $this->adapter->commit();
            }
        } catch (\Throwable $e) {
            if ($inTx) {
                $this->adapter->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Build TableDefinition from an associative array, for convenience.
     * @param array<string,mixed> $arr
     */
    private function arrayToTableDefinition(array $arr): TableDefinition
    {
        $name = (string)($arr['name'] ?? '');
        if ($name === '') {
            throw new \InvalidArgumentException('TableDefinition array must include a name');
        }
        $columns = [];
        foreach ((array)($arr['columns'] ?? []) as $c) {
            if ($c instanceof ColumnDefinition) { $columns[] = $c; continue; }
            $columns[] = new ColumnDefinition(
                name: (string)($c['name'] ?? ''),
                type: (string)($c['type'] ?? 'TEXT'),
                nullable: (bool)($c['nullable'] ?? false),
                default: $c['default'] ?? null,
                length: isset($c['length']) ? (int)$c['length'] : null,
                precision: isset($c['precision']) ? (int)$c['precision'] : null,
                scale: isset($c['scale']) ? (int)$c['scale'] : null,
                unsigned: (bool)($c['unsigned'] ?? false),
                autoIncrement: (bool)($c['autoIncrement'] ?? false),
                extras: (array)($c['extras'] ?? []),
            );
        }
        $indexes = [];
        foreach ((array)($arr['indexes'] ?? []) as $i) {
            if ($i instanceof IndexDefinition) { $indexes[] = $i; continue; }
            $indexes[] = new IndexDefinition(
                name: (string)($i['name'] ?? ''),
                columns: (array)($i['columns'] ?? []),
                type: (string)($i['type'] ?? 'index'),
                where: isset($i['where']) ? (string)$i['where'] : null,
                extras: (array)($i['extras'] ?? []),
            );
        }
        return new TableDefinition($name, $columns, $indexes, (array)($arr['extras'] ?? []));
    }

    private function resolveLogger(): LoggerInterface
    {
        // Try service locator (if present)
        if (function_exists('app')) {
            $svc = app(\Psr\Log\LoggerInterface::class);
            if ($svc instanceof LoggerInterface) {
                return $svc;
            }
        }
        // Fallback to framework default if available
        try {
            if (class_exists(Logger::class) && method_exists(Logger::class, 'init')) {
                Logger::init([]);
                if (function_exists('app')) {
                    $svc = app(\Psr\Log\LoggerInterface::class);
                    if ($svc instanceof LoggerInterface) {
                        return $svc;
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore and fallback to null channel
        }
        // Last resort: no-op channel
        return new \Ishmael\Core\Log\NullChannel();
    }

    /**
     * Utility: Build TableDefinitions from model classes that declare static metadata.
     *
     * A model is recognized if it contains:
     *  - public static string $table
     *  - public static function schema(): TableDefinition
     *
     * @param array<int,string> $modelClasses FQCNs to inspect.
     * @return array<int,TableDefinition>
     */
    public function collectFromModels(array $modelClasses): array
    {
        $defs = [];
        foreach ($modelClasses as $class) {
            if (!is_string($class) || !class_exists($class)) { continue; }
            if (!method_exists($class, 'schema')) { continue; }
            /** @var TableDefinition|null $td */
            $td = $class::schema();
            if (!$td instanceof TableDefinition) { continue; }
            // Ensure name matches declared table if present
            $tableName = '';
            // Prefer schema-declared name
            $tableName = $td->name ?: $tableName;
            // If model exposes a static $table (may be protected), read via reflection
            if (property_exists($class, 'table')) {
                try {
                    $ref = new \ReflectionProperty($class, 'table');
                    if ($ref->isStatic()) {
                        $ref->setAccessible(true);
                        $val = (string)$ref->getValue();
                        if ($val !== '') { $tableName = $val; }
                    }
                } catch (\ReflectionException $e) {
                    // ignore; fall back to any prior value
                }
            }
            if ($tableName !== '') {
                $td->name = $tableName;
            }
            $defs[] = $td;
        }
        return $defs;
    }
}
