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
                <?php echo renderButton(
                    'View Children Needing Sponsorship',
                    baseUrl('?page=children'),
                    'primary',
                    ['size' => 'large']
                ); ?>
                <?php echo renderButton(
                    'Make a Donation',
                    baseUrl('?page=donate'),
                    'success',
                    ['size' => 'large']
                ); ?>
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
                <?php foreach ($featuredChildren as $child):
                    // Set options for the child card component
                    $options = [
                        'show_wishes' => true,
                        'show_siblings' => false,
                        'card_class' => 'featured-child-card',
                        'button_text' => 'Learn More'
                    ];
                    include __DIR__ . '/../includes/components/child_card.php';
                endforeach; ?>
            </div>
            
            <div class="view-all-action">
                <?php echo renderButton(
                    "View All {$totalAvailable} Children",
                    baseUrl('?page=children'),
                    'secondary',
                    ['size' => 'large']
                ); ?>
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
                <?php echo renderButton(
                    'Find a Child to Sponsor',
                    baseUrl('?page=children'),
                    'primary',
                    ['size' => 'large']
                ); ?>
                <?php echo renderButton(
                    'Make a General Donation',
                    baseUrl('?page=donate'),
                    'outline',
                    [
                        'size' => 'large',
                        'id' => 'final-donate-btn'
                    ]
                ); ?>
            </div>
        </div>
    </section>
</div>

