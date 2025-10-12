<?php
/**
 * My Selections Page
 * View and manage selected children before confirming sponsorship
 * v1.5 - Reservation System
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

$pageTitle = 'My Selections';
?>

<div class="selections-page" x-data="selectionsApp()">
    <!-- Page Header -->
    <div class="page-header">
        <h1>My Selections</h1>
        <p class="page-description">
            Review the children you've selected for sponsorship. When you're ready, proceed to confirm your sponsorship commitment.
        </p>
    </div>

    <!-- Empty State -->
    <template x-if="selections.length === 0">
        <div class="empty-selections">
            <div class="empty-icon">🎄</div>
            <h2>No Children Selected Yet</h2>
            <p>Browse our children in need and add them to your selections to get started.</p>
            <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-primary btn-large">
                Browse Children
            </a>
        </div>
    </template>

    <!-- Selections Grid -->
    <template x-if="selections.length > 0">
        <div>
            <!-- Summary Bar -->
            <div class="selections-summary">
                <div class="summary-info">
                    <strong x-text="selections.length"></strong>
                    <span x-text="selections.length === 1 ? 'child' : 'children'"></span> selected
                </div>
                <div class="summary-actions">
                    <button @click="clearAllSelections()" class="btn btn-secondary">
                        Clear All
                    </button>
                    <a href="<?php echo baseUrl('?page=confirm_sponsorship'); ?>" class="btn btn-success btn-large">
                        Proceed to Confirm Sponsorship
                    </a>
                </div>
            </div>

            <!-- Selected Children Cards -->
            <div class="selections-grid">
                <template x-for="child in selections" :key="child.id">
                    <div class="selection-card">
                        <!-- Card Header -->
                        <div class="selection-header">
                            <div class="selection-title">
                                <strong x-text="child.display_id"></strong>
                                <span class="badge badge-success">Selected</span>
                            </div>
                            <button @click="removeSelection(child.id)"
                                    class="btn-remove"
                                    title="Remove from selections">
                                ✕
                            </button>
                        </div>

                        <!-- Child Details -->
                        <div class="selection-details">
                            <div class="detail-row">
                                <div class="detail-item">
                                    <strong>Age:</strong>
                                    <span x-text="child.age + ' years old'"></span>
                                </div>
                                <div class="detail-item">
                                    <strong>Gender:</strong>
                                    <span x-text="child.gender === 'M' ? 'Boy' : 'Girl'"></span>
                                </div>
                                <div class="detail-item" x-show="child.grade">
                                    <strong>Grade:</strong>
                                    <span x-text="child.grade"></span>
                                </div>
                            </div>

                            <div class="detail-section" x-show="child.school">
                                <strong>School:</strong>
                                <p x-text="child.school"></p>
                            </div>

                            <div class="detail-section" x-show="child.interests">
                                <strong>Interests:</strong>
                                <p x-text="child.interests"></p>
                            </div>

                            <div class="detail-section" x-show="child.wishes">
                                <strong>Christmas Wishes:</strong>
                                <p x-text="child.wishes" class="wishes-text"></p>
                            </div>

                            <div class="detail-section" x-show="child.clothing_sizes">
                                <strong>Clothing Sizes:</strong>
                                <p x-text="child.clothing_sizes"></p>
                            </div>

                            <div class="detail-section" x-show="child.shoe_size">
                                <strong>Shoe Size:</strong>
                                <p x-text="child.shoe_size"></p>
                            </div>
                        </div>

                        <!-- Added Date -->
                        <div class="selection-footer">
                            <small class="text-muted">
                                Added <span x-text="formatDate(child.added_at)"></span>
                            </small>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Bottom Actions -->
            <div class="selections-bottom-actions">
                <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-outline">
                    ← Continue Browsing
                </a>
                <a href="<?php echo baseUrl('?page=confirm_sponsorship'); ?>" class="btn btn-success btn-large">
                    Proceed to Confirm Sponsorship
                </a>
            </div>
        </div>
    </template>
</div>

<script>
function selectionsApp() {
    return {
        selections: [],

        init() {
            this.loadSelections();

            // Listen for selection changes
            window.addEventListener('selectionsUpdated', () => {
                this.loadSelections();
            });
        },

        loadSelections() {
            this.selections = SelectionsManager.getSelections();
        },

        removeSelection(childId) {
            if (confirm('Remove this child from your selections?')) {
                SelectionsManager.removeChild(childId);
                this.loadSelections();
                window.showNotification('Child removed from selections', 'info');
            }
        },

        clearAllSelections() {
            if (confirm(`Remove all ${this.selections.length} children from your selections?`)) {
                SelectionsManager.clearAll();
                this.loadSelections();
                window.showNotification('All selections cleared', 'info');
            }
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);

            if (minutes < 1) return 'just now';
            if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            if (days < 7) return `${days} day${days > 1 ? 's' : ''} ago`;
            return date.toLocaleDateString();
        }
    }
}
</script>

<style>
/* Selections Page Styles */
.selections-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--spacing-xl);
}

/* Empty State */
.empty-selections {
    text-align: center;
    padding: var(--spacing-4xl) var(--spacing-xl);
}

.empty-icon {
    font-size: 5rem;
    margin-bottom: var(--spacing-lg);
}

.empty-selections h2 {
    color: var(--color-text-primary);
    margin-bottom: var(--spacing-md);
}

.empty-selections p {
    color: var(--color-text-secondary);
    font-size: var(--font-size-lg);
    margin-bottom: var(--spacing-xl);
}

/* Summary Bar */
.selections-summary {
    background: var(--color-bg-secondary);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow-sm);
}

.summary-info {
    font-size: var(--font-size-lg);
}

.summary-info strong {
    color: var(--color-primary);
    font-size: var(--font-size-2xl);
    margin-right: var(--spacing-xs);
}

.summary-actions {
    display: flex;
    gap: var(--spacing-md);
}

/* Selections Grid */
.selections-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--spacing-xl);
    margin-bottom: var(--spacing-2xl);
}

/* Selection Card */
.selection-card {
    background: var(--color-white);
    border: 2px solid var(--color-border-lighter);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: all var(--transition-fast);
}

.selection-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--color-primary);
}

.selection-header {
    background: var(--color-bg-secondary);
    padding: var(--spacing-md) var(--spacing-lg);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--color-border-lighter);
}

.selection-title {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.selection-title strong {
    color: var(--color-primary);
    font-size: var(--font-size-lg);
}

.btn-remove {
    background: none;
    border: none;
    color: var(--color-text-tertiary);
    font-size: 1.5rem;
    cursor: pointer;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-sm);
    transition: all var(--transition-fast);
}

.btn-remove:hover {
    background: var(--color-danger);
    color: var(--color-white);
}

.selection-details {
    padding: var(--spacing-lg);
}

.detail-row {
    display: flex;
    gap: var(--spacing-md);
    flex-wrap: wrap;
    margin-bottom: var(--spacing-md);
}

.detail-item {
    display: flex;
    gap: var(--spacing-xs);
    font-size: var(--font-size-sm);
}

.detail-item strong {
    color: var(--color-primary);
}

.detail-section {
    margin-bottom: var(--spacing-md);
}

.detail-section:last-child {
    margin-bottom: 0;
}

.detail-section strong {
    display: block;
    color: var(--color-primary);
    font-size: var(--font-size-sm);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: var(--spacing-xs);
}

.detail-section p {
    margin: 0;
    padding: var(--spacing-sm);
    background: var(--color-bg-primary);
    border-left: 3px solid var(--color-secondary);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-sm);
    line-height: 1.6;
}

.wishes-text {
    border-left-color: var(--color-danger);
    background: #fef5f5;
}

.selection-footer {
    padding: var(--spacing-sm) var(--spacing-lg);
    background: var(--color-bg-primary);
    border-top: 1px solid var(--color-border-lighter);
}

.text-muted {
    color: var(--color-text-tertiary);
    font-size: var(--font-size-xs);
}

/* Bottom Actions */
.selections-bottom-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-xl) 0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .selections-page {
        padding: var(--spacing-md);
    }

    .selections-grid {
        grid-template-columns: 1fr;
    }

    .selections-summary {
        flex-direction: column;
        text-align: center;
        gap: var(--spacing-md);
    }

    .summary-actions {
        flex-direction: column;
        width: 100%;
    }

    .summary-actions .btn {
        width: 100%;
    }

    .selections-bottom-actions {
        flex-direction: column;
        gap: var(--spacing-md);
    }

    .selections-bottom-actions .btn {
        width: 100%;
    }
}
</style>
