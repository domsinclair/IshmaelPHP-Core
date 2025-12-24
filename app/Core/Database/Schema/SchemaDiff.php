<?php

declare(strict_types=1);

namespace Ishmael\Core\Database\Schema;

/**
 * Value object representing a conservative schema diff.
 *
 * Captures only safe, auto-applicable operations and records unsafe changes
 * with human-readable reasons to guide explicit migrations.
 */
final class SchemaDiff implements \JsonSerializable
{
    /** @var ColumnDefinition[] */
    public array $addColumns = [];
/** @var IndexDefinition[] */
    public array $addIndexes = [];
/** @var array<int,string> */
    public array $unsafeChanges = [];
    public bool $createTable = false;
    public function __construct(public string $table)
    {
    }

    /**
     * Whether this diff contains only safe, auto-applicable operations.
     */
    public function isSafe(): bool
    {
        return count($this->unsafeChanges) === 0;
    }

    /**
     * Whether there is any change to apply (safe or unsafe recorded).
     */
    public function hasChanges(): bool
    {
        return $this->createTable || !empty($this->addColumns) || !empty($this->addIndexes) || !empty($this->unsafeChanges);
    }

    /**
     * Add an unsafe change message.
     */
    public function addUnsafe(string $message): void
    {
        $this->unsafeChanges[] = $message;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'table' => $this->table,
            'createTable' => $this->createTable,
            'addColumns' => array_map(fn(ColumnDefinition $c) => $c->toArray(), $this->addColumns),
            'addIndexes' => array_map(fn(IndexDefinition $i) => $i->toArray(), $this->addIndexes),
            'unsafe' => $this->unsafeChanges,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
