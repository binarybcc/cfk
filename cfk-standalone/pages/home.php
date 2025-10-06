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

// Get some featured children (available children, limit to 6)
$featuredChildren = getChildren(['status' => 'available'], 1, 6);

// Eager load family members to prevent N+1 queries
$siblingsByFamily = eagerLoadFamilyMembers($featuredChildren);

// Get count statistics
$totalAvailable = getChildrenCount(['status' => 'available']);
$totalFamilies = Database::fetchRow("SELECT COUNT(DISTINCT family_id) as total FROM children WHERE status = 'available'")['total'] ?? 0;
?>

<div class="home-page">
    <!-- Hero Section -->
    <section class="hero">
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
            <div class="hero-actions">
                <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-large btn-primary">
                    View Children Needing Sponsorship
                </a>
                <button id="hero-donate-btn" class="btn btn-large btn-success" 
                        zeffy-form-link="https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true">
                    Make a Donation
                </button>
            </div>
        </div>
        <div class="hero-image">
            <img src="<?php echo baseUrl('assets/images/christmas-hero.jpg'); ?>" 
                 alt="Children celebrating Christmas" 
                 onerror="this.style.display='none'">
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <h2>How Christmas Sponsorship Works</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-icon">👥</div>
                    <h3>1. Browse Children</h3>
                    <p>Look through profiles of local children and families who need Christmas support. Each profile includes their interests, wishes, and clothing sizes.</p>
                </div>
                <div class="step">
                    <div class="step-icon">🎯</div>
                    <h3>2. Choose to Sponsor</h3>
                    <p>Select a child or family that speaks to your heart. You can sponsor individuals or keep siblings together.</p>
                </div>
                <div class="step">
                    <div class="step-icon">🎁</div>
                    <h3>3. Provide Gifts</h3>
                    <p>Purchase gifts based on their wishes and needs, or provide gift cards. We'll coordinate delivery to ensure every child has a magical Christmas.</p>
                </div>
                <div class="step">
                    <div class="step-icon">✨</div>
                    <h3>4. Create Magic</h3>
                    <p>Experience the joy of knowing you've made Christmas possible for a child who might not have had one otherwise.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Children -->
    <?php if (!empty($featuredChildren)): ?>
    <section class="featured-children">
        <div class="container">
            <h2>Meet Some Children Who Need Your Help</h2>
            <p class="section-description">
                These are just a few of the wonderful children and families hoping for a Christmas miracle this year.
            </p>
            
            <div class="featured-grid">
                <?php foreach ($featuredChildren as $child): ?>
                    <div class="featured-child-card">
                        <div class="child-photo">
                            <img src="<?php echo getPhotoUrl($child['photo_filename'], $child); ?>" 
                                 alt="Avatar for <?php echo sanitizeString($child['name']); ?>"
                                 loading="lazy">
                        </div>
                        <div class="child-info">
                            <h3><?php echo sanitizeString($child['name']); ?></h3>
                            <p class="child-details">
                                <?php echo formatAge($child['age']); ?>
                                <?php if (!empty($child['grade'])): ?>
                                    • <?php echo sanitizeString($child['grade']); ?> Grade
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($child['wishes'])): ?>
                                <p class="child-wishes">
                                    "<?php echo sanitizeString(substr($child['wishes'], 0, 80)); ?><?php echo strlen($child['wishes']) > 80 ? '...' : ''; ?>"
                                </p>
                            <?php endif; ?>
                            <a href="<?php echo baseUrl('?page=child&id=' . $child['id']); ?>" 
                               class="btn btn-primary">Learn More</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="view-all-action">
                <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-large btn-secondary">
                    View All <?php echo $totalAvailable; ?> Children
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Impact Section -->
    <section class="impact">
        <div class="container">
            <h2>Your Impact Matters</h2>
            <div class="impact-content">
                <div class="impact-text">
                    <h3>More Than Just Gifts</h3>
                    <p>
                        When you sponsor a child for Christmas, you're providing more than presents. 
                        You're giving hope, dignity, and the message that someone cares. Many of these families 
                        are going through temporary difficult times, and your support helps them get through 
                        the holidays with joy instead of stress.
                    </p>
                    
                    <h3>Community Connection</h3>
                    <p>
                        Our program keeps families together and maintains their dignity while providing the 
                        Christmas magic every child deserves. We work directly with local families, schools, 
                        and community organizations to identify children who would benefit most from sponsorship.
                    </p>
                    
                    <h3>Every Child Counts</h3>
                    <p>
                        Whether you sponsor one child or an entire family, your contribution makes a real 
                        difference in our community. Together, we ensure that no child goes without 
                        Christmas morning excitement.
                    </p>
                </div>
                <div class="impact-image">
                    <img src="<?php echo baseUrl('assets/images/impact-photo.jpg'); ?>" 
                         alt="Happy children with Christmas gifts" 
                         onerror="this.style.display='none'">
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="final-cta">
        <div class="container">
            <h2>Ready to Make a Difference?</h2>
            <p>
                Christmas magic happens when caring people like you reach out to help. 
                Every child deserves to believe in the wonder of Christmas morning.
            </p>
            <div class="cta-actions">
                <a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-large btn-primary">
                    Find a Child to Sponsor
                </a>
                <button id="final-donate-btn" class="btn btn-large btn-outline" 
                        zeffy-form-link="https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true">
                    Make a General Donation
                </button>
            </div>
        </div>
    </section>
</div>

<style>
.home-page {
    margin-bottom: 2rem;
}

/* Hero Section */
.hero {
    background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
    color: white;
    padding: 4rem 2rem;
    border-radius: 12px;
    margin-bottom: 3rem;
    display: flex;
    align-items: center;
    gap: 3rem;
    min-height: 500px;
}

.hero-content {
    flex: 1;
}

.hero h1 {
    font-size: 3rem;
    margin-bottom: 1.5rem;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 1.3rem;
    margin-bottom: 2rem;
    opacity: 0.95;
    line-height: 1.5;
}

.hero-stats {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
}

.stat {
    text-align: center;
    padding: 1rem;
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    min-width: 140px;
}

.stat strong {
    display: block;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.stat span {
    font-size: 0.9rem;
    opacity: 0.9;
}

.hero-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.hero-image {
    flex: 1;
    max-width: 500px;
}

.hero-image img {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

/* How It Works */
.how-it-works {
    background: #f8f9fa;
    padding: 4rem 2rem;
    border-radius: 12px;
    margin-bottom: 3rem;
}

.how-it-works h2 {
    text-align: center;
    font-size: 2.5rem;
    color: #2c5530;
    margin-bottom: 3rem;
}

.steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.step {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.step:hover {
    transform: translateY(-5px);
}

.step-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.step h3 {
    color: #2c5530;
    margin-bottom: 1rem;
    font-size: 1.3rem;
}

.step p {
    color: #555;
    line-height: 1.6;
}

/* Featured Children */
.featured-children {
    margin-bottom: 4rem;
}

.featured-children h2 {
    text-align: center;
    font-size: 2.5rem;
    color: #2c5530;
    margin-bottom: 1rem;
}

.section-description {
    text-align: center;
    font-size: 1.2rem;
    color: #666;
    margin-bottom: 3rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.featured-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.featured-child-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.featured-child-card:hover {
    transform: translateY(-3px);
}

.featured-child-card .child-photo {
    width: 100%;
    height: 200px;
    background: #f8f9fa;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.featured-child-card .child-photo img {
    max-width: 75%;
    max-height: 75%;
    width: auto;
    height: auto;
    object-fit: contain;
    display: block;
}

.featured-child-card .child-info {
    padding: 1.5rem;
}

.featured-child-card h3 {
    color: #2c5530;
    font-size: 1.3rem;
    margin-bottom: 0.5rem;
}

.child-details {
    color: #666;
    margin-bottom: 1rem;
    font-weight: bold;
}

.child-wishes {
    font-style: italic;
    color: #555;
    margin-bottom: 1.5rem;
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    border-left: 3px solid #2c5530;
}

.view-all-action {
    text-align: center;
}

/* Impact Section */
.impact {
    background: white;
    padding: 4rem 2rem;
    border-radius: 12px;
    margin-bottom: 3rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.impact h2 {
    text-align: center;
    font-size: 2.5rem;
    color: #2c5530;
    margin-bottom: 3rem;
}

.impact-content {
    display: flex;
    align-items: center;
    gap: 3rem;
}

.impact-text {
    flex: 2;
}

.impact-text h3 {
    color: #2c5530;
    margin-bottom: 1rem;
    font-size: 1.3rem;
}

.impact-text p {
    margin-bottom: 2rem;
    line-height: 1.7;
    color: #555;
}

.impact-image {
    flex: 1;
}

.impact-image img {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

/* Final CTA */
.final-cta {
    background: linear-gradient(135deg, #4a7c59 0%, #2c5530 100%);
    color: white;
    padding: 4rem 2rem;
    border-radius: 12px;
    text-align: center;
}

.final-cta h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.final-cta p {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    opacity: 0.95;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.cta-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Button Styles */
.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: #2c5530;
    color: white;
}

.btn-primary:hover {
    background: #1e3a21;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545862;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-outline {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.btn-outline:hover {
    background: white;
    color: #2c5530;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

/* Responsive Design */
@media (max-width: 968px) {
    .hero {
        flex-direction: column;
        text-align: center;
    }
    
    .hero h1 {
        font-size: 2.5rem;
    }
    
    .hero-stats {
        justify-content: center;
    }
    
    .steps {
        grid-template-columns: 1fr;
    }
    
    .featured-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
    
    .impact-content {
        flex-direction: column;
    }
    
    .cta-actions {
        flex-direction: column;
        align-items: center;
    }
}

@media (max-width: 600px) {
    .hero-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .hero-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
    }
}
</style>