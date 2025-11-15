<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Configuration - Installation</title>
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
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }
        .form-section:last-of-type {
            border-bottom: none;
        }
        .form-section h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-hint {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
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
        }
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Site Configuration</h1>
            <p>Step 3 of 5</p>
        </div>

        <div class="content">
            <div class="alert info">
                <strong>Note:</strong> These settings will be saved to your .env file. You can change them later if needed.
            </div>

            <form id="configForm" onsubmit="return false;">
                <input type="hidden" name="action" value="save_config">

                <div class="form-section">
                    <h3>Site Information</h3>

                    <div class="form-group">
                        <label for="base_url">Site URL</label>
                        <input
                            type="url"
                            id="base_url"
                            name="base_url"
                            value="<?= htmlspecialchars($base_url) ?>"
                            required
                        >
                        <div class="form-hint">Your site's full URL (with trailing slash)</div>
                    </div>

                    <div class="form-group">
                        <label for="admin_email">Admin Email Address</label>
                        <input
                            type="email"
                            id="admin_email"
                            name="admin_email"
                            value="<?= htmlspecialchars($admin_email) ?>"
                            required
                        >
                        <div class="form-hint">System notifications will be sent to this address</div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Email Configuration (Optional)</h3>

                    <div class="form-group">
                        <label for="smtp_host">SMTP Host</label>
                        <input
                            type="text"
                            id="smtp_host"
                            name="smtp_host"
                            value="<?= htmlspecialchars($smtp_host) ?>"
                        >
                        <div class="form-hint">Leave default for MailChannels (Nexcess hosting)</div>
                    </div>

                    <div class="form-group">
                        <label for="smtp_port">SMTP Port</label>
                        <input
                            type="number"
                            id="smtp_port"
                            name="smtp_port"
                            value="<?= htmlspecialchars($smtp_port) ?>"
                        >
                        <div class="form-hint">Usually 587 for TLS or 465 for SSL</div>
                    </div>

                    <div class="form-group">
                        <label for="smtp_user">SMTP Username (Optional)</label>
                        <input
                            type="text"
                            id="smtp_user"
                            name="smtp_user"
                            value=""
                        >
                    </div>

                    <div class="form-group">
                        <label for="smtp_pass">SMTP Password (Optional)</label>
                        <input
                            type="password"
                            id="smtp_pass"
                            name="smtp_pass"
                            value=""
                        >
                    </div>
                </div>

                <button type="button" class="btn" onclick="saveAndContinue()">
                    Continue to Admin Setup →
                </button>
            </form>
        </div>
    </div>

    <script>
        function saveAndContinue() {
            // Store in session and continue
            sessionStorage.setItem('base_url', document.getElementById('base_url').value);
            sessionStorage.setItem('admin_email', document.getElementById('admin_email').value);
            sessionStorage.setItem('smtp_host', document.getElementById('smtp_host').value);
            sessionStorage.setItem('smtp_port', document.getElementById('smtp_port').value);
            sessionStorage.setItem('smtp_user', document.getElementById('smtp_user').value);
            sessionStorage.setItem('smtp_pass', document.getElementById('smtp_pass').value);

            window.location.href = 'install.php?step=admin';
        }
    </script>
</body>
</html>
