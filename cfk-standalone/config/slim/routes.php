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

use CFK\Controller\AdminController;
use CFK\Controller\CartController;
use CFK\Controller\ChildController;
use CFK\Controller\SponsorController;
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
    // Admin Routes (Week 3 Migration - Parts 2 & 3)
    // =========================================================================

    /**
     * Admin Dashboard: /admin/dashboard
     * Display admin statistics and recent activity
     * Migrated from: admin/index.php
     */
    $app->get('/admin/dashboard', [AdminController::class, 'dashboard']);

    /**
     * Admin Reports: /admin/reports
     * Display comprehensive reports and statistics
     * Migrated from: admin/reports.php
     */
    $app->get('/admin/reports', [AdminController::class, 'reports']);

    /**
     * Get Sponsor Data: /admin/api/sponsor (AJAX)
     * Retrieve sponsor information by email
     */
    $app->get('/admin/api/sponsor', [AdminController::class, 'getSponsor']);

    /**
     * Update Sponsor: /admin/api/sponsor (AJAX)
     * Update sponsor information
     */
    $app->post('/admin/api/sponsor', [AdminController::class, 'updateSponsor']);

    // =========================================================================
    // Sponsor Routes (Week 4 & 6 Migration)
    // =========================================================================

    /**
     * Sponsor Lookup Form: /sponsor/lookup (GET)
     * Display email lookup form for portal access
     * Migrated from: ?page=sponsor_lookup
     */
    $app->get('/sponsor/lookup', [SponsorController::class, 'showLookupForm']);

    /**
     * Sponsor Lookup Form: /sponsor/lookup (POST)
     * Process email lookup and send magic link
     * Migrated from: ?page=sponsor_lookup (POST)
     */
    $app->post('/sponsor/lookup', [SponsorController::class, 'processLookup']);

    /**
     * Single Child Sponsorship Form: /sponsor/child/{id} (GET)
     * Display sponsorship form for individual child
     * Migrated from: ?page=sponsor&child_id={id}
     * Week 6 Migration
     */
    $app->get('/sponsor/child/{id:\d+}', [SponsorController::class, 'showSponsorForm']);

    /**
     * Single Child Sponsorship Form: /sponsor/child/{id} (POST)
     * Process sponsorship request submission
     * Migrated from: ?page=sponsor&child_id={id} (POST)
     * Week 6 Migration
     */
    $app->post('/sponsor/child/{id:\d+}', [SponsorController::class, 'submitSponsorship']);

    /**
     * Sponsorship Success Page: /sponsorship/success (GET)
     * Display success message after sponsorship submission
     * Migrated from: ?page=reservation_success
     * Week 6 Migration
     */
    $app->get('/sponsorship/success', [SponsorController::class, 'showSuccess']);

    /**
     * Family Sponsorship Form: /sponsor/family/{id} (GET)
     * Display sponsorship form for entire family
     * Migrated from: ?page=sponsor&family_id={id}
     * Week 6 Phase 2 Migration
     */
    $app->get('/sponsor/family/{id:\d+}', [SponsorController::class, 'showFamilyForm']);

    /**
     * Family Sponsorship Form: /sponsor/family/{id} (POST)
     * Process family sponsorship request submission
     * Migrated from: ?page=sponsor&family_id={id} (POST)
     * Week 6 Phase 2 Migration
     */
    $app->post('/sponsor/family/{id:\d+}', [SponsorController::class, 'submitFamilySponsorship']);

    // =========================================================================
    // Cart Routes (Week 6 Phase 3 Migration)
    // =========================================================================

    /**
     * Cart Review Page: /cart/review (GET)
     * Display cart review page with all selected children
     * Migrated from: ?page=reservation_review
     * Week 6 Phase 3 Migration
     */
    $app->get('/cart/review', [CartController::class, 'review']);

    /**
     * Create Reservation: /cart/api/create (POST - JSON API)
     * Create reservation from cart selections
     * Week 6 Phase 3 Migration
     */
    $app->post('/cart/api/create', [CartController::class, 'createReservation']);

    /**
     * Cart Success Page: /cart/success (GET)
     * Display success message after reservation creation
     * Migrated from: ?page=reservation_success
     * Week 6 Phase 3 Migration
     */
    $app->get('/cart/success', [CartController::class, 'success']);

    // =========================================================================
    // Future Routes (Will be added during migration)
    // =========================================================================

    // Week 7-8: Additional Features
    // Future enhancements

    // =========================================================================
    // Legacy Fallback (During Migration)
    // =========================================================================

    // Old query string routes (?page=children) continue to work via index.php
    // New Slim routes coexist with old routes
    // Gradually migrate features from old to new routing
};
