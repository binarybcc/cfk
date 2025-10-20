<?php
/**
 * Sponsorship Request Form
 * Handles child sponsorship requests with single-sponsor logic
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

require_once __DIR__ . '/../includes/sponsorship_manager.php';
require_once __DIR__ . '/../includes/email_manager.php';

// Get child ID or family ID
$childId = sanitizeInt($_GET['child_id'] ?? 0);
$familyId = sanitizeInt($_GET['family_id'] ?? 0);
$isFamilySponsorship = ($familyId > 0);

$childrenToSponsor = [];

if ($isFamilySponsorship) {
    // Family sponsorship - get all available children in family
    $allFamilyMembers = getFamilyMembers($familyId);
    $childrenToSponsor = array_filter($allFamilyMembers, fn($c): bool => $c['status'] === 'available');

    if ($childrenToSponsor === []) {
        setMessage('No available children in this family to sponsor.', 'error');
        header('Location: ' . baseUrl('?page=children'));
        exit;
    }

    $child = $childrenToSponsor[0]; // Use first child for display
    $pageTitle = 'Sponsor Family ' . $child['family_number'];
} else {
    // Individual child sponsorship
    if (!$childId) {
        setMessage('Please select a child to sponsor.', 'error');
        header('Location: ' . baseUrl('?page=children'));
        exit;
    }

    // Check if child is available
    $availability = CFK_Sponsorship_Manager::isChildAvailable($childId);
    $child = $availability['child'];

    if (!$child) {
        setMessage('Child not found.', 'error');
        header('Location: ' . baseUrl('?page=children'));
        exit;
    }

    $childrenToSponsor = [$child];
    $pageTitle = 'Sponsor ' . $child['display_id'];
}
$errors = [];
$formData = [];

// Handle form submission
if ($_POST && isset($_POST['submit_sponsorship'])) {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        $formData = [
            'name' => $_POST['sponsor_name'] ?? '',
            'email' => $_POST['sponsor_email'] ?? '',
            'phone' => $_POST['sponsor_phone'] ?? '',
            'address' => $_POST['sponsor_address'] ?? '',
            'gift_preference' => $_POST['gift_preference'] ?? 'shopping',
            'message' => $_POST['special_message'] ?? ''
        ];
        
        // Attempt to create sponsorship(s)
        if ($isFamilySponsorship) {
            // Create sponsorships for all children in family
            $allSuccess = true;
            $sponsoredChildren = [];

            foreach ($childrenToSponsor as $childToSponsor) {
                $result = CFK_Sponsorship_Manager::createSponsorshipRequest($childToSponsor['id'], $formData);

                if ($result['success']) {
                    $sponsoredChildren[] = $childToSponsor['display_id'];
                } else {
                    $errors[] = "Error sponsoring {$childToSponsor['display_id']}: {$result['message']}";
                    $allSuccess = false;
                }
            }

            if ($allSuccess) {
                $childList = implode(', ', $sponsoredChildren);
                setMessage("Successfully submitted sponsorship for family members: {$childList}!", 'success');
                header('Location: ' . baseUrl('?page=children'));
                exit;
            }
        } else {
            // Single child sponsorship
            $result = CFK_Sponsorship_Manager::createSponsorshipRequest($childId, $formData);

            if ($result['success']) {
                setMessage($result['message'], 'success');
                header('Location: ' . baseUrl('?page=children'));
                exit;
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

// Check availability
if ($isFamilySponsorship) {
    $isAvailable = $childrenToSponsor !== [];
    $unavailableReason = $isAvailable ? '' : 'All family members are already sponsored or unavailable.';
} else {
    $availability = CFK_Sponsorship_Manager::isChildAvailable($childId);
    $isAvailable = $availability['available'];
    $unavailableReason = $availability['reason'] ?? '';
}

// Get full child details for display
if (!$isFamilySponsorship) {
    $fullChild = getChildById($childId);
    $siblings = getFamilyMembers($fullChild['family_id'], $childId);
} else {
    $fullChild = $child; // Use first child for display purposes
    $siblings = [];
}
?>

<div class="sponsor-page">
    <div class="page-header">
        <?php if ($isFamilySponsorship): ?>
            <h1>🎁 Sponsor Family <?php echo sanitizeString($child['family_number']); ?></h1>
            <p class="family-sponsor-subtitle">
                You're sponsoring <?php echo count($childrenToSponsor); ?> children together!
            </p>
        <?php else: ?>
            <h1>Sponsor Child <?php echo sanitizeString($child['display_id']); ?></h1>
        <?php endif; ?>

        <nav class="breadcrumb">
            <a href="<?php echo baseUrl('?page=children'); ?>">All Children</a>
            <span>&raquo;</span>
            <?php if (!$isFamilySponsorship): ?>
                <a href="<?php echo baseUrl('?page=child&id=' . $childId); ?>">View Profile</a>
                <span>&raquo;</span>
            <?php endif; ?>
            <span>Sponsor</span>
        </nav>
    </div>

    <?php if (!$isAvailable): ?>
        <!-- Child Not Available -->
        <div class="unavailable-notice">
            <div class="alert alert-warning">
                <h2>🚫 Sponsorship Not Available</h2>
                <p><strong><?php echo sanitizeString($unavailableReason); ?></strong></p>
                <p>This child is no longer available for new sponsorship requests. This happens when:</p>
                <ul>
                    <li>Another sponsor has already selected this child</li>
                    <li>The child's sponsorship is being processed</li>
                    <li>The child has already been sponsored</li>
                </ul>
                
                <div class="alternative-actions">
                    <h3>What you can do instead:</h3>
                    <div class="action-buttons">
                        <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-primary">
                            Browse Other Children
                        </a>
                        <?php if ($siblings !== []): ?>
                            <a href="<?php echo baseUrl('?page=children&family_id=' . $fullChild['family_id']); ?>" class="btn btn-secondary">
                                View This Child's Family
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo baseUrl('?page=donate'); ?>" class="btn btn-success">
                            Make a General Donation
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Sponsorship Form -->
        <div class="sponsor-form-container">
            <?php if ($isFamilySponsorship): ?>
                <!-- Family Children Summary -->
                <div class="family-children-summary">
                    <h3>You are sponsoring these family members:</h3>
                    <div class="family-children-grid">
                        <?php foreach ($childrenToSponsor as $childToSponsor): ?>
                            <div class="family-child-card">
                                <div class="family-child-photo">
                                    <img src="<?php echo getPhotoUrl($childToSponsor['photo_filename'], $childToSponsor); ?>"
                                         alt="Avatar for <?php echo sanitizeString($childToSponsor['display_id']); ?>">
                                </div>
                                <div class="family-child-info">
                                    <h4><?php echo sanitizeString($childToSponsor['display_id']); ?></h4>
                                    <p><?php echo sanitizeString($childToSponsor['name']); ?></p>
                                    <p><?php echo formatAge($childToSponsor['age']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Single Child Summary -->
                <div class="child-summary">
                    <div class="child-photo">
                        <img src="<?php echo getPhotoUrl($fullChild['photo_filename'], $fullChild); ?>"
                             alt="Avatar for child <?php echo sanitizeString($fullChild['display_id']); ?>">
                    </div>
                    <div class="child-info">
                        <h3>Child <?php echo sanitizeString($fullChild['display_id']); ?></h3>
                        <p><strong>Age:</strong> <?php echo formatAge($fullChild['age']); ?></p>
                        <?php if (!empty($fullChild['grade'])): ?>
                            <p><strong>Grade:</strong> <?php echo sanitizeString($fullChild['grade']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($fullChild['interests'])): ?>
                            <div class="summary-section">
                                <strong>Interests:</strong>
                                <p><?php echo sanitizeString($fullChild['interests']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($fullChild['wishes'])): ?>
                            <div class="summary-section">
                                <strong>Christmas Wishes:</strong>
                                <p><?php echo sanitizeString($fullChild['wishes']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Sponsorship Form -->
            <form method="POST" action="" class="sponsorship-form" id="sponsorshipForm" data-child-id="<?php echo sanitizeString($child['display_id'] ?? ''); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <h2>Your Sponsorship Information</h2>
                
                <?php if ($errors !== []): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo sanitizeString($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="sponsor_name" class="form-label">Your Name *</label>
                        <input type="text" 
                               id="sponsor_name" 
                               name="sponsor_name" 
                               class="form-input" 
                               value="<?php echo sanitizeString($formData['name'] ?? ''); ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sponsor_email" class="form-label">Email Address *</label>
                        <input type="email" 
                               id="sponsor_email" 
                               name="sponsor_email" 
                               class="form-input" 
                               value="<?php echo sanitizeString($formData['email'] ?? ''); ?>"
                               required>
                        <div class="form-help">We'll use this to send you confirmation and updates</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="sponsor_phone" class="form-label">Phone Number (optional)</label>
                        <input type="tel" 
                               id="sponsor_phone" 
                               name="sponsor_phone" 
                               class="form-input" 
                               value="<?php echo sanitizeString($formData['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="sponsor_address" class="form-label">Mailing Address (optional)</label>
                    <textarea id="sponsor_address" 
                              name="sponsor_address" 
                              class="form-textarea" 
                              rows="3"><?php echo sanitizeString($formData['address'] ?? ''); ?></textarea>
                    <div class="form-help">We may use this for gift coordination if needed</div>
                </div>

                <div class="form-group">
                    <label for="gift_preference" class="form-label">Gift Preference</label>
                    <select id="gift_preference" name="gift_preference" class="form-select">
                        <option value="shopping" <?php echo ($formData['gift_preference'] ?? 'shopping') === 'shopping' ? 'selected' : ''; ?>>
                            I'll shop for specific gifts
                        </option>
                        <option value="gift_card" <?php echo ($formData['gift_preference'] ?? '') === 'gift_card' ? 'selected' : ''; ?>>
                            I'll provide gift cards
                        </option>
                        <option value="cash_donation" <?php echo ($formData['gift_preference'] ?? '') === 'cash_donation' ? 'selected' : ''; ?>>
                            I'll make a cash donation for gifts
                        </option>
                    </select>
                    <div class="form-help">Choose how you'd like to provide gifts for this child</div>
                </div>

                <div class="form-group">
                    <label for="special_message" class="form-label">Special Message (optional)</label>
                    <textarea id="special_message" 
                              name="special_message" 
                              class="form-textarea" 
                              rows="4"><?php echo sanitizeString($formData['message'] ?? ''); ?></textarea>
                    <div class="form-help">Any special notes or questions about the sponsorship</div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_sponsorship" class="btn btn-large btn-primary">
                        Submit Sponsorship Request
                    </button>
                    <a href="<?php echo baseUrl('?page=child&id=' . $childId); ?>" class="btn btn-large btn-secondary">
                        Back to Child Profile
                    </a>
                </div>
            </form>

            <!-- Important Notes -->
            <div class="important-notes">
                <h3>Important Information:</h3>
                <ul>
                    <li><strong>Confirmation Process:</strong> We'll review your request and confirm within 24 hours</li>
                    <li><strong>Gift Coordination:</strong> We'll provide specific gift suggestions and coordination details</li>
                    <li><strong>Delivery:</strong> Gifts are typically delivered 1-2 weeks before Christmas</li>
                    <li><strong>One Sponsor per Child:</strong> Each child can only have one sponsor to ensure fairness</li>
                    <li><strong>Questions?</strong> Contact us at <a href="mailto:<?php echo config('admin_email'); ?>"><?php echo config('admin_email'); ?></a></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>


