<?php

/**
 * Family View Page
 * Displays all members of a family for sponsorship consideration
 * Replaces the modal approach with a dedicated page for better UX and accessibility
 */

// Prevent direct access
if (!defined('CFK_APP')) {
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

if (!$family) {
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
$available_count = count(array_filter($family_members, fn($member): bool => $member['status'] === 'available'));

$pageTitle = 'Family ' . sanitizeString($family['family_number']);
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

    <?php if (!empty($family['background_info'])) : ?>
        <div class="family-background">
            <h2>About the Family</h2>
            <p><?php echo nl2br(sanitizeString($family['background_info'])); ?></p>
        </div>
    <?php endif; ?>

    <!-- Family Members Grid -->
    <div class="family-members-grid">
        <?php foreach ($family_members as $member) : ?>
            <div class="family-member-card <?php echo $member['status'] !== 'available' ? 'member-sponsored' : ''; ?>">
                <!-- Card Header -->
                <div class="member-card-header">
                    <div class="member-photo">
                        <img src="<?php echo getPhotoUrl($member['photo_filename'], $member); ?>"
                             alt="Avatar for Child <?php echo sanitizeString($member['display_id']); ?>">
                    </div>
                    <div class="member-title">
                        <h2>Child <?php echo sanitizeString($member['display_id']); ?></h2>
                        <span class="status-badge status-<?php echo $member['status']; ?>">
                            <?php echo ucfirst((string) $member['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Basic Info (Compact) -->
                <div class="member-basic-info">
                    <span class="info-chip"><strong>Age:</strong> <?php echo sanitizeInt($member['age']); ?></span>
                    <span class="info-chip"><strong>Gender:</strong> <?php echo $member['gender'] === 'M' ? 'Boy' : 'Girl'; ?></span>
                    <?php if (!empty($member['grade'])) : ?>
                        <span class="info-chip"><strong>Grade:</strong> <?php echo sanitizeString($member['grade']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Detailed Info (Collapsible sections) -->
                <div class="member-details">
                    <?php if (!empty($member['clothing_sizes'])) : ?>
                        <div class="detail-row">
                            <strong>Sizes:</strong>
                            <span><?php echo sanitizeString($member['clothing_sizes']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($member['interests'])) : ?>
                        <div class="detail-row">
                            <strong>Interests:</strong>
                            <span><?php echo sanitizeString($member['interests']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($member['wishes'])) : ?>
                        <div class="detail-row">
                            <strong>Wishes:</strong>
                            <span><?php echo sanitizeString($member['wishes']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($member['special_needs'])) : ?>
                        <div class="detail-row special-needs">
                            <strong>⚠️ Special Notes:</strong>
                            <span><?php echo sanitizeString($member['special_needs']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <?php if ($member['status'] === 'available') : ?>
                    <div class="member-actions">
                        <button class="btn btn-primary btn-block btn-sponsor-child"
                                data-child-id="<?php echo $member['id']; ?>"
                                data-display-id="<?php echo sanitizeString($member['display_id']); ?>"
                                aria-label="Sponsor child <?php echo sanitizeString($member['display_id']); ?>, age <?php echo sanitizeInt($member['age']); ?>">
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

/* Family Members Grid - 2 columns on desktop */
.family-members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
}

/* Family Member Card - COMPACT */
.family-member-card {
    background: white;
    border: 2px solid var(--color-border);
    border-radius: 8px;
    padding: 1rem;
    transition: box-shadow 0.3s ease;
}

.family-member-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.member-sponsored {
    opacity: 0.7;
    background: var(--color-light);
}

/* Card Header - Horizontal layout */
.member-card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--color-border);
}

.member-photo {
    flex-shrink: 0;
}

.member-photo img {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    object-fit: contain;
    background: var(--color-light);
}

.member-title {
    flex-grow: 1;
}

.member-title h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.25rem;
    color: var(--color-primary);
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
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

/* Basic Info - Inline chips */
.member-basic-info {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.info-chip {
    background: var(--color-light);
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.875rem;
}

.info-chip strong {
    color: var(--color-text-muted);
    font-weight: 600;
}

/* Details - Compact rows */
.member-details {
    margin-bottom: 0.75rem;
}

.detail-row {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    line-height: 1.4;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.detail-row strong {
    color: var(--color-text-muted);
    display: inline-block;
    min-width: 80px;
}

.detail-row.special-needs {
    background: var(--color-warning-bg);
    padding: 0.5rem;
    border-radius: 4px;
    border-left: 3px solid var(--color-warning);
}

/* Member Actions */
.member-actions {
    margin-top: 0.75rem;
}

.btn-block {
    width: 100%;
}

.sponsored-message {
    text-align: center;
    color: var(--color-text-muted);
    font-style: italic;
    margin: 0;
    padding: 0.5rem;
}

/* Family Footer */
.family-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 0;
    border-top: 2px solid var(--color-border);
}

/* Responsive - Single column on mobile */
@media (max-width: 768px) {
    .family-members-grid {
        grid-template-columns: 1fr;
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
    // Individual child sponsor buttons
    document.querySelectorAll('.btn-sponsor-child').forEach(button => {
        button.addEventListener('click', function(event) {
            const childId = parseInt(this.getAttribute('data-child-id'));
            const displayId = this.getAttribute('data-display-id');

            const childData = {
                id: childId,
                display_id: displayId
            };

            // Use existing SelectionsManager
            if (typeof SelectionsManager !== 'undefined') {
                const success = SelectionsManager.addChild(childData);

                if (success) {
                    // Visual feedback
                    event.target.textContent = 'Adding...';
                    event.target.disabled = true;

                    setTimeout(() => {
                        event.target.textContent = '✓ Added';
                        event.target.setAttribute('aria-label', `Child ${displayId} added to selections`);

                        setTimeout(() => {
                            event.target.textContent = 'Sponsor This Child';
                            event.target.disabled = false;
                            event.target.setAttribute('aria-label', `Sponsor child ${displayId}`);
                        }, 1500);
                    }, 500);
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

                const childData = {
                    id: childId,
                    display_id: displayId
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
