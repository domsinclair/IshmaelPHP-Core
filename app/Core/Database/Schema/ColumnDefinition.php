<?php
declare(strict_types=1);

namespace Ishmael\Core\Database\Schema;

use JsonSerializable;

class ColumnDefinition implements JsonSerializable
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable = false,
        public mixed $default = null,
        public ?int $length = null,
        public ?int $precision = null,
        public ?int $scale = null,
        public bool $unsigned = false,
        public bool $autoIncrement = false,
        public array $extras = [],
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'nullable' => $this->nullable,
            'default' => $this->default,
            'length' => $this->length,
            'precision' => $this->precision,
            'scale' => $this->scale,
            'unsigned' => $this->unsigned,
            'autoIncrement' => $this->autoIncrement,
            'extras' => $this->extras,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
