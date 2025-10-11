<?php
/**
 * Individual Child Profile Page
 * Display detailed information about a specific child
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Get child ID
$childId = sanitizeInt($_GET['id'] ?? 0);
if (!$childId) {
    header('Location: ' . baseUrl('?page=children'));
    exit;
}

// Get child information
$child = getChildById($childId);
if (!$child) {
    setMessage('Child not found.', 'error');
    header('Location: ' . baseUrl('?page=children'));
    exit;
}

$pageTitle = 'Family ' . $child['display_id'] . ' - Child Profile';

// Get siblings
$siblings = getFamilyMembers($child['family_id'], $child['id']);

// Check if child is still available
$isAvailable = $child['status'] === 'available';
?>

<div class="child-profile-page">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="<?php echo baseUrl('?page=children'); ?>">All Children</a>
        <span>&raquo;</span>
        <span>Family <?php echo sanitizeString($child['display_id']); ?></span>
    </nav>

    <div class="child-profile">
        <div class="profile-header">
            <div class="profile-photo">
                <img src="<?php echo getPhotoUrl($child['photo_filename'], $child); ?>"
                     alt="Avatar for Family <?php echo sanitizeString($child['display_id']); ?>">
            </div>

            <div class="profile-basic-info">
                <h1>Family Code: <?php echo sanitizeString($child['display_id']); ?></h1>
                <p class="child-id">ID: <?php echo sanitizeString($child['display_id']); ?></p>
                
                <div class="basic-details">
                    <div class="detail-item">
                        <strong>Age:</strong> <?php echo formatAge($child['age']); ?>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Grade:</strong> <?php echo sanitizeString($child['grade'] ?: 'Not specified'); ?>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Age Group:</strong> <?php echo getAgeCategory($child['age']); ?>
                    </div>
                    
                    <?php if (!empty($child['school'])): ?>
                        <div class="detail-item">
                            <strong>School:</strong> <?php echo sanitizeString($child['school']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Status -->
                <div class="status-badge status-<?php echo $child['status']; ?>">
                    <?php 
                    global $childStatusOptions;
                    echo $childStatusOptions[$child['status']] ?? ucfirst($child['status']);
                    ?>
                </div>
            </div>
        </div>

        <?php if (!$isAvailable): ?>
            <div class="alert alert-warning">
                <h3>This child is no longer available for sponsorship</h3>
                <p>
                    <?php if ($child['status'] === 'sponsored'): ?>
                        This child has already found a sponsor! Thank you to everyone who showed interest.
                    <?php elseif ($child['status'] === 'pending'): ?>
                        This child's sponsorship is currently being processed by another family.
                    <?php else: ?>
                        This child is not currently available for sponsorship.
                    <?php endif; ?>
                </p>
                <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-primary">
                    View Other Children
                </a>
            </div>
        <?php endif; ?>

        <div class="profile-details">
            <!-- Clothing Sizes -->
            <?php if ($child['shirt_size'] || $child['pant_size'] || $child['shoe_size'] || $child['jacket_size']): ?>
                <section class="detail-section">
                    <h2>Clothing Sizes</h2>
                    <div class="size-grid">
                        <?php if ($child['shirt_size']): ?>
                            <div class="size-item">
                                <strong>Shirt:</strong> <?php echo sanitizeString($child['shirt_size']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($child['pant_size']): ?>
                            <div class="size-item">
                                <strong>Pants:</strong> <?php echo sanitizeString($child['pant_size']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($child['shoe_size']): ?>
                            <div class="size-item">
                                <strong>Shoes:</strong> <?php echo sanitizeString($child['shoe_size']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($child['jacket_size']): ?>
                            <div class="size-item">
                                <strong>Jacket:</strong> <?php echo sanitizeString($child['jacket_size']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Interests -->
            <?php if (!empty($child['interests'])): ?>
                <section class="detail-section">
                    <h2>Interests & Hobbies</h2>
                    <p class="interests-text"><?php echo nl2br(sanitizeString($child['interests'])); ?></p>
                </section>
            <?php endif; ?>

            <!-- Christmas Wishes -->
            <?php if (!empty($child['wishes'])): ?>
                <section class="detail-section">
                    <h2>Christmas Wishes</h2>
                    <p class="wishes-text"><?php echo nl2br(sanitizeString($child['wishes'])); ?></p>
                </section>
            <?php endif; ?>

            <!-- Special Needs -->
            <?php if (!empty($child['special_needs'])): ?>
                <section class="detail-section">
                    <h2>Special Considerations</h2>
                    <p class="special-needs-text"><?php echo nl2br(sanitizeString($child['special_needs'])); ?></p>
                </section>
            <?php endif; ?>

            <!-- Family Information -->
            <?php if (!empty($siblings) || !empty($child['family_notes'])): ?>
                <section class="detail-section">
                    <h2>Family Information</h2>
                    
                    <?php if (!empty($siblings)): ?>
                        <div class="siblings-section">
                            <h3>Siblings Also Needing Sponsorship</h3>
                            <div class="siblings-grid">
                                <?php foreach ($siblings as $sibling): ?>
                                    <div class="sibling-card">
                                        <div class="sibling-photo">
                                            <img src="<?php echo getPhotoUrl($sibling['photo_filename'], $sibling); ?>" 
                                                 alt="Avatar for <?php echo sanitizeString($sibling['name']); ?>">
                                        </div>
                                        <div class="sibling-info">
                                            <h4><?php echo sanitizeString($sibling['name']); ?></h4>
                                            <p>ID: <?php echo sanitizeString($sibling['display_id']); ?></p>
                                            <p><?php echo formatAge($sibling['age']); ?></p>
                                            <div class="status-badge status-<?php echo $sibling['status']; ?>">
                                                <?php echo $childStatusOptions[$sibling['status']] ?? ucfirst($sibling['status']); ?>
                                            </div>
                                            <a href="<?php echo baseUrl('?page=child&id=' . $sibling['id']); ?>" 
                                               class="btn btn-small btn-secondary">View Profile</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($child['family_notes'])): ?>
                        <div class="family-notes">
                            <h3>About the Family</h3>
                            <p><?php echo nl2br(sanitizeString($child['family_notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>

        <!-- Sponsorship Action -->
        <?php 
        require_once __DIR__ . '/../includes/sponsorship_manager.php';
        $availability = CFK_Sponsorship_Manager::isChildAvailable($child['id']);
        ?>
        <?php if ($availability['available']): ?>
            <div class="sponsorship-action">
                <div class="action-content">
                    <h2>Sponsor Child <?php echo sanitizeString($child['display_id']); ?></h2>
                    <p>Ready to make this child's Christmas special? 
                       Click below to begin the sponsorship process.</p>
                    
                    <a href="<?php echo baseUrl('?page=sponsor&child_id=' . $child['id']); ?>" 
                       class="btn btn-large btn-primary">
                        Sponsor This Child
                    </a>
                    
                    <?php if (!empty($siblings)):
                        // Count available siblings
                        $availableSiblings = array_filter($siblings, fn($s) => $s['status'] === 'available');
                        $availableCount = count($availableSiblings);
                    ?>
                        <p class="family-sponsor-note">
                            <strong>Consider sponsoring siblings together!</strong>
                            Keeping families connected during Christmas creates even more joy.
                        </p>

                        <?php if ($availableCount > 0): ?>
                            <a href="<?php echo baseUrl('?page=sponsor&family_id=' . $child['family_id']); ?>"
                               class="btn btn-large btn-success">
                                🎁 Sponsor Entire Family (<?php echo ($availableCount + 1); ?> children)
                            </a>
                        <?php endif; ?>

                        <a href="<?php echo baseUrl('?page=children&family_id=' . $child['family_id']); ?>"
                           class="btn btn-secondary">
                            View All Family Members
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="sponsorship-unavailable">
                <div class="alert alert-warning">
                    <h2>Sponsorship Status</h2>
                    <p><strong><?php echo sanitizeString($availability['reason']); ?></strong></p>
                    <p>This child is no longer available for new sponsorship requests.</p>
                    <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-primary">
                        Browse Other Children
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Other Ways to Help -->
        <div class="other-help">
            <h2>Other Ways to Help</h2>
            <p>Not ready to sponsor a specific child? You can still make a difference:</p>
            <div class="help-options">
                <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-secondary">
                    Browse Other Children
                </a>
                <a href="<?php echo baseUrl('?page=donate'); ?>" class="btn btn-success">
                    Make a General Donation
                </a>
            </div>
        </div>
    </div>
</div>

