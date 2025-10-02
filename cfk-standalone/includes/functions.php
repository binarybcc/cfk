<?php
declare(strict_types=1);

/**
 * Christmas for Kids - Helper Functions
 * Core functionality for the sponsorship application
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

/**
 * Children Management Functions
 */

/**
 * Get children with optional filtering and pagination
 */
function getChildren(array $filters = [], int $page = 1, int $limit = null): array {
    $limit = $limit ?? config('children_per_page', 12);
    $offset = ($page - 1) * $limit;
    
    // Base query
    $sql = "
        SELECT c.*, f.family_number, f.family_name,
               CONCAT(f.family_number, c.child_letter) as display_id
        FROM children c 
        JOIN families f ON c.family_id = f.id 
        WHERE 1=1
    ";
    
    $params = [];
    
    // Apply filters
    if (!empty($filters['search'])) {
        $sql .= " AND (c.name LIKE :search OR c.interests LIKE :search OR c.wishes LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    if (!empty($filters['age_category'])) {
        global $ageCategories;
        if (isset($ageCategories[$filters['age_category']])) {
            $category = $ageCategories[$filters['age_category']];
            $sql .= " AND c.age BETWEEN :min_age AND :max_age";
            $params['min_age'] = $category['min'];
            $params['max_age'] = $category['max'];
        }
    }
    
    if (!empty($filters['gender'])) {
        $sql .= " AND c.gender = :gender";
        $params['gender'] = $filters['gender'];
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND c.status = :status";
        $params['status'] = $filters['status'];
    }
    
    if (!empty($filters['family_id'])) {
        $sql .= " AND c.family_id = :family_id";
        $params['family_id'] = $filters['family_id'];
    }
    
    // Default to available children only
    if (!isset($filters['status'])) {
        $sql .= " AND c.status = 'available'";
    }
    
    // Order by family, then by child letter
    $sql .= " ORDER BY f.family_number, c.child_letter";
    $sql .= " LIMIT :limit OFFSET :offset";
    
    $params['limit'] = $limit;
    $params['offset'] = $offset;
    
    return Database::fetchAll($sql, $params);
}

/**
 * Get total count of children matching filters
 */
function getChildrenCount(array $filters = []): int {
    $sql = "
        SELECT COUNT(*) as total
        FROM children c 
        JOIN families f ON c.family_id = f.id 
        WHERE 1=1
    ";
    
    $params = [];
    
    // Apply same filters as getChildren()
    if (!empty($filters['search'])) {
        $sql .= " AND (c.name LIKE :search OR c.interests LIKE :search OR c.wishes LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    if (!empty($filters['age_category'])) {
        global $ageCategories;
        if (isset($ageCategories[$filters['age_category']])) {
            $category = $ageCategories[$filters['age_category']];
            $sql .= " AND c.age BETWEEN :min_age AND :max_age";
            $params['min_age'] = $category['min'];
            $params['max_age'] = $category['max'];
        }
    }
    
    if (!empty($filters['gender'])) {
        $sql .= " AND c.gender = :gender";
        $params['gender'] = $filters['gender'];
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND c.status = :status";
        $params['status'] = $filters['status'];
    }
    
    if (!empty($filters['family_id'])) {
        $sql .= " AND c.family_id = :family_id";
        $params['family_id'] = $filters['family_id'];
    }
    
    // Default to available children only
    if (!isset($filters['status'])) {
        $sql .= " AND c.status = 'available'";
    }
    
    $result = Database::fetchRow($sql, $params);
    return (int) $result['total'];
}

/**
 * Get single child by ID with family information
 */
function getChildById(int $childId): ?array {
    $sql = "
        SELECT c.*, f.family_number, f.family_name, f.notes as family_notes,
               CONCAT(f.family_number, c.child_letter) as display_id
        FROM children c 
        JOIN families f ON c.family_id = f.id 
        WHERE c.id = :id
    ";
    
    return Database::fetchRow($sql, ['id' => $childId]);
}

/**
 * Get family members (siblings) for a child
 */
function getFamilyMembers(int $familyId, int $excludeChildId = null): array {
    $sql = "
        SELECT c.*, CONCAT(f.family_number, c.child_letter) as display_id
        FROM children c 
        JOIN families f ON c.family_id = f.id 
        WHERE c.family_id = :family_id
    ";
    
    $params = ['family_id' => $familyId];
    
    if ($excludeChildId) {
        $sql .= " AND c.id != :exclude_id";
        $params['exclude_id'] = $excludeChildId;
    }
    
    $sql .= " ORDER BY c.child_letter";
    
    return Database::fetchAll($sql, $params);
}

/**
 * Sponsorship Functions
 */

/**
 * Create a sponsorship request
 */
function createSponsorship(int $childId, array $sponsorData): int {
    // First, mark child as pending
    Database::update('children', ['status' => 'pending'], ['id' => $childId]);
    
    // Create sponsorship record
    $sponsorshipData = [
        'child_id' => $childId,
        'sponsor_name' => sanitizeString($sponsorData['name']),
        'sponsor_email' => sanitizeEmail($sponsorData['email']),
        'sponsor_phone' => sanitizeString($sponsorData['phone'] ?? ''),
        'sponsor_address' => sanitizeString($sponsorData['address'] ?? ''),
        'gift_preference' => $sponsorData['gift_preference'] ?? 'shopping',
        'special_message' => sanitizeString($sponsorData['message'] ?? ''),
        'status' => 'pending'
    ];
    
    return Database::insert('sponsorships', $sponsorshipData);
}

/**
 * Get sponsorship by ID
 */
function getSponsorshipById(int $sponsorshipId): ?array {
    $sql = "
        SELECT s.*, c.name as child_name, 
               CONCAT(f.family_number, c.child_letter) as child_display_id
        FROM sponsorships s
        JOIN children c ON s.child_id = c.id
        JOIN families f ON c.family_id = f.id
        WHERE s.id = :id
    ";
    
    return Database::fetchRow($sql, ['id' => $sponsorshipId]);
}

/**
 * Utility Functions
 */

/**
 * Format age display
 */
function formatAge(int $age): string {
    return $age . ' year' . ($age !== 1 ? 's' : '') . ' old';
}

/**
 * Get age category for a given age
 */
function getAgeCategory(int $age): string {
    global $ageCategories;
    
    foreach ($ageCategories as $key => $category) {
        if ($age >= $category['min'] && $age <= $category['max']) {
            return $category['label'];
        }
    }
    
    return 'Other';
}

/**
 * Generate pagination HTML
 */
function generatePagination(int $currentPage, int $totalPages, string $baseUrl): string {
    if ($totalPages <= 1) return '';
    
    $html = '<nav class="pagination"><ul>';
    
    // Previous
    if ($currentPage > 1) {
        $html .= '<li><a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">&laquo; Previous</a></li>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<li><a href="' . $baseUrl . '?page=1">1</a></li>';
        if ($start > 2) $html .= '<li><span>...</span></li>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $class = $i === $currentPage ? ' class="active"' : '';
        $html .= '<li><a href="' . $baseUrl . '?page=' . $i . '"' . $class . '>' . $i . '</a></li>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<li><span>...</span></li>';
        $html .= '<li><a href="' . $baseUrl . '?page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Next
    if ($currentPage < $totalPages) {
        $html .= '<li><a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Next &raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Simple success/error message system
 */
function setMessage(string $message, string $type = 'success'): void {
    $_SESSION['cfk_message'] = ['text' => $message, 'type' => $type];
}

function getMessage(): ?array {
    if (isset($_SESSION['cfk_message'])) {
        $message = $_SESSION['cfk_message'];
        unset($_SESSION['cfk_message']);
        return $message;
    }
    return null;
}

/**
 * Photo handling - Uses avatar system instead of real photos for privacy
 */
function getPhotoUrl(string $filename = null, array $child = null): string {
    // ALWAYS use avatars - no real photos for privacy protection
    if ($child && isset($child['age']) && isset($child['gender'])) {
        require_once __DIR__ . '/avatar_manager.php';
        return CFK_Avatar_Manager::getAvatarForChild($child);
    }
    
    // Fallback avatar if child data not available
    require_once __DIR__ . '/avatar_manager.php';
    return CFK_Avatar_Manager::generateSilhouettedAvatar('default');
}

/**
 * Security functions
 */
function isLoggedIn(): bool {
    return isset($_SESSION['cfk_admin_id']) && !empty($_SESSION['cfk_admin_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . baseUrl('admin/login.php'));
        exit;
    }
}

/**
 * Simple validation functions
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateRequired(array $data, array $requiredFields): array {
    $errors = [];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $errors[] = ucfirst($field) . ' is required';
        }
    }
    return $errors;
}