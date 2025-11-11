<?php

declare(strict_types=1);

namespace CFK\Controller;

use CFK\Reservation\Manager as ReservationManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Cart Controller
 *
 * Handles shopping cart and reservation workflow for multi-child sponsorships.
 */
class CartController
{
    private Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Display cart review page
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function review(Request $request, Response $response): Response
    {
        // Generate CSP nonce for inline scripts
        $cspNonce = bin2hex(random_bytes(16));

        // Prepare view data
        $data = [
            'pageTitle' => 'Review Your Sponsorship',
            'cspNonce' => $cspNonce,
            'baseUrl' => \baseUrl(''),
            'adminEmail' => \config('admin_email'),
        ];

        // Set CSP header to allow inline scripts with nonce
        $response = $response->withHeader(
            'Content-Security-Policy',
            "script-src 'self' 'nonce-{$cspNonce}' https://cdn.jsdelivr.net;"
        );

        return $this->view->render($response, 'cart/review.twig', $data);
    }

    /**
     * Create reservation (API endpoint)
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function createReservation(Request $request, Response $response): Response
    {
        try {
            // Get JSON payload
            $payload = $request->getParsedBody();

            if (! isset($payload['sponsor']) || ! isset($payload['children_ids'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Missing required data: sponsor and children_ids',
                ], 400);
            }

            $sponsorData = $payload['sponsor'];
            $childrenIds = $payload['children_ids'];

            // Validate sponsor data
            if (empty($sponsorData['name']) || empty($sponsorData['email'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Sponsor name and email are required',
                ], 400);
            }

            // Validate email
            if (! filter_var($sponsorData['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid email address',
                ], 400);
            }

            // Validate children IDs
            if (! is_array($childrenIds) || empty($childrenIds)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'At least one child must be selected',
                ], 400);
            }

            // Create reservation using existing manager
            $result = ReservationManager::createReservation(
                $sponsorData,
                array_map('intval', $childrenIds)
            );

            if ($result['success']) {
                // Return success with details
                return $this->jsonResponse($response, [
                    'success' => true,
                    'message' => 'Reservation created successfully',
                    'sponsor_email' => $sponsorData['email'],
                    'children_count' => count($childrenIds),
                    'reservation_token' => $result['token'],
                    'expires_at' => $result['expires_at'],
                ]);
            }

            // Return error from manager
            return $this->jsonResponse($response, $result, 400);
        } catch (\Exception $e) {
            error_log('Cart reservation error: ' . $e->getMessage());

            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'An error occurred while creating your reservation',
            ], 500);
        }
    }

    /**
     * Display reservation success page
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function success(Request $request, Response $response): Response
    {
        // Get confirmation data from session
        $confirmationData = $_SESSION['cfk_sponsorship_confirmation'] ?? null;

        if (! $confirmationData) {
            // No confirmation data - redirect to children list
            return $response
                ->withHeader('Location', \baseUrl('/children'))
                ->withStatus(302);
        }

        // Clear from session
        unset($_SESSION['cfk_sponsorship_confirmation']);

        // Prepare view data
        $data = [
            'pageTitle' => 'Reservation Confirmed!',
            'sponsorEmail' => $confirmationData['sponsor_email'] ?? '',
            'childrenCount' => $confirmationData['children_count'] ?? 0,
            'adminEmail' => \config('admin_email'),
        ];

        return $this->view->render($response, 'cart/success.twig', $data);
    }

    /**
     * Helper: Return JSON response
     *
     * @param Response $response PSR-7 response
     * @param array<string, mixed> $data Response data
     * @param int $status HTTP status code
     * @return Response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
