<?php
declare(strict_types=1);

/**
 * Create Sponsorship API Endpoint
 * Converts localStorage selections into immediate sponsorship confirmation
 * v1.5 - Instant Confirmation System
 */

// Define constant before loading config
define('CFK_APP', true);

// Load configuration and functions
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/reservation_functions.php';
require_once __DIR__ . '/../includes/reservation_emails.php';

// Set JSON response headers
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    // Validate sponsor data
    if (empty($data['sponsor']['name']) || empty($data['sponsor']['email'])) {
        throw new Exception('Missing required sponsor information');
    }

    // Validate children IDs
    if (empty($data['children_ids']) || !is_array($data['children_ids'])) {
        throw new Exception('No children selected');
    }

    // Sanitize sponsor data
    $sponsorData = [
        'name' => sanitizeString($data['sponsor']['name']),
        'email' => sanitizeEmail($data['sponsor']['email']),
        'phone' => !empty($data['sponsor']['phone']) ? sanitizeString($data['sponsor']['phone']) : null,
        'address' => !empty($data['sponsor']['address']) ? sanitizeString($data['sponsor']['address']) : null
    ];

    // Sanitize children IDs
    $childrenIds = array_map('intval', $data['children_ids']);

    // Check if children are available
    $unavailableChildren = checkChildrenAvailability($childrenIds);
    if (!empty($unavailableChildren)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Some children are no longer available: ' . implode(', ', $unavailableChildren)
        ]);
        exit;
    }

    // Create immediate sponsorships (no delay!)
    Database::beginTransaction();

    $sponsorshipIds = [];
    try {
        foreach ($childrenIds as $childId) {
            // Mark child as sponsored
            Database::update('children', ['status' => 'sponsored'], ['id' => $childId]);

            // Create sponsorship record
            $sponsorshipId = Database::insert('sponsorships', [
                'child_id' => $childId,
                'sponsor_name' => $sponsorData['name'],
                'sponsor_email' => $sponsorData['email'],
                'sponsor_phone' => $sponsorData['phone'],
                'sponsor_address' => $sponsorData['address'],
                'confirmation_date' => gmdate('Y-m-d H:i:s'),
                'status' => 'confirmed'
            ]);

            $sponsorshipIds[] = $sponsorshipId;
        }

        Database::commit();

        // Try to send confirmation email, but don't fail if it doesn't work
        $emailResult = ['success' => false];
        try {
            // Get children details for email
            $children = [];
            foreach ($childrenIds as $childId) {
                $child = Database::fetchRow(
                    "SELECT c.*, f.family_number, CONCAT(f.family_number, c.child_letter) as display_id
                     FROM children c
                     JOIN families f ON c.family_id = f.id
                     WHERE c.id = ?",
                    [$childId]
                );
                if ($child) {
                    $children[] = $child;
                }
            }

            // Create reservation data structure for email template
            $reservationData = [
                'id' => $sponsorshipIds[0], // Use first sponsorship ID
                'sponsor_name' => $sponsorData['name'],
                'sponsor_email' => $sponsorData['email'],
                'reservation_token' => 'SPO-' . strtoupper(substr(md5($sponsorData['email'] . time()), 0, 8)),
                'total_children' => count($children),
                'children' => $children,
                'created_at' => gmdate('Y-m-d H:i:s'),
                'expires_at' => gmdate('Y-m-d H:i:s', strtotime('+2 hours')) // Not used but required by template
            ];

            // Send HTML email using the proper email template
            $emailResult = sendReservationConfirmationEmail($reservationData);

            error_log(sprintf(
                'Sponsorship confirmed: Email=%s, Children=%d, EmailSent=%s',
                $sponsorData['email'],
                count($childrenIds),
                $emailResult['success'] ? 'yes' : 'no'
            ));
        } catch (Throwable $emailException) {
            error_log('Email sending failed: ' . $emailException->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Sponsorship confirmed! Thank you!',
            'sponsorship_ids' => $sponsorshipIds,
            'sponsor_email' => $sponsorData['email'],
            'children_count' => count($childrenIds),
            'email_sent' => $emailResult['success']
        ]);

    } catch (Throwable $e) {
        try {
            Database::rollback();
        } catch (Throwable $rollbackException) {
            // Ignore rollback errors
        }
        throw $e; // Re-throw to be caught by outer catch
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log('Create reservation API error: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating your reservation. Please try again.'
    ]);
}
