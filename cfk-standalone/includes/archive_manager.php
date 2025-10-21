<?php

/**
 * DEPRECATED: This file is kept for backwards compatibility only.
 * The actual implementation has moved to src/Archive/Manager.php
 *
 * The CFK_Archive_Manager class is automatically available via class_alias()
 * defined in config/config.php
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Exit early - class loaded via Composer autoloader
return;

// DEPRECATED CODE BELOW
class CFK_Archive_Manager_DEPRECATED
{
    /**
     * Create full database backup
     */
    public static function createDatabaseBackup(string $year): array
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $archiveDir = __DIR__ . '/../archives/' . $year;

            // Create archive directory if it doesn't exist
            if (!file_exists($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }

            $backupFile = $archiveDir . '/database_backup_' . $timestamp . '.sql';

            // Get database connection details from environment/config
            $dbHost = getenv('DB_HOST') ?: config('db_host', 'localhost');
            $dbName = getenv('DB_NAME') ?: config('db_name', '');
            $dbUser = getenv('DB_USER') ?: config('db_user', '');
            $dbPass = getenv('DB_PASSWORD') ?: config('db_password', '');

            // Build mysqldump command
            $command = sprintf(
                'mysqldump -h %s -u %s -p%s %s > %s 2>&1',
                escapeshellarg($dbHost),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($backupFile)
            );

            // Execute backup
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
                return [
                    'success' => true,
                    'message' => 'Database backup created successfully',
                    'file' => $backupFile,
                    'size' => filesize($backupFile)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Database backup failed: ' . implode("\n", $output)
                ];
            }
        } catch (Exception $e) {
            error_log('Database backup failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Backup error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Export all data to CSV files
     */
    public static function exportAllDataToCSV(string $year): array
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $archiveDir = __DIR__ . '/../archives/' . $year;

            if (!file_exists($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }

            $exports = [];

            // Export children
            $childrenFile = $archiveDir . '/children_' . $timestamp . '.csv';
            $children = Database::fetchAll("
                SELECT c.*, f.family_number,
                       CONCAT(f.family_number, c.child_letter) as display_id
                FROM children c
                JOIN families f ON c.family_id = f.id
                ORDER BY f.family_number, c.child_letter
            ");
            self::writeCSV($childrenFile, $children);
            $exports['children'] = $childrenFile;

            // Export families
            $familiesFile = $archiveDir . '/families_' . $timestamp . '.csv';
            $families = Database::fetchAll("SELECT * FROM families ORDER BY family_number");
            self::writeCSV($familiesFile, $families);
            $exports['families'] = $familiesFile;

            // Export sponsorships
            $sponsorshipsFile = $archiveDir . '/sponsorships_' . $timestamp . '.csv';
            $sponsorships = Database::fetchAll("
                SELECT s.*,
                       CONCAT(f.family_number, c.child_letter) as child_display_id,
                       CONCAT(f.family_number, c.child_letter) as child_name
                FROM sponsorships s
                JOIN children c ON s.child_id = c.id
                JOIN families f ON c.family_id = f.id
                ORDER BY s.request_date DESC
            ");
            self::writeCSV($sponsorshipsFile, $sponsorships);
            $exports['sponsorships'] = $sponsorshipsFile;

            // Export email log
            $emailLogFile = $archiveDir . '/email_log_' . $timestamp . '.csv';
            $emailLog = Database::fetchAll("SELECT * FROM email_log ORDER BY sent_date DESC");
            self::writeCSV($emailLogFile, $emailLog);
            $exports['email_log'] = $emailLogFile;

            return [
                'success' => true,
                'message' => 'All data exported successfully',
                'files' => $exports
            ];
        } catch (Exception $e) {
            error_log('CSV export failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Export error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Write data to CSV file
     */
    private static function writeCSV(string $filename, array $data): void
    {
        $handle = fopen($filename, 'w');

        if ($data !== []) {
            // Write headers
            fputcsv($handle, array_keys($data[0]));

            // Write data
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
        }

        fclose($handle);
    }

    /**
     * Create archive summary document
     */
    public static function createArchiveSummary(string $year): array
    {
        try {
            $archiveDir = __DIR__ . '/../archives/' . $year;
            $summaryFile = $archiveDir . '/ARCHIVE_SUMMARY.txt';

            // Get statistics
            $stats = [
                'children' => Database::fetchRow("SELECT COUNT(*) as count FROM children")['count'],
                'families' => Database::fetchRow("SELECT COUNT(*) as count FROM families")['count'],
                'sponsorships' => Database::fetchRow("SELECT COUNT(*) as count FROM sponsorships")['count'],
                'email_log' => Database::fetchRow("SELECT COUNT(*) as count FROM email_log")['count']
            ];

            $sponsorshipStats = Database::fetchAll("
                SELECT status, COUNT(*) as count
                FROM sponsorships
                GROUP BY status
            ");

            $content = "===================================\n";
            $content .= "CHRISTMAS FOR KIDS - YEAR-END ARCHIVE\n";
            $content .= "Year: $year\n";
            $content .= "Archive Date: " . date('Y-m-d H:i:s') . "\n";
            $content .= "===================================\n\n";

            $content .= "STATISTICS SUMMARY:\n";
            $content .= "-------------------\n";
            $content .= "Total Children: {$stats['children']}\n";
            $content .= "Total Families: {$stats['families']}\n";
            $content .= "Total Sponsorships: {$stats['sponsorships']}\n";
            $content .= "Email Log Entries: {$stats['email_log']}\n\n";

            $content .= "SPONSORSHIP BREAKDOWN:\n";
            $content .= "----------------------\n";
            foreach ($sponsorshipStats as $stat) {
                $content .= ucfirst((string) $stat['status']) . ": {$stat['count']}\n";
            }

            $content .= "\nARCHIVE CONTENTS:\n";
            $content .= "-----------------\n";
            $content .= "- database_backup_*.sql (Full database backup)\n";
            $content .= "- children_*.csv (All children data)\n";
            $content .= "- families_*.csv (All families data)\n";
            $content .= "- sponsorships_*.csv (All sponsorships data)\n";
            $content .= "- email_log_*.csv (All email logs)\n\n";

            $content .= "RESTORATION:\n";
            $content .= "------------\n";
            $content .= "To restore this data:\n";
            $content .= "1. Import database_backup_*.sql into MySQL\n";
            $content .= "2. Or use CSV files for selective restoration\n\n";

            $content .= "NOTES:\n";
            $content .= "------\n";
            $content .= "This archive was created automatically by the CFK system.\n";
            $content .= "Keep this archive in a safe location for record-keeping.\n";
            $content .= "Contact system administrator for restoration assistance.\n";

            file_put_contents($summaryFile, $content);

            return [
                'success' => true,
                'message' => 'Archive summary created',
                'file' => $summaryFile
            ];
        } catch (Exception $e) {
            error_log('Archive summary creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Summary creation error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clear all seasonal data (DESTRUCTIVE - use with caution!)
     */
    public static function clearSeasonalData(): array
    {
        try {
            Database::getConnection()->beginTransaction();

            // Count records before deletion
            $beforeCounts = [
                'children' => Database::fetchRow("SELECT COUNT(*) as count FROM children")['count'],
                'families' => Database::fetchRow("SELECT COUNT(*) as count FROM families")['count'],
                'sponsorships' => Database::fetchRow("SELECT COUNT(*) as count FROM sponsorships")['count'],
                'email_log' => Database::fetchRow("SELECT COUNT(*) as count FROM email_log")['count']
            ];

            // Delete in correct order (respecting foreign keys)
            Database::execute("DELETE FROM email_log");
            Database::execute("DELETE FROM sponsorships");
            Database::execute("DELETE FROM children");
            Database::execute("DELETE FROM families");

            // Reset auto-increment counters
            Database::execute("ALTER TABLE families AUTO_INCREMENT = 1");
            Database::execute("ALTER TABLE children AUTO_INCREMENT = 1");
            Database::execute("ALTER TABLE sponsorships AUTO_INCREMENT = 1");
            Database::execute("ALTER TABLE email_log AUTO_INCREMENT = 1");

            Database::getConnection()->commit();

            return [
                'success' => true,
                'message' => 'Seasonal data cleared successfully',
                'deleted' => $beforeCounts
            ];
        } catch (Exception $e) {
            Database::getConnection()->rollback();
            error_log('Data clearing failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Data clearing error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Perform complete year-end reset with archiving
     */
    public static function performYearEndReset(string $year, string $confirmationCode): array
    {
        // Validate confirmation code
        $expectedCode = 'RESET ' . $year;
        if ($confirmationCode !== $expectedCode) {
            return [
                'success' => false,
                'message' => 'Invalid confirmation code. Expected: ' . $expectedCode
            ];
        }

        $results = [];
        $errors = [];

        // Step 1: Create database backup
        $backupResult = self::createDatabaseBackup($year);
        $results['backup'] = $backupResult;
        if (!$backupResult['success']) {
            $errors[] = 'Database backup failed';
            return [
                'success' => false,
                'message' => 'Year-end reset aborted: Database backup failed',
                'results' => $results,
                'errors' => $errors
            ];
        }

        // Step 2: Export all data to CSV
        $exportResult = self::exportAllDataToCSV($year);
        $results['export'] = $exportResult;
        if (!$exportResult['success']) {
            $errors[] = 'CSV export failed';
            return [
                'success' => false,
                'message' => 'Year-end reset aborted: CSV export failed',
                'results' => $results,
                'errors' => $errors
            ];
        }

        // Step 3: Create archive summary
        $summaryResult = self::createArchiveSummary($year);
        $results['summary'] = $summaryResult;

        // Step 4: Clear seasonal data
        $clearResult = self::clearSeasonalData();
        $results['clear'] = $clearResult;
        if (!$clearResult['success']) {
            $errors[] = 'Data clearing failed - Data may be partially deleted!';
            return [
                'success' => false,
                'message' => 'WARNING: Data clearing failed. Check database state!',
                'results' => $results,
                'errors' => $errors
            ];
        }

        return [
            'success' => true,
            'message' => 'Year-end reset completed successfully',
            'results' => $results,
            'deleted_counts' => $clearResult['deleted']
        ];
    }

    /**
     * Get list of available archives
     */
    public static function getAvailableArchives(): array
    {
        $archivesDir = __DIR__ . '/../archives';

        if (!file_exists($archivesDir)) {
            return [];
        }

        $archives = [];
        $dirs = glob($archivesDir . '/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $year = basename($dir);
            $summaryFile = $dir . '/ARCHIVE_SUMMARY.txt';

            $archives[] = [
                'year' => $year,
                'path' => $dir,
                'has_summary' => file_exists($summaryFile),
                'file_count' => count(glob($dir . '/*')),
                'size' => self::getDirectorySize($dir)
            ];
        }

        // Sort by year descending
        usort($archives, fn($a, $b): int => strcmp($b['year'], $a['year']));

        return $archives;
    }

    /**
     * Get directory size recursively
     */
    private static function getDirectorySize(string $dir): int
    {
        try {
            $size = 0;
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                $size += $file->getSize();
            }

            return $size;
        } catch (Exception $e) {
            error_log("Failed to get directory size for {$dir}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Format bytes to human readable
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
