<?php
declare(strict_types=1);

/**
 * Request Magic Link Endpoint
 * Handles magic link generation and email sending
 */

define('CFK_APP', true);
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/magic_link_manager.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once __DIR__ . '/../includes/magic_link_email_template.php';

header('Content-Type: application/json');

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
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        throw new Exception('Invalid email format');
    }

    // Get client IP and user agent
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Check rate limiting
    if (RateLimiter::isRateLimited($email, $ipAddress)) {
        MagicLinkManager::logEvent(null, 'rate_limit_exceeded', $ipAddress, $userAgent, 'blocked', [
            'email' => $email
        ]);

        // Return generic response (don't reveal rate limiting)
        echo json_encode([
            'success' => true,
            'message' => 'If your email is registered, you will receive a magic link'
        ]);
        exit;
    }

    // Check if email is registered as admin
    $adminSql = "SELECT id, email FROM admin_users WHERE email = :email LIMIT 1";
    $adminUser = Database::fetchRow($adminSql, ['email' => $email]);

    // CRITICAL: Generic response regardless of whether email exists (prevent enumeration)
    if (!$adminUser) {
        MagicLinkManager::logEvent(null, 'magic_link_requested_nonexistent_email', $ipAddress, $userAgent, 'success', [
            'email' => $email
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'If your email is registered, you will receive a magic link'
        ]);
        exit;
    }

    // Generate magic link token
    $token = MagicLinkManager::createMagicLink($email, $ipAddress, $userAgent);

    // Build login URL
    $loginUrl = baseUrl('admin/verify-magic-link.php?token=' . urlencode($token));
    $expirationMinutes = MagicLinkManager::getExpirationMinutes();

    // Prepare email content
    $htmlContent = MagicLinkEmailTemplate::getHtmlTemplate($loginUrl, $expirationMinutes);
    $textContent = MagicLinkEmailTemplate::getPlainTextTemplate($loginUrl, $expirationMinutes);

    // Send email
    $emailManager = new EmailManager();
    $subject = 'Magic Link Login - ' . config('app_name', 'Christmas for Kids');

    $sent = $emailManager->send(
        to: $email,
        subject: $subject,
        htmlContent: $htmlContent,
        textContent: $textContent
    );

    if (!$sent) {
        MagicLinkManager::logEvent($adminUser['id'], 'magic_link_email_failed', $ipAddress, $userAgent, 'failed', [
            'email' => $email
        ]);

        throw new Exception('Failed to send magic link email');
    }

    // Log successful magic link request
    MagicLinkManager::logEvent($adminUser['id'], 'magic_link_sent', $ipAddress, $userAgent, 'success', [
        'email' => $email
    ]);

    // Return success response (generic)
    echo json_encode([
        'success' => true,
        'message' => 'If your email is registered, you will receive a magic link'
    ]);

} catch (Exception $e) {
    error_log('Magic link request error: ' . $e->getMessage());

    // Return generic error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => config('debug') ? $e->getMessage() : 'Unable to process request'
    ]);
}
