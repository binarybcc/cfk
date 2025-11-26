<?php

/**
 * Temporary Landing Page - Pre-Launch (Until Oct 31, 2025 11:00 AM ET)
 * Shows donation information and countdown to sponsorship launch
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

global $cspNonce;
$pageTitle = 'Christmas for Kids - Opening October 31, 2025';

// Calculate countdown to Oct 31, 2025 10:00 AM ET
$launchTime = new DateTime('2025-10-31 10:00:00', new DateTimeZone('America/New_York'));
$now = new DateTime('now', new DateTimeZone('America/New_York'));
$interval = $now->diff($launchTime);
?>

<style nonce="<?php echo $cspNonce; ?>">
    /* Temporary Landing Page Styles */
    .temp-landing {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .hero-countdown {
        background: linear-gradient(135deg, #2c5530 0%, #1e3d21 100%);
        color: white;
        padding: 3rem 2rem;
        border-radius: 12px;
        text-align: center;
        margin-bottom: 3rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .hero-countdown h1 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        color: white;
    }

    .hero-countdown .emoji {
        font-size: 3rem;
        margin-bottom: 1rem;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    .message-section {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        margin-bottom: 2rem;
        border: 2px solid #2c5530;
        animation: fadeInUp 0.6s ease-out;
        animation-fill-mode: both;
    }

    .message-section:nth-child(2) {
        animation-delay: 0.1s;
    }

    .message-section:nth-child(3) {
        animation-delay: 0.2s;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message-section h2 {
        color: #2c5530;
        font-size: 1.8rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .message-section .icon {
        font-size: 2rem;
    }

    .message-section p {
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    .countdown-timer {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin: 2rem 0;
        flex-wrap: wrap;
    }

    .countdown-unit {
        background: rgba(255,255,255,0.2);
        padding: 1.5rem 2rem;
        border-radius: 8px;
        backdrop-filter: blur(10px);
        min-width: 100px;
        transition: transform 0.3s ease, background 0.3s ease;
    }

    .countdown-unit:hover {
        transform: scale(1.05);
        background: rgba(255,255,255,0.3);
    }

    .countdown-number {
        font-size: 3rem;
        font-weight: bold;
        display: block;
        line-height: 1;
    }

    .countdown-label {
        font-size: 0.9rem;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 0.5rem;
        display: block;
    }

    .launch-date {
        font-size: 1.5rem;
        margin-top: 2rem;
        padding: 1rem;
        background: rgba(255,255,255,0.1);
        border-radius: 8px;
        font-weight: 600;
    }

    .cta-button {
        display: inline-block;
        background: #c41e3a;
        color: white;
        padding: 1rem 2rem;
        border-radius: 8px;
        text-decoration: none;
        font-size: 1.2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-top: 1rem;
        border: none;
        cursor: pointer;
        font-family: inherit;
    }

    .cta-button:hover {
        background: #a01629;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(196, 30, 58, 0.4);
    }

    .how-to-apply-section {
        margin-top: 3rem;
        padding-top: 3rem;
        border-top: 2px solid #e0e0e0;
        scroll-margin-top: 80px;
    }

    .how-to-apply-section h2 {
        text-align: center;
        color: #2c5530;
        margin-bottom: 1rem;
        font-size: 2rem;
    }

    .important-dates {
        background: #f0f7f0;
        padding: 2rem;
        border-radius: 8px;
        margin: 2rem 0;
    }

    .important-dates h3 {
        color: #2c5530;
        margin-top: 0;
        margin-bottom: 1rem;
    }

    .dates-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .date-card {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        text-align: center;
        border: 2px solid #2c5530;
    }

    .hours-info {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        text-align: center;
        margin-top: 1rem;
    }

    .apply-highlights {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin: 2rem 0;
    }

    .highlight-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        border: 2px solid #e0e0e0;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .highlight-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .highlight-card h3 {
        color: #2c5530;
        margin-top: 0;
        margin-bottom: 1rem;
    }

    .highlight-card p {
        color: #333;
        line-height: 1.6;
        margin: 0;
    }

    .downloads-section-temp {
        background: #f0f7f0;
        padding: 2rem;
        border-radius: 8px;
        margin: 2rem 0;
    }

    .downloads-section-temp h3 {
        color: #2c5530;
        margin-top: 0;
        margin-bottom: 0.5rem;
    }

    .downloads-section-temp > p {
        color: #666;
        margin-bottom: 1.5rem;
    }

    .download-cards-temp {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .download-card-temp {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .download-card-temp:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }

    .download-icon-temp {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .download-card-temp h4 {
        color: #2c5530;
        margin: 0.5rem 0;
        font-size: 1.2rem;
    }

    .download-card-temp p {
        color: #666;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }

    .download-btn {
        display: inline-block;
        background: #2c5530;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .download-btn:hover {
        background: #1e3d21;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(44, 85, 48, 0.4);
    }

    .zeffy-section {
        margin-top: 3rem;
        padding-top: 3rem;
        border-top: 2px solid #e0e0e0;
        scroll-margin-top: 80px; /* Account for sticky header */
    }

    .zeffy-section h2 {
        text-align: center;
        color: #2c5530;
        margin-bottom: 2rem;
        font-size: 2rem;
    }

    .zeffy-embed-container {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .hero-countdown h1 {
            font-size: 1.8rem;
        }

        .countdown-timer {
            gap: 1rem;
        }

        .countdown-unit {
            padding: 1rem 1.5rem;
            min-width: 80px;
        }

        .countdown-number {
            font-size: 2rem;
        }

        .message-section h2 {
            font-size: 1.4rem;
        }

        .message-section p {
            font-size: 1rem;
        }

        .launch-date {
            font-size: 1.2rem;
        }
    }
</style>

<div class="temp-landing">
    <!-- Hero with Countdown -->
    <div class="hero-countdown">
        <div class="emoji">🎄</div>
        <h1>Christmas for Kids 2025</h1>

        <div class="countdown-timer" id="countdown">
            <div class="countdown-unit">
                <span class="countdown-number" id="days"><?php echo $interval->days; ?></span>
                <span class="countdown-label">Days</span>
            </div>
            <div class="countdown-unit">
                <span class="countdown-number" id="hours"><?php echo $interval->h; ?></span>
                <span class="countdown-label">Hours</span>
            </div>
            <div class="countdown-unit">
                <span class="countdown-number" id="minutes"><?php echo $interval->i; ?></span>
                <span class="countdown-label">Minutes</span>
            </div>
            <div class="countdown-unit">
                <span class="countdown-number" id="seconds"><?php echo $interval->s; ?></span>
                <span class="countdown-label">Seconds</span>
            </div>
        </div>

        <div class="launch-date">
            🎁 Child Sponsorships Open:<br>
            <strong>Friday, October 31, 2025 at 11:00 AM ET</strong>
        </div>
    </div>

    <!-- Monetary Donations Section -->
    <div class="message-section">
        <h2><span class="icon">💝</span> Monetary Donations Are Always Welcome</h2>
        <p>
            We're gratefully accepting donations now to help make Christmas magical for local children in need.
            100% of your donation goes directly to the children and families we serve.
        </p>
        <button class="cta-button" data-scroll-to="donate-form">Donate Now</button>
    </div>

    <!-- Sponsorships Coming Soon Section -->
    <div class="message-section">
        <h2><span class="icon">🎁</span> Child Sponsorships Begin October 31st</h2>
        <p>
            Browse children and select sponsorships starting Friday, October 31, 2025 at 11:00 AM Eastern Time.
            Each child represents a family in our community who could use extra support this Christmas season.
        </p>
        <p>
            <strong>New to sponsorships?</strong> Learn more about how the program works and how to apply:
        </p>
        <button class="cta-button" style="background: #2c5530;" data-scroll-to="how-to-apply">
            How to Apply
        </button>
    </div>

    <!-- How to Apply Section -->
    <div class="how-to-apply-section" id="how-to-apply">
        <h2>📋 How To Apply For Assistance</h2>
        <p style="text-align: center; margin-bottom: 2rem; color: #666;">
            Information for families seeking Christmas assistance
        </p>

        <div class="important-dates">
            <h3>🗓️ Important Dates & Hours</h3>
            <div class="dates-grid">
                <div class="date-card">
                    <strong>First Day to Apply:</strong><br>
                    Tuesday, October 28
                </div>
                <div class="date-card">
                    <strong>Applications End:</strong><br>
                    November 28
                </div>
            </div>
            <div class="hours-info">
                <strong>Application Hours:</strong><br>
                Tuesdays & Thursdays: 10:00 AM – 2:30 PM<br>
                Fridays: 6:00 PM – 8:30 PM
            </div>
        </div>

        <div class="downloads-section-temp">
            <h3>📥 Download Application Forms</h3>
            <p>Download, print, and complete the forms before you arrive to reduce wait time.</p>

            <div class="download-cards-temp">
                <div class="download-card-temp">
                    <div class="download-icon-temp">📄</div>
                    <h4>2025 Application</h4>
                    <p>Main application form for Christmas assistance</p>
                    <a href="<?php echo baseUrl('assets/downloads/cfk-application-2025.pdf'); ?>" class="download-btn" download>
                        ⬇️ Download Application PDF
                    </a>
                </div>

                <div class="download-card-temp">
                    <div class="download-icon-temp">🎁</div>
                    <h4>2025 Family Wish Lists</h4>
                    <p>Wish list form for each child</p>
                    <a href="<?php echo baseUrl('assets/downloads/cfk-family-wish-lists-2025.pdf'); ?>" class="download-btn" download>
                        ⬇️ Download Wish List PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="apply-highlights">
            <div class="highlight-card">
                <h3>📍 Location</h3>
                <p><strong>Seneca Industrial Complex</strong><br>
                324 Shiloh Road, Seneca<br>
                Next door to Seneca Police Department</p>
            </div>

            <div class="highlight-card">
                <h3>✅ What to Bring</h3>
                <p><strong>PREFERRED:</strong> Current DSS Family Profile or proof of Food Stamps<br>
                <strong>OR:</strong> Proof of income, proof children live with you, valid photo ID</p>
            </div>

            <div class="highlight-card">
                <h3>👨‍👩‍👧‍👦 Eligibility</h3>
                <p>Children birth through age 17<br>
                Teens must be attending school<br>
                Family must reside in Oconee County</p>
            </div>
        </div>

        <div class="alert alert-warning" style="margin-top: 2rem;">
            <p><strong>⚠️ IMPORTANT:</strong> Families who apply with Christmas for Kids <strong>cannot apply</strong> for similar assistance with any other agency, church, or organization.</p>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <p>Questions? Email us at <a href="mailto:christmasforkids@upstatetoday.com" style="color: #c41e3a; font-weight: bold;">christmasforkids@upstatetoday.com</a></p>
        </div>
    </div>

    <!-- Zeffy Donation Form Section -->
    <div class="zeffy-section" id="donate-form">
        <h2>Make Your Donation</h2>
        <p style="text-align: center; margin-bottom: 2rem; color: #666;">
            Complete the form below to donate securely through Zeffy
        </p>

        <div class="zeffy-embed-container">
            <iframe
                src="https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids"
                style="width: 100%; height: 900px; border: none;"
                title="Donation form powered by Zeffy"
                sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox"
                allow="payment"
                loading="lazy">
            </iframe>
            <noscript>
                <p style="text-align: center;">
                    Please enable JavaScript to view the donation form, or visit
                    <a href="https://www.zeffy.com/donation-form/donate-to-christmas-for-kids" target="_blank" rel="noopener">
                        our donation page directly
                    </a>.
                </p>
            </noscript>
        </div>
    </div>
</div>

<!-- Live Countdown JavaScript -->
<script nonce="<?php echo $cspNonce; ?>">
(function() {
    // Countdown to Oct 31, 2025 10:00:00 ET (EST is UTC-5)
    const launchDate = new Date('2025-10-31T10:00:00-05:00');

    function updateCountdown() {
        const now = new Date();
        const diff = launchDate - now;

        if (diff <= 0) {
            // Launch time reached - reload page to show normal homepage
            window.location.reload();
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        document.getElementById('days').textContent = days;
        document.getElementById('hours').textContent = hours;
        document.getElementById('minutes').textContent = minutes;
        document.getElementById('seconds').textContent = seconds;
    }

    // Update immediately and then every second
    updateCountdown();
    setInterval(updateCountdown, 1000);
})();

// Smooth scroll function for buttons
function smoothScrollTo(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Attach event listeners to all scroll buttons
document.addEventListener('DOMContentLoaded', function() {
    const scrollButtons = document.querySelectorAll('[data-scroll-to]');
    scrollButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-scroll-to');
            smoothScrollTo(targetId);
        });
    });
});
</script>
