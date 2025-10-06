<?php
/**
 * Sponsor Portal - View and Manage Sponsorships
 * Displays all sponsorships for a verified email address
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

require_once __DIR__ . '/../includes/sponsorship_manager.php';
require_once __DIR__ . '/../includes/email_manager.php';

// Check for verification token
$token = $_GET['token'] ?? '';
$verificationResult = CFK_Sponsorship_Manager::verifyPortalToken($token);

if (!$verificationResult['valid']) {
    // Token invalid or expired
    ?>
    <div class="portal-page">
        <div class="alert alert-error">
            <h2>⚠️ Access Denied</h2>
            <p><strong><?php echo sanitizeString($verificationResult['message']); ?></strong></p>
            <p>Please request a new access link from the <a href="<?php echo baseUrl('?page=sponsor_lookup'); ?>">sponsor lookup page</a>.</p>
        </div>
    </div>
    <?php
    return;
}

$sponsorEmail = $verificationResult['email'];

// Get all sponsorships for this email
$sponsorships = CFK_Sponsorship_Manager::getSponsorshipsWithDetails($sponsorEmail);

// Group sponsorships by family
$families = [];
foreach ($sponsorships as $sponsorship) {
    $familyId = $sponsorship['family_id'];
    if (!isset($families[$familyId])) {
        $families[$familyId] = [
            'family_number' => $sponsorship['family_number'],
            'family_name' => $sponsorship['family_name'],
            'children' => []
        ];
    }
    $families[$familyId]['children'][] = $sponsorship;
}

// Check if adding children (GET parameter)
$showAddChildren = isset($_GET['add_children']) && $_GET['add_children'] === '1';

// Handle adding children to sponsorship
$addChildrenResult = null;
if ($_POST && isset($_POST['add_children'])) {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $addChildrenResult = ['success' => false, 'message' => 'Security token invalid. Please try again.'];
    } else {
        $selectedChildIds = $_POST['child_ids'] ?? [];

        if (empty($selectedChildIds)) {
            $addChildrenResult = ['success' => false, 'message' => 'Please select at least one child.'];
        } else {
            // Create sponsorship data from first existing sponsorship
            $firstSponsorship = $sponsorships[0];
            $sponsorData = [
                'name' => $firstSponsorship['sponsor_name'],
                'email' => $firstSponsorship['sponsor_email'],
                'phone' => $firstSponsorship['sponsor_phone'] ?? '',
                'address' => $firstSponsorship['sponsor_address'] ?? '',
                'gift_preference' => $firstSponsorship['gift_preference'],
                'message' => 'Additional children added to existing sponsorship'
            ];

            $addChildrenResult = CFK_Sponsorship_Manager::addChildrenToSponsorship($selectedChildIds, $sponsorData, $sponsorEmail);

            if ($addChildrenResult['success']) {
                // Refresh sponsorships
                header('Location: ' . baseUrl('?page=sponsor_portal&token=' . urlencode($token)));
                exit;
            }
        }
    }
}
?>

<div class="portal-page">
    <div class="page-header">
        <h1>🎄 Your Sponsorship Portal</h1>
        <p class="sponsor-email">Logged in as: <strong><?php echo sanitizeEmail($sponsorEmail); ?></strong></p>
    </div>

    <?php if (!$showAddChildren): ?>
        <!-- Display Sponsorships -->
        <div class="sponsorships-container">
            <div class="portal-actions">
                <a href="<?php echo baseUrl('?page=sponsor_portal&token=' . urlencode($token) . '&add_children=1'); ?>" class="btn btn-primary">
                    ➕ Add More Children
                </a>
                <a href="<?php echo baseUrl('?page=sponsor_lookup'); ?>" class="btn btn-secondary">
                    ← Back to Lookup
                </a>
            </div>

            <div class="sponsorships-summary">
                <h2>Your Sponsored Children</h2>
                <p>You are sponsoring <strong><?php echo count($sponsorships); ?></strong> child(ren) across <strong><?php echo count($families); ?></strong> family(ies).</p>
            </div>

            <?php foreach ($families as $familyId => $family): ?>
                <div class="family-section">
                    <div class="family-header">
                        <h3>Family <?php echo sanitizeString($family['family_number']); ?></h3>
                        <?php if (!empty($family['family_name'])): ?>
                            <span class="family-name">(<?php echo sanitizeString($family['family_name']); ?>)</span>
                        <?php endif; ?>
                    </div>

                    <div class="children-grid">
                        <?php foreach ($family['children'] as $child): ?>
                            <div class="child-card">
                                <div class="child-header">
                                    <h4>Child <?php echo sanitizeString($child['child_display_id']); ?></h4>
                                    <span class="status-badge status-<?php echo $child['status']; ?>">
                                        <?php echo ucfirst($child['status']); ?>
                                    </span>
                                </div>

                                <div class="child-photo">
                                    <img src="<?php echo getPhotoUrl($child['photo_filename'], $child); ?>"
                                         alt="Avatar for <?php echo sanitizeString($child['child_display_id']); ?>">
                                </div>

                                <div class="child-details">
                                    <p><strong>Name:</strong> <?php echo sanitizeString($child['child_name']); ?></p>
                                    <p><strong>Age:</strong> <?php echo sanitizeInt($child['child_age']); ?> years old</p>
                                    <p><strong>Grade:</strong> <?php echo sanitizeString($child['child_grade']); ?></p>
                                    <p><strong>Gender:</strong> <?php echo $child['child_gender'] === 'M' ? 'Boy' : 'Girl'; ?></p>
                                </div>

                                <div class="clothing-sizes">
                                    <h5>Clothing Sizes:</h5>
                                    <div class="sizes-grid">
                                        <span>👕 Shirt: <?php echo sanitizeString($child['shirt_size'] ?? 'N/A'); ?></span>
                                        <span>👖 Pants: <?php echo sanitizeString($child['pant_size'] ?? 'N/A'); ?></span>
                                        <span>👟 Shoes: <?php echo sanitizeString($child['shoe_size'] ?? 'N/A'); ?></span>
                                        <span>🧥 Jacket: <?php echo sanitizeString($child['jacket_size'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($child['interests'])): ?>
                                    <div class="child-section">
                                        <h5>Interests:</h5>
                                        <p><?php echo sanitizeString($child['interests']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($child['wishes'])): ?>
                                    <div class="child-section wishes">
                                        <h5>🎁 Christmas Wishes:</h5>
                                        <p><?php echo sanitizeString($child['wishes']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($child['special_needs'])): ?>
                                    <div class="child-section special-needs">
                                        <h5>⚠️ Special Notes:</h5>
                                        <p><?php echo sanitizeString($child['special_needs']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="child-meta">
                                    <small>Requested: <?php echo date('M j, Y', strtotime($child['request_date'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="contact-notice">
                <h3>Need to Make Changes?</h3>
                <p><strong>We're here to help!</strong> To cancel or modify a sponsorship, please contact Christmas for Kids:</p>
                <p>📧 Email: <a href="mailto:<?php echo config('admin_email'); ?>"><?php echo config('admin_email'); ?></a></p>
                <p>📞 Phone: <?php echo sanitizeString(config('contact_phone', 'Contact via email')); ?></p>
            </div>
        </div>

    <?php else: ?>
        <!-- Add Children Form -->
        <div class="add-children-container">
            <div class="form-header">
                <h2>Add More Children to Your Sponsorship</h2>
                <p>Select additional children you'd like to sponsor. They'll be added to your existing sponsorship.</p>
            </div>

            <?php if ($addChildrenResult && !$addChildrenResult['success']): ?>
                <div class="alert alert-error">
                    <?php echo sanitizeString($addChildrenResult['message']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="addChildrenForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <?php
                // Get available children
                $availableChildren = getChildren(['status' => 'available'], 1, 100);

                if (empty($availableChildren)): ?>
                    <div class="alert alert-info">
                        <p>There are currently no available children to add. All children have been sponsored!</p>
                    </div>
                <?php else: ?>
                    <div class="available-children-grid">
                        <?php foreach ($availableChildren as $child): ?>
                            <label class="child-checkbox-card">
                                <input type="checkbox" name="child_ids[]" value="<?php echo $child['id']; ?>">
                                <div class="checkbox-card-content">
                                    <div class="checkbox-child-photo">
                                        <img src="<?php echo getPhotoUrl($child['photo_filename'], $child); ?>"
                                             alt="Avatar for <?php echo sanitizeString($child['display_id']); ?>">
                                    </div>
                                    <div class="checkbox-child-info">
                                        <h4><?php echo sanitizeString($child['display_id']); ?></h4>
                                        <p><?php echo formatAge($child['age']); ?></p>
                                        <p><?php echo $child['gender'] === 'M' ? 'Boy' : 'Girl'; ?></p>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add_children" class="btn btn-large btn-primary">
                            Add Selected Children
                        </button>
                        <a href="<?php echo baseUrl('?page=sponsor_portal&token=' . urlencode($token)); ?>" class="btn btn-large btn-secondary">
                            Cancel
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
.portal-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.sponsor-email {
    color: #666;
    font-size: 1rem;
}

.portal-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.sponsorships-summary {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    text-align: center;
}

.sponsorships-summary h2 {
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.family-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
}

.family-header {
    border-bottom: 2px solid #2c5530;
    padding-bottom: 1rem;
    margin-bottom: 2rem;
}

.family-header h3 {
    color: #2c5530;
    display: inline;
    margin-right: 1rem;
}

.family-name {
    color: #666;
    font-size: 1.1rem;
}

.children-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.child-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    border: 2px solid #ddd;
}

.child-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.child-header h4 {
    color: #2c5530;
    margin: 0;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: bold;
    text-transform: uppercase;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-confirmed {
    background: #d4edda;
    color: #155724;
}

.status-completed {
    background: #d1ecf1;
    color: #0c5460;
}

.child-photo {
    width: 120px;
    height: 120px;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 50%;
    overflow: hidden;
}

.child-photo img {
    max-width: 75%;
    max-height: 75%;
    width: auto;
    height: auto;
    object-fit: contain;
}

.child-details, .child-section {
    margin: 1rem 0;
}

.child-details p, .child-section p {
    margin: 0.5rem 0;
    color: #333;
}

.child-section h5 {
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.clothing-sizes {
    background: white;
    padding: 1rem;
    border-radius: 6px;
    margin: 1rem 0;
}

.clothing-sizes h5 {
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.sizes-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.wishes {
    background: #e8f5e9;
    padding: 1rem;
    border-radius: 6px;
}

.special-needs {
    background: #fff3cd;
    padding: 1rem;
    border-radius: 6px;
}

.child-meta {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #ddd;
    text-align: center;
    color: #666;
}

.contact-notice {
    background: #e3f2fd;
    padding: 2rem;
    border-radius: 8px;
    margin-top: 2rem;
    text-align: center;
}

.contact-notice h3 {
    color: #2c5530;
    margin-bottom: 1rem;
}

.contact-notice a {
    color: #2c5530;
    text-decoration: none;
    font-weight: bold;
}

.contact-notice a:hover {
    text-decoration: underline;
}

/* Add Children Styles */
.add-children-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    padding: 2rem;
}

.form-header {
    text-align: center;
    margin-bottom: 2rem;
}

.form-header h2 {
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.available-children-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.child-checkbox-card {
    display: block;
    cursor: pointer;
    position: relative;
}

.child-checkbox-card input[type="checkbox"] {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.checkbox-card-content {
    background: #f8f9fa;
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    transition: all 0.2s;
}

.child-checkbox-card:hover .checkbox-card-content {
    border-color: #2c5530;
    transform: translateY(-2px);
}

.child-checkbox-card input[type="checkbox"]:checked ~ .checkbox-card-content {
    background: #d4edda;
    border-color: #2c5530;
}

.checkbox-child-photo {
    width: 100px;
    height: 100px;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 50%;
    overflow: hidden;
}

.checkbox-child-photo img {
    max-width: 75%;
    max-height: 75%;
    width: auto;
    height: auto;
    object-fit: contain;
}

.checkbox-child-info h4 {
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.checkbox-child-info p {
    margin: 0.25rem 0;
    color: #666;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.alert {
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert-info {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}

@media (max-width: 768px) {
    .children-grid {
        grid-template-columns: 1fr;
    }

    .portal-actions {
        flex-direction: column;
    }

    .form-actions {
        flex-direction: column;
    }

    .available-children-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}
</style>

<script>
// Form validation
const form = document.getElementById('addChildrenForm');
if (form) {
    form.addEventListener('submit', function(e) {
        const checkboxes = form.querySelectorAll('input[name="child_ids[]"]:checked');

        if (checkboxes.length === 0) {
            alert('Please select at least one child to add.');
            e.preventDefault();
            return;
        }

        if (!confirm(`Are you sure you want to add ${checkboxes.length} child(ren) to your sponsorship?`)) {
            e.preventDefault();
        }
    });
}
</script>
