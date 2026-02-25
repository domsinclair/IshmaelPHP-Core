<?php
declare(strict_types=1);

namespace Ishmael\Tests\Events;

use Ishmael\Core\Event;
use Ishmael\Core\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

class EventFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        Event::setInstance(new Dispatcher());
    }

    public function testFacadeDispatchesToInstance(): void
    {
        $called = false;
        Event::subscribe('facade.test', function() use (&$called) {
            $called = true;
        });

        Event::dispatch('facade.test');

        $this->assertTrue($called);
    }

    public function testSetAndGetInstance(): void
    {
        $dispatcher = new Dispatcher();
        Event::setInstance($dispatcher);
        $this->assertSame($dispatcher, Event::getInstance());
    }
}
