# Ishmael Framework Conventions

This document provides a machine-readable reference for Ishmael framework conventions. It is intended for both developers and AI assistants to ensure consistent project structure and implementation.

## 1. Database Standards

| Feature | Convention | Example |
|---------|------------|---------|
| **Table Names** | snake_case, plural | `users`, `blog_posts` |
| **Primary Key** | `{singular_table}_id` | `user_id` |
| **Foreign Key** | `{singular_table}_id` | `user_id` |
| **Timestamps** | `created_at`, `updated_at` | `2026-01-10 12:00:00` |
| **Auditing** | `created_by`, `updated_by` | User IDs |
| **Soft Deletes** | `deleted_at` (nullable) | `NULL` or timestamp |
| **Boolean Fields** | `is_{property}` or `has_{property}` | `is_active`, `has_permission` |
| **Index Prefix** | `idx_` | `idx_email` |
| **Unique Prefix** | `uk_` | `uk_username` |

## 2. Structural Standards

| Component | Naming Convention | Location |
|-----------|-------------------|----------|
| **Modules** | PascalCase, Singular | `Modules/{Name}/` |
| **Controllers** | PascalCase + `Controller` | `Modules/{Name}/Controllers/` |
| **Services** | PascalCase + `Service` | `Modules/{Name}/Services/` |
| **Models** | PascalCase, Singular | `Modules/{Name}/Models/` |
| **Migrations** | `snake_case` (Only for file names and DB tables) | `Modules/{Name}/Database/Migrations/` |
| **Views** | `kebab-case` | `Modules/{Name}/Views/` |

## 3. Implementation Rules

1.  **Strict Modularity**: Logic must be in a Module. The `app/` directory is for framework overrides only.
2.  **Pragmatic Abstraction**: Use interfaces and base modules when polymorphism is required or when shared features across multiple modules create a maintenance burden. Recognition of shared features is a key refactoring opportunity.
3.  **Service Decoupling**: Controllers should call Services for business logic. Avoid logic in Controllers or Models.
4.  **Type Hinting**: Always use strict typing and return type hints.
5.  **CLI-First**: Use `ish` CLI for all scaffolding.
6.  **Snake Case Policy**: `snake_case` is reserved exclusively for database table names and migration filenames. Use `PascalCase` for classes and `camelCase` for variables and methods.

## 4. AI-Specific Metadata

When generating code, AI assistants should prioritize these patterns to ensure compatibility with Ishmael's introspection tools.

-   **Docblocks**: Use `@return`, `@param`, and `@throws` explicitly.
-   **Knowledge Patterns**: Use `knowledge_page()`, `concept()`, and `code_example()` in views.
-   **Manifest Intent**: Declare `type`, `audience`, and `knowledge` in `module.php` or `module.json`.
