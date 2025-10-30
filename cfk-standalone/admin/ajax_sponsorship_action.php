<?php

declare(strict_types=1);

/**
 * AJAX Handler for Sponsorship Actions
 * Handles sponsorship status changes without page reload
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Use namespaced classes
use CFK\Sponsorship\Manager as SponsorshipManager;

// Set JSON response headers
header('Content-Type: application/json');

// Check if user is logged in
if (! isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (! verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

// Get action and sponsorship ID
$action = $_POST['action'] ?? '';
$sponsorshipId = sanitizeInt($_POST['sponsorship_id'] ?? 0);

if (! $sponsorshipId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid sponsorship ID']);
    exit;
}

// Process action
$result = match ($action) {
    'log' => SponsorshipManager::logSponsorship($sponsorshipId),
    'unlog' => SponsorshipManager::unlogSponsorship($sponsorshipId),
    'complete' => SponsorshipManager::completeSponsorship($sponsorshipId),
    'cancel' => SponsorshipManager::cancelSponsorship(
        $sponsorshipId,
        sanitizeString($_POST['reason'] ?? 'Cancelled by admin')
    ),
    default => ['success' => false, 'message' => 'Invalid action']
};

// Return JSON response
echo json_encode($result);
