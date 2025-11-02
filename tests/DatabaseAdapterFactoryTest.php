<?php
declare(strict_types=1);

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterFactory;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\DatabaseAdapters\MySQLAdapter;
use Ishmael\Core\DatabaseAdapters\SQLiteAdapter;
use Ishmael\Core\DatabaseAdapters\PostgresAdapter;
use PHPUnit\Framework\TestCase;

final class DatabaseAdapterFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetRegistry();
    }

    protected function tearDown(): void
    {
        $this->resetRegistry();
    }

    private function resetRegistry(): void
    {
        $ref = new ReflectionClass(DatabaseAdapterFactory::class);
        $prop = $ref->getProperty('registry');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    public function testRegisterDefaultsAndCreateAdapters(): void
    {
        DatabaseAdapterFactory::registerDefaults();

        $this->assertInstanceOf(MySQLAdapter::class, DatabaseAdapterFactory::create('mysql'));
        $this->assertInstanceOf(SQLiteAdapter::class, DatabaseAdapterFactory::create('sqlite'));
        $this->assertInstanceOf(PostgresAdapter::class, DatabaseAdapterFactory::create('pgsql'));
        $this->assertInstanceOf(PostgresAdapter::class, DatabaseAdapterFactory::create('postgres'));
    }

    public function testCreateUnknownDriverThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No database adapter registered for driver [unknown]');
        DatabaseAdapterFactory::create('unknown');
    }

    public function testRegisterCustomAdapterAndCreate(): void
    {
        // Define a dummy adapter in test scope
        if (!class_exists(Tests\DummyAdapter::class)) {
            eval('namespace Tests; use PDO; use Ishmael\\Core\\DatabaseAdapters\\DatabaseAdapterInterface; class DummyAdapter implements DatabaseAdapterInterface { public function connect(array $config): PDO { return new PDO(\'sqlite::memory:\'); } }');
        }

        DatabaseAdapterFactory::register('dummy', Tests\DummyAdapter::class);
        $adapter = DatabaseAdapterFactory::create('dummy');

        $this->assertInstanceOf(DatabaseAdapterInterface::class, $adapter);
        $pdo = $adapter->connect([]);
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame('sqlite', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }
}
