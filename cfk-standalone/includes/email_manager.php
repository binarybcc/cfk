<?php
declare(strict_types=1);

/**
 * Email Manager - PHPMailer Integration
 * Handles email notifications for sponsors and administrators
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

class CFK_Email_Manager {
    
    private static ?PHPMailer\PHPMailer\PHPMailer $mailer = null;
    
    /**
     * Initialize PHPMailer instance (public for email queue access)
     */
    public static function getMailer(): PHPMailer\PHPMailer\PHPMailer {
        if (self::$mailer === null) {
            // Auto-load PHPMailer if available via Composer
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
            }
            
            // Fallback: Use basic PHP mail() function
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return self::getFallbackMailer();
            }
            
            self::$mailer = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP Configuration (can be configured via environment)
            if (config('email_use_smtp', false)) {
                self::$mailer->isSMTP();
                self::$mailer->Host = config('smtp_host', 'localhost');
                self::$mailer->SMTPAuth = config('smtp_auth', false);
                self::$mailer->Username = config('smtp_username', '');
                self::$mailer->Password = config('smtp_password', '');
                self::$mailer->SMTPSecure = config('smtp_encryption', 'tls');
                self::$mailer->Port = config('smtp_port', 587);
            } else {
                // Use sendmail/mail() function
                self::$mailer->isSendmail();
            }
            
            // Set defaults
            self::$mailer->setFrom(config('from_email'), config('from_name'));
            self::$mailer->isHTML(true);
            self::$mailer->CharSet = 'UTF-8';
        }
        
        return self::$mailer;
    }
    
    /**
     * Fallback mailer for basic PHP mail() function
     */
    private static function getFallbackMailer(): object {
        return new class {
            public $Subject = '';
            public $Body = '';
            private $to = [];
            private $from = ['email' => '', 'name' => ''];
            
            public function addAddress(string $email, string $name = ''): void {
                $this->to[] = ['email' => $email, 'name' => $name];
            }
            
            public function setFrom(string $email, string $name = ''): void {
                $this->from = ['email' => $email, 'name' => $name];
            }
            
            public function send(): bool {
                $headers = "From: " . ($this->from['name'] ? $this->from['name'] . ' <' . $this->from['email'] . '>' : $this->from['email']) . "\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                
                foreach ($this->to as $recipient) {
                    $toAddress = $recipient['name'] ? $recipient['name'] . ' <' . $recipient['email'] . '>' : $recipient['email'];
                    if (!mail($toAddress, $this->Subject, $this->Body, $headers)) {
                        return false;
                    }
                }
                
                return true;
            }
            
            public function isHTML(bool $html): void {}
        };
    }
    
    /**
     * Send sponsorship confirmation email to sponsor
     */
    public static function sendSponsorConfirmation(array $sponsorship): bool {
        try {
            $mailer = self::getMailer();
            
            $mailer->addAddress($sponsorship['sponsor_email'], $sponsorship['sponsor_name']);
            $mailer->Subject = 'Christmas for Kids - Sponsorship Confirmation';
            
            $mailer->Body = self::getSponsorConfirmationTemplate($sponsorship);
            
            $success = $mailer->send();
            
            // Log the email
            self::logEmail(
                $sponsorship['sponsor_email'],
                'sponsor_confirmation',
                $success ? 'sent' : 'failed',
                $sponsorship['id']
            );
            
            return $success;
            
        } catch (Exception $e) {
            error_log('Failed to send sponsor confirmation email: ' . $e->getMessage());
            self::logEmail(
                $sponsorship['sponsor_email'] ?? 'unknown',
                'sponsor_confirmation',
                'failed',
                $sponsorship['id'] ?? 0,
                $e->getMessage()
            );
            return false;
        }
    }
    
    /**
     * Send admin notification email
     */
    public static function sendAdminNotification(string $subject, string $message, array $sponsorship = []): bool {
        try {
            $mailer = self::getMailer();
            
            $mailer->addAddress(config('admin_email'));
            $mailer->Subject = 'CFK Admin - ' . $subject;
            
            $mailer->Body = self::getAdminNotificationTemplate($subject, $message, $sponsorship);
            
            $success = $mailer->send();
            
            // Log the email
            self::logEmail(
                config('admin_email'),
                'admin_notification',
                $success ? 'sent' : 'failed',
                $sponsorship['id'] ?? 0
            );
            
            return $success;
            
        } catch (Exception $e) {
            error_log('Failed to send admin notification email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get sponsor confirmation email template (public for email queue access)
     */
    public static function getSponsorConfirmationTemplate(array $sponsorship): string {
        $childDisplayId = $sponsorship['child_display_id'] ?? 'Unknown';
        $childName = $sponsorship['child_name'] ?? 'Child';
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #2c5530; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { background: #f4f4f4; padding: 15px; text-align: center; font-size: 12px; }
                .highlight { background: #fffacd; padding: 10px; border-left: 4px solid #2c5530; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Christmas for Kids</h1>
                <h2>Thank You for Your Sponsorship!</h2>
            </div>
            
            <div class='content'>
                <p>Dear {$sponsorship['sponsor_name']},</p>
                
                <p>Thank you so much for choosing to sponsor <strong>Child {$childDisplayId}</strong> through our Christmas for Kids program! Your generosity will help make this Christmas truly special for a child in our community.</p>
                
                <div class='highlight'>
                    <h3>What Happens Next:</h3>
                    <ol>
                        <li>Our team will review your sponsorship request</li>
                        <li>You'll receive a confirmation within 24-48 hours</li>
                        <li>We'll provide gift suggestions and delivery details</li>
                        <li>You'll have the joy of knowing you've made Christmas magical for a child</li>
                    </ol>
                </div>
                
                " . (!empty($sponsorship['special_message']) ? "
                <p><strong>Your Message:</strong> <em>{$sponsorship['special_message']}</em></p>
                " : "") . "
                
                <p>If you have any questions or need to make changes to your sponsorship, please don't hesitate to contact us.</p>
                
                <p>With heartfelt gratitude,<br>
                The Christmas for Kids Team</p>
            </div>
            
            <div class='footer'>
                <p>Christmas for Kids | Making Christmas Magical for Children in Need</p>
                <p>If you need assistance, please contact us at " . config('admin_email') . "</p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get admin notification email template (public for email queue access)
     */
    public static function getAdminNotificationTemplate(string $subject, string $message, array $sponsorship = []): string {
        $content = "<html><body style='font-family: Arial, sans-serif;'>";
        $content .= "<h2>CFK Admin Notification</h2>";
        $content .= "<p><strong>Subject:</strong> $subject</p>";
        $content .= "<div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #2c5530;'>";
        $content .= "<p>$message</p>";
        $content .= "</div>";
        
        if (!empty($sponsorship)) {
            $content .= "<h3>Sponsorship Details:</h3>";
            $content .= "<ul>";
            $content .= "<li><strong>Sponsor:</strong> {$sponsorship['sponsor_name']}</li>";
            $content .= "<li><strong>Email:</strong> {$sponsorship['sponsor_email']}</li>";
            $content .= "<li><strong>Child:</strong> {$sponsorship['child_display_id'] ?? 'Unknown'}</li>";
            $content .= "<li><strong>Date:</strong> {$sponsorship['request_date'] ?? 'Unknown'}</li>";
            $content .= "</ul>";
        }
        
        $content .= "<p><a href='" . baseUrl('admin/manage_sponsorships.php') . "'>View in Admin Panel</a></p>";
        $content .= "</body></html>";
        
        return $content;
    }
    
    /**
     * Log email for audit trail
     */
    private static function logEmail(string $recipient, string $type, string $status, int $sponsorshipId = 0, string $error = ''): void {
        try {
            Database::insert('email_log', [
                'recipient' => $recipient,
                'type' => $type,
                'status' => $status,
                'sponsorship_id' => $sponsorshipId ?: null,
                'error_message' => $error ?: null,
                'sent_date' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Failed to log email: ' . $e->getMessage());
        }
    }
    
    /**
     * Test email configuration
     */
    public static function testEmailConfig(): array {
        try {
            $testEmail = config('admin_email');
            $mailer = self::getMailer();
            
            $mailer->addAddress($testEmail);
            $mailer->Subject = 'CFK Email Test - ' . date('Y-m-d H:i:s');
            $mailer->Body = '<h2>Email Test Successful</h2><p>Your email configuration is working correctly!</p>';
            
            $success = $mailer->send();
            
            return [
                'success' => $success,
                'message' => $success ? 'Test email sent successfully' : 'Failed to send test email'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Email test failed: ' . $e->getMessage()
            ];
        }
    }
}