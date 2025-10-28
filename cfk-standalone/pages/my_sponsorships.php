<?php

/**
 * My Sponsorships - Unified Page
 * Combines pending selections + confirmed sponsorship lookup
 * v1.5 - Reservation System
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

require_once __DIR__ . '/../includes/reservation_functions.php';
require_once __DIR__ . '/../includes/reservation_emails.php';

$errors = [];
$success = false;
$emailSent = false;
$sponsorships = [];
$lookupEmail = '';

// Debug logging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("MY_SPONSORSHIPS: POST request received");
    error_log("MY_SPONSORSHIPS: POST keys: " . implode(', ', array_keys($_POST)));
}

// Check for token-based access
$token = $_GET['token'] ?? '';
if (!empty($token)) {
    $verifiedEmail = verifyAccessToken($token);

    if ($verifiedEmail) {
        // Valid token - load sponsorships
        $sponsorships = Database::fetchAll(
            "SELECT s.*, c.child_letter, c.age_months, c.gender, c.grade, c.wishes,
                    c.clothing_sizes, c.shoe_size, f.family_number,
                    CONCAT(f.family_number, c.child_letter) as display_id
             FROM sponsorships s
             JOIN children c ON s.child_id = c.id
             JOIN families f ON c.family_id = f.id
             WHERE s.sponsor_email = ?
             AND s.status IN ('confirmed', 'logged')
             ORDER BY s.confirmation_date DESC",
            [$verifiedEmail]
        );

        if ($sponsorships !== []) {
            $success = true;
            $lookupEmail = $verifiedEmail;
        } else {
            $errors[] = 'No sponsorships found for this access link.';
        }
    } else {
        $errors[] = 'This access link has expired or is invalid. Please request a new one below.';
    }
}

// Handle email access link request
if ($_POST && isset($_POST['lookup_email'])) {
    error_log("MY_SPONSORSHIPS: Form submitted, lookup_email detected");
    error_log("MY_SPONSORSHIPS: POST data: " . print_r($_POST, true));

    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        error_log("MY_SPONSORSHIPS: CSRF token verification FAILED");
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        error_log("MY_SPONSORSHIPS: CSRF token verified successfully");
        $email = sanitizeEmail($_POST['sponsor_email'] ?? '');
        $lookupEmail = $email;
        error_log("MY_SPONSORSHIPS: Email to send to: " . $email);

        if (empty($email)) {
            error_log("MY_SPONSORSHIPS: Email is empty");
            $errors[] = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("MY_SPONSORSHIPS: Email validation failed");
            $errors[] = 'Please enter a valid email address.';
        } else {
            error_log("MY_SPONSORSHIPS: Calling sendAccessLinkEmail()");
            // Send access link email using the same system as reservation emails
            $result = sendAccessLinkEmail($email);
            error_log("MY_SPONSORSHIPS: sendAccessLinkEmail returned: " . print_r($result, true));

            if ($result['success']) {
                error_log("MY_SPONSORSHIPS: Email sent successfully");
                $emailSent = true;
                $success = true;
            } else {
                error_log("MY_SPONSORSHIPS: Email send failed: " . $result['message']);
                $errors[] = $result['message'];
            }
        }
    }
}

// Handle resend email request
if ($_POST && isset($_POST['resend_email'])) {
    $email = sanitizeEmail($_POST['resend_to_email'] ?? '');

    if (!empty($email)) {
        $result = sendAccessLinkEmail($email);
        if ($result['success']) {
            $emailSent = true;
        }
    }
}

$pageTitle = 'My Sponsorships';
?>

<div class="my-sponsorships-page" x-data="mySponsorshipsApp()">
    <!-- Page Header -->
    <div class="page-header">
        <h1>My Sponsorships</h1>
        <p class="page-description">
            Manage your pending selections and request your sponsorship details via email
        </p>
    </div>

    <!-- Pending Selections Section (only shows if there are selections) -->
    <div x-show="selectionCount > 0" x-cloak class="pending-selections-section">
        <div class="section-card highlighted">
            <div class="section-header">
                <h2>📋 Pending Selections</h2>
                <span class="selection-count-badge" x-text="selectionCount + ' ' + (selectionCount === 1 ? 'child' : 'children')"></span>
            </div>
            <p class="section-description">
                You have children waiting to be confirmed. Complete your sponsorship to reserve them.
            </p>

            <!-- Selections Grid -->
            <div class="selections-grid">
                <template x-for="child in selections" :key="child.id">
                    <div class="selection-card-compact">
                        <div class="card-header">
                            <strong x-text="child.display_id"></strong>
                            <button @click="removeSelection(child.id)"
                                    class="btn-remove-small"
                                    title="Remove">
                                ✕
                            </button>
                        </div>
                        <div class="card-details">
                            <span x-text="formatAge(child.age_months)"></span> •
                            <span x-text="child.gender === 'M' ? 'Boy' : 'Girl'"></span>
                            <template x-if="child.grade">
                                • <span x-text="'Grade ' + child.grade"></span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Actions -->
            <div class="section-actions">
                <button @click="clearAllSelections()" class="btn btn-secondary">
                    Clear All
                </button>
                <a href="<?php echo baseUrl('?page=confirm_sponsorship'); ?>" class="btn btn-success btn-large">
                    Complete Sponsorship →
                </a>
            </div>
        </div>
    </div>

    <!-- Confirmed Sponsorships Lookup Section -->
    <div class="lookup-section">
        <div class="section-card">
            <div class="section-header">
                <h2>📧 Request Your Sponsorship Details</h2>
            </div>
            <p class="section-description">
                Already sponsored children? Enter your email and we'll send you a detailed email with all your sponsorship information, including wish lists, clothing sizes, and interests.
            </p>

            <?php if ($emailSent && $sponsorships === []) : ?>
                <!-- Email Sent Confirmation -->
                <div class="email-sent-confirmation">
                    <div class="alert alert-success">
                        <h3>✓ Sponsorship Details Sent!</h3>
                        <p>We've sent your sponsorship information to <strong><?php echo sanitizeString($lookupEmail); ?></strong></p>
                        <p>Check your email to view complete details about the children you've sponsored.</p>
                    </div>

                    <div class="info-box">
                        <h4>What to do next:</h4>
                        <ul class="feature-list">
                            <li>Check your email inbox (and spam folder)</li>
                            <li>Review the children's wishes, sizes, and interests</li>
                            <li>Use the information to shop for gifts</li>
                        </ul>
                        <div class="security-note">
                            <strong>Didn't receive it?</strong> Check your spam folder or <a href="<?php echo baseUrl('?page=my_sponsorships'); ?>">try again</a> with the correct email address.
                        </div>
                    </div>
                </div>

            <?php elseif ($success && $sponsorships !== []) : ?>
                <!-- Sponsorship Results -->
                <div class="sponsorships-found">
                    <div class="alert alert-success">
                        <h3>✓ Sponsorships Found!</h3>
                        <p>You have sponsored <strong><?php echo count($sponsorships); ?></strong>
                           <?php echo count($sponsorships) === 1 ? 'child' : 'children'; ?> for Christmas!</p>
                        <p><strong>Email:</strong> <?php echo sanitizeString($lookupEmail); ?></p>
                    </div>

                    <!-- Sponsored Children List -->
                    <div class="sponsored-children-list">
                        <h3>Your Sponsored Children:</h3>
                        <?php foreach ($sponsorships as $sponsorship) : ?>
                            <div class="sponsored-child-card">
                                <div class="child-card-header">
                                    <h4><?php echo sanitizeString($sponsorship['display_id']); ?></h4>
                                    <span class="status-badge status-confirmed">Confirmed</span>
                                </div>

                                <div class="child-info-grid">
                                    <div class="info-item">
                                        <strong>Age:</strong>
                                        <span><?php echo displayAge($sponsorship['age_months']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong>Gender:</strong>
                                        <span><?php echo $sponsorship['gender'] === 'M' ? 'Boy' : 'Girl'; ?></span>
                                    </div>
                                    <?php if (!empty($sponsorship['grade'])) : ?>
                                        <div class="info-item">
                                            <strong>Grade:</strong>
                                            <span>Grade <?php echo (int)$sponsorship['grade']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="info-item">
                                        <strong>Confirmed:</strong>
                                        <span><?php echo date('M j, Y', strtotime((string) $sponsorship['confirmation_date'])); ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($sponsorship['wishes'])) : ?>
                                    <div class="child-wishes">
                                        <strong>Wishes:</strong>
                                        <p><?php echo nl2br(sanitizeString($sponsorship['wishes'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($sponsorship['clothing_sizes']) || !empty($sponsorship['shoe_size'])) : ?>
                                    <div class="child-sizes">
                                        <?php if (!empty($sponsorship['clothing_sizes'])) : ?>
                                            <span><strong>Clothing:</strong> <?php echo sanitizeString($sponsorship['clothing_sizes']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($sponsorship['shoe_size'])) : ?>
                                            <span><strong>Shoes:</strong> <?php echo sanitizeString($sponsorship['shoe_size']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Actions -->
                    <div class="sponsorship-actions">
                        <?php if ($emailSent) : ?>
                            <div class="alert alert-info">
                                ✓ Confirmation email sent to <?php echo sanitizeString($lookupEmail); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="resend_to_email" value="<?php echo sanitizeString($lookupEmail); ?>">
                            <button type="submit" name="resend_email" class="btn btn-outline">
                                📧 Resend Confirmation Email
                            </button>
                        </form>

                        <button id="print-details-btn" class="btn btn-outline">
                            🖨️ Print Details
                        </button>
                        <script nonce="<?php echo $cspNonce; ?>">
                        document.getElementById('print-details-btn').addEventListener('click', function() {
                            window.print();
                        });
                        </script>

                        <a href="<?php echo baseUrl('?page=my_sponsorships'); ?>" class="btn btn-secondary">
                            ← Look Up Different Email
                        </a>
                    </div>
                </div>

            <?php else : ?>
                <!-- Lookup Form -->
                <div class="lookup-form-container">
                    <div class="info-box">
                        <h4>What you'll receive in your email:</h4>
                        <ul class="feature-list">
                            <li>✓ Complete details for all your sponsored children</li>
                            <li>✓ Gift wishes and clothing sizes</li>
                            <li>✓ Interests and special considerations</li>
                            <li>✓ Print-friendly format for shopping</li>
                        </ul>
                        <div class="security-note">
                            <strong>🔒 Privacy:</strong> Your email is never shared. We'll send the information directly to you.
                        </div>
                    </div>

                    <form method="POST" action="" class="lookup-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="lookup_email" value="1">

                        <?php if ($errors !== []) : ?>
                            <div class="alert alert-error" role="alert" aria-live="polite">
                                <strong>Error:</strong>
                                <ul id="sponsorship-form-errors">
                                    <?php foreach ($errors as $error) : ?>
                                        <li><?php echo sanitizeString($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="sponsor_email" class="form-label">Your Email Address <span aria-label="required">*</span></label>
                            <input type="email"
                                   id="sponsor_email"
                                   name="sponsor_email"
                                   class="form-input <?php echo $errors === [] ? '' : 'input-error'; ?>"
                                   placeholder="example@email.com"
                                   value="<?php echo sanitizeString($_POST['sponsor_email'] ?? ''); ?>"
                                   required
                                   aria-required="true"
                                   aria-describedby="sponsorship-email-help<?php echo $errors === [] ? '' : ' sponsorship-form-errors'; ?>"
                                   <?php echo $errors === [] ? '' : 'aria-invalid="true"'; ?>
                                   autocomplete="email">
                            <div id="sponsorship-email-help" class="form-help">Enter the email address you used when sponsoring</div>
                        </div>

                        <button type="submit" class="btn btn-large btn-primary">
                            Email My Sponsorship Details
                        </button>
                    </form>

                    <div class="help-section">
                        <h4>Need Help?</h4>
                        <p><strong>Don't have sponsorships yet?</strong> <a href="<?php echo baseUrl('?page=children'); ?>">Browse available children</a></p>
                        <p><strong>Forgot which email you used?</strong> Contact us at <a href="mailto:<?php echo config('admin_email'); ?>"><?php echo config('admin_email'); ?></a></p>
                        <p><strong>Need to make changes?</strong> Email us - we're happy to help!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
function mySponsorshipsApp() {
    return {
        selections: [],
        selectionCount: 0,

        formatAge(ageMonths) {
            if (!ageMonths) return '';
            if (ageMonths < 25) {
                return ageMonths + ' month' + (ageMonths !== 1 ? 's' : '');
            } else if (ageMonths < 36) {
                return '2 years';
            } else {
                const years = Math.floor(ageMonths / 12);
                return years + ' year' + (years !== 1 ? 's' : '');
            }
        },

        init() {
            // Wait for SelectionsManager to be ready
            if (typeof SelectionsManager !== 'undefined') {
                this.loadSelections();
            } else {
                // Retry after a short delay
                setTimeout(() => this.init(), 100);
                return;
            }

            // Listen for selection changes
            window.addEventListener('selectionsUpdated', () => {
                this.loadSelections();
            });

            // Listen for storage changes (cross-tab sync)
            window.addEventListener('storage', (e) => {
                if (e.key === 'cfk_selections') {
                    this.loadSelections();
                }
            });
        },

        loadSelections() {
            if (typeof SelectionsManager !== 'undefined') {
                this.selections = SelectionsManager.getSelections();
                this.selectionCount = this.selections.length;
                console.log('Loaded selections:', this.selectionCount, this.selections);
            }
        },

        removeSelection(childId) {
            if (confirm('Remove this child from your selections?')) {
                if (typeof SelectionsManager !== 'undefined') {
                    SelectionsManager.removeChild(childId);
                    this.loadSelections();

                    if (typeof window.showNotification === 'function') {
                        window.showNotification('Child removed from selections', 'info');
                    }
                }
            }
        },

        clearAllSelections() {
            if (confirm(`Remove all ${this.selectionCount} children from your selections?`)) {
                console.log('=== CLEAR ALL CLICKED ===');
                console.log('Before clear - selectionCount:', this.selectionCount);
                console.log('Before clear - selections:', this.selections);

                // Check localStorage before clear
                const beforeClear = localStorage.getItem('cfk_selections');
                console.log('localStorage BEFORE clear:', beforeClear);

                // Clear via SelectionsManager
                if (typeof SelectionsManager !== 'undefined') {
                    SelectionsManager.clearAll();
                    console.log('SelectionsManager.clearAll() called');
                } else {
                    // Fallback: clear localStorage directly
                    console.warn('SelectionsManager not found, clearing localStorage directly');
                    localStorage.removeItem('cfk_selections');
                }

                // Check localStorage after clear
                const afterClear = localStorage.getItem('cfk_selections');
                console.log('localStorage AFTER clear:', afterClear);

                // Force immediate UI update
                this.selections = [];
                this.selectionCount = 0;
                console.log('After clear - selectionCount:', this.selectionCount);
                console.log('After clear - selections:', this.selections);
                console.log('=== CLEAR COMPLETE ===');

                if (typeof window.showNotification === 'function') {
                    window.showNotification('All selections cleared', 'info');
                }
            }
        }
    }
}
</script>

<style>
/* My Sponsorships Page Styles */
.my-sponsorships-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--spacing-xl);
}

/* Section Cards */
.section-card {
    background: var(--color-white);
    border: 2px solid var(--color-border-lighter);
    border-radius: var(--radius-lg);
    padding: var(--spacing-2xl);
    margin-bottom: var(--spacing-2xl);
}

.section-card.highlighted {
    border-color: var(--color-primary);
    background: linear-gradient(to bottom, #f8fdf9 0%, var(--color-white) 100%);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-md);
}

.section-header h2 {
    margin: 0;
    color: var(--color-primary);
    font-size: var(--font-size-xl);
}

.selection-count-badge {
    background: var(--color-primary);
    color: var(--color-white);
    padding: var(--spacing-xs) var(--spacing-md);
    border-radius: var(--radius-full);
    font-weight: 700;
    font-size: var(--font-size-sm);
}

.section-description {
    color: var(--color-text-secondary);
    margin-bottom: var(--spacing-xl);
}

/* Compact Selection Cards */
.selections-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-xl);
}

.selection-card-compact {
    background: var(--color-white);
    border: 2px solid var(--color-border-lighter);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    transition: all var(--transition-fast);
}

.selection-card-compact:hover {
    border-color: var(--color-primary);
    box-shadow: var(--shadow-sm);
}

.selection-card-compact .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-xs);
}

.selection-card-compact .card-header strong {
    color: var(--color-primary);
    font-size: var(--font-size-lg);
}

.btn-remove-small {
    background: none;
    border: none;
    color: var(--color-text-tertiary);
    font-size: 1.2rem;
    cursor: pointer;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-sm);
    transition: all var(--transition-fast);
}

.btn-remove-small:hover {
    background: var(--color-danger);
    color: var(--color-white);
}

.selection-card-compact .card-details {
    color: var(--color-text-secondary);
    font-size: var(--font-size-sm);
}

/* Section Actions */
.section-actions {
    display: flex;
    justify-content: space-between;
    gap: var(--spacing-md);
    padding-top: var(--spacing-lg);
    border-top: 2px solid var(--color-border-lighter);
}

/* Info Box */
.info-box {
    background: var(--color-bg-secondary);
    border-radius: var(--radius-md);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.info-box h4 {
    margin: 0 0 var(--spacing-md) 0;
    color: var(--color-primary);
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0 0 var(--spacing-md) 0;
}

.feature-list li {
    padding: var(--spacing-xs) 0;
    color: var(--color-text-secondary);
}

.security-note {
    background: #fffbea;
    border-left: 4px solid #f5b800;
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-sm);
    color: #856404;
}

/* Lookup Form */
.lookup-form-container {
    max-width: 600px;
    margin: 0 auto;
}

/* Mobile: Use full width */
@media (max-width: 768px) {
    .lookup-form-container {
        max-width: 100%;
        padding: 0;
    }

    .lookup-form {
        padding: var(--spacing-lg);
    }

    .info-box {
        padding: var(--spacing-md);
    }
}

.lookup-form {
    background: var(--color-bg-secondary);
    border-radius: var(--radius-md);
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-xl);
}

.form-group {
    margin-bottom: var(--spacing-lg);
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: var(--spacing-xs);
    color: var(--color-text-primary);
}

.form-input {
    width: 100%;
    padding: var(--spacing-sm) var(--spacing-md);
    border: 2px solid var(--color-border-lighter);
    border-radius: var(--radius-md);
    font-size: var(--font-size-md);
    transition: border-color var(--transition-fast);
}

.form-input:focus {
    outline: none;
    border-color: var(--color-primary);
}

.form-help {
    display: block;
    color: var(--color-text-tertiary);
    font-size: var(--font-size-sm);
    margin-top: var(--spacing-xs);
}

/* Help Section */
.help-section {
    text-align: center;
    padding: var(--spacing-lg);
    color: var(--color-text-secondary);
    font-size: var(--font-size-sm);
}

.help-section h4 {
    margin: 0 0 var(--spacing-md) 0;
    color: var(--color-primary);
}

.help-section p {
    margin: var(--spacing-sm) 0;
}

.help-box {
    background: var(--color-bg-primary);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    margin-top: var(--spacing-md);
}

.help-box ul {
    margin: var(--spacing-sm) 0;
    padding-left: var(--spacing-xl);
}

.help-box li {
    margin: var(--spacing-xs) 0;
}

/* Sponsorships Found Section */
.sponsorships-found {
    margin-top: var(--spacing-xl);
}

.sponsored-children-list {
    margin: var(--spacing-xl) 0;
}

.sponsored-children-list h3 {
    color: var(--color-primary);
    margin-bottom: var(--spacing-lg);
}

.sponsored-child-card {
    background: var(--color-white);
    border: 2px solid var(--color-border-lighter);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
}

.child-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-md);
    padding-bottom: var(--spacing-md);
    border-bottom: 2px solid var(--color-primary);
}

.child-card-header h4 {
    margin: 0;
    color: var(--color-primary);
    font-size: var(--font-size-xl);
}

.status-badge {
    padding: var(--spacing-xs) var(--spacing-md);
    border-radius: var(--radius-full);
    font-size: var(--font-size-sm);
    font-weight: 700;
}

.status-badge.status-confirmed {
    background: var(--color-success);
    color: var(--color-white);
}

.child-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.child-info-grid .info-item {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.child-info-grid .info-item strong {
    color: var(--color-text-tertiary);
    font-size: var(--font-size-sm);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.child-wishes {
    background: var(--color-bg-secondary);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.child-wishes strong {
    color: var(--color-primary);
}

.child-wishes p {
    margin: var(--spacing-xs) 0 0 0;
    color: var(--color-text-secondary);
}

.child-sizes {
    display: flex;
    gap: var(--spacing-lg);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--color-border-lighter);
    font-size: var(--font-size-sm);
}

.child-sizes strong {
    color: var(--color-primary);
}

.sponsorship-actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-md);
    margin-top: var(--spacing-xl);
    padding-top: var(--spacing-xl);
    border-top: 2px solid var(--color-border-lighter);
}

.sponsorship-actions .alert {
    flex: 1 1 100%;
}

.inline-form {
    display: inline;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .my-sponsorships-page {
        padding: var(--spacing-md);
    }

    .selections-grid {
        grid-template-columns: 1fr;
    }

    .section-actions {
        flex-direction: column;
    }

    .section-actions .btn {
        width: 100%;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-sm);
    }

    .child-info-grid {
        grid-template-columns: 1fr;
    }

    .sponsorship-actions {
        flex-direction: column;
    }

    .sponsorship-actions .btn,
    .sponsorship-actions button {
        width: 100%;
    }

    .child-sizes {
        flex-direction: column;
        gap: var(--spacing-sm);
    }
}
</style>
