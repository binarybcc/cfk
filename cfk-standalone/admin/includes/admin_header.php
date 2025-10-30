<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>CFK Admin</title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo baseUrl('assets/css/styles.css'); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo baseUrl('assets/images/favicon.ico'); ?>">

    <!-- Alpine.js for reactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Scroll Position Preservation - Execute BEFORE page renders -->
    <script>
        // Restore scroll position immediately (before any rendering)
        (function() {
            var savedScroll = sessionStorage.getItem('cfk_admin_scroll');
            if (savedScroll) {
                // Restore on DOMContentLoaded (most reliable timing)
                document.addEventListener('DOMContentLoaded', function() {
                    window.scrollTo(0, parseInt(savedScroll));
                    sessionStorage.removeItem('cfk_admin_scroll');
                });
            }
        })();
    </script>
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo">
                <h1><a href="index.php">CFK Admin</a></h1>
                <p class="admin-tagline">Christmas for Kids Administration</p>
            </div>
            
            <nav class="admin-nav">
                <ul>
                    <li><a href="index.php" <?php echo basename((string) $_SERVER['PHP_SELF']) === 'index.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
                    <li><a href="manage_children.php" <?php echo basename((string) $_SERVER['PHP_SELF']) === 'manage_children.php' ? 'class="active"' : ''; ?>>Children</a></li>
                    <li><a href="manage_sponsorships.php" <?php echo basename((string) $_SERVER['PHP_SELF']) === 'manage_sponsorships.php' ? 'class="active"' : ''; ?>>Sponsorships</a></li>
                    <li><a href="reports.php" <?php echo basename((string) $_SERVER['PHP_SELF']) === 'reports.php' ? 'class="active"' : ''; ?>>Reports</a></li>
                    <?php if ($_SESSION['cfk_admin_role'] === 'admin') : ?>
                    <li><a href="manage_admins.php" <?php echo basename((string) $_SERVER['PHP_SELF']) === 'manage_admins.php' ? 'class="active"' : ''; ?>>Administrators</a></li>
                    <?php endif; ?>
                    <li><a href="year_end_reset.php" <?php echo basename((string) $_SERVER['PHP_SELF']) === 'year_end_reset.php' ? 'class="active"' : ''; ?> style="color: #dc3545;">Year-End Reset</a></li>
                </ul>
            </nav>
            
            <div class="admin-user">
                <span class="user-greeting">
                    Welcome, <?php echo sanitizeString($_SESSION['cfk_admin_username'] ?? 'Admin'); ?>
                </span>
                <div class="user-actions">
                    <a href="<?php echo baseUrl(); ?>" class="btn btn-small btn-secondary" target="_blank">View Site</a>
                    <a href="logout.php" class="btn btn-small btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="admin-content">
        <?php
        // Display messages - support both session-based and direct variable approaches
        $displayMessage = getMessage(); // Check session first
        if (!$displayMessage && isset($message) && !empty($message)) {
            // Fallback to direct variables (for pages not using session-based messaging)
            $displayMessage = [
                'text' => $message,
                'type' => $messageType ?? 'success'
            ];
        }

        // Persistent error messages (for import errors and critical messages)
        if ($displayMessage && $displayMessage['type'] === 'error') : ?>
            <div class="persistent-alert persistent-alert-error">
                <strong>❌ Error: </strong>
                <?php echo sanitizeString($displayMessage['text']); ?>
            </div>
        <?php elseif ($displayMessage) : ?>
            <!-- Toast notification for success/info messages -->
            <script>
                // Show toast notification without disrupting page
                (function() {
                    var toast = document.createElement('div');
                    toast.className = 'cfk-toast cfk-toast-<?php echo $displayMessage['type']; ?>';
                    toast.innerHTML = '<span class="cfk-toast-icon"><?php
                        if ($displayMessage['type'] === 'success') echo '✓';
                        elseif ($displayMessage['type'] === 'error') echo '✕';
                        elseif ($displayMessage['type'] === 'warning') echo '⚠';
                    ?></span><span class="cfk-toast-text"><?php echo addslashes(sanitizeString($displayMessage['text'])); ?></span>';

                    document.body.appendChild(toast);

                    // Auto-dismiss after 1.5 seconds
                    setTimeout(function() {
                        toast.classList.add('cfk-toast-hiding');
                        setTimeout(function() { toast.remove(); }, 300);
                    }, 1500);
                })();
            </script>
        <?php endif; ?>

        <script>
            // Save scroll position before ANY page navigation
            (function() {
                // Save before page unloads
                window.addEventListener('beforeunload', function() {
                    sessionStorage.setItem('cfk_admin_scroll', window.scrollY.toString());
                });

                // Also save on form submit (backup)
                document.addEventListener('DOMContentLoaded', function() {
                    var forms = document.querySelectorAll('form');
                    forms.forEach(function(form) {
                        form.addEventListener('submit', function() {
                            sessionStorage.setItem('cfk_admin_scroll', window.scrollY.toString());
                        });
                    });
                });
            })();
        </script>

<style>
.admin-body {
    background: #f5f7fa;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.admin-header {
    background: white;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.admin-header-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.admin-logo h1 {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
}

.admin-logo h1 a {
    text-decoration: none;
    color: #2c5530;
    font-weight: bold;
}

.admin-tagline {
    font-size: 0.85rem;
    color: #666;
    margin: 0;
    font-style: italic;
}

.admin-nav ul {
    display: flex;
    list-style: none;
    gap: 0.5rem;
    margin: 0;
    padding: 0;
}

.admin-nav a {
    display: block;
    padding: 0.75rem 1rem;
    text-decoration: none;
    color: #333;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.2s;
    white-space: nowrap;
}

.admin-nav a:hover,
.admin-nav a.active {
    background: #2c5530;
    color: white;
}

.admin-user {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-greeting {
    font-weight: 500;
    color: #333;
    white-space: nowrap;
}

.user-actions {
    display: flex;
    gap: 0.5rem;
}

.admin-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
    min-height: calc(100vh - 200px);
}

/* Responsive Admin Header */
@media (max-width: 968px) {
    .admin-header-content {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .admin-nav ul {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .admin-user {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .user-greeting {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}

@media (max-width: 600px) {
    .admin-nav ul {
        flex-direction: column;
        gap: 0.25rem;
    }

    .admin-nav a {
        text-align: center;
    }

    .user-actions {
        flex-direction: column;
        width: 100%;
        max-width: 200px;
    }
}

/* Compact Toast Notification - Bottom of Screen */
.cfk-toast {
    position: fixed;
    bottom: 30px;
    right: 30px;
    padding: 12px 20px;
    background: white;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 999999;
    animation: slideUpIn 0.3s ease-out;
    max-width: 400px;
    border-left: 4px solid;
}

.cfk-toast-success {
    border-left-color: #28a745;
}

.cfk-toast-error {
    border-left-color: #dc3545;
}

.cfk-toast-warning {
    border-left-color: #ffc107;
}

.cfk-toast-icon {
    font-size: 1.25rem;
    font-weight: bold;
    line-height: 1;
}

.cfk-toast-success .cfk-toast-icon {
    color: #28a745;
}

.cfk-toast-error .cfk-toast-icon {
    color: #dc3545;
}

.cfk-toast-warning .cfk-toast-icon {
    color: #ffc107;
}

.cfk-toast-text {
    color: #333;
    font-size: 0.95rem;
    line-height: 1.4;
}

.cfk-toast-hiding {
    animation: slideDownOut 0.3s ease-out;
}

@keyframes slideUpIn {
    from {
        transform: translateY(100px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slideDownOut {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(100px);
        opacity: 0;
    }
}

/* Mobile responsive toast */
@media (max-width: 600px) {
    .cfk-toast {
        bottom: 20px;
        left: 20px;
        right: 20px;
        max-width: none;
    }
}

/* Persistent Alert Messages (for errors) */
.persistent-alert {
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    border-radius: 8px;
    border-left: 4px solid;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    line-height: 1.6;
}

.persistent-alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left-color: #dc3545;
}

.persistent-alert-success {
    background: #d4edda;
    color: #155724;
    border-left-color: #28a745;
}

.persistent-alert-warning {
    background: #fff3cd;
    color: #856404;
    border-left-color: #ffc107;
}

.persistent-alert strong {
    font-weight: 600;
}
</style>