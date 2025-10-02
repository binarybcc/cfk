/**
 * Christmas for Kids - Admin JavaScript
 * 
 * Administrative functionality for the plugin backend
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Main CFK Admin object
    const CFK_Admin = {
        
        // Configuration
        config: {
            debounceDelay: 300,
            animationDuration: 300,
            maxFileSize: 5 * 1024 * 1024, // 5MB
            allowedFileTypes: ['text/csv', 'application/csv', 'text/plain']
        },
        
        // Cache for DOM elements
        cache: {
            $document: null,
            $csvUploader: null,
            $importProgress: null,
            $importResults: null,
            $quickEditRows: null
        },
        
        // State management
        state: {
            isImporting: false,
            importData: null,
            dashboardStats: null
        },
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.cacheDOMElements();
            this.bindEvents();
            this.initializeComponents();
            this.loadDashboardStats();
            console.log('[CFK Admin] JavaScript initialized');
        },
        
        /**
         * Cache DOM elements for performance
         */
        cacheDOMElements: function() {
            this.cache.$document = $(document);
            this.cache.$csvUploader = $('#cfk-csv-file');
            this.cache.$importProgress = $('.cfk-import-progress');
            this.cache.$importResults = $('.cfk-import-results');
            this.cache.$quickEditRows = $('.inline-edit-row');
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;
            
            // CSV Import functionality
            if (this.cache.$csvUploader.length) {
                this.bindCSVImportEvents();
            }
            
            // Meta box functionality
            this.bindMetaBoxEvents();
            
            // Quick edit functionality
            this.bindQuickEditEvents();
            
            // Dashboard refresh
            this.cache.$document.on('click', '.cfk-refresh-stats', function(e) {
                e.preventDefault();
                self.loadDashboardStats(true);
            });
            
            // Bulk actions
            this.bindBulkActionEvents();
            
            // Form validation
            this.bindFormValidation();
        },
        
        /**
         * Initialize components
         */
        initializeComponents: function() {
            // Initialize tooltips if available
            if (typeof $.fn.tooltip !== 'undefined') {
                $('.cfk-tooltip').tooltip();
            }
            
            // Initialize file upload drag and drop
            this.initializeFileUpload();
            
            // Initialize form enhancements
            this.enhanceForms();
        },
        
        /**
         * Bind CSV import events
         */
        bindCSVImportEvents: function() {
            const self = this;
            
            // File input change
            this.cache.$csvUploader.on('change', function() {
                const file = this.files[0];
                self.validateCSVFile(file);
            });
            
            // Import form submit
            $('.cfk-import-form').on('submit', function(e) {
                e.preventDefault();
                self.handleCSVImport($(this));
            });
            
            // Download sample CSV
            $('.cfk-download-sample').on('click', function(e) {
                e.preventDefault();
                self.downloadSampleCSV();
            });
        },
        
        /**
         * Bind meta box events
         */
        bindMetaBoxEvents: function() {
            const self = this;
            
            // Availability status changes
            $('#cfk_availability_status').on('change', function() {
                self.handleAvailabilityChange($(this));
            });
            
            // Age validation
            $('#cfk_age').on('input', function() {
                self.validateAge($(this));
            });
            
            // Auto-save functionality
            $('.cfk-auto-save').on('change input', 
                this.debounce(function() {
                    self.autoSaveMetaData($(this));
                }, this.config.debounceDelay)
            );
        },
        
        /**
         * Bind quick edit events
         */
        bindQuickEditEvents: function() {
            const self = this;
            
            // Quick edit button click
            this.cache.$document.on('click', '.editinline', function() {
                setTimeout(function() {
                    self.populateQuickEditFields();
                }, 100);
            });
            
            // Quick edit save
            this.cache.$document.on('click', '.save', function() {
                self.handleQuickEditSave($(this));
            });
        },
        
        /**
         * Bind bulk action events
         */
        bindBulkActionEvents: function() {
            const self = this;
            
            // Bulk action form submit
            $('#posts-filter').on('submit', function(e) {
                const action = $('#bulk-action-selector-top').val();
                if (action.startsWith('cfk_')) {
                    self.handleBulkAction(action, e);
                }
            });
        },
        
        /**
         * Bind form validation
         */
        bindFormValidation: function() {
            // Real-time validation
            $('form input[required], form select[required]').on('blur', function() {
                this.setCustomValidity('');
                if (!this.validity.valid) {
                    this.setCustomValidity('This field is required');
                }
            });
            
            // Age validation
            $('#cfk_age').on('input', function() {
                const age = parseInt($(this).val());
                if (age < 0 || age > 25) {
                    this.setCustomValidity('Age must be between 0 and 25');
                } else {
                    this.setCustomValidity('');
                }
            });
        },
        
        /**
         * Initialize file upload with drag and drop
         */
        initializeFileUpload: function() {
            const $uploadArea = $('.cfk-file-upload');
            
            if (!$uploadArea.length) return;
            
            // Prevent default drag behaviors
            $(document).on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
            
            // Add hover class
            $uploadArea.on('dragover dragenter', function() {
                $(this).addClass('dragover');
            });
            
            $uploadArea.on('dragleave dragend drop', function() {
                $(this).removeClass('dragover');
            });
            
            // Handle file drop
            $uploadArea.on('drop', function(e) {
                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    $('#cfk-csv-file')[0].files = files;
                    $('#cfk-csv-file').trigger('change');
                }
            });
        },
        
        /**
         * Enhance forms with better UX
         */
        enhanceForms: function() {
            // Add loading states to form submissions
            $('form').on('submit', function() {
                const $submit = $(this).find('[type="submit"]');
                $submit.prop('disabled', true).addClass('cfk-loading');
                
                setTimeout(function() {
                    $submit.prop('disabled', false).removeClass('cfk-loading');
                }, 3000);
            });
            
            // Character counters for text areas
            $('textarea[maxlength]').each(function() {
                const $textarea = $(this);
                const maxLength = $textarea.attr('maxlength');
                const $counter = $('<div class="cfk-char-counter">0/' + maxLength + '</div>');
                
                $textarea.after($counter);
                
                $textarea.on('input', function() {
                    const currentLength = $(this).val().length;
                    $counter.text(currentLength + '/' + maxLength);
                    
                    if (currentLength > maxLength * 0.9) {
                        $counter.addClass('warning');
                    } else {
                        $counter.removeClass('warning');
                    }
                });
            });
        },
        
        /**
         * Load dashboard statistics
         */
        loadDashboardStats: function(refresh = false) {
            if (!refresh && this.state.dashboardStats) {
                return;
            }
            
            const self = this;
            const $statsContainer = $('.cfk-dashboard-stats');
            
            if (!$statsContainer.length) return;
            
            if (refresh) {
                $statsContainer.addClass('cfk-loading');
            }
            
            $.ajax({
                url: cfk_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cfk_ajax_handler',
                    cfk_action: 'get_dashboard_stats',
                    nonce: cfk_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateDashboardStats(response.data);
                        self.state.dashboardStats = response.data;
                    }
                },
                error: function() {
                    console.log('[CFK Admin] Failed to load dashboard stats');
                },
                complete: function() {
                    $statsContainer.removeClass('cfk-loading');
                }
            });
        },
        
        /**
         * Update dashboard statistics display
         */
        updateDashboardStats: function(stats) {
            $('.cfk-stat-number.total').text(stats.total_children);
            $('.cfk-stat-number.available').text(stats.available_children);
            $('.cfk-stat-number.sponsored').text(stats.sponsored_children);
        },
        
        /**
         * Validate CSV file
         */
        validateCSVFile: function(file) {
            if (!file) {
                this.showImportMessage('Please select a file.', 'error');
                return false;
            }
            
            // Check file size
            if (file.size > this.config.maxFileSize) {
                this.showImportMessage('File size must be less than 5MB.', 'error');
                return false;
            }
            
            // Check file type
            if (!this.config.allowedFileTypes.includes(file.type)) {
                this.showImportMessage('Please select a valid CSV file.', 'error');
                return false;
            }
            
            this.showImportMessage('File looks good! Click Import to proceed.', 'success');
            return true;
        },
        
        /**
         * Handle CSV import
         */
        handleCSVImport: function($form) {
            if (this.state.isImporting) return;
            
            const file = this.cache.$csvUploader[0].files[0];
            if (!this.validateCSVFile(file)) return;
            
            this.state.isImporting = true;
            
            const formData = new FormData();
            formData.append('csv_file', file);
            formData.append('action', 'cfk_csv_import');
            formData.append('nonce', cfk_ajax.csv_import_nonce);
            
            this.showImportProgress();
            this.updateImportProgress(0, 'Starting import...');
            
            const self = this;
            
            $.ajax({
                url: cfk_ajax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            self.updateImportProgress(percentComplete, 'Uploading...');
                        }
                    });
                    return xhr;
                },
                success: function(response) {
                    if (response.success) {
                        self.showImportResults(response.data, 'success');
                        self.cache.$csvUploader.val('');
                        
                        // Refresh dashboard stats
                        self.loadDashboardStats(true);
                    } else {
                        self.showImportResults(response.data, 'error');
                    }
                },
                error: function() {
                    self.showImportResults({
                        message: 'Import failed due to server error.'
                    }, 'error');
                },
                complete: function() {
                    self.state.isImporting = false;
                    self.hideImportProgress();
                }
            });
        },
        
        /**
         * Show import progress
         */
        showImportProgress: function() {
            this.cache.$importProgress.show();
            this.cache.$importResults.hide();
        },
        
        /**
         * Hide import progress
         */
        hideImportProgress: function() {
            this.cache.$importProgress.hide();
        },
        
        /**
         * Update import progress
         */
        updateImportProgress: function(percent, message) {
            $('.cfk-progress-fill').css('width', percent + '%');
            $('.cfk-progress-message').text(message);
        },
        
        /**
         * Show import results
         */
        showImportResults: function(data, type) {
            const $results = this.cache.$importResults;
            
            $results.removeClass('success error').addClass(type);
            $results.find('.cfk-results-message').text(data.message || 'Import completed');
            
            if (data.log) {
                $results.find('.cfk-import-log').html(data.log).show();
            } else {
                $results.find('.cfk-import-log').hide();
            }
            
            $results.show();
        },
        
        /**
         * Show import message
         */
        showImportMessage: function(message, type) {
            $('.cfk-import-message').remove();
            
            const $message = $('<div class="cfk-import-message cfk-notice ' + type + '">' + message + '</div>');
            $('.cfk-file-upload').after($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Download sample CSV
         */
        downloadSampleCSV: function() {
            const csvContent = [
                'name,age,gender,school_grade,interests,special_needs,clothing_size,shoe_size',
                'Emma Johnson,8,female,3,"Reading, Drawing, Soccer","None","Youth M","2"',
                'Michael Smith,10,male,5,"Basketball, Video Games, Science","None","Youth L","4"',
                'Sarah Davis,6,female,1,"Dance, Painting, Animals","Gluten-free diet","Youth S","1"'
            ].join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            
            a.href = url;
            a.download = 'cfk-sample-children.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },
        
        /**
         * Handle availability status change
         */
        handleAvailabilityChange: function($select) {
            const status = $select.val();
            const $badge = $('.cfk-status-badge span');
            
            // Update visual status badge
            const statusColors = {
                'available': '#00a32a',
                'selected': '#ff8c00',
                'sponsored': '#0073aa',
                'unavailable': '#d63638'
            };
            
            const statusLabels = {
                'available': 'Available',
                'selected': 'Selected',
                'sponsored': 'Sponsored',
                'unavailable': 'Unavailable'
            };
            
            $badge.css('background', statusColors[status] || '#6c757d')
                  .text(statusLabels[status] || 'Unknown');
            
            // Show/hide relevant information
            $('.cfk-status-info').hide();
            $('.cfk-status-' + status).show();
        },
        
        /**
         * Validate age input
         */
        validateAge: function($input) {
            const age = parseInt($input.val());
            const $feedback = $input.siblings('.cfk-validation-feedback');
            
            if (isNaN(age) || age < 0 || age > 25) {
                if (!$feedback.length) {
                    $input.after('<div class="cfk-validation-feedback error">Age must be between 0 and 25</div>');
                }
                $input.addClass('error');
            } else {
                $feedback.remove();
                $input.removeClass('error');
            }
        },
        
        /**
         * Auto-save meta data
         */
        autoSaveMetaData: function($field) {
            const postId = $('#post_ID').val();
            if (!postId) return;
            
            const fieldName = $field.attr('name');
            const fieldValue = $field.val();
            
            $.ajax({
                url: cfk_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cfk_auto_save_meta',
                    post_id: postId,
                    field_name: fieldName,
                    field_value: fieldValue,
                    nonce: cfk_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $field.addClass('saved').removeClass('error');
                        setTimeout(function() {
                            $field.removeClass('saved');
                        }, 2000);
                    }
                }
            });
        },
        
        /**
         * Populate quick edit fields
         */
        populateQuickEditFields: function() {
            const $row = $('.inline-edit-row');
            const $prevRow = $row.prev();
            
            if (!$prevRow.length) return;
            
            // Get data from the list table row
            const age = $prevRow.find('.column-child_age').text().replace(' years', '');
            const availability = $prevRow.find('[style*="background"]').text().toLowerCase();
            
            // Populate quick edit fields
            $row.find('[name="_cfk_age"]').val(age);
            $row.find('[name="_cfk_availability_status"]').val(availability);
        },
        
        /**
         * Handle quick edit save
         */
        handleQuickEditSave: function($button) {
            const $row = $button.closest('.inline-edit-row');
            const postId = $row.find('[name="post_ID"]').val();
            
            // Additional validation or processing could go here
            console.log('[CFK Admin] Quick edit save for post:', postId);
        },
        
        /**
         * Handle bulk actions
         */
        handleBulkAction: function(action, event) {
            const selectedPosts = $('input[name="post[]"]:checked').length;
            
            if (selectedPosts === 0) {
                alert('Please select at least one child.');
                event.preventDefault();
                return false;
            }
            
            let confirmMessage = '';
            
            switch (action) {
                case 'cfk_make_available':
                    confirmMessage = 'Make ' + selectedPosts + ' children available for sponsorship?';
                    break;
                case 'cfk_make_unavailable':
                    confirmMessage = 'Make ' + selectedPosts + ' children unavailable for sponsorship?';
                    break;
                case 'cfk_export_csv':
                    confirmMessage = 'Export ' + selectedPosts + ' children to CSV?';
                    break;
            }
            
            if (confirmMessage && !confirm(confirmMessage)) {
                event.preventDefault();
                return false;
            }
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
        },
        
        /**
         * Show notification to user
         */
        showNotification: function(message, type = 'info') {
            const $notification = $('<div class="cfk-notification cfk-notification-' + type + '">' + message + '</div>');
            
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.addClass('show');
            }, 100);
            
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize on admin pages
        if (window.location.href.indexOf('wp-admin') !== -1) {
            CFK_Admin.init();
        }
    });
    
    // Expose to global scope for debugging
    window.CFK_Admin = CFK_Admin;
    
})(jQuery);