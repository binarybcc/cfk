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

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Import Children from CSV';
$message = '';
$messageType = '';
$importResults = null;

// Handle file upload and import
if ($_POST && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Security token invalid. Please try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'import_csv':
                $result = handleCsvImport();
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
        }
    }
}

function handleCsvImport(): array {
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
        
        // Process the CSV
        $options = [
            'update_existing' => isset($_POST['update_existing']),
            'dry_run' => isset($_POST['dry_run'])
        ];
        
        $results = CFK_CSV_Handler::importChildrenFromCsv($file['tmp_name'], $options);
        
        if ($results['success']) {
            $message = $options['dry_run'] ? 
                'Preview completed successfully. No data was actually imported.' :
                'CSV import completed successfully!';
            
            return [
                'success' => true,
                'message' => $message,
                'results' => [
                    'processed' => $results['imported'] + count($results['errors']),
                    'imported' => $results['imported'],
                    'errors' => $results['errors'],
                    'warnings' => $results['warnings'],
                    'success' => $results['success'],
                    'message' => $results['message']
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => $results['message']
            ];
        }
        
    } catch (Exception $e) {
        error_log('CSV import error: ' . $e->getMessage());
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
            <a href="login.php?logout=1">Logout</a>
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
        </div>

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
                    📂 Step 3: Upload & Import
                </div>
                <div class="section-body">
                    <form method="POST" enctype="multipart/form-data" id="importForm" class="import-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="import_csv">

                        <div class="form-group">
                            <label for="csv_file">Select CSV File</label>
                            <div class="file-input-wrapper">
                                <div class="file-input" id="fileInput">
                                    <input type="file" 
                                           id="csv_file" 
                                           name="csv_file" 
                                           accept=".csv,.txt" 
                                           required>
                                    <div class="file-input-label">
                                        📁 Click to select CSV file or drag and drop
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="dry_run" name="dry_run" checked>
                                <label for="dry_run">Preview Import (don't actually import data)</label>
                            </div>
                            <div class="help-text">Recommended for first-time imports to check for errors</div>


                            <div class="checkbox-item">
                                <input type="checkbox" id="update_existing" name="update_existing">
                                <label for="update_existing">Update existing children</label>
                            </div>
                            <div class="help-text">Update child records if family_id + child_letter already exists</div>
                        </div>

                        <div style="text-align: center; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary" id="importBtn">
                                🚀 Start Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Import Results -->
        <?php if ($importResults): ?>
            <div class="results-section section">
                <div class="section-header">
                    📊 Import Results
                </div>
                <div class="section-body">
                    <div class="results-grid">
                        <div class="result-card">
                            <div class="result-number"><?php echo $importResults['processed'] ?? 0; ?></div>
                            <div class="result-label">Rows Processed</div>
                        </div>
                        <div class="result-card">
                            <div class="result-number"><?php echo $importResults['imported'] ?? 0; ?></div>
                            <div class="result-label">Successfully Imported</div>
                        </div>
                        <div class="result-card">
                            <div class="result-number"><?php echo count($importResults['errors'] ?? []); ?></div>
                            <div class="result-label">Errors</div>
                        </div>
                    </div>

                    <?php if (isset($importResults['error_details']) && !empty($importResults['error_details'])): ?>
                        <h4 style="color: #721c24; margin-top: 1.5rem;">Errors Found:</h4>
                        <div class="error-list">
                            <?php foreach ($importResults['error_details'] as $error): ?>
                                <div class="error-item">
                                    <strong>Row <?php echo $error['row'] ?? '?'; ?>:</strong> 
                                    <?php echo sanitizeString($error['message'] ?? 'Unknown error'); ?>
                                    <?php if (isset($error['data'])): ?>
                                        <br><small>Data: <?php echo sanitizeString(substr(json_encode($error['data']), 0, 100)); ?>...</small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_POST['dry_run']) && $importResults['imported'] > 0): ?>
                        <div class="alert alert-success" style="border-left: 5px solid #28a745; background: #d4edda;">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                <div style="font-size: 2rem;">✅</div>
                                <div>
                                    <h3 style="margin: 0; color: #155724;">Preview Successful!</h3>
                                    <p style="margin: 0; color: #155724;">Your CSV file is valid and ready to import <?php echo $importResults['imported']; ?> children.</p>
                                </div>
                            </div>
                            
                            <div style="background: #fff; padding: 1rem; border-radius: 6px; border: 1px solid #c3e6cb;">
                                <h4 style="margin: 0 0 0.5rem 0; color: #155724;">📋 Next Steps:</h4>
                                <ol style="margin: 0; padding-left: 1.5rem; color: #155724;">
                                    <li><strong>Uncheck the "Preview Import" checkbox below</strong></li>
                                    <li><strong>Click "🚀 Start Import" to save the data to your database</strong></li>
                                </ol>
                                <div style="margin-top: 1rem; padding: 0.5rem; background: #f8f9fa; border-radius: 4px; border-left: 3px solid #28a745;">
                                    <small style="color: #155724;"><strong>💡 Important:</strong> The preview did NOT import any data. You must run the import again with preview unchecked to actually save the records.</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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