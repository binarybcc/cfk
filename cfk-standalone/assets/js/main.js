/**
 * Christmas for Kids - Main JavaScript
 * Client-side functionality for the sponsorship system
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Zeffy donation buttons
    initializeZeffyButtons();
    
    // Setup search functionality
    setupSearch();
    
    // Setup form enhancements
    setupForms();
    
    // Setup image lazy loading
    setupLazyLoading();
});

/**
 * Initialize Zeffy donation buttons
 */
function initializeZeffyButtons() {
    const zeffyButtons = document.querySelectorAll('[zeffy-form-link]');
    zeffyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const link = this.getAttribute('zeffy-form-link');
            if (link && typeof window.zeffyOpen === 'function') {
                window.zeffyOpen(link);
            } else {
                // Fallback: open in new window
                window.open(link, '_blank', 'width=600,height=700');
            }
        });
    });
}

/**
 * Setup search functionality
 */
function setupSearch() {
    const searchInput = document.querySelector('.search-input');
    const searchForm = document.querySelector('.search-form');
    
    if (searchInput && searchForm) {
        // Auto-submit after user stops typing (debounced)
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    searchForm.submit();
                }
            }, 1000);
        });
        
        // Clear button for search
        if (searchInput.value) {
            addClearButton(searchInput);
        }
    }
}

/**
 * Add clear button to search input
 */
function addClearButton(input) {
    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.innerHTML = '×';
    clearBtn.className = 'search-clear-btn';
    clearBtn.title = 'Clear search';
    
    clearBtn.addEventListener('click', function() {
        input.value = '';
        input.focus();
        input.form.submit();
    });
    
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(clearBtn);
    
    // Style the clear button
    const style = document.createElement('style');
    style.textContent = `
        .search-clear-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 20px;
            color: #666;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .search-clear-btn:hover {
            color: #333;
        }
    `;
    document.head.appendChild(style);
}

/**
 * Setup form enhancements
 */
function setupForms() {
    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Loading...';
                
                // Re-enable after 10 seconds (failsafe)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = submitBtn.getAttribute('data-original-text') || 'Submit';
                }, 10000);
            }
        });
    });
    
    // Store original button text
    document.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(btn => {
        btn.setAttribute('data-original-text', btn.textContent || btn.value);
    });
}

/**
 * Setup lazy loading for images
 */
function setupLazyLoading() {
    const images = document.querySelectorAll('img[loading="lazy"]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
}

/**
 * Utility functions
 */

// Smooth scroll to element
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

// Simple toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    // Style the toast
    Object.assign(toast.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '1rem 1.5rem',
        borderRadius: '6px',
        color: 'white',
        fontWeight: 'bold',
        zIndex: '9999',
        opacity: '0',
        transform: 'translateY(-20px)',
        transition: 'all 0.3s ease'
    });
    
    // Set background color based on type
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    toast.style.backgroundColor = colors[type] || colors.info;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 10);
    
    // Remove after 5 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Format numbers with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Simple client-side form validation
function validateForm(form, rules) {
    let isValid = true;
    const errors = [];
    
    for (const fieldName in rules) {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (!field) continue;
        
        const value = field.value.trim();
        const rule = rules[fieldName];
        
        // Required validation
        if (rule.required && !value) {
            isValid = false;
            errors.push(`${rule.label || fieldName} is required`);
            field.classList.add('error');
        }
        
        // Email validation
        if (rule.email && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errors.push(`${rule.label || fieldName} must be a valid email`);
                field.classList.add('error');
            }
        }
        
        // Min length validation
        if (rule.minLength && value && value.length < rule.minLength) {
            isValid = false;
            errors.push(`${rule.label || fieldName} must be at least ${rule.minLength} characters`);
            field.classList.add('error');
        }
        
        // Max length validation
        if (rule.maxLength && value && value.length > rule.maxLength) {
            isValid = false;
            errors.push(`${rule.label || fieldName} must be no more than ${rule.maxLength} characters`);
            field.classList.add('error');
        }
        
        // Remove error class if field is now valid
        if (value && !field.classList.contains('error')) {
            field.classList.remove('error');
        }
    }
    
    if (!isValid) {
        showToast(errors.join('\n'), 'error');
    }
    
    return isValid;
}

// Add error styling
const errorStyles = document.createElement('style');
errorStyles.textContent = `
    .error {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }
    
    img.loaded {
        opacity: 1;
        transition: opacity 0.3s ease;
    }
    
    img[loading="lazy"] {
        opacity: 0;
    }
`;
document.head.appendChild(errorStyles);