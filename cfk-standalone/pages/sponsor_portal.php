<?php
/**
 * Sponsor Portal - View and Manage Sponsorships
 * Displays all sponsorships for a verified email address
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

require_once __DIR__ . '/../includes/sponsorship_manager.php';
require_once __DIR__ . '/../includes/email_manager.php';

// Check for verification token
$token = $_GET['token'] ?? '';
$verificationResult = CFK_Sponsorship_Manager::verifyPortalToken($token);

if (!$verificationResult['valid']) {
    // Token invalid or expired
    ?>
    <div class="portal-page">
        <div class="alert alert-error">
            <h2>⚠️ Access Denied</h2>
            <p><strong><?php echo sanitizeString($verificationResult['message']); ?></strong></p>
            <p>Please request a new access link from the <a href="<?php echo baseUrl('?page=sponsor_lookup'); ?>">sponsor lookup page</a>.</p>
        </div>
    </div>
    <?php
    return;
}

$sponsorEmail = $verificationResult['email'];

// Get all sponsorships for this email
$sponsorships = CFK_Sponsorship_Manager::getSponsorshipsWithDetails($sponsorEmail);

// Group sponsorships by family
$families = [];
foreach ($sponsorships as $sponsorship) {
    $familyId = $sponsorship['family_id'];
    if (!isset($families[$familyId])) {
        $families[$familyId] = [
            'family_number' => $sponsorship['family_number'],
            'children' => []
        ];
    }
    $families[$familyId]['children'][] = $sponsorship;
}

// Check if adding children (GET parameter)
$showAddChildren = isset($_GET['add_children']) && $_GET['add_children'] === '1';

// Handle adding children to sponsorship
$addChildrenResult = null;
if ($_POST && isset($_POST['add_children'])) {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $addChildrenResult = ['success' => false, 'message' => 'Security token invalid. Please try again.'];
    } else {
        $selectedChildIds = $_POST['child_ids'] ?? [];

        if (empty($selectedChildIds)) {
            $addChildrenResult = ['success' => false, 'message' => 'Please select at least one child.'];
        } else {
            // Create sponsorship data from first existing sponsorship
            $firstSponsorship = $sponsorships[0];
            $sponsorData = [
                'name' => $firstSponsorship['sponsor_name'],
                'email' => $firstSponsorship['sponsor_email'],
                'phone' => $firstSponsorship['sponsor_phone'] ?? '',
                'address' => $firstSponsorship['sponsor_address'] ?? '',
                'gift_preference' => $firstSponsorship['gift_preference'],
                'message' => 'Additional children added to existing sponsorship'
            ];

            $addChildrenResult = CFK_Sponsorship_Manager::addChildrenToSponsorship($selectedChildIds, $sponsorData, $sponsorEmail);

            if ($addChildrenResult['success']) {
                // Refresh sponsorships
                header('Location: ' . baseUrl('?page=sponsor_portal&token=' . urlencode($token)));
                exit;
            }
        }
    }
}
?>

<div class="portal-page">
    <?php
    // Page header component
    $title = '🎄 Your Sponsorship Portal';
    ob_start();
    ?>
        <p class="sponsor-email">Logged in as: <strong><?php echo sanitizeEmail($sponsorEmail); ?></strong></p>
    <?php
    $additionalContent = ob_get_clean();
    require_once __DIR__ . '/../includes/components/page_header.php';
    ?>

    <?php if (!$showAddChildren): ?>
        <!-- Display Sponsorships -->
        <div class="sponsorships-container">
            <div class="portal-actions">
                <a href="<?php echo baseUrl('?page=sponsor_portal&token=' . urlencode($token) . '&add_children=1'); ?>" class="btn btn-primary">
                    ➕ Add More Children
                </a>
                <a href="<?php echo baseUrl('?page=sponsor_lookup'); ?>" class="btn btn-secondary">
                    ← Back to Lookup
                </a>
            </div>

            <div class="sponsorships-summary">
                <h2>Your Sponsored Children</h2>
                <p>You are sponsoring <strong><?php echo count($sponsorships); ?></strong> child(ren) across <strong><?php echo count($families); ?></strong> family(ies).</p>
            </div>

            <?php foreach ($families as $familyId => $family): ?>
                <div class="family-section">
                    <div class="family-header">
                        <h3>Family <?php echo sanitizeString($family['family_number']); ?></h3>
                    </div>

                    <div class="children-grid">
                        <?php foreach ($family['children'] as $child): ?>
                            <div class="child-card">
                                <div class="child-header">
                                    <h4>Child <?php echo sanitizeString($child['child_display_id']); ?></h4>
                                    <span class="status-badge status-<?php echo $child['status']; ?>">
                                        <?php echo ucfirst($child['status']); ?>
                                    </span>
                                </div>

                                <div class="child-photo">
                                    <img src="<?php echo getPhotoUrl($child['photo_filename'], $child); ?>"
                                         alt="Avatar for <?php echo sanitizeString($child['child_display_id']); ?>">
                                </div>

                                <div class="child-details">
                                    <p><strong>Name:</strong> <?php echo sanitizeString($child['child_name']); ?></p>
                                    <p><strong>Age:</strong> <?php echo sanitizeInt($child['child_age']); ?> years old</p>
                                    <p><strong>Grade:</strong> <?php echo sanitizeString($child['child_grade']); ?></p>
                                    <p><strong>Gender:</strong> <?php echo $child['child_gender'] === 'M' ? 'Boy' : 'Girl'; ?></p>
                                </div>

                                <div class="clothing-sizes">
                                    <h5>Clothing Sizes:</h5>
                                    <div class="sizes-grid">
                                        <span>👕 Shirt: <?php echo sanitizeString($child['shirt_size'] ?? 'N/A'); ?></span>
                                        <span>👖 Pants: <?php echo sanitizeString($child['pant_size'] ?? 'N/A'); ?></span>
                                        <span>👟 Shoes: <?php echo sanitizeString($child['shoe_size'] ?? 'N/A'); ?></span>
                                        <span>🧥 Jacket: <?php echo sanitizeString($child['jacket_size'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($child['interests'])): ?>
                                    <div class="child-section">
                                        <h5>Interests:</h5>
                                        <p><?php echo sanitizeString($child['interests']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($child['wishes'])): ?>
                                    <div class="child-section wishes">
                                        <h5>🎁 Christmas Wishes:</h5>
                                        <p><?php echo sanitizeString($child['wishes']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($child['special_needs'])): ?>
                                    <div class="child-section special-needs">
                                        <h5>⚠️ Special Notes:</h5>
                                        <p><?php echo sanitizeString($child['special_needs']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="child-meta">
                                    <small>Requested: <?php echo date('M j, Y', strtotime($child['request_date'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="contact-notice">
                <h3>Need to Make Changes?</h3>
                <p><strong>We're here to help!</strong> To cancel or modify a sponsorship, please contact Christmas for Kids:</p>
                <p>📧 Email: <a href="mailto:<?php echo config('admin_email'); ?>"><?php echo config('admin_email'); ?></a></p>
                <p>📞 Phone: <?php echo sanitizeString(config('contact_phone', 'Contact via email')); ?></p>
            </div>
        </div>

    <?php else: ?>
        <!-- Add Children Form -->
        <div class="add-children-container">
            <div class="form-header">
                <h2>Add More Children to Your Sponsorship</h2>
                <p>Select additional children you'd like to sponsor. They'll be added to your existing sponsorship.</p>
            </div>

            <?php if ($addChildrenResult && !$addChildrenResult['success']): ?>
                <div class="alert alert-error">
                    <?php echo sanitizeString($addChildrenResult['message']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="addChildrenForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <?php
                // Get available children
                $availableChildren = getChildren(['status' => 'available'], 1, 100);

                if (empty($availableChildren)): ?>
                    <div class="alert alert-info">
                        <p>There are currently no available children to add. All children have been sponsored!</p>
                    </div>
                <?php else: ?>
                    <div class="available-children-grid">
                        <?php foreach ($availableChildren as $child): ?>
                            <label class="child-checkbox-card">
                                <input type="checkbox" name="child_ids[]" value="<?php echo $child['id']; ?>">
                                <div class="checkbox-card-content">
                                    <div class="checkbox-child-photo">
                                        <img src="<?php echo getPhotoUrl($child['photo_filename'], $child); ?>"
                                             alt="Avatar for <?php echo sanitizeString($child['display_id']); ?>">
                                    </div>
                                    <div class="checkbox-child-info">
                                        <h4><?php echo sanitizeString($child['display_id']); ?></h4>
                                        <p><?php echo formatAge($child['age']); ?></p>
                                        <p><?php echo $child['gender'] === 'M' ? 'Boy' : 'Girl'; ?></p>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add_children" class="btn btn-large btn-primary">
                            Add Selected Children
                        </button>
                        <a href="<?php echo baseUrl('?page=sponsor_portal&token=' . urlencode($token)); ?>" class="btn btn-large btn-secondary">
                            Cancel
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</div>

