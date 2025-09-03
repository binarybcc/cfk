/**
 * Christmas for Kids - Public JavaScript
 * 
 * Frontend functionality for child sponsorship interactions
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Main CFK Public object
    const CFK_Public = {
        
        // Configuration
        config: {
            debounceDelay: 300,
            animationDuration: 300,
            loadingClass: 'cfk-loading',
            errorClass: 'cfk-error'
        },
        
        // Cache for DOM elements
        cache: {
            $wrapper: null,
            $grid: null,
            $filters: null,
            $searchInput: null,
            $ageFilter: null,
            $genderFilter: null,
            $sortFilter: null,
            $sponsorButtons: null
        },
        
        // State management
        state: {
            isLoading: false,
            currentFilters: {
                search: '',
                age: '',
                gender: '',
                sort: 'random'
            },
            sponsorModal: null,
            selectedChild: null
        },
        
        /**
         * Initialize the public functionality
         */
        init: function() {
            this.cacheDOMElements();
            this.bindEvents();
            this.initializeFilters();
            this.createSponsorModal();
            console.log('[CFK] Public JavaScript initialized');
        },
        
        /**
         * Cache DOM elements for performance
         */
        cacheDOMElements: function() {
            this.cache.$wrapper = $('.cfk-children-wrapper');
            this.cache.$grid = $('.cfk-children-grid');
            this.cache.$filters = $('.cfk-children-filters');
            this.cache.$searchInput = $('#cfk-child-search');
            this.cache.$ageFilter = $('#cfk-age-filter');
            this.cache.$genderFilter = $('#cfk-gender-filter');
            this.cache.$sortFilter = $('#cfk-sort-filter');
            this.cache.$sponsorButtons = $('.cfk-sponsor-btn');
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;
            
            // Search input with debouncing
            if (this.cache.$searchInput.length) {
                this.cache.$searchInput.on('input', 
                    this.debounce(function() {
                        self.handleSearchChange();
                    }, this.config.debounceDelay)
                );
            }
            
            // Filter dropdowns
            this.cache.$ageFilter.on('change', function() {
                self.handleFilterChange();
            });
            
            this.cache.$genderFilter.on('change', function() {
                self.handleFilterChange();
            });
            
            this.cache.$sortFilter.on('change', function() {
                self.handleFilterChange();
            });
            
            // Sponsor buttons
            $(document).on('click', '.cfk-sponsor-btn', function(e) {
                e.preventDefault();
                const childId = $(this).data('child-id');
                self.handleSponsorClick(childId, $(this));
            });
            
            // Modal events
            $(document).on('click', '.cfk-modal-close', function() {
                self.closeSponsorModal();
            });
            
            $(document).on('click', '.cfk-sponsor-modal', function(e) {
                if (e.target === this) {
                    self.closeSponsorModal();
                }
            });
            
            // Form submission
            $(document).on('submit', '.cfk-sponsor-form', function(e) {
                e.preventDefault();
                self.handleSponsorSubmit($(this));
            });
            
            // Keyboard navigation
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.state.sponsorModal) {
                    self.closeSponsorModal();
                }
            });
        },
        
        /**
         * Initialize filters with current values
         */
        initializeFilters: function() {
            this.updateStateFromFilters();
        },
        
        /**
         * Handle search input changes
         */
        handleSearchChange: function() {
            this.state.currentFilters.search = this.cache.$searchInput.val().toLowerCase();
            this.applyFilters();
        },
        
        /**
         * Handle filter dropdown changes
         */
        handleFilterChange: function() {
            this.updateStateFromFilters();
            this.applyFilters();
        },
        
        /**
         * Update state from filter inputs
         */
        updateStateFromFilters: function() {
            this.state.currentFilters.age = this.cache.$ageFilter.val();
            this.state.currentFilters.gender = this.cache.$genderFilter.val();
            this.state.currentFilters.sort = this.cache.$sortFilter.val();
        },
        
        /**
         * Apply filters to the children grid
         */
        applyFilters: function() {
            if (this.state.isLoading) return;
            
            const self = this;
            const $cards = this.cache.$grid.find('.cfk-child-card');
            
            // Show loading state
            this.showLoadingState();
            
            // Apply filters with animation
            setTimeout(function() {
                let visibleCards = $cards.filter(function() {
                    return self.cardMatchesFilters($(this));
                });
                
                // Hide cards that don't match
                $cards.not(visibleCards).fadeOut(self.config.animationDuration);
                
                // Show cards that match
                visibleCards.fadeIn(self.config.animationDuration);
                
                // Apply sorting if needed
                if (self.state.currentFilters.sort !== 'random') {
                    self.sortCards(visibleCards);
                }
                
                self.hideLoadingState();
                
                // Show no results message if needed
                self.toggleNoResultsMessage(visibleCards.length === 0);
                
            }, 100);
        },
        
        /**
         * Check if a card matches current filters
         */
        cardMatchesFilters: function($card) {
            const filters = this.state.currentFilters;
            
            // Search filter
            if (filters.search) {
                const childName = $card.find('.cfk-child-name').text().toLowerCase();
                const childInterests = $card.find('.cfk-child-interests').text().toLowerCase();
                const searchTerm = filters.search.toLowerCase();
                
                if (!childName.includes(searchTerm) && !childInterests.includes(searchTerm)) {
                    return false;
                }
            }
            
            // Age filter
            if (filters.age) {
                const [minAge, maxAge] = filters.age.split('-').map(Number);
                const childAge = this.extractAgeFromCard($card);
                
                if (childAge < minAge || childAge > maxAge) {
                    return false;
                }
            }
            
            // Gender filter (this would need to be stored as data attribute)
            if (filters.gender) {
                const childGender = $card.data('gender');
                if (childGender && childGender !== filters.gender) {
                    return false;
                }
            }
            
            return true;
        },
        
        /**
         * Extract age from card text
         */
        extractAgeFromCard: function($card) {
            const ageText = $card.find('.cfk-child-age').text();
            const ageMatch = ageText.match(/\d+/);
            return ageMatch ? parseInt(ageMatch[0]) : 0;
        },
        
        /**
         * Sort cards based on current sort filter
         */
        sortCards: function($cards) {
            const self = this;
            const $grid = this.cache.$grid;
            
            const sortedCards = $cards.toArray().sort(function(a, b) {
                const $a = $(a);
                const $b = $(b);
                
                switch (self.state.currentFilters.sort) {
                    case 'age_asc':
                        return self.extractAgeFromCard($a) - self.extractAgeFromCard($b);
                    case 'age_desc':
                        return self.extractAgeFromCard($b) - self.extractAgeFromCard($a);
                    case 'name':
                        const nameA = $a.find('.cfk-child-name').text().toLowerCase();
                        const nameB = $b.find('.cfk-child-name').text().toLowerCase();
                        return nameA.localeCompare(nameB);
                    default:
                        return 0;
                }
            });
            
            // Reorder DOM elements
            $.each(sortedCards, function(index, card) {
                $grid.append(card);
            });
        },
        
        /**
         * Show/hide no results message
         */
        toggleNoResultsMessage: function(show) {
            let $noResults = this.cache.$wrapper.find('.cfk-no-results');
            
            if (show && $noResults.length === 0) {
                $noResults = $('<div class="cfk-no-results cfk-no-children">' +
                             '<p>' + cfk_public_ajax.messages.no_results + '</p>' +
                             '</div>');
                this.cache.$grid.after($noResults);
            } else if (!show) {
                $noResults.remove();
            }
        },
        
        /**
         * Handle sponsor button click
         */
        handleSponsorClick: function(childId, $button) {
            if (!childId) return;
            
            const childData = this.extractChildData($button.closest('.cfk-child-card'));
            this.state.selectedChild = {
                id: childId,
                data: childData
            };
            
            this.showSponsorModal(childData);
        },
        
        /**
         * Extract child data from card
         */
        extractChildData: function($card) {
            return {
                id: $card.data('child-id'),
                name: $card.find('.cfk-child-name').text(),
                age: this.extractAgeFromCard($card),
                photo: $card.find('.cfk-child-photo img').attr('src') || '',
                interests: $card.find('.cfk-child-interests').text().replace('Interests: ', '')
            };
        },
        
        /**
         * Create sponsor modal HTML
         */
        createSponsorModal: function() {
            if ($('.cfk-sponsor-modal').length) return;
            
            const modalHTML = `
                <div class="cfk-sponsor-modal" role="dialog" aria-labelledby="cfk-modal-title" aria-hidden="true">
                    <div class="cfk-sponsor-modal-content">
                        <div class="cfk-modal-header">
                            <h2 id="cfk-modal-title">Sponsor a Child</h2>
                            <button class="cfk-modal-close" aria-label="Close modal">&times;</button>
                        </div>
                        <div class="cfk-modal-body">
                            <div class="cfk-modal-child-info">
                                <div class="cfk-modal-child-photo"></div>
                                <div class="cfk-modal-child-details">
                                    <h3 class="cfk-modal-child-name"></h3>
                                    <p class="cfk-modal-child-age"></p>
                                    <p class="cfk-modal-child-interests"></p>
                                </div>
                            </div>
                            
                            <form class="cfk-sponsor-form">
                                <div class="form-group">
                                    <label for="cfk-sponsor-name">Your Name *</label>
                                    <input type="text" id="cfk-sponsor-name" name="sponsor_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="cfk-sponsor-email">Email Address *</label>
                                    <input type="email" id="cfk-sponsor-email" name="sponsor_email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="cfk-sponsor-phone">Phone Number (Optional)</label>
                                    <input type="tel" id="cfk-sponsor-phone" name="sponsor_phone">
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="btn-cancel cfk-modal-close">Cancel</button>
                                    <button type="submit" class="btn-submit">Submit Sponsorship</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            this.state.sponsorModal = $('.cfk-sponsor-modal');
        },
        
        /**
         * Show sponsor modal with child data
         */
        showSponsorModal: function(childData) {
            const $modal = this.state.sponsorModal;
            
            // Populate child information
            $modal.find('.cfk-modal-child-name').text(childData.name);
            $modal.find('.cfk-modal-child-age').text('Age: ' + childData.age);
            $modal.find('.cfk-modal-child-interests').text(childData.interests ? 'Interests: ' + childData.interests : '');
            
            if (childData.photo) {
                $modal.find('.cfk-modal-child-photo').html(
                    '<img src="' + childData.photo + '" alt="' + childData.name + '">'
                );
            }
            
            // Reset form
            $modal.find('.cfk-sponsor-form')[0].reset();
            
            // Show modal
            $modal.addClass('active').attr('aria-hidden', 'false');
            $modal.find('#cfk-sponsor-name').focus();
            
            // Prevent body scroll
            $('body').addClass('cfk-modal-open');
        },
        
        /**
         * Close sponsor modal
         */
        closeSponsorModal: function() {
            const $modal = this.state.sponsorModal;
            
            $modal.removeClass('active').attr('aria-hidden', 'true');
            $('body').removeClass('cfk-modal-open');
            
            this.state.selectedChild = null;
        },
        
        /**
         * Handle sponsor form submission
         */
        handleSponsorSubmit: function($form) {
            if (this.state.isLoading) return;
            
            const formData = {
                child_id: this.state.selectedChild.id,
                sponsor_name: $form.find('[name="sponsor_name"]').val().trim(),
                sponsor_email: $form.find('[name="sponsor_email"]').val().trim(),
                sponsor_phone: $form.find('[name="sponsor_phone"]').val().trim(),
                nonce: cfk_public_ajax.nonce,
                action: 'cfk_sponsor_child'
            };
            
            // Validate form
            if (!formData.sponsor_name || !formData.sponsor_email) {
                this.showMessage('Please fill in all required fields.', 'error');
                return;
            }
            
            if (!this.isValidEmail(formData.sponsor_email)) {
                this.showMessage('Please enter a valid email address.', 'error');
                return;
            }
            
            this.submitSponsorForm(formData);
        },
        
        /**
         * Submit sponsor form via AJAX
         */
        submitSponsorForm: function(formData) {
            const self = this;
            const $submitBtn = this.state.sponsorModal.find('.btn-submit');
            
            // Show loading state
            this.state.isLoading = true;
            $submitBtn.prop('disabled', true).text('Submitting...');
            
            $.ajax({
                url: cfk_public_ajax.ajaxurl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.handleSponsorSuccess(response.data);
                    } else {
                        self.handleSponsorError(response.data);
                    }
                },
                error: function() {
                    self.handleSponsorError({
                        message: cfk_public_ajax.messages.error
                    });
                },
                complete: function() {
                    self.state.isLoading = false;
                    $submitBtn.prop('disabled', false).text('Submit Sponsorship');
                }
            });
        },
        
        /**
         * Handle successful sponsor submission
         */
        handleSponsorSuccess: function(data) {
            this.showMessage(data.message || cfk_public_ajax.messages.success, 'success');
            
            // Close modal after delay
            setTimeout(() => {
                this.closeSponsorModal();
                
                // Remove or disable the sponsored child's button
                if (this.state.selectedChild) {
                    const $card = $(`.cfk-child-card[data-child-id="${this.state.selectedChild.id}"]`);
                    const $button = $card.find('.cfk-sponsor-btn');
                    
                    $button.prop('disabled', true)
                           .text('Sponsorship Submitted')
                           .removeClass('cfk-sponsor-btn')
                           .addClass('cfk-sponsored');
                }
                
            }, 2000);
        },
        
        /**
         * Handle sponsor submission error
         */
        handleSponsorError: function(data) {
            this.showMessage(data.message || cfk_public_ajax.messages.error, 'error');
        },
        
        /**
         * Show loading state
         */
        showLoadingState: function() {
            if (!this.cache.$wrapper.find('.cfk-loading-overlay').length) {
                this.cache.$wrapper.append('<div class="cfk-loading-overlay cfk-loading">Loading children...</div>');
            }
            this.state.isLoading = true;
        },
        
        /**
         * Hide loading state
         */
        hideLoadingState: function() {
            this.cache.$wrapper.find('.cfk-loading-overlay').remove();
            this.state.isLoading = false;
        },
        
        /**
         * Show message to user
         */
        showMessage: function(message, type) {
            // Remove existing messages
            $('.cfk-message').remove();
            
            const $message = $('<div class="cfk-message cfk-message-' + type + '">' + message + '</div>');
            
            this.state.sponsorModal.find('.cfk-modal-body').prepend($message);
            
            // Auto-hide after delay
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut();
                }, 3000);
            }
        },
        
        /**
         * Utility: Email validation
         */
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        /**
         * Utility: Debounce function
         */
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Only initialize if we have the children wrapper
        if ($('.cfk-children-wrapper').length) {
            CFK_Public.init();
        }
    });
    
    // Expose to global scope for debugging
    window.CFK_Public = CFK_Public;
    
})(jQuery);