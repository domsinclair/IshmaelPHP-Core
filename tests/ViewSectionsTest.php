<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\ViewSections;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ishmael\Core\ViewSections
 */
final class ViewSectionsTest extends TestCase
{
    /**
     * Ensure basic start/end/yield flow captures output correctly.
     */
    public function testBasicCaptureAndYield(): void
    {
        $sections = new ViewSections();
        $sections->start('title');
        echo 'Posts';
        $sections->end();
        self::assertSame('Posts', $sections->yield('title'));
        self::assertSame('', $sections->yield('missing'));
        self::assertSame('Default', $sections->yield('missing', 'Default'));
        self::assertTrue($sections->has('title'));
        self::assertFalse($sections->has('content'));
    }

    /**
     * Programmatic set() should assign values and respect overwrite flag.
     */
    public function testSetAndOverwriteBehavior(): void
    {
        $sections = new ViewSections();
        $sections->set('content', 'First');
        self::assertSame('First', $sections->yield('content'));
// Do not overwrite when flag is false
        $sections->set('content', 'Second', false);
        self::assertSame('First', $sections->yield('content'));
// Overwrite by default
        $sections->set('content', 'Second');
        self::assertSame('Second', $sections->yield('content'));
    }

    /**
     * Nested sections should capture independently using a simple stack.
     */
    public function testNestedSectionsCaptureIndependently(): void
    {
        $sections = new ViewSections();
        $sections->start('outer');
        echo 'A';
        $sections->start('inner');
        echo 'B';
        $sections->end();
// ends inner
        echo 'C';
        $sections->end();
// ends outer

        self::assertSame('B', $sections->yield('inner'));
        self::assertSame('AC', $sections->yield('outer'));
    }

    /**
     * Calling end() without a matching start() should be a safe no-op.
     */
    public function testEndWithoutStartIsNoOp(): void
    {
        $sections = new ViewSections();
// Nothing started, should not throw or emit warnings
        $sections->end();
// Ensure other behavior still works after the no-op
        $sections->start('title');
        echo 'Ok';
        $sections->end();
        self::assertSame('Ok', $sections->yield('title'));
    }
}
