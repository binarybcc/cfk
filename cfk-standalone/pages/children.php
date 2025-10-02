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

// Get filters from URL
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

// Pagination
$currentPage = sanitizeInt($_GET['page'] ?? 1);
$limit = config('children_per_page');

// Get children and count
$children = getChildren($filters, $currentPage, $limit);
$totalCount = getChildrenCount($filters);
$totalPages = ceil($totalCount / $limit);

// Build query string for pagination
$queryParams = [];
if (!empty($filters['search'])) $queryParams['search'] = $filters['search'];
if (!empty($filters['age_category'])) $queryParams['age_category'] = $filters['age_category'];
if (!empty($filters['gender'])) $queryParams['gender'] = $filters['gender'];
$queryString = http_build_query($queryParams);
$baseUrl = baseUrl('?page=children' . ($queryString ? '&' . $queryString : ''));
?>

<div class="children-page">
    <div class="page-header">
        <h1>Children Needing Christmas Sponsorship</h1>
        <p class="page-description">
            Each child represents a family in our community who could use extra support this Christmas season. 
            Browse the children below and select someone to sponsor.
史</p>
        
        <?php if ($totalCount > 0): ?>
            <div class="results-summary">
                <p>Showing <?php echo count($children); ?> of <?php echo $totalCount; ?> children
                <?php if (!empty($filters['search']) || !empty($filters['age_category']) || !empty($filters['gender'])): ?>
                    matching your filters
                <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filters Section -->
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
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

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
                <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-primary">View All Children</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="children-grid">
            <?php foreach ($children as $child): ?>
                <div class="child-card">
                    <div class="child-photo">
                        <img src="<?php echo getPhotoUrl($child['photo_filename'], $child); ?>" 
                             alt="Avatar for <?php echo sanitizeString($child['name']); ?>"
                             loading="lazy">
                    </div>
                    
                    <div class="child-info">
                        <h3 class="child-name"><?php echo sanitizeString($child['name']); ?></h3>
                        <p class="child-id">ID: <?php echo sanitizeString($child['display_id']); ?></p>
                        <p class="child-age"><?php echo formatAge($child['age']); ?></p>
                        
                        <?php if (!empty($child['grade'])): ?>
                            <p class="child-grade">Grade: <?php echo sanitizeString($child['grade']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($child['interests'])): ?>
                            <div class="child-interests">
                                <strong>Likes:</strong> 
                                <?php echo sanitizeString(substr($child['interests'], 0, 100)); ?>
                                <?php if (strlen($child['interests']) > 100): ?>...<?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($child['wishes'])): ?>
                            <div class="child-wishes">
                                <strong>Wishes for:</strong> 
                                <?php echo sanitizeString(substr($child['wishes'], 0, 100)); ?>
                                <?php if (strlen($child['wishes']) > 100): ?>...<?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Family Info -->
                        <?php 
                        $siblings = getFamilyMembers($child['family_id'], $child['id']);
                        if (!empty($siblings)): ?>
                            <div class="family-info">
                                <strong>Has <?php echo count($siblings); ?> sibling<?php echo count($siblings) > 1 ? 's' : ''; ?>:</strong>
                                <?php 
                                $siblingNames = array_map(fn($s) => $s['name'] . ' (' . $s['display_id'] . ')', $siblings);
                                echo sanitizeString(implode(', ', $siblingNames));
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="child-actions">
                        <a href="<?php echo baseUrl('?page=child&id=' . $child['id']); ?>" 
                           class="btn btn-primary">
                            Learn More & Sponsor
                        </a>
                        
                        <?php if (!empty($siblings)): ?>
                            <a href="<?php echo baseUrl('?page=children&family_id=' . $child['family_id']); ?>" 
                               class="btn btn-secondary btn-small">
                                View Family
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
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
            <button id="cta-donate-btn" class="btn btn-large btn-success" zeffy-form-link="https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true">
                Make a General Donation
            </button>
        </div>
    </div>
</div>

<style>
.children-page { margin-bottom: 2rem; }

.page-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem 0;
    background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
    color: white;
    border-radius: 8px;
}

.page-header h1 { margin-bottom: 1rem; font-size: 2.5rem; }
.page-description { font-size: 1.1rem; max-width: 800px; margin: 0 auto; }
.results-summary { margin-top: 1rem; opacity: 0.9; }

.filters-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 150px;
}

.filter-group label {
    font-weight: bold;
    margin-bottom: 0.5rem;
    color: #333;
}

.filter-group input, .filter-group select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    align-items: end;
}

.children-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.child-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #e1e5e9;
}

.child-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.child-photo img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    display: block;
}

.child-info {
    padding: 1.5rem;
}

.child-name {
    font-size: 1.4rem;
    font-weight: bold;
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.child-id {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.child-age, .child-grade {
    color: #555;
    margin-bottom: 0.5rem;
}

.child-interests, .child-wishes {
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.child-interests strong, .child-wishes strong {
    color: #2c5530;
}

.family-info {
    background: #f1f8f3;
    padding: 0.75rem;
    border-radius: 6px;
    font-size: 0.9rem;
    margin-top: 1rem;
}

.child-actions {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: #2c5530;
    color: white;
}

.btn-primary:hover {
    background: #1e3a21;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545862;
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
    transform: translateY(-1px);
}

.no-results {
    text-align: center;
    padding: 3rem 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.cta-section {
    background: linear-gradient(135deg, #4a7c59 0%, #2c5530 100%);
    color: white;
    padding: 3rem 2rem;
    border-radius: 12px;
    text-align: center;
    margin-top: 3rem;
}

.cta-section h2 {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin: 2rem 0;
}

.pagination ul {
    display: flex;
    list-style: none;
    padding: 0;
    gap: 0.5rem;
}

.pagination a, .pagination span {
    padding: 0.5rem 1rem;
    text-decoration: none;
    border-radius: 4px;
    border: 1px solid #ddd;
    color: #2c5530;
}

.pagination a:hover, .pagination .active {
    background: #2c5530;
    color: white;
}

@media (max-width: 768px) {
    .children-grid {
        grid-template-columns: 1fr;
    }
    
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-actions {
        justify-content: center;
        margin-top: 1rem;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
}
</style>