/**
 * Christmas for Kids - Professional Selection Management System
 * Enterprise-grade implementation with security, accessibility, and performance
 * @version 2.0
 * @author God-Tier Developer
 */

'use strict';

// ============================================================================
// CONSTANTS & CONFIGURATION
// ============================================================================

const CONFIG = Object.freeze({
    STORAGE_KEY: 'cfk_selections',
    BADGE_SELECTOR: '#selections-badge',
    TOAST_DURATION: 5000,
    TOAST_ANIMATION_DURATION: 300,
    STICKY_Z_INDEX: 9998,
    MAX_SELECTIONS: 50, // Prevent memory issues
});

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Sanitize HTML to prevent XSS
 * @param {string} str - String to sanitize
 * @returns {string} Sanitized string
 */
const sanitizeHTML = (str) => {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
};

/**
 * Create DOM element safely
 * @param {string} tag - Element tag
 * @param {Object} attrs - Attributes
 * @param {string|Element|Array} children - Children
 * @returns {Element}
 */
const createElement = (tag, attrs = {}, children = []) => {
    const el = document.createElement(tag);

    Object.entries(attrs).forEach(([key, value]) => {
        if (key === 'className') {
            el.className = value;
        } else if (key === 'dataset') {
            Object.entries(value).forEach(([dataKey, dataValue]) => {
                el.dataset[dataKey] = dataValue;
            });
        } else if (key.startsWith('on') && typeof value === 'function') {
            el.addEventListener(key.substring(2).toLowerCase(), value);
        } else {
            el.setAttribute(key, value);
        }
    });

    const addChild = (child) => {
        if (typeof child === 'string') {
            el.appendChild(document.createTextNode(child));
        } else if (child instanceof Element) {
            el.appendChild(child);
        }
    };

    if (Array.isArray(children)) {
        children.forEach(addChild);
    } else {
        addChild(children);
    }

    return el;
};

/**
 * Debounce function calls
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in ms
 * @returns {Function}
 */
const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// ============================================================================
// SELECTIONS MANAGER - Singleton Pattern
// ============================================================================

const SelectionsManager = (() => {
    let instance;

    class SelectionsManagerClass {
        constructor() {
            if (instance) {
                return instance;
            }

            this.storageKey = CONFIG.STORAGE_KEY;
            this.badgeSelector = CONFIG.BADGE_SELECTOR;
            this.listeners = new Set();

            instance = this;
            this.init();
        }

        /**
         * Initialize the selections system
         */
        init() {
            this.updateBadge();

            // Listen for storage changes (cross-tab sync)
            window.addEventListener('storage', (e) => {
                if (e.key === this.storageKey) {
                    this.updateBadge();
                    this.notifyListeners();
                }
            });
        }

        /**
         * Get selections with error handling and validation
         * @returns {Array} Array of child objects
         */
        getSelections() {
            try {
                const data = localStorage.getItem(this.storageKey);
                if (!data) return [];

                const selections = JSON.parse(data);

                // Validate data structure
                if (!Array.isArray(selections)) {
                    console.error('Invalid selections data structure');
                    this.clearAll();
                    return [];
                }

                return selections;
            } catch (error) {
                console.error('Error reading selections:', error);
                // Attempt recovery
                this.clearAll();
                return [];
            }
        }

        /**
         * Save selections with validation
         * @param {Array} selections - Array of child objects
         * @throws {Error} If selections exceed limit
         */
        saveSelections(selections) {
            try {
                // Validate input
                if (!Array.isArray(selections)) {
                    throw new Error('Selections must be an array');
                }

                if (selections.length > CONFIG.MAX_SELECTIONS) {
                    throw new Error(`Cannot exceed ${CONFIG.MAX_SELECTIONS} selections`);
                }

                // Validate each selection has required fields
                const validSelections = selections.filter(child =>
                    child && typeof child === 'object' && child.id
                );

                if (validSelections.length !== selections.length) {
                    console.warn('Some selections were invalid and removed');
                }

                localStorage.setItem(this.storageKey, JSON.stringify(validSelections));
                this.updateBadge();
                this.notifyListeners();

            } catch (error) {
                console.error('Error saving selections:', error);
                this.announce(`Error saving selections: ${error.message}`, 'error');
                throw error;
            }
        }

        /**
         * Add listener for selection changes
         * @param {Function} callback - Callback function
         * @returns {Function} Unsubscribe function
         */
        subscribe(callback) {
            if (typeof callback !== 'function') {
                throw new Error('Callback must be a function');
            }

            this.listeners.add(callback);

            // Return unsubscribe function
            return () => this.listeners.delete(callback);
        }

        /**
         * Notify all listeners of changes
         */
        notifyListeners() {
            const count = this.getCount();
            this.listeners.forEach(callback => {
                try {
                    callback({ count, selections: this.getSelections() });
                } catch (error) {
                    console.error('Error in selection listener:', error);
                }
            });
        }

        /**
         * Add a child with validation
         * @param {Object} child - Child object
         * @returns {boolean} Success status
         */
        addChild(child) {
            // Validate input
            if (!child || typeof child !== 'object' || !child.id) {
                this.announce('Invalid child data', 'error');
                return false;
            }

            const selections = this.getSelections();

            // Check if already selected
            if (selections.some(c => c.id === child.id)) {
                this.announce(`Child ${sanitizeHTML(child.display_id || 'Unknown')} is already in your selections`);
                return false;
            }

            // Check limit
            if (selections.length >= CONFIG.MAX_SELECTIONS) {
                this.announce(`Cannot select more than ${CONFIG.MAX_SELECTIONS} children`, 'error');
                return false;
            }

            // Create safe copy with only needed fields
            const safeChild = {
                id: child.id,
                display_id: child.display_id || 'Unknown',
                family_id: child.family_id,
                age: child.age,
                gender: child.gender,
                grade: child.grade || '',
                school: child.school || '',
                interests: child.interests || '',
                wishes: child.wishes || '',
                clothing_sizes: child.clothing_sizes || {},
                shoe_size: child.shoe_size || '',
                added_at: new Date().toISOString()
            };

            selections.push(safeChild);

            try {
                this.saveSelections(selections);
                this.announce(
                    `Added child ${sanitizeHTML(safeChild.display_id)} to your selections. ` +
                    `You have ${selections.length} ${selections.length === 1 ? 'child' : 'children'} selected.`
                );
                return true;
            } catch (error) {
                return false;
            }
        }

        /**
         * Remove a child
         * @param {number} childId - Child ID
         */
        removeChild(childId) {
            const selections = this.getSelections();
            const child = selections.find(c => c.id === childId);
            const filtered = selections.filter(c => c.id !== childId);

            try {
                this.saveSelections(filtered);

                if (child) {
                    this.announce(
                        `Removed child ${sanitizeHTML(child.display_id)} from your selections. ` +
                        `You have ${filtered.length} ${filtered.length === 1 ? 'child' : 'children'} remaining.`
                    );
                }
            } catch (error) {
                console.error('Error removing child:', error);
            }
        }

        /**
         * Get count of selections
         * @returns {number}
         */
        getCount() {
            return this.getSelections().length;
        }

        /**
         * Clear all selections
         */
        clearAll() {
            const count = this.getCount();
            localStorage.removeItem(this.storageKey);
            this.updateBadge();
            this.notifyListeners();

            if (count > 0) {
                this.announce('All selections cleared. Your cart is now empty.');
            }
        }

        /**
         * Update badge with debouncing
         */
        updateBadge = debounce(() => {
            const badge = document.querySelector(this.badgeSelector);
            const count = this.getCount();

            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
                badge.setAttribute('aria-label', `${count} ${count === 1 ? 'child' : 'children'} selected`);
            }
        }, 100);

        /**
         * Announce changes to screen readers (WCAG 4.1.3)
         * @param {string} message - Message to announce
         * @param {string} type - 'info' | 'error' | 'success'
         */
        announce(message, type = 'info') {
            const announcer = document.getElementById('a11y-announcements');
            if (!announcer) {
                console.warn('Accessibility announcer not found');
                return;
            }

            // Set politeness level
            announcer.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');

            // Clear and set message
            announcer.textContent = '';
            setTimeout(() => {
                announcer.textContent = message;
            }, 100);
        }
    }

    return new SelectionsManagerClass();
})();

// ============================================================================
// TOAST MANAGER - Professional Implementation
// ============================================================================

const ToastManager = (() => {
    let instance;

    class ToastManagerClass {
        constructor() {
            if (instance) {
                return instance;
            }

            this.activeToast = null;
            this.hideTimeout = null;
            this.eventListeners = [];

            instance = this;
        }

        /**
         * Show a toast notification
         * @param {Object} options - Toast configuration
         */
        show(options = {}) {
            // Validate options
            if (!options.message) {
                console.error('Toast message is required');
                return;
            }

            // Remove existing toast
            if (this.activeToast) {
                this.hide();
            }

            const {
                message,
                actionUrl = '',
                actionText = 'View Cart',
                dismissText = 'Keep Browsing',
                duration = CONFIG.TOAST_DURATION,
                type = 'info'
            } = options;

            // Create toast with safe DOM creation
            const toast = createElement('div', {
                className: `toast-notification toast-${type}`,
                role: 'alert',
                'aria-live': 'polite'
            }, [
                createElement('p', { className: 'toast-message' }, sanitizeHTML(message)),
                createElement('div', { className: 'toast-actions' }, [
                    actionUrl ? createElement('a', {
                        href: actionUrl,
                        className: 'toast-btn toast-btn-primary'
                    }, actionText) : null,
                    createElement('button', {
                        className: 'toast-btn toast-btn-secondary',
                        type: 'button',
                        'aria-label': 'Dismiss notification'
                    }, dismissText)
                ].filter(Boolean))
            ]);

            // Add dismiss handler
            const dismissBtn = toast.querySelector('.toast-btn-secondary');
            const dismissHandler = () => this.hide();
            dismissBtn.addEventListener('click', dismissHandler);

            // Track for cleanup
            this.eventListeners.push({ element: dismissBtn, handler: dismissHandler });

            // Add to page
            document.body.appendChild(toast);
            this.activeToast = toast;

            // Auto-hide after duration
            this.hideTimeout = setTimeout(() => this.hide(), duration);

            // Focus management for accessibility
            dismissBtn.focus();
        }

        /**
         * Hide active toast with cleanup
         */
        hide() {
            if (!this.activeToast) return;

            // Clear timeout
            if (this.hideTimeout) {
                clearTimeout(this.hideTimeout);
                this.hideTimeout = null;
            }

            // Remove event listeners (prevent memory leaks)
            this.eventListeners.forEach(({ element, handler }) => {
                element.removeEventListener('click', handler);
            });
            this.eventListeners = [];

            // Animate out
            this.activeToast.classList.add('hiding');

            const toastToRemove = this.activeToast;
            setTimeout(() => {
                if (toastToRemove && toastToRemove.parentNode) {
                    toastToRemove.parentNode.removeChild(toastToRemove);
                }
            }, CONFIG.TOAST_ANIMATION_DURATION);

            this.activeToast = null;
        }
    }

    return new ToastManagerClass();
})();

// ============================================================================
// STICKY BAR MANAGER - Professional Implementation
// ============================================================================

const StickyBarManager = (() => {
    let instance;

    class StickyBarManagerClass {
        constructor() {
            if (instance) {
                return instance;
            }

            this.bar = null;
            this.baseUrl = '';
            this.unsubscribe = null;

            instance = this;
        }

        /**
         * Initialize sticky bar
         * @param {string} baseUrl - Base URL for links
         */
        init(baseUrl = '') {
            if (this.bar) {
                console.warn('StickyBarManager already initialized');
                return;
            }

            this.baseUrl = baseUrl;

            // Create sticky bar with safe DOM creation
            this.bar = createElement('div', {
                className: 'selections-sticky-bar',
                role: 'complementary',
                'aria-label': 'Selections summary',
                style: 'background-color: #ffffff; z-index: ' + CONFIG.STICKY_Z_INDEX
            }, [
                createElement('div', { className: 'sticky-bar-content' }, [
                    createElement('div', { className: 'sticky-bar-info' }, [
                        createElement('span', {
                            className: 'sticky-bar-icon',
                            role: 'img',
                            'aria-label': 'Selections'
                        }, ''),
                        createElement('span', { className: 'sticky-bar-text' }, [
                            createElement('span', {
                                className: 'sticky-bar-count',
                                'aria-live': 'polite'
                            }, '0'),
                            createElement('span', { className: 'sticky-bar-label' }, 'children selected')
                        ])
                    ]),
                    createElement('div', { className: 'sticky-bar-actions' }, [
                        createElement('a', {
                            href: `${this.baseUrl}?page=my_sponsorships`,
                            className: 'btn btn-outline btn-small'
                        }, 'Review Selections'),
                        createElement('a', {
                            href: `${this.baseUrl}?page=confirm_sponsorship`,
                            className: 'btn btn-success btn-small'
                        }, 'Complete Sponsorship →')
                    ])
                ])
            ]);

            // Add to page
            document.body.appendChild(this.bar);

            // Subscribe to selection changes
            this.unsubscribe = SelectionsManager.subscribe(() => this.update());

            // Initial update
            this.update();
        }

        /**
         * Update sticky bar visibility and content
         */
        update() {
            if (!this.bar) return;

            try {
                const count = SelectionsManager.getCount();
                const countBadge = this.bar.querySelector('.sticky-bar-count');
                const label = this.bar.querySelector('.sticky-bar-label');

                if (count > 0) {
                    this.bar.classList.add('visible');

                    if (countBadge) {
                        countBadge.textContent = count;
                    }

                    if (label) {
                        label.textContent = count === 1 ? 'child selected' : 'children selected';
                    }
                } else {
                    this.bar.classList.remove('visible');
                }
            } catch (error) {
                console.error('Error updating sticky bar:', error);
            }
        }

        /**
         * Manually hide the bar
         */
        hide() {
            if (this.bar) {
                this.bar.classList.remove('visible');
            }
        }

        /**
         * Destroy sticky bar and cleanup
         */
        destroy() {
            if (this.unsubscribe) {
                this.unsubscribe();
                this.unsubscribe = null;
            }

            if (this.bar && this.bar.parentNode) {
                this.bar.parentNode.removeChild(this.bar);
            }

            this.bar = null;
            instance = null;
        }
    }

    return new StickyBarManagerClass();
})();

// ============================================================================
// EXPORTS & INITIALIZATION
// ============================================================================

// Export to global scope (for backwards compatibility)
window.SelectionsManager = SelectionsManager;
window.ToastManager = ToastManager;
window.StickyBarManager = StickyBarManager;

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.info('Selections system initialized');
    });
} else {
    console.info('Selections system initialized');
}

// Freeze exports to prevent tampering
Object.freeze(window.SelectionsManager);
Object.freeze(window.ToastManager);
// Note: StickyBarManager not frozen to allow init() to set properties
// Object.freeze(window.StickyBarManager);
