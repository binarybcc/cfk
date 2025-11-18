<?php
/**
 * Google Workspace SMTP Test Script
 *
 * Tests email delivery via Google SMTP Relay
 * Run this after configuring Google Admin Console
 */

declare(strict_types=1);

// Bootstrap application
define('CFK_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Load email manager
require_once __DIR__ . '/../vendor/autoload.php';

use CFK\Email\Manager as EmailManager;

// Admin authentication check
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Admin access required. Please log in first.');
}

$testResults = [];
$testEmail = $_POST['test_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($testEmail)) {
    // Test 1: SMTP Connection
    $testResults['connection'] = testSMTPConnection();

    // Test 2: Send test email
    $testResults['send'] = sendTestEmail($testEmail);

    // Test 3: Check email queue
    $testResults['queue'] = checkEmailQueue();
}

function testSMTPConnection(): array {
    try {
        $mailer = EmailManager::getMailer();

        return [
            'status' => 'success',
            'message' => 'SMTP connection initialized successfully',
            'config' => [
                'Host' => config('smtp_host'),
                'Port' => config('smtp_port'),
                'Auth' => config('smtp_auth') ? 'Enabled' : 'Disabled (IP-based)',
                'Encryption' => config('smtp_encryption')
            ]
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'SMTP connection failed: ' . $e->getMessage()
        ];
    }
}

function sendTestEmail(string $recipient): array {
    try {
        $mailer = EmailManager::getMailer();

        $mailer->clearAddresses();
        $mailer->addAddress($recipient);
        $mailer->Subject = 'Google Workspace SMTP Test - Christmas for Kids';

        $mailer->Body = '
            <h2>✅ Google Workspace SMTP Test Successful!</h2>
            <p><strong>Date:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Server IP:</strong> 199.189.224.131</p>
            <p><strong>SMTP Host:</strong> ' . config('smtp_host') . '</p>
            <p><strong>Configuration:</strong> IP-based authentication</p>
            <hr>
            <p>If you received this email, your Google Workspace SMTP Relay is configured correctly!</p>
            <p><strong>Next steps:</strong></p>
            <ul>
                <li>Verify DNS records (SPF, DKIM, DMARC)</li>
                <li>Test from production server</li>
                <li>Monitor email delivery rates</li>
            </ul>
            <p style="color: #666; font-size: 12px;">
                Christmas for Kids Sponsorship System<br>
                Powered by Google Workspace
            </p>
        ';

        $success = $mailer->send();

        return [
            'status' => $success ? 'success' : 'error',
            'message' => $success
                ? "Test email sent successfully to {$recipient}"
                : 'Failed to send test email',
            'recipient' => $recipient,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Failed to send email: ' . $e->getMessage()
        ];
    }
}

function checkEmailQueue(): array {
    try {
        $db = CFK\Database\Connection::getInstance();
        $stmt = $db->prepare("
            SELECT status, COUNT(*) as count
            FROM email_queue
            GROUP BY status
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'status' => 'success',
            'queue_status' => $results
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Failed to check queue: ' . $e->getMessage()
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google SMTP Test - Christmas for Kids</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #2c5aa0; margin-top: 0; }
        h2 { color: #333; border-bottom: 2px solid #2c5aa0; padding-bottom: 10px; }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background: #2c5aa0;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #1e3a70;
        }
        .result {
            margin: 20px 0;
            padding: 15px;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .config-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .config-info code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Google Workspace SMTP Test</h1>

        <div class="config-info">
            <strong>Current Configuration:</strong>
            <ul>
                <li>SMTP Host: <code><?= htmlspecialchars(config('smtp_host')) ?></code></li>
                <li>Port: <code><?= config('smtp_port') ?></code></li>
                <li>Authentication: <code><?= config('smtp_auth') ? 'Password' : 'IP-based (199.189.224.131)' ?></code></li>
                <li>Encryption: <code><?= config('smtp_encryption') ?></code></li>
                <li>From Email: <code><?= htmlspecialchars(config('from_email')) ?></code></li>
            </ul>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="test_email">Test Email Address:</label>
                <input
                    type="email"
                    id="test_email"
                    name="test_email"
                    value="<?= htmlspecialchars($testEmail) ?>"
                    placeholder="your.email@example.com"
                    required
                >
                <small style="color: #666;">We'll send a test email to this address</small>
            </div>

            <button type="submit">🚀 Send Test Email</button>
        </form>

        <?php if (!empty($testResults)): ?>
            <h2>Test Results</h2>

            <!-- Connection Test -->
            <?php if (isset($testResults['connection'])): ?>
                <div class="result <?= $testResults['connection']['status'] ?>">
                    <strong>✓ SMTP Connection Test:</strong>
                    <p><?= htmlspecialchars($testResults['connection']['message']) ?></p>
                    <?php if (isset($testResults['connection']['config'])): ?>
                        <pre><?php print_r($testResults['connection']['config']); ?></pre>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Send Test -->
            <?php if (isset($testResults['send'])): ?>
                <div class="result <?= $testResults['send']['status'] ?>">
                    <strong>✉️ Email Send Test:</strong>
                    <p><?= htmlspecialchars($testResults['send']['message']) ?></p>
                    <?php if ($testResults['send']['status'] === 'success'): ?>
                        <p><small>Check your inbox at <strong><?= htmlspecialchars($testResults['send']['recipient']) ?></strong></small></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Queue Check -->
            <?php if (isset($testResults['queue'])): ?>
                <div class="result <?= $testResults['queue']['status'] ?>">
                    <strong>📬 Email Queue Status:</strong>
                    <?php if (isset($testResults['queue']['queue_status'])): ?>
                        <pre><?php print_r($testResults['queue']['queue_status']); ?></pre>
                    <?php else: ?>
                        <p><?= htmlspecialchars($testResults['queue']['message']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <h2>📋 DNS Records Checklist</h2>
        <div class="config-info">
            <p><strong>Make sure these DNS records are configured:</strong></p>
            <ol>
                <li><strong>SPF:</strong> <code>v=spf1 include:_spf.google.com ~all</code></li>
                <li><strong>DKIM:</strong> Generate from Google Admin Console</li>
                <li><strong>DMARC:</strong> <code>v=DMARC1; p=quarantine; rua=mailto:admin@cforkids.org</code></li>
            </ol>
            <p><a href="https://admin.google.com" target="_blank">→ Open Google Admin Console</a></p>
        </div>

        <p style="margin-top: 30px; text-align: center; color: #666;">
            <a href="index.php">← Back to Admin Dashboard</a>
        </p>
    </div>
</body>
</html>
