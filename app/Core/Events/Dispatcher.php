<?php
declare(strict_types=1);

namespace Ishmael\Core\Events;

use Ishmael\Core\Container;
use ReflectionClass;

/**
 * Synchronous Event Dispatcher.
 * Handles immediate execution of event listeners.
 */
class Dispatcher implements EventBusInterface
{
    /** @var array<string, array<mixed>> */
    protected array $listeners = [];

    protected ?Container $container = null;

    public function __construct(?Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(object|string $event, mixed $payload = null): void
    {
        $eventName = is_object($event) ? get_class($event) : (string)$event;
        $eventData = is_object($event) ? $event : $payload;

        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            $this->callListener($listener, $eventData);
        }
    }

    /**
     * @inheritDoc
     */
    public function subscribe(string $eventName, $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    /**
     * Invoke the listener.
     */
    protected function callListener(mixed $listener, mixed $eventData): void
    {
        if (is_callable($listener)) {
            $listener($eventData);
            return;
        }

        // Handle string listeners: 'Class@method' or 'Class' (defaulting to handle)
        if (is_string($listener)) {
            $parts = explode('@', $listener);
            $class = $parts[0];
            $method = $parts[1] ?? 'handle';

            $instance = $this->resolve($class);
            if ($instance && method_exists($instance, $method)) {
                $instance->$method($eventData);
            }
            return;
        }

        // Handle array listeners: [Class, method]
        if (is_array($listener) && count($listener) >= 2) {
            $class = $listener[0];
            $method = $listener[1];

            if (is_string($class)) {
                $class = $this->resolve($class);
            }

            if ($class && method_exists($class, $method)) {
                $class->$method($eventData);
            }
        }
    }

    /**
     * Resolve a class from the container if possible.
     */
    protected function resolve(string $class): ?object
    {
        if ($this->container && $this->container->has($class)) {
            return $this->container->get($class);
        }

        if (class_exists($class)) {
            return new $class();
        }

        return null;
    }
}
