<?php
declare(strict_types=1);

/**
 * Admin page for CSV import functionality
 * 
 * This template provides the user interface for uploading and processing
 * CSV files containing child data. It includes upload form, progress tracking,
 * and import results display.
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap cfk-csv-import-page">
    <h1><?php _e('Import Children from CSV', CFK_TEXT_DOMAIN); ?></h1>
    
    <div class="cfk-import-container">
        <!-- Upload Section -->
        <div class="cfk-import-section" id="cfk-upload-section">
            <div class="cfk-card">
                <h2><?php _e('Upload CSV File', CFK_TEXT_DOMAIN); ?></h2>
                
                <div class="cfk-instructions">
                    <p><?php _e('Upload a CSV file containing child information to import multiple children at once.', CFK_TEXT_DOMAIN); ?></p>
                    
                    <h4><?php _e('Required CSV Format:', CFK_TEXT_DOMAIN); ?></h4>
                    <div class="cfk-csv-example">
                        <code>name,age,gender,shirt_size,pants_size,shoe_size,coat_size,interests,family_situation,special_needs</code>
                        <br>
                        <code>John Doe,8,M,Youth M,28,3,10/12,Sports|Reading,Single parent,None</code>
                    </div>
                    
                    <div class="cfk-format-notes">
                        <h4><?php _e('Important Notes:', CFK_TEXT_DOMAIN); ?></h4>
                        <ul>
                            <li><?php _e('<strong>Required fields:</strong> name, age, gender', CFK_TEXT_DOMAIN); ?></li>
                            <li><?php _e('<strong>Age:</strong> Must be between 0-18', CFK_TEXT_DOMAIN); ?></li>
                            <li><?php _e('<strong>Gender:</strong> Use M/F or Male/Female', CFK_TEXT_DOMAIN); ?></li>
                            <li><?php _e('<strong>Interests:</strong> Separate multiple interests with | (pipe) character', CFK_TEXT_DOMAIN); ?></li>
                            <li><?php _e('<strong>File size:</strong> Maximum 5MB', CFK_TEXT_DOMAIN); ?></li>
                            <li><?php _e('<strong>Duplicates:</strong> Children with same name and age will be updated', CFK_TEXT_DOMAIN); ?></li>
                        </ul>
                    </div>
                </div>
                
                <form id="cfk-csv-upload-form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('cfk_csv_import_nonce', 'cfk_csv_import_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="csv_file"><?php _e('CSV File', CFK_TEXT_DOMAIN); ?></label>
                            </th>
                            <td>
                                <input type="file" 
                                       id="csv_file" 
                                       name="csv_file" 
                                       accept=".csv" 
                                       required 
                                       class="cfk-file-input">
                                <p class="description">
                                    <?php _e('Select a CSV file containing child data to import.', CFK_TEXT_DOMAIN); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary" id="cfk-upload-btn">
                            <?php _e('Upload and Validate CSV', CFK_TEXT_DOMAIN); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Preview Section -->
        <div class="cfk-import-section cfk-hidden" id="cfk-preview-section">
            <div class="cfk-card">
                <h2><?php _e('Import Preview', CFK_TEXT_DOMAIN); ?></h2>
                
                <div id="cfk-preview-summary" class="cfk-summary-box">
                    <!-- Summary will be populated via JavaScript -->
                </div>
                
                <div id="cfk-validation-errors" class="cfk-error-box cfk-hidden">
                    <h4><?php _e('Validation Errors:', CFK_TEXT_DOMAIN); ?></h4>
                    <ul id="cfk-error-list"></ul>
                </div>
                
                <div id="cfk-preview-data" class="cfk-preview-table">
                    <h4><?php _e('Sample Data (First 5 Records):', CFK_TEXT_DOMAIN); ?></h4>
                    <table class="wp-list-table widefat fixed striped">
                        <thead id="cfk-preview-header">
                            <!-- Headers will be populated via JavaScript -->
                        </thead>
                        <tbody id="cfk-preview-body">
                            <!-- Data will be populated via JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <div class="cfk-preview-actions">
                    <button type="button" class="button" id="cfk-cancel-import">
                        <?php _e('Cancel', CFK_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="button button-primary" id="cfk-start-import">
                        <?php _e('Start Import', CFK_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Progress Section -->
        <div class="cfk-import-section cfk-hidden" id="cfk-progress-section">
            <div class="cfk-card">
                <h2><?php _e('Import Progress', CFK_TEXT_DOMAIN); ?></h2>
                
                <div class="cfk-progress-container">
                    <div class="cfk-progress-bar">
                        <div class="cfk-progress-fill" id="cfk-progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="cfk-progress-text" id="cfk-progress-text">
                        <?php _e('Preparing import...', CFK_TEXT_DOMAIN); ?>
                    </div>
                </div>
                
                <div class="cfk-progress-stats" id="cfk-progress-stats">
                    <div class="cfk-stat">
                        <span class="cfk-stat-label"><?php _e('Total Records:', CFK_TEXT_DOMAIN); ?></span>
                        <span class="cfk-stat-value" id="cfk-total-records">0</span>
                    </div>
                    <div class="cfk-stat">
                        <span class="cfk-stat-label"><?php _e('Processed:', CFK_TEXT_DOMAIN); ?></span>
                        <span class="cfk-stat-value" id="cfk-processed-records">0</span>
                    </div>
                    <div class="cfk-stat">
                        <span class="cfk-stat-label"><?php _e('Successful:', CFK_TEXT_DOMAIN); ?></span>
                        <span class="cfk-stat-value cfk-stat-success" id="cfk-success-count">0</span>
                    </div>
                    <div class="cfk-stat">
                        <span class="cfk-stat-label"><?php _e('Failed:', CFK_TEXT_DOMAIN); ?></span>
                        <span class="cfk-stat-value cfk-stat-error" id="cfk-error-count">0</span>
                    </div>
                </div>
                
                <div class="cfk-progress-log cfk-hidden" id="cfk-progress-log">
                    <h4><?php _e('Import Log:', CFK_TEXT_DOMAIN); ?></h4>
                    <div class="cfk-log-container" id="cfk-log-messages"></div>
                </div>
            </div>
        </div>
        
        <!-- Results Section -->
        <div class="cfk-import-section cfk-hidden" id="cfk-results-section">
            <div class="cfk-card">
                <h2><?php _e('Import Complete', CFK_TEXT_DOMAIN); ?></h2>
                
                <div class="cfk-results-summary" id="cfk-results-summary">
                    <!-- Results will be populated via JavaScript -->
                </div>
                
                <div class="cfk-results-actions">
                    <button type="button" class="button button-primary" id="cfk-view-children">
                        <?php _e('View All Children', CFK_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="button" id="cfk-new-import">
                        <?php _e('Import Another File', CFK_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="button button-link-delete" id="cfk-rollback-import">
                        <?php _e('Rollback Import', CFK_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cfk-csv-import-page {
    max-width: 1200px;
}

.cfk-import-container {
    margin-top: 20px;
}

.cfk-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.cfk-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.cfk-instructions {
    margin-bottom: 20px;
}

.cfk-csv-example {
    background: #f0f0f1;
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
    font-family: monospace;
    font-size: 13px;
    overflow-x: auto;
}

.cfk-format-notes {
    background: #fff3cd;
    border: 1px solid #ffecb5;
    border-radius: 4px;
    padding: 15px;
    margin: 15px 0;
}

.cfk-format-notes ul {
    margin: 10px 0 0 0;
}

.cfk-hidden {
    display: none;
}

.cfk-summary-box {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.cfk-error-box {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.cfk-error-box ul {
    margin: 0;
    padding-left: 20px;
}

.cfk-preview-table {
    margin: 20px 0;
}

.cfk-preview-actions {
    text-align: right;
    border-top: 1px solid #ddd;
    padding-top: 15px;
}

.cfk-progress-container {
    margin: 20px 0;
}

.cfk-progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f1;
    border-radius: 10px;
    overflow: hidden;
}

.cfk-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #00a32a, #4caf50);
    transition: width 0.3s ease;
}

.cfk-progress-text {
    text-align: center;
    margin-top: 10px;
    font-weight: 500;
}

.cfk-progress-stats {
    display: flex;
    justify-content: space-around;
    margin: 20px 0;
    padding: 15px;
    background: #f6f7f7;
    border-radius: 4px;
}

.cfk-stat {
    text-align: center;
}

.cfk-stat-label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.cfk-stat-value {
    display: block;
    font-size: 20px;
    font-weight: bold;
}

.cfk-stat-success {
    color: #00a32a;
}

.cfk-stat-error {
    color: #d63638;
}

.cfk-progress-log {
    margin-top: 20px;
}

.cfk-log-container {
    max-height: 200px;
    overflow-y: auto;
    background: #f6f7f7;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    font-family: monospace;
    font-size: 12px;
}

.cfk-results-summary {
    margin: 20px 0;
}

.cfk-results-actions {
    text-align: center;
    border-top: 1px solid #ddd;
    padding-top: 15px;
}

.cfk-results-actions .button {
    margin: 0 5px;
}

.cfk-file-input {
    margin-bottom: 10px;
}

@media screen and (max-width: 768px) {
    .cfk-progress-stats {
        flex-direction: column;
    }
    
    .cfk-stat {
        margin-bottom: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentImportId = null;
    let importInterval = null;
    
    // Handle CSV upload
    $('#cfk-csv-upload-form').on('submit', function(e) {
        e.preventDefault();
        
        const fileInput = $('#csv_file')[0];
        if (!fileInput.files.length) {
            alert('<?php _e('Please select a CSV file', CFK_TEXT_DOMAIN); ?>');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'cfk_upload_csv');
        formData.append('nonce', $('#cfk_csv_import_nonce').val());
        formData.append('csv_file', fileInput.files[0]);
        
        const uploadBtn = $('#cfk-upload-btn');
        uploadBtn.prop('disabled', true).text('<?php _e('Uploading...', CFK_TEXT_DOMAIN); ?>');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    currentImportId = response.data.import_id;
                    showPreviewSection(response.data);
                } else {
                    alert('Upload failed: ' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Upload failed. Please try again.', CFK_TEXT_DOMAIN); ?>');
            },
            complete: function() {
                uploadBtn.prop('disabled', false).text('<?php _e('Upload and Validate CSV', CFK_TEXT_DOMAIN); ?>');
            }
        });
    });
    
    // Show preview section
    function showPreviewSection(data) {
        // Hide upload section
        $('#cfk-upload-section').addClass('cfk-hidden');
        
        // Populate summary
        const summary = `
            <strong><?php _e('File validated successfully!', CFK_TEXT_DOMAIN); ?></strong><br>
            <?php _e('Total records found:', CFK_TEXT_DOMAIN); ?> ${data.total_records}<br>
            <?php _e('Validation errors:', CFK_TEXT_DOMAIN); ?> ${data.validation_errors ? data.validation_errors.length : 0}
        `;
        $('#cfk-preview-summary').html(summary);
        
        // Show validation errors if any
        if (data.validation_errors && data.validation_errors.length > 0) {
            const errorList = data.validation_errors.map(error => `<li>${error}</li>`).join('');
            $('#cfk-error-list').html(errorList);
            $('#cfk-validation-errors').removeClass('cfk-hidden');
        }
        
        // Populate preview table
        if (data.preview_data && data.preview_data.length > 0) {
            const headers = data.columns.map(col => `<th>${col}</th>`).join('');
            $('#cfk-preview-header').html(`<tr>${headers}</tr>`);
            
            const rows = data.preview_data.map(row => {
                const cells = data.columns.map(col => `<td>${row[col] || ''}</td>`).join('');
                return `<tr>${cells}</tr>`;
            }).join('');
            $('#cfk-preview-body').html(rows);
        }
        
        // Show preview section
        $('#cfk-preview-section').removeClass('cfk-hidden');
    }
    
    // Start import
    $('#cfk-start-import').on('click', function() {
        $('#cfk-preview-section').addClass('cfk-hidden');
        $('#cfk-progress-section').removeClass('cfk-hidden');
        startImportProcess();
    });
    
    // Start import processing
    function startImportProcess() {
        $('#cfk-progress-text').text('<?php _e('Starting import...', CFK_TEXT_DOMAIN); ?>');
        processNextBatch(0);
    }
    
    // Process import batch
    function processNextBatch(batchStart) {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'cfk_process_import',
                nonce: $('#cfk_csv_import_nonce').val(),
                import_id: currentImportId,
                batch_start: batchStart
            },
            success: function(response) {
                if (response.success) {
                    updateProgress(response.data);
                    
                    if (response.data.is_complete) {
                        showResults(response.data);
                    } else if (response.data.next_batch_start !== null) {
                        setTimeout(() => processNextBatch(response.data.next_batch_start), 500);
                    }
                } else {
                    alert('Import failed: ' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Import process failed. Please try again.', CFK_TEXT_DOMAIN); ?>');
            }
        });
    }
    
    // Update progress display
    function updateProgress(data) {
        const progress = data.progress;
        $('#cfk-progress-fill').css('width', progress.percentage + '%');
        $('#cfk-progress-text').text(`<?php _e('Processing records...', CFK_TEXT_DOMAIN); ?> ${progress.processed}/${progress.total}`);
        
        $('#cfk-total-records').text(progress.total);
        $('#cfk-processed-records').text(progress.processed);
        $('#cfk-success-count').text(data.session_summary.successful);
        $('#cfk-error-count').text(data.session_summary.failed);
        
        // Log batch results
        if (data.batch_results.errors && data.batch_results.errors.length > 0) {
            const logContainer = $('#cfk-log-messages');
            data.batch_results.errors.forEach(error => {
                logContainer.append(`<div style="color: #d63638;">${error}</div>`);
            });
            $('#cfk-progress-log').removeClass('cfk-hidden');
        }
    }
    
    // Show results
    function showResults(data) {
        $('#cfk-progress-section').addClass('cfk-hidden');
        
        const summary = `
            <div style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
                <h3 style="margin-top: 0;"><?php _e('Import Completed Successfully!', CFK_TEXT_DOMAIN); ?></h3>
                <p><strong><?php _e('Total Records:', CFK_TEXT_DOMAIN); ?></strong> ${data.progress.total}</p>
                <p><strong><?php _e('Successfully Imported:', CFK_TEXT_DOMAIN); ?></strong> ${data.session_summary.successful}</p>
                <p><strong><?php _e('Failed:', CFK_TEXT_DOMAIN); ?></strong> ${data.session_summary.failed}</p>
                <p><strong><?php _e('Created New Children:', CFK_TEXT_DOMAIN); ?></strong> ${data.session_summary.created_posts}</p>
            </div>
        `;
        
        $('#cfk-results-summary').html(summary);
        $('#cfk-results-section').removeClass('cfk-hidden');
    }
    
    // Action handlers
    $('#cfk-cancel-import').on('click', function() {
        location.reload();
    });
    
    $('#cfk-view-children').on('click', function() {
        window.location.href = 'edit.php?post_type=cfk_child';
    });
    
    $('#cfk-new-import').on('click', function() {
        location.reload();
    });
    
    $('#cfk-rollback-import').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to rollback this import? All newly created children will be permanently deleted.', CFK_TEXT_DOMAIN); ?>')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'cfk_rollback_import',
                nonce: $('#cfk_csv_import_nonce').val(),
                import_id: currentImportId
            },
            success: function(response) {
                if (response.success) {
                    alert(`<?php _e('Import rolled back successfully. Deleted', CFK_TEXT_DOMAIN); ?> ${response.data.deleted_count} <?php _e('children.', CFK_TEXT_DOMAIN); ?>`);
                    location.reload();
                } else {
                    alert('Rollback failed: ' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Rollback failed. Please try again.', CFK_TEXT_DOMAIN); ?>');
            }
        });
    });
});
</script>