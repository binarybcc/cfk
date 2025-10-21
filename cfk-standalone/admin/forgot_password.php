<?php

declare(strict_types=1);

/**
 * Admin Forgot Password Page
 * Request password reset link
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';
$success = false;

// Handle password reset request
if ($_POST && isset($_POST['reset_request'])) {
    $username = sanitizeString($_POST['username'] ?? '');
    $email = sanitizeEmail($_POST['email'] ?? '');

    // Validate CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please try again.';
    } elseif (empty($username) || empty($email)) {
        $error = 'Username and email are required.';
    } else {
        // Check if user exists with this username and email
        $user = Database::fetchRow(
            "SELECT * FROM admin_users WHERE username = ? AND email = ? AND role IN ('admin', 'editor')",
            [$username, $email]
        );

        if ($user) {
            // Generate reset token (valid for 1 hour)
            $resetToken = bin2hex(random_bytes(32));
            $resetExpiry = gmdate('Y-m-d H:i:s', time() + 3600); // 1 hour from now in UTC

            // Store token in database
            Database::update(
                'admin_users',
                [
                    'reset_token' => password_hash($resetToken, PASSWORD_DEFAULT),
                    'reset_token_expiry' => $resetExpiry
                ],
                ['id' => $user['id']]
            );

            // Generate reset link
            $resetLink = baseUrl("admin/reset_password.php?token=$resetToken&user=" . urlencode($username));

            // Send email
            $to = $user['email'];
            $subject = 'Password Reset Request - CFK Admin';
            $message = "Hello {$user['username']},\n\n";
            $message .= "You requested a password reset for your Christmas for Kids admin account.\n\n";
            $message .= "Click the link below to reset your password:\n\n";
            $message .= $resetLink . "\n\n";
            $message .= "This link will expire in 1 hour.\n\n";
            $message .= "If you didn't request this, please ignore this email.\n\n";
            $message .= "Best regards,\n";
            $message .= "Christmas for Kids Team";

            $headers = "From: " . config('admin_email') . "\r\n";
            $headers .= "Reply-To: " . config('admin_email') . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            if (mail((string) $to, $subject, $message, $headers)) {
                $success = true;
                $message = 'Password reset instructions have been sent to your email address.';

                // Log the reset request
                error_log("CFK Admin: Password reset requested for username: $username from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            } else {
                $error = 'Failed to send reset email. Please contact the administrator.';
                error_log("CFK Admin: Failed to send password reset email for username: $username");
            }
        } else {
            // For security, don't reveal if user exists or not
            // Show same message as success
            $success = true;
            $message = 'If an account exists with that username and email, password reset instructions will be sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CFK Admin</title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo baseUrl('assets/css/styles.css'); ?>">

    <style>
        body {
            background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .reset-container {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
            margin: 1rem;
        }

        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .reset-header h1 {
            color: #2c5530;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .reset-header p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
            font-weight: 500;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #c3e6cb;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: #2c5530;
        }

        .reset-btn {
            width: 100%;
            padding: 1rem;
            background: #2c5530;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }

        .reset-btn:hover {
            background: #1e3a21;
            transform: translateY(-1px);
        }

        .reset-btn:active {
            transform: translateY(0);
        }

        .reset-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .reset-footer a {
            color: #2c5530;
            text-decoration: none;
            font-size: 0.95rem;
            margin: 0 0.5rem;
        }

        .reset-footer a:hover {
            text-decoration: underline;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>Forgot Password?</h1>
            <p>Enter your username and email address to receive password reset instructions.</p>
        </div>

        <?php if ($error !== '' && $error !== '0') : ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success) : ?>
            <div class="success-message">
                <?php echo htmlspecialchars($message); ?>
            </div>

            <div class="reset-footer">
                <a href="login.php">← Back to Login</a>
            </div>
        <?php else : ?>
            <div class="info-box">
                💡 <strong>Note:</strong> You must have an email address associated with your admin account to reset your password.
            </div>

            <form method="POST" action="" id="resetForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text"
                           id="username"
                           name="username"
                           class="form-input"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           required
                           autocomplete="username"
                           autofocus>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email"
                           id="email"
                           name="email"
                           class="form-input"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           required
                           autocomplete="email">
                </div>

                <button type="submit" name="reset_request" class="reset-btn">
                    Send Reset Instructions
                </button>
            </form>

            <div class="reset-footer">
                <a href="login.php">← Back to Login</a>
                <span style="margin: 0 0.5rem; color: #ddd;">|</span>
                <a href="<?php echo baseUrl(); ?>">Public Site</a>
            </div>
        <?php endif; ?>
    </div>

    <script nonce="<?php echo $cspNonce; ?>">
        // Simple client-side validation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();

            if (!username) {
                alert('Please enter your username.');
                document.getElementById('username').focus();
                e.preventDefault();
                return;
            }

            if (!email) {
                alert('Please enter your email address.');
                document.getElementById('email').focus();
                e.preventDefault();
                return;
            }

            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address.');
                document.getElementById('email').focus();
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
