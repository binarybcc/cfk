<?php
declare(strict_types=1);

/**
 * Verify Magic Link Endpoint
 * Handles magic link validation and session creation
 */

define('CFK_APP', true);
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/email_manager.php';
require_once __DIR__ . '/../includes/magic_link_manager.php';

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Handle GET request - show auto-submit form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = sanitizeString($_GET['token'] ?? '');

    if (empty($token)) {
        setMessage('Invalid or missing magic link', 'error');
        header('Location: ' . baseUrl('admin/'));
        exit;
    }

    // Generate CSRF token for form submission
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $csrfToken = $_SESSION['csrf_token'];
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Completing Login...</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background: linear-gradient(135deg, #2c5530 0%, #1a3a1d 100%);
            }
            .container {
                text-align: center;
                background: white;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #2c5530;
                margin-top: 0;
            }
            p {
                color: #666;
                font-size: 16px;
            }
            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #2c5530;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
                margin: 20px auto;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎄 Logging you in...</h1>
            <div class="spinner"></div>
            <p>Please wait while we verify your login link.</p>

            <form id="magic-link-form" method="POST" action="<?php echo baseUrl('admin/verify-magic-link.php'); ?>" style="display: none;">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <button type="submit">Complete Login</button>
            </form>

            <script>
                // Auto-submit form on page load (POST-based token submission)
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('magic-link-form').submit();
                });
            </script>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle POST request - validate token and create session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        MagicLinkManager::logEvent(null, 'magic_link_csrf_failure', $ipAddress, $userAgent, 'failed');
        setMessage('Security validation failed. Please try again.', 'error');
        header('Location: ' . baseUrl('admin/'));
        exit;
    }

    $token = sanitizeString($_POST['token'] ?? '');

    if (empty($token)) {
        setMessage('Invalid or missing magic link', 'error');
        header('Location: ' . baseUrl('admin/'));
        exit;
    }

    // Validate token
    $tokenData = MagicLinkManager::validateToken($token);

    if (!$tokenData) {
        MagicLinkManager::logEvent(null, 'magic_link_validation_failed', $ipAddress, $userAgent, 'failed');
        setMessage('Invalid or expired magic link. Please request a new one.', 'error');
        header('Location: ' . baseUrl('admin/'));
        exit;
    }

    // Check if email has associated admin account
    $adminSql = "SELECT id, email, username FROM admin_users WHERE email = :email LIMIT 1";
    $adminUser = Database::fetchRow($adminSql, ['email' => $tokenData['email']]);

    if (!$adminUser) {
        MagicLinkManager::logEvent(null, 'magic_link_no_admin_account', $ipAddress, $userAgent, 'failed', [
            'email' => $tokenData['email']
        ]);
        setMessage('No admin account found for this email', 'error');
        header('Location: ' . baseUrl('admin/'));
        exit;
    }

    try {
        // Token already deleted in validateToken() to prevent race conditions
        // No need to call consumeToken() here

        // Regenerate session ID (prevent session fixation attacks)
        session_regenerate_id(true);

        // Create admin session
        $_SESSION['admin_id'] = $adminUser['id'];
        $_SESSION['admin_email'] = $adminUser['email'];
        $_SESSION['admin_username'] = $adminUser['username'];
        $_SESSION['login_time'] = time();
        $_SESSION['login_ip'] = $ipAddress;

        // Log successful login
        MagicLinkManager::logEvent($adminUser['id'], 'admin_login_success', $ipAddress, $userAgent, 'success');

        // Send login notification email
        sendLoginNotificationEmail($adminUser['email'], $ipAddress, $userAgent);

        // Redirect to admin dashboard
        header('Location: ' . baseUrl('admin/'));
        exit;

    } catch (Exception $e) {
        error_log('Magic link verification error: ' . $e->getMessage());
        MagicLinkManager::logEvent($adminUser['id'], 'magic_link_verification_error', $ipAddress, $userAgent, 'failed');
        setMessage('An error occurred during login. Please try again.', 'error');
        header('Location: ' . baseUrl('admin/'));
        exit;
    }
}

// Invalid method
http_response_code(405);
setMessage('Method not allowed', 'error');
header('Location: ' . baseUrl('admin/'));
exit;

/**
 * Send login notification email to admin
 */
function sendLoginNotificationEmail(string $email, string $ipAddress, string $userAgent): void {
    try {
        // Get IP geolocation (simple approach)
        $location = getLocationFromIp($ipAddress);
        $deviceInfo = parseUserAgent($userAgent);
        $timestamp = date('M d, Y \a\t g:i A T');

        $subject = '🔐 New Admin Login - ' . config('app_name', 'Christmas for Kids');

        $htmlContent = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; }
        .info-box { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; }
        a { color: #2c5530; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🎄 New Admin Login Notification</h2>
        <p>Your admin account just logged in. Here are the details:</p>

        <div class="info-box">
            <strong>Login Time:</strong> {$timestamp}<br>
            <strong>IP Address:</strong> {$ipAddress}<br>
            <strong>Location:</strong> {$location}<br>
            <strong>Device:</strong> {$deviceInfo}
        </div>

        <div class="warning">
            <strong>⚠️ Suspicious Activity?</strong><br>
            If this login wasn't you, please contact the system administrator immediately.
        </div>

        <p style="font-size: 12px; color: #999;">
            This is an automated security notification. Please do not reply to this email.
        </p>
    </div>
</body>
</html>
HTML;

        $textContent = <<<TEXT
New Admin Login Notification

Your admin account just logged in. Here are the details:

Login Time: {$timestamp}
IP Address: {$ipAddress}
Location: {$location}
Device: {$deviceInfo}

Suspicious Activity?
If this login wasn't you, please contact the system administrator immediately.

This is an automated security notification.
TEXT;

        // Use working email pattern from reservation_emails.php
        $mailer = CFK_Email_Manager::getMailer();
        $mailer->clearAddresses();
        $mailer->addAddress($email);
        $mailer->Subject = $subject;
        $mailer->Body = $htmlContent;
        $mailer->AltBody = $textContent;
        $mailer->send();
    } catch (Exception $e) {
        error_log('Failed to send login notification: ' . $e->getMessage());
        // Don't fail the login if notification fails
    }
}

/**
 * Get approximate location from IP address
 */
function getLocationFromIp(string $ip): string {
    // Simple implementation - just return IP for now
    // In production, could use a geolocation service
    return $ip;
}

/**
 * Parse user agent for device info
 */
function parseUserAgent(string $userAgent): string {
    if (strpos($userAgent, 'Windows') !== false) {
        return 'Windows';
    } elseif (strpos($userAgent, 'Mac') !== false) {
        return 'macOS';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        return 'Linux';
    } elseif (strpos($userAgent, 'iPhone') !== false) {
        return 'iPhone';
    } elseif (strpos($userAgent, 'Android') !== false) {
        return 'Android';
    }
    return 'Unknown Device';
}
