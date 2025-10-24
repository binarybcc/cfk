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

// Use namespaced classes
use CFK\Report\Manager as ReportManager;

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Reports';
$message = '';
$messageType = '';

// Handle sponsor edit action
if ($_POST !== [] && isset($_POST['action']) && $_POST['action'] === 'edit_sponsor') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Security token invalid. Please try again.';
        $messageType = 'error';
    } else {
        $sponsorEmail = sanitizeEmail($_POST['sponsor_email'] ?? '');
        $newEmail = sanitizeEmail($_POST['new_email'] ?? '');

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email address';
            $messageType = 'error';
        } else {
            try {
                // Update all sponsorships with this email
                Database::query(
                    "UPDATE sponsorships
                     SET sponsor_name = ?,
                         sponsor_email = ?,
                         sponsor_phone = ?,
                         sponsor_address = ?
                     WHERE sponsor_email = ?",
                    [
                        sanitizeString($_POST['sponsor_name'] ?? ''),
                        $newEmail,
                        sanitizeString($_POST['sponsor_phone'] ?? ''),
                        sanitizeString($_POST['sponsor_address'] ?? ''),
                        $sponsorEmail
                    ]
                );
                $message = 'Sponsor information updated successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                error_log('Update sponsor error: ' . $e->getMessage());
                $message = 'Failed to update sponsor information';
                $messageType = 'error';
            }
        }
    }
}

// AJAX endpoint to fetch sponsor data
if (isset($_GET['action']) && $_GET['action'] === 'get_sponsor' && isset($_GET['email'])) {
    $email = sanitizeEmail($_GET['email']);
    $sponsor = Database::fetchRow(
        "SELECT sponsor_name, sponsor_email, sponsor_phone, sponsor_address
         FROM sponsorships
         WHERE sponsor_email = ?
         LIMIT 1",
        [$email]
    );

    if ($sponsor) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'sponsor' => $sponsor]);
        exit;
    } else {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sponsor not found']);
        exit;
    }
}

// Get report type
$reportType = $_GET['type'] ?? 'dashboard';
$exportFormat = $_GET['export'] ?? '';

// Handle CSV exports
if ($exportFormat === 'csv') {
    switch ($reportType) {
        case 'sponsor_directory':
            $data = ReportManager::getSponsorDirectoryReport();
            $headers = ['Sponsor Name', 'Sponsor Email', 'Sponsor Phone', 'Child Display ID', 'Child Name', 'Child Age', 'Status'];
            ReportManager::exportToCSV($data, $headers, 'sponsor-directory-' . date('Y-m-d') . '.csv');
            break;

        case 'child_sponsor':
            $data = ReportManager::getChildSponsorLookup();
            $headers = ['Child ID', 'Child Display ID', 'Child Name', 'Age', 'Gender', 'Child Status', 'Sponsor Name', 'Sponsor Email', 'Sponsorship Status'];
            ReportManager::exportToCSV($data, $headers, 'child-sponsor-lookup-' . date('Y-m-d') . '.csv');
            break;

        case 'family_report':
            $data = ReportManager::getFamilySponsorshipReport();
            $headers = ['Family Number', 'Total Children', 'Available', 'Pending', 'Sponsored'];
            ReportManager::exportToCSV($data, $headers, 'family-report-' . date('Y-m-d') . '.csv');
            break;

        case 'available_children':
            $data = ReportManager::getAvailableChildrenReport();
            $headers = ['Display ID', 'Name', 'Age', 'Gender', 'Family Number', 'Family Size', 'Available Siblings'];
            ReportManager::exportToCSV($data, $headers, 'available-children-' . date('Y-m-d') . '.csv');
            break;

        case 'complete_export':
            $data = ReportManager::getCompleteChildSponsorReport();
            $headers = [
                'Child ID', 'Child Name', 'Age', 'Gender', 'Grade', 'School',
                'Shirt Size', 'Pant Size', 'Shoe Size', 'Jacket Size',
                'Essential Needs', 'Wishes', 'Special Needs', 'Child Status',
                'Family Number',
                'Sponsor Name', 'Sponsor Email', 'Sponsor Phone', 'Sponsor Address',
                'Sponsorship Status', 'Sponsorship Date', 'Request Date', 'Confirmation Date', 'Completion Date'
            ];
            ReportManager::exportToCSV($data, $headers, 'complete-children-sponsors-' . date('Y-m-d') . '.csv');
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
        <a href="?type=available_children" class="report-nav-item <?php echo $reportType === 'available_children' ? 'active' : ''; ?>">
            ⭐ Available Children
        </a>
        <a href="?type=complete_export" class="report-nav-item <?php echo $reportType === 'complete_export' ? 'active' : ''; ?>">
            📋 Complete Export
        </a>
    </div>

    <!-- Report Content -->
    <div class="report-content">
        <?php if ($reportType === 'dashboard') : ?>
            <?php $stats = ReportManager::getStatisticsSummary(); ?>

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
                    <a href="?type=complete_export&export=csv" class="btn btn-primary">📋 Export Complete Database</a>
                    <a href="?type=sponsor_directory&export=csv" class="btn btn-primary">Export All Sponsors</a>
                    <a href="?type=available_children&export=csv" class="btn btn-primary">Export Available Children</a>
                </div>
            </div>

        <?php elseif ($reportType === 'sponsor_directory') : ?>
            <?php
            $sponsors = ReportManager::getSponsorDirectoryReport();

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

            <?php if ($message) : ?>
                <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 1rem;">
                    <?php echo sanitizeString($message); ?>
                </div>
            <?php endif; ?>

            <div class="sponsor-directory">
                <?php if ($groupedSponsors === []) : ?>
                    <div class="empty-state">
                        <h3>No Sponsors Found</h3>
                        <p>There are currently no sponsorships in the system.</p>
                    </div>
                <?php else : ?>
                    <?php foreach ($groupedSponsors as $sponsor) : ?>
                        <div class="sponsor-card">
                        <div class="sponsor-info">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <h3><?php echo sanitizeString($sponsor['name']); ?></h3>
                                <button class="btn btn-small btn-edit-sponsor"
                                        data-sponsor-email="<?php echo htmlspecialchars($sponsor['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                        style="background: #0d6efd; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer;">
                                    ✏️ Edit Sponsor
                                </button>
                            </div>
                            <p><strong>Email:</strong> <a href="mailto:<?php echo $sponsor['email']; ?>"><?php echo $sponsor['email']; ?></a></p>
                            <?php if (!empty($sponsor['phone'])) : ?>
                                <p><strong>Phone:</strong> <a href="tel:<?php echo sanitizeString($sponsor['phone']); ?>">📞 <?php echo sanitizeString($sponsor['phone']); ?></a></p>
                            <?php endif; ?>
                            <?php if (!empty($sponsor['address'])) : ?>
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
                                    <?php foreach ($sponsor['children'] as $child) : ?>
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
                                            <td><span class="status-badge status-<?php echo $child['status']; ?>"><?php echo ucfirst((string) $child['status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php elseif ($reportType === 'child_sponsor') : ?>
            <?php
            $searchTerm = $_GET['search'] ?? '';
            $children = ReportManager::getChildSponsorLookup($searchTerm);
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

            <?php if ($children === []) : ?>
                <div class="empty-state">
                    <h3>No Children Found</h3>
                    <p><?php echo $searchTerm ? 'No children match your search criteria.' : 'There are currently no children in the system.'; ?></p>
                </div>
            <?php else : ?>
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
                        <?php foreach ($children as $child) : ?>
                        <tr>
                            <td><?php echo sanitizeString($child['child_display_id']); ?></td>
                            <td><?php echo sanitizeString($child['child_name']); ?></td>
                            <td><?php echo $child['age']; ?></td>
                            <td><span class="status-badge status-<?php echo $child['child_status']; ?>"><?php echo ucfirst((string) $child['child_status']); ?></span></td>
                            <td><?php echo $child['sponsor_name'] ? sanitizeString($child['sponsor_name']) : '-'; ?></td>
                            <td>
                                <?php if ($child['sponsor_email']) : ?>
                                    <a href="mailto:<?php echo $child['sponsor_email']; ?>"><?php echo $child['sponsor_email']; ?></a><br>
                                    <?php if ($child['sponsor_phone']) : ?>
                                        <a href="tel:<?php echo sanitizeString($child['sponsor_phone']); ?>">📞 <?php echo sanitizeString($child['sponsor_phone']); ?></a>
                                    <?php endif; ?>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($child['sponsorship_status']) : ?>
                                    <span class="status-badge status-<?php echo $child['sponsorship_status']; ?>">
                                        <?php echo ucfirst((string) $child['sponsorship_status']); ?>
                                    </span>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

        <?php elseif ($reportType === 'family_report') : ?>
            <?php $families = ReportManager::getFamilySponsorshipReport(); ?>

            <div class="report-header">
                <h2>Family Sponsorship Report</h2>
                <a href="?type=family_report&export=csv" class="btn btn-primary">Export to CSV</a>
            </div>

            <?php if ($families === []) : ?>
                <div class="empty-state">
                    <h3>No Families Found</h3>
                    <p>There are currently no families in the system.</p>
                </div>
            <?php else : ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Family #</th>
                            <th>Total Children</th>
                            <th>Available</th>
                            <th>Pending</th>
                            <th>Sponsored</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($families as $family) : ?>
                        <tr>
                            <td><?php echo sanitizeString($family['family_number']); ?></td>
                            <td><?php echo $family['total_children']; ?></td>
                            <td><?php echo $family['available_count']; ?></td>
                            <td><?php echo $family['pending_count']; ?></td>
                            <td><?php echo $family['sponsored_count']; ?></td>
                            <td>
                                <?php if ($family['sponsored_count'] == $family['total_children']) : ?>
                                    <span class="status-badge status-confirmed">Complete</span>
                                <?php elseif ($family['sponsored_count'] > 0) : ?>
                                    <span class="status-badge status-pending">Partial</span>
                                <?php else : ?>
                                    <span class="status-badge status-available">None</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

        <?php elseif ($reportType === 'available_children') : ?>
            <?php
            $filters = [
                'age_min' => $_GET['age_min'] ?? '',
                'age_max' => $_GET['age_max'] ?? '',
                'gender' => $_GET['gender'] ?? ''
            ];
            $availableChildren = ReportManager::getAvailableChildrenReport($filters);
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

            <?php if ($availableChildren === []) : ?>
                <div class="empty-state">
                    <h3>No Available Children</h3>
                    <p>There are currently no children available for sponsorship.</p>
                </div>
            <?php else : ?>
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
                        <?php foreach ($availableChildren as $child) : ?>
                        <tr>
                            <td><?php echo sanitizeString($child['display_id']); ?></td>
                            <td><?php echo sanitizeString($child['name']); ?></td>
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

        <?php elseif ($reportType === 'complete_export') : ?>
            <?php
            $filters = [];
            $completeData = ReportManager::getCompleteChildSponsorReport($filters);

            // Count sponsored vs unsponsored
            $sponsoredCount = 0;
            $unsponsoredCount = 0;
            foreach ($completeData as $row) {
                if (!empty($row['sponsor_name'])) {
                    $sponsoredCount++;
                } else {
                    $unsponsoredCount++;
                }
            }
            ?>

            <div class="report-header">
                <h2>Complete Children & Sponsor Export</h2>
                <a href="?type=complete_export&export=csv" class="btn btn-primary">📥 Download CSV</a>
            </div>

            <div class="export-summary">
                <p><strong>This report includes all children in the database with complete information:</strong></p>
                <ul>
                    <li>✅ Total Children: <?php echo count($completeData); ?></li>
                    <li>✅ Sponsored: <?php echo $sponsoredCount; ?></li>
                    <li>✅ Not Yet Sponsored: <?php echo $unsponsoredCount; ?></li>
                </ul>
                <p><strong>Data Included:</strong></p>
                <ul>
                    <li>Child Information: ID, Name, Age, Gender, Grade, School, Clothing Sizes, Essential Needs, Wishes, Special Needs</li>
                    <li>Family Information: Family Number</li>
                    <li>Sponsor Information: Name, Email, Phone, Address (if child is sponsored)</li>
                    <li>Sponsorship Details: Status, Request Date, Confirmation Date, Completion Date (if applicable)</li>
                </ul>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Child ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Grade</th>
                        <th>Child Status</th>
                        <th>Sponsor</th>
                        <th>Contact</th>
                        <th>Sponsorship Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completeData as $row) : ?>
                        <tr>
                            <td><?php echo sanitizeString($row['child_id']); ?></td>
                            <td><?php echo sanitizeString($row['child_name']); ?></td>
                            <td><?php echo $row['age']; ?></td>
                            <td><?php echo $row['gender'] === 'M' ? 'Boy' : 'Girl'; ?></td>
                            <td><?php echo sanitizeString($row['grade'] ?? '-'); ?></td>
                            <td><span class="status-badge status-<?php echo $row['child_status']; ?>"><?php echo ucfirst((string) $row['child_status']); ?></span></td>
                            <td><?php echo $row['sponsor_name'] ? sanitizeString($row['sponsor_name']) : '-'; ?></td>
                            <td>
                                <?php if ($row['sponsor_email']) : ?>
                                    <a href="mailto:<?php echo $row['sponsor_email']; ?>"><?php echo $row['sponsor_email']; ?></a>
                                    <?php if ($row['sponsor_phone']) : ?>
                                        <br><?php echo sanitizeString($row['sponsor_phone']); ?>
                                    <?php endif; ?>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['sponsorship_date']) : ?>
                                    <?php echo date('M j, Y', strtotime((string) $row['sponsorship_date'])); ?>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.export-summary {
    background: #f8f9fa;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border-radius: 8px;
    border-left: 4px solid #2c5530;
}

.export-summary ul {
    margin: 0.5rem 0 0.5rem 2rem;
}

.export-summary li {
    margin: 0.25rem 0;
}
</style>

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
    padding: 0.07rem 0.5rem;
    text-align: left;
    line-height: 1.2;
}

.data-table td {
    padding: 0.07rem 0.5rem;
    border-bottom: 1px solid #ddd;
    line-height: 1.2;
}

/* Zebra Striping */
.data-table tbody tr:nth-child(even) {
    background: #e9ecef;
}

.data-table tbody tr:nth-child(odd) {
    background: white;
}

.data-table tr:hover {
    background: #e7f1ff !important;
    transition: background-color 0.15s ease;
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

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 2rem 0;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 0.5rem;
    font-size: 1.5rem;
}

.empty-state p {
    color: #999;
    font-size: 1rem;
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
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.modal-header {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #2c5530;
}

.modal-header h3 {
    margin: 0;
    color: #2c5530;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: #000;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}
</style>

<!-- Edit Sponsor Modal -->
<div id="editSponsorModal" class="modal">
    <div class="modal-content">
        <span class="close" id="close-edit-sponsor-modal-x">&times;</span>
        <div class="modal-header">
            <h3>Edit Sponsor Information</h3>
        </div>
        <form method="POST" action="?type=sponsor_directory">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="edit_sponsor">
            <input type="hidden" name="sponsor_email" id="editOriginalEmail">

            <div class="form-group">
                <label for="editSponsorName">Sponsor Name: *</label>
                <input type="text" name="sponsor_name" id="editSponsorName" required
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div class="form-group">
                <label for="editNewEmail">Sponsor Email: *</label>
                <input type="email" name="new_email" id="editNewEmail" required
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                <small style="color: #666;">This will update the email for all of this sponsor's children</small>
            </div>

            <div class="form-group">
                <label for="editSponsorPhoneReport">Sponsor Phone:</label>
                <input type="tel" name="sponsor_phone" id="editSponsorPhoneReport"
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div class="form-group">
                <label for="editSponsorAddressReport">Sponsor Address:</label>
                <textarea name="sponsor_address" id="editSponsorAddressReport" rows="3"
                          style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;"></textarea>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Update Sponsor</button>
                <button type="button" id="close-edit-sponsor-modal-btn" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script nonce="<?php echo $cspNonce ?? ''; ?>">
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editSponsorModal');
    const editButtons = document.querySelectorAll('.btn-edit-sponsor');
    const closeEditModalX = document.getElementById('close-edit-sponsor-modal-x');
    const closeEditModalBtn = document.getElementById('close-edit-sponsor-modal-btn');

    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sponsorEmail = this.getAttribute('data-sponsor-email');

            // Fetch sponsor data via AJAX
            fetch(`?action=get_sponsor&email=${encodeURIComponent(sponsorEmail)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.sponsor) {
                        const sp = data.sponsor;

                        // Populate form fields
                        document.getElementById('editOriginalEmail').value = sp.sponsor_email;
                        document.getElementById('editSponsorName').value = sp.sponsor_name || '';
                        document.getElementById('editNewEmail').value = sp.sponsor_email || '';
                        document.getElementById('editSponsorPhoneReport').value = sp.sponsor_phone || '';
                        document.getElementById('editSponsorAddressReport').value = sp.sponsor_address || '';

                        // Show modal
                        editModal.style.display = 'block';
                    } else {
                        alert('Error loading sponsor data: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error fetching sponsor data:', error);
                    alert('Error loading sponsor data. Please try again.');
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
