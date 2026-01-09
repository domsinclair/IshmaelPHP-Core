# AI-Friendly RAG Configuration for Ishmael Modules

Ishmael is designed to be "AI-native," providing structured ways for AI agents (like those powered by MCP) to understand your application's architecture and extract high-quality knowledge.

## 1. Module Intent Metadata

You can signal the purpose and nature of a module by adding intent metadata to your `module.php` or `module.json` manifest.

### module.php

```php
return [
    'name' => 'Docs',
    'type' => 'content',      // content | service | utility | feature
    'audience' => 'developer', // developer | end-user
    'stability' => 'stable',  // stable | experimental | legacy
    'knowledge' => true,      // Tells AI agents this is a primary knowledge source
    // ...
];
```

### module.json

```json
{
  "name": "Docs",
  "intent": {
    "type": "content",
    "audience": "developer",
    "stability": "stable",
    "knowledge": true
  }
}
```

## 2. Semantic View Helpers

To help RAG (Retrieval-Augmented Generation) systems accurately "chunk" your content, use Ishmael's semantic view helpers. These helpers wrap your content in machine-readable HTML tags with descriptive `data` attributes.

### `knowledge_page($title, $content)`
Defines the root of a knowledge-dense view.

```php
<?= knowledge_page('Routing', function() use ($sections) { ?>
    <!-- Page content here -->
<?php }) ?>
```

### `concept($name, $content)`
Wraps a specific conceptual unit or section.

```php
<?= concept('Basic Routing', function() { ?>
    <p>Routes map URLs to controllers.</p>
<?php }) ?>
```

### `code_example($lang, $content)`
Explicitly marks code blocks to distinguish them from descriptive text.

```php
<?= code_example('php', function() { ?>
    $router->get('/blog', BlogController::class);
<?php }) ?>
```

## 3. Scaffolding RAG Content

You can scaffold RAG-friendly modules and views directly via the CLI:

### Create a Knowledge-Dense Module
```bash
php ish make:module Help --knowledge
```
This will inject the `knowledge => true` metadata into your manifest automatically.

### Create a Semantic View
```bash
php ish make:view Help reference/routing --knowledge
```
This will wrap the generated view in `knowledge_page` and a default `concept` block.

## 4. Why This Matters

By using these tools, you are not just writing code; you are building a **Structured Knowledge Graph**.
1. **AI Agents** can instantly identify where the "truth" is located.
2. **IDE Plugins** can provide better context-aware suggestions.
3. **Documentation** stays synchronized with architectural intent.
