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

// Get count statistics for hero section - total children and families this season
$totalChildren = getChildrenCount(); // All children regardless of status
$totalFamilies = Database::fetchRow("SELECT COUNT(DISTINCT family_id) as total FROM children")['total'] ?? 0; // All families
?>

<div class="home-page">
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
                        <strong><?php echo $totalChildren; ?></strong>
                        <span>Children We're Helping</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo $totalFamilies; ?></strong>
                        <span>Families We're Helping</span>
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

