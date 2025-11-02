<?php
declare(strict_types=1);

use Ishmael\Core\Database;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterFactory;
use PHPUnit\Framework\TestCase;

final class DatabaseTest extends TestCase
{
    protected function tearDown(): void
    {
        // reset static internals of Database between tests
        $ref = new ReflectionClass(Database::class);
        foreach (['connection', 'adapter'] as $prop) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue(null, null);
        }

        // Also reset the adapter factory registry to avoid cross-test pollution
        $refFactory = new ReflectionClass(DatabaseAdapterFactory::class);
        $registry = $refFactory->getProperty('registry');
        $registry->setAccessible(true);
        $registry->setValue(null, []);
    }

    public function testConnBeforeInitThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database not initialized');
        Database::conn();
    }

    public function testInitWithMissingConnectionThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Database connection 'mysql' not found.");
        Database::init([
            'default' => 'mysql',
            'connections' => [
                // empty on purpose
            ],
        ]);
    }

    public function testInitWithSQLiteInMemoryAndConnWorks(): void
    {
        $config = [
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                ],
            ],
        ];

        Database::init($config);
        $pdo = Database::conn();

        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame('sqlite', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        // default fetch mode set by adapter
        $this->assertSame(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }
}
