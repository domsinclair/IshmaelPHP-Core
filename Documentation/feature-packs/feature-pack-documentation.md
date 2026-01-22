# Feature Pack Guidelines & Documentation

Feature Packs (Modules) in Ishmael are designed to be self-contained, professional, and easily shareable. To ensure your Feature Pack is usable by both human developers and AI agents, it must adhere to specific structural guidelines and include well-structured documentation.

## What is a Feature Pack?

In IshmaelPHP, a **Feature Pack** is a professional, portable module. While all application logic lives in modules, a Feature Pack is designed for reuse and discovery via the registry. It follows the exact same structure as a standard module but includes additional metadata for licensing and capabilities.

## The Professional Foundation

Authors are strongly encouraged to use the official [Feature Pack Template](D:\JetBrainsProjects\PhpStorm\Ishmael Features\ishmaelphp-feature) as a starting point. This template ensures alignment with the "Ishmael Way" and simplifies integration with the MCP server and CLI tools.

## Structure and Metadata

A Feature Pack lives in the `modules/` directory. Its identity is defined by its manifest (`module.php` or `module.json`).

```text
modules/
└── <Vendor>.<Name>/
    ├── Docs/                <-- Documentation goes here
    ├── Controllers/         # UI Logic
    ├── Models/              # Persistence
    ├── Services/            # Business Logic
    ├── Database/
    │   └── Migrations/      # Schema definitions
    └── module.php           # The Source of Truth
```

### Manifest Integration (Licensing)

For professional packs, the manifest should include licensing metadata. This allows the MCP server and framework to handle capabilities correctly.

```php
// module.php example
return [
    'name' => 'AdvancedSEO',
    'licensing' => [
        'model' => 'dual', // community, commercial, or dual
        'enforcement' => 'development',
    ],
    'capabilities' => [
        ['id' => 'seo.base', 'license' => 'community'],
        ['id' => 'seo.analyzer', 'license' => 'commercial'],
    ],
    // ... standard module keys
];
```

## Documentation Standards

Documentation for a Feature Pack must be placed in a `Docs/` directory at the root of the module.

### AI-Ready Documentation

To make your documentation most useful for AI agents (like those powered by this MCP server):
- **Be Explicit**: Use full namespaces (e.g., `Modules\MyFeature\Services\MyService`).
- **Define Boundaries**: Clearly state what the module *does* and what it *does not* do.
- **Service Signatures**: Document the public methods of your primary services, including parameter types and return values.
- **Licensing Guidance**: Explain which features belong to which `capability` ID so the AI can correctly guide users.

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
4. **Diagrams**: Use Mermaid.js syntax for diagrams if needed.

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
- External dependencies or requirements.

### 3. `INTEGRATION.md` (or `CONSUMER_GUIDE.md`)
Explains how other modules should interact with this one:
- Which Services to inject.
- Common use cases with code snippets.
- Configuration options.
- Event listeners or hooks provided.
