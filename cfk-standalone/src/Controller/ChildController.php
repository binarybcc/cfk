<?php

declare(strict_types=1);

namespace CFK\Controller;

use CFK\Repository\ChildRepository;
use CFK\Sponsorship\Manager as SponsorshipManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Child Controller
 *
 * Handles child profile viewing and related actions.
 */
class ChildController
{
    private ChildRepository $childRepo;
    private Twig $view;

    public function __construct(ChildRepository $childRepo, Twig $view)
    {
        $this->childRepo = $childRepo;
        $this->view = $view;
    }

    /**
     * Display individual child profile
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @param array $args Route parameters
     * @return Response
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $childId = (int) ($args['id'] ?? 0);

        // Validate child ID
        if ($childId <= 0) {
            return $this->notFound($response);
        }

        // Get child information
        $child = $this->childRepo->findById($childId);

        if (!$child) {
            return $this->notFound($response);
        }

        // Get siblings
        $siblings = $this->childRepo->findFamilyMembers(
            (int) $child['family_id'],
            $childId
        );

        // Check availability using existing SponsorshipManager
        $availability = SponsorshipManager::isChildAvailable($childId);

        // Count available siblings
        $availableSiblings = array_filter(
            $siblings,
            fn($s): bool => $s['status'] === 'available'
        );

        // Prepare view data
        $data = [
            'child' => $child,
            'siblings' => $siblings,
            'availability' => $availability,
            'isAvailable' => $availability['available'],
            'availableSiblingsCount' => count($availableSiblings),
            'pageTitle' => 'Family ' . $child['display_id'] . ' - Child Profile',
        ];

        // Render template
        return $this->view->render($response, 'children/show.twig', $data);
    }

    /**
     * Return 404 response
     *
     * @param Response $response
     * @return Response
     */
    private function notFound(Response $response): Response
    {
        return $this->view->render(
            $response->withStatus(404),
            'errors/404.twig',
            ['message' => 'Child not found']
        );
    }
}
