<?php
// Temporary header for pre-launch landing page
// CSP nonce is generated in config.php and available as $cspNonce
global $cspNonce;

// Build CSP header - allows Zeffy iframe and Alpine.js while maintaining security
$csp = implode('; ', [
    "default-src 'self'",
        "script-src 'self' 'nonce-{$cspNonce}' 'unsafe-eval' 'unsafe-inline' https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/ https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.1/ https://zeffy-scripts.s3.ca-central-1.amazonaws.com/",
        "style-src 'self' 'unsafe-inline'", // Allow inline styles for simplicity
        "img-src 'self' data: https: http:", // Allow HTTP images for local development
        "font-src 'self' data:",
        "frame-src https://www.zeffy.com", // Allow Zeffy iframe
        "connect-src 'self' https://www.zeffy.com https://*.zeffy.com https://zeffy-scripts.s3.ca-central-1.amazonaws.com",
        "base-uri 'self'",
        "form-action 'self' https://www.zeffy.com https://*.zeffy.com",
        "frame-ancestors 'none'", // Prevent site from being iframed (replaces X-Frame-Options)
        // Note: upgrade-insecure-requests and block-all-mixed-content removed for local development compatibility
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

    <!-- Open Graph / Social Sharing Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo baseUrl(); ?>">
    <meta property="og:title" content="Christmas for Kids 2025 - Coming November 1st">
    <meta property="og:description" content="Child sponsorships open November 1, 2025 at 12:01 AM ET. Donate now to help make Christmas magical for local children in need. 100% of donations go directly to families.">
    <meta property="og:image" content="<?php echo baseUrl('assets/images/cfk-horizontal.png'); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="<?php echo config('app_name'); ?>">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo baseUrl(); ?>">
    <meta name="twitter:title" content="Christmas for Kids 2025 - Coming November 1st">
    <meta name="twitter:description" content="Child sponsorships open November 1, 2025 at 12:01 AM ET. Donate now to help make Christmas magical for local children in need.">
    <meta name="twitter:image" content="<?php echo baseUrl('assets/images/cfk-horizontal.png'); ?>">

    <!-- Additional Meta Tags -->
    <meta name="theme-color" content="#2c5530">
    <link rel="canonical" href="<?php echo baseUrl(); ?>">

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo baseUrl('assets/css/styles.css?v=' . filemtime(__DIR__ . '/../assets/css/styles.css')); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo baseUrl('assets/images/favicon.ico'); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo baseUrl('assets/images/favicon-32x32.png'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo baseUrl('assets/images/favicon-16x16.png'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo baseUrl('assets/images/apple-touch-icon.png'); ?>">

    <style nonce="<?php echo $cspNonce; ?>">
        /* Temporary Header Styles */
        .temp-header {
            background: white;
            border-bottom: 2px solid #2c5530;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .temp-header .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .temp-header .logo img {
            height: 60px;
            width: auto;
        }

        .temp-header nav ul {
            list-style: none;
            display: flex;
            gap: 2rem;
            margin: 0;
            padding: 0;
        }

        .temp-header nav a {
            color: #2c5530;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .temp-header nav a:hover {
            color: #c41e3a;
        }

        @media (max-width: 768px) {
            .temp-header .logo img {
                height: 45px;
            }

            .temp-header nav ul {
                gap: 1rem;
            }

            .temp-header nav a {
                font-size: 0.9rem;
            }
        }
    </style>

    <!-- Zeffy donation integration -->
    <script src="https://zeffy-scripts.s3.ca-central-1.amazonaws.com/embed-form-script.min.js" nonce="<?php echo $cspNonce; ?>"></script>

    <!-- Smooth scroll function for navigation -->
    <script nonce="<?php echo $cspNonce; ?>">
    function smoothScrollTo(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    // Attach event listeners to header navigation links
    document.addEventListener('DOMContentLoaded', function() {
        const scrollLinks = document.querySelectorAll('a[data-scroll-to]');
        scrollLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-scroll-to');
                smoothScrollTo(targetId);
            });
        });

        // Handle logo image error (fallback to text)
        const logoImage = document.getElementById('logo-image');
        if (logoImage) {
            // Only add error handler if image hasn't already loaded successfully
            if (logoImage.complete && logoImage.naturalWidth === 0) {
                // Image failed to load
                logoImage.style.display = 'none';
                document.getElementById('logo-text-fallback').style.display = 'block';
            } else if (!logoImage.complete) {
                // Image still loading, add error handler
                logoImage.addEventListener('error', function() {
                    this.style.display = 'none';
                    document.getElementById('logo-text-fallback').style.display = 'block';
                });
            }
        }
    });
    </script>
</head>
<body>
    <!-- Skip Navigation Link for Keyboard Users (WCAG 2.4.1) -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <header class="temp-header">
        <div class="container">
            <div class="logo">
                <img src="<?php echo baseUrl('assets/images/cfk-horizontal.png'); ?>"
                     alt="Christmas for Kids"
                     id="logo-image">
                <h1 id="logo-text-fallback" style="display:none; color: #2c5530; margin: 0;">
                    <?php echo config('app_name'); ?>
                </h1>
            </div>

            <nav>
                <ul>
                    <li><a href="#how-to-apply" data-scroll-to="how-to-apply">How to Apply</a></li>
                    <li><a href="<?php echo baseUrl('admin/'); ?>">Admin</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- ARIA Live Region for Screen Reader Announcements (WCAG 4.1.3) -->
    <div id="a11y-announcements" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

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
