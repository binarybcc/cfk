<?php

declare(strict_types=1);

/**
 * Cron Job - Cleanup Expired Sponsorships
 * Run this script hourly to clean up expired pending sponsorships
 */

// Security constant
define('CFK_APP', true);

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
// Sponsorship Manager available via autoloader (src/Sponsorship/Manager.php)

// Log start
error_log('CFK Cleanup: Starting expired sponsorship cleanup');

try {
    // Clean up expired sponsorships
    $cleaned = CFK_Sponsorship_Manager::cleanupExpiredPendingSponsorships();

    if ($cleaned > 0) {
        error_log("CFK Cleanup: Successfully cleaned up $cleaned expired sponsorships");
    } else {
        error_log('CFK Cleanup: No expired sponsorships found');
    }
} catch (Exception $e) {
    error_log('CFK Cleanup Error: ' . $e->getMessage());
}

echo "Cleanup completed. Processed: $cleaned expired sponsorships\n";
