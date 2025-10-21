<?php

declare(strict_types=1);

/**
 * Remember Me Token Manager
 * Handles secure, database-backed "Remember Me" functionality
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

class RememberMeTokens
{
    const TOKEN_LENGTH = 32; // 256 bits
    const TOKEN_EXPIRY_DAYS = 30;
    const COOKIE_NAME = 'cfk_remember_token';

    /**
     * Generate and store a new remember-me token
     *
     * @param int $userId The admin user ID
     * @return string The token (to be set in cookie)
     */
    public static function generateToken(int $userId): string
    {
        // Generate cryptographically secure random token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));

        // Hash token for database storage (never store plaintext)
        $tokenHash = hash('sha256', $token);

        // Calculate expiry
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_DAYS . ' days'));

        // Store in database
        Database::insert('admin_remember_tokens', [
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        return $token;
    }

    /**
     * Validate a remember-me token
     *
     * @param string $token The token from cookie
     * @return array|null User data if valid, null if invalid
     */
    public static function validateToken(string $token): ?array
    {
        // Hash the provided token
        $tokenHash = hash('sha256', $token);

        // Look up token in database
        $tokenRecord = Database::fetchRow(
            "SELECT t.*, u.id, u.username, u.role, u.email
             FROM admin_remember_tokens t
             JOIN admin_users u ON t.user_id = u.id
             WHERE t.token_hash = ? AND t.expires_at > NOW()",
            [$tokenHash]
        );

        if (!$tokenRecord) {
            return null;
        }

        // Update last_used_at timestamp
        Database::update(
            'admin_remember_tokens',
            ['last_used_at' => date('Y-m-d H:i:s')],
            ['id' => $tokenRecord['id']]
        );

        // Update user's last_login
        Database::update(
            'admin_users',
            ['last_login' => date('Y-m-d H:i:s')],
            ['id' => $tokenRecord['user_id']]
        );

        // Return user data
        return [
            'id' => $tokenRecord['user_id'],
            'username' => $tokenRecord['username'],
            'role' => $tokenRecord['role'],
            'email' => $tokenRecord['email']
        ];
    }

    /**
     * Set remember-me cookie
     *
     * @param string $token The token value
     * @param bool $isProduction Whether we're in production (determines Secure flag)
     */
    public static function setCookie(string $token, bool $isProduction = false): void
    {
        $expiry = time() + (self::TOKEN_EXPIRY_DAYS * 24 * 60 * 60);

        setcookie(
            self::COOKIE_NAME,
            $token,
            [
                'expires' => $expiry,
                'path' => '/',
                'domain' => '',
                'secure' => $isProduction, // HTTPS only in production
                'httponly' => true, // Prevent JavaScript access
                'samesite' => 'Strict' // CSRF protection
            ]
        );
    }

    /**
     * Clear remember-me cookie
     */
    public static function clearCookie(): void
    {
        setcookie(
            self::COOKIE_NAME,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true
            ]
        );
    }

    /**
     * Revoke a specific token
     *
     * @param string $token The token to revoke
     * @return bool Whether revocation was successful
     */
    public static function revokeToken(string $token): bool
    {
        $tokenHash = hash('sha256', $token);

        $deleted = Database::delete('admin_remember_tokens', [
            'token_hash' => $tokenHash
        ]);

        return $deleted > 0;
    }

    /**
     * Revoke all tokens for a specific user
     *
     * @param int $userId The user ID
     * @return int Number of tokens revoked
     */
    public static function revokeAllUserTokens(int $userId): int
    {
        return Database::delete('admin_remember_tokens', [
            'user_id' => $userId
        ]);
    }

    /**
     * Get remember-me token from cookie
     *
     * @return string|null Token if exists, null otherwise
     */
    public static function getTokenFromCookie(): ?string
    {
        return $_COOKIE[self::COOKIE_NAME] ?? null;
    }

    /**
     * Clean up expired tokens (run via cron)
     *
     * @return int Number of tokens deleted
     */
    public static function cleanupExpiredTokens(): int
    {
        $sql = "DELETE FROM admin_remember_tokens WHERE expires_at < NOW()";
        return Database::execute($sql);
    }

    /**
     * Get all active tokens for a user (for admin interface)
     *
     * @param int $userId The user ID
     * @return array List of active tokens with metadata
     */
    public static function getUserTokens(int $userId): array
    {
        return Database::fetchAll(
            "SELECT id, created_at, last_used_at, expires_at, ip_address, user_agent
             FROM admin_remember_tokens
             WHERE user_id = ? AND expires_at > NOW()
             ORDER BY created_at DESC",
            [$userId]
        );
    }
}
