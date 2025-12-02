<?php

/**
 * Sponsor Remaining Children Script
 *
 * PURPOSE: At the end of sponsorship period, automatically sponsor all
 *          unsponsored children to Christmas for Kids organization.
 *
 * USAGE:
 *   - Dry run (preview): php sponsor-remaining-children.php --dry-run
 *   - Execute:          php sponsor-remaining-children.php --execute
 *   - Force execute:    php sponsor-remaining-children.php --execute --force
 *
 * SAFETY:
 *   - Requires --execute flag to make changes
 *   - --dry-run shows what WOULD happen without making changes
 *   - Confirms before proceeding (unless --force flag used)
 *   - Logs all operations to file
 *   - Can be reversed with rollback script
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
// IMPORTANT: Uses separate email to distinguish from genuine CFK sponsorships
// This allows rollback to ONLY affect auto-sponsored children, not real CFK sponsorships
$CFK_SPONSOR = [
    'name' => 'C-F-K Auto-Sponsor',
    'email' => 'end-of-season@christmasforkids.org',
    'phone' => '', // Optional
    'address' => '', // Optional
    'city' => '', // Optional
    'state' => '', // Optional
    'zip' => '' // Optional
];

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
    echo colorOutput("\nChristmas for Kids - Sponsor Remaining Children Script\n", 'bold');
    echo colorOutput("=" . str_repeat("=", 55) . "\n", 'cyan');
    echo "\nPURPOSE:\n";
    echo "  Automatically sponsor all unsponsored children to Christmas for Kids\n";
    echo "  at the end of the sponsorship period.\n\n";
    echo "USAGE:\n";
    echo "  " . colorOutput("Dry Run (Preview):", 'yellow') . "\n";
    echo "    php sponsor-remaining-children.php --dry-run\n\n";
    echo "  " . colorOutput("Execute:", 'green') . "\n";
    echo "    php sponsor-remaining-children.php --execute\n\n";
    echo "  " . colorOutput("Force Execute (No Confirmation):", 'red') . "\n";
    echo "    php sponsor-remaining-children.php --execute --force\n\n";
    echo "SAFETY FEATURES:\n";
    echo "  • Requires --execute flag to make changes\n";
    echo "  • --dry-run shows preview without changes\n";
    echo "  • Asks for confirmation before proceeding\n";
    echo "  • Logs all operations to file\n";
    echo "  • Can be reversed if needed\n\n";
    exit(0);
}

// Setup logging
$logFile = __DIR__ . '/../logs/sponsor-remaining-' . date('Y-m-d-His') . '.log';
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

function logMessage(string $message, string $level = 'INFO'): void
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] [{$level}] {$message}\n";
    file_put_contents($logFile, $logLine, FILE_APPEND);

    // Also output to console
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
echo colorOutput("║  Christmas for Kids - Sponsor Remaining Children        ║\n", 'cyan');
echo colorOutput("╚══════════════════════════════════════════════════════════╝\n", 'cyan');
echo "\n";

logMessage("Script started");
logMessage("Mode: " . ($isDryRun ? "DRY RUN (preview only)" : "EXECUTE (will make changes)"));
logMessage("CFK Sponsor Email: {$CFK_SPONSOR['email']}");

// Step 1: Find all unsponsored children
logMessage("Step 1: Finding unsponsored children...");

try {
    $unsponsored = Database::fetchAll(
        "SELECT c.id, c.family_id, CONCAT(f.family_number, c.child_letter) as display_id,
                c.age_months, c.gender, c.grade,
                f.family_number
         FROM children c
         JOIN families f ON c.family_id = f.id
         WHERE c.id NOT IN (
             SELECT child_id FROM sponsorships
             WHERE status IN ('confirmed', 'logged')
         )
         ORDER BY f.family_number, c.child_letter"
    );
} catch (Exception $e) {
    logMessage("ERROR: Failed to query database: " . $e->getMessage(), 'ERROR');
    exit(1);
}

$count = count($unsponsored);
logMessage("Found {$count} unsponsored children");

if ($count === 0) {
    echo "\n";
    logMessage("✓ All children are already sponsored! No action needed.", 'SUCCESS');
    echo "\n";
    exit(0);
}

// Step 2: Display summary
echo "\n";
echo colorOutput("UNSPONSORED CHILDREN SUMMARY\n", 'bold');
echo colorOutput(str_repeat("-", 60) . "\n", 'cyan');
printf("%-10s %-8s %-10s %-10s\n", "Child ID", "Age", "Gender", "Grade");
echo colorOutput(str_repeat("-", 60) . "\n", 'cyan');

foreach ($unsponsored as $child) {
    $age = displayAge($child['age_months']);
    $gender = $child['gender'] === 'M' ? 'Boy' : 'Girl';
    $grade = $child['grade'] ? "Grade {$child['grade']}" : 'N/A';
    printf("%-10s %-8s %-10s %-10s\n", $child['display_id'], $age, $gender, $grade);
}

echo colorOutput(str_repeat("-", 60) . "\n", 'cyan');
echo "\n";

// Step 3: Confirmation (if not dry-run and not forced)
if ($isExecute && !$isForce) {
    echo colorOutput("⚠️  WARNING: This will create {$count} sponsorship records!\n", 'yellow');
    echo colorOutput("Sponsor: {$CFK_SPONSOR['name']} ({$CFK_SPONSOR['email']})\n\n", 'yellow');
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
        logMessage("Operation cancelled by user", 'WARNING');
        echo "\n" . colorOutput("Operation cancelled.\n\n", 'yellow');
        exit(0);
    }
    echo "\n";
}

// Step 4: Create sponsorships
if ($isDryRun) {
    echo colorOutput("DRY RUN MODE - No changes will be made\n", 'yellow');
    echo colorOutput("The following would be created:\n\n", 'yellow');

    foreach ($unsponsored as $child) {
        echo "  • Sponsorship for child {$child['display_id']} → CFK\n";
    }

    echo "\n";
    logMessage("Dry run completed. No changes made.", 'INFO');
    echo colorOutput("To execute for real, run with --execute flag\n\n", 'cyan');
    exit(0);
}

// EXECUTE MODE - Create actual sponsorships
logMessage("Step 2: Creating sponsorship records...");

$successCount = 0;
$errorCount = 0;
$errors = [];

foreach ($unsponsored as $child) {
    try {
        // Create sponsorship record
        $result = Database::execute(
            "INSERT INTO sponsorships (
                child_id,
                sponsor_name,
                sponsor_email,
                sponsor_phone,
                sponsor_address,
                sponsor_city,
                sponsor_state,
                sponsor_zip,
                confirmation_date,
                status,
                notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'confirmed', ?)",
            [
                $child['id'],
                $CFK_SPONSOR['name'],
                $CFK_SPONSOR['email'],
                $CFK_SPONSOR['phone'],
                $CFK_SPONSOR['address'],
                $CFK_SPONSOR['city'],
                $CFK_SPONSOR['state'],
                $CFK_SPONSOR['zip'],
                'Auto-sponsored by CFK - End of season unsponsored child'
            ]
        );

        if ($result) {
            $successCount++;
            logMessage("✓ Created sponsorship for child {$child['display_id']}", 'SUCCESS');
        } else {
            $errorCount++;
            $error = "Failed to create sponsorship for child {$child['display_id']}";
            $errors[] = $error;
            logMessage("✗ {$error}", 'ERROR');
        }
    } catch (Exception $e) {
        $errorCount++;
        $error = "Exception for child {$child['display_id']}: " . $e->getMessage();
        $errors[] = $error;
        logMessage("✗ {$error}", 'ERROR');
    }
}

// Step 5: Summary Report
echo "\n";
echo colorOutput("╔══════════════════════════════════════════════════════════╗\n", 'cyan');
echo colorOutput("║                    OPERATION COMPLETE                    ║\n", 'cyan');
echo colorOutput("╚══════════════════════════════════════════════════════════╝\n", 'cyan');
echo "\n";

logMessage("Operation completed");
logMessage("Total unsponsored found: {$count}");
logMessage("Successfully created: {$successCount}");
logMessage("Errors: {$errorCount}");

echo colorOutput("SUMMARY:\n", 'bold');
echo "  Total unsponsored children: {$count}\n";
echo "  " . colorOutput("Successfully sponsored: {$successCount}\n", 'green');
if ($errorCount > 0) {
    echo "  " . colorOutput("Errors: {$errorCount}\n", 'red');
}
echo "\n";

if ($errorCount > 0) {
    echo colorOutput("ERRORS:\n", 'red');
    foreach ($errors as $error) {
        echo "  • {$error}\n";
    }
    echo "\n";
}

echo "Log file: {$logFile}\n\n";

// Step 6: Verification Query
logMessage("Step 3: Verification check...");

try {
    $remaining = Database::fetchRow(
        "SELECT COUNT(*) as count FROM children c
         WHERE c.id NOT IN (
             SELECT child_id FROM sponsorships
             WHERE status IN ('confirmed', 'logged')
         )"
    );

    $remainingCount = $remaining['count'] ?? 0;

    if ($remainingCount === 0) {
        echo colorOutput("✓ VERIFICATION PASSED: All children are now sponsored!\n", 'green');
        logMessage("✓ Verification: All children sponsored", 'SUCCESS');
    } else {
        echo colorOutput("⚠️  VERIFICATION WARNING: {$remainingCount} children still unsponsored\n", 'yellow');
        logMessage("⚠️  Verification: {$remainingCount} children still unsponsored", 'WARNING');
    }
} catch (Exception $e) {
    echo colorOutput("⚠️  Could not verify: " . $e->getMessage() . "\n", 'yellow');
    logMessage("Could not verify: " . $e->getMessage(), 'WARNING');
}

echo "\n";

// Final success message
if ($successCount === $count && $errorCount === 0) {
    echo colorOutput("🎄 All remaining children have been sponsored by Christmas for Kids! 🎄\n", 'green');
    logMessage("Script completed successfully", 'SUCCESS');
} else {
    echo colorOutput("⚠️  Script completed with some errors. Review log file for details.\n", 'yellow');
    logMessage("Script completed with errors", 'WARNING');
}

echo "\n";
exit($errorCount > 0 ? 1 : 0);
