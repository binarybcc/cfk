<?php

declare(strict_types=1);

/**
 * Slim Framework - Route Definitions
 *
 * This file defines all routes for the Slim Framework application.
 * Routes are registered with the Slim App instance.
 *
 * URL Structure:
 * - Old style: ?page=children (still works during migration)
 * - New style: /children (Slim routes)
 */

use CFK\Controller\ChildController;
use CFK\Controller\TestController;
use Slim\App;

return function (App $app) {
    // =========================================================================
    // Test Routes (Infrastructure Verification)
    // =========================================================================

    /**
     * Test Route: /slim-test
     * Verifies Slim infrastructure is working
     * Returns JSON response
     */
    $app->get('/slim-test', [TestController::class, 'test']);

    /**
     * Test Route: /slim-test-view
     * Verifies Twig template rendering works
     * Returns HTML response
     */
    $app->get('/slim-test-view', [TestController::class, 'testView']);

    // =========================================================================
    // Child Routes (Week 2-3 Migration)
    // =========================================================================

    /**
     * Children List Page: /children
     * Display all children with filtering and pagination
     * Migrated from: ?page=children
     */
    $app->get('/children', [ChildController::class, 'index']);

    /**
     * Child Detail Page: /children/{id}
     * Display individual child profile
     * Migrated from: ?page=child&id={id}
     */
    $app->get('/children/{id:\d+}', [ChildController::class, 'show']);

    // =========================================================================
    // Future Routes (Will be added during migration)
    // =========================================================================

    // Week 3-8: Additional Features
    // $app->get('/children', [ChildController::class, 'index']);
    // $app->get('/admin/dashboard', [AdminController::class, 'dashboard']);
    // $app->post('/cart/add', [CartController::class, 'add']);
    // etc.

    // =========================================================================
    // Legacy Fallback (During Migration)
    // =========================================================================

    // Old query string routes (?page=children) continue to work via index.php
    // New Slim routes coexist with old routes
    // Gradually migrate features from old to new routing
};
