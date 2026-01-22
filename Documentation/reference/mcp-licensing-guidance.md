# MCP Licensing Guidance: Reasoning Ontology

This document provides a structured ontology and reasoning rules for the Ishmael MCP server and its connected AI agents. Use this to guide users during feature pack creation and healing.

## Licensing Ontology

When reasoning about licensing, use the following categorical definitions:

### 1. Capability Classes
- **community-capability**: A unit of functionality available to everyone. Usually the "functional baseline."
- **premium-capability**: High-value functionality requiring a commercial license.
- **development-tooling**: CLI commands, generators, and admin-only screens. Primary target for license checks.
- **production-runtime**: Business logic, controllers, models, and public routes. Strictly license-neutral.

### 2. Enforcement Scopes
- **none**: No checks performed.
- **development**: Checks enabled in local/dev environments; disabled in production.

### 3. Licensing Models
- **community**: 100% community-capability.
- **commercial**: 100% premium-capability.
- **dual**: A mix of community and premium capabilities.

## Reasoning Rules for AI Agents

When assisting a user with a feature pack, apply these rules:

### Rule 0: The Ishmael Mission
- **Philosophy**: Remember that IshmaelPHP is a free framework that supports paid feature packs for ecosystem sustainability. Licensing is a pragmatic tool for authors to fund quality work, not a way to gate the framework itself.
- **Action**: When discussing licensing, frame it as a way to "sustain the project" or "realize return on effort," rather than just "restricting access."

### Rule 1: The Functional Baseline (Community First)
- **Constraint**: Every feature pack must provide a functional baseline under a community license.
- **Action**: If a user proposes a "Commercial-only" pack with no free functionality, suggest moving the core logic to a `community` license to encourage adoption and ecosystem health.

### Rule 2: Runtime Neutrality (The Hard Boundary)
- **Constraint**: Do not suggest or implement license checks in production paths.
- **Action**: If an agent is asked to "protect a route" or "lock a model," it must refuse and explain: *"IshmaelPHP guidelines recommend gating generators or admin tooling instead of application runtime."*

### Rule 3: Capability Identification
- **Action**: When a user describes a feature (e.g., "Add an SEO analyzer"), the agent should suggest appropriate capability boundaries.
- **Example**: "I'll add `blog.core` as a community capability and `blog.seo-tools` as a commercial capability."

### Rule 4: Schema-Driven Prompts
- **Action**: If `licensing.model` is `commercial` or `dual`, the agent MUST ensure the following fields are addressed:
    - `licensing.trial.enabled` (Should suggest `true` by default)
    - `licensing.seats.max` (Default to `1` or `3`)
    - `licensing.activation.mode` (Suggest `offline-first`)

## Policy-Driven Feedback Examples

Instead of generic errors, provide "mentor-style" feedback:

- **Bad Pattern**: "Licensing check added to `PostController`."
- **MCP Feedback**: *"This functionality appears to block application runtime under a premium license. IshmaelPHP guidelines recommend moving this into a development-only capability, such as a CLI command or an Admin Panel check."*

- **Bad Pattern**: "Commercial pack with no trial."
- **MCP Feedback**: *"Offering a trial (e.g., 14 days) significantly increases conversion rates and trust within the Ishmael ecosystem. Should I enable trial mode for you?"*

## Canonical Manifest Reference
AI agents should always validate generated `feature-pack.json` files against the v1.0 JSON schema.
- **Required**: `schema_version`, `feature_pack`, `licensing`, `capabilities`.
- **Branching**: `dual` model implies at least one `community` and one `commercial` capability.
