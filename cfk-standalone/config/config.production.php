<?php
declare(strict_types=1);

/**
 * Christmas for Kids - Production Configuration
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Production Environment
$isProduction = true;

// Database Configuration - PRODUCTION
$dbConfig = [
    'host' => 'localhost',
    'database' => 'a4409d26_509946',
    'username' => 'a4409d26_509946',
    'password' => 'Fests42Cue50Fennel56Auk46'
];

// Application Settings - PRODUCTION
$appConfig = [
    'app_name' => 'Christmas for Kids Sponsorship',
    'app_version' => '1.4',
    'timezone' => 'America/New_York',
    'debug' => false,

    // Paths
    'base_url' => 'https://cforkids.org/',
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
    'admin_email' => 'admin@cforkids.org',
    'from_email' => 'noreply@cforkids.org',
    'from_name' => 'Christmas for Kids',

    // SMTP Configuration
    'email_use_smtp' => false, // Use sendmail for now
    'smtp_host' => 'relay.mailchannels.net',
    'smtp_port' => 587,
    'smtp_auth' => true,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',

    // Security
    'session_name' => 'CFK_SESSION',
    'csrf_token_name' => 'cfk_csrf_token',
    'password_min_length' => 8,

    // Upload limits
    'max_file_size' => 5 * 1024 * 1024,
    'allowed_photo_types' => ['jpg', 'jpeg', 'png', 'gif'],
    'photo_max_width' => 800,
    'photo_max_height' => 600,
];

// Age categories
$ageCategories = [
    'birth_to_4' => ['label' => 'Birth to 4', 'min' => 0, 'max' => 4],
    'elementary' => ['label' => 'Elementary (5-10)', 'min' => 5, 'max' => 10],
    'middle_school' => ['label' => 'Middle School (11-13)', 'min' => 11, 'max' => 13],
    'high_school' => ['label' => 'High School (14-18)', 'min' => 14, 'max' => 18]
];

$childStatusOptions = [
    'available' => 'Available for Sponsorship',
    'pending' => 'Sponsorship Pending',
    'sponsored' => 'Already Sponsored',
    'inactive' => 'Not Available'
];

$sponsorshipStatusOptions = [
    'pending' => 'Pending Confirmation',
    'confirmed' => 'Confirmed',
    'completed' => 'Gift Delivered',
    'cancelled' => 'Cancelled'
];

date_default_timezone_set($appConfig['timezone']);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');
    ini_set('session.gc_maxlifetime', '7200');
    ini_set('session.cookie_lifetime', '0');
    session_name($appConfig['session_name']);
}

require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../includes/database_wrapper.php';
\CFK\Config\Database::init($dbConfig);

function config(string $key, $default = null) {
    global $appConfig;
    return $appConfig[$key] ?? $default;
}

function baseUrl(string $path = ''): string {
    return rtrim(config('base_url'), '/') . '/' . ltrim($path, '/');
}

function uploadUrl(string $path = ''): string {
    return baseUrl('uploads/' . ltrim($path, '/'));
}

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

function sanitizeString(string $input): string {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

function sanitizeInt(mixed $input): int {
    return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

function sanitizeEmail(string $input): string {
    return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
}
