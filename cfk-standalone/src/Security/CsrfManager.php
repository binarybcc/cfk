<?php

declare(strict_types=1);

namespace CFK\Security;

/**
 * CSRF (Cross-Site Request Forgery) protection manager
 */
class CsrfManager
{
    private const TOKEN_KEY = 'csrf_token';
    private const TOKEN_LENGTH = 32;

    /**
     * Generate a new CSRF token
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION[self::TOKEN_KEY] = $token;

        return $token;
    }

    /**
     * Get the current CSRF token (generate if not exists)
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::TOKEN_KEY])) {
            return self::generateToken();
        }

        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Validate CSRF token
     */
    public static function validateToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::TOKEN_KEY])) {
            return false;
        }

        return hash_equals($_SESSION[self::TOKEN_KEY], $token);
    }

    /**
     * Generate HTML hidden input field for CSRF token
     */
    public static function getHiddenField(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Refresh the CSRF token
     */
    public static function refreshToken(): string
    {
        return self::generateToken();
    }

    /**
     * Clear the CSRF token
     */
    public static function clearToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION[self::TOKEN_KEY]);
    }
}