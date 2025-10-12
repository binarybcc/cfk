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

    // Define siblings data for Alpine.js
    window.siblingsByFamily = <?php echo json_encode($siblingsByFamily, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

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

    // Helper function to get sibling count
    window.getSiblingCount = function(familyId) {
        const siblings = window.siblingsByFamily[familyId] || [];
        // Count available siblings (excluding current child)
        return siblings.filter(s => s.status === 'available').length - 1;
    };

    // Helper function to get all family members
    window.getFamilyMembers = function(familyId) {
        return window.siblingsByFamily[familyId] || [];
    };

    // Placeholder for cart/selections functionality (to be fully implemented)
    window.addToSelections = function(child) {
        // TODO: Build full "My Selections" cart system
        // For now, show confirmation
        alert(`Added ${child.display_id} to your Sponsorship List!\n\n(Full cart system coming soon)`);
        console.log('Child added to selections:', child);
    };

    // Add entire family to selections
    window.addEntireFamily = function(familyId) {
        const familyMembers = window.siblingsByFamily[familyId] || [];
        const availableMembers = familyMembers.filter(m => m.status === 'available');

        if (availableMembers.length === 0) {
            alert('No family members are currently available for sponsorship.');
            return;
        }

        // TODO: Build full cart system - for now show confirmation
        const names = availableMembers.map(m => m.display_id).join(', ');
        alert(`Added ${availableMembers.length} family members to your Sponsorship List:\n${names}\n\n(Full cart system coming soon)`);
        console.log('Family members added:', availableMembers);
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
            <div class="filter-group filter-group-search">
                <label for="child-search-input">Search:</label>
                <div class="search-input-wrapper">
                    <input type="text"
                           id="child-search-input"
                           x-model="search"
                           placeholder="Family code, interests, wishes, age...">
                    <small class="search-help-text">
                        Try: "123A", "bike", "doll", "boy 6", etc.
                    </small>
                </div>
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
                <div class="child-card child-card-v2"
                     x-data="{
                         showFamilyModal: false,
                         siblingCount: window.getSiblingCount(child.family_id),
                         familyMembers: window.getFamilyMembers(child.family_id)
                     }"
                     x-transition>
                    <!-- Top Section: Image + Metadata Side by Side -->
                    <div class="child-top-section">
                        <!-- Child Avatar (Age/Gender-Appropriate Generic Image) -->
                        <div class="child-photo-compact">
                            <img :src="window.getPlaceholderImage(child.age, child.gender)"
                                 :alt="'Child ' + child.display_id">
                        </div>

                        <!-- Metadata beside image -->
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
                    </div>

                    <!-- Middle Section: Details -->
                    <div class="child-info">
                        <!-- Interests & Wishes -->
                        <div x-show="child.interests" style="margin-bottom: 15px;">
                            <strong style="color: #2c5530;">Interests:</strong>
                            <p style="margin: 5px 0 0 0; color: #666;" x-text="child.interests"></p>
                        </div>

                        <div x-show="child.wishes" style="margin-bottom: 15px;">
                            <strong style="color: #c41e3a;">Wishes:</strong>
                            <p style="margin: 5px 0 0 0; color: #666;" x-text="child.wishes"></p>
                        </div>
                    </div>

                    <!-- Bottom Section: Sibling Info + Actions (Blue Border Area) -->
                    <div class="child-action-section">
                        <!-- Sibling Count -->
                        <div class="sibling-info">
                            <span class="sibling-text">Number of Siblings available: <strong x-text="siblingCount"></strong></span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button @click="showFamilyModal = true"
                                    class="btn btn-secondary btn-view-family"
                                    :disabled="siblingCount === 0">
                                View Family
                            </button>
                            <button @click="addToSelections(child)"
                                    class="btn btn-primary btn-sponsor">
                                SPONSOR
                            </button>
                        </div>
                    </div>

                    <!-- Family Modal -->
                    <div x-show="showFamilyModal"
                         x-cloak
                         x-transition.opacity.duration.300ms
                         class="family-modal-overlay"
                         @click="showFamilyModal = false">
                        <div class="family-modal-content" @click.stop>
                            <div class="family-modal-header">
                                <h3>Family <span x-text="child.display_id.replace(/[A-Z]$/, '')"></span></h3>
                                <button @click="showFamilyModal = false" class="modal-close">&times;</button>
                            </div>

                            <!-- Add Entire Family Button -->
                            <div class="family-modal-actions">
                                <button @click="addEntireFamily(child.family_id); showFamilyModal = false;"
                                        class="btn btn-primary btn-add-family"
                                        x-show="siblingCount > 0">
                                    <span x-text="'Add All ' + (siblingCount + 1) + ' Family Members'"></span>
                                </button>
                            </div>

                            <div class="family-modal-body">
                                <template x-for="member in familyMembers" :key="member.id">
                                    <div class="family-member-card-detailed">
                                        <!-- Member Header -->
                                        <div class="family-member-header">
                                            <div class="family-member-title">
                                                <strong x-text="member.display_id"></strong>
                                                <span :class="member.status === 'available' ? 'badge badge-success' : 'badge badge-secondary'"
                                                      x-text="member.status === 'available' ? 'Available' : 'Sponsored'"></span>
                                            </div>
                                        </div>

                                        <!-- Member Basic Info -->
                                        <div class="family-member-basic-info">
                                            <div class="info-item">
                                                <strong>Age:</strong> <span x-text="member.age"></span>
                                            </div>
                                            <div class="info-item">
                                                <strong>Gender:</strong> <span x-text="member.gender === 'M' ? 'Boy' : 'Girl'"></span>
                                            </div>
                                            <div class="info-item" x-show="member.grade">
                                                <strong>Grade:</strong> <span x-text="member.grade"></span>
                                            </div>
                                        </div>

                                        <!-- Member Details -->
                                        <div class="family-member-details">
                                            <div x-show="member.interests" class="detail-section">
                                                <strong class="detail-label">Interests:</strong>
                                                <p class="detail-text" x-text="member.interests"></p>
                                            </div>
                                            <div x-show="member.wishes" class="detail-section">
                                                <strong class="detail-label">Wishes:</strong>
                                                <p class="detail-text" x-text="member.wishes"></p>
                                            </div>
                                            <div x-show="member.clothing_sizes" class="detail-section">
                                                <strong class="detail-label">Clothing Sizes:</strong>
                                                <p class="detail-text" x-text="member.clothing_sizes"></p>
                                            </div>
                                            <div x-show="member.shoe_size" class="detail-section">
                                                <strong class="detail-label">Shoe Size:</strong>
                                                <p class="detail-text" x-text="member.shoe_size"></p>
                                            </div>
                                        </div>

                                        <!-- Member Actions -->
                                        <div class="family-member-actions">
                                            <button @click="addToSelections(member); showFamilyModal = false;"
                                                    class="btn btn-primary btn-sm"
                                                    x-show="member.status === 'available'">
                                                Add to Selections
                                            </button>
                                            <a :href="'<?php echo baseUrl(); ?>?page=child&id=' + member.id"
                                               class="btn btn-secondary btn-sm">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>
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

