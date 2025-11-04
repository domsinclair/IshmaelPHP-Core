<?php
declare(strict_types=1);

namespace Ishmael\Core\Database;

use PDOStatement;

/**
 * Lightweight query result wrapper to normalize fetch operations across adapters.
 */
class Result
{
    private PDOStatement $stmt;

    public function __construct(PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    public function fetch(): array|false
    {
        return $this->stmt->fetch();
    }

    public function fetchAll(): array
    {
        return $this->stmt->fetchAll();
    }

    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }
}
