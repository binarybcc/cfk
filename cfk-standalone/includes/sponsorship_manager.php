<?php
declare(strict_types=1);

/**
 * Sponsorship Manager - Single-Sponsor-Per-Child Logic
 * Prevents multiple sponsors from selecting the same child
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

class CFK_Sponsorship_Manager {
    
    const STATUS_AVAILABLE = 'available';
    const STATUS_PENDING = 'pending';
    const STATUS_SPONSORED = 'confirmed';  // Match database enum
    const STATUS_COMPLETED = 'completed';
    const STATUS_INACTIVE = 'inactive';
    
    const PENDING_TIMEOUT_HOURS = 48; // Hours before pending expires
    
    /**
     * Check if a child is available for sponsorship
     */
    public static function isChildAvailable(int $childId): array {
        $child = Database::fetchRow(
            "SELECT c.id, c.name, c.status, 
                    CONCAT(f.family_number, c.child_letter) as display_id
             FROM children c
             JOIN families f ON c.family_id = f.id  
             WHERE c.id = ?",
            [$childId]
        );
        
        if (!$child) {
            return [
                'available' => false,
                'reason' => 'Child not found',
                'child' => null
            ];
        }
        
        // Check current status
        if ($child['status'] !== self::STATUS_AVAILABLE) {
            return [
                'available' => false,
                'reason' => self::getStatusMessage($child['status']),
                'child' => $child
            ];
        }
        
        return [
            'available' => true,
            'reason' => 'Child is available for sponsorship',
            'child' => $child
        ];
    }
    
    /**
     * Reserve a child for sponsorship (sets to pending)
     */
    public static function reserveChild(int $childId): array {
        // Start transaction to prevent race conditions
        Database::getConnection()->beginTransaction();
        
        try {
            // Double-check availability within transaction
            $availability = self::isChildAvailable($childId);
            if (!$availability['available']) {
                Database::getConnection()->rollback();
                return [
                    'success' => false,
                    'message' => $availability['reason'],
                    'child' => $availability['child']
                ];
            }
            
            // Reserve the child (set to pending)
            $updated = Database::update('children', 
                ['status' => self::STATUS_PENDING], 
                ['id' => $childId, 'status' => self::STATUS_AVAILABLE]
            );
            
            if ($updated === 0) {
                // Another process got there first
                Database::getConnection()->rollback();
                return [
                    'success' => false,
                    'message' => 'This child was just selected by another sponsor. Please choose a different child.',
                    'child' => $availability['child']
                ];
            }
            
            Database::getConnection()->commit();
            
            return [
                'success' => true,
                'message' => 'Child reserved successfully',
                'child' => $availability['child']
            ];
            
        } catch (Exception $e) {
            Database::getConnection()->rollback();
            error_log('Failed to reserve child ' . $childId . ': ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'System error occurred. Please try again.',
                'child' => null
            ];
        }
    }
    
    /**
     * Create sponsorship request
     */
    public static function createSponsorshipRequest(int $childId, array $sponsorData): array {
        // First reserve the child
        $reservation = self::reserveChild($childId);
        if (!$reservation['success']) {
            return $reservation;
        }
        
        // Validate sponsor data
        $validation = self::validateSponsorData($sponsorData);
        if (!$validation['valid']) {
            // Release the reservation since validation failed
            self::releaseChild($childId);
            return [
                'success' => false,
                'message' => 'Please correct the following errors: ' . implode(', ', $validation['errors']),
                'child' => $reservation['child']
            ];
        }
        
        try {
            // Create sponsorship record
            $sponsorshipId = Database::insert('sponsorships', [
                'child_id' => $childId,
                'sponsor_name' => sanitizeString($sponsorData['name']),
                'sponsor_email' => sanitizeEmail($sponsorData['email']),
                'sponsor_phone' => sanitizeString($sponsorData['phone'] ?? ''),
                'sponsor_address' => sanitizeString($sponsorData['address'] ?? ''),
                'gift_preference' => $sponsorData['gift_preference'] ?? 'shopping',
                'special_message' => sanitizeString($sponsorData['message'] ?? ''),
                'status' => self::STATUS_PENDING
            ]);
            
            // Send email notifications if email manager is available
            if (class_exists('CFK_Email_Manager')) {
                // Get full sponsorship data for emails (includes ALL child details for shopping)
                $fullSponsorship = Database::fetchRow(
                    "SELECT s.*,
                            c.name as child_name,
                            c.age as child_age,
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
                    CFK_Email_Manager::sendSponsorConfirmation($fullSponsorship);
                    
                    // Send notification to admin
                    CFK_Email_Manager::sendAdminNotification(
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
                'child' => $reservation['child']
            ];
            
        } catch (Exception $e) {
            // Release the child reservation on error
            self::releaseChild($childId);
            error_log('Failed to create sponsorship for child ' . $childId . ': ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'System error occurred. Please try again.',
                'child' => $reservation['child']
            ];
        }
    }
    
    /**
     * Release child (set back to available) - used for timeouts or errors
     */
    public static function releaseChild(int $childId): bool {
        try {
            $updated = Database::update('children', 
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
     * Confirm sponsorship (admin action)
     */
    public static function confirmSponsorship(int $sponsorshipId): array {
        try {
            Database::getConnection()->beginTransaction();
            
            // Get sponsorship details
            $sponsorship = Database::fetchRow(
                "SELECT s.*, c.id as child_id, c.status 
                 FROM sponsorships s 
                 JOIN children c ON s.child_id = c.id 
                 WHERE s.id = ?",
                [$sponsorshipId]
            );
            
            if (!$sponsorship) {
                Database::getConnection()->rollback();
                return ['success' => false, 'message' => 'Sponsorship not found'];
            }
            
            // Update sponsorship status
            Database::update('sponsorships', 
                ['status' => 'confirmed', 'confirmation_date' => date('Y-m-d H:i:s')], 
                ['id' => $sponsorshipId]
            );
            
            // Update child status
            Database::update('children', 
                ['status' => 'sponsored'], 
                ['id' => $sponsorship['child_id']]
            );
            
            Database::getConnection()->commit();
            
            return [
                'success' => true,
                'message' => 'Sponsorship confirmed successfully',
                'sponsorship' => $sponsorship
            ];
            
        } catch (Exception $e) {
            Database::getConnection()->rollback();
            error_log('Failed to confirm sponsorship ' . $sponsorshipId . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'System error occurred'];
        }
    }
    
    /**
     * Complete sponsorship (gifts delivered)
     */
    public static function completeSponsorship(int $sponsorshipId): array {
        try {
            Database::getConnection()->beginTransaction();
            
            // Update sponsorship
            Database::update('sponsorships', 
                ['status' => 'completed', 'completion_date' => date('Y-m-d H:i:s')], 
                ['id' => $sponsorshipId]
            );
            
            // Update child - children table doesn't have 'completed' status, use 'sponsored'
            $sponsorship = Database::fetchRow("SELECT child_id FROM sponsorships WHERE id = ?", [$sponsorshipId]);
            if ($sponsorship) {
                Database::update('children', 
                    ['status' => 'sponsored'], 
                    ['id' => $sponsorship['child_id']]
                );
            }
            
            Database::getConnection()->commit();
            return ['success' => true, 'message' => 'Sponsorship marked as completed'];
            
        } catch (Exception $e) {
            Database::getConnection()->rollback();
            error_log('Failed to complete sponsorship ' . $sponsorshipId . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'System error occurred'];
        }
    }
    
    /**
     * Cancel sponsorship
     */
    public static function cancelSponsorship(int $sponsorshipId, string $reason = ''): array {
        try {
            Database::getConnection()->beginTransaction();
            
            $sponsorship = Database::fetchRow("SELECT child_id FROM sponsorships WHERE id = ?", [$sponsorshipId]);
            
            if ($sponsorship) {
                // Cancel sponsorship
                Database::update('sponsorships', 
                    ['status' => 'cancelled', 'notes' => $reason], 
                    ['id' => $sponsorshipId]
                );
                
                // Release child back to available
                Database::update('children', 
                    ['status' => self::STATUS_AVAILABLE], 
                    ['id' => $sponsorship['child_id']]
                );
            }
            
            Database::getConnection()->commit();
            return ['success' => true, 'message' => 'Sponsorship cancelled successfully'];
            
        } catch (Exception $e) {
            Database::getConnection()->rollback();
            error_log('Failed to cancel sponsorship ' . $sponsorshipId . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'System error occurred'];
        }
    }
    
    /**
     * Clean up expired pending sponsorships
     */
    public static function cleanupExpiredPendingSponsorships(): int {
        $cutoffTime = date('Y-m-d H:i:s', strtotime('-' . self::PENDING_TIMEOUT_HOURS . ' hours'));
        
        try {
            // Get expired sponsorships
            $expiredSponsorships = Database::fetchAll(
                "SELECT s.id, s.child_id 
                 FROM sponsorships s 
                 WHERE s.status = 'pending' AND s.request_date < ?",
                [$cutoffTime]
            );
            
            $cleaned = 0;
            
            foreach ($expiredSponsorships as $sponsorship) {
                // Cancel the sponsorship
                Database::update('sponsorships', 
                    ['status' => 'cancelled', 'notes' => 'Automatically cancelled due to timeout'], 
                    ['id' => $sponsorship['id']]
                );
                
                // Release the child
                Database::update('children', 
                    ['status' => self::STATUS_AVAILABLE], 
                    ['id' => $sponsorship['child_id']]
                );
                
                $cleaned++;
            }
            
            if ($cleaned > 0) {
                error_log("CFK: Cleaned up $cleaned expired pending sponsorships");
            }
            
            return $cleaned;
            
        } catch (Exception $e) {
            error_log('Failed to cleanup expired sponsorships: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get sponsorship statistics
     */
    public static function getStats(): array {
        $stats = [];
        
        // Children by status
        $statusCounts = Database::fetchAll(
            "SELECT status, COUNT(*) as count FROM children GROUP BY status"
        );
        
        foreach ($statusCounts as $row) {
            $stats['children'][$row['status']] = (int)$row['count'];
        }
        
        // Sponsorship by status
        $sponsorshipCounts = Database::fetchAll(
            "SELECT status, COUNT(*) as count FROM sponsorships GROUP BY status"
        );
        
        foreach ($sponsorshipCounts as $row) {
            $stats['sponsorships'][$row['status']] = (int)$row['count'];
        }
        
        return $stats;
    }
    
    /**
     * Validate sponsor data using centralized validator
     */
    private static function validateSponsorData(array $data): array {
        // Load validator if not already included
        if (!class_exists('Validator')) {
            require_once __DIR__ . '/validator.php';
        }

        $validator = validate($data, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'max:20',
            'address' => 'max:500',
            'gift_preference' => 'in:shopping,gift_card,cash_donation',
            'message' => 'max:1000'
        ]);

        return [
            'valid' => $validator->passes(),
            'errors' => $validator->allErrors()
        ];
    }
    
    /**
     * Get user-friendly status message
     */
    private static function getStatusMessage(string $status): string {
        switch ($status) {
            case self::STATUS_PENDING:
                return 'This child is currently being processed by another sponsor';
            case self::STATUS_SPONSORED:
                return 'This child has already been sponsored';
            case self::STATUS_COMPLETED:
                return 'This child has already received their Christmas gifts';
            case self::STATUS_INACTIVE:
                return 'This child is not currently available for sponsorship';
            default:
                return 'This child is not available for sponsorship';
        }
    }
    
    /**
     * Get children needing admin attention (stuck in pending, etc.)
     */
    public static function getChildrenNeedingAttention(): array {
        $cutoffTime = date('Y-m-d H:i:s', strtotime('-' . (self::PENDING_TIMEOUT_HOURS - 6) . ' hours'));

        return Database::fetchAll("
            SELECT c.*, f.family_number,
                   CONCAT(f.family_number, c.child_letter) as display_id,
                   s.request_date, s.sponsor_name, s.sponsor_email
            FROM children c
            JOIN families f ON c.family_id = f.id
            LEFT JOIN sponsorships s ON c.id = s.child_id AND s.status = 'pending'
            WHERE c.status = 'pending' AND s.request_date < ?
            ORDER BY s.request_date ASC
        ", [$cutoffTime]);
    }

    /**
     * Get all sponsorships for an email address
     */
    public static function getSponsorshipsByEmail(string $email): array {
        return Database::fetchAll(
            "SELECT * FROM sponsorships
             WHERE sponsor_email = ?
             AND status != 'cancelled'
             ORDER BY request_date DESC",
            [$email]
        );
    }

    /**
     * Get sponsorships with full child and family details
     */
    public static function getSponsorshipsWithDetails(string $email): array {
        return Database::fetchAll(
            "SELECT s.*,
                    c.id as child_id,
                    c.name as child_name,
                    c.age as child_age,
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
                    f.family_name,
                    CONCAT(f.family_number, c.child_letter) as child_display_id
             FROM sponsorships s
             JOIN children c ON s.child_id = c.id
             JOIN families f ON c.family_id = f.id
             WHERE s.sponsor_email = ?
             AND s.status != 'cancelled'
             ORDER BY f.family_number, c.child_letter",
            [$email]
        );
    }

    /**
     * Generate portal access token for sponsor email (DATABASE STORED)
     */
    public static function generatePortalToken(string $email): string {
        $token = bin2hex(random_bytes(32));
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        try {
            // Store in database for revocation capability
            Database::insert('portal_access_tokens', [
                'token_hash' => $tokenHash,
                'sponsor_email' => $email,
                'expires_at' => $expiresAt,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log('Failed to store portal token: ' . $e->getMessage());
        }

        return $token;  // Return plain token (only sent via email once)
    }

    /**
     * Verify portal access token (DATABASE VERIFIED)
     */
    public static function verifyPortalToken(string $token): array {
        if (empty($token)) {
            return [
                'valid' => false,
                'message' => 'Access token is required.',
                'email' => null
            ];
        }

        try {
            // Get recent tokens (within last 24 hours, not expired, not used, not revoked)
            $recentTokens = Database::fetchAll(
                "SELECT * FROM portal_access_tokens
                 WHERE expires_at > NOW()
                 AND used_at IS NULL
                 AND revoked_at IS NULL
                 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 ORDER BY created_at DESC"
            );

            // Check token against each hash (constant-time comparison via password_verify)
            foreach ($recentTokens as $tokenRecord) {
                if (password_verify($token, $tokenRecord['token_hash'])) {
                    // Check expiration
                    if (strtotime($tokenRecord['expires_at']) < time()) {
                        return [
                            'valid' => false,
                            'message' => 'Access token has expired. Please request a new access link.',
                            'email' => null
                        ];
                    }

                    // Mark as used
                    Database::update('portal_access_tokens',
                        ['used_at' => date('Y-m-d H:i:s')],
                        ['id' => $tokenRecord['id']]
                    );

                    return [
                        'valid' => true,
                        'message' => 'Token valid',
                        'email' => $tokenRecord['sponsor_email']
                    ];
                }
            }

            return [
                'valid' => false,
                'message' => 'Invalid or expired access token.',
                'email' => null
            ];
        } catch (Exception $e) {
            error_log('Failed to verify portal token: ' . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'System error occurred.',
                'email' => null
            ];
        }
    }

    /**
     * Revoke all portal access tokens for an email address
     */
    public static function revokePortalTokens(string $email): bool {
        try {
            $updated = Database::execute(
                "UPDATE portal_access_tokens SET revoked_at = NOW()
                 WHERE sponsor_email = ? AND revoked_at IS NULL",
                [$email]
            );

            if ($updated > 0) {
                error_log("CFK: Revoked $updated portal tokens for $email");
            }

            return true;
        } catch (Exception $e) {
            error_log('Failed to revoke portal tokens: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send portal access email to sponsor
     */
    public static function sendPortalAccessEmail(string $email): array {
        try {
            // Generate token
            $token = self::generatePortalToken($email);

            // Build access URL
            $portalUrl = baseUrl('?page=sponsor_portal&token=' . urlencode($token));

            // Get sponsor name from first sponsorship
            $sponsorships = self::getSponsorshipsByEmail($email);
            $sponsorName = $sponsorships[0]['sponsor_name'] ?? 'Valued Sponsor';

            // Send email
            if (class_exists('CFK_Email_Manager')) {
                $mailer = CFK_Email_Manager::getMailer();
                $mailer->addAddress($email, $sponsorName);
                $mailer->Subject = 'Christmas for Kids - Portal Access Link';
                $mailer->Body = self::getPortalAccessEmailTemplate($sponsorName, $portalUrl);

                $success = $mailer->send();

                if ($success) {
                    return [
                        'success' => true,
                        'message' => 'Access link sent successfully'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Failed to send email. Please try again.'
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Email service unavailable. Please contact support.'
            ];

        } catch (Exception $e) {
            error_log('Failed to send portal access email: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'System error occurred. Please try again.'
            ];
        }
    }

    /**
     * Get portal access email template
     */
    private static function getPortalAccessEmailTemplate(string $sponsorName, string $portalUrl): string {
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
                <img src='" . baseUrl('assets/images/cfk-horizontal.png') . "' alt='Christmas for Kids' style='max-width: 400px; height: auto; margin: 0 auto 15px; display: block;'>
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

                <p style='margin-top: 20px;'><strong>Questions?</strong> Contact us at " . config('admin_email') . "</p>

                <p>Thank you for making Christmas special for children in need!</p>

                <p>With gratitude,<br>
                <strong>The Christmas for Kids Team</strong></p>
            </div>

            <div class='footer'>
                <p><strong>Christmas for Kids</strong> | Making Christmas Magical for Children in Need</p>
                <p>📧 " . config('admin_email') . "</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Add children to existing sponsorship
     */
    public static function addChildrenToSponsorship(array $childIds, array $sponsorData, string $sponsorEmail): array {
        if (empty($childIds)) {
            return [
                'success' => false,
                'message' => 'No children selected'
            ];
        }

        try {
            $addedChildren = [];
            $errors = [];

            foreach ($childIds as $childId) {
                $result = self::createSponsorshipRequest((int)$childId, $sponsorData);

                if ($result['success']) {
                    $addedChildren[] = $result['child']['display_id'];
                } else {
                    $errors[] = $result['message'];
                }
            }

            if (!empty($addedChildren)) {
                // Send updated email with ALL children
                if (class_exists('CFK_Email_Manager')) {
                    $allSponsorships = self::getSponsorshipsWithDetails($sponsorEmail);
                    CFK_Email_Manager::sendMultiChildSponsorshipEmail($sponsorEmail, $allSponsorships);
                }

                return [
                    'success' => true,
                    'message' => 'Successfully added ' . count($addedChildren) . ' child(ren) to your sponsorship!',
                    'added_children' => $addedChildren,
                    'errors' => $errors
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to add children: ' . implode(', ', $errors)
                ];
            }

        } catch (Exception $e) {
            error_log('Failed to add children to sponsorship: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'System error occurred. Please try again.'
            ];
        }
    }
}