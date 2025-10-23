/**
 * CFK Admin - Centralized JavaScript
 * Handles AJAX form submissions and dynamic UI updates across all admin pages
 * No page reloads = smooth UX
 */

(function() {
    'use strict';

    // Toast notification system (works across all pages)
    window.showToast = function(message, type) {
        const toast = document.createElement('div');
        toast.className = 'cfk-toast cfk-toast-' + type;
        const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : '⚠';
        toast.innerHTML = '<span class="cfk-toast-icon">' + icon + '</span><span class="cfk-toast-text">' + message + '</span>';

        document.body.appendChild(toast);

        // Auto-dismiss after 1.5 seconds
        setTimeout(function() {
            toast.classList.add('cfk-toast-hiding');
            setTimeout(function() { toast.remove(); }, 300);
        }, 1500);
    };

    // AJAX form submission handler (no page reload)
    window.submitFormViaAjax = function(form, options) {
        options = options || {};

        const formData = new FormData(form);
        const button = form.querySelector('button[type="submit"]');
        const originalText = button ? button.textContent : '';

        // Show loading state
        if (button) {
            button.disabled = true;
            button.textContent = options.loadingText || 'Processing...';
        }

        // Determine AJAX endpoint
        const endpoint = options.endpoint || 'ajax_handler.php';

        // Submit via AJAX
        return fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Show toast notification
            showToast(data.message, data.success ? 'success' : 'error');

            if (data.success && options.onSuccess) {
                // Call custom success handler
                options.onSuccess(data, form, button);
            } else if (!data.success) {
                // Re-enable button on error
                if (button) {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            }

            return data;
        })
        .catch(error => {
            showToast('An error occurred. Please try again.', 'error');
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
            throw error;
        });
    };

    // Dynamic button state updater (changes button color/text without reload)
    window.updateButtonState = function(button, newState, newText) {
        // Remove all state classes
        button.classList.remove('btn-log-pending', 'btn-complete-pending',
            'btn-unlog', 'btn-complete-logged', 'btn-completed');

        // Add new state class
        if (newState) {
            button.classList.add(newState);
        }

        // Update text
        if (newText) {
            button.textContent = newText;
        }

        // Handle disabled state
        if (newState === 'btn-completed') {
            button.disabled = true;
        } else {
            button.disabled = false;
        }
    };

    // Dynamic stats counter updater
    window.updateStatsCounter = function(statId, newValue) {
        const statElement = document.querySelector('[data-stat-id="' + statId + '"]');
        if (statElement) {
            // Animate the change
            statElement.style.transform = 'scale(1.2)';
            statElement.textContent = newValue;
            setTimeout(function() {
                statElement.style.transform = 'scale(1)';
            }, 200);
        }
    };

    // Auto-setup: Intercept all admin action forms
    document.addEventListener('DOMContentLoaded', function() {
        // Only run on admin pages
        if (!document.querySelector('.admin-body')) {
            return;
        }

        console.log('CFK Admin JS: Initializing centralized AJAX handlers');

        // Find all forms with data-ajax attribute
        const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');

        ajaxForms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const endpoint = form.getAttribute('data-ajax-endpoint') || 'ajax_handler.php';
                const onSuccessHandler = form.getAttribute('data-ajax-onsuccess');

                submitFormViaAjax(form, {
                    endpoint: endpoint,
                    onSuccess: onSuccessHandler ? window[onSuccessHandler] : null
                });
            });
        });
    });
})();
