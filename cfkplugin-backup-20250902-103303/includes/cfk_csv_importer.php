<?php
/**
 * CSV Importer for Christmas for Kids Plugin
 * Handles importing children data from CSV files with memory optimization
 *
 * @package ChristmasForKids
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Import status enum
enum CFK_ImportStatus: string {
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}

// Import result enum
enum CFK_ImportResult: string {
    case SUCCESS = 'success';
    case ERROR = 'error';
    case WARNING = 'warning';
    case SKIPPED = 'skipped';
}

// Readonly configuration for CSV structure
readonly class CFK_CSV_Config {
    public function __construct(
        public array $required_fields = ['name', 'age', 'gender'],
        public array $optional_fields = [
            'clothing_size_shirt', 'clothing_size_pants', 'clothing_size_shoes',
            'wish_list', 'notes', 'family_id', 'special_needs'
        ],
        public int $max_file_size = 10485760, // 10MB
        public int $batch_size = 100,
        public int $max_execution_time = 300, // 5 minutes
        public array $allowed_extensions = ['csv', 'txt'],
        public string $upload_dir = 'cfk-imports'
    ) {}
}

// Data transfer object for import results
readonly class CFK_ImportResult_DTO {
    public function __construct(
        public int $total_rows = 0,
        public int $processed_rows = 0,
        public int $successful_imports = 0,
        public int $failed_imports = 0,
        public int $skipped_rows = 0,
        public array $errors = [],
        public array $warnings = [],
        public float $execution_time = 0.0,
        public int $memory_peak = 0
    ) {}
}

class CFK_CSV_Importer {
    private array $import_sessions = [];
    private int $current_batch = 0;
    
    public function __construct(
        private readonly CFK_CSV_Config $config = new CFK_CSV_Config()
    ) {
        $this->register_hooks();
        $this->setup_upload_directory();
    }
    
    private function register_hooks(): void {
        add_action('wp_ajax_cfk_upload_csv', $this->handle_csv_upload(...));
        add_action('wp_ajax_cfk_process_import', $this->handle_import_processing(...));
        add_action('wp_ajax_cfk_cancel_import', $this->handle_import_cancellation(...));
        add_action('wp_ajax_cfk_get_import_status', $this->handle_status_check(...));
        
        // Cleanup old import files
        add_action('cfk_cleanup_import_files', $this->cleanup_old_files(...));
        if (!wp_next_scheduled('cfk_cleanup_import_files')) {
            wp_schedule_event(time(), 'daily', 'cfk_cleanup_import_files');
        }
    }
    
    private function setup_upload_directory(): void {
        $upload_dir = wp_upload_dir();
        $cfk_dir = $upload_dir['basedir'] . '/' . $this->config->upload_dir;
        
        if (!file_exists($cfk_dir)) {
            wp_mkdir_p($cfk_dir);
            
            // Add security files
            file_put_contents($cfk_dir . '/.htaccess', 'deny from all');
            file_put_contents($cfk_dir . '/index.php', '<?php // Silence is golden');
        }
    }
    
    public function display_import_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cfk-sponsorship'));
        }
        
        $this->enqueue_import_assets();
        $this->render_import_interface();
    }
    
    private function enqueue_import_assets(): void {
        wp_enqueue_script('cfk-csv-importer', CFK_PLUGIN_URL . 'assets/js/csv-importer.js', ['jquery'], CFK_PLUGIN_VERSION, true);
        wp_enqueue_style('cfk-csv-importer', CFK_PLUGIN_URL . 'assets/css/csv-importer.css', [], CFK_PLUGIN_VERSION);
        
        wp_localize_script('cfk-csv-importer', 'cfkImporter', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cfk_csv_import'),
            'maxFileSize' => $this->config->max_file_size,
            'allowedExtensions' => $this->config->allowed_extensions,
            'batchSize' => $this->config->batch_size,
            'strings' => [
                'uploading' => __('Uploading file...', 'cfk-sponsorship'),
                'processing' => __('Processing import...', 'cfk-sponsorship'),
                'completed' => __('Import completed!', 'cfk-sponsorship'),
                'failed' => __('Import failed', 'cfk-sponsorship'),
                'cancelled' => __('Import cancelled', 'cfk-sponsorship')
            ]
        ]);
    }
    
    private function render_import_interface(): void {
        echo '<div class="wrap cfk-csv-importer">';
        echo '<h1>' . __('Import Children Data', 'cfk-sponsorship') . '</h1>';
        
        $this->render_upload_section();
        $this->render_progress_section();
        $this->render_results_section();
        $this->render_template_section();
        
        echo '</div>';
    }
    
    private function render_upload_section(): void {
        ?>
        <div class="cfk-upload-section">
            <h2><?php _e('Upload CSV File', 'cfk-sponsorship'); ?></h2>
            
            <div class="cfk-upload-area" id="cfk-upload-area">
                <div class="cfk-upload-content">
                    <div class="cfk-upload-icon">📁</div>
                    <h3><?php _e('Drag & Drop CSV File Here', 'cfk-sponsorship'); ?></h3>
                    <p><?php _e('or click to browse files', 'cfk-sponsorship'); ?></p>
                    <input type="file" id="cfk-csv-file" accept=".csv,.txt" style="display: none;">
                </div>
                
                <div class="cfk-upload-requirements">
                    <h4><?php _e('File Requirements:', 'cfk-sponsorship'); ?></h4>
                    <ul>
                        <li><?php printf(__('Maximum file size: %s', 'cfk-sponsorship'), size_format($this->config->max_file_size)); ?></li>
                        <li><?php printf(__('Allowed formats: %s', 'cfk-sponsorship'), implode(', ', $this->config->allowed_extensions)); ?></li>
                        <li><?php printf(__('Required columns: %s', 'cfk-sponsorship'), implode(', ', $this->config->required_fields)); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_progress_section(): void {
        ?>
        <div class="cfk-progress-section" id="cfk-progress-section" style="display: none;">
            <h2><?php _e('Import Progress', 'cfk-sponsorship'); ?></h2>
            
            <div class="cfk-progress-bar">
                <div class="cfk-progress-fill" id="cfk-progress-fill"></div>
                <span class="cfk-progress-text" id="cfk-progress-text">0%</span>
            </div>
            
            <div class="cfk-progress-details">
                <div class="cfk-progress-stats">
                    <span id="cfk-processed-count">0</span> / <span id="cfk-total-count">0</span> 
                    <?php _e('rows processed', 'cfk-sponsorship'); ?>
                </div>
                
                <div class="cfk-progress-actions">
                    <button type="button" id="cfk-cancel-import" class="button">
                        <?php _e('Cancel Import', 'cfk-sponsorship'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_results_section(): void {
        ?>
        <div class="cfk-results-section" id="cfk-results-section" style="display: none;">
            <h2><?php _e('Import Results', 'cfk-sponsorship'); ?></h2>
            
            <div class="cfk-results-summary" id="cfk-results-summary"></div>
            <div class="cfk-results-details" id="cfk-results-details"></div>
        </div>
        <?php
    }
    
    private function render_template_section(): void {
        ?>
        <div class="cfk-template-section">
            <h2><?php _e('CSV Template', 'cfk-sponsorship'); ?></h2>
            <p><?php _e('Download the template file to ensure your CSV has the correct format.', 'cfk-sponsorship'); ?></p>
            
            <button type="button" id="cfk-download-template" class="button button-secondary">
                <?php _e('Download CSV Template', 'cfk-sponsorship'); ?>
            </button>
            
            <div class="cfk-template-preview">
                <h3><?php _e('Required Columns:', 'cfk-sponsorship'); ?></h3>
                <code><?php echo implode(', ', $this->config->required_fields); ?></code>
                
                <h3><?php _e('Optional Columns:', 'cfk-sponsorship'); ?></h3>
                <code><?php echo implode(', ', $this->config->optional_fields); ?></code>
            </div>
        </div>
        <?php
    }
    
    public function handle_csv_upload(): void {
        try {
            $this->verify_upload_security();
            $file_info = $this->process_uploaded_file();
            $validation_result = $this->validate_csv_structure($file_info['path']);
            
            if ($validation_result['valid']) {
                $session_id = $this->create_import_session($file_info, $validation_result);
                wp_send_json_success([
                    'session_id' => $session_id,
                    'total_rows' => $validation_result['total_rows'],
                    'preview' => $validation_result['preview']
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('CSV validation failed', 'cfk-sponsorship'),
                    'errors' => $validation_result['errors']
                ]);
            }
            
        } catch (Throwable $e) {
            error_log('CFK CSV Upload Error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    private function verify_upload_security(): void {
        check_ajax_referer('cfk_csv_import', 'nonce');
        
        if (!current_user_can('manage_options')) {
            throw new Exception(__('Insufficient permissions', 'cfk-sponsorship'));
        }
        
        if (!isset($_FILES['csv_file'])) {
            throw new Exception(__('No file uploaded', 'cfk-sponsorship'));
        }
    }
    
    private function process_uploaded_file(): array {
        $file = $_FILES['csv_file'];
        
        // Validate file
        match(true) {
            $file['error'] !== UPLOAD_ERR_OK => throw new Exception(__('File upload error', 'cfk-sponsorship')),
            $file['size'] > $this->config->max_file_size => throw new Exception(__('File too large', 'cfk-sponsorship')),
            !$this->is_valid_file_type($file['name']) => throw new Exception(__('Invalid file type', 'cfk-sponsorship')),
            default => null
        };
        
        // Move to secure location
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/' . $this->config->upload_dir;
        $filename = uniqid('cfk_import_') . '.csv';
        $target_path = $target_dir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            throw new Exception(__('Failed to save uploaded file', 'cfk-sponsorship'));
        }
        
        return [
            'name' => sanitize_file_name($file['name']),
            'path' => $target_path,
            'size' => $file['size']
        ];
    }
    
    private function is_valid_file_type(string $filename): bool {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $this->config->allowed_extensions, true);
    }
    
    private function validate_csv_structure(string $file_path): array {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return ['valid' => false, 'errors' => [__('Cannot read CSV file', 'cfk-sponsorship')]];
        }
        
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return ['valid' => false, 'errors' => [__('Empty CSV file', 'cfk-sponsorship')]];
        }
        
        // Normalize headers
        $headers = array_map('trim', array_map('strtolower', $headers));
        
        // Check required fields
        $missing_fields = array_diff($this->config->required_fields, $headers);
        if ($missing_fields !== []) {
            fclose($handle);
            return [
                'valid' => false,
                'errors' => [
                    sprintf(__('Missing required columns: %s', 'cfk-sponsorship'), implode(', ', $missing_fields))
                ]
            ];
        }
        
        // Count rows and get preview
        $total_rows = 0;
        $preview = [];
        
        while (($row = fgetcsv($handle)) !== false && count($preview) < 5) {
            $total_rows++;
            if (count($preview) < 5) {
                $preview[] = array_combine($headers, array_pad($row, count($headers), ''));
            }
        }
        
        // Count remaining rows
        while (fgetcsv($handle) !== false) {
            $total_rows++;
        }
        
        fclose($handle);
        
        return [
            'valid' => true,
            'headers' => $headers,
            'total_rows' => $total_rows,
            'preview' => $preview
        ];
    }
    
    private function create_import_session(array $file_info, array $validation_result): string {
        $session_id = uniqid('import_');
        
        $this->import_sessions[$session_id] = [
            'file_path' => $file_info['path'],
            'file_name' => $file_info['name'],
            'file_size' => $file_info['size'],
            'headers' => $validation_result['headers'],
            'total_rows' => $validation_result['total_rows'],
            'status' => CFK_ImportStatus::PENDING,
            'created_at' => time(),
            'user_id' => get_current_user_id()
        ];
        
        // Store in transient for persistence
        set_transient("cfk_import_session_{$session_id}", $this->import_sessions[$session_id], DAY_IN_SECONDS);
        
        return $session_id;
    }
    
    public function handle_import_processing(): void {
        try {
            check_ajax_referer('cfk_csv_import', 'nonce');
            
            $session_id = sanitize_text_field($_POST['session_id'] ?? '');
            $batch_start = intval($_POST['batch_start'] ?? 0);
            
            $session = $this->get_import_session($session_id);
            if (!$session) {
                wp_send_json_error(['message' => __('Invalid import session', 'cfk-sponsorship')]);
            }
            
            $result = $this->process_csv_batch($session, $batch_start);
            
            // Update session
            $this->update_import_session($session_id, [
                'status' => $result->processed_rows >= $session['total_rows'] 
                    ? CFK_ImportStatus::COMPLETED 
                    : CFK_ImportStatus::PROCESSING
            ]);
            
            wp_send_json_success([
                'batch_result' => $result,
                'is_complete' => $result->processed_rows >= $session['total_rows']
            ]);
            
        } catch (Throwable $e) {
            error_log('CFK Import Processing Error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    private function get_import_session(string $session_id): ?array {
        return get_transient("cfk_import_session_{$session_id}") ?: null;
    }
    
    private function update_import_session(string $session_id, array $updates): void {
        $session = $this->get_import_session($session_id);
        if ($session) {
            $session = array_merge($session, $updates);
            set_transient("cfk_import_session_{$session_id}", $session, DAY_IN_SECONDS);
        }
    }
    
    private function process_csv_batch(array $session, int $batch_start): CFK_ImportResult_DTO {
        $start_time = microtime(true);
        $start_memory = memory_get_peak_usage();
        
        $handle = fopen($session['file_path'], 'r');
        if (!$handle) {
            throw new Exception(__('Cannot read CSV file', 'cfk-sponsorship'));
        }
        
        // Skip to start position
        $headers = fgetcsv($handle); // Skip header
        for ($i = 0; $i < $batch_start; $i++) {
            fgetcsv($handle);
        }
        
        $processed = 0;
        $successful = 0;
        $failed = 0;
        $errors = [];
        $warnings = [];
        
        // Process batch
        while ($processed < $this->config->batch_size && ($row = fgetcsv($handle)) !== false) {
            try {
                $child_data = $this->parse_csv_row($headers, $row);
                $validation = $this->validate_child_data($child_data);
                
                if ($validation['valid']) {
                    $this->insert_child_data($child_data);
                    $successful++;
                } else {
                    $failed++;
                    $errors[] = "Row " . ($batch_start + $processed + 1) . ": " . implode(', ', $validation['errors']);
                }
                
                if ($validation['warnings'] !== []) {
                    $warnings[] = "Row " . ($batch_start + $processed + 1) . ": " . implode(', ', $validation['warnings']);
                }
                
            } catch (Throwable $e) {
                $failed++;
                $errors[] = "Row " . ($batch_start + $processed + 1) . ": " . $e->getMessage();
            }
            
            $processed++;
            
            // Memory management
            if (memory_get_peak_usage() > (512 * 1024 * 1024)) { // 512MB limit
                break;
            }
        }
        
        fclose($handle);
        
        return new CFK_ImportResult_DTO(
            total_rows: $session['total_rows'],
            processed_rows: $batch_start + $processed,
            successful_imports: $successful,
            failed_imports: $failed,
            errors: $errors,
            warnings: $warnings,
            execution_time: microtime(true) - $start_time,
            memory_peak: memory_get_peak_usage() - $start_memory
        );
    }
    
    private function parse_csv_row(array $headers, array $row): array {
        $data = array_combine($headers, array_pad($row, count($headers), ''));
        
        // Clean and normalize data
        return array_map(fn($value) => trim($value), $data);
    }
    
    private function validate_child_data(array $data): array {
        $errors = [];
        $warnings = [];
        
        // Required field validation
        foreach ($this->config->required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = sprintf(__('Missing required field: %s', 'cfk-sponsorship'), $field);
            }
        }
        
        // Data type validation
        if (!empty($data['age']) && (!is_numeric($data['age']) || $data['age'] < 0 || $data['age'] > 18)) {
            $errors[] = __('Age must be a number between 0 and 18', 'cfk-sponsorship');
        }
        
        if (!empty($data['gender']) && !in_array(strtolower($data['gender']), ['male', 'female', 'm', 'f'], true)) {
            $warnings[] = __('Gender should be Male/Female or M/F', 'cfk-sponsorship');
        }
        
        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    private function insert_child_data(array $data): void {
        global $wpdb;
        
        $table = $wpdb->prefix . 'cfk_children';
        
        // Prepare data for insertion
        $insert_data = [
            'name' => sanitize_text_field($data['name']),
            'age' => intval($data['age']),
            'gender' => sanitize_text_field($data['gender']),
            'clothing_size_shirt' => sanitize_text_field($data['clothing_size_shirt'] ?? ''),
            'clothing_size_pants' => sanitize_text_field($data['clothing_size_pants'] ?? ''),
            'clothing_size_shoes' => sanitize_text_field($data['clothing_size_shoes'] ?? ''),
            'wish_list' => sanitize_textarea_field($data['wish_list'] ?? ''),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'family_id' => sanitize_text_field($data['family_id'] ?? ''),
            'special_needs' => sanitize_textarea_field($data['special_needs'] ?? ''),
            'is_sponsored' => false,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($table, $insert_data);
        
        if ($result === false) {
            throw new Exception(__('Database insert failed', 'cfk-sponsorship'));
        }
    }
    
    public function handle_import_cancellation(): void {
        try {
            check_ajax_referer('cfk_csv_import', 'nonce');
            
            $session_id = sanitize_text_field($_POST['session_id'] ?? '');
            $session = $this->get_import_session($session_id);
            
            if ($session) {
                $this->update_import_session($session_id, ['status' => CFK_ImportStatus::CANCELLED]);
                
                // Clean up uploaded file
                if (file_exists($session['file_path'])) {
                    unlink($session['file_path']);
                }
            }
            
            wp_send_json_success(['message' => __('Import cancelled', 'cfk-sponsorship')]);
            
        } catch (Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public function handle_status_check(): void {
        try {
            check_ajax_referer('cfk_csv_import', 'nonce');
            
            $session_id = sanitize_text_field($_POST['session_id'] ?? '');
            $session = $this->get_import_session($session_id);
            
            wp_send_json_success(['session' => $session]);
            
        } catch (Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public function cleanup_old_files(): void {
        $upload_dir = wp_upload_dir();
        $cfk_dir = $upload_dir['basedir'] . '/' . $this->config->upload_dir;
        
        if (!is_dir($cfk_dir)) {
            return;
        }
        
        $files = glob($cfk_dir . '/cfk_import_*.csv');
        $cutoff_time = time() - DAY_IN_SECONDS;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }
    
    public function generate_csv_template(): void {
        $filename = 'cfk-children-template.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Write header
        $headers = array_merge($this->config->required_fields, $this->config->optional_fields);
        fputcsv($output, $headers);
        
        // Write sample data
        $sample_data = [
            ['John Doe', '8', 'Male', 'Medium', '10', '5', 'Lego, Books', 'Loves reading', 'FAM001', ''],
            ['Jane Smith', '6', 'Female', 'Small', '8', '4', 'Dolls, Art supplies', 'Artistic child', 'FAM002', 'Allergic to nuts']
        ];
        
        foreach ($sample_data as $row) {
            fputcsv($output, array_pad($row, count($headers), ''));
        }
        
        fclose($output);
        exit;
    }
}

?>