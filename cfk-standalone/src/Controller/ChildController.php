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
     * Display children list with filters and pagination
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @return Response
     */
    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();

        // Check if viewing a specific family
        $viewingFamily = !empty($queryParams['family_id']);
        $familyId = $viewingFamily ? (int)$queryParams['family_id'] : null;

        if ($viewingFamily) {
            return $this->familyView($request, $response, $familyId);
        }

        // Normal browsing mode with filters
        $filters = $this->buildFilters($queryParams);

        // Pagination
        $currentPage = max(1, (int)($queryParams['p'] ?? 1));
        $perPageOptions = [12, 24, 48];
        $perPage = isset($queryParams['per_page']) && in_array((int)$queryParams['per_page'], $perPageOptions, true)
            ? (int)$queryParams['per_page']
            : 12;

        // Get children and count
        $children = $this->childRepo->findAll($filters, $currentPage, $perPage);
        $totalCount = $this->childRepo->count($filters);
        $totalPages = (int)ceil($totalCount / $perPage);

        // Eager load family members to prevent N+1 queries
        $siblingsByFamily = $this->childRepo->eagerLoadFamilyMembers($children);

        // Build query string for pagination
        $paginationParams = array_filter([
            'search' => $filters['search'] ?? null,
            'age_category' => $filters['age_category'] ?? null,
            'gender' => $filters['gender'] ?? null,
            'per_page' => $perPage !== 12 ? $perPage : null,
        ]);

        // Prepare view data
        $data = [
            'children' => $children,
            'siblingsByFamily' => $siblingsByFamily,
            'totalCount' => $totalCount,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'filters' => $filters,
            'paginationParams' => $paginationParams,
            'pageTitle' => 'Children Needing Christmas Sponsorship',
            'viewingFamily' => false,
        ];

        return $this->view->render($response, 'children/index.twig', $data);
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
     * Family view mode - show only children in one family
     *
     * @param Request $request
     * @param Response $response
     * @param int $familyId
     * @return Response
     */
    private function familyView(Request $request, Response $response, int $familyId): Response
    {
        $filters = ['family_id' => $familyId];
        $children = $this->childRepo->findAll($filters, 1, 999);

        if (empty($children)) {
            return $this->notFound($response);
        }

        $familyInfo = $this->childRepo->findFamilyById($familyId);
        $siblingsByFamily = [$familyId => $children];

        $data = [
            'children' => $children,
            'siblingsByFamily' => $siblingsByFamily,
            'familyInfo' => $familyInfo,
            'totalCount' => count($children),
            'currentPage' => 1,
            'totalPages' => 1,
            'perPage' => 12,
            'perPageOptions' => [12, 24, 48],
            'filters' => $filters,
            'paginationParams' => [],
            'pageTitle' => 'Family ' . ($familyInfo['family_number'] ?? ''),
            'viewingFamily' => true,
        ];

        return $this->view->render($response, 'children/index.twig', $data);
    }

    /**
     * Build filters from query parameters
     *
     * @param array $queryParams
     * @return array
     */
    private function buildFilters(array $queryParams): array
    {
        $filters = [];

        if (!empty($queryParams['search'])) {
            $filters['search'] = trim($queryParams['search']);
        }

        if (!empty($queryParams['age_category'])) {
            $filters['age_category'] = $queryParams['age_category'];
        }

        if (!empty($queryParams['gender'])) {
            $filters['gender'] = $queryParams['gender'];
        }

        return $filters;
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
