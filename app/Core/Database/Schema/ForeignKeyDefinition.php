<?php
declare(strict_types=1);

namespace Ishmael\Core\Database\Schema;

use JsonSerializable;

/**
 * ForeignKeyDefinition represents a foreign key constraint.
 */
final class ForeignKeyDefinition implements JsonSerializable
{
    public function __construct(
        public string $name,
        public array $columns,
        public string $referencesTable,
        public array $referencesColumns = ['id'],
        public ?string $onDelete = null,
        public ?string $onUpdate = null,
        public array $extras = [],
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'columns' => $this->columns,
            'referencesTable' => $this->referencesTable,
            'referencesColumns' => $this->referencesColumns,
            'onDelete' => $this->onDelete,
            'onUpdate' => $this->onUpdate,
            'extras' => $this->extras,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
