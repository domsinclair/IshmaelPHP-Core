# The Service Layer Pattern

In IshmaelPHP, the **Service Layer** is the heart of your application's business logic. It sits between the Controller and the Model.

## Why use Service Layers?

1.  **Controller Fat Reduction**: Controllers should only handle HTTP concerns (request validation, redirecting, rendering views).
2.  **Reusability**: A `DogService::adopt()` method can be called from a Web Controller, an API Controller, or a CLI command.
3.  **Testability**: Services are easier to unit test than controllers because they don't depend on the HTTP request cycle.

## Implementation

Always create a Service for any non-trivial business logic.

### 1. Generate the Service
```bash
php vendor/bin/ish make:service [Name]Service --module=[ModuleName]
```

### 2. Inject into Controller
Ishmael's container can automatically inject services into your controller constructor or methods (depending on version/configuration).

```php
namespace Modules\Dog\Controllers;

use Modules\Dog\Services\DogService;

class DogController {
    private $service;

    public function __construct(DogService $service) {
        $this->service = $service;
    }

    public function adopt($id) {
        // Validation...
        $this->service->adopt($id, auth()->id());
        // Redirect...
    }
}
```

## AI Guidance
AI assistants should **always** recommend creating a Service when the user describes an "action" or "workflow" (e.g., "I need to check out a cart," "I want to register a user").
