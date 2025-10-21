<?php

/**
 * DEPRECATED: Moved to src/Backup/Manager.php
 * Class available via class_alias() in config.php
 */

if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}
return;

class CFK_Backup_Manager_DEPRECATED
{
    private const BACKUP_DIR = __DIR__ . '/../backups/';
    private const MAX_BACKUPS = 2;

    /**
     * Create automatic backup before import
     */
    public static function createAutoBackup(string $reason = 'csv_import'): array
    {
        try {
            // Ensure backup directory exists
            if (!file_exists(self::BACKUP_DIR)) {
                mkdir(self::BACKUP_DIR, 0750, true);
            }

            // Generate filename with timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "children_backup_{$timestamp}_{$reason}.csv";
            $filepath = self::BACKUP_DIR . $filename;

            // Export current data
            require_once __DIR__ . '/csv_handler.php';
            $handler = new CFK_CSV_Handler();
            $csvContent = $handler->exportChildren();

            // Save backup file
            $success = file_put_contents($filepath, $csvContent);

            if ($success === false) {
                return [
                    'success' => false,
                    'message' => 'Failed to write backup file'
                ];
            }

            // Clean up old backups (keep only last MAX_BACKUPS)
            self::cleanupOldBackups();

            // Also create a metadata file
            $metadata = [
                'created_at' => $timestamp,
                'reason' => $reason,
                'file' => $filename,
                'children_count' => self::getChildrenCount(),
                'families_count' => self::getFamiliesCount()
            ];

            file_put_contents(
                self::BACKUP_DIR . $filename . '.meta.json',
                json_encode($metadata, JSON_PRETTY_PRINT)
            );

            return [
                'success' => true,
                'message' => 'Backup created successfully',
                'filename' => $filename,
                'filepath' => $filepath,
                'metadata' => $metadata
            ];
        } catch (Exception $e) {
            error_log('Backup creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * List available backups
     */
    public static function listBackups(): array
    {
        if (!file_exists(self::BACKUP_DIR)) {
            return [];
        }

        $files = glob(self::BACKUP_DIR . 'children_backup_*.csv');
        rsort($files); // Most recent first

        $backups = [];
        foreach ($files as $file) {
            $metaFile = $file . '.meta.json';
            $metadata = file_exists($metaFile)
                ? json_decode(file_get_contents($metaFile), true)
                : null;

            $backups[] = [
                'filename' => basename($file),
                'filepath' => $file,
                'size' => filesize($file),
                'created' => filectime($file),
                'metadata' => $metadata
            ];
        }

        return $backups;
    }

    /**
     * Restore from backup
     */
    public static function restoreFromBackup(string $filename, bool $clearExisting = true): array
    {
        $filepath = self::BACKUP_DIR . $filename;

        if (!file_exists($filepath)) {
            return [
                'success' => false,
                'message' => 'Backup file not found'
            ];
        }

        try {
            // Create backup of current state before restoring
            $preRestoreBackup = self::createAutoBackup('pre_restore');

            // Clear existing data if requested
            if ($clearExisting) {
                Database::query('DELETE FROM sponsorships WHERE 1=1');
                Database::query('DELETE FROM children WHERE 1=1');
                Database::query('DELETE FROM families WHERE 1=1');
            }

            // Import from backup file
            require_once __DIR__ . '/csv_handler.php';
            $handler = new CFK_CSV_Handler();
            $result = $handler->importChildren($filepath);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => 'Restore failed: ' . $result['message'],
                    'details' => $result
                ];
            }

            return [
                'success' => true,
                'message' => "Successfully restored {$result['imported']} children from backup",
                'imported' => $result['imported'],
                'pre_restore_backup' => $preRestoreBackup['filename'] ?? null
            ];
        } catch (Exception $e) {
            error_log('Restore failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clean up old backups (keep only last MAX_BACKUPS)
     */
    private static function cleanupOldBackups(): void
    {
        $files = glob(self::BACKUP_DIR . 'children_backup_*.csv');
        rsort($files); // Most recent first

        // Keep only MAX_BACKUPS, delete the rest
        $toDelete = array_slice($files, self::MAX_BACKUPS);

        foreach ($toDelete as $file) {
            @unlink($file);
            @unlink($file . '.meta.json'); // Also delete metadata
        }
    }

    /**
     * Get current children count
     */
    private static function getChildrenCount(): int
    {
        $result = Database::fetchRow('SELECT COUNT(*) as count FROM children');
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get current families count
     */
    private static function getFamiliesCount(): int
    {
        $result = Database::fetchRow('SELECT COUNT(*) as count FROM families');
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Download backup file
     */
    public static function downloadBackup(string $filename): void
    {
        $filepath = self::BACKUP_DIR . $filename;

        if (!file_exists($filepath)) {
            http_response_code(404);
            die('Backup file not found');
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    /**
     * Get backup statistics
     */
    public static function getBackupStats(): array
    {
        $backups = self::listBackups();

        return [
            'total_backups' => count($backups),
            'max_backups' => self::MAX_BACKUPS,
            'most_recent' => $backups[0] ?? null,
            'total_size' => array_sum(array_column($backups, 'size')),
            'backups' => $backups
        ];
    }
}
