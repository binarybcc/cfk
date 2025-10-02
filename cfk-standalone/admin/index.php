<?php
declare(strict_types=1);

/**
 * Admin Dashboard
 * Simple, non-coder friendly interface for managing children and sponsorships
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

$pageTitle = 'Admin Dashboard';

// Get dashboard statistics
$stats = [
    'total_children' => getChildrenCount([]),
    'available_children' => getChildrenCount(['status' => 'available']),
    'pending_sponsorships' => Database::fetchRow("SELECT COUNT(*) as total FROM sponsorships WHERE status = 'pending'")['total'] ?? 0,
    'completed_sponsorships' => Database::fetchRow("SELECT COUNT(*) as total FROM sponsorships WHERE status = 'completed'")['total'] ?? 0,
    'total_families' => Database::fetchRow("SELECT COUNT(*) as total FROM families")['total'] ?? 0
];

// Get recent activity
$recentSponsorships = Database::fetchAll("
    SELECT s.*, c.name as child_name, 
           CONCAT(f.family_number, c.child_letter) as child_display_id
    FROM sponsorships s
    JOIN children c ON s.child_id = c.id
    JOIN families f ON c.family_id = f.id
    ORDER BY s.request_date DESC
    LIMIT 10
");

// Get children needing attention (pending too long, etc.)
$childrenNeedingAttention = Database::fetchAll("
    SELECT c.*, f.family_number, f.family_name,
           CONCAT(f.family_number, c.child_letter) as display_id,
           s.request_date
    FROM children c 
    JOIN families f ON c.family_id = f.id
    LEFT JOIN sponsorships s ON c.id = s.child_id AND s.status = 'pending'
    WHERE c.status = 'pending' AND s.request_date < DATE_SUB(NOW(), INTERVAL 48 HOUR)
    ORDER BY s.request_date ASC
");

include __DIR__ . '/includes/admin_header.php';
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <p class="dashboard-subtitle">Manage children and sponsorships for Christmas for Kids</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3><?php echo $stats['total_children']; ?></h3>
                <p>Total Children</p>
            </div>
            <a href="manage_children.php" class="stat-link">Manage</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🎁</div>
            <div class="stat-content">
                <h3><?php echo $stats['available_children']; ?></h3>
                <p>Available for Sponsorship</p>
            </div>
            <a href="manage_children.php?status=available" class="stat-link">View</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <h3><?php echo $stats['pending_sponsorships']; ?></h3>
                <p>Pending Sponsorships</p>
            </div>
            <a href="manage_sponsorships.php?status=pending" class="stat-link">Review</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3><?php echo $stats['completed_sponsorships']; ?></h3>
                <p>Completed Sponsorships</p>
            </div>
            <a href="manage_sponsorships.php?status=completed" class="stat-link">View</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🏠</div>
            <div class="stat-content">
                <h3><?php echo $stats['total_families']; ?></h3>
                <p>Families</p>
            </div>
            <a href="manage_families.php" class="stat-link">Manage</a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="add_child.php" class="btn btn-primary">
                <span class="btn-icon">➕</span>
                Add New Child
            </a>
            <a href="add_family.php" class="btn btn-secondary">
                <span class="btn-icon">🏠</span>
                Add New Family
            </a>
            <a href="import_csv.php" class="btn btn-success">
                <span class="btn-icon">📊</span>
                Import from CSV
            </a>
            <a href="reports.php" class="btn btn-info">
                <span class="btn-icon">📈</span>
                View Reports
            </a>
        </div>
    </div>

    <!-- Attention Needed -->
    <?php if (!empty($childrenNeedingAttention)): ?>
    <div class="attention-section">
        <h2>🚨 Children Needing Attention</h2>
        <p>These children have pending sponsorships that may have expired or need follow-up:</p>
        <div class="attention-list">
            <?php foreach ($childrenNeedingAttention as $child): ?>
                <div class="attention-item">
                    <div class="attention-info">
                        <strong><?php echo sanitizeString($child['name']); ?></strong>
                        (ID: <?php echo sanitizeString($child['display_id']); ?>)
                        <span class="attention-date">
                            Pending since <?php echo date('M j, Y', strtotime($child['request_date'])); ?>
                        </span>
                    </div>
                    <div class="attention-actions">
                        <a href="edit_child.php?id=<?php echo $child['id']; ?>" class="btn btn-small btn-primary">Review</a>
                        <a href="manage_sponsorships.php?child_id=<?php echo $child['id']; ?>" class="btn btn-small btn-secondary">View Sponsorship</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h2>Recent Sponsorship Activity</h2>
        <?php if (empty($recentSponsorships)): ?>
            <p>No recent sponsorship activity.</p>
        <?php else: ?>
            <div class="activity-list">
                <?php foreach ($recentSponsorships as $sponsorship): ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-main">
                                <strong><?php echo sanitizeString($sponsorship['sponsor_name']); ?></strong>
                                requested to sponsor
                                <strong><?php echo sanitizeString($sponsorship['child_name']); ?></strong>
                                (ID: <?php echo sanitizeString($sponsorship['child_display_id']); ?>)
                            </div>
                            <div class="activity-meta">
                                Status: <span class="status status-<?php echo $sponsorship['status']; ?>">
                                    <?php echo ucfirst($sponsorship['status']); ?>
                                </span>
                                • <?php echo date('M j, Y g:i A', strtotime($sponsorship['request_date'])); ?>
                            </div>
                        </div>
                        <div class="activity-actions">
                            <a href="view_sponsorship.php?id=<?php echo $sponsorship['id']; ?>" 
                               class="btn btn-small btn-primary">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="view-all-activity">
                <a href="manage_sponsorships.php" class="btn btn-secondary">View All Sponsorships</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
}

.dashboard-header {
    text-align: center;
    margin-bottom: 3rem;
    padding: 2rem 0;
    background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
    color: white;
    border-radius: 12px;
}

.dashboard-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: white;
}

.dashboard-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.stat-icon {
    font-size: 2.5rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 50%;
}

.stat-content {
    flex: 1;
}

.stat-content h3 {
    font-size: 2rem;
    margin-bottom: 0.25rem;
    color: #2c5530;
}

.stat-content p {
    color: #666;
    margin: 0;
    font-size: 0.9rem;
}

.stat-link {
    color: #2c5530;
    text-decoration: none;
    font-weight: bold;
    padding: 0.5rem 1rem;
    border: 1px solid #2c5530;
    border-radius: 6px;
    transition: all 0.2s;
}

.stat-link:hover {
    background: #2c5530;
    color: white;
}

.quick-actions {
    margin-bottom: 3rem;
}

.quick-actions h2 {
    margin-bottom: 1.5rem;
    color: #2c5530;
}

.action-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.2s;
    color: white;
}

.btn-icon {
    font-size: 1.2rem;
}

.btn-primary {
    background: #2c5530;
}

.btn-primary:hover {
    background: #1e3a21;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
}

.btn-secondary:hover {
    background: #545862;
}

.btn-success {
    background: #28a745;
}

.btn-success:hover {
    background: #218838;
}

.btn-info {
    background: #17a2b8;
}

.btn-info:hover {
    background: #138496;
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.attention-section {
    margin-bottom: 3rem;
    background: #fff3cd;
    padding: 2rem;
    border-radius: 12px;
    border: 1px solid #ffeaa7;
}

.attention-section h2 {
    color: #856404;
    margin-bottom: 1rem;
}

.attention-list {
    margin-top: 1.5rem;
}

.attention-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.attention-info {
    flex: 1;
}

.attention-date {
    display: block;
    color: #666;
    font-size: 0.9rem;
    margin-top: 0.25rem;
}

.attention-actions {
    display: flex;
    gap: 0.5rem;
}

.recent-activity {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.recent-activity h2 {
    margin-bottom: 1.5rem;
    color: #2c5530;
}

.activity-list {
    margin-bottom: 1.5rem;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #eee;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-info {
    flex: 1;
}

.activity-main {
    margin-bottom: 0.5rem;
}

.activity-meta {
    font-size: 0.9rem;
    color: #666;
}

.status {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: bold;
    text-transform: uppercase;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-confirmed {
    background: #cce7ff;
    color: #004085;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.view-all-activity {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        grid-template-columns: 1fr;
    }
    
    .attention-item,
    .activity-item {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .attention-actions,
    .activity-actions {
        justify-content: center;
    }
}
</style>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>