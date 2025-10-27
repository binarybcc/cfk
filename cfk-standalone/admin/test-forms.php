<?php

declare(strict_types=1);

/**
 * Diagnostic Page - Test Form Submission
 * STAGING ONLY - Remove before production
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Only on staging
if (config('environment') !== 'staging') {
    die('Staging only');
}

// Check if logged in
if (!isLoggedIn()) {
    header('Location: staging-login.php');
    exit;
}

$testResult = '';

// Handle test form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testResult = '<h3>POST Data Received:</h3>';
    $testResult .= '<pre>' . print_r($_POST, true) . '</pre>';

    $testResult .= '<h3>CSRF Token Check:</h3>';
    if (isset($_POST['csrf_token'])) {
        $isValid = verifyCsrfToken($_POST['csrf_token']);
        $testResult .= $isValid ? '✅ CSRF Token VALID' : '❌ CSRF Token INVALID';
    } else {
        $testResult .= '❌ No CSRF token in POST data';
    }

    $testResult .= '<h3>Session Data:</h3>';
    $testResult .= '<pre>' . print_r($_SESSION, true) . '</pre>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Test - Staging</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        form {
            margin: 20px 0;
            padding: 20px;
            background: #e3f2fd;
            border-radius: 4px;
        }
        button {
            padding: 10px 20px;
            background: #2c5530;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #1f3d22;
        }
        .result {
            margin-top: 20px;
            padding: 20px;
            background: #fff3cd;
            border-radius: 4px;
        }
        h1 { color: #2c5530; }
        h3 { margin-top: 20px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Form Submission Test (Staging)</h1>

        <h2>Session Status</h2>
        <pre>Logged in: <?php echo isLoggedIn() ? 'YES ✅' : 'NO ❌'; ?>
Admin ID: <?php echo $_SESSION['cfk_admin_id'] ?? 'Not set'; ?>
Username: <?php echo $_SESSION['cfk_admin_username'] ?? 'Not set'; ?>
Session ID: <?php echo session_id(); ?>
CSRF Token (current): <?php echo generateCsrfToken(); ?></pre>

        <h2>Test Form Submission</h2>
        <p>Click the button below to test if forms are working:</p>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="test">
            <input type="hidden" name="test_id" value="123">
            <button type="submit">Submit Test Form</button>
        </form>

        <?php if ($testResult): ?>
            <div class="result">
                <?php echo $testResult; ?>
            </div>
        <?php endif; ?>

        <h2>Sponsorship Manager Test</h2>
        <?php
        use CFK\Sponsorship\Manager as SponsorshipManager;

        $methods = get_class_methods(SponsorshipManager::class);
        echo '<pre>';
        echo 'SponsorshipManager class loaded: ' . (class_exists('CFK\Sponsorship\Manager') ? 'YES ✅' : 'NO ❌') . PHP_EOL;
        echo 'Available methods: ' . count($methods) . PHP_EOL;
        echo 'Methods: ' . implode(', ', array_slice($methods, 0, 5)) . '...' . PHP_EOL;
        echo '</pre>';
        ?>

        <h2>Database Test</h2>
        <?php
        $sponsorships = Database::fetchAll("SELECT id, status FROM sponsorships LIMIT 5");
        echo '<pre>';
        echo 'Sponsorships in DB: ' . count($sponsorships) . PHP_EOL;
        foreach ($sponsorships as $s) {
            echo "  ID: {$s['id']}, Status: {$s['status']}" . PHP_EOL;
        }
        echo '</pre>';
        ?>

        <p><a href="manage_sponsorships.php">← Back to Manage Sponsorships</a></p>
        <p><a href="manage_children.php">← Back to Manage Children</a></p>
    </div>
</body>
</html>
