<?php
declare(strict_types=1);

/**
 * Rate Limiter
 * Prevents brute force attacks on magic link requests
 */

if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

class RateLimiter {
    private const EMAIL_RATE_PER_WINDOW = 1; // 1 request per 5 minutes
    private const EMAIL_RATE_PER_HOUR = 3; // max 3 per hour
    private const IP_RATE_PER_WINDOW = 5; // 5 requests per 5 minutes
    private const IP_RATE_PER_HOUR = 10; // max 10 per hour
    private const WINDOW_MINUTES = 5;

    /**
     * Check if a request is rate limited
     * Returns: true if rate limited (block request), false if allowed
     */
    public static function isRateLimited(string $email, string $ipAddress): bool {
        // Check email rate limiting
        if (self::checkEmailLimit($email)) {
            return true;
        }

        // Check IP rate limiting
        if (self::checkIpLimit($ipAddress)) {
            return true;
        }

        // Record the request
        self::recordRequest($email, $ipAddress);

        return false;
    }

    /**
     * Check email-based rate limiting
     */
    private static function checkEmailLimit(string $email): bool {
        try {
            $now = time();
            $windowStart = date('Y-m-d H:i:s', $now - (self::WINDOW_MINUTES * 60));
            $hourStart = date('Y-m-d H:i:s', $now - 3600);

            // Check requests in current 5-minute window
            $sql = "
                SELECT COUNT(*) as count
                FROM rate_limit_tracking
                WHERE email = :email
                AND window_start > :window_start
                AND last_request > :window_start
            ";

            $windowResult = Database::fetchRow($sql, [
                'email' => $email,
                'window_start' => $windowStart
            ]);

            if ($windowResult && $windowResult['count'] >= self::EMAIL_RATE_PER_WINDOW) {
                return true; // Rate limited for this window
            }

            // Check requests in the hour
            $hourSql = "
                SELECT COUNT(*) as count
                FROM rate_limit_tracking
                WHERE email = :email
                AND last_request > :hour_start
            ";

            $hourResult = Database::fetchRow($hourSql, [
                'email' => $email,
                'hour_start' => $hourStart
            ]);

            if ($hourResult && $hourResult['count'] >= self::EMAIL_RATE_PER_HOUR) {
                return true; // Rate limited for the hour
            }

            return false;
        } catch (Exception $e) {
            error_log('Email rate limit check failed: ' . $e->getMessage());
            // On error, deny the request (fail closed for security)
            // Better to block legitimate requests during DB issues than allow attacks
            return true;
        }
    }

    /**
     * Check IP-based rate limiting
     */
    private static function checkIpLimit(string $ipAddress): bool {
        try {
            $now = time();
            $windowStart = date('Y-m-d H:i:s', $now - (self::WINDOW_MINUTES * 60));
            $hourStart = date('Y-m-d H:i:s', $now - 3600);

            // Check requests from this IP in current 5-minute window
            $sql = "
                SELECT COUNT(*) as count
                FROM rate_limit_tracking
                WHERE ip_address = :ip_address
                AND window_start > :window_start
                AND last_request > :window_start
            ";

            $windowResult = Database::fetchRow($sql, [
                'ip_address' => $ipAddress,
                'window_start' => $windowStart
            ]);

            if ($windowResult && $windowResult['count'] >= self::IP_RATE_PER_WINDOW) {
                return true; // Rate limited for this window
            }

            // Check requests from this IP in the hour
            $hourSql = "
                SELECT COUNT(*) as count
                FROM rate_limit_tracking
                WHERE ip_address = :ip_address
                AND last_request > :hour_start
            ";

            $hourResult = Database::fetchRow($hourSql, [
                'ip_address' => $ipAddress,
                'hour_start' => $hourStart
            ]);

            if ($hourResult && $hourResult['count'] >= self::IP_RATE_PER_HOUR) {
                return true; // Rate limited for the hour
            }

            return false;
        } catch (Exception $e) {
            error_log('IP rate limit check failed: ' . $e->getMessage());
            // On error, deny the request (fail closed for security)
            // Better to block legitimate requests during DB issues than allow attacks
            return true;
        }
    }

    /**
     * Record a request for rate limiting
     */
    private static function recordRequest(string $email, string $ipAddress): void {
        try {
            $now = new DateTime();
            $windowStart = $now->format('Y-m-d H:i:00'); // Round to minute

            $sql = "
                INSERT INTO rate_limit_tracking (email, ip_address, last_request, request_count, window_start)
                VALUES (:email, :ip_address, NOW(), 1, :window_start)
                ON DUPLICATE KEY UPDATE
                    request_count = request_count + 1,
                    last_request = NOW()
            ";

            Database::execute($sql, [
                'email' => $email,
                'ip_address' => $ipAddress,
                'window_start' => $windowStart
            ]);
        } catch (Exception $e) {
            error_log('Failed to record rate limit: ' . $e->getMessage());
        }
    }

    /**
     * Clean up old rate limit records
     */
    public static function cleanup(): int {
        try {
            $sql = "DELETE FROM rate_limit_tracking WHERE window_start < NOW() - INTERVAL 1 HOUR";
            return Database::execute($sql, []);
        } catch (Exception $e) {
            error_log('Failed to cleanup rate limits: ' . $e->getMessage());
            return 0;
        }
    }
}
