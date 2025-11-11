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

// ============================================================================
// MIGRATION: Redirect legacy query string routes → new Slim Framework routes
// ============================================================================
// All public-facing pages have been migrated to clean Slim routes.
// Redirect old ?page= URLs to new routes for SEO and user bookmarks.

$legacyRedirects = [
    'children' => '/children',
    'child' => isset($_GET['id']) ? '/children/' . (int)$_GET['id'] : '/children',
    'about' => '/about',
    'donate' => '/donate',
    'how_to_apply' => '/how-to-apply',
    'sponsor_lookup' => '/sponsor/lookup',
    'sponsor_portal' => isset($_GET['token']) ? '/portal?token=' . urlencode($_GET['token']) : '/sponsor/lookup',
    'my_sponsorships' => '/portal',
    'sponsor' => isset($_GET['child_id']) ? '/sponsor/child/' . (int)$_GET['child_id'] :
                 (isset($_GET['family_id']) ? '/sponsor/family/' . (int)$_GET['family_id'] : '/children'),
    'family' => isset($_GET['id']) ? '/sponsor/family/' . (int)$_GET['id'] : '/children',
    'reservation_review' => '/cart/review',
    'reservation_success' => '/cart/success',
    'confirm_sponsorship' => '/children', // Obsolete - redirect to browse
    'selections' => '/cart/review', // Old cart name
];

// Perform redirect if this is a migrated page
if (isset($legacyRedirects[$page])) {
    header('Location: ' . baseUrl($legacyRedirects[$page]), true, 301); // 301 = Permanent redirect
    exit;
}

// Special handling for search
if ($page === 'search') {
    $searchQuery = sanitizeString($_GET['q'] ?? $_GET['search'] ?? '');
    if (!empty($searchQuery)) {
        header('Location: ' . baseUrl('/children?search=' . urlencode($searchQuery)), true, 301);
    } else {
        header('Location: ' . baseUrl('/children'), true, 301);
    }
    exit;
}

// Legacy pages list (now only used for temp_landing)
$validPages = ['home', 'temp_landing'];

// Default to homepage (/) if page is invalid
if (!in_array($page, $validPages)) {
    header('Location: ' . baseUrl('/'), true, 301);
    exit;
}

// ============================================================================
// TEMPORARY LANDING PAGE LOGIC - Active until Oct 31, 2025 11:00 AM ET
// ============================================================================
// Check if we should show temporary landing page (only for home page)
// Preview modes: ?preview=temp (force temp page) or ?preview=normal (force normal page)
$showTempLanding = false;
$previewMode = $_GET['preview'] ?? null;

if ($page === 'home') {
    if ($previewMode === 'temp') {
        // Force show temporary landing page
        $showTempLanding = true;
    } elseif ($previewMode === 'normal') {
        // Force show normal home page
        $showTempLanding = false;
    } else {
        // Automatic mode - check current date/time
        $launchTime = new DateTime('2025-10-31 10:00:00', new DateTimeZone('America/New_York'));
        $now = new DateTime('now', new DateTimeZone('America/New_York'));
        $showTempLanding = ($now < $launchTime);
    }
}

// Use appropriate header based on landing page mode
if ($showTempLanding) {
    include __DIR__ . '/includes/header_temp.php';
} else {
    include __DIR__ . '/includes/header.php';
}

// Route to appropriate page
// NOTE: Most pages now use Slim Framework routes. Only temp_landing and home remain here.
switch ($page) {
    case 'home':
        if ($showTempLanding) {
            include __DIR__ . '/pages/temp_landing.php';
        } else {
            // Redirect to Slim route
            header('Location: ' . baseUrl('/'), true, 301);
            exit;
        }
        break;

    case 'temp_landing':
        // Explicit temp landing (for preview mode)
        include __DIR__ . '/pages/temp_landing.php';
        break;

    default:
        // Should never reach here due to redirects above
        header('Location: ' . baseUrl('/'), true, 301);
        exit;
}

// Include footer
// Use appropriate footer based on landing page mode
if ($showTempLanding) {
    include __DIR__ . '/includes/footer_temp.php';
} else {
    include __DIR__ . '/includes/footer.php';
}