<?php
declare(strict_types=1);

/**
 * Admin - Manage Sponsorships
 * Process sponsorship requests, confirmations, and completions
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sponsorship_manager.php';
require_once __DIR__ . '/../includes/email_manager.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Manage Sponsorships';
$message = '';
$messageType = '';

// Handle actions
if ($_POST) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Security token invalid. Please try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $sponsorshipId = sanitizeInt($_POST['sponsorship_id'] ?? 0);
        
        switch ($action) {
            case 'confirm':
                $result = CFK_Sponsorship_Manager::confirmSponsorship($sponsorshipId);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'complete':
                $result = CFK_Sponsorship_Manager::completeSponsorship($sponsorshipId);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'cancel':
                $reason = sanitizeString($_POST['reason'] ?? 'Cancelled by admin');
                $result = CFK_Sponsorship_Manager::cancelSponsorship($sponsorshipId, $reason);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'release_child':
                $childId = sanitizeInt($_POST['child_id'] ?? 0);
                if (CFK_Sponsorship_Manager::releaseChild($childId)) {
                    $message = 'Child released and is now available for sponsorship';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to release child';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'newest';

// Build query based on filters
$whereConditions = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereConditions[] = "s.status = ?";
    $params[] = $statusFilter;
}

$whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

// Sort options
$orderBy = match($sortBy) {
    'oldest' => 'ORDER BY s.request_date ASC',
    'name' => 'ORDER BY s.sponsor_name ASC',
    'child' => 'ORDER BY f.family_number ASC, c.child_letter ASC',
    default => 'ORDER BY s.request_date DESC'
};

// Get sponsorships
$sponsorships = Database::fetchAll("
    SELECT s.*,
           c.id as child_id, c.name as child_name, c.age, c.grade, c.gender, c.status as child_status,
           CONCAT(f.family_number, c.child_letter) as child_display_id,
           f.family_number
    FROM sponsorships s
    JOIN children c ON s.child_id = c.id
    JOIN families f ON c.family_id = f.id
    $whereClause
    $orderBy
", $params);

// Get statistics
$stats = CFK_Sponsorship_Manager::getStats();
$childrenNeedingAttention = CFK_Sponsorship_Manager::getChildrenNeedingAttention();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Christmas for Kids</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

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

        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 500;
            color: #333;
        }

        .filter-group select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

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
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-sponsored {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background 0.2s;
        }

        .btn-primary {
            background: #2c5530;
            color: white;
        }

        .btn-primary:hover {
            background: #1e3a21;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-small {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        .child-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .child-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #2c5530;
        }

        .child-details {
            flex: 1;
        }

        .child-id {
            font-weight: bold;
            color: #2c5530;
        }

        .child-meta {
            font-size: 0.9rem;
            color: #666;
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

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .table {
                font-size: 0.9rem;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>Manage Sponsorships</h1>
        <nav class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="manage_children.php">Manage Children</a>
            <a href="import_csv.php">Import CSV</a>
            <a href="../index.php" target="_blank">View Site</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo sanitizeString($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['sponsorships']['pending'] ?? 0; ?></span>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['sponsorships']['confirmed'] ?? 0; ?></span>
                <div class="stat-label">Confirmed Sponsorships</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['sponsorships']['completed'] ?? 0; ?></span>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['children']['available'] ?? 0; ?></span>
                <div class="stat-label">Available Children</div>
            </div>
        </div>

        <!-- Children Needing Attention -->
        <?php if (!empty($childrenNeedingAttention)): ?>
            <div class="attention-section">
                <h3>⚠️ Children Needing Attention (<?php echo count($childrenNeedingAttention); ?>)</h3>
                <div class="attention-list">
                    <?php foreach ($childrenNeedingAttention as $child): ?>
                        <div class="attention-item">
                            <div>
                                <strong><?php echo sanitizeString($child['display_id']); ?></strong>
                                - Pending since <?php echo formatDateTime($child['request_date']); ?>
                                <br>
                                <small>Sponsor: <?php echo sanitizeString($child['sponsor_name']); ?> (<?php echo sanitizeString($child['sponsor_email']); ?>)</small>
                            </div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="release_child">
                                <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                                <button type="submit" class="btn btn-warning btn-small" 
                                        onclick="return confirm('Release this child back to available status?')">
                                    Release Child
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <label for="status-filter">Status:</label>
                <select id="status-filter" onchange="filterSponsorships()">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="sponsored" <?php echo $statusFilter === 'sponsored' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sort-filter">Sort by:</label>
                <select id="sort-filter" onchange="filterSponsorships()">
                    <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Sponsor Name</option>
                    <option value="child" <?php echo $sortBy === 'child' ? 'selected' : ''; ?>>Child ID</option>
                </select>
            </div>
        </div>

        <!-- Sponsorships Table -->
        <div class="sponsorships-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Child</th>
                        <th>Sponsor</th>
                        <th>Request Date</th>
                        <th>Gift Preference</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sponsorships)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: #666;">
                                No sponsorships found for the selected filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sponsorships as $sponsorship): ?>
                            <tr>
                                <td>
                                    <div class="child-info">
                                        <div class="child-avatar">
                                            <?php echo strtoupper(substr($sponsorship['child_display_id'], 0, 2)); ?>
                                        </div>
                                        <div class="child-details">
                                            <div class="child-id"><?php echo sanitizeString($sponsorship['child_display_id']); ?></div>
                                            <div class="child-meta">
                                                <?php echo formatAge($sponsorship['age']); ?>
                                                <?php if (!empty($sponsorship['grade'])): ?>
                                                    • Grade <?php echo sanitizeString($sponsorship['grade']); ?>
                                                <?php endif; ?>
                                                • <?php echo ucfirst($sponsorship['gender']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo sanitizeString($sponsorship['sponsor_name']); ?></strong><br>
                                        <a href="mailto:<?php echo sanitizeString($sponsorship['sponsor_email']); ?>">
                                            <?php echo sanitizeString($sponsorship['sponsor_email']); ?>
                                        </a>
                                        <?php if (!empty($sponsorship['sponsor_phone'])): ?>
                                            <br><small><?php echo sanitizeString($sponsorship['sponsor_phone']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo formatDateTime($sponsorship['request_date']); ?>
                                    <?php if ($sponsorship['confirmation_date']): ?>
                                        <br><small>Confirmed: <?php echo formatDate($sponsorship['confirmation_date']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $preferences = [
                                        'shopping' => 'Shopping',
                                        'gift_card' => 'Gift Cards',
                                        'cash_donation' => 'Cash Donation'
                                    ];
                                    echo $preferences[$sponsorship['gift_preference']] ?? ucfirst($sponsorship['gift_preference']);
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $sponsorship['status']; ?>">
                                        <?php echo ucfirst($sponsorship['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <?php if ($sponsorship['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="action" value="confirm">
                                                <input type="hidden" name="sponsorship_id" value="<?php echo $sponsorship['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-small">Confirm</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($sponsorship['status'] === 'confirmed'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="action" value="complete">
                                                <input type="hidden" name="sponsorship_id" value="<?php echo $sponsorship['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-small">Mark Complete</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (in_array($sponsorship['status'], ['pending', 'confirmed'])): ?>
                                            <button onclick="showCancelModal(<?php echo $sponsorship['id']; ?>, '<?php echo sanitizeString($sponsorship['child_display_id']); ?>', '<?php echo sanitizeString($sponsorship['sponsor_name']); ?>')" 
                                                    class="btn btn-danger btn-small">Cancel</button>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($sponsorship['special_message'])): ?>
                                            <button onclick="showMessage('<?php echo addslashes(sanitizeString($sponsorship['special_message'])); ?>')" 
                                                    class="btn btn-warning btn-small">View Message</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideCancelModal()">&times;</span>
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
                    <textarea name="reason" id="reason" required placeholder="Please provide a reason for cancelling this sponsorship..."></textarea>
                </div>
                
                <div class="actions">
                    <button type="submit" class="btn btn-danger">Cancel Sponsorship</button>
                    <button type="button" onclick="hideCancelModal()" class="btn btn-secondary">Close</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Message Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideMessageModal()">&times;</span>
            <div class="modal-header">
                <h3>Special Message from Sponsor</h3>
            </div>
            <p id="messageText" style="white-space: pre-wrap; line-height: 1.5;"></p>
            <div class="actions">
                <button type="button" onclick="hideMessageModal()" class="btn btn-primary">Close</button>
            </div>
        </div>
    </div>

    <script>
        function filterSponsorships() {
            const status = document.getElementById('status-filter').value;
            const sort = document.getElementById('sort-filter').value;
            
            const url = new URL(window.location);
            url.searchParams.set('status', status);
            url.searchParams.set('sort', sort);
            window.location = url;
        }

        function showCancelModal(sponsorshipId, childId, sponsorName) {
            document.getElementById('cancelSponsorshipId').value = sponsorshipId;
            document.getElementById('cancelText').textContent = 
                `Are you sure you want to cancel the sponsorship of Child ${childId} by ${sponsorName}? This action will release the child back to available status.`;
            document.getElementById('cancelModal').style.display = 'block';
        }

        function hideCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
            document.getElementById('reason').value = '';
        }

        function showMessage(message) {
            document.getElementById('messageText').textContent = message;
            document.getElementById('messageModal').style.display = 'block';
        }

        function hideMessageModal() {
            document.getElementById('messageModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const cancelModal = document.getElementById('cancelModal');
            const messageModal = document.getElementById('messageModal');
            
            if (event.target === cancelModal) {
                hideCancelModal();
            }
            if (event.target === messageModal) {
                hideMessageModal();
            }
        }

        // Auto-refresh for pending sponsorships
        if (window.location.search.includes('status=pending') || window.location.search.includes('status=all') || !window.location.search.includes('status=')) {
            setTimeout(() => {
                window.location.reload();
            }, 60000); // Refresh every minute
        }
    </script>
</body>
</html>