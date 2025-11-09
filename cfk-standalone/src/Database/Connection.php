<?php

declare(strict_types=1);

namespace CFK\Database;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Database Connection - Standalone PDO connection and helper methods
 *
 * Provides a simple, static database interface for the CFK application.
 * Uses PDO with prepared statements for security.
 *
 * @package CFK\Database
 */
class Connection
{
    private static ?PDO $connection = null;

    /**
     * Initialize database connection
     *
     * @param array{host: string, port: int, database: string, username: string, password: string, charset: string, options: array<int, mixed>} $config Database configuration
     * @throws PDOException If connection fails
     */
    public static function init(array $config): void
    {
        if (self::$connection instanceof PDO) {
            return; // Already initialized
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        self::$connection = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options']
        );
    }

    /**
     * Get PDO connection (for transaction support and advanced usage)
     *
     * @return PDO The active PDO connection
     * @throws RuntimeException If database not initialized
     */
    public static function getConnection(): PDO
    {
        if (! self::$connection instanceof PDO) {
            throw new RuntimeException('Database not initialized. Call Connection::init() first.');
        }

        return self::$connection;
    }

    /**
     * Execute a query and fetch all results
     *
     * @param string $sql SQL query with placeholders
     * @param array<int|string, mixed> $params Parameters to bind
     *
     * @return (null|scalar)[][] Array of associative arrays
     *
     * @throws PDOException If query fails
     *
     * @psalm-return list<array<string, null|scalar>>
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute a query and fetch a single row
     *
     * @param string $sql SQL query with placeholders
     * @param array<int|string, mixed> $params Parameters to bind
     *
     * @return (null|scalar)[]|null Associative array or null if no results
     *
     * @throws PDOException If query fails
     *
     * @psalm-return array<string, null|scalar>|null
     */
    public static function fetchRow(string $sql, array $params = []): array|null
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result === false ? null : $result;
    }

    /**
     * Execute a query and return number of affected rows
     *
     * @param string $sql SQL query with placeholders
     * @param array<int|string, mixed> $params Parameters to bind
     * @return int Number of affected rows
     * @throws PDOException If query fails
     */
    public static function execute(string $sql, array $params = []): int
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Insert data into a table
     *
     * @param string $table Table name
     * @param array<string, mixed> $data Associative array of column => value
     * @return int Last insert ID
     * @throws PDOException If insert fails
     */
    public static function insert(string $table, array $data): int
    {
        $pdo = self::getConnection();

        $columns = array_keys($data);
        $placeholders = ':' . implode(', :', $columns);
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Update data in a table
     *
     * @param string $table Table name
     * @param array<string, mixed> $data Associative array of column => value to update
     * @param array<string, mixed> $where Associative array of column => value for WHERE clause
     * @return int Number of affected rows
     * @throws PDOException If update fails
     */
    public static function update(string $table, array $data, array $where): int
    {
        $pdo = self::getConnection();

        $setClause = [];
        foreach ($data as $column => $value) {
            $setClause[] = "{$column} = :{$column}";
        }

        $whereClause = [];
        foreach ($where as $column => $value) {
            $whereClause[] = "{$column} = :where_{$column}";
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setClause) . " WHERE " . implode(' AND ', $whereClause);

        $stmt = $pdo->prepare($sql);

        // Bind data parameters with proper NULL handling
        foreach ($data as $column => $value) {
            if ($value === null) {
                $stmt->bindValue(":{$column}", null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(":{$column}", $value);
            }
        }

        // Bind where parameters
        foreach ($where as $column => $value) {
            $stmt->bindValue(":where_{$column}", $value);
        }

        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Begin database transaction
     *
     * @throws PDOException If transaction cannot be started
     */
    public static function beginTransaction(): void
    {
        self::getConnection()->beginTransaction();
    }

    /**
     * Commit database transaction
     *
     * @throws PDOException If transaction cannot be committed
     */
    public static function commit(): void
    {
        self::getConnection()->commit();
    }

    /**
     * Rollback database transaction
     *
     * @throws PDOException If transaction cannot be rolled back
     */
    public static function rollback(): void
    {
        self::getConnection()->rollBack();
    }

    /**
     * Execute a raw query (for special cases)
     *
     * Use with caution - prefer prepare() for user input
     *
     * @param string $sql Raw SQL query
     * @return PDOStatement Statement object for processing results
     * @throws PDOException If query fails
     */
    public static function query(string $sql): PDOStatement
    {
        $statement = self::getConnection()->query($sql);
        if ($statement === false) {
            throw new RuntimeException('Query execution failed: ' . $sql);
        }

        return $statement;
    }
}
