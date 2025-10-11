<?php
define('CFK_APP', true);

// Set up minimal $_SERVER for CLI
$_SERVER['HTTP_HOST'] = 'localhost:8082';

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Test 1: No filters (should return all 133)
echo "=== Test 1: No Filters ===\n";
$children = getChildren([], 1, 12);
$totalCount = getChildrenCount([]);
echo "Children found: " . count($children) . " of $totalCount total\n\n";

// Test 2: Search for 'pants'
echo "=== Test 2: Search for 'pants' ===\n";
$filters = ['search' => 'pants'];

// Debug: Build the query manually to see what's happening
$sql = "
    SELECT c.*, f.family_number,
           CONCAT(f.family_number, c.child_letter) as display_id
    FROM children c
    JOIN families f ON c.family_id = f.id
    WHERE 1=1
";
$params = [];
if (!empty($filters['search'])) {
    $sql .= " AND (c.name LIKE :search OR c.interests LIKE :search OR c.wishes LIKE :search)";
    $params['search'] = '%' . $filters['search'] . '%';
}
$sql .= " AND c.status = 'available'";
echo "SQL: " . str_replace("\n", " ", $sql) . "\n";
echo "Params: ";
print_r($params);

$children = getChildren($filters, 1, 12);
$totalCount = getChildrenCount($filters);
echo "Children found: " . count($children) . " of $totalCount total\n\n";

// Test 3: Elementary age filter
echo "=== Test 3: Elementary Age (5-10) ===\n";
$filters = ['age_category' => 'elementary'];
$children = getChildren($filters, 1, 12);
$totalCount = getChildrenCount($filters);
echo "Children found: " . count($children) . " of $totalCount total\n\n";

// Test 4: Gender filter (Boys)
echo "=== Test 4: Gender = M ===\n";
$filters = ['gender' => 'M'];
$children = getChildren($filters, 1, 12);
$totalCount = getChildrenCount($filters);
echo "Children found: " . count($children) . " of $totalCount total\n\n";

// Test 5: Combined filters
echo "=== Test 5: Elementary Boys ===\n";
$filters = ['age_category' => 'elementary', 'gender' => 'M'];
$children = getChildren($filters, 1, 12);
$totalCount = getChildrenCount($filters);
echo "Children found: " . count($children) . " of $totalCount total\n\n";

// Show age categories config
echo "=== Age Categories Config ===\n";
print_r($ageCategories);
