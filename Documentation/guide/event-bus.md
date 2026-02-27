# Event Bus

The Ishmael Event Bus provides a lightweight, synchronous mechanism for decoupled communication between different parts of your application and its modules. It follows a "Discoverability First" philosophy, ensuring that events are not just functional but also visible to developer tools like the Ishmael MCP server and the PHPStorm plugin.

## Core Concepts

The Event Bus operates on a simple Publish-Subscribe pattern:
- **Events**: Signals that something significant has happened (e.g., `user.registered`).
- **Listeners**: Callables or classes that execute in response to an event.
- **Dispatcher**: The central hub that matches events to their listeners.

## Dispatching Events

You can dispatch events using the `Event` facade or the `event()` helper.

### String-based Events
Simple events can be represented as strings.

```php
use Ishmael\Core\Event;

// Dispatch with optional payload
Event::dispatch('order.placed', ['order_id' => 123]);

// Or using the helper
event('order.placed', ['order_id' => 123]);
```

### Typed Event Classes (Recommended)
For better type safety and discoverability, we recommend using Plain Old PHP Objects (POPOs) as events.

```php
namespace Modules\Billing\Events;

class InvoicePaid
{
    public function __construct(
        public int $invoiceId,
        public float $amount
    ) {}
}

// Dispatching a typed event
Event::dispatch(new InvoicePaid(456, 99.99));
```

## Listening for Events

Listeners can be registered manually or declaratively in a module's manifest.

### 1. Declarative Registration (Preferred)
Modules should declare their listeners in `module.php`. This allows Ishmael to automatically wire them during bootstrap and enables discovery by external tools.

```php
// modules/my-module/module.php
return [
    'listeners' => [
        'order.placed' => [
            'Modules\MyModule\Listeners\NotifyAdmin',
        ],
        \Modules\Billing\Events\InvoicePaid::class => [
            'Modules\MyModule\Listeners\UpdateAccountBalance@handle',
        ],
    ],
];
```

### 2. Manual Subscription
You can subscribe to events at runtime using the `Event` facade.

```php
use Ishmael\Core\Event;

// Using a Closure
Event::subscribe('user.login', function($user) {
    Logger::info("User logged in: {$user->email}");
});

// Using a class string (default method is 'handle')
Event::subscribe('user.login', 'App\Listeners\UserLoginLogger');

// Using a specific method
Event::subscribe('user.login', 'App\Listeners\UserLoginLogger@onLogin');
```

## Creating Listeners

A listener is typically a class with a `handle()` method.

```php
namespace Modules\MyModule\Listeners;

use Modules\Billing\Events\InvoicePaid;
use Ishmael\Core\Logger;

class UpdateAccountBalance
{
    public function handle(InvoicePaid $event): void
    {
        // Access typed data from the event object
        Logger::info("Updating balance for invoice #{$event->invoiceId}");
    }
}
```

## Discoverability & Tooling

To make your events discoverable for the MCP server and PHPStorm plugin, you should document them in your module manifest's `emits` section.

```php
// modules/billing/module.php
return [
    'emits' => [
        \Modules\Billing\Events\InvoicePaid::class => [
            'description' => 'Dispatched when a customer successfully pays an invoice.',
            'payload' => ['invoiceId' => 'int', 'amount' => 'float']
        ],
    ],
];
```

### CLI Discovery
You can list all registered events and listeners using the Ishmael CLI:

```bash
php ish events:list
```

For a machine-readable format (JSON), used by the MCP server:

```bash
php ish events:list --json
```

## Framework Events

Ishmael Core emits several lifecycle events that you can hook into:

| Event Name | Description | Payload |
| :--- | :--- | :--- |
| `app.boot` | Fired when the application finishes booting. | `null` |
| `app.terminate` | Fired after the response has been sent to the client. | `['request' => Request, 'response' => Response]` |
| `router.matched` | Fired when a route is successfully matched. | `['route' => Route, 'params' => array]` |

## Advanced Features

### Wildcard Support
You can listen to a group of events using the `*` notation.

```php
// Listen to all user events
Event::subscribe('user.*', function($event) {
    // ...
});
```

### Listener Prioritization
Ensure critical listeners run first by providing a priority (higher numbers run first, default is 0).

```php
Event::subscribe('order.placed', 'Modules\Security\Listeners\FraudCheck', 100);
Event::subscribe('order.placed', 'Modules\Sales\Listeners\UpdateStats', 10);
```

### Asynchronous Event Handling

For heavy tasks (e.g., resizing images, sending emails, or calling external APIs), you can offload listener execution to a background worker.

#### 1. Mark the Listener as Queued
A listener is automatically queued if it implements the `Ishmael\Core\Events\QueuedEventListenerInterface`.

```php
namespace Modules\Upload\Listeners;

use Ishmael\Core\Events\QueuedEventListenerInterface;
use Modules\Upload\Events\FileUploaded;

class ResizeImage implements QueuedEventListenerInterface
{
    /**
     * This method will be executed by a background worker.
     */
    public function handle(FileUploaded $event): void
    {
        // Heavy image processing logic here
    }
}
```

#### 2. Implement the Queue Driver
Ishmael provides the `QueueInterface`, but you must provide an implementation that handles the actual queuing logic (e.g., using Redis, a database, or a cloud service).

```php
namespace App\Services;

use Ishmael\Core\Events\QueueInterface;
use Ishmael\Core\Logger;

class MyDatabaseQueue implements QueueInterface
{
    public function push(mixed $listener, mixed $eventData): void
    {
        // 1. Serialize the listener and event data
        // 2. Insert into your 'jobs' table
        Logger::info("Job queued: " . (is_string($listener) ? $listener : get_class($listener)));
    }
}
```

#### 3. Register the Queue Driver
You must register your queue driver with the `Dispatcher` during the application bootstrap. This is typically done in a service provider or directly in `app/Core/App.php` for simpler setups.

```php
use Ishmael\Core\Event;
use App\Services\MyDatabaseQueue;

// Get the dispatcher from the facade or container
$dispatcher = Event::getInstance();

if ($dispatcher) {
    $dispatcher->setQueue(new MyDatabaseQueue());
}
```

#### Important Considerations for Queued Listeners
*   **Serialization**: Both the listener and the event payload must be serializable. Avoid passing complex objects with non-serializable resources (like database connections or open file handles).
*   **Listener References**: When registering queued listeners in the manifest, use **Class Strings** (e.g., `ResizeImage::class`) rather than Closures, as Closures cannot be easily serialized and pushed to a queue.
*   **Worker Process**: You will need a separate CLI process (a "worker") that monitors your queue and executes the `handle()` method of the queued listeners.
