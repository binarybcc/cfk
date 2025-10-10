<?php
/**
 * How to Apply Page
 * Information for families applying for Christmas assistance
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}
?>

<div class="how-to-apply-page">
    <?php
    // Page header component
    $title = '📋 How To Apply For Assistance';
    $description = 'Information for families seeking Christmas assistance';
    require_once __DIR__ . '/../includes/components/page_header.php';
    ?>

    <div class="apply-container">
        <div class="important-dates">
            <h2>🗓️ Important Dates & Hours</h2>
            <div class="dates-grid">
                <div class="date-card highlight">
                    <h3>First Day to Apply</h3>
                    <p class="big-text">Tuesday, October 28</p>
                </div>
                <div class="date-card deadline">
                    <h3>Applications End</h3>
                    <p class="big-text">November 28</p>
                </div>
            </div>

            <div class="hours-section">
                <h3>Application Hours:</h3>
                <ul class="hours-list">
                    <li><strong>Tuesdays & Thursdays:</strong> 10:00 AM – 2:30 PM</li>
                    <li><strong>Fridays:</strong> 6:00 PM – 8:30 PM</li>
                </ul>
            </div>
        </div>

        <div class="alert alert-warning">
            <p><strong>⚠️ PLEASE NOTE:</strong> Do not bring your children when applying unless it is completely unavoidable.</p>
        </div>

        <div class="content-section">
            <h2>📝 Before You Come</h2>

            <div class="info-box">
                <h3>No Appointments – Expect a Wait</h3>
                <p>We do not make appointments. There is typically a long wait.</p>
                <p><strong>You may not join the queue line until your Application and Wish List for each child are completed.</strong></p>
            </div>

            <div class="preparation-section">
                <h3>Come Prepared:</h3>
                <ul class="checklist">
                    <li>✓ 12 general wish list ideas or favorite characters for each child</li>
                    <li>✓ Children's clothing & shoe sizes</li>
                    <li>✓ Completed application and wish list forms (download below)</li>
                </ul>
                <p class="note"><em>Note: Requests for expensive or name brand items typically will not be fulfilled.</em></p>
            </div>
        </div>

        <div class="downloads-section">
            <h2>📥 Download Application Forms</h2>
            <p>You may download, print and complete the forms before you arrive. <strong>You do not need a login to download files.</strong></p>

            <div class="download-cards">
                <div class="download-card">
                    <div class="download-icon">📄</div>
                    <h3>2025 Application</h3>
                    <p>Main application form for Christmas assistance</p>
                    <a href="<?php echo baseUrl('assets/downloads/cfk-application-2025.pdf'); ?>" class="btn btn-primary" download>
                        ⬇️ Download Application PDF
                    </a>
                </div>

                <div class="download-card">
                    <div class="download-icon">🎁</div>
                    <h3>2025 Family Wish Lists</h3>
                    <p>Wish list form for each child</p>
                    <a href="<?php echo baseUrl('assets/downloads/cfk-family-wish-lists-2025.pdf'); ?>" class="btn btn-primary" download>
                        ⬇️ Download Wish List PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="location-section">
            <h2>📍 Location</h2>
            <div class="location-box">
                <h3>Seneca Industrial Complex</h3>
                <p class="address">324 Shiloh Road, Seneca</p>

                <div class="alert alert-info">
                    <p><strong>⚠️ NOTE: APPLICATION OFFICE MOVED WITHIN THE COMPLEX!</strong></p>
                    <p>We are now next door to the Seneca Police Department.</p>
                    <p>Park in the front parking lot (2nd left). Our office is behind the large flag pole.</p>
                </div>
            </div>
        </div>

        <div class="requirements-section">
            <h2>📋 Required Documents</h2>

            <div class="preferred-docs">
                <h3>✅ PREFERRED DOCUMENTS:</h3>
                <div class="highlight-box">
                    <ul>
                        <li><strong>Current DSS Family Profile</strong></li>
                        <li><strong>Proof That You Currently Receive Food Stamps</strong></li>
                    </ul>
                    <p class="note"><strong>If you have these, you do not need to bring any other documents!</strong></p>
                </div>
            </div>

            <div class="alternative-docs">
                <h3>📄 If You DO NOT Have The Items Above or Do Not Receive Food Stamps</h3>
                <p>Then You Must Bring The Following:</p>
                <ul class="docs-list">
                    <li><strong>Proof Of Income:</strong> Last year's tax returns OR 4 current pay stubs</li>
                    <li><strong>Proof That All Children Are Living In Your Home:</strong> Typically a tax return or rental agreement listing residents</li>
                    <li><strong>Valid Photo ID with current address</strong> (Family must reside in Oconee County)
                        <ul>
                            <li>If address is not current, must provide a utility bill with applicant name & current address</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <div class="eligibility-section">
            <h2>✓ Eligibility Requirements</h2>
            <ul class="requirements-list">
                <li>Children birth through 17 accepted</li>
                <li>Teens must still be attending school to qualify</li>
                <li>Family must reside in Oconee County</li>
                <li>Income limitations apply</li>
            </ul>

            <div class="alert alert-error">
                <p><strong>⚠️ IMPORTANT:</strong></p>
                <p>Families who apply for Christmas assistance with Christmas for Kids <strong>cannot apply for similar assistance with any other agency, church or organization</strong>.</p>
                <p>If found on any other list, you will not receive gifts through Christmas For Kids.</p>
            </div>
        </div>

        <div class="language-notice">
            <h3>Español / Spanish Notice</h3>
            <p class="spanish-text"><em>Lo siento, pero actualmente no tenemos voluntarios que hablen español. Por favor, traiga un intérprete con usted o no podremos completar su solicitud. Todos los formularios deben completarse en inglés. Gracias.</em></p>
        </div>

        <div class="contact-section">
            <h2>❓ Questions?</h2>
            <p>Please email us at <a href="mailto:christmasforkids@upstatetoday.com">christmasforkids@upstatetoday.com</a></p>
        </div>
    </div>
</div>

<style>
.how-to-apply-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.apply-container {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.important-dates {
    background: linear-gradient(135deg, #2c5530 0%, #3d7a42 100%);
    color: white;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.important-dates h2 {
    color: white;
    margin-top: 0;
}

.dates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.date-card {
    background: rgba(255,255,255,0.1);
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid rgba(255,255,255,0.2);
}

.date-card.highlight {
    border-color: #ffd700;
    background: rgba(255,215,0,0.1);
}

.date-card.deadline {
    border-color: #ff6b6b;
    background: rgba(255,107,107,0.1);
}

.date-card h3 {
    margin-top: 0;
    font-size: 1.1em;
    color: white;
}

.big-text {
    font-size: 1.5em;
    font-weight: bold;
    margin: 10px 0 0 0;
    color: white;
}

.hours-section {
    margin-top: 20px;
    background: rgba(255,255,255,0.1);
    padding: 20px;
    border-radius: 8px;
}

.hours-section h3 {
    margin-top: 0;
    color: white;
}

.hours-list {
    list-style: none;
    padding: 0;
    margin: 10px 0 0 0;
}

.hours-list li {
    padding: 10px;
    background: rgba(255,255,255,0.1);
    margin: 8px 0;
    border-radius: 4px;
    font-size: 1.1em;
}

.content-section {
    margin: 30px 0;
}

.content-section h2 {
    color: #2c5530;
    border-bottom: 3px solid #c41e3a;
    padding-bottom: 10px;
    margin-top: 30px;
}

.info-box {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #c41e3a;
    margin: 20px 0;
}

.info-box h3 {
    margin-top: 0;
    color: #c41e3a;
}

.preparation-section {
    margin: 20px 0;
}

.checklist {
    list-style: none;
    padding: 0;
}

.checklist li {
    padding: 10px;
    background: #f8f9fa;
    margin: 8px 0;
    border-radius: 4px;
    font-size: 1.05em;
}

.note {
    font-style: italic;
    color: #666;
    margin: 10px 0;
}

.downloads-section {
    margin: 40px 0;
    background: #f0f7f0;
    padding: 30px;
    border-radius: 8px;
}

.downloads-section h2 {
    color: #2c5530;
    margin-top: 0;
}

.download-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.download-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.download-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.download-icon {
    font-size: 3em;
    margin-bottom: 15px;
}

.download-card h3 {
    color: #2c5530;
    margin: 10px 0;
}

.download-card p {
    color: #666;
    margin-bottom: 20px;
}

.location-section {
    margin: 30px 0;
}

.location-section h2 {
    color: #2c5530;
    border-bottom: 3px solid #c41e3a;
    padding-bottom: 10px;
}

.location-box {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

.location-box h3 {
    margin-top: 0;
    color: #2c5530;
    font-size: 1.5em;
}

.address {
    font-size: 1.2em;
    color: #333;
    margin: 10px 0 20px 0;
}

.requirements-section {
    margin: 30px 0;
}

.requirements-section h2 {
    color: #2c5530;
    border-bottom: 3px solid #c41e3a;
    padding-bottom: 10px;
}

.preferred-docs,
.alternative-docs {
    margin: 20px 0;
}

.preferred-docs h3 {
    color: #2c5530;
}

.highlight-box {
    background: #e8f5e9;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #4caf50;
}

.alternative-docs h3 {
    color: #c41e3a;
    margin-top: 30px;
}

.docs-list {
    list-style: none;
    padding: 0;
}

.docs-list li {
    padding: 12px;
    background: #f8f9fa;
    margin: 10px 0;
    border-radius: 4px;
    border-left: 3px solid #c41e3a;
}

.docs-list ul {
    margin-top: 10px;
    padding-left: 20px;
}

.eligibility-section {
    margin: 30px 0;
}

.eligibility-section h2 {
    color: #2c5530;
    border-bottom: 3px solid #c41e3a;
    padding-bottom: 10px;
}

.requirements-list {
    list-style: none;
    padding: 0;
}

.requirements-list li {
    padding: 12px;
    background: #f0f7f0;
    margin: 10px 0;
    border-radius: 4px;
    border-left: 4px solid #4caf50;
    font-size: 1.05em;
}

.language-notice {
    background: #fff3cd;
    padding: 20px;
    border-radius: 8px;
    margin: 30px 0;
    border-left: 4px solid #ffc107;
}

.language-notice h3 {
    margin-top: 0;
    color: #856404;
}

.spanish-text {
    color: #856404;
    line-height: 1.6;
}

.contact-section {
    text-align: center;
    margin: 40px 0;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 8px;
}

.contact-section h2 {
    color: #2c5530;
    margin-top: 0;
}

.contact-section a {
    color: #c41e3a;
    text-decoration: none;
    font-weight: bold;
    font-size: 1.1em;
}

.contact-section a:hover {
    text-decoration: underline;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .apply-container {
        padding: 20px;
    }

    .dates-grid {
        grid-template-columns: 1fr;
    }

    .download-cards {
        grid-template-columns: 1fr;
    }

    .big-text {
        font-size: 1.3em;
    }
}
</style>
