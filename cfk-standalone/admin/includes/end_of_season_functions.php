<?php

/**
 * End of Season Functions
 * Web-callable functions for end-of-season operations
 */

declare(strict_types=1);

if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

use CFK\Database\Connection as Database;

/**
 * Deploy end-of-season pages (cuny pages)
 *
 * @return array{success: bool, message: string} Result with success status and message
 */
function deployEndOfSeasonPages(): array
{
    $basePath = __DIR__ . '/../../';

    try {
        // Backup current pages
        $backupTimestamp = date('Y-m-d-His');
        copy($basePath . 'pages/home.php', $basePath . "pages/home-backup-{$backupTimestamp}.php");
        copy($basePath . 'pages/my_sponsorships.php', $basePath . "pages/my_sponsorships-backup-{$backupTimestamp}.php");
        copy($basePath . 'includes/header.php', $basePath . "includes/header-backup-{$backupTimestamp}.php");
        copy($basePath . 'includes/footer.php', $basePath . "includes/footer-backup-{$backupTimestamp}.php");

        // Deploy cuny pages
        copy($basePath . 'pages/cuny-home.php', $basePath . 'pages/home.php');
        copy($basePath . 'pages/cuny-my_sponsorships.php', $basePath . 'pages/my_sponsorships.php');
        copy($basePath . 'includes/cuny-header.php', $basePath . 'includes/header.php');
        copy($basePath . 'includes/cuny-footer.php', $basePath . 'includes/footer.php');

        return [
            'success' => true,
            'message' => "End-of-season pages deployed successfully! Backups saved with timestamp {$backupTimestamp}."
        ];
    } catch (Exception $e) {
        error_log("Failed to deploy end-of-season pages: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to deploy pages: ' . $e->getMessage()
        ];
    }
}

/**
 * Restore active-season pages (old pages)
 *
 * @return array{success: bool, message: string} Result with success status and message
 */
function restoreActiveSeasonPages(): array
{
    $basePath = __DIR__ . '/../../';

    try {
        // Backup current pages before restoring
        $backupTimestamp = date('Y-m-d-His');
        copy($basePath . 'pages/home.php', $basePath . "pages/home-backup-{$backupTimestamp}.php");
        copy($basePath . 'pages/my_sponsorships.php', $basePath . "pages/my_sponsorships-backup-{$backupTimestamp}.php");
        copy($basePath . 'includes/header.php', $basePath . "includes/header-backup-{$backupTimestamp}.php");

        // Restore from old- pages
        copy($basePath . 'pages/old-home.php', $basePath . 'pages/home.php');
        copy($basePath . 'pages/old-my_sponsorships.php', $basePath . 'pages/my_sponsorships.php');
        copy($basePath . 'includes/old-header.php', $basePath . 'includes/header.php');
        // Note: footer doesn't need restoring as it's not significantly different

        return [
            'success' => true,
            'message' => "Active-season pages restored successfully! Previous version backed up with timestamp {$backupTimestamp}."
        ];
    } catch (Exception $e) {
        error_log("Failed to restore active-season pages: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to restore pages: ' . $e->getMessage()
        ];
    }
}

/**
 * Sponsor remaining unsponsored children
 *
 * @param bool $dryRun If true, only preview without making changes
 * @return array{success: bool, message: string, count: int, children?: array<int, array<string, mixed>>, dry_run?: bool, success_count?: int, error_count?: int, errors?: array<int, string>} Result with success status, message, and counts
 */
function sponsorRemainingChildren(bool $dryRun = false): array
{
    $CFK_SPONSOR = [
        'name' => 'C-F-K Auto-Sponsor',
        'email' => 'end-of-season@christmasforkids.org',
        'phone' => '',
        'address' => '',
        'city' => '',
        'state' => '',
        'zip' => ''
    ];

    try {
        // Find unsponsored children
        $unsponsored = Database::fetchAll(
            "SELECT c.id, c.family_id, CONCAT(f.family_number, c.child_letter) as display_id,
                    c.age_months, c.gender, c.grade,
                    f.family_number
             FROM children c
             JOIN families f ON c.family_id = f.id
             WHERE c.id NOT IN (
                 SELECT child_id FROM sponsorships
                 WHERE status IN ('confirmed', 'logged')
             )
             ORDER BY f.family_number, c.child_letter"
        );

        $count = count($unsponsored);

        if ($count === 0) {
            return [
                'success' => true,
                'message' => 'All children are already sponsored!',
                'count' => 0,
                'children' => []
            ];
        }

        if ($dryRun) {
            return [
                'success' => true,
                'message' => "Preview: {$count} children would be auto-sponsored",
                'count' => $count,
                'children' => $unsponsored,
                'dry_run' => true
            ];
        }

        // Execute - create sponsorships
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($unsponsored as $child) {
            try {
                // Create sponsorship record
                $result = Database::execute(
                    "INSERT INTO sponsorships (
                        child_id, sponsor_name, sponsor_email, sponsor_phone,
                        sponsor_address, confirmation_date, status, notes
                    ) VALUES (?, ?, ?, ?, ?, NOW(), 'confirmed', ?)",
                    [
                        $child['id'],
                        $CFK_SPONSOR['name'],
                        $CFK_SPONSOR['email'],
                        $CFK_SPONSOR['phone'],
                        $CFK_SPONSOR['address'],
                        'Auto-sponsored by CFK - End of season unsponsored child'
                    ]
                );

                if ($result) {
                    // Update child status to 'sponsored'
                    Database::execute(
                        "UPDATE children SET status = 'sponsored' WHERE id = ?",
                        [$child['id']]
                    );
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = "Failed to sponsor child {$child['display_id']}";
                }
            } catch (Exception $e) {
                $errorCount++;
                $errors[] = "Error sponsoring child {$child['display_id']}: " . $e->getMessage();
            }
        }

        return [
            'success' => $errorCount === 0,
            'message' => "Successfully sponsored {$successCount} of {$count} children.",
            'count' => $count,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ];
    } catch (Exception $e) {
        error_log("Failed to sponsor remaining children: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed: ' . $e->getMessage(),
            'count' => 0
        ];
    }
}

/**
 * Rollback auto-sponsored children
 *
 * @param bool $dryRun If true, only preview without making changes
 * @return array{success: bool, message: string, count: int, sponsorships?: array<int, array<string, mixed>>, dry_run?: bool, success_count?: int, error_count?: int} Result with success status, message, and counts
 */
function rollbackAutoSponsorships(bool $dryRun = false): array
{
    $CFK_EMAIL = 'end-of-season@christmasforkids.org';

    try {
        // Find auto-sponsorships
        $autoSponsorships = Database::fetchAll(
            "SELECT s.id, s.child_id, s.sponsor_name, s.sponsor_email,
                    CONCAT(f.family_number, c.child_letter) as display_id,
                    s.confirmation_date
             FROM sponsorships s
             JOIN children c ON s.child_id = c.id
             JOIN families f ON c.family_id = f.id
             WHERE s.sponsor_email = ?
             AND s.status = 'confirmed'
             ORDER BY s.confirmation_date DESC",
            [$CFK_EMAIL]
        );

        $count = count($autoSponsorships);

        if ($count === 0) {
            return [
                'success' => true,
                'message' => 'No auto-sponsorships found to remove.',
                'count' => 0
            ];
        }

        if ($dryRun) {
            return [
                'success' => true,
                'message' => "Preview: {$count} auto-sponsorships would be removed",
                'count' => $count,
                'sponsorships' => $autoSponsorships,
                'dry_run' => true
            ];
        }

        // Execute - remove sponsorships
        $successCount = 0;
        $errorCount = 0;

        foreach ($autoSponsorships as $sponsorship) {
            try {
                $result = Database::execute(
                    "DELETE FROM sponsorships WHERE id = ? AND sponsor_email = ?",
                    [$sponsorship['id'], $CFK_EMAIL]
                );

                if ($result) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } catch (Exception $e) {
                $errorCount++;
            }
        }

        return [
            'success' => $errorCount === 0,
            'message' => "Successfully removed {$successCount} of {$count} auto-sponsorships.",
            'count' => $count,
            'success_count' => $successCount,
            'error_count' => $errorCount
        ];
    } catch (Exception $e) {
        error_log("Failed to rollback auto-sponsorships: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed: ' . $e->getMessage(),
            'count' => 0
        ];
    }
}
