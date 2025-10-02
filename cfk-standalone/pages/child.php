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

$pageTitle = $child['name'] . ' - Child Profile';

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
        <span><?php echo sanitizeString($child['name']); ?></span>
    </nav>

    <div class="child-profile">
        <div class="profile-header">
            <div class="profile-photo">
                <img src="<?php echo getPhotoUrl($child['photo_filename'], $child); ?>" 
                     alt="Avatar for <?php echo sanitizeString($child['name']); ?>">
            </div>
            
            <div class="profile-basic-info">
                <h1><?php echo sanitizeString($child['name']); ?></h1>
                <p class="child-id">Child ID: <?php echo sanitizeString($child['display_id']); ?></p>
                
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
                    
                    <?php if (!empty($siblings)): ?>
                        <p class="family-sponsor-note">
                            <strong>Consider sponsoring siblings together!</strong> 
                            Keeping families connected during Christmas creates even more joy.
                        </p>
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
                <button id="general-donate-btn" class="btn btn-success" 
                        zeffy-form-link="https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true">
                    Make a General Donation
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.child-profile-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 1rem;
}

.breadcrumb {
    margin-bottom: 2rem;
    font-size: 0.9rem;
}

.breadcrumb a {
    color: #2c5530;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.breadcrumb span {
    margin: 0 0.5rem;
    color: #666;
}

.profile-header {
    display: flex;
    gap: 2rem;
    margin-bottom: 3rem;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.profile-photo img {
    width: 300px;
    height: 300px;
    object-fit: cover;
    border-radius: 8px;
}

.profile-basic-info {
    flex: 1;
}

.profile-basic-info h1 {
    font-size: 2.5rem;
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.child-id {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 1.5rem;
    font-weight: bold;
}

.basic-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.detail-item {
    font-size: 1.1rem;
}

.detail-item strong {
    color: #2c5530;
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 0.9rem;
}

.status-available {
    background: #d4edda;
    color: #155724;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-sponsored {
    background: #cce7ff;
    color: #004085;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.profile-details {
    margin-bottom: 3rem;
}

.detail-section {
    background: white;
    padding: 2rem;
    margin-bottom: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.detail-section h2 {
    color: #2c5530;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
    margin-bottom: 1.5rem;
}

.size-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.size-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    text-align: center;
}

.size-item strong {
    color: #2c5530;
    display: block;
    margin-bottom: 0.5rem;
}

.interests-text, .wishes-text, .special-needs-text {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #444;
}

.siblings-section {
    margin-bottom: 2rem;
}

.siblings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.sibling-card {
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    overflow: hidden;
    text-align: center;
}

.sibling-photo img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.sibling-info {
    padding: 1rem;
}

.sibling-info h4 {
    margin-bottom: 0.5rem;
    color: #2c5530;
}

.family-notes {
    background: #f1f8f3;
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 2rem;
}

.sponsorship-action {
    background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
    color: white;
    padding: 3rem;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 2rem;
}

.sponsorship-action h2 {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.family-sponsor-note {
    margin: 1.5rem 0 1rem 0;
    font-style: italic;
    opacity: 0.9;
}

.other-help {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
}

.help-options {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 1.5rem;
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

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.alert {
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.alert-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-photo img {
        width: 250px;
        height: 250px;
        margin: 0 auto;
    }
    
    .profile-basic-info h1 {
        font-size: 2rem;
    }
    
    .basic-details {
        grid-template-columns: 1fr;
    }
    
    .size-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .siblings-grid {
        grid-template-columns: 1fr;
    }
    
    .help-options {
        flex-direction: column;
    }
}
</style>