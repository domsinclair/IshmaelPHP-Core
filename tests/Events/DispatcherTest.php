<?php
declare(strict_types=1);

namespace Ishmael\Tests\Events;

use Ishmael\Core\Events\Dispatcher;
use Ishmael\Core\Container;
use PHPUnit\Framework\TestCase;

class DispatcherTest extends TestCase
{
    public function testStringEventDispatchesToClosure(): void
    {
        $dispatcher = new Dispatcher();
        $called = false;
        $payloadData = null;

        $dispatcher->subscribe('user.created', function ($payload) use (&$called, &$payloadData) {
            $called = true;
            $payloadData = $payload;
        });

        $dispatcher->dispatch('user.created', ['id' => 1]);

        $this->assertTrue($called);
        $this->assertSame(['id' => 1], $payloadData);
    }

    public function testClassEventDispatchesToClosure(): void
    {
        $dispatcher = new Dispatcher();
        $called = false;
        $eventObject = null;

        $event = new class {
            public int $id = 123;
        };
        $eventClass = get_class($event);

        $dispatcher->subscribe($eventClass, function ($e) use (&$called, &$eventObject) {
            $called = true;
            $eventObject = $e;
        });

        $dispatcher->dispatch($event);

        $this->assertTrue($called);
        $this->assertSame($event, $eventObject);
        $this->assertEquals(123, $eventObject->id);
    }

    public function testStringListenerWithAtSign(): void
    {
        $container = new Container();
        $dispatcher = new Dispatcher($container);

        // Use a real class instead of anonymous to avoid issues with get_class and '@'
        require_once __DIR__ . '/Fixtures/TestListener.php';
        $listenerClass = \Ishmael\Tests\Events\Fixtures\TestListener::class;
        $listener = new $listenerClass();
        $container->bind($listenerClass, $listener);

        $dispatcher->subscribe('test.event', $listenerClass . '@handle');
        $dispatcher->dispatch('test.event', 'data');

        $this->assertTrue(\Ishmael\Tests\Events\Fixtures\TestListener::$called);
    }

    public function testArrayListener(): void
    {
        $dispatcher = new Dispatcher();
        $called = false;

        $listener = new class {
            public bool $called = false;
            public function onTest($payload): void {
                $this->called = true;
            }
        };

        $dispatcher->subscribe('test.event', [$listener, 'onTest']);
        $dispatcher->dispatch('test.event');

        $this->assertTrue($listener->called);
    }

    public function testMultipleListeners(): void
    {
        $dispatcher = new Dispatcher();
        $count = 0;

        $dispatcher->subscribe('test', function() use (&$count) { $count++; });
        $dispatcher->subscribe('test', function() use (&$count) { $count++; });

        $dispatcher->dispatch('test');

        $this->assertEquals(2, $count);
    }
}
