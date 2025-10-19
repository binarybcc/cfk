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

require_once __DIR__ . '/../includes/db.php';

// Get family_id from URL
$family_id = isset($_GET['family_id']) ? intval($_GET['family_id']) : 0;

if (!$family_id) {
    setMessage('Invalid family ID.', 'error');
    redirect('?page=children');
    exit;
}

// Get database connection
$db = getDBConnection();

// Fetch family information
$stmt = $db->prepare("
    SELECT
        f.id,
        f.family_number,
        f.total_children,
        f.background_info
    FROM families f
    WHERE f.id = :family_id
    LIMIT 1
");
$stmt->execute(['family_id' => $family_id]);
$family = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$family) {
    setMessage('Family not found.', 'error');
    redirect('?page=children');
    exit;
}

// Fetch all family members
$stmt = $db->prepare("
    SELECT
        c.id,
        c.display_id,
        c.age,
        c.gender,
        c.grade,
        c.school,
        c.interests,
        c.wishes,
        c.special_needs,
        c.clothing_sizes,
        c.shoe_size,
        c.status,
        c.photo_filename
    FROM children c
    WHERE c.family_id = :family_id
    ORDER BY c.age DESC
");
$stmt->execute(['family_id' => $family_id]);
$family_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count available members
$available_count = count(array_filter($family_members, function($member) {
    return $member['status'] === 'available';
}));

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

        <?php if ($available_count > 0): ?>
            <button onclick="addEntireFamily(<?php echo $family_id; ?>)"
                    class="btn btn-large btn-primary btn-add-all-family"
                    aria-label="Sponsor all <?php echo $available_count; ?> available family member<?php echo $available_count > 1 ? 's' : ''; ?> from family <?php echo sanitizeString($family['family_number']); ?>">
                Sponsor All <?php echo $available_count; ?> Available Member<?php echo $available_count > 1 ? 's' : ''; ?>
            </button>
        <?php endif; ?>
    </div>

    <?php if (!empty($family['background_info'])): ?>
        <div class="family-background">
            <h2>About the Family</h2>
            <p><?php echo nl2br(sanitizeString($family['background_info'])); ?></p>
        </div>
    <?php endif; ?>

    <!-- Family Members Grid -->
    <div class="family-members-grid">
        <?php foreach ($family_members as $member): ?>
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
                            <?php echo ucfirst($member['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Basic Info (Compact) -->
                <div class="member-basic-info">
                    <span class="info-chip"><strong>Age:</strong> <?php echo sanitizeInt($member['age']); ?></span>
                    <span class="info-chip"><strong>Gender:</strong> <?php echo $member['gender'] === 'M' ? 'Boy' : 'Girl'; ?></span>
                    <?php if (!empty($member['grade'])): ?>
                        <span class="info-chip"><strong>Grade:</strong> <?php echo sanitizeString($member['grade']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Detailed Info (Collapsible sections) -->
                <div class="member-details">
                    <?php if (!empty($member['clothing_sizes'])): ?>
                        <div class="detail-row">
                            <strong>Sizes:</strong>
                            <span><?php echo sanitizeString($member['clothing_sizes']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($member['interests'])): ?>
                        <div class="detail-row">
                            <strong>Interests:</strong>
                            <span><?php echo sanitizeString($member['interests']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($member['wishes'])): ?>
                        <div class="detail-row">
                            <strong>Wishes:</strong>
                            <span><?php echo sanitizeString($member['wishes']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($member['special_needs'])): ?>
                        <div class="detail-row special-needs">
                            <strong>⚠️ Special Notes:</strong>
                            <span><?php echo sanitizeString($member['special_needs']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <?php if ($member['status'] === 'available'): ?>
                    <div class="member-actions">
                        <button onclick="addChildToCart(<?php echo $member['id']; ?>, '<?php echo sanitizeString($member['display_id']); ?>')"
                                class="btn btn-primary btn-block"
                                aria-label="Sponsor child <?php echo sanitizeString($member['display_id']); ?>, age <?php echo sanitizeInt($member['age']); ?>">
                            Sponsor This Child
                        </button>
                    </div>
                <?php else: ?>
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
        <?php if ($available_count > 0): ?>
            <button onclick="addEntireFamily(<?php echo $family_id; ?>)"
                    class="btn btn-primary"
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

/* Breadcrumb */
.breadcrumb {
    margin-bottom: 1rem;
}

.breadcrumb-link {
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 0;
}

.breadcrumb-link:hover {
    text-decoration: underline;
}

/* Family Header */
.family-header {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.family-header h1 {
    margin: 0 0 0.75rem 0;
    font-size: 2rem;
}

.family-stats {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.stat-item strong {
    font-size: 1.25rem;
    display: block;
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
    object-fit: cover;
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

    .family-header h1 {
        font-size: 1.5rem;
    }

    .family-stats {
        flex-direction: column;
        gap: 0.5rem;
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

<script>
// Add child to cart (selections)
function addChildToCart(childId, displayId) {
    // Get child data from the page
    const childData = {
        id: childId,
        display_id: displayId
    };

    // Use existing SelectionsManager
    const success = window.SelectionsManager.addChild(childData);

    if (success) {
        // ARIA announcement for screen readers
        if (typeof window.announce === 'function') {
            window.announce(`Added child ${displayId} to your cart`);
        }

        // Visual feedback
        event.target.textContent = '✓ Added to Cart';
        event.target.disabled = true;
        event.target.setAttribute('aria-label', `Child ${displayId} added to cart`);

        setTimeout(() => {
            event.target.textContent = 'Sponsor This Child';
            event.target.disabled = false;
            event.target.setAttribute('aria-label', `Sponsor child ${displayId}`);
        }, 2000);
    } else {
        // Already in cart
        if (typeof window.announce === 'function') {
            window.announce(`Child ${displayId} is already in your cart`);
        }
    }
}

// Add entire family
function addEntireFamily(familyId) {
    // Get all available children
    const availableChildren = document.querySelectorAll('.family-member-card:not(.member-sponsored)');

    if (availableChildren.length === 0) {
        if (typeof window.announce === 'function') {
            window.announce('No family members available to sponsor');
        }
        return;
    }

    let addedCount = 0;
    let childIds = [];

    availableChildren.forEach(card => {
        const button = card.querySelector('button[onclick*="addChildToCart"]');
        if (button) {
            // Extract displayId from button's onclick attribute
            const onclickAttr = button.getAttribute('onclick');
            const displayIdMatch = onclickAttr.match(/'([^']+)'/);
            if (displayIdMatch) {
                childIds.push(displayIdMatch[1]);
            }
            button.click();
            addedCount++;
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
}
</script>
