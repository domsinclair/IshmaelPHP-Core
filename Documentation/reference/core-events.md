# Core Framework Events

Ishmael emits a set of minimal, high-signal core events representing essential state transitions in the application lifecycle. These events provide a stable contract for developers and AI agents to observe and react to system changes.

## Overview

Core events are located in the `Ishmael\Core\Events\Core` namespace. They are structured as Typed Event Classes, allowing for IDE autocompletion and type-safe payload access.

| Event | Description |
|-------|-------------|
| `ApplicationBooted` | Emitted when the framework has fully initialized and is ready to serve requests. |
| `ModuleRegistered` | Emitted after a module has been successfully registered and its services wired. |
| `FeaturePackInstalled` | Emitted when a feature pack has been installed and any migrations completed. |
| `FeaturePackUninstalled` | Emitted when a feature pack has been removed from the system. |
| `AuthenticationSucceeded` | Emitted when a user successfully authenticates. |
| `AuthenticationFailed` | Emitted when an authentication attempt fails. |
| `AuthorizationFailed` | Emitted when a user attempts an action without sufficient permissions. |
| `RequestFailed` | Emitted when an unhandled exception occurs during request processing. |
| `DatabaseTransactionCommitted` | Emitted when a database transaction is successfully committed. |
| `DatabaseTransactionRolledBack` | Emitted when a database transaction is rolled back. |
| `ConfigurationChanged` | Emitted when a runtime configuration value or feature flag is changed. |

---

## 1. Application Lifecycle Events

### ApplicationBooted
Emitted at the end of `App::boot()`.

**Payload:**
* `float $bootTimeMs`: The total time taken to boot the application in milliseconds.

**Example:**
```php
use Ishmael\Core\Events\Core\ApplicationBooted;
use Ishmael\Core\Logger;

Event::subscribe(ApplicationBooted::class, function(ApplicationBooted $event) {
    Logger::info("Application booted in {$event->bootTimeMs}ms");
});
```

---

## 2. Module & Feature Pack Events

### ModuleRegistered
Emitted during module discovery for each discovered module.

**Payload:**
* `string $name`: The name of the module.
* `string $path`: The absolute path to the module directory.
* `array $manifest`: The parsed `module.php` or `module.json` content.

**Example:**
```php
use Ishmael\Core\Events\Core\ModuleRegistered;

Event::subscribe(ModuleRegistered::class, function(ModuleRegistered $event) {
    if ($event->name === 'Blog') {
        // Perform blog-specific global initialization
    }
});
```

---

## 3. Security & Identity Events

### AuthenticationSucceeded
Emitted when `Auth::attempt()` succeeds or `Auth::login()` is called.

**Payload:**
* `array $user`: The authenticated user data.
* `string $guard`: The name of the auth guard (default: 'web').

### AuthenticationFailed
Emitted when `Auth::attempt()` fails due to missing user or invalid credentials.

**Payload:**
* `array $credentials`: The credentials used for the attempt (excluding sensitive fields if filtered).
* `string $guard`: The name of the auth guard.

**Example:**
```php
use Ishmael\Core\Events\Core\AuthenticationFailed;
use Ishmael\Core\Logger;

Event::subscribe(AuthenticationFailed::class, function(AuthenticationFailed $event) {
    Logger::warning("Failed login attempt for: " . ($event->credentials['email'] ?? 'unknown'));
});
```

### AuthorizationFailed
Emitted when `Gate::authorize()` throws an `AuthorizationException`.

**Payload:**
* `?array $user`: The current user (if any).
* `string $ability`: The name of the ability being checked.
* `mixed $resource`: The resource being checked against (if any).

---

## 4. HTTP Request Lifecycle Events

### RequestFailed
Emitted when an exception bubbles up to the `App::handle()` method.

**Payload:**
* `\Throwable $exception`: The unhandled exception.
* `?Request $request`: The request instance.

**Example:**
```php
use Ishmael\Core\Events\Core\RequestFailed;
use Ishmael\Core\Logger;

Event::subscribe(RequestFailed::class, function(RequestFailed $event) {
    // Send alert to external monitoring service
    ExternalMonitor::report($event->exception);
});
```

---

## 5. Database & Transaction Events

### DatabaseTransactionCommitted
Emitted after a successful `Database::transaction()` commit.

**Payload:**
* `string $connection`: The connection name (default: 'default').

### DatabaseTransactionRolledBack
Emitted after a `Database::transaction()` rollback.

**Payload:**
* `string $connection`: The connection name.
* `?\Throwable $reason`: The exception that triggered the rollback.

**Example:**
```php
use Ishmael\Core\Events\Core\DatabaseTransactionRolledBack;

Event::subscribe(DatabaseTransactionRolledBack::class, function(DatabaseTransactionRolledBack $event) {
    if ($event->reason) {
        // Log specifically why the transaction failed
    }
});
```

## Design Principles

* **Stable and Immutable Names**: Core events use PascalCase class names and are part of the framework's stable API contract.
* **Minimal and Lightweight**: Only essential state transitions are emitted by the core.
* **Structured Payloads**: Events provide clear, type-safe properties for observability and AI agent interaction.
* **Runtime Authority**: The framework remains the source of truth for these events, regardless of how an action was initiated (Web, CLI, or MCP).
