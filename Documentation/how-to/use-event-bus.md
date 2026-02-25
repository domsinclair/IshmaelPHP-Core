# How to: Use the Event Bus

The Event Bus allows different parts of your Ishmael application to communicate without being tightly coupled.

## Dispatch an event

### From a Controller or Service
Use the `event()` helper or the `Event` facade.

```php
// Dispatch a string event with data
event('user.registered', ['user_id' => $user->id]);

// Dispatch a typed event object
use Modules\Auth\Events\UserRegistered;
Event::dispatch(new UserRegistered($user));
```

## Subscribe to an event

### Using a Module Manifest (Recommended)
Add a `listeners` key to your `module.php`. This is the best way to handle events in a modular application.

```php
// modules/my-module/module.php
return [
    'listeners' => [
        'user.registered' => [
            'Modules\MyModule\Listeners\SendWelcomeEmail',
        ],
    ],
];
```

### Manually in code
Use `Event::subscribe()` in a service provider or during app bootstrap.

```php
use Ishmael\Core\Event;

Event::subscribe('user.registered', function($data) {
    // Your logic here
});
```

## Create a Listener class

Create a class with a `handle` method. If you are responding to a typed event, type-hint it in the constructor or handle method.

```php
namespace Modules\MyModule\Listeners;

class SendWelcomeEmail
{
    public function handle($event): void
    {
        // $event is the payload or the event object
        $userId = is_array($event) ? $event['user_id'] : $event->user->id;

        // Logic to send email...
    }
}
```

## Document your events for AI/IDE discovery

To help the MCP server and PHPStorm plugin understand what events your module emits, add them to the `emits` section of your `module.php`.

```php
// modules/my-module/module.php
return [
    'emits' => [
        'user.registered' => [
            'description' => 'Fired when a new user registers.',
            'payload' => ['user_id' => 'int']
        ],
    ],
];
```

## List all events in the system

Use the CLI to see what events are available and who is listening to them.

```bash
php ish events:list
```
