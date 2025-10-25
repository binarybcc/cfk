<?php

declare(strict_types=1);

/**
 * STAGING ONLY - Password Login Bypass
 * TEMPORARY: Remove before deploying to production
 * This file allows password login on staging when email is unavailable
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// SECURITY: Only allow on staging environment
if (config('environment') !== 'staging') {
    die('This login method is only available on staging environment.');
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Get admin user from database
        $admin = Database::fetchRow(
            "SELECT id, username, password_hash, role FROM admin_users WHERE username = ?",
            [$username]
        );

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Login successful - create session
            $_SESSION['cfk_admin_id'] = $admin['id'];
            $_SESSION['cfk_admin_username'] = $admin['username'];
            $_SESSION['cfk_admin_role'] = $admin['role'];

            // Update last login
            Database::query(
                "UPDATE admin_users SET last_login = NOW() WHERE id = ?",
                [$admin['id']]
            );

            // Redirect to dashboard
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staging Login - CFK Admin</title>
    <link rel="stylesheet" href="<?php echo baseUrl('assets/css/styles.css'); ?>">

    <style>
        body {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 420px;
            margin: 1rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: #2c5530;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .staging-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #ffc107;
            color: #333;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }

        .login-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #2c5530;
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: #2c5530;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-login:hover {
            background: #1f3d22;
        }

        .login-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: #666;
        }

        .login-footer a {
            color: #2c5530;
            text-decoration: none;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="staging-badge">⚠️ STAGING ENVIRONMENT</div>
            <h1>🎄 CFK Admin</h1>
            <p>Staging Password Login</p>
        </div>

        <div class="warning-box">
            <strong>⚠️ Staging Only:</strong> This password login is temporary and only works on staging. Email is not available on this server.
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-input"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn-login">
                Login to Staging
            </button>
        </form>

        <div class="login-footer">
            <p>🔒 This is a secure staging area. All login attempts are logged.</p>
            <p><a href="<?php echo baseUrl(); ?>">← Back to Public Site</a></p>
        </div>
    </div>
</body>
</html>
