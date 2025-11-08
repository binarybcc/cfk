<?php

declare(strict_types=1);

/**
 * Admin Logout
 * Destroys session and redirects to login page
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration for message system
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Clear remember-me cookie if it exists
if (isset($_COOKIE['cfk_remember_token'])) {
    setcookie('cfk_remember_token', '', ['expires' => time() - 3600, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => true]);
    unset($_COOKIE['cfk_remember_token']);
}

// Log the logout action
$username = $_SESSION['cfk_admin_username'] ?? 'Unknown';
error_log("CFK Admin: User '$username' logged out from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Destroy all session data
$_SESSION = [];

// Destroy the session cookie
$sessionName = session_name();
if ($sessionName && isset($_COOKIE[$sessionName])) {
    setcookie($sessionName, '', ['expires' => time() - 3600, 'path' => '/']);
}

// Destroy session
session_destroy();

// Start new session for message
session_start();
setMessage('You have been logged out successfully.', 'success');

// Redirect to login page
header('Location: login.php');
exit;
