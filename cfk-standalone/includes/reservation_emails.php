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

/**
 * Send reservation confirmation email to sponsor
 *
 * @param array $reservation Reservation data from database
 * @return array ['success' => bool, 'message' => string]
 */
function sendReservationConfirmationEmail(array $reservation): array {
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
 * @return bool
 */
function sendAdminReservationNotification(array $reservation): bool {
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
function generateReservationConfirmationHTML(array $reservation): string {
    $children = $reservation['children'] ?? [];
    $expiresAt = new DateTime($reservation['expires_at']);
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

                    <!-- Reservation Token -->
                    <tr>
                        <td style="padding: 30px; background-color: #fffbea; border-bottom: 3px solid #f5b800;">
                            <h2 style="color: #856404; margin: 0 0 15px 0; font-size: 18px;">📋 Your Reservation Token</h2>
                            <div style="background: #ffffff; padding: 15px; border-radius: 6px; border: 2px solid #f5b800;">
                                <code style="font-family: \'Courier New\', monospace; font-size: 16px; font-weight: bold; color: #2c5530; word-break: break-all;">' . htmlspecialchars($reservation['reservation_token']) . '</code>
                            </div>
                            <p style="color: #856404; margin: 15px 0 0 0; font-size: 14px;">
                                <strong>Save this token!</strong> You\'ll need it to track your reservation or make changes.
                            </p>
                        </td>
                    </tr>

                    <!-- Important Dates -->
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="color: #2c5530; margin: 0 0 20px 0; font-size: 20px;">⏰ Important Dates</h2>
                            <table width="100%" cellpadding="10" style="background-color: #f8f9fa; border-radius: 6px;">
                                <tr>
                                    <td style="color: #2c5530; font-weight: bold; padding: 10px 15px;">Created:</td>
                                    <td style="color: #666; padding: 10px 15px;">' . $createdAt->format('F j, Y g:i A') . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #2c5530; font-weight: bold; padding: 10px 15px; border-top: 1px solid #dee2e6;">Expires:</td>
                                    <td style="color: #c41e3a; font-weight: bold; padding: 10px 15px; border-top: 1px solid #dee2e6;">' . $expiresAt->format('F j, Y g:i A') . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #2c5530; font-weight: bold; padding: 10px 15px; border-top: 1px solid #dee2e6;">Valid For:</td>
                                    <td style="color: #666; padding: 10px 15px; border-top: 1px solid #dee2e6;">48 hours</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Sponsored Children -->
                    <tr>
                        <td style="padding: 30px; background-color: #f8fdf9;">
                            <h2 style="color: #2c5530; margin: 0 0 20px 0; font-size: 20px;">🎄 Your Sponsored Children (' . count($children) . ')</h2>';

    foreach ($children as $child) {
        $html .= '
                            <div style="background: #ffffff; border: 2px solid #2c5530; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                                <h3 style="color: #2c5530; margin: 0 0 15px 0; font-size: 18px; border-bottom: 2px solid #2c5530; padding-bottom: 10px;">' . htmlspecialchars($child['display_id']) . '</h3>

                                <table width="100%" cellpadding="5">
                                    <tr>
                                        <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Age:</td>
                                        <td style="color: #666; padding: 5px 0;">' . htmlspecialchars($child['age']) . ' years old</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Gender:</td>
                                        <td style="color: #666; padding: 5px 0;">' . ($child['gender'] === 'M' ? 'Boy' : 'Girl') . '</td>
                                    </tr>';

        if (!empty($child['grade'])) {
            $html .= '
                                    <tr>
                                        <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">Grade:</td>
                                        <td style="color: #666; padding: 5px 0;">' . htmlspecialchars($child['grade']) . '</td>
                                    </tr>';
        }

        if (!empty($child['school'])) {
            $html .= '
                                    <tr>
                                        <td style="color: #2c5530; font-weight: bold; padding: 5px 0;">School:</td>
                                        <td style="color: #666; padding: 5px 0;">' . htmlspecialchars($child['school']) . '</td>
                                    </tr>';
        }

        $html .= '
                                </table>';

        if (!empty($child['interests'])) {
            $html .= '
                                <div style="margin-top: 15px;">
                                    <strong style="color: #2c5530;">Interests:</strong>
                                    <p style="margin: 5px 0; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #3a6f3f; border-radius: 4px; color: #666;">' . nl2br(htmlspecialchars($child['interests'])) . '</p>
                                </div>';
        }

        if (!empty($child['wishes'])) {
            $html .= '
                                <div style="margin-top: 15px;">
                                    <strong style="color: #c41e3a;">🎁 Christmas Wishes:</strong>
                                    <p style="margin: 5px 0; padding: 10px; background-color: #fef5f5; border-left: 3px solid #c41e3a; border-radius: 4px; color: #666;">' . nl2br(htmlspecialchars($child['wishes'])) . '</p>
                                </div>';
        }

        if (!empty($child['clothing_sizes'])) {
            $html .= '
                                <div style="margin-top: 15px;">
                                    <strong style="color: #2c5530;">👕 Clothing Sizes:</strong>
                                    <p style="margin: 5px 0; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #3a6f3f; border-radius: 4px; color: #666;">' . htmlspecialchars($child['clothing_sizes']) . '</p>
                                </div>';
        }

        if (!empty($child['shoe_size'])) {
            $html .= '
                                <div style="margin-top: 15px;">
                                    <strong style="color: #2c5530;">👟 Shoe Size:</strong>
                                    <p style="margin: 5px 0; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #3a6f3f; border-radius: 4px; color: #666;">' . htmlspecialchars($child['shoe_size']) . '</p>
                                </div>';
        }

        $html .= '
                            </div>';
    }

    $html .= '
                        </td>
                    </tr>

                    <!-- Next Steps -->
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="color: #2c5530; margin: 0 0 20px 0; font-size: 20px;">📋 What Happens Next?</h2>
                            <ol style="color: #666; line-height: 1.8; padding-left: 20px;">
                                <li style="margin-bottom: 15px;"><strong style="color: #2c5530;">Shop for Gifts</strong><br/>You have 48 hours to purchase gifts based on each child\'s wishes and sizes listed above.</li>
                                <li style="margin-bottom: 15px;"><strong style="color: #2c5530;">Wrap the Gifts</strong><br/>Please wrap each child\'s gifts separately and clearly label with their ID.</li>
                                <li style="margin-bottom: 15px;"><strong style="color: #2c5530;">Deliver or Ship</strong><br/>Contact us at <a href="mailto:' . config('admin_email') . '" style="color: #2c5530;">' . config('admin_email') . '</a> for delivery instructions.</li>
                                <li style="margin-bottom: 15px;"><strong style="color: #2c5530;">Make a Difference!</strong><br/>Your generosity will bring joy to ' . count($children) . ' ' . (count($children) === 1 ? 'child' : 'children') . ' this Christmas!</li>
                            </ol>
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
</html>';

    return $html;
}

/**
 * Generate plain text version of confirmation email
 */
function generateReservationConfirmationText(array $reservation): string {
    $children = $reservation['children'] ?? [];
    $text = "RESERVATION CONFIRMED!\n\n";
    $text .= "Thank you for sponsoring " . count($children) . " " . (count($children) === 1 ? 'child' : 'children') . " this Christmas!\n\n";
    $text .= "YOUR RESERVATION TOKEN\n";
    $text .= "Token: " . $reservation['reservation_token'] . "\n";
    $text .= "Save this token! You'll need it to track your reservation.\n\n";
    $text .= "IMPORTANT DATES\n";
    $text .= "Created: " . date('F j, Y g:i A', strtotime($reservation['created_at'])) . "\n";
    $text .= "Expires: " . date('F j, Y g:i A', strtotime($reservation['expires_at'])) . "\n";
    $text .= "Valid For: 48 hours\n\n";
    $text .= "YOUR SPONSORED CHILDREN (" . count($children) . ")\n";
    $text .= str_repeat('-', 50) . "\n\n";

    foreach ($children as $child) {
        $text .= $child['display_id'] . "\n";
        $text .= "Age: " . $child['age'] . " years old\n";
        $text .= "Gender: " . ($child['gender'] === 'M' ? 'Boy' : 'Girl') . "\n";
        if (!empty($child['grade'])) $text .= "Grade: " . $child['grade'] . "\n";
        if (!empty($child['school'])) $text .= "School: " . $child['school'] . "\n";
        if (!empty($child['interests'])) $text .= "Interests: " . $child['interests'] . "\n";
        if (!empty($child['wishes'])) $text .= "Wishes: " . $child['wishes'] . "\n";
        if (!empty($child['clothing_sizes'])) $text .= "Clothing: " . $child['clothing_sizes'] . "\n";
        if (!empty($child['shoe_size'])) $text .= "Shoes: " . $child['shoe_size'] . "\n";
        $text .= "\n";
    }

    $text .= "WHAT HAPPENS NEXT?\n";
    $text .= "1. Shop for gifts based on each child's wishes and sizes\n";
    $text .= "2. Wrap gifts separately and label with child ID\n";
    $text .= "3. Contact us for delivery instructions: " . config('admin_email') . "\n";
    $text .= "4. Make a difference in " . count($children) . " " . (count($children) === 1 ? "child's" : "children's") . " Christmas!\n\n";
    $text .= "Questions? Contact us at " . config('admin_email') . "\n";

    return $text;
}

/**
 * Generate admin notification HTML
 */
function generateAdminNotificationHTML(array $reservation): string {
    return '<html><body><h2>New Reservation</h2><p>Sponsor: ' . htmlspecialchars($reservation['sponsor_name']) . ' (' . htmlspecialchars($reservation['sponsor_email']) . ')</p><p>Children: ' . $reservation['total_children'] . '</p><p>Token: ' . htmlspecialchars($reservation['reservation_token']) . '</p></body></html>';
}

/**
 * Generate admin notification text
 */
function generateAdminNotificationText(array $reservation): string {
    return "New Reservation\nSponsor: " . $reservation['sponsor_name'] . " (" . $reservation['sponsor_email'] . ")\nChildren: " . $reservation['total_children'] . "\nToken: " . $reservation['reservation_token'];
}

/**
 * Log email to database
 */
function logReservationEmail(int $reservationId, string $email, string $type, string $status, string $error = ''): void {
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
