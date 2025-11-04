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

    /**
     * @param PDOStatement $stmt Underlying PDO statement to delegate to.
     */
    public function __construct(PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * Fetch the next row from the result set.
     *
     * @return array<string,mixed>|false Associative array for the next row, or false if no more rows.
     */
    public function fetch(): array|false
    {
        return $this->stmt->fetch();
    }

    /**
     * Fetch all remaining rows from the result set.
     *
     * @return array<int, array<string,mixed>> List of rows as associative arrays.
     */
    public function fetchAll(): array
    {
        return $this->stmt->fetchAll();
    }

    /**
     * Return the number of affected rows (for UPDATE/DELETE) or rows in the result set if supported.
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }
}
