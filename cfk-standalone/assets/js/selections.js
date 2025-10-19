/**
 * Christmas for Kids - Selection Management System
 * localStorage-based system for managing child sponsorship selections
 * v1.5 - Reservation System
 */

const SelectionsManager = {
    STORAGE_KEY: 'cfk_selections',
    BADGE_SELECTOR: '#selections-badge',

    /**
     * Initialize the selections system
     */
    init() {
        this.updateBadge();

        // Listen for storage changes (sync across tabs)
        window.addEventListener('storage', (e) => {
            if (e.key === this.STORAGE_KEY) {
                this.updateBadge();
            }
        });

        // Update badge on custom events
        window.addEventListener('selectionsUpdated', () => {
            this.updateBadge();
        });
    },

    /**
     * Get all selections from localStorage
     * @returns {Array} Array of child objects
     */
    getSelections() {
        try {
            const data = localStorage.getItem(this.STORAGE_KEY);
            return data ? JSON.parse(data) : [];
        } catch (error) {
            console.error('Error reading selections:', error);
            return [];
        }
    },

    /**
     * Save selections to localStorage
     * @param {Array} selections - Array of child objects
     */
    saveSelections(selections) {
        try {
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(selections));
            this.updateBadge();

            // Dispatch custom event for other components
            window.dispatchEvent(new CustomEvent('selectionsUpdated', {
                detail: { count: selections.length }
            }));
        } catch (error) {
            console.error('Error saving selections:', error);
        }
    },

    /**
     * Add a child to selections
     * @param {Object} child - Child object
     * @returns {Boolean} Success status
     */
    addChild(child) {
        const selections = this.getSelections();

        // Check if already selected
        if (selections.some(c => c.id === child.id)) {
            this.announce(`Child ${child.display_id} is already in your selections.`);
            return false;
        }

        selections.push({
            id: child.id,
            display_id: child.display_id,
            family_id: child.family_id,
            age: child.age,
            gender: child.gender,
            grade: child.grade,
            school: child.school,
            interests: child.interests,
            wishes: child.wishes,
            clothing_sizes: child.clothing_sizes,
            shoe_size: child.shoe_size,
            added_at: new Date().toISOString()
        });

        this.saveSelections(selections);
        this.announce(`Added child ${child.display_id} to your selections. You have ${selections.length} ${selections.length === 1 ? 'child' : 'children'} selected.`);
        return true;
    },

    /**
     * Add multiple children (e.g., entire family)
     * @param {Array} children - Array of child objects
     * @returns {Number} Number of children added
     */
    addMultiple(children) {
        const selections = this.getSelections();
        const existingIds = new Set(selections.map(c => c.id));
        let addedCount = 0;

        children.forEach(child => {
            if (!existingIds.has(child.id) && child.status === 'available') {
                selections.push({
                    id: child.id,
                    display_id: child.display_id,
                    family_id: child.family_id,
                    age: child.age,
                    gender: child.gender,
                    grade: child.grade,
                    school: child.school,
                    interests: child.interests,
                    wishes: child.wishes,
                    clothing_sizes: child.clothing_sizes,
                    shoe_size: child.shoe_size,
                    added_at: new Date().toISOString()
                });
                addedCount++;
            }
        });

        if (addedCount > 0) {
            this.saveSelections(selections);
            this.announce(`Added ${addedCount} ${addedCount === 1 ? 'child' : 'children'} to your selections. You now have ${selections.length} total selected.`);
        } else {
            this.announce('No new children were added. They may already be in your selections.');
        }

        return addedCount;
    },

    /**
     * Remove a child from selections
     * @param {Number} childId - Child ID
     */
    removeChild(childId) {
        const selections = this.getSelections();
        const child = selections.find(c => c.id === childId);
        const filtered = selections.filter(c => c.id !== childId);
        this.saveSelections(filtered);

        if (child) {
            this.announce(`Removed child ${child.display_id} from your selections. You have ${filtered.length} ${filtered.length === 1 ? 'child' : 'children'} remaining.`);
        }
    },

    /**
     * Check if a child is selected
     * @param {Number} childId - Child ID
     * @returns {Boolean}
     */
    isSelected(childId) {
        const selections = this.getSelections();
        return selections.some(c => c.id === childId);
    },

    /**
     * Get count of selections
     * @returns {Number}
     */
    getCount() {
        return this.getSelections().length;
    },

    /**
     * Clear all selections
     */
    clearAll() {
        const count = this.getCount();
        localStorage.removeItem(this.STORAGE_KEY);
        this.updateBadge();
        window.dispatchEvent(new CustomEvent('selectionsUpdated', {
            detail: { count: 0 }
        }));

        if (count > 0) {
            this.announce(`All selections cleared. Your cart is now empty.`);
        }
    },

    /**
     * Update the badge in the header
     */
    updateBadge() {
        const badge = document.querySelector(this.BADGE_SELECTOR);
        const count = this.getCount();

        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    },

    /**
     * Announce changes to screen readers (WCAG 4.1.3)
     * @param {String} message - Message to announce
     */
    announce(message) {
        const announcer = document.getElementById('a11y-announcements');
        if (announcer) {
            // Clear previous message
            announcer.textContent = '';
            // Set new message after brief delay (ensures screen reader catches the change)
            setTimeout(() => {
                announcer.textContent = message;
            }, 100);
        }
    },

    /**
     * Get selections grouped by family
     * @returns {Object} Family groups
     */
    getGroupedByFamily() {
        const selections = this.getSelections();
        const grouped = {};

        selections.forEach(child => {
            const familyId = child.family_id;
            if (!grouped[familyId]) {
                grouped[familyId] = [];
            }
            grouped[familyId].push(child);
        });

        return grouped;
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => SelectionsManager.init());
} else {
    SelectionsManager.init();
}

// Export for use in other scripts
window.SelectionsManager = SelectionsManager;
