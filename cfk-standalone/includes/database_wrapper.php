<?php
declare(strict_types=1);

/**
 * Database wrapper functions for backward compatibility
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

class Database {

    /**
     * Get PDO connection (for transaction support)
     */
    public static function getConnection(): \PDO {
        return \CFK\Config\Database::getConnection();
    }

    /**
     * Execute a query and fetch all results
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $pdo = \CFK\Config\Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Execute a query and fetch a single row
     */
    public static function fetchRow(string $sql, array $params = []):? array {
        $pdo = \CFK\Config\Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Execute a query and return number of affected rows
     */
    public static function execute(string $sql, array $params = []): int {
        $pdo = \CFK\Config\Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * Insert data into a table
     */
    public static function insert(string $table, array $data): int {
        $pdo = \CFK\Config\Database::getConnection();
        
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
        $pdo = \CFK\Config\Database::getConnection();
        
        $setClause = [];
        foreach ($data as $column => $value) {
            $setClause[] = "{$column} = :{$column}";
        }
        
        $whereClause = [];
        foreach ($where as $column => $value) {
            $whereClause[] = "{$column} = :where_{$column}";
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setClause) . " WHERE " . implode(' AND ', $whereClause);
        
        // Merge data and where params, prefixing where params
        $params = $data;
        foreach ($where as $column => $value) {
            $params["where_{$column}"] = $value;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete data from a table
     */
    public static function delete(string $table, array $where): int {
        $pdo = \CFK\Config\Database::getConnection();
        
        $whereClause = [];
        foreach ($where as $column => $value) {
            $whereClause[] = "{$column} = :{$column}";
        }
        
        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClause);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($where);
        
        return $stmt->rowCount();
    }
}