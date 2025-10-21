<?php

/**
 * About Page
 * Information about the Christmas for Kids program
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

$pageTitle = 'About Us';
?>

<div class="about-page">
    <?php
    // Page header component
    $title = 'About Christmas for Kids';
    $description = 'Our mission is to ensure every child in our community experiences the magic and joy of Christmas morning.';
    require_once __DIR__ . '/../includes/components/page_header.php';
    ?>

    <div class="about-content">
        <section class="mission-section">
            <div class="content-grid">
                <div class="content-text">
                    <h2>Our Mission</h2>
                    <p>
                        Christmas for Kids connects generous community members with local families who need 
                        extra support during the holiday season. We believe every child deserves to wake up 
                        on Christmas morning with gifts to unwrap and the knowledge that someone cares about them.
                    </p>
                    <p>
                        Our approach is built on dignity and respect. Families aren't charity cases - they're 
                        our neighbors going through temporary difficult times. We work to maintain family 
                        connections while providing the Christmas magic every child deserves.
                    </p>
                </div>
                <div class="content-image">
                    <img src="<?php echo baseUrl('assets/images/about-mission.jpg'); ?>" 
                         alt="Children celebrating Christmas" 
                         onerror="this.style.display='none'">
                </div>
            </div>
        </section>

        <section class="values-section">
            <h2>Our Values</h2>
            <div class="values-grid">
                <div class="value-item">
                    <div class="value-icon">🎁</div>
                    <h3>Dignity First</h3>
                    <p>Every family is treated with respect and compassion. We maintain privacy and ensure families feel supported, not judged.</p>
                </div>
                <div class="value-item">
                    <div class="value-icon">👨‍👩‍👧‍👦</div>
                    <h3>Family Unity</h3>
                    <p>We work to keep siblings together when possible and support entire families, not just individual children.</p>
                </div>
                <div class="value-item">
                    <div class="value-icon">🏘️</div>
                    <h3>Community Connection</h3>
                    <p>We build bridges within our community, connecting those who can give with those who need support.</p>
                </div>
                <div class="value-item">
                    <div class="value-icon">✨</div>
                    <h3>Christmas Magic</h3>
                    <p>We believe in the wonder of Christmas and work to ensure every child experiences that special morning joy.</p>
                </div>
            </div>
        </section>

        <section class="how-it-works-section">
            <h2>How We Work</h2>
            <div class="process-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Family Identification</h3>
                    <p>We work with local schools, community organizations, and families directly to identify children who would benefit from Christmas sponsorship.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Profile Creation</h3>
                    <p>With family permission, we create respectful profiles highlighting each child's interests, wishes, and basic needs like clothing sizes.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Sponsor Matching</h3>
                    <p>Community members browse profiles and select children or families they'd like to sponsor, providing gifts or gift cards.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Christmas Delivery</h3>
                    <p>We coordinate gift delivery to ensure every sponsored child has presents under the tree on Christmas morning.</p>
                </div>
            </div>
        </section>

        <section class="impact-section">
            <h2>Our Impact</h2>
            <div class="impact-grid">
                <div class="impact-stat">
                    <div class="stat-number"><?php echo getChildrenCount([]); ?></div>
                    <div class="stat-label">Children Helped This Year</div>
                </div>
                <div class="impact-stat">
                    <div class="stat-number"><?php
                    $familyCount = Database::fetchRow("SELECT COUNT(DISTINCT family_id) as total FROM children")['total'] ?? 0;
                    echo $familyCount;
                    ?></div>
                    <div class="stat-label">Families Supported</div>
                </div>
                <div class="impact-stat">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Local Community Focus</div>
                </div>
            </div>
            
            <div class="testimonial">
                <blockquote>
                    "Christmas for Kids helped make the holidays magical for our family during a difficult time. 
                    The way they handled everything with such care and respect meant everything to us."
                </blockquote>
                <cite>- Local Family</cite>
            </div>
        </section>

        <section class="get-involved-section">
            <h2>Get Involved</h2>
            <div class="involvement-options">
                <div class="involvement-option">
                    <h3>Sponsor a Child</h3>
                    <p>Browse our children and select someone to sponsor. You can provide specific gifts or gift cards based on their wishes and needs.</p>
                    <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-primary">View Children</a>
                </div>
                <div class="involvement-option">
                    <h3>Make a Donation</h3>
                    <p>Can't sponsor a specific child but want to help? General donations help us support families and maintain the program.</p>
                    <a href="<?php echo baseUrl('?page=donate'); ?>" class="btn btn-success">
                        Donate Now
                    </a>
                </div>
                <div class="involvement-option">
                    <h3>Spread the Word</h3>
                    <p>Help us reach more families in need and more potential sponsors by sharing our mission with friends and family.</p>
                    <div class="social-sharing">
                        <button id="share-facebook-btn" class="btn btn-secondary">Share on Facebook</button>
                        <button id="share-email-btn" class="btn btn-secondary">Share by Email</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="contact-section">
            <h2>Contact Us</h2>
            <div class="contact-info">
                <p>
                    <strong>Questions about sponsoring a child?</strong><br>
                    Email us at <a href="mailto:<?php echo config('admin_email'); ?>"><?php echo config('admin_email'); ?></a>
                </p>
                <p>
                    <strong>Need help or know a family that could benefit?</strong><br>
                    We're here to help connect children with Christmas sponsors while maintaining dignity and privacy.
                </p>
                <p>
                    <strong>Organization partnerships:</strong><br>
                    We work with schools, churches, and community organizations to identify families who would benefit from support.
                </p>
            </div>
        </section>
    </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
// CSP-compliant social sharing functionality
document.addEventListener('DOMContentLoaded', function() {
    // Facebook Share Button
    const facebookBtn = document.getElementById('share-facebook-btn');
    if (facebookBtn) {
        facebookBtn.addEventListener('click', function() {
            const shareUrl = encodeURIComponent(window.location.origin);
            const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`;
            window.open(facebookUrl, 'facebook-share', 'width=600,height=400,toolbar=0,status=0');

            // ARIA announcement
            if (typeof window.announce === 'function') {
                window.announce('Opening Facebook share dialog');
            }
        });
    }

    // Email Share Button
    const emailBtn = document.getElementById('share-email-btn');
    if (emailBtn) {
        emailBtn.addEventListener('click', function() {
            const subject = encodeURIComponent('Christmas for Kids - Help Sponsor a Child');
            const body = encodeURIComponent(
                'I wanted to share this amazing organization with you!\n\n' +
                'Christmas for Kids connects generous community members with local families who need support during the holidays.\n\n' +
                'You can browse children and sponsor someone to make their Christmas magical.\n\n' +
                'Learn more: ' + window.location.origin + '\n\n' +
                'Every child deserves Christmas morning joy!'
            );
            const mailtoUrl = `mailto:?subject=${subject}&body=${body}`;
            window.location.href = mailtoUrl;

            // ARIA announcement
            if (typeof window.announce === 'function') {
                window.announce('Opening email client to share Christmas for Kids');
            }
        });
    }
});
</script>


