<?php

declare(strict_types=1);

namespace CFK\Archive;

use CFK\Database\Connection;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Archive Manager - Year-End Data Archiving and Reset
 *
 * Handles safe archiving and clearing of data for new seasons
 *
 * @package CFK\Archive
 */
class Manager
{
    /**
     * Create full database backup
     *
     * @param string $year Year for archiving (e.g., "2024")
     *
     * @return (bool|int|string)[] Result with success status and details
     *
     * @psalm-return array{success: bool, message: string, file?: string, size?: false|int}
     */
    public static function createDatabaseBackup(string $year): array
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $archiveDir = __DIR__ . '/../../archives/' . $year;

            // Create archive directory if it doesn't exist
            if (! file_exists($archiveDir)) {
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
                    'size' => filesize($backupFile),
                ];
            }

            return [
                'success' => false,
                'message' => 'Database backup failed: ' . implode("\n", $output),
            ];
        } catch (Exception $e) {
            error_log('Database backup failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Backup error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Export all data to CSV files
     *
     * @param string $year Year for archiving
     *
     * @return (bool|string|string[])[] Result with success status and file list
     *
     * @psalm-return array{success: bool, message: string, files?: array{children: string, families: string, sponsorships: string, email_log: string}}
     */
    public static function exportAllDataToCSV(string $year): array
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $archiveDir = __DIR__ . '/../../archives/' . $year;

            if (! file_exists($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }

            $exports = [];

            // Export children
            $childrenFile = $archiveDir . '/children_' . $timestamp . '.csv';
            $children = Connection::fetchAll("
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
            $families = Connection::fetchAll("SELECT * FROM families ORDER BY family_number");
            self::writeCSV($familiesFile, $families);
            $exports['families'] = $familiesFile;

            // Export sponsorships
            $sponsorshipsFile = $archiveDir . '/sponsorships_' . $timestamp . '.csv';
            $sponsorships = Connection::fetchAll("
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
            $emailLog = Connection::fetchAll("SELECT * FROM email_log ORDER BY sent_date DESC");
            self::writeCSV($emailLogFile, $emailLog);
            $exports['email_log'] = $emailLogFile;

            return [
                'success' => true,
                'message' => 'All data exported successfully',
                'files' => $exports,
            ];
        } catch (Exception $e) {
            error_log('CSV export failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Export error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Write data to CSV file
     *
     * @param string $filename Path to CSV file
     * @param array<int, array<string, mixed>> $data Data to write
     */
    private static function writeCSV(string $filename, array $data): void
    {
        $handle = fopen($filename, 'w');

        if ($handle === false) {
            throw new RuntimeException("Failed to open file for writing: {$filename}");
        }

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
     *
     * @param string $year Year for archiving
     *
     * @return (bool|string)[] Result with success status and file path
     *
     * @psalm-return array{success: bool, message: string, file?: string}
     */
    public static function createArchiveSummary(string $year): array
    {
        try {
            $archiveDir = __DIR__ . '/../../archives/' . $year;
            $summaryFile = $archiveDir . '/ARCHIVE_SUMMARY.txt';

            // Get statistics
            $stats = [
                'children' => Connection::fetchRow("SELECT COUNT(*) as count FROM children")['count'] ?? 0,
                'families' => Connection::fetchRow("SELECT COUNT(*) as count FROM families")['count'] ?? 0,
                'sponsorships' => Connection::fetchRow("SELECT COUNT(*) as count FROM sponsorships")['count'] ?? 0,
                'email_log' => Connection::fetchRow("SELECT COUNT(*) as count FROM email_log")['count'] ?? 0,
            ];

            $sponsorshipStats = Connection::fetchAll("
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
                'file' => $summaryFile,
            ];
        } catch (Exception $e) {
            error_log('Archive summary creation failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Summary creation error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Clear all seasonal data (DESTRUCTIVE - use with caution!)
     *
     * @return ((int|mixed)[]|bool|string)[] Result with success status and deleted counts
     *
     * @psalm-return array{success: bool, message: string, deleted?: array{children: 0|mixed, families: 0|mixed, sponsorships: 0|mixed, email_log: 0|mixed}}
     */
    public static function clearSeasonalData(): array
    {
        try {
            Connection::beginTransaction();

            // Count records before deletion
            $beforeCounts = [
                'children' => Connection::fetchRow("SELECT COUNT(*) as count FROM children")['count'] ?? 0,
                'families' => Connection::fetchRow("SELECT COUNT(*) as count FROM families")['count'] ?? 0,
                'sponsorships' => Connection::fetchRow("SELECT COUNT(*) as count FROM sponsorships")['count'] ?? 0,
                'email_log' => Connection::fetchRow("SELECT COUNT(*) as count FROM email_log")['count'] ?? 0,
            ];

            // Delete in correct order (respecting foreign keys)
            Connection::execute("DELETE FROM email_log");
            Connection::execute("DELETE FROM sponsorships");
            Connection::execute("DELETE FROM children");
            Connection::execute("DELETE FROM families");

            // Reset auto-increment counters
            Connection::execute("ALTER TABLE families AUTO_INCREMENT = 1");
            Connection::execute("ALTER TABLE children AUTO_INCREMENT = 1");
            Connection::execute("ALTER TABLE sponsorships AUTO_INCREMENT = 1");
            Connection::execute("ALTER TABLE email_log AUTO_INCREMENT = 1");

            Connection::commit();

            return [
                'success' => true,
                'message' => 'Seasonal data cleared successfully',
                'deleted' => $beforeCounts,
            ];
        } catch (Exception $e) {
            Connection::rollback();
            error_log('Data clearing failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Data clearing error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Perform complete year-end reset with archiving
     *
     * @param string $year Year for archiving
     * @param string $confirmationCode Security confirmation code
     *
     * @return ((array|string)[]|bool|mixed|string)[] Result with success status and detailed results
     *
     * @psalm-return array{success: bool, message: string, results?: array{backup: array<string, mixed>, export?: array<string, mixed>, summary?: array<string, mixed>, clear?: array<string, mixed>}, deleted_counts?: mixed, errors?: list{'CSV export failed'|'Data clearing failed - Data may be partially deleted!'|'Database backup failed'}}
     */
    public static function performYearEndReset(string $year, string $confirmationCode): array
    {
        // Validate confirmation code
        $expectedCode = 'RESET ' . $year;
        if ($confirmationCode !== $expectedCode) {
            return [
                'success' => false,
                'message' => 'Invalid confirmation code. Expected: ' . $expectedCode,
            ];
        }

        $results = [];
        $errors = [];

        // Step 1: Create database backup
        $backupResult = self::createDatabaseBackup($year);
        $results['backup'] = $backupResult;
        if (! $backupResult['success']) {
            $errors[] = 'Database backup failed';

            return [
                'success' => false,
                'message' => 'Year-end reset aborted: Database backup failed',
                'results' => $results,
                'errors' => $errors,
            ];
        }

        // Step 2: Export all data to CSV
        $exportResult = self::exportAllDataToCSV($year);
        $results['export'] = $exportResult;
        if (! $exportResult['success']) {
            $errors[] = 'CSV export failed';

            return [
                'success' => false,
                'message' => 'Year-end reset aborted: CSV export failed',
                'results' => $results,
                'errors' => $errors,
            ];
        }

        // Step 3: Create archive summary
        $summaryResult = self::createArchiveSummary($year);
        $results['summary'] = $summaryResult;

        // Step 4: Clear seasonal data
        $clearResult = self::clearSeasonalData();
        $results['clear'] = $clearResult;
        if (! $clearResult['success']) {
            $errors[] = 'Data clearing failed - Data may be partially deleted!';

            return [
                'success' => false,
                'message' => 'WARNING: Data clearing failed. Check database state!',
                'results' => $results,
                'errors' => $errors,
            ];
        }

        return [
            'success' => true,
            'message' => 'Year-end reset completed successfully',
            'results' => $results,
            'deleted_counts' => $clearResult['deleted'],
        ];
    }

    /**
     * Get list of available archives (individual archive sets by timestamp)
     *
     * @return (bool|int|string)[][] List of available archives
     *
     * @psalm-return list<array{backup_file: non-empty-string, date: string, file_count: int<0, max>, has_data: bool, has_summary: bool, path: non-empty-string, size: int<min, max>, timestamp: string, year: non-empty-string}>
     */
    public static function getAvailableArchives(): array
    {
        $archivesDir = __DIR__ . '/../../archives';

        if (! file_exists($archivesDir)) {
            return [];
        }

        $archives = [];
        $yearDirs = glob($archivesDir . '/*', GLOB_ONLYDIR);

        if ($yearDirs === false) {
            return [];
        }

        foreach ($yearDirs as $yearDir) {
            $year = basename($yearDir);

            // Find all backup files in this year to identify individual archives
            $backupFiles = glob($yearDir . '/database_backup_*.sql');

            if (! $backupFiles) {
                continue;
            }

            foreach ($backupFiles as $backupFile) {
                // Extract timestamp from backup filename
                if (preg_match('/_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.sql$/', $backupFile, $matches)) {
                    $timestamp = $matches[1];
                    $archiveDate = str_replace('_', ' ', $timestamp);

                    // Find all files for this archive set
                    $archiveFiles = [
                        $backupFile,
                        $yearDir . '/children_' . $timestamp . '.csv',
                        $yearDir . '/families_' . $timestamp . '.csv',
                        $yearDir . '/sponsorships_' . $timestamp . '.csv',
                        $yearDir . '/email_log_' . $timestamp . '.csv',
                    ];

                    // Calculate size and count of this archive set
                    $size = 0;
                    $fileCount = 0;
                    $hasData = false;

                    foreach ($archiveFiles as $file) {
                        if (file_exists($file)) {
                            $size += filesize($file);
                            $fileCount++;
                            // Check if children CSV has data
                            if (str_contains($file, 'children_') && filesize($file) > 100) {
                                $hasData = true;
                            }
                        }
                    }

                    // Add ARCHIVE_SUMMARY.txt if it exists (shared by all archives in year)
                    $summaryFile = $yearDir . '/ARCHIVE_SUMMARY.txt';
                    $hasSummary = file_exists($summaryFile);
                    if ($hasSummary) {
                        $size += filesize($summaryFile);
                        $fileCount++;
                    }

                    $archives[] = [
                        'year' => $year,
                        'timestamp' => $timestamp,
                        'date' => $archiveDate,
                        'path' => $yearDir,
                        'backup_file' => basename($backupFile),
                        'has_summary' => $hasSummary,
                        'has_data' => $hasData,
                        'file_count' => $fileCount,
                        'size' => $size,
                    ];
                }
            }
        }

        // Sort by year and timestamp descending (newest first)
        usort($archives, function ($a, $b) {
            $yearCompare = strcmp((string) $b['year'], (string) $a['year']);
            if ($yearCompare !== 0) {
                return $yearCompare;
            }

            return strcmp((string) $b['timestamp'], (string) $a['timestamp']);
        });

        return $archives;
    }

    /**
     * Get directory size recursively
     *
     * @param string $dir Directory path
     * @return int Size in bytes
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
     *
     * @param int $bytes Size in bytes
     *
     * @return string Formatted size string
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

    /**
     * Restore database from SQL backup file
     *
     * @param string $year Archive year
     * @param string $backupFile Backup filename (not full path)
     * @param bool $debug Enable detailed debug logging
     *
     * @return (scalar|string[])[] Result with success status and details
     *
     * @psalm-return array{success: bool, message: string, debug_log: list{0: string, 1?: string, 2?: string, 3?: string, 4?: string, 5?: string, 6?: string, 7?: string, 8?: string}, duration?: float, file_size?: false|int}
     */
    public static function restoreDatabase(string $year, string $backupFile, bool $debug = true): array
    {
        $debugLog = [];

        try {
            $debugLog[] = "Starting database restore for year: {$year}";
            $archiveDir = __DIR__ . '/../../archives/' . $year;
            $backupPath = $archiveDir . '/' . $backupFile;

            // Validate backup file exists
            if (! file_exists($backupPath)) {
                $debugLog[] = "ERROR: Backup file not found at: {$backupPath}";
                error_log("Archive restore failed: Backup file not found - {$backupPath}");

                return [
                    'success' => false,
                    'message' => 'Backup file not found',
                    'debug_log' => $debugLog,
                ];
            }

            $fileSize = filesize($backupPath);
            $debugLog[] = "Backup file found: " . self::formatBytes($fileSize);

            // Get database credentials
            $dbHost = getenv('DB_HOST') ?: config('db_host', 'localhost');
            $dbName = getenv('DB_NAME') ?: config('db_name', '');
            $dbUser = getenv('DB_USER') ?: config('db_user', '');
            $dbPass = getenv('DB_PASSWORD') ?: config('db_password', '');

            $debugLog[] = "Database: {$dbName}@{$dbHost}";

            // Build mysql restore command
            $command = sprintf(
                'mysql -h %s -u %s -p%s %s < %s 2>&1',
                escapeshellarg($dbHost),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($backupPath)
            );

            $debugLog[] = "Executing restore command...";
            $startTime = microtime(true);

            exec($command, $output, $returnCode);

            $duration = round(microtime(true) - $startTime, 2);
            $debugLog[] = "Restore completed in {$duration} seconds";
            $debugLog[] = "Return code: {$returnCode}";

            if ($returnCode === 0) {
                $debugLog[] = "✅ Database restore successful";
                error_log("Archive restore successful: {$year}/{$backupFile}");

                return [
                    'success' => true,
                    'message' => 'Database restored successfully',
                    'duration' => $duration,
                    'file_size' => $fileSize,
                    'debug_log' => $debugLog,
                ];
            }

            $debugLog[] = "❌ Restore command failed";
            $debugLog[] = "Output: " . implode("\n", $output);
            error_log("Archive restore failed: " . implode(" | ", $output));

            return [
                'success' => false,
                'message' => 'Database restore failed: ' . implode("\n", $output),
                'debug_log' => $debugLog,
            ];
        } catch (Exception $e) {
            $debugLog[] = "EXCEPTION: " . $e->getMessage();
            error_log('Archive restore exception: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Restore error: ' . $e->getMessage(),
                'debug_log' => $debugLog,
            ];
        }
    }

    /**
     * Get restore preview - what data would be restored
     *
     * @param string $year Archive year
     *
     * @return (bool|int|int[]|null|string)[] Preview data
     *
     * @psalm-return array{success: bool, year?: string, summary?: false|null|string, counts?: array{children: int<0, max>, families: int<0, max>, sponsorships: int<0, max>, email_log: int<0, max>}, backup_file?: null|string, backup_size?: false|int, backup_date?: null|string, message?: 'Archive not found'}
     */
    public static function getRestorePreview(string $year): array
    {
        $archiveDir = __DIR__ . '/../../archives/' . $year;

        if (! file_exists($archiveDir)) {
            return [
                'success' => false,
                'message' => 'Archive not found',
            ];
        }

        // Read archive summary
        $summaryFile = $archiveDir . '/ARCHIVE_SUMMARY.txt';
        $summary = file_exists($summaryFile) ? file_get_contents($summaryFile) : null;

        // Find CSV files to get record counts
        // Use the largest children CSV to find the backup with actual data
        $childrenCSVs = glob($archiveDir . '/children_*.csv');
        usort($childrenCSVs, function ($a, $b) {
            return filesize($b) <=> filesize($a);
        });

        $bestChildrenCSV = $childrenCSVs[0] ?? null;

        // Extract timestamp from children CSV to find matching backup
        $csvTimestamp = null;
        if ($bestChildrenCSV && preg_match('/_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.csv$/', $bestChildrenCSV, $matches)) {
            $csvTimestamp = $matches[1];
        }

        $csvFiles = [
            'children' => $bestChildrenCSV,
            'families' => $csvTimestamp ? glob($archiveDir . '/families_*' . $csvTimestamp . '.csv')[0] ?? null : null,
            'sponsorships' => $csvTimestamp ? glob($archiveDir . '/sponsorships_*' . $csvTimestamp . '.csv')[0] ?? null : null,
            'email_log' => $csvTimestamp ? glob($archiveDir . '/email_log_*' . $csvTimestamp . '.csv')[0] ?? null : null,
        ];

        $counts = [];
        foreach ($csvFiles as $type => $file) {
            if ($file && file_exists($file)) {
                // Count lines (minus header)
                $lines = count(file($file));
                $counts[$type] = max(0, $lines - 1);
            } else {
                $counts[$type] = 0;
            }
        }

        // Find matching database backup using the same timestamp
        $latestBackup = null;
        if ($csvTimestamp) {
            $matchingBackup = glob($archiveDir . '/database_backup_' . $csvTimestamp . '.sql')[0] ?? null;
            if ($matchingBackup) {
                $latestBackup = $matchingBackup;
            }
        }

        // Fallback to newest backup if no match found
        if (! $latestBackup) {
            $backupFiles = glob($archiveDir . '/database_backup_*.sql');
            if ($backupFiles) {
                usort($backupFiles, function ($a, $b) {
                    return filemtime($b) <=> filemtime($a);
                });
                $latestBackup = $backupFiles[0];
            }
        }

        return [
            'success' => true,
            'year' => $year,
            'summary' => $summary,
            'counts' => $counts,
            'backup_file' => $latestBackup ? basename($latestBackup) : null,
            'backup_size' => $latestBackup ? filesize($latestBackup) : 0,
            'backup_date' => $latestBackup ? date('Y-m-d H:i:s', filemtime($latestBackup)) : null,
        ];
    }

    /**
     * Perform full archive restoration with confirmation
     *
     * @param string $year Year to restore
     * @param string $confirmationCode Must be "RESTORE [YEAR]"
     * @param bool $debug Enable debug logging
     *
     * @return (array|bool|int|mixed|string)[] Result with success status and details
     *
     * @psalm-return array{success: bool, message: string, debug_log: array, restored_counts?: array{children: 0|mixed, families: 0|mixed, sponsorships: 0|mixed, email_log: 0|mixed}, duration?: 0|mixed}
     */
    public static function performArchiveRestore(string $year, string $confirmationCode, bool $debug = true): array
    {
        $debugLog = [];

        // Validate confirmation code
        $expectedCode = 'RESTORE ' . $year;
        if ($confirmationCode !== $expectedCode) {
            return [
                'success' => false,
                'message' => 'Invalid confirmation code. Expected: ' . $expectedCode,
                'debug_log' => ['Confirmation code mismatch'],
            ];
        }

        $debugLog[] = "=== Starting Archive Restore for Year {$year} ===";
        $debugLog[] = "Timestamp: " . date('Y-m-d H:i:s');

        // Get preview to find backup file
        $preview = self::getRestorePreview($year);
        if (! $preview['success']) {
            $debugLog[] = "ERROR: Archive not found";

            return [
                'success' => false,
                'message' => 'Archive not found for year: ' . $year,
                'debug_log' => $debugLog,
            ];
        }

        $backupFile = $preview['backup_file'];
        if (! $backupFile) {
            $debugLog[] = "ERROR: No backup file found in archive";

            return [
                'success' => false,
                'message' => 'No database backup found in archive',
                'debug_log' => $debugLog,
            ];
        }

        $debugLog[] = "Backup file: {$backupFile}";
        $debugLog[] = "Expected records - Children: {$preview['counts']['children']}, Families: {$preview['counts']['families']}";

        // Perform database restore
        $debugLog[] = "--- Starting Database Restore ---";
        $restoreResult = self::restoreDatabase($year, $backupFile, $debug);

        // Merge debug logs
        if (isset($restoreResult['debug_log'])) {
            $debugLog = array_merge($debugLog, $restoreResult['debug_log']);
        }

        if (! $restoreResult['success']) {
            $debugLog[] = "❌ DATABASE RESTORE FAILED";
            error_log("Archive restoration failed for {$year}: " . $restoreResult['message']);

            return [
                'success' => false,
                'message' => 'Archive restoration failed: ' . $restoreResult['message'],
                'debug_log' => $debugLog,
            ];
        }

        // Verify restoration by counting records
        $debugLog[] = "--- Verifying Restoration ---";

        try {
            $verifyStats = [
                'children' => Connection::fetchRow("SELECT COUNT(*) as count FROM children")['count'] ?? 0,
                'families' => Connection::fetchRow("SELECT COUNT(*) as count FROM families")['count'] ?? 0,
                'sponsorships' => Connection::fetchRow("SELECT COUNT(*) as count FROM sponsorships")['count'] ?? 0,
                'email_log' => Connection::fetchRow("SELECT COUNT(*) as count FROM email_log")['count'] ?? 0,
            ];

            $debugLog[] = "Restored records:";
            $debugLog[] = "  - Children: {$verifyStats['children']}";
            $debugLog[] = "  - Families: {$verifyStats['families']}";
            $debugLog[] = "  - Sponsorships: {$verifyStats['sponsorships']}";
            $debugLog[] = "  - Email Logs: {$verifyStats['email_log']}";

            $debugLog[] = "=== Archive Restore Completed Successfully ===";

            error_log("Archive restoration completed for {$year} - Children: {$verifyStats['children']}, Families: {$verifyStats['families']}");

            return [
                'success' => true,
                'message' => 'Archive restored successfully',
                'restored_counts' => $verifyStats,
                'duration' => $restoreResult['duration'] ?? 0,
                'debug_log' => $debugLog,
            ];
        } catch (Exception $e) {
            $debugLog[] = "ERROR during verification: " . $e->getMessage();

            return [
                'success' => false,
                'message' => 'Restore completed but verification failed: ' . $e->getMessage(),
                'debug_log' => $debugLog,
            ];
        }
    }

    /**
     * Get list of archives that will be deleted (all but last 2 with data)
     *
     * @return (array[]|float|int|mixed)[] Array with archives to keep and delete
     *
     * @psalm-return array{to_keep: list<array<string, mixed>>, to_delete: list<array<string, mixed>>, delete_count: int<0, max>, total_size: mixed, total_size_mb: float}
     */
    public static function getArchivesForDeletion(): array
    {
        $allArchives = self::getAvailableArchives();

        // Filter to only archives with data
        $archivesWithData = array_filter($allArchives, function ($archive) {
            return $archive['has_data'];
        });

        // Sort by year DESC, then timestamp DESC (newest first)
        usort($archivesWithData, function ($a, $b) {
            if ($a['year'] !== $b['year']) {
                return $b['year'] <=> $a['year'];
            }

            return $b['timestamp'] <=> $a['timestamp'];
        });

        // Keep the 2 most recent, mark rest for deletion
        $toKeep = array_slice($archivesWithData, 0, 2);
        $toDelete = array_slice($archivesWithData, 2);

        // Calculate total size to be deleted
        $totalSize = 0;
        foreach ($toDelete as $archive) {
            $totalSize += $archive['size'];
        }

        return [
            'to_keep' => $toKeep,
            'to_delete' => $toDelete,
            'delete_count' => count($toDelete),
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
        ];
    }

    /**
     * Delete a specific archive by year and timestamp
     *
     * @param string $year Year of archive
     * @param string $timestamp Timestamp of archive (YYYY-MM-DD_HH-MM-SS)
     * @param bool $debug Enable debug logging
     *
     * @return (scalar|string[])[] Result with success status and details
     *
     * @psalm-return array{success: bool, message: string, deleted_files?: list<non-falsy-string>, deleted_size?: int<min, max>, deleted_size_mb?: float, debug_log: list{0?: string,...}, errors?: non-empty-list<non-falsy-string>}
     */
    public static function deleteArchive(string $year, string $timestamp, bool $debug = false): array
    {
        $debugLog = [];
        $archiveDir = __DIR__ . '/../../archives/' . $year;

        if (! is_dir($archiveDir)) {
            return [
                'success' => false,
                'message' => "Archive directory not found for year: {$year}",
                'debug_log' => ['Archive directory does not exist'],
            ];
        }

        // Find all files for this archive set
        $archiveFiles = [
            $archiveDir . '/database_backup_' . $timestamp . '.sql',
            $archiveDir . '/children_' . $timestamp . '.csv',
            $archiveDir . '/families_' . $timestamp . '.csv',
            $archiveDir . '/sponsorships_' . $timestamp . '.csv',
            $archiveDir . '/email_log_' . $timestamp . '.csv',
        ];

        $deletedFiles = [];
        $deletedSize = 0;
        $errors = [];

        foreach ($archiveFiles as $file) {
            if (file_exists($file)) {
                $fileSize = filesize($file);
                if ($debug) {
                    $debugLog[] = "Deleting: " . basename($file) . " (" . round($fileSize / 1024, 2) . " KB)";
                }

                if (unlink($file)) {
                    $deletedFiles[] = basename($file);
                    $deletedSize += $fileSize;
                } else {
                    $errors[] = "Failed to delete: " . basename($file);
                }
            }
        }

        // Check if we should delete the year directory (if empty)
        $remainingFiles = glob($archiveDir . '/*');
        $shouldDeleteDir = false;

        // Only delete if just ARCHIVE_SUMMARY.txt remains (or directory is empty)
        if ($remainingFiles === false || count($remainingFiles) === 0) {
            $shouldDeleteDir = true;
        } elseif (count($remainingFiles) === 1 && basename($remainingFiles[0]) === 'ARCHIVE_SUMMARY.txt') {
            $shouldDeleteDir = true;
            unlink($remainingFiles[0]);
            $debugLog[] = "Deleted: ARCHIVE_SUMMARY.txt";
        }

        if ($shouldDeleteDir) {
            rmdir($archiveDir);
            $debugLog[] = "Deleted empty directory: {$year}";
        }

        if (count($errors) > 0) {
            return [
                'success' => false,
                'message' => 'Some files could not be deleted',
                'deleted_files' => $deletedFiles,
                'deleted_size' => $deletedSize,
                'errors' => $errors,
                'debug_log' => $debugLog,
            ];
        }

        if ($debug) {
            $debugLog[] = "Successfully deleted " . count($deletedFiles) . " files (" . round($deletedSize / 1024 / 1024, 2) . " MB)";
        }

        error_log("Deleted archive: {$year} - {$timestamp} (" . count($deletedFiles) . " files, " . round($deletedSize / 1024 / 1024, 2) . " MB)");

        return [
            'success' => true,
            'message' => 'Archive deleted successfully',
            'deleted_files' => $deletedFiles,
            'deleted_size' => $deletedSize,
            'deleted_size_mb' => round($deletedSize / 1024 / 1024, 2),
            'debug_log' => $debugLog,
        ];
    }

    /**
     * Delete old archives, keeping only the last 2 archives with data
     *
     * @param string $confirmationCode Must be "DELETE OLD ARCHIVES"
     * @param bool $debug Enable debug logging
     *
     * @return (array|scalar)[] Result with success status and details
     *
     * @psalm-return array{success: bool, message: string, deleted_count?: int<0, max>, kept_count?: int<0, max>, total_deleted_mb?: float, errors?: list{0?: string,...}, debug_log: array}
     */
    public static function deleteOldArchives(string $confirmationCode, bool $debug = true): array
    {
        $debugLog = [];

        // Validate confirmation code
        if ($confirmationCode !== 'DELETE OLD ARCHIVES') {
            return [
                'success' => false,
                'message' => 'Invalid confirmation code. Expected: DELETE OLD ARCHIVES',
                'debug_log' => ['Confirmation code mismatch'],
            ];
        }

        $debugLog[] = "=== Starting Old Archives Deletion ===";
        $debugLog[] = "Timestamp: " . date('Y-m-d H:i:s');

        // Get deletion preview
        $deletionInfo = self::getArchivesForDeletion();

        if ($deletionInfo['delete_count'] === 0) {
            $debugLog[] = "No archives to delete (keeping last 2 with data)";

            return [
                'success' => true,
                'message' => 'No archives to delete',
                'deleted_count' => 0,
                'kept_count' => count($deletionInfo['to_keep']),
                'debug_log' => $debugLog,
            ];
        }

        $debugLog[] = "Archives to delete: " . $deletionInfo['delete_count'];
        $debugLog[] = "Total size to free: " . $deletionInfo['total_size_mb'] . " MB";
        $debugLog[] = "Archives to keep: " . count($deletionInfo['to_keep']);

        $deletedCount = 0;
        $totalDeleted = 0;
        $errors = [];

        foreach ($deletionInfo['to_delete'] as $archive) {
            $debugLog[] = "--- Deleting: Year {$archive['year']} - {$archive['date']} ---";

            $result = self::deleteArchive($archive['year'], $archive['timestamp'], $debug);

            if ($result['success']) {
                $deletedCount++;
                $totalDeleted += $result['deleted_size'];
                $debugLog[] = "✅ Deleted successfully (" . $result['deleted_size_mb'] . " MB)";
            } else {
                $errors[] = "Year {$archive['year']} - {$archive['date']}: " . $result['message'];
                $debugLog[] = "❌ Failed: " . $result['message'];
            }

            // Merge individual delete logs
            if (isset($result['debug_log'])) {
                $debugLog = array_merge($debugLog, $result['debug_log']);
            }
        }

        $debugLog[] = "=== Deletion Complete ===";
        $debugLog[] = "Deleted: {$deletedCount} archives";
        $debugLog[] = "Freed: " . round($totalDeleted / 1024 / 1024, 2) . " MB";

        error_log("Deleted {$deletedCount} old archives, freed " . round($totalDeleted / 1024 / 1024, 2) . " MB");

        return [
            'success' => count($errors) === 0,
            'message' => count($errors) === 0
                ? "Successfully deleted {$deletedCount} old archives"
                : "Deleted {$deletedCount} archives with " . count($errors) . " errors",
            'deleted_count' => $deletedCount,
            'kept_count' => count($deletionInfo['to_keep']),
            'total_deleted_mb' => round($totalDeleted / 1024 / 1024, 2),
            'errors' => $errors,
            'debug_log' => $debugLog,
        ];
    }
}
