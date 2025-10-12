<?php
/**
 * Reservation Success Page
 * Shows reservation token and next steps after successful reservation
 * v1.5 - Reservation System
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

$pageTitle = 'Reservation Confirmed!';
?>

<div class="reservation-success-page" x-data="reservationSuccessApp()">
    <!-- Loading State -->
    <template x-if="!loaded">
        <div class="loading-state">
            <p>Loading your reservation details...</p>
        </div>
    </template>

    <!-- Success Content -->
    <template x-if="loaded && reservation">
        <div class="success-container">
            <!-- Success Header -->
            <div class="success-header">
                <div class="success-icon">✓</div>
                <h1>Reservation Confirmed!</h1>
                <p class="success-subtitle">
                    Thank you for sponsoring <strong x-text="reservation.total_children"></strong>
                    <span x-text="reservation.total_children === 1 ? 'child' : 'children'"></span> this Christmas!
                </p>
            </div>

            <!-- Reservation Token Card -->
            <div class="token-card">
                <h2>Your Reservation Token</h2>
                <div class="token-display">
                    <code x-text="reservationToken"></code>
                    <button @click="copyToken()" class="btn-copy" title="Copy to clipboard">
                        <span x-text="copied ? '✓ Copied!' : '📋 Copy'"></span>
                    </button>
                </div>
                <p class="token-help">
                    Save this token! You'll need it to track your reservation or make changes.
                </p>
            </div>

            <!-- Reservation Details -->
            <div class="details-card">
                <h2>Reservation Details</h2>

                <div class="detail-section">
                    <h3>Sponsor Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Name:</strong>
                            <span x-text="reservation.sponsor_name"></span>
                        </div>
                        <div class="info-item">
                            <strong>Email:</strong>
                            <span x-text="reservation.sponsor_email"></span>
                        </div>
                        <div class="info-item" x-show="reservation.sponsor_phone">
                            <strong>Phone:</strong>
                            <span x-text="reservation.sponsor_phone"></span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Important Dates</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Created:</strong>
                            <span x-text="formatDate(reservation.created_at)"></span>
                        </div>
                        <div class="info-item">
                            <strong>Expires:</strong>
                            <span x-text="formatDate(reservation.expires_at)"></span>
                        </div>
                        <div class="info-item">
                            <strong>Time Remaining:</strong>
                            <span x-text="getTimeRemaining(reservation.expires_at)"></span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Selected Children (<span x-text="reservation.children?.length || 0"></span>)</h3>
                    <div class="children-list">
                        <template x-for="child in reservation.children" :key="child.id">
                            <div class="child-item">
                                <div class="child-header">
                                    <strong x-text="child.display_id"></strong>
                                    <span class="child-meta">
                                        <span x-text="child.age"></span> years,
                                        <span x-text="child.gender === 'M' ? 'Boy' : 'Girl'"></span>
                                    </span>
                                </div>
                                <div class="child-details">
                                    <p x-show="child.wishes"><strong>Wishes:</strong> <span x-text="child.wishes"></span></p>
                                    <p x-show="child.clothing_sizes"><strong>Clothing:</strong> <span x-text="child.clothing_sizes"></span></p>
                                    <p x-show="child.shoe_size"><strong>Shoes:</strong> <span x-text="child.shoe_size"></span></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Next Steps Card -->
            <div class="next-steps-card">
                <h2>📋 What Happens Next?</h2>
                <ol class="steps-list">
                    <li>
                        <strong>Check Your Email</strong>
                        <p>We've sent a detailed confirmation email to <strong x-text="reservation.sponsor_email"></strong> with all child information.</p>
                    </li>
                    <li>
                        <strong>Shop for Gifts</strong>
                        <p>You have 48 hours to purchase gifts based on each child's wishes and sizes listed above.</p>
                    </li>
                    <li>
                        <strong>Deliver or Ship Gifts</strong>
                        <p>Follow the instructions in your confirmation email for gift delivery.</p>
                    </li>
                    <li>
                        <strong>Make a Difference</strong>
                        <p>Your generosity will bring joy to <span x-text="reservation.total_children"></span>
                        <span x-text="reservation.total_children === 1 ? 'child' : 'children'"></span> this Christmas!</p>
                    </li>
                </ol>
            </div>

            <!-- Action Buttons -->
            <div class="success-actions">
                <a href="<?php echo baseUrl('?page=home'); ?>" class="btn btn-primary">
                    ← Return Home
                </a>
                <button @click="printPage()" class="btn btn-outline">
                    🖨️ Print Details
                </button>
            </div>
        </div>
    </template>

    <!-- Error State -->
    <template x-if="loaded && !reservation">
        <div class="error-state">
            <h2>Reservation Not Found</h2>
            <p>We couldn't find your reservation details. Please check your email for confirmation.</p>
            <a href="<?php echo baseUrl('?page=home'); ?>" class="btn btn-primary">Return Home</a>
        </div>
    </template>
</div>

<script>
function reservationSuccessApp() {
    return {
        reservationToken: '',
        reservation: null,
        loaded: false,
        copied: false,

        async init() {
            // Get token from sessionStorage
            this.reservationToken = sessionStorage.getItem('cfk_reservation_token');

            if (!this.reservationToken) {
                this.loaded = true;
                return;
            }

            // Fetch reservation details
            await this.loadReservation();

            // Clear token from sessionStorage
            sessionStorage.removeItem('cfk_reservation_token');
        },

        async loadReservation() {
            try {
                const response = await fetch(`<?php echo baseUrl('api/get_reservation.php'); ?>?token=${this.reservationToken}`);
                const result = await response.json();

                if (result.success) {
                    this.reservation = result.reservation;
                }
            } catch (error) {
                console.error('Failed to load reservation:', error);
            } finally {
                this.loaded = true;
            }
        },

        copyToken() {
            navigator.clipboard.writeText(this.reservationToken).then(() => {
                this.copied = true;
                setTimeout(() => {
                    this.copied = false;
                }, 2000);
            });
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        },

        getTimeRemaining(expiresAt) {
            const now = new Date();
            const expires = new Date(expiresAt);
            const diff = expires - now;

            if (diff <= 0) return 'Expired';

            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            return `${hours} hours, ${minutes} minutes`;
        },

        printPage() {
            window.print();
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

/* Success Header */
.success-header {
    text-align: center;
    padding: var(--spacing-3xl) var(--spacing-xl);
    background: linear-gradient(135deg, #2c5530 0%, #3a6f3f 100%);
    color: var(--color-white);
    border-radius: var(--radius-xl);
    margin-bottom: var(--spacing-xl);
}

.success-icon {
    font-size: 5rem;
    margin-bottom: var(--spacing-md);
    animation: scaleIn 0.6s ease-out;
}

@keyframes scaleIn {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.success-header h1 {
    margin: 0 0 var(--spacing-md) 0;
    font-size: var(--font-size-3xl);
}

.success-subtitle {
    font-size: var(--font-size-lg);
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
