<?php
declare(strict_types=1);

/**
 * Christmas for Kids - Sponsorship System
 * Main entry point for the standalone PHP application
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Get requested page
$page = $_GET['page'] ?? 'home';
$validPages = ['home', 'children', 'child', 'sponsor', 'search', 'about'];

// Default to children listing if page is invalid
if (!in_array($page, $validPages)) {
    $page = 'children';
}

// Include header
include __DIR__ . '/includes/header.php';

// Route to appropriate page
switch ($page) {
    case 'home':
        include __DIR__ . '/pages/home.php';
        break;
    case 'children':
        include __DIR__ . '/pages/children.php';
        break;
    case 'child':
        include __DIR__ . '/pages/child.php';
        break;
    case 'sponsor':
        include __DIR__ . '/pages/sponsor.php';
        break;
    case 'search':
        include __DIR__ . '/pages/search.php';
        break;
    case 'about':
        include __DIR__ . '/pages/about.php';
        break;
    default:
        include __DIR__ . '/pages/children.php';
}

// Include footer
include __DIR__ . '/includes/footer.php';