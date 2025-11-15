<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account Setup - Installation</title>
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
        .alert.warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
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
        }
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        #message {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Create Admin Account</h1>
            <p>Step 4 of 5</p>
        </div>

        <div class="content">
            <div id="message"></div>

            <div class="alert warning">
                <strong>Important: Passwordless Authentication</strong><br>
                This system uses magic link authentication. You will log in by receiving a secure link via email - no passwords needed!
            </div>

            <div class="alert info">
                <strong>Make sure you can receive email at this address!</strong> You'll need to click a magic link sent to this email every time you log in.
            </div>

            <form id="adminForm">
                <div class="form-group">
                    <label for="admin_name">Your Full Name</label>
                    <input
                        type="text"
                        id="admin_name"
                        name="admin_name"
                        required
                        placeholder="John Doe"
                    >
                    <div class="form-hint">This will be displayed in the admin panel</div>
                </div>

                <div class="form-group">
                    <label for="admin_email">Admin Email Address</label>
                    <input
                        type="email"
                        id="admin_email"
                        name="admin_email"
                        required
                        placeholder="admin@example.com"
                    >
                    <div class="form-hint">Magic links will be sent to this address</div>
                </div>

                <div class="form-group">
                    <label for="admin_email_confirm">Confirm Email Address</label>
                    <input
                        type="email"
                        id="admin_email_confirm"
                        name="admin_email_confirm"
                        required
                        placeholder="admin@example.com"
                    >
                    <div class="form-hint">Re-enter your email to confirm</div>
                </div>

                <button
                    type="button"
                    class="btn"
                    onclick="createAdmin()"
                >
                    Create Admin Account →
                </button>
            </form>
        </div>
    </div>

    <script>
        // Pre-populate from sessionStorage if available
        window.addEventListener('load', () => {
            const adminEmail = sessionStorage.getItem('admin_email');
            if (adminEmail) {
                document.getElementById('admin_email').value = adminEmail;
            }
        });

        async function createAdmin() {
            const messageEl = document.getElementById('message');
            const form = document.getElementById('adminForm');
            const submitBtn = form.querySelector('button');

            messageEl.style.display = 'none';

            // Get form values
            const adminName = document.getElementById('admin_name').value.trim();
            const adminEmail = document.getElementById('admin_email').value.trim();
            const adminEmailConfirm = document.getElementById('admin_email_confirm').value.trim();

            // Validate
            if (!adminName || !adminEmail || !adminEmailConfirm) {
                messageEl.className = 'alert error';
                messageEl.textContent = 'Please fill in all fields.';
                messageEl.style.display = 'block';
                return;
            }

            if (adminEmail !== adminEmailConfirm) {
                messageEl.className = 'alert error';
                messageEl.textContent = 'Email addresses do not match.';
                messageEl.style.display = 'block';
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating Account...';

            // Prepare form data with all config values
            const formData = new FormData();
            formData.append('action', 'create_admin');
            formData.append('admin_name', adminName);
            formData.append('admin_email', adminEmail);
            formData.append('admin_email_confirm', adminEmailConfirm);
            formData.append('base_url', sessionStorage.getItem('base_url') || '');
            formData.append('admin_email_config', sessionStorage.getItem('admin_email') || adminEmail);
            formData.append('smtp_host', sessionStorage.getItem('smtp_host') || '');
            formData.append('smtp_port', sessionStorage.getItem('smtp_port') || '587');
            formData.append('smtp_user', sessionStorage.getItem('smtp_user') || '');
            formData.append('smtp_pass', sessionStorage.getItem('smtp_pass') || '');

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

                    setTimeout(() => {
                        window.location.href = result.data.redirect;
                    }, 1000);
                } else {
                    messageEl.className = 'alert error';
                    messageEl.textContent = result.message;
                    messageEl.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Create Admin Account →';
                }
            } catch (error) {
                messageEl.className = 'alert error';
                messageEl.textContent = 'An error occurred. Please try again.';
                messageEl.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create Admin Account →';
            }
        }
    </script>
</body>
</html>
