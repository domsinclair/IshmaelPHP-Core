<?php
declare(strict_types=1);

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
        $this->fixturesBase = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures') ?: (__DIR__ . DIRECTORY_SEPARATOR . 'fixtures');
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
        if (!class_exists(Modules\Foo\Controllers\RenderDemoController::class)) {
            eval('namespace Modules\\Foo\\Controllers; class RenderDemoController extends \\Ishmael\\Core\\Controller { public function showNoLayout(array $vars = []): void { $this->render("child_no_layout", $vars); } }');
        }
        $ctrl = new Modules\Foo\Controllers\RenderDemoController();

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
        if (!class_exists(Modules\Foo\Controllers\RenderDemoController2::class)) {
            eval('namespace Modules\\Foo\\Controllers; class RenderDemoController2 extends \\Ishmael\\Core\\Controller { public function showWithLayout(array $vars = []): void { $this->render("child_with_layout", $vars); } }');
        }
        $ctrl = new Modules\Foo\Controllers\RenderDemoController2();

        ob_start();
        $ctrl->showWithLayout(['who' => 'Alice']);
        $out = (string)ob_get_clean();

        // Should include layout chrome and yielded content
        $this->assertStringContainsString('<header>Header</header>', $out);
        $this->assertStringContainsString('<footer>Footer</footer>', $out);
        $this->assertStringContainsString('<p>Hi Alice</p>', $out);
    }

    public function testRedirectHelperReturnsResponseWithLocation(): void
    {
        if (!class_exists(Tests\RevealRedirectController::class)) {
            eval('namespace Tests; class RevealRedirectController extends \\Ishmael\\Core\\Controller { public function go(string $to): \\Ishmael\\Core\\Http\\Response { return $this->redirect($to); } }');
        }
        $ctrl = new Tests\RevealRedirectController();
        $res = $ctrl->go('/next');
        $this->assertInstanceOf(Response::class, $res);
        $this->assertSame(302, $res->getStatusCode());
        $this->assertSame('/next', $res->getHeaders()['Location'] ?? null);
    }
}
