<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\ModuleManager;
use Ishmael\Core\Controller;
use Ishmael\Core\Http\Response;
use PHPUnit\Framework\TestCase;

final class ControllerRenderPathTest extends TestCase
{
    private string $fixturesBase;
    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesBase = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures')
            ?: (__DIR__ . DIRECTORY_SEPARATOR . 'fixtures');
        // Point ModuleManager to our fixtures module
        $fooPath = $this->fixturesBase . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . 'Foo';
        ModuleManager::$modules['Foo'] = [
            'name' => 'Foo',
            'path' => $fooPath,
            'routes' => [],
            'routeClosure' => null,
        ];
    }

    protected function tearDown(): void
    {
        unset(ModuleManager::$modules['Foo']);
        parent::tearDown();
    }

    public function testRenderWithoutLayoutPrintsChildOutput(): void
    {
        // Create a tiny controller that exposes a public method to call protected render()
        if (!class_exists(\Modules\Foo\Controllers\RenderDemoController::class)) {
            eval('namespace Modules\\Foo\\Controllers; class RenderDemoController extends \\Ishmael\\Core\\Controller { ' .
                'public function showNoLayout(array $vars = []): void { $this->render("child_no_layout", $vars); } }');
        }
        $ctrl = new \Modules\Foo\Controllers\RenderDemoController();
        ob_start();
        $ctrl->showNoLayout(['who' => 'Tester']);
        $out = (string)ob_get_clean();
        $this->assertStringContainsString('<p>Hello Tester</p>', $out);
        // Ensure layout elements are not present
        $this->assertStringNotContainsString('Header', $out);
        $this->assertStringNotContainsString('Footer', $out);
    }

    public function testRenderWithLayoutUsesSectionsYield(): void
    {
        if (!class_exists(\Modules\Foo\Controllers\RenderDemoController2::class)) {
            eval('namespace Modules\\Foo\\Controllers; class RenderDemoController2 extends \\Ishmael\\Core\\Controller { ' .
                'public function showWithLayout(array $vars = []): void { $this->render("child_with_layout", $vars); } }');
        }
        $ctrl = new \Modules\Foo\Controllers\RenderDemoController2();
        ob_start();
        $ctrl->showWithLayout(['who' => 'Alice']);
        $out = (string)ob_get_clean();
        // Should include layout chrome and yielded content
        $this->assertStringContainsString('<header>Header</header>', $out);
        $this->assertStringContainsString('<footer>Footer</footer>', $out);
        $this->assertStringContainsString('<p>Hi Alice</p>', $out);
    }

    public function testRenderWithRelativeParentLayoutPath(): void
    {
        if (!class_exists(\Modules\Foo\Controllers\RenderDemoController3::class)) {
            eval('namespace Modules\\Foo\\Controllers; class RenderDemoController3 extends \\Ishmael\\Core\\Controller { ' .
                'public function show(array $vars = []): void { ' .
                '$this->render("subdir/child_with_relative_parent", $vars); } }');
        }
        // Ensure the subdir exists and file is present in fixtures
        $ctrl = new \Modules\Foo\Controllers\RenderDemoController3();
        ob_start();
        $ctrl->show(['who' => 'Bob']);
        $out = (string)ob_get_clean();
        $this->assertStringContainsString('<header>Header</header>', $out);
        $this->assertStringContainsString('<footer>Footer</footer>', $out);
        $this->assertStringContainsString('<p>Hi Bob</p>', $out);
    }

    public function testRenderWithAbsoluteLayoutPathHonored(): void
    {
        if (!class_exists(\Modules\Foo\Controllers\RenderDemoController4::class)) {
            eval('namespace Modules\\Foo\\Controllers; class RenderDemoController4 extends \\Ishmael\\Core\\Controller { ' .
                'public function show(array $vars = []): void { $this->render("child_with_absolute_layout", $vars); } }');
        }
        $ctrl = new \Modules\Foo\Controllers\RenderDemoController4();
        ob_start();
        $ctrl->show(['who' => 'Cara']);
        $out = (string)ob_get_clean();
        $this->assertStringContainsString('<header>Header</header>', $out);
        $this->assertStringContainsString('<footer>Footer</footer>', $out);
        $this->assertStringContainsString('<p>Hi Cara</p>', $out);
    }

    public function testAutoContentSectionWhenChildDoesNotDefineSections(): void
    {
        if (!class_exists(\Modules\Foo\Controllers\RenderDemoController5::class)) {
            eval('namespace Modules\\Foo\\Controllers; class RenderDemoController5 extends \\Ishmael\\Core\\Controller { ' .
                'public function show(array $vars = []): void { $this->render("child_auto_content", $vars); } }');
        }
        $ctrl = new \Modules\Foo\Controllers\RenderDemoController5();
        ob_start();
        $ctrl->show(['who' => 'Dora']);
        $out = (string)ob_get_clean();
        $this->assertStringContainsString('<header>Header</header>', $out);
        $this->assertStringContainsString('<footer>Footer</footer>', $out);
        // The child output should be yielded as 'content' automatically
        $this->assertStringContainsString('<p>Hi Dora</p>', $out);
    }

    public function testRedirectHelperReturnsResponseWithLocation(): void
    {
        if (!class_exists(\Tests\RevealRedirectController::class)) {
            eval('namespace Tests; class RevealRedirectController extends \\Ishmael\\Core\\Controller { ' .
                'public function go(string $to): \\Ishmael\\Core\\Http\\Response { return $this->redirect($to); } }');
        }
        $ctrl = new \Tests\RevealRedirectController();
        $res = $ctrl->go('/next');
        $this->assertInstanceOf(Response::class, $res);
        $this->assertSame(302, $res->getStatusCode());
        $this->assertSame('/next', $res->getHeaders()['Location'] ?? null);
    }

    public function testDataArrayIsVisibleInLayout(): void
    {
        if (!class_exists(\Modules\Foo\Controllers\RenderDemoController6::class)) {
            eval('namespace Modules\\Foo\\Controllers; class RenderDemoController6 extends \\Ishmael\\Core\\Controller { ' .
                'public function show(): void { $this->data["appName"] = "DemoApp"; ' .
                '$this->render("child_with_data_layout", []); } }');
        }
        $ctrl = new \Modules\Foo\Controllers\RenderDemoController6();
        ob_start();
        $ctrl->show();
        $out = (string)ob_get_clean();
        $this->assertStringContainsString('DemoApp', $out, 'Expected $data[appName] to be visible in layout output');
        $this->assertStringContainsString('<p>Body</p>', $out);
    }
}
