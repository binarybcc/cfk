<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Complete!</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            color: #28a745;
            font-size: 48px;
            line-height: 80px;
            margin: 0 auto 20px;
            animation: scaleIn 0.5s ease-out;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px 30px;
        }
        .success-message {
            text-align: center;
            margin-bottom: 30px;
        }
        .success-message h2 {
            color: #28a745;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .success-message p {
            color: #666;
            line-height: 1.6;
        }
        .info-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .info-box h3 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .info-box ol {
            margin-left: 20px;
            color: #555;
        }
        .info-box li {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .info-box strong {
            color: #333;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert.warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            text-align: center;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="checkmark">✓</div>
            <h1>Installation Complete!</h1>
            <p>Christmas for Kids is ready to use</p>
        </div>

        <div class="content">
            <div class="success-message">
                <h2>Congratulations!</h2>
                <p>Your Christmas for Kids sponsorship system has been successfully installed and configured.</p>
            </div>

            <div class="info-box">
                <h3>How to Access the Admin Panel:</h3>
                <ol>
                    <li>Click the button below to go to the admin login page</li>
                    <li>Enter your email address: <strong><?= htmlspecialchars($admin_email) ?></strong></li>
                    <li>Check your email for a magic link (it expires in 5 minutes)</li>
                    <li>Click the magic link to log in - no password needed!</li>
                </ol>
            </div>

            <div class="alert warning">
                <strong>Security Note:</strong> The installer has been disabled. To run it again, you must delete the <code>.installed</code> file from your server.
            </div>

            <div class="info-box">
                <h3>Next Steps:</h3>
                <ol>
                    <li>Log in to the admin panel</li>
                    <li>Upload child profiles (or import via CSV)</li>
                    <li>Configure your site settings</li>
                    <li>Test the sponsorship workflow</li>
                </ol>
            </div>

            <a href="<?= htmlspecialchars($base_url) ?>admin/login" class="btn">
                Go to Admin Login →
            </a>
        </div>
    </div>
</body>
</html>
