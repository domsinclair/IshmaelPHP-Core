<?php
declare(strict_types=1);

use Ishmael\Core\Database\Migrations\BaseMigration;
use Ishmael\Core\Database\Schema\TableDefinition;
use Ishmael\Core\Database\Schema\ColumnDefinition;
use Ishmael\Core\Database\Schema\IndexDefinition;

final class CreateRolesTable extends BaseMigration
{
    public function up(): void
    {
        $def = new TableDefinition('roles');
        $def->addColumn(new ColumnDefinition('id', 'integer', nullable: false, autoIncrement: true));
        $def->addColumn(new ColumnDefinition('name', 'string', length: 100));
        $def->addColumn(new ColumnDefinition('slug', 'string', length: 100));
        $def->addColumn(new ColumnDefinition('created_at', 'datetime'));
        $def->addColumn(new ColumnDefinition('updated_at', 'datetime'));
        $def->addIndex(new IndexDefinition('roles_name_unique', ['name'], 'unique'));
        $def->addIndex(new IndexDefinition('roles_slug_unique', ['slug'], 'unique'));
        $this->adapter()->createTable($def);
    }

    public function down(): void
    {
        $this->adapter()->dropTable('roles');
    }
}
