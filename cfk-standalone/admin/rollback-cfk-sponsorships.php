<?php

/**
 * Rollback CFK Sponsorships Script
 *
 * PURPOSE: Reverses the sponsor-remaining-children.php script by removing
 *          all sponsorships created for Christmas for Kids organization.
 *
 * USAGE:
 *   - Dry run (preview): php rollback-cfk-sponsorships.php --dry-run
 *   - Execute:          php rollback-cfk-sponsorships.php --execute
 *   - Force execute:    php rollback-cfk-sponsorships.php --execute --force
 *
 * SAFETY:
 *   - Requires --execute flag to make changes
 *   - Only removes CFK-created sponsorships (by email)
 *   - Confirms before proceeding
 *   - Logs all operations
 *
 * @version 1.0.0
 * @date 2025-12-01
 */

declare(strict_types=1);

// Security constant
define('CFK_APP', true);

// Load configuration and database
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Configuration
// IMPORTANT: This matches the email used by sponsor-remaining-children.php
// This ensures we ONLY rollback auto-sponsored children, not genuine CFK sponsorships
$CFK_EMAIL = 'end-of-season@christmasforkids.org';

// Parse command line arguments
$options = getopt('', ['dry-run', 'execute', 'force', 'help']);
$isDryRun = isset($options['dry-run']);
$isExecute = isset($options['execute']);
$isForce = isset($options['force']);
$showHelp = isset($options['help']);

// Color output for terminal
function colorOutput(string $text, string $color = 'white'): string
{
    $colors = [
        'red' => "\033[0;31m",
        'green' => "\033[0;32m",
        'yellow' => "\033[0;33m",
        'blue' => "\033[0;34m",
        'cyan' => "\033[0;36m",
        'white' => "\033[0;37m",
        'bold' => "\033[1m",
        'reset' => "\033[0m"
    ];
    return ($colors[$color] ?? $colors['white']) . $text . $colors['reset'];
}

// Show help
if ($showHelp || (!$isDryRun && !$isExecute)) {
    echo colorOutput("\nChristmas for Kids - Rollback CFK Sponsorships Script\n", 'bold');
    echo colorOutput("=" . str_repeat("=", 55) . "\n", 'cyan');
    echo "\nPURPOSE:\n";
    echo "  Reverses sponsor-remaining-children.php by removing CFK sponsorships\n\n";
    echo "USAGE:\n";
    echo "  " . colorOutput("Dry Run (Preview):", 'yellow') . "\n";
    echo "    php rollback-cfk-sponsorships.php --dry-run\n\n";
    echo "  " . colorOutput("Execute:", 'green') . "\n";
    echo "    php rollback-cfk-sponsorships.php --execute\n\n";
    echo "  " . colorOutput("Force Execute (No Confirmation):", 'red') . "\n";
    echo "    php rollback-cfk-sponsorships.php --execute --force\n\n";
    exit(0);
}

// Setup logging
$logFile = __DIR__ . '/../logs/rollback-cfk-' . date('Y-m-d-His') . '.log';
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

function logMessage(string $message, string $level = 'INFO'): void
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] [{$level}] {$message}\n";
    file_put_contents($logFile, $logLine, FILE_APPEND);

    $color = match ($level) {
        'ERROR' => 'red',
        'WARNING' => 'yellow',
        'SUCCESS' => 'green',
        'INFO' => 'cyan',
        default => 'white'
    };
    echo colorOutput($logLine, $color);
}

// Banner
echo "\n";
echo colorOutput("╔══════════════════════════════════════════════════════════╗\n", 'cyan');
echo colorOutput("║  Christmas for Kids - Rollback CFK Sponsorships         ║\n", 'cyan');
echo colorOutput("╚══════════════════════════════════════════════════════════╝\n", 'cyan');
echo "\n";

logMessage("Rollback script started");
logMessage("Mode: " . ($isDryRun ? "DRY RUN (preview only)" : "EXECUTE (will make changes)"));

// Find CFK sponsorships
logMessage("Finding CFK-created sponsorships...");

try {
    $cfkSponsorships = Database::fetchAll(
        "SELECT s.id, s.child_id, s.sponsor_name, s.sponsor_email,
                CONCAT(f.family_number, c.child_letter) as display_id,
                s.confirmation_date
         FROM sponsorships s
         JOIN children c ON s.child_id = c.id
         JOIN families f ON c.family_id = f.id
         WHERE s.sponsor_email = ?
         AND s.status = 'confirmed'
         ORDER BY s.confirmation_date DESC",
        [$CFK_EMAIL]
    );
} catch (Exception $e) {
    logMessage("ERROR: Failed to query database: " . $e->getMessage(), 'ERROR');
    exit(1);
}

$count = count($cfkSponsorships);
logMessage("Found {$count} CFK sponsorships");

if ($count === 0) {
    echo "\n";
    logMessage("No CFK sponsorships found to remove.", 'INFO');
    echo "\n";
    exit(0);
}

// Display summary
echo "\n";
echo colorOutput("CFK SPONSORSHIPS TO REMOVE\n", 'bold');
echo colorOutput(str_repeat("-", 70) . "\n", 'cyan');
printf("%-15s %-12s %-25s %-20s\n", "Sponsorship ID", "Child ID", "Email", "Date");
echo colorOutput(str_repeat("-", 70) . "\n", 'cyan');

foreach ($cfkSponsorships as $sponsorship) {
    printf(
        "%-15s %-12s %-25s %-20s\n",
        $sponsorship['id'],
        $sponsorship['display_id'],
        $sponsorship['sponsor_email'],
        date('Y-m-d H:i', strtotime($sponsorship['confirmation_date']))
    );
}

echo colorOutput(str_repeat("-", 70) . "\n", 'cyan');
echo "\n";

// Confirmation
if ($isExecute && !$isForce) {
    echo colorOutput("⚠️  WARNING: This will DELETE {$count} sponsorship records!\n", 'red');
    echo colorOutput("These children will become unsponsored again.\n\n", 'yellow');
    echo "Are you sure you want to proceed? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    if ($handle === false) {
        logMessage("Failed to open stdin for confirmation", 'ERROR');
        exit(1);
    }
    $line = fgets($handle);
    if ($line === false) {
        $line = '';
    }
    $confirmation = trim(strtolower($line));
    fclose($handle);

    if ($confirmation !== 'yes') {
        logMessage("Rollback cancelled by user", 'WARNING');
        echo "\n" . colorOutput("Rollback cancelled.\n\n", 'yellow');
        exit(0);
    }
    echo "\n";
}

// Execute rollback
if ($isDryRun) {
    echo colorOutput("DRY RUN MODE - No changes will be made\n", 'yellow');
    echo colorOutput("The following would be removed:\n\n", 'yellow');

    foreach ($cfkSponsorships as $sponsorship) {
        echo "  • Sponsorship #{$sponsorship['id']} for child {$sponsorship['display_id']}\n";
    }

    echo "\n";
    logMessage("Dry run completed. No changes made.", 'INFO');
    echo colorOutput("To execute for real, run with --execute flag\n\n", 'cyan');
    exit(0);
}

// EXECUTE MODE
logMessage("Removing CFK sponsorships...");

$successCount = 0;
$errorCount = 0;

foreach ($cfkSponsorships as $sponsorship) {
    try {
        $result = Database::execute(
            "DELETE FROM sponsorships WHERE id = ? AND sponsor_email = ?",
            [$sponsorship['id'], $CFK_EMAIL]
        );

        if ($result) {
            $successCount++;
            logMessage("✓ Removed sponsorship #{$sponsorship['id']} for child {$sponsorship['display_id']}", 'SUCCESS');
        } else {
            $errorCount++;
            logMessage("✗ Failed to remove sponsorship #{$sponsorship['id']}", 'ERROR');
        }
    } catch (Exception $e) {
        $errorCount++;
        logMessage("✗ Exception removing sponsorship #{$sponsorship['id']}: " . $e->getMessage(), 'ERROR');
    }
}

// Summary
echo "\n";
echo colorOutput("╔══════════════════════════════════════════════════════════╗\n", 'cyan');
echo colorOutput("║                  ROLLBACK COMPLETE                       ║\n", 'cyan');
echo colorOutput("╚══════════════════════════════════════════════════════════╝\n", 'cyan');
echo "\n";

logMessage("Rollback completed");
logMessage("Total CFK sponsorships: {$count}");
logMessage("Successfully removed: {$successCount}");
logMessage("Errors: {$errorCount}");

echo colorOutput("SUMMARY:\n", 'bold');
echo "  Total CFK sponsorships: {$count}\n";
echo "  " . colorOutput("Successfully removed: {$successCount}\n", 'green');
if ($errorCount > 0) {
    echo "  " . colorOutput("Errors: {$errorCount}\n", 'red');
}
echo "\n";
echo "Log file: {$logFile}\n\n";

if ($successCount === $count && $errorCount === 0) {
    echo colorOutput("✓ All CFK sponsorships have been removed!\n", 'green');
    logMessage("Rollback completed successfully", 'SUCCESS');
} else {
    echo colorOutput("⚠️  Rollback completed with errors. Review log file.\n", 'yellow');
    logMessage("Rollback completed with errors", 'WARNING');
}

echo "\n";
exit($errorCount > 0 ? 1 : 0);
