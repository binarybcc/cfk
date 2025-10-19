<?php
declare(strict_types=1);

/**
 * Admin Login Page - Magic Link Authentication Only
 * Passwordless secure authentication for admin access
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/remember_me_tokens.php';
require_once __DIR__ . '/../includes/magic_link_manager.php';

// Check for remember-me token (auto-login)
if (!isLoggedIn()) {
    $rememberToken = RememberMeTokens::getTokenFromCookie();
    if ($rememberToken) {
        $user = RememberMeTokens::validateToken($rememberToken);
        if ($user) {
            // Auto-login via remember-me token
            $_SESSION['cfk_admin_id'] = $user['id'];
            $_SESSION['cfk_admin_username'] = $user['username'];
            $_SESSION['cfk_admin_role'] = $user['role'];

            header('Location: index.php');
            exit;
        } else {
            // Invalid token - clear cookie
            RememberMeTokens::clearCookie();
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
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
            font-weight: 600;
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

        .login-btn:disabled {
            background: #999;
            cursor: not-allowed;
            transform: none;
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

        .magic-link-info {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            padding: 1.25rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #2c5530;
        }

        .magic-link-info strong {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .magic-link-info p {
            margin: 0.5rem 0;
        }

        .magic-link-info ul {
            margin: 0.5rem 0 0 1.5rem;
            padding: 0;
        }

        .magic-link-info li {
            margin-bottom: 0.3rem;
        }

        #magicLinkMessage {
            margin-top: 1rem;
            text-align: center;
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🎄 CFK Admin</h1>
            <p>Christmas for Kids Administration</p>
        </div>

        <?php
        // Display success messages (e.g., after logout)
        $message = getMessage();
        if ($message && $message['type'] === 'success'):
        ?>
            <div class="success-message">
                <?php echo htmlspecialchars($message['text']); ?>
            </div>
        <?php endif; ?>

        <!-- Magic Link Login Form -->
        <form method="POST" action="<?php echo baseUrl('admin/request-magic-link.php'); ?>" id="magicLinkForm">
            <div class="magic-link-info">
                <strong>✉️ Passwordless Login</strong>
                <p>A secure login link will be sent to your email. No password needed!</p>
                <ul>
                    <li>✓ No password to remember</li>
                    <li>✓ Secure email verification</li>
                    <li>✓ Expires in 5 minutes</li>
                </ul>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="form-group">
                <label for="magic_email" class="form-label">Admin Email Address</label>
                <input type="email"
                       id="magic_email"
                       name="email"
                       class="form-input"
                       placeholder="your-email@example.com"
                       required
                       autofocus
                       autocomplete="email">
            </div>

            <button type="submit" class="login-btn" id="magicLinkBtn">
                Send Magic Link
            </button>

            <div id="magicLinkMessage"></div>
        </form>

        <div class="login-footer">
            <a href="<?php echo baseUrl(); ?>">← Back to Public Site</a>
        </div>

        <div class="security-notice">
            🔒 This is a secure admin area. All login attempts are logged.
        </div>
    </div>

    <script>
        // Magic Link form handling
        document.getElementById('magicLinkForm').addEventListener('submit', function(e) {
            e.preventDefault();

            var email = document.getElementById('magic_email').value.trim();
            var btn = document.getElementById('magicLinkBtn');
            var msgDiv = document.getElementById('magicLinkMessage');

            if (!email) {
                alert('Please enter your email address.');
                document.getElementById('magic_email').focus();
                return;
            }

            // Disable button and show loading state
            btn.disabled = true;
            btn.textContent = 'Sending...';

            // Send magic link request
            fetch('<?php echo baseUrl('admin/request-magic-link.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                msgDiv.style.display = 'block';
                if (data.success) {
                    msgDiv.className = 'success-message';
                    msgDiv.innerHTML = '✅ Magic link sent! Check your email and click the login button.';
                    document.getElementById('magic_email').value = '';
                    setTimeout(function() {
                        window.location.href = '<?php echo baseUrl('admin/magic-link-sent.php'); ?>';
                    }, 2000);
                } else {
                    msgDiv.className = 'error-message';
                    msgDiv.innerHTML = '❌ ' + (data.message || 'Failed to send magic link');
                    btn.disabled = false;
                    btn.textContent = 'Send Magic Link';
                }
            })
            .catch(function(error) {
                msgDiv.style.display = 'block';
                msgDiv.className = 'error-message';
                msgDiv.innerHTML = '❌ An error occurred. Please try again.';
                btn.disabled = false;
                btn.textContent = 'Send Magic Link';
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>
