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

    /**
     * Display sponsorship form for a single child
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @param array<string, mixed> $args Route arguments
     * @return Response
     */
    public function showSponsorForm(Request $request, Response $response, array $args): Response
    {
        $childId = (int) $args['id'];

        // Check if child is available
        $availability = SponsorshipManager::isChildAvailable($childId);
        $child = $availability['child'];

        if (! $child) {
            // Child not found - redirect to children list
            $_SESSION['flash_message'] = 'Child not found.';
            $_SESSION['flash_type'] = 'error';
            return $response
                ->withHeader('Location', \baseUrl('/children'))
                ->withStatus(302);
        }

        if (! $availability['available']) {
            // Child not available - redirect with message
            $_SESSION['flash_message'] = $availability['reason'];
            $_SESSION['flash_type'] = 'error';
            return $response
                ->withHeader('Location', \baseUrl('/children'))
                ->withStatus(302);
        }

        // Prepare view data
        $data = [
            'pageTitle' => 'Sponsor ' . $child['display_id'],
            'children' => [$child],
            'formData' => [],
            'errors' => [],
            'csrfToken' => \generateCsrfToken(),
            'submitUrl' => \baseUrl('/sponsor/child/' . $childId),
            'isFamily' => false,
            'adminEmail' => \config('admin_email'),
        ];

        return $this->view->render($response, 'sponsor/form.twig', $data);
    }

    /**
     * Process sponsorship form submission for a single child
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @param array<string, mixed> $args Route arguments
     * @return Response
     */
    public function submitSponsorship(Request $request, Response $response, array $args): Response
    {
        $childId = (int) $args['id'];
        $parsedBody = $request->getParsedBody();
        $errors = [];
        $formData = [];

        // Verify CSRF token
        if (! \verifyCsrfToken($parsedBody['csrf_token'] ?? '')) {
            $errors[] = 'Security token invalid. Please try again.';
        } else {
            // Collect form data
            $formData = [
                'name' => $parsedBody['sponsor_name'] ?? '',
                'email' => $parsedBody['sponsor_email'] ?? '',
                'phone' => $parsedBody['sponsor_phone'] ?? '',
                'address' => $parsedBody['sponsor_address'] ?? '',
                'gift_preference' => $parsedBody['gift_preference'] ?? 'shopping',
                'message' => $parsedBody['special_message'] ?? '',
            ];

            // Attempt to create sponsorship
            $result = SponsorshipManager::createSponsorshipRequest($childId, $formData);

            if ($result['success']) {
                // Success! Get the sponsorship details for success page
                $sponsorshipId = $result['sponsorship_id'];
                $sponsorships = SponsorshipManager::getSponsorshipsWithDetails($formData['email']);

                // Store in session for success page
                $_SESSION['sponsorship_success'] = $sponsorships;

                // Redirect to success page
                return $response
                    ->withHeader('Location', \baseUrl('/sponsorship/success'))
                    ->withStatus(302);
            }

            // Error occurred
            $errors[] = $result['message'];
        }

        // Re-display form with errors
        $availability = SponsorshipManager::isChildAvailable($childId);
        $child = $availability['child'];

        if (! $child) {
            $_SESSION['flash_message'] = 'Child not found.';
            $_SESSION['flash_type'] = 'error';
            return $response
                ->withHeader('Location', \baseUrl('/children'))
                ->withStatus(302);
        }

        $data = [
            'pageTitle' => 'Sponsor ' . $child['display_id'],
            'children' => [$child],
            'formData' => $formData,
            'errors' => $errors,
            'csrfToken' => \generateCsrfToken(),
            'submitUrl' => \baseUrl('/sponsor/child/' . $childId),
            'isFamily' => false,
            'adminEmail' => \config('admin_email'),
        ];

        return $this->view->render($response, 'sponsor/form.twig', $data);
    }

    /**
     * Display sponsorship success page
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function showSuccess(Request $request, Response $response): Response
    {
        // Get sponsorships from session
        $sponsorships = $_SESSION['sponsorship_success'] ?? [];

        if ($sponsorships === []) {
            // No sponsorships in session - redirect to children list
            return $response
                ->withHeader('Location', \baseUrl('/children'))
                ->withStatus(302);
        }

        // Clear from session
        unset($_SESSION['sponsorship_success']);

        // Prepare view data
        $data = [
            'sponsorships' => $sponsorships,
            'adminEmail' => \config('admin_email'),
        ];

        return $this->view->render($response, 'sponsor/success.twig', $data);
    }

    /**
     * Display family sponsorship form
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @param array<string, mixed> $args Route arguments
     * @return Response
     */
    public function showFamilyForm(Request $request, Response $response, array $args): Response
    {
        $familyId = (int) $args['id'];

        // Get all children in family
        $allChildren = \CFK\Repository\ChildRepository::getByFamily($familyId);

        if ($allChildren === []) {
            $_SESSION['flash_message'] = 'Family not found.';
            $_SESSION['flash_type'] = 'error';
            return $response
                ->withHeader('Location', \baseUrl('/children'))
                ->withStatus(302);
        }

        // Filter for available children only
        $availableChildren = array_filter($allChildren, fn($child) => $child['status'] === 'available');

        if ($availableChildren === []) {
            $_SESSION['flash_message'] = 'No available children in this family to sponsor.';
            $_SESSION['flash_type'] = 'error';
            return $response
                ->withHeader('Location', \baseUrl('/children'))
                ->withStatus(302);
        }

        // Reset array keys after filtering
        $availableChildren = array_values($availableChildren);

        // Get family number for page title
        $familyNumber = $availableChildren[0]['family_number'];

        // Prepare view data
        $data = [
            'pageTitle' => 'Sponsor Family ' . $familyNumber,
            'children' => $availableChildren,
            'formData' => [],
            'errors' => [],
            'csrfToken' => \generateCsrfToken(),
            'submitUrl' => \baseUrl('/sponsor/family/' . $familyId),
            'isFamily' => true,
            'adminEmail' => \config('admin_email'),
        ];

        return $this->view->render($response, 'sponsor/form.twig', $data);
    }

    /**
     * Process family sponsorship form submission
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @param array<string, mixed> $args Route arguments
     * @return Response
     */
    public function submitFamilySponsorship(Request $request, Response $response, array $args): Response
    {
        $familyId = (int) $args['id'];
        $parsedBody = $request->getParsedBody();
        $errors = [];
        $formData = [];

        // Verify CSRF token
        if (! \verifyCsrfToken($parsedBody['csrf_token'] ?? '')) {
            $errors[] = 'Security token invalid. Please try again.';
        } else {
            // Collect form data
            $formData = [
                'name' => $parsedBody['sponsor_name'] ?? '',
                'email' => $parsedBody['sponsor_email'] ?? '',
                'phone' => $parsedBody['sponsor_phone'] ?? '',
                'address' => $parsedBody['sponsor_address'] ?? '',
                'gift_preference' => $parsedBody['gift_preference'] ?? 'shopping',
                'message' => $parsedBody['special_message'] ?? '',
            ];

            // Get all available children in family
            $allChildren = \CFK\Repository\ChildRepository::getByFamily($familyId);
            $availableChildren = array_filter($allChildren, fn($child) => $child['status'] === 'available');

            if ($availableChildren === []) {
                $errors[] = 'No available children in this family to sponsor.';
            } else {
                // Attempt to create sponsorships for all children
                $successfulSponsorships = [];
                $failedChildren = [];
                $allSuccessful = true;

                foreach ($availableChildren as $child) {
                    $result = SponsorshipManager::createSponsorshipRequest((int) $child['id'], $formData);

                    if ($result['success']) {
                        $successfulSponsorships[] = $child['display_id'];
                    } else {
                        $failedChildren[] = [
                            'id' => $child['display_id'],
                            'error' => $result['message']
                        ];
                        $allSuccessful = false;
                    }
                }

                if ($allSuccessful && count($successfulSponsorships) > 0) {
                    // All succeeded! Get sponsorship details for success page
                    $sponsorships = SponsorshipManager::getSponsorshipsWithDetails($formData['email']);

                    // Store in session for success page
                    $_SESSION['sponsorship_success'] = $sponsorships;

                    // Redirect to success page
                    return $response
                        ->withHeader('Location', \baseUrl('/sponsorship/success'))
                        ->withStatus(302);
                }

                // Some or all failed
                if (count($successfulSponsorships) > 0) {
                    $errors[] = 'Successfully sponsored: ' . implode(', ', $successfulSponsorships);
                }

                foreach ($failedChildren as $failed) {
                    $errors[] = "Failed to sponsor {$failed['id']}: {$failed['error']}";
                }
            }
        }

        // Re-display form with errors
        $allChildren = \CFK\Repository\ChildRepository::getByFamily($familyId);
        $availableChildren = array_filter($allChildren, fn($child) => $child['status'] === 'available');
        $availableChildren = array_values($availableChildren);

        if ($availableChildren === []) {
            $_SESSION['flash_message'] = 'No available children in this family to sponsor.';
            $_SESSION['flash_type'] = 'error';
            return $response
                ->withHeader('Location', \baseUrl('/children'))
                ->withStatus(302);
        }

        $familyNumber = $availableChildren[0]['family_number'];

        $data = [
            'pageTitle' => 'Sponsor Family ' . $familyNumber,
            'children' => $availableChildren,
            'formData' => $formData,
            'errors' => $errors,
            'csrfToken' => \generateCsrfToken(),
            'submitUrl' => \baseUrl('/sponsor/family/' . $familyId),
            'isFamily' => true,
            'adminEmail' => \config('admin_email'),
        ];

        return $this->view->render($response, 'sponsor/form.twig', $data);
    }
}
