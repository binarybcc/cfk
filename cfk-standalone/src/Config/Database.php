<?php

declare(strict_types=1);

namespace CFK\Config;

use PDO;
use PDOException;

/**
 * Database configuration and connection manager
 */
class Database
{
    private static ?PDO $connection = null;
    private static array $config = [];

    /**
     * Initialize database configuration
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Get database connection
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::createConnection();
        }

        return self::$connection;
    }

    /**
     * Create new database connection
     */
    private static function createConnection(): PDO
    {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                self::$config['host'] ?? 'localhost',
                self::$config['port'] ?? 3306,
                self::$config['database'] ?? '',
                self::$config['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            return new PDO(
                $dsn,
                self::$config['username'] ?? '',
                self::$config['password'] ?? '',
                $options
            );
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Close database connection
     */
    public static function close(): void
    {
        self::$connection = null;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): void
    {
        self::getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): void
    {
        self::getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): void
    {
        self::getConnection()->rollBack();
    }
}