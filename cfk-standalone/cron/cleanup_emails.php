<?php
declare(strict_types=1);

/**
 * Email Cleanup Cron Job
 * Removes old sent/failed emails from queue to keep database clean
 * Run this daily via cron
 *
 * Crontab entry example:
 * 0 2 * * * /usr/bin/php /path/to/cfk-standalone/cron/cleanup_emails.php >> /var/log/cfk_email_cleanup.log 2>&1
 */

// Security constant
define('CFK_APP', true);

// Load dependencies
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/email_queue.php';

echo "[" . date('Y-m-d H:i:s') . "] Email Cleanup Started\n";

try {
    // Delete sent/failed emails older than 30 days
    $deleted = CFK_Email_Queue::cleanup(30);

    echo "Deleted {$deleted} old email records (older than 30 days)\n";

    // Get current queue stats
    $queueStats = CFK_Email_Queue::getStats();
    echo "\nRemaining Queue Status:\n";
    echo "  Queued: {$queueStats['queued']}\n";
    echo "  Processing: {$queueStats['processing']}\n";
    echo "  Sent: {$queueStats['sent']}\n";
    echo "  Failed: {$queueStats['failed']}\n";
    echo "  Total: {$queueStats['total']}\n";

    echo "[" . date('Y-m-d H:i:s') . "] Email Cleanup Completed\n\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
