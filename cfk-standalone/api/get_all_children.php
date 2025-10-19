<?php
/**
 * API Endpoint: Get All Available Children
 * Returns all children with 'available' status for client-side filtering
 */

// Start output buffering to catch any stray output
ob_start();

// Define app constant
define('CFK_APP', true);

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Clear any output from config loading
ob_clean();

// Set JSON header
header('Content-Type: application/json');

try {
    // Get all available children (no pagination, no filters)
    // Only get children with 'available' status
    $db = Database::getConnection();

    $query = "
        SELECT
            c.*,
            f.family_number,
            CONCAT(f.family_number, COALESCE(c.child_letter, '')) as display_id,
            CONCAT_WS(', ',
                NULLIF(CONCAT('Shirt: ', c.shirt_size), 'Shirt: '),
                NULLIF(CONCAT('Pant: ', c.pant_size), 'Pant: '),
                NULLIF(CONCAT('Jacket: ', c.jacket_size), 'Jacket: ')
            ) as clothing_sizes,
            COUNT(DISTINCT s.id) as sibling_count
        FROM children c
        LEFT JOIN families f ON c.family_id = f.id
        LEFT JOIN children s ON c.family_id = s.family_id
            AND s.id != c.id
            AND s.status = 'available'
        WHERE c.status = 'available'
        GROUP BY c.id
        ORDER BY c.family_id, c.age
    ";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format children for frontend
    $formattedChildren = array_map(function($child) {
        return [
            'id' => (int)$child['id'],
            'display_id' => $child['display_id'],
            'family_id' => (int)$child['family_id'],
            'family_number' => $child['family_number'],
            'age' => (int)$child['age'],
            'gender' => $child['gender'],
            'grade' => $child['grade'] ?? null,
            'interests' => $child['interests'] ?? null,
            'wishes' => $child['wishes'] ?? null,
            'clothing_sizes' => $child['clothing_sizes'] ?: null,
            'shoe_size' => $child['shoe_size'] ?? null,
            'special_needs' => $child['special_needs'] ?? null,
            'status' => $child['status'],
            'sibling_count' => (int)$child['sibling_count']
        ];
    }, $children);

    // Also get all family members for modals (including non-available ones)
    $familyQuery = "
        SELECT
            c.id,
            c.family_id,
            c.age,
            c.gender,
            c.grade,
            c.interests,
            c.wishes,
            c.shoe_size,
            c.status,
            f.family_number,
            CONCAT(f.family_number, COALESCE(c.child_letter, '')) as display_id,
            CONCAT_WS(', ',
                NULLIF(CONCAT('Shirt: ', c.shirt_size), 'Shirt: '),
                NULLIF(CONCAT('Pant: ', c.pant_size), 'Pant: '),
                NULLIF(CONCAT('Jacket: ', c.jacket_size), 'Jacket: ')
            ) as clothing_sizes
        FROM children c
        LEFT JOIN families f ON c.family_id = f.id
        WHERE c.family_id IN (
            SELECT DISTINCT family_id FROM children WHERE status = 'available'
        )
        ORDER BY c.family_id, c.age
    ";

    $familyStmt = $db->prepare($familyQuery);
    $familyStmt->execute();
    $allFamilyMembers = $familyStmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by family_id
    $siblingsByFamily = [];
    foreach ($allFamilyMembers as $member) {
        $familyId = (int)$member['family_id'];
        if (!isset($siblingsByFamily[$familyId])) {
            $siblingsByFamily[$familyId] = [];
        }
        $siblingsByFamily[$familyId][] = [
            'id' => (int)$member['id'],
            'display_id' => $member['display_id'],
            'family_id' => $familyId,
            'age' => (int)$member['age'],
            'gender' => $member['gender'],
            'grade' => $member['grade'] ?? null,
            'interests' => $member['interests'] ?? null,
            'wishes' => $member['wishes'] ?? null,
            'clothing_sizes' => $member['clothing_sizes'] ?: null,
            'shoe_size' => $member['shoe_size'] ?? null,
            'status' => $member['status']
        ];
    }

    echo json_encode([
        'success' => true,
        'children' => $formattedChildren,
        'siblings' => $siblingsByFamily,
        'count' => count($formattedChildren)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load children',
        'message' => config('debug') ? $e->getMessage() : 'Internal server error'
    ]);
}
