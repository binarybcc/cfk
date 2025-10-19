<?php
declare(strict_types=1);

/**
 * Database Migration Runner
 * Executes SQL migration files
 */

// Define the constant before loading config
define('CFK_APP', true);

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Migration file to run (passed as command line argument)
$migrationFile = $argv[1] ?? null;

if (!$migrationFile) {
    echo "Usage: php run_migration.php <migration_file.sql>\n";
    echo "Example: php run_migration.php 002_create_reservations_table.sql\n";
    exit(1);
}

$migrationPath = __DIR__ . '/' . $migrationFile;

if (!file_exists($migrationPath)) {
    echo "Error: Migration file not found: $migrationPath\n";
    exit(1);
}

echo "Running migration: $migrationFile\n";
echo str_repeat('-', 50) . "\n";

// Read the SQL file
$sql = file_get_contents($migrationPath);

if ($sql === false) {
    echo "Error: Could not read migration file\n";
    exit(1);
}

try {
    // Get database connection
    $pdo = \CFK\Config\Database::getConnection();

    // Split by semicolons to handle multiple statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
    );

    $pdo->beginTransaction();

    foreach ($statements as $statement) {
        if (empty($statement)) continue;

        echo "Executing statement...\n";
        $pdo->exec($statement);
        echo "✓ Success\n\n";
    }

    $pdo->commit();

    echo str_repeat('-', 50) . "\n";
    echo "✓ Migration completed successfully!\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "\n✗ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
