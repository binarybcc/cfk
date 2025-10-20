<?php
declare(strict_types=1);

/**
 * Database wrapper - Standalone PDO connection and helper methods
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

class Database {
    private static ?PDO $connection = null;

    /**
     * Initialize database connection
     */
    public static function init(array $config): void {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['database']
            );

            self::$connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed', $e->getCode(), $e);
        }
    }

    /**
     * Get PDO connection (for transaction support)
     */
    public static function getConnection(): PDO {
        if (!self::$connection instanceof PDO) {
            throw new RuntimeException('Database not initialized. Call Database::init() first.');
        }
        return self::$connection;
    }

    /**
     * Execute a query and fetch all results
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute a query and fetch a single row
     */
    public static function fetchRow(string $sql, array $params = []): ?array {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Execute a query and return number of affected rows
     */
    public static function execute(string $sql, array $params = []): int {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Insert data into a table
     */
    public static function insert(string $table, array $data): int {
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
     */
    public static function update(string $table, array $data, array $where): int {
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
     * Delete data from a table
     */
    public static function delete(string $table, array $where): int {
        $pdo = self::getConnection();

        $whereClause = [];
        foreach (array_keys($where) as $column) {
            $whereClause[] = "{$column} = :{$column}";
        }

        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClause);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($where);

        return $stmt->rowCount();
    }

    /**
     * Begin database transaction
     */
    public static function beginTransaction(): void {
        self::getConnection()->beginTransaction();
    }

    /**
     * Commit database transaction
     */
    public static function commit(): void {
        self::getConnection()->commit();
    }

    /**
     * Rollback database transaction
     */
    public static function rollback(): void {
        self::getConnection()->rollBack();
    }
}