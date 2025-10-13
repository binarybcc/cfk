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

require_once __DIR__ . '/../includes/sponsorship_manager.php';
require_once __DIR__ . '/../includes/email_manager.php';

$errors = [];
$success = false;
$emailSent = false;

// Handle lookup form submission
if ($_POST && isset($_POST['lookup_email'])) {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        $email = sanitizeEmail($_POST['sponsor_email'] ?? '');

        if (empty($email)) {
            $errors[] = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            // Check if email has any sponsorships
            $sponsorships = CFK_Sponsorship_Manager::getSponsorshipsByEmail($email);

            if (empty($sponsorships)) {
                $errors[] = 'No sponsorships found for this email address. Please check your email or contact us for assistance.';
            } else {
                // Generate verification token and send email
                $result = CFK_Sponsorship_Manager::sendPortalAccessEmail($email);

                if ($result['success']) {
                    $emailSent = true;
                    $success = true;
                } else {
                    $errors[] = $result['message'];
                }
            }
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
            Manage your pending selections and access your confirmed sponsorships
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
                            <span x-text="child.age + ' years'"></span> •
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
                <h2>🔍 Look Up Your Confirmed Sponsorships</h2>
            </div>
            <p class="section-description">
                Already sponsored children? Enter your email to access your sponsorship portal.
            </p>

            <?php if ($emailSent): ?>
                <!-- Success Message -->
                <div class="alert alert-success">
                    <h3>✉️ Check Your Email!</h3>
                    <p><strong>We've sent you a secure access link.</strong></p>
                    <p>Click the link in your email to access your sponsorship portal. The link will expire in 30 minutes.</p>
                    <div class="help-box">
                        <strong>Email not received?</strong>
                        <ul>
                            <li>Check your spam/junk folder</li>
                            <li>Make sure you entered the correct email address</li>
                            <li>Wait a few minutes and try again</li>
                            <li>Contact us at <a href="mailto:<?php echo config('admin_email'); ?>"><?php echo config('admin_email'); ?></a> if problems persist</li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <!-- Lookup Form -->
                <div class="lookup-form-container">
                    <div class="info-box">
                        <h4>What's in the portal?</h4>
                        <ul class="feature-list">
                            <li>✓ View all your sponsored children with complete details</li>
                            <li>✓ See which children are in the same family</li>
                            <li>✓ Add more children to your sponsorship</li>
                            <li>✓ Download shopping lists for gift buying</li>
                            <li>✓ View sponsorship status (pending/confirmed/completed)</li>
                        </ul>
                        <div class="security-note">
                            <strong>🔒 Secure Access:</strong> No password needed! We'll send you a secure, one-time access link via email.
                        </div>
                    </div>

                    <form method="POST" action="" class="lookup-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-error">
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo sanitizeString($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="sponsor_email" class="form-label">Your Email Address</label>
                            <input type="email"
                                   id="sponsor_email"
                                   name="sponsor_email"
                                   class="form-input"
                                   placeholder="example@email.com"
                                   value="<?php echo sanitizeString($_POST['sponsor_email'] ?? ''); ?>"
                                   required
                                   autocomplete="email">
                            <div class="form-help">Enter the email address you used when sponsoring</div>
                        </div>

                        <button type="submit" name="lookup_email" class="btn btn-large btn-primary">
                            Send Access Link
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

<script>
function mySponsorshipsApp() {
    return {
        selections: [],
        selectionCount: 0,

        init() {
            this.loadSelections();

            // Listen for selection changes
            window.addEventListener('selectionsUpdated', () => {
                this.loadSelections();
            });
        },

        loadSelections() {
            this.selections = SelectionsManager.getSelections();
            this.selectionCount = this.selections.length;
        },

        removeSelection(childId) {
            if (confirm('Remove this child from your selections?')) {
                SelectionsManager.removeChild(childId);
                this.loadSelections();
                window.showNotification('Child removed from selections', 'info');
            }
        },

        clearAllSelections() {
            if (confirm(`Remove all ${this.selectionCount} children from your selections?`)) {
                SelectionsManager.clearAll();
                this.loadSelections();
                window.showNotification('All selections cleared', 'info');
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
}
</style>
