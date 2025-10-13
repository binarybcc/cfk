<?php
/**
 * Reservation Review Page
 * Step 2: Review all information before final confirmation
 * v1.5 - Reservation System
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

$pageTitle = 'Review Your Sponsorship';
?>

<div class="reservation-review-page" x-data="reservationReviewApp()">
    <div class="page-header">
        <h1>Review Your Sponsorship</h1>
        <p class="page-description">
            Please review all details before submitting your reservation.
        </p>
    </div>

    <!-- Loading State -->
    <template x-if="!dataLoaded">
        <div class="loading-state">
            <p>Loading your information...</p>
        </div>
    </template>

    <!-- Review Content -->
    <template x-if="dataLoaded">
        <div class="review-container">
            <!-- Sticky Action Bar at Top -->
            <div class="sticky-action-bar">
                <div class="action-bar-content">
                    <div class="summary-info">
                        <strong class="summary-count">
                            <span x-text="selections.length"></span>
                            <span x-text="selections.length === 1 ? 'Child' : 'Children'"></span> Selected
                        </strong>
                        <p class="summary-instruction">👇 Review details below, then confirm your reservation</p>
                    </div>
                    <button
                        @click="confirmReservation()"
                        class="btn btn-success btn-large btn-prominent"
                        :disabled="isSubmitting">
                        <span x-show="!isSubmitting">✓ Confirm Reservation</span>
                        <span x-show="isSubmitting">Creating Reservation...</span>
                    </button>
                </div>
            </div>

            <!-- Children Summary -->
            <div class="review-section">
                <h2>Selected Children (<span x-text="selections.length"></span>)</h2>
                <div class="children-review-grid">
                    <template x-for="child in selections" :key="child.id">
                        <div class="child-review-card">
                            <div class="card-header">
                                <strong x-text="child.display_id"></strong>
                                <span class="age-gender">
                                    <span x-text="child.age"></span> years,
                                    <span x-text="child.gender === 'M' ? 'Boy' : 'Girl'"></span>
                                </span>
                            </div>
                            <div class="card-details">
                                <div x-show="child.grade"><strong>Grade:</strong> <span x-text="child.grade"></span></div>
                                <div x-show="child.school"><strong>School:</strong> <span x-text="child.school"></span></div>
                                <div x-show="child.interests"><strong>Interests:</strong> <span x-text="child.interests"></span></div>
                                <div x-show="child.wishes"><strong>Wishes:</strong> <span x-text="child.wishes"></span></div>
                                <div x-show="child.clothing_sizes"><strong>Clothing:</strong> <span x-text="child.clothing_sizes"></span></div>
                                <div x-show="child.shoe_size"><strong>Shoes:</strong> <span x-text="child.shoe_size"></span></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Sponsor Information -->
            <div class="review-section">
                <h2>Your Contact Information</h2>
                <div class="sponsor-info-card">
                    <div class="info-row">
                        <strong>Name:</strong>
                        <span x-text="sponsorData.name"></span>
                    </div>
                    <div class="info-row">
                        <strong>Email:</strong>
                        <span x-text="sponsorData.email"></span>
                    </div>
                    <div class="info-row" x-show="sponsorData.phone">
                        <strong>Phone:</strong>
                        <span x-text="sponsorData.phone"></span>
                    </div>
                    <div class="info-row" x-show="sponsorData.address">
                        <strong>Address:</strong>
                        <span x-text="sponsorData.address" style="white-space: pre-line;"></span>
                    </div>
                </div>
                <button @click="editInfo()" class="btn btn-outline btn-small">
                    ✏️ Edit Information
                </button>
            </div>

            <!-- Important Notice -->
            <div class="review-section">
                <div class="notice-box">
                    <h3>📋 Important Information</h3>
                    <ul>
                        <li>Your reservation will be valid for <strong>48 hours</strong></li>
                        <li>You will receive a confirmation email with full child details</li>
                        <li>You'll have a unique reservation token to track your sponsorship</li>
                        <li>After 48 hours, unreserved children will become available again</li>
                    </ul>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="review-actions">
                <button @click="goBack()" class="btn btn-secondary" :disabled="isSubmitting">
                    ← Back to Edit
                </button>
                <button
                    @click="confirmReservation()"
                    class="btn btn-success btn-large"
                    :disabled="isSubmitting">
                    <span x-show="!isSubmitting">✓ Confirm Reservation</span>
                    <span x-show="isSubmitting">Creating Reservation...</span>
                </button>
            </div>
        </div>
    </template>
</div>

<script>
function reservationReviewApp() {
    return {
        selections: [],
        sponsorData: {},
        dataLoaded: false,
        isSubmitting: false,

        init() {
            this.loadData();
        },

        loadData() {
            // Load selections from localStorage
            this.selections = SelectionsManager.getSelections();

            // Load sponsor data from sessionStorage
            const storedData = sessionStorage.getItem('cfk_sponsor_form');
            if (storedData) {
                this.sponsorData = JSON.parse(storedData);
            }

            // Validate we have data
            if (this.selections.length === 0 || !this.sponsorData.name) {
                alert('Missing required information. Redirecting to form...');
                window.location.href = '<?php echo baseUrl('?page=confirm_sponsorship'); ?>';
                return;
            }

            this.dataLoaded = true;
        },

        editInfo() {
            window.location.href = '<?php echo baseUrl('?page=confirm_sponsorship'); ?>';
        },

        goBack() {
            window.location.href = '<?php echo baseUrl('?page=confirm_sponsorship'); ?>';
        },

        async confirmReservation() {
            if (this.isSubmitting) return;

            this.isSubmitting = true;

            try {
                // Prepare data for API
                const payload = {
                    sponsor: this.sponsorData,
                    children_ids: this.selections.map(c => c.id)
                };

                // Call API to create reservation
                const response = await fetch('api/create_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    // Clear selections from localStorage
                    SelectionsManager.clearAll();

                    // Clear sponsor data from sessionStorage
                    sessionStorage.removeItem('cfk_sponsor_form');

                    // Store reservation token in sessionStorage for success page
                    sessionStorage.setItem('cfk_reservation_token', result.token);

                    // Redirect to success page
                    window.location.href = '<?php echo baseUrl('?page=reservation_success'); ?>';
                } else {
                    alert('Error: ' + (result.message || 'Failed to create reservation'));
                    this.isSubmitting = false;
                }

            } catch (error) {
                console.error('Reservation error:', error);
                alert('An error occurred while creating your reservation. Please try again.');
                this.isSubmitting = false;
            }
        }
    }
}
</script>

<style>
/* Reservation Review Page Styles */
.reservation-review-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--spacing-xl);
}

.loading-state {
    text-align: center;
    padding: var(--spacing-4xl);
    color: var(--color-text-secondary);
}

.review-container {
    margin-top: var(--spacing-xl);
}

/* Sticky Action Bar */
.sticky-action-bar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: linear-gradient(135deg, #2c5f2d 0%, #3a7d3b 100%);
    border-radius: var(--radius-lg);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    margin-bottom: var(--spacing-xl);
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.action-bar-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-lg) var(--spacing-xl);
    gap: var(--spacing-xl);
}

.summary-info {
    flex: 1;
}

.summary-count {
    color: white;
    font-size: var(--font-size-2xl);
    display: block;
    margin-bottom: var(--spacing-xs);
}

.summary-instruction {
    color: rgba(255, 255, 255, 0.9);
    font-size: var(--font-size-md);
    margin: 0;
}

.btn-prominent {
    font-size: var(--font-size-lg);
    padding: var(--spacing-md) var(--spacing-2xl);
    background: white;
    color: var(--color-primary);
    font-weight: 700;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    min-width: 250px;
    white-space: nowrap;
}

.btn-prominent:hover:not(:disabled) {
    background: #f8f8f8;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
}

.btn-prominent:disabled {
    background: #e0e0e0;
    color: #999;
}

.review-section {
    background: var(--color-white);
    border: 2px solid var(--color-border-lighter);
    border-radius: var(--radius-lg);
    padding: var(--spacing-2xl);
    margin-bottom: var(--spacing-xl);
}

.review-section h2 {
    margin: 0 0 var(--spacing-lg) 0;
    color: var(--color-primary);
    font-size: var(--font-size-xl);
}

/* Children Review Grid */
.children-review-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.child-review-card {
    background: var(--color-bg-secondary);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    border: 1px solid var(--color-border-lighter);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-sm);
    padding-bottom: var(--spacing-sm);
    border-bottom: 2px solid var(--color-primary);
}

.card-header strong {
    color: var(--color-primary);
    font-size: var(--font-size-lg);
}

.age-gender {
    color: var(--color-text-secondary);
    font-size: var(--font-size-sm);
}

.card-details {
    font-size: var(--font-size-sm);
}

.card-details > div {
    margin-bottom: var(--spacing-xs);
    display: flex;
    gap: var(--spacing-xs);
}

.card-details strong {
    color: var(--color-primary);
    min-width: 80px;
}

/* Sponsor Info Card */
.sponsor-info-card {
    background: var(--color-bg-secondary);
    border-radius: var(--radius-md);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
}

.info-row {
    display: flex;
    gap: var(--spacing-md);
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--color-border-lighter);
}

.info-row:last-child {
    border-bottom: none;
}

.info-row strong {
    color: var(--color-primary);
    min-width: 100px;
}

/* Notice Box */
.notice-box {
    background: #fffbea;
    border: 2px solid #f5b800;
    border-radius: var(--radius-md);
    padding: var(--spacing-lg);
}

.notice-box h3 {
    margin: 0 0 var(--spacing-md) 0;
    color: #856404;
}

.notice-box ul {
    margin: 0;
    padding-left: var(--spacing-xl);
}

.notice-box li {
    margin-bottom: var(--spacing-sm);
    color: #856404;
}

/* Review Actions */
.review-actions {
    display: flex;
    justify-content: space-between;
    gap: var(--spacing-md);
    padding: var(--spacing-xl) 0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .action-bar-content {
        flex-direction: column;
        padding: var(--spacing-md);
        gap: var(--spacing-md);
        text-align: center;
    }

    .summary-count {
        font-size: var(--font-size-xl);
    }

    .summary-instruction {
        font-size: var(--font-size-sm);
    }

    .btn-prominent {
        width: 100%;
        min-width: auto;
    }

    .children-review-grid {
        grid-template-columns: 1fr;
    }

    .review-actions {
        flex-direction: column;
    }

    .review-actions .btn {
        width: 100%;
    }

    .info-row {
        flex-direction: column;
        gap: var(--spacing-xs);
    }
}
</style>
