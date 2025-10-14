<?php
declare(strict_types=1);

/**
 * Rate Limiter - Prevents brute force attacks
 * Session-based rate limiting for login attempts
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

class RateLimiter {
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutes in seconds

    /**
     * Check if an identifier is allowed to make an attempt
     */
    public static function checkLoginAttempt(string $identifier): bool {
        $key = self::getKey($identifier);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'lockout_until' => null
            ];
        }

        $data = $_SESSION[$key];

        // Check if currently locked out
        if ($data['lockout_until'] && time() < $data['lockout_until']) {
            return false; // Still locked out
        }

        // Reset if lockout period has expired
        if ($data['lockout_until'] && time() >= $data['lockout_until']) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'lockout_until' => null
            ];
        }

        return true; // Allowed to attempt
    }

    /**
     * Record a failed login attempt
     */
    public static function recordFailedAttempt(string $identifier): void {
        $key = self::getKey($identifier);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'lockout_until' => null
            ];
        }

        $_SESSION[$key]['attempts']++;

        // Lock out after reaching max attempts
        if ($_SESSION[$key]['attempts'] >= self::MAX_ATTEMPTS) {
            $_SESSION[$key]['lockout_until'] = time() + self::LOCKOUT_TIME;

            error_log("CFK Rate Limit: User $identifier locked out for " . self::LOCKOUT_TIME . " seconds after " . self::MAX_ATTEMPTS . " failed attempts");
        }
    }

    /**
     * Record a successful login attempt (clears rate limit data)
     */
    public static function recordSuccessfulAttempt(string $identifier): void {
        $key = self::getKey($identifier);
        unset($_SESSION[$key]); // Clear all rate limit data on success
    }

    /**
     * Get remaining lockout time in seconds
     */
    public static function getRemainingLockoutTime(string $identifier): int {
        $key = self::getKey($identifier);

        if (!isset($_SESSION[$key]) || !$_SESSION[$key]['lockout_until']) {
            return 0;
        }

        $remaining = $_SESSION[$key]['lockout_until'] - time();
        return max(0, $remaining);
    }

    /**
     * Get number of failed attempts
     */
    public static function getAttemptCount(string $identifier): int {
        $key = self::getKey($identifier);
        return $_SESSION[$key]['attempts'] ?? 0;
    }

    /**
     * Get remaining attempts before lockout
     */
    public static function getRemainingAttempts(string $identifier): int {
        $attempts = self::getAttemptCount($identifier);
        return max(0, self::MAX_ATTEMPTS - $attempts);
    }

    /**
     * Generate session key for rate limit data
     */
    private static function getKey(string $identifier): string {
        return 'rate_limit_' . md5($identifier);
    }

    /**
     * Get max attempts constant
     */
    public static function getMaxAttempts(): int {
        return self::MAX_ATTEMPTS;
    }

    /**
     * Get lockout time in seconds
     */
    public static function getLockoutTime(): int {
        return self::LOCKOUT_TIME;
    }
}
