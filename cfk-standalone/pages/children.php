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

    <!-- Filters Section (hidden in family view mode) - Alpine.js Enhanced for Instant Search -->
    <?php if (!$viewingFamily): ?>
    <script>
    // Define children data for Alpine.js
    window.childrenData = <?php echo json_encode($children, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    // Helper function for age/gender-appropriate placeholder images
    window.getPlaceholderImage = function(age, gender) {
        const baseUrl = '<?php echo baseUrl('assets/images/'); ?>';

        // Age categories
        if (age <= 5) {
            return baseUrl + (gender === 'M' ? 'b-4boysm.png' : 'b-4girlsm.png');
        } else if (age <= 11) {
            return baseUrl + (gender === 'M' ? 'elementaryboysm.png' : 'elementarygirlsm.png');
        } else if (age <= 14) {
            return baseUrl + (gender === 'M' ? 'middleboysm.png' : 'middlegirlsm.png');
        } else {
            return baseUrl + (gender === 'M' ? 'hsboysm.png' : 'hsgirlsm.png');
        }
    };
    </script>
    <div class="filters-section" x-data="{
        search: '',
        genderFilter: '',
        ageMin: 0,
        ageMax: 18,
        allChildren: window.childrenData || [],
        get filteredChildren() {
            return this.allChildren.filter(child => {
                const searchLower = this.search.toLowerCase();
                const matchesSearch = !this.search ||
                    (child.display_id && child.display_id.toLowerCase().includes(searchLower)) ||
                    (child.interests && child.interests.toLowerCase().includes(searchLower)) ||
                    (child.wishes && child.wishes.toLowerCase().includes(searchLower)) ||
                    child.age.toString().includes(searchLower);
                const matchesGender = !this.genderFilter || child.gender === this.genderFilter;
                const matchesAge = child.age >= this.ageMin && child.age <= this.ageMax;
                return matchesSearch && matchesGender && matchesAge;
            });
        }
    }">
        <div class="filters-form">
            <div class="filter-group">
                <label for="child-search-input">Search:</label>
                <input type="text"
                       id="child-search-input"
                       x-model="search"
                       placeholder="Family code, interests, wishes, age...">
                <small style="display: block; color: #666; margin-top: 5px;">
                    Try: "123A", "bike", "doll", "boy 6", etc.
                </small>
            </div>

            <div class="filter-group">
                <label for="child-gender-filter">Gender:</label>
                <select id="child-gender-filter" x-model="genderFilter">
                    <option value="">Both</option>
                    <option value="M">Boys</option>
                    <option value="F">Girls</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="child-age-min">Age Range:</label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="number"
                           id="child-age-min"
                           x-model.number="ageMin"
                           min="0"
                           max="18"
                           style="width: 70px;">
                    <span>to</span>
                    <input type="number"
                           id="child-age-max"
                           x-model.number="ageMax"
                           min="0"
                           max="18"
                           style="width: 70px;">
                </div>
            </div>

            <div class="filter-actions">
                <button @click="search = ''; genderFilter = ''; ageMin = 0; ageMax = 18;" class="btn btn-secondary">
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- Results Counter -->
        <div class="results-summary" style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
            <p style="margin: 0; font-weight: 600; color: #2c5530;">
                Showing <span x-text="filteredChildren.length"></span> of <span x-text="allChildren.length"></span> children
                <span x-show="search || genderFilter || ageMin > 0 || ageMax < 18" style="color: #666; font-weight: normal;">
                    (filtered)
                </span>
            </p>
        </div>

        <!-- Children Grid with Alpine.js Instant Filtering -->
        <div class="children-grid">
            <!-- No Results Message -->
            <div x-show="filteredChildren.length === 0" x-transition class="no-results">
                <h3>No Children Found</h3>
                <p>No children match your current filters. Try adjusting your search criteria.</p>
            </div>

            <!-- Filtered Children Cards -->
            <template x-for="child in filteredChildren" :key="child.id">
                <div class="child-card child-card-horizontal" x-transition>
                    <!-- Child Avatar (Age/Gender-Appropriate Generic Image) - Left Side -->
                    <div class="child-photo-left">
                        <img :src="window.getPlaceholderImage(child.age, child.gender)"
                             :alt="'Child ' + child.display_id">
                    </div>

                    <!-- Child Info - Right Side -->
                    <div class="child-info-right">
                        <!-- Top Section: Metadata beside image -->
                        <div class="child-header-meta">
                            <div class="child-meta-item">
                                <strong>Family Code:</strong> <span x-text="child.display_id"></span>
                            </div>
                            <div class="child-meta-item">
                                <strong>Age:</strong> <span x-text="child.age"></span>
                            </div>
                            <div class="child-meta-item">
                                <strong>Gender:</strong> <span x-text="child.gender === 'M' ? 'Boy' : 'Girl'"></span>
                            </div>
                            <div class="child-meta-item" x-show="child.grade">
                                <strong>Grade:</strong> <span x-text="child.grade || 'N/A'"></span>
                            </div>
                        </div>

                        <!-- Interests & Wishes -->
                        <div class="child-details-section">
                            <div x-show="child.interests" class="detail-block">
                                <strong style="color: #2c5530;">Interests:</strong>
                                <p x-text="child.interests"></p>
                            </div>

                            <div x-show="child.wishes" class="detail-block">
                                <strong style="color: #c41e3a;">Wishes:</strong>
                                <p x-text="child.wishes"></p>
                            </div>
                        </div>

                        <!-- Bottom Section: Status & Actions -->
                        <div class="child-footer">
                            <span :class="child.status === 'sponsored' ? 'badge badge-success' : 'badge badge-warning'"
                                  x-text="child.status === 'sponsored' ? 'Sponsored' : 'Available'"
                                  class="status-badge">
                            </span>
                            <a :href="'<?php echo baseUrl(); ?>?page=child&id=' + child.id"
                               class="btn btn-primary btn-sm">
                                Learn More & Sponsor
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>
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
                    'id' => 'cta-donate-btn'
                ]
            ); ?>
        </div>
    </div>
</div>

