<?php

declare(strict_types=1);

namespace Tests\Database;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\Database\Schema\TableDefinition;
use Ishmael\Core\Database\Schema\ColumnDefinition;
use Ishmael\Core\Database\Schema\IndexDefinition;
use PHPUnit\Framework\TestCase;

abstract class AdapterConformanceTest extends TestCase
{
    abstract protected function adapter(): DatabaseAdapterInterface;
    public function testConnectAndBasicQuerying(): void
    {
        $adapter = $this->adapter();
        $this->assertTrue($adapter->isConnected());
    // basic DDL/DML
        $adapter->runSql('CREATE TABLE IF NOT EXISTS tmp_conformance (id INTEGER PRIMARY KEY, name TEXT)');
        $affected = $adapter->execute('INSERT INTO tmp_conformance (name) VALUES (:n)', [':n' => 'Ada']);
        $this->assertSame(1, $affected);
        $id = (int)$adapter->lastInsertId();
        $row = $adapter->query('SELECT id,name FROM tmp_conformance WHERE id = :id', [':id' => $id])->first();
        $this->assertNotNull($row);
        $this->assertSame('Ada', $row['name']);
    }

    public function testTransactionsApi(): void
    {
        $adapter = $this->adapter();
        $adapter->runSql('CREATE TABLE IF NOT EXISTS tmp_tx (id INTEGER PRIMARY KEY, v INT)');
        $adapter->beginTransaction();
        $this->assertTrue($adapter->inTransaction());
        $adapter->execute('INSERT INTO tmp_tx (v) VALUES (1)');
        $adapter->rollBack();
        $this->assertFalse($adapter->inTransaction());
        $count = $adapter->query('SELECT COUNT(*) AS c FROM tmp_tx')->first()['c'] ?? 0;
        $this->assertSame(0, (int)$count);
    }

    public function testDeclarativeSchemaHelpers(): void
    {
        $adapter = $this->adapter();
        $def = new TableDefinition('tmp_decl', [
            new ColumnDefinition('id', 'INTEGER', false, null, null, null, null, false, true),
            new ColumnDefinition('name', 'TEXT', false, null),
        ], [
            new IndexDefinition('idx_tmp_decl_name', ['name'], 'index'),
        ]);
        if ($adapter->tableExists('tmp_decl')) {
            $adapter->dropTable('tmp_decl');
        }
        $adapter->createTable($def);
        $this->assertTrue($adapter->tableExists('tmp_decl'));
        $this->assertTrue($adapter->columnExists('tmp_decl', 'name'));
        $adapter->addColumn('tmp_decl', new ColumnDefinition('extra', 'TEXT', true));
        $this->assertTrue($adapter->columnExists('tmp_decl', 'extra'));
        $adapter->dropTable('tmp_decl');
        $this->assertFalse($adapter->tableExists('tmp_decl'));
    }
}
