<?php

declare(strict_types=1);

namespace CFK\Controllers;

use CFK\Repositories\ChildRepository;
use CFK\Repositories\SponsorshipRepository;
use CFK\Services\SponsorshipService;

/**
 * Controller for administrative operations
 */
class AdminController
{
    public function __construct(
        private ChildRepository $childRepository,
        private SponsorshipRepository $sponsorshipRepository,
        private SponsorshipService $sponsorshipService
    ) {}

    /**
     * Display admin dashboard
     */
    public function dashboard(): void
    {
        $this->requireAdminAuth();
        
        // Get statistics
        $stats = $this->sponsorshipService->getStatistics(30);
        $recentActivity = $this->sponsorshipRepository->getRecentActivity(10);
        
        // Get children counts by status
        $childrenStats = [
            'available' => $this->childRepository->countAvailable(),
            'selected' => $this->getChildrenCountByStatus('selected'),
            'sponsored' => $this->getChildrenCountByStatus('sponsored')
        ];
        
        $data = [
            'stats' => $stats,
            'childrenStats' => $childrenStats,
            'recentActivity' => $recentActivity
        ];
        
        $this->render('admin/dashboard', $data);
    }

    /**
     * Display children management page
     */
    public function children(): void
    {
        $this->requireAdminAuth();
        
        $page = (int) ($_GET['page'] ?? 1);
        $limit = 25;
        $offset = ($page - 1) * $limit;
        
        // Get filters
        $filters = [
            'status' => $_GET['status'] ?? '',
            'gender' => $_GET['gender'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        $filters = array_filter($filters);
        
        // Modify filters for admin view (include all statuses)
        $adminFilters = $filters;
        unset($adminFilters['status']);
        
        $children = $this->childRepository->findAvailable($limit, $offset, $adminFilters);
        $totalChildren = $this->childRepository->countAvailable($adminFilters);
        $totalPages = ceil($totalChildren / $limit);
        
        $data = [
            'children' => $children,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalChildren' => $totalChildren,
            'filters' => $filters
        ];
        
        $this->render('admin/children', $data);
    }

    /**
     * Display sponsorships management page
     */
    public function sponsorships(): void
    {
        $this->requireAdminAuth();
        
        $recentSponsorships = $this->sponsorshipRepository->getRecentActivity(50);
        $expiredSelections = $this->sponsorshipRepository->findExpired();
        
        $data = [
            'sponsorships' => $recentSponsorships,
            'expiredSelections' => $expiredSelections
        ];
        
        $this->render('admin/sponsorships', $data);
    }

    /**
     * Handle CSV import
     */
    public function importCsv(): void
    {
        $this->requireAdminAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processCSVUpload();
            return;
        }
        
        $this->render('admin/import');
    }

    /**
     * Export children data as CSV
     */
    public function exportCsv(): void
    {
        $this->requireAdminAuth();
        
        $children = $this->childRepository->findAvailable(1000, 0);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="children-export-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID', 'Family ID', 'Name', 'Age', 'Gender', 'Grade', 
            'Interests', 'Status', 'Created At'
        ]);
        
        // CSV data
        foreach ($children as $child) {
            fputcsv($output, [
                $child->id,
                $child->familyId,
                $child->name,
                $child->age,
                $child->gender,
                $child->grade,
                $child->interests,
                $child->status,
                $child->createdAt?->format('Y-m-d H:i:s')
            ]);
        }
        
        fclose($output);
    }

    /**
     * Cleanup expired selections
     */
    public function cleanupExpired(): void
    {
        $this->requireAdminAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid security token']);
            return;
        }
        
        try {
            $cleanedCount = $this->sponsorshipService->cleanupExpiredSelections();
            $this->jsonResponse([
                'success' => true, 
                'message' => "Cleaned up {$cleanedCount} expired selections"
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Cleanup failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Update child status
     */
    public function updateChildStatus(): void
    {
        $this->requireAdminAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid security token']);
            return;
        }
        
        $childId = (int) ($_POST['child_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if (!$childId || !in_array($status, ['available', 'selected', 'sponsored'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }
        
        try {
            $success = $this->childRepository->updateStatus($childId, $status);
            
            if ($success) {
                $this->jsonResponse(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update status']);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Process CSV upload
     */
    private function processCSVUpload(): void
    {
        if (!$this->validateCsrfToken()) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/import');
            return;
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'No file uploaded or upload error';
            header('Location: /admin/import');
            return;
        }
        
        $file = $_FILES['csv_file'];
        
        // Validate file type
        $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileType), ['csv', 'txt'])) {
            $_SESSION['error'] = 'Invalid file type. Please upload a CSV file.';
            header('Location: /admin/import');
            return;
        }
        
        try {
            $csvData = $this->parseCsvFile($file['tmp_name']);
            $importedCount = $this->importChildrenData($csvData);
            
            $_SESSION['success'] = "Successfully imported {$importedCount} children";
            header('Location: /admin/import');
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Import failed: ' . $e->getMessage();
            header('Location: /admin/import');
        }
    }

    /**
     * Parse CSV file
     */
    private function parseCsvFile(string $filePath): array
    {
        $csvData = [];
        $handle = fopen($filePath, 'r');
        
        if (!$handle) {
            throw new \Exception('Could not open CSV file');
        }
        
        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new \Exception('Invalid CSV format - no headers found');
        }
        
        // Normalize headers
        $headers = array_map('strtolower', array_map('trim', $headers));
        
        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $csvData[] = array_combine($headers, $row);
            }
        }
        
        fclose($handle);
        return $csvData;
    }

    /**
     * Import children data from CSV
     */
    private function importChildrenData(array $csvData): int
    {
        $children = [];
        
        foreach ($csvData as $row) {
            // Validate and normalize data
            $child = [
                'family_id' => trim($row['family_id'] ?? ''),
                'name' => trim($row['name'] ?? ''),
                'age' => (int) ($row['age'] ?? 0),
                'gender' => strtolower(trim($row['gender'] ?? '')),
                'grade' => trim($row['grade'] ?? ''),
                'interests' => trim($row['interests'] ?? ''),
                'avatar' => $this->generateAvatar($row['name'] ?? '', $row['gender'] ?? '')
            ];
            
            // Validate required fields
            if (empty($child['family_id']) || empty($child['name']) || $child['age'] <= 0) {
                continue; // Skip invalid rows
            }
            
            // Normalize gender
            if (!in_array($child['gender'], ['male', 'female'])) {
                $child['gender'] = 'male'; // Default
            }
            
            $children[] = $child;
        }
        
        if (empty($children)) {
            throw new \Exception('No valid children data found in CSV');
        }
        
        // Batch insert
        $this->childRepository->batchInsert($children);
        
        return count($children);
    }

    /**
     * Generate avatar filename for a child
     */
    private function generateAvatar(string $name, string $gender): string
    {
        // Simple avatar generation based on name hash and gender
        $hash = crc32($name);
        $avatarNumber = abs($hash) % 10 + 1;
        return "avatar-{$gender}-{$avatarNumber}.png";
    }

    /**
     * Get children count by status
     */
    private function getChildrenCountByStatus(string $status): int
    {
        $pdo = \CFK\Config\Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM children WHERE status = ?');
        $stmt->execute([$status]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Require admin authentication
     */
    private function requireAdminAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            header('Location: /admin/login');
            exit;
        }
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