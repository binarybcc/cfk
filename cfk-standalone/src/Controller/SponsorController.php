<?php

declare(strict_types=1);

namespace CFK\Controller;

use CFK\Sponsorship\Manager as SponsorshipManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Sponsor Controller
 *
 * Handles sponsor portal access and email lookup functionality.
 */
class SponsorController
{
    private Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Display sponsor lookup form
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function showLookupForm(Request $request, Response $response): Response
    {
        $data = [
            'pageTitle' => 'Access Your Sponsorships',
            'errors' => [],
            'emailSent' => false,
            'adminEmail' => \config('admin_email'),
            'csrfToken' => \generateCsrfToken(),
            'submittedEmail' => '',
        ];

        return $this->view->render($response, 'sponsor/lookup.twig', $data);
    }

    /**
     * Process sponsor lookup form submission
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function processLookup(Request $request, Response $response): Response
    {
        $errors = [];
        $emailSent = false;
        $submittedEmail = '';

        // Get POST data
        $parsedBody = $request->getParsedBody();

        // Verify CSRF token
        if (! \verifyCsrfToken($parsedBody['csrf_token'] ?? '')) {
            $errors[] = 'Security token invalid. Please try again.';
        } else {
            $email = \sanitizeEmail($parsedBody['sponsor_email'] ?? '');
            $submittedEmail = $email;

            // Validate email
            if (empty($email)) {
                $errors[] = 'Please enter your email address.';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            } else {
                // Check if email has any sponsorships
                $sponsorships = SponsorshipManager::getSponsorshipsByEmail($email);

                if ($sponsorships === []) {
                    $errors[] = 'No sponsorships found for this email address. Please check your email or contact us for assistance.';
                } else {
                    // Send email with sponsorship details (no portal, just details in email)
                    $success = \CFK\Email\Manager::sendMultiChildSponsorshipEmail($email, $sponsorships);

                    if ($success) {
                        $emailSent = true;
                    } else {
                        $errors[] = 'Failed to send email. Please try again or contact us for assistance.';
                    }
                }
            }
        }

        // Prepare view data
        $data = [
            'pageTitle' => 'Access Your Sponsorships',
            'errors' => $errors,
            'emailSent' => $emailSent,
            'adminEmail' => \config('admin_email'),
            'csrfToken' => \generateCsrfToken(),
            'submittedEmail' => $submittedEmail,
        ];

        return $this->view->render($response, 'sponsor/lookup.twig', $data);
    }
}
