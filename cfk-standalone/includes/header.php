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
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo baseUrl('assets/images/favicon-32x32.png'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo baseUrl('assets/images/favicon-16x16.png'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo baseUrl('assets/images/apple-touch-icon.png'); ?>">

    <!-- Alpine.js v1.4 - Progressive Enhancement -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <!-- Selections System v1.5 -->
    <script src="<?php echo baseUrl('assets/js/selections.js'); ?>"></script>

    <style>
        /* Alpine.js Cloak - Prevent Flash of Unstyled Content */
        [x-cloak] {
            display: none !important;
        }

        /* Logo Styling */
        .logo-link {
            display: block;
            text-decoration: none;
        }

        .logo-image {
            height: 70px;
            width: auto;
            max-width: 100%;
            display: block;
        }

        .logo-text-fallback {
            margin: 0;
            color: #2c5530;
            font-size: 1.8em;
        }

        .logo-text-fallback a {
            color: #2c5530;
            text-decoration: none;
        }

        /* Mobile Logo - More compact */
        @media (max-width: 768px) {
            .logo-image {
                height: 45px;
            }

            .tagline {
                font-size: 0.75rem;
                margin-top: 0.25rem;
            }
        }

        /* Hamburger Menu Button */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: 2px solid #2c5530;
            padding: 0.5rem;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .mobile-menu-toggle:hover {
            background: #f8f9fa;
        }

        .mobile-menu-toggle span {
            display: block;
            width: 25px;
            height: 3px;
            background: #2c5530;
            margin: 5px 0;
            transition: all 0.3s ease;
        }

        @media (max-width: 968px) {
            .mobile-menu-toggle {
                display: block;
            }
        }

        /* Selections Badge */
        .selections-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #c41e3a;
            color: white;
            border-radius: 12px;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            font-size: 12px;
            font-weight: 700;
            margin-left: 6px;
            line-height: 1;
        }

        .selections-link a {
            display: flex;
            align-items: center;
        }
    </style>
    
    <!-- Zeffy donation integration -->
    <script src="https://zeffy-scripts.s3.ca-central-1.amazonaws.com/embed-form-script.min.js"></script>
</head>
<body>
    <header class="main-header" x-data="{ mobileMenuOpen: false, isDesktop: window.innerWidth > 968 }"
            x-init="window.addEventListener('resize', () => { isDesktop = window.innerWidth > 968 })">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="<?php echo baseUrl(); ?>" class="logo-link">
                        <img src="<?php echo baseUrl('assets/images/cfk-horizontal.png'); ?>"
                             alt="Christmas for Kids"
                             class="logo-image"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <h1 class="logo-text-fallback" style="display:none;"><?php echo config('app_name'); ?></h1>
                    </a>
                    <p class="tagline">Bringing Christmas joy to local children in need</p>
                </div>

                <!-- Mobile Menu Toggle Button -->
                <button class="mobile-menu-toggle"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        :aria-expanded="mobileMenuOpen"
                        aria-label="Toggle navigation menu">
                    <span :style="mobileMenuOpen ? 'transform: rotate(45deg) translateY(8px)' : ''"></span>
                    <span :style="mobileMenuOpen ? 'opacity: 0' : ''"></span>
                    <span :style="mobileMenuOpen ? 'transform: rotate(-45deg) translateY(-8px)' : ''"></span>
                </button>

                <nav class="main-nav" :class="{ 'mobile-nav-open': mobileMenuOpen }">
                    <ul>
                        <li><a href="<?php echo baseUrl('?page=home'); ?>" <?php echo ($page ?? '') === 'home' ? 'class="active"' : ''; ?>>Home</a></li>
                        <li><a href="<?php echo baseUrl('?page=children'); ?>" <?php echo ($page ?? '') === 'children' ? 'class="active"' : ''; ?>>Children</a></li>
                        <li><a href="<?php echo baseUrl('?page=how_to_apply'); ?>" <?php echo ($page ?? '') === 'how_to_apply' ? 'class="active"' : ''; ?>>How to Apply</a></li>
                        <li class="selections-link">
                            <a href="<?php echo baseUrl('?page=my_sponsorships'); ?>" <?php echo in_array($page ?? '', ['my_sponsorships', 'selections', 'sponsor_lookup', 'sponsor_portal']) ? 'class="active"' : ''; ?>>
                                My Sponsorships
                                <span id="selections-badge" class="selections-badge">0</span>
                            </a>
                        </li>
                        <li><a href="<?php echo baseUrl('?page=about'); ?>" <?php echo ($page ?? '') === 'about' ? 'class="active"' : ''; ?>>About</a></li>
                        <li class="admin-link"><a href="<?php echo baseUrl('admin/'); ?>">Admin</a></li>
                    </ul>
                </nav>
            </div>

            <!-- Search Bar - Always visible on desktop, toggle with menu on mobile -->
            <div class="header-search" :class="{ 'search-visible': isDesktop || mobileMenuOpen }">
                <form method="GET" action="<?php echo baseUrl(); ?>" class="search-form">
                    <input type="hidden" name="page" value="children">
                    <div class="search-group">
                        <input type="text"
                               name="search"
                               value="<?php echo isset($_GET['search']) ? sanitizeString($_GET['search']) : ''; ?>"
                               placeholder="Search children..."
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