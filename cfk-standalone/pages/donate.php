<?php

/**
 * Donation Page
 * Information about Zeffy and embedded donation form
 */

// Prevent direct access
if (! defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

$pageTitle = 'Donate';
?>

<div class="donate-page">
    <!-- Information Section at Top -->
    <div class="donation-info-section">
        <div class="donation-info-icon">💝</div>

        <h1>Support Christmas for Kids</h1>

        <div class="donation-info-content">
            <h2>100% of Your Donation Goes to the Children</h2>

            <p class="lead">
                We use <strong>Zeffy</strong>, a platform that allows <strong>100% of your donation</strong> 
                to reach the children and families we serve. Unlike other platforms that charge fees, 
                Zeffy is completely free for nonprofits like us.
            </p>

            <div class="important-notice">
                <h3>⚠️ Important: Tips Are Optional</h3>
                <p>
                    After you enter your donation amount below, Zeffy will ask if you'd like to leave 
                    a <strong>voluntary tip</strong> to support their platform. This tip helps keep 
                    Zeffy free for charities.
                </p>

                <ul class="tip-info-list">
                    <li><strong>Tips are completely optional</strong> - you can choose $0</li>
                    <li>Zeffy may suggest a tip amount - you can change it to any amount you prefer</li>
                    <li>Your donation to us is separate from any tip to Zeffy</li>
                    <li>Changing or declining the tip does not affect your donation to our charity</li>
                </ul>
            </div>

            <p class="thank-you-message">
                We appreciate any amount you choose to donate. Every dollar makes a difference 
                in a child's Christmas! 🎄
            </p>
        </div>
    </div>

    <!-- Zeffy Donation Form Embedded Below -->
    <div class="zeffy-form-section">
        <h2>Make Your Donation</h2>
        <p class="form-instruction">Complete the form below to donate securely through Zeffy:</p>

        <!-- Zeffy Embedded Form -->
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
                <p>Please enable JavaScript to view the donation form, or visit
                <a href="https://www.zeffy.com/donation-form/donate-to-christmas-for-kids" target="_blank" rel="noopener">
                    our donation page directly
                </a>.</p>
            </noscript>
        </div>
    </div>
</div>

