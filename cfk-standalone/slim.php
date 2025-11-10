<?php

declare(strict_types=1);

/**
 * Slim Framework Entry Point (Test/Development)
 *
 * This is a temporary entry point to test Slim Framework infrastructure.
 * Once verified working, this will be integrated into index.php.
 *
 * Access via: /slim.php/slim-test or /slim.php/slim-test-view
 */

// Security constant (required by config.php)
define('CFK_APP', true);

// Load Composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Load environment variables
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load configuration
require_once __DIR__ . '/config/config.php';

// Initialize database connection (required for future routes)
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
// Slim Framework Setup
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

// ============================================================================
// Run Application
// ============================================================================

$app->run();
