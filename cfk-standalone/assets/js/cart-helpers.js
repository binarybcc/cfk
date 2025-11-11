/**
 * Cart Helper Functions
 * Bridge between Twig templates and SelectionsManager
 */

'use strict';

/**
 * Add child to cart from template
 * @param {number} id - Child ID
 * @param {string} displayId - Display ID (e.g., "175A")
 * @param {number} ageMonths - Age in months
 * @param {string} gender - Gender (M/F)
 * @param {string} grade - Grade level
 * @param {string} school - School name
 * @param {string} interests - Interests/Essential needs
 * @param {string} wishes - Wishes
 * @param {string} shirtSize - Shirt size
 * @param {string} pantSize - Pant size
 * @param {string} jacketSize - Jacket size
 * @param {string} shoeSize - Shoe size
 * @param {number} familyId - Family ID
 */
function addChildToCart(
    id,
    displayId,
    ageMonths,
    gender,
    grade = '',
    school = '',
    interests = '',
    wishes = '',
    shirtSize = '',
    pantSize = '',
    jacketSize = '',
    shoeSize = '',
    familyId = null
) {
    // Validate SelectionsManager is available
    if (typeof SelectionsManager === 'undefined') {
        console.error('SelectionsManager not loaded');
        alert('Cart system not available. Please refresh the page.');
        return;
    }

    // Create child object
    const childData = {
        id: parseInt(id, 10),
        display_id: displayId,
        age_months: parseInt(ageMonths, 10),
        gender: gender,
        grade: grade || '',
        school: school || '',
        interests: interests || '',
        wishes: wishes || '',
        shirt_size: shirtSize || '',
        pant_size: pantSize || '',
        jacket_size: jacketSize || '',
        shoe_size: shoeSize || '',
        family_id: familyId ? parseInt(familyId, 10) : null
    };

    // Add to cart using SelectionsManager
    const success = SelectionsManager.addChild(childData);

    // Optional: Show toast notification (if ToastManager is available)
    if (success && typeof ToastManager !== 'undefined') {
        ToastManager.show({
            message: `Added ${displayId} to your cart!`,
            actionUrl: window.baseUrl ? `${window.baseUrl}/cart/review` : '/cart/review',
            actionText: 'Review Cart',
            dismissText: 'Continue Browsing',
            type: 'success'
        });
    }

    return success;
}

/**
 * Navigate to cart review page
 */
function goToCart() {
    const count = SelectionsManager ? SelectionsManager.getCount() : 0;

    if (count === 0) {
        alert('Your cart is empty. Please select children to sponsor first.');
        return;
    }

    // Redirect to cart review
    window.location.href = window.baseUrl ? `${window.baseUrl}/cart/review` : '/cart/review';
}

/**
 * Get cart item count
 * @returns {number}
 */
function getCartCount() {
    return SelectionsManager ? SelectionsManager.getCount() : 0;
}

// Export to global scope
window.addChildToCart = addChildToCart;
window.goToCart = goToCart;
window.getCartCount = getCartCount;
