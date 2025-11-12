<?php

declare(strict_types=1);

namespace CFK\Controller;

use CFK\Sponsorship\Manager as SponsorshipManager;
use Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Admin Sponsorship Controller
 *
 * Handles admin management of sponsorships.
 * Migrated from admin/manage_sponsorships.php (Week 8 Part 2 Phase 3)
 */
class AdminSponsorshipController
{
    private Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Display sponsorships list with filters and actions
     *
     * GET /admin/sponsorships
     */
    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();

        // Get filter parameters
        $statusFilter = $queryParams['status'] ?? 'all';
        $sortBy = $queryParams['sort'] ?? 'newest';
        $searchQuery = $queryParams['search'] ?? '';
        $showCancelled = isset($queryParams['show_cancelled']) && $queryParams['show_cancelled'] === '1';

        // Build query based on filters
        $whereConditions = [];
        $params = [];

        // Status filter
        if ($statusFilter !== 'all') {
            $whereConditions[] = 's.status = ?';
            $params[] = $statusFilter;
        }

        // Hide cancelled by default unless toggled on
        if (! $showCancelled) {
            $whereConditions[] = "s.status != 'cancelled'";
        }

        // Search functionality
        if ($searchQuery !== '') {
            $whereConditions[] = '(s.sponsor_name LIKE ? OR s.sponsor_email LIKE ? OR CONCAT(f.family_number, c.child_letter) LIKE ?)';
            $searchParam = '%' . $searchQuery . '%';
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        }

        $whereClause = $whereConditions === [] ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

        // Sort options
        $orderBy = match ($sortBy) {
            'oldest' => 'ORDER BY s.request_date ASC',
            'name' => 'ORDER BY s.sponsor_name ASC',
            'child' => 'ORDER BY CAST(f.family_number AS UNSIGNED) ASC, c.child_letter ASC',
            default => 'ORDER BY s.request_date DESC'
        };

        // Get sponsorships
        $sponsorships = Database::fetchAll("
            SELECT s.*,
                   c.id as child_id,
                   CONCAT(f.family_number, c.child_letter) as child_name,
                   c.age_months, c.grade, c.gender, c.status as child_status,
                   c.interests, c.wishes, c.special_needs,
                   c.shirt_size, c.pant_size, c.jacket_size, c.shoe_size,
                   CONCAT(f.family_number, c.child_letter) as child_display_id,
                   f.family_number
            FROM sponsorships s
            JOIN children c ON s.child_id = c.id
            JOIN families f ON c.family_id = f.id
            {$whereClause}
            {$orderBy}
        ", $params);

        // Get statistics
        $stats = SponsorshipManager::getStats();
        $childrenNeedingAttention = SponsorshipManager::getChildrenNeedingAttention();

        // Get flash message from session
        $flashMessage = getMessage();

        return $this->view->render($response, 'admin/sponsorships/index.twig', [
            'pageTitle' => 'Manage Sponsorships',
            'sponsorships' => $sponsorships,
            'stats' => $stats,
            'childrenNeedingAttention' => $childrenNeedingAttention,
            'filters' => [
                'status' => $statusFilter,
                'sort' => $sortBy,
                'search' => $searchQuery,
                'showCancelled' => $showCancelled,
            ],
            'csrfToken' => generateCsrfToken(),
            'flash_message' => $flashMessage['text'] ?? null,
            'flash_type' => $flashMessage['type'] ?? 'info',
        ]);
    }

    /**
     * Mark sponsorship as logged
     *
     * POST /admin/sponsorships/{id}/log
     */
    public function markLogged(Request $request, Response $response, array $args): Response
    {
        $sponsorshipId = (int) $args['id'];
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/sponsorships'))
                ->withStatus(302);
        }

        $result = SponsorshipManager::logSponsorship($sponsorshipId);

        setMessage($result['message'], $result['success'] ? 'success' : 'error');

        return $response
            ->withHeader('Location', baseUrl('/admin/sponsorships'))
            ->withStatus(302);
    }

    /**
     * Unlog sponsorship (revert to confirmed)
     *
     * POST /admin/sponsorships/{id}/unlog
     */
    public function unlog(Request $request, Response $response, array $args): Response
    {
        $sponsorshipId = (int) $args['id'];
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/sponsorships'))
                ->withStatus(302);
        }

        $result = SponsorshipManager::unlogSponsorship($sponsorshipId);

        setMessage($result['message'], $result['success'] ? 'success' : 'error');

        return $response
            ->withHeader('Location', baseUrl('/admin/sponsorships'))
            ->withStatus(302);
    }

    /**
     * Mark sponsorship as complete
     *
     * POST /admin/sponsorships/{id}/complete
     */
    public function markComplete(Request $request, Response $response, array $args): Response
    {
        $sponsorshipId = (int) $args['id'];
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/sponsorships'))
                ->withStatus(302);
        }

        $result = SponsorshipManager::completeSponsorship($sponsorshipId);

        setMessage($result['message'], $result['success'] ? 'success' : 'error');

        return $response
            ->withHeader('Location', baseUrl('/admin/sponsorships'))
            ->withStatus(302);
    }

    /**
     * Cancel sponsorship
     *
     * POST /admin/sponsorships/{id}/cancel
     */
    public function cancel(Request $request, Response $response, array $args): Response
    {
        $sponsorshipId = (int) $args['id'];
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/sponsorships'))
                ->withStatus(302);
        }

        $reason = sanitizeString($data['reason'] ?? 'Cancelled by admin');
        $result = SponsorshipManager::cancelSponsorship($sponsorshipId, $reason);

        setMessage($result['message'], $result['success'] ? 'success' : 'error');

        return $response
            ->withHeader('Location', baseUrl('/admin/sponsorships'))
            ->withStatus(302);
    }

    /**
     * Bulk actions (log, complete, export)
     *
     * POST /admin/sponsorships/bulk
     */
    public function bulkAction(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/sponsorships'))
                ->withStatus(302);
        }

        $bulkAction = $data['bulk_action'] ?? '';
        $sponsorshipIds = array_map('intval', (array) ($data['sponsorship_ids'] ?? []));

        if (empty($sponsorshipIds)) {
            setMessage('No sponsorships selected', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/sponsorships'))
                ->withStatus(302);
        }

        // Handle CSV export separately
        if ($bulkAction === 'export') {
            return $this->exportCsv($response, $sponsorshipIds);
        }

        // Process bulk actions
        $successCount = 0;
        $failCount = 0;

        foreach ($sponsorshipIds as $sponsorshipId) {
            $result = match ($bulkAction) {
                'log' => SponsorshipManager::logSponsorship($sponsorshipId),
                'complete' => SponsorshipManager::completeSponsorship($sponsorshipId),
                default => ['success' => false, 'message' => 'Invalid action']
            };

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        $message = sprintf(
            'Bulk action completed. Success: %d, Failed: %d',
            $successCount,
            $failCount
        );
        $messageType = $failCount > 0 ? 'warning' : 'success';

        setMessage($message, $messageType);

        return $response
            ->withHeader('Location', baseUrl('/admin/sponsorships'))
            ->withStatus(302);
    }

    /**
     * Export sponsorships to CSV
     */
    private function exportCsv(Response $response, array $sponsorshipIds): Response
    {
        $exportData = Database::fetchAll("
            SELECT s.*,
                   CONCAT(f.family_number, c.child_letter) as child_display_id,
                   c.age_months, c.grade, c.gender
            FROM sponsorships s
            JOIN children c ON s.child_id = c.id
            JOIN families f ON c.family_id = f.id
            WHERE s.id IN (" . implode(',', array_fill(0, count($sponsorshipIds), '?')) . ')
        ', $sponsorshipIds);

        $csv = "Child ID,Child Age,Sponsor Name,Sponsor Email,Sponsor Phone,Request Date,Status\n";

        foreach ($exportData as $row) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $row['child_display_id'],
                formatAge((int) $row['age_months']),
                $row['sponsor_name'],
                $row['sponsor_email'],
                $row['sponsor_phone'] ?? '',
                $row['request_date'],
                $row['status']
            );
        }

        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="sponsorships-' . date('Y-m-d') . '.csv"');
    }
}
