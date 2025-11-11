<?php

declare(strict_types=1);

/**
 * Admin - Manage Children (LEGACY REDIRECT)
 *
 * This file has been migrated to Slim Framework.
 * All requests are now redirected to /admin/children
 *
 * Migration Date: 2025-11-11
 * Week 8 Part 2: Admin Panel Migration
 */

// Security constant
define('CFK_APP', true);

// Load configuration for baseUrl() function
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect to Slim route
header('Location: ' . baseUrl('/admin/children'), true, 301);
exit;
