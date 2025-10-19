        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section footer-brand">
                    <img src="<?php echo baseUrl('assets/images/cfk-horizontal.png'); ?>"
                         alt="Christmas for Kids"
                         class="footer-logo"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <h3 style="display:none;"><?php echo config('app_name'); ?></h3>
                    <p>Connecting generous hearts with children in need during the Christmas season.</p>
                    <p>Making the holidays brighter, one child at a time.</p>
                </div>
                
                <div class="footer-section">
                    <h3>How It Works</h3>
                    <ul>
                        <li>Browse children who need sponsorship</li>
                        <li>Select a child or family to sponsor</li>
                        <li>Provide gifts</li>
                        <li>Make a child's Christmas special</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Support Our Mission</h3>
                    <p>Your donation helps us reach more children and families in need.</p>
                    
                    <!-- Zeffy Donation Button Integration -->
                    <div class="donation-section">
                        <a href="<?php echo baseUrl('?page=donate'); ?>" class="btn btn-success donate-btn">
                            Donate Now
                        </a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p>Email: <a href="mailto:christmasforkids@upstatetoday.com">christmasforkids@upstatetoday.com</a></p>
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

    <!-- Zeffy donation script loaded in header, buttons initialized in main.js -->

    <style>
        /* Footer Logo Styling */
        .footer-brand {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .footer-logo {
            height: 60px;
            width: auto;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .footer-logo {
                height: 50px;
            }
        }
    </style>
</body>
</html>