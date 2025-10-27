<?php

declare(strict_types=1);

/**
 * Year-End Reset Page
 * Administrative tool for archiving and clearing seasonal data
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Use namespaced classes
use CFK\Archive\Manager as ArchiveManager;
use CFK\Database\Connection as Database;

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Year-End Reset';

// Get current stats
try {
    $currentStats = [
        'children' => Database::fetchRow("SELECT COUNT(*) as count FROM children")['count'],
        'families' => Database::fetchRow("SELECT COUNT(*) as count FROM families")['count'],
        'sponsorships' => Database::fetchRow("SELECT COUNT(*) as count FROM sponsorships")['count'],
        'email_log' => Database::fetchRow("SELECT COUNT(*) as count FROM email_log")['count']
    ];
} catch (Exception $e) {
    error_log("Failed to get stats: " . $e->getMessage());
    $currentStats = [
        'children' => 0,
        'families' => 0,
        'sponsorships' => 0,
        'email_log' => 0
    ];
}

// Get available archives
try {
    $archives = ArchiveManager::getAvailableArchives();
} catch (Exception $e) {
    error_log("Failed to get archives: " . $e->getMessage());
    $archives = [];
}

$errors = [];
$success = null;
$resetResult = null;
$restoreResult = null;
$deleteResult = null;
$debugLog = [];
$deletionPreview = null;

// Debug: Log all requests
error_log("YEAR_END_RESET: Page loaded. REQUEST_METHOD=" . ($_SERVER['REQUEST_METHOD'] ?? 'NONE') . ", POST keys: " . implode(',', array_keys($_POST ?? [])));

// Get deletion preview for display
try {
    $deletionPreview = ArchiveManager::getArchivesForDeletion();
} catch (Exception $e) {
    error_log("Failed to get deletion preview: " . $e->getMessage());
    $deletionPreview = null;
}

// Handle delete archives form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['perform_delete'])) {
    error_log("DELETE_ARCHIVES: Form submitted. POST data: " . print_r($_POST, true));

    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        error_log("DELETE_ARCHIVES: CSRF token verification FAILED");
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        $confirmationCode = $_POST['delete_confirmation_code'] ?? '';

        // Perform deletion
        error_log("DELETE_ARCHIVES: Calling deleteOldArchives");
        $deleteResult = ArchiveManager::deleteOldArchives($confirmationCode, true);
        $debugLog = $deleteResult['debug_log'] ?? [];
        error_log("DELETE_ARCHIVES: Result: " . ($deleteResult['success'] ? 'SUCCESS' : 'FAILED'));

        if ($deleteResult['success']) {
            $success = $deleteResult['message'];

            // Log the action
            error_log("Old archives deleted by admin: " . ($_SESSION['cfk_admin_username'] ?? 'unknown') . " - Deleted: " . $deleteResult['deleted_count'] . " archives, Freed: " . $deleteResult['total_deleted_mb'] . " MB");

            // Refresh archives list and deletion preview
            try {
                $archives = ArchiveManager::getAvailableArchives();
                $deletionPreview = ArchiveManager::getArchivesForDeletion();
            } catch (Exception $e) {
                error_log("Failed to refresh archives: " . $e->getMessage());
            }
        } else {
            $errors[] = $deleteResult['message'];
        }
    }
}

// Handle restore form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['perform_restore'])) {
    error_log("ARCHIVE_RESTORE: Form submitted. POST data: " . print_r($_POST, true));

    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        error_log("ARCHIVE_RESTORE: CSRF token verification FAILED");
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        $year = sanitizeString($_POST['restore_year'] ?? '');
        $confirmationCode = $_POST['restore_confirmation_code'] ?? '';

        if (empty($year)) {
            $errors[] = 'Please select a year to restore.';
        } else {
            // Perform restore
            error_log("ARCHIVE_RESTORE: Calling performArchiveRestore for year={$year}");
            $restoreResult = ArchiveManager::performArchiveRestore($year, $confirmationCode, true);
            $debugLog = $restoreResult['debug_log'] ?? [];
            error_log("ARCHIVE_RESTORE: Result: " . ($restoreResult['success'] ? 'SUCCESS' : 'FAILED'));

            if ($restoreResult['success']) {
                $success = $restoreResult['message'];

                // Log the action
                error_log("Archive restored for year {$year} by admin: " . ($_SESSION['cfk_admin_username'] ?? 'unknown'));

                // Refresh stats to show restored data
                try {
                    $currentStats = [
                        'children' => Database::fetchRow("SELECT COUNT(*) as count FROM children")['count'],
                        'families' => Database::fetchRow("SELECT COUNT(*) as count FROM families")['count'],
                        'sponsorships' => Database::fetchRow("SELECT COUNT(*) as count FROM sponsorships")['count'],
                        'email_log' => Database::fetchRow("SELECT COUNT(*) as count FROM email_log")['count']
                    ];
                } catch (Exception $e) {
                    // Keep existing stats
                }
            } else {
                $errors[] = $restoreResult['message'];
            }
        }
    }
}

// Handle reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['perform_reset'])) {
    error_log("YEAR_END_RESET: Form submitted. POST data: " . print_r($_POST, true));

    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        error_log("YEAR_END_RESET: CSRF token verification FAILED");
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        error_log("YEAR_END_RESET: CSRF token verified successfully");
        $year = sanitizeString($_POST['year'] ?? '');
        $confirmationCode = $_POST['confirmation_code'] ?? '';

        if (empty($year)) {
            $errors[] = 'Please enter the year.';
        } elseif (!preg_match('/^\d{4}$/', $year)) {
            $errors[] = 'Year must be a 4-digit number.';
        } else {
            // Perform reset
            error_log("YEAR_END_RESET: Calling performYearEndReset for year={$year}, code={$confirmationCode}");
            $resetResult = ArchiveManager::performYearEndReset($year, $confirmationCode);
            error_log("YEAR_END_RESET: Result: " . print_r($resetResult, true));

            if ($resetResult['success']) {
                $success = $resetResult['message'];

                // Log the action
                error_log("Year-end reset performed for year {$year} by admin: " . ($_SESSION['cfk_admin_username'] ?? 'unknown'));

                // Refresh stats
                $currentStats = [
                    'children' => 0,
                    'families' => 0,
                    'sponsorships' => 0,
                    'email_log' => 0
                ];
            } else {
                $errors[] = $resetResult['message'];
            }
        }
    }
}

include __DIR__ . '/includes/admin_header.php';
?>

<div class="year-end-reset-page">
    <div class="page-header">
        <h1>⚠️ Year-End Reset</h1>
        <p class="page-subtitle">Archive current data and prepare for new season</p>
    </div>

    <?php if ($errors !== []) : ?>
        <div class="alert alert-error">
            <h3>Errors:</h3>
            <ul>
                <?php foreach ($errors as $error) : ?>
                    <li><?php echo sanitizeString($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success) : ?>
        <div class="alert alert-success">
            <h3>✅ Success!</h3>
            <p><?php echo sanitizeString($success); ?></p>

            <?php if ($resetResult && isset($resetResult['deleted_counts'])) : ?>
                <h4>Deleted Records:</h4>
                <ul>
                    <li>Children: <?php echo (int)($resetResult['deleted_counts']['children'] ?? 0); ?></li>
                    <li>Families: <?php echo (int)($resetResult['deleted_counts']['families'] ?? 0); ?></li>
                    <li>Sponsorships: <?php echo (int)($resetResult['deleted_counts']['sponsorships'] ?? 0); ?></li>
                    <li>Email Logs: <?php echo (int)($resetResult['deleted_counts']['email_log'] ?? 0); ?></li>
                </ul>

                <p><strong>Next Steps:</strong></p>
                <ol>
                    <li>Verify archive was created in <code>archives/<?php echo sanitizeString($_POST['year'] ?? ''); ?>/</code></li>
                    <li>Import new season's CSV data</li>
                    <li>Test with a few sample children</li>
                </ol>
            <?php endif; ?>

            <?php if ($restoreResult && isset($restoreResult['restored_counts'])) : ?>
                <h4>Restored Records:</h4>
                <ul>
                    <li>Children: <?php echo (int)($restoreResult['restored_counts']['children'] ?? 0); ?></li>
                    <li>Families: <?php echo (int)($restoreResult['restored_counts']['families'] ?? 0); ?></li>
                    <li>Sponsorships: <?php echo (int)($restoreResult['restored_counts']['sponsorships'] ?? 0); ?></li>
                    <li>Email Logs: <?php echo (int)($restoreResult['restored_counts']['email_log'] ?? 0); ?></li>
                </ul>
                <p><strong>Duration:</strong> <?php echo number_format($restoreResult['duration'] ?? 0, 2); ?> seconds</p>

                <p><strong>Next Steps:</strong></p>
                <ol>
                    <li>Verify data on <a href="manage_children.php">Children Management</a> page</li>
                    <li>Check <a href="manage_sponsorships.php">Sponsorships</a> are restored correctly</li>
                    <li>Test the public-facing site functionality</li>
                </ol>
            <?php endif; ?>

            <?php if ($deleteResult) : ?>
                <h4>Deletion Summary:</h4>
                <ul>
                    <li>Archives Deleted: <?php echo (int)($deleteResult['deleted_count'] ?? 0); ?></li>
                    <li>Archives Kept: <?php echo (int)($deleteResult['kept_count'] ?? 0); ?></li>
                    <li>Disk Space Freed: <?php echo $deleteResult['total_deleted_mb'] ?? 0; ?> MB</li>
                </ul>

                <?php if (!empty($deleteResult['errors'])) : ?>
                    <h4>⚠️ Errors During Deletion:</h4>
                    <ul>
                        <?php foreach ($deleteResult['errors'] as $error) : ?>
                            <li><?php echo sanitizeString($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($debugLog)) : ?>
        <div class="debug-log-section">
            <h3>🔍 Restoration Debug Log</h3>
            <div class="debug-log-content">
                <?php foreach ($debugLog as $logEntry) : ?>
                    <div class="debug-log-entry"><?php echo htmlspecialchars($logEntry); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Current System State -->
    <div class="system-state-section">
        <h2>Current System State</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?php echo (int)($currentStats['children'] ?? 0); ?></h3>
                    <p>Children</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👨‍👩‍👧‍👦</div>
                <div class="stat-content">
                    <h3><?php echo (int)($currentStats['families'] ?? 0); ?></h3>
                    <p>Families</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎁</div>
                <div class="stat-content">
                    <h3><?php echo (int)($currentStats['sponsorships'] ?? 0); ?></h3>
                    <p>Sponsorships</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📧</div>
                <div class="stat-content">
                    <h3><?php echo (int)($currentStats['email_log'] ?? 0); ?></h3>
                    <p>Email Logs</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Year-End Reset Form -->
    <div class="reset-form-section">
        <h2>🚨 Perform Year-End Reset</h2>

        <div class="warning-box">
            <h3>⚠️ CRITICAL WARNING</h3>
            <p><strong>This action will:</strong></p>
            <ul>
                <li>✅ Create a full database backup</li>
                <li>✅ Export all data to CSV files</li>
                <li>❌ DELETE all children, families, and sponsorships</li>
                <li>❌ CLEAR email logs</li>
                <li>✅ Preserve admin user accounts</li>
                <li>✅ Preserve system settings</li>
            </ul>
            <p class="warning-note"><strong>THIS ACTION CANNOT BE UNDONE!</strong></p>
            <p>Only proceed if you have verified all data is backed up and you are ready to start a new season.</p>
        </div>

        <form method="POST" action="" id="resetForm" class="reset-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="perform_reset" value="1">

            <div class="form-group">
                <label for="year" class="form-label">Year to Archive</label>
                <input type="text"
                       id="year"
                       name="year"
                       class="form-input"
                       placeholder="e.g., <?php echo date('Y'); ?>"
                       pattern="\d{4}"
                       required>
                <div class="form-help">Enter the year you are archiving (4 digits)</div>
            </div>

            <div class="form-group">
                <label for="confirmation_code" class="form-label">Confirmation Code</label>
                <input type="text"
                       id="confirmation_code"
                       name="confirmation_code"
                       class="form-input"
                       placeholder="Type: RESET [YEAR]"
                       required>
                <div class="form-help">Type <strong>RESET</strong> followed by a space and the year (e.g., "RESET <?php echo date('Y'); ?>")</div>
            </div>

            <div class="form-actions">
                <button type="submit" name="perform_reset" class="btn btn-large btn-danger" id="resetButton">
                    🗑️ Archive and Reset System
                </button>
                <a href="index.php" class="btn btn-large btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Available Archives -->
    <?php if ($archives !== []) : ?>
        <div class="archives-section">
            <h2>📦 Available Archives</h2>

            <div class="archives-list">
                <?php foreach ($archives as $archive) : ?>
                    <div class="archive-card <?php echo $archive['has_data'] ? '' : 'archive-empty'; ?>">
                        <div class="archive-header">
                            <h3>Year <?php echo sanitizeString($archive['year']); ?> - <?php echo sanitizeString($archive['date']); ?></h3>
                            <span class="archive-size"><?php echo ArchiveManager::formatBytes($archive['size']); ?></span>
                        </div>
                        <div class="archive-details">
                            <p><strong>Files:</strong> <?php echo $archive['file_count']; ?></p>
                            <p><strong>Backup:</strong> <code><?php echo sanitizeString($archive['backup_file']); ?></code></p>
                            <?php if ($archive['has_data']) : ?>
                                <p class="archive-status">✅ Contains data</p>
                            <?php else : ?>
                                <p class="archive-status-warning">⚠️ Empty archive (0 records)</p>
                            <?php endif; ?>
                            <?php if ($archive['has_summary']) : ?>
                                <p class="archive-status">📄 Summary available</p>
                            <?php endif; ?>
                        </div>
                        <div class="archive-actions">
                            <?php if ($archive['has_summary']) : ?>
                                <a href="<?php echo str_replace(__DIR__ . '/..', '..', $archive['path']); ?>/ARCHIVE_SUMMARY.txt"
                                   class="btn btn-small btn-primary"
                                   target="_blank">View Summary</a>
                            <?php endif; ?>
                            <button type="button"
                                    class="btn btn-small btn-success <?php echo $archive['has_data'] ? '' : 'btn-disabled'; ?>"
                                    onclick="showRestoreForm('<?php echo sanitizeString($archive['year']); ?>', '<?php echo sanitizeString($archive['timestamp']); ?>', <?php echo $archive['has_data'] ? 'true' : 'false'; ?>)"
                                    <?php echo $archive['has_data'] ? '' : 'disabled title="Cannot restore empty archive"'; ?>>
                                🔄 Restore
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Restore Archive Form -->
        <div class="restore-form-section" id="restoreFormSection" style="display: none;">
            <h2>🔄 Restore Archive</h2>

            <div class="warning-box">
                <h3>⚠️ CRITICAL WARNING</h3>
                <p><strong>This action will:</strong></p>
                <ul>
                    <li>❌ OVERWRITE all current data in the database</li>
                    <li>✅ Restore all children, families, and sponsorships from archive</li>
                    <li>✅ Restore email logs</li>
                    <li>⚠️  Current data will be LOST unless you create a backup first!</li>
                </ul>
                <p class="warning-note"><strong>THIS ACTION CANNOT BE UNDONE!</strong></p>
                <p>Only proceed if you are certain you want to restore this archive.</p>
            </div>

            <form method="POST" action="" id="restoreForm" class="reset-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="perform_restore" value="1">
                <input type="hidden" name="restore_year" id="restoreYear" value="">

                <div class="form-group">
                    <label class="form-label">Year to Restore</label>
                    <div class="form-input-readonly" id="restoreYearDisplay"></div>
                    <div class="form-help">The archive year that will be restored</div>
                </div>

                <div class="form-group" id="restorePreview">
                    <!-- Preview will be loaded via JavaScript -->
                </div>

                <div class="form-group">
                    <label for="restore_confirmation_code" class="form-label">Confirmation Code</label>
                    <input type="text"
                           id="restore_confirmation_code"
                           name="restore_confirmation_code"
                           class="form-input"
                           placeholder="Type: RESTORE [YEAR]"
                           required>
                    <div class="form-help">Type <strong>RESTORE</strong> followed by a space and the year (e.g., "RESTORE <span id="restoreYearHint"></span>")</div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="perform_restore" class="btn btn-large btn-success" id="restoreButton">
                        🔄 Restore Archive
                    </button>
                    <button type="button" class="btn btn-large btn-secondary" onclick="hideRestoreForm()">Cancel</button>
                </div>
            </form>
        </div>
    <?php else : ?>
        <div class="archives-section">
            <h2>📦 Available Archives</h2>
            <p class="no-archives">No archives found. Archives will appear here after performing a year-end reset.</p>
        </div>
    <?php endif; ?>

    <!-- Delete Old Archives -->
    <?php if ($deletionPreview && $deletionPreview['delete_count'] > 0) : ?>
        <div class="delete-archives-section">
            <h2>🗑️ Delete Old Archives</h2>

            <div class="info-box">
                <h3>💾 Disk Space Management</h3>
                <p>This system keeps the <strong>2 most recent archives with data</strong>. Older archives can be safely deleted to free disk space.</p>
            </div>

            <div class="deletion-preview">
                <h3>Preview: What Will Be Deleted</h3>

                <div class="deletion-summary">
                    <div class="deletion-stat">
                        <span class="deletion-label">Archives to Delete:</span>
                        <span class="deletion-value"><?php echo $deletionPreview['delete_count']; ?></span>
                    </div>
                    <div class="deletion-stat">
                        <span class="deletion-label">Space to Free:</span>
                        <span class="deletion-value"><?php echo $deletionPreview['total_size_mb']; ?> MB</span>
                    </div>
                    <div class="deletion-stat">
                        <span class="deletion-label">Archives to Keep:</span>
                        <span class="deletion-value"><?php echo count($deletionPreview['to_keep']); ?></span>
                    </div>
                </div>

                <h4>❌ Archives That Will Be Deleted:</h4>
                <div class="deletion-list">
                    <?php foreach ($deletionPreview['to_delete'] as $archive) : ?>
                        <div class="deletion-item">
                            <span class="deletion-item-name">Year <?php echo sanitizeString($archive['year']); ?> - <?php echo sanitizeString($archive['date']); ?></span>
                            <span class="deletion-item-size"><?php echo ArchiveManager::formatBytes($archive['size']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h4>✅ Archives That Will Be Kept (Most Recent 2):</h4>
                <div class="kept-list">
                    <?php foreach ($deletionPreview['to_keep'] as $archive) : ?>
                        <div class="kept-item">
                            <span class="kept-item-name">Year <?php echo sanitizeString($archive['year']); ?> - <?php echo sanitizeString($archive['date']); ?></span>
                            <span class="kept-item-size"><?php echo ArchiveManager::formatBytes($archive['size']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <form method="POST" action="" id="deleteForm" class="delete-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="perform_delete" value="1">

                <div class="form-group">
                    <label for="delete_confirmation_code" class="form-label">Confirmation Code</label>
                    <input type="text"
                           id="delete_confirmation_code"
                           name="delete_confirmation_code"
                           class="form-input"
                           placeholder="Type: DELETE OLD ARCHIVES"
                           required>
                    <div class="form-help">Type exactly: <strong>DELETE OLD ARCHIVES</strong></div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-large btn-warning">
                        🗑️ Delete Old Archives (Free <?php echo $deletionPreview['total_size_mb']; ?> MB)
                    </button>
                    <button type="button" class="btn btn-large btn-secondary" onclick="document.getElementById('deleteForm').reset()">Cancel</button>
                </div>
            </form>
        </div>
    <?php elseif ($deletionPreview && $deletionPreview['delete_count'] === 0) : ?>
        <div class="delete-archives-section">
            <h2>🗑️ Delete Old Archives</h2>
            <div class="info-box">
                <p>✅ Only the 2 most recent archives are being kept. No old archives to delete.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Instructions -->
    <div class="instructions-section">
        <h2>📖 Instructions</h2>

        <div class="instruction-steps">
            <h3>Before Reset:</h3>
            <ol>
                <li>Verify all sponsorships are completed or cancelled</li>
                <li>Generate final reports for the current season</li>
                <li>Notify all administrators about the scheduled reset</li>
                <li>Prepare new season's CSV data for import</li>
            </ol>

            <h3>After Reset:</h3>
            <ol>
                <li>Verify archive was created successfully</li>
                <li>Download archive files to secure location</li>
                <li>Go to <a href="import_csv.php">CSV Import</a> page</li>
                <li>Import new season's families and children data</li>
                <li>Test the system with sample data</li>
                <li>Announce system is ready for new season</li>
            </ol>

            <h3>Archive Contents:</h3>
            <ul>
                <li><strong>database_backup_*.sql</strong> - Complete database backup</li>
                <li><strong>children_*.csv</strong> - All children data</li>
                <li><strong>families_*.csv</strong> - All families data</li>
                <li><strong>sponsorships_*.csv</strong> - All sponsorships data</li>
                <li><strong>email_log_*.csv</strong> - Email sending logs</li>
                <li><strong>ARCHIVE_SUMMARY.txt</strong> - Statistics and information</li>
            </ul>
        </div>
    </div>
</div>

<style>
.year-end-reset-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    color: #dc3545;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: #666;
}

.system-state-section, .reset-form-section, .archives-section, .instructions-section, .delete-archives-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.system-state-section h2, .reset-form-section h2, .archives-section h2, .instructions-section h2 {
    color: #2c5530;
    margin-bottom: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.stat-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    font-size: 2rem;
}

.stat-content h3 {
    font-size: 2rem;
    color: #2c5530;
    margin: 0;
}

.stat-content p {
    margin: 0;
    color: #666;
}

.warning-box {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.warning-box h3 {
    color: #856404;
    margin-top: 0;
}

.warning-box ul {
    margin: 1rem 0;
}

.warning-box li {
    margin: 0.5rem 0;
}

.warning-note {
    background: #dc3545;
    color: white;
    padding: 1rem;
    border-radius: 4px;
    font-weight: bold;
    text-align: center;
    margin: 1rem 0;
}

.reset-form {
    max-width: 600px;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: bold;
    color: #333;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

.form-input:focus {
    outline: none;
    border-color: #2c5530;
}

.form-help {
    font-size: 0.9rem;
    color: #666;
    margin-top: 0.25rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 1rem;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-primary {
    background: #2c5530;
    color: white;
}

.btn-primary:hover {
    background: #1d3820;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.archives-list {
    display: grid;
    gap: 1rem;
}

.archive-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #2c5530;
}

.archive-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.archive-header h3 {
    margin: 0;
    color: #2c5530;
}

.archive-size {
    background: #2c5530;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.9rem;
}

.archive-details p {
    margin: 0.5rem 0;
}

.archive-details code {
    background: #e9ecef;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
}

.archive-status {
    color: #28a745;
    font-weight: bold;
}

.no-archives {
    color: #666;
    font-style: italic;
}

.instruction-steps h3 {
    color: #2c5530;
    margin-top: 1.5rem;
}

.instruction-steps ol, .instruction-steps ul {
    margin-left: 1.5rem;
}

.instruction-steps li {
    margin: 0.5rem 0;
}

.alert {
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert h3 {
    margin-top: 0;
}

.alert ul {
    margin: 1rem 0;
}

.restore-form-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.restore-form-section h2 {
    color: #28a745;
    margin-bottom: 1.5rem;
}

.form-input-readonly {
    padding: 0.75rem;
    border: 2px solid #ddd;
    border-radius: 6px;
    background: #f8f9fa;
    font-size: 1.2rem;
    font-weight: bold;
    color: #2c5530;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.debug-log-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border-left: 4px solid #007bff;
}

.debug-log-section h3 {
    color: #007bff;
    margin-top: 0;
}

.debug-log-content {
    background: #fff;
    padding: 1rem;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    max-height: 400px;
    overflow-y: auto;
}

.debug-log-entry {
    padding: 0.25rem 0;
    border-bottom: 1px solid #eee;
}

.debug-log-entry:last-child {
    border-bottom: none;
}

.archive-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.archive-empty {
    opacity: 0.6;
    border-left-color: #dc3545 !important;
}

.archive-status-warning {
    color: #856404;
    font-weight: bold;
}

.btn-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Delete Archives Section */
.delete-archives-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.delete-archives-section h2 {
    color: #ff6b35;
    margin-bottom: 1.5rem;
}

.info-box {
    background: #e7f3ff;
    border: 2px solid #007bff;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.info-box h3 {
    color: #004085;
    margin-top: 0;
}

.deletion-preview {
    margin-bottom: 2rem;
}

.deletion-preview h3 {
    color: #2c5530;
    margin-bottom: 1rem;
}

.deletion-preview h4 {
    color: #666;
    margin: 1.5rem 0 0.75rem 0;
    font-size: 1.1rem;
}

.deletion-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.deletion-stat {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.deletion-label {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
}

.deletion-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #2c5530;
}

.deletion-list, .kept-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.deletion-item, .kept-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    background: #f8f9fa;
}

.deletion-item {
    border-left: 4px solid #dc3545;
}

.kept-item {
    border-left: 4px solid #28a745;
}

.deletion-item-name, .kept-item-name {
    font-weight: 500;
    color: #333;
}

.deletion-item-size, .kept-item-size {
    color: #666;
    font-size: 0.9rem;
}

.delete-form {
    margin-top: 2rem;
}

.btn-warning {
    background: #ff6b35;
    color: white;
}

.btn-warning:hover {
    background: #e55a28;
}
</style>

<script nonce="<?php echo $cspNonce; ?>">
// Form validation
document.getElementById('resetForm').addEventListener('submit', function(e) {
    const year = document.getElementById('year').value.trim();
    const confirmationCode = document.getElementById('confirmation_code').value.trim();
    const expectedCode = 'RESET ' + year;

    if (!year.match(/^\d{4}$/)) {
        alert('Please enter a valid 4-digit year.');
        e.preventDefault();
        return;
    }

    if (confirmationCode !== expectedCode) {
        alert(`Confirmation code incorrect.\n\nYou must type exactly: ${expectedCode}`);
        e.preventDefault();
        return;
    }

    // Final confirmation
    const message = `⚠️ FINAL CONFIRMATION ⚠️\n\n` +
                    `This will:\n` +
                    `• Archive year ${year}\n` +
                    `• DELETE all children, families, and sponsorships\n` +
                    `• This CANNOT be undone!\n\n` +
                    `Are you ABSOLUTELY SURE you want to proceed?`;

    if (!confirm(message)) {
        e.preventDefault();
        return;
    }

    // Disable button to prevent double-submit
    document.getElementById('resetButton').disabled = true;
    document.getElementById('resetButton').textContent = '⏳ Processing...';

    // Allow form to submit naturally (don't prevent default)
    // Form will submit after this event handler completes
});

// Restore form functions
function showRestoreForm(year, timestamp, hasData) {
    if (!hasData) {
        alert('Cannot restore an empty archive. This archive contains 0 records.');
        return;
    }

    // Set the year
    document.getElementById('restoreYear').value = year;
    document.getElementById('restoreYearDisplay').textContent = year + ' - ' + timestamp.replace('_', ' at ');
    document.getElementById('restoreYearHint').textContent = year;

    // Show the form
    document.getElementById('restoreFormSection').style.display = 'block';

    // Scroll to the form
    document.getElementById('restoreFormSection').scrollIntoView({ behavior: 'smooth' });

    // Clear previous confirmation
    document.getElementById('restore_confirmation_code').value = '';
}

function hideRestoreForm() {
    document.getElementById('restoreFormSection').style.display = 'none';
    document.getElementById('restoreYear').value = '';
    document.getElementById('restore_confirmation_code').value = '';
}

// Restore form validation
const restoreFormEl = document.getElementById('restoreForm');
if (restoreFormEl) {
    restoreFormEl.addEventListener('submit', function(e) {
        const year = document.getElementById('restoreYear').value.trim();
        const confirmationCode = document.getElementById('restore_confirmation_code').value.trim();
        const expectedCode = 'RESTORE ' + year;

        if (!year) {
            alert('Please select a year to restore.');
            e.preventDefault();
            return;
        }

        if (confirmationCode !== expectedCode) {
            alert(`Confirmation code incorrect.\n\nYou must type exactly: ${expectedCode}`);
            e.preventDefault();
            return;
        }

        // Final confirmation
        const message = `⚠️ FINAL CONFIRMATION ⚠️\n\n` +
                        `This will:\n` +
                        `• OVERWRITE all current database data\n` +
                        `• Restore archive from year ${year}\n` +
                        `• Current data will be LOST!\n` +
                        `• This CANNOT be undone!\n\n` +
                        `Are you ABSOLUTELY SURE you want to proceed?`;

        if (!confirm(message)) {
            e.preventDefault();
            return;
        }

        // Disable button to prevent double-submit
        document.getElementById('restoreButton').disabled = true;
        document.getElementById('restoreButton').textContent = '⏳ Restoring...';
    });
}
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
