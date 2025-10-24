<?php
declare(strict_types=1);

/**
 * Christmas for Kids - Configuration File
 * Main configuration settings for the standalone PHP application
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Load Composer autoloader (for namespaced classes)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = parse_ini_file(__DIR__ . '/../.env');
    if ($envFile) {
        foreach ($envFile as $key => $value) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Environment Detection Function
function detectEnvironment(): string {
    // Allow manual override via .env file
    $envOverride = getenv('ENVIRONMENT');
    if ($envOverride && in_array($envOverride, ['local', 'staging', 'production'], true)) {
        return $envOverride;
    }

    // Auto-detect based on hostname
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Staging detection (customize these patterns for your Nexcess staging URL)
    if (str_contains($host, 'staging') || str_contains($host, 'stage') || str_contains($host, '.test')) {
        return 'staging';
    }

    // Production detection
    if ($host === 'cforkids.org' || $host === 'www.cforkids.org') {
        return 'production';
    }

    // Local development (localhost, .local, or with port numbers)
    return 'local';
}

// Detect current environment
$environment = detectEnvironment();
$isProduction = ($environment === 'production');
$isStaging = ($environment === 'staging');

// Database Configuration - USE ENVIRONMENT VARIABLES
$dbConfig = [
    'host' => getenv('DB_HOST') ?: ($isProduction ? 'localhost' : 'db'),
    'database' => getenv('DB_NAME') ?: ($isProduction ? 'a4409d26_509946' : 'cfk_sponsorship_dev'),
    'username' => getenv('DB_USER') ?: ($isProduction ? 'a4409d26_509946' : 'root'),
    'password' => getenv('DB_PASSWORD') ?: ($isProduction ? '' : 'root')
];

// Application Settings
$appConfig = [
    'app_name' => 'Christmas for Kids',
    'app_version' => '1.8',
    'environment' => $environment,
    'timezone' => 'America/New_York',
    'debug' => getenv('APP_DEBUG') === 'true' || (!$isProduction && !$isStaging),

    // Paths - prioritize .env, then auto-detect
    'base_url' => getenv('BASE_URL') ?: match($environment) {
        'production' => 'https://cforkids.org/',
        'staging' => 'https://staging.cforkids.org/', // Update with your actual staging URL
        default => 'http://localhost:8082/',
    },
    'upload_path' => __DIR__ . '/../uploads/',
    'photo_path' => __DIR__ . '/../uploads/photos/',
    
    // Pagination
    'children_per_page' => 12,
    'admin_items_per_page' => 25,
    
    // Features
    'enable_registration' => true,
    'require_admin_approval' => true,
    'max_pending_hours' => 48,
    
    // Email settings
    'admin_email' => 'christmasforkids@upstatetoday.com',
    'from_email' => 'noreply@cforkids.org',
    'from_name' => 'Christmas for Kids',

    // SMTP Configuration (MailChannels via Nexcess)
    'email_use_smtp' => ($isProduction || $isStaging), // Use SMTP in production/staging, sendmail in local
    'smtp_host' => getenv('SMTP_HOST') ?: 'relay.mailchannels.net',
    'smtp_port' => (int)(getenv('SMTP_PORT') ?: 587),
    'smtp_auth' => true,
    'smtp_username' => getenv('SMTP_USERNAME') ?: '', // Set in .env file
    'smtp_password' => getenv('SMTP_PASSWORD') ?: '', // Set in .env file
    'smtp_encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls', // TLS encryption on port 587
    
    // Security
    'session_name' => 'CFK_SESSION',
    'csrf_token_name' => 'cfk_csrf_token',
    'password_min_length' => 8,
    
    // Upload limits
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'allowed_photo_types' => ['jpg', 'jpeg', 'png', 'gif'],
    'photo_max_width' => 800,
    'photo_max_height' => 600,
];

// Age categories for filtering
$ageCategories = [
    'birth_to_4' => ['label' => 'Birth to 4', 'min' => 0, 'max' => 4],
    'elementary' => ['label' => 'Elementary (5-10)', 'min' => 5, 'max' => 10],
    'middle_school' => ['label' => 'Middle School (11-13)', 'min' => 11, 'max' => 13],
    'high_school' => ['label' => 'High School (14-18)', 'min' => 14, 'max' => 18]
];

// Status options for children
$childStatusOptions = [
    'available' => 'Available for Sponsorship',
    'pending' => 'Sponsorship Pending',
    'sponsored' => 'Already Sponsored',
    'inactive' => 'Not Available'
];

// Sponsorship status options
$sponsorshipStatusOptions = [
    'pending' => 'Pending Confirmation',
    'confirmed' => 'Confirmed',
    'completed' => 'Gift Delivered',
    'cancelled' => 'Cancelled'
];

// Generate CSP nonce for this request
if (!isset($_SESSION['csp_nonce'])) {
    $_SESSION['csp_nonce'] = base64_encode(random_bytes(16));
}
$cspNonce = $_SESSION['csp_nonce'];

// Set timezone
date_default_timezone_set($appConfig['timezone']);

// Error reporting
if ($appConfig['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Session Security Configuration
// Must be set before session_start()
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');  // Prevent JavaScript access to session cookie
    ini_set('session.cookie_secure', $isProduction ? '1' : '0');  // HTTPS only in production
    ini_set('session.cookie_samesite', 'Strict');  // CSRF protection
    ini_set('session.use_strict_mode', '1');  // Reject uninitialized session IDs
    ini_set('session.use_only_cookies', '1');  // No URL-based session IDs
    ini_set('session.sid_length', '48');  // Longer session IDs
    ini_set('session.sid_bits_per_character', '6');  // More entropy
    ini_set('session.gc_maxlifetime', '7200');  // 2 hour session lifetime
    ini_set('session.cookie_lifetime', '0');  // Expire on browser close

    // Set session name before starting
    session_name($appConfig['session_name']);
}

// Initialize database (unless running in CLI mode where it might not be needed)
if (!defined('SKIP_DB_INIT')) {
    // Initialize database with namespaced class
    if (!class_exists('CFK\Database\Connection')) {
        die('Composer autoloader not loaded. Run: composer install');
    }

    \CFK\Database\Connection::init($dbConfig);

    // Create Database alias for legacy code that still uses it
    if (!class_exists('Database')) {
        class_alias('CFK\Database\Connection', 'Database');
    }

    // Create MagicLinkManager alias for legacy code
    if (!class_exists('MagicLinkManager')) {
        class_alias('CFK\Auth\MagicLinkManager', 'MagicLinkManager');
    }

    // Create CFK_Email_Manager alias for legacy code
    if (!class_exists('CFK_Email_Manager')) {
        class_alias('CFK\Email\Manager', 'CFK_Email_Manager');
    }

    // Create CFK_Import_Analyzer alias for legacy code
    if (!class_exists('CFK_Import_Analyzer')) {
        class_alias('CFK\Import\Analyzer', 'CFK_Import_Analyzer');
    }

    // Create manager class aliases for legacy code
    if (!class_exists('CFK_Archive_Manager')) {
        class_alias('CFK\Archive\Manager', 'CFK_Archive_Manager');
    }
    if (!class_exists('CFK_Avatar_Manager')) {
        class_alias('CFK\Avatar\Manager', 'CFK_Avatar_Manager');
    }
    if (!class_exists('CFK_Backup_Manager')) {
        class_alias('CFK\Backup\Manager', 'CFK_Backup_Manager');
    }
    if (!class_exists('CFK_CSV_Handler')) {
        class_alias('CFK\CSV\Handler', 'CFK_CSV_Handler');
    }
    if (!class_exists('CFK_Report_Manager')) {
        class_alias('CFK\Report\Manager', 'CFK_Report_Manager');
    }
    if (!class_exists('CFK_Sponsorship_Manager')) {
        class_alias('CFK\Sponsorship\Manager', 'CFK_Sponsorship_Manager');
    }
    if (!class_exists('CFK_Reservation_Manager')) {
        class_alias('CFK\Reservation\Manager', 'CFK_Reservation_Manager');
    }
}

// Helper function to get config values
function config(string $key, $default = null) {
    global $appConfig;
    return $appConfig[$key] ?? $default;
}

// Helper function to get base URL
function baseUrl(string $path = ''): string {
    return rtrim(config('base_url'), '/') . '/' . ltrim($path, '/');
}

// Helper function to get upload URL
function uploadUrl(string $path = ''): string {
    return baseUrl('uploads/' . ltrim($path, '/'));
}

// CSRF Protection
function generateCsrfToken(): string {
    if (!isset($_SESSION[config('csrf_token_name')])) {
        $_SESSION[config('csrf_token_name')] = bin2hex(random_bytes(32));
    }
    return $_SESSION[config('csrf_token_name')];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION[config('csrf_token_name')]) 
        && hash_equals($_SESSION[config('csrf_token_name')], $token);
}

// Simple sanitization helpers
function sanitizeString(string $input): string {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

function sanitizeInt(mixed $input): int {
    return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

function sanitizeEmail(string $input): string {
    return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
}

// Environment badge helper (for displaying in admin)
function getEnvironmentBadge(): string {
    $env = config('environment');
    $colors = [
        'local' => '#6c757d',      // gray
        'staging' => '#ffc107',    // yellow/orange
        'production' => '#28a745'  // green
    ];
    $color = $colors[$env] ?? '#6c757d';

    return sprintf(
        '<span style="display:inline-block;padding:4px 12px;background:%s;color:white;border-radius:4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">%s</span>',
        $color,
        htmlspecialchars($env)
    );
}