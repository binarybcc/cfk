<?php

/**
 * Family View Page
 * Displays all members of a family for sponsorship consideration
 * Replaces the modal approach with a dedicated page for better UX and accessibility
 */

// Prevent direct access
if (! defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Get family_number from URL (e.g., 201, 202, etc.)
$family_number = sanitizeString($_GET['family_number'] ?? '');

if (empty($family_number)) {
    setMessage('Invalid family number.', 'error');
    header('Location: ' . baseUrl('?page=children'));
    exit;
}

// Fetch family information using helper function
$family = getFamilyByNumber($family_number);

if (! $family) {
    setMessage('Family not found.', 'error');
    header('Location: ' . baseUrl('?page=children'));
    exit;
}

// Fetch all family members using helper function
$members = getFamilyMembersByNumber($family_number);

if ($members === []) {
    setMessage('No family members found.', 'error');
    header('Location: ' . baseUrl('?page=children'));
    exit;
}

// Members are already fetched, just assign to expected variable name
$family_members = $members;

// Count available members
$available_count = count(array_filter($family_members, fn ($member): bool => $member['status'] === 'available'));

$pageTitle = 'Family ' . sanitizeString($family['family_number']);

// CSP nonce is generated in config.php and available globally
global $cspNonce;
?>

<div class="family-page">
    <!-- Breadcrumb Navigation -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?php echo baseUrl('?page=children'); ?>"
           class="breadcrumb-link"
           aria-label="Go back to browse all children">
            ← Back to Children
        </a>
    </nav>

    <!-- Family Header -->
    <div class="family-header">
        <h1>Family <?php echo sanitizeString($family['family_number']); ?></h1>
        <div class="family-stats">
            <span class="stat-item">
                <strong><?php echo count($family_members); ?></strong> Total Members
            </span>
            <span class="stat-item">
                <strong><?php echo $available_count; ?></strong> Available to Sponsor
            </span>
        </div>

        <?php if ($available_count > 0) : ?>
            <button class="btn btn-large btn-primary btn-add-all-family"
                    aria-label="Sponsor all <?php echo $available_count; ?> available family member<?php echo $available_count > 1 ? 's' : ''; ?> from family <?php echo sanitizeString($family['family_number']); ?>">
                Sponsor All <?php echo $available_count; ?> Available Member<?php echo $available_count > 1 ? 's' : ''; ?>
            </button>
        <?php endif; ?>
    </div>

    <?php if (! empty($family['background_info'])) : ?>
        <div class="family-background">
            <h2>About the Family</h2>
            <p><?php echo nl2br(sanitizeString($family['background_info'])); ?></p>
        </div>
    <?php endif; ?>

    <!-- Family Members Grid -->
    <div class="family-members-grid">
        <?php foreach ($family_members as $member) : ?>
            <div class="family-member-card <?php echo $member['status'] !== 'available' ? 'member-sponsored' : ''; ?>">
                <!-- Card Header - Left Column -->
                <div class="member-card-header">
                    <div class="member-photo">
                        <img src="<?php echo getPhotoUrl($member['photo_filename'], $member); ?>"
                             alt="Avatar for Child <?php echo sanitizeString($member['display_id']); ?>">
                    </div>
                    <div class="member-title">
                        <h2><?php echo sanitizeString($member['display_id']); ?></h2>
                        <span class="status-badge status-<?php echo $member['status']; ?>">
                            <?php echo ucfirst((string) $member['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Detailed Info - Center Column -->
                <?php
                $demographics = displayAge($member['age_months']) . ' • ' .
                               ($member['gender'] === 'M' ? 'Boy' : 'Girl');
            if (! empty($member['grade'])) {
                $demographics .= ' • Grade: ' . sanitizeString($member['grade']);
            }
            ?>
                <div class="member-details" data-demographics="<?php echo $demographics; ?>">
                    <!-- Essential Needs -->
                    <?php if (! empty($member['interests'])) : ?>
                        <div class="detail-row">
                            <strong>Essential Needs:</strong>
                            <span><?php echo nl2br(sanitizeString($member['interests'])); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Christmas Wishes -->
                    <?php if (! empty($member['wishes'])) : ?>
                        <div class="detail-row">
                            <strong>Christmas Wishes:</strong>
                            <span><?php echo nl2br(sanitizeString(cleanWishesText($member['wishes']))); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Special Needs/Considerations -->
                    <?php if (! empty($member['special_needs'])) : ?>
                        <div class="detail-row special-needs">
                            <strong>⚠️ Special Considerations:</strong>
                            <span><?php echo nl2br(sanitizeString($member['special_needs'])); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Clothing Sizes - Individual fields -->
                    <?php if ($member['shirt_size'] || $member['pant_size'] || $member['shoe_size'] || $member['jacket_size']) : ?>
                        <div class="detail-section">
                            <strong>Clothing Sizes:</strong>
                            <div class="size-grid">
                                <?php if ($member['shirt_size']) : ?>
                                    <span class="size-item">Shirt: <?php echo sanitizeString($member['shirt_size']); ?></span>
                                <?php endif; ?>
                                <?php if ($member['pant_size']) : ?>
                                    <span class="size-item">Pants: <?php echo sanitizeString($member['pant_size']); ?></span>
                                <?php endif; ?>
                                <?php if ($member['shoe_size']) : ?>
                                    <span class="size-item">Shoes: <?php echo sanitizeString($member['shoe_size']); ?></span>
                                <?php endif; ?>
                                <?php if ($member['jacket_size']) : ?>
                                    <span class="size-item">Jacket: <?php echo sanitizeString($member['jacket_size']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions - Right Column -->
                <?php if ($member['status'] === 'available') : ?>
                    <div class="member-actions">
                        <button class="btn btn-primary btn-sponsor-child"
                                data-child-id="<?php echo $member['id']; ?>"
                                data-display-id="<?php echo sanitizeString($member['display_id']); ?>"
                                data-family-id="<?php echo $member['family_id']; ?>"
                                data-age-months="<?php echo sanitizeInt($member['age_months']); ?>"
                                data-gender="<?php echo sanitizeString($member['gender']); ?>"
                                data-grade="<?php echo sanitizeString($member['grade'] ?? ''); ?>"
                                data-school="<?php echo sanitizeString($member['school'] ?? ''); ?>"
                                data-shirt-size="<?php echo sanitizeString($member['shirt_size'] ?? ''); ?>"
                                data-pant-size="<?php echo sanitizeString($member['pant_size'] ?? ''); ?>"
                                data-jacket-size="<?php echo sanitizeString($member['jacket_size'] ?? ''); ?>"
                                data-shoe-size="<?php echo sanitizeString($member['shoe_size'] ?? ''); ?>"
                                data-interests="<?php echo sanitizeString($member['interests'] ?? ''); ?>"
                                data-wishes="<?php echo sanitizeString($member['wishes'] ?? ''); ?>"
                                aria-label="Sponsor child <?php echo sanitizeString($member['display_id']); ?>, age <?php echo displayAge($member['age_months']); ?>">
                            Sponsor This Child
                        </button>
                    </div>
                <?php else : ?>
                    <div class="member-actions">
                        <p class="sponsored-message" role="status" aria-live="polite">This child is already sponsored</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Navigation -->
    <div class="family-footer">
        <a href="<?php echo baseUrl('?page=children'); ?>"
           class="btn btn-secondary"
           aria-label="Go back to browse all children">
            ← Back to Children
        </a>
        <?php if ($available_count > 0) : ?>
            <button class="btn btn-primary btn-add-all-family"
                    aria-label="Sponsor all <?php echo $available_count; ?> available family member<?php echo $available_count > 1 ? 's' : ''; ?> from family <?php echo sanitizeString($family['family_number']); ?>">
                Sponsor All Available (<?php echo $available_count; ?>)
            </button>
        <?php endif; ?>
    </div>
</div>

<style>
/* Family Page Styles - Optimized for reduced whitespace */
.family-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
}

/* Breadcrumb - High Contrast for Visibility */
.breadcrumb {
    margin-bottom: 1rem;
}

.breadcrumb-link {
    color: var(--color-primary);
    background: white;
    text-decoration: none;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    border: 2px solid var(--color-primary);
}

.breadcrumb-link:hover {
    background: var(--color-primary);
    color: white;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transform: translateY(-2px);
}

/* Family Header - Reduced Padding for Compact Design */
.family-header {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.family-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
}

.family-stats {
    display: flex;
    gap: 2rem;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
}

.stat-item {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
}

.stat-item strong {
    font-size: 1.5rem;
    line-height: 1;
}

.btn-add-all-family {
    margin-top: 0.5rem;
}

/* Family Background */
.family-background {
    background: var(--color-light);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border-left: 4px solid var(--color-primary);
}

.family-background h2 {
    margin-top: 0;
    font-size: 1.25rem;
    color: var(--color-primary);
}

.family-background p {
    margin-bottom: 0;
    line-height: 1.6;
}

/* Family Members Grid - Responsive columns */
.family-members-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
    margin-bottom: 2rem;
}

/* Desktop: 2 columns for wider screens */
@media (min-width: 1024px) {
    .family-members-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Family Member Card - ULTRA COMPACT */
.family-member-card {
    background: white;
    border: 2px solid var(--color-border);
    border-radius: 8px;
    padding: 0.75rem;
    transition: box-shadow 0.3s ease;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 0.75rem;
    align-items: start;
}

.family-member-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.member-sponsored {
    opacity: 0.7;
    background: var(--color-light);
}

/* Card Header - Compact vertical layout with photo */
.member-card-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
    min-width: 90px;
}

.member-photo {
    flex-shrink: 0;
}

.member-photo img {
    width: 70px;
    height: 70px;
    border-radius: 6px;
    object-fit: contain;
    background: var(--color-light);
}

.member-title {
    text-align: center;
}

.member-title h2 {
    margin: 0 0 0.15rem 0;
    font-size: 1rem;
    color: var(--color-primary);
    font-weight: 700;
}

.status-badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    white-space: nowrap;
}

.status-available {
    background: var(--color-success-bg);
    color: var(--color-success);
}

.status-sponsored,
.status-pending,
.status-confirmed {
    background: var(--color-secondary-bg);
    color: var(--color-secondary);
}

/* Basic Info - Inline chips (REMOVED - Now in details) */
.member-basic-info {
    display: none;
}

/* Details - ULTRA Compact with inline layout */
.member-details {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    font-size: 0.85rem;
}

/* Basic demographics - inline */
.member-details::before {
    content: attr(data-demographics);
    font-size: 0.8rem;
    color: var(--color-text-muted);
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    background: var(--color-light);
    border-radius: 4px;
    display: inline-block;
    margin-bottom: 0.2rem;
}

.detail-section {
    margin: 0;
    margin-top: 0.3rem;
}

.detail-section strong {
    color: var(--color-text-muted);
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.2rem;
}

.size-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.size-item {
    background: var(--color-light);
    padding: 0.2rem 0.5rem;
    border-radius: 3px;
    font-size: 0.75rem;
    white-space: nowrap;
}

.detail-row {
    margin: 0;
    margin-bottom: 0.35rem;
    font-size: 0.82rem;
    line-height: 1.4;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.detail-row strong {
    color: var(--color-text-muted);
    display: inline;
    font-weight: 600;
}

.detail-row span {
    display: inline;
}

.detail-row.special-needs {
    background: var(--color-warning-bg);
    padding: 0.35rem 0.5rem;
    border-radius: 4px;
    border-left: 3px solid var(--color-warning);
    font-size: 0.82rem;
}

.detail-row.special-needs strong {
    color: var(--color-warning-dark);
    display: inline;
}

/* Member Actions - Right column */
.member-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 140px;
}

.member-actions .btn {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    white-space: nowrap;
}

.sponsored-message {
    text-align: center;
    color: var(--color-text-muted);
    font-style: italic;
    margin: 0;
    padding: 0.5rem;
    font-size: 0.85rem;
}

/* Family Footer */
.family-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 0;
    border-top: 2px solid var(--color-border);
}

/* Responsive - Adjustments for mobile and tablet */
@media (max-width: 768px) {
    /* Stack card layout vertically on mobile */
    .family-member-card {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto auto;
        gap: 0.75rem;
    }

    .member-card-header {
        flex-direction: row;
        justify-content: flex-start;
        text-align: left;
        min-width: 0;
    }

    .member-title {
        text-align: left;
    }

    .member-photo img {
        width: 60px;
        height: 60px;
    }

    .member-actions {
        min-width: 0;
        width: 100%;
    }

    .member-actions .btn {
        width: 100%;
    }

    .family-header {
        padding: 1rem;
    }

    .family-header h1 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .family-stats {
        gap: 1.5rem;
        flex-wrap: wrap;
        margin-bottom: 0.75rem;
    }

    .stat-item {
        flex-direction: column;
        gap: 0.25rem;
    }

    .family-footer {
        flex-direction: column;
        gap: 1rem;
    }

    .family-footer .btn {
        width: 100%;
    }
}
</style>

<script nonce="<?php echo $cspNonce; ?>">
// CSP-compliant event listeners for family sponsorship (v1.5)
document.addEventListener('DOMContentLoaded', function() {
    // Check which children are already in selections and mark their buttons
    function updateButtonStates() {
        if (typeof SelectionsManager !== 'undefined') {
            const selections = SelectionsManager.getSelections();
            const selectedIds = selections.map(c => c.id);

            document.querySelectorAll('.btn-sponsor-child').forEach(button => {
                const childId = parseInt(button.getAttribute('data-child-id'));
                if (selectedIds.includes(childId)) {
                    button.textContent = '✓ ADDED';
                    button.disabled = true;
                    button.classList.add('btn-success');
                    button.classList.remove('btn-primary');
                    const displayId = button.getAttribute('data-display-id');
                    button.setAttribute('aria-label', `Child ${displayId} already in selections`);
                }
            });

            // Update "Sponsor All" button if all children are already selected
            const allButtons = document.querySelectorAll('.btn-sponsor-child');
            const allAdded = Array.from(allButtons).every(btn => selectedIds.includes(parseInt(btn.getAttribute('data-child-id'))));
            document.querySelectorAll('.btn-add-all-family').forEach(familyBtn => {
                if (allAdded && allButtons.length > 0) {
                    familyBtn.textContent = '✓ All Added';
                    familyBtn.disabled = true;
                    familyBtn.classList.add('btn-success');
                    familyBtn.classList.remove('btn-primary');
                }
            });
        }
    }

    // Update button states on page load
    updateButtonStates();

    // Listen for custom event from SelectionsManager
    window.addEventListener('selectionsUpdated', updateButtonStates);

    // Individual child sponsor buttons
    document.querySelectorAll('.btn-sponsor-child').forEach(button => {
        button.addEventListener('click', function(event) {
            const childId = parseInt(this.getAttribute('data-child-id'));
            const displayId = this.getAttribute('data-display-id');
            const familyId = parseInt(this.getAttribute('data-family-id'));
            const ageMonths = parseInt(this.getAttribute('data-age-months'));
            const gender = this.getAttribute('data-gender');
            const grade = this.getAttribute('data-grade') || '';
            const school = this.getAttribute('data-school') || '';
            const shirtSize = this.getAttribute('data-shirt-size') || '';
            const pantSize = this.getAttribute('data-pant-size') || '';
            const jacketSize = this.getAttribute('data-jacket-size') || '';
            const shoeSize = this.getAttribute('data-shoe-size') || '';
            const interests = this.getAttribute('data-interests') || '';
            const wishes = this.getAttribute('data-wishes') || '';

            const childData = {
                id: childId,
                display_id: displayId,
                family_id: familyId,
                age_months: ageMonths,
                gender: gender,
                grade: grade,
                school: school,
                shirt_size: shirtSize,
                pant_size: pantSize,
                jacket_size: jacketSize,
                shoe_size: shoeSize,
                interests: interests,
                wishes: wishes
            };

            // Use existing SelectionsManager
            if (typeof SelectionsManager !== 'undefined') {
                const success = SelectionsManager.addChild(childData);

                if (success) {
                    // Visual feedback - permanent "ADDED" state
                    event.target.textContent = 'Adding...';
                    event.target.disabled = true;

                    setTimeout(() => {
                        event.target.textContent = '✓ ADDED';
                        event.target.classList.add('btn-success');
                        event.target.classList.remove('btn-primary');
                        event.target.setAttribute('aria-label', `Child ${displayId} added to selections`);

                        // Trigger custom event
                        window.dispatchEvent(new Event('selectionsUpdated'));

                        // Show toast notification
                        if (typeof ToastManager !== 'undefined') {
                            ToastManager.show({
                                message: `✓ Child ${displayId} added to your cart`,
                                actionUrl: '<?php echo baseUrl('?page=my_sponsorships'); ?>',
                                actionText: 'View Reservations',
                                dismissText: 'Keep Browsing',
                                duration: 5000
                            });
                        }
                    }, 500);
                } else {
                    // Already in selections
                    event.target.textContent = '✓ Already Added';
                    event.target.classList.add('btn-success');
                    event.target.classList.remove('btn-primary');
                    event.target.disabled = true;
                }
            }
        });
    });

    // Sponsor all family buttons
    document.querySelectorAll('.btn-add-all-family').forEach(button => {
        button.addEventListener('click', function() {
            // Get all available child buttons
            const availableButtons = document.querySelectorAll('.btn-sponsor-child');

            if (availableButtons.length === 0) {
                if (typeof window.announce === 'function') {
                    window.announce('No family members available to sponsor');
                }
                return;
            }

            let addedCount = 0;
            let childIds = [];

            availableButtons.forEach(childButton => {
                const childId = parseInt(childButton.getAttribute('data-child-id'));
                const displayId = childButton.getAttribute('data-display-id');
                const familyId = parseInt(childButton.getAttribute('data-family-id'));
                const ageMonths = parseInt(childButton.getAttribute('data-age-months'));
                const gender = childButton.getAttribute('data-gender');
                const grade = childButton.getAttribute('data-grade') || '';
                const school = childButton.getAttribute('data-school') || '';
                const shirtSize = childButton.getAttribute('data-shirt-size') || '';
                const pantSize = childButton.getAttribute('data-pant-size') || '';
                const jacketSize = childButton.getAttribute('data-jacket-size') || '';
                const shoeSize = childButton.getAttribute('data-shoe-size') || '';
                const interests = childButton.getAttribute('data-interests') || '';
                const wishes = childButton.getAttribute('data-wishes') || '';

                const childData = {
                    id: childId,
                    display_id: displayId,
                    family_id: familyId,
                    age_months: ageMonths,
                    gender: gender,
                    grade: grade,
                    school: school,
                    shirt_size: shirtSize,
                    pant_size: pantSize,
                    jacket_size: jacketSize,
                    shoe_size: shoeSize,
                    interests: interests,
                    wishes: wishes
                };

                if (typeof SelectionsManager !== 'undefined') {
                    if (SelectionsManager.addChild(childData)) {
                        addedCount++;
                        childIds.push(displayId);
                    }
                }
            });

            if (addedCount > 0) {
                // ARIA announcement for screen readers
                if (typeof window.announce === 'function') {
                    const childList = childIds.slice(0, 3).join(', ');
                    const more = childIds.length > 3 ? ` and ${childIds.length - 3} more` : '';
                    window.announce(`Added ${addedCount} family members to your cart: ${childList}${more}. Redirecting to your cart.`);
                }

                // Redirect to cart after brief delay
                setTimeout(() => {
                    window.location.href = '<?php echo baseUrl('?page=my_sponsorships'); ?>';
                }, 1500);
            }
        });
    });
});
</script>
