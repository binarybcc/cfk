<?php
declare(strict_types=1);

/**
 * Email Configuration Test Script
 * Tests SMTP connection and email delivery
 *
 * Usage: php test_email.php
 */

define('CFK_APP', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/email_manager.php';

echo "=================================================\n";
echo "  Christmas for Kids - Email Configuration Test\n";
echo "=================================================\n\n";

// Display current configuration
echo "Current Configuration:\n";
echo "---------------------\n";
echo "Environment: " . (config('debug') ? 'Development' : 'Production') . "\n";
echo "Use SMTP: " . (config('email_use_smtp') ? 'Yes' : 'No (using sendmail)') . "\n";

if (config('email_use_smtp')) {
    echo "SMTP Host: " . config('smtp_host') . "\n";
    echo "SMTP Port: " . config('smtp_port') . "\n";
    echo "SMTP User: " . (config('smtp_username') ?: '[NOT SET]') . "\n";
    echo "SMTP Pass: " . (config('smtp_password') ? '[SET]' : '[NOT SET]') . "\n";
    echo "Encryption: " . config('smtp_encryption') . "\n";
}

echo "\nFrom Email: " . config('from_email') . "\n";
echo "From Name: " . config('from_name') . "\n";
echo "Admin Email: " . config('admin_email') . "\n\n";

// Check for missing configuration
$issues = [];
if (config('email_use_smtp')) {
    if (!config('smtp_username')) {
        $issues[] = "SMTP username not configured (set SMTP_USERNAME env var)";
    }
    if (!config('smtp_password')) {
        $issues[] = "SMTP password not configured (set SMTP_PASSWORD env var)";
    }
}

if (!empty($issues)) {
    echo "⚠️  Configuration Issues Found:\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
    echo "\n";
}

// Test email configuration
echo "Testing email configuration...\n";
echo "------------------------------\n";

$result = CFK_Email_Manager::testEmailConfig();

if ($result['success']) {
    echo "✅ SUCCESS: {$result['message']}\n\n";
    echo "Check your admin email inbox: " . config('admin_email') . "\n";
    echo "\nIf you don't receive the test email:\n";
    echo "1. Check spam/junk folder\n";
    echo "2. Verify SMTP credentials are correct\n";
    echo "3. Check server logs for errors\n";
} else {
    echo "❌ FAILED: {$result['message']}\n\n";
    echo "Troubleshooting:\n";
    echo "1. Verify SMTP credentials with Nexcess support\n";
    echo "2. Check if port 587 is open on your server\n";
    echo "3. Try alternative port 2525 if 587 is blocked\n";
    echo "4. Ensure SPF record includes MailChannels\n";
}

echo "\n=================================================\n";
