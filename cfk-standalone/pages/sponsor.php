<?php
/**
 * Sponsorship Request Form
 * Handles child sponsorship requests with single-sponsor logic
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

require_once __DIR__ . '/../includes/sponsorship_manager.php';
require_once __DIR__ . '/../includes/email_manager.php';

// Get child ID
$childId = sanitizeInt($_GET['child_id'] ?? 0);
if (!$childId) {
    setMessage('Please select a child to sponsor.', 'error');
    header('Location: ' . baseUrl('?page=children'));
    exit;
}

// Check if child is available
$availability = CFK_Sponsorship_Manager::isChildAvailable($childId);
$child = $availability['child'];

if (!$child) {
    setMessage('Child not found.', 'error');
    header('Location: ' . baseUrl('?page=children'));
    exit;
}

$pageTitle = 'Sponsor ' . $child['display_id'];
$errors = [];
$formData = [];

// Handle form submission
if ($_POST && isset($_POST['submit_sponsorship'])) {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        $formData = [
            'name' => $_POST['sponsor_name'] ?? '',
            'email' => $_POST['sponsor_email'] ?? '',
            'phone' => $_POST['sponsor_phone'] ?? '',
            'address' => $_POST['sponsor_address'] ?? '',
            'gift_preference' => $_POST['gift_preference'] ?? 'shopping',
            'message' => $_POST['special_message'] ?? ''
        ];
        
        // Attempt to create sponsorship
        $result = CFK_Sponsorship_Manager::createSponsorshipRequest($childId, $formData);
        
        if ($result['success']) {
            setMessage($result['message'], 'success');
            header('Location: ' . baseUrl('?page=children'));
            exit;
        } else {
            $errors[] = $result['message'];
            // Refresh availability status
            $availability = CFK_Sponsorship_Manager::isChildAvailable($childId);
        }
    }
}

// If child is not available, show message and redirect option
if (!$availability['available']) {
    $unavailableReason = $availability['reason'];
}

// Get full child details for display
$fullChild = getChildById($childId);
$siblings = getFamilyMembers($fullChild['family_id'], $childId);
?>

<div class="sponsor-page">
    <div class="page-header">
        <h1>Sponsor Child <?php echo sanitizeString($child['display_id']); ?></h1>
        <nav class="breadcrumb">
            <a href="<?php echo baseUrl('?page=children'); ?>">All Children</a> 
            <span>&raquo;</span>
            <a href="<?php echo baseUrl('?page=child&id=' . $childId); ?>">View Profile</a>
            <span>&raquo;</span>
            <span>Sponsor</span>
        </nav>
    </div>

    <?php if (!$availability['available']): ?>
        <!-- Child Not Available -->
        <div class="unavailable-notice">
            <div class="alert alert-warning">
                <h2>🚫 Sponsorship Not Available</h2>
                <p><strong><?php echo sanitizeString($unavailableReason); ?></strong></p>
                <p>This child is no longer available for new sponsorship requests. This happens when:</p>
                <ul>
                    <li>Another sponsor has already selected this child</li>
                    <li>The child's sponsorship is being processed</li>
                    <li>The child has already been sponsored</li>
                </ul>
                
                <div class="alternative-actions">
                    <h3>What you can do instead:</h3>
                    <div class="action-buttons">
                        <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-primary">
                            Browse Other Children
                        </a>
                        <?php if (!empty($siblings)): ?>
                            <a href="<?php echo baseUrl('?page=children&family_id=' . $fullChild['family_id']); ?>" class="btn btn-secondary">
                                View This Child's Family
                            </a>
                        <?php endif; ?>
                        <button id="unavailable-donate-btn" class="btn btn-success" 
                                zeffy-form-link="https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true">
                            Make a General Donation
                        </button>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Sponsorship Form -->
        <div class="sponsor-form-container">
            <!-- Child Summary -->
            <div class="child-summary">
                <div class="child-photo">
                    <img src="<?php echo getPhotoUrl($fullChild['photo_filename'], $fullChild); ?>" 
                         alt="Avatar for child <?php echo sanitizeString($fullChild['display_id']); ?>">
                </div>
                <div class="child-info">
                    <h3>Child <?php echo sanitizeString($fullChild['display_id']); ?></h3>
                    <p><strong>Age:</strong> <?php echo formatAge($fullChild['age']); ?></p>
                    <?php if (!empty($fullChild['grade'])): ?>
                        <p><strong>Grade:</strong> <?php echo sanitizeString($fullChild['grade']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($fullChild['interests'])): ?>
                        <div class="summary-section">
                            <strong>Interests:</strong> 
                            <p><?php echo sanitizeString($fullChild['interests']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($fullChild['wishes'])): ?>
                        <div class="summary-section">
                            <strong>Christmas Wishes:</strong>
                            <p><?php echo sanitizeString($fullChild['wishes']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sponsorship Form -->
            <form method="POST" action="" class="sponsorship-form" id="sponsorshipForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <h2>Your Sponsorship Information</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo sanitizeString($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="sponsor_name" class="form-label">Your Name *</label>
                        <input type="text" 
                               id="sponsor_name" 
                               name="sponsor_name" 
                               class="form-input" 
                               value="<?php echo sanitizeString($formData['name'] ?? ''); ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sponsor_email" class="form-label">Email Address *</label>
                        <input type="email" 
                               id="sponsor_email" 
                               name="sponsor_email" 
                               class="form-input" 
                               value="<?php echo sanitizeString($formData['email'] ?? ''); ?>"
                               required>
                        <div class="form-help">We'll use this to send you confirmation and updates</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="sponsor_phone" class="form-label">Phone Number (optional)</label>
                        <input type="tel" 
                               id="sponsor_phone" 
                               name="sponsor_phone" 
                               class="form-input" 
                               value="<?php echo sanitizeString($formData['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="sponsor_address" class="form-label">Mailing Address (optional)</label>
                    <textarea id="sponsor_address" 
                              name="sponsor_address" 
                              class="form-textarea" 
                              rows="3"><?php echo sanitizeString($formData['address'] ?? ''); ?></textarea>
                    <div class="form-help">We may use this for gift coordination if needed</div>
                </div>

                <div class="form-group">
                    <label for="gift_preference" class="form-label">Gift Preference</label>
                    <select id="gift_preference" name="gift_preference" class="form-select">
                        <option value="shopping" <?php echo ($formData['gift_preference'] ?? 'shopping') === 'shopping' ? 'selected' : ''; ?>>
                            I'll shop for specific gifts
                        </option>
                        <option value="gift_card" <?php echo ($formData['gift_preference'] ?? '') === 'gift_card' ? 'selected' : ''; ?>>
                            I'll provide gift cards
                        </option>
                        <option value="cash_donation" <?php echo ($formData['gift_preference'] ?? '') === 'cash_donation' ? 'selected' : ''; ?>>
                            I'll make a cash donation for gifts
                        </option>
                    </select>
                    <div class="form-help">Choose how you'd like to provide gifts for this child</div>
                </div>

                <div class="form-group">
                    <label for="special_message" class="form-label">Special Message (optional)</label>
                    <textarea id="special_message" 
                              name="special_message" 
                              class="form-textarea" 
                              rows="4"><?php echo sanitizeString($formData['message'] ?? ''); ?></textarea>
                    <div class="form-help">Any special notes or questions about the sponsorship</div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_sponsorship" class="btn btn-large btn-primary">
                        Submit Sponsorship Request
                    </button>
                    <a href="<?php echo baseUrl('?page=child&id=' . $childId); ?>" class="btn btn-large btn-secondary">
                        Back to Child Profile
                    </a>
                </div>
            </form>

            <!-- Important Notes -->
            <div class="important-notes">
                <h3>Important Information:</h3>
                <ul>
                    <li><strong>Confirmation Process:</strong> We'll review your request and confirm within 24 hours</li>
                    <li><strong>Gift Coordination:</strong> We'll provide specific gift suggestions and coordination details</li>
                    <li><strong>Delivery:</strong> Gifts are typically delivered 1-2 weeks before Christmas</li>
                    <li><strong>One Sponsor per Child:</strong> Each child can only have one sponsor to ensure fairness</li>
                    <li><strong>Questions?</strong> Contact us at <a href="mailto:<?php echo config('admin_email'); ?>"><?php echo config('admin_email'); ?></a></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.sponsor-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 1rem;
}

.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    color: #2c5530;
    margin-bottom: 1rem;
}

.breadcrumb {
    margin-top: 1rem;
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

.unavailable-notice {
    margin-bottom: 2rem;
}

.alternative-actions {
    margin-top: 1.5rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
    margin-top: 1rem;
}

.sponsor-form-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.child-summary {
    background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
    color: white;
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 2rem;
}

.child-summary .child-photo img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid white;
}

.child-summary h3 {
    font-size: 1.8rem;
    margin-bottom: 1rem;
    color: white;
}

.summary-section {
    margin-top: 1rem;
}

.summary-section strong {
    display: block;
    margin-bottom: 0.5rem;
}

.sponsorship-form {
    padding: 2rem;
}

.sponsorship-form h2 {
    color: #2c5530;
    margin-bottom: 2rem;
    text-align: center;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: bold;
    color: #333;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #2c5530;
}

.form-help {
    font-size: 0.9rem;
    color: #666;
    margin-top: 0.25rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.important-notes {
    background: #f8f9fa;
    padding: 2rem;
    border-top: 1px solid #ddd;
}

.important-notes h3 {
    color: #2c5530;
    margin-bottom: 1rem;
}

.important-notes ul {
    margin-left: 1rem;
}

.important-notes li {
    margin-bottom: 0.75rem;
    line-height: 1.5;
}

.important-notes a {
    color: #2c5530;
    text-decoration: none;
}

.important-notes a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .child-summary {
        flex-direction: column;
        text-align: center;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<script>
// Form validation
document.getElementById('sponsorshipForm').addEventListener('submit', function(e) {
    const name = document.getElementById('sponsor_name').value.trim();
    const email = document.getElementById('sponsor_email').value.trim();
    
    if (!name) {
        alert('Please enter your name.');
        document.getElementById('sponsor_name').focus();
        e.preventDefault();
        return;
    }
    
    if (!email) {
        alert('Please enter your email address.');
        document.getElementById('sponsor_email').focus();
        e.preventDefault();
        return;
    }
    
    // Confirm submission
    const childId = '<?php echo sanitizeString($child['display_id'] ?? ''); ?>';
    if (!confirm(`Are you sure you want to sponsor Child ${childId}? This will reserve the child for your sponsorship.`)) {
        e.preventDefault();
    }
});
</script>