<?php
declare(strict_types=1);

/**
 * Email Queue Test Script
 * Tests the email queue system by adding test emails
 *
 * Usage: php test_email_queue.php
 */

define('CFK_APP', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/email_queue.php';

echo "=================================================\n";
echo "  Christmas for Kids - Email Queue Test\n";
echo "=================================================\n\n";

// Get queue stats before
$beforeStats = CFK_Email_Queue::getStats();
echo "Queue Status Before:\n";
echo "-------------------\n";
echo "Queued: {$beforeStats['queued']}\n";
echo "Processing: {$beforeStats['processing']}\n";
echo "Sent: {$beforeStats['sent']}\n";
echo "Failed: {$beforeStats['failed']}\n";
echo "Total: {$beforeStats['total']}\n\n";

// Queue a test email
echo "Queueing test email...\n";

$testEmail = config('admin_email');
$queueId = CFK_Email_Queue::queue(
    $testEmail,
    'Test Email from CFK Queue System',
    '<html>
        <body style="font-family: Arial, sans-serif;">
            <h1 style="color: #c41e3a;">Christmas for Kids</h1>
            <h2>Email Queue Test</h2>
            <p>This is a test email from the queue system.</p>
            <p><strong>Queue ID:</strong> ' . time() . '</p>
            <p><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <hr>
            <p style="color: #666; font-size: 12px;">
                This is an automated test email from the Christmas for Kids sponsorship system.
            </p>
        </body>
    </html>',
    [
        'priority' => 'high',
        'metadata' => [
            'test' => true,
            'source' => 'test_email_queue.php'
        ]
    ]
);

if ($queueId) {
    echo "✅ Email queued successfully!\n";
    echo "Queue ID: {$queueId}\n\n";
} else {
    echo "❌ Failed to queue email\n\n";
    exit(1);
}

// Get queue stats after
$afterStats = CFK_Email_Queue::getStats();
echo "Queue Status After:\n";
echo "------------------\n";
echo "Queued: {$afterStats['queued']}\n";
echo "Processing: {$afterStats['processing']}\n";
echo "Sent: {$afterStats['sent']}\n";
echo "Failed: {$afterStats['failed']}\n";
echo "Total: {$afterStats['total']}\n\n";

echo "Next Steps:\n";
echo "-----------\n";
echo "1. Run the queue processor manually:\n";
echo "   php cron/process_email_queue.php\n\n";
echo "2. Or wait for the cron job to process it automatically\n\n";
echo "3. Check the email_queue table to see the status:\n";
echo "   SELECT * FROM email_queue WHERE id = {$queueId};\n\n";

echo "=================================================\n";
