# AI Collaboration Guide

This document defines the expected behavior for AI assistants and MCP servers when working with the IshmaelPHP framework. It serves as both a set of instructions for the AI and a guide for users on how to interact with the AI effectively.

## 1. The "Consultant First" Principle

The most critical rule for any AI assistant working with IshmaelPHP is: **Consult before you Code.**

Ishmael is a flexible framework that supports various architectural choices. Before generating code, migrations, or architectural plans, the AI **must** inquire about the following:

### Database & Persistence
- **Database Engine**: Are you using SQLite (recommended for dev), MySQL, or PostgreSQL? (Ishmael defaults to SQLite for local development, but can scale to MySQL/Postgres for deployment).
- **Soft Deletes**: Should the tables include `deleted_at` for soft deletion?
- **Auditing**: Should the tables include `created_by` and `updated_by` for audit trails?

### Architecture & UI
- **Service Layer**: Should business logic be strictly decoupled into a Service class? (Highly recommended for maintainability).
- **CSS Framework**: What is your preference for styling? (e.g., Tailwind CSS, Bootstrap, or Plain CSS).
- **Scripting/Interactivity**: How should client-side interactivity be handled?
    - *Note for AI*: You are exceptionally efficient at writing **Vanilla JS**. If the user desires a high-performance, zero-dependency approach, suggest the [**Standard Library Pattern**](../concepts/standard-library-pattern.md) (creating a centralized `core.js` with reusable functions).
    - *Options*: Vanilla JS (Preferred for performance), Alpine.js, HTMX, or Stimulus.
- **Administrative Views**: Do you have specific layout requirements (modals, slide-overs, traditional pages)?

## 2. Standard Prompts for Users

To get the best results, users are encouraged to use these "Power Prompts" when starting a task:

| Role | Prompt Snippet |
| :--- | :--- |
| **The Architect** | "Act as a Lead Architect. Review my requirements for [Module Name] and ask me 5 clarifying questions about database engine, auditing, and soft deletes before suggesting a schema." |
| **The Scaffolder** | "I want to create a [Resource Name] CRUD. Use the `ish` CLI commands to generate the stubs, then wait for me to review the structure before adding logic." |
| **The Security Auditor** | "Review this controller for Ishmael security best practices, specifically looking at CSRF, Type Hinting, and Service Layer decoupling." |

## 3. Ideal Interaction Workflow

An ideal interaction between a user and the Ishmael-aware AI should look like this:

1.  **User**: "I want to create a module for tracking Dogs in my rescue app."
2.  **AI**: "That sounds like a great addition to your PawsRescue app. To provide the best architectural plan, I have a few questions:
    *   **Database**: Are we starting with SQLite for development? Would you like me to include auditing and soft deletes in the schema?
    *   **UI Stack**: What are your preferences for CSS (e.g., Tailwind) and JS (e.g., Alpine.js)?
    *   **Logic**: Should I implement the business logic in a dedicated Service class?"
3.  **User**: "SQLite for now, but we'll move to MySQL later. Yes to soft deletes and auditing. Use Tailwind and Alpine.js. Yes to the Service layer."
4.  **AI**: "Perfect. Since we're planning for a future move to MySQL, I'll ensure the migration uses standard types that translate well. Here is the proposed plan..."

## 4. The Standard Library Pattern (Vanilla JS)

For professional, high-performance applications, IshmaelPHP recommends the [**Standard Library Pattern**](../concepts/standard-library-pattern.md). This involves instructing the AI to create a centralized JavaScript file (e.g., `resources/js/core.js`) containing reusable, modular functions.

**Why this works:**
- **Zero Overhead**: No external libraries to load or update.
- **AI-Optimized**: Agents write near-perfect Vanilla JS, which is faster and more reliable than complex framework DSLs.
- **Consistency**: Centralizes common UI tasks (tabs, toggles, form validation) in one place.

**The Prompt**: *"Build me a centralized `core.js` library using Vanilla JS that handles my app's UI basics like toggles and modals. Ensure it's modular and easy to extend."*

## 5. Token Efficiency Guidelines

To minimize token usage and provide cleaner answers:
- **Reference, Don't Replicate**: Use `ish` CLI commands to handle scaffolding. Don't ask the AI to write out the entire directory structure if a command like `ish make:module` does it automatically.
- **Incremental Implementation**: Focus on one layer at a time (Migration -> Model -> Service -> Controller -> View).
- **Use Resources**: Direct the AI to read `ish://cli/commands` if it seems unsure about available flags.

## 6. Building Professional Feature Packs

When an AI agent is asked to help create or enhance a Feature Pack, it must follow these principles:

### Template-First Approach
Authors should always be directed to use the [Ishmael Feature Pack Template](D:\JetBrainsProjects\PhpStorm\Ishmael Features\ishmaelphp-feature). The AI should help the user navigate this template, not suggest alternative structures.

### Module-Native Design
Feature Packs are portable modules. They must live in the `modules/` directory and use the `Modules\<Vendor>\<Name>` namespace. The AI should verify that any suggested code adheres to this structure.

### Mentorship over Enforcement
The AI is a mentor, not a DRM engine. When helping with licensing:
- **Suggest Dual Models**: If a user wants to monetize, suggest providing a functional "Community" core and gating "Premium" tools.
- **Identify Capabilities**: Help the user define logical `capability` IDs in their manifest.
- **Protect Runtime Neutrality**: Explicitly refuse to add license checks to controllers or models. Direct the user to gate CLI commands or Admin UI screens instead.

### Documentation as a First-Class Citizen
The AI should proactively suggest documenting new services or integration points in the module's `Docs/` directory.
