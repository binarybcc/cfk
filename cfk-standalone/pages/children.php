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

    // Helper function to get age category from age
    window.getAgeCategory = function(age) {
        if (age <= 4) {
            return 'birth_to_4';
        } else if (age <= 10) {
            return 'elementary';
        } else if (age <= 13) {
            return 'middle_school';
        } else if (age <= 18) {
            return 'high_school';
        }
        return 'high_school'; // Default for ages > 18
    };

    // Helper function to get age category label
    window.getAgeCategoryLabel = function(age) {
        const category = window.getAgeCategory(age);
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
    <div class="filters-section" x-data="{
        search: '',
        genderFilter: '',
        ageCategoryFilter: '',
        allChildren: [],
        isLoading: true,
        async init() {
            // Load ALL available children from database
            const apiUrl = '<?php echo baseUrl('api/get_all_children.php'); ?>';
            console.log('Loading all children from:', apiUrl);

            try {
                const response = await fetch(apiUrl);
                console.log('Response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('API returned:', data.count, 'children');

                if (data.success && data.children) {
                    this.allChildren = data.children;
                    // Also update the window variables for other functions
                    window.childrenData = data.children;
                    window.siblingsByFamily = data.siblings || {};
                    console.log('Successfully loaded', this.allChildren.length, 'children');
                } else {
                    console.warn('API response not successful:', data);
                    // Fallback to page data
                    this.allChildren = window.childrenData || [];
                }
            } catch (error) {
                console.error('Error loading children:', error);
                console.error('Error details:', error.message);
                // Fallback to page data
                this.allChildren = window.childrenData || [];
                console.log('Falling back to page data:', this.allChildren.length, 'children');
            } finally {
                this.isLoading = false;
            }
        },
        get filteredChildren() {
            return this.allChildren.filter(child => {
                const searchLower = this.search.toLowerCase();
                const matchesSearch = !this.search ||
                    (child.display_id && child.display_id.toLowerCase().includes(searchLower)) ||
                    (child.interests && child.interests.toLowerCase().includes(searchLower)) ||
                    (child.wishes && child.wishes.toLowerCase().includes(searchLower)) ||
                    child.age.toString().includes(searchLower);
                const matchesGender = !this.genderFilter || child.gender === this.genderFilter;
                const matchesAgeCategory = !this.ageCategoryFilter ||
                    window.getAgeCategory(child.age) === this.ageCategoryFilter;
                return matchesSearch && matchesGender && matchesAgeCategory;
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
                <label for="child-age-category-filter">Age Group:</label>
                <select id="child-age-category-filter" x-model="ageCategoryFilter">
                    <option value="">All Age Groups</option>
                    <option value="birth_to_4">Birth to 4 Years</option>
                    <option value="elementary">Elementary (5-10)</option>
                    <option value="middle_school">Middle School (11-13)</option>
                    <option value="high_school">High School (14-18)</option>
                </select>
            </div>

            <div class="filter-actions">
                <button @click="search = ''; genderFilter = ''; ageCategoryFilter = '';" class="btn btn-secondary">
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- Results Counter -->
        <div class="results-summary" style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
            <p style="margin: 0; font-weight: 600; color: #2c5530;">
                <span x-show="isLoading">Loading children...</span>
                <span x-show="!isLoading">
                    Showing <span x-text="filteredChildren.length"></span> of <span x-text="allChildren.length"></span> children
                    <span x-show="search || genderFilter || ageCategoryFilter" style="color: #666; font-weight: normal;">
                        (filtered)
                    </span>
                </span>
            </p>
        </div>

        <!-- Children Grid with Alpine.js Instant Filtering -->
        <div class="children-grid">
            <!-- No Results Message -->
            <div x-show="filteredChildren.length === 0" x-transition class="no-results">
                <h2>No Children Found</h2>
                <p>No children match your current filters. Try adjusting your search criteria.</p>
            </div>

            <!-- Filtered Children Cards -->
            <template x-for="child in filteredChildren" :key="child.id">
                <div class="child-card child-card-v2"
                     x-data="{
                         siblingCount: window.getSiblingCount(child.family_id)
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
                                <strong>Child:</strong> <span x-text="child.display_id"></span>
                            </div>
                            <div class="child-meta-item">
                                <strong>Age:</strong> <span x-text="child.age"></span>
                            </div>
                            <div class="child-meta-item">
                                <strong></strong> <span x-text="child.gender === 'M' ? 'Boy' : 'Girl'"></span>
                            </div>
                            <div class="child-meta-item" x-show="child.grade">
                                <strong>Age Group:</strong> <span x-text="child.grade || 'N/A'"></span>
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
                            <a :href="'<?php echo baseUrl('?page=family&family_id='); ?>' + child.family_id"
                               class="btn btn-secondary btn-view-family"
                               :class="siblingCount === 0 ? 'btn-disabled' : ''"
                               :aria-disabled="siblingCount === 0 ? 'true' : 'false'">
                                View Family
                            </a>
                            <button @click="addToSelections(child)"
                                    class="btn btn-primary btn-sponsor">
                                SPONSOR
                            </button>
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

