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
        }

        return addedCount;
    },

    /**
     * Remove a child from selections
     * @param {Number} childId - Child ID
     */
    removeChild(childId) {
        const selections = this.getSelections();
        const filtered = selections.filter(c => c.id !== childId);
        this.saveSelections(filtered);
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
        localStorage.removeItem(this.STORAGE_KEY);
        this.updateBadge();
        window.dispatchEvent(new CustomEvent('selectionsUpdated', {
            detail: { count: 0 }
        }));
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
