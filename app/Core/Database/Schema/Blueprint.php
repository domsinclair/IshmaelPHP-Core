<?php

declare(strict_types=1);

namespace Ishmael\Core\Database\Schema;

/**
 * Blueprint is a minimal schema builder used within migrations to describe a table.
 *
 * It collects columns and indexes and can be converted to a TableDefinition that
 * database adapters understand.
 */
final class Blueprint
{
    /**
     * Name of the table this blueprint targets.
     * @var string
     */
    private string $table;
/** @var ColumnDefinition[] */
    private array $columns = [];
/** @var IndexDefinition[] */
    private array $indexes = [];
/** @var ForeignKeyDefinition[] */
    private array $foreignKeys = [];
/**
     * @param string $table Table name this blueprint targets.
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Add an auto-incrementing primary key named "id".
     * @return $this Fluent return.
     */
    public function id(): self
    {
        $this->columns[] = new ColumnDefinition('id', 'INTEGER', nullable: false, autoIncrement: true);
// Primary key is implied for INTEGER AUTOINCREMENT on SQLite. For other engines
        // an index could be emitted by adapter when it sees autoIncrement.
        return $this;
    }

    /**
     * Add an auto-incrementing primary key with a custom name (module style: posts_id).
     *
     * Note: SQLite treats any single INTEGER PRIMARY KEY column as rowid. Our adapters
     * infer primary keys from autoIncrement flag.
     *
     * @param string $name Column name.
     * @return $this Fluent return.
     */
    public function increments(string $name): self
    {
        $this->columns[] = new ColumnDefinition($name, 'INTEGER', nullable: false, autoIncrement: true);
        return $this;
    }

    /**
     * Define a VARCHAR/TEXT-like column.
     *
     * @param string $name Column name.
     * @param int $length Max length for character column when supported (default 255).
     * @param bool $nullable Whether the column allows NULL values (default false).
     * @param string|null $default Default value.
     * @return $this Fluent builder.
     */
    public function string(string $name, int $length = 255, bool $nullable = false, ?string $default = null): self
    {
        $this->columns[] = new ColumnDefinition($name, 'VARCHAR', nullable: $nullable, default: $default, length: $length);
        return $this;
    }

    /**
     * Define a TEXT column.
     *
     * @param string $name Column name.
     * @param bool $nullable Whether the column allows NULL values.
     * @param string|null $default Default value.
     * @return $this Fluent builder.
     */
    public function text(string $name, bool $nullable = false, ?string $default = null): self
    {
        $this->columns[] = new ColumnDefinition($name, 'TEXT', nullable: $nullable, default: $default);
        return $this;
    }

    /**
     * Define a BOOLEAN column.
     *
     * @param string $name Column name.
     * @param bool $nullable Whether the column allows NULL values.
     * @param bool|null $default Default boolean value.
     * @return $this Fluent builder.
     */
    public function boolean(string $name, bool $nullable = false, ?bool $default = null): self
    {
        $this->columns[] = new ColumnDefinition($name, 'BOOLEAN', nullable: $nullable, default: $default);
        return $this;
    }

    /**
     * Convenience to add created_at and updated_at timestamp columns.
     * @return $this Fluent builder.
     */
    public function timestamps(): self
    {
        $this->columns[] = new ColumnDefinition('created_at', 'DATETIME', nullable: false);
        $this->columns[] = new ColumnDefinition('updated_at', 'DATETIME', nullable: false);
        return $this;
    }

    /**
     * Add a simple index on one or more columns.
     *
     * @param string|string[] $columns Column name or list of names.
     * @param string|null $name Optional index name.
     * @return $this Fluent builder.
     */
    public function index(string|array $columns, ?string $name = null): self
    {
        $cols = is_array($columns) ? $columns : [$columns];
        $this->indexes[] = new IndexDefinition($name ?? ('idx_' . $this->table . '_' . implode('_', $cols)), $cols, 'index');
        return $this;
    }

    /**
     * Add a unique index on one or more columns.
     *
     * @param string|string[] $columns Column name or list of names.
     * @param string|null $name Optional index name.
     * @return $this Fluent builder.
     */
    public function unique(string|array $columns, ?string $name = null): self
    {
        $cols = is_array($columns) ? $columns : [$columns];
        $this->indexes[] = new IndexDefinition($name ?? ('uniq_' . $this->table . '_' . implode('_', $cols)), $cols, 'unique');
        return $this;
    }

    /**
     * Define a foreign key on one or more columns.
     *
     * Example:
     *   $bp->foreignKey('user_id', 'users', 'id', onDelete: 'cascade');
     *
     * @param string|array $columns Local column name(s).
     * @param string $referencesTable Referenced table name.
     * @param string|array $referencesColumns Referenced column name(s). Defaults to 'id'.
     * @param string|null $name Optional constraint name. If omitted, a deterministic name is generated.
     * @param string|null $onDelete Action on delete (cascade|restrict|set null|no action) when supported.
     * @param string|null $onUpdate Action on update when supported.
     * @return $this Fluent builder.
     */
    public function foreignKey(string|array $columns, string $referencesTable, string|array $referencesColumns = 'id', ?string $name = null, ?string $onDelete = null, ?string $onUpdate = null): self
    {
        $localCols = is_array($columns) ? $columns : [$columns];
        $refCols = is_array($referencesColumns) ? $referencesColumns : [$referencesColumns];
        $constraintName = $name ?? ('fk_' . $this->table . '_' . implode('_', $localCols) . '__' . $referencesTable . '_' . implode('_', $refCols));
        $this->foreignKeys[] = new ForeignKeyDefinition(name: $constraintName, columns: $localCols, referencesTable: $referencesTable, referencesColumns: $refCols, onDelete: $onDelete, onUpdate: $onUpdate,);
        return $this;
    }

    /**
     * Convenience: add a nullable/non-nullable integer column and a foreign key to another table's id.
     *
     * @param string $name Local column name (e.g., user_id).
     * @param string $referencesTable Referenced table (e.g., users).
     * @param bool $nullable Whether the FK column allows NULL.
     * @param string $type Column type (adapter maps appropriately). Defaults to INTEGER.
     * @param string $referencesColumn Referenced column (default 'id').
     * @param string|null $onDelete Action on delete.
     * @param string|null $onUpdate Action on update.
     * @return $this
     */
    public function foreignId(string $name, string $referencesTable, bool $nullable = false, string $type = 'INTEGER', string $referencesColumn = 'id', ?string $onDelete = null, ?string $onUpdate = null): self
    {
        $this->columns[] = new ColumnDefinition($name, $type, nullable: $nullable);
        return $this->foreignKey($name, $referencesTable, $referencesColumn, null, $onDelete, $onUpdate);
    }

    /**
     * Convert to a TableDefinition for adapter consumption.
     */
    public function toTableDefinition(): TableDefinition
    {
        return new TableDefinition($this->table, $this->columns, $this->indexes, $this->foreignKeys);
    }
}
