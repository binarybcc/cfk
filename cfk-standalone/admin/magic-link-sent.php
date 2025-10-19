<?php
declare(strict_types=1);

/**
 * Magic Link Sent Confirmation Page
 */

define('CFK_APP', true);
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/magic_link_manager.php';

$expirationMinutes = MagicLinkManager::getExpirationMinutes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magic Link Sent - <?php echo config('app_name'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c5530 0%, #1a3a1d 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .logo {
            font-size: 48px;
            margin-bottom: 20px;
        }
        h1 {
            color: #2c5530;
            margin-bottom: 20px;
            font-size: 24px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .info-box {
            background: #f9f9f9;
            border-left: 4px solid #2c5530;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
            border-radius: 4px;
        }
        .info-box strong {
            display: block;
            color: #2c5530;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .info-box li {
            margin-left: 20px;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .timer {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 13px;
        }
        .timer strong {
            color: #856404;
            display: block;
            margin-bottom: 5px;
        }
        .btn {
            display: inline-block;
            padding: 12px 32px;
            background: #2c5530;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #1a3a1d;
        }
        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📧</div>

        <h1>Check Your Email</h1>

        <p>We've sent a magic link to your registered email address.</p>

        <div class="info-box">
            <strong>Next Steps:</strong>
            <ul style="list-style: none; padding: 0;">
                <li>✅ Check your email inbox</li>
                <li>✅ Click the "Login to Admin Panel" button</li>
                <li>✅ You'll be logged in automatically</li>
            </ul>
        </div>

        <div class="timer">
            <strong>⏰ Link Expires In <?php echo $expirationMinutes; ?> Minutes</strong>
            Make sure to click the link within <?php echo $expirationMinutes; ?> minutes, or you'll need to request a new one.
        </div>

        <p><strong>Didn't receive the email?</strong></p>
        <ul style="margin: 15px 0; padding-left: 20px; text-align: left; font-size: 13px; color: #666;">
            <li>Check your spam or junk folder</li>
            <li>Make sure you entered the correct email address</li>
            <li>If you still don't see it, you can request a new link</li>
        </ul>

        <div>
            <form method="GET" style="display: inline;">
                <button type="submit" class="btn" formaction="<?php echo baseUrl('admin/'); ?>">Request Another Link</button>
            </form>
            <a href="<?php echo baseUrl('admin/'); ?>" class="btn btn-secondary">Back to Login</a>
        </div>

        <div class="footer">
            <p>If you have any issues, contact the system administrator.</p>
        </div>
    </div>
</body>
</html>
<?php
