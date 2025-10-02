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
                    <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
                    <li><a href="manage_children.php" <?php echo basename($_SERVER['PHP_SELF']) === 'manage_children.php' ? 'class="active"' : ''; ?>>Children</a></li>
                    <li><a href="manage_families.php" <?php echo basename($_SERVER['PHP_SELF']) === 'manage_families.php' ? 'class="active"' : ''; ?>>Families</a></li>
                    <li><a href="manage_sponsorships.php" <?php echo basename($_SERVER['PHP_SELF']) === 'manage_sponsorships.php' ? 'class="active"' : ''; ?>>Sponsorships</a></li>
                    <li><a href="reports.php" <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'class="active"' : ''; ?>>Reports</a></li>
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
        // Display messages
        $message = getMessage();
        if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?>">
                <?php echo sanitizeString($message['text']); ?>
            </div>
        <?php endif; ?>

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
</style>