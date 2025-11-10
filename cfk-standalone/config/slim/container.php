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
 */
$container->register('twig', Twig::class)
    ->setFactory(function () {
        // Create Twig environment
        $twig = Twig::create(__DIR__ . '/../../templates', [
            'cache' => false, // Disable cache during development
            'debug' => config('environment') !== 'production',
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

        return $twig;
    })
    ->setPublic(true);

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
