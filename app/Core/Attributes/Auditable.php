<?php

declare(strict_types=1);

namespace Ishmael\Core\Attributes;

/**
 * Auditable attribute marks a Model as supporting automatic audit fields.
 *
 * Options:
 * - timestamps: automatically manage created_at/updated_at columns.
 * - userAttribution: when true, also fill created_by/updated_by from AuthContext when available.
 * - createdByColumn/updatedByColumn: customize column names for user attribution.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Auditable
{
    /**
     * @param bool $timestamps Enable created_at/updated_at population.
     * @param bool $userAttribution Enable created_by/updated_by population when current user is available.
     * @param string $createdByColumn Column name for creator reference (default 'created_by').
     * @param string $updatedByColumn Column name for updater reference (default 'updated_by').
     */
    public function __construct(
        public bool $timestamps = true,
        public bool $userAttribution = false,
        public string $createdByColumn = 'created_by',
        public string $updatedByColumn = 'updated_by',
    ) {
    }
}
