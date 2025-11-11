<?php

declare(strict_types=1);

namespace CFK\Sponsorship;

use CFK\Database\Connection;
use Exception;

/**
 * Sponsorship Manager - Single-Sponsor-Per-Child Logic
 *
 * Handles all sponsorship workflows including reservation, confirmation,
 * and portal access for sponsors.
 *
 * @package CFK\Sponsorship
 */
class Manager
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SPONSORED = 'confirmed';  // Match database enum
    public const STATUS_LOGGED = 'logged';        // Logged in external system
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_INACTIVE = 'inactive';

    public const PENDING_TIMEOUT_HOURS = 2; // Hours before pending expires (shopping cart timeout)

    /**
     * Check if a child is available for sponsorship
     *
     * @param int $childId Child ID to check
     *
     * @return (array|bool|null|string)[] Availability status with details
     *
     * @psalm-return array{available: bool, reason: string, child: array<string, mixed>|null}
     */
    public static function isChildAvailable(int $childId): array
    {
        $child = Connection::fetchRow(
            "SELECT c.id, CONCAT(f.family_number, c.child_letter) as name, c.status,
                    CONCAT(f.family_number, c.child_letter) as display_id
             FROM children c
             JOIN families f ON c.family_id = f.id
             WHERE c.id = ?",
            [$childId]
        );

        if (! $child) {
            return [
                'available' => false,
                'reason' => 'Child not found',
                'child' => null,
            ];
        }

        // Check current status
        if ($child['status'] !== self::STATUS_AVAILABLE) {
            return [
                'available' => false,
                'reason' => self::getStatusMessage((string) $child['status']),
                'child' => $child,
            ];
        }

        return [
            'available' => true,
            'reason' => 'Child is available for sponsorship',
            'child' => $child,
        ];
    }

    /**
     * Reserve a child for sponsorship (sets to pending)
     *
     * @param int $childId Child ID to reserve
     *
     * @return (bool|mixed|null|string)[] Reservation result
     *
     * @psalm-return array{success: bool, message: 'Child reserved successfully'|'System error occurred. Please try again.'|'This child was just selected by another sponsor. Please choose a different child.'|mixed, child: mixed|null}
     */
    public static function reserveChild(int $childId): array
    {
        // Start transaction to prevent race conditions
        Connection::beginTransaction();

        try {
            // Double-check availability within transaction
            $availability = self::isChildAvailable($childId);
            if (! $availability['available']) {
                Connection::rollback();

                return [
                    'success' => false,
                    'message' => $availability['reason'],
                    'child' => $availability['child'],
                ];
            }

            // Reserve the child (set to pending)
            $updated = Connection::update(
                'children',
                ['status' => self::STATUS_PENDING],
                ['id' => $childId, 'status' => self::STATUS_AVAILABLE]
            );

            if ($updated === 0) {
                // Another process got there first
                Connection::rollback();

                return [
                    'success' => false,
                    'message' => 'This child was just selected by another sponsor. Please choose a different child.',
                    'child' => $availability['child'],
                ];
            }

            Connection::commit();

            return [
                'success' => true,
                'message' => 'Child reserved successfully',
                'child' => $availability['child'],
            ];
        } catch (Exception $e) {
            Connection::rollback();
            error_log('Failed to reserve child ' . $childId . ': ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'System error occurred. Please try again.',
                'child' => null,
            ];
        }
    }

    /**
     * Create sponsorship request
     *
     * @param int $childId Child ID to sponsor
     * @param array<string, mixed> $sponsorData Sponsor information
     *
     * @return (bool|int|mixed|string)[] Creation result
     *
     * @psalm-return array{success: bool|mixed, message?: mixed|string, child?: mixed, sponsorship_id?: int|mixed,...}
     */
    public static function createSponsorshipRequest(int $childId, array $sponsorData): array
    {
        // First reserve the child
        $reservation = self::reserveChild($childId);
        if (! $reservation['success']) {
            return $reservation;
        }

        // Validate sponsor data
        $validation = self::validateSponsorData($sponsorData);
        if (! $validation['valid']) {
            // Release the reservation since validation failed
            self::releaseChild($childId);

            return [
                'success' => false,
                'message' => 'Please correct the following errors: ' . implode(', ', $validation['errors']),
                'child' => $reservation['child'],
            ];
        }

        try {
            // Create sponsorship record
            $sponsorshipId = Connection::insert('sponsorships', [
                'child_id' => $childId,
                'sponsor_name' => sanitizeString((string) $sponsorData['name']),
                'sponsor_email' => sanitizeEmail((string) $sponsorData['email']),
                'sponsor_phone' => sanitizeString((string) ($sponsorData['phone'] ?? '')),
                'sponsor_address' => sanitizeString((string) ($sponsorData['address'] ?? '')),
                'gift_preference' => $sponsorData['gift_preference'] ?? 'shopping',
                'special_message' => sanitizeString((string) ($sponsorData['message'] ?? '')),
                'status' => self::STATUS_PENDING,
            ]);

            // Send email notifications if email manager is available
            if (class_exists('CFK_Email_Manager')) {
                // Get full sponsorship data for emails (includes ALL child details for shopping)
                $fullSponsorship = Connection::fetchRow(
                    "SELECT s.*,
                            CONCAT(f.family_number, c.child_letter) as child_name,
                            c.age_months as child_age,
                            c.grade as child_grade,
                            c.gender as child_gender,
                            c.shirt_size,
                            c.pant_size,
                            c.shoe_size,
                            c.jacket_size,
                            c.interests,
                            c.wishes,
                            c.special_needs,
                            CONCAT(f.family_number, c.child_letter) as child_display_id
                     FROM sponsorships s
                     JOIN children c ON s.child_id = c.id
                     JOIN families f ON c.family_id = f.id
                     WHERE s.id = ?",
                    [$sponsorshipId]
                );

                if ($fullSponsorship) {
                    // Send confirmation email to sponsor
                    \CFK_Email_Manager::sendSponsorConfirmation($fullSponsorship);

                    // Send notification to admin
                    \CFK_Email_Manager::sendAdminNotification(
                        'New Sponsorship Request',
                        "A new sponsorship request has been submitted for Child {$fullSponsorship['child_display_id']}.",
                        $fullSponsorship
                    );
                }
            }

            return [
                'success' => true,
                'message' => 'Sponsorship request submitted successfully! You will receive confirmation within 24 hours.',
                'sponsorship_id' => $sponsorshipId,
                'child' => $reservation['child'],
            ];
        } catch (Exception $e) {
            // Release the child reservation on error
            self::releaseChild($childId);
            error_log('Failed to create sponsorship for child ' . $childId . ': ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'System error occurred. Please try again.',
                'child' => $reservation['child'],
            ];
        }
    }

    /**
     * Release child (set back to available) - used for timeouts or errors
     *
     * @param int $childId Child ID to release
     * @return bool True if released successfully
     */
    public static function releaseChild(int $childId): bool
    {
        try {
            $updated = Connection::update(
                'children',
                ['status' => self::STATUS_AVAILABLE],
                ['id' => $childId]
            );

            return $updated > 0;
        } catch (Exception $e) {
            error_log('Failed to release child ' . $childId . ': ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Get all sponsorships for an email address
     *
     * @param string $email Sponsor email address
     * @return array<int, array<string, mixed>> List of sponsorships
     */
    public static function getSponsorshipsByEmail(string $email): array
    {
        return Connection::fetchAll(
            "SELECT * FROM sponsorships
             WHERE sponsor_email = ?
             AND status != 'cancelled'
             ORDER BY request_date DESC",
            [$email]
        );
    }

    /**
     * Get sponsorships with full child and family details
     *
     * @param string $email Sponsor email address
     * @return array<int, array<string, mixed>> List of detailed sponsorships
     */
    public static function getSponsorshipsWithDetails(string $email): array
    {
        return Connection::fetchAll(
            "SELECT s.*,
                    c.id as child_id,
                    CONCAT(f.family_number, c.child_letter) as child_name,
                    c.age_months as child_age,
                    c.grade as child_grade,
                    c.gender as child_gender,
                    c.shirt_size,
                    c.pant_size,
                    c.shoe_size,
                    c.jacket_size,
                    c.interests,
                    c.wishes,
                    c.special_needs,
                    c.photo_filename,
                    c.status as child_status,
                    f.id as family_id,
                    f.family_number,
                    CONCAT(f.family_number, c.child_letter) as child_display_id
             FROM sponsorships s
             JOIN children c ON s.child_id = c.id
             JOIN families f ON c.family_id = f.id
             WHERE s.sponsor_email = ?
             AND s.status != 'cancelled'
             ORDER BY CAST(f.family_number AS UNSIGNED), c.child_letter",
            [$email]
        );
    }

    /**
     * Generate portal access token for sponsor email (DATABASE STORED)
     *
     * @param string $email Sponsor email address
     *
     * @return string Generated token (plain text, only sent once)
     */
    public static function generatePortalToken(string $email): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        try {
            // Store in database for revocation capability
            Connection::insert('portal_access_tokens', [
                'token_hash' => $tokenHash,
                'sponsor_email' => $email,
                'expires_at' => $expiresAt,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Exception $e) {
            error_log('Failed to store portal token: ' . $e->getMessage());
        }

        return $token;  // Return plain token (only sent via email once)
    }

    /**
     * Validate sponsor data using centralized validator
     *
     * @param array<string, mixed> $data Sponsor data to validate
     *
     * @return array{valid: bool, errors: array<int, string>} Validation result
     */
    private static function validateSponsorData(array $data): array
    {
        // Load validator if not already included
        if (! class_exists('Validator')) {
            require_once __DIR__ . '/../../includes/validator.php';
        }

        $validator = validate($data, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'max:20',
            'address' => 'max:500',
            'gift_preference' => 'in:shopping,gift_card,cash_donation',
            'message' => 'max:1000',
        ]);

        return [
            'valid' => $validator->passes(),
            'errors' => $validator->allErrors(),
        ];
    }

    /**
     * Get user-friendly status message
     *
     * @param string $status Child status
     *
     * @return string Human-readable message
     */
    private static function getStatusMessage(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'This child is currently being processed by another sponsor',
            self::STATUS_SPONSORED => 'This child has already been sponsored',
            self::STATUS_COMPLETED => 'This child has already received their Christmas gifts',
            self::STATUS_INACTIVE => 'This child is not currently available for sponsorship',
            default => 'This child is not available for sponsorship',
        };
    }

    /**
     * Get portal access email template
     *
     * @param string $sponsorName Sponsor name
     * @param string $portalUrl Portal access URL with token
     *
     * @return string HTML email template
     */
    private static function getPortalAccessEmailTemplate(string $sponsorName, string $portalUrl): string
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #2c5530; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; max-width: 600px; margin: 0 auto; }
                .button {
                    display: inline-block;
                    background: #c41e3a;
                    color: white;
                    padding: 15px 30px;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 20px 0;
                    font-weight: bold;
                }
                .footer { background: #f4f4f4; padding: 15px; text-align: center; font-size: 12px; }
                .security-note { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <img src='" . \baseUrl('assets/images/cfk-horizontal.png') . "' alt='Christmas for Kids' style='max-width: 400px; height: auto; margin: 0 auto 15px; display: block;'>
                <h2>Sponsorship Portal Access</h2>
            </div>

            <div class='content'>
                <p>Dear {$sponsorName},</p>

                <p>You requested access to view your Christmas for Kids sponsorships. Click the button below to access your portal:</p>

                <div style='text-align: center;'>
                    <a href='{$portalUrl}' class='button'>Access Your Portal</a>
                </div>

                <div class='security-note'>
                    <p><strong>🔒 Security Notice:</strong></p>
                    <ul>
                        <li>This link will expire in <strong>30 minutes</strong></li>
                        <li>Do not share this link with anyone</li>
                        <li>If you didn't request this, please ignore this email</li>
                    </ul>
                </div>

                <p><strong>In your portal you can:</strong></p>
                <ul>
                    <li>View all your sponsored children with complete details</li>
                    <li>See which children are in the same family</li>
                    <li>Add more children to your sponsorship</li>
                    <li>Download shopping lists for gift buying</li>
                </ul>

                <p>If the button doesn't work, copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #666; font-size: 12px;'>{$portalUrl}</p>

                <p style='margin-top: 20px;'><strong>Questions?</strong> Contact us at " . \config('admin_email') . "</p>

                <p>Thank you for making Christmas special for children in need!</p>

                <p>With gratitude,<br>
                <strong>The Christmas for Kids Team</strong></p>
            </div>

            <div class='footer'>
                <p><strong>Christmas for Kids</strong> | Making Christmas Magical for Children in Need</p>
                <p>📧 " . \config('admin_email') . "</p>
            </div>
        </body>
        </html>";
    }

    // ============================================================
    // ADMIN MANAGEMENT METHODS
    // ============================================================

    /**
     * Mark sponsorship as logged in external system
     *
     * @param int $sponsorshipId Sponsorship ID
     * @return array{success: bool, message: string} Operation result
     */
    public static function logSponsorship(int $sponsorshipId): array
    {
        try {
            $sponsorship = Connection::fetchRow(
                "SELECT s.id, s.child_id, s.status
                 FROM sponsorships s
                 WHERE s.id = ?",
                [$sponsorshipId]
            );

            if (!$sponsorship) {
                return ['success' => false, 'message' => 'Sponsorship not found'];
            }

            if ($sponsorship['status'] !== 'confirmed') {
                return ['success' => false, 'message' => 'Only confirmed sponsorships can be logged'];
            }

            // Update sponsorship status to logged
            Connection::update('sponsorships', [
                'status' => self::STATUS_LOGGED,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $sponsorshipId]);

            return ['success' => true, 'message' => 'Sponsorship marked as logged'];
        } catch (Exception $e) {
            error_log('Failed to log sponsorship ' . $sponsorshipId . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'System error occurred'];
        }
    }

    /**
     * Mark sponsorship as completed
     *
     * @param int $sponsorshipId Sponsorship ID
     * @return array{success: bool, message: string} Operation result
     */
    public static function completeSponsorship(int $sponsorshipId): array
    {
        try {
            $sponsorship = Connection::fetchRow(
                "SELECT s.id, s.child_id, s.status
                 FROM sponsorships s
                 WHERE s.id = ?",
                [$sponsorshipId]
            );

            if (!$sponsorship) {
                return ['success' => false, 'message' => 'Sponsorship not found'];
            }

            // Update sponsorship status to completed
            Connection::update('sponsorships', [
                'status' => self::STATUS_COMPLETED,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $sponsorshipId]);

            // Update child status to completed
            Connection::update('children', [
                'status' => self::STATUS_COMPLETED
            ], ['id' => $sponsorship['child_id']]);

            return ['success' => true, 'message' => 'Sponsorship marked as completed'];
        } catch (Exception $e) {
            error_log('Failed to complete sponsorship ' . $sponsorshipId . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'System error occurred'];
        }
    }

    /**
     * Reverse logged status back to confirmed
     *
     * @param int $sponsorshipId Sponsorship ID
     * @return array{success: bool, message: string} Operation result
     */
    public static function unlogSponsorship(int $sponsorshipId): array
    {
        try {
            $sponsorship = Connection::fetchRow(
                "SELECT s.id, s.status
                 FROM sponsorships s
                 WHERE s.id = ?",
                [$sponsorshipId]
            );

            if (!$sponsorship) {
                return ['success' => false, 'message' => 'Sponsorship not found'];
            }

            if ($sponsorship['status'] !== self::STATUS_LOGGED) {
                return ['success' => false, 'message' => 'Only logged sponsorships can be unlogged'];
            }

            // Revert status to confirmed
            Connection::update('sponsorships', [
                'status' => self::STATUS_SPONSORED,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $sponsorshipId]);

            return ['success' => true, 'message' => 'Sponsorship status reverted to confirmed'];
        } catch (Exception $e) {
            error_log('Failed to unlog sponsorship ' . $sponsorshipId . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'System error occurred'];
        }
    }

    /**
     * Cancel a sponsorship with reason
     *
     * @param int $sponsorshipId Sponsorship ID
     * @param string $reason Cancellation reason
     * @return array{success: bool, message: string} Operation result
     */
    public static function cancelSponsorship(int $sponsorshipId, string $reason = ''): array
    {
        try {
            $sponsorship = Connection::fetchRow(
                "SELECT s.id, s.child_id, s.status
                 FROM sponsorships s
                 WHERE s.id = ?",
                [$sponsorshipId]
            );

            if (!$sponsorship) {
                return ['success' => false, 'message' => 'Sponsorship not found'];
            }

            // Start transaction to update both sponsorship and child
            Connection::beginTransaction();

            // Update sponsorship to cancelled
            Connection::update('sponsorships', [
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $sponsorshipId]);

            // Release child back to available
            Connection::update('children', [
                'status' => self::STATUS_AVAILABLE
            ], ['id' => $sponsorship['child_id']]);

            Connection::commit();

            return ['success' => true, 'message' => 'Sponsorship cancelled and child released'];
        } catch (Exception $e) {
            Connection::rollback();
            error_log('Failed to cancel sponsorship ' . $sponsorshipId . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'System error occurred'];
        }
    }

    /**
     * Get sponsorship statistics for admin dashboard
     *
     * @return array{total: int, pending: int, confirmed: int, logged: int, completed: int} Statistics
     */
    public static function getStats(): array
    {
        try {
            $stats = Connection::fetchRow("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'logged' THEN 1 ELSE 0 END) as logged,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM sponsorships
                WHERE status != 'cancelled'
            ");

            return [
                'total' => (int) ($stats['total'] ?? 0),
                'pending' => (int) ($stats['pending'] ?? 0),
                'confirmed' => (int) ($stats['confirmed'] ?? 0),
                'logged' => (int) ($stats['logged'] ?? 0),
                'completed' => (int) ($stats['completed'] ?? 0)
            ];
        } catch (Exception $e) {
            error_log('Failed to get sponsorship stats: ' . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'logged' => 0, 'completed' => 0];
        }
    }

    /**
     * Get children that need admin attention (pending too long, etc.)
     *
     * @return array<int, array<string, mixed>> List of children needing attention
     */
    public static function getChildrenNeedingAttention(): array
    {
        try {
            // Get pending sponsorships older than 24 hours
            $oldPending = Connection::fetchAll("
                SELECT s.*,
                       CONCAT(f.family_number, c.child_letter) as child_display_id,
                       c.age_months as child_age,
                       c.gender as child_gender,
                       TIMESTAMPDIFF(HOUR, s.request_date, NOW()) as hours_pending
                FROM sponsorships s
                JOIN children c ON s.child_id = c.id
                JOIN families f ON c.family_id = f.id
                WHERE s.status = 'pending'
                AND s.request_date < DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY s.request_date ASC
            ");

            return $oldPending;
        } catch (Exception $e) {
            error_log('Failed to get children needing attention: ' . $e->getMessage());
            return [];
        }
    }
}
