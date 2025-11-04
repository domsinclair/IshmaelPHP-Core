<?php
declare(strict_types=1);

namespace Ishmael\Core\Database\Schema;

use JsonSerializable;

class IndexDefinition implements JsonSerializable
{
    public function __construct(
        public string $name,
        public array $columns,
        public string $type = 'index', // index|unique|primary|fulltext|spatial
        public ?string $where = null,
        public array $extras = [],
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'columns' => $this->columns,
            'type' => $this->type,
            'where' => $this->where,
            'extras' => $this->extras,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
