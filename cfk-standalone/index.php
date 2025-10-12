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
$validPages = ['home', 'children', 'child', 'sponsor', 'about', 'donate', 'sponsor_lookup', 'sponsor_portal', 'how_to_apply', 'selections', 'confirm_sponsorship', 'reservation_review', 'reservation_success'];

// Redirect search to children page with search parameter (before headers are sent)
if ($page === 'search') {
    $searchQuery = sanitizeString($_GET['q'] ?? $_GET['search'] ?? '');
    if (!empty($searchQuery)) {
        header('Location: ' . baseUrl('?page=children&search=' . urlencode($searchQuery)));
    } else {
        header('Location: ' . baseUrl('?page=children'));
    }
    exit;
}

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
    case 'about':
        include __DIR__ . '/pages/about.php';
        break;
    case 'donate':
        include __DIR__ . '/pages/donate.php';
        break;
    case 'sponsor_lookup':
        include __DIR__ . '/pages/sponsor_lookup.php';
        break;
    case 'sponsor_portal':
        include __DIR__ . '/pages/sponsor_portal.php';
        break;
    case 'how_to_apply':
        include __DIR__ . '/pages/how_to_apply.php';
        break;
    case 'selections':
        include __DIR__ . '/pages/selections.php';
        break;
    case 'confirm_sponsorship':
        include __DIR__ . '/pages/confirm_sponsorship.php';
        break;
    case 'reservation_review':
        include __DIR__ . '/pages/reservation_review.php';
        break;
    case 'reservation_success':
        include __DIR__ . '/pages/reservation_success.php';
        break;
    default:
        include __DIR__ . '/pages/children.php';
}

// Include footer
include __DIR__ . '/includes/footer.php';