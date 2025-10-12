<?php
declare(strict_types=1);

/**
 * Reports Page - Comprehensive Reporting System
 * Admin tool for viewing and exporting various reports
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/report_manager.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Reports';

// Get report type
$reportType = $_GET['type'] ?? 'dashboard';
$exportFormat = $_GET['export'] ?? '';

// Handle CSV exports
if ($exportFormat === 'csv') {
    switch ($reportType) {
        case 'sponsor_directory':
            $data = CFK_Report_Manager::getSponsorDirectoryReport();
            $headers = ['Sponsor Name', 'Email', 'Phone', 'Child ID', 'Child Name', 'Age', 'Status'];
            CFK_Report_Manager::exportToCSV($data, $headers, 'sponsor-directory-' . date('Y-m-d') . '.csv');
            break;

        case 'child_sponsor':
            $data = CFK_Report_Manager::getChildSponsorLookup();
            $headers = ['Child ID', 'Child Name', 'Age', 'Gender', 'Sponsor Name', 'Sponsor Email', 'Status'];
            CFK_Report_Manager::exportToCSV($data, $headers, 'child-sponsor-lookup-' . date('Y-m-d') . '.csv');
            break;

        case 'family_report':
            $data = CFK_Report_Manager::getFamilySponsorshipReport();
            $headers = ['Family Number', 'Family Name', 'Total Children', 'Available', 'Pending', 'Sponsored'];
            CFK_Report_Manager::exportToCSV($data, $headers, 'family-report-' . date('Y-m-d') . '.csv');
            break;

        case 'delivery_tracking':
            $data = CFK_Report_Manager::getGiftDeliveryReport();
            $headers = ['Sponsor Name', 'Email', 'Phone', 'Child ID', 'Child Name', 'Status', 'Days Since Confirmed'];
            CFK_Report_Manager::exportToCSV($data, $headers, 'delivery-tracking-' . date('Y-m-d') . '.csv');
            break;

        case 'available_children':
            $data = CFK_Report_Manager::getAvailableChildrenReport();
            $headers = ['Child ID', 'Name', 'Age', 'Gender', 'Family', 'Family Size', 'Available Siblings'];
            CFK_Report_Manager::exportToCSV($data, $headers, 'available-children-' . date('Y-m-d') . '.csv');
            break;
    }
}

include __DIR__ . '/includes/admin_header.php';
?>

<div class="reports-page">
    <div class="page-header">
        <h1>📊 Reports & Analytics</h1>
        <p class="page-subtitle">View, analyze, and export sponsorship data</p>
    </div>

    <!-- Report Navigation -->
    <div class="report-nav">
        <a href="?type=dashboard" class="report-nav-item <?php echo $reportType === 'dashboard' ? 'active' : ''; ?>">
            📈 Dashboard
        </a>
        <a href="?type=sponsor_directory" class="report-nav-item <?php echo $reportType === 'sponsor_directory' ? 'active' : ''; ?>">
            👥 Sponsor Directory
        </a>
        <a href="?type=child_sponsor" class="report-nav-item <?php echo $reportType === 'child_sponsor' ? 'active' : ''; ?>">
            🔍 Child-Sponsor Lookup
        </a>
        <a href="?type=family_report" class="report-nav-item <?php echo $reportType === 'family_report' ? 'active' : ''; ?>">
            👨‍👩‍👧‍👦 Family Report
        </a>
        <a href="?type=delivery_tracking" class="report-nav-item <?php echo $reportType === 'delivery_tracking' ? 'active' : ''; ?>">
            🎁 Gift Delivery Tracking
        </a>
        <a href="?type=available_children" class="report-nav-item <?php echo $reportType === 'available_children' ? 'active' : ''; ?>">
            ⭐ Available Children
        </a>
    </div>

    <!-- Report Content -->
    <div class="report-content">
        <?php if ($reportType === 'dashboard'): ?>
            <?php $stats = CFK_Report_Manager::getStatisticsSummary(); ?>

            <div class="stats-dashboard">
                <h2>Statistics Summary</h2>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Children</h3>
                        <div class="stat-details">
                            <p><strong>Total:</strong> <?php echo $stats['children']['total']; ?></p>
                            <p><strong>Available:</strong> <?php echo $stats['children']['available']; ?></p>
                            <p><strong>Pending:</strong> <?php echo $stats['children']['pending']; ?></p>
                            <p><strong>Sponsored:</strong> <?php echo $stats['children']['sponsored']; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <h3>Sponsorships</h3>
                        <div class="stat-details">
                            <p><strong>Total:</strong> <?php echo $stats['sponsorships']['total']; ?></p>
                            <p><strong>Pending:</strong> <?php echo $stats['sponsorships']['pending']; ?></p>
                            <p><strong>Confirmed:</strong> <?php echo $stats['sponsorships']['confirmed']; ?></p>
                            <p><strong>Completed:</strong> <?php echo $stats['sponsorships']['completed']; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <h3>Families</h3>
                        <div class="stat-details">
                            <p><strong>Total:</strong> <?php echo $stats['families']['total']; ?></p>
                            <p><strong>Fully Sponsored:</strong> <?php echo $stats['families']['fully_sponsored']; ?></p>
                            <p><strong>Partially Sponsored:</strong> <?php echo $stats['families']['total'] - $stats['families']['fully_sponsored']; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <h3>Sponsors</h3>
                        <div class="stat-details">
                            <p><strong>Unique Sponsors:</strong> <?php echo $stats['unique_sponsors']; ?></p>
                            <p><strong>Avg Children/Sponsor:</strong> <?php echo $stats['unique_sponsors'] > 0 ? round($stats['sponsorships']['total'] / $stats['unique_sponsors'], 1) : 0; ?></p>
                        </div>
                    </div>
                </div>

                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <a href="?type=sponsor_directory&export=csv" class="btn btn-primary">Export All Sponsors</a>
                    <a href="?type=delivery_tracking&export=csv" class="btn btn-primary">Export Delivery List</a>
                    <a href="?type=available_children&export=csv" class="btn btn-primary">Export Available Children</a>
                </div>
            </div>

        <?php elseif ($reportType === 'sponsor_directory'): ?>
            <?php
            $sponsors = CFK_Report_Manager::getSponsorDirectoryReport();

            // Group by sponsor
            $groupedSponsors = [];
            foreach ($sponsors as $row) {
                $email = $row['sponsor_email'];
                if (!isset($groupedSponsors[$email])) {
                    $groupedSponsors[$email] = [
                        'name' => $row['sponsor_name'],
                        'email' => $row['sponsor_email'],
                        'phone' => $row['sponsor_phone'],
                        'address' => $row['sponsor_address'],
                        'children' => []
                    ];
                }
                $groupedSponsors[$email]['children'][] = $row;
            }
            ?>

            <div class="report-header">
                <h2>Sponsor Directory</h2>
                <a href="?type=sponsor_directory&export=csv" class="btn btn-primary">Export to CSV</a>
            </div>

            <div class="sponsor-directory">
                <?php foreach ($groupedSponsors as $sponsor): ?>
                    <div class="sponsor-card">
                        <div class="sponsor-info">
                            <h3><?php echo sanitizeString($sponsor['name']); ?></h3>
                            <p><strong>Email:</strong> <a href="mailto:<?php echo $sponsor['email']; ?>"><?php echo $sponsor['email']; ?></a></p>
                            <?php if (!empty($sponsor['phone'])): ?>
                                <p><strong>Phone:</strong> <?php echo sanitizeString($sponsor['phone']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($sponsor['address'])): ?>
                                <p><strong>Address:</strong> <?php echo sanitizeString($sponsor['address']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="sponsored-children">
                            <h4>Sponsored Children (<?php echo count($sponsor['children']); ?>):</h4>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Child ID</th>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Sizes</th>
                                        <th>Wishes</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sponsor['children'] as $child): ?>
                                        <tr>
                                            <td><?php echo sanitizeString($child['child_display_id']); ?></td>
                                            <td><?php echo sanitizeString($child['child_name']); ?></td>
                                            <td><?php echo $child['child_age']; ?></td>
                                            <td>
                                                Shirt: <?php echo sanitizeString($child['shirt_size'] ?? 'N/A'); ?><br>
                                                Pants: <?php echo sanitizeString($child['pant_size'] ?? 'N/A'); ?><br>
                                                Shoes: <?php echo sanitizeString($child['shoe_size'] ?? 'N/A'); ?>
                                            </td>
                                            <td><?php echo sanitizeString(substr($child['wishes'] ?? '', 0, 50)) . '...'; ?></td>
                                            <td><span class="status-badge status-<?php echo $child['status']; ?>"><?php echo ucfirst($child['status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($reportType === 'child_sponsor'): ?>
            <?php
            $searchTerm = $_GET['search'] ?? '';
            $children = CFK_Report_Manager::getChildSponsorLookup($searchTerm);
            ?>

            <div class="report-header">
                <h2>Child-Sponsor Lookup</h2>
                <div class="search-form">
                    <form method="GET" action="">
                        <input type="hidden" name="type" value="child_sponsor">
                        <input type="text" name="search" placeholder="Search by child ID..." value="<?php echo sanitizeString($searchTerm); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="?type=child_sponsor&export=csv" class="btn btn-secondary">Export CSV</a>
                    </form>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Child ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Status</th>
                        <th>Sponsor</th>
                        <th>Contact</th>
                        <th>Sponsorship Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($children as $child): ?>
                        <tr>
                            <td><?php echo sanitizeString($child['child_display_id']); ?></td>
                            <td><?php echo sanitizeString($child['child_name']); ?></td>
                            <td><?php echo $child['age']; ?></td>
                            <td><span class="status-badge status-<?php echo $child['child_status']; ?>"><?php echo ucfirst($child['child_status']); ?></span></td>
                            <td><?php echo $child['sponsor_name'] ? sanitizeString($child['sponsor_name']) : '-'; ?></td>
                            <td>
                                <?php if ($child['sponsor_email']): ?>
                                    <a href="mailto:<?php echo $child['sponsor_email']; ?>"><?php echo $child['sponsor_email']; ?></a><br>
                                    <?php if ($child['sponsor_phone']): ?>
                                        <?php echo sanitizeString($child['sponsor_phone']); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($child['sponsorship_status']): ?>
                                    <span class="status-badge status-<?php echo $child['sponsorship_status']; ?>">
                                        <?php echo ucfirst($child['sponsorship_status']); ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($reportType === 'family_report'): ?>
            <?php $families = CFK_Report_Manager::getFamilySponsorshipReport(); ?>

            <div class="report-header">
                <h2>Family Sponsorship Report</h2>
                <a href="?type=family_report&export=csv" class="btn btn-primary">Export to CSV</a>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Family #</th>
                        <th>Family Name</th>
                        <th>Total Children</th>
                        <th>Available</th>
                        <th>Pending</th>
                        <th>Sponsored</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($families as $family): ?>
                        <tr>
                            <td><?php echo sanitizeString($family['family_number']); ?></td>
                            <td><?php echo sanitizeString($family['family_name'] ?? '-'); ?></td>
                            <td><?php echo $family['total_children']; ?></td>
                            <td><?php echo $family['available_count']; ?></td>
                            <td><?php echo $family['pending_count']; ?></td>
                            <td><?php echo $family['sponsored_count']; ?></td>
                            <td>
                                <?php if ($family['sponsored_count'] == $family['total_children']): ?>
                                    <span class="status-badge status-confirmed">Complete</span>
                                <?php elseif ($family['sponsored_count'] > 0): ?>
                                    <span class="status-badge status-pending">Partial</span>
                                <?php else: ?>
                                    <span class="status-badge status-available">None</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($reportType === 'delivery_tracking'): ?>
            <?php $deliveries = CFK_Report_Manager::getGiftDeliveryReport(); ?>

            <div class="report-header">
                <h2>Gift Delivery Tracking</h2>
                <a href="?type=delivery_tracking&export=csv" class="btn btn-primary">Export to CSV</a>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sponsor</th>
                        <th>Contact</th>
                        <th>Child ID</th>
                        <th>Child Name</th>
                        <th>Status</th>
                        <th>Confirmed Date</th>
                        <th>Days Waiting</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deliveries as $delivery): ?>
                        <tr>
                            <td><?php echo sanitizeString($delivery['sponsor_name']); ?></td>
                            <td>
                                <a href="mailto:<?php echo $delivery['sponsor_email']; ?>"><?php echo $delivery['sponsor_email']; ?></a><br>
                                <?php if ($delivery['sponsor_phone']): ?>
                                    <?php echo sanitizeString($delivery['sponsor_phone']); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo sanitizeString($delivery['child_display_id']); ?></td>
                            <td><?php echo sanitizeString($delivery['child_name']); ?></td>
                            <td><span class="status-badge status-<?php echo $delivery['status']; ?>"><?php echo ucfirst($delivery['status']); ?></span></td>
                            <td><?php echo $delivery['confirmation_date'] ? date('M j, Y', strtotime($delivery['confirmation_date'])) : '-'; ?></td>
                            <td><?php echo $delivery['days_since_confirmed'] ?? 0; ?> days</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($reportType === 'available_children'): ?>
            <?php
            $filters = [
                'age_min' => $_GET['age_min'] ?? '',
                'age_max' => $_GET['age_max'] ?? '',
                'gender' => $_GET['gender'] ?? ''
            ];
            $availableChildren = CFK_Report_Manager::getAvailableChildrenReport($filters);
            ?>

            <div class="report-header">
                <h2>Available Children</h2>
                <a href="?type=available_children&export=csv" class="btn btn-primary">Export to CSV</a>
            </div>

            <div class="filter-form">
                <form method="GET" action="">
                    <input type="hidden" name="type" value="available_children">
                    <input type="number" name="age_min" placeholder="Min Age" value="<?php echo sanitizeInt($filters['age_min']); ?>">
                    <input type="number" name="age_max" placeholder="Max Age" value="<?php echo sanitizeInt($filters['age_max']); ?>">
                    <select name="gender">
                        <option value="">All Genders</option>
                        <option value="M" <?php echo $filters['gender'] === 'M' ? 'selected' : ''; ?>>Boys</option>
                        <option value="F" <?php echo $filters['gender'] === 'F' ? 'selected' : ''; ?>>Girls</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Child ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Grade</th>
                        <th>Family</th>
                        <th>Siblings</th>
                        <th>Wishes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($availableChildren as $child): ?>
                        <tr>
                            <td><?php echo sanitizeString($child['display_id']); ?></td>
                            <td><?php echo $child['age']; ?></td>
                            <td><?php echo $child['gender'] === 'M' ? 'Boy' : 'Girl'; ?></td>
                            <td><?php echo sanitizeString($child['grade'] ?? '-'); ?></td>
                            <td><?php echo sanitizeString($child['family_number']); ?></td>
                            <td><?php echo $child['available_siblings']; ?> available</td>
                            <td><?php echo sanitizeString(substr($child['wishes'] ?? '', 0, 50)) . '...'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.reports-page {
    padding: 2rem;
}

.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: #666;
}

.report-nav {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.report-nav-item {
    padding: 0.75rem 1.5rem;
    background: #f8f9fa;
    border: 2px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    transition: all 0.2s;
}

.report-nav-item:hover {
    background: #e9ecef;
    border-color: #2c5530;
}

.report-nav-item.active {
    background: #2c5530;
    color: white;
    border-color: #2c5530;
}

.report-content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #2c5530;
}

.stats-dashboard {
    max-width: 1200px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.stat-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #2c5530;
}

.stat-card h3 {
    color: #2c5530;
    margin-bottom: 1rem;
}

.stat-details p {
    margin: 0.5rem 0;
}

.quick-actions {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #ddd;
}

.quick-actions h3 {
    margin-bottom: 1rem;
    color: #2c5530;
}

.quick-actions a {
    margin-right: 1rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.data-table th {
    background: #2c5530;
    color: white;
    padding: 0.75rem;
    text-align: left;
}

.data-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #ddd;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: bold;
}

.status-available {
    background: #d1ecf1;
    color: #0c5460;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-confirmed {
    background: #d4edda;
    color: #155724;
}

.status-completed {
    background: #cce5ff;
    color: #004085;
}

.sponsor-card {
    background: #f8f9fa;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border-radius: 8px;
    border-left: 4px solid #2c5530;
}

.sponsor-info h3 {
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.sponsored-children {
    margin-top: 1.5rem;
}

.sponsored-children h4 {
    color: #2c5530;
    margin-bottom: 1rem;
}

.search-form form, .filter-form form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.search-form input, .filter-form input, .filter-form select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #2c5530;
    color: white;
}

.btn-primary:hover {
    background: #1d3820;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}
</style>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
