<?php

declare(strict_types=1);

namespace CFK\Controller;

use CFK\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Content Controller
 *
 * Handles static/informational content pages (home, about, donate, how to apply).
 */
class ContentController
{
    private Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Display homepage
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function home(Request $request, Response $response): Response
    {
        // Get statistics for hero section
        $totalChildren = Connection::fetchRow("SELECT COUNT(*) as total FROM children")['total'] ?? 0;
        $totalFamilies = Connection::fetchRow("SELECT COUNT(DISTINCT family_id) as total FROM children")['total'] ?? 0;

        // Prepare view data
        $data = [
            'pageTitle' => 'Home',
            'totalChildren' => $totalChildren,
            'totalFamilies' => $totalFamilies,
        ];

        return $this->view->render($response, 'content/home.twig', $data);
    }

    /**
     * Display about page
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function about(Request $request, Response $response): Response
    {
        // Get statistics for impact section
        $childrenCount = \getChildrenCount([]);
        $familyCount = Connection::fetchRow("SELECT COUNT(DISTINCT family_id) as total FROM children")['total'] ?? 0;

        // Generate CSP nonce for inline scripts
        $cspNonce = bin2hex(random_bytes(16));

        // Prepare view data
        $data = [
            'pageTitle' => 'About Us',
            'childrenCount' => $childrenCount,
            'familyCount' => $familyCount,
            'adminEmail' => \config('admin_email'),
            'cspNonce' => $cspNonce,
        ];

        // Set CSP header to allow inline scripts with nonce
        $response = $response->withHeader(
            'Content-Security-Policy',
            "script-src 'self' 'nonce-{$cspNonce}';"
        );

        return $this->view->render($response, 'content/about.twig', $data);
    }

    /**
     * Display donate page
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function donate(Request $request, Response $response): Response
    {
        // Prepare view data
        $data = [
            'pageTitle' => 'Donate',
        ];

        return $this->view->render($response, 'content/donate.twig', $data);
    }

    /**
     * Display how to apply page
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function howToApply(Request $request, Response $response): Response
    {
        // Prepare view data
        $data = [
            'pageTitle' => 'How To Apply',
            'adminEmail' => \config('admin_email'),
        ];

        return $this->view->render($response, 'content/how-to-apply.twig', $data);
    }
}
