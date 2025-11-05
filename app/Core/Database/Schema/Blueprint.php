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
     * Convert to a TableDefinition for adapter consumption.
     */
    public function toTableDefinition(): TableDefinition
    {
        return new TableDefinition($this->table, $this->columns, $this->indexes);
    }
}
