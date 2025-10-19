<?php
declare(strict_types=1);

/**
 * Magic Link Manager
 * Handles secure generation, validation, and management of magic link tokens
 */

if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

class MagicLinkManager {
    private const TOKEN_LENGTH = 32; // 256 bits
    private const EXPIRATION_MINUTES = 5;
    private const TOKEN_HASH_ALGO = 'sha256';

    /**
     * Generate a secure token
     */
    public static function generateToken(): string {
        $randomBytes = random_bytes(self::TOKEN_LENGTH);
        return bin2hex($randomBytes);
    }

    /**
     * Hash a token for storage
     */
    public static function hashToken(string $token): string {
        return hash(self::TOKEN_HASH_ALGO, $token);
    }

    /**
     * Create a magic link in database
     */
    public static function createMagicLink(
        string $email,
        string $ipAddress,
        string $userAgent
    ): string {
        try {
            $token = self::generateToken();
            $tokenHash = self::hashToken($token);
            $expiresAt = date('Y-m-d H:i:s', time() + (self::EXPIRATION_MINUTES * 60));

            $sql = "
                INSERT INTO admin_magic_links (token_hash, email, expires_at, ip_address, user_agent)
                VALUES (:token_hash, :email, :expires_at, :ip_address, :user_agent)
            ";

            $params = [
                'token_hash' => $tokenHash,
                'email' => $email,
                'expires_at' => $expiresAt,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent
            ];

            Database::execute($sql, $params);

            // Log the magic link request
            self::logEvent(null, 'magic_link_requested', $ipAddress, $userAgent, 'success', [
                'email' => $email
            ]);

            return $token;
        } catch (Exception $e) {
            error_log('Magic link creation failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to create magic link');
        }
    }

    /**
     * Validate a magic link token
     * Uses constant-time comparison to prevent timing attacks
     * Uses row-level locking to prevent race conditions
     */
    public static function validateToken(string $token): ?array {
        $db = Database::getConnection();

        try {
            // Start transaction for atomic validation + deletion
            $db->beginTransaction();

            $tokenHash = self::hashToken($token);

            // Use FOR UPDATE to acquire row-level lock (prevents concurrent validation)
            $sql = "
                SELECT aml.*, au.id as admin_user_id, au.email as admin_email
                FROM admin_magic_links aml
                LEFT JOIN admin_users au ON aml.email = au.email
                WHERE aml.token_hash = :token_hash
                AND aml.expires_at > NOW()
                LIMIT 1
                FOR UPDATE
            ";

            $result = Database::fetchRow($sql, ['token_hash' => $tokenHash]);

            if (!$result) {
                $db->rollback();
                self::logEvent(null, 'magic_link_validation_failed', $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', 'failed', [
                    'reason' => 'token_not_found_or_expired'
                ]);
                return null;
            }

            // Use hash_equals for constant-time comparison (prevent timing attacks)
            if (!hash_equals($result['token_hash'], $tokenHash)) {
                $db->rollback();
                self::logEvent(null, 'magic_link_validation_failed', $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', 'failed', [
                    'reason' => 'token_mismatch'
                ]);
                return null;
            }

            // Delete token immediately while still holding lock (single-use enforcement)
            $deleteSql = "DELETE FROM admin_magic_links WHERE id = :id";
            Database::execute($deleteSql, ['id' => $result['id']]);

            // Commit transaction - releases lock
            $db->commit();

            return $result;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log('Token validation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mark token as used and delete it
     * NOTE: This method is now deprecated as token deletion happens in validateToken()
     * to prevent race conditions. Kept for backward compatibility.
     */
    public static function consumeToken(int $tokenId): bool {
        try {
            $sql = "DELETE FROM admin_magic_links WHERE id = :id";
            Database::execute($sql, ['id' => $tokenId]);
            return true;
        } catch (Exception $e) {
            error_log('Token consumption failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log authentication events for audit trail
     */
    public static function logEvent(
        ?int $adminUserId,
        string $eventType,
        string $ipAddress,
        string $userAgent,
        string $result,
        array $details = []
    ): void {
        try {
            $sql = "
                INSERT INTO admin_login_log (admin_user_id, event_type, ip_address, user_agent, result, details)
                VALUES (:admin_user_id, :event_type, :ip_address, :user_agent, :result, :details)
            ";

            $params = [
                'admin_user_id' => $adminUserId,
                'event_type' => $eventType,
                'ip_address' => $ipAddress,
                'user_agent' => substr($userAgent, 0, 1000),
                'result' => $result,
                'details' => json_encode($details)
            ];

            Database::execute($sql, $params);
        } catch (Exception $e) {
            error_log('Failed to log auth event: ' . $e->getMessage());
        }
    }

    /**
     * Get expiration time in minutes
     */
    public static function getExpirationMinutes(): int {
        return self::EXPIRATION_MINUTES;
    }

    /**
     * Clean up expired tokens
     */
    public static function cleanupExpiredTokens(): int {
        try {
            $sql = "DELETE FROM admin_magic_links WHERE expires_at < NOW() - INTERVAL 1 HOUR";
            return Database::execute($sql, []);
        } catch (Exception $e) {
            error_log('Failed to cleanup expired tokens: ' . $e->getMessage());
            return 0;
        }
    }
}
