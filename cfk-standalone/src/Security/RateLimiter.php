<?php

declare(strict_types=1);

namespace CFK\Security;

/**
 * Rate limiting implementation to prevent abuse
 */
class RateLimiter
{
    private const CACHE_PREFIX = 'rate_limit_';
    private const DEFAULT_MAX_ATTEMPTS = 10;
    private const DEFAULT_WINDOW_SECONDS = 3600; // 1 hour

    /**
     * Check if an action is rate limited
     */
    public static function isLimited(
        string $identifier, 
        int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
        int $windowSeconds = self::DEFAULT_WINDOW_SECONDS
    ): bool {
        $key = self::CACHE_PREFIX . md5($identifier);
        $attempts = self::getAttempts($key);

        if ($attempts >= $maxAttempts) {
            return true;
        }

        return false;
    }

    /**
     * Record an attempt
     */
    public static function recordAttempt(
        string $identifier,
        int $windowSeconds = self::DEFAULT_WINDOW_SECONDS
    ): void {
        $key = self::CACHE_PREFIX . md5($identifier);
        $attempts = self::getAttempts($key);
        
        // In a real application, you'd use Redis or Memcached
        // For simplicity, we'll use APCu if available, otherwise session
        if (function_exists('apcu_store')) {
            apcu_store($key, $attempts + 1, $windowSeconds);
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION[$key] = [
                'attempts' => $attempts + 1,
                'expires' => time() + $windowSeconds
            ];
        }
    }

    /**
     * Reset attempts for an identifier
     */
    public static function resetAttempts(string $identifier): void
    {
        $key = self::CACHE_PREFIX . md5($identifier);
        
        if (function_exists('apcu_delete')) {
            apcu_delete($key);
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            unset($_SESSION[$key]);
        }
    }

    /**
     * Get remaining time until rate limit resets
     */
    public static function getResetTime(
        string $identifier,
        int $windowSeconds = self::DEFAULT_WINDOW_SECONDS
    ): int {
        $key = self::CACHE_PREFIX . md5($identifier);
        
        if (function_exists('apcu_fetch')) {
            $info = apcu_key_info($key);
            if ($info) {
                return $info['creation_time'] + $windowSeconds - time();
            }
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (isset($_SESSION[$key]['expires'])) {
                return $_SESSION[$key]['expires'] - time();
            }
        }

        return 0;
    }

    /**
     * Get current attempt count
     */
    private static function getAttempts(string $key): int
    {
        if (function_exists('apcu_fetch')) {
            $attempts = apcu_fetch($key);
            return $attempts !== false ? (int) $attempts : 0;
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            if (isset($_SESSION[$key])) {
                // Check if expired
                if ($_SESSION[$key]['expires'] < time()) {
                    unset($_SESSION[$key]);
                    return 0;
                }
                return (int) $_SESSION[$key]['attempts'];
            }
        }

        return 0;
    }

    /**
     * Create rate limiter for IP address
     */
    public static function forIp(
        string $action = 'general',
        int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
        int $windowSeconds = self::DEFAULT_WINDOW_SECONDS
    ): array {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $identifier = "ip:{$ip}:{$action}";
        
        return [
            'is_limited' => self::isLimited($identifier, $maxAttempts, $windowSeconds),
            'record_attempt' => fn() => self::recordAttempt($identifier, $windowSeconds),
            'reset_attempts' => fn() => self::resetAttempts($identifier),
            'reset_time' => self::getResetTime($identifier, $windowSeconds)
        ];
    }

    /**
     * Create rate limiter for user session
     */
    public static function forSession(
        string $action = 'general',
        int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
        int $windowSeconds = self::DEFAULT_WINDOW_SECONDS
    ): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $sessionId = session_id();
        $identifier = "session:{$sessionId}:{$action}";
        
        return [
            'is_limited' => self::isLimited($identifier, $maxAttempts, $windowSeconds),
            'record_attempt' => fn() => self::recordAttempt($identifier, $windowSeconds),
            'reset_attempts' => fn() => self::resetAttempts($identifier),
            'reset_time' => self::getResetTime($identifier, $windowSeconds)
        ];
    }
}