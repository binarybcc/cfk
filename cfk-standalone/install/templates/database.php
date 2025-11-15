<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Installation</title>
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
        }
        .btn-outline {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        #message {
            display: none;
        }
        #installBtn {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Database Configuration</h1>
            <p>Step 2 of 5</p>
        </div>

        <div class="content">
            <div id="message"></div>

            <div class="alert info">
                <strong>Important:</strong> You must create the database before proceeding. This installer will create the tables, but the database itself must already exist.
            </div>

            <form id="dbForm">
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input
                        type="text"
                        id="db_host"
                        name="db_host"
                        value="<?= htmlspecialchars($db_host) ?>"
                        required
                    >
                    <div class="form-hint">Usually "localhost"</div>
                </div>

                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input
                        type="text"
                        id="db_name"
                        name="db_name"
                        value="<?= htmlspecialchars($db_name) ?>"
                        required
                    >
                    <div class="form-hint">The name of the database you created</div>
                </div>

                <div class="form-group">
                    <label for="db_user">Database Username</label>
                    <input
                        type="text"
                        id="db_user"
                        name="db_user"
                        value="<?= htmlspecialchars($db_user) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input
                        type="password"
                        id="db_pass"
                        name="db_pass"
                        placeholder="Leave empty if no password"
                    >
                </div>

                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-outline"
                        onclick="testConnection()"
                    >
                        Test Connection
                    </button>
                    <button
                        type="button"
                        id="installBtn"
                        class="btn"
                        onclick="installDatabase()"
                    >
                        Install Database →
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function testConnection() {
            const messageEl = document.getElementById('message');
            const form = document.getElementById('dbForm');
            const formData = new FormData(form);
            formData.append('action', 'test_database');

            messageEl.style.display = 'none';

            try {
                const response = await fetch('install.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    messageEl.className = 'alert success';
                    messageEl.textContent = result.message;
                    messageEl.style.display = 'block';
                    document.getElementById('installBtn').style.display = 'inline-block';
                } else {
                    messageEl.className = 'alert error';
                    messageEl.textContent = result.message;
                    messageEl.style.display = 'block';
                    document.getElementById('installBtn').style.display = 'none';
                }
            } catch (error) {
                messageEl.className = 'alert error';
                messageEl.textContent = 'An error occurred. Please try again.';
                messageEl.style.display = 'block';
            }
        }

        async function installDatabase() {
            const messageEl = document.getElementById('message');
            const installBtn = document.getElementById('installBtn');

            installBtn.disabled = true;
            installBtn.textContent = 'Installing...';

            try {
                const response = await fetch('install.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=install_database'
                });

                const result = await response.json();

                if (result.success) {
                    messageEl.className = 'alert success';
                    messageEl.textContent = result.message;
                    messageEl.style.display = 'block';

                    setTimeout(() => {
                        window.location.href = result.data.redirect;
                    }, 1000);
                } else {
                    messageEl.className = 'alert error';
                    messageEl.textContent = result.message;
                    messageEl.style.display = 'block';
                    installBtn.disabled = false;
                    installBtn.textContent = 'Install Database →';
                }
            } catch (error) {
                messageEl.className = 'alert error';
                messageEl.textContent = 'An error occurred. Please try again.';
                messageEl.style.display = 'block';
                installBtn.disabled = false;
                installBtn.textContent = 'Install Database →';
            }
        }
    </script>
</body>
</html>
