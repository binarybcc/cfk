<?php

/**
 * Children Listing Page
 * Display all available children with filtering and pagination
 */

// Prevent direct access
if (! defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

global $cspNonce;
$pageTitle = 'Children Needing Sponsorship';

// Check if viewing a specific family
$viewingFamily = ! empty($_GET['family_id']);
$familyId = $viewingFamily ? sanitizeInt($_GET['family_id']) : null;
$familyInfo = null; // Initialize to prevent undefined variable warning

if ($viewingFamily) {
    // Family view mode - show only this family, no pagination
    $filters = ['family_id' => $familyId];
    $children = getChildren($filters, 1, 999); // Get all family members
    $totalCount = count($children);
    $totalPages = 1;
    $currentPage = 1;

    // Define per-page variables (not used in family view, but prevents undefined variable warnings)
    $perPageOptions = [12, 24, 48];
    $perPage = config('children_per_page', 12);

    // Get family info for display
    $familyInfo = $children === [] ? null : getFamilyById($familyId);

    // No need for eager loading since we're showing one family
    $siblingsByFamily = [$familyId => $children];
} else {
    // Normal browsing mode with filters
    $filters = [];
    if (! empty($_GET['search'])) {
        $filters['search'] = sanitizeString($_GET['search']);
    }
    if (! empty($_GET['age_category'])) {
        $filters['age_category'] = sanitizeString($_GET['age_category']);
    }
    if (! empty($_GET['gender'])) {
        $filters['gender'] = sanitizeString($_GET['gender']);
    }

    // Pagination (using 'p' parameter to avoid conflict with page routing)
    $currentPage = sanitizeInt($_GET['p'] ?? 1);
    if ($currentPage < 1) {
        $currentPage = 1;
    }

    // Per-page selector - allow users to choose how many children to display
    $perPageOptions = [12, 24, 48];
    $perPage = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $perPageOptions, true)
        ? (int)$_GET['per_page']
        : config('children_per_page', 12);
    $limit = $perPage;

    // Get children and count
    $children = getChildren($filters, $currentPage, $limit);
    $totalCount = getChildrenCount($filters);
    $totalPages = ceil($totalCount / $limit);

    // Eager load family members to prevent N+1 queries
    $siblingsByFamily = eagerLoadFamilyMembers($children);
}

// Build query string for pagination
$queryParams = [];
if (! empty($filters['search'])) {
    $queryParams['search'] = $filters['search'];
}
if (! empty($filters['age_category'])) {
    $queryParams['age_category'] = $filters['age_category'];
}
if (! empty($filters['gender'])) {
    $queryParams['gender'] = $filters['gender'];
}
$queryString = http_build_query($queryParams);
$baseUrl = baseUrl('?page=children' . ($queryString !== '' && $queryString !== '0' ? '&' . $queryString : ''));
?>

<div class="children-page">
    <?php
    // Page header component
    if ($viewingFamily && $familyInfo) {
        $title = 'Family ' . sanitizeString($familyInfo['family_number']);
        $description = 'All children in this family who need Christmas sponsorship.';

        // Add back button as additional content
        ob_start();
        ?>
        <div class="family-view-actions">
            <?php echo renderButton('← Back to All Children', baseUrl('?page=children'), 'secondary'); ?>
        </div>
        <?php
        $additionalContent = ob_get_clean();
    } else {
        $title = 'Children Needing Christmas Sponsorship';
        $description = 'Each child represents a family in our community who could use extra support this Christmas season. Browse the children below and select someone to sponsor.';

        // Results summary will be shown by Alpine.js dynamically
        // No need for server-side static count that doesn't match filtered results
        $additionalContent = '';
    }

    require_once __DIR__ . '/../includes/components/page_header.php';
    ?>

    <!-- Filters Section (hidden in family view mode) - Alpine.js Enhanced for Instant Search -->
    <?php if (! $viewingFamily) : ?>
    <script nonce="<?php echo $cspNonce; ?>">
    // Define children data for Alpine.js
    window.childrenData = <?php echo json_encode($children, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    // Define siblings data for Alpine.js
    window.siblingsByFamily = <?php echo json_encode($siblingsByFamily, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    // Helper function for age/gender-appropriate placeholder images
    window.getPlaceholderImage = function(ageMonths, gender) {
        const baseUrl = '<?php echo baseUrl('assets/images/'); ?>';

        // Convert months to years for categorization
        const ageYears = Math.floor(ageMonths / 12);

        // Age categories
        if (ageYears <= 5) {
            return baseUrl + (gender === 'M' ? 'b-4boysm.png' : 'b-4girlsm.png');
        } else if (ageYears <= 11) {
            return baseUrl + (gender === 'M' ? 'elementaryboysm.png' : 'elementarygirlsm.png');
        } else if (ageYears <= 14) {
            return baseUrl + (gender === 'M' ? 'middleboysm.png' : 'middlegirlsm.png');
        } else {
            return baseUrl + (gender === 'M' ? 'hsboysm.png' : 'hsgirlsm.png');
        }
    };

    // Helper function to get age category from age in months
    window.getAgeCategory = function(ageMonths) {
        const ageYears = Math.floor(ageMonths / 12);
        if (ageYears <= 4) {
            return 'birth_to_4';
        } else if (ageYears <= 10) {
            return 'elementary';
        } else if (ageYears <= 13) {
            return 'middle_school';
        } else if (ageYears <= 18) {
            return 'high_school';
        }
        return 'high_school'; // Default for ages > 18
    };

    // Helper function to get age category label
    window.getAgeCategoryLabel = function(ageMonths) {
        const category = window.getAgeCategory(ageMonths);
        const labels = {
            'birth_to_4': 'Birth to 4 Years',
            'elementary': 'Elementary',
            'middle_school': 'Middle School',
            'high_school': 'High School'
        };
        return labels[category] || '';
    };

    // Helper function to get sibling count
    window.getSiblingCount = function(familyId) {
        const siblings = window.siblingsByFamily[familyId] || [];
        // Count available siblings (excluding current child)
        return siblings.filter(s => s.status === 'available').length - 1;
    };

    // Selections System v1.5 - Real implementation
    window.addToSelections = function(child) {
        if (SelectionsManager.addChild(child)) {
            // Successfully added
            showNotification(`Added ${child.display_id} to your selections!`, 'success');
        } else {
            // Already in selections
            showNotification(`${child.display_id} is already in your selections.`, 'info');
        }
    };

    // Simple notification system
    window.showNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: ${type === 'success' ? '#2c5530' : type === 'warning' ? '#f39c12' : '#3498db'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
            max-width: 300px;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    };
    </script>
    <div class="filters-section">
        <!-- Server-Side Filter Form -->
        <form method="get" action="" class="filters-form">
            <input type="hidden" name="page" value="children">

            <div class="filter-group filter-group-search">
                <label for="child-search-input">Search:</label>
                <div class="search-input-wrapper">
                    <input type="text"
                           id="child-search-input"
                           name="search"
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                           placeholder="Family code, interests, wishes, age...">
                    <small class="search-help-text">
                        Try: "123A", "bike", "doll", "boy 6", etc.
                    </small>
                </div>
            </div>

            <div class="filter-group">
                <label for="child-gender-filter">Gender:</label>
                <select id="child-gender-filter" name="gender">
                    <option value="">Both</option>
                    <option value="M" <?php echo ($_GET['gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Boys</option>
                    <option value="F" <?php echo ($_GET['gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Girls</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="child-age-category-filter">Age Group:</label>
                <select id="child-age-category-filter" name="age_category">
                    <option value="">All Age Groups</option>
                    <option value="birth_to_4" <?php echo ($_GET['age_category'] ?? '') === 'birth_to_4' ? 'selected' : ''; ?>>Birth to 4 Years</option>
                    <option value="elementary" <?php echo ($_GET['age_category'] ?? '') === 'elementary' ? 'selected' : ''; ?>>Elementary (5-10)</option>
                    <option value="middle_school" <?php echo ($_GET['age_category'] ?? '') === 'middle_school' ? 'selected' : ''; ?>>Middle School (11-13)</option>
                    <option value="high_school" <?php echo ($_GET['age_category'] ?? '') === 'high_school' ? 'selected' : ''; ?>>High School (14-18)</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="?page=children" class="btn btn-secondary">Clear Filters</a>
            </div>
        </form>

        <!-- Results Counter with Per-Page Selector -->
        <div class="results-summary" style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <p style="margin: 0; font-weight: 600; color: #2c5530;">
                Showing <?php echo count($children); ?> of <?php echo $totalCount; ?> children
                <?php if (! empty($filters)) : ?>
                    <span style="color: #666; font-weight: normal;">(filtered)</span>
                <?php endif; ?>
            </p>

            <!-- Per-Page Selector -->
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label for="per-page-select" style="margin: 0; font-weight: 500; color: #2c5530;">Show:</label>
                <select id="per-page-select"
                        onchange="window.location.href = updateQueryParam('per_page', this.value)"
                        style="padding: 0.4rem 0.8rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.95rem;">
                    <?php foreach ($perPageOptions as $option) : ?>
                        <option value="<?php echo $option; ?>" <?php echo $perPage === $option ? 'selected' : ''; ?>>
                            <?php echo $option; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span style="color: #666;">per page</span>
            </div>
        </div>

        <script nonce="<?php echo $cspNonce; ?>">
        // Helper function to update query parameter while preserving others
        function updateQueryParam(key, value) {
            const url = new URL(window.location.href);
            url.searchParams.set(key, value);
            url.searchParams.delete('p'); // Reset to page 1 when changing per-page
            return url.toString();
        }
        </script>

        <!-- Children Grid with Server-Side Rendering -->
        <div class="children-grid">
            <?php if ($children === []) : ?>
                <!-- No Results Message -->
                <div class="no-results">
                    <h2>No Children Found</h2>
                    <p>No children match your current filters. Try adjusting your search criteria.</p>
                </div>
            <?php else : ?>
                <!-- Children Cards -->
                <?php foreach ($children as $child) : ?>
                    <?php
                    $siblingCount = getSiblingCount($child['family_id']);
                    ?>
                    <div class="child-card child-card-v2">
                        <!-- Top Section: Image + Metadata Side by Side -->
                        <div class="child-top-section">
                            <!-- Child Avatar (Age/Gender-Appropriate Generic Image) -->
                            <div class="child-photo-compact">
                                <img src="<?php echo getPlaceholderImage($child['age_months'], $child['gender']); ?>"
                                     alt="Child <?php echo htmlspecialchars((string) $child['display_id']); ?>">
                            </div>

                            <!-- Metadata beside image -->
                            <div class="child-header-meta">
                                <div class="child-meta-item">
                                    <strong>Child:</strong> <span><?php echo htmlspecialchars((string) $child['display_id']); ?></span>
                                </div>
                                <div class="child-meta-item">
                                    <strong>Age:</strong> <span><?php echo displayAge($child['age_months']); ?></span>
                                </div>
                                <div class="child-meta-item">
                                    <strong></strong> <span><?php echo $child['gender'] === 'M' ? 'Boy' : 'Girl'; ?></span>
                                </div>
                                <?php if (! empty($child['grade'])) : ?>
                                    <div class="child-meta-item">
                                        <strong>Age Group:</strong> <span><?php echo htmlspecialchars((string) $child['grade']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Middle Section: Details -->
                        <div class="child-info">
                            <!-- Essential Needs & Wishes -->
                            <?php if (! empty($child['interests'])) : ?>
                                <div style="margin-bottom: 6px;">
                                    <strong style="color: #2c5530; font-size: 0.9rem;">💙 Essential Needs:</strong>
                                    <p style="margin: 3px 0 0 0; padding: 5px; background-color: #f8f9fa; border-left: 2px solid #3a6f3f; border-radius: 3px; color: #666; font-size: 0.85rem; line-height: 1.3;"><?php echo nl2br(htmlspecialchars((string) $child['interests'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (! empty($child['wishes'])) : ?>
                                <div style="margin-bottom: 6px;">
                                    <strong style="color: #c41e3a; font-size: 0.9rem;">🎁 Wishes:</strong>
                                    <p style="margin: 3px 0 0 0; padding: 5px; background-color: #fef5f5; border-left: 2px solid #c41e3a; border-radius: 3px; color: #666; font-size: 0.85rem; line-height: 1.3;"><?php echo nl2br(htmlspecialchars(cleanWishesText((string) $child['wishes']))); ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Special Needs -->
                            <?php if (! empty($child['special_needs'])) : ?>
                                <div style="margin-bottom: 6px;">
                                    <strong style="color: #856404; font-size: 0.9rem;">⚠️ Special Needs:</strong>
                                    <p style="margin: 3px 0 0 0; padding: 5px; background-color: #fff3cd; border-left: 2px solid #f5b800; border-radius: 3px; color: #666; font-size: 0.85rem; line-height: 1.3;"><?php echo nl2br(htmlspecialchars((string) $child['special_needs'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Clothing Sizes Section -->
                            <?php if (! empty($child['shirt_size']) || ! empty($child['pant_size']) || ! empty($child['jacket_size']) || ! empty($child['shoe_size'])) : ?>
                                <div style="margin-bottom: 6px; background-color: #e7f3ff; padding: 6px; border-radius: 3px;">
                                    <strong style="color: #2c5530; font-size: 0.9rem;">👕 Sizes:</strong>
                                    <div style="margin-top: 4px; font-size: 0.85rem; line-height: 1.3;">
                                        <?php if (! empty($child['shirt_size'])) : ?>
                                            <div style="display: flex; justify-content: space-between; padding: 2px 0;">
                                                <span style="color: #2c5530; font-weight: bold;">Shirt:</span>
                                                <span style="color: #666;"><?php echo htmlspecialchars((string) $child['shirt_size']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (! empty($child['pant_size'])) : ?>
                                            <div style="display: flex; justify-content: space-between; padding: 2px 0;">
                                                <span style="color: #2c5530; font-weight: bold;">Pants:</span>
                                                <span style="color: #666;"><?php echo htmlspecialchars((string) $child['pant_size']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (! empty($child['jacket_size'])) : ?>
                                            <div style="display: flex; justify-content: space-between; padding: 2px 0;">
                                                <span style="color: #2c5530; font-weight: bold;">Jacket:</span>
                                                <span style="color: #666;"><?php echo htmlspecialchars((string) $child['jacket_size']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (! empty($child['shoe_size'])) : ?>
                                            <div style="display: flex; justify-content: space-between; padding: 2px 0;">
                                                <span style="color: #2c5530; font-weight: bold;">Shoes:</span>
                                                <span style="color: #666;"><?php echo htmlspecialchars((string) $child['shoe_size']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Bottom Section: Sibling Info + Actions (Blue Border Area) -->
                        <div class="child-action-section">
                            <!-- Sibling Count -->
                            <div class="sibling-info">
                                <span class="sibling-text">Number of Siblings available: <strong><?php echo $siblingCount; ?></strong></span>
                            </div>

                            <!-- Action Buttons -->
                            <div class="action-buttons">
                                <a href="<?php echo baseUrl('?page=family&family_number=' . urlencode((string) $child['family_number'])); ?>"
                                   class="btn btn-secondary btn-view-family <?php echo $siblingCount === 0 ? 'btn-disabled' : ''; ?>"
                                   <?php echo $siblingCount === 0 ? 'aria-disabled="true"' : ''; ?>>
                                    View Family
                                </a>
                                <button class="btn btn-primary btn-sponsor"
                                        data-child='<?php echo htmlspecialchars(json_encode($child)); ?>'>
                                    SPONSOR
                                </button>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1) : ?>
            <div class="pagination-wrapper">
                <?php
                // Preserve all query params except 'p' (page number)
                $queryParams = $_GET;
                unset($queryParams['p']);
                $baseQuery = http_build_query($queryParams);
                $baseQuery = $baseQuery !== '' && $baseQuery !== '0' ? '&' . $baseQuery : '';

            // Calculate page range to display
                $delta = 2; // Number of pages to show on each side of current page
                $rangeStart = max(1, $currentPage - $delta);
                $rangeEnd = min($totalPages, $currentPage + $delta);

            // Adjust range if we're near the start or end
                if ($rangeEnd - $rangeStart < $delta * 2) {
                    if ($currentPage < $totalPages / 2) {
                        $rangeEnd = min($totalPages, $rangeStart + ($delta * 2));
                    } else {
                        $rangeStart = max(1, $rangeEnd - ($delta * 2));
                    }
                }
                ?>

                <nav class="pagination" role="navigation" aria-label="Pagination Navigation">
                    <!-- First Page -->
                    <?php if ($currentPage > 1) : ?>
                        <a href="?p=1<?php echo $baseQuery; ?>"
                           class="pagination-item pagination-first"
                           aria-label="Go to first page">
                            «
                        </a>
                    <?php else : ?>
                        <span class="pagination-item pagination-first disabled" aria-disabled="true">«</span>
                    <?php endif; ?>

                    <!-- Previous Page -->
                    <?php if ($currentPage > 1) : ?>
                        <a href="?p=<?php echo $currentPage - 1; ?><?php echo $baseQuery; ?>"
                           class="pagination-item pagination-prev"
                           aria-label="Go to previous page">
                            ‹
                        </a>
                    <?php else : ?>
                        <span class="pagination-item pagination-prev disabled" aria-disabled="true">‹</span>
                    <?php endif; ?>

                    <!-- Start Ellipsis -->
                    <?php if ($rangeStart > 1) : ?>
                        <span class="pagination-item pagination-ellipsis">...</span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php for ($i = $rangeStart; $i <= $rangeEnd; $i++) : ?>
                        <?php if ($i === $currentPage) : ?>
                            <span class="pagination-item active" aria-current="page"><?php echo $i; ?></span>
                        <?php else : ?>
                            <a href="?p=<?php echo $i; ?><?php echo $baseQuery; ?>"
                               class="pagination-item"
                               aria-label="Go to page <?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- End Ellipsis -->
                    <?php if ($rangeEnd < $totalPages) : ?>
                        <span class="pagination-item pagination-ellipsis">...</span>
                    <?php endif; ?>

                    <!-- Next Page -->
                    <?php if ($currentPage < $totalPages) : ?>
                        <a href="?p=<?php echo $currentPage + 1; ?><?php echo $baseQuery; ?>"
                           class="pagination-item pagination-next"
                           aria-label="Go to next page">
                            ›
                        </a>
                    <?php else : ?>
                        <span class="pagination-item pagination-next disabled" aria-disabled="true">›</span>
                    <?php endif; ?>

                    <!-- Last Page -->
                    <?php if ($currentPage < $totalPages) : ?>
                        <a href="?p=<?php echo $totalPages; ?><?php echo $baseQuery; ?>"
                           class="pagination-item pagination-last"
                           aria-label="Go to last page">
                            »
                        </a>
                    <?php else : ?>
                        <span class="pagination-item pagination-last disabled" aria-disabled="true">»</span>
                    <?php endif; ?>
                </nav>

                <!-- Page Info -->
                <div class="pagination-info">
                    Page <strong><?php echo $currentPage; ?></strong> of <strong><?php echo $totalPages; ?></strong>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Call to Action -->
    <div class="cta-section">
        <h2>Ready to Make a Difference?</h2>
        <p>Every child deserves to experience the joy of Christmas. Your sponsorship can make that possible.</p>
        <div class="cta-buttons">
            <?php echo renderButton(
                'Make a General Donation',
                baseUrl('?page=donate'),
                'success',
                [
                    'size' => 'large',
                    'id' => 'cta-donate-btn',
                ]
            ); ?>
        </div>
    </div>
</div>

<!-- Event Listeners for Sponsor Buttons (CSP-compliant) -->
<script nonce="<?php echo $cspNonce; ?>">
document.addEventListener('DOMContentLoaded', function() {
    // Check which children are already in selections and mark their buttons
    function updateButtonStates() {
        if (typeof SelectionsManager !== 'undefined') {
            const selections = SelectionsManager.getSelections();
            const selectedIds = selections.map(c => c.id);

            document.querySelectorAll('.btn-sponsor').forEach(button => {
                const childData = JSON.parse(button.getAttribute('data-child'));
                if (selectedIds.includes(childData.id)) {
                    button.textContent = '✓ ADDED';
                    button.disabled = true;
                    button.classList.add('btn-success');
                    button.classList.remove('btn-primary');
                    button.setAttribute('aria-label', `Child ${childData.display_id} already in selections`);
                }
            });
        }
    }

    // Update button states on page load
    updateButtonStates();

    // Listen for selection changes from other tabs/windows
    window.addEventListener('storage', function(e) {
        if (e.key === 'cfk_selections') {
            updateButtonStates();
        }
    });

    // Also listen for custom event from SelectionsManager
    window.addEventListener('selectionsUpdated', updateButtonStates);

    // Attach event listeners to all SPONSOR buttons
    document.querySelectorAll('.btn-sponsor').forEach(button => {
        button.addEventListener('click', function(event) {
            const childData = JSON.parse(this.getAttribute('data-child'));

            // Use SelectionsManager directly
            if (typeof SelectionsManager !== 'undefined') {
                const success = SelectionsManager.addChild(childData);

                if (success) {
                    // Visual feedback - "Adding..." then "✓ ADDED" (permanent)
                    event.target.textContent = 'Adding...';
                    event.target.disabled = true;

                    setTimeout(() => {
                        event.target.textContent = '✓ ADDED';
                        event.target.classList.add('btn-success');
                        event.target.classList.remove('btn-primary');
                        event.target.setAttribute('aria-label', `Child ${childData.display_id} added to selections`);

                        // Trigger custom event so other listeners can update
                        window.dispatchEvent(new Event('selectionsUpdated'));

                        // Show toast notification
                        if (typeof ToastManager !== 'undefined') {
                            ToastManager.show({
                                message: `✓ Child ${childData.display_id} added to your cart`,
                                actionUrl: '<?php echo baseUrl('?page=my_sponsorships'); ?>',
                                actionText: 'View Reservations',
                                dismissText: 'Keep Browsing',
                                duration: 5000
                            });
                        }
                    }, 500);
                } else {
                    // Already in selections - show feedback
                    const originalText = event.target.textContent;
                    event.target.textContent = '✓ Already Added';
                    event.target.classList.add('btn-success');
                    event.target.classList.remove('btn-primary');
                    event.target.disabled = true;
                }
            }
        });
    });
});
</script>

