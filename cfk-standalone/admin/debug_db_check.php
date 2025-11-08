<?php

declare(strict_types=1);

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

use CFK\Database\Connection;

// Check if user is logged in
if (! isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Database Diagnostic';

// Generate CSP nonce for inline scripts
$cspNonce = bin2hex(random_bytes(16));

// Initialize variables in case of exception
$childCount = 0;
$familyCount = 0;
$sampleChildren = [];
$familyRange = ['min' => 0, 'max' => 0];

try {
    $db = Connection::getConnection();

    // Check children table
    $stmt = $db->query('SELECT COUNT(*) FROM cfk_children');
    $childCount = $stmt !== false ? $stmt->fetchColumn() : 0;

    // Check families table
    $stmt = $db->query('SELECT COUNT(*) FROM cfk_families');
    $familyCount = $stmt !== false ? $stmt->fetchColumn() : 0;

    if ($childCount > 0) {
        $stmt = $db->query('
            SELECT c.id, f.family_number, c.child_letter, c.name, c.status
            FROM cfk_children c
            JOIN cfk_families f ON c.family_id = f.id
            ORDER BY f.family_number, c.child_letter
            LIMIT 20
        ');
        $sampleChildren = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmt = $db->query('
            SELECT MIN(family_number) as min, MAX(family_number) as max
            FROM cfk_families
        ');
        $familyRange = $stmt !== false ? $stmt->fetch(PDO::FETCH_ASSOC) : ['min' => 0, 'max' => 0];
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

include __DIR__ . '/includes/admin_header.php';
?>

<div class="container" style="max-width: 1200px; margin: 2rem auto; padding: 0 2rem;">
    <h1>Database Diagnostic</h1>

    <?php if (isset($error)) : ?>
        <div class="alert alert-error">
            <strong>Error:</strong> <?php echo htmlspecialchars((string) $error); ?>
        </div>
    <?php else : ?>
        <div class="stats-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0;">
            <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3>Children in Database</h3>
                <p style="font-size: 3rem; font-weight: bold; margin: 1rem 0;"><?php echo $childCount; ?></p>
            </div>

            <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3>Families in Database</h3>
                <p style="font-size: 3rem; font-weight: bold; margin: 1rem 0;"><?php echo $familyCount; ?></p>
            </div>
        </div>

        <?php if ($childCount > 0) : ?>
            <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 2rem 0;">
                <h3>Family Number Range</h3>
                <p><strong>Minimum:</strong> <?php echo $familyRange['min']; ?></p>
                <p><strong>Maximum:</strong> <?php echo $familyRange['max']; ?></p>
            </div>

            <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3>Sample Children (First 20)</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f5f7fa; border-bottom: 2px solid #e9ecef;">
                            <th style="padding: 0.75rem; text-align: left;">ID</th>
                            <th style="padding: 0.75rem; text-align: left;">Family#</th>
                            <th style="padding: 0.75rem; text-align: left;">Letter</th>
                            <th style="padding: 0.75rem; text-align: left;">Full Name</th>
                            <th style="padding: 0.75rem; text-align: left;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sampleChildren as $child) : ?>
                            <tr style="border-bottom: 1px solid #e9ecef;">
                                <td style="padding: 0.75rem;"><?php echo $child['id']; ?></td>
                                <td style="padding: 0.75rem;"><?php echo $child['family_number']; ?></td>
                                <td style="padding: 0.75rem;"><?php echo $child['child_letter']; ?></td>
                                <td style="padding: 0.75rem;"><?php echo htmlspecialchars((string) $child['name']); ?></td>
                                <td style="padding: 0.75rem;"><?php echo $child['status']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; padding: 1rem; border-radius: 4px; margin: 2rem 0;">
                <strong>Database is empty</strong> - No children found in the database.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
