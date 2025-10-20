<?php
/**
 * Sponsor Lookup Portal - Email-Based Access
 * Allows sponsors to view their sponsorships by entering their email
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Use namespaced classes
use CFK\Sponsorship\Manager as SponsorshipManager;
use CFK\Email\Manager as EmailManager;

$errors = [];
$success = false;
$emailSent = false;

// Handle form submission
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
            $sponsorships = SponsorshipManager::getSponsorshipsByEmail($email);

            if ($sponsorships === []) {
                $errors[] = 'No sponsorships found for this email address. Please check your email or contact us for assistance.';
            } else {
                // Generate verification token and send email
                $result = SponsorshipManager::sendPortalAccessEmail($email);

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
?>

<div class="lookup-page">
    <?php
    // Page header component
    $title = '🔍 Access Your Sponsorships';
    $description = 'Enter your email to view and manage your Christmas for Kids sponsorships';
    require_once __DIR__ . '/../includes/components/page_header.php';
    ?>

    <div class="lookup-container">
        <?php if ($emailSent): ?>
            <!-- Success Message -->
            <div class="alert alert-success">
                <h2>✉️ Check Your Email!</h2>
                <p><strong>We've sent you a secure access link.</strong></p>
                <p>Click the link in your email to access your sponsorship portal. The link will expire in 30 minutes.</p>
                <p><strong>Email not received?</strong></p>
                <ul>
                    <li>Check your spam/junk folder</li>
                    <li>Make sure you entered the correct email address</li>
                    <li>Wait a few minutes and try again</li>
                    <li>Contact us at <a href="mailto:<?php echo config('admin_email'); ?>"><?php echo config('admin_email'); ?></a> if problems persist</li>
                </ul>
            </div>
        <?php else: ?>
            <!-- Lookup Form -->
            <div class="lookup-form-container">
                <div class="info-section">
                    <h3>What can you do in the portal?</h3>
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

                <form method="POST" action="" class="lookup-form" id="lookupForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                    <?php if ($errors !== []): ?>
                        <div class="alert alert-error" role="alert" aria-live="polite">
                            <strong>Error:</strong>
                            <ul id="form-errors">
                                <?php foreach ($errors as $error): ?>
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
                               aria-describedby="email-help<?php echo $errors === [] ? '' : ' form-errors'; ?>"
                               <?php echo $errors === [] ? '' : 'aria-invalid="true"'; ?>
                               autocomplete="email">
                        <div id="email-help" class="form-help">Enter the email address you used when sponsoring</div>
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


