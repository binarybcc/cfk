<?php

declare(strict_types=1);

namespace CFK\Controller;

use CFK\Repository\AdminRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Admin Controller
 *
 * Handles admin dashboard and reporting functionality.
 */
class AdminController
{
    private AdminRepository $adminRepo;
    private Twig $view;

    public function __construct(AdminRepository $adminRepo, Twig $view)
    {
        $this->adminRepo = $adminRepo;
        $this->view = $view;
    }

    /**
     * Display admin dashboard
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dashboard(Request $request, Response $response): Response
    {
        // Get dashboard statistics
        $stats = $this->adminRepo->getDashboardStats();

        // Get recent sponsorships
        $recentSponsorships = $this->adminRepo->getRecentSponsorships(10);

        // Get children needing attention
        $childrenNeedingAttention = $this->adminRepo->getChildrenNeedingAttention();

        // Prepare view data
        $data = [
            'stats' => $stats,
            'recentSponsorships' => $recentSponsorships,
            'childrenNeedingAttention' => $childrenNeedingAttention,
            'pageTitle' => 'Admin Dashboard',
        ];

        return $this->view->render($response, 'admin/dashboard.twig', $data);
    }

    /**
     * Display reports page
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function reports(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $reportType = $queryParams['type'] ?? 'summary';

        $data = [
            'reportType' => $reportType,
            'pageTitle' => 'Reports',
        ];

        // Build report data based on type
        switch ($reportType) {
            case 'sponsorships':
                $filters = $this->buildReportFilters($queryParams);
                $data['sponsorships'] = $this->adminRepo->getAllSponsorships($filters);
                $data['filters'] = $filters;

                break;

            case 'children':
                $data['childrenStats'] = $this->adminRepo->getChildrenStats();

                break;

            case 'summary':
            default:
                $data['sponsorshipSummary'] = $this->adminRepo->getSponsorshipSummary();
                $data['childrenStats'] = $this->adminRepo->getChildrenStats();
                $data['dashboardStats'] = $this->adminRepo->getDashboardStats();

                break;
        }

        return $this->view->render($response, 'admin/reports.twig', $data);
    }

    /**
     * Get sponsor data (AJAX endpoint)
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getSponsor(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $email = $queryParams['email'] ?? '';

        if (empty($email)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Email required',
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $sponsor = $this->adminRepo->getSponsorByEmail($email);

        if ($sponsor) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'sponsor' => $sponsor,
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Sponsor not found',
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404);
    }

    /**
     * Update sponsor information (POST endpoint)
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function updateSponsor(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $oldEmail = $data['sponsor_email'] ?? '';
        $newData = [
            'sponsor_name' => $data['sponsor_name'] ?? '',
            'sponsor_email' => $data['new_email'] ?? '',
            'sponsor_phone' => $data['sponsor_phone'] ?? '',
            'sponsor_address' => $data['sponsor_address'] ?? '',
        ];

        // Validate email
        if (! filter_var($newData['sponsor_email'], FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid email address',
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        try {
            $rowsAffected = $this->adminRepo->updateSponsor($oldEmail, $newData);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Sponsor information updated successfully',
                'rows_affected' => $rowsAffected,
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update sponsor information',
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Build filters for reports from query parameters
     *
     * @param array $queryParams
     * @return array
     */
    private function buildReportFilters(array $queryParams): array
    {
        $filters = [];

        if (! empty($queryParams['status'])) {
            $filters['status'] = $queryParams['status'];
        }

        if (! empty($queryParams['date_from'])) {
            $filters['date_from'] = $queryParams['date_from'];
        }

        if (! empty($queryParams['date_to'])) {
            $filters['date_to'] = $queryParams['date_to'];
        }

        return $filters;
    }
}
