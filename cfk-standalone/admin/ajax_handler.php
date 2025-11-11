<?php

declare(strict_types=1);

/**
 * Centralized AJAX Handler for Admin Actions
 * Routes requests to appropriate managers based on action type
 * Works across all admin pages (sponsorships, children, admins, etc.)
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Use namespaced classes
use CFK\Child\Manager as ChildManager;
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

// Get action and route to appropriate handler
$action = $_POST['action'] ?? '';
$actionType = $_POST['action_type'] ?? 'sponsorship'; // Default to sponsorship for backward compatibility

try {
    $result = match ($actionType) {
        'sponsorship' => handleSponsorshipAction($action, $_POST),
        'child' => handleChildAction($action, $_POST),
        'admin' => handleAdminAction($action, $_POST),
        default => ['success' => false, 'message' => 'Invalid action type']
    };

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => config('app_debug') ? $e->getMessage() : 'An error occurred',
    ]);
}

/**
 * Handle sponsorship-related actions
 *
 * @param array<string, mixed> $data Request data
 * @return array<string, mixed> JSON response with 'success' and 'message' keys
 */
function handleSponsorshipAction(string $action, array $data): array
{
    $sponsorshipId = sanitizeInt($data['sponsorship_id'] ?? 0);

    if (! $sponsorshipId) {
        return ['success' => false, 'message' => 'Invalid sponsorship ID'];
    }

    return match ($action) {
        'log' => SponsorshipManager::logSponsorship($sponsorshipId),
        'unlog' => SponsorshipManager::unlogSponsorship($sponsorshipId),
        'complete' => SponsorshipManager::completeSponsorship($sponsorshipId),
        'cancel' => SponsorshipManager::cancelSponsorship(
            $sponsorshipId,
            sanitizeString($data['reason'] ?? 'Cancelled by admin')
        ),
        'edit_sponsorship' => editSponsorship($data),
        default => ['success' => false, 'message' => 'Invalid sponsorship action']
    };
}

/**
 * Handle child-related actions
 *
 * @param array<string, mixed> $data Request data
 * @return array<string, mixed> JSON response with 'success' and 'message' keys
 */
function handleChildAction(string $action, array $data): array
{
    $childId = sanitizeInt($data['child_id'] ?? 0);

    if (! $childId) {
        return ['success' => false, 'message' => 'Invalid child ID'];
    }

    return match ($action) {
        'toggle_status' => ChildManager::toggleChildStatus($childId),
        'delete_child' => ChildManager::deleteChild($childId),
        'edit_child' => ChildManager::editChild($data),
        default => ['success' => false, 'message' => 'Invalid child action']
    };
}

/**
 * Handle admin user actions
 *
 * @param array<string, mixed> $data Request data
 * @return array<string, mixed> JSON response with 'success' and 'message' keys
 */
function handleAdminAction(string $action, array $data): array
{
    // Only allow admins to manage other admins
    if ($_SESSION['cfk_admin_role'] !== 'admin') {
        return ['success' => false, 'message' => 'Unauthorized'];
    }

    return match ($action) {
        'create_admin' => createAdmin($data),
        'update_admin' => updateAdmin($data),
        'delete_admin' => deleteAdmin($data),
        default => ['success' => false, 'message' => 'Invalid admin action']
    };
}

// Child functions moved to src/Child/Manager.php

/**
 * Create admin (placeholder - not implemented)
 *
 * @param array<string, mixed> $data Admin data
 * @return array<string, mixed> Result with success status and message
 */
function createAdmin(array $data): array
{
    return ['success' => false, 'message' => 'Not implemented yet'];
}

/**
 * Update admin (placeholder - not implemented)
 *
 * @param array<string, mixed> $data Admin data
 * @return array<string, mixed> Result with success status and message
 */
function updateAdmin(array $data): array
{
    return ['success' => false, 'message' => 'Not implemented yet'];
}

/**
 * Delete admin (placeholder - not implemented)
 *
 * @param array<string, mixed> $data Admin data
 * @return array<string, mixed> Result with success status and message
 */
function deleteAdmin(array $data): array
{
    return ['success' => false, 'message' => 'Not implemented yet'];
}

/**
 * Edit sponsorship details
 *
 * @param array<string, mixed> $data Sponsorship data to update
 * @return array<string, mixed> Result with success status and message
 */
function editSponsorship(array $data): array
{
    try {
        $sponsorshipId = sanitizeInt($data['sponsorship_id'] ?? 0);
        if (! $sponsorshipId) {
            return ['success' => false, 'message' => 'Invalid sponsorship ID'];
        }

        // Validate email
        $email = sanitizeEmail($data['sponsor_email'] ?? '');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }

        // Update sponsorship
        Database::update('sponsorships', [
            'sponsor_name' => sanitizeString($data['sponsor_name'] ?? ''),
            'sponsor_email' => $email,
            'sponsor_phone' => sanitizeString($data['sponsor_phone'] ?? ''),
            'sponsor_address' => sanitizeString($data['sponsor_address'] ?? ''),
        ], ['id' => $sponsorshipId]);

        return ['success' => true, 'message' => 'Sponsorship updated successfully'];
    } catch (Exception $e) {
        error_log('Update sponsorship error: ' . $e->getMessage());

        return ['success' => false, 'message' => 'Failed to update sponsorship'];
    }
}

// editChild and validateChildData functions moved to src/Child/Manager.php
