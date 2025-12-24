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
 *
 * @phpstan-consistent-constructor
 */
abstract class Model
{
    /**
     * Explicit table name for this model.
     * Required for all CRUD operations.
     */
    protected static string $table;
/**
     * Opt-in soft delete toggle for this model.
     *
     * When true, delete() will set a deleted_at timestamp instead of removing the row,
     * and all read queries will, by default, exclude rows where deleted_at is not null.
     *
     * You can override the default behavior per-query using withDeleted() and onlyDeleted().
     *
     * Models may set this to true to enable soft deletes. If left unset, a global default may
     * be applied via config('database.soft_deletes.default', false).
     */
    protected static bool $softDeletes = false;
/**
     * One-shot scope override for the next read query.
     * Values: null (default scope), 'with' (include deleted), 'only' (only deleted)
     * Reset to null after each read operation.
     *
     * This is static to be scoped per concrete Model class via late static binding.
     * @var string|null
     */
    protected static ?string $nextScope = null;
/**
     * Generate a UTC timestamp string with microsecond precision.
     * Using microseconds guarantees two adjacent operations in the same second
     * still yield distinct values (important for fast unit tests on SQLite).
     */
    protected static function nowTimestamp(): string
    {
        try {
            $dt = new \DateTimeImmutable('now');
            $dt = $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d H:i:s.u');
        } catch (\Throwable) {
        // Extremely defensive fallback
            return gmdate('Y-m-d H:i:s.u');
        }
    }

    /**
     * Determine whether this model is auditable and the options in effect.
     * Options keys:
     *  - timestamps: bool
     *  - userAttribution: bool
     *  - createdByColumn: string
     *  - updatedByColumn: string
     *
     * Resolution order:
     * 1) #[\Ishmael\Core\Attributes\Auditable(...)] attribute on the model class, if present
     * 2) Global config defaults (database.audit.*), when config() is available
     * 3) Built-in defaults (timestamps=true, userAttribution=false)
     *
     * @return array{timestamps:bool,userAttribution:bool,createdByColumn:string,updatedByColumn:string}
     */
    protected static function auditingOptions(): array
    {
        $defaults = [
            'timestamps' => true,
            'userAttribution' => false,
            'createdByColumn' => 'created_by',
            'updatedByColumn' => 'updated_by',
        ];
// Attribute override if present
        try {
            if (class_exists(\Ishmael\Core\Attributes\Auditable::class)) {
                $ref = new \ReflectionClass(static::class);
                $attrs = $ref->getAttributes(\Ishmael\Core\Attributes\Auditable::class);
                if ($attrs !== []) {
        /** @var \Ishmael\Core\Attributes\Auditable $inst */
                    $inst = $attrs[0]->newInstance();
                    $defaults['timestamps'] = (bool)$inst->timestamps;
                    $defaults['userAttribution'] = (bool)$inst->userAttribution;
                    $defaults['createdByColumn'] = (string)$inst->createdByColumn;
                    $defaults['updatedByColumn'] = (string)$inst->updatedByColumn;
                }
            }
        } catch (\Throwable) {
        // Ignore reflection issues; fall back to config/defaults
        }

        // Config fallback (allows global enabling without attribute)
        try {
            $cfg = function_exists('config') ? (array)(\config('database.audit') ?? []) : [];
            if ($cfg !== []) {
                foreach (['timestamps','userAttribution','createdByColumn','updatedByColumn'] as $k) {
                    if (array_key_exists($k, $cfg) && $cfg[$k] !== null && $cfg[$k] !== '') {
                            $defaults[$k] = $cfg[$k];
                    }
                }
            }
        } catch (\Throwable) {
        // config() may not exist; ignore
        }

        return [
            'timestamps' => (bool)$defaults['timestamps'],
            'userAttribution' => (bool)$defaults['userAttribution'],
            'createdByColumn' => (string)$defaults['createdByColumn'],
            'updatedByColumn' => (string)$defaults['updatedByColumn'],
        ];
    }

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
        [$scopeSql, $scopeParams] = static::scopeWhereSql();
        $sql = "SELECT * FROM {$table} WHERE id = :id" . $scopeSql . " LIMIT 1";
        $params = array_merge(['id' => $id], $scopeParams);
        $result = $adapter->query($sql, $params);
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
        [$scopeSql, $scopeParams] = static::scopeWhereSql();
        if ($where === []) {
        // No where: return all rows (respect soft delete scope if enabled)
            $sql = "SELECT * FROM {$table}" . ($scopeSql !== '' ? " WHERE 1=1{$scopeSql}" : '');
            return $adapter->query($sql, $scopeParams)->fetchAll();
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

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $clauses) . $scopeSql;
        $allParams = array_merge($params, $scopeParams);
        return $adapter->query($sql, $allParams)->fetchAll();
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

        // Auditing: timestamps and optional user attribution
        $audit = static::auditingOptions();
        if ($audit['timestamps']) {
            $now = static::nowTimestamp();
            if (!array_key_exists('created_at', $data)) {
                $data['created_at'] = $now;
            }
            if (!array_key_exists('updated_at', $data)) {
                $data['updated_at'] = $now;
            }
        }
        if ($audit['userAttribution'] && class_exists(\Ishmael\Core\Auth\AuthContext::class)) {
            try {
                $uid = \Ishmael\Core\Auth\AuthContext::getCurrentUserId();
            } catch (\Throwable) {
                $uid = null;
            }
            if ($uid !== null && $uid !== '') {
                $cb = $audit['createdByColumn'];
                $ub = $audit['updatedByColumn'];
                if (!array_key_exists($cb, $data)) {
                    $data[$cb] = $uid;
                }
                if (!array_key_exists($ub, $data)) {
                    $data[$ub] = $uid;
                }
            }
        }

        $columns = array_keys($data);
        foreach ($columns as $col) {
            if (!is_string($col) || $col === '') {
                throw new \InvalidArgumentException('Data keys must be non-empty column names.');
            }
        }
        $placeholders = array_map(fn($c) => ':' . $c, $columns);
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $columns), implode(', ', $placeholders));
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

        // Auditing: auto-update updated_at and optional updated_by
        $audit = static::auditingOptions();
        if ($audit['timestamps'] && !array_key_exists('updated_at', $data)) {
            $data['updated_at'] = static::nowTimestamp();
        }
        if ($audit['userAttribution'] && !array_key_exists($audit['updatedByColumn'], $data) && class_exists(\Ishmael\Core\Auth\AuthContext::class)) {
            try {
                $uid = \Ishmael\Core\Auth\AuthContext::getCurrentUserId();
            } catch (\Throwable) {
                        $uid = null;
            }
            if ($uid !== null && $uid !== '') {
                $data[$audit['updatedByColumn']] = $uid;
            }
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
        if (static::usesSoftDeletes()) {
            $now = static::nowTimestamp();
        // Also update updated_at when auditable timestamps are enabled
            $audit = static::auditingOptions();
            if ($audit['timestamps']) {
                $sql = "UPDATE {$table} SET deleted_at = :now, updated_at = :now WHERE id = :id";
            } else {
                $sql = "UPDATE {$table} SET deleted_at = :now WHERE id = :id";
            }
            return $adapter->execute($sql, ['id' => $id, 'now' => $now]);
        }
        $sql = "DELETE FROM {$table} WHERE id = :id";
        return $adapter->execute($sql, ['id' => $id]);
    }

    /**
     * Ensure a concrete model declared a table name and return it.
     */
    protected static function requireTable(): string
    {
        $table = static::$table ?? '';
        if ($table === '') {
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
    protected static function paramName(string $column, array $existingParams): string
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

    /**
     * Enable inclusion of soft-deleted rows for the next read query on this model.
     *
     * Usage: Post::withDeleted()::findBy([...]);
     */
    public static function withDeleted(): static
    {
        static::$nextScope = 'with';
        return new static();
// return a dummy instance to allow chaining ::withDeleted()::findBy()
    }

    /**
     * Restrict the next read query to only soft-deleted rows.
     */
    public static function onlyDeleted(): static
    {
        static::$nextScope = 'only';
        return new static();
    }

    /**
     * Restore a soft-deleted row back to active (sets deleted_at to NULL).
     */
    public static function restore(int|string $id): int
    {
        if (!static::usesSoftDeletes()) {
            return 0;
        }
        $adapter = self::adapter();
        $table = static::requireTable();
        $sql = "UPDATE {$table} SET deleted_at = NULL WHERE id = :id";
        return $adapter->execute($sql, ['id' => $id]);
    }

    /**
     * Permanently remove a row, bypassing soft deletes.
     */
    public static function forceDelete(int|string $id): int
    {
        $adapter = self::adapter();
        $table = static::requireTable();
        $sql = "DELETE FROM {$table} WHERE id = :id";
        return $adapter->execute($sql, ['id' => $id]);
    }

    /**
     * Determine if this model uses soft deletes, based on the static toggle or global config default.
     */
    public static function usesSoftDeletes(): bool
    {
        $flag = static::$softDeletes ?? false;
        if ($flag === false) {
        // Allow global opt-in default via config; model can still explicitly enable.
            try {
                $flag = (bool) (\config('database.soft_deletes.default') ?? false);
            } catch (\Throwable) {
        // config() may not be available in some contexts
            }
        }
        return (bool)$flag;
    }

    /**
     * Build an additional WHERE SQL snippet and parameters for the current soft-delete scope.
     * Returns array: [sql, params]
     * - sql includes leading ' AND ...' to be appended to existing WHERE or empty if no condition.
     */
    protected static function scopeWhereSql(): array
    {
        $scope = static::$nextScope;
// reset after read consumption
        static::$nextScope = null;
        if (!static::usesSoftDeletes()) {
            return ['', []];
        }

        // default: exclude deleted rows
        if ($scope === 'with') {
            return ['', []];
        }
        if ($scope === 'only') {
            return [' AND deleted_at IS NOT NULL', []];
        }
        return [' AND deleted_at IS NULL', []];
    }
}
