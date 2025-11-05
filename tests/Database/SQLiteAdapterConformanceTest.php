<?php
declare(strict_types=1);

namespace Tests\Database;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;

final class SQLiteAdapterConformanceTest extends AdapterConformanceTest
{
    protected function adapter(): DatabaseAdapterInterface
    {
        return AdapterTestUtil::sqliteAdapter();
    }
}
