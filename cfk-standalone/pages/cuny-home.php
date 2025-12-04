<?php

/**
 * Home Page - End of Season Version
 * Displays when sponsorships have ended for the current season
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

$pageTitle = 'Home';

// Hardcoded final season statistics (2024-2025 season totals)
$totalChildren = 1010;
$totalFamilies = 466;
?>

<div class="home-page">
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-content">
                <h1>Thank You for Making Christmas Magical</h1>
                <p class="hero-subtitle">
                    The sponsorship period for this season has ended. Thank you to everyone who helped bring joy to local families! Donations are still needed. If you've already sponsored children, you can still access your sponsorship details.
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
                <!-- Access Sponsorships Button -->
                <?php echo renderButton(
                    'Access My Sponsorships',
                    baseUrl('?page=my_sponsorships'),
                    'secondary',
                    ['size' => 'large']
                ); ?>

                <!-- Donation Button - Make it PRIMARY and more prominent -->
                <?php echo renderButton(
                    'Make a Donation Today',
                    baseUrl('?page=donate'),
                    'success',
                    ['size' => 'large', 'class' => 'hero-cta-primary donation-cta-pulse']
                ); ?>

                <!-- Check Donation Information Box -->
                <div class="check-donation-box">
                    <p>Donations by CHECK should be made out to <strong>Christmas for Kids</strong> and can be mailed to or dropped off at <strong>The Journal, 210 W North 1st Street, Seneca SC 29678</strong>.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Donation Impact Section -->
    <section class="donation-impact-section">
        <div class="impact-container">
            <h2>Your Donations Still Make a Difference</h2>
            <p class="impact-intro">
                While child sponsorships have closed for this season, <strong>financial donations are still urgently needed</strong> to help us meet our goals and serve every family with excellence.
            </p>

            <div class="impact-grid">
                <div class="impact-card">
                    <div class="impact-icon">🎁</div>
                    <h3>Fill the Gaps</h3>
                    <p>Help us provide additional gifts and essentials for families with the greatest needs.</p>
                </div>

                <div class="impact-card">
                    <div class="impact-icon">📦</div>
                    <h3>Program Support</h3>
                    <p>Cover operational costs including gift wrapping, distribution, and volunteer coordination.</p>
                </div>

                <div class="impact-card">
                    <div class="impact-icon">💝</div>
                    <h3>Emergency Assistance</h3>
                    <p>Provide last-minute support for urgent family situations that arise before Christmas.</p>
                </div>

                <div class="impact-card">
                    <div class="impact-icon">🌟</div>
                    <h3>Next Year's Hope</h3>
                    <p>Build capacity to serve even more children and families in future seasons.</p>
                </div>
            </div>

            <div class="donation-cta-box">
                <h3>Every Dollar Counts</h3>
                <p>Your tax-deductible donation directly supports local families in need. No amount is too small.</p>
                <div class="donation-buttons">
                    <?php echo renderButton(
                        'Donate Online Now',
                        baseUrl('?page=donate'),
                        'success',
                        ['size' => 'large', 'class' => 'donation-cta-pulse']
                    ); ?>
                </div>
                <p class="donation-methods-note">
                    <strong>Multiple ways to give:</strong> Online, check, or cash donations accepted
                </p>
            </div>
        </div>
    </section>

    <!-- Thank You Section -->
    <section class="thank-you-section">
        <div class="thank-you-container">
            <h2>Thank You to Our Generous Community</h2>
            <p>
                Every sponsor, donor, and volunteer makes Christmas possible for children who might otherwise go without.
                Your generosity transforms lives and creates memories that last a lifetime.
            </p>
            <p class="emphasis-text">
                Together, we're proving that our community cares for its most vulnerable families.
            </p>
        </div>
    </section>
</div>

<style>
/* Donation Impact Section */
.donation-impact-section {
    background: linear-gradient(to bottom, #f8fdf9 0%, #ffffff 100%);
    padding: 4rem 2rem;
    margin: 3rem 0;
    border-top: 3px solid var(--color-primary);
    border-bottom: 3px solid var(--color-primary);
}

.impact-container {
    max-width: 1200px;
    margin: 0 auto;
}

.impact-container h2 {
    text-align: center;
    color: var(--color-primary);
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.impact-intro {
    text-align: center;
    font-size: 1.25rem;
    color: var(--color-text-secondary);
    max-width: 800px;
    margin: 0 auto 3rem;
    line-height: 1.6;
}

.impact-intro strong {
    color: var(--color-primary);
    font-weight: 700;
}

.impact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.impact-card {
    background: white;
    border: 2px solid var(--color-border-lighter);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.impact-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(44, 85, 48, 0.15);
    border-color: var(--color-primary);
}

.impact-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.impact-card h3 {
    color: var(--color-primary);
    margin: 0 0 1rem 0;
    font-size: 1.5rem;
}

.impact-card p {
    color: var(--color-text-secondary);
    margin: 0;
    line-height: 1.6;
}

.donation-cta-box {
    background: linear-gradient(135deg, #2c5530 0%, #3a6d3f 100%);
    color: white;
    padding: 3rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 8px 24px rgba(44, 85, 48, 0.3);
}

.donation-cta-box h3 {
    color: white;
    font-size: 2rem;
    margin: 0 0 1rem 0;
}

.donation-cta-box p {
    color: white;
    font-size: 1.1rem;
    margin: 0 0 2rem 0;
}

.donation-buttons {
    margin: 2rem 0;
}

.donation-methods-note {
    margin-top: 1.5rem;
    font-size: 1rem;
    opacity: 0.9;
}

/* Pulse animation for donation button */
.donation-cta-pulse {
    animation: pulse-shadow 2s infinite;
}

@keyframes pulse-shadow {
    0%, 100% {
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
    50% {
        box-shadow: 0 4px 20px rgba(40, 167, 69, 0.6);
    }
}

/* Thank You Section */
.thank-you-section {
    padding: 3rem 2rem;
    background: white;
}

.thank-you-container {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.thank-you-container h2 {
    color: var(--color-primary);
    font-size: 2rem;
    margin-bottom: 1.5rem;
}

.thank-you-container p {
    color: var(--color-text-secondary);
    font-size: 1.1rem;
    line-height: 1.8;
    margin-bottom: 1rem;
}

.emphasis-text {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-primary);
    margin-top: 2rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .donation-impact-section {
        padding: 2rem 1rem;
    }

    .impact-container h2 {
        font-size: 1.75rem;
    }

    .impact-intro {
        font-size: 1rem;
        margin-bottom: 2rem;
    }

    .impact-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .donation-cta-box {
        padding: 2rem 1.5rem;
    }

    .donation-cta-box h3 {
        font-size: 1.5rem;
    }

    .thank-you-section {
        padding: 2rem 1rem;
    }

    .thank-you-container h2 {
        font-size: 1.5rem;
    }

    .thank-you-container p {
        font-size: 1rem;
    }

    .emphasis-text {
        font-size: 1.1rem;
    }
}
</style>
