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
     * Fetch a single row as an associative array or null if none.
     * Back-compat for older call sites that used fetchAssoc().
     *
     * @return array<string,mixed>|null
     */
    public function fetchAssoc(): ?array
    {
        $row = $this->stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Fetch the first column of the next row in the result set.
     * Useful for EXISTS checks and scalar queries.
     *
     * @param int $column Zero-based column index
     * @return mixed
     */
    public function fetchColumn(int $column = 0): mixed
    {
        return $this->stmt->fetchColumn($column);
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
     * BC alias: return all rows as associative arrays.
     * Some callers expect Result::all(); delegate to fetchAll().
     *
     * @return array<int, array<string,mixed>>
     */
    public function all(): array
    {
        return $this->fetchAll();
    }

    /**
     * Return the number of affected rows (for UPDATE/DELETE) or rows in the result set if supported.
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }

    /**
     * Convenience: fetch the first row as an associative array or null if none.
     * Does not rewind the cursor; simply performs one fetch() call.
     *
     * @return array<string,mixed>|null
     */
    public function first(): ?array
    {
        $row = $this->fetch();
        return $row === false ? null : $row;
    }
}
