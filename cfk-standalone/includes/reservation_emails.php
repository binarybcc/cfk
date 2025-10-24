<?php

declare(strict_types=1);

/**
 * Reservation Email Functions
 * v1.5 - Handles email notifications for reservation system
 */

if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

require_once __DIR__ . '/email_manager.php';
require_once __DIR__ . '/functions.php'; // For cleanWishesText()

/**
 * Send reservation confirmation email to sponsor
 *
 * @param array $reservation Reservation data from database
 * @return array ['success' => bool, 'message' => string]
 */
function sendReservationConfirmationEmail(array $reservation): array
{
    try {
        $mailer = CFK_Email_Manager::getMailer();

        // Clear any previous recipients
        $mailer->clearAddresses();

        // Set recipient
        $mailer->addAddress($reservation['sponsor_email'], $reservation['sponsor_name']);

        // Set subject
        $mailer->Subject = 'Your Christmas for Kids Sponsorship Reservation - ' . $reservation['reservation_token'];

        // Generate HTML email body
        $mailer->Body = generateReservationConfirmationHTML($reservation);

        // Generate plain text version
        $mailer->AltBody = generateReservationConfirmationText($reservation);

        // Send email
        if ($mailer->send()) {
            // Log email
            logReservationEmail(
                $reservation['id'],
                $reservation['sponsor_email'],
                'reservation_confirmation',
                'sent'
            );

            return [
                'success' => true,
                'message' => 'Confirmation email sent successfully'
            ];
        } else {
            throw new Exception('Failed to send email');
        }
    } catch (Exception $e) {
        error_log('Reservation email error: ' . $e->getMessage());

        // Log failed attempt
        logReservationEmail(
            $reservation['id'] ?? 0,
            $reservation['sponsor_email'] ?? 'unknown',
            'reservation_confirmation',
            'failed',
            $e->getMessage()
        );

        return [
            'success' => false,
            'message' => 'Failed to send confirmation email: ' . $e->getMessage()
        ];
    }
}

/**
 * Send reservation notification to admin
 *
 * @param array $reservation Reservation data
 */
function sendAdminReservationNotification(array $reservation): bool
{
    try {
        $mailer = CFK_Email_Manager::getMailer();

        $mailer->clearAddresses();
        $mailer->addAddress(config('admin_email'), 'CFK Admin');

        $mailer->Subject = 'New Sponsorship Reservation - ' . $reservation['total_children'] . ' children';
        $mailer->Body = generateAdminNotificationHTML($reservation);
        $mailer->AltBody = generateAdminNotificationText($reservation);

        return $mailer->send();
    } catch (Exception $e) {
        error_log('Admin notification error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generate HTML email for reservation confirmation
 */
function generateReservationConfirmationHTML(array $reservation): string
{
    $children = $reservation['children'] ?? [];
    new DateTime($reservation['expires_at']);
    $createdAt = new DateTime($reservation['created_at']);

    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmation</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #2c5530 0%, #3a6f3f 100%); padding: 40px 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">✓ Reservation Confirmed!</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; opacity: 0.95;">Thank you for sponsoring ' . count($children) . ' ' . (count($children) === 1 ? 'child' : 'children') . ' this Christmas</p>
                        </td>
                    </tr>

                    <!-- Sponsorship Confirmed -->
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="color: #2c5530; margin: 0 0 20px 0; font-size: 20px;">✅ Sponsorship Confirmed</h2>
                            <div style="background-color: #d4edda; border: 2px solid #28a745; border-radius: 6px; padding: 20px;">
                                <p style="color: #155724; margin: 0; font-size: 16px; line-height: 1.6;">
                                    <strong>Your sponsorship is now confirmed!</strong><br/>
                                    These children are reserved for you. Only Christmas for Kids admin can cancel this sponsorship.<br/>
                                    <br/>
                                    <strong>Confirmation Date:</strong> ' . $createdAt->format('F j, Y g:i A') . '
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Sponsored Children -->
                    <tr>
                        <td style="padding: 30px; background-color: #f8fdf9;">
                            <h2 style="color: #2c5530; margin: 0 0 20px 0; font-size: 20px;">🎄 Your Sponsored Children (' . count($children) . ')</h2>';

    foreach ($children as $child) {
        $html .= '
                            <div style="background: #ffffff; border: 2px solid #2c5530; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                                <h3 style="color: #2c5530; margin: 0 0 15px 0; font-size: 18px; border-bottom: 2px solid #2c5530; padding-bottom: 10px;">' . htmlspecialchars((string) $child['display_id']) . '</h3>

                                <table width="100%" cellpadding="5">
                                    <tr>
                                        <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Age:</td>
                                        <td style="color: #666; padding: 5px 0;">' . htmlspecialchars((string) $child['age']) . ' years old</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Gender:</td>
                                        <td style="color: #666; padding: 5px 0;">' . ($child['gender'] === 'M' ? 'Boy' : 'Girl') . '</td>
                                    </tr>
                                </table>';

        // Interests/Essential Needs
        if (!empty($child['interests'])) {
            $html .= '
                                <div style="margin-top: 15px;">
                                    <strong style="color: #2c5530;">💙 Essential Needs/Interests:</strong>
                                    <p style="margin: 5px 0; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #3a6f3f; border-radius: 4px; color: #666;">' . nl2br(htmlspecialchars((string) $child['interests'])) . '</p>
                                </div>';
        }

        // Christmas Wishes
        if (!empty($child['wishes'])) {
            $html .= '
                                <div style="margin-top: 15px;">
                                    <strong style="color: #c41e3a;">🎁 Christmas Wish List:</strong>
                                    <p style="margin: 5px 0; padding: 10px; background-color: #fef5f5; border-left: 3px solid #c41e3a; border-radius: 4px; color: #666;">' . nl2br(htmlspecialchars(cleanWishesText((string) $child['wishes']))) . '</p>
                                </div>';
        }

        // Special Needs
        if (!empty($child['special_needs'])) {
            $html .= '
                                <div style="margin-top: 15px;">
                                    <strong style="color: #856404;">⚠️ Special Needs/Considerations:</strong>
                                    <p style="margin: 5px 0; padding: 10px; background-color: #fff3cd; border-left: 3px solid #f5b800; border-radius: 4px; color: #666;">' . nl2br(htmlspecialchars((string) $child['special_needs'])) . '</p>
                                </div>';
        }

        // Clothing Sizes Section
        $html .= '
                                <div style="margin-top: 15px; background-color: #e7f3ff; padding: 15px; border-radius: 6px;">
                                    <strong style="color: #2c5530; font-size: 16px;">👕 Clothing Sizes:</strong>
                                    <table width="100%" cellpadding="5" style="margin-top: 10px;">';

        if (!empty($child['shirt_size'])) {
            $html .= '
                                        <tr>
                                            <td style="color: #2c5530; font-weight: bold; padding: 5px 0; width: 40%;">Shirt:</td>
                                            <td style="color: #666; padding: 5px 0;">' . htmlspecialchars((string) $child['shirt_size']) . '</td>
                                        </tr>';
        }

        if (!empty($child['pant_size'])) {
            $html .= '
                                        <tr>
                                            <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Pants:</td>
                                            <td style="color: #666; padding: 5px 0;">' . htmlspecialchars((string) $child['pant_size']) . '</td>
                                        </tr>';
        }

        if (!empty($child['jacket_size'])) {
            $html .= '
                                        <tr>
                                            <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Jacket:</td>
                                            <td style="color: #666; padding: 5px 0;">' . htmlspecialchars((string) $child['jacket_size']) . '</td>
                                        </tr>';
        }

        if (!empty($child['shoe_size'])) {
            $html .= '
                                        <tr>
                                            <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Shoes:</td>
                                            <td style="color: #666; padding: 5px 0;">' . htmlspecialchars((string) $child['shoe_size']) . '</td>
                                        </tr>';
        }

        $html .= '
                                    </table>
                                </div>';

        $html .= '
                            </div>';
    }

    return $html . ('
                        </td>
                    </tr>

                    <!-- Next Steps -->
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="color: #2c5530; margin: 0 0 20px 0; font-size: 20px;">📋 Important Gift Guidelines</h2>

                            <div style="background-color: #fff3cd; border: 2px solid #f5b800; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                <h3 style="color: #856404; margin: 0 0 15px 0; font-size: 18px;">🎁 What to Include</h3>
                                <p style="color: #856404; margin: 0 0 10px 0; line-height: 1.6;">
                                    You do <strong>not</strong> need to buy everything on a child\'s Wish List. Typically, a package includes:
                                </p>
                                <ul style="color: #856404; margin: 0 0 15px 0; padding-left: 20px; line-height: 1.8;">
                                    <li>1 outfit</li>
                                    <li>Undergarments and socks</li>
                                    <li>Shoes</li>
                                    <li>5 or 6 other wish list items</li>
                                </ul>
                                <p style="color: #856404; margin: 0; line-height: 1.6;">
                                    <strong>If you can\'t get everything mentioned above, it\'s okay!</strong> Anything you can do is wonderful and appreciated. We can add some items to complete the package.
                                </p>
                            </div>

                            <div style="background-color: #d4edda; border: 2px solid #28a745; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                <h3 style="color: #155724; margin: 0 0 15px 0; font-size: 18px;">✅ Important Notes</h3>
                                <ul style="color: #155724; margin: 0; padding-left: 20px; line-height: 1.8;">
                                    <li style="margin-bottom: 10px;"><strong>No gift cards</strong> except those related to video games</li>
                                    <li style="margin-bottom: 10px;"><strong>All gifts must be new</strong></li>
                                    <li style="margin-bottom: 10px;"><strong>Gifts must NOT be wrapped</strong> - If desired, you may include gift wrap</li>
                                    <li><strong>Place all gifts in a large bag</strong>, marked with that child\'s number</li>
                                </ul>
                            </div>

                            <div style="background-color: #f8d7da; border: 2px solid #c41e3a; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                <h3 style="color: #721c24; margin: 0 0 15px 0; font-size: 18px;">📅 Deadline & Drop-Off</h3>
                                <p style="color: #721c24; margin: 0 0 15px 0; line-height: 1.6;">
                                    <strong>All gifts must be received by Friday, December 5th</strong>
                                </p>
                                <p style="color: #721c24; margin: 0; line-height: 1.6;">
                                    <strong>Drop-off location:</strong><br/>
                                    The Journal<br/>
                                    210 W North 1st Street, Seneca<br/>
                                    Weekdays 8 a.m. - 5 p.m.
                                </p>
                            </div>

                            <div style="background-color: #e7f3ff; border: 2px solid #2c5530; border-radius: 8px; padding: 15px;">
                                <p style="color: #2c5530; margin: 0; font-size: 13px; line-height: 1.6;">
                                    <strong>Tax Deduction:</strong> CFK Inc is a recognized 501(c)(3) not-for-profit organization<br/>
                                    EIN: 82-3083435 - This number may be used for tax deduction purposes.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px; background-color: #f8f9fa; border-radius: 0 0 8px 8px; text-align: center;">
                            <p style="color: #666; margin: 0 0 10px 0; font-size: 14px;">
                                Questions? Contact us at <a href="mailto:' . config('admin_email') . '" style="color: #2c5530;">' . config('admin_email') . '</a>
                            </p>
                            <p style="color: #999; margin: 0; font-size: 12px;">
                                Christmas for Kids • ' . config('app_version') . '<br/>
                                Bringing Christmas joy to local children in need
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>');
}

/**
 * Generate plain text version of confirmation email
 */
function generateReservationConfirmationText(array $reservation): string
{
    $children = $reservation['children'] ?? [];
    $text = "RESERVATION CONFIRMED!\n\n";
    $text .= "Thank you for sponsoring " . count($children) . " " . (count($children) === 1 ? 'child' : 'children') . " this Christmas!\n\n";
    $text .= "SPONSORSHIP CONFIRMED\n";
    $text .= "Your sponsorship is now confirmed!\n";
    $text .= "These children are reserved for you.\n";
    $text .= "Only Christmas for Kids admin can cancel this sponsorship.\n";
    $text .= "Confirmation Date: " . date('F j, Y g:i A', strtotime((string) $reservation['created_at'])) . "\n\n";
    $text .= "YOUR SPONSORED CHILDREN (" . count($children) . ")\n";
    $text .= str_repeat('-', 50) . "\n\n";

    foreach ($children as $child) {
        $text .= $child['display_id'] . "\n";
        $text .= "Age: " . $child['age'] . " years old\n";
        $text .= "Gender: " . ($child['gender'] === 'M' ? 'Boy' : 'Girl') . "\n\n";

        // Essential Needs/Interests
        if (!empty($child['interests'])) {
            $text .= "ESSENTIAL NEEDS/INTERESTS:\n" . $child['interests'] . "\n\n";
        }

        // Christmas Wish List
        if (!empty($child['wishes'])) {
            $text .= "CHRISTMAS WISH LIST:\n" . cleanWishesText($child['wishes']) . "\n\n";
        }

        // Special Needs
        if (!empty($child['special_needs'])) {
            $text .= "SPECIAL NEEDS/CONSIDERATIONS:\n" . $child['special_needs'] . "\n\n";
        }

        // Clothing Sizes
        $text .= "CLOTHING SIZES:\n";
        if (!empty($child['shirt_size'])) {
            $text .= "  Shirt: " . $child['shirt_size'] . "\n";
        }
        if (!empty($child['pant_size'])) {
            $text .= "  Pants: " . $child['pant_size'] . "\n";
        }
        if (!empty($child['jacket_size'])) {
            $text .= "  Jacket: " . $child['jacket_size'] . "\n";
        }
        if (!empty($child['shoe_size'])) {
            $text .= "  Shoes: " . $child['shoe_size'] . "\n";
        }
        $text .= "\n" . str_repeat('-', 50) . "\n\n";
    }

    $text .= "IMPORTANT GIFT GUIDELINES\n";
    $text .= str_repeat('=', 50) . "\n\n";

    $text .= "WHAT TO INCLUDE:\n";
    $text .= "You do NOT need to buy everything on a child's Wish List.\n";
    $text .= "Typically, a package includes:\n";
    $text .= "  • 1 outfit\n";
    $text .= "  • Undergarments and socks\n";
    $text .= "  • Shoes\n";
    $text .= "  • 5 or 6 other wish list items\n\n";
    $text .= "If you can't get everything mentioned above, it's okay!\n";
    $text .= "Anything you can do is wonderful and appreciated.\n";
    $text .= "We can add some items to complete the package.\n\n";

    $text .= "IMPORTANT NOTES:\n";
    $text .= "  • No gift cards except those related to video games\n";
    $text .= "  • All gifts must be new\n";
    $text .= "  • Gifts must NOT be wrapped - If desired, you may include gift wrap\n";
    $text .= "  • Place all gifts in a large bag, marked with that child's number\n\n";

    $text .= "DEADLINE & DROP-OFF:\n";
    $text .= "All gifts must be received by Friday, December 5th\n\n";
    $text .= "Drop-off location:\n";
    $text .= "  The Journal\n";
    $text .= "  210 W North 1st Street, Seneca\n";
    $text .= "  Weekdays 8 a.m. - 5 p.m.\n\n";

    $text .= "TAX DEDUCTION:\n";
    $text .= "CFK Inc is a recognized 501(c)(3) not-for-profit organization\n";
    $text .= "EIN: 82-3083435 - This number may be used for tax deduction purposes.\n\n";

    return $text . ("Questions? Contact us at " . config('admin_email') . "\n");
}

/**
 * Generate admin notification HTML
 */
function generateAdminNotificationHTML(array $reservation): string
{
    return '<html><body><h2>New Reservation</h2><p>Sponsor: ' . htmlspecialchars((string) $reservation['sponsor_name']) . ' (' . htmlspecialchars((string) $reservation['sponsor_email']) . ')</p><p>Children: ' . $reservation['total_children'] . '</p><p>Token: ' . htmlspecialchars((string) $reservation['reservation_token']) . '</p></body></html>';
}

/**
 * Generate admin notification text
 */
function generateAdminNotificationText(array $reservation): string
{
    return "New Reservation\nSponsor: " . $reservation['sponsor_name'] . " (" . $reservation['sponsor_email'] . ")\nChildren: " . $reservation['total_children'] . "\nToken: " . $reservation['reservation_token'];
}

/**
 * Log email to database
 */
function logReservationEmail(int $reservationId, string $email, string $type, string $status, string $error = ''): void
{
    try {
        Database::insert('email_log', [
            'recipient_email' => $email,
            'email_type' => $type,
            'subject' => 'Reservation Confirmation',
            'status' => $status,
            'error_message' => $error ?: null,
            'reservation_id' => $reservationId,
            'sent_at' => gmdate('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log('Failed to log email: ' . $e->getMessage());
    }
}

/**
 * Send access link email to sponsor (using same pattern as reservation emails)
 *
 * @param string $email Sponsor email address
 * @return array ['success' => bool, 'message' => string]
 */
function sendAccessLinkEmail(string $email): array
{
    error_log("ACCESS LINK: Function called for email: " . $email);
    try {
        // Get sponsorships for this email with full child details
        error_log("ACCESS LINK: Fetching sponsorships from database");
        $sponsorships = Database::fetchAll(
            "SELECT s.*,
                    c.child_letter, c.age, c.gender, c.wishes, c.interests, c.special_needs,
                    c.shirt_size, c.pant_size, c.jacket_size, c.shoe_size,
                    f.family_number,
                    CONCAT(f.family_number, c.child_letter) as display_id
             FROM sponsorships s
             JOIN children c ON s.child_id = c.id
             JOIN families f ON c.family_id = f.id
             WHERE s.sponsor_email = ?
             AND s.status = 'confirmed'
             ORDER BY s.confirmation_date DESC",
            [$email]
        );

        error_log("ACCESS LINK: Found " . count($sponsorships) . " sponsorships");

        if ($sponsorships === []) {
            error_log("ACCESS LINK: No sponsorships found");
            return [
                'success' => false,
                'message' => 'No confirmed sponsorships found for this email address'
            ];
        }

        error_log("ACCESS LINK: Getting mailer");
        $mailer = CFK_Email_Manager::getMailer();

        // Clear any previous recipients
        error_log("ACCESS LINK: Clearing addresses");
        $mailer->clearAddresses();

        // Set recipient
        $sponsorName = $sponsorships[0]['sponsor_name'];
        error_log("ACCESS LINK: Adding recipient: $email ($sponsorName)");
        $mailer->addAddress($email, $sponsorName);

        // Set subject
        $mailer->Subject = 'Christmas for Kids - Your Sponsorship Details Enclosed';
        error_log("ACCESS LINK: Subject set");

        // Generate HTML email body
        error_log("ACCESS LINK: Generating HTML body");
        $mailer->Body = generateAccessLinkHTML($email, $sponsorName, $sponsorships);

        // Generate plain text version
        error_log("ACCESS LINK: Generating text body");
        $mailer->AltBody = generateAccessLinkText($email, $sponsorName, $sponsorships);

        // Send email
        error_log("ACCESS LINK: Attempting to send email");
        if ($mailer->send()) {
            error_log("ACCESS LINK: Email sent successfully");
            // Log email
            logReservationEmail(
                $sponsorships[0]['id'],
                $email,
                'access_link',
                'sent'
            );

            return [
                'success' => true,
                'message' => 'Access link email sent successfully'
            ];
        } else {
            throw new Exception('Failed to send email');
        }
    } catch (Exception $e) {
        error_log('Access link email error: ' . $e->getMessage());

        // Log failed attempt
        logReservationEmail(
            0,
            $email ?? 'unknown',
            'access_link',
            'failed',
            $e->getMessage()
        );

        return [
            'success' => false,
            'message' => 'Failed to send access link email: ' . $e->getMessage()
        ];
    }
}

/**
 * Generate access token for secure link
 */
function generateAccessToken(string $email): string
{
    $data = [
        'email' => $email,
        'expires' => time() + (24 * 60 * 60) // 24 hours
    ];

    $json = json_encode($data);
    $signature = hash_hmac('sha256', $json, config('secret_key', 'cfk-default-secret'));

    return base64_encode($json . '|' . $signature);
}

/**
 * Verify access token
 */
function verifyAccessToken(string $token): ?string
{
    try {
        $decoded = base64_decode($token);
        if (!str_contains($decoded, '|')) {
            return null;
        }

        [$json, $signature] = explode('|', $decoded, 2);

        $expectedSignature = hash_hmac('sha256', $json, config('secret_key', 'cfk-default-secret'));

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $data = json_decode($json, true);

        if ($data['expires'] < time()) {
            return null;
        }

        return $data['email'];
    } catch (Exception) {
        return null;
    }
}

/**
 * Generate HTML email for access link
 */
function generateAccessLinkHTML(string $email, string $name, array $sponsorships): string
{
    $childCount = count($sponsorships);

    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Sponsorships</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #2c5530 0%, #3a6f3f 100%); padding: 40px 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">🎄 Christmas for Kids</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; opacity: 0.95; font-size: 18px;">Your Sponsorship Details</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="font-size: 16px; color: #333; margin: 0 0 20px 0;">Hi ' . htmlspecialchars($name) . ',</p>

                            <p style="font-size: 16px; color: #333; margin: 0 0 20px 0;">Thank you for sponsoring ' . $childCount . ' ' . ($childCount === 1 ? 'child' : 'children') . ' this Christmas! This email contains all the details you need for your sponsored ' . ($childCount === 1 ? 'child' : 'children') . ', including wish lists, clothing sizes, and interests.</p>

                            <p style="font-size: 14px; color: #666; margin: 0 0 20px 0; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #2c5530; border-radius: 4px;"><strong>📌 Tip:</strong> Save this email for reference when shopping! All the information you need is included below.</p>
                        </td>
                    </tr>

                    <!-- Sponsored Children -->
                    <tr>
                        <td style="padding: 30px; background-color: #f8fdf9;">
                            <h2 style="color: #2c5530; margin: 0 0 20px 0; font-size: 20px;">🎄 Your Sponsored Children (' . $childCount . ')</h2>';

    // Loop through each sponsorship and display details
    foreach ($sponsorships as $child) {
        $html .= '
                            <div style="background: #ffffff; border: 2px solid #2c5530; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                                <h3 style="color: #2c5530; margin: 0 0 15px 0; font-size: 18px; border-bottom: 2px solid #2c5530; padding-bottom: 10px;">' . htmlspecialchars((string) $child['display_id']) . '</h3>

                                <table width="100%" cellpadding="5">
                                    <tr>
                                        <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Age:</td>
                                        <td style="color: #666; padding: 5px 0;">' . htmlspecialchars((string) $child['age']) . ' years old</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Gender:</td>
                                        <td style="color: #666; padding: 5px 0;">' . ($child['gender'] === 'M' ? 'Boy' : 'Girl') . '</td>
                                    </tr>
                                </table>';

        // Interests/Essential Needs
        if (!empty($child['interests'])) {
            $html .= '
                                <div style="margin-top: 15px;">
                                    <strong style="color: #2c5530;">💙 Essential Needs/Interests:</strong>
                                    <p style="margin: 5px 0; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #3a6f3f; border-radius: 4px; color: #666;">' . nl2br(htmlspecialchars((string) $child['interests'])) . '</p>
                                </div>';
        }

        // Christmas Wishes
        if (!empty($child['wishes'])) {
            $html .= '
                                <div style="margin-top: 15px;">
                                    <strong style="color: #c41e3a;">🎁 Christmas Wish List:</strong>
                                    <p style="margin: 5px 0; padding: 10px; background-color: #fef5f5; border-left: 3px solid #c41e3a; border-radius: 4px; color: #666;">' . nl2br(htmlspecialchars(cleanWishesText((string) $child['wishes']))) . '</p>
                                </div>';
        }

        // Special Needs
        if (!empty($child['special_needs'])) {
            $html .= '
                                <div style="margin-top: 15px;">
                                    <strong style="color: #856404;">⚠️ Special Needs/Considerations:</strong>
                                    <p style="margin: 5px 0; padding: 10px; background-color: #fff3cd; border-left: 3px solid #f5b800; border-radius: 4px; color: #666;">' . nl2br(htmlspecialchars((string) $child['special_needs'])) . '</p>
                                </div>';
        }

        // Clothing Sizes Section
        $html .= '
                                <div style="margin-top: 15px; background-color: #e7f3ff; padding: 15px; border-radius: 6px;">
                                    <strong style="color: #2c5530; font-size: 16px;">👕 Clothing Sizes:</strong>
                                    <table width="100%" cellpadding="5" style="margin-top: 10px;">';

        if (!empty($child['shirt_size'])) {
            $html .= '
                                        <tr>
                                            <td style="color: #2c5530; font-weight: bold; padding: 5px 0; width: 40%;">Shirt:</td>
                                            <td style="color: #666; padding: 5px 0;">' . htmlspecialchars((string) $child['shirt_size']) . '</td>
                                        </tr>';
        }

        if (!empty($child['pant_size'])) {
            $html .= '
                                        <tr>
                                            <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Pants:</td>
                                            <td style="color: #666; padding: 5px 0;">' . htmlspecialchars((string) $child['pant_size']) . '</td>
                                        </tr>';
        }

        if (!empty($child['jacket_size'])) {
            $html .= '
                                        <tr>
                                            <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Jacket:</td>
                                            <td style="color: #666; padding: 5px 0;">' . htmlspecialchars((string) $child['jacket_size']) . '</td>
                                        </tr>';
        }

        if (!empty($child['shoe_size'])) {
            $html .= '
                                        <tr>
                                            <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Shoes:</td>
                                            <td style="color: #666; padding: 5px 0;">' . htmlspecialchars((string) $child['shoe_size']) . '</td>
                                        </tr>';
        }

        $html .= '
                                    </table>
                                </div>';

        $html .= '
                            </div>';
    }

    return $html . ('
                        </td>
                    </tr>

                    <!-- Next Steps -->
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="color: #2c5530; margin: 0 0 20px 0; font-size: 20px;">📋 Important Gift Guidelines</h2>

                            <div style="background-color: #fff3cd; border: 2px solid #f5b800; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                <h3 style="color: #856404; margin: 0 0 15px 0; font-size: 18px;">🎁 What to Include</h3>
                                <p style="color: #856404; margin: 0 0 10px 0; line-height: 1.6;">
                                    You do <strong>not</strong> need to buy everything on a child\'s Wish List. Typically, a package includes:
                                </p>
                                <ul style="color: #856404; margin: 0 0 15px 0; padding-left: 20px; line-height: 1.8;">
                                    <li>1 outfit</li>
                                    <li>Undergarments and socks</li>
                                    <li>Shoes</li>
                                    <li>5 or 6 other wish list items</li>
                                </ul>
                                <p style="color: #856404; margin: 0; line-height: 1.6;">
                                    <strong>If you can\'t get everything mentioned above, it\'s okay!</strong> Anything you can do is wonderful and appreciated. We can add some items to complete the package.
                                </p>
                            </div>

                            <div style="background-color: #d4edda; border: 2px solid #28a745; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                <h3 style="color: #155724; margin: 0 0 15px 0; font-size: 18px;">✅ Important Notes</h3>
                                <ul style="color: #155724; margin: 0; padding-left: 20px; line-height: 1.8;">
                                    <li style="margin-bottom: 10px;"><strong>No gift cards</strong> except those related to video games</li>
                                    <li style="margin-bottom: 10px;"><strong>All gifts must be new</strong></li>
                                    <li style="margin-bottom: 10px;"><strong>Gifts must NOT be wrapped</strong> - If desired, you may include gift wrap</li>
                                    <li><strong>Place all gifts in a large bag</strong>, marked with that child\'s number</li>
                                </ul>
                            </div>

                            <div style="background-color: #f8d7da; border: 2px solid #c41e3a; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                <h3 style="color: #721c24; margin: 0 0 15px 0; font-size: 18px;">📅 Deadline & Drop-Off</h3>
                                <p style="color: #721c24; margin: 0 0 15px 0; line-height: 1.6;">
                                    <strong>All gifts must be received by Friday, December 5th</strong>
                                </p>
                                <p style="color: #721c24; margin: 0; line-height: 1.6;">
                                    <strong>Drop-off location:</strong><br/>
                                    The Journal<br/>
                                    210 W North 1st Street, Seneca<br/>
                                    Weekdays 8 a.m. - 5 p.m.
                                </p>
                            </div>

                            <div style="background-color: #e7f3ff; border: 2px solid #2c5530; border-radius: 8px; padding: 15px;">
                                <p style="color: #2c5530; margin: 0; font-size: 13px; line-height: 1.6;">
                                    <strong>Tax Deduction:</strong> CFK Inc is a recognized 501(c)(3) not-for-profit organization<br/>
                                    EIN: 82-3083435 - This number may be used for tax deduction purposes.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px; background-color: #f8f9fa; border-radius: 0 0 8px 8px; text-align: center;">
                            <p style="color: #666; margin: 0 0 10px 0; font-size: 14px;">
                                Questions? Contact us at <a href="mailto:' . config('admin_email') . '" style="color: #2c5530;">' . config('admin_email') . '</a>
                            </p>
                            <p style="color: #999; margin: 0; font-size: 12px;">
                                Christmas for Kids • ' . config('app_version') . '<br/>
                                Bringing Christmas joy to local children in need
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>');
}

/**
 * Generate plain text version of access link email
 */
function generateAccessLinkText(string $email, string $name, array $sponsorships): string
{
    $childCount = count($sponsorships);

    $text = "CHRISTMAS FOR KIDS - YOUR SPONSORSHIPS\n";
    $text .= "==========================================\n\n";
    $text .= "Hi $name,\n\n";
    $text .= "Thank you for sponsoring $childCount " . ($childCount === 1 ? 'child' : 'children') . " this Christmas! Below are the complete details for your sponsorships:\n\n";
    $text .= "YOUR SPONSORED CHILDREN (" . $childCount . ")\n";
    $text .= str_repeat('-', 50) . "\n\n";

    foreach ($sponsorships as $child) {
        $text .= $child['display_id'] . "\n";
        $text .= "Age: " . $child['age'] . " years old\n";
        $text .= "Gender: " . ($child['gender'] === 'M' ? 'Boy' : 'Girl') . "\n\n";

        // Essential Needs/Interests
        if (!empty($child['interests'])) {
            $text .= "ESSENTIAL NEEDS/INTERESTS:\n" . $child['interests'] . "\n\n";
        }

        // Christmas Wish List
        if (!empty($child['wishes'])) {
            $text .= "CHRISTMAS WISH LIST:\n" . cleanWishesText($child['wishes']) . "\n\n";
        }

        // Special Needs
        if (!empty($child['special_needs'])) {
            $text .= "SPECIAL NEEDS/CONSIDERATIONS:\n" . $child['special_needs'] . "\n\n";
        }

        // Clothing Sizes
        $text .= "CLOTHING SIZES:\n";
        if (!empty($child['shirt_size'])) {
            $text .= "  Shirt: " . $child['shirt_size'] . "\n";
        }
        if (!empty($child['pant_size'])) {
            $text .= "  Pants: " . $child['pant_size'] . "\n";
        }
        if (!empty($child['jacket_size'])) {
            $text .= "  Jacket: " . $child['jacket_size'] . "\n";
        }
        if (!empty($child['shoe_size'])) {
            $text .= "  Shoes: " . $child['shoe_size'] . "\n";
        }
        $text .= "\n" . str_repeat('-', 50) . "\n\n";
    }

    $text .= "IMPORTANT GIFT GUIDELINES\n";
    $text .= str_repeat('=', 50) . "\n\n";

    $text .= "WHAT TO INCLUDE:\n";
    $text .= "You do NOT need to buy everything on a child's Wish List.\n";
    $text .= "Typically, a package includes:\n";
    $text .= "  • 1 outfit\n";
    $text .= "  • Undergarments and socks\n";
    $text .= "  • Shoes\n";
    $text .= "  • 5 or 6 other wish list items\n\n";
    $text .= "If you can't get everything mentioned above, it's okay!\n";
    $text .= "Anything you can do is wonderful and appreciated.\n";
    $text .= "We can add some items to complete the package.\n\n";

    $text .= "IMPORTANT NOTES:\n";
    $text .= "  • No gift cards except those related to video games\n";
    $text .= "  • All gifts must be new\n";
    $text .= "  • Gifts must NOT be wrapped - If desired, you may include gift wrap\n";
    $text .= "  • Place all gifts in a large bag, marked with that child's number\n\n";

    $text .= "DEADLINE & DROP-OFF:\n";
    $text .= "All gifts must be received by Friday, December 5th\n\n";
    $text .= "Drop-off location:\n";
    $text .= "  The Journal\n";
    $text .= "  210 W North 1st Street, Seneca\n";
    $text .= "  Weekdays 8 a.m. - 5 p.m.\n\n";

    $text .= "TAX DEDUCTION:\n";
    $text .= "CFK Inc is a recognized 501(c)(3) not-for-profit organization\n";
    $text .= "EIN: 82-3083435 - This number may be used for tax deduction purposes.\n\n";

    return $text . ("Questions? Contact us at " . config('admin_email') . "\n");
}
