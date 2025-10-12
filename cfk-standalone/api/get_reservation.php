<?php
declare(strict_types=1);

/**
 * Get Reservation API Endpoint
 * Retrieves reservation details by token
 * v1.5 - Reservation System
 */

// Define constant before loading config
define('CFK_APP', true);

// Load configuration and functions
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/reservation_functions.php';

// Set JSON response headers
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get token from query parameter
    $token = $_GET['token'] ?? '';

    if (empty($token)) {
        throw new Exception('Reservation token is required');
    }

    // Sanitize token
    $token = sanitizeString($token);

    // Get reservation
    $reservation = getReservation($token);

    if (!$reservation) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Reservation not found'
        ]);
        exit;
    }

    // Return reservation data
    echo json_encode([
        'success' => true,
        'reservation' => $reservation
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('Get reservation API error: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving your reservation.'
    ]);
}
