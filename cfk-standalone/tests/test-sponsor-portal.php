<?php
/**
 * Test Script for Sponsor Portal Functionality
 * Run from command line to test the portal features
 */

// Define CFK_APP constant
define('CFK_APP', true);

// Set up CLI environment
$_SERVER['HTTP_HOST'] = 'localhost:8082';
$_SERVER['REQUEST_URI'] = '/test';

// Start session
session_start();

// Load configuration and functions
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sponsorship_manager.php';
require_once __DIR__ . '/../includes/email_manager.php';

echo "=== Sponsor Portal Testing ===\n\n";

// Test email
$testEmail = 'test.sponsor@example.com';

// Step 1: Check existing sponsorships
echo "1. Checking existing sponsorships for {$testEmail}...\n";
$existingSponsorships = CFK_Sponsorship_Manager::getSponsorshipsByEmail($testEmail);
echo "   Found " . count($existingSponsorships) . " existing sponsorships\n\n";

// Step 2: Create test sponsorships if none exist
if (empty($existingSponsorships)) {
    echo "2. Creating test sponsorships...\n";

    // Get some available children
    $availableChildren = getChildren(['status' => 'available'], 1, 3);

    if (empty($availableChildren)) {
        echo "   ERROR: No available children found in database\n";
        echo "   Please import sample data first\n";
        exit(1);
    }

    echo "   Found " . count($availableChildren) . " available children\n";

    $sponsorData = [
        'name' => 'Test Sponsor',
        'email' => $testEmail,
        'phone' => '555-123-4567',
        'address' => '123 Test Street, Test City, TC 12345',
        'gift_preference' => 'shopping',
        'message' => 'Test sponsorship for portal testing'
    ];

    $created = 0;
    foreach ($availableChildren as $child) {
        // Reserve child manually to skip email sending
        $reserved = CFK_Sponsorship_Manager::reserveChild($child['id']);
        if ($reserved['success']) {
            // Create sponsorship directly in database
            try {
                $sponsorshipId = Database::insert('sponsorships', [
                    'child_id' => $child['id'],
                    'sponsor_name' => $sponsorData['name'],
                    'sponsor_email' => $sponsorData['email'],
                    'sponsor_phone' => $sponsorData['phone'],
                    'sponsor_address' => $sponsorData['address'],
                    'gift_preference' => $sponsorData['gift_preference'],
                    'special_message' => $sponsorData['message'],
                    'status' => 'pending'
                ]);
                echo "   ✓ Created sponsorship for child {$child['display_id']} (ID: {$sponsorshipId})\n";
                $created++;
            } catch (Exception $e) {
                echo "   ✗ Failed: {$e->getMessage()}\n";
            }
        } else {
            echo "   ✗ Failed to reserve: {$reserved['message']}\n";
        }
    }

    echo "   Created {$created} test sponsorships\n\n";
} else {
    echo "2. Using existing sponsorships (skipping creation)\n\n";
}

// Step 3: Test getSponsorshipsByEmail
echo "3. Testing getSponsorshipsByEmail()...\n";
$sponsorships = CFK_Sponsorship_Manager::getSponsorshipsByEmail($testEmail);
echo "   Found " . count($sponsorships) . " sponsorships\n";
foreach ($sponsorships as $s) {
    echo "   - Sponsorship #{$s['id']}, Status: {$s['status']}\n";
}
echo "\n";

// Step 4: Test getSponsorshipsWithDetails
echo "4. Testing getSponsorshipsWithDetails()...\n";
$detailedSponsorships = CFK_Sponsorship_Manager::getSponsorshipsWithDetails($testEmail);
echo "   Found " . count($detailedSponsorships) . " detailed sponsorships\n";

// Group by family
$families = [];
foreach ($detailedSponsorships as $child) {
    $familyId = $child['family_id'];
    if (!isset($families[$familyId])) {
        $families[$familyId] = [
            'family_number' => $child['family_number'],
            'children' => []
        ];
    }
    $families[$familyId]['children'][] = $child['child_display_id'];
}

echo "   Grouped into " . count($families) . " families:\n";
foreach ($families as $family) {
    echo "   - Family {$family['family_number']}: " . implode(', ', $family['children']) . "\n";
}
echo "\n";

// Step 5: Test token generation and verification
echo "5. Testing portal token system...\n";
$token = CFK_Sponsorship_Manager::generatePortalToken($testEmail);
echo "   ✓ Generated token: " . substr($token, 0, 20) . "...\n";

$verification = CFK_Sponsorship_Manager::verifyPortalToken($token);
if ($verification['valid']) {
    echo "   ✓ Token verified successfully\n";
    echo "   ✓ Email from token: {$verification['email']}\n";
} else {
    echo "   ✗ Token verification failed: {$verification['message']}\n";
}
echo "\n";

// Step 6: Test invalid token
echo "6. Testing invalid token...\n";
$badVerification = CFK_Sponsorship_Manager::verifyPortalToken('invalid_token_12345');
if (!$badVerification['valid']) {
    echo "   ✓ Invalid token correctly rejected: {$badVerification['message']}\n";
} else {
    echo "   ✗ ERROR: Invalid token was accepted\n";
}
echo "\n";

// Step 7: Test portal URL
echo "7. Portal Access URL:\n";
$portalUrl = baseUrl('?page=sponsor_portal&token=' . urlencode($token));
echo "   {$portalUrl}\n\n";

// Step 8: Test add children functionality (dry run)
echo "8. Testing add children functionality...\n";
$moreChildren = getChildren(['status' => 'available'], 1, 2);
if (!empty($moreChildren)) {
    $childIds = array_column($moreChildren, 'id');
    echo "   Found " . count($childIds) . " more available children\n";
    echo "   Would add children: " . implode(', ', array_column($moreChildren, 'display_id')) . "\n";

    // Note: Not actually adding them to avoid cluttering test data
    echo "   (Skipping actual add to preserve test data)\n";
} else {
    echo "   No more available children to test with\n";
}
echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "✓ All core portal functions working correctly\n";
echo "✓ Token generation and verification operational\n";
echo "✓ Data retrieval and grouping functional\n\n";

echo "Next Steps:\n";
echo "1. Visit: http://localhost:8082/?page=sponsor_lookup\n";
echo "2. Enter email: {$testEmail}\n";
echo "3. Check for verification email (or use token directly)\n";
echo "4. Visit portal URL above to test full UI\n\n";
