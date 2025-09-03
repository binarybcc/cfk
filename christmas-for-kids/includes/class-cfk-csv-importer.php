<?php
declare(strict_types=1);

/**
 * CSV Import System for Christmas for Kids
 * 
 * Handles CSV file uploads and batch importing of child data.
 * Provides validation, duplicate detection, progress tracking,
 * and rollback capabilities for failed imports.
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 */
class CFK_CSV_Importer {
    
    /**
     * Batch size for processing records
     * 
     * @since 1.0.0
     * @var int
     */
    private const BATCH_SIZE = 50;
    
    /**
     * Required CSV columns (updated for family support)
     * 
     * @since 1.0.0
     * @var array<string>
     */
    private const REQUIRED_COLUMNS = ['name', 'age', 'gender', 'family_id'];
    
    /**
     * Optional CSV columns (updated for family support)
     * 
     * @since 1.0.0
     * @var array<string>
     */
    private const OPTIONAL_COLUMNS = [
        'family_name', 'shirt_size', 'pants_size', 'shoe_size', 'coat_size',
        'interests', 'family_situation', 'special_needs'
    ];
    
    /**
     * Import session data
     * 
     * @since 1.0.0
     * @var array<string, mixed>
     */
    private array $import_session = [];
    
    /**
     * Initialize the CSV importer
     * 
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        add_action('wp_ajax_cfk_upload_csv', [$this, 'handle_csv_upload']);
        add_action('wp_ajax_cfk_process_import', [$this, 'handle_import_processing']);
        add_action('wp_ajax_cfk_rollback_import', [$this, 'handle_import_rollback']);
    }
    
    /**
     * Handle CSV file upload via AJAX
     * 
     * @since 1.0.0
     * @return void
     */
    public function handle_csv_upload(): void {
        // Verify nonce and capabilities
        if (!$this->verify_import_request()) {
            wp_die('Security check failed');
        }
        
        try {
            $upload_result = $this->process_csv_upload();
            wp_send_json_success($upload_result);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle import processing via AJAX
     * 
     * @since 1.0.0
     * @return void
     */
    public function handle_import_processing(): void {
        // Verify nonce and capabilities
        if (!$this->verify_import_request()) {
            wp_die('Security check failed');
        }
        
        try {
            $import_id = sanitize_text_field($_POST['import_id'] ?? '');
            $batch_start = intval($_POST['batch_start'] ?? 0);
            
            if (!$import_id) {
                throw new Exception('Invalid import session');
            }
            
            $result = $this->process_import_batch($import_id, $batch_start);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle import rollback via AJAX
     * 
     * @since 1.0.0
     * @return void
     */
    public function handle_import_rollback(): void {
        // Verify nonce and capabilities
        if (!$this->verify_import_request()) {
            wp_die('Security check failed');
        }
        
        try {
            $import_id = sanitize_text_field($_POST['import_id'] ?? '');
            
            if (!$import_id) {
                throw new Exception('Invalid import session');
            }
            
            $result = $this->rollback_import($import_id);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Verify import request security and permissions
     * 
     * @since 1.0.0
     * @return bool True if request is valid
     */
    private function verify_import_request(): bool {
        return wp_verify_nonce($_POST['nonce'] ?? '', 'cfk_csv_import_nonce') &&
               current_user_can('manage_options');
    }
    
    /**
     * Process CSV file upload and validation
     * 
     * @since 1.0.0
     * @return array<string, mixed> Upload result
     * @throws Exception If upload or validation fails
     */
    private function process_csv_upload(): array {
        if (!isset($_FILES['csv_file'])) {
            throw new Exception('No file uploaded');
        }
        
        $file = $_FILES['csv_file'];
        
        // Validate file upload
        $this->validate_uploaded_file($file);
        
        // Move uploaded file to temporary location
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['path'] . '/cfk_import_' . uniqid() . '.csv';
        
        if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        // Parse and validate CSV content
        $csv_data = $this->parse_csv_file($temp_file);
        
        // Create import session
        $import_id = $this->create_import_session($temp_file, $csv_data);
        
        return [
            'import_id' => $import_id,
            'total_records' => count($csv_data['rows']),
            'preview_data' => array_slice($csv_data['rows'], 0, 5),
            'columns' => $csv_data['columns'],
            'validation_errors' => $csv_data['validation_errors'] ?? []
        ];
    }
    
    /**
     * Validate uploaded file
     * 
     * @since 1.0.0
     * @param array $file File upload data
     * @return void
     * @throws Exception If file validation fails
     */
    private function validate_uploaded_file(array $file): void {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $this->get_upload_error_message($file['error']));
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }
        
        // Check file type
        $allowed_types = ['text/csv', 'text/plain', 'application/csv'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types, true)) {
            // Also check file extension as fallback
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($extension !== 'csv') {
                throw new Exception('Invalid file type. Only CSV files are allowed.');
            }
        }
    }
    
    /**
     * Get upload error message
     * 
     * @since 1.0.0
     * @param int $error_code Upload error code
     * @return string Error message
     */
    private function get_upload_error_message(int $error_code): string {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds maximum size limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
        ];
        
        return $errors[$error_code] ?? 'Unknown upload error';
    }
    
    /**
     * Parse and validate CSV file
     * 
     * @since 1.0.0
     * @param string $file_path Path to CSV file
     * @return array<string, mixed> Parsed CSV data
     * @throws Exception If parsing fails
     */
    private function parse_csv_file(string $file_path): array {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception('Unable to read CSV file');
        }
        
        $columns = fgetcsv($handle);
        if (!$columns) {
            fclose($handle);
            throw new Exception('Empty or invalid CSV file');
        }
        
        // Validate required columns
        $missing_columns = array_diff(self::REQUIRED_COLUMNS, $columns);
        if (!empty($missing_columns)) {
            fclose($handle);
            throw new Exception('Missing required columns: ' . implode(', ', $missing_columns));
        }
        
        $rows = [];
        $validation_errors = [];
        $row_number = 1;
        
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            if (count($row) !== count($columns)) {
                $validation_errors[] = "Row {$row_number}: Column count mismatch";
                continue;
            }
            
            $row_data = array_combine($columns, $row);
            $row_errors = $this->validate_row_data($row_data, $row_number);
            
            if (!empty($row_errors)) {
                $validation_errors = array_merge($validation_errors, $row_errors);
            } else {
                $rows[] = $row_data;
            }
        }
        
        fclose($handle);
        
        if (empty($rows)) {
            throw new Exception('No valid data rows found in CSV file');
        }
        
        return [
            'columns' => $columns,
            'rows' => $rows,
            'validation_errors' => $validation_errors
        ];
    }
    
    /**
     * Validate individual row data
     * 
     * @since 1.0.0
     * @param array $row_data Row data
     * @param int $row_number Row number for error messages
     * @return array<string> Validation errors
     */
    private function validate_row_data(array $row_data, int $row_number): array {
        $errors = [];
        
        // Validate required fields
        foreach (self::REQUIRED_COLUMNS as $column) {
            if (empty(trim($row_data[$column] ?? ''))) {
                $errors[] = "Row {$row_number}: Missing required field '{$column}'";
            }
        }
        
        // Validate age
        if (isset($row_data['age'])) {
            $age = intval($row_data['age']);
            if ($age < 0 || $age > 18) {
                $errors[] = "Row {$row_number}: Invalid age '{$row_data['age']}' (must be 0-18)";
            }
        }
        
        // Validate gender
        if (isset($row_data['gender'])) {
            $gender = strtoupper(trim($row_data['gender']));
            if (!in_array($gender, ['M', 'F', 'MALE', 'FEMALE'], true)) {
                $errors[] = "Row {$row_number}: Invalid gender '{$row_data['gender']}' (use M/F or Male/Female)";
            }
        }
        
        // Validate name length
        if (isset($row_data['name'])) {
            $name = trim($row_data['name']);
            if (strlen($name) > 255) {
                $errors[] = "Row {$row_number}: Name too long (max 255 characters)";
            }
        }
        
        // Validate family ID format (NEW)
        if (isset($row_data['family_id'])) {
            $family_id = trim($row_data['family_id']);
            if (!empty($family_id) && !CFK_Child_Manager::validate_family_id($family_id)) {
                $errors[] = "Row {$row_number}: Invalid family ID format '{$family_id}' (use format: 123A)";
            }
        }
        
        return $errors;
    }
    
    /**
     * Create import session for tracking progress
     * 
     * @since 1.0.0
     * @param string $file_path Path to CSV file
     * @param array $csv_data Parsed CSV data
     * @return string Import session ID
     */
    private function create_import_session(string $file_path, array $csv_data): string {
        $import_id = 'cfk_import_' . uniqid();
        
        $session_data = [
            'id' => $import_id,
            'file_path' => $file_path,
            'total_records' => count($csv_data['rows']),
            'processed_records' => 0,
            'successful_imports' => 0,
            'failed_imports' => 0,
            'created_posts' => [],
            'start_time' => current_time('timestamp'),
            'data' => $csv_data,
            'status' => 'ready'
        ];
        
        set_transient($import_id, $session_data, HOUR_IN_SECONDS);
        
        return $import_id;
    }
    
    /**
     * Process a batch of import records
     * 
     * @since 1.0.0
     * @param string $import_id Import session ID
     * @param int $batch_start Starting index for batch
     * @return array<string, mixed> Processing result
     * @throws Exception If processing fails
     */
    private function process_import_batch(string $import_id, int $batch_start): array {
        $session = get_transient($import_id);
        if (!$session) {
            throw new Exception('Import session not found or expired');
        }
        
        $rows = $session['data']['rows'];
        $batch_end = min($batch_start + self::BATCH_SIZE, count($rows));
        $batch_rows = array_slice($rows, $batch_start, self::BATCH_SIZE);
        
        $batch_results = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        foreach ($batch_rows as $index => $row) {
            try {
                $result = $this->import_child_record($row);
                
                if ($result['action'] === 'created') {
                    $batch_results['imported']++;
                    $session['created_posts'][] = $result['post_id'];
                } elseif ($result['action'] === 'updated') {
                    $batch_results['updated']++;
                } else {
                    $batch_results['skipped']++;
                }
                
                $session['successful_imports']++;
            } catch (Exception $e) {
                $batch_results['errors'][] = "Row " . ($batch_start + $index + 2) . ": " . $e->getMessage();
                $session['failed_imports']++;
            }
        }
        
        // Update session data
        $session['processed_records'] = $batch_end;
        $session['status'] = $batch_end >= count($rows) ? 'completed' : 'processing';
        
        set_transient($import_id, $session, HOUR_IN_SECONDS);
        
        return [
            'batch_results' => $batch_results,
            'progress' => [
                'total' => count($rows),
                'processed' => $batch_end,
                'remaining' => count($rows) - $batch_end,
                'percentage' => round(($batch_end / count($rows)) * 100, 1)
            ],
            'session_summary' => [
                'successful' => $session['successful_imports'],
                'failed' => $session['failed_imports'],
                'created_posts' => count($session['created_posts'])
            ],
            'is_complete' => $session['status'] === 'completed',
            'next_batch_start' => $batch_end < count($rows) ? $batch_end : null
        ];
    }
    
    /**
     * Import individual child record
     * 
     * @since 1.0.0
     * @param array $row_data Row data
     * @return array<string, mixed> Import result
     * @throws Exception If import fails
     */
    private function import_child_record(array $row_data): array {
        // Normalize gender value
        $gender = strtoupper(trim($row_data['gender']));
        if (in_array($gender, ['MALE', 'M'], true)) {
            $row_data['gender'] = 'M';
        } elseif (in_array($gender, ['FEMALE', 'F'], true)) {
            $row_data['gender'] = 'F';
        }
        
        // Check for duplicate by name and age
        $existing_post = $this->find_duplicate_child(
            trim($row_data['name']),
            intval($row_data['age'])
        );
        
        if ($existing_post) {
            // Update existing post
            $post_id = $existing_post->ID;
            $this->update_child_meta($post_id, $row_data);
            
            return [
                'action' => 'updated',
                'post_id' => $post_id,
                'title' => $row_data['name']
            ];
        } else {
            // Create new post
            $post_id = $this->create_child_post($row_data);
            
            return [
                'action' => 'created',
                'post_id' => $post_id,
                'title' => $row_data['name']
            ];
        }
    }
    
    /**
     * Find duplicate child by name and age
     * 
     * @since 1.0.0
     * @param string $name Child name
     * @param int $age Child age
     * @return WP_Post|null Existing post or null
     */
    private function find_duplicate_child(string $name, int $age): ?WP_Post {
        $posts = get_posts([
            'post_type' => CFK_Child_Manager::get_post_type(),
            'post_status' => 'any',
            'posts_per_page' => 1,
            'title' => $name,
            'meta_query' => [
                [
                    'key' => 'cfk_child_age',
                    'value' => $age,
                    'compare' => '='
                ]
            ]
        ]);
        
        return !empty($posts) ? $posts[0] : null;
    }
    
    /**
     * Create new child post
     * 
     * @since 1.0.0
     * @param array $row_data Row data
     * @return int Post ID
     * @throws Exception If post creation fails
     */
    private function create_child_post(array $row_data): int {
        $post_data = [
            'post_type' => CFK_Child_Manager::get_post_type(),
            'post_title' => sanitize_text_field($row_data['name']),
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            throw new Exception('Failed to create child post: ' . $post_id->get_error_message());
        }
        
        // Add meta data
        $this->update_child_meta($post_id, $row_data);
        
        return $post_id;
    }
    
    /**
     * Update child meta data
     * 
     * @since 1.0.0
     * @param int $post_id Post ID
     * @param array $row_data Row data
     * @return void
     */
    private function update_child_meta(int $post_id, array $row_data): void {
        $meta_mapping = [
            'age' => 'cfk_child_age',
            'gender' => 'cfk_child_gender',
            // Family relationship fields (NEW)
            'family_id' => 'cfk_child_family_id',
            'family_name' => 'cfk_child_family_name',
            // Clothing sizes
            'shirt_size' => 'cfk_child_shirt_size',
            'pants_size' => 'cfk_child_pants_size',
            'shoe_size' => 'cfk_child_shoe_size',
            'coat_size' => 'cfk_child_coat_size',
            // Other fields
            'interests' => 'cfk_child_interests',
            'family_situation' => 'cfk_child_family_situation',
            'special_needs' => 'cfk_child_special_needs',
        ];
        
        foreach ($meta_mapping as $csv_field => $meta_key) {
            if (isset($row_data[$csv_field]) && $row_data[$csv_field] !== '') {
                $value = $csv_field === 'interests' 
                    ? str_replace('|', ', ', $row_data[$csv_field])
                    : $row_data[$csv_field];
                
                update_post_meta($post_id, $meta_key, sanitize_text_field($value));
            }
        }
        
        // Handle family ID parsing and set derived fields (NEW)
        if (!empty($row_data['family_id'])) {
            $family_components = CFK_Child_Manager::parse_family_id($row_data['family_id']);
            
            if (!empty($family_components['family_number'])) {
                update_post_meta($post_id, 'cfk_child_family_number', $family_components['family_number']);
            }
            
            if (!empty($family_components['child_letter'])) {
                update_post_meta($post_id, 'cfk_child_child_letter', $family_components['child_letter']);
            }
        }
    }
    
    /**
     * Rollback import by deleting created posts
     * 
     * @since 1.0.0
     * @param string $import_id Import session ID
     * @return array<string, mixed> Rollback result
     * @throws Exception If rollback fails
     */
    private function rollback_import(string $import_id): array {
        $session = get_transient($import_id);
        if (!$session) {
            throw new Exception('Import session not found or expired');
        }
        
        $deleted_count = 0;
        $errors = [];
        
        foreach ($session['created_posts'] as $post_id) {
            $result = wp_delete_post($post_id, true);
            if ($result) {
                $deleted_count++;
            } else {
                $errors[] = "Failed to delete post ID: {$post_id}";
            }
        }
        
        // Clean up temporary file
        if (file_exists($session['file_path'])) {
            unlink($session['file_path']);
        }
        
        // Remove import session
        delete_transient($import_id);
        
        return [
            'deleted_count' => $deleted_count,
            'total_created' => count($session['created_posts']),
            'errors' => $errors
        ];
    }
    
    /**
     * Render CSV import page
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_import_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        include CFK_PLUGIN_DIR . '/admin/partials/csv-import-page.php';
    }
    
    /**
     * Get import session data
     * 
     * @since 1.0.0
     * @param string $import_id Import session ID
     * @return array|null Session data or null if not found
     */
    public function get_import_session(string $import_id): ?array {
        return get_transient($import_id) ?: null;
    }
}