<?php

declare(strict_types=1);

/**
 * Request Magic Link Endpoint
 * Handles magic link generation and email sending
 */

define('CFK_APP', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once __DIR__ . '/../includes/magic_link_email_template.php';

use CFK\Auth\MagicLinkManager;
use CFK\Database\Connection as Database;
use CFK\Email\Manager as EmailManager;

header('Content-Type: application/json');

// Track execution time to prevent timing attacks (email enumeration)
$startTime = microtime(true);

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);

        throw new Exception('Method not allowed');
    }

    // Get email from POST
    $email = sanitizeString($_POST['email'] ?? '');

    if (empty($email)) {
        http_response_code(400);

        throw new Exception('Email is required');
    }

    // Validate email format
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);

        throw new Exception('Invalid email format');
    }

    // Get client IP and user agent
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Check rate limiting
    if (RateLimiter::isRateLimited($email, $ipAddress)) {
        MagicLinkManager::logEvent(null, 'rate_limit_exceeded', $ipAddress, $userAgent, 'blocked', [
            'email' => $email,
        ]);

        // Ensure constant-time response before returning
        ensureConstantTime($startTime);

        // Return generic response (don't reveal rate limiting)
        echo json_encode([
            'success' => true,
            'message' => 'If your email is registered, you will receive a magic link',
        ]);
        exit;
    }

    // Check if email is registered as admin
    $adminSql = "SELECT id, email FROM admin_users WHERE email = :email LIMIT 1";
    $adminUser = Database::fetchRow($adminSql, ['email' => $email]);

    // ALWAYS generate token and prepare email (prevents timing attacks)
    // Only send if admin exists, but preparation happens regardless
    $token = MagicLinkManager::generateToken();
    $loginUrl = baseUrl('admin/verify-magic-link.php?token=' . urlencode($token));
    $expirationMinutes = MagicLinkManager::getExpirationMinutes();
    $htmlContent = MagicLinkEmailTemplate::getHtmlTemplate($loginUrl, $expirationMinutes);
    $textContent = MagicLinkEmailTemplate::getPlainTextTemplate($loginUrl, $expirationMinutes);

    // CRITICAL: Only store token and send email if admin exists
    // But preparation time is identical regardless
    if ($adminUser) {
        // Store the token in database (only for valid admins)
        $tokenHash = MagicLinkManager::hashToken($token);

        // Use MySQL's DATE_ADD to avoid timezone issues
        $sql = "
            INSERT INTO admin_magic_links (token_hash, email, expires_at, ip_address, user_agent)
            VALUES (:token_hash, :email, DATE_ADD(NOW(), INTERVAL :expiration_minutes MINUTE), :ip_address, :user_agent)
        ";

        Database::execute($sql, [
            'token_hash' => $tokenHash,
            'email' => $email,
            'expiration_minutes' => $expirationMinutes,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        // Send email (only for valid admins) - Use working pattern from reservation_emails.php
        try {
            $mailer = EmailManager::getMailer();
            $mailer->clearAddresses();
            $mailer->isHTML(true);
            $mailer->addAddress($email);
            $mailer->Subject = 'Magic Link Login - ' . config('app_name', 'Christmas for Kids');
            $mailer->Body = $htmlContent;
            $mailer->AltBody = $textContent;

            $sent = $mailer->send();

            if (! $sent) {
                error_log('Magic link email send failed: ' . $mailer->ErrorInfo);
                MagicLinkManager::logEvent(isset($adminUser['id']) ? (int)$adminUser['id'] : null, 'magic_link_email_failed', $ipAddress, $userAgent, 'failed', [
                    'email' => $email,
                    'error' => $mailer->ErrorInfo,
                ]);
            } else {
                error_log('Magic link email sent successfully to: ' . $email);
                MagicLinkManager::logEvent(isset($adminUser['id']) ? (int)$adminUser['id'] : null, 'magic_link_sent', $ipAddress, $userAgent, 'success', [
                    'email' => $email,
                ]);
            }
        } catch (Exception $e) {
            error_log('Magic link email exception: ' . $e->getMessage());
            MagicLinkManager::logEvent(isset($adminUser['id']) ? (int)$adminUser['id'] : null, 'magic_link_email_exception', $ipAddress, $userAgent, 'failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    } else {
        // Log non-existent email attempt (for security monitoring)
        MagicLinkManager::logEvent(null, 'magic_link_requested_nonexistent_email', $ipAddress, $userAgent, 'success', [
            'email' => $email,
        ]);
    }

    // Ensure constant-time response regardless of email validity
    ensureConstantTime($startTime);

    // CRITICAL: Generic response regardless of whether email exists (prevent enumeration)
    echo json_encode([
        'success' => true,
        'message' => 'If your email is registered, you will receive a magic link',
    ]);
} catch (Exception $e) {
    error_log('Magic link request error: ' . $e->getMessage());

    // Ensure constant-time even on error
    ensureConstantTime($startTime);

    // Return generic error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => config('debug') ? $e->getMessage() : 'Unable to process request',
    ]);
}

/**
 * Ensure constant-time response to prevent timing attacks
 * Minimum execution time: 800ms (typical SMTP send duration)
 */
function ensureConstantTime(float $startTime): void
{
    $executionTimeMs = (microtime(true) - $startTime) * 1000;
    $targetTimeMs = 800; // Target 800ms minimum (typical email send time)

    if ($executionTimeMs < $targetTimeMs) {
        $sleepMs = (int)($targetTimeMs - $executionTimeMs);
        usleep($sleepMs * 1000); // Convert to microseconds
    }
}
