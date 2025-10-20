<?php
declare(strict_types=1);

/**
 * Diagnostic Script for Year-End Reset Form
 * Tests form submission detection
 */

define('CFK_APP', true);
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Submission Test</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-box {
            background: white;
            border: 2px solid #333;
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
        }
        .debug-box h3 {
            margin-top: 0;
            color: #2c5530;
        }
        pre {
            background: #f0f0f0;
            padding: 10px;
            overflow-x: auto;
            border-left: 4px solid #2c5530;
        }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        input, button {
            display: block;
            margin: 10px 0;
            padding: 8px;
            font-size: 14px;
        }
        button {
            background: #2c5530;
            color: white;
            border: none;
            padding: 12px 24px;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background: #1e3b20;
        }
    </style>
</head>
<body>
    <h1>Year-End Reset Form Diagnostic</h1>

    <div class="debug-box">
        <h3>1. Request Information</h3>
        <pre><?php
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET') . "\n";
echo "CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
        ?></pre>
    </div>

    <div class="debug-box">
        <h3>2. POST Data</h3>
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <p class="success">✓ POST request detected</p>
            <pre><?php print_r($_POST); ?></pre>
        <?php else: ?>
            <p class="error">✗ Not a POST request (GET)</p>
            <pre>$_POST is empty (GET request)</pre>
        <?php endif; ?>
    </div>

    <div class="debug-box">
        <h3>3. Form Submission Test</h3>
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_submit'])): ?>
            <p class="success">✓ Form submission detected!</p>
            <pre><?php
echo "Year: " . ($_POST['year'] ?? 'NOT SET') . "\n";
echo "Code: " . ($_POST['confirmation_code'] ?? 'NOT SET') . "\n";
echo "CSRF Token: " . (isset($_POST['csrf_token']) ? 'PRESENT' : 'MISSING') . "\n";
echo "Submit button: " . (isset($_POST['test_submit']) ? 'PRESENT' : 'MISSING') . "\n";
            ?></pre>
        <?php else: ?>
            <p>Fill out and submit the form below to test:</p>
        <?php endif; ?>
    </div>

    <div class="debug-box">
        <h3>4. Test Form</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <label for="year">Year:</label>
            <input type="text" id="year" name="year" value="2024" required>

            <label for="confirmation_code">Confirmation Code:</label>
            <input type="text" id="confirmation_code" name="confirmation_code" value="RESET 2024" required>

            <button type="submit" name="test_submit" value="1">Submit Test Form</button>
        </form>
    </div>

    <div class="debug-box">
        <h3>5. CFK_Archive_Manager Class Test</h3>
        <?php
        require_once __DIR__ . '/../includes/archive_manager.php';
        if (class_exists('CFK_Archive_Manager')) {
            echo '<p class="success">✓ CFK_Archive_Manager class is loaded</p>';
            echo '<pre>';
            echo "Available methods:\n";
            $methods = get_class_methods('CFK_Archive_Manager');
            foreach ($methods as $method) {
                echo "  - {$method}\n";
            }
            echo '</pre>';
        } else {
            echo '<p class="error">✗ CFK_Archive_Manager class NOT found</p>';
        }
        ?>
    </div>

    <div class="debug-box">
        <h3>6. Database Connection Test</h3>
        <?php
        try {
            $testQuery = Database::fetchRow("SELECT COUNT(*) as count FROM children");
            echo '<p class="success">✓ Database connection working</p>';
            echo '<pre>Children count: ' . $testQuery['count'] . '</pre>';
        } catch (Exception $e) {
            echo '<p class="error">✗ Database error: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>

    <div class="debug-box">
        <h3>7. Archives Directory Test</h3>
        <?php
        $archivesDir = __DIR__ . '/../archives';
        if (is_dir($archivesDir)) {
            echo '<p class="success">✓ Archives directory exists</p>';
            echo '<pre>';
            echo "Path: {$archivesDir}\n";
            echo "Writable: " . (is_writable($archivesDir) ? 'YES' : 'NO') . "\n";
            echo "Permissions: " . substr(sprintf('%o', fileperms($archivesDir)), -4) . "\n";
            echo '</pre>';
        } else {
            echo '<p class="error">✗ Archives directory does NOT exist</p>';
        }
        ?>
    </div>

</body>
</html>
