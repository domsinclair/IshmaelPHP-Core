# Phase 12 — Database Additions

This phase focuses on closing core gaps in the database layer: relationships (foreign keys), indexes, custom primary keys, soft deletes, audit fields, and developer‑friendly seeding/reset tools. The milestones are self‑contained and build upon each other.

## Milestones

### 1) Schema and migrations foundation (relationships, indexes, custom PKs)

Goals:

- Define foreign keys with onDelete/onUpdate policies.
- Define indexes (single, composite, unique) in a portable way.
- Allow custom primary key names and strategies (auto increment, UUID/ULID, manual).

Deliverables:

- Model metadata/attributes or config to declare keys, FKs, and indexes.
- Migration DSL and adapter‑specific DDL generation (SQLite, MySQL/MariaDB, Postgres).
- CLI support to run, roll back, and list migrations.

Acceptance criteria:

- Tables can be created with FKs, indexes, and custom PKs across supported adapters.
- Migrations are transactional where supported and are idempotent on re‑runs.

### 2) Soft deletes

Goals:

- Opt‑in soft delete support with a conventional deleted_at column.
- Global query scope that hides soft‑deleted rows by default.

Deliverables:

- Attribute/config toggle per model (e.g., SoftDeletes).
- Helpers: withTrashed, onlyTrashed, restore, forceDelete.

Acceptance criteria:

- Soft‑deleted records are excluded by default and can be retrieved/restored via helpers.

### 3) Audit fields

Goals:

- Automatic created_at/updated_at timestamps.
- Optional created_by/updated_by fields populated from request/job context.

Deliverables:

- Attribute/config (e.g., Auditable) with options for timestamps and user attribution.
- Hooks/middleware to provide the current user subject to the model layer.

Acceptance criteria:

- Timestamps are set/updated correctly; optional user fields are populated when available.

### 4) Seeding and reset tools (dev‑only)

Goals:

- Deterministic, realistic fake data for development/testing.
- Safe purge and reset of auto‑increment/sequence values across adapters.

Deliverables:

- Seeder classes and a registry with ordering.
- CLI: ish db:seed, ish db:reset, and purge/reset options.

Acceptance criteria:

- Seeders run idempotently; tables can be truncated in FK‑safe order; sequences/reset applied.

### Defer: Query builder

Rationale: Can be a separate phase once schema/model conventions and metadata are stable.

## Documentation and examples

A new Database guide page demonstrates end‑to‑end usage: custom PKs, FKs, indexes, soft deletes, audit, and seeding.

See also the top‑level project doc: Docs/Phase-12-Database-Additions.md.

## Testing

- Add PHPUnit tests to assert the new documentation files exist and contain the expected headings.
- Run existing database adapter tests; amend only if the new defaults affect them (no changes expected for this phase).

## Notes

- All new PHP code introduced in this phase must include PHPDoc comments and use camelCase/PascalCase names (no snake_case).
