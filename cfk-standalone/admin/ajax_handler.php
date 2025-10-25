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
use CFK\Sponsorship\Manager as SponsorshipManager;

// Set JSON response headers
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
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
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
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
        'message' => config('app_debug') ? $e->getMessage() : 'An error occurred'
    ]);
}

/**
 * Handle sponsorship-related actions
 */
function handleSponsorshipAction(string $action, array $data): array
{
    $sponsorshipId = sanitizeInt($data['sponsorship_id'] ?? 0);

    if (!$sponsorshipId) {
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
 */
function handleChildAction(string $action, array $data): array
{
    $childId = sanitizeInt($data['child_id'] ?? 0);

    if (!$childId) {
        return ['success' => false, 'message' => 'Invalid child ID'];
    }

    return match ($action) {
        'toggle_status' => toggleChildStatus($childId),
        'delete_child' => deleteChild($childId),
        'edit_child' => editChild($data),
        default => ['success' => false, 'message' => 'Invalid child action']
    };
}

/**
 * Handle admin user actions
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

/**
 * Toggle child status between available and inactive
 */
function toggleChildStatus(int $childId): array
{
    $child = Database::fetchRow("SELECT status FROM children WHERE id = ?", [$childId]);

    if (!$child) {
        return ['success' => false, 'message' => 'Child not found'];
    }

    $newStatus = $child['status'] === 'available' ? 'inactive' : 'available';

    $success = Database::execute(
        "UPDATE children SET status = ? WHERE id = ?",
        [$newStatus, $childId]
    );

    if ($success) {
        return [
            'success' => true,
            'message' => 'Child status updated to ' . $newStatus,
            'new_status' => $newStatus
        ];
    }

    return ['success' => false, 'message' => 'Failed to update child status'];
}

/**
 * Delete a child
 */
function deleteChild(int $childId): array
{
    // Check if child is sponsored
    $sponsorships = Database::fetchAll(
        "SELECT id FROM sponsorships WHERE child_id = ? AND status IN ('confirmed', 'logged')",
        [$childId]
    );

    if ($sponsorships !== []) {
        return ['success' => false, 'message' => 'Cannot delete: Child is currently sponsored'];
    }

    $success = Database::execute("DELETE FROM children WHERE id = ?", [$childId]);

    if ($success) {
        return ['success' => true, 'message' => 'Child deleted successfully'];
    }

    return ['success' => false, 'message' => 'Failed to delete child'];
}

// Placeholder functions for admin management (implement as needed)
function createAdmin(array $data): array
{
    return ['success' => false, 'message' => 'Not implemented yet'];
}

function updateAdmin(array $data): array
{
    return ['success' => false, 'message' => 'Not implemented yet'];
}

function deleteAdmin(array $data): array
{
    return ['success' => false, 'message' => 'Not implemented yet'];
}

/**
 * Edit sponsorship details
 */
function editSponsorship(array $data): array
{
    try {
        $sponsorshipId = sanitizeInt($data['sponsorship_id'] ?? 0);
        if (!$sponsorshipId) {
            return ['success' => false, 'message' => 'Invalid sponsorship ID'];
        }

        // Validate email
        $email = sanitizeEmail($data['sponsor_email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }

        // Update sponsorship
        Database::update('sponsorships', [
            'sponsor_name' => sanitizeString($data['sponsor_name'] ?? ''),
            'sponsor_email' => $email,
            'sponsor_phone' => sanitizeString($data['sponsor_phone'] ?? ''),
            'sponsor_address' => sanitizeString($data['sponsor_address'] ?? '')
        ], ['id' => $sponsorshipId]);

        return ['success' => true, 'message' => 'Sponsorship updated successfully'];
    } catch (Exception $e) {
        error_log('Update sponsorship error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update sponsorship'];
    }
}

/**
 * Edit child details
 */
function editChild(array $data): array
{
    try {
        $childId = sanitizeInt($data['child_id'] ?? 0);
        if (!$childId) {
            return ['success' => false, 'message' => 'Invalid child ID'];
        }

        // Validate required fields
        $validation = validateChildData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => 'Please fix the following errors: ' . implode(', ', $validation['errors'])];
        }

        // Check if child exists
        $child = Database::fetchRow("SELECT id, family_id, child_letter FROM children WHERE id = ?", [$childId]);
        if (!$child) {
            return ['success' => false, 'message' => 'Child not found'];
        }

        // Check if child_letter is unique within family (if changed)
        if ($child['family_id'] != $data['family_id'] || $child['child_letter'] != $data['child_letter']) {
            $existing = Database::fetchRow(
                "SELECT id FROM children WHERE family_id = ? AND child_letter = ? AND id != ?",
                [$data['family_id'], $data['child_letter'], $childId]
            );
            if ($existing) {
                return ['success' => false, 'message' => 'Child letter already exists in this family'];
            }
        }

        Database::update('children', [
            'family_id' => sanitizeInt($data['family_id']),
            'child_letter' => sanitizeString($data['child_letter']),
            'age' => sanitizeInt($data['age']),
            'grade' => sanitizeString($data['grade']),
            'gender' => sanitizeString($data['gender']),
            'school' => sanitizeString($data['school'] ?? ''),
            'shirt_size' => sanitizeString($data['shirt_size'] ?? ''),
            'pant_size' => sanitizeString($data['pant_size'] ?? ''),
            'shoe_size' => sanitizeString($data['shoe_size'] ?? ''),
            'jacket_size' => sanitizeString($data['jacket_size'] ?? ''),
            'interests' => sanitizeString($data['interests'] ?? ''),
            'wishes' => sanitizeString($data['wishes'] ?? ''),
            'special_needs' => sanitizeString($data['special_needs'] ?? '')
        ], ['id' => $childId]);

        return ['success' => true, 'message' => 'Child updated successfully'];
    } catch (Exception $e) {
        error_log('Failed to edit child: ' . $e->getMessage());
        return ['success' => false, 'message' => 'System error occurred. Please try again.'];
    }
}

/**
 * Validate child data
 */
function validateChildData(array $data): array
{
    $errors = [];

    // Name is optional - no validation needed

    $age = sanitizeInt($data['age'] ?? 0);
    if ($age < 1 || $age > 18) {
        $errors[] = 'Age must be between 1 and 18';
    }

    if (in_array(trim($data['gender'] ?? ''), ['', '0'], true) || !in_array($data['gender'], ['M', 'F'])) {
        $errors[] = 'Valid gender is required';
    }

    if (in_array(trim($data['child_letter'] ?? ''), ['', '0'], true) || !preg_match('/^[A-Z]$/', (string) $data['child_letter'])) {
        $errors[] = 'Child letter must be a single uppercase letter (A-Z)';
    }

    if (empty(sanitizeInt($data['family_id'] ?? 0))) {
        $errors[] = 'Family selection is required';
    }

    return [
        'valid' => $errors === [],
        'errors' => $errors
    ];
}
