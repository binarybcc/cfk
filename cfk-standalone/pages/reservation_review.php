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
                                    <span x-text="formatAge(child.age_months)"></span>,
                                    <span x-text="child.gender === 'M' ? 'Boy' : 'Girl'"></span>
                                </span>
                            </div>
                            <div class="card-details">
                                <div x-show="child.grade"><strong>Grade:</strong> <span x-text="child.grade"></span></div>
                                <div x-show="child.school"><strong>School:</strong> <span x-text="child.school"></span></div>
                                <div x-show="child.interests"><strong>Essential Needs:</strong> <span x-text="child.interests"></span></div>
                                <div x-show="child.wishes"><strong>Wishes:</strong> <span x-text="child.wishes"></span></div>
                                <div x-show="child.shirt_size || child.pant_size || child.jacket_size">
                                    <strong>Clothing:</strong>
                                    <span>
                                        <span x-show="child.shirt_size">Shirt: <span x-text="child.shirt_size"></span></span>
                                        <span x-show="child.pant_size"> | Pants: <span x-text="child.pant_size"></span></span>
                                        <span x-show="child.jacket_size"> | Jacket: <span x-text="child.jacket_size"></span></span>
                                    </span>
                                </div>
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

<script nonce="<?php echo $cspNonce; ?>">
// Helper function to format age
function formatAge(ageMonths) {
    if (!ageMonths) return '';
    if (ageMonths < 25) {
        return ageMonths + ' month' + (ageMonths !== 1 ? 's' : '');
    } else if (ageMonths < 36) {
        return '2 years';
    } else {
        const years = Math.floor(ageMonths / 12);
        return years + ' year' + (years !== 1 ? 's' : '');
    }
}

function reservationReviewApp() {
    return {
        selections: [],
        sponsorData: {},
        dataLoaded: false,
        isSubmitting: false,
        formatAge: formatAge, // Make it available in Alpine scope

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

                    // Store confirmation data for success page
                    const confirmationData = {
                        sponsor_email: result.sponsor_email,
                        children_count: result.children_count
                    };
                    sessionStorage.setItem('cfk_sponsorship_confirmation', JSON.stringify(confirmationData));

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

/* Action Bar - Christmas Red emphasis */
.sticky-action-bar {
    position: relative;
    background: linear-gradient(135deg, #c41e3a 0%, #a01829 100%);
    border-radius: var(--radius-lg);
    box-shadow: 0 4px 12px rgba(196, 30, 58, 0.25);
    margin-bottom: var(--spacing-lg);
    border: 2px solid #8b1520;
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
    padding: 1rem 1.25rem;
    gap: var(--spacing-md);
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
    color: rgba(255, 255, 255, 0.95);
    font-size: var(--font-size-md);
    margin: 0;
    font-weight: 500;
}

.btn-prominent {
    font-size: var(--font-size-lg);
    padding: 0.875rem 1.75rem;
    background: white;
    color: #c41e3a;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    min-width: 260px;
    white-space: nowrap;
    border: 2px solid white;
    text-transform: none;
    letter-spacing: 0.3px;
    animation: gentlePulse 3s ease-in-out infinite;
}

@keyframes gentlePulse {
    0%, 100% {
        box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
    }
    50% {
        box-shadow: 0 6px 20px rgba(255, 255, 255, 0.5);
    }
}

.btn-prominent:hover:not(:disabled) {
    background: #f8f8f8;
    color: #a01829;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(255, 255, 255, 0.6);
    animation: none;
}

.btn-prominent:disabled {
    background: #e0e0e0;
    color: #999;
    border-color: #ccc;
    animation: none;
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
