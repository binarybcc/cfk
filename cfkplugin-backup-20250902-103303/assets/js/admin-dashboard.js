/**
 * CFK Admin Dashboard JavaScript
 * Following WordPress and Universal Methodology standards
 */

// Use IIFE (Immediately Invoked Function Expression) to create private scope
(function($) {
    'use strict';

    // Define CFK Dashboard namespace
    window.CFKDashboard = window.CFKDashboard || {};

    // Dashboard management class
    CFKDashboard.Manager = {
        
        // Configuration (populated via wp_localize_script)
        config: window.cfkDashboardConfig || {},
        
        /**
         * Initialize dashboard functionality
         */
        init: function() {
            this.bindEvents();
            this.initCharts();
        },

        /**
         * Bind event handlers using event delegation
         */
        bindEvents: function() {
            const self = this;
            
            // Sponsorship toggle handler
            $(document).on('change', '#cfk-toggle-sponsorships', function() {
                self.handleSponsorshipToggle($(this));
            });

            // Export button handlers
            $(document).on('click', '.cfk-export-btn', function(e) {
                e.preventDefault();
                self.handleExport($(this));
            });

            // Refresh stats handler
            $(document).on('click', '.cfk-refresh-stats-btn', function(e) {
                e.preventDefault();
                self.refreshStats($(this));
            });

            // Bulk reminder handler
            $(document).on('click', '.cfk-bulk-reminder-btn', function(e) {
                e.preventDefault();
                self.handleBulkReminder($(this));
            });
        },

        /**
         * Initialize charts when Chart.js is loaded
         */
        initCharts: function() {
            const self = this;
            
            if (typeof Chart === 'undefined') {
                // Wait for Chart.js to load
                setTimeout(function() {
                    self.initCharts();
                }, 100);
                return;
            }

            if (!this.config.chartData) {
                console.warn('CFK Dashboard: Chart data not available');
                return;
            }

            this.initProgressChart();
            this.initAgeChart();
        },

        /**
         * Initialize progress chart (doughnut)
         */
        initProgressChart: function() {
            const ctx = document.getElementById('cfk-progress-chart');
            if (!ctx || !this.config.chartData.progress) return;

            const progressData = this.config.chartData.progress;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [
                        this.config.labels.sponsored, 
                        this.config.labels.available
                    ],
                    datasets: [{
                        data: [progressData.sponsored, progressData.available],
                        backgroundColor: [
                            'var(--cfk-secondary)', 
                            'var(--cfk-danger)'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        /**
         * Initialize age range chart (bar)
         */
        initAgeChart: function() {
            const ctx = document.getElementById('cfk-age-chart');
            if (!ctx || !this.config.chartData.ageBreakdown) return;

            const ageData = this.config.chartData.ageBreakdown;
            const labels = Object.keys(ageData);
            const sponsored = labels.map(label => ageData[label].sponsored);
            const available = labels.map(label => ageData[label].available);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: this.config.labels.sponsored,
                            data: sponsored,
                            backgroundColor: 'var(--cfk-secondary)'
                        },
                        {
                            label: this.config.labels.available,
                            data: available,
                            backgroundColor: 'var(--cfk-danger)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            stacked: true,
                            beginAtZero: true
                        },
                        x: {
                            stacked: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        /**
         * Handle sponsorship toggle
         */
        handleSponsorshipToggle: function($toggle) {
            const isChecked = $toggle.is(':checked');
            const self = this;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cfk_toggle_sponsorships',
                    nonce: this.config.nonce,
                    status: isChecked ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(response.data, 'success');
                    } else {
                        self.showNotification(response.data || self.config.labels.error, 'error');
                        $toggle.prop('checked', !isChecked);
                    }
                },
                error: function() {
                    self.showNotification(self.config.labels.networkError, 'error');
                    $toggle.prop('checked', !isChecked);
                }
            });
        },

        /**
         * Handle data export
         */
        handleExport: function($button) {
            const exportType = $button.data('type');
            const originalText = $button.text();
            const self = this;

            if (!exportType) return;

            $button.text(this.config.labels.exporting).prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cfk_export_data',
                    nonce: this.config.nonce,
                    export_type: exportType
                },
                success: function(response) {
                    $button.text(originalText).prop('disabled', false);
                    
                    if (response.success) {
                        // Trigger download via hidden form or window.open
                        if (response.data.url) {
                            window.open(response.data.url, '_blank');
                        }
                        self.showNotification(self.config.labels.exportSuccess, 'success');
                    } else {
                        self.showNotification(response.data || self.config.labels.error, 'error');
                    }
                },
                error: function() {
                    $button.text(originalText).prop('disabled', false);
                    self.showNotification(self.config.labels.networkError, 'error');
                }
            });
        },

        /**
         * Refresh dashboard statistics
         */
        refreshStats: function($button) {
            const originalText = $button.text();
            const self = this;

            $button.text(this.config.labels.refreshing).prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cfk_dashboard_stats',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    $button.text(originalText).prop('disabled', false);
                    
                    if (response.success) {
                        // Update dashboard stats in DOM
                        self.updateStatsDisplay(response.data);
                        self.showNotification(self.config.labels.statsUpdated, 'success');
                    } else {
                        self.showNotification(response.data || self.config.labels.error, 'error');
                    }
                },
                error: function() {
                    $button.text(originalText).prop('disabled', false);
                    self.showNotification(self.config.labels.networkError, 'error');
                }
            });
        },

        /**
         * Handle bulk reminder sending
         */
        handleBulkReminder: function($button) {
            const reminderType = $('#bulk-reminder-type').val();
            if (!reminderType) return;

            const originalText = $button.text();
            const self = this;

            if (!confirm(this.config.labels.confirmBulkReminder)) {
                return;
            }

            $button.text(this.config.labels.sending).prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cfk_bulk_reminder',
                    nonce: this.config.nonce,
                    reminder_type: reminderType
                },
                success: function(response) {
                    $button.text(originalText).prop('disabled', false);
                    
                    if (response.success) {
                        self.showNotification(response.data, 'success');
                    } else {
                        self.showNotification(response.data || self.config.labels.error, 'error');
                    }
                },
                error: function() {
                    $button.text(originalText).prop('disabled', false);
                    self.showNotification(self.config.labels.networkError, 'error');
                }
            });
        },

        /**
         * Update stats display in DOM
         */
        updateStatsDisplay: function(stats) {
            // Update stat card values
            Object.keys(stats).forEach(function(key) {
                const $statCard = $(`.cfk-stat-card--${key} .cfk-stat-card__number`);
                if ($statCard.length) {
                    $statCard.text(stats[key].toLocaleString());
                }
            });
        },

        /**
         * Show notification to user
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            // Create notification element
            const $notification = $('<div>')
                .addClass(`notice notice-${type} is-dismissible`)
                .html(`<p>${message}</p>`)
                .hide()
                .prependTo('.cfk-dashboard')
                .slideDown();

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notification.slideUp(function() {
                    $(this).remove();
                });
            }, 5000);

            // Manual dismiss handler
            $notification.on('click', '.notice-dismiss', function() {
                $notification.slideUp(function() {
                    $(this).remove();
                });
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        CFKDashboard.Manager.init();
    });

})(jQuery); // Pass jQuery to the IIFE