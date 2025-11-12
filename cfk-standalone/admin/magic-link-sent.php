<?php

declare(strict_types=1);

/**
 * Magic Link Sent (LEGACY REDIRECT)
 *
 * This file has been migrated to Slim Framework.
 * All requests are now redirected to /admin/auth/magic-link-sent
 *
 * Migration Date: 2025-11-12
 * Week 8 Part 2 Phase 8: Admin Authentication Migration
 */

// Security constant
define('CFK_APP', true);

// Load configuration for baseUrl() function
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect to Slim route
header('Location: ' . baseUrl('/admin/auth/magic-link-sent'), true, 301);
exit;
