<?php
declare(strict_types=1);

/**
 * Admin Login Page
 * Simple authentication for admin access
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

$error = '';

// Handle login form submission
if ($_POST && isset($_POST['login'])) {
    $username = sanitizeString($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    // Validate CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        // Check credentials
        $user = Database::fetchRow(
            "SELECT * FROM admin_users WHERE username = ? AND role IN ('admin', 'editor')",
            [$username]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['cfk_admin_id'] = $user['id'];
            $_SESSION['cfk_admin_username'] = $user['username'];
            $_SESSION['cfk_admin_role'] = $user['role'];
            
            // Update last login
            Database::update('admin_users', 
                ['last_login' => date('Y-m-d H:i:s')], 
                ['id' => $user['id']]
            );
            
            // Set remember me cookie if requested
            if ($rememberMe) {
                $token = bin2hex(random_bytes(32));
                setcookie('cfk_remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                // In production, store this token hashed in database for security
            }
            
            // Redirect to dashboard
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
            // Log failed login attempt
            error_log("CFK Admin: Failed login attempt for username: $username from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Christmas for Kids</title>
    
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
        
        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
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
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-checkbox input {
            margin: 0;
        }
        
        .form-checkbox label {
            margin: 0;
            font-weight: normal;
            color: #555;
            font-size: 0.95rem;
        }
        
        .login-btn {
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
        
        .login-btn:hover {
            background: #1e3a21;
            transform: translateY(-1px);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        .login-footer a {
            color: #2c5530;
            text-decoration: none;
            font-size: 0.95rem;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .security-notice {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>CFK Admin</h1>
            <p>Christmas for Kids Administration</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
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
                <label for="password" class="form-label">Password</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-input" 
                       required 
                       autocomplete="current-password">
            </div>

            <div class="form-checkbox">
                <input type="checkbox" id="remember_me" name="remember_me" value="1">
                <label for="remember_me">Remember me for 30 days</label>
            </div>

            <button type="submit" name="login" class="login-btn">
                Login to Admin Panel
            </button>
        </form>

        <div class="login-footer">
            <a href="<?php echo baseUrl(); ?>">← Back to Public Site</a>
        </div>
        
        <div class="security-notice">
            🔒 This is a secure admin area. All login attempts are logged.
        </div>
    </div>

    <script>
        // Simple client-side validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username) {
                alert('Please enter your username.');
                document.getElementById('username').focus();
                e.preventDefault();
                return;
            }
            
            if (!password) {
                alert('Please enter your password.');
                document.getElementById('password').focus();
                e.preventDefault();
                return;
            }
            
            if (password.length < 3) {
                alert('Password is too short.');
                document.getElementById('password').focus();
                e.preventDefault();
                return;
            }
        });

        // Clear any error messages after user starts typing
        document.getElementById('username').addEventListener('input', clearErrors);
        document.getElementById('password').addEventListener('input', clearErrors);
        
        function clearErrors() {
            const errorDiv = document.querySelector('.error-message');
            if (errorDiv) {
                errorDiv.style.opacity = '0';
                setTimeout(() => errorDiv.remove(), 300);
            }
        }
    </script>
</body>
</html>