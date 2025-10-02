        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo config('app_name'); ?></h3>
                    <p>Connecting generous hearts with children in need during the Christmas season.</p>
                    <p>Making the holidays brighter, one child at a time.</p>
                </div>
                
                <div class="footer-section">
                    <h3>How It Works</h3>
                    <ul>
                        <li>Browse children who need sponsorship</li>
                        <li>Select a child or family to sponsor</li>
                        <li>Provide gifts or gift cards</li>
                        <li>Make a child's Christmas special</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Support Our Mission</h3>
                    <p>Your donation helps us reach more children and families in need.</p>
                    
                    <!-- Zeffy Donation Button Integration -->
                    <div class="donation-section">
                        <button id="zeffy-donate-btn" class="donate-btn">Donate Now</button>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p>Email: <a href="mailto:<?php echo config('admin_email'); ?>"><?php echo config('admin_email'); ?></a></p>
                    <p>Questions about sponsorship? We're here to help!</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo config('app_name'); ?>. All rights reserved.</p>
                <p class="version">Version <?php echo config('app_version'); ?></p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="<?php echo baseUrl('assets/js/main.js'); ?>"></script>
    
    <!-- Zeffy Integration Script -->
    <script src="https://zeffy-scripts.s3.ca-central-1.amazonaws.com/embed-form-script.min.js"></script>
    <script>
        // Initialize Zeffy donation button
        document.addEventListener('DOMContentLoaded', function() {
            const donateBtn = document.getElementById('zeffy-donate-btn');
            if (donateBtn) {
                donateBtn.setAttribute('zeffy-form-link', 'https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true');
            }
        });
    </script>
</body>
</html>