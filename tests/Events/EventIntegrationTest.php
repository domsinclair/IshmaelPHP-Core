<?php
declare(strict_types=1);

namespace Ishmael\Tests\Events;

use Ishmael\Core\App;
use Ishmael\Core\ModuleManager;
use Ishmael\Core\Event;
use PHPUnit\Framework\TestCase;

class EventIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetModules();
    }

    protected function tearDown(): void
    {
        $this->resetModules();
    }

    private function resetModules(): void
    {
        $ref = new \ReflectionClass(ModuleManager::class);
        foreach (['modules', 'cachePath'] as $propName) {
            if ($ref->hasProperty($propName)) {
                $prop = $ref->getProperty($propName);
                $prop->setAccessible(true);
                $prop->setValue(null, $propName === 'modules' ? [] : null);
            }
        }
    }

    public function testAppBootRegistersListenersFromManifest(): void
    {
        $baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_events_integration_' . uniqid();
        $modDir = $baseDir . DIRECTORY_SEPARATOR . 'EventModule';
        @mkdir($modDir, 0777, true);

        $called = false;
        // Since we can't easily put a closure in a required file and have it work with var_export,
        // and we want to test registration, we'll use a global variable as a flag.
        $GLOBALS['test_event_called'] = false;

        $manifest = [
            'name' => 'EventModule',
            'listeners' => [
                'test.integration.event' => function() {
                    $GLOBALS['test_event_called'] = true;
                }
            ]
        ];

        // We'll write it manually to include the closure correctly
        $manifestContent = "<?php return [
            'name' => 'EventModule',
            'listeners' => [
                'test.integration.event' => function() {
                    \$GLOBALS['test_event_called'] = true;
                }
            ]
        ];";
        file_put_contents($modDir . DIRECTORY_SEPARATOR . 'module.php', $manifestContent);

        // TEST DISCOVERY DIRECTLY
        ModuleManager::discover($baseDir);
        $this->assertNotEmpty(ModuleManager::$modules, "ModuleManager::discover failed to find modules in $baseDir.");
        $this->resetModules();

        $app = new App();

        // We need to trick App into looking at our temp modules dir.
        // App::boot() uses $this->config['paths']['modules'] ?? base_path('modules')
        // We can use Reflection to set the config.
        $ref = new \ReflectionClass($app);
        $configProp = $ref->getProperty('config');
        $configProp->setAccessible(true);
        $configProp->setValue($app, [
            'paths' => ['modules' => $baseDir],
            'debug' => true
        ]);

        $app->boot();

        Event::dispatch('test.integration.event');

        $this->assertTrue($GLOBALS['test_event_called']);

        // Cleanup
        unset($GLOBALS['test_event_called']);
        @unlink($modDir . DIRECTORY_SEPARATOR . 'module.php');
        @rmdir($modDir);
        @rmdir($baseDir);
    }
}
