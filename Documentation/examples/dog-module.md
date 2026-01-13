# Example: Dog Management Module

This example demonstrates how to implement a complete module for an Animal Rescue application, following IshmaelPHP's best practices and AI-collaboration principles. 

### Why this matters
Implementing a module is more than just generating code. It requires deciding how you will track data (Auditing), how you will handle mistakes (Soft Deletes), and how your users will interact with it (UI Stack).

## 1. Requirements Gathering (The Consultation)

Before any code was written, the developer and the AI established the following requirements. This "Consultation First" phase ensures that the technical choices align with the project's goals.

*   **Database**: SQLite (Development) -> Planning for MySQL (Production).
    *   *Reason*: Using SQLite for dev allows for rapid prototyping without server setup, while MySQL ensures scalability later.
*   **Soft Deletes**: Enabled (`deleted_at`).
    *   *Reason*: Allows "un-deleting" a dog if they were removed by mistake—critical for maintaining accurate rescue history.
*   **Auditing**: Enabled (`created_by`, `updated_by`).
    *   *Reason*: Essential for accountability; we need to know exactly which staff member updated a dog's record or status.
*   **CSS Framework**: Tailwind CSS.
    *   *Reason*: Allows for a highly customized, responsive design using its vast component ecosystem (like DaisyUI) without writing thousands of lines of custom CSS.
*   **Scripting**: HTMX and Alpine.js.
    *   *Reason*: **HTMX** handles the high-performance stuff (like searching the dog gallery) by returning HTML partials from the server. **Alpine.js** handles tiny on-screen things like opening a "Help" modal or a mobile menu.
*   **Statuses**: `Available`, `In Foster`, `Adopted`, `Deceased`, `Returned`.
    *   *Reason*: Clearly defined workflow states for the rescue operation.

## 2. Database Schema

Depending on your requirements, the migration can be simple or include advanced tracking.

### Option A: Standard Schema (No Auditing/Soft Deletes)
This is ideal for simple prototypes where history tracking isn't required.

```php
$this->schema->create('dogs', function(Blueprint $table) {
    $table->id('dog_id');
    $table->string('name');
    $table->string('breed')->nullable();
    $table->string('status'); // Simpler string status for SQLite compatibility
    $table->date('intake_date');
    $table->timestamps(); // created_at, updated_at
});
```

### Option B: Advanced Schema (With Auditing & Soft Deletes)
Recommended for the PawsRescue app to maintain a full audit trail.

| Field | SQLite Type | MySQL Type | Description |
| :--- | :--- | :--- | :--- |
| `dog_id` | INTEGER PK | INT AUTO_INC | Primary Key |
| `name` | TEXT | VARCHAR(255) | Dog's name |
| `status` | TEXT | ENUM(...) | Current status |
| `deleted_at` | DATETIME | TIMESTAMP | **Optional** (Soft delete) |
| `created_by` | INTEGER | INT | **Optional** (Auditing) |

### Migration Snippet (`Modules/Dog/Database/Migrations/xxxx_create_dogs_table.php`)

```php
<?php
use Ishmael\Core\Database\Migrations\BaseMigration;
use Ishmael\Core\Database\Schema\Blueprint;

class CreateDogsTable extends BaseMigration {
    public function up() {
        $this->schema->create('dogs', function(Blueprint $table) {
            $table->id('dog_id');
            $table->string('name');
            $table->string('breed')->nullable();
            
            // For cross-DB compatibility, we use string for status if SQLite is target
            // If strictly MySQL, $table->enum() is preferred.
            $table->string('status'); 
            
            $table->date('intake_date');
            $table->timestamps();
            
            // Optional Features (Based on requirements)
            $table->softDeletes();  // Adds deleted_at
            $table->auditColumns(); // Adds created_by, updated_by
        });
    }
}
```

## 3. Scaffolding the Module

Use the `ish` CLI to generate the foundation. This ensures the correct directory structure and namespace mapping.

```bash
# Generate the module structure
php vendor/bin/ish make:module Dog

# Generate the Migration
php vendor/bin/ish make:migration create_dogs_table --module=Dog

# Generate the Model
php vendor/bin/ish make:model Dog --module=Dog

# Generate the Service (Business Logic)
php vendor/bin/ish make:service DogService --module=Dog
```

## 4. Implementation Details

### The Service Layer (`Modules/Dog/Services/DogService.php`)

Logic for promoting a dog from "Foster" to "Adopted" belongs here, not in the controller.

```php
<?php
namespace Modules\Dog\Services;

use Modules\Dog\Models\Dog;

class DogService {
    public function adopt(int $dogId, int $adopterId): bool {
        $dog = Dog::find($dogId);
        if ($dog->status !== 'In Foster') {
            throw new \Exception("Only dogs currently in foster can be adopted.");
        }
        
        return $dog->update([
            'status' => 'Adopted',
            'adopter_id' => $adopterId
        ]);
    }
}
```

## 5. UI Design Decisions

The framework is CSS-agnostic. For this module, we use:
- **CSS**: Tailwind CSS + DaisyUI (for beautiful, pre-made card components).
- **Interactivity**: 
    - **HTMX** for live status filtering (fast, server-rendered updates).
    - **Alpine.js** for a "Quick View" modal.

### View: `Modules/Dog/Views/index.php` (with HTMX)
```html
<div class="p-4">
    <!-- HTMX Live Search -->
    <input type="text" name="search" hx-get="/dog/search" hx-target="#dog-grid" hx-trigger="keyup changed delay:500ms" placeholder="Search dogs..." class="input input-bordered w-full max-w-xs mb-4">

    <div id="dog-grid" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?= $this->insert('partials/_dog_list', ['dogs' => $dogs]) ?>
    </div>
</div>
```

### View: `Modules/Dog/Views/partials/_dog_list.php`
```html
<?php foreach($dogs as $dog): ?>
    <div class="card bg-base-100 shadow-xl border border-gray-200">
        <div class="card-body">
            <h2 class="card-title"><?= $dog->name ?></h2>
            <div class="badge badge-<?= $dog->status ?>"><?= $dog->status ?></div>
            <div class="card-actions justify-end">
                <!-- Alpine.js Modal Toggle -->
                <button @click="showModal = true; selectedDog = <?= $dog->dog_id ?>" class="btn btn-primary btn-sm">Quick View</button>
                <a href="/dog/<?= $dog->dog_id ?>" class="btn btn-ghost btn-sm">Details</a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
```
