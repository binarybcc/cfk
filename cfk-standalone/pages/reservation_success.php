<?php

/**
 * Sponsorship Success Page
 * Shows confirmation after immediate sponsorship
 * v1.5 - Instant Confirmation System
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

$pageTitle = 'Sponsorship Confirmed!';
?>

<div class="reservation-success-page" x-data="sponsorshipSuccessApp()">
    <!-- Success Content -->
    <div class="success-container">
        <!-- Success Header -->
        <div class="success-header">
            <div class="success-icon">✓</div>
            <h1>Sponsorship Confirmed!</h1>
            <p class="success-subtitle">
                Thank you for sponsoring <strong x-text="childrenCount"></strong>
                <span x-text="childrenCount === 1 ? 'child' : 'children'"></span> this Christmas!
            </p>
        </div>

        <!-- Email Confirmation Card -->
        <div class="token-card">
            <h2>📧 Confirmation Email</h2>
            <p class="email-info">
                We've sent (or will send) confirmation details to:
                <strong x-text="sponsorEmail"></strong>
            </p>
            <p class="token-help">
                Need to review your sponsorships later? Visit the
                <a href="<?php echo baseUrl('?page=my_sponsorships'); ?>">My Sponsorships</a>
                page and enter your email address.
            </p>
        </div>

        <!-- Next Steps Card -->
        <div class="next-steps-card">
            <h2>📋 What Happens Next?</h2>
            <ol class="steps-list">
                <li>
                    <strong>Check Your Email</strong>
                    <p>We've sent (or will send) a detailed confirmation email to <strong x-text="sponsorEmail"></strong> with all child information.</p>
                </li>
                <li>
                    <strong>Shop for Gifts</strong>
                    <p>Purchase gifts based on each child's wishes and sizes. Details are in your confirmation email.</p>
                </li>
                <li>
                    <strong>Deliver or Ship Gifts</strong>
                    <p>Follow the instructions in your confirmation email for gift delivery.</p>
                </li>
                <li>
                    <strong>Make a Difference</strong>
                    <p>Your generosity will bring joy to <span x-text="childrenCount"></span>
                    <span x-text="childrenCount === 1 ? 'child' : 'children'"></span> this Christmas!</p>
                </li>
            </ol>
        </div>

        <!-- Action Buttons -->
        <div class="success-actions">
            <a href="<?php echo baseUrl('?page=home'); ?>" class="btn btn-primary">
                ← Return Home
            </a>
            <a href="<?php echo baseUrl('?page=my_sponsorships'); ?>" class="btn btn-outline">
                View My Sponsorships
            </a>
        </div>
    </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
function sponsorshipSuccessApp() {
    return {
        sponsorEmail: '',
        childrenCount: 0,
        loaded: false,

        init() {
            // Get confirmation data from sessionStorage
            const confirmationData = sessionStorage.getItem('cfk_sponsorship_confirmation');

            if (confirmationData) {
                try {
                    const data = JSON.parse(confirmationData);
                    this.sponsorEmail = data.sponsor_email || '';
                    this.childrenCount = data.children_count || 0;

                    // Clear confirmation data from sessionStorage
                    sessionStorage.removeItem('cfk_sponsorship_confirmation');
                } catch (error) {
                    console.error('Failed to parse confirmation data:', error);
                }
            }

            this.loaded = true;
        }
    }
}
</script>

<style>
/* Reservation Success Page Styles */
.reservation-success-page {
    max-width: 900px;
    margin: 0 auto;
    padding: var(--spacing-xl);
}

.loading-state,
.error-state {
    text-align: center;
    padding: var(--spacing-4xl);
}

.error-state h2 {
    color: var(--color-danger);
    margin-bottom: var(--spacing-md);
}

/* Success Container */
.success-container {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Success Header - Reduced for better content visibility */
.success-header {
    text-align: center;
    padding: 1.5rem 1.25rem;
    background: linear-gradient(135deg, #2c5530 0%, #3a6f3f 100%);
    color: var(--color-white);
    border-radius: var(--radius-xl);
    margin-bottom: var(--spacing-lg);
}

.success-icon {
    font-size: 3rem;
    margin-bottom: var(--spacing-sm);
    animation: scaleIn 0.6s ease-out;
}

@keyframes scaleIn {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.success-header h1 {
    margin: 0 0 var(--spacing-sm) 0;
    font-size: var(--font-size-2xl);
    color: var(--color-white) !important;
}

.success-subtitle {
    font-size: var(--font-size-base);
    opacity: 0.95;
}

/* Token Card */
.token-card {
    background: #fffbea;
    border: 3px solid #f5b800;
    border-radius: var(--radius-lg);
    padding: var(--spacing-2xl);
    margin-bottom: var(--spacing-xl);
    text-align: center;
}

.token-card h2 {
    margin: 0 0 var(--spacing-md) 0;
    color: #856404;
}

.token-display {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-md);
    background: var(--color-white);
    padding: var(--spacing-lg);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-md);
}

.token-display code {
    font-family: 'Courier New', monospace;
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--color-primary);
    word-break: break-all;
}

.btn-copy {
    background: var(--color-primary);
    color: var(--color-white);
    border: none;
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--radius-md);
    cursor: pointer;
    font-weight: 600;
    transition: all var(--transition-fast);
    white-space: nowrap;
}

.btn-copy:hover {
    background: var(--color-primary-dark);
}

.token-help {
    color: #856404;
    margin: 0;
    font-size: var(--font-size-sm);
}

/* Details Card */
.details-card {
    background: var(--color-white);
    border: 2px solid var(--color-border-lighter);
    border-radius: var(--radius-lg);
    padding: var(--spacing-2xl);
    margin-bottom: var(--spacing-xl);
}

.details-card h2 {
    margin: 0 0 var(--spacing-xl) 0;
    color: var(--color-primary);
}

.detail-section {
    margin-bottom: var(--spacing-2xl);
}

.detail-section:last-child {
    margin-bottom: 0;
}

.detail-section h3 {
    margin: 0 0 var(--spacing-md) 0;
    color: var(--color-text-primary);
    font-size: var(--font-size-lg);
    border-bottom: 2px solid var(--color-border-lighter);
    padding-bottom: var(--spacing-sm);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-md);
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.info-item strong {
    color: var(--color-primary);
    font-size: var(--font-size-sm);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Children List */
.children-list {
    display: grid;
    gap: var(--spacing-md);
}

.child-item {
    background: var(--color-bg-secondary);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    border: 1px solid var(--color-border-lighter);
}

.child-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-sm);
    padding-bottom: var(--spacing-sm);
    border-bottom: 2px solid var(--color-primary);
}

.child-header strong {
    color: var(--color-primary);
    font-size: var(--font-size-lg);
}

.child-meta {
    color: var(--color-text-secondary);
    font-size: var(--font-size-sm);
}

.child-details {
    font-size: var(--font-size-sm);
}

.child-details p {
    margin: var(--spacing-xs) 0;
}

/* Next Steps Card */
.next-steps-card {
    background: var(--color-bg-secondary);
    border-radius: var(--radius-lg);
    padding: var(--spacing-2xl);
    margin-bottom: var(--spacing-xl);
}

.next-steps-card h2 {
    margin: 0 0 var(--spacing-lg) 0;
    color: var(--color-primary);
}

.steps-list {
    list-style: none;
    counter-reset: steps;
    padding: 0;
    margin: 0;
}

.steps-list li {
    counter-increment: steps;
    position: relative;
    padding-left: var(--spacing-3xl);
    margin-bottom: var(--spacing-xl);
}

.steps-list li:last-child {
    margin-bottom: 0;
}

.steps-list li::before {
    content: counter(steps);
    position: absolute;
    left: 0;
    top: 0;
    background: var(--color-primary);
    color: var(--color-white);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

.steps-list strong {
    display: block;
    margin-bottom: var(--spacing-xs);
    color: var(--color-text-primary);
}

.steps-list p {
    margin: 0;
    color: var(--color-text-secondary);
}

/* Success Actions */
.success-actions {
    display: flex;
    justify-content: center;
    gap: var(--spacing-md);
    padding: var(--spacing-xl) 0;
}

/* Print Styles */
@media print {
    .success-actions,
    .btn-copy {
        display: none;
    }

    .success-header {
        background: var(--color-primary);
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }

    .token-display {
        flex-direction: column;
    }

    .token-display code {
        font-size: var(--font-size-sm);
    }

    .success-actions {
        flex-direction: column;
    }

    .success-actions .btn {
        width: 100%;
    }
}
</style>
