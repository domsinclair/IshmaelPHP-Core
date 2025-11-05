<?php
declare(strict_types=1);

namespace Tests\Database;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;

final class PostgresAdapterConformanceTest extends AdapterConformanceTest
{
    protected function adapter(): DatabaseAdapterInterface
    {
        $adapter = AdapterTestUtil::fromDsn('TEST_PG_DSN');
        if ($adapter === null) {
            $this->markTestSkipped('TEST_PG_DSN not set; skipping Postgres adapter tests.');
        }
        return $adapter; // @phpstan-ignore-line
    }
}
