<?php
declare(strict_types=1);

namespace Ishmael\Core;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\Database\Schema\TableDefinition;

/**
 * Minimal, explicit Model base.
 *
 * Responsibilities:
 * - Provide a thin, static CRUD surface that delegates to the active Database adapter.
 * - Expose a static schema() hook for SchemaManager (optional) to obtain a TableDefinition.
 * - Keep zero hidden state; no relations or magic properties.
 */
abstract class Model
{
    /**
     * Explicit table name for this model.
     * Required for all CRUD operations.
     */
    protected static string $table;

    /**
     * Optional model-declared schema metadata.
     *
     * When provided, SchemaManager may use it to create or validate tables.
     * Override in your model to return a TableDefinition.
     *
     * @return TableDefinition|null The table definition for this model, or null if not declared.
     */
    public static function schema(): ?TableDefinition
    {
        return null;
    }

    /**
     * Find a single row by its primary key id.
     *
     * @param int|string $id Primary key value to look up.
     * @return array<string,mixed>|null Associative row array or null if not found.
     */
    public static function find(int|string $id): ?array
    {
        $adapter = self::adapter();
        $table = static::requireTable();

        $sql = "SELECT * FROM {$table} WHERE id = :id LIMIT 1";
        $result = $adapter->query($sql, ['id' => $id]);
        $row = $result->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Find rows matching the provided where conditions combined with AND.
     *
     * @param array<string,mixed> $where Column => value pairs.
     * @return array<int, array<string,mixed>> Matching rows.
     */
    public static function findBy(array $where): array
    {
        $adapter = self::adapter();
        $table = static::requireTable();

        if ($where === []) {
            // No where: return all rows; stays explicit and unsurprising.
            $sql = "SELECT * FROM {$table}";
            return $adapter->query($sql, [])->fetchAll();
        }

        $clauses = [];
        $params = [];
        foreach ($where as $col => $val) {
            if (!is_string($col) || $col === '') {
                throw new \InvalidArgumentException('Where keys must be non-empty column names.');
            }
            $param = static::paramName($col, $params);
            $clauses[] = "{$col} = :{$param}";
            $params[$param] = $val;
        }

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $clauses);
        return $adapter->query($sql, $params)->fetchAll();
    }

    /**
     * Insert a new row.
     *
     * @param array<string,mixed> $data Column => value pairs to insert.
     * @return int|string The last insert id as returned by the adapter.
     */
    public static function insert(array $data): int|string
    {
        $adapter = self::adapter();
        $table = static::requireTable();

        if ($data === []) {
            throw new \InvalidArgumentException('Insert data must not be empty.');
        }

        $columns = array_keys($data);
        foreach ($columns as $col) {
            if (!is_string($col) || $col === '') {
                throw new \InvalidArgumentException('Data keys must be non-empty column names.');
            }
        }
        $placeholders = array_map(fn($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        // Use original keys as param names
        $adapter->execute($sql, $data);
        return $adapter->lastInsertId();
    }

    /**
     * Update a row identified by its primary key id.
     *
     * @param int|string $id Primary key value to update.
     * @param array<string,mixed> $data Column => value pairs to set.
     * @return int Number of affected rows.
     */
    public static function update(int|string $id, array $data): int
    {
        $adapter = self::adapter();
        $table = static::requireTable();

        if ($data === []) {
            throw new \InvalidArgumentException('Update data must not be empty.');
        }

        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            if (!is_string($col) || $col === '') {
                throw new \InvalidArgumentException('Data keys must be non-empty column names.');
            }
            $param = static::paramName($col, $params);
            $sets[] = "{$col} = :{$param}";
            $params[$param] = $val;
        }
        $params['id'] = $id;

        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE id = :id';
        return $adapter->execute($sql, $params);
    }

    /**
     * Delete a row by its primary key id.
     *
     * @param int|string $id Primary key value to delete.
     * @return int Number of affected rows.
     */
    public static function delete(int|string $id): int
    {
        $adapter = self::adapter();
        $table = static::requireTable();
        $sql = "DELETE FROM {$table} WHERE id = :id";
        return $adapter->execute($sql, ['id' => $id]);
    }

    /**
     * Ensure a concrete model declared a table name and return it.
     */
    protected static function requireTable(): string
    {
        $table = static::$table ?? '';
        if (!is_string($table) || $table === '') {
            $cls = static::class;
            throw new \LogicException("Model {$cls} must declare a non-empty protected static $table name.");
        }
        return $table;
    }

    /**
     * Resolve the active database adapter.
     */
    protected static function adapter(): DatabaseAdapterInterface
    {
        return Database::adapter();
    }

    /**
     * Generate a unique parameter name for a column, avoiding collisions.
     *
     * @param string $column Column name
     * @param array<string,mixed> $existingParams Current param map to avoid collisions
     */
    private static function paramName(string $column, array $existingParams): string
    {
        $base = preg_replace('/[^a-zA-Z0-9_]/', '_', $column) ?: 'p';
        $name = $base;
        $i = 1;
        while (array_key_exists($name, $existingParams)) {
            $name = $base . '_' . $i;
            $i++;
        }
        return $name;
    }
}
