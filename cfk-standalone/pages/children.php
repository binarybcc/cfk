<?php
/**
 * Children Listing Page
 * Display all available children with filtering and pagination
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

$pageTitle = 'Children Needing Sponsorship';

// Check if viewing a specific family
$viewingFamily = !empty($_GET['family_id']);
$familyId = $viewingFamily ? sanitizeInt($_GET['family_id']) : null;

if ($viewingFamily) {
    // Family view mode - show only this family, no pagination
    $filters = ['family_id' => $familyId];
    $children = getChildren($filters, 1, 999); // Get all family members
    $totalCount = count($children);
    $totalPages = 1;
    $currentPage = 1;

    // Get family info for display
    $familyInfo = !empty($children) ? getFamilyById($familyId) : null;

    // No need for eager loading since we're showing one family
    $siblingsByFamily = [$familyId => $children];
} else {
    // Normal browsing mode with filters
    $filters = [];
    if (!empty($_GET['search'])) {
        $filters['search'] = sanitizeString($_GET['search']);
    }
    if (!empty($_GET['age_category'])) {
        $filters['age_category'] = sanitizeString($_GET['age_category']);
    }
    if (!empty($_GET['gender'])) {
        $filters['gender'] = sanitizeString($_GET['gender']);
    }

    // Pagination (using 'p' parameter to avoid conflict with page routing)
    $currentPage = sanitizeInt($_GET['p'] ?? 1);
    if ($currentPage < 1) $currentPage = 1;
    $limit = config('children_per_page');

    // Get children and count
    $children = getChildren($filters, $currentPage, $limit);
    $totalCount = getChildrenCount($filters);
    $totalPages = ceil($totalCount / $limit);

    // Eager load family members to prevent N+1 queries
    $siblingsByFamily = eagerLoadFamilyMembers($children);
}

// Build query string for pagination
$queryParams = [];
if (!empty($filters['search'])) $queryParams['search'] = $filters['search'];
if (!empty($filters['age_category'])) $queryParams['age_category'] = $filters['age_category'];
if (!empty($filters['gender'])) $queryParams['gender'] = $filters['gender'];
$queryString = http_build_query($queryParams);
$baseUrl = baseUrl('?page=children' . ($queryString ? '&' . $queryString : ''));
?>

<div class="children-page">
    <?php
    // Page header component
    if ($viewingFamily && $familyInfo) {
        $title = 'Family ' . sanitizeString($familyInfo['family_number']) . ' - ' . sanitizeString($familyInfo['family_name']);
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

        // Add results summary as additional content
        ob_start();
        if ($totalCount > 0):
        ?>
            <div class="results-summary">
                <p>Showing <?php echo count($children); ?> of <?php echo $totalCount; ?> children
                <?php if (!empty($filters['search']) || !empty($filters['age_category']) || !empty($filters['gender'])): ?>
                    matching your filters
                <?php endif; ?>
                </p>
            </div>
        <?php
        endif;
        $additionalContent = ob_get_clean();
    }

    require_once __DIR__ . '/../includes/components/page_header.php';
    ?>

    <!-- Filters Section (hidden in family view mode) -->
    <?php if (!$viewingFamily): ?>
    <div class="filters-section">
        <form method="GET" action="<?php echo baseUrl(); ?>" class="filters-form">
            <input type="hidden" name="page" value="children">
            
            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       value="<?php echo $filters['search'] ?? ''; ?>"
                       placeholder="Name, interests, or wishes">
            </div>
            
            <div class="filter-group">
                <label for="age_category">Age Group:</label>
                <select id="age_category" name="age_category">
                    <option value="">All Ages</option>
                    <?php 
                    global $ageCategories;
                    foreach ($ageCategories as $key => $category): ?>
                        <option value="<?php echo $key; ?>" 
                                <?php echo ($filters['age_category'] ?? '') === $key ? 'selected' : ''; ?>>
                            <?php echo $category['label']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="gender">Gender:</label>
                <select id="gender" name="gender">
                    <option value="">Both</option>
                    <option value="M" <?php echo ($filters['gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Boys</option>
                    <option value="F" <?php echo ($filters['gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Girls</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <?php echo renderButton('Filter', null, 'primary', ['submit' => true]); ?>
                <?php echo renderButton('Clear', baseUrl('?page=children'), 'secondary'); ?>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Children Grid -->
    <?php if (empty($children)): ?>
        <div class="no-results">
            <h3>No Children Found</h3>
            <p>
                <?php if (!empty($filters['search']) || !empty($filters['age_category']) || !empty($filters['gender'])): ?>
                    No children match your current filters. Try adjusting your search criteria.
                <?php else: ?>
                    There are no children currently available for sponsorship.
                <?php endif; ?>
            </p>
            <?php if (!empty($filters['search']) || !empty($filters['age_category']) || !empty($filters['gender'])): ?>
                <?php echo renderButton('View All Children', baseUrl('?page=children'), 'primary'); ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="children-grid">
            <?php foreach ($children as $child):
                // Get pre-loaded siblings (no database query!)
                $allFamilyMembers = $siblingsByFamily[$child['family_id']] ?? [];
                $siblings = array_filter($allFamilyMembers, fn($s) => $s['id'] != $child['id']);

                // Set options for the child card component
                $options = [
                    'show_wishes' => true,
                    'show_interests' => true,
                    'show_id' => true,
                    'show_siblings' => !$viewingFamily, // Hide siblings info when viewing family
                    'siblings' => $siblings,
                    'card_class' => 'child-card',
                    'button_text' => 'Learn More & Sponsor',
                    'show_actions' => true,
                    'show_family_button' => !$viewingFamily // Hide "View Family" button in family view
                ];
                include __DIR__ . '/../includes/components/child_card.php';
            endforeach; ?>
        </div>

        <!-- Pagination (hidden in family view mode) -->
        <?php if (!$viewingFamily && $totalPages > 1): ?>
            <div class="pagination-wrapper">
                <?php echo generatePagination($currentPage, $totalPages, $baseUrl); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Call to Action -->
    <div class="cta-section">
        <h2>Ready to Make a Difference?</h2>
        <p>Every child deserves to experience the joy of Christmas. Your sponsorship can make that possible.</p>
        <div class="cta-buttons">
            <?php echo renderButton(
                'Make a General Donation',
                null,
                'success',
                [
                    'size' => 'large',
                    'id' => 'cta-donate-btn',
                    'attributes' => [
                        'zeffy-form-link' => 'https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true'
                    ]
                ]
            ); ?>
        </div>
    </div>
</div>

