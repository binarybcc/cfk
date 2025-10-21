<?php
/**
 * Confirm Sponsorship - Sponsor Information Form
 * Step 1: Collect sponsor details before creating reservation
 * v1.5 - Reservation System
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

$pageTitle = 'Confirm Your Sponsorship';
?>

<div class="confirm-sponsorship-page" x-data="confirmSponsorshipApp()">
    <div class="page-header">
        <h1>Confirm Your Sponsorship</h1>
        <p class="page-description">
            Please provide your contact information to complete your sponsorship reservation.
        </p>
    </div>

    <!-- No Selections Warning -->
    <template x-if="selectionCount === 0">
        <div class="alert alert-warning">
            <strong>No children selected!</strong>
            <p>You haven't selected any children yet. Please browse our children and add them to your selections first.</p>
            <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-primary">Browse Children</a>
        </div>
    </template>

    <!-- Sponsor Information Form -->
    <template x-if="selectionCount > 0">
        <div class="confirmation-container">
            <!-- Selections Summary -->
            <div class="selections-summary-card">
                <h2>Your Selections</h2>
                <p class="summary-count">
                    <strong x-text="selectionCount"></strong>
                    <span x-text="selectionCount === 1 ? 'child' : 'children'"></span> selected
                </p>
                <ul class="selected-children-list">
                    <template x-for="child in selections" :key="child.id">
                        <li>
                            <strong x-text="child.display_id"></strong>
                            <span class="child-details">
                                - <span x-text="child.age"></span> years old,
                                <span x-text="child.gender === 'M' ? 'Boy' : 'Girl'"></span>
                            </span>
                        </li>
                    </template>
                </ul>
                <a href="<?php echo baseUrl('?page=selections'); ?>" class="btn btn-outline btn-small">
                    ← Review Selections
                </a>
            </div>

            <!-- Sponsor Form -->
            <div class="sponsor-form-card">
                <h2>Your Contact Information</h2>
                <p class="form-intro">We'll use this information to send you confirmation and follow-up details.</p>

                <form @submit.prevent="submitForm()" class="sponsor-form">
                    <!-- Name -->
                    <div class="form-group">
                        <label for="sponsor_name">Full Name <span class="required" aria-label="required">*</span></label>
                        <input
                            type="text"
                            id="sponsor_name"
                            x-model="formData.name"
                            required
                            aria-required="true"
                            placeholder="Enter your full name"
                            :class="errors.name ? 'form-input input-error' : 'form-input'"
                            :aria-invalid="errors.name ? 'true' : 'false'"
                            :aria-describedby="errors.name ? 'name-error' : null">
                        <template x-if="errors.name">
                            <span class="error-message" id="name-error" role="alert" x-text="errors.name"></span>
                        </template>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="sponsor_email">Email Address <span class="required" aria-label="required">*</span></label>
                        <input
                            type="email"
                            id="sponsor_email"
                            x-model="formData.email"
                            required
                            aria-required="true"
                            placeholder="your.email@example.com"
                            :class="errors.email ? 'form-input input-error' : 'form-input'"
                            :aria-invalid="errors.email ? 'true' : 'false'"
                            :aria-describedby="errors.email ? 'email-error' : null">
                        <template x-if="errors.email">
                            <span class="error-message" id="email-error" role="alert" x-text="errors.email"></span>
                        </template>
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label for="sponsor_phone">Phone Number</label>
                        <input
                            type="tel"
                            id="sponsor_phone"
                            x-model="formData.phone"
                            placeholder="(555) 123-4567"
                            class="form-input"
                            :aria-invalid="errors.phone ? 'true' : 'false'"
                            :aria-describedby="errors.phone ? 'phone-error' : null">
                        <template x-if="errors.phone">
                            <span class="error-message" id="phone-error" role="alert" x-text="errors.phone"></span>
                        </template>
                        <small class="form-help">Optional - for follow-up questions only</small>
                    </div>

                    <!-- Address -->
                    <div class="form-group">
                        <label for="sponsor_address">Mailing Address</label>
                        <textarea
                            id="sponsor_address"
                            x-model="formData.address"
                            rows="3"
                            placeholder="Street address, City, State, ZIP"
                            class="form-textarea"></textarea>
                        <small class="form-help">Optional</small>
                    </div>

                    <!-- Terms Acknowledgment -->
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input
                                type="checkbox"
                                x-model="formData.acceptTerms"
                                required>
                            <span>
                                I understand that this reservation is valid for 48 hours.
                                I will receive confirmation details via email.
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-actions">
                        <a href="<?php echo baseUrl('?page=selections'); ?>" class="btn btn-secondary">
                            ← Back to Selections
                        </a>
                        <button
                            type="submit"
                            class="btn btn-success btn-large"
                            :disabled="isSubmitting"
                            x-text="isSubmitting ? 'Creating Reservation...' : 'Review & Confirm'">
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script nonce="<?php echo $cspNonce; ?>">
function confirmSponsorshipApp() {
    return {
        selections: [],
        selectionCount: 0,
        formData: {
            name: '',
            email: '',
            phone: '',
            address: '',
            acceptTerms: false
        },
        errors: {},
        isSubmitting: false,

        init() {
            this.loadSelections();
        },

        loadSelections() {
            this.selections = SelectionsManager.getSelections();
            this.selectionCount = this.selections.length;
        },

        validateForm() {
            this.errors = {};
            let isValid = true;

            // Validate name
            if (!this.formData.name.trim()) {
                this.errors.name = 'Name is required';
                isValid = false;
            }

            // Validate email
            if (!this.formData.email.trim()) {
                this.errors.email = 'Email is required';
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.formData.email)) {
                this.errors.email = 'Please enter a valid email address';
                isValid = false;
            }

            return isValid;
        },

        async submitForm() {
            if (!this.validateForm()) {
                window.showNotification('Please fix the errors in the form', 'warning');
                return;
            }

            if (this.selectionCount === 0) {
                window.showNotification('No children selected', 'warning');
                return;
            }

            this.isSubmitting = true;

            try {
                // Store form data in sessionStorage for the review page
                sessionStorage.setItem('cfk_sponsor_form', JSON.stringify(this.formData));

                // Redirect to review page
                window.location.href = '<?php echo baseUrl('?page=reservation_review'); ?>';

            } catch (error) {
                console.error('Form submission error:', error);
                window.showNotification('An error occurred. Please try again.', 'error');
                this.isSubmitting = false;
            }
        }
    }
}
</script>

<style>
/* Confirm Sponsorship Page Styles */
.confirm-sponsorship-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--spacing-xl);
}

.confirmation-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: var(--spacing-xl);
    margin-top: var(--spacing-xl);
}

/* Selections Summary Card */
.selections-summary-card {
    background: var(--color-bg-secondary);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    height: fit-content;
    position: sticky;
    top: 20px;
}

.selections-summary-card h3 {
    margin: 0 0 var(--spacing-md) 0;
    color: var(--color-primary);
}

.summary-count {
    font-size: var(--font-size-lg);
    margin-bottom: var(--spacing-md);
}

.summary-count strong {
    color: var(--color-primary);
    font-size: var(--font-size-2xl);
}

.selected-children-list {
    list-style: none;
    padding: 0;
    margin: 0 0 var(--spacing-lg) 0;
    max-height: 400px;
    overflow-y: auto;
}

.selected-children-list li {
    padding: var(--spacing-sm);
    border-bottom: 1px solid var(--color-border-lighter);
}

.selected-children-list li:last-child {
    border-bottom: none;
}

.child-details {
    color: var(--color-text-secondary);
    font-size: var(--font-size-sm);
}

/* Sponsor Form Card */
.sponsor-form-card {
    background: var(--color-white);
    border: 2px solid var(--color-border-lighter);
    border-radius: var(--radius-lg);
    padding: var(--spacing-2xl);
}

.sponsor-form-card h3 {
    margin: 0 0 var(--spacing-sm) 0;
    color: var(--color-text-primary);
}

.form-intro {
    color: var(--color-text-secondary);
    margin-bottom: var(--spacing-xl);
}

/* Form Groups */
.form-group {
    margin-bottom: var(--spacing-lg);
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: var(--spacing-xs);
    color: var(--color-text-primary);
}

.required {
    color: var(--color-danger);
}

.form-input,
.form-textarea {
    width: 100%;
    padding: var(--spacing-sm) var(--spacing-md);
    border: 2px solid var(--color-border-lighter);
    border-radius: var(--radius-md);
    font-size: var(--font-size-md);
    transition: border-color var(--transition-fast);
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--color-primary);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.form-help {
    display: block;
    color: var(--color-text-tertiary);
    font-size: var(--font-size-sm);
    margin-top: var(--spacing-xs);
}

.error-message {
    display: block;
    color: var(--color-danger);
    font-size: var(--font-size-sm);
    margin-top: var(--spacing-xs);
}

/* Checkbox Group */
.checkbox-group {
    background: var(--color-bg-primary);
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
    border: 2px solid var(--color-border-lighter);
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-sm);
    cursor: pointer;
    font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
    margin-top: 3px;
    cursor: pointer;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: space-between;
    gap: var(--spacing-md);
    margin-top: var(--spacing-2xl);
    padding-top: var(--spacing-xl);
    border-top: 2px solid var(--color-border-lighter);
}

/* Mobile Responsive */
@media (max-width: 968px) {
    .confirmation-container {
        grid-template-columns: 1fr;
    }

    .selections-summary-card {
        position: static;
        order: -1;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
    }
}
</style>
