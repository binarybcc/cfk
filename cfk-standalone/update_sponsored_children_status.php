<?php
/**
 * One-time script to update children status for existing auto-sponsorships
 * Run this once to fix the 203 children that were sponsored but status wasn't updated
 */

define('CFK_APP', true);
require_once __DIR__ . '/config/config.php';

use CFK\Database\Connection as Database;

try {
    // Find children with sponsorships but still marked as 'available'
    $children = Database::fetchAll(
        "SELECT c.id, CONCAT(f.family_number, c.child_letter) as display_id
         FROM children c
         JOIN families f ON c.family_id = f.id
         WHERE c.status = 'available'
         AND c.id IN (
             SELECT child_id FROM sponsorships
             WHERE status IN ('confirmed', 'logged')
         )
         ORDER BY f.family_number, c.child_letter"
    );

    $count = count($children);

    if ($count === 0) {
        echo "✅ All sponsored children already have correct status!\n";
        exit(0);
    }

    echo "Found {$count} children with sponsorships but status='available'\n";
    echo "Updating their status to 'sponsored'...\n\n";

    $successCount = 0;
    foreach ($children as $child) {
        $result = Database::execute(
            "UPDATE children SET status = 'sponsored' WHERE id = ?",
            [$child['id']]
        );

        if ($result) {
            $successCount++;
            echo "  ✅ Updated child {$child['display_id']}\n";
        } else {
            echo "  ❌ Failed to update child {$child['display_id']}\n";
        }
    }

    echo "\n✅ Updated {$successCount} of {$count} children to status='sponsored'\n";
    echo "\nYou can now delete this script: update_sponsored_children_status.php\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
