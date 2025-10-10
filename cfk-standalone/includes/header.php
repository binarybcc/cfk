<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo config('app_name'); ?></title>
    <meta name="description" content="<?php echo config('site_description', 'Connect with local children who need Christmas support'); ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo baseUrl('assets/css/styles.css'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo baseUrl('assets/images/favicon.ico'); ?>">
    
    <!-- Zeffy donation integration -->
    <script src="https://zeffy-scripts.s3.ca-central-1.amazonaws.com/embed-form-script.min.js"></script>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1><a href="<?php echo baseUrl(); ?>"><?php echo config('app_name'); ?></a></h1>
                    <p class="tagline">Bringing Christmas joy to local children in need</p>
                </div>
                
                <nav class="main-nav">
                    <ul>
                        <li><a href="<?php echo baseUrl('?page=home'); ?>" <?php echo ($page ?? '') === 'home' ? 'class="active"' : ''; ?>>Home</a></li>
                        <li><a href="<?php echo baseUrl('?page=children'); ?>" <?php echo ($page ?? '') === 'children' ? 'class="active"' : ''; ?>>Children</a></li>
                        <li><a href="<?php echo baseUrl('?page=sponsor_lookup'); ?>" <?php echo in_array($page ?? '', ['sponsor_lookup', 'sponsor_portal']) ? 'class="active"' : ''; ?>>My Sponsorships</a></li>
                        <li><a href="<?php echo baseUrl('?page=about'); ?>" <?php echo ($page ?? '') === 'about' ? 'class="active"' : ''; ?>>About</a></li>
                        <li class="donate-link"><a href="<?php echo baseUrl('?page=donate'); ?>" class="donate-btn">Donate</a></li>
                        <li class="admin-link"><a href="<?php echo baseUrl('admin/'); ?>">Admin</a></li>
                    </ul>
                </nav>
            </div>
            
            <!-- Search Bar -->
            <div class="header-search">
                <form method="GET" action="<?php echo baseUrl(); ?>" class="search-form">
                    <input type="hidden" name="page" value="search">
                    <div class="search-group">
                        <input type="text" 
                               name="q" 
                               value="<?php echo isset($_GET['q']) ? sanitizeString($_GET['q']) : ''; ?>"
                               placeholder="Search by name, interests, or wishes..." 
                               class="search-input">
                        <button type="submit" class="search-btn">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <?php
            // Display messages
            $message = getMessage();
            if ($message): ?>
                <div class="alert alert-<?php echo $message['type']; ?>">
                    <?php echo sanitizeString($message['text']); ?>
                </div>
            <?php endif; ?>