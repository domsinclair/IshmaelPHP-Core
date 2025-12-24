<?php

declare(strict_types=1);

namespace Ishmael\Core\Database\Schema;

use JsonSerializable;

class TableDefinition implements JsonSerializable
{
    /**
     * @param ColumnDefinition[] $columns
     * @param IndexDefinition[] $indexes
     * @param ForeignKeyDefinition[] $foreignKeys
     */
    public function __construct(
        public string $name,
        public array $columns = [],
        public array $indexes = [],
        public array $foreignKeys = [],
        public array $extras = [],
    ) {
    }

    public function addColumn(ColumnDefinition $col): self
    {
        $this->columns[] = $col;
        return $this;
    }

    public function addIndex(IndexDefinition $idx): self
    {
        $this->indexes[] = $idx;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'columns' => array_map(fn(ColumnDefinition $c) => $c->toArray(), $this->columns),
            'indexes' => array_map(fn(IndexDefinition $i) => $i->toArray(), $this->indexes),
            'foreignKeys' => array_map(fn(ForeignKeyDefinition $f) => $f->toArray(), $this->foreignKeys),
            'extras' => $this->extras,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
