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
    /** @var array<string, array<int, array<array{listener: mixed, priority: int}>>> */
    protected array $listeners = [];

    /** @var array<string, bool> */
    protected array $sorted = [];

    protected ?Container $container = null;

    protected ?QueueInterface $queue = null;

    public function __construct(?Container $container = null, ?QueueInterface $queue = null)
    {
        $this->container = $container;
        $this->queue = $queue;
    }

    /**
     * Set the queue driver.
     */
    public function setQueue(QueueInterface $queue): void
    {
        $this->queue = $queue;
    }

    public function dispatch(object|string $event, mixed $payload = null): void
    {
        $eventName = is_object($event) ? get_class($event) : (string)$event;
        $eventData = is_object($event) ? $event : $payload;

        $matchedListeners = $this->getListenersForEvent($eventName);

        foreach ($matchedListeners as $listenerData) {
            $this->callListener($listenerData['listener'], $eventData);
        }
    }

    /**
     * Get all listeners that match the event name (including wildcards).
     *
     * @return array<array{listener: mixed, priority: int}>
     */
    protected function getListenersForEvent(string $eventName): array
    {
        $matched = $this->listeners[$eventName] ?? [];

        // Check for wildcard matches (e.g., 'user.*')
        foreach ($this->listeners as $pattern => $listeners) {
            if ($pattern === $eventName) {
                continue;
            }

            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace(['\\', '*'], ['\\\\', '.*'], $pattern) . '$/';
                if (preg_match($regex, $eventName)) {
                    $matched = array_merge($matched, $listeners);
                }
            }
        }

        // Sort by priority (higher first)
        usort($matched, fn($a, $b) => $b['priority'] <=> $a['priority']);

        return $matched;
    }

    /**
     * @inheritDoc
     */
    public function subscribe(string $eventName, $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][] = [
            'listener' => $listener,
            'priority' => $priority,
        ];
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

            if ($this->shouldQueue($class)) {
                $this->queue?->push($listener, $eventData);
                return;
            }

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

            if ($this->shouldQueue($class)) {
                $this->queue?->push($listener, $eventData);
                return;
            }

            if (is_string($class)) {
                $class = $this->resolve($class);
            }

            if ($class && method_exists($class, $method)) {
                $class->$method($eventData);
            }
        }
    }

    /**
     * Determine if a listener should be queued.
     */
    protected function shouldQueue(mixed $listener): bool
    {
        if (!$this->queue) {
            return false;
        }

        $class = is_object($listener) ? get_class($listener) : (string)$listener;

        return is_subclass_of($class, QueuedEventListenerInterface::class);
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
