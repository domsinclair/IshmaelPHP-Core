# Concept: The Standard Library Pattern (Vanilla JS)

The **Standard Library Pattern** is an architectural approach for managing client-side interactivity in IshmaelPHP applications without relying on heavy external libraries like jQuery, React, or Vue. 

It leverages the fact that modern Vanilla JavaScript is extremely powerful and that AI agents (like the Ishmael MCP) are exceptionally good at writing pure, performant ECMAScript.

## 1. Core Philosophy

Instead of writing "Spaghetti JS" (unique, non-reusable scripts for every page), you create a centralized, modular library of functions that handle common UI tasks. 

**The goal is to have zero dependencies while maintaining high reusability.**

## 2. Why use this pattern?

*   **Performance**: Zero library overhead. Your pages load faster and use fewer system resources.
*   **AI Efficiency**: AI agents write near-perfect Vanilla JS. They often struggle with the "magic" of specific libraries, leading to bugs.
*   **Maintainability**: All your UI logic is in one place (`resources/js/core.js`). 
*   **Future-Proof**: Vanilla JS doesn't have "version breaks" or deprecations like 3rd-party libraries.

## 3. Implementation Example

### Step 1: The Central Library (`resources/js/core.js`)

You ask the AI to create a library like this:

```javascript
/**
 * PawsRescue Core UI Library
 * Zero-dependency Vanilla JS helpers.
 */
const CoreUI = {
    /**
     * Toggle a class on a target element when a trigger is clicked.
     * data-toggle="target-id" data-class="hidden"
     */
    initToggles() {
        document.querySelectorAll('[data-toggle]').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = trigger.dataset.toggle;
                const className = trigger.dataset.class || 'active';
                const target = document.getElementById(targetId);
                if (target) {
                    target.classList.toggle(className);
                }
            });
        });
    },

    /**
     * Simple Modal Handler
     * data-modal-open="modal-id" / data-modal-close
     */
    initModals() {
        document.querySelectorAll('[data-modal-open]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = document.getElementById(btn.dataset.modalOpen);
                if (modal) modal.style.display = 'flex';
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.modal').style.display = 'none';
            });
        });
    },

    /**
     * Run all initializers
     */
    init() {
        this.initToggles();
        this.initModals();
        // Add more helpers here (Tabs, Tooltips, etc.)
    }
};

// Start the library when the DOM is ready
document.addEventListener('DOMContentLoaded', () => CoreUI.init());
```

### Step 2: Using the Pattern in Views

In your Ishmael views, you don't write `<script>` tags. You use **Data Attributes**:

```html
<!-- A simple Toggle Button -->
<button data-toggle="mobile-menu" data-class="show">Menu</button>

<!-- The Menu -->
<div id="mobile-menu" class="hidden">
    ...
</div>

<!-- A Modal Trigger -->
<button data-modal-open="delete-confirm">Delete Dog</button>

<!-- The Modal -->
<div id="delete-confirm" class="modal" style="display:none">
    <div class="modal-content">
        <h3>Are you sure?</h3>
        <button data-modal-close>Cancel</button>
    </div>
</div>
```

## 4. How to Prompt the AI for this Pattern

When using the Ishmael MCP, use the "Architect" approach to trigger this pattern:

> *"I want to implement the **Standard Library Pattern** for my UI. Create a `resources/js/core.js` file with Vanilla JS functions for handling tab switching and mobile menu toggles. Ensure it uses data attributes for triggers so I don't have to write extra JS in my views."*

## 5. Summary

The Standard Library Pattern turns your AI assistant into a master craftsman. By providing a structured way to write pure JavaScript, you ensure your application remains fast, professional, and easy for any developer (or AI) to understand.
