<?php
/**
 * CFK Sponsorship System - Web Installer
 *
 * WordPress-style 5-minute installation system
 * Handles environment checks, database setup, and initial admin account creation
 *
 * @package CFK
 * @version 1.9.4
 */

declare(strict_types=1);

// Start session for installer state management
session_start();

// Check if already installed
if (file_exists(__DIR__ . '/.installed')) {
    http_response_code(403);
    die('<h1>Already Installed</h1><p>The application has already been installed. Please delete the <code>.installed</code> file to run the installer again.</p>');
}

// Autoload installer class
require_once __DIR__ . '/install/Installer.php';

// Initialize installer
$installer = new CFK\Install\Installer();

// Handle POST requests (form submissions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $installer->handlePost();
    exit;
}

// Get current step
$step = $_GET['step'] ?? 'welcome';

// Render current step
$installer->render($step);
