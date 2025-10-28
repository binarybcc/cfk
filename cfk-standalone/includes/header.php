<?php
// Set strict Content Security Policy with nonces
$cspNonce = $_SESSION['csp_nonce'] ?? base64_encode(random_bytes(16));

// Build CSP header - allows Zeffy iframe and Alpine.js while maintaining security
$csp = implode('; ', [
    "default-src 'self'",
        "script-src 'self' 'nonce-{$cspNonce}' 'unsafe-eval' https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/ https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.1/ https://zeffy-scripts.s3.ca-central-1.amazonaws.com/",
        "style-src 'self' 'unsafe-inline'", // Allow inline styles for simplicity
        "img-src 'self' data: https:",
        "font-src 'self' data:",
        "frame-src https://www.zeffy.com", // Allow Zeffy iframe
        "connect-src 'self' https://www.zeffy.com https://*.zeffy.com https://zeffy-scripts.s3.ca-central-1.amazonaws.com",
        "base-uri 'self'",
        "form-action 'self' https://www.zeffy.com https://*.zeffy.com",
        "frame-ancestors 'none'", // Prevent site from being iframed (replaces X-Frame-Options)
        "upgrade-insecure-requests",
        "block-all-mixed-content"
    ]);

header("Content-Security-Policy: {$csp}");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo config('app_name'); ?></title>
    <meta name="description" content="<?php echo config('site_description', 'Connect with local children who need Christmas support'); ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo baseUrl('assets/css/styles.css?v=' . filemtime(__DIR__ . '/../assets/css/styles.css')); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo baseUrl('assets/images/favicon.ico'); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo baseUrl('assets/images/favicon-32x32.png'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo baseUrl('assets/images/favicon-16x16.png'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo baseUrl('assets/images/apple-touch-icon.png'); ?>">

    <!-- Alpine.js v3.14.1 - Progressive Enhancement -->
    <!-- Load Collapse plugin before Alpine.js core -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.1/dist/cdn.min.js" nonce="<?php echo $cspNonce; ?>"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" nonce="<?php echo $cspNonce; ?>"></script>

    <!-- Selections System v1.5 -->
    <script src="<?php echo baseUrl('assets/js/selections.js'); ?>" nonce="<?php echo $cspNonce; ?>"></script>

    <style nonce="<?php echo $cspNonce; ?>">
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
    <!-- Skip Navigation Link for Keyboard Users (WCAG 2.4.1) -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

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
        </div>
    </header>

    <!-- ARIA Live Region for Screen Reader Announcements (WCAG 4.1.3) -->
    <div id="a11y-announcements" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

    <!-- Auto-hide Header on Scroll Script -->
    <script nonce="<?php echo $cspNonce; ?>">
    (function() {
        let lastScrollTop = 0;
        let scrollTimer = null;
        const header = document.querySelector('.main-header');
        const scrollThreshold = 100; // Pixels to scroll before hiding

        function handleScroll() {
            // Clear previous timer
            if (scrollTimer) {
                clearTimeout(scrollTimer);
            }

            // Debounce scroll event for performance
            scrollTimer = setTimeout(function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                // Don't hide header if near top of page
                if (scrollTop < scrollThreshold) {
                    header.classList.remove('header-hidden');
                    lastScrollTop = scrollTop;
                    return;
                }

                // Scrolling down - hide header
                if (scrollTop > lastScrollTop && scrollTop > scrollThreshold) {
                    header.classList.add('header-hidden');
                }
                // Scrolling up - show header
                else if (scrollTop < lastScrollTop) {
                    header.classList.remove('header-hidden');
                }

                lastScrollTop = scrollTop;
            }, 10); // 10ms debounce
        }

        // Listen for scroll events
        window.addEventListener('scroll', handleScroll, { passive: true });
    })();
    </script>

    <main id="main-content" class="main-content" tabindex="-1">
        <div class="container">
            <?php
            // Display messages
            $message = getMessage();
            if ($message) : ?>
                <div class="alert alert-<?php echo $message['type']; ?>">
                    <?php echo sanitizeString($message['text']); ?>
                </div>
            <?php endif; ?>