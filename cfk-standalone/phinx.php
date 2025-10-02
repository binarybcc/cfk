<?php
declare(strict_types=1);

/**
 * Phinx Configuration
 * Database migrations configuration for Christmas for Kids
 */

// Load application config to get database credentials
define('CFK_APP', true);
require_once __DIR__ . '/config/config.php';

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/database/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinx_migrations',
        'default_environment' => 'development',
        'production' => [
            'adapter' => 'mysql',
            'host' => $dbConfig['host'],
            'name' => $dbConfig['database'],
            'user' => $dbConfig['username'],
            'pass' => $dbConfig['password'],
            'port' => $dbConfig['port'] ?? 3306,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'development' => [
            'adapter' => 'mysql',
            'host' => $dbConfig['host'],
            'name' => $dbConfig['database'],
            'user' => $dbConfig['username'],
            'pass' => $dbConfig['password'],
            'port' => $dbConfig['port'] ?? 3306,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'testing' => [
            'adapter' => 'mysql',
            'host' => $dbConfig['host'],
            'name' => $dbConfig['database'] . '_test',
            'user' => $dbConfig['username'],
            'pass' => $dbConfig['password'],
            'port' => $dbConfig['port'] ?? 3306,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]
    ],
    'version_order' => 'creation'
];
