<?php
declare(strict_types=1);

/**
 * Admin Reset Password Page
 * Set new password with valid reset token
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Allow password reset even when logged in (for convenience)
// Users may want to change their password while logged in

$error = '';
$success = false;
$validToken = false;
$user = null;

// Get token and username from URL
$token = $_GET['token'] ?? '';
$username = $_GET['user'] ?? '';

// Validate token
if ($token && $username) {
    $user = Database::fetchRow(
        "SELECT * FROM admin_users WHERE username = ? AND reset_token IS NOT NULL AND reset_token_expiry > NOW()",
        [sanitizeString($username)]
    );

    if ($user && password_verify($token, $user['reset_token'])) {
        $validToken = true;
    } else {
        $error = 'Invalid or expired reset link. Please request a new password reset.';
    }
} else {
    $error = 'Invalid reset link. Please request a new password reset.';
}

// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    if (!$validToken) {
        $error = 'Your reset link has expired or is invalid. Please request a new password reset.';
        error_log("CFK Admin: Password reset failed - invalid or expired token");
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate CSRF token
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Security token invalid. Please try again.';
        } elseif (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Both password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } else {
        // Update password and clear reset token
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $updated = Database::update('admin_users',
            [
                'password_hash' => $passwordHash,
                'reset_token' => null,
                'reset_token_expiry' => null,
                'updated_at' => gmdate('Y-m-d H:i:s')
            ],
            ['id' => $user['id']]
        );

            if ($updated !== false) {
                $success = true;

                // Log successful password reset
                error_log("CFK Admin: Password reset successful for username: {$user['username']} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            } else {
                $error = 'Failed to update password. Please try again or contact the administrator.';
                error_log("CFK Admin: Password reset failed for username: {$user['username']} - Database update failed");
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CFK Admin</title>

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
            padding: 1.5rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #c3e6cb;
            text-align: center;
        }

        .success-message h2 {
            color: #155724;
            margin: 0 0 0.5rem 0;
            font-size: 1.3rem;
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

        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 2px;
        }

        .strength-weak { background: #f44336; width: 33%; }
        .strength-medium { background: #ff9800; width: 66%; }
        .strength-strong { background: #4caf50; width: 100%; }

        .password-hint {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
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

        .reset-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
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
            padding: 0.75rem 1.5rem;
            background: #f8f9fa;
            border-radius: 6px;
            display: inline-block;
            transition: all 0.2s;
        }

        .reset-footer a:hover {
            background: #e9ecef;
            text-decoration: none;
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
        <?php if ($success): ?>
            <div class="reset-header">
                <h1>✓ Password Reset Successful</h1>
            </div>

            <div class="success-message">
                <h2>Your password has been updated!</h2>
                <p>You can now log in with your new password.</p>
            </div>

            <div class="reset-footer">
                <a href="login.php">Continue to Login →</a>
            </div>

        <?php elseif (!$validToken): ?>
            <div class="reset-header">
                <h1>Invalid Reset Link</h1>
            </div>

            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>

            <div class="reset-footer">
                <a href="forgot_password.php">Request New Reset Link</a>
            </div>

        <?php else: ?>
            <div class="reset-header">
                <h1>Set New Password</h1>
                <p>Choose a strong password for your admin account.</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                🔒 <strong>Password Requirements:</strong>
                <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                    <li>At least 8 characters long</li>
                    <li>Mix of letters, numbers, and symbols recommended</li>
                </ul>
            </div>

            <form method="POST" action="" id="resetForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <div class="form-group">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password"
                           id="new_password"
                           name="new_password"
                           class="form-input"
                           required
                           autocomplete="new-password"
                           minlength="8"
                           autofocus>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-hint" id="strengthText"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password"
                           id="confirm_password"
                           name="confirm_password"
                           class="form-input"
                           required
                           autocomplete="new-password"
                           minlength="8">
                    <div class="password-hint" id="matchText"></div>
                </div>

                <button type="submit" name="reset_password" class="reset-btn" id="submitBtn">
                    Reset Password
                </button>
            </form>

            <div class="reset-footer">
                <a href="login.php">← Back to Login</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const matchText = document.getElementById('matchText');
        const submitBtn = document.getElementById('submitBtn');

        // Check password strength
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            return strength;
        }

        newPassword?.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);

            strengthBar.className = 'password-strength-bar';
            if (password.length === 0) {
                strengthBar.className = 'password-strength-bar';
                strengthText.textContent = '';
            } else if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = '⚠️ Weak password';
                strengthText.style.color = '#f44336';
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = '⚡ Medium strength';
                strengthText.style.color = '#ff9800';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = '✓ Strong password';
                strengthText.style.color = '#4caf50';
            }

            checkPasswordMatch();
        });

        confirmPassword?.addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            const password = newPassword.value;
            const confirm = confirmPassword.value;

            if (confirm.length === 0) {
                matchText.textContent = '';
                return;
            }

            if (password === confirm) {
                matchText.textContent = '✓ Passwords match';
                matchText.style.color = '#4caf50';
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.style.color = '#f44336';
            }
        }

        // Form validation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = newPassword.value;
            const confirm = confirmPassword.value;

            if (password.length < 8) {
                alert('Password must be at least 8 characters long.');
                newPassword.focus();
                e.preventDefault();
                return;
            }

            if (password !== confirm) {
                alert('Passwords do not match.');
                confirmPassword.focus();
                e.preventDefault();
                return;
            }

            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.textContent = 'Resetting Password...';
        });
    </script>
</body>
</html>
