<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?> - Installation</title>
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
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
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
        .welcome-text {
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            margin-bottom: 30px;
        }
        .features {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .features h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #667eea;
        }
        .features ul {
            list-style: none;
        }
        .features li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
            color: #555;
        }
        .features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
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
        .version {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars($app_name) ?></h1>
            <p>Installation Wizard</p>
        </div>

        <div class="content">
            <div class="welcome-text">
                <p><strong>Welcome!</strong> This wizard will help you set up your Christmas for Kids sponsorship system in just a few minutes.</p>
            </div>

            <div class="features">
                <h3>What This Installer Will Do:</h3>
                <ul>
                    <li>Check your server environment for compatibility</li>
                    <li>Create and configure your database</li>
                    <li>Set up your site configuration</li>
                    <li>Create your first admin account</li>
                    <li>Secure your installation</li>
                </ul>
            </div>

            <div class="welcome-text">
                <p><strong>Before you begin,</strong> please make sure you have:</p>
                <ul style="margin-left: 20px; margin-top: 10px; color: #555;">
                    <li style="margin-bottom: 8px;">Created a MySQL database</li>
                    <li style="margin-bottom: 8px;">Database username and password</li>
                    <li style="margin-bottom: 8px;">A valid email address for admin access</li>
                </ul>
            </div>

            <a href="install.php?step=environment" class="btn">Get Started</a>

            <div class="version">Version <?= htmlspecialchars($version) ?></div>
        </div>
    </div>
</body>
</html>
