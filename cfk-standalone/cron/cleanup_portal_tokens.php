<?php

declare(strict_types=1);

/**
 * Cleanup Expired Portal Access Tokens
 * Run via cron: 0 3 * * * php /path/to/cron/cleanup_portal_tokens.php
 */

// Security constant
define('CFK_APP', true);

// Load dependencies
require_once __DIR__ . '/../config/config.php';

use CFK\Sponsorship\Manager as SponsorshipManager;

// Clean up expired tokens
$deleted = SponsorshipManager::cleanupExpiredPortalTokens();

// Log result
$timestamp = date('Y-m-d H:i:s');
$logMessage = "[{$timestamp}] Cleaned up {$deleted} expired portal access token(s)\n";

// Log to file
error_log($logMessage);

// Also output for cron email
echo $logMessage;
