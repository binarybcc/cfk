<?php

declare(strict_types=1);

/**
 * Christmas for Kids - Sponsorship System
 * Main entry point - Slim Framework 4.x with legacy compatibility
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load Composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Load environment variables (.env file)
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load configuration
require_once __DIR__ . '/config/config.php';

// Load helper functions
require_once __DIR__ . '/includes/functions.php';

// Initialize database connection
use CFK\Database\Connection;

Connection::init([
    'host' => config('db_host'),
    'port' => 3306,
    'database' => config('db_name'),
    'username' => config('db_user'),
    'password' => config('db_password'),
    'charset' => 'utf8mb4',
]);

// ============================================================================
// Legacy Query String Redirects
// ============================================================================
// Redirect old ?page= URLs to new Slim routes (for SEO and bookmarks)

$page = $_GET['page'] ?? null;

if ($page !== null) {
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
        'confirm_sponsorship' => '/children',
        'selections' => '/cart/review',
        'home' => '/',
    ];

    // Perform redirect if this is a migrated page
    if (isset($legacyRedirects[$page])) {
        header('Location: ' . baseUrl($legacyRedirects[$page]), true, 301);
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

    // Special handling for temp_landing
    if ($page === 'temp_landing') {
        // Include old-style page (still used for temporary landing)
        include __DIR__ . '/includes/header_temp.php';
        include __DIR__ . '/pages/temp_landing.php';
        include __DIR__ . '/includes/footer_temp.php';
        exit;
    }

    // Unknown legacy page - redirect to homepage
    header('Location: ' . baseUrl('/'), true, 301);
    exit;
}

// ============================================================================
// Slim Framework Bootstrap
// ============================================================================

use Slim\Factory\AppFactory;

// Load DI Container
$container = require __DIR__ . '/config/slim/container.php';
AppFactory::setContainer($container);

// Create Slim App
$app = AppFactory::create();

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(
    displayErrorDetails: config('environment') !== 'production',
    logErrors: true,
    logErrorDetails: true
);

// Register Routes
$routes = require __DIR__ . '/config/slim/routes.php';
$routes($app);

// Run Application
$app->run();
