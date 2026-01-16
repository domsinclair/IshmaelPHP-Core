# Documenting Ishmael Feature Packs

Feature Packs (Modules) in Ishmael are designed to be self-contained and easily shareable. To ensure your Feature Pack is usable by both human developers and AI agents, it must include well-structured documentation.

## Documentation Location

Documentation for a Feature Pack must be placed in a `Docs/` directory at the root of the module:

```text
Modules/
└── MyFeaturePack/
    ├── Docs/                <-- Documentation goes here
    │   ├── README.md        # Overview and quick start
    │   ├── ARCHITECTURE.md  # Deep dive into how it works
    │   └── INTEGRATION.md   # How to use it in other modules
    ├── Controllers/
    ├── Services/
    └── module.php
```

The Ishmael MCP server automatically discovers these folders and surfaces them in the main documentation index under the `feature-packs` category.

## Format and Style

1. **Markdown**: All documentation must be written in Markdown (`.md`).
2. **Clear Headings**: Use hierarchical headings (`#`, `##`, `###`) to structure your content.
3. **Code Examples**: Always include PHP code blocks for integration examples.
4. **Diagrams**: Use Mermaid.js syntax for diagrams if needed (many AI agents and IDEs support this).

## Recommended Articles

While you can add any number of documents, we recommend the following core set:

### 1. `README.md` (or `INDEX.md`)
The entry point. It should contain:
- A brief description of what the Feature Pack does.
- Key features.
- Quick installation/setup steps.

### 2. `ARCHITECTURE.md`
Explains the "Why" and "How":
- Design patterns used (e.g., Service Layer, Factory).
- Database schema overview (if applicable).
- External dependencies or requirements (e.g., PHP extensions, external APIs).

### 3. `CONSUMER_GUIDE.md` (or `INTEGRATION.md`)
Explains how other modules should interact with this one:
- Which Services to inject.
- Common use cases with code snippets.
- Configuration options.
- Event listeners or hooks provided.

## AI-Ready Documentation

To make your documentation most useful for AI agents (like those powered by this MCP server):
- **Be Explicit**: Don't just say "inject the service," show the full namespace: `Modules\MyFeature\Services\MyService`.
- **Define Boundaries**: Clearly state what the module *does* and what it *does not* do.
- **Service Signatures**: Document the public methods of your primary services, including parameter types and return values.
