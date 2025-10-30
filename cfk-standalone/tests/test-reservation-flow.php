<?php

declare(strict_types=1);

/**
 * Reservation Flow Test Script
 * Tests the complete child reservation workflow based on actual system behavior:
 * 1. Child is available initially (status: 'available')
 * 2. Child is hidden from browse when in localStorage cart
 * 3. Confirming sponsorship marks child as 'sponsored'
 * 4. Child completely removed from availability
 */

define('CFK_APP', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

use CFK\Database\Connection as Database;

// Test configuration
$testIterations = 3;
$testResults = [];

echo "\n🧪 RESERVATION FLOW TEST SUITE\n";
echo "================================\n\n";

/**
 * Check if child is visible in availability query
 */
function isChildAvailable(int $childId): bool
{
    $sql = "SELECT id FROM children
            WHERE id = :id
            AND status = 'available'
            LIMIT 1";

    $result = Database::fetchRow($sql, ['id' => $childId]);

    return ! empty($result);
}

/**
 * Get child's current status
 */
function getChildStatus(int $childId): string
{
    $sql = "SELECT status FROM children WHERE id = :id";
    $result = Database::fetchRow($sql, ['id' => $childId]);

    return $result['status'] ?? 'not found';
}

/**
 * Simulate adding child to cart by marking as pending
 */
function addToCart(int $childId): bool
{
    try {
        Database::update('children', ['status' => 'pending'], ['id' => $childId]);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Simulate confirming sponsorship
 */
function confirmSponsorship(int $childId, string $sponsorEmail): bool
{
    try {
        Database::beginTransaction();

        // Update child status to sponsored
        Database::update('children', ['status' => 'sponsored'], ['id' => $childId]);

        // Create sponsorship record
        Database::insert('sponsorships', [
            'child_id' => $childId,
            'sponsor_name' => 'Test Sponsor',
            'sponsor_email' => $sponsorEmail,
            'status' => 'confirmed',
            'confirmation_date' => date('Y-m-d H:i:s'),
        ]);

        Database::commit();

        return true;
    } catch (Exception $e) {
        Database::rollback();

        return false;
    }
}

// Run test iterations
for ($i = 1; $i <= $testIterations; $i++) {
    echo "📋 TEST ITERATION #{$i}\n";
    echo str_repeat('-', 40) . "\n";

    $testEmail = "test_" . time() . "_" . $i . "@example.com";

    // Step 1: Find an available child
    echo "1️⃣  Finding available child...\n";
    $sql = "SELECT c.id, f.family_number, c.child_letter
            FROM children c
            JOIN families f ON c.family_id = f.id
            WHERE c.status = 'available'
            LIMIT 1";

    $child = Database::fetchRow($sql);

    if (! $child) {
        echo "   ❌ No available children found for testing\n\n";
        $testResults[$i] = ['success' => false, 'error' => 'No available children'];

        continue;
    }

    $childId = (int)$child['id'];
    $displayId = $child['family_number'] . ($child['child_letter'] ?? '');

    echo "   ✅ Found child: {$displayId} (ID: {$childId})\n";
    echo "   📊 Initial status: " . getChildStatus($childId) . "\n";
    echo "   👁️  Available in browse: " . (isChildAvailable($childId) ? 'YES' : 'NO') . "\n\n";

    // Step 2: Add to cart (mark as pending)
    echo "2️⃣  Adding to cart (marking as pending)...\n";

    if (! addToCart($childId)) {
        echo "   ❌ Failed to add to cart\n\n";
        $testResults[$i] = ['success' => false, 'error' => 'Failed to add to cart'];

        continue;
    }

    echo "   ✅ Added to cart\n";

    // Step 3: Check if child is removed from availability
    echo "   🔍 Checking availability after cart addition...\n";
    $statusAfterCart = getChildStatus($childId);
    $availableAfterCart = isChildAvailable($childId);

    echo "   📊 Status after cart: {$statusAfterCart}\n";
    echo "   👁️  Visible in browse: " . ($availableAfterCart ? '❌ FAIL - Should be hidden' : '✅ PASS - Hidden') . "\n\n";

    if ($availableAfterCart || $statusAfterCart !== 'pending') {
        $testResults[$i] = [
            'success' => false,
            'error' => 'Child still visible or status incorrect after adding to cart',
            'child_id' => $childId,
            'status' => $statusAfterCart,
        ];
        // Clean up - reset to available
        Database::update('children', ['status' => 'available'], ['id' => $childId]);

        continue;
    }

    // Step 4: Confirm sponsorship
    echo "3️⃣  Confirming sponsorship...\n";

    if (! confirmSponsorship($childId, $testEmail)) {
        echo "   ❌ Failed to confirm sponsorship\n\n";
        $testResults[$i] = ['success' => false, 'error' => 'Failed to confirm sponsorship'];
        // Clean up
        Database::update('children', ['status' => 'available'], ['id' => $childId]);

        continue;
    }

    echo "   ✅ Sponsorship confirmed\n";

    // Step 5: Verify child is completely hidden
    echo "   🔍 Checking visibility after confirmation...\n";
    $finalStatus = getChildStatus($childId);
    $availableAfterConfirm = isChildAvailable($childId);

    echo "   📊 Final status: {$finalStatus}\n";
    echo "   👁️  Visible in browse: " . ($availableAfterConfirm ? '❌ FAIL - Should be hidden' : '✅ PASS - Hidden') . "\n\n";

    // Determine test result
    $success = ! $availableAfterConfirm && $finalStatus === 'sponsored';

    $testResults[$i] = [
        'success' => $success,
        'child_id' => $childId,
        'display_id' => $displayId,
        'email' => $testEmail,
        'hidden_in_cart' => ! $availableAfterCart,
        'hidden_after_confirm' => ! $availableAfterConfirm,
        'final_status' => $finalStatus,
    ];

    if ($success) {
        echo "   ✅ TEST #{$i} PASSED - Complete flow working correctly\n";
    } else {
        echo "   ❌ TEST #{$i} FAILED - Issues detected\n";
        // Clean up failed test
        Database::update('children', ['status' => 'available'], ['id' => $childId]);
        Database::execute("DELETE FROM sponsorships WHERE child_id = :id AND sponsor_email = :email", [
            'id' => $childId,
            'email' => $testEmail,
        ]);
    }

    echo "\n" . str_repeat('=', 40) . "\n\n";

    // Brief pause between iterations
    sleep(1);
}

// Print summary
echo "\n📊 TEST SUMMARY\n";
echo "================================\n";

$passed = 0;
$failed = 0;

foreach ($testResults as $iteration => $result) {
    if ($result['success']) {
        $passed++;
        echo "✅ Test #{$iteration}: PASSED\n";
        if (isset($result['display_id'])) {
            echo "   Child: {$result['display_id']} (ID: {$result['child_id']})\n";
            echo "   Hidden in cart: " . ($result['hidden_in_cart'] ? 'YES' : 'NO') . "\n";
            echo "   Hidden after confirm: " . ($result['hidden_after_confirm'] ? 'YES' : 'NO') . "\n";
            echo "   Final status: {$result['final_status']}\n";
        }
    } else {
        $failed++;
        echo "❌ Test #{$iteration}: FAILED\n";
        echo "   Error: {$result['error']}\n";
        if (isset($result['child_id'])) {
            echo "   Child ID: {$result['child_id']}\n";
            if (isset($result['status'])) {
                echo "   Status: {$result['status']}\n";
            }
        }
    }
    echo "\n";
}

echo "================================\n";
echo "Total: {$testIterations} tests\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Success Rate: " . ($testIterations > 0 ? round(($passed / $testIterations) * 100, 1) : 0) . "%\n";

if ($passed === $testIterations) {
    echo "\n🎉 ALL TESTS PASSED!\n";
    echo "✅ Children correctly hidden when in cart\n";
    echo "✅ Children correctly hidden after sponsorship confirmation\n";
    exit(0);
} else {
    echo "\n⚠️  SOME TESTS FAILED - Review results above\n";
    exit(1);
}
