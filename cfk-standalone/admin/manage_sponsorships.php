<?php

declare(strict_types=1);

/**
 * Admin - Manage Sponsorships
 * Professional table interface with bulk actions and improved UX
 * v2.0 - Complete redesign with color-coded status, bulk operations, and modern UI
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

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Manage Sponsorships';
$message = '';
$messageType = '';

// Handle bulk actions
if ($_POST !== [] && isset($_POST['bulk_action']) && isset($_POST['sponsorship_ids'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Security token invalid. Please try again.';
        $messageType = 'error';
    } else {
        $bulkAction = $_POST['bulk_action'];
        $sponsorshipIds = array_map('intval', (array) $_POST['sponsorship_ids']);
        $successCount = 0;
        $failCount = 0;

        foreach ($sponsorshipIds as $sponsorshipId) {
            $result = match ($bulkAction) {
                'log' => SponsorshipManager::logSponsorship($sponsorshipId),
                'complete' => SponsorshipManager::completeSponsorship($sponsorshipId),
                'export' => ['success' => true], // Handled separately
                default => ['success' => false, 'message' => 'Invalid action']
            };

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        if ($bulkAction === 'export') {
            // Handle CSV export
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="sponsorships-' . date('Y-m-d') . '.csv"');

            echo "Child ID,Child Age,Sponsor Name,Sponsor Email,Sponsor Phone,Request Date,Status\n";

            $exportData = Database::fetchAll("
                SELECT s.*,
                       CONCAT(f.family_number, c.child_letter) as child_display_id,
                       c.age, c.grade, c.gender
                FROM sponsorships s
                JOIN children c ON s.child_id = c.id
                JOIN families f ON c.family_id = f.id
                WHERE s.id IN (" . implode(',', array_fill(0, count($sponsorshipIds), '?')) . ")
            ", $sponsorshipIds);

            foreach ($exportData as $row) {
                echo sprintf(
                    "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                    $row['child_display_id'],
                    $row['age'],
                    $row['sponsor_name'],
                    $row['sponsor_email'],
                    $row['sponsor_phone'] ?? '',
                    $row['request_date'],
                    $row['status']
                );
            }
            exit;
        }

        $message = sprintf(
            'Bulk action completed. Success: %d, Failed: %d',
            $successCount,
            $failCount
        );
        $messageType = $failCount > 0 ? 'warning' : 'success';
    }
}

// Handle individual actions
if ($_POST !== [] && !isset($_POST['bulk_action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Security token invalid. Please try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $sponsorshipId = sanitizeInt($_POST['sponsorship_id'] ?? 0);

        switch ($action) {
            case 'complete':
                $result = SponsorshipManager::completeSponsorship($sponsorshipId);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;

            case 'log':
                $result = SponsorshipManager::logSponsorship($sponsorshipId);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;

            case 'unlog':
                $result = SponsorshipManager::unlogSponsorship($sponsorshipId);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;

            case 'cancel':
                $reason = sanitizeString($_POST['reason'] ?? 'Cancelled by admin');
                $result = SponsorshipManager::cancelSponsorship($sponsorshipId, $reason);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;

            case 'release_child':
                $childId = sanitizeInt($_POST['child_id'] ?? 0);
                if (SponsorshipManager::releaseChild($childId)) {
                    $message = 'Child released and is now available for sponsorship';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to release child';
                    $messageType = 'error';
                }
                break;

            case 'edit_sponsorship':
                $result = updateSponsorship($_POST);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// AJAX endpoint to fetch sponsorship data for editing
if (isset($_GET['action']) && $_GET['action'] === 'get_sponsorship' && isset($_GET['id'])) {
    $sponsorshipId = sanitizeInt($_GET['id']);
    $sponsorship = Database::fetchRow(
        "SELECT * FROM sponsorships WHERE id = ?",
        [$sponsorshipId]
    );

    if ($sponsorship) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'sponsorship' => $sponsorship]);
        exit;
    } else {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sponsorship not found']);
        exit;
    }
}

// Function to update sponsorship details
function updateSponsorship($data): array
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

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'newest';
$searchQuery = $_GET['search'] ?? '';
$showCancelled = isset($_GET['show_cancelled']) && $_GET['show_cancelled'] === '1';

// Build query based on filters
$whereConditions = [];
$params = [];

// Status filter
if ($statusFilter !== 'all') {
    $whereConditions[] = "s.status = ?";
    $params[] = $statusFilter;
}

// Hide cancelled by default unless toggled on
if (!$showCancelled) {
    $whereConditions[] = "s.status != 'cancelled'";
}

// Search functionality
if ($searchQuery !== '') {
    $whereConditions[] = "(s.sponsor_name LIKE ? OR s.sponsor_email LIKE ? OR CONCAT(f.family_number, c.child_letter) LIKE ?)";
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = $whereConditions === [] ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

// Sort options
$orderBy = match ($sortBy) {
    'oldest' => 'ORDER BY s.request_date ASC',
    'name' => 'ORDER BY s.sponsor_name ASC',
    'child' => 'ORDER BY f.family_number ASC, c.child_letter ASC',
    default => 'ORDER BY s.request_date DESC'
};

// Get sponsorships
$sponsorships = Database::fetchAll("
    SELECT s.*,
           c.id as child_id,
           CONCAT(f.family_number, c.child_letter) as child_name,
           c.age, c.grade, c.gender, c.status as child_status,
           c.interests, c.wishes, c.special_needs,
           c.shirt_size, c.pant_size, c.jacket_size, c.shoe_size,
           CONCAT(f.family_number, c.child_letter) as child_display_id,
           f.family_number
    FROM sponsorships s
    JOIN children c ON s.child_id = c.id
    JOIN families f ON c.family_id = f.id
    $whereClause
    $orderBy
", $params);

// Get statistics
$stats = SponsorshipManager::getStats();
$childrenNeedingAttention = SponsorshipManager::getChildrenNeedingAttention();

include __DIR__ . '/includes/admin_header.php';
?>

<!-- Enhanced Page Styles with Color-Coded Buttons -->
<style>
/* ===== Modern Admin Table System ===== */

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #2c5530;
    display: block;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

/* Filters and Search */
.filters-section {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.filters-row {
    display: flex;
    gap: 1.5rem;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.filters-row:last-child {
    margin-bottom: 0;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 500;
    color: #333;
    white-space: nowrap;
}

.filter-group input[type="text"],
.filter-group select {
    padding: 0.5rem 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
    min-width: 200px;
}

.filter-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

/* Bulk Actions Toolbar */
.bulk-actions-toolbar {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.bulk-select-all {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.bulk-select-all input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.bulk-actions-form {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.bulk-actions-form label {
    font-weight: 500;
    color: #333;
}

.bulk-actions-form select {
    padding: 0.5rem 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
    min-width: 180px;
}

.bulk-selected-count {
    color: #666;
    font-size: 0.9rem;
    margin-left: auto;
}

/* Attention Section */
.attention-section {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.attention-section h3 {
    color: #856404;
    margin-bottom: 1rem;
}

.attention-list {
    display: grid;
    gap: 1rem;
}

.attention-item {
    background: white;
    padding: 1rem;
    border-radius: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Table Container */
.sponsorships-table {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 0.07rem 0.5rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Checkbox Column */
.table th.col-checkbox,
.table td.col-checkbox {
    width: 50px;
    text-align: center;
    padding: 0.5rem;
}

.table td.col-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

/* Zebra Striping */
.table tbody tr:nth-child(even) {
    background: #e9ecef;
}

.table tbody tr:nth-child(odd) {
    background: white;
}

/* Hover State */
.table tbody tr:hover {
    background: #e7f1ff !important;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

/* Selected Row State */
.table tbody tr.selected {
    background: #cfe2ff !important;
}

/* Child Info Column */
.child-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0;
    margin: 0;
}

.child-details {
    padding: 0;
    margin: 0;
}

.child-details .child-id {
    font-weight: 600;
    color: #2c5530;
    font-size: 0.95rem;
    line-height: 1.2;
    margin: 0;
}

.child-details .child-meta {
    font-size: 0.85rem;
    color: #666;
    margin: 0;
    line-height: 1.2;
}

/* Sponsor Info Column */
.sponsor-info {
    padding: 0;
    margin: 0;
}

.sponsor-info .sponsor-name {
    font-weight: 600;
    color: #333;
    margin: 0;
    line-height: 1.2;
}

.sponsor-info a {
    color: #0066cc;
    text-decoration: none;
    font-size: 0.9rem;
    line-height: 1.2;
}

.sponsor-info a:hover {
    text-decoration: underline;
}

.sponsor-info .sponsor-phone {
    color: #666;
    font-size: 0.85rem;
    display: block;
    margin: 0;
    line-height: 1.2;
}

/* Date Column */
.date-info {
    padding: 0;
    margin: 0;
}

.date-info .request-date {
    font-weight: 500;
    color: #333;
    margin: 0;
    line-height: 1.2;
}

.date-info .confirmed-date {
    font-size: 0.85rem;
    color: #666;
    margin: 0;
    line-height: 1.2;
}

/* Actions Column */
.actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
    padding: 0;
    margin: 0;
}

/* ===== Color-Coded Button System ===== */

/* Base Button Styles */
.btn-action {
    padding: 0.4rem 0.8rem;
    border: none;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

/* STATUS: CONFIRMED (Not Logged, Not Complete) */
.btn-log-pending {
    background: #6c757d;
    color: white;
}

.btn-log-pending:hover {
    background: #5a6268;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.btn-complete-pending {
    background: #6c757d;
    color: white;
}

.btn-complete-pending:hover {
    background: #5a6268;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* STATUS: LOGGED */
.btn-unlog {
    background: #0d6efd;
    color: white;
}

.btn-unlog:hover {
    background: #0b5ed7;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.btn-complete-logged {
    background: #6c757d;
    color: white;
}

.btn-complete-logged:hover {
    background: #5a6268;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* STATUS: COMPLETED */
.btn-completed {
    background: #198754;
    color: white;
    cursor: default;
}

.btn-completed:hover {
    background: #198754;
    transform: none;
}

/* STATUS: CANCELLED */
.btn-cancelled {
    background: #343a40;
    color: white;
    cursor: default;
}

.btn-cancelled:hover {
    background: #343a40;
    transform: none;
}

/* CANCEL ACTION BUTTON */
.btn-cancel-action {
    background: #dc3545;
    color: white;
}

.btn-cancel-action:hover {
    background: #bb2d3b;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* VIEW MESSAGE BUTTON */
.btn-view-message {
    background: #ffc107;
    color: #000;
}

.btn-view-message:hover {
    background: #ffca2c;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* EDIT SPONSORSHIP BUTTON */
.btn-edit-sponsorship {
    background: #0d6efd;
    color: white;
}

.btn-edit-sponsorship:hover {
    background: #0b5ed7;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Modal Styles */
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
    margin: 10% auto;
    padding: 2rem;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    position: relative;
}

.modal-header {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.close {
    position: absolute;
    right: 1rem;
    top: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
    color: #999;
}

.close:hover {
    color: #333;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem;
    color: #666;
}

/* Responsive Design */
@media (max-width: 768px) {
    .filters-row {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-group {
        width: 100%;
    }

    .filter-group input[type="text"],
    .filter-group select {
        width: 100%;
    }

    .bulk-actions-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .bulk-actions-form {
        flex-direction: column;
    }

    .bulk-selected-count {
        margin-left: 0;
        text-align: center;
    }

    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }

    .table {
        font-size: 0.85rem;
    }

    .table th,
    .table td {
        padding: 0.75rem 0.5rem;
    }

    .actions {
        flex-direction: column;
        gap: 0.25rem;
    }

    .btn-action {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-number"><?php echo $stats['sponsorships']['confirmed'] ?? 0; ?></span>
        <div class="stat-label">Active Sponsorships</div>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?php echo $stats['sponsorships']['logged'] ?? 0; ?></span>
        <div class="stat-label">Logged Externally</div>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?php echo $stats['sponsorships']['completed'] ?? 0; ?></span>
        <div class="stat-label">Gifts Delivered</div>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?php echo $stats['children']['available'] ?? 0; ?></span>
        <div class="stat-label">Available Children</div>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?php echo ($stats['sponsorships']['confirmed'] ?? 0) + ($stats['sponsorships']['logged'] ?? 0) + ($stats['sponsorships']['completed'] ?? 0); ?></span>
        <div class="stat-label">Total Sponsored</div>
    </div>
</div>

<!-- Children Needing Attention -->
<?php if ($childrenNeedingAttention !== []) : ?>
    <div class="attention-section">
        <h3>⚠️ Children Needing Attention (<?php echo count($childrenNeedingAttention); ?>)</h3>
        <div class="attention-list">
            <?php foreach ($childrenNeedingAttention as $child) : ?>
                <div class="attention-item">
                    <div>
                        <strong><?php echo sanitizeString($child['display_id']); ?></strong>
                        - Pending since <?php echo formatDateTime($child['request_date']); ?>
                        <br>
                        <small>Sponsor: <?php echo sanitizeString($child['sponsor_name']); ?> (<?php echo sanitizeString($child['sponsor_email']); ?>)</small>
                    </div>
                    <form method="POST" style="display: inline;" class="release-child-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="release_child">
                        <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                        <button type="submit" class="btn btn-warning btn-small btn-release-child">
                            Release Child
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Filters and Search -->
<div class="filters-section">
    <div class="filters-row">
        <div class="filter-group">
            <label for="search-input">🔍 Search:</label>
            <input type="text"
                   id="search-input"
                   placeholder="Sponsor name, email, or child ID..."
                   value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="filter-group">
            <label for="status-filter">Status:</label>
            <select id="status-filter">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="logged" <?php echo $statusFilter === 'logged' ? 'selected' : ''; ?>>Logged Externally</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="sort-filter">Sort:</label>
            <select id="sort-filter">
                <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Sponsor Name</option>
                <option value="child" <?php echo $sortBy === 'child' ? 'selected' : ''; ?>>Child ID</option>
            </select>
        </div>
    </div>

    <div class="filters-row">
        <div class="filter-group">
            <input type="checkbox" id="show-cancelled" <?php echo $showCancelled ? 'checked' : ''; ?>>
            <label for="show-cancelled">Show Cancelled Sponsorships (for audit purposes)</label>
        </div>
    </div>
</div>

<!-- Bulk Actions Toolbar -->
<div class="bulk-actions-toolbar">
    <div class="bulk-select-all">
        <input type="checkbox" id="select-all-checkbox">
        <label for="select-all-checkbox">Select All</label>
    </div>

    <form method="POST" class="bulk-actions-form" id="bulk-actions-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <label for="bulk-action-select">Bulk Actions:</label>
        <select id="bulk-action-select" name="bulk_action">
            <option value="">Choose Action...</option>
            <option value="log">Mark as Logged</option>
            <option value="complete">Mark as Complete</option>
            <option value="export">Export Selected (CSV)</option>
        </select>

        <button type="submit" class="btn btn-primary btn-small" id="apply-bulk-action">Apply</button>
    </form>

    <div class="bulk-selected-count">
        Selected: <strong id="selected-count">0</strong> sponsorships
    </div>
</div>

<!-- Sponsorships Table -->
<div class="sponsorships-table">
    <table class="table">
        <thead>
            <tr>
                <th class="col-checkbox">
                    <input type="checkbox" id="select-all-header" title="Select all visible">
                </th>
                <th>Child</th>
                <th>Sponsor</th>
                <th>Request Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($sponsorships === []) : ?>
                <tr>
                    <td colspan="5" class="empty-state">
                        <?php if ($searchQuery !== '' || $statusFilter !== 'all') : ?>
                            No sponsorships found for the selected filters.
                            <br><small>Try adjusting your search or filters.</small>
                        <?php else : ?>
                            No sponsorships yet.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($sponsorships as $sponsorship) : ?>
                    <tr class="sponsorship-row">
                        <td class="col-checkbox">
                            <input type="checkbox"
                                   class="sponsorship-checkbox"
                                   name="sponsorship_ids[]"
                                   value="<?php echo $sponsorship['id']; ?>"
                                   form="bulk-actions-form">
                        </td>
                        <td>
                            <div class="child-info">
                                <div class="child-details">
                                    <div class="child-id"><?php echo sanitizeString($sponsorship['child_display_id']); ?></div>
                                    <div class="child-meta">
                                        <?php echo $sponsorship['age']; ?>y
                                        <?php echo $sponsorship['gender'] === 'M' ? '♂' : '♀'; ?>
                                        <?php if (!empty($sponsorship['grade'])) : ?>
                                            • <?php echo sanitizeString($sponsorship['grade']); ?>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($sponsorship['interests'])) : ?>
                                        <div style="margin-top: 4px; font-size: 0.8rem;">
                                            <strong style="color: #2c5530;">Essential Needs:</strong>
                                            <div style="color: #666; margin-top: 2px; line-height: 1.3;"><?php echo nl2br(sanitizeString($sponsorship['interests'])); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($sponsorship['wishes'])) : ?>
                                        <div style="margin-top: 4px; font-size: 0.8rem;">
                                            <strong style="color: #c41e3a;">Wishes:</strong>
                                            <div style="color: #666; margin-top: 2px; line-height: 1.3;"><?php echo nl2br(sanitizeString($sponsorship['wishes'])); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($sponsorship['special_needs'])) : ?>
                                        <div style="margin-top: 4px; font-size: 0.8rem;">
                                            <strong style="color: #856404;">⚠️ Special Needs:</strong>
                                            <div style="color: #666; margin-top: 2px; padding: 4px; background-color: #fff3cd; border-left: 2px solid #f5b800; border-radius: 2px; line-height: 1.3;"><?php echo nl2br(sanitizeString($sponsorship['special_needs'])); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($sponsorship['shirt_size']) || !empty($sponsorship['pant_size']) || !empty($sponsorship['jacket_size']) || !empty($sponsorship['shoe_size'])) : ?>
                                        <div style="margin-top: 4px; font-size: 0.8rem; background-color: #e7f3ff; padding: 4px; border-radius: 2px;">
                                            <strong style="color: #2c5530;">Sizes:</strong>
                                            <div style="margin-top: 2px; color: #666; line-height: 1.3;">
                                                <?php if (!empty($sponsorship['shirt_size'])) : ?>
                                                    Shirt: <?php echo sanitizeString($sponsorship['shirt_size']); ?><br>
                                                <?php endif; ?>
                                                <?php if (!empty($sponsorship['pant_size'])) : ?>
                                                    Pants: <?php echo sanitizeString($sponsorship['pant_size']); ?><br>
                                                <?php endif; ?>
                                                <?php if (!empty($sponsorship['jacket_size'])) : ?>
                                                    Jacket: <?php echo sanitizeString($sponsorship['jacket_size']); ?><br>
                                                <?php endif; ?>
                                                <?php if (!empty($sponsorship['shoe_size'])) : ?>
                                                    Shoes: <?php echo sanitizeString($sponsorship['shoe_size']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="sponsor-info">
                                <div class="sponsor-name"><?php echo sanitizeString($sponsorship['sponsor_name']); ?></div>
                                <a href="mailto:<?php echo sanitizeString($sponsorship['sponsor_email']); ?>"
                                   title="Click to send email">
                                    <?php echo sanitizeString($sponsorship['sponsor_email']); ?>
                                </a>
                                <?php if (!empty($sponsorship['sponsor_phone'])) : ?>
                                    <a href="tel:<?php echo sanitizeString($sponsorship['sponsor_phone']); ?>"
                                       class="sponsor-phone"
                                       title="Click to call">
                                        📞 <?php echo sanitizeString($sponsorship['sponsor_phone']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="date-info">
                                <div class="request-date"><?php echo formatDateTime($sponsorship['request_date']); ?></div>
                                <?php if ($sponsorship['confirmation_date']) : ?>
                                    <div class="confirmed-date">Confirmed: <?php echo formatDate($sponsorship['confirmation_date']); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if ($sponsorship['status'] === 'confirmed') : ?>
                                    <!-- CONFIRMED STATUS: Gray buttons for pending actions -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="log">
                                        <input type="hidden" name="sponsorship_id" value="<?php echo $sponsorship['id']; ?>">
                                        <button type="submit" class="btn-action btn-log-pending" title="Mark as logged in external spreadsheet">
                                            Mark Logged
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="complete">
                                        <input type="hidden" name="sponsorship_id" value="<?php echo $sponsorship['id']; ?>">
                                        <button type="submit" class="btn-action btn-complete-pending" title="Mark as complete (gifts delivered)">
                                            Mark Complete
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($sponsorship['status'] === 'logged') : ?>
                                    <!-- LOGGED STATUS: Blue unlog button, gray complete -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="unlog">
                                        <input type="hidden" name="sponsorship_id" value="<?php echo $sponsorship['id']; ?>">
                                        <button type="submit" class="btn-action btn-unlog" title="Remove from logged status">
                                            ↻ Unlog
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="complete">
                                        <input type="hidden" name="sponsorship_id" value="<?php echo $sponsorship['id']; ?>">
                                        <button type="submit" class="btn-action btn-complete-logged" title="Mark as complete (gifts delivered)">
                                            Mark Complete
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($sponsorship['status'] === 'completed') : ?>
                                    <!-- COMPLETED STATUS: Green badge (display only) -->
                                    <button class="btn-action btn-completed" disabled title="Sponsorship completed">
                                        ✓ Completed
                                    </button>
                                <?php endif; ?>

                                <?php if ($sponsorship['status'] === 'cancelled') : ?>
                                    <!-- CANCELLED STATUS: Dark badge (display only) -->
                                    <button class="btn-action btn-cancelled" disabled title="Sponsorship cancelled">
                                        ✗ Cancelled
                                    </button>
                                <?php endif; ?>

                                <!-- EDIT ACTION: Blue edit button for all active sponsorships -->
                                <?php if ($sponsorship['status'] !== 'cancelled') : ?>
                                    <button class="btn-action btn-edit-sponsorship"
                                            data-sponsorship-id="<?php echo $sponsorship['id']; ?>"
                                            title="Edit sponsor information">
                                        ✏️ Edit
                                    </button>
                                <?php endif; ?>

                                <?php if (in_array($sponsorship['status'], ['confirmed', 'logged'])) : ?>
                                    <!-- CANCEL ACTION: Red button for active sponsorships -->
                                    <button class="btn-action btn-cancel-action btn-cancel-sponsorship"
                                            data-sponsorship-id="<?php echo $sponsorship['id']; ?>"
                                            data-child-id="<?php echo sanitizeString($sponsorship['child_display_id']); ?>"
                                            data-sponsor-name="<?php echo sanitizeString($sponsorship['sponsor_name']); ?>">
                                        Cancel
                                    </button>
                                <?php endif; ?>

                                <?php if (!empty($sponsorship['special_message'])) : ?>
                                    <button class="btn-action btn-view-message btn-view-message-btn"
                                            data-message="<?php echo htmlspecialchars((string) $sponsorship['special_message'], ENT_QUOTES, 'UTF-8'); ?>"
                                            title="View special message from sponsor">
                                        💬 Message
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" class="modal">
    <div class="modal-content">
        <span class="close" id="close-cancel-modal-x">&times;</span>
        <div class="modal-header">
            <h3>Cancel Sponsorship</h3>
        </div>
        <p id="cancelText"></p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="sponsorship_id" id="cancelSponsorshipId">

            <div class="form-group">
                <label for="reason">Reason for cancellation:</label>
                <textarea name="reason" id="reason" required placeholder="Please provide a reason for cancelling this sponsorship..." style="width: 100%; min-height: 100px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;"></textarea>
            </div>

            <div class="actions" style="margin-top: 1rem;">
                <button type="submit" class="btn btn-danger">Cancel Sponsorship</button>
                <button type="button" id="close-cancel-modal-btn" class="btn btn-secondary">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- Message Modal -->
<div id="messageModal" class="modal">
    <div class="modal-content">
        <span class="close" id="close-message-modal-x">&times;</span>
        <div class="modal-header">
            <h3>Special Message from Sponsor</h3>
        </div>
        <p id="messageText" style="white-space: pre-wrap; line-height: 1.5;"></p>
        <div class="actions" style="margin-top: 1rem;">
            <button type="button" id="close-message-modal-btn" class="btn btn-primary">Close</button>
        </div>
    </div>
</div>

<!-- Edit Sponsorship Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" id="close-edit-modal-x">&times;</span>
        <div class="modal-header">
            <h3>Edit Sponsor Information</h3>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="edit_sponsorship">
            <input type="hidden" name="sponsorship_id" id="editSponsorshipId">

            <div class="form-group">
                <label for="editSponsorName">Sponsor Name: *</label>
                <input type="text" name="sponsor_name" id="editSponsorName" required
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div class="form-group">
                <label for="editSponsorEmail">Sponsor Email: *</label>
                <input type="email" name="sponsor_email" id="editSponsorEmail" required
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div class="form-group">
                <label for="editSponsorPhone">Sponsor Phone:</label>
                <input type="tel" name="sponsor_phone" id="editSponsorPhone"
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div class="form-group">
                <label for="editSponsorAddress">Sponsor Address:</label>
                <textarea name="sponsor_address" id="editSponsorAddress" rows="3"
                          style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;"></textarea>
            </div>

            <div class="actions" style="margin-top: 1rem;">
                <button type="submit" class="btn btn-primary">Update Sponsor</button>
                <button type="button" id="close-edit-modal-btn" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
// CSP-compliant JavaScript for manage_sponsorships.php
document.addEventListener('DOMContentLoaded', function() {
    // ===== DOM Element References =====
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const selectAllHeader = document.getElementById('select-all-header');
    const sponsorshipCheckboxes = document.querySelectorAll('.sponsorship-checkbox');
    const selectedCountEl = document.getElementById('selected-count');
    const bulkActionSelect = document.getElementById('bulk-action-select');
    const applyBulkActionBtn = document.getElementById('apply-bulk-action');
    const bulkActionsForm = document.getElementById('bulk-actions-form');

    const searchInput = document.getElementById('search-input');
    const statusFilter = document.getElementById('status-filter');
    const sortFilter = document.getElementById('sort-filter');
    const showCancelledCheckbox = document.getElementById('show-cancelled');

    const cancelModal = document.getElementById('cancelModal');
    const messageModal = document.getElementById('messageModal');
    const cancelSponsorshipId = document.getElementById('cancelSponsorshipId');
    const cancelText = document.getElementById('cancelText');
    const messageText = document.getElementById('messageText');
    const reasonTextarea = document.getElementById('reason');

    // ===== Checkbox Selection System =====
    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.sponsorship-checkbox:checked').length;
        selectedCountEl.textContent = checkedCount;

        // Update select all checkbox state
        const totalCheckboxes = sponsorshipCheckboxes.length;
        if (checkedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            selectAllHeader.checked = false;
            selectAllHeader.indeterminate = false;
        } else if (checkedCount === totalCheckboxes) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
            selectAllHeader.checked = true;
            selectAllHeader.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
            selectAllHeader.checked = false;
            selectAllHeader.indeterminate = true;
        }

        // Highlight selected rows
        document.querySelectorAll('.sponsorship-row').forEach(row => {
            const checkbox = row.querySelector('.sponsorship-checkbox');
            if (checkbox && checkbox.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
        });
    }

    function toggleSelectAll(checked) {
        sponsorshipCheckboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        updateSelectedCount();
    }

    // Select all checkbox (in toolbar)
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            toggleSelectAll(this.checked);
        });
    }

    // Select all checkbox (in header)
    if (selectAllHeader) {
        selectAllHeader.addEventListener('change', function() {
            toggleSelectAll(this.checked);
        });
    }

    // Individual checkbox changes
    sponsorshipCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    // Initialize selected count
    updateSelectedCount();

    // ===== Bulk Actions Form Validation =====
    if (bulkActionsForm) {
        bulkActionsForm.addEventListener('submit', function(e) {
            const selectedCheckboxes = document.querySelectorAll('.sponsorship-checkbox:checked');
            const action = bulkActionSelect.value;

            if (selectedCheckboxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one sponsorship.');
                return false;
            }

            if (action === '') {
                e.preventDefault();
                alert('Please select a bulk action.');
                return false;
            }

            // Confirm action
            const actionNames = {
                'log': 'mark as logged',
                'complete': 'mark as complete',
                'export': 'export'
            };

            const confirmMessage = `Are you sure you want to ${actionNames[action]} ${selectedCheckboxes.length} sponsorship(s)?`;

            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ===== Filter and Search Functions =====
    function applyFilters() {
        const status = statusFilter.value;
        const sort = sortFilter.value;
        const search = searchInput.value.trim();
        const showCancelled = showCancelledCheckbox.checked ? '1' : '0';

        const url = new URL(window.location);
        url.searchParams.set('status', status);
        url.searchParams.set('sort', sort);
        url.searchParams.set('show_cancelled', showCancelled);

        if (search !== '') {
            url.searchParams.set('search', search);
        } else {
            url.searchParams.delete('search');
        }

        window.location = url;
    }

    // Auto-filter on dropdown change
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }

    if (sortFilter) {
        sortFilter.addEventListener('change', applyFilters);
    }

    if (showCancelledCheckbox) {
        showCancelledCheckbox.addEventListener('change', applyFilters);
    }

    // Search input with debounce
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 500);
        });

        // Also search on Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                applyFilters();
            }
        });
    }

    // ===== Modal Functions =====
    function showCancelModal(sponsorshipId, childId, sponsorName) {
        cancelSponsorshipId.value = sponsorshipId;
        cancelText.textContent =
            `Are you sure you want to cancel the sponsorship of Child ${childId} by ${sponsorName}? This action will release the child back to available status.`;
        cancelModal.style.display = 'block';
    }

    function hideCancelModal() {
        cancelModal.style.display = 'none';
        reasonTextarea.value = '';
    }

    function showMessage(message) {
        messageText.textContent = message;
        messageModal.style.display = 'block';
    }

    function hideMessageModal() {
        messageModal.style.display = 'none';
    }

    // ===== Event Listeners for Buttons =====

    // Release child confirmation
    document.querySelectorAll('.release-child-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Release this child back to available status?')) {
                e.preventDefault();
                return false;
            }
        });
    });

    // Cancel sponsorship buttons
    document.querySelectorAll('.btn-cancel-sponsorship').forEach(button => {
        button.addEventListener('click', function() {
            const sponsorshipId = this.getAttribute('data-sponsorship-id');
            const childId = this.getAttribute('data-child-id');
            const sponsorName = this.getAttribute('data-sponsor-name');
            showCancelModal(sponsorshipId, childId, sponsorName);
        });
    });

    // View message buttons
    document.querySelectorAll('.btn-view-message-btn').forEach(button => {
        button.addEventListener('click', function() {
            const message = this.getAttribute('data-message');
            showMessage(message);
        });
    });

    // Close cancel modal
    const closeCancelX = document.getElementById('close-cancel-modal-x');
    if (closeCancelX) {
        closeCancelX.addEventListener('click', hideCancelModal);
    }

    const closeCancelBtn = document.getElementById('close-cancel-modal-btn');
    if (closeCancelBtn) {
        closeCancelBtn.addEventListener('click', hideCancelModal);
    }

    // Close message modal
    const closeMessageX = document.getElementById('close-message-modal-x');
    if (closeMessageX) {
        closeMessageX.addEventListener('click', hideMessageModal);
    }

    const closeMessageBtn = document.getElementById('close-message-modal-btn');
    if (closeMessageBtn) {
        closeMessageBtn.addEventListener('click', hideMessageModal);
    }

    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === cancelModal) {
            hideCancelModal();
        }
        if (event.target === messageModal) {
            hideMessageModal();
        }
    });

    // ===== AJAX Form Submission - FULLY DYNAMIC (NO PAGE RELOAD) =====
    // Intercept all action forms and submit via AJAX
    const actionForms = document.querySelectorAll('form[method="POST"]');
    actionForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            // Only intercept non-bulk action forms
            if (!form.closest('#bulk-actions-form') && form.querySelector('input[name="action"]')) {
                e.preventDefault(); // STOP page reload

                const formData = new FormData(form);
                const button = form.querySelector('button[type="submit"]');
                const originalText = button ? button.textContent : '';
                const action = form.querySelector('input[name="action"]').value;

                // Show loading state
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Processing...';
                }

                // Submit via AJAX
                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Show toast notification
                    if (typeof showToast === 'function') {
                        showToast(data.message, data.success ? 'success' : 'error');
                    }

                    if (data.success) {
                        // UPDATE UI DYNAMICALLY (NO PAGE RELOAD)
                        updateButtonStatesAfterAction(button, action, form);
                        updateStatsAfterAction(action);
                    } else {
                        // Re-enable button on error
                        if (button) {
                            button.disabled = false;
                            button.textContent = originalText;
                        }
                    }
                })
                .catch(error => {
                    if (typeof showToast === 'function') {
                        showToast('An error occurred. Please try again.', 'error');
                    }
                    if (button) {
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                });
            }
        });
    });

    // Dynamic UI Update: Change button states without page reload
    function updateButtonStatesAfterAction(clickedButton, action, form) {
        const row = form.closest('tr');
        if (!row) return;

        // Find all action buttons in this row
        const actionsCell = row.querySelector('.actions');
        if (!actionsCell) return;

        // Clear existing buttons
        actionsCell.innerHTML = '';

        // Create new buttons based on action
        if (action === 'log') {
            // confirmed → logged: Show Unlog (blue) and Mark Complete (gray) buttons
            actionsCell.innerHTML = `
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="unlog">
                    <input type="hidden" name="sponsorship_id" value="${form.querySelector('[name="sponsorship_id"]').value}">
                    <button type="submit" class="btn-action btn-unlog" title="Remove from logged status">
                        ↻ Unlog
                    </button>
                </form>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="complete">
                    <input type="hidden" name="sponsorship_id" value="${form.querySelector('[name="sponsorship_id"]').value}">
                    <button type="submit" class="btn-action btn-complete-logged" title="Mark as complete (gifts delivered)">
                        Mark Complete
                    </button>
                </form>
            `;
        } else if (action === 'unlog') {
            // logged → confirmed: Show Mark Logged (gray) and Mark Complete (gray) buttons
            actionsCell.innerHTML = `
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="log">
                    <input type="hidden" name="sponsorship_id" value="${form.querySelector('[name="sponsorship_id"]').value}">
                    <button type="submit" class="btn-action btn-log-pending" title="Mark as logged in external spreadsheet">
                        Mark Logged
                    </button>
                </form>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="complete">
                    <input type="hidden" name="sponsorship_id" value="${form.querySelector('[name="sponsorship_id"]').value}">
                    <button type="submit" class="btn-action btn-complete-pending" title="Mark as complete (gifts delivered)">
                        Mark Complete
                    </button>
                </form>
            `;
        } else if (action === 'complete') {
            // Any → completed: Show green "Completed" badge
            actionsCell.innerHTML = `
                <button class="btn-action btn-completed" disabled title="Sponsorship completed">
                    ✓ Completed
                </button>
            `;
        }

        // Re-attach event listeners to new forms
        const newForms = actionsCell.querySelectorAll('form');
        newForms.forEach(attachFormListener);
    }

    // Re-attach listener to dynamically created forms
    function attachFormListener(form) {
        form.addEventListener('submit', function(e) {
            if (form.querySelector('input[name="action"]')) {
                e.preventDefault();

                const formData = new FormData(form);
                const button = form.querySelector('button[type="submit"]');
                const originalText = button ? button.textContent : '';
                const action = form.querySelector('input[name="action"]').value;

                if (button) {
                    button.disabled = true;
                    button.textContent = 'Processing...';
                }

                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (typeof showToast === 'function') {
                        showToast(data.message, data.success ? 'success' : 'error');
                    }
                    if (data.success) {
                        updateButtonStatesAfterAction(button, action, form);
                        updateStatsAfterAction(action);
                    } else {
                        if (button) {
                            button.disabled = false;
                            button.textContent = originalText;
                        }
                    }
                })
                .catch(error => {
                    if (typeof showToast === 'function') {
                        showToast('An error occurred.', 'error');
                    }
                    if (button) {
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                });
            }
        });
    }

    // Dynamic Stats Update: Change counters without page reload
    function updateStatsAfterAction(action) {
        const activeStatEl = document.querySelector('.stat-number');
        if (!activeStatEl) return;

        const currentActive = parseInt(activeStatEl.textContent);

        // Update based on action
        if (action === 'complete') {
            // completed reduces active count
            activeStatEl.textContent = Math.max(0, currentActive - 1);
            // Animate
            activeStatEl.style.transform = 'scale(1.2)';
            setTimeout(() => activeStatEl.style.transform = 'scale(1)', 200);
        }
    }

    // ===== Edit Sponsorship Modal =====
    const editModal = document.getElementById('editModal');
    const editButtons = document.querySelectorAll('.btn-edit-sponsorship');
    const closeEditModalX = document.getElementById('close-edit-modal-x');
    const closeEditModalBtn = document.getElementById('close-edit-modal-btn');

    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sponsorshipId = this.getAttribute('data-sponsorship-id');

            // Fetch sponsorship data via AJAX
            fetch(`?action=get_sponsorship&id=${sponsorshipId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.sponsorship) {
                        const sp = data.sponsorship;

                        // Populate form fields
                        document.getElementById('editSponsorshipId').value = sp.id;
                        document.getElementById('editSponsorName').value = sp.sponsor_name || '';
                        document.getElementById('editSponsorEmail').value = sp.sponsor_email || '';
                        document.getElementById('editSponsorPhone').value = sp.sponsor_phone || '';
                        document.getElementById('editSponsorAddress').value = sp.sponsor_address || '';

                        // Show modal
                        editModal.style.display = 'block';
                    } else {
                        alert('Error loading sponsorship data: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error fetching sponsorship data:', error);
                    alert('Error loading sponsorship data. Please try again.');
                });
        });
    });

    // Close edit modal
    if (closeEditModalX) {
        closeEditModalX.addEventListener('click', () => editModal.style.display = 'none');
    }
    if (closeEditModalBtn) {
        closeEditModalBtn.addEventListener('click', () => editModal.style.display = 'none');
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === editModal) {
            editModal.style.display = 'none';
        }
    });
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
