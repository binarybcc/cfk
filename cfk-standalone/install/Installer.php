<?php
/**
 * CFK Sponsorship System - Installer Class
 *
 * Handles the complete installation process including:
 * - Environment checks
 * - Database setup
 * - Configuration file creation
 * - First admin account creation
 *
 * @package CFK\Install
 * @version 1.9.4
 */

declare(strict_types=1);

namespace CFK\Install;

use PDO;
use PDOException;
use Exception;

class Installer
{
    private const REQUIRED_PHP_VERSION = '8.1.0';
    private const REQUIRED_EXTENSIONS = [
        'pdo',
        'pdo_mysql',
        'mbstring',
        'json',
        'session',
        'openssl'
    ];

    private const WRITABLE_DIRECTORIES = [
        'uploads',
        'uploads/photos'
    ];

    private array $errors = [];
    private array $warnings = [];

    /**
     * Render installer step
     */
    public function render(string $step): void
    {
        $method = 'render' . ucfirst($step);

        if (!method_exists($this, $method)) {
            $this->renderWelcome();
            return;
        }

        $this->$method();
    }

    /**
     * Handle POST requests
     */
    public function handlePost(): void
    {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'check_environment':
                $this->handleEnvironmentCheck();
                break;
            case 'test_database':
                $this->handleDatabaseTest();
                break;
            case 'install_database':
                $this->handleDatabaseInstall();
                break;
            case 'create_admin':
                $this->handleCreateAdmin();
                break;
            case 'finish':
                $this->handleFinish();
                break;
            default:
                $this->jsonResponse(false, 'Invalid action');
        }
    }

    /**
     * Render welcome screen
     */
    private function renderWelcome(): void
    {
        $this->renderTemplate('welcome', [
            'app_name' => 'Christmas for Kids',
            'version' => '1.9.4'
        ]);
    }

    /**
     * Render environment check screen
     */
    private function renderEnvironment(): void
    {
        $checks = $this->performEnvironmentChecks();

        $this->renderTemplate('environment', [
            'checks' => $checks,
            'can_proceed' => !$checks['has_errors']
        ]);
    }

    /**
     * Render database setup screen
     */
    private function renderDatabase(): void
    {
        $this->renderTemplate('database', [
            'db_host' => $_SESSION['install']['db_host'] ?? 'localhost',
            'db_name' => $_SESSION['install']['db_name'] ?? '',
            'db_user' => $_SESSION['install']['db_user'] ?? '',
        ]);
    }

    /**
     * Render configuration screen
     */
    private function renderConfig(): void
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $defaultUrl = $protocol . '://' . $host . '/';

        $this->renderTemplate('config', [
            'base_url' => $_SESSION['install']['base_url'] ?? $defaultUrl,
            'admin_email' => $_SESSION['install']['admin_email'] ?? '',
            'smtp_host' => $_SESSION['install']['smtp_host'] ?? 'relay.mailchannels.net',
            'smtp_port' => $_SESSION['install']['smtp_port'] ?? '587',
        ]);
    }

    /**
     * Render admin account creation screen
     */
    private function renderAdmin(): void
    {
        $this->renderTemplate('admin', [
            'admin_email' => $_SESSION['install']['admin_email'] ?? '',
        ]);
    }

    /**
     * Render completion screen
     */
    private function renderComplete(): void
    {
        $this->renderTemplate('complete', [
            'admin_email' => $_SESSION['install']['admin_email'] ?? '',
            'base_url' => $_SESSION['install']['base_url'] ?? '/',
        ]);
    }

    /**
     * Perform environment checks
     */
    private function performEnvironmentChecks(): array
    {
        $checks = [
            'php_version' => [
                'label' => 'PHP Version (>= ' . self::REQUIRED_PHP_VERSION . ')',
                'pass' => version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION, '>='),
                'value' => PHP_VERSION,
                'required' => true
            ]
        ];

        // Check required extensions
        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $checks['ext_' . $ext] = [
                'label' => 'PHP Extension: ' . $ext,
                'pass' => extension_loaded($ext),
                'value' => extension_loaded($ext) ? 'Loaded' : 'Missing',
                'required' => true
            ];
        }

        // Check writable directories
        foreach (self::WRITABLE_DIRECTORIES as $dir) {
            $fullPath = __DIR__ . '/../' . $dir;
            $isWritable = is_dir($fullPath) && is_writable($fullPath);

            $checks['dir_' . str_replace('/', '_', $dir)] = [
                'label' => 'Directory writable: ' . $dir,
                'pass' => $isWritable,
                'value' => $isWritable ? 'Writable' : 'Not writable',
                'required' => true
            ];
        }

        // Check if .env file exists (warning, not error)
        $envExists = file_exists(__DIR__ . '/../.env');
        $checks['env_file'] = [
            'label' => '.env file',
            'pass' => !$envExists, // We want it NOT to exist
            'value' => $envExists ? 'Already exists (will be overwritten)' : 'Not found (will be created)',
            'required' => false
        ];

        // Determine if there are any errors
        $hasErrors = false;
        foreach ($checks as $check) {
            if ($check['required'] && !$check['pass']) {
                $hasErrors = true;
                break;
            }
        }

        $checks['has_errors'] = $hasErrors;

        return $checks;
    }

    /**
     * Handle environment check
     */
    private function handleEnvironmentCheck(): void
    {
        $checks = $this->performEnvironmentChecks();

        if ($checks['has_errors']) {
            $this->jsonResponse(false, 'Environment check failed. Please fix the errors and try again.');
        } else {
            $_SESSION['install']['environment_checked'] = true;
            $this->jsonResponse(true, 'Environment check passed!', ['redirect' => 'install.php?step=database']);
        }
    }

    /**
     * Handle database connection test
     */
    private function handleDatabaseTest(): void
    {
        $host = $_POST['db_host'] ?? '';
        $name = $_POST['db_name'] ?? '';
        $user = $_POST['db_user'] ?? '';
        $pass = $_POST['db_pass'] ?? '';

        // Validate inputs
        if (empty($host) || empty($name) || empty($user)) {
            $this->jsonResponse(false, 'All database fields are required (password can be empty).');
            return;
        }

        try {
            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);

            // Store credentials in session
            $_SESSION['install']['db_host'] = $host;
            $_SESSION['install']['db_name'] = $name;
            $_SESSION['install']['db_user'] = $user;
            $_SESSION['install']['db_pass'] = $pass;

            $this->jsonResponse(true, 'Database connection successful!');
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle database installation
     */
    private function handleDatabaseInstall(): void
    {
        if (!isset($_SESSION['install']['db_host'])) {
            $this->jsonResponse(false, 'Database credentials not found. Please test connection first.');
            return;
        }

        try {
            $pdo = $this->getDatabaseConnection();

            // Read schema file
            $schemaFile = __DIR__ . '/schema.sql';
            if (!file_exists($schemaFile)) {
                $this->jsonResponse(false, 'Schema file not found.');
                return;
            }

            $schema = file_get_contents($schemaFile);

            // Split into individual statements and execute
            $statements = array_filter(
                array_map('trim', explode(';', $schema)),
                fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
            );

            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }

            $_SESSION['install']['database_installed'] = true;

            $this->jsonResponse(true, 'Database tables created successfully!', [
                'redirect' => 'install.php?step=config'
            ]);
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Database installation failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle create admin account
     */
    private function handleCreateAdmin(): void
    {
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminEmailConfirm = trim($_POST['admin_email_confirm'] ?? '');
        $adminName = trim($_POST['admin_name'] ?? '');
        $baseUrl = rtrim(trim($_POST['base_url'] ?? ''), '/') . '/';
        $adminEmailConfig = trim($_POST['admin_email_config'] ?? '');
        $smtpHost = trim($_POST['smtp_host'] ?? '');
        $smtpPort = trim($_POST['smtp_port'] ?? '587');
        $smtpUser = trim($_POST['smtp_user'] ?? '');
        $smtpPass = trim($_POST['smtp_pass'] ?? '');

        // Validate
        if (empty($adminEmail) || empty($adminName)) {
            $this->jsonResponse(false, 'Admin email and name are required.');
            return;
        }

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(false, 'Please provide a valid email address.');
            return;
        }

        if ($adminEmail !== $adminEmailConfirm) {
            $this->jsonResponse(false, 'Email addresses do not match.');
            return;
        }

        if (empty($baseUrl)) {
            $this->jsonResponse(false, 'Base URL is required.');
            return;
        }

        try {
            $pdo = $this->getDatabaseConnection();

            // Create admin user (password is random placeholder, not used for magic link auth)
            $randomHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                INSERT INTO admin_users (username, email, password_hash, full_name, role, created_at)
                VALUES (:username, :email, :password_hash, :full_name, 'admin', NOW())
            ");

            $stmt->execute([
                'username' => 'admin',
                'email' => $adminEmail,
                'password_hash' => $randomHash,
                'full_name' => $adminName
            ]);

            // Store configuration
            $_SESSION['install']['admin_email'] = $adminEmail;
            $_SESSION['install']['admin_name'] = $adminName;
            $_SESSION['install']['base_url'] = $baseUrl;
            $_SESSION['install']['admin_email_config'] = $adminEmailConfig ?: $adminEmail;
            $_SESSION['install']['smtp_host'] = $smtpHost;
            $_SESSION['install']['smtp_port'] = $smtpPort;
            $_SESSION['install']['smtp_user'] = $smtpUser;
            $_SESSION['install']['smtp_pass'] = $smtpPass;

            // Create .env file
            $this->createEnvFile();

            // Create uploads directories if they don't exist
            $this->createDirectories();

            $_SESSION['install']['admin_created'] = true;

            $this->jsonResponse(true, 'Admin account created successfully!', [
                'redirect' => 'install.php?step=complete'
            ]);
        } catch (PDOException $e) {
            $this->jsonResponse(false, 'Failed to create admin account: ' . $e->getMessage());
        }
    }

    /**
     * Create .env configuration file
     */
    private function createEnvFile(): void
    {
        $envContent = <<<ENV
# CFK Sponsorship System - Environment Configuration
# Generated by installer on {date}
# DO NOT commit this file to version control!

# Database Configuration
DB_HOST={$_SESSION['install']['db_host']}
DB_NAME={$_SESSION['install']['db_name']}
DB_USER={$_SESSION['install']['db_user']}
DB_PASSWORD={$_SESSION['install']['db_pass']}

# Application Configuration
BASE_URL={$_SESSION['install']['base_url']}
APP_DEBUG=false

# Email Configuration
ADMIN_EMAIL={$_SESSION['install']['admin_email_config']}
SMTP_HOST={$_SESSION['install']['smtp_host']}
SMTP_PORT={$_SESSION['install']['smtp_port']}
SMTP_USERNAME={$_SESSION['install']['smtp_user']}
SMTP_PASSWORD={$_SESSION['install']['smtp_pass']}
ENV;

        $envContent = str_replace('{date}', date('Y-m-d H:i:s'), $envContent);

        file_put_contents(__DIR__ . '/../.env', $envContent);
        chmod(__DIR__ . '/../.env', 0600); // Secure permissions
    }

    /**
     * Create required directories
     */
    private function createDirectories(): void
    {
        foreach (self::WRITABLE_DIRECTORIES as $dir) {
            $fullPath = __DIR__ . '/../' . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }
    }

    /**
     * Handle installation finish
     */
    private function handleFinish(): void
    {
        if (!isset($_SESSION['install']['admin_created'])) {
            $this->jsonResponse(false, 'Installation not complete.');
            return;
        }

        // Create .installed lock file
        file_put_contents(__DIR__ . '/../.installed', date('Y-m-d H:i:s'));

        // Clear installation session
        unset($_SESSION['install']);

        $this->jsonResponse(true, 'Installation completed successfully!', [
            'redirect' => 'admin/login'
        ]);
    }

    /**
     * Get database connection from session
     */
    private function getDatabaseConnection(): PDO
    {
        $host = $_SESSION['install']['db_host'];
        $name = $_SESSION['install']['db_name'];
        $user = $_SESSION['install']['db_user'];
        $pass = $_SESSION['install']['db_pass'];

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
    }

    /**
     * Send JSON response
     */
    private function jsonResponse(bool $success, string $message, array $data = []): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    /**
     * Render HTML template
     */
    private function renderTemplate(string $template, array $data = []): void
    {
        extract($data);
        ob_start();
        require __DIR__ . '/templates/' . $template . '.php';
        $content = ob_get_clean();
        echo $content;
    }
}
