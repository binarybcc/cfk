<?php

declare(strict_types=1);

namespace CFK\Controller;

use CFK\Backup\Manager as BackupManager;
use CFK\CSV\Handler as CSVHandler;
use CFK\Import\Analyzer as ImportAnalyzer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Views\Twig;

/**
 * Admin Import Controller
 *
 * Handles CSV import/export and backup operations for children data.
 * Migrated from admin/import_csv.php (Week 8 Part 2 Phase 4)
 */
class AdminImportController
{
    private Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Display CSV import page
     *
     * GET /admin/import
     */
    public function index(Request $request, Response $response): Response
    {
        // Get flash message from session
        $flashMessage = getMessage();

        return $this->view->render($response, 'admin/import/index.twig', [
            'pageTitle' => 'Import Children from CSV',
            'csrfToken' => generateCsrfToken(),
            'flash_message' => $flashMessage['text'] ?? null,
            'flash_type' => $flashMessage['type'] ?? 'info',
        ]);
    }

    /**
     * Preview CSV import
     *
     * POST /admin/import/preview
     */
    public function preview(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        // Get uploaded file
        $uploadedFiles = $request->getUploadedFiles();
        $csvFile = $uploadedFiles['csv_file'] ?? null;

        if (! $csvFile || $csvFile->getError() !== UPLOAD_ERR_OK) {
            setMessage('Please select a valid CSV file to upload.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        // Validate file
        if (! $this->isValidCsvFile($csvFile)) {
            setMessage('Please upload a CSV file (.csv extension). Maximum size is 5MB.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        // Save to temp location
        $tempFile = $this->saveTempFile($csvFile);
        if (! $tempFile) {
            setMessage('Failed to process uploaded file.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        // Parse CSV for preview
        $handler = new CSVHandler();
        $parseResult = $handler->parseCSVForPreview($tempFile);

        if (! $parseResult['success']) {
            @unlink($tempFile);
            setMessage('CSV parsing failed: ' . implode(', ', $parseResult['errors'] ?? ['Unknown error']), 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        // Analyze the data
        $analyzer = new ImportAnalyzer();
        $analysis = $analyzer->analyze($parseResult['data']);

        // Store preview data in session for confirmation
        $_SESSION['csv_preview'] = [
            'filename' => $csvFile->getClientFilename(),
            'temp_file' => $tempFile,
            'total_rows' => count($parseResult['data']),
            'analysis' => $analysis,
            'parse_warnings' => $parseResult['warnings'] ?? [],
        ];

        return $this->view->render($response, 'admin/import/preview.twig', [
            'pageTitle' => 'Preview CSV Import',
            'preview' => $_SESSION['csv_preview'],
            'csrfToken' => generateCsrfToken(),
        ]);
    }

    /**
     * Confirm and execute import
     *
     * POST /admin/import/confirm
     */
    public function confirm(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        // Get preview data from session
        $previewData = $_SESSION['csv_preview'] ?? null;
        if (! $previewData || ! file_exists($previewData['temp_file'])) {
            setMessage('No file to import. Please upload a CSV file first.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        // Create backup before import
        $backupManager = new BackupManager();
        $backupResult = $backupManager->createBackup('pre_import_' . date('Y-m-d_His'));

        // Execute import
        $handler = new CSVHandler();
        $importResult = $handler->importFromCSV($previewData['temp_file']);

        // Clean up temp file
        @unlink($previewData['temp_file']);
        unset($_SESSION['csv_preview']);

        if ($importResult['success']) {
            $message = sprintf(
                'Import completed successfully! Imported %d children. %s',
                $importResult['imported_count'],
                $backupResult['success'] ? '(Backup created: ' . $backupResult['filename'] . ')' : ''
            );
            setMessage($message, 'success');
        } else {
            setMessage('Import failed: ' . ($importResult['message'] ?? 'Unknown error'), 'error');
        }

        return $response
            ->withHeader('Location', baseUrl('/admin/import'))
            ->withStatus(302);
    }

    /**
     * Download CSV template
     *
     * GET /admin/import/template
     */
    public function downloadTemplate(Request $request, Response $response): Response
    {
        $csv = "family_number,child_letter,age_months,age_years,gender,grade,school,shirt_size,pant_size,jacket_size,shoe_size,interests,wishes,special_needs\n";
        $csv .= "175,A,,,M,5th,Lincoln Elementary,M,10,M,5,Soccer and video games,Nintendo Switch,None\n";
        $csv .= "175,B,36,,F,Pre-K,Lincoln Elementary,4T,4T,4T,10,Dolls and coloring,Baby doll,Gluten-free diet\n";

        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="cfk-import-template.csv"');
    }

    /**
     * Delete all children (dangerous operation)
     *
     * POST /admin/import/delete-all
     */
    public function deleteAll(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        // Require confirmation
        if (($data['confirm'] ?? '') !== 'DELETE ALL') {
            setMessage('You must type "DELETE ALL" to confirm this action.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        // Create backup first
        $backupManager = new BackupManager();
        $backupResult = $backupManager->createBackup('pre_delete_' . date('Y-m-d_His'));

        if (! $backupResult['success']) {
            setMessage('Backup failed. Delete operation cancelled for safety.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        // Delete all children and families
        try {
            \Database::execute('DELETE FROM sponsorships');
            \Database::execute('DELETE FROM reservations');
            \Database::execute('DELETE FROM children');
            \Database::execute('DELETE FROM families');

            setMessage('All children and families deleted. Backup saved: ' . $backupResult['filename'], 'success');
        } catch (\Exception $e) {
            error_log('Delete all error: ' . $e->getMessage());
            setMessage('Delete operation failed. Database may be partially cleared.', 'error');
        }

        return $response
            ->withHeader('Location', baseUrl('/admin/import'))
            ->withStatus(302);
    }

    /**
     * Download backup file
     *
     * GET /admin/import/backup/{filename}
     */
    public function downloadBackup(Request $request, Response $response, array $args): Response
    {
        $filename = basename($args['filename']); // Sanitize

        $backupManager = new BackupManager();
        $backupPath = $backupManager->getBackupPath($filename);

        if (! file_exists($backupPath)) {
            setMessage('Backup file not found.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        $fileContent = file_get_contents($backupPath);
        $response->getBody()->write($fileContent);

        return $response
            ->withHeader('Content-Type', 'application/sql')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Restore from backup
     *
     * POST /admin/import/restore
     */
    public function restoreBackup(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        $filename = $data['backup_file'] ?? '';
        if (empty($filename)) {
            setMessage('No backup file selected.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/import'))
                ->withStatus(302);
        }

        $backupManager = new BackupManager();
        $result = $backupManager->restoreBackup($filename);

        setMessage($result['message'], $result['success'] ? 'success' : 'error');

        return $response
            ->withHeader('Location', baseUrl('/admin/import'))
            ->withStatus(302);
    }

    /**
     * Validate CSV file upload
     */
    private function isValidCsvFile(UploadedFileInterface $file): bool
    {
        // Check size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return false;
        }

        // Check extension
        $filename = $file->getClientFilename();
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($ext, ['csv', 'txt']);
    }

    /**
     * Save uploaded file to temp location
     */
    private function saveTempFile(UploadedFileInterface $file): ?string
    {
        $tempDir = sys_get_temp_dir() . '/cfk_uploads';
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0700, true);
        }

        $tempFile = $tempDir . '/upload_' . session_id() . '.csv';

        try {
            $file->moveTo($tempFile);

            return $tempFile;
        } catch (\Exception $e) {
            error_log('Failed to save temp file: ' . $e->getMessage());

            return null;
        }
    }
}
