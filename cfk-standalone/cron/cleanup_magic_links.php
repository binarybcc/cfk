<?php
declare(strict_types=1);

/**
 * Magic Link Cleanup Cron Job
 * Removes expired tokens and old rate limiting data
 * Run hourly via cron
 */

// Don't display output for cron
ini_set('display_errors', '0');

define('CFK_APP', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/magic_link_manager.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

// Log file path
$logFile = __DIR__ . '/../logs/cron_cleanup_magic_links.log';

try {
    // Create logs directory if not exists
    if (!is_dir(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }

    $startTime = time();
    $timestamp = date('Y-m-d H:i:s');

    // Log start
    file_put_contents($logFile, "[{$timestamp}] Starting cleanup job\n", FILE_APPEND);

    // Clean up expired magic link tokens (older than 1 hour)
    $expiredTokens = MagicLinkManager::cleanupExpiredTokens();
    file_put_contents($logFile, "[{$timestamp}] Deleted {$expiredTokens} expired magic link tokens\n", FILE_APPEND);

    // Clean up old rate limiting records (older than 1 hour)
    $oldRateLimits = RateLimiter::cleanup();
    file_put_contents($logFile, "[{$timestamp}] Deleted {$oldRateLimits} old rate limit records\n", FILE_APPEND);

    // Clean up old audit logs (older than 90 days)
    $sql = "DELETE FROM admin_login_log WHERE timestamp < NOW() - INTERVAL 90 DAY";
    $oldAuditLogs = Database::execute($sql, []);
    file_put_contents($logFile, "[{$timestamp}] Deleted {$oldAuditLogs} old audit log entries\n", FILE_APPEND);

    $duration = time() - $startTime;
    file_put_contents($logFile, "[{$timestamp}] Cleanup job completed successfully in {$duration} seconds\n\n", FILE_APPEND);

    // Write to syslog if available
    if (function_exists('syslog')) {
        syslog(LOG_INFO, "CFK magic link cleanup: deleted {$expiredTokens} tokens, {$oldRateLimits} rate limits, {$oldAuditLogs} audit logs");
    }

} catch (Exception $e) {
    $timestamp = date('Y-m-d H:i:s');
    $error = "[{$timestamp}] ERROR: " . $e->getMessage() . "\n";

    file_put_contents($logFile, $error, FILE_APPEND);

    if (function_exists('syslog')) {
        syslog(LOG_ERR, "CFK magic link cleanup failed: " . $e->getMessage());
    }

    // Exit with error code for cron monitoring
    exit(1);
}

// Success
exit(0);
