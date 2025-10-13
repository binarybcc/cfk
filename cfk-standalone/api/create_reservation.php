<?php
declare(strict_types=1);

/**
 * Create Reservation API Endpoint
 * Converts localStorage selections into database reservation
 * v1.5 - Reservation System
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

    // Create reservation (48 hour expiration by default)
    $result = createReservation($sponsorData, $childrenIds, 48);

    if ($result['success']) {
        http_response_code(201); // Created

        // Get full reservation details for email
        $reservation = getReservation($result['token']);

        // Send confirmation email to sponsor
        $emailResult = sendReservationConfirmationEmail($reservation);

        // Send notification to admin
        sendAdminReservationNotification($reservation);

        // Log successful reservation
        error_log(sprintf(
            'Reservation created: Token=%s, Sponsor=%s, Children=%d, Email=%s',
            $result['token'],
            $sponsorData['email'],
            count($childrenIds),
            $emailResult['success'] ? 'sent' : 'failed'
        ));

        echo json_encode([
            'success' => true,
            'message' => 'Reservation created successfully!',
            'token' => $result['token'],
            'reservation_id' => $result['reservation_id'],
            'expires_at' => $result['expires_at'],
            'email_sent' => $emailResult['success']
        ]);
    } else {
        http_response_code(400); // Bad Request
        echo json_encode($result);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log('Create reservation API error: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating your reservation. Please try again.'
    ]);
}
