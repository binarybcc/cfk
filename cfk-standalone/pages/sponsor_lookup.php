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

require_once __DIR__ . '/../includes/sponsorship_manager.php';
require_once __DIR__ . '/../includes/email_manager.php';

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
?>

<div class="lookup-page">
    <div class="page-header">
        <h1>🔍 Access Your Sponsorships</h1>
        <p class="subtitle">Enter your email to view and manage your Christmas for Kids sponsorships</p>
    </div>

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

<style>
.lookup-page {
    max-width: 700px;
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

.subtitle {
    color: #666;
    font-size: 1.1rem;
}

.lookup-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.lookup-form-container {
    padding: 2rem;
}

.info-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.info-section h3 {
    color: #2c5530;
    margin-bottom: 1rem;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0 0 1rem 0;
}

.feature-list li {
    padding: 0.5rem 0;
    color: #333;
}

.security-note {
    background: #e3f2fd;
    padding: 1rem;
    border-radius: 6px;
    margin-top: 1rem;
    border-left: 4px solid #2196f3;
}

.lookup-form {
    margin: 2rem 0;
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

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #2c5530;
}

.form-help {
    font-size: 0.9rem;
    color: #666;
    margin-top: 0.25rem;
}

.btn-large {
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
    margin-top: 1rem;
}

.help-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 2rem;
}

.help-section h4 {
    color: #2c5530;
    margin-bottom: 1rem;
}

.help-section p {
    margin-bottom: 0.5rem;
}

.help-section a {
    color: #2c5530;
    text-decoration: none;
    font-weight: bold;
}

.help-section a:hover {
    text-decoration: underline;
}

.alert {
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-success h2 {
    color: #155724;
    margin-bottom: 1rem;
}

.alert-success ul {
    margin: 1rem 0 0 1.5rem;
}

.alert-success li {
    margin-bottom: 0.5rem;
}

.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert-error ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.alert-error li {
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .lookup-page {
        padding: 1rem;
    }

    .lookup-form-container {
        padding: 1.5rem;
    }
}
</style>

<script>
// Form validation
document.getElementById('lookupForm').addEventListener('submit', function(e) {
    const email = document.getElementById('sponsor_email').value.trim();

    if (!email) {
        alert('Please enter your email address.');
        e.preventDefault();
        return;
    }

    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        e.preventDefault();
        return;
    }
});
</script>
