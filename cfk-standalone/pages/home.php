<?php

/**
 * Home Page
 * Welcome page with overview and quick access to children
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

$pageTitle = 'Home';

// Get count statistics for hero section
$totalAvailable = getChildrenCount(['status' => 'available']);
$totalFamilies = Database::fetchRow("SELECT COUNT(DISTINCT family_id) as total FROM children WHERE status = 'available'")['total'] ?? 0;
?>

<div class="home-page">
    <!-- TEST DATA BANNER -->
    <div style="background: #ff6b6b; color: white; padding: 1.5rem; text-align: center; font-weight: bold; font-size: 1.2rem; border-bottom: 4px solid #c92a2a; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        ⚠️ NOTICE: These are TEST children only - Not real sponsorship data ⚠️
        <div style="font-size: 0.9rem; font-weight: normal; margin-top: 0.5rem;">
            We are currently testing the system. All children displayed are sample data for demonstration purposes.
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-content">
                <h1>Make Christmas Magical for a Child in Need</h1>
                <p class="hero-subtitle">
                    Every child deserves to experience the wonder and joy of Christmas morning.
                    Your generosity can make that dream come true for local families facing difficult times.
                </p>
                <div class="hero-stats">
                    <div class="stat">
                        <strong><?php echo $totalAvailable; ?></strong>
                        <span>Children Need Sponsors</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo $totalFamilies; ?></strong>
                        <span>Families Seeking Help</span>
                    </div>
                </div>
            </div>
            <div class="hero-actions">
                <?php echo renderButton(
                    'View Children Needing Sponsorship',
                    baseUrl('?page=children'),
                    'primary',
                    ['size' => 'large', 'class' => 'hero-cta-primary']
                ); ?>
                <?php echo renderButton(
                    'Make a Donation',
                    baseUrl('?page=donate'),
                    'success',
                    ['size' => 'large']
                ); ?>
            </div>
        </div>
    </section>
</div>

