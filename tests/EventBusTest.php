<?php
declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Events\Dispatcher;
use Ishmael\Core\Events\QueueInterface;
use Ishmael\Core\Events\QueuedEventListenerInterface;
use PHPUnit\Framework\TestCase;

class EventBusTest extends TestCase
{
    public function testWildcardSubscription(): void
    {
        $dispatcher = new Dispatcher();
        $called = [];

        $dispatcher->subscribe('user.*', function($event) use (&$called) {
            $called[] = 'wildcard:' . $event;
        });

        $dispatcher->subscribe('user.login', function($event) use (&$called) {
            $called[] = 'exact:' . $event;
        });

        $dispatcher->dispatch('user.login', 'data');

        $this->assertContains('wildcard:data', $called);
        $this->assertContains('exact:data', $called);
    }

    public function testPriority(): void
    {
        $dispatcher = new Dispatcher();
        $order = [];

        $dispatcher->subscribe('test', function() use (&$order) {
            $order[] = 'low';
        }, 0);

        $dispatcher->subscribe('test', function() use (&$order) {
            $order[] = 'high';
        }, 100);

        $dispatcher->subscribe('test', function() use (&$order) {
            $order[] = 'medium';
        }, 50);

        $dispatcher->dispatch('test');

        $this->assertSame(['high', 'medium', 'low'], $order);
    }

    public function testQueuedListeners(): void
    {
        $queue = new class implements QueueInterface {
            public array $pushed = [];
            public function push(mixed $listener, mixed $eventData): void {
                $this->pushed[] = [$listener, $eventData];
            }
        };

        $dispatcher = new Dispatcher(null, $queue);

        // Use a real class for testing is_subclass_of
        $dispatcher->subscribe('test.async', TestQueuedListener::class);
        $dispatcher->dispatch('test.async', 'payload');

        $this->assertCount(1, $queue->pushed);
        $this->assertSame(TestQueuedListener::class, $queue->pushed[0][0]);
        $this->assertSame('payload', $queue->pushed[0][1]);
    }
}

class TestQueuedListener implements QueuedEventListenerInterface {
    public function handle($data) {}
}
