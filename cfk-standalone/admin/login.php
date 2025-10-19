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
require_once __DIR__ . '/../includes/rate_limiter.php';
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

$error = '';
$isLockedOut = false;
$remainingTime = 0;

// Handle login form submission
if ($_POST && isset($_POST['login'])) {
    $username = sanitizeString($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);

    // Create rate limit identifier (username + IP)
    $rateLimitId = $username . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    // Check rate limiting FIRST
    if (!RateLimiter::checkLoginAttempt($rateLimitId)) {
        $remainingTime = ceil(RateLimiter::getRemainingLockoutTime($rateLimitId) / 60);
        $error = "Too many failed login attempts. Please try again in $remainingTime minute(s).";
        $isLockedOut = true;
    }
    // Validate CSRF token
    elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
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
            // Login successful - clear rate limit
            RateLimiter::recordSuccessfulAttempt($rateLimitId);

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
                $token = RememberMeTokens::generateToken($user['id']);
                $isProduction = ($_SERVER['HTTP_HOST'] ?? 'localhost') !== 'localhost';
                RememberMeTokens::setCookie($token, $isProduction);
            }

            // Redirect to dashboard
            header('Location: index.php');
            exit;
        } else {
            // Login failed - record attempt
            RateLimiter::recordFailedAttempt($rateLimitId);

            $remainingAttempts = RateLimiter::getRemainingAttempts($rateLimitId);
            if ($remainingAttempts > 0) {
                $error = "Invalid username or password. You have $remainingAttempts attempt(s) remaining.";
            } else {
                $lockoutMinutes = ceil(RateLimiter::getLockoutTime() / 60);
                $error = "Too many failed attempts. Account locked for $lockoutMinutes minutes.";
                $isLockedOut = true;
            }

            // Log failed login attempt
            error_log("CFK Admin: Failed login attempt for username: $username from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " (attempts: " . RateLimiter::getAttemptCount($rateLimitId) . ")");
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

        .login-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e0e0e0;
        }

        .login-tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 0.95rem;
            font-weight: 600;
            color: #999;
            transition: all 0.2s;
        }

        .login-tab.active {
            color: #2c5530;
            border-bottom: 3px solid #2c5530;
            margin-bottom: -2px;
        }

        .login-form-section {
            display: none;
        }

        .login-form-section.active {
            display: block;
        }

        .magic-link-info {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #2c5530;
        }

        .magic-link-info strong {
            display: block;
            margin-bottom: 0.5rem;
        }

        .magic-link-info ul {
            margin: 0.5rem 0 0 1.5rem;
            padding: 0;
        }

        .magic-link-info li {
            margin-bottom: 0.3rem;
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

        <?php
        // Display success messages (e.g., after logout)
        $message = getMessage();
        if ($message && $message['type'] === 'success'):
        ?>
            <div class="success-message">
                <?php echo htmlspecialchars($message['text']); ?>
            </div>
        <?php endif; ?>

        <!-- Login Tabs -->
        <div class="login-tabs">
            <button type="button" class="login-tab active" data-tab="magic-link">
                ✉️ Magic Link
            </button>
            <button type="button" class="login-tab" data-tab="password">
                🔐 Password
            </button>
        </div>

        <!-- Password Login Form -->
        <form method="POST" action="" id="loginForm" class="login-form-section">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text"
                       id="username"
                       name="username"
                       class="form-input"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       required
                       autocomplete="username">
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

        <!-- Magic Link Login Form -->
        <form method="POST" action="<?php echo baseUrl('admin/request-magic-link.php'); ?>" id="magicLinkForm" class="login-form-section active">
            <div class="magic-link-info">
                <strong>🎄 Magic Link Login</strong>
                <p>A secure login link will be sent to your email. No password needed!</p>
                <ul>
                    <li>✓ No password to remember</li>
                    <li>✓ Automatic after email verification</li>
                    <li>✓ Expires in 5 minutes for security</li>
                </ul>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="form-group">
                <label for="magic_email" class="form-label">Admin Email</label>
                <input type="email"
                       id="magic_email"
                       name="email"
                       class="form-input"
                       placeholder="your-email@example.com"
                       required
                       autofocus>
            </div>

            <button type="submit" class="login-btn" id="magicLinkBtn">
                Send Magic Link
            </button>

            <div id="magicLinkMessage" style="margin-top: 1rem; text-align: center; display: none;"></div>
        </form>

        <div class="login-footer">
            <a href="forgot_password.php">Forgot Password?</a>
            <span style="margin: 0 1rem; color: #ddd;">|</span>
            <a href="<?php echo baseUrl(); ?>">← Back to Public Site</a>
        </div>
        
        <div class="security-notice">
            🔒 This is a secure admin area. All login attempts are logged.
        </div>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.login-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');

                // Remove active class from all tabs and sections
                document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.login-form-section').forEach(s => s.classList.remove('active'));

                // Add active class to clicked tab and corresponding section
                this.classList.add('active');
                document.querySelector(`.login-form-section[style*="display: none"]`) ||
                    document.querySelectorAll('.login-form-section').forEach(s => {
                        if (s.id === (tabName === 'password' ? 'loginForm' : 'magicLinkForm')) {
                            s.classList.add('active');
                        }
                    });

                // Properly show/hide sections
                const sections = document.querySelectorAll('.login-form-section');
                if (tabName === 'password') {
                    sections[0].classList.add('active');
                    sections[1].classList.remove('active');
                } else {
                    sections[0].classList.remove('active');
                    sections[1].classList.add('active');
                }
            });
        });

        // Password form validation
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

        // Magic Link form handling
        document.getElementById('magicLinkForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const email = document.getElementById('magic_email').value.trim();
            const btn = document.getElementById('magicLinkBtn');
            const msgDiv = document.getElementById('magicLinkMessage');

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
            .then(response => response.json())
            .then(data => {
                msgDiv.style.display = 'block';
                if (data.success) {
                    msgDiv.className = 'success-message';
                    msgDiv.innerHTML = '✅ Magic link sent! Check your email and click the login button.';
                    document.getElementById('magic_email').value = '';
                    setTimeout(() => {
                        window.location.href = '<?php echo baseUrl('admin/magic-link-sent.php'); ?>';
                    }, 2000);
                } else {
                    msgDiv.className = 'error-message';
                    msgDiv.innerHTML = '❌ ' + (data.message || 'Failed to send magic link');
                    btn.disabled = false;
                    btn.textContent = 'Send Magic Link';
                }
            })
            .catch(error => {
                msgDiv.style.display = 'block';
                msgDiv.className = 'error-message';
                msgDiv.innerHTML = '❌ An error occurred. Please try again.';
                btn.disabled = false;
                btn.textContent = 'Send Magic Link';
                console.error('Error:', error);
            });
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