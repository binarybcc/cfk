<?php

declare(strict_types=1);

namespace CFK\Controller;

use CFK\Child\Manager as ChildManager;
use Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Admin Child Controller
 *
 * Handles admin CRUD operations for children.
 * Migrated from admin/manage_children.php (Week 8 Part 2)
 */
class AdminChildController
{
    private Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Display children list with filters and pagination
     *
     * GET /admin/children
     */
    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();

        // Get filter parameters
        $statusFilter = $queryParams['status'] ?? 'all';
        $familyFilter = $queryParams['family'] ?? 'all';
        $ageFilter = $queryParams['age'] ?? 'all';
        $searchQuery = $queryParams['search'] ?? '';
        $page = max(1, sanitizeInt($queryParams['page'] ?? 1));

        // Per-page selector for admin
        $perPageOptions = [25, 50, 100];
        $perPage = isset($queryParams['per_page']) && in_array((int) $queryParams['per_page'], $perPageOptions, true)
            ? (int) $queryParams['per_page']
            : 25;

        // Build query based on filters
        $whereConditions = [];
        $params = [];

        if ($statusFilter !== 'all') {
            $whereConditions[] = 'c.status = ?';
            $params[] = $statusFilter;
        }

        if ($familyFilter !== 'all') {
            $whereConditions[] = 'f.family_number = ?';
            $params[] = $familyFilter;
        }

        if ($ageFilter !== 'all') {
            switch ($ageFilter) {
                case 'birth-4':
                    $whereConditions[] = 'c.age_months BETWEEN 0 AND 48';

                    break;
                case 'elementary':
                    $whereConditions[] = 'c.age_months BETWEEN 60 AND 120';

                    break;
                case 'middle':
                    $whereConditions[] = 'c.age_months BETWEEN 132 AND 156';

                    break;
                case 'high':
                    $whereConditions[] = 'c.age_months BETWEEN 168 AND 216';

                    break;
            }
        }

        if (! empty($searchQuery)) {
            $whereConditions[] = "(CONCAT(f.family_number, c.child_letter) LIKE ? OR c.interests LIKE ? OR c.wishes LIKE ? OR f.family_number LIKE ?)";
            $searchTerm = '%' . $searchQuery . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $whereClause = $whereConditions === [] ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM children c JOIN families f ON c.family_id = f.id {$whereClause}";
        $totalCount = Database::fetchRow($countQuery, $params)['total'] ?? 0;
        $totalPages = (int) ceil($totalCount / $perPage);
        $offset = ($page - 1) * $perPage;

        // Get children data
        $children = Database::fetchAll("
            SELECT c.*, f.family_number,
                   CONCAT(f.family_number, c.child_letter) as display_id,
                   (SELECT COUNT(*) FROM sponsorships s WHERE s.child_id = c.id AND s.status IN ('pending', 'confirmed')) as sponsorship_count
            FROM children c
            JOIN families f ON c.family_id = f.id
            {$whereClause}
            ORDER BY CAST(f.family_number AS UNSIGNED) ASC, c.child_letter ASC
            LIMIT {$perPage} OFFSET {$offset}
        ", $params);

        // Get families for dropdowns
        $families = Database::fetchAll("SELECT id, family_number FROM families ORDER BY CAST(family_number AS UNSIGNED) ASC");

        // Get unique family numbers for filter dropdown
        $familyNumbers = Database::fetchAll("SELECT DISTINCT family_number FROM families ORDER BY CAST(family_number AS UNSIGNED) ASC");

        // Get flash message from session
        $flashMessage = getMessage();

        return $this->view->render($response, 'admin/children/index.twig', [
            'pageTitle' => 'Manage Children',
            'children' => $children,
            'families' => $families,
            'familyNumbers' => $familyNumbers,
            'filters' => [
                'status' => $statusFilter,
                'family' => $familyFilter,
                'age' => $ageFilter,
                'search' => $searchQuery,
                'perPage' => $perPage,
            ],
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalCount' => $totalCount,
                'perPage' => $perPage,
                'perPageOptions' => $perPageOptions,
            ],
            'csrfToken' => generateCsrfToken(),
            'flash_message' => $flashMessage['text'] ?? null,
            'flash_type' => $flashMessage['type'] ?? 'info',
        ]);
    }

    /**
     * Create a new child
     *
     * POST /admin/children
     */
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/slim.php/admin/children'))
                ->withStatus(302);
        }

        $result = ChildManager::addChild($data);

        setMessage($result['message'], $result['success'] ? 'success' : 'error');

        return $response
            ->withHeader('Location', baseUrl('/slim.php/admin/children'))
            ->withStatus(302);
    }

    /**
     * Update an existing child
     *
     * POST /admin/children/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $childId = (int) $args['id'];
        $data = $request->getParsedBody();
        $data['child_id'] = $childId;

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/slim.php/admin/children'))
                ->withStatus(302);
        }

        $result = ChildManager::editChild($data);

        setMessage($result['message'], $result['success'] ? 'success' : 'error');

        return $response
            ->withHeader('Location', baseUrl('/slim.php/admin/children'))
            ->withStatus(302);
    }

    /**
     * Delete a child
     *
     * POST /admin/children/{id}/delete
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $childId = (int) $args['id'];
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/slim.php/admin/children'))
                ->withStatus(302);
        }

        $result = ChildManager::deleteChild($childId);

        setMessage($result['message'], $result['success'] ? 'success' : 'error');

        return $response
            ->withHeader('Location', baseUrl('/slim.php/admin/children'))
            ->withStatus(302);
    }

    /**
     * Toggle child status (AJAX endpoint)
     *
     * POST /admin/children/{id}/toggle-status
     */
    public function toggleStatus(Request $request, Response $response, array $args): Response
    {
        $childId = (int) $args['id'];
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid security token',
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        $result = ChildManager::toggleChildStatus($childId);

        $response->getBody()->write(json_encode($result));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get child data for editing (AJAX endpoint)
     *
     * GET /admin/children/{id}/data
     */
    public function getData(Request $request, Response $response, array $args): Response
    {
        $childId = (int) $args['id'];

        $child = ChildManager::getChildById($childId);

        if ($child) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'child' => $child,
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Child not found',
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404);
    }
}
