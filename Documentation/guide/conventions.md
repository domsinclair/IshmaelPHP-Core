# Ishmael Framework Conventions

This document provides a machine-readable reference for Ishmael framework conventions. It is intended for both developers and AI assistants to ensure consistent project structure and implementation.

## 1. Database Standards

| Feature | Convention | Example | Mandatory? |
|---------|------------|---------|------------|
| **Database Engine** | SQLite (Local), MySQL/Postgres (Prod) | Configured in `.env` | **Consult User** |
| **Table Names** | snake_case, plural | `users`, `blog_posts` | Yes |
| **Primary Key** | `{singular_table}_id` | `user_id` | Yes |
| **Foreign Key** | `{singular_table}_id` | `user_id` | Yes |
| **Timestamps** | `created_at`, `updated_at` | `2026-01-10 12:00:00` | Yes |
| **Auditing** | `created_by`, `updated_by` | User IDs | **Consult User** |
| **Soft Deletes** | `deleted_at` (nullable) | `NULL` or timestamp | **Consult User** |
| **Boolean Fields** | `is_{property}` or `has_{property}` | `is_active`, `has_permission` | Yes |
| **Index Prefix** | `idx_` | `idx_email` | Yes |
| **Unique Prefix** | `uk_` | `uk_username` | Yes |

## 2. Structural Standards

| Component | Naming Convention | Location | Mandatory? |
|-----------|-------------------|----------|------------|
| **Modules** | PascalCase, Singular | `Modules/{Name}/` | Yes |
| **Controllers** | PascalCase + `Controller` | `Modules/{Name}/Controllers/` | Yes |
| **Services** | PascalCase + `Service` | `Modules/{Name}/Services/` | Yes |
| **Models** | PascalCase, Singular | `Modules/{Name}/Models/` | Yes |
| **Migrations** | `snake_case` | `Modules/{Name}/Database/Migrations/` | Yes |
| **Views** | `kebab-case` | `Modules/{Name}/Views/` | Yes |
| **CSS/JS Stack** | User Preference | Tailwind, Alpine.js, etc. | **Consult User** |

## 3. Implementation Rules

1.  **Strict Modularity**: Logic must be in a Module. The `app/` directory is for framework overrides only.
2.  **Pragmatic Abstraction**: Use interfaces and base modules when polymorphism is required.
3.  **Service Decoupling**: Controllers should call Services for business logic. **AI should always recommend this.**
4.  **Type Hinting**: Always use strict typing and return type hints.
5.  **CLI-First**: Use `ish` CLI for all scaffolding.
6.  **Snake Case Policy**: `snake_case` is reserved exclusively for database table names and migration filenames. Use `PascalCase` for classes and `camelCase` for variables and methods.

## 4. AI-Specific Behavior

When working with Ishmael, AI assistants must:
1.  **Inquire before Implementation**: Ask about DB engine, soft deletes, and auditing.
2.  **UI Agnosticism**: Ask the user for their preferred CSS and JS stack before suggesting view code.
3.  **Encapsulation**: Ensure all logic for a feature stays within its designated Module.
