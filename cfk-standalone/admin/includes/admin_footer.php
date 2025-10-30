    </main>

    <?php
    // Ensure CSP nonce is defined (should be set by admin_header.php)
    if (!isset($cspNonce)) {
        $cspNonce = bin2hex(random_bytes(16));
    }
    ?>

    <footer class="admin-footer">
        <div class="admin-footer-content">
            <p>&copy; <?php echo date('Y'); ?> Christmas for Kids - Administration Panel</p>
            <p class="admin-version">
                Version <?php echo config('app_version'); ?> |
                <a href="<?php echo baseUrl(); ?>" target="_blank">View Public Site</a>
            </p>
        </div>
    </footer>

    <!-- Centralized Admin JavaScript -->
    <script src="<?php echo baseUrl('admin/assets/admin.js'); ?>" nonce="<?php echo $cspNonce; ?>"></script>

    <!-- Additional inline JavaScript -->
    <script nonce="<?php echo $cspNonce; ?>">
        // Simple confirmation for delete actions
        document.addEventListener('DOMContentLoaded', function() {
            const deleteLinks = document.querySelectorAll('.delete-action, .btn-danger[href*="delete"]');
            deleteLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });

        // Form validation helper
        function validateForm(formId, rules) {
            const form = document.getElementById(formId);
            if (!form) return true;

            let isValid = true;
            const errors = [];

            for (const field in rules) {
                const input = form.querySelector(`[name="${field}"]`);
                if (!input) continue;

                const value = input.value.trim();
                const rule = rules[field];

                // Required field validation
                if (rule.required && !value) {
                    isValid = false;
                    errors.push(`${rule.label || field} is required`);
                    input.classList.add('error');
                }

                // Email validation
                if (rule.email && value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        isValid = false;
                        errors.push(`${rule.label || field} must be a valid email`);
                        input.classList.add('error');
                    }
                }

                // Min length validation
                if (rule.minLength && value && value.length < rule.minLength) {
                    isValid = false;
                    errors.push(`${rule.label || field} must be at least ${rule.minLength} characters`);
                    input.classList.add('error');
                }

                // Remove error class on valid input
                if (value && !input.classList.contains('error')) {
                    input.classList.remove('error');
                }
            }

            if (!isValid) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
            }

            return isValid;
        }

        // Add error styling
        const style = document.createElement('style');
        style.textContent = `
            .error {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

<style>
.admin-footer {
    margin-top: 4rem;
    background: #2c5530;
    color: white;
    padding: 1.5rem 0;
}

.admin-footer-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
    text-align: center;
}

.admin-footer-content p {
    margin-bottom: 0.5rem;
}

.admin-version {
    font-size: 0.85rem;
    opacity: 0.8;
}

.admin-version a {
    color: #ccc;
    text-decoration: none;
}

.admin-version a:hover {
    color: white;
    text-decoration: underline;
}

/* Alert styling for admin */
.alert {
    position: relative;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-error,
.alert-danger {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

.alert-info {
    background: #cce7ff;
    border: 1px solid #b6d7ff;
    color: #004085;
}
</style>