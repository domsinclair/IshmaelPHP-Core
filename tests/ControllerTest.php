<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use PHPUnit\Framework\TestCase;

final class ControllerTest extends TestCase
{
    public function testGetModuleNameFromModulesNamespace(): void
    {
        // Define a concrete controller in a Modules namespace to exercise parsing
        if (!class_exists(\Modules\Foo\Controllers\DemoController::class)) {
            eval('namespace Modules\\Foo\\Controllers; class DemoController extends \\Ishmael\\Core\\Controller { ' .
                'public function expose(): string { return $this->getModuleName(); } }');
        }

        $ctrl = new \Modules\Foo\Controllers\DemoController();
        $this->assertSame('Foo', $ctrl->expose());
    }

    public function testGetModuleNameDefaultsToApp(): void
    {
        if (!class_exists(\Tests\DummyBaseController::class)) {
            eval('namespace Tests; class DummyBaseController extends \\Ishmael\\Core\\Controller { ' .
                'public function expose(): string { return $this->getModuleName(); } }');
        }

        $ctrl = new \Tests\DummyBaseController();
        $this->assertSame('App', $ctrl->expose());
    }
}
