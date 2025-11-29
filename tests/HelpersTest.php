<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    private string $coreBase;
    private ?string $envBackup = null;
    private string $logsDir;
    private string $logFile;
    private string $configDir;
    private array $createdFiles = [];

    protected function setUp(): void
    {
        // Determine base paths using the helper itself
        $this->coreBase = base_path();
        $this->logsDir = base_path('storage' . DIRECTORY_SEPARATOR . 'logs');
        $this->logFile = $this->logsDir . DIRECTORY_SEPARATOR . 'ishmael.log';
        $this->configDir = base_path('config');

        // Ensure directories exist
        if (!is_dir($this->logsDir)) {
            @mkdir($this->logsDir, 0777, true);
        }
        if (!is_dir($this->configDir)) {
            @mkdir($this->configDir, 0777, true);
        }

        // Backup an existing .env if present so tests can manipulate it safely
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $this->envBackup = $envPath . '.bak_' . uniqid();
            rename($envPath, $this->envBackup);
        }

        // Clean existing helper log to make assertions deterministic
        if (file_exists($this->logFile)) {
            @unlink($this->logFile);
        }
    }

    protected function tearDown(): void
    {
        // Restore .env if it was backed up; otherwise remove created .env
        $envPath = base_path('.env');
        if ($this->envBackup !== null) {
            // Remove any .env the tests may have created
            if (file_exists($envPath)) {
                @unlink($envPath);
            }
            // Restore original
            @rename($this->envBackup, $envPath);
            $this->envBackup = null;
        } else {
            // No original .env existed; ensure we clean up any created one
            if (file_exists($envPath)) {
                @unlink($envPath);
            }
        }

        // Remove files created in config directory by tests
        foreach ($this->createdFiles as $f) {
            if (file_exists($f)) {
                @unlink($f);
            }
        }
        $this->createdFiles = [];

        // Do not remove logs directory itself, but we can delete the file for tidiness
        if (file_exists($this->logFile)) {
            @unlink($this->logFile);
        }
    }

    public function testBasePathResolvesProjectRoot(): void
    {
        $expected = realpath(dirname(__DIR__)); // IshmaelPHP-Core
        $this->assertSame($expected, realpath(base_path()));
        $this->assertSame($expected . DIRECTORY_SEPARATOR . 'storage', base_path('storage'));
    }

    public function testStoragePathJoinsUnderStorage(): void
    {
        $this->assertStringEndsWith('IshmaelPHP-Core' . DIRECTORY_SEPARATOR . 'storage', storage_path());
        $this->assertStringEndsWith('IshmaelPHP-Core' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs', storage_path('logs'));
    }

    public function testLogMessageCreatesFileAndAppends(): void
    {
        $this->assertFileDoesNotExist($this->logFile);

        log_message('info', 'First message');
        $this->assertFileExists($this->logFile);
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('INFO: First message', $contents);

        log_message('warning', 'Second message');
        $contents2 = file_get_contents($this->logFile);
        $this->assertGreaterThan(strlen($contents), strlen($contents2));
        $this->assertStringContainsString('WARNING: Second message', $contents2);
    }

    public function testEnsureEnvFileCreatesDefaultEnvWhenMissing(): void
    {
        $envPath = base_path('.env');
        $this->assertFileDoesNotExist($envPath);

        ensure_env_file();
        $this->assertFileExists($envPath);
        $envText = file_get_contents($envPath);
        // If a .env.example exists at the app root, it should be copied verbatim
        $examplePath = base_path('.env.example');
        if (file_exists($examplePath)) {
            $exampleText = file_get_contents($examplePath);
            // Spot-check a couple of lines from the example template
            $this->assertStringContainsString('IshmaelPHP Starter — .env.example', $exampleText);
            $this->assertStringContainsString('APP_NAME="Ishmael Starter"', $envText);
            $this->assertStringContainsString('DB_CONNECTION=sqlite', $envText);
        } else {
            // Otherwise the core default should be used
            $this->assertStringContainsString('APP_NAME=Ishmael', $envText);
            $this->assertStringContainsString('DB_CONNECTION=sqlite', $envText);
        }
    }

    public function testLoadEnvParsesFileAndEnvHelperReadsValues(): void
    {
        $envPath = base_path('.env');
        file_put_contents($envPath, "APP_NAME=UnitTest\nCUSTOM_FLAG=on\n");

        $loaded = load_env();
        $this->assertArrayHasKey('APP_NAME', $loaded);
        $this->assertSame('UnitTest', $loaded['APP_NAME']);
        $this->assertSame('on', env('CUSTOM_FLAG'));
        $this->assertSame('default', env('MISSING_KEY', 'default'));

        // Also confirm superglobals populated
        $this->assertSame('UnitTest', $_ENV['APP_NAME'] ?? null);
        $this->assertSame('UnitTest', $_SERVER['APP_NAME'] ?? null);
        $this->assertSame('UnitTest', getenv('APP_NAME'));
    }

    public function testConfigReadsExistingFileAndReturnsDefaultWhenMissing(): void
    {
        // Create a temporary config file under the project config dir
        $cfgPath = $this->configDir . DIRECTORY_SEPARATOR . 'sample.php';
        $cfgPhp = <<<'PHP'
<?php
return [
    'foo' => 'bar',
    'nested' => [ 'x' => 42 ],
];
PHP;
        file_put_contents($cfgPath, $cfgPhp);
        $this->createdFiles[] = $cfgPath;

        // Should read file and return values
        $this->assertSame('bar', config('sample.foo'));
        $this->assertSame(42, config('sample.nested')['x'] ?? null);
        // Dot-notation deeper than one level is not implemented; access nested array directly
        $this->assertSame(42, (config('sample.nested')['x'] ?? null));

        // Non-existent key defaults
        $this->assertSame('def', config('sample.missing', 'def'));

        // Non-existent file defaults
        $this->assertSame('fallback', config('nope.key', 'fallback'));
    }

    public function testDdIsNotExecutedInUnitTests(): void
    {
        $this->markTestSkipped('dd() terminates the process; skip invoking in unit tests.');
    }
}
