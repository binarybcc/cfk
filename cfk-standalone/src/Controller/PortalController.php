<?php

declare(strict_types=1);

namespace CFK\Controller;

use CFK\Sponsorship\Manager as SponsorshipManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Portal Controller
 *
 * Handles sponsor portal access for viewing and managing existing sponsorships.
 */
class PortalController
{
    private Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Display sponsor portal (view all sponsorships)
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function viewSponsorships(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $token = $queryParams['token'] ?? '';

        // Verify token
        $verificationResult = SponsorshipManager::verifyPortalToken($token);

        if (! $verificationResult['valid']) {
            // Token invalid - show error
            $data = [
                'pageTitle' => 'Access Denied',
                'valid' => false,
                'message' => $verificationResult['message'],
                'adminEmail' => \config('admin_email'),
            ];

            return $this->view->render($response, 'portal/view.twig', $data);
        }

        $sponsorEmail = $verificationResult['email'];

        // Get all sponsorships for this email
        $sponsorships = SponsorshipManager::getSponsorshipsWithDetails($sponsorEmail);

        // Group sponsorships by family
        $families = [];
        foreach ($sponsorships as $sponsorship) {
            $familyId = $sponsorship['family_id'];
            if (! isset($families[$familyId])) {
                $families[$familyId] = [
                    'family_number' => $sponsorship['family_number'],
                    'children' => [],
                ];
            }
            $families[$familyId]['children'][] = $sponsorship;
        }

        // Prepare view data
        $data = [
            'pageTitle' => 'Your Sponsorship Portal',
            'valid' => true,
            'sponsorEmail' => $sponsorEmail,
            'sponsorships' => $sponsorships,
            'families' => $families,
            'token' => $token,
            'adminEmail' => \config('admin_email'),
            'contactPhone' => \config('contact_phone', 'Contact via email'),
        ];

        return $this->view->render($response, 'portal/view.twig', $data);
    }

    /**
     * Display form to add more children to sponsorship
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function showAddChildren(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $token = $queryParams['token'] ?? '';

        // Verify token
        $verificationResult = SponsorshipManager::verifyPortalToken($token);

        if (! $verificationResult['valid']) {
            // Token invalid - redirect to portal
            return $response
                ->withHeader('Location', \baseUrl('/portal?token=' . urlencode($token)))
                ->withStatus(302);
        }

        $sponsorEmail = $verificationResult['email'];

        // Get current sponsorships (for displaying sponsor info)
        $existingSponsorships = SponsorshipManager::getSponsorshipsWithDetails($sponsorEmail);

        // Get available children (using legacy function for now)
        $availableChildren = \getChildren(['status' => 'available'], 1, 100);

        // Prepare view data
        $data = [
            'pageTitle' => 'Add More Children',
            'sponsorEmail' => $sponsorEmail,
            'existingSponsorships' => $existingSponsorships,
            'availableChildren' => $availableChildren,
            'token' => $token,
            'csrfToken' => \generateCsrfToken(),
            'errors' => [],
            'adminEmail' => \config('admin_email'),
        ];

        return $this->view->render($response, 'portal/add-children.twig', $data);
    }

    /**
     * Process adding children to existing sponsorship
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function addChildren(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $token = $queryParams['token'] ?? '';

        // Verify token
        $verificationResult = SponsorshipManager::verifyPortalToken($token);

        if (! $verificationResult['valid']) {
            // Token invalid - redirect to portal
            return $response
                ->withHeader('Location', \baseUrl('/portal?token=' . urlencode($token)))
                ->withStatus(302);
        }

        $sponsorEmail = $verificationResult['email'];
        $parsedBody = $request->getParsedBody();
        $errors = [];

        // Verify CSRF token
        if (! \verifyCsrfToken($parsedBody['csrf_token'] ?? '')) {
            $errors[] = 'Security token invalid. Please try again.';
        } else {
            $selectedChildIds = $parsedBody['child_ids'] ?? [];

            if (empty($selectedChildIds)) {
                $errors[] = 'Please select at least one child.';
            } else {
                // Get existing sponsorship info
                $existingSponsorships = SponsorshipManager::getSponsorshipsWithDetails($sponsorEmail);

                if (empty($existingSponsorships)) {
                    $errors[] = 'No existing sponsorship found.';
                } else {
                    // Create sponsor data from first existing sponsorship
                    $firstSponsorship = $existingSponsorships[0];
                    $sponsorData = [
                        'name' => $firstSponsorship['sponsor_name'],
                        'email' => $firstSponsorship['sponsor_email'],
                        'phone' => $firstSponsorship['sponsor_phone'] ?? '',
                        'address' => $firstSponsorship['sponsor_address'] ?? '',
                        'gift_preference' => $firstSponsorship['gift_preference'],
                        'message' => 'Additional children added to existing sponsorship',
                    ];

                    // Add children to sponsorship
                    $result = SponsorshipManager::addChildrenToSponsorship(
                        array_map('intval', $selectedChildIds),
                        $sponsorData,
                        $sponsorEmail
                    );

                    if ($result['success']) {
                        // Success! Redirect to portal
                        $_SESSION['flash_message'] = $result['message'];
                        $_SESSION['flash_type'] = 'success';

                        return $response
                            ->withHeader('Location', \baseUrl('/portal?token=' . urlencode($token)))
                            ->withStatus(302);
                    }

                    // Error occurred
                    $errors[] = $result['message'];
                }
            }
        }

        // Re-display form with errors
        $availableChildren = \getChildren(['status' => 'available'], 1, 100);
        $existingSponsorships = SponsorshipManager::getSponsorshipsWithDetails($sponsorEmail);

        $data = [
            'pageTitle' => 'Add More Children',
            'sponsorEmail' => $sponsorEmail,
            'existingSponsorships' => $existingSponsorships,
            'availableChildren' => $availableChildren,
            'token' => $token,
            'csrfToken' => \generateCsrfToken(),
            'errors' => $errors,
            'adminEmail' => \config('admin_email'),
        ];

        return $this->view->render($response, 'portal/add-children.twig', $data);
    }
}
