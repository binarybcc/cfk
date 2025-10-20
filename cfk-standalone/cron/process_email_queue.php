<?php
declare(strict_types=1);

/**
 * Process Email Queue Cron Job
 * Run this every 5-15 minutes via cron
 *
 * Crontab entry example (every 5 minutes):
 * [minute] [hour] [day] [month] [weekday] [command]
 * 5,10,15,20,25,30,35,40,45,50,55,0 * * * * /usr/bin/php /path/to/cfk-standalone/cron/process_email_queue.php >> /var/log/cfk_email_queue.log 2>&1
 */

// Security constant
define('CFK_APP', true);

// Load dependencies
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/email_queue.php';

echo "[" . date('Y-m-d H:i:s') . "] Email Queue Processing Started\n";

try {
    // Process up to 50 emails per run
    $stats = CFK_Email_Queue::processQueue(50);

    echo "Processed: {$stats['processed']}\n";
    echo "Sent: {$stats['sent']}\n";
    echo "Failed: {$stats['failed']}\n";

    if (!empty($stats['errors'])) {
        echo "Errors:\n";
        foreach ($stats['errors'] as $error) {
            echo "  - $error\n";
        }
    }

    // Get current queue stats
    $queueStats = CFK_Email_Queue::getStats();
    echo "\nQueue Status:\n";
    echo "  Queued: {$queueStats['queued']}\n";
    echo "  Processing: {$queueStats['processing']}\n";
    echo "  Sent: {$queueStats['sent']}\n";
    echo "  Failed: {$queueStats['failed']}\n";
    echo "  Total: {$queueStats['total']}\n";

    echo "[" . date('Y-m-d H:i:s') . "] Email Queue Processing Completed\n\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
