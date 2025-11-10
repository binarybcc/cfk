<?php

declare(strict_types=1);

namespace CFK\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Test Controller
 *
 * Simple controller to verify Slim Framework infrastructure is working correctly.
 * This controller will be removed once migration to Slim is complete.
 *
 * @package CFK\Controller
 */
class TestController
{
    /**
     * @param Twig $twig Twig template engine
     */
    public function __construct(
        private Twig $twig
    ) {
    }

    /**
     * Test Route - JSON Response
     *
     * Tests that Slim routing works and can return JSON responses.
     * Access via: /slim-test
     *
     * @param Request $request PSR-7 Request
     * @param Response $response PSR-7 Response
     * @return Response JSON response
     */
    public function test(Request $request, Response $response): Response
    {
        $data = [
            'status' => 'success',
            'message' => 'Slim Framework is working!',
            'timestamp' => date('Y-m-d H:i:s'),
            'framework' => [
                'name' => 'Slim Framework',
                'version' => '4.x',
                'di_container' => 'Symfony DI',
                'template_engine' => 'Twig',
            ],
            'architecture' => [
                'pattern' => 'Controller-Service-Repository',
                'entry_point' => 'index.php',
                'coexists_with' => 'Legacy routing',
            ],
        ];

        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Test Route - Twig Template Response
     *
     * Tests that Twig template rendering works correctly.
     * Access via: /slim-test-view
     *
     * @param Request $request PSR-7 Request
     * @param Response $response PSR-7 Response
     * @return Response HTML response rendered via Twig
     */
    public function testView(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'test/slim-test.twig', [
            'title' => 'Slim Framework Test',
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => 'If you can see this, Twig template rendering is working!',
            'features' => [
                'Slim Framework 4.x routing',
                'Symfony DI Container',
                'Twig template engine',
                'PSR-7 Request/Response',
                'Modern PHP 8.2+ features',
            ],
        ]);
    }
}
