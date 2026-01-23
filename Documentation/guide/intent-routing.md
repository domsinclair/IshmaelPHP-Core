# Intent-Aware AI Collaboration

## What is Intent Routing?
When you ask an AI assistant like Junie a question, it has to guess your goal. If you say, *"I want to build a plugin,"* the AI doesn't know if you're building a project-specific module or a reusable library, what database you use, or your preferred UI tools.

**Intent Routing** is a system that allows the Ishmael MCP server to act as a consultant. It "normalizes" your request, identifies your **Canonical Intent**, and ensures you are asked the right questions before any code is written.

## Why Should You Use It?
1. **Zero Guesswork**: The AI stops guessing and starts following the framework's official architectural rules.
2. **Token Efficiency**: By clarifying requirements (like your DB engine or UI stack) early, you avoid generating hundreds of lines of "wrong" code that you'd have to pay for and then fix.
3. **Professional Results**: It ensures your code follows the **Ishmael Way**, using the correct CLI tools, service layers, and directory structures.

## How to Use It Effectively

### 1. The `ishmael:intent-router` Prompt
The most effective way to start a new feature is to use the dedicated **`ishmael:intent-router`** prompt in your IDE's MCP interface.

- **The Query**: Provide your high-level goal (e.g., *"How do I add a license to my addon?"*).
- **The Response**: The MCP will identify the `add_licensing_to_pack` intent and ask you specific questions (e.g., *"Do you want node-locked or seat-based licensing?"*).

### 2. Tailoring Your Questions
Even if you aren't using the router prompt directly, you can get better results by speaking the "language" of the intent map.

| Instead of saying... | Try saying... | Benefit |
| :--- | :--- | :--- |
| *"Make a plugin"* | *"Design a new **Feature Pack**"* | Identifies a reusable library intent. |
| *"Add a feature"* | *"Create a new **Local Module**"* | Identifies an app-specific intent. |
| *"Secure this"* | *"Add **Licensing** to this pack"* | Triggers the licensing behavior contract. |

### 3. The "Architect" Pattern
Always encourage the AI to be a consultant first. A perfect prompt looks like this:

> *"I want to create a new **Local Module** for my rescue app. Use the `ishmael:intent-router` logic to ask me about my database and UI stack before you propose any code."*

## What the AI "Understands"
The system uses a **Glossary** to map your words to framework concepts:
- **Feature Pack**: plugin, addon, extension.
- **Local Module**: app module, project module.
- **UI Stack**: tailwind, bootstrap, htmx, alpine, vanilla.
- **Database**: sqlite, mysql, postgres, sql.

## Summary Checklist
- [ ] Use the `ishmael:intent-router` prompt for new features.
- [ ] Answer the clarifying questions precisely.
- [ ] Reference the `ish://docs/intent-map` resource if you want to see the rules the AI is following.
- [ ] Always ask the AI to "propose a plan" before it writes any code.

By using Intent Routing, you turn your AI assistant into a framework expert, saving time, reducing costs, and building better software.
