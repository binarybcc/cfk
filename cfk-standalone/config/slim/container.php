<?php

declare(strict_types=1);

/**
 * Slim Framework - Dependency Injection Container Configuration
 *
 * This file configures the Symfony DI container for Slim Framework.
 * It registers all services, controllers, and dependencies needed for the application.
 */

use CFK\Database\Connection;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

$container = new ContainerBuilder();

// =============================================================================
// Core Services
// =============================================================================

/**
 * Database Connection
 * Existing CFK\Database\Connection class - keep as-is
 */
$container->register('db.connection', Connection::class)
    ->setPublic(true);

/**
 * Twig Template Engine
 * Auto-escaping enabled for XSS prevention
 * Note: Factory closures not supported in Symfony DI - using manual registration
 */
$twig = Twig::create(__DIR__ . '/../../templates', [
    'cache' => false, // Disable cache during development
    'debug' => \config('environment') !== 'production',
    'auto_reload' => true,
]);

// Get Twig environment to add functions
$env = $twig->getEnvironment();

// Add PHP helper functions as Twig functions
$env->addFunction(new \Twig\TwigFunction('getPhotoUrl', 'getPhotoUrl'));
$env->addFunction(new \Twig\TwigFunction('formatAge', 'formatAge'));
$env->addFunction(new \Twig\TwigFunction('getAgeCategory', 'getAgeCategory'));
$env->addFunction(new \Twig\TwigFunction('baseUrl', 'baseUrl'));
$env->addFunction(new \Twig\TwigFunction('sanitizeString', 'sanitizeString'));

// Add global variables
global $childStatusOptions;
$env->addGlobal('childStatusOptions', $childStatusOptions ?? []);

// Register Twig as a service (using instance directly since we can't use closure factory)
$container->set('twig', $twig);

// =============================================================================
// Controllers
// =============================================================================

/**
 * Test Controller
 * Simple test controller to verify Slim infrastructure works
 */
$container->register(CFK\Controller\TestController::class)
    ->addArgument(new Reference('twig'))
    ->setPublic(true);

/**
 * Child Controller
 * Handles child profile viewing (Week 2-3 migration)
 */
$container->register(CFK\Controller\ChildController::class)
    ->addArgument(new Reference('repository.child'))
    ->addArgument(new Reference('twig'))
    ->setPublic(true);

/**
 * Admin Controller
 * Handles admin dashboard and reports (Week 3)
 */
$container->register(CFK\Controller\AdminController::class)
    ->addArgument(new Reference('repository.admin'))
    ->addArgument(new Reference('twig'))
    ->setPublic(true);

/**
 * Sponsor Controller
 * Handles sponsor email lookup and portal access (Week 4)
 */
$container->register(CFK\Controller\SponsorController::class)
    ->addArgument(new Reference('twig'))
    ->setPublic(true);

/**
 * Content Controller
 * Handles static content pages (Week 7 migration)
 */
$container->register(CFK\Controller\ContentController::class)
    ->addArgument(new Reference('twig'))
    ->setPublic(true);

/**
 * Cart Controller
 * Handles reservation cart functionality (Week 6 Phase 3)
 */
$container->register(CFK\Controller\CartController::class)
    ->addArgument(new Reference('twig'))
    ->setPublic(true);

/**
 * Portal Controller
 * Handles sponsor portal (Week 6 Phase 4)
 */
$container->register(CFK\Controller\PortalController::class)
    ->addArgument(new Reference('twig'))
    ->setPublic(true);

/**
 * Admin Child Controller
 * Handles admin CRUD for children (Week 8 Part 2)
 */
$container->register(CFK\Controller\AdminChildController::class)
    ->addArgument(new Reference('twig'))
    ->setPublic(true);

// =============================================================================
// Repositories (Data Access Layer)
// =============================================================================

/**
 * Child Repository
 * Data access layer for child-related queries
 */
$container->register('repository.child', CFK\Repository\ChildRepository::class)
    ->addArgument(new Reference('db.connection'))
    ->setPublic(true);

/**
 * Admin Repository
 * Data access layer for admin dashboard and reports (Week 3)
 */
$container->register('repository.admin', CFK\Repository\AdminRepository::class)
    ->addArgument(new Reference('db.connection'))
    ->setPublic(true);

// =============================================================================
// Services (Business Logic Layer)
// =============================================================================

// Will be added as we migrate features
// Example:
// $container->register('service.sponsorship', CFK\Service\SponsorshipService::class)
//     ->addArgument(new Reference('repository.sponsorship'))
//     ->addArgument(new Reference('service.email'))
//     ->setPublic(true);

// =============================================================================
// Compile Container
// =============================================================================

$container->compile();

return $container;
