<?php
declare(strict_types=1);

use Ishmael\Core\Database\Migrations\BaseMigration;
use Ishmael\Core\Database\Schema\TableDefinition;
use Ishmael\Core\Database\Schema\ColumnDefinition;
use Ishmael\Core\Database\Schema\IndexDefinition;

final class CreateContactsTable extends BaseMigration
{
    public function up(): void
    {
        $def = new TableDefinition('contacts');
        $def->addColumn(new ColumnDefinition('id', 'integer', nullable: false, default: null, length: null, precision: null, scale: null, unsigned: false, autoIncrement: true));
        $def->addColumn(new ColumnDefinition('first_name', 'string', length: 100));
        $def->addColumn(new ColumnDefinition('last_name', 'string', length: 100));
        $def->addColumn(new ColumnDefinition('email', 'string', length: 190));
        $def->addColumn(new ColumnDefinition('phone', 'string', nullable: true, length: 50));
        $def->addColumn(new ColumnDefinition('notes', 'text', nullable: true));
        $def->addColumn(new ColumnDefinition('created_at', 'datetime'));
        $def->addColumn(new ColumnDefinition('updated_at', 'datetime'));
        $def->addIndex(new IndexDefinition('contacts_email_unique', ['email'], 'unique'));
        $this->adapter()->createTable($def);
    }

    public function down(): void
    {
        $this->adapter()->dropTable('contacts');
    }
}
