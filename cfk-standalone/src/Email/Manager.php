<?php

declare(strict_types=1);

namespace CFK\Email;

use CFK\Database\Connection;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Email Manager - PHPMailer Integration
 *
 * Handles email notifications for sponsors and administrators with
 * SMTP support and fallback to PHP mail().
 *
 * @package CFK\Email
 */
class Manager
{
    private static ?PHPMailer $mailer = null;

    /**
     * Initialize PHPMailer instance (public for email queue access)
     *
     * @return PHPMailer|object PHPMailer instance or fallback mailer
     */
    public static function getMailer(): object
    {
        if (! self::$mailer instanceof PHPMailer) {
            // Auto-load PHPMailer if available via Composer
            if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../vendor/autoload.php';
            }

            // Fallback: Use basic PHP mail() function
            if (! class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $fallback = self::getFallbackMailer();
                $fallback->setFrom((string) config('from_email'), (string) config('from_name'));

                return $fallback;
            }

            self::$mailer = new PHPMailer(true);

            // SMTP Configuration (can be configured via environment)
            if (config('email_use_smtp', false)) {
                self::$mailer->isSMTP();
                self::$mailer->Host = (string) config('smtp_host', 'localhost');
                self::$mailer->SMTPAuth = (bool) config('smtp_auth', false);
                self::$mailer->Username = (string) config('smtp_username', '');
                self::$mailer->Password = (string) config('smtp_password', '');
                self::$mailer->SMTPSecure = (string) config('smtp_encryption', 'tls');
                self::$mailer->Port = (int) config('smtp_port', 587);
            } else {
                // Use sendmail/mail() function
                self::$mailer->isSendmail();
            }

            // Set defaults
            self::$mailer->setFrom((string) config('from_email'), (string) config('from_name'));
            self::$mailer->isHTML(true);
            self::$mailer->CharSet = 'UTF-8';
        }

        return self::$mailer;
    }

    /**
     * Fallback mailer for basic PHP mail() function
     *
     * @return object Anonymous class implementing basic mailer interface
     */
    private static function getFallbackMailer(): object
    {
        return new class () {
            public string $Subject = '';
            public string $Body = '';
            public string $AltBody = '';

            /** @var array<int, array{email: string, name: string}> */
            private array $to = [];

            /** @var array{email: string, name: string} */
            private array $from = ['email' => '', 'name' => ''];

            public function addAddress(string $email, string $name = ''): void
            {
                $this->to[] = ['email' => $email, 'name' => $name];
            }

            public function clearAddresses(): void
            {
                $this->to = [];
            }

            public function clearReplyTos(): void
            {
                // Fallback mailer doesn't support reply-to
            }

            public function addCC(string $email, string $name = ''): void
            {
                // Fallback mailer doesn't support CC
            }

            public function addBCC(string $email, string $name = ''): void
            {
                // Fallback mailer doesn't support BCC
            }

            public function setFrom(string $email, string $name = ''): void
            {
                $this->from = ['email' => $email, 'name' => $name];
            }

            public function send(): bool
            {
                $headers = "From: " . ($this->from['name'] ? $this->from['name'] . ' <' . $this->from['email'] . '>' : $this->from['email']) . "\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

                foreach ($this->to as $recipient) {
                    $toAddress = $recipient['name'] ? $recipient['name'] . ' <' . $recipient['email'] . '>' : $recipient['email'];
                    if (! mail($toAddress, $this->Subject, $this->Body, $headers)) {
                        return false;
                    }
                }

                return true;
            }

            public function isHTML(bool $html): void
            {
            }
        };
    }

    /**
     * Send sponsorship confirmation email to sponsor
     *
     * @param array<string, mixed> $sponsorship Sponsorship data
     * @return bool True if sent successfully
     */
    public static function sendSponsorConfirmation(array $sponsorship): bool
    {
        try {
            $mailer = self::getMailer();

            $mailer->addAddress((string) $sponsorship['sponsor_email'], (string) $sponsorship['sponsor_name']);
            $mailer->Subject = 'Christmas for Kids - Sponsorship Confirmation';

            $mailer->Body = self::getSponsorConfirmationTemplate($sponsorship);

            $success = $mailer->send();

            // Log the email
            self::logEmail(
                (string) $sponsorship['sponsor_email'],
                'sponsor_confirmation',
                $success ? 'sent' : 'failed',
                (int) $sponsorship['id']
            );

            return $success;
        } catch (Exception $e) {
            error_log('Failed to send sponsor confirmation email: ' . $e->getMessage());
            self::logEmail(
                (string) ($sponsorship['sponsor_email'] ?? 'unknown'),
                'sponsor_confirmation',
                'failed',
                (int) ($sponsorship['id'] ?? 0),
                $e->getMessage()
            );

            return false;
        }
    }

    /**
     * Send admin notification email
     *
     * @param string $subject Email subject
     * @param string $message Email message
     * @param array<string, mixed> $sponsorship Optional sponsorship data
     * @return bool True if sent successfully
     */
    public static function sendAdminNotification(string $subject, string $message, array $sponsorship = []): bool
    {
        try {
            $mailer = self::getMailer();

            $mailer->addAddress((string) config('admin_email'));
            $mailer->Subject = 'CFK Admin - ' . $subject;

            $mailer->Body = self::getAdminNotificationTemplate($subject, $message, $sponsorship);

            $success = $mailer->send();

            // Log the email
            self::logEmail(
                (string) config('admin_email'),
                'admin_notification',
                $success ? 'sent' : 'failed',
                (int) ($sponsorship['id'] ?? 0)
            );

            return $success;
        } catch (Exception $e) {
            error_log('Failed to send admin notification email: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Send multi-child sponsorship email (when sponsor adds more children)
     *
     * @param string $sponsorEmail Sponsor email address
     * @param array<int, array<string, mixed>> $sponsorships List of sponsorships
     * @return bool True if sent successfully
     */
    public static function sendMultiChildSponsorshipEmail(string $sponsorEmail, array $sponsorships): bool
    {
        if ($sponsorships === []) {
            return false;
        }

        try {
            $mailer = self::getMailer();
            $sponsorName = (string) $sponsorships[0]['sponsor_name'];

            $mailer->addAddress($sponsorEmail, $sponsorName);
            $mailer->Subject = 'Christmas for Kids - Updated Sponsorship Details';
            $mailer->Body = self::getMultiChildSponsorshipTemplate($sponsorName, $sponsorships);

            $success = $mailer->send();

            // Log the email
            self::logEmail(
                $sponsorEmail,
                'multi_child_update',
                $success ? 'sent' : 'failed',
                (int) $sponsorships[0]['id']
            );

            return $success;
        } catch (Exception $e) {
            error_log('Failed to send multi-child sponsorship email: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Send access link email to sponsor
     *
     * @param string $email Sponsor email address
     * @return bool True if sent successfully
     */
    public static function sendAccessLink(string $email): bool
    {
        try {
            // Get sponsorships for this email
            $sponsorships = Connection::fetchAll(
                "SELECT s.*, c.child_letter, f.family_number,
                        CONCAT(f.family_number, c.child_letter) as display_id
                 FROM sponsorships s
                 JOIN children c ON s.child_id = c.id
                 JOIN families f ON c.family_id = f.id
                 WHERE s.sponsor_email = ?
                 AND s.status IN ('confirmed', 'logged')
                 ORDER BY s.confirmation_date DESC",
                [$email]
            );

            if ($sponsorships === []) {
                return false; // No sponsorships found
            }

            $mailer = self::getMailer();

            // Clear any previous recipients
            $mailer->clearAddresses();

            // Use first sponsorship's name for greeting
            $sponsorName = (string) $sponsorships[0]['sponsor_name'];
            $mailer->addAddress($email, $sponsorName);
            $mailer->Subject = 'Christmas for Kids - Access Your Sponsorships';

            // Generate secure access token
            $token = self::generateAccessToken($email);
            $accessUrl = baseUrl("?page=my_sponsorships&token=$token");

            $mailer->Body = self::getAccessLinkTemplate($email, $sponsorName, $accessUrl, count($sponsorships));

            // Generate plain text version
            $mailer->AltBody = strip_tags($mailer->Body);

            $success = $mailer->send();

            // Log the email
            self::logEmail(
                $email,
                'access_link',
                $success ? 'sent' : 'failed',
                (int) $sponsorships[0]['id']
            );

            return $success;
        } catch (Exception $e) {
            error_log('Failed to send access link email: ' . $e->getMessage());
            self::logEmail(
                $email,
                'access_link',
                'failed',
                0,
                $e->getMessage()
            );

            return false;
        }
    }

    /**
     * Test email configuration
     *
     * @return (false|mixed|string)[] Test result with success status and message
     *
     * @psalm-return array{success: false|mixed, message: string}
     */
    public static function testEmailConfig(): array
    {
        try {
            $testEmail = (string) config('admin_email');
            $mailer = self::getMailer();

            $mailer->addAddress($testEmail);
            $mailer->Subject = 'CFK Email Test - ' . date('Y-m-d H:i:s');
            $mailer->Body = '<h2>Email Test Successful</h2><p>Your email configuration is working correctly!</p>';

            $success = $mailer->send();

            return [
                'success' => $success,
                'message' => $success ? 'Test email sent successfully' : 'Failed to send test email',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Email test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get sponsor confirmation email template (public for email queue access)
     *
     * @param array<string, mixed> $sponsorship Sponsorship data
     *
     * @return string HTML email template
     */
    public static function getSponsorConfirmationTemplate(array $sponsorship): string
    {
        $childDisplayId = sanitizeString((string) ($sponsorship['child_display_id'] ?? 'Unknown'));
        $childName = sanitizeString((string) ($sponsorship['child_name'] ?? 'Child'));
        $childAge = sanitizeInt($sponsorship['child_age'] ?? 0);
        $childGrade = sanitizeString((string) ($sponsorship['child_grade'] ?? ''));
        $childGender = $sponsorship['child_gender'] === 'M' ? 'Boy' : 'Girl';

        // Clothing sizes
        $shirtSize = sanitizeString((string) ($sponsorship['shirt_size'] ?? 'Not specified'));
        $pantSize = sanitizeString((string) ($sponsorship['pant_size'] ?? 'Not specified'));
        $shoeSize = sanitizeString((string) ($sponsorship['shoe_size'] ?? 'Not specified'));
        $jacketSize = sanitizeString((string) ($sponsorship['jacket_size'] ?? 'Not specified'));

        // Personal details
        $interests = sanitizeString((string) ($sponsorship['interests'] ?? 'Not specified'));
        $wishes = sanitizeString((string) ($sponsorship['wishes'] ?? 'Not specified'));
        $specialNeeds = sanitizeString((string) ($sponsorship['special_needs'] ?? 'None'));

        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #c41e3a; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; max-width: 700px; margin: 0 auto; }
                .footer { background: #f4f4f4; padding: 15px; text-align: center; font-size: 12px; }
                .child-info { background: #fff; border: 2px solid #2c5530; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .info-section { margin: 15px 0; }
                .info-label { font-weight: bold; color: #2c5530; display: inline-block; width: 140px; }
                .info-value { display: inline-block; }
                .sizes-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 10px 0; }
                .size-item { background: #f9f9f9; padding: 8px; border-radius: 4px; }
                .important-box { background: #fffacd; border-left: 4px solid #c41e3a; padding: 15px; margin: 20px 0; }
                .wishes-box { background: #e8f5e9; border-left: 4px solid #2c5530; padding: 15px; margin: 15px 0; }
                h3 { color: #2c5530; margin-top: 20px; }
                .print-note { background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 15px 0; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🎄 Christmas for Kids 🎄</h1>
                <h2>Thank You for Your Sponsorship!</h2>
            </div>

            <div class='content'>
                <p>Dear {$sponsorship['sponsor_name']},</p>

                <p>Thank you for choosing to sponsor <strong>Child {$childDisplayId}</strong>! You're making Christmas magical for a child in our community.</p>

                <div class='important-box'>
                    <h3>📋 IMPORTANT - Save This Email!</h3>
                    <p><strong>This email contains all the information you need to shop for your sponsored child.</strong></p>

                    <p>You do not need to buy everything on a child's Wish List. Typically a package includes 1 outfit, undergarments, socks, shoes, and 5 or 6 other wish list items.</p>

                    <p>If you cannot get everything mentioned above it is ok. Anything you can do is wonderful and appreciated. We can add some items to complete the package.</p>

                    <p><strong>Please no gift cards except for those related to video games.</strong> All gifts must be new — please do not wrap. If desired, you may include gift wrap for the parents.</p>

                    <p><strong>Please place all gifts in a large black bag, marked with that Child's Number.</strong></p>

                    <p><strong>All Gifts must be received by Friday, Dec. 5.</strong></p>

                    <p>Gifts can be dropped off weekdays 8 a.m.-5 p.m. at <strong>The Journal, 210 W North 1st Street in Seneca</strong>.</p>

                    <p style='font-size: 11px; margin-top: 10px;'><em>CFK Inc is a recognized 501(c)(3) not-for-profit organization; EIN: 82-3083435. This number may be used for tax deduction purposes.</em></p>

                    <p style='margin-top: 15px;'><strong>Questions?</strong> Contact us at <a href='mailto:" . config('admin_email') . "'>" . config('admin_email') . "</a></p>
                </div>

                <div class='child-info'>
                    <h3>👧 Child Information - {$childDisplayId}</h3>

                    <div class='info-section'>
                        <div><span class='info-label'>Child ID:</span> <span class='info-value'><strong>{$childDisplayId}</strong></span></div>
                        <div><span class='info-label'>First Name:</span> <span class='info-value'>{$childName}</span></div>
                        <div><span class='info-label'>Age:</span> <span class='info-value'>{$childAge} years old</span></div>
                        <div><span class='info-label'>Grade:</span> <span class='info-value'>{$childGrade}</span></div>
                        <div><span class='info-label'>Gender:</span> <span class='info-value'>{$childGender}</span></div>
                    </div>

                    <h3>👕 Clothing Sizes</h3>
                    <div class='sizes-grid'>
                        <div class='size-item'><strong>Shirt:</strong> {$shirtSize}</div>
                        <div class='size-item'><strong>Pants:</strong> {$pantSize}</div>
                        <div class='size-item'><strong>Shoes:</strong> {$shoeSize}</div>
                        <div class='size-item'><strong>Jacket:</strong> {$jacketSize}</div>
                    </div>

                    <div class='info-section'>
                        <h3>🎨 Essential Needs</h3>
                        <p style='background: #f9f9f9; padding: 10px; border-radius: 4px;'>{$interests}</p>
                    </div>

                    <div class='wishes-box'>
                        <h3>🎁 Christmas Wishes & Gift Ideas</h3>
                        <p><strong>{$wishes}</strong></p>
                    </div>

                    " . (! empty($specialNeeds) && $specialNeeds !== 'None' ? "
                    <div class='info-section'>
                        <h3>⚠️ Special Notes</h3>
                        <p style='background: #fff3cd; padding: 10px; border-radius: 4px;'>{$specialNeeds}</p>
                    </div>
                    " : "") . "
                </div>

                <div class='print-note'>
                    <strong>💡 Tip:</strong> Print this email and take it with you while shopping! It has everything you need.
                </div>

                <p style='margin-top: 20px;'><strong>Questions or need to make changes?</strong> Please contact us - we're here to help!</p>

                <p>With heartfelt gratitude,<br>
                <strong>The Christmas for Kids Team</strong></p>
            </div>

            <div class='footer'>
                <p><strong>Christmas for Kids</strong> | Making Christmas Magical for Children in Need</p>
                <p>📧 " . config('admin_email') . " | 📍 CFK Office - [ADDRESS TBD]</p>
                <p style='font-size: 11px; color: #666; margin-top: 10px;'>Please keep this email for your records. Gifts should be delivered unwrapped to our office.</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Get admin notification email template (public for email queue access)
     *
     * @param string $subject Email subject
     * @param string $message Email message
     * @param array<string, mixed> $sponsorship Optional sponsorship data
     *
     * @return string HTML email template
     */
    public static function getAdminNotificationTemplate(string $subject, string $message, array $sponsorship = []): string
    {
        $content = "<html><body style='font-family: Arial, sans-serif;'>";
        $content .= "<h2>CFK Admin Notification</h2>";
        $content .= "<p><strong>Subject:</strong> $subject</p>";
        $content .= "<div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #2c5530;'>";
        $content .= "<p>$message</p>";
        $content .= "</div>";

        if ($sponsorship !== []) {
            $childId = $sponsorship['child_display_id'] ?? 'Unknown';
            $requestDate = $sponsorship['request_date'] ?? 'Unknown';

            $content .= "<h3>Sponsorship Details:</h3>";
            $content .= "<ul>";
            $content .= "<li><strong>Sponsor:</strong> {$sponsorship['sponsor_name']}</li>";
            $content .= "<li><strong>Email:</strong> {$sponsorship['sponsor_email']}</li>";
            $content .= "<li><strong>Child:</strong> {$childId}</li>";
            $content .= "<li><strong>Date:</strong> {$requestDate}</li>";
            $content .= "</ul>";
        }

        $content .= "<p><a href='" . baseUrl('admin/manage_sponsorships.php') . "'>View in Admin Panel</a></p>";

        return $content . "</body></html>";
    }

    /**
     * Get multi-child sponsorship email template
     *
     * @param string $sponsorName Sponsor name
     * @param array<int, array<string, mixed>> $sponsorships List of sponsorships
     *
     * @return string HTML email template
     */
    private static function getMultiChildSponsorshipTemplate(string $sponsorName, array $sponsorships): string
    {
        $childCount = count($sponsorships);

        // Group by family
        $families = [];
        foreach ($sponsorships as $child) {
            $familyId = (int) $child['family_id'];
            if (! isset($families[$familyId])) {
                $families[$familyId] = [
                    'family_number' => $child['family_number'],
                    'children' => [],
                ];
            }
            $families[$familyId]['children'][] = $child;
        }

        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #c41e3a; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; max-width: 800px; margin: 0 auto; }
                .footer { background: #f4f4f4; padding: 15px; text-align: center; font-size: 12px; }
                .family-section { background: #f8f9fa; border: 2px solid #2c5530; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .family-header { background: #2c5530; color: white; padding: 10px; margin: -20px -20px 20px -20px; border-radius: 6px 6px 0 0; }
                .child-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 15px 0; }
                .child-header { border-bottom: 2px solid #2c5530; padding-bottom: 10px; margin-bottom: 10px; }
                .info-label { font-weight: bold; color: #2c5530; display: inline-block; width: 120px; }
                .sizes-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 10px 0; }
                .size-item { background: #f9f9f9; padding: 8px; border-radius: 4px; }
                .wishes-box { background: #e8f5e9; border-left: 4px solid #2c5530; padding: 15px; margin: 15px 0; }
                .special-needs-box { background: #fff3cd; border-left: 4px solid #c41e3a; padding: 15px; margin: 15px 0; }
                .important-box { background: #fffacd; border-left: 4px solid #c41e3a; padding: 15px; margin: 20px 0; }
                h3 { color: #2c5530; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🎄 Christmas for Kids 🎄</h1>
                <h2>Updated Sponsorship Details</h2>
            </div>

            <div class='content'>
                <p>Dear {$sponsorName},</p>

                <p><strong>Thank you for your generosity!</strong> You are now sponsoring <strong>{$childCount}</strong> child" . ($childCount > 1 ? 'ren' : '') . ".</p>

                <div class='important-box'>
                    <h3>📋 IMPORTANT - Print & Save This Email!</h3>
                    <p><strong>This email contains ALL the children you're sponsoring with complete shopping details.</strong></p>
                    <p><strong>What to do:</strong></p>
                    <ol>
                        <li><strong>Shop for gifts</strong> using the details below for ALL children</li>
                        <li><strong>Keep gifts UNWRAPPED</strong> (parents will wrap them)</li>
                        <li><strong>Deliver to CFK office</strong> by [DATE TBD]</li>
                        <li><strong>Contact us</strong> with questions: " . config('admin_email') . "</li>
                    </ol>
                </div>";

        // Add each family section
        foreach ($families as $family) {
            $html .= "
                <div class='family-section'>
                    <div class='family-header'>
                        <h3 style='color: white; margin: 0;'>Family {$family['family_number']}</h3>
                    </div>";

            // Add each child in the family
            foreach ($family['children'] as $child) {
                $childDisplayId = sanitizeString((string) $child['child_display_id']);
                $childName = sanitizeString((string) $child['child_name']);
                $childAge = sanitizeInt($child['child_age']);
                $childGrade = sanitizeString((string) $child['child_grade']);
                $childGender = $child['child_gender'] === 'M' ? 'Boy' : 'Girl';

                $shirtSize = sanitizeString((string) ($child['shirt_size'] ?? 'Not specified'));
                $pantSize = sanitizeString((string) ($child['pant_size'] ?? 'Not specified'));
                $shoeSize = sanitizeString((string) ($child['shoe_size'] ?? 'Not specified'));
                $jacketSize = sanitizeString((string) ($child['jacket_size'] ?? 'Not specified'));

                $interests = sanitizeString((string) ($child['interests'] ?? 'Not specified'));
                $wishes = sanitizeString((string) ($child['wishes'] ?? 'Not specified'));
                $specialNeeds = sanitizeString((string) ($child['special_needs'] ?? ''));

                $html .= "
                    <div class='child-card'>
                        <div class='child-header'>
                            <h3>Child {$childDisplayId} - {$childName}</h3>
                        </div>

                        <div style='margin: 10px 0;'>
                            <div><span class='info-label'>Child ID:</span> <strong>{$childDisplayId}</strong></div>
                            <div><span class='info-label'>First Name:</span> {$childName}</div>
                            <div><span class='info-label'>Age:</span> {$childAge} years old</div>
                            <div><span class='info-label'>Grade:</span> {$childGrade}</div>
                            <div><span class='info-label'>Gender:</span> {$childGender}</div>
                        </div>

                        <h4 style='color: #2c5530;'>👕 Clothing Sizes</h4>
                        <div class='sizes-grid'>
                            <div class='size-item'><strong>Shirt:</strong> {$shirtSize}</div>
                            <div class='size-item'><strong>Pants:</strong> {$pantSize}</div>
                            <div class='size-item'><strong>Shoes:</strong> {$shoeSize}</div>
                            <div class='size-item'><strong>Jacket:</strong> {$jacketSize}</div>
                        </div>

                        <div style='margin: 15px 0;'>
                            <h4 style='color: #2c5530;'>🎨 Essential Needs</h4>
                            <p style='background: #f9f9f9; padding: 10px; border-radius: 4px;'>{$interests}</p>
                        </div>

                        <div class='wishes-box'>
                            <h4 style='color: #2c5530; margin-top: 0;'>🎁 Christmas Wishes & Gift Ideas</h4>
                            <p><strong>{$wishes}</strong></p>
                        </div>";

                if (! empty($specialNeeds)) {
                    $html .= "
                        <div class='special-needs-box'>
                            <h4 style='color: #c41e3a; margin-top: 0;'>⚠️ Special Notes</h4>
                            <p>{$specialNeeds}</p>
                        </div>";
                }

                $html .= "
                    </div>";
            }

            $html .= "
                </div>";
        }

        return $html . ("
                <div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>💡 Shopping Tip:</strong> Print this email and take it with you! It has complete details for all {$childCount} child" . ($childCount > 1 ? 'ren' : '') . ".</p>
                </div>

                <p style='margin-top: 20px;'><strong>Questions or need to make changes?</strong> Please contact us - we're here to help!</p>

                <p>With heartfelt gratitude,<br>
                <strong>The Christmas for Kids Team</strong></p>
            </div>

            <div class='footer'>
                <p><strong>Christmas for Kids</strong> | Making Christmas Magical for Children in Need</p>
                <p>📧 " . config('admin_email') . " | 📍 CFK Office - [ADDRESS TBD]</p>
                <p style='font-size: 11px; color: #666; margin-top: 10px;'>You are sponsoring {$childCount} child" . ($childCount > 1 ? 'ren' : '') . ". Please deliver unwrapped gifts to our office.</p>
            </div>
        </body>
        </html>");
    }

    /**
     * Get access link email template
     *
     * @param string $email Sponsor email
     * @param string $name Sponsor name
     * @param string $accessUrl Access URL with token
     * @param int $childCount Number of sponsored children
     *
     * @return string HTML email template
     */
    private static function getAccessLinkTemplate(string $email, string $name, string $accessUrl, int $childCount): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2c5530 0%, #4a7c4e 100%); color: white; padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px 20px; }
                .button { display: inline-block; background: #2c5530; color: white !important; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .button:hover { background: #1e3d21; }
                .footer { background: #333; color: #999; padding: 20px; text-align: center; font-size: 12px; border-radius: 0 0 8px 8px; }
                .info-box { background: #fff; border-left: 4px solid #2c5530; padding: 15px; margin: 15px 0; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0;'>🎄 Christmas for Kids</h1>
                    <p style='margin: 10px 0 0 0; font-size: 18px;'>Access Your Sponsorships</p>
                </div>

                <div class='content'>
                    <p>Hi " . sanitizeString($name) . ",</p>

                    <p>Thank you for being part of Christmas for Kids! You requested access to view your confirmed sponsorships.</p>

                    <div class='info-box'>
                        <strong>📧 Email:</strong> " . sanitizeString($email) . "<br>
                        <strong>🎁 Sponsorships:</strong> $childCount " . ($childCount === 1 ? 'child' : 'children') . "
                    </div>

                    <p style='text-align: center;'>
                        <a href='$accessUrl' class='button'>View My Sponsorships</a>
                    </p>

                    <p><strong>What you'll find:</strong></p>
                    <ul>
                        <li>Complete details for each child you're sponsoring</li>
                        <li>Gift wishes and clothing sizes</li>
                        <li>Confirmation dates and child information</li>
                        <li>Print-friendly format for shopping</li>
                    </ul>

                    <p style='background: #fff3cd; border-left: 4px solid #f5b800; padding: 10px; border-radius: 4px;'>
                        <strong>🔒 Security:</strong> This link will expire in 24 hours. If you didn't request this, please ignore this email.
                    </p>

                    <p><strong>Need help?</strong> Contact us at " . config('admin_email') . "</p>

                    <p>With gratitude,<br>
                    <strong>The Christmas for Kids Team</strong></p>
                </div>

                <div class='footer'>
                    <p><strong>Christmas for Kids</strong> | Making Christmas Magical for Children in Need</p>
                    <p>📧 " . config('admin_email') . "</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate secure access token for sponsor
     *
     * @param string $email Sponsor email address
     *
     * @return string Base64 encoded token
     */
    private static function generateAccessToken(string $email): string
    {
        // Create a token that expires in 24 hours
        $data = [
            'email' => $email,
            'expires' => time() + (24 * 60 * 60),
        ];

        $json = json_encode($data);
        $signature = hash_hmac('sha256', (string) $json, (string) config('secret_key', 'cfk-default-secret'));

        return base64_encode($json . '|' . $signature);
    }

    /**
     * Verify access token
     *
     * @param string $token Token to verify
     * @return string|null Email address if valid, null otherwise
     */
    public static function verifyAccessToken(string $token): ?string
    {
        try {
            $decoded = base64_decode($token);
            [$json, $signature] = explode('|', $decoded, 2);

            $expectedSignature = hash_hmac('sha256', $json, (string) config('secret_key', 'cfk-default-secret'));

            if (! hash_equals($expectedSignature, $signature)) {
                return null; // Invalid signature
            }

            $data = json_decode($json, true);

            if ($data['expires'] < time()) {
                return null; // Token expired
            }

            return (string) $data['email'];
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Log email for audit trail
     *
     * @param string $recipient Email recipient
     * @param string $type Email type
     * @param string $status Send status (sent/failed)
     * @param int $sponsorshipId Optional sponsorship ID
     * @param string $error Optional error message
     */
    private static function logEmail(string $recipient, string $type, string $status, int $sponsorshipId = 0, string $error = ''): void
    {
        try {
            Connection::insert('email_log', [
                'recipient' => $recipient,
                'type' => $type,
                'status' => $status,
                'sponsorship_id' => $sponsorshipId ?: null,
                'error_message' => $error ?: null,
                'sent_date' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            error_log('Failed to log email: ' . $e->getMessage());
        }
    }
}
