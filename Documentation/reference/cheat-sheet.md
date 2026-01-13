# IshmaelPHP Decision Cheat Sheet

This cheat sheet provides a quick "Decision vs. Result" reference for developers and AI assistants. Use it to quickly identify the best tools and patterns for your project requirements.

## 1. Core Architectural Decisions

| If you need... | Choose this... | Why? |
| :--- | :--- | :--- |
| **Fast prototyping** | **SQLite** | Zero-setup, file-based, perfect for local development. |
| **Scalability / Production** | **MySQL or Postgres** | Handles concurrent users and large datasets efficiently. |
| **An "Undo" button** | **Soft Deletes** | Keeps deleted records in the DB with a `deleted_at` timestamp. |
| **Accountability** | **Auditing** | Tracks `created_by` and `updated_by` for every record. |
| **Clean Controllers** | **Service Layer** | Keeps business logic out of controllers for better testing. |

## 2. UI & Interaction Decisions

| If you need... | Choose this... | Why? |
| :--- | :--- | :--- |
| **Rapid, Custom Design** | **Tailwind CSS** | Utility-first; huge ecosystem of pre-built components (Tailwind UI, DaisyUI). |
| **Pre-made Components** | **Bootstrap** | Classic component-based library; good for traditional dashboard layouts. |
| **Server-Driven UX** | **HTMX** | Best for high-performance, dynamic updates (live search, filters) without complex JS. |
| **Declarative UI Logic** | **Alpine.js** | Lightweight JS for reactive components (modals, dropdowns) directly in HTML. |
| **Structured Interactivity** | **Stimulus** | Explicit controller-based JS that pairs perfectly with server-rendered HTML. |
| **Type Safety & Scale** | **TypeScript** | Catch bugs early and get better IDE support for complex client-side logic. |
| **Zero Dependencies** | **Vanilla JS** | **The Professional Choice**. Best for performance and long-term assets; highly optimized for AI generation. |

## 3. CLI Command Quick Reference

| Action | CLI Command |
| :--- | :--- |
| **Start a new module** | `php vendor/bin/ish make:module [Name]` |
| **Add database table** | `php vendor/bin/ish make:migration [name] --module=[Name]` |
| **Apply migrations** | `php vendor/bin/ish migrate` |
| **Add business logic** | `php vendor/bin/ish make:service [Name]Service --module=[Name]` |
| **Add a controller** | `php vendor/bin/ish make:controller [Name]Controller --module=[Name]` |
| **Add a model** | `php vendor/bin/ish make:model [Name] --module=[Name]` |
| **Check Environment** | `php vendor/bin/ish env:validate` |

## 4. Best Practice Checklist

- [ ] **Consult First**: Did I ask the user about their DB/UI stack before writing code?
- [ ] **CLI First**: Did I use the `ish` command instead of manually creating files?
- [ ] **Module First**: Is this code living inside a module?
- [ ] **Service First**: Is the complex logic in a Service instead of the Controller?
- [ ] **Strict Typing**: Am I using PHP 8+ type hints and return types?

---
*For more detailed explanations, see the [Question Framing Guide](../how-to/framing-questions.md).*
