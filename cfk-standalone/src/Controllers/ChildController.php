<?php

declare(strict_types=1);

namespace CFK\Controllers;

use CFK\Repositories\ChildRepository;
use CFK\Services\SponsorshipService;

/**
 * Controller for child-related operations
 */
class ChildController
{
    public function __construct(
        private ChildRepository $childRepository,
        private SponsorshipService $sponsorshipService
    ) {}

    /**
     * Display children listing page
     */
    public function index(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $limit = 12;
        $offset = ($page - 1) * $limit;
        
        // Get filters from request
        $filters = [
            'gender' => $_GET['gender'] ?? '',
            'age_min' => $_GET['age_min'] ?? '',
            'age_max' => $_GET['age_max'] ?? '',
            'grade' => $_GET['grade'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        // Remove empty filters
        $filters = array_filter($filters);
        
        // Get children and total count
        $children = $this->childRepository->findAvailable($limit, $offset, $filters);
        $totalChildren = $this->childRepository->countAvailable($filters);
        $totalPages = ceil($totalChildren / $limit);
        
        // Prepare data for view
        $data = [
            'children' => $children,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalChildren' => $totalChildren,
            'filters' => $filters,
            'hasFilters' => !empty($filters)
        ];
        
        $this->render('children/index', $data);
    }

    /**
     * Show individual child details
     */
    public function show(): void
    {
        $childId = (int) ($_GET['id'] ?? 0);
        
        if (!$childId) {
            $this->redirectWithError('/', 'Invalid child ID');
            return;
        }
        
        $child = $this->childRepository->findById($childId);
        
        if (!$child) {
            $this->redirectWithError('/', 'Child not found');
            return;
        }
        
        // Get siblings
        $siblings = $this->childRepository->getSiblings($childId);
        
        // Get available sponsorship options
        $sponsorshipOptions = $this->sponsorshipService->getAvailableOptions($childId);
        
        // Get family statistics
        $familyStats = $this->childRepository->getFamilyStats($child->getBaseFamilyId());
        
        $data = [
            'child' => $child,
            'siblings' => $siblings,
            'sponsorshipOptions' => $sponsorshipOptions,
            'familyStats' => $familyStats
        ];
        
        $this->render('children/show', $data);
    }

    /**
     * Handle child selection via AJAX
     */
    public function select(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid security token']);
            return;
        }
        
        // Get and validate input data
        $childId = (int) ($_POST['child_id'] ?? 0);
        $sponsorData = [
            'name' => trim($_POST['sponsor_name'] ?? ''),
            'email' => trim($_POST['sponsor_email'] ?? ''),
            'phone' => trim($_POST['sponsor_phone'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '')
        ];
        $type = $_POST['type'] ?? 'individual';
        
        // Validate required fields
        if (!$childId || !$sponsorData['name'] || !$sponsorData['email']) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        // Validate email
        if (!filter_var($sponsorData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid email address']);
            return;
        }
        
        // Validate sponsorship type
        if (!in_array($type, ['individual', 'sibling', 'family'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid sponsorship type']);
            return;
        }
        
        // Process selection
        $sessionId = session_id();
        $result = $this->sponsorshipService->selectChild($childId, $sponsorData, $type, $sessionId);
        
        $this->jsonResponse($result);
    }

    /**
     * Handle sponsorship confirmation
     */
    public function confirm(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid security token']);
            return;
        }
        
        $sponsorshipId = (int) ($_POST['sponsorship_id'] ?? 0);
        $additionalNotes = trim($_POST['additional_notes'] ?? '');
        
        if (!$sponsorshipId) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing sponsorship ID']);
            return;
        }
        
        $additionalData = [];
        if ($additionalNotes) {
            $additionalData['notes'] = $additionalNotes;
        }
        
        $result = $this->sponsorshipService->confirmSponsorship($sponsorshipId, $additionalData);
        
        $this->jsonResponse($result);
    }

    /**
     * Handle sponsorship cancellation
     */
    public function cancel(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid security token']);
            return;
        }
        
        $sponsorshipId = (int) ($_POST['sponsorship_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        
        if (!$sponsorshipId) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing sponsorship ID']);
            return;
        }
        
        $result = $this->sponsorshipService->cancelSponsorship($sponsorshipId, $reason);
        
        $this->jsonResponse($result);
    }

    /**
     * Render a view template
     */
    private function render(string $template, array $data = []): void
    {
        extract($data);
        include __DIR__ . "/../Views/{$template}.php";
    }

    /**
     * Redirect with error message
     */
    private function redirectWithError(string $url, string $message): void
    {
        $_SESSION['error'] = $message;
        header("Location: {$url}");
        exit;
    }

    /**
     * Send JSON response
     */
    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Validate CSRF token
     */
    private function validateCsrfToken(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}