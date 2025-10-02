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
    <div class="page-header">
        <h1>About Christmas for Kids</h1>
        <p class="page-description">
            Our mission is to ensure every child in our community experiences the magic and joy of Christmas morning.
        </p>
    </div>

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
                    <button id="about-donate-btn" class="btn btn-success" 
                            zeffy-form-link="https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true">
                        Donate Now
                    </button>
                </div>
                <div class="involvement-option">
                    <h3>Spread the Word</h3>
                    <p>Help us reach more families in need and more potential sponsors by sharing our mission with friends and family.</p>
                    <div class="social-sharing">
                        <button onclick="shareOnFacebook()" class="btn btn-secondary">Share on Facebook</button>
                        <button onclick="shareByEmail()" class="btn btn-secondary">Share by Email</button>
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

<style>
.about-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 1rem;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
    padding: 3rem 2rem;
    background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
    color: white;
    border-radius: 12px;
}

.page-header h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: white;
}

.page-description {
    font-size: 1.2rem;
    max-width: 600px;
    margin: 0 auto;
    opacity: 0.95;
}

.about-content {
    space-y: 4rem;
}

.about-content section {
    margin-bottom: 4rem;
    background: white;
    padding: 3rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.about-content h2 {
    font-size: 2rem;
    color: #2c5530;
    margin-bottom: 2rem;
    text-align: center;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
}

.content-text p {
    margin-bottom: 1.5rem;
    line-height: 1.7;
    color: #555;
    font-size: 1.1rem;
}

.content-image img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.value-item {
    text-align: center;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 8px;
    transition: transform 0.2s;
}

.value-item:hover {
    transform: translateY(-3px);
}

.value-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.value-item h3 {
    color: #2c5530;
    margin-bottom: 1rem;
}

.process-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.step {
    text-align: center;
    padding: 2rem;
}

.step-number {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #2c5530;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0 auto 1.5rem auto;
}

.step h3 {
    color: #2c5530;
    margin-bottom: 1rem;
}

.impact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.impact-stat {
    text-align: center;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-number {
    font-size: 3rem;
    font-weight: bold;
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.testimonial {
    background: #f1f8f3;
    padding: 2rem;
    border-radius: 8px;
    border-left: 4px solid #2c5530;
}

.testimonial blockquote {
    font-style: italic;
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 1rem;
    color: #444;
}

.testimonial cite {
    color: #666;
    font-size: 0.9rem;
}

.involvement-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.involvement-option {
    text-align: center;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.involvement-option h3 {
    color: #2c5530;
    margin-bottom: 1rem;
}

.involvement-option p {
    margin-bottom: 1.5rem;
    color: #555;
}

.social-sharing {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.contact-section {
    text-align: center;
}

.contact-info p {
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.contact-info a {
    color: #2c5530;
    text-decoration: none;
    font-weight: bold;
}

.contact-info a:hover {
    text-decoration: underline;
}

/* Responsive Design */
@media (max-width: 768px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .values-grid,
    .process-steps,
    .impact-grid,
    .involvement-options {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        padding: 2rem 1rem;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .about-content section {
        padding: 2rem 1rem;
    }
    
    .social-sharing {
        flex-direction: column;
    }
}
</style>

<script>
function shareOnFacebook() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent('Help local children have a magical Christmas through Christmas for Kids sponsorship program!');
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
}

function shareByEmail() {
    const subject = encodeURIComponent('Christmas for Kids - Help Local Children');
    const body = encodeURIComponent(`Hi!

I wanted to share this wonderful program with you: Christmas for Kids helps connect community members with local children who need Christmas sponsorship.

Every child deserves to experience the magic of Christmas morning, and you can help make that happen.

Check it out: ${window.location.href}

Thanks for caring about our community!`);
    
    window.open(`mailto:?subject=${subject}&body=${body}`);
}
</script>