<?php

declare(strict_types=1);

/**
 * Cleanup Expired Remember-Me Tokens
 * Run via cron: 0 2 * * * php /path/to/cron/cleanup_remember_tokens.php
 */

// Security constant
define('CFK_APP', true);

// Load dependencies
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/remember_me_tokens.php';

// Clean up expired tokens
$deleted = RememberMeTokens::cleanupExpiredTokens();

// Log result
$timestamp = date('Y-m-d H:i:s');
$logMessage = "[{$timestamp}] Cleaned up {$deleted} expired remember-me token(s)\n";

// Log to file
error_log($logMessage);

// Also output for cron email
echo $logMessage;
