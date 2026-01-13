# How to Frame Questions for Ishmael MCP

Collaborating with an AI via the Ishmael MCP server is most effective when you frame your questions to leverage the framework's modularity and the agent's consultative capabilities. 

Many users expect a framework to "just handle everything." While IshmaelPHP does a lot, high-quality software requires making specific decisions early on. This guide helps you understand **what** to ask and **why** it matters.

## 1. The Core Concepts: Why We Ask
If you are new to framework development, some of the terms the AI might ask you about can seem confusing. IshmaelPHP is **agnostic**, meaning it doesn't force you to use one specific tool. This gives you freedom, but it requires you to make choices.

Here is a simple breakdown of the most important concepts and why your choice matters:

### A. Database Engine (The Storage)
The "engine" is the software that actually manages your data. 
*   **SQLite**: A file-based database. It lives in a single file in your project. It is perfect for development because it requires **no installation** and is very fast for single users.
*   **MySQL / PostgreSQL**: These are "Server-based" databases. They are much more powerful, handle many users at once, and have advanced features for data integrity.
*   **Why it matters**: While Ishmael tries to hide the differences, some features (like how dates are stored or how "Auto-increment" works) vary between engines. Choosing early ensures your "Migrations" (the code that builds your tables) work perfectly.

### B. UI Stack (The Look and Feel)
This is divided into two parts: how it looks (CSS) and how it moves (Scripting/JS).

#### 1. CSS (Layout & Design)
*   **Tailwind CSS**: A modern "utility" framework. While it's great for custom design, it also has a **massive ecosystem of pre-built components** (like Tailwind UI or DaisyUI). You get the speed of pre-made parts with the flexibility to change anything.
*   **Bootstrap**: The "gold standard" for classic dashboards. It comes with everything out of the box (modals, navbars, buttons). Good if you want a familiar, standard look with zero design effort.

#### 2. Scripting (Interactivity & Speed)
Choose the right level of interactivity for your needs. Loading too many large libraries can slow down your site and "hog" computer resources, so it's best to use the simplest tool that does the job.

*   **HTMX (The "Fast" Choice)**: Allows the server to update parts of your page without a full reload. 
    *   *Example*: A search bar that updates a list of results as you type.
    *   *Code Example*: `<input type="text" name="q" hx-get="/search" hx-target="#results">`
    *   *Pros*: Very easy for PHP developers; extremely small and fast; no complex JS to learn.
    *   *Cons*: Requires a round-trip to the server for every update.
*   **Alpine.js (The "Reactive" Choice)**: Great for things that happen entirely on the screen.
    *   *Example*: Clicking a button to open a popup or "modal" window.
    *   *Code Example*: `<div x-data="{ open: false }"><button @click="open = true">Open</button><div x-show="open">...</div></div>`
    *   *Pros*: Powerful features like "state" (e.g., is the menu open?) without writing a separate JS file.
    *   *Cons*: Slightly larger library than HTMX; can become messy if you have too much logic in your HTML.
*   **Stimulus (The "Structured" Choice)**: Best for professional, long-term projects. It keeps your JS organized into "Controllers" that pair perfectly with Ishmael's server-rendered HTML.
*   **TypeScript (The "Safe" Choice)**: Not a framework, but a way to write JS that catches errors before they happen. Essential if your app has complex logic (like a medical dose calculator).
    *   *Cons*: Requires a "build" step (compiling), which adds complexity to your development setup.
*   **Vanilla JS (The "Professional" Choice)**: Pure JavaScript without any libraries.
    *   *Example*: A centralized script managing all app-wide UI interactions.
    *   *Code Example*: `document.querySelectorAll('.toggle').forEach(el => ...)`
    *   *Pros*: Zero extra weight; absolute fastest performance; **highly optimized for AI** (agents write near-perfect Vanilla JS).
    *   *Cons*: Requires a more structured approach to prevent code duplication (see [**Standard Library Pattern**](../concepts/standard-library-pattern.md)).

| Tool | Difficulty | Library Size | Best For |
| :--- | :--- | :--- | :--- |
| **HTMX** | Very Easy | Tiny (12kb) | Live searches, filters, and forms. |
| **Alpine.js** | Easy | Small (15kb) | Modals, dropdowns, and toggles. |
| **Vanilla JS** | Harder | None (0kb) | Tiny, unique tweaks. |
| **TypeScript**| Expert | Build Step | Complex, mission-critical logic. |

*   **Why it matters**: If you don't tell the AI, it might write code for Tailwind when you wanted Bootstrap. Even more importantly, choosing **HTMX** can save you from writing hundreds of lines of complex JavaScript. Choosing the wrong scripting tool is the #1 cause of "Spaghetti Code" and slow websites.

### C. Soft Deletes (The "Undo" Button)
*   **What it is**: Instead of permanently deleting a row from the database, it simply adds a date to a `deleted_at` column. The framework then "hides" these records from your app.
*   **Why it matters**: In professional apps, you almost always want this. If a volunteer accidentally deletes a dog's record, you can "un-delete" it in seconds. Without it, that data is gone forever.

### D. Auditing (The Paper Trail)
*   **What it is**: Adding columns like `created_by` and `updated_by` which store the ID of the user who made the change.
*   **Why it matters**: Accountability. If a dog's status changes from "Available" to "Adopted," you need to know *who* authorized that change. This is essential for any app where multiple people share responsibility.

## 2. Leveraging Built-in Prompts

Before asking specific questions, you can "prime" the AI with built-in prompts that establish the "rules" for the project.

| Prompt | What it does |
| :--- | :--- |
| `ishmael:best-practices` | Tells the AI to use CLI tools first and keep code modular. |
| `ishmael:setup-project` | A step-by-step guide for starting a brand new app. |

**Tip**: Start your session by saying: *"Apply the `ishmael:best-practices` prompt for this conversation."*

---

## 3. The Art of the Question: From "Naive" to "Architect"

The difference between a "good" and "bad" question is about how much the AI has to **assume**. Every assumption the AI makes that you have to correct later costs **Tokens** (the "currency" of AI usage) and your time.

### Example 1: Creating a "Dogs" Module (The Basic CRUD)
*The common scenario: A management module with specific workflow rules.*

#### ❌ The "Naive" Question (High Effort)
> *"Create a module for managing dogs in my rescue app."*

*   **The Problem**: The AI doesn't know if you want to track medical history, who added the dog, or how the page should look. It will guess everything.
*   **The Result**: It might write 500 lines of code. You then realize it used a database format you don't like and a CSS style you don't use. You spend 4 more turns "fixing" it.
*   **Token Impact**: **Very High**. You pay for the wrong code, then you pay to fix it.

#### ⚠️ The "Improved" Question (Medium Effort)
> *"I want to create a Dog module. Use the `ish` CLI and tell me what the database schema should look like first."*

*   **The Improvement**: You've told it to use the framework's tools (`ish`) and to show you the plan before writing all the code.
*   **The Result**: The AI shows you a table structure. You can now tell it, "Add a breed column and enable soft deletes."

#### ✅ The "Architect" Question (Low Effort / High Precision)
> *"I want to create a `Dog` module. Before you write any code, act as an Architect. Ask me about my requirements for the database engine, soft deletes, auditing, and my preferred UI stack (CSS/JS). Assume I want a high-performance app using the **Standard Library Pattern** for Vanilla JS."*

*   **The Strategy**: You are forcing the AI to be a consultant and identifying a specific high-performance pattern.
*   **The Result**: The AI stops and asks its questions. Once answered, it not only creates the module but also suggests a centralized `core.js` file for your reusable Vanilla JS functions.
*   **Token Impact**: **Lowest**. You get professional, modular code that is fast and easy to maintain.

---

### Example 2: Creating a "Medical" Module (The Secure Logic)
*The high-security scenario: Sensitive data requiring strict auditing.*

#### ❌ The "Naive" Question
> *"Create a medical records system for the dogs."*

*   **The Problem**: The AI might forget that medical records need **strict history tracking**. It might just create a simple table that any user can edit without leaving a trace.
*   **The Risk**: You lose the medical history of a dog because an edit overwrote previous data.

#### ✅ The "Architect" Question
> *"I'm building a `Medical` module. I need high accountability. Before proposing the schema, ask me about auditing requirements and how we should handle historical record versions. Also, assume I want to use Alpine.js for the dynamic forms."*

*   **The Strategy**: You've identified a "theme" (Accountability) and a specific tool (Alpine.js).
*   **The Result**: The AI suggests a schema with `created_by` and perhaps a separate `medical_history` table, ensuring your data is safe and professional.

---

### Example 3: Creating a "News" Portal (The Public Face)
*The public-facing scenario: Focusing on design and performance.*

#### ❌ The "Naive" Question
> *"Build a news page for my site."*

*   **The Problem**: The AI doesn't know if this is for internal staff or the public. It might build a boring admin table when you wanted a beautiful, image-heavy blog layout.

#### ✅ The "Architect" Question
> *"I want to create a `News` module for the public portal. It needs to be very visual. Before you write code, ask me about my CSS preferences and how we should handle image uploads. I'm using MySQL for production, so keep that in mind for the schema."*

*   **The Strategy**: You've told the AI it's for the "public portal" (audience) and mentioned "MySQL" (environment).
*   **The Result**: The AI suggests a layout using your favorite CSS tool and ensures the database types are optimized for a high-traffic site.

---

## 4. Comparison Table

| Feature | Vague Question | Precise Question |
| :--- | :--- | :--- |
| **CLI Usage** | AI manually writes file structures (error prone). | AI suggests `php vendor/bin/ish make:module`. |
| **Architecture** | Logic ends up in Controllers (messy). | AI suggests `make:service` for business logic. |
| **Environment** | Guesses MySQL (might break on your SQLite setup). | Asks for DB engine; provides compatible types. |
| **Efficiency** | ~2000+ tokens (multiple corrections). | ~500 tokens (one consultation, one execution). |

---

## 5. Summary Checklist for Success

1.  **Stop the AI**: Use phrases like *"Ask me clarifying questions first"* or *"Propose a plan before writing code."*
2.  **Define your Stack**: Early in the chat, say *"I am using SQLite and Tailwind CSS."*
3.  **Use the CLI**: Always ask *"What is the `ish` command for this?"* instead of asking the AI to "create the files."
4.  **Mention the Module**: Always specify the module name (e.g., *"In the Dog module..."*) so the AI doesn't get lost.

By following these steps, you are "training" the AI to be a high-level partner, ensuring your IshmaelPHP project is professional, maintainable, and cost-effective.
