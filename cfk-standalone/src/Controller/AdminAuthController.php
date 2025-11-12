<?php

declare(strict_types=1);

namespace CFK\Controller;

use CFK\Auth\MagicLinkManager;
use CFK\Database\Connection as Database;
use CFK\Email\Manager as EmailManager;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Admin Authentication Controller
 *
 * Handles all admin authentication operations:
 * - Magic link passwordless login
 * - Token verification and session creation
 * - Logout functionality
 *
 * Week 8 Part 2 Phase 8 Migration
 * Migrated from: admin/login.php, admin/logout.php, admin/request-magic-link.php,
 *                admin/verify-magic-link.php, admin/magic-link-sent.php
 */
class AdminAuthController
{
    private Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Show Login Page
     *
     * Route: GET /admin/login
     * Access: Public (redirects if already logged in)
     */
    public function showLogin(Request $request, Response $response): Response
    {
        // Redirect if already logged in
        if (isLoggedIn()) {
            return $response
                ->withHeader('Location', baseUrl('/admin/dashboard'))
                ->withStatus(302);
        }

        // Get flash messages
        $message = getMessage();

        return $this->view->render($response, 'admin/auth/login.twig', [
            'pageTitle' => 'Admin Login',
            'message' => $message,
            'csrfToken' => generateCsrfToken(),
        ]);
    }

    /**
     * Request Magic Link (API Endpoint)
     *
     * Route: POST /admin/auth/request-magic-link
     * Access: Public
     * Returns: JSON response
     */
    public function requestMagicLink(Request $request, Response $response): Response
    {
        // Set JSON response header
        $response = $response->withHeader('Content-Type', 'application/json');

        // Track execution time to prevent timing attacks (email enumeration)
        $startTime = microtime(true);

        try {
            $data = $request->getParsedBody();
            $email = sanitizeString($data['email'] ?? '');

            if (empty($email)) {
                $this->ensureConstantTime($startTime);
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Email is required',
                ]));
                return $response->withStatus(400);
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->ensureConstantTime($startTime);
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid email format',
                ]));
                return $response->withStatus(400);
            }

            // Get client IP and user agent
            $serverParams = $request->getServerParams();
            $ipAddress = $serverParams['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $serverParams['HTTP_USER_AGENT'] ?? '';

            // Check rate limiting
            require_once __DIR__ . '/../../includes/rate_limiter.php';
            if (\RateLimiter::isRateLimited($email, $ipAddress)) {
                MagicLinkManager::logEvent(null, 'rate_limit_exceeded', $ipAddress, $userAgent, 'blocked', [
                    'email' => $email,
                ]);

                $this->ensureConstantTime($startTime);
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'If your email is registered, you will receive a magic link',
                ]));
                return $response;
            }

            // Check if email is registered as admin
            $adminUser = Database::fetchRow(
                "SELECT id, email FROM admin_users WHERE email = ? LIMIT 1",
                [$email]
            );

            // ALWAYS generate token and prepare email (prevents timing attacks)
            $token = MagicLinkManager::generateToken();
            $loginUrl = baseUrl('/admin/auth/verify-magic-link?token=' . urlencode($token));
            $expirationMinutes = MagicLinkManager::getExpirationMinutes();

            // Load email template
            require_once __DIR__ . '/../../includes/magic_link_email_template.php';
            $htmlContent = \MagicLinkEmailTemplate::getHtmlTemplate($loginUrl, $expirationMinutes);
            $textContent = \MagicLinkEmailTemplate::getPlainTextTemplate($loginUrl, $expirationMinutes);

            // CRITICAL: Only store token and send email if admin exists
            if ($adminUser) {
                // Store the token in database
                $tokenHash = MagicLinkManager::hashToken($token);

                Database::execute("
                    INSERT INTO admin_magic_links (token_hash, email, expires_at, ip_address, user_agent)
                    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), ?, ?)
                ", [
                    $tokenHash,
                    $email,
                    $expirationMinutes,
                    $ipAddress,
                    $userAgent,
                ]);

                // Send email
                try {
                    $mailer = EmailManager::getMailer();
                    $mailer->clearAddresses();
                    $mailer->isHTML(true);
                    $mailer->addAddress($email);
                    $mailer->Subject = 'Magic Link Login - ' . config('app_name', 'Christmas for Kids');
                    $mailer->Body = $htmlContent;
                    $mailer->AltBody = $textContent;

                    $sent = $mailer->send();

                    if (!$sent) {
                        error_log('Magic link email send failed: ' . $mailer->ErrorInfo);
                        MagicLinkManager::logEvent((int)$adminUser['id'], 'magic_link_email_failed', $ipAddress, $userAgent, 'failed', [
                            'email' => $email,
                            'error' => $mailer->ErrorInfo,
                        ]);
                    } else {
                        error_log('Magic link email sent successfully to: ' . $email);
                        MagicLinkManager::logEvent((int)$adminUser['id'], 'magic_link_sent', $ipAddress, $userAgent, 'success', [
                            'email' => $email,
                        ]);
                    }
                } catch (Exception $e) {
                    error_log('Magic link email exception: ' . $e->getMessage());
                    MagicLinkManager::logEvent((int)$adminUser['id'], 'magic_link_email_exception', $ipAddress, $userAgent, 'failed', [
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
            $this->ensureConstantTime($startTime);

            // CRITICAL: Generic response regardless of whether email exists (prevent enumeration)
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'If your email is registered, you will receive a magic link',
            ]));

            return $response;
        } catch (Exception $e) {
            error_log('Magic link request error: ' . $e->getMessage());
            $this->ensureConstantTime($startTime);

            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => config('debug') ? $e->getMessage() : 'Unable to process request',
            ]));

            return $response->withStatus(400);
        }
    }

    /**
     * Show Magic Link Sent Confirmation Page
     *
     * Route: GET /admin/auth/magic-link-sent
     * Access: Public
     */
    public function showMagicLinkSent(Request $request, Response $response): Response
    {
        $expirationMinutes = MagicLinkManager::getExpirationMinutes();

        return $this->view->render($response, 'admin/auth/magic-link-sent.twig', [
            'pageTitle' => 'Magic Link Sent',
            'expirationMinutes' => $expirationMinutes,
        ]);
    }

    /**
     * Verify Magic Link
     *
     * Route: GET/POST /admin/auth/verify-magic-link
     * Access: Public
     */
    public function verifyMagicLink(Request $request, Response $response): Response
    {
        $serverParams = $request->getServerParams();
        $ipAddress = $serverParams['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $serverParams['HTTP_USER_AGENT'] ?? '';

        // Handle GET request - show auto-submit form
        if ($request->getMethod() === 'GET') {
            $params = $request->getQueryParams();
            $token = sanitizeString($params['token'] ?? '');

            if (empty($token)) {
                $_SESSION['error_message'] = 'Invalid or missing magic link';
                return $response
                    ->withHeader('Location', baseUrl('/admin/login'))
                    ->withStatus(302);
            }

            // Generate CSRF token for form submission
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            return $this->view->render($response, 'admin/auth/verify-magic-link.twig', [
                'pageTitle' => 'Completing Login',
                'token' => $token,
                'csrfToken' => $_SESSION['csrf_token'],
            ]);
        }

        // Handle POST request - validate token and create session
        if ($request->getMethod() === 'POST') {
            try {
                $data = $request->getParsedBody();

                // Verify CSRF token
                $csrfToken = $data['csrf_token'] ?? '';
                if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
                    MagicLinkManager::logEvent(null, 'magic_link_csrf_failure', $ipAddress, $userAgent, 'failed');
                    $_SESSION['error_message'] = 'Security validation failed. Please try again.';
                    return $response
                        ->withHeader('Location', baseUrl('/admin/login'))
                        ->withStatus(302);
                }

                $token = sanitizeString($data['token'] ?? '');

                if (empty($token)) {
                    $_SESSION['error_message'] = 'Invalid or missing magic link';
                    return $response
                        ->withHeader('Location', baseUrl('/admin/login'))
                        ->withStatus(302);
                }

                // Validate token
                $tokenData = MagicLinkManager::validateToken($token);

                if (!$tokenData) {
                    MagicLinkManager::logEvent(null, 'magic_link_validation_failed', $ipAddress, $userAgent, 'failed');
                    $_SESSION['error_message'] = 'Invalid or expired magic link. Please request a new one.';
                    return $response
                        ->withHeader('Location', baseUrl('/admin/login'))
                        ->withStatus(302);
                }

                // Check if email has associated admin account
                $adminUser = Database::fetchRow(
                    "SELECT id, email, username, role FROM admin_users WHERE email = ? LIMIT 1",
                    [$tokenData['email']]
                );

                if (!$adminUser) {
                    MagicLinkManager::logEvent(null, 'magic_link_no_admin_account', $ipAddress, $userAgent, 'failed', [
                        'email' => $tokenData['email'],
                    ]);
                    $_SESSION['error_message'] = 'No admin account found for this email';
                    return $response
                        ->withHeader('Location', baseUrl('/admin/login'))
                        ->withStatus(302);
                }

                // Update last_login timestamp
                Database::execute(
                    "UPDATE admin_users SET last_login = NOW() WHERE id = ?",
                    [$adminUser['id']]
                );

                // Regenerate session ID (prevent session fixation attacks)
                session_regenerate_id(true);

                // Create admin session
                $_SESSION['cfk_admin_id'] = $adminUser['id'];
                $_SESSION['cfk_admin_email'] = $adminUser['email'];
                $_SESSION['cfk_admin_username'] = $adminUser['username'];
                $_SESSION['cfk_admin_role'] = $adminUser['role'];
                $_SESSION['login_time'] = time();
                $_SESSION['login_ip'] = $ipAddress;

                // Log successful login
                MagicLinkManager::logEvent((int)$adminUser['id'], 'admin_login_success', $ipAddress, $userAgent, 'success');

                // Redirect to admin dashboard
                return $response
                    ->withHeader('Location', baseUrl('/admin/dashboard'))
                    ->withStatus(302);
            } catch (Exception $e) {
                error_log('Magic link verification error: ' . $e->getMessage());
                MagicLinkManager::logEvent(null, 'magic_link_verification_error', $ipAddress, $userAgent, 'failed');
                $_SESSION['error_message'] = 'An error occurred during login. Please try again.';
                return $response
                    ->withHeader('Location', baseUrl('/admin/login'))
                    ->withStatus(302);
            }
        }

        // Invalid method
        $_SESSION['error_message'] = 'Method not allowed';
        return $response
            ->withHeader('Location', baseUrl('/admin/login'))
            ->withStatus(302);
    }

    /**
     * Logout
     *
     * Route: GET /admin/logout
     * Access: Public
     */
    public function logout(Request $request, Response $response): Response
    {
        // Clear remember-me cookie if it exists
        if (isset($_COOKIE['cfk_remember_token'])) {
            setcookie('cfk_remember_token', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true]);
            unset($_COOKIE['cfk_remember_token']);
        }

        // Log the logout action
        $username = $_SESSION['cfk_admin_username'] ?? 'Unknown';
        $serverParams = $request->getServerParams();
        $ipAddress = $serverParams['REMOTE_ADDR'] ?? 'unknown';
        error_log("CFK Admin: User '$username' logged out from IP: $ipAddress");

        // Destroy all session data
        $_SESSION = [];

        // Destroy the session cookie
        $sessionName = session_name();
        if ($sessionName && isset($_COOKIE[$sessionName])) {
            setcookie($sessionName, '', ['expires' => time() - 3600, 'path' => '/']);
        }

        // Destroy session
        session_destroy();

        // Start new session for message
        session_start();
        setMessage('You have been logged out successfully.', 'success');

        // Redirect to login page
        return $response
            ->withHeader('Location', baseUrl('/admin/login'))
            ->withStatus(302);
    }

    /**
     * Ensure constant-time response to prevent timing attacks
     * Minimum execution time: 800ms (typical SMTP send duration)
     */
    private function ensureConstantTime(float $startTime): void
    {
        $executionTimeMs = (microtime(true) - $startTime) * 1000;
        $targetTimeMs = 800; // Target 800ms minimum (typical email send time)

        if ($executionTimeMs < $targetTimeMs) {
            $sleepMs = (int)($targetTimeMs - $executionTimeMs);
            usleep($sleepMs * 1000); // Convert to microseconds
        }
    }
}
