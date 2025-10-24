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
 * Text Cleaning Functions
 */

/**
 * Clean up wishes text - remove .Wishlist: prefix if present
 *
 * @param string $wishes Raw wishes text from database
 * @return string Cleaned wishes text
 */
function cleanWishesText(string $wishes): string
{
    // Remove .Wishlist: or Wishlist: prefix (case-insensitive, with or without dot)
    $cleaned = preg_replace('/^\.?\s*wish\s*list\s*:\s*/i', '', trim($wishes));
    return $cleaned;
}

/**
 * Children Management Functions
 */

/**
 * Get children with optional filtering and pagination
 */
function getChildren(array $filters = [], int $page = 1, int $limit = null): array
{
    $limit = $limit ?? config('children_per_page', 12);
    $offset = ($page - 1) * $limit;

    // Base query
    $sql = "
        SELECT c.*, f.family_number,
               CONCAT(f.family_number, c.child_letter) as display_id
        FROM children c
        JOIN families f ON c.family_id = f.id
        WHERE 1=1
    ";

    $params = [];

    // Apply filters
    if (!empty($filters['search'])) {
        $searchValue = '%' . $filters['search'] . '%';
        $sql .= " AND (CONCAT(f.family_number, c.child_letter) LIKE :search1 OR c.interests LIKE :search2 OR c.wishes LIKE :search3)";
        $params['search1'] = $searchValue;
        $params['search2'] = $searchValue;
        $params['search3'] = $searchValue;
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
    // Note: Using literal values for LIMIT/OFFSET to avoid PDO binding issues
    $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    return Database::fetchAll($sql, $params);
}

/**
 * Get total count of children matching filters
 */
function getChildrenCount(array $filters = []): int
{
    $sql = "
        SELECT COUNT(*) as total
        FROM children c 
        JOIN families f ON c.family_id = f.id 
        WHERE 1=1
    ";

    $params = [];

    // Apply same filters as getChildren()
    if (!empty($filters['search'])) {
        $searchValue = '%' . $filters['search'] . '%';
        $sql .= " AND (CONCAT(f.family_number, c.child_letter) LIKE :search1 OR c.interests LIKE :search2 OR c.wishes LIKE :search3)";
        $params['search1'] = $searchValue;
        $params['search2'] = $searchValue;
        $params['search3'] = $searchValue;
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
function getChildById(int $childId): ?array
{
    $sql = "
        SELECT c.*, f.family_number, f.notes as family_notes,
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
function getFamilyMembers(int $familyId, int $excludeChildId = null): array
{
    $sql = "
        SELECT c.*, f.family_number, CONCAT(f.family_number, c.child_letter) as display_id
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
 * Get count of available siblings in a family
 */
function getSiblingCount(int $familyId): int
{
    $sql = "
        SELECT COUNT(*) as count
        FROM children
        WHERE family_id = :family_id AND status = 'available'
    ";

    $result = Database::fetchRow($sql, ['family_id' => $familyId]);
    return (int) ($result['count'] ?? 0);
}

/**
 * Get age/gender-appropriate placeholder avatar image path
 */
function getPlaceholderImage(int $age, string $gender): string
{
    $baseUrl = baseUrl('assets/images/');

    // Age categories
    if ($age <= 5) {
        return $baseUrl . ($gender === 'M' ? 'b-4boysm.png' : 'b-4girlsm.png');
    } elseif ($age <= 11) {
        return $baseUrl . ($gender === 'M' ? 'elementaryboysm.png' : 'elementarygirlsm.png');
    } elseif ($age <= 14) {
        return $baseUrl . ($gender === 'M' ? 'middleboysm.png' : 'middlegirlsm.png');
    } else {
        return $baseUrl . ($gender === 'M' ? 'hsboysm.png' : 'hsgirlsm.png');
    }
}

/**
 * Get family information by family ID
 */
function getFamilyById(int $familyId): ?array
{
    $sql = "SELECT * FROM families WHERE id = :family_id";
    return Database::fetchRow($sql, ['family_id' => $familyId]);
}

/**
 * Get family information by family number (user-facing ID like 201, 202, etc.)
 */
function getFamilyByNumber(string $familyNumber): ?array
{
    $sql = "SELECT * FROM families WHERE family_number = :family_number";
    return Database::fetchRow($sql, ['family_number' => $familyNumber]);
}

/**
 * Get all family members by family number
 */
function getFamilyMembersByNumber(string $familyNumber, int $excludeChildId = null): array
{
    $sql = "
        SELECT c.*, f.family_number, CONCAT(f.family_number, c.child_letter) as display_id
        FROM children c
        JOIN families f ON c.family_id = f.id
        WHERE f.family_number = :family_number
    ";

    $params = ['family_number' => $familyNumber];

    if ($excludeChildId) {
        $sql .= " AND c.id != :exclude_id";
        $params['exclude_id'] = $excludeChildId;
    }

    $sql .= " ORDER BY c.child_letter";

    return Database::fetchAll($sql, $params);
}

/**
 * Eager load family members for multiple children (prevents N+1 queries)
 * Returns array indexed by family_id containing arrays of siblings
 */
function eagerLoadFamilyMembers(array $children): array
{
    if (empty($children)) {
        return [];
    }

    // Get unique family IDs
    $familyIds = array_unique(array_column($children, 'family_id'));

    if (empty($familyIds)) {
        return [];
    }

    // Create named parameters for PDO
    $params = [];
    $placeholders = [];
    foreach ($familyIds as $index => $familyId) {
        $paramName = 'family_id_' . $index;
        $placeholders[] = ':' . $paramName;
        $params[$paramName] = $familyId;
    }

    $placeholderString = implode(',', $placeholders);

    $sql = "
        SELECT c.*, f.family_number,
               CONCAT(f.family_number, c.child_letter) as display_id
        FROM children c
        JOIN families f ON c.family_id = f.id
        WHERE c.family_id IN ($placeholderString)
        ORDER BY f.family_number, c.child_letter
    ";

    $allSiblings = Database::fetchAll($sql, $params);

    // Group by family_id
    $siblingsByFamily = [];
    foreach ($allSiblings as $sibling) {
        $siblingsByFamily[$sibling['family_id']][] = $sibling;
    }

    return $siblingsByFamily;
}

/**
 * Sponsorship Functions
 */

/**
 * Create a sponsorship request
 */
function createSponsorship(int $childId, array $sponsorData): int
{
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
function getSponsorshipById(int $sponsorshipId): ?array
{
    $sql = "
        SELECT s.*, CONCAT(f.family_number, c.child_letter) as child_name,
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
function formatAge(int $age): string
{
    return $age . ' year' . ($age !== 1 ? 's' : '') . ' old';
}

/**
 * Get age category for a given age
 */
function getAgeCategory(int $age): string
{
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
 * Note: Uses 'p' parameter for page number to avoid conflict with 'page' routing parameter
 */
function generatePagination(int $currentPage, int $totalPages, string $baseUrl): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav class="pagination"><ul>';

    // Previous
    if ($currentPage > 1) {
        $html .= '<li><a href="' . $baseUrl . '&p=' . ($currentPage - 1) . '">&laquo; Previous</a></li>';
    }

    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<li><a href="' . $baseUrl . '&p=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li><span>...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $class = $i === $currentPage ? ' class="active"' : '';
        $html .= '<li><a href="' . $baseUrl . '&p=' . $i . '"' . $class . '>' . $i . '</a></li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li><span>...</span></li>';
        }
        $html .= '<li><a href="' . $baseUrl . '&p=' . $totalPages . '">' . $totalPages . '</a></li>';
    }

    // Next
    if ($currentPage < $totalPages) {
        $html .= '<li><a href="' . $baseUrl . '&p=' . ($currentPage + 1) . '">Next &raquo;</a></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}

/**
 * Simple success/error message system
 */
function setMessage(string $message, string $type = 'success'): void
{
    $_SESSION['cfk_message'] = ['text' => $message, 'type' => $type];
}

function getMessage(): ?array
{
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
function getPhotoUrl(string $filename = null, array $child = null): string
{
    // ALWAYS use avatars - no real photos for privacy protection
    if ($child && isset($child['age']) && isset($child['gender'])) {
        return \CFK\Avatar\Manager::getAvatarForChild($child);
    }

    // Fallback avatar if child data not available
    return baseUrl('assets/images/b-4girlsm.png');
}

/**
 * Security functions
 */

/**
 * Regenerate session ID periodically (prevents session fixation)
 */
function regenerateSessionIfNeeded(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Regenerate every 30 minutes
        $regenerateInterval = 1800; // 30 minutes

        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > $regenerateInterval) {
            session_regenerate_id(true); // Delete old session
            $_SESSION['last_regeneration'] = time();
        }
    }
}

function isLoggedIn(): bool
{
    regenerateSessionIfNeeded();
    return isset($_SESSION['cfk_admin_id']) && !empty($_SESSION['cfk_admin_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . baseUrl('admin/login.php'));
        exit;
    }
}

/**
 * Simple validation functions
 */
function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateRequired(array $data, array $requiredFields): array
{
    $errors = [];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $errors[] = ucfirst($field) . ' is required';
        }
    }
    return $errors;
}

/**
 * UI Helper Functions
 */

/**
 * Render standardized button HTML with accessibility and consistency
 *
 * This function generates consistent button HTML across the application,
 * supporting both link buttons (<a>) and form buttons (<button>).
 *
 * @param string $text Button text (will be sanitized)
 * @param string|null $url URL for link buttons (null for form buttons)
 * @param string $type Button type: 'primary', 'secondary', 'success', 'danger', 'outline', 'info', 'warning'
 * @param array $options Additional options:
 *   - 'size': 'large', 'small', or default (string)
 *   - 'id': Element ID (string)
 *   - 'class': Additional CSS classes (string)
 *   - 'attributes': Array of additional HTML attributes (array)
 *   - 'onclick': JavaScript onclick handler (string)
 *   - 'block': Make button full-width (bool)
 *   - 'submit': For button elements, type="submit" instead of type="button" (bool)
 *   - 'target': For links, target attribute like '_blank' (string)
 *
 * @return string HTML button/link element
 *
 * @example
 * // Primary link button
 * echo renderButton('View Profile', '?page=child&id=5', 'primary');
 *
 * @example
 * // Large success button with custom class
 * echo renderButton('Submit Form', null, 'success', [
 *     'size' => 'large',
 *     'submit' => true,
 *     'id' => 'submitBtn'
 * ]);
 *
 * @example
 * // Button with Zeffy donation modal
 * echo renderButton('Donate Now', null, 'success', [
 *     'attributes' => [
 *         'zeffy-form-link' => 'https://www.zeffy.com/embed/donation-form/...'
 *     ]
 * ]);
 */
function renderButton(string $text, ?string $url = null, string $type = 'primary', array $options = []): string
{
    // Sanitize text
    $text = sanitizeString($text);

    // Build CSS classes
    $classes = ['btn'];

    // Add type class
    $validTypes = ['primary', 'secondary', 'success', 'danger', 'outline', 'info', 'warning'];
    if (in_array($type, $validTypes)) {
        $classes[] = 'btn-' . $type;
    } else {
        $classes[] = 'btn-primary'; // Default fallback
    }

    // Add size class
    if (!empty($options['size'])) {
        $validSizes = ['small', 'large'];
        if (in_array($options['size'], $validSizes)) {
            $classes[] = 'btn-' . $options['size'];
        }
    }

    // Add block class
    if (!empty($options['block'])) {
        $classes[] = 'btn-block';
    }

    // Add custom classes
    if (!empty($options['class'])) {
        $classes[] = sanitizeString($options['class']);
    }

    $classString = implode(' ', $classes);

    // Build attributes array
    $attrs = [];

    // Add ID if provided
    if (!empty($options['id'])) {
        $attrs['id'] = sanitizeString($options['id']);
    }

    // Add onclick if provided
    if (!empty($options['onclick'])) {
        $attrs['onclick'] = sanitizeString($options['onclick']);
    }

    // Add custom attributes
    if (!empty($options['attributes']) && is_array($options['attributes'])) {
        foreach ($options['attributes'] as $key => $value) {
            // Allow data-* and zeffy-* attributes without sanitization
            if (str_starts_with($key, 'data-') || str_starts_with($key, 'zeffy-')) {
                $attrs[$key] = $value;
            } else {
                $attrs[sanitizeString($key)] = sanitizeString($value);
            }
        }
    }

    // Build attribute string
    $attrString = '';
    foreach ($attrs as $key => $value) {
        $attrString .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
    }

    // Render link or button
    if ($url !== null) {
        // Render as <a> tag
        $target = '';
        if (!empty($options['target'])) {
            $target = ' target="' . sanitizeString($options['target']) . '"';
        }

        return sprintf(
            '<a href="%s" class="%s"%s%s>%s</a>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $classString,
            $attrString,
            $target,
            $text
        );
    } else {
        // Render as <button> tag
        $buttonType = !empty($options['submit']) ? 'submit' : 'button';

        return sprintf(
            '<button type="%s" class="%s"%s>%s</button>',
            $buttonType,
            $classString,
            $attrString,
            $text
        );
    }
}

/**
 * Format a datetime string for display
 */
function formatDateTime(?string $datetime): string
{
    if (empty($datetime)) {
        return "";
    }
    try {
        $dt = new DateTime($datetime);
        return $dt->format("M j, Y g:i A");
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Format a date string for display
 */
function formatDate(?string $date): string
{
    if (empty($date)) {
        return "";
    }
    try {
        $dt = new DateTime($date);
        return $dt->format("M j, Y");
    } catch (Exception $e) {
        return $date;
    }
}
