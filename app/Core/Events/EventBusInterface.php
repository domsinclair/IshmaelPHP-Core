<?php
declare(strict_types=1);

namespace Ishmael\Core\Events;

/**
 * EventBusInterface defines the contract for dispatching and subscribing to events.
 * It supports both simple string events and Typed Event Classes.
 */
interface EventBusInterface
{
    /**
     * Dispatch an event to all registered listeners.
     *
     * @param object|string $event The event instance or name.
     * @param mixed $payload Optional payload if $event is a string.
     * @return void
     */
    public function dispatch(object|string $event, mixed $payload = null): void;

    /**
     * Subscribe a listener to an event.
     *
     * @param string $eventName The event name or class name.
     * @param callable|string|array $listener The listener callback or FQCN.
     * @param int $priority Priority for the listener (higher runs first).
     * @return void
     */
    public function subscribe(string $eventName, $listener, int $priority = 0): void;
}
