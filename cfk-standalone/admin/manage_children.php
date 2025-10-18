<?php
declare(strict_types=1);

/**
 * Admin - Manage Children
 * CRUD operations for child profiles - non-coder friendly interface
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Manage Children';
$message = '';
$messageType = '';

// Handle actions
if ($_POST && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Security token invalid. Please try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add_child':
                $result = addChild($_POST);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'edit_child':
                $result = editChild($_POST);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'delete_child':
                $childId = sanitizeInt($_POST['child_id'] ?? 0);
                $result = deleteChild($childId);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'toggle_status':
                $childId = sanitizeInt($_POST['child_id'] ?? 0);
                $newStatus = sanitizeString($_POST['new_status'] ?? '');
                $result = updateChildStatus($childId, $newStatus);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$familyFilter = $_GET['family'] ?? 'all';
$ageFilter = $_GET['age'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';
$page = max(1, sanitizeInt($_GET['page'] ?? 1));
$perPage = 20;

// Build query based on filters
$whereConditions = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereConditions[] = "c.status = ?";
    $params[] = $statusFilter;
}

if ($familyFilter !== 'all') {
    $whereConditions[] = "f.family_number = ?";
    $params[] = $familyFilter;
}

if ($ageFilter !== 'all') {
    switch ($ageFilter) {
        case 'birth-4':
            $whereConditions[] = "c.age BETWEEN 0 AND 4";
            break;
        case 'elementary':
            $whereConditions[] = "c.age BETWEEN 5 AND 10";
            break;
        case 'middle':
            $whereConditions[] = "c.age BETWEEN 11 AND 13";
            break;
        case 'high':
            $whereConditions[] = "c.age BETWEEN 14 AND 18";
            break;
    }
}

if (!empty($searchQuery)) {
    $whereConditions[] = "(CONCAT(f.family_number, c.child_letter) LIKE ? OR c.interests LIKE ? OR c.wishes LIKE ? OR f.family_number LIKE ?)";
    $searchTerm = '%' . $searchQuery . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM children c JOIN families f ON c.family_id = f.id $whereClause";
$totalCount = Database::fetchRow($countQuery, $params)['total'] ?? 0;
$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// Get children data
$children = Database::fetchAll("
    SELECT c.*, f.family_number,
           CONCAT(f.family_number, c.child_letter) as display_id,
           (SELECT COUNT(*) FROM sponsorships s WHERE s.child_id = c.id AND s.status IN ('pending', 'confirmed')) as sponsorship_count
    FROM children c
    JOIN families f ON c.family_id = f.id
    $whereClause
    ORDER BY f.family_number ASC, c.child_letter ASC
    LIMIT $perPage OFFSET $offset
", $params);

// Get families for dropdowns
$families = Database::fetchAll("SELECT id, family_number FROM families ORDER BY family_number ASC");

// Functions for CRUD operations
function addChild($data): array {
    try {
        // Validate required fields
        $validation = validateChildData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => 'Please fix the following errors: ' . implode(', ', $validation['errors'])];
        }
        
        // Check if family exists
        $family = Database::fetchRow("SELECT id FROM families WHERE id = ?", [$data['family_id']]);
        if (!$family) {
            return ['success' => false, 'message' => 'Selected family does not exist'];
        }
        
        // Check if child_letter is unique within family
        $existing = Database::fetchRow(
            "SELECT id FROM children WHERE family_id = ? AND child_letter = ?",
            [$data['family_id'], $data['child_letter']]
        );
        if ($existing) {
            return ['success' => false, 'message' => 'Child letter already exists in this family'];
        }
        
        $childId = Database::insert('children', [
            'family_id' => sanitizeInt($data['family_id']),
            'child_letter' => sanitizeString($data['child_letter']),
            'name' => sanitizeString($data['name']),
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
            'special_needs' => sanitizeString($data['special_needs'] ?? ''),
            'status' => 'available'
        ]);
        
        return ['success' => true, 'message' => 'Child added successfully'];
        
    } catch (Exception $e) {
        error_log('Failed to add child: ' . $e->getMessage());
        return ['success' => false, 'message' => 'System error occurred. Please try again.'];
    }
}

function editChild($data): array {
    try {
        $childId = sanitizeInt($data['child_id'] ?? 0);
        if (!$childId) {
            return ['success' => false, 'message' => 'Invalid child ID'];
        }
        
        // Validate data
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
            'name' => sanitizeString($data['name']),
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

function deleteChild($childId): array {
    try {
        if (!$childId) {
            return ['success' => false, 'message' => 'Invalid child ID'];
        }
        
        // Check if child has any sponsorships
        $sponsorships = Database::fetchRow(
            "SELECT COUNT(*) as count FROM sponsorships WHERE child_id = ? AND status IN ('pending', 'confirmed')",
            [$childId]
        );
        
        if ($sponsorships['count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete child with active sponsorships'];
        }
        
        Database::delete('children', ['id' => $childId]);
        
        return ['success' => true, 'message' => 'Child deleted successfully'];
        
    } catch (Exception $e) {
        error_log('Failed to delete child: ' . $e->getMessage());
        return ['success' => false, 'message' => 'System error occurred. Please try again.'];
    }
}

function updateChildStatus($childId, $newStatus): array {
    try {
        if (!$childId || !in_array($newStatus, ['available', 'pending', 'sponsored', 'inactive'])) {
            return ['success' => false, 'message' => 'Invalid parameters'];
        }
        
        Database::update('children', ['status' => $newStatus], ['id' => $childId]);
        
        return ['success' => true, 'message' => 'Child status updated successfully'];
        
    } catch (Exception $e) {
        error_log('Failed to update child status: ' . $e->getMessage());
        return ['success' => false, 'message' => 'System error occurred. Please try again.'];
    }
}

function validateChildData($data): array {
    $errors = [];
    
    if (empty(trim($data['name'] ?? ''))) {
        $errors[] = 'Name is required';
    }
    
    $age = sanitizeInt($data['age'] ?? 0);
    if ($age < 1 || $age > 18) {
        $errors[] = 'Age must be between 1 and 18';
    }
    
    if (empty(trim($data['gender'] ?? '')) || !in_array($data['gender'], ['M', 'F'])) {
        $errors[] = 'Valid gender is required';
    }
    
    if (empty(trim($data['child_letter'] ?? '')) || !preg_match('/^[A-Z]$/', $data['child_letter'])) {
        $errors[] = 'Child letter must be a single uppercase letter (A-Z)';
    }
    
    if (empty(sanitizeInt($data['family_id'] ?? 0))) {
        $errors[] = 'Family selection is required';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

include __DIR__ . '/includes/admin_header.php';
?>

<!-- Page-specific styles -->
<style>
.filters {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 500;
    color: #333;
    font-size: 0.9rem;
}

.filter-group input,
.filter-group select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

.children-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.child-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.child-header {
    background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
    color: white;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.child-id {
    font-size: 1.2rem;
    font-weight: bold;
}

.child-body {
    padding: 1rem;
}

.child-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.2rem;
}

.info-value {
    font-weight: 500;
}

.child-details {
    margin: 1rem 0;
}

.detail-section {
    margin-bottom: 0.75rem;
}

.detail-label {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.2rem;
}

.detail-value {
    font-size: 0.9rem;
    line-height: 1.4;
}

.child-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.modal-content {
    background: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 700px;
    max-height: 80vh;
    overflow: hidden;
    position: relative;
}

.modal-header {
    background: #2c5530;
    color: white;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 2rem;
    max-height: 60vh;
    overflow-y: auto;
}

.close {
    font-size: 1.5rem;
    cursor: pointer;
    color: white;
    background: none;
    border: none;
}

.close:hover {
    opacity: 0.7;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group-full {
    grid-column: span 2;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.form-group textarea {
    min-height: 80px;
    resize: vertical;
}

.stats-summary {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    text-align: center;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.empty-state h3 {
    margin-bottom: 1rem;
    color: #333;
}

@media (max-width: 768px) {
    .filters {
        grid-template-columns: 1fr;
    }

    .children-grid {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group-full {
        grid-column: span 1;
    }

    .child-info {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Summary Stats -->
        <div class="stats-summary">
            <strong>Total Children: <?php echo $totalCount; ?></strong>
            <?php if ($statusFilter !== 'all' || $familyFilter !== 'all' || $ageFilter !== 'all' || !empty($searchQuery)): ?>
                (filtered from <?php echo getChildrenCount([]); ?> total)
            <?php endif; ?>
        </div>

        <!-- Top Actions -->
        <div class="top-actions">
            <button onclick="showAddModal()" class="btn btn-primary btn-large">
                ➕ Add New Child
            </button>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters" id="filterForm">
            <input type="hidden" name="page" value="1">
            
            <div class="filter-group">
                <label for="status">Status:</label>
                <select id="status" name="status" onchange="document.getElementById('filterForm').submit()">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="available" <?php echo $statusFilter === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="sponsored" <?php echo $statusFilter === 'sponsored' ? 'selected' : ''; ?>>Sponsored</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="family">Family:</label>
                <select id="family" name="family" onchange="document.getElementById('filterForm').submit()">
                    <option value="all" <?php echo $familyFilter === 'all' ? 'selected' : ''; ?>>All Families</option>
                    <?php foreach ($families as $family): ?>
                        <option value="<?php echo $family['family_number']; ?>"
                                <?php echo $familyFilter === $family['family_number'] ? 'selected' : ''; ?>>
                            Family <?php echo $family['family_number']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="age">Age Group:</label>
                <select id="age" name="age" onchange="document.getElementById('filterForm').submit()">
                    <option value="all" <?php echo $ageFilter === 'all' ? 'selected' : ''; ?>>All Ages</option>
                    <option value="birth-4" <?php echo $ageFilter === 'birth-4' ? 'selected' : ''; ?>>Birth to 4 Years</option>
                    <option value="elementary" <?php echo $ageFilter === 'elementary' ? 'selected' : ''; ?>>Elementary (5-10)</option>
                    <option value="middle" <?php echo $ageFilter === 'middle' ? 'selected' : ''; ?>>Middle School (11-13)</option>
                    <option value="high" <?php echo $ageFilter === 'high' ? 'selected' : ''; ?>>High School (14-18)</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       value="<?php echo sanitizeString($searchQuery); ?>"
                       placeholder="Name, interests, wishes..."
                       onchange="document.getElementById('filterForm').submit()">
            </div>
        </form>

        <!-- Children Grid -->
        <?php if (empty($children)): ?>
            <div class="empty-state">
                <h3>No Children Found</h3>
                <p>No children match the current filters. Try adjusting your search criteria or add a new child.</p>
                <button onclick="showAddModal()" class="btn btn-primary">Add First Child</button>
            </div>
        <?php else: ?>
            <div class="children-grid">
                <?php foreach ($children as $child): ?>
                    <div class="child-card">
                        <div class="child-header">
                            <div class="child-id"><?php echo sanitizeString($child['display_id']); ?></div>
                            <span class="status-badge status-<?php echo $child['status']; ?>">
                                <?php echo ucfirst($child['status']); ?>
                            </span>
                        </div>
                        
                        <div class="child-body">
                            <div class="child-info">
                                <div class="info-item">
                                    <div class="info-label">Family Code</div>
                                    <div class="info-value"><?php echo sanitizeString($child['display_id']); ?></div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Age</div>
                                    <div class="info-value"><?php echo $child['age']; ?> years</div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Grade</div>
                                    <div class="info-value"><?php echo !empty($child['grade']) ? sanitizeString($child['grade']) : 'Not specified'; ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Gender</div>
                                    <div class="info-value"><?php echo $child['gender'] === 'M' ? 'Male' : 'Female'; ?></div>
                                </div>
                            </div>
                            
                            <div class="child-details">
                                <?php if (!empty($child['interests'])): ?>
                                    <div class="detail-section">
                                        <div class="detail-label">Interests</div>
                                        <div class="detail-value"><?php echo sanitizeString($child['interests']); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($child['wishes'])): ?>
                                    <div class="detail-section">
                                        <div class="detail-label">Christmas Wishes</div>
                                        <div class="detail-value"><?php echo sanitizeString(substr($child['wishes'], 0, 100)); ?><?php echo strlen($child['wishes']) > 100 ? '...' : ''; ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="detail-section">
                                    <div class="detail-label">Family</div>
                                    <div class="detail-value">Family <?php echo sanitizeString($child['family_number']); ?></div>
                                </div>
                                
                                <?php if ($child['sponsorship_count'] > 0): ?>
                                    <div class="detail-section">
                                        <div class="detail-label">Active Sponsorships</div>
                                        <div class="detail-value"><?php echo $child['sponsorship_count']; ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="child-actions">
                                <button onclick="showEditModal(<?php echo $child['id']; ?>)" class="btn btn-primary btn-small">
                                    Edit
                                </button>
                                
                                <?php if ($child['status'] === 'available'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                                        <input type="hidden" name="new_status" value="inactive">
                                        <button type="submit" class="btn btn-warning btn-small">Deactivate</button>
                                    </form>
                                <?php elseif ($child['status'] === 'inactive'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                                        <input type="hidden" name="new_status" value="available">
                                        <button type="submit" class="btn btn-success btn-small">Activate</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($child['sponsorship_count'] == 0): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="delete_child">
                                        <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                                        <button type="submit" 
                                                class="btn btn-danger btn-small"
                                                onclick="return confirm('Are you sure you want to delete this child? This action cannot be undone.')">
                                            Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">← Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Child Modal -->
    <div id="childModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Child</h3>
                <button class="close" onclick="hideChildModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="childForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" id="formAction" value="add_child">
                    <input type="hidden" name="child_id" id="childId" value="">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="childName">Name *</label>
                            <input type="text" id="childName" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="childAge">Age *</label>
                            <input type="number" id="childAge" name="age" min="1" max="18" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="childGender">Gender *</label>
                            <select id="childGender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="childGrade">Grade</label>
                            <input type="text" id="childGrade" name="grade" placeholder="Pre-K, K, 1st, 2nd, etc.">
                        </div>
                        
                        <div class="form-group">
                            <label for="childFamily">Family *</label>
                            <select id="childFamily" name="family_id" required>
                                <option value="">Select Family</option>
                                <?php foreach ($families as $family): ?>
                                    <option value="<?php echo $family['id']; ?>">
                                        Family <?php echo $family['family_number']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="childLetter">Child Letter *</label>
                            <select id="childLetter" name="child_letter" required>
                                <option value="">Select Letter</option>
                                <?php for ($i = ord('A'); $i <= ord('Z'); $i++): ?>
                                    <option value="<?php echo chr($i); ?>"><?php echo chr($i); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="childSchool">School</label>
                            <input type="text" id="childSchool" name="school">
                        </div>
                        
                        <div class="form-group">
                            <label for="shirtSize">Shirt Size</label>
                            <input type="text" id="shirtSize" name="shirt_size" placeholder="XS, S, M, L, XL, etc.">
                        </div>
                        
                        <div class="form-group">
                            <label for="pantSize">Pant Size</label>
                            <input type="text" id="pantSize" name="pant_size" placeholder="XS, S, M, L, XL, etc.">
                        </div>
                        
                        <div class="form-group">
                            <label for="shoeSize">Shoe Size</label>
                            <input type="text" id="shoeSize" name="shoe_size" placeholder="1, 2, 3, etc.">
                        </div>
                        
                        <div class="form-group">
                            <label for="jacketSize">Jacket Size</label>
                            <input type="text" id="jacketSize" name="jacket_size" placeholder="XS, S, M, L, XL, etc.">
                        </div>
                        
                        <div class="form-group form-group-full">
                            <label for="interests">Interests</label>
                            <textarea id="interests" name="interests" placeholder="Sports, crafts, music, reading, etc."></textarea>
                        </div>
                        
                        <div class="form-group form-group-full">
                            <label for="wishes">Christmas Wishes</label>
                            <textarea id="wishes" name="wishes" placeholder="What would this child like for Christmas?"></textarea>
                        </div>
                        
                        <div class="form-group form-group-full">
                            <label for="specialNeeds">Special Needs</label>
                            <textarea id="specialNeeds" name="special_needs" placeholder="Any special considerations, allergies, or needs"></textarea>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #eee;">
                        <button type="button" onclick="hideChildModal()" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Add Child</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Child';
            document.getElementById('formAction').value = 'add_child';
            document.getElementById('childId').value = '';
            document.getElementById('submitBtn').textContent = 'Add Child';
            document.getElementById('childForm').reset();
            document.getElementById('childModal').style.display = 'block';
        }

        function showEditModal(childId) {
            // This would normally fetch child data via AJAX
            // For now, we'll implement a basic version
            document.getElementById('modalTitle').textContent = 'Edit Child';
            document.getElementById('formAction').value = 'edit_child';
            document.getElementById('childId').value = childId;
            document.getElementById('submitBtn').textContent = 'Update Child';
            
            // In a full implementation, you would fetch the child data here
            // and populate the form fields
            
            document.getElementById('childModal').style.display = 'block';
        }

        function hideChildModal() {
            document.getElementById('childModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('childModal');
            if (event.target === modal) {
                hideChildModal();
            }
        }

        // Form validation
        document.getElementById('childForm').addEventListener('submit', function(e) {
            const name = document.getElementById('childName').value.trim();
            const age = document.getElementById('childAge').value;
            const gender = document.getElementById('childGender').value;
            const family = document.getElementById('childFamily').value;
            const letter = document.getElementById('childLetter').value;
            
            if (!name || !age || !gender || !family || !letter) {
                alert('Please fill in all required fields (marked with *)');
                e.preventDefault();
                return false;
            }
            
            if (age < 1 || age > 18) {
                alert('Age must be between 1 and 18');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>