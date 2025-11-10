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
    ->setFactory([Twig::class, 'create'])
    ->addArgument(__DIR__ . '/../../templates')
    ->addArgument([
        'cache' => false, // Disable cache during development
        'debug' => config('environment') !== 'production',
        'auto_reload' => true,
    ])
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

// =============================================================================
// Repositories (Data Access Layer)
// =============================================================================

// Will be added as we migrate features
// Example:
// $container->register('repository.child', CFK\Repository\ChildRepository::class)
//     ->addArgument(new Reference('db.connection'))
//     ->setPublic(true);

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
