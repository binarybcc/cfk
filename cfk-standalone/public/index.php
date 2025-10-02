<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CFK\Config\Database;
use CFK\Controllers\AdminController;
use CFK\Controllers\ChildController;
use CFK\Repositories\ChildRepository;
use CFK\Repositories\SponsorshipRepository;
use CFK\Services\SponsorshipService;
use CFK\Utils\Container;
use CFK\Utils\Router;

// Start session
session_start();

// Initialize container
$container = new Container();

// Load configuration
$config = require __DIR__ . '/../config/database.php';
Database::init($config);

// Register services in container
$container->singleton(ChildRepository::class);
$container->singleton(SponsorshipRepository::class);
$container->singleton(SponsorshipService::class, function($container) {
    return new SponsorshipService(
        $container->make(ChildRepository::class),
        $container->make(SponsorshipRepository::class)
    );
});

$container->singleton(ChildController::class, function($container) {
    return new ChildController(
        $container->make(ChildRepository::class),
        $container->make(SponsorshipService::class)
    );
});

$container->singleton(AdminController::class, function($container) {
    return new AdminController(
        $container->make(ChildRepository::class),
        $container->make(SponsorshipRepository::class),
        $container->make(SponsorshipService::class)
    );
});

// Initialize router
$router = new Router();

// Add security middleware
$router->middleware(function($method, $uri) {
    // Generate CSRF token if not exists
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    return true; // Continue processing
});

// Public routes
$router->get('/', function() use ($container) {
    $container->make(ChildController::class)->index();
});

$router->get('/child/{id}', function($id) use ($container) {
    $_GET['id'] = $id;
    $container->make(ChildController::class)->show();
});

// AJAX routes
$router->post('/ajax/select-child', function() use ($container) {
    $container->make(ChildController::class)->select();
});

$router->post('/ajax/confirm-sponsorship', function() use ($container) {
    $container->make(ChildController::class)->confirm();
});

$router->post('/ajax/cancel-sponsorship', function() use ($container) {
    $container->make(ChildController::class)->cancel();
});

// Admin routes
$router->get('/admin', function() use ($container) {
    $container->make(AdminController::class)->dashboard();
});

$router->get('/admin/dashboard', function() use ($container) {
    $container->make(AdminController::class)->dashboard();
});

$router->get('/admin/children', function() use ($container) {
    $container->make(AdminController::class)->children();
});

$router->get('/admin/sponsorships', function() use ($container) {
    $container->make(AdminController::class)->sponsorships();
});

$router->get('/admin/import', function() use ($container) {
    $container->make(AdminController::class)->importCsv();
});

$router->post('/admin/import', function() use ($container) {
    $container->make(AdminController::class)->importCsv();
});

$router->get('/admin/export', function() use ($container) {
    $container->make(AdminController::class)->exportCsv();
});

// Admin AJAX routes
$router->post('/admin/cleanup-expired', function() use ($container) {
    $container->make(AdminController::class)->cleanupExpired();
});

$router->post('/admin/update-child-status', function() use ($container) {
    $container->make(AdminController::class)->updateChildStatus();
});

// Admin login (simple implementation)
$router->get('/admin/login', function() {
    if (isset($_SESSION['admin_logged_in'])) {
        header('Location: /admin/dashboard');
        return;
    }
    include __DIR__ . '/../src/Views/admin/login.php';
});

$router->post('/admin/login', function() {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple authentication (in production, use proper password hashing)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: /admin/dashboard');
    } else {
        $_SESSION['error'] = 'Invalid credentials';
        header('Location: /admin/login');
    }
});

$router->post('/admin/logout', function() {
    session_destroy();
    header('Location: /');
});

// Error handler
set_error_handler(function($severity, $message, $file, $line) {
    error_log("Error: {$message} in {$file} on line {$line}");
    
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

// Exception handler
set_exception_handler(function($exception) {
    error_log("Uncaught exception: " . $exception->getMessage());
    
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        } else {
            echo '500 - Internal Server Error';
        }
    }
});

// Route the request
$router->route();