<?php
declare(strict_types=1);

namespace Ishmael\Tests\Events\Fixtures;

class TestListener
{
    public static bool $called = false;
    public function handle($payload): void {
        self::$called = true;
    }
}
