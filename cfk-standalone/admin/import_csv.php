<?php
declare(strict_types=1);

/**
 * Admin - CSV Import Interface
 * User-friendly bulk import of child data from standardized CSV files
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csv_handler.php';
require_once __DIR__ . '/../includes/backup_manager.php';
require_once __DIR__ . '/../includes/import_analyzer.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Import Children from CSV';
$message = '';
$messageType = '';
$importResults = null;
$previewData = null;

// Handle file upload and import
if ($_POST && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Security token invalid. Please try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'preview_import':
                $result = handlePreviewImport();
                if ($result['success']) {
                    $previewData = $result['preview'];
                } else {
                    $message = $result['message'];
                    $messageType = 'error';
                }
                break;

            case 'confirm_import':
                $result = handleConfirmImport();
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                if ($result['success'] && isset($result['results'])) {
                    $importResults = $result['results'];
                }
                break;

            case 'download_template':
                downloadTemplate();
                break;

            case 'delete_all_children':
                $result = handleDeleteAllChildren();
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;

            case 'restore_backup':
                $result = handleRestoreBackup();
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;

            case 'download_backup':
                handleDownloadBackup();
                break;
        }
    }
}

function handlePreviewImport(): array {
    try {
        // Check if file was uploaded
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Please select a valid CSV file to upload.'];
        }

        $file = $_FILES['csv_file'];

        // Validate file type
        if ($file['type'] !== 'text/csv' && !in_array(pathinfo($file['name'], PATHINFO_EXTENSION), ['csv', 'txt'])) {
            return ['success' => false, 'message' => 'Please upload a CSV file (.csv extension).'];
        }

        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File size too large. Maximum size is 5MB.'];
        }

        // Save file to temp location for confirmation step
        $tempDir = sys_get_temp_dir() . '/cfk_uploads';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0700, true);
        }

        $tempFile = $tempDir . '/upload_' . session_id() . '.csv';
        if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
            return ['success' => false, 'message' => 'Failed to process uploaded file.'];
        }

        // Parse CSV for preview
        $handler = new CFK_CSV_Handler();
        $parseResult = $handler->parseCSVForPreview($tempFile);

        if (!$parseResult['success']) {
            @unlink($tempFile);
            return [
                'success' => false,
                'message' => 'CSV parsing failed: ' . implode(', ', $parseResult['errors'] ?? ['Unknown error'])
            ];
        }

        // Analyze changes
        $analysis = CFK_Import_Analyzer::analyzeImport($parseResult['children']);

        // Store temp file path in session
        $_SESSION['cfk_import_file'] = $tempFile;
        $_SESSION['cfk_import_filename'] = $file['name'];

        return [
            'success' => true,
            'preview' => [
                'analysis' => $analysis,
                'filename' => $file['name'],
                'total_rows' => count($parseResult['children']),
                'parse_warnings' => $parseResult['warnings'] ?? []
            ]
        ];

    } catch (Exception $e) {
        error_log('CSV preview error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'System error occurred during preview. Please try again.'];
    }
}

function handleConfirmImport(): array {
    try {
        // Check if we have a file in session
        if (!isset($_SESSION['cfk_import_file']) || !file_exists($_SESSION['cfk_import_file'])) {
            return ['success' => false, 'message' => 'No file to import. Please upload again.'];
        }

        $tempFile = $_SESSION['cfk_import_file'];
        $keepInactive = isset($_POST['keep_inactive_children']);

        // CREATE AUTOMATIC BACKUP BEFORE IMPORT
        $backupResult = CFK_Backup_Manager::createAutoBackup('csv_import');
        if (!$backupResult['success']) {
            error_log('Backup creation failed: ' . $backupResult['message']);
        }

        // Apply import with sponsorship preservation
        $result = CFK_Import_Analyzer::applyImportWithPreservation($tempFile, [
            'keep_inactive' => $keepInactive
        ]);

        // Clean up temp file
        @unlink($tempFile);
        unset($_SESSION['cfk_import_file']);
        unset($_SESSION['cfk_import_filename']);

        if ($result['success']) {
            $sponsorMsg = isset($result['sponsorships_preserved']) && $result['sponsorships_preserved'] > 0
                ? " ({$result['sponsorships_preserved']} sponsorships preserved)"
                : '';

            return [
                'success' => true,
                'message' => "Successfully imported {$result['imported']} children!{$sponsorMsg}",
                'results' => $result
            ];
        } else {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Import failed'
            ];
        }

    } catch (Exception $e) {
        error_log('CSV confirm import error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'System error occurred during import. Please try again.'];
    }
}

function handleDeleteAllChildren(): array {
    try {
        // Require confirmation
        if (!isset($_POST['confirm_delete']) || $_POST['confirm_delete'] !== 'DELETE') {
            return ['success' => false, 'message' => 'Please type "DELETE" in the confirmation box to proceed.'];
        }
        
        // Get count before deletion for confirmation
        $countResult = Database::fetchRow("SELECT COUNT(*) as total FROM children");
        $count = $countResult['total'] ?? 0;
        
        if ($count == 0) {
            return ['success' => false, 'message' => 'No children records found to delete.'];
        }
        
        // Delete all children records
        Database::query("DELETE FROM children");
        
        // Also delete related family records if they exist
        Database::query("DELETE FROM families");
        
        // Delete related sponsorships if table exists
        try {
            Database::query("DELETE FROM cfk_sponsorships");
        } catch (Exception $e) {
            // Table might not exist, continue without error
            error_log('cfk_sponsorships table not found during delete: ' . $e->getMessage());
        }
        
        return [
            'success' => true, 
            'message' => "Successfully deleted {$count} children records and all related data."
        ];
        
    } catch (Exception $e) {
        error_log('Delete all children error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'System error occurred during deletion. Please try again.'];
    }
}

function handleRestoreBackup(): array {
    try {
        $filename = $_POST['backup_filename'] ?? '';
        if (empty($filename)) {
            return ['success' => false, 'message' => 'Please select a backup file to restore.'];
        }

        $clearExisting = isset($_POST['clear_existing']);
        $result = CFK_Backup_Manager::restoreFromBackup($filename, $clearExisting);

        return $result;

    } catch (Exception $e) {
        error_log('Restore backup error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'System error occurred during restore. Please try again.'];
    }
}

function handleDownloadBackup(): void {
    $filename = $_POST['backup_filename'] ?? $_GET['file'] ?? '';
    if (empty($filename)) {
        http_response_code(400);
        die('No backup file specified');
    }

    CFK_Backup_Manager::downloadBackup($filename);
}

function downloadTemplate(): void {
    $filename = 'cfk-import-template.csv';
    $templatePath = __DIR__ . '/../templates/' . $filename;

    if (file_exists($templatePath)) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($templatePath));
        readfile($templatePath);
        exit;
    } else {
        // Generate template on the fly if file doesn't exist
        $headers = [
            'family_id',
            'child_letter',
            'age',
            'gender',
            'grade',
            'shirt_size',
            'pant_size',
            'shoe_size',
            'jacket_size',
            'interests',
            'greatest_need',
            'wish_list',
            'special_needs',
            'family_situation'
        ];

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cfk-import-template.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);

        // Add sample rows
        fputcsv($output, [
            '001', 'A', 8, 'F', '3rd', 'Girls 8', 'Girls 8', 'Youth 3', 'Girls 8',
            'Sports, Art', 'Winter Coat, Socks', 'Soccer Ball, Art Supplies', 'None',
            'Single mother, working two jobs'
        ]);

        fputcsv($output, [
            '001', 'B', 6, 'M', '1st', 'Boys 6', 'Boys 6', 'Youth 1', 'Boys 6',
            'Cars, Building', 'Shoes, Underwear', 'Lego Sets, Hot Wheels', 'None',
            'Single mother, working two jobs'
        ]);

        fclose($output);
        exit;
    }
}

// Get current statistics
$stats = [
    'total_children' => getChildrenCount([])
];

// Get backup information
$backupStats = CFK_Backup_Manager::getBackupStats();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Christmas for Kids Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c5530;
            display: block;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: #2c5530;
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .section-body {
            padding: 1.5rem;
        }

        .template-section {
            grid-column: span 2;
            margin-bottom: 2rem;
        }

        .import-form {
            margin-top: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px dashed #ddd;
            border-radius: 6px;
            background: #f9f9f9;
            cursor: pointer;
            transition: border-color 0.2s, background-color 0.2s;
        }

        .file-input:hover {
            border-color: #2c5530;
            background: #f0f8f0;
        }

        .file-input input[type="file"] {
            opacity: 0;
            position: absolute;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: block;
            text-align: center;
            color: #666;
        }

        .file-input.has-file {
            border-color: #2c5530;
            background: #f0f8f0;
        }

        .file-input.has-file .file-input-label {
            color: #2c5530;
            font-weight: 500;
        }

        .checkbox-group {
            margin: 1rem 0;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .checkbox-item input[type="checkbox"] {
            margin-right: 0.5rem;
        }

        .checkbox-item label {
            margin: 0;
            font-weight: normal;
            color: #333;
            cursor: pointer;
        }

        .help-text {
            font-size: 0.9rem;
            color: #666;
            margin-left: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background 0.2s;
            margin-right: 0.5rem;
        }

        .btn-primary {
            background: #2c5530;
            color: white;
        }

        .btn-primary:hover {
            background: #1e3a21;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .requirements {
            background: #f8f9fa;
            border-left: 4px solid #2c5530;
            padding: 1rem;
            margin: 1rem 0;
        }

        .requirements h4 {
            color: #2c5530;
            margin-bottom: 0.5rem;
        }

        .requirements ul {
            margin-left: 1.5rem;
            color: #666;
        }

        .requirements li {
            margin-bottom: 0.25rem;
        }

        .results-section {
            grid-column: span 2;
            margin-top: 2rem;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .result-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            border-left: 4px solid #2c5530;
        }

        .result-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c5530;
        }

        .result-label {
            font-size: 0.9rem;
            color: #666;
        }

        .error-list {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 1rem;
            margin: 1rem 0;
            max-height: 300px;
            overflow-y: auto;
        }

        .error-item {
            color: #721c24;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .progress-bar {
            background: #f0f0f0;
            border-radius: 10px;
            height: 8px;
            margin: 1rem 0;
            overflow: hidden;
        }

        .progress-fill {
            background: #2c5530;
            height: 100%;
            transition: width 0.3s ease;
        }

        .sample-format {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .template-section {
                grid-column: span 1;
            }
            
            .results-section {
                grid-column: span 1;
            }
        }

        /* Backup Management Styles */
        .backup-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1rem;
        }

        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }

        .backup-item:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-color: #17a2b8;
        }

        .backup-info {
            flex: 1;
        }

        .backup-filename {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            color: #2c5530;
        }

        .backup-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.875rem;
            color: #666;
        }

        .backup-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .backup-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
            border: 1px solid #ffc107;
        }

        .btn-warning:hover {
            background: #e0a800;
            border-color: #e0a800;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: 1px solid #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
            border-color: #545b62;
        }

        @media (max-width: 768px) {
            .backup-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .backup-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .backup-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>Import Children from CSV</h1>
        <nav class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="manage_children.php">Manage Children</a>
            <a href="manage_sponsorships.php">Sponsorships</a>
            <a href="../index.php" target="_blank">View Site</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo sanitizeString($message); ?>
            </div>
        <?php endif; ?>

        <!-- Current Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['total_children']; ?></span>
                <div class="stat-label">Total Children</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $backupStats['total_backups']; ?></span>
                <div class="stat-label">Backup Files Available</div>
            </div>
        </div>

        <!-- Backup Management Section -->
        <?php if ($backupStats['total_backups'] > 0): ?>
        <div class="backup-section section">
            <div class="section-header" style="background-color: #d1ecf1; color: #0c5460;">
                💾 Automatic Backups (Last <?php echo $backupStats['max_backups']; ?> Versions)
            </div>
            <div class="section-body">
                <div class="alert alert-info">
                    <strong>ℹ️ Auto-Protection Enabled:</strong> Every CSV import automatically creates a backup first. You can restore any of the last <?php echo $backupStats['max_backups']; ?> backups below.
                </div>

                <div class="backup-list">
                    <?php foreach ($backupStats['backups'] as $backup): ?>
                        <?php
                        $metadata = $backup['metadata'];
                        $created = date('F j, Y g:i A', $backup['created']);
                        $size = round($backup['size'] / 1024, 1);
                        ?>
                        <div class="backup-item">
                            <div class="backup-info">
                                <div class="backup-filename">
                                    <strong>📄 <?php echo htmlspecialchars($backup['filename']); ?></strong>
                                </div>
                                <div class="backup-meta">
                                    <span>🕐 <?php echo $created; ?></span>
                                    <span>📊 <?php echo $metadata['children_count'] ?? 0; ?> children</span>
                                    <span>👨‍👩‍👧‍👦 <?php echo $metadata['families_count'] ?? 0; ?> families</span>
                                    <span>💾 <?php echo $size; ?> KB</span>
                                </div>
                            </div>
                            <div class="backup-actions">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Restore this backup? Current data will be replaced.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="restore_backup">
                                    <input type="hidden" name="backup_filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                    <input type="hidden" name="clear_existing" value="1">
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        ♻️ Restore
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="download_backup">
                                    <input type="hidden" name="backup_filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        💾 Download
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="backup-section section">
            <div class="section-header" style="background-color: #d1ecf1; color: #0c5460;">
                💾 Automatic Backups
            </div>
            <div class="section-body">
                <div class="alert alert-info">
                    <strong>ℹ️ No backups yet.</strong> Backups will be created automatically when you import your first CSV file.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Delete All Children Section -->
        <div class="delete-section section">
            <div class="section-header" style="background-color: #f8d7da; color: #721c24;">
                🗑️ Danger Zone: Delete All Children Records
            </div>
            <div class="section-body">
                <div class="alert alert-warning">
                    <strong>⚠️ Warning:</strong> This will permanently delete ALL children records and related data. This action cannot be undone!
                </div>
                
                <form method="post" class="delete-form" onsubmit="return confirmDelete(this);">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="delete_all_children">
                    
                    <div class="form-group">
                        <label for="confirm_delete">Type "DELETE" to confirm:</label>
                        <input type="text" id="confirm_delete" name="confirm_delete" required 
                               placeholder="Type DELETE to confirm" style="width: 200px; margin-left: 10px;">
                        <button type="submit" class="btn btn-danger" style="margin-left: 10px;">
                            🗑️ Delete All Records
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Template Download Section -->
        <div class="template-section section">
            <div class="section-header">
                📋 Step 1: Download CSV Template
            </div>
            <div class="section-body">
                <p>Start by downloading our standardized CSV template. This ensures your data is formatted correctly for import.</p>
                
                <form method="POST" style="margin-top: 1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="download_template">
                    <button type="submit" class="btn btn-success">📥 Download CSV Template</button>
                </form>

                <div class="requirements">
                    <h4>Template Format</h4>
                    <div class="sample-format">
family_id,child_letter,age,gender,grade,shirt_size,pant_size,shoe_size,jacket_size,interests,greatest_need,wish_list,special_needs,family_situation
"001","A",8,"F","3rd","Girls 8","Girls 8","Youth 3","Girls 8","Sports, Art","Winter Coat","Soccer Ball","None","Single mother"
                    </div>
                    <ul>
                        <li><strong>family_id</strong>: Unique family number (001, 002, etc.)</li>
                        <li><strong>child_letter</strong>: Child identifier within family (A, B, C, etc.)</li>
                        <li><strong>age</strong>: Child's age (1-18)</li>
                        <li><strong>gender</strong>: M or F</li>
                        <li><strong>grade</strong>: School grade (Pre-K, K, 1st, 2nd, etc.)</li>
                        <li><strong>Clothing sizes</strong>: Standard clothing sizes</li>
                        <li><strong>interests</strong>: Comma-separated hobbies/interests</li>
                        <li><strong>greatest_need</strong>: Essential items needed</li>
                        <li><strong>wish_list</strong>: Christmas gift wishes</li>
                        <li><strong>special_needs</strong>: Any special considerations</li>
                        <li><strong>family_situation</strong>: Brief family background</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="main-content">
            <!-- Import Instructions -->
            <div class="section">
                <div class="section-header">
                    📝 Step 2: Prepare Your Data
                </div>
                <div class="section-body">
                    <div class="requirements">
                        <h4>Before Importing:</h4>
                        <ul>
                            <li>Fill out the CSV template with your child data</li>
                            <li>Ensure all family_id values are unique</li>
                            <li>Use consistent child_letter assignments (A, B, C for siblings)</li>
                            <li>Double-check ages and gender values</li>
                            <li>Save your file as CSV format (.csv)</li>
                        </ul>
                    </div>

                    <div class="alert alert-info">
                        <strong>💡 Pro Tip:</strong> Use the "Preview Import" option first to check your data without actually importing it!
                    </div>
                </div>
            </div>

            <!-- Import Form -->
            <div class="section">
                <div class="section-header">
                    📂 Step 3: Upload & Preview
                </div>
                <div class="section-body">
                    <div class="alert alert-info">
                        <strong>💡 How it works:</strong> Upload your CSV file and the system will automatically analyze what will change. You'll see a preview before anything is imported, and can confirm or cancel.
                    </div>

                    <!-- Alpine.js Live Validation Wrapper -->
                    <div x-data="{
                        file: null,
                        fileName: '',
                        fileSize: 0,
                        errors: [],
                        warnings: [],

                        handleFileSelect(event) {
                            this.file = event.target.files[0];
                            this.fileName = this.file ? this.file.name : '';
                            this.fileSize = this.file ? this.file.size : 0;
                            this.validate();
                        },

                        validate() {
                            this.errors = [];
                            this.warnings = [];

                            if (!this.file) {
                                this.errors.push('Please select a file to upload');
                                return false;
                            }

                            // File extension check
                            const ext = this.fileName.toLowerCase().split('.').pop();
                            if (ext !== 'csv' && ext !== 'txt') {
                                this.errors.push('File must be a CSV file (.csv extension)');
                            }

                            // File size check (5MB limit)
                            if (this.fileSize > 5 * 1024 * 1024) {
                                this.errors.push('File size exceeds 5MB limit');
                            }

                            // Warning for large files (1MB+)
                            if (this.fileSize > 1 * 1024 * 1024) {
                                this.warnings.push('Large file detected - import may take several minutes');
                            }

                            return this.errors.length === 0;
                        },

                        formatFileSize(bytes) {
                            if (bytes === 0) return '0 Bytes';
                            const k = 1024;
                            const sizes = ['Bytes', 'KB', 'MB'];
                            const i = Math.floor(Math.log(bytes) / Math.log(k));
                            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
                        }
                    }">
                        <form method="POST"
                              enctype="multipart/form-data"
                              id="importForm"
                              class="import-form"
                              @submit="if (!validate()) { $event.preventDefault(); }">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="preview_import">

                            <div class="form-group">
                                <label for="csv_file">Select CSV File</label>
                                <div class="file-input-wrapper">
                                    <div class="file-input" id="fileInput">
                                        <input type="file"
                                               id="csv_file"
                                               name="csv_file"
                                               accept=".csv,.txt"
                                               required
                                               @change="handleFileSelect($event)">
                                        <div class="file-input-label">
                                            📁 Click to select CSV file or drag and drop
                                        </div>
                                    </div>
                                </div>

                                <!-- Live File Info Display -->
                                <div x-show="file" x-transition class="file-info-display" style="margin-top: 1rem;">
                                    <div class="alert alert-info">
                                        <p style="margin: 0;">
                                            <strong>Selected File:</strong> <span x-text="fileName"></span><br>
                                            <strong>Size:</strong> <span x-text="formatFileSize(fileSize)"></span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Live Error Messages -->
                                <div x-show="errors.length > 0" x-transition class="alert alert-danger" style="margin-top: 1rem;">
                                    <strong>⚠️ Cannot Upload:</strong>
                                    <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                                        <template x-for="error in errors" :key="error">
                                            <li x-text="error" style="margin-bottom: 0.25rem;"></li>
                                        </template>
                                    </ul>
                                </div>

                                <!-- Live Warning Messages -->
                                <div x-show="warnings.length > 0 && errors.length === 0"
                                     x-transition
                                     class="alert alert-warning"
                                     style="margin-top: 1rem;">
                                    <strong>⚡ Notice:</strong>
                                    <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                                        <template x-for="warning in warnings" :key="warning">
                                            <li x-text="warning" style="margin-bottom: 0.25rem;"></li>
                                        </template>
                                    </ul>
                                </div>
                            </div>

                            <div style="text-align: center; margin-top: 2rem;">
                                <button type="submit"
                                        class="btn btn-primary"
                                        id="importBtn"
                                        :disabled="errors.length > 0 || !file"
                                        :style="{ opacity: (errors.length > 0 || !file) ? '0.5' : '1', cursor: (errors.length > 0 || !file) ? 'not-allowed' : 'pointer' }">
                                    🔍 Upload & Preview
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Results -->
        <?php if ($previewData): ?>
            <div class="preview-section section">
                <div class="section-header" style="background-color: #d1ecf1; color: #0c5460;">
                    🔍 Preview: What Will Change
                </div>
                <div class="section-body">
                    <div class="alert alert-success">
                        <strong>✅ CSV Parsed Successfully!</strong> File: <code><?php echo htmlspecialchars($previewData['filename']); ?></code> (<?php echo $previewData['total_rows']; ?> rows)
                    </div>

                    <!-- Change Statistics -->
                    <div class="results-grid">
                        <div class="result-card" style="border-left: 4px solid #28a745;">
                            <div class="result-number"><?php echo $previewData['analysis']['stats']['total_new']; ?></div>
                            <div class="result-label">New Children</div>
                        </div>
                        <div class="result-card" style="border-left: 4px solid #17a2b8;">
                            <div class="result-number"><?php echo $previewData['analysis']['stats']['total_updated']; ?></div>
                            <div class="result-label">Updated</div>
                        </div>
                        <div class="result-card" style="border-left: 4px solid #ffc107;">
                            <div class="result-number"><?php echo $previewData['analysis']['stats']['total_removed']; ?></div>
                            <div class="result-label">Removed</div>
                        </div>
                        <div class="result-card" style="border-left: 4px solid #6c757d;">
                            <div class="result-number"><?php echo $previewData['analysis']['stats']['total_unchanged']; ?></div>
                            <div class="result-label">Unchanged</div>
                        </div>
                    </div>

                    <!-- Warnings -->
                    <?php if (!empty($previewData['analysis']['warnings'])): ?>
                        <div class="alert alert-warning" style="margin-top: 1.5rem;">
                            <h4 style="margin-bottom: 1rem;">⚠️ Important Changes Detected:</h4>
                            <ul style="margin: 0; padding-left: 1.5rem;">
                                <?php foreach ($previewData['analysis']['warnings'] as $warning): ?>
                                    <li style="margin-bottom: 0.5rem;">
                                        <strong><?php echo htmlspecialchars($warning['message']); ?></strong>
                                        <?php if ($warning['type'] === 'sponsored_child_removed'): ?>
                                            <br><small style="color: #856404;">This child has an active sponsorship. You can choose to keep them as inactive below.</small>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Confirmation Form -->
                    <form method="POST" style="margin-top: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; border: 2px solid #dee2e6;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="confirm_import">

                        <h4 style="margin-bottom: 1rem;">🎯 Ready to Import?</h4>

                        <?php if ($previewData['analysis']['stats']['total_removed'] > 0): ?>
                            <div class="checkbox-item" style="margin-bottom: 1rem;">
                                <input type="checkbox" id="keep_inactive_children" name="keep_inactive_children" checked>
                                <label for="keep_inactive_children">
                                    <strong>Keep removed children as "Inactive"</strong> instead of deleting them
                                </label>
                                <div class="help-text">Recommended if children have sponsorships or you want to preserve their history.</div>
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                            <button type="submit" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem;">
                                ✅ Confirm Import
                            </button>
                            <a href="?" class="btn btn-secondary" style="padding: 1rem 2rem; font-size: 1.1rem; text-decoration: none;">
                                ❌ Cancel
                            </a>
                        </div>

                        <div class="alert alert-info" style="margin-top: 1.5rem; background: #fff; border: 1px solid #17a2b8;">
                            <small>
                                <strong>📌 Note:</strong>
                                • A backup will be created automatically before import<br>
                                • Sponsorship status will be preserved for matching children<br>
                                • You can restore from backup if needed
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Import Success Results -->
        <?php if ($importResults): ?>
            <div class="results-section section">
                <div class="section-header" style="background-color: #d4edda; color: #155724;">
                    ✅ Import Complete!
                </div>
                <div class="section-body">
                    <div class="alert alert-success">
                        <h3 style="margin-bottom: 0.5rem;">🎉 Success!</h3>
                        <p style="margin: 0;"><?php echo htmlspecialchars($message); ?></p>
                    </div>

                    <div class="results-grid" style="margin-top: 1.5rem;">
                        <div class="result-card" style="border-left: 4px solid #28a745;">
                            <div class="result-number"><?php echo $importResults['imported'] ?? 0; ?></div>
                            <div class="result-label">Children Imported</div>
                        </div>
                        <?php if (isset($importResults['sponsorships_preserved']) && $importResults['sponsorships_preserved'] > 0): ?>
                            <div class="result-card" style="border-left: 4px solid #17a2b8;">
                                <div class="result-number"><?php echo $importResults['sponsorships_preserved']; ?></div>
                                <div class="result-label">Sponsorships Preserved</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="?" class="btn btn-primary">Import Another File</a>
                        <a href="manage_children.php" class="btn btn-secondary">View Children</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // File input handling
        document.getElementById('csv_file').addEventListener('change', function() {
            const fileInput = document.getElementById('fileInput');
            const label = fileInput.querySelector('.file-input-label');
            
            if (this.files && this.files[0]) {
                fileInput.classList.add('has-file');
                label.textContent = '📄 ' + this.files[0].name;
            } else {
                fileInput.classList.remove('has-file');
                label.textContent = '📁 Click to select CSV file or drag and drop';
            }
        });

        // Form submission handling
        document.getElementById('importForm').addEventListener('submit', function() {
            const importBtn = document.getElementById('importBtn');
            const isDryRun = document.getElementById('dry_run').checked;
            
            importBtn.textContent = isDryRun ? '🔍 Processing Preview...' : '🚀 Importing Data...';
            importBtn.disabled = true;
            
            // Re-enable button after 30 seconds as fallback
            setTimeout(() => {
                importBtn.textContent = '🚀 Start Import';
                importBtn.disabled = false;
            }, 30000);
        });

        // Drag and drop functionality
        const fileInput = document.getElementById('fileInput');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileInput.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileInput.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileInput.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            fileInput.style.borderColor = '#2c5530';
            fileInput.style.backgroundColor = '#f0f8f0';
        }

        function unhighlight(e) {
            fileInput.style.borderColor = '#ddd';
            fileInput.style.backgroundColor = '#f9f9f9';
        }

        fileInput.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                document.getElementById('csv_file').files = files;
                document.getElementById('csv_file').dispatchEvent(new Event('change'));
            }
        }
        
        // Delete confirmation function
        function confirmDelete(form) {
            const confirmText = form.confirm_delete.value;
            if (confirmText !== 'DELETE') {
                alert('Please type "DELETE" exactly to confirm deletion.');
                return false;
            }
            
            return confirm('Are you absolutely sure you want to delete ALL children records? This action cannot be undone!');
        }

        // Enhanced preview success handling
        document.addEventListener('DOMContentLoaded', function() {
            const previewSuccessAlert = document.querySelector('.alert-success');
            const dryRunCheckbox = document.getElementById('dry_run');
            const importBtn = document.getElementById('importBtn');
            
            // If preview was successful, highlight the next steps
            if (previewSuccessAlert && dryRunCheckbox) {
                // Scroll to show the success message
                previewSuccessAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Add visual emphasis to the checkbox area
                const checkboxGroup = dryRunCheckbox.closest('.checkbox-group');
                if (checkboxGroup) {
                    checkboxGroup.style.border = '2px solid #28a745';
                    checkboxGroup.style.borderRadius = '6px';
                    checkboxGroup.style.padding = '1rem';
                    checkboxGroup.style.background = '#f8fff9';
                    
                    // Add a pulsing animation to draw attention
                    checkboxGroup.style.animation = 'gentle-pulse 2s ease-in-out 3';
                }
                
                // Update button text to be more specific
                if (importBtn) {
                    importBtn.innerHTML = '🚀 Import ' + <?php echo isset($importResults) ? $importResults['imported'] : 0; ?> + ' Children';
                }
                
                // Add click handler to checkbox to update button text
                dryRunCheckbox.addEventListener('change', function() {
                    if (importBtn) {
                        if (this.checked) {
                            importBtn.innerHTML = '🔍 Preview Import';
                        } else {
                            importBtn.innerHTML = '🚀 Import ' + <?php echo isset($importResults) ? $importResults['imported'] : 0; ?> + ' Children';
                        }
                    }
                });
            }
        });
        
        // Add CSS animation for gentle pulse
        const style = document.createElement('style');
        style.textContent = `
            @keyframes gentle-pulse {
                0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
                50% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0.1); }
                100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>