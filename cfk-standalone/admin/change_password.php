<?php
declare(strict_types=1);

/**
 * Admin Password Change Page
 * Forces password change for default admin account
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Get current admin
$admin = Database::fetchRow(
    "SELECT * FROM admin_users WHERE id = ?",
    [$_SESSION['cfk_admin_id']]
);

// Handle password change submission
if ($_POST && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please try again.';
    } elseif (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif (!password_verify($currentPassword, $admin['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters long.';
    } elseif ($currentPassword === $newPassword) {
        $error = 'New password must be different from current password.';
    } else {
        // Update password
        try {
            Database::update('admin_users',
                ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)],
                ['id' => $_SESSION['cfk_admin_id']]
            );

            // Clear force password change flag
            unset($_SESSION['force_password_change']);

            // Log the change
            error_log("CFK Admin: Password changed for user ID: {$_SESSION['cfk_admin_id']}");

            // Redirect to dashboard
            setMessage('success', 'Password changed successfully!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            error_log('Failed to change password: ' . $e->getMessage());
            $error = 'System error occurred. Please try again.';
        }
    }
}

// Check if this is a forced password change
$isForced = isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Christmas for Kids Admin</title>
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

        .password-container {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 500px;
            margin: 1rem;
        }

        .password-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .password-header h1 {
            color: #2c5530;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .password-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .warning-notice {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .warning-notice h3 {
            margin-top: 0;
            color: #856404;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
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

        .password-requirements {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .password-requirements ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.5rem;
        }

        .password-requirements li {
            margin-bottom: 0.25rem;
        }

        .btn-primary {
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

        .btn-primary:hover {
            background: #1e3a21;
            transform: translateY(-1px);
        }

        .btn-secondary {
            display: block;
            width: 100%;
            padding: 0.875rem;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            margin-top: 1rem;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="password-header">
            <h1><?php echo $isForced ? '🔒 Password Change Required' : 'Change Password'; ?></h1>
            <p><?php echo $isForced ? 'For security, you must change your password' : 'Update your admin password'; ?></p>
        </div>

        <?php if ($isForced): ?>
            <div class="warning-notice">
                <h3>⚠️ Default Password Detected</h3>
                <p>You are currently using the default password. For security reasons, you must set a new, unique password before accessing the admin panel.</p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="passwordForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="form-group">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password"
                       id="current_password"
                       name="current_password"
                       class="form-input"
                       required
                       autocomplete="current-password"
                       autofocus>
            </div>

            <div class="form-group">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password"
                       id="new_password"
                       name="new_password"
                       class="form-input"
                       required
                       autocomplete="new-password"
                       minlength="8">
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password"
                       id="confirm_password"
                       name="confirm_password"
                       class="form-input"
                       required
                       autocomplete="new-password"
                       minlength="8">
            </div>

            <div class="password-requirements">
                <strong>Password Requirements:</strong>
                <ul>
                    <li>At least 8 characters long</li>
                    <li>Different from current password</li>
                    <li>Recommended: Mix of letters, numbers, and symbols</li>
                </ul>
            </div>

            <button type="submit" name="change_password" class="btn-primary">
                Change Password
            </button>

            <?php if (!$isForced): ?>
                <a href="index.php" class="btn-secondary">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <script>
        // Client-side validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (!currentPassword || !newPassword || !confirmPassword) {
                alert('Please fill in all fields.');
                e.preventDefault();
                return;
            }

            if (newPassword.length < 8) {
                alert('New password must be at least 8 characters long.');
                e.preventDefault();
                return;
            }

            if (newPassword !== confirmPassword) {
                alert('New passwords do not match.');
                e.preventDefault();
                return;
            }

            if (currentPassword === newPassword) {
                alert('New password must be different from current password.');
                e.preventDefault();
                return;
            }
        });

        // Clear error message when user starts typing
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('input', function() {
                const errorDiv = document.querySelector('.error-message');
                if (errorDiv) {
                    errorDiv.style.opacity = '0';
                    setTimeout(() => errorDiv.remove(), 300);
                }
            });
        });
    </script>
</body>
</html>
