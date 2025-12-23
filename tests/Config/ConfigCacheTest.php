<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\ConfigCache;
use PHPUnit\Framework\TestCase;

final class ConfigCacheTest extends TestCase
{
    private string $tmpRoot;
    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_cfg_' . bin2hex(random_bytes(4));
        @mkdir($this->tmpRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        // Best-effort cleanup
        $cache = ConfigCache::cachePath();
        if (is_file($cache)) {
            @unlink($cache);
        }
        $this->rrmdir($this->tmpRoot);
        parent::tearDown();
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $it) {
            if ($it === '.' || $it === '..') {
                continue;
            }
            $p = $dir . DIRECTORY_SEPARATOR . $it;
            if (is_dir($p)) {
                $this->rrmdir($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }

    public function testCompileSaveLoadAndFreshness(): void
    {
        $dirA = $this->tmpRoot . DIRECTORY_SEPARATOR . 'configA';
        $dirB = $this->tmpRoot . DIRECTORY_SEPARATOR . 'configB';
        @mkdir($dirA, 0777, true);
        @mkdir($dirB, 0777, true);
// Create two config files where B overrides A
        file_put_contents($dirA . DIRECTORY_SEPARATOR . 'app.php', '<?php return ["name" => "Ish", "debug" => false];');
        file_put_contents($dirB . DIRECTORY_SEPARATOR . 'app.php', '<?php return ["debug" => true];');
        file_put_contents($dirA . DIRECTORY_SEPARATOR . 'logging.php', '<?php return ["channel" => "single"];');
        $compiled = ConfigCache::compile([$dirA, $dirB]);
        $this->assertIsArray($compiled['config'] ?? null);
        $this->assertSame('single', $compiled['config']['logging']['channel'] ?? null);
// app.debug should be overridden by dirB
        $this->assertTrue($compiled['config']['app']['debug'] ?? false);
        $path = ConfigCache::save($compiled);
        $this->assertFileExists($path);
        $loaded = ConfigCache::load();
        $this->assertIsArray($loaded);
        $this->assertTrue(ConfigCache::isFresh($loaded, [$dirA, $dirB]));
// Touch and modify one source file to break freshness
        sleep(1);
// ensure mtime change
        file_put_contents($dirB . DIRECTORY_SEPARATOR . 'app.php', '<?php return ["debug" => false];');
        $this->assertFalse(ConfigCache::isFresh($loaded, [$dirA, $dirB]));
// Clear should remove the cache
        $this->assertTrue(ConfigCache::clear());
        $this->assertFileDoesNotExist($path);
    }
}
