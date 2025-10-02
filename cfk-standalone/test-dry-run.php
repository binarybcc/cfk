<?php
define('CFK_APP', true);

// Set required environment variables for CLI
$_SERVER['HTTP_HOST'] = 'localhost:8082';

require_once '/var/www/html/config/config.php';
require_once '/var/www/html/includes/csv_handler.php';

echo "Testing dry run functionality...\n";

// Get initial count
$countResult = Database::fetchRow("SELECT COUNT(*) as total FROM children");
$initialCount = $countResult['total'] ?? 0;
echo "Initial children count: $initialCount\n";

// Test dry run (preview)
echo "\n--- Testing DRY RUN (Preview) ---\n";
$dryRunResults = CFK_CSV_Handler::importChildrenFromCsv('/var/www/html/dry-run-test.csv', ['dry_run' => true]);
echo "Dry run results:\n";
echo "- Success: " . ($dryRunResults['success'] ? 'Yes' : 'No') . "\n";
echo "- Imported: " . $dryRunResults['imported'] . "\n";
echo "- Errors: " . count($dryRunResults['errors']) . "\n";
echo "- Warnings: " . count($dryRunResults['warnings']) . "\n";

// Check count after dry run
$countResult = Database::fetchRow("SELECT COUNT(*) as total FROM children");
$afterDryRunCount = $countResult['total'] ?? 0;
echo "Children count after dry run: $afterDryRunCount\n";

if ($afterDryRunCount == $initialCount) {
    echo "✅ DRY RUN SUCCESS: No records actually imported!\n";
} else {
    echo "❌ DRY RUN FAILED: Records were imported when they shouldn't have been!\n";
}

echo "\n--- Testing ACTUAL IMPORT ---\n";
$actualResults = CFK_CSV_Handler::importChildrenFromCsv('/var/www/html/dry-run-test.csv');
echo "Actual import results:\n";
echo "- Success: " . ($actualResults['success'] ? 'Yes' : 'No') . "\n";
echo "- Imported: " . $actualResults['imported'] . "\n";
echo "- Errors: " . count($actualResults['errors']) . "\n";
echo "- Warnings: " . count($actualResults['warnings']) . "\n";

// Check final count
$countResult = Database::fetchRow("SELECT COUNT(*) as total FROM children");
$finalCount = $countResult['total'] ?? 0;
echo "Final children count: $finalCount\n";

if ($finalCount == $afterDryRunCount + $actualResults['imported']) {
    echo "✅ ACTUAL IMPORT SUCCESS: Records were properly imported!\n";
} else {
    echo "❌ ACTUAL IMPORT ISSUE: Expected " . ($afterDryRunCount + $actualResults['imported']) . " but got $finalCount\n";
}

echo "\n--- SUMMARY ---\n";
echo "Initial: $initialCount\n";
echo "After dry run: $afterDryRunCount (should be same as initial)\n";
echo "After actual: $finalCount (should be initial + imported)\n";
echo "Expected change: " . $actualResults['imported'] . "\n";
echo "Actual change: " . ($finalCount - $initialCount) . "\n";