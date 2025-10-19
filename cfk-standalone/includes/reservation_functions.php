<?php
declare(strict_types=1);

/**
 * Reservation System Functions
 * v1.5 - Manages temporary reservations with 24-48 hour expiration
 */

if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

/**
 * Create a new reservation
 *
 * @param array $sponsorData ['name', 'email', 'phone', 'address']
 * @param array $childrenIds Array of child IDs to reserve
 * @param int $expirationHours Number of hours until expiration (default: 48)
 * @return array ['success' => bool, 'token' => string, 'message' => string, 'reservation_id' => int]
 */
function createReservation(array $sponsorData, array $childrenIds, int $expirationHours = 48): array {
    try {
        // Validate input
        if (empty($sponsorData['name']) || empty($sponsorData['email']) || empty($childrenIds)) {
            return [
                'success' => false,
                'message' => 'Missing required information: name, email, and at least one child selection.'
            ];
        }

        // Validate email
        if (!filter_var($sponsorData['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email address.'
            ];
        }

        // Check if any children are already reserved or sponsored
        $unavailableChildren = checkChildrenAvailability($childrenIds);
        if (!empty($unavailableChildren)) {
            return [
                'success' => false,
                'message' => 'Some children are no longer available: ' . implode(', ', $unavailableChildren),
                'unavailable' => $unavailableChildren
            ];
        }

        // Generate unique reservation token
        $token = generateReservationToken();

        // Calculate expiration time
        $expiresAt = gmdate('Y-m-d H:i:s', time() + ($expirationHours * 3600));

        // Start transaction
        Database::beginTransaction();

        // Insert reservation
        $reservationId = Database::insert(
            'reservations',
            [
                'reservation_token' => $token,
                'sponsor_name' => $sponsorData['name'],
                'sponsor_email' => $sponsorData['email'],
                'sponsor_phone' => $sponsorData['phone'] ?? null,
                'sponsor_address' => $sponsorData['address'] ?? null,
                'children_ids' => json_encode($childrenIds),
                'total_children' => count($childrenIds),
                'status' => 'pending',
                'expires_at' => $expiresAt,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]
        );

        if (!$reservationId) {
            Database::rollback();
            return [
                'success' => false,
                'message' => 'Failed to create reservation.'
            ];
        }

        // Mark children as reserved
        foreach ($childrenIds as $childId) {
            Database::update(
                'children',
                ['id' => $childId],
                [
                    'status' => 'pending',
                    'reservation_id' => $reservationId,
                    'reservation_expires_at' => $expiresAt
                ]
            );
        }

        Database::commit();

        return [
            'success' => true,
            'token' => $token,
            'reservation_id' => $reservationId,
            'expires_at' => $expiresAt,
            'message' => 'Reservation created successfully!'
        ];

    } catch (Exception $e) {
        // Only rollback if transaction was started
        try {
            Database::rollback();
        } catch (Exception $rollbackException) {
            // Transaction might not have been started yet, ignore
        }
        error_log('Reservation creation error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while creating your reservation. Please try again.'
        ];
    }
}

/**
 * Get reservation details by token
 *
 * @param string $token Reservation token
 * @return array|null Reservation data or null if not found
 */
function getReservation(string $token): ?array {
    $reservation = Database::fetchRow(
        'SELECT * FROM reservations WHERE reservation_token = :token',
        ['token' => $token]
    );

    if ($reservation) {
        // Decode children IDs
        $reservation['children_ids'] = json_decode($reservation['children_ids'], true);

        // Check if expired
        $reservation['is_expired'] = strtotime($reservation['expires_at']) < time();

        // Get children details
        if (!empty($reservation['children_ids'])) {
            $placeholders = implode(',', array_fill(0, count($reservation['children_ids']), '?'));
            $reservation['children'] = Database::fetchAll(
                "SELECT * FROM children WHERE id IN ($placeholders)",
                $reservation['children_ids']
            );
        }
    }

    return $reservation ?: null;
}

/**
 * Confirm a reservation
 *
 * @param string $token Reservation token
 * @return array ['success' => bool, 'message' => string]
 */
function confirmReservation(string $token): array {
    try {
        $reservation = getReservation($token);

        if (!$reservation) {
            return [
                'success' => false,
                'message' => 'Reservation not found.'
            ];
        }

        if ($reservation['status'] === 'confirmed') {
            return [
                'success' => false,
                'message' => 'This reservation has already been confirmed.'
            ];
        }

        if ($reservation['is_expired']) {
            return [
                'success' => false,
                'message' => 'This reservation has expired.'
            ];
        }

        Database::beginTransaction();

        // Update reservation status
        Database::update(
            'reservations',
            ['reservation_token' => $token],
            [
                'status' => 'confirmed',
                'confirmed_at' => gmdate('Y-m-d H:i:s')
            ]
        );

        // Update children status to sponsored
        foreach ($reservation['children_ids'] as $childId) {
            Database::update(
                'children',
                ['id' => $childId],
                [
                    'status' => 'sponsored',
                    'reservation_expires_at' => null
                ]
            );
        }

        Database::commit();

        return [
            'success' => true,
            'message' => 'Reservation confirmed successfully!'
        ];

    } catch (Exception $e) {
        try {
            Database::rollback();
        } catch (Exception $rollbackException) {
            // Transaction might not have been started yet, ignore
        }
        error_log('Reservation confirmation error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while confirming your reservation.'
        ];
    }
}

/**
 * Cancel a reservation
 *
 * @param string $token Reservation token
 * @return array ['success' => bool, 'message' => string]
 */
function cancelReservation(string $token): array {
    try {
        $reservation = getReservation($token);

        if (!$reservation) {
            return [
                'success' => false,
                'message' => 'Reservation not found.'
            ];
        }

        if ($reservation['status'] === 'confirmed') {
            return [
                'success' => false,
                'message' => 'Cannot cancel a confirmed reservation. Please contact support.'
            ];
        }

        Database::beginTransaction();

        // Update reservation status
        Database::update(
            'reservations',
            ['reservation_token' => $token],
            [
                'status' => 'cancelled',
                'cancelled_at' => gmdate('Y-m-d H:i:s')
            ]
        );

        // Free up the children
        foreach ($reservation['children_ids'] as $childId) {
            Database::update(
                'children',
                ['id' => $childId],
                [
                    'status' => 'available',
                    'reservation_id' => null,
                    'reservation_expires_at' => null
                ]
            );
        }

        Database::commit();

        return [
            'success' => true,
            'message' => 'Reservation cancelled successfully.'
        ];

    } catch (Exception $e) {
        try {
            Database::rollback();
        } catch (Exception $rollbackException) {
            // Transaction might not have been started yet, ignore
        }
        error_log('Reservation cancellation error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while cancelling your reservation.'
        ];
    }
}

/**
 * Clean up expired reservations (for cron job)
 *
 * @return array ['expired_count' => int, 'freed_children' => int]
 */
function cleanupExpiredReservations(): array {
    try {
        // Find expired reservations
        $expiredReservations = Database::fetchAll(
            "SELECT id, children_ids FROM reservations
             WHERE status = 'pending'
             AND expires_at < NOW()"
        );

        if (empty($expiredReservations)) {
            return ['expired_count' => 0, 'freed_children' => 0];
        }

        Database::beginTransaction();

        $expiredCount = 0;
        $freedChildren = 0;

        foreach ($expiredReservations as $reservation) {
            // Update reservation status
            Database::update(
                'reservations',
                ['id' => $reservation['id']],
                ['status' => 'expired']
            );

            // Free up children
            $childrenIds = json_decode($reservation['children_ids'], true);
            foreach ($childrenIds as $childId) {
                Database::update(
                    'children',
                    ['id' => $childId],
                    [
                        'status' => 'available',
                        'reservation_id' => null,
                        'reservation_expires_at' => null
                    ]
                );
                $freedChildren++;
            }

            $expiredCount++;
        }

        Database::commit();

        return [
            'expired_count' => $expiredCount,
            'freed_children' => $freedChildren
        ];

    } catch (Exception $e) {
        try {
            Database::rollback();
        } catch (Exception $rollbackException) {
            // Transaction might not have been started yet, ignore
        }
        error_log('Cleanup expired reservations error: ' . $e->getMessage());
        return ['expired_count' => 0, 'freed_children' => 0];
    }
}

/**
 * Check if children are available for reservation
 *
 * @param array $childrenIds Array of child IDs
 * @return array Array of unavailable child display IDs
 */
function checkChildrenAvailability(array $childrenIds): array {
    $placeholders = implode(',', array_fill(0, count($childrenIds), '?'));

    $unavailable = Database::fetchAll(
        "SELECT CONCAT(f.family_number, c.child_letter) as display_id
         FROM children c
         JOIN families f ON c.family_id = f.id
         WHERE c.id IN ($placeholders)
         AND c.status != 'available'",
        $childrenIds
    );

    return array_column($unavailable, 'display_id');
}

/**
 * Generate a unique reservation token
 *
 * @return string
 */
function generateReservationToken(): string {
    return bin2hex(random_bytes(32)); // 64 character hex string
}

/**
 * Get all reservations for admin view
 *
 * @param string $status Filter by status (optional)
 * @param int $limit Number of results (default: 50)
 * @return array Array of reservations
 */
function getAllReservations(?string $status = null, int $limit = 50): array {
    $sql = "SELECT * FROM reservations";
    $params = [];

    if ($status) {
        $sql .= " WHERE status = :status";
        $params['status'] = $status;
    }

    $sql .= " ORDER BY created_at DESC LIMIT :limit";
    $params['limit'] = $limit;

    $reservations = Database::fetchAll($sql, $params);

    // Decode children IDs for each reservation
    foreach ($reservations as &$reservation) {
        $reservation['children_ids'] = json_decode($reservation['children_ids'], true);
        $reservation['is_expired'] = strtotime($reservation['expires_at']) < time();
    }

    return $reservations;
}
