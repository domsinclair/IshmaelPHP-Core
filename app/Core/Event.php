<?php
declare(strict_types=1);

namespace Ishmael\Core;

use Ishmael\Core\Events\EventBusInterface;

/**
 * Static Facade for the Event Bus.
 * Provides a clean, global API for dispatching and subscribing to events.
 */
class Event
{
    protected static ?EventBusInterface $instance = null;

    /**
     * Set the Event Bus instance.
     */
    public static function setInstance(EventBusInterface $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * Get the Event Bus instance.
     */
    public static function getInstance(): ?EventBusInterface
    {
        return self::$instance;
    }

    /**
     * Dispatch an event.
     *
     * @param object|string $event
     * @param mixed $payload
     */
    public static function dispatch(object|string $event, mixed $payload = null): void
    {
        if (self::$instance) {
            self::$instance->dispatch($event, $payload);
        }
    }

    /**
     * Subscribe to an event.
     *
     * @param string $eventName
     * @param callable|string|array $listener
     */
    public static function subscribe(string $eventName, $listener): void
    {
        if (self::$instance) {
            self::$instance->subscribe($eventName, $listener);
        }
    }
}
