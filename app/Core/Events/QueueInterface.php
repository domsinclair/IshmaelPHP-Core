<?php
declare(strict_types=1);

namespace Ishmael\Core\Events;

/**
 * QueueInterface defines the contract for offloading tasks to a background worker.
 */
interface QueueInterface
{
    /**
     * Push a listener and its event data to the queue.
     *
     * @param mixed $listener The listener to execute.
     * @param mixed $eventData The event data to pass to the listener.
     * @return void
     */
    public function push(mixed $listener, mixed $eventData): void;
}
