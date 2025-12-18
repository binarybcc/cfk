<?php

declare(strict_types=1);

namespace CFK\Controller;

use CFK\Archive\Manager as ArchiveManager;
use CFK\Database\Connection as Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Admin Year-End Reset Controller
 *
 * Handles seasonal archiving and reset operations.
 * Migrated from admin/year_end_reset.php (Week 8 Part 2 Phase 5/6)
 */
class AdminArchiveController
{
    private Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Display year-end reset page
     *
     * GET /admin/archive
     */
    public function index(Request $request, Response $response): Response
    {
        // Get current stats
        try {
            $childrenResult = Database::fetchRow('SELECT COUNT(*) as count FROM children');
            $familiesResult = Database::fetchRow('SELECT COUNT(*) as count FROM families');
            $sponsorshipsResult = Database::fetchRow('SELECT COUNT(*) as count FROM sponsorships');
            $emailLogResult = Database::fetchRow('SELECT COUNT(*) as count FROM email_log');

            $currentStats = [
                'children' => (int) ($childrenResult['count'] ?? 0),
                'families' => (int) ($familiesResult['count'] ?? 0),
                'sponsorships' => (int) ($sponsorshipsResult['count'] ?? 0),
                'email_log' => (int) ($emailLogResult['count'] ?? 0),
            ];
        } catch (\Exception $e) {
            error_log('Failed to get stats: ' . $e->getMessage());
            $currentStats = [
                'children' => 0,
                'families' => 0,
                'sponsorships' => 0,
                'email_log' => 0,
            ];
        }

        // Get available archives
        try {
            $archives = ArchiveManager::getAvailableArchives();
        } catch (\Exception $e) {
            error_log('Failed to get archives: ' . $e->getMessage());
            $archives = [];
        }

        // Get deletion preview
        try {
            $deletionPreview = ArchiveManager::getArchivesForDeletion();
        } catch (\Exception $e) {
            error_log('Failed to get deletion preview: ' . $e->getMessage());
            $deletionPreview = null;
        }

        // Get flash message from session
        $flashMessage = getMessage();

        return $this->view->render($response, 'admin/archive/index.twig', [
            'pageTitle' => 'Year-End Reset',
            'currentStats' => $currentStats,
            'archives' => $archives,
            'deletionPreview' => $deletionPreview,
            'csrfToken' => generateCsrfToken(),
            'flash_message' => $flashMessage['text'] ?? null,
            'flash_type' => $flashMessage['type'] ?? 'info',
        ]);
    }

    /**
     * Perform year-end reset (archive and clear)
     *
     * POST /admin/archive/reset
     */
    public function reset(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/archive'))
                ->withStatus(302);
        }

        // Require confirmation code
        $confirmationCode = $data['confirmation_code'] ?? '';
        if (strtoupper($confirmationCode) !== 'RESET') {
            setMessage('You must type "RESET" to confirm this action.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/archive'))
                ->withStatus(302);
        }

        // Get current year for archiving
        $currentYear = date('Y');
        $fullConfirmationCode = 'RESET ' . $currentYear;

        // Perform reset
        try {
            $result = ArchiveManager::performYearEndReset($currentYear, $fullConfirmationCode);

            if ($result['success']) {
                $message = sprintf(
                    'Year-end reset completed! Archived: %d children, %d families, %d sponsorships. Archive: %s',
                    $result['archived_children'] ?? 0,
                    $result['archived_families'] ?? 0,
                    $result['archived_sponsorships'] ?? 0,
                    $result['archive_file'] ?? 'unknown'
                );
                setMessage($message, 'success');
            } else {
                setMessage('Reset failed: ' . ($result['message'] ?? 'Unknown error'), 'error');
            }
        } catch (\Exception $e) {
            error_log('Year-end reset error: ' . $e->getMessage());
            setMessage('Reset failed: ' . $e->getMessage(), 'error');
        }

        return $response
            ->withHeader('Location', baseUrl('/admin/archive'))
            ->withStatus(302);
    }

    /**
     * Restore from archive
     *
     * POST /admin/archive/restore
     */
    public function restore(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/archive'))
                ->withStatus(302);
        }

        $archiveYear = $data['archive_year'] ?? '';
        $archiveTimestamp = $data['archive_timestamp'] ?? '';

        if (empty($archiveYear) || empty($archiveTimestamp)) {
            setMessage('Please select an archive to restore.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/archive'))
                ->withStatus(302);
        }

        // Require confirmation code
        $confirmationCode = $data['restore_confirmation_code'] ?? '';
        if (strtoupper($confirmationCode) !== 'RESTORE') {
            setMessage('You must type "RESTORE" to confirm this action.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/archive'))
                ->withStatus(302);
        }

        // Build full confirmation code for Archive Manager
        $fullConfirmationCode = 'RESTORE ' . $archiveYear;

        // Perform restore
        try {
            $result = ArchiveManager::performArchiveRestore($archiveYear, $fullConfirmationCode);

            if ($result['success']) {
                $message = sprintf(
                    'Archive restored! Loaded: %d children, %d families, %d sponsorships from %s archive.',
                    $result['restored_children'] ?? 0,
                    $result['restored_families'] ?? 0,
                    $result['restored_sponsorships'] ?? 0,
                    $archiveYear
                );
                setMessage($message, 'success');
            } else {
                setMessage('Restore failed: ' . ($result['message'] ?? 'Unknown error'), 'error');
            }
        } catch (\Exception $e) {
            error_log('Archive restore error: ' . $e->getMessage());
            setMessage('Restore failed: ' . $e->getMessage(), 'error');
        }

        return $response
            ->withHeader('Location', baseUrl('/admin/archive'))
            ->withStatus(302);
    }

    /**
     * Delete old archives
     *
     * POST /admin/archive/delete-old
     */
    public function deleteOld(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Verify CSRF token
        if (! verifyCsrfToken($data['csrf_token'] ?? '')) {
            setMessage('Security token invalid. Please try again.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/archive'))
                ->withStatus(302);
        }

        // Require confirmation code
        $confirmationCode = $data['delete_confirmation_code'] ?? '';
        if (strtoupper($confirmationCode) !== 'DELETE') {
            setMessage('You must type "DELETE" to confirm this action.', 'error');

            return $response
                ->withHeader('Location', baseUrl('/admin/archive'))
                ->withStatus(302);
        }

        // Build full confirmation code for Archive Manager
        $fullConfirmationCode = 'DELETE OLD ARCHIVES';

        // Perform deletion
        try {
            $result = ArchiveManager::deleteOldArchives($fullConfirmationCode);

            if ($result['success']) {
                $message = sprintf(
                    'Old archives deleted! Removed %d archives, freed %s of space.',
                    $result['deleted_count'] ?? 0,
                    $result['space_freed'] ?? '0 KB'
                );
                setMessage($message, 'success');
            } else {
                setMessage('Deletion failed: ' . ($result['message'] ?? 'Unknown error'), 'error');
            }
        } catch (\Exception $e) {
            error_log('Archive deletion error: ' . $e->getMessage());
            setMessage('Deletion failed: ' . $e->getMessage(), 'error');
        }

        return $response
            ->withHeader('Location', baseUrl('/admin/archive'))
            ->withStatus(302);
    }
}
