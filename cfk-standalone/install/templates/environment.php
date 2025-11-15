<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Environment Check - Installation</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .check-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .check-item:last-child {
            border-bottom: none;
        }
        .check-status {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 16px;
            flex-shrink: 0;
        }
        .check-status.pass {
            background: #28a745;
            color: white;
        }
        .check-status.fail {
            background: #dc3545;
            color: white;
        }
        .check-status.warning {
            background: #ffc107;
            color: #333;
        }
        .check-info {
            flex: 1;
        }
        .check-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }
        .check-value {
            font-size: 14px;
            color: #666;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
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
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .btn-secondary {
            background: #6c757d;
            margin-right: 10px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        #message {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Environment Check</h1>
            <p>Step 1 of 5</p>
        </div>

        <div class="content">
            <div id="message"></div>

            <?php if ($can_proceed): ?>
                <div class="alert success">
                    <strong>Great!</strong> Your server meets all the requirements to run Christmas for Kids.
                </div>
            <?php else: ?>
                <div class="alert error">
                    <strong>Action Required:</strong> Please fix the failed requirements below before continuing.
                </div>
            <?php endif; ?>

            <div style="margin: 20px 0;">
                <?php foreach ($checks as $key => $check):
                    if ($key === 'has_errors') continue;
                    $statusClass = $check['pass'] ? 'pass' : ($check['required'] ? 'fail' : 'warning');
                    $statusIcon = $check['pass'] ? '✓' : '✗';
                ?>
                    <div class="check-item">
                        <div class="check-status <?= $statusClass ?>">
                            <?= $statusIcon ?>
                        </div>
                        <div class="check-info">
                            <div class="check-label"><?= htmlspecialchars($check['label']) ?></div>
                            <div class="check-value"><?= htmlspecialchars($check['value']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="btn-group">
                <a href="install.php?step=welcome" class="btn btn-secondary" style="width: auto;">← Back</a>
                <button
                    class="btn"
                    onclick="proceedToNext()"
                    <?= !$can_proceed ? 'disabled' : '' ?>
                >
                    Continue to Database Setup →
                </button>
            </div>
        </div>
    </div>

    <script>
        async function proceedToNext() {
            const messageEl = document.getElementById('message');

            try {
                const response = await fetch('install.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=check_environment'
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = result.data.redirect;
                } else {
                    messageEl.className = 'alert error';
                    messageEl.textContent = result.message;
                    messageEl.style.display = 'block';
                }
            } catch (error) {
                messageEl.className = 'alert error';
                messageEl.textContent = 'An error occurred. Please try again.';
                messageEl.style.display = 'block';
            }
        }
    </script>
</body>
</html>
