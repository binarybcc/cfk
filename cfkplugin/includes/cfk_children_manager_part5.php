<?php
/**
 * Children Manager Class - Part 5: CSV Integration & Utility Methods
 * CSV import/export, validation, AJAX handlers, and helper methods
 *
 * @package ChristmasForKids
 */

declare(strict_types=1);

// This file extends the CFK_Children_Manager class
// Add these final methods to complete the class:

// CSV Import Integration Methods
public static function create_child_from_csv_data(array $csv_data): ?int {
    try {
        // Validate data first
        $validation_errors = self::validate_child_data($csv_data);
        if (!empty($validation_errors)) {
            throw new Exception(implode(', ', $validation_errors));
        }
        
        // Create post
        $post_data = [
            'post_title' => sanitize_text_field($csv_data['name'] ?? ''),
            'post_type' => 'child',
            'post_status' => 'publish',
            'post_content' => ''
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            throw new Exception('Failed to create child post: ' . $post_id->get_error_message());
        }
        
        // Save meta fields
        $meta_mapping = [
            'child_id' => $csv_data['child_id'] ?? $csv_data['id'] ?? '',
            'child_age' => intval($csv_data['age'] ?? 0),
            'child_gender' => $csv_data['gender'] ?? '',
            'child_family_id' => $csv_data['family_id'] ?? '',
            'child_age_range' => self::determine_age_range(intval($csv_data['age'] ?? 0)),
            'child_clothing_info' => $csv_data['clothing_info'] ?? $csv_data['clothing'] ?? '',
            'child_gift_requests' => $csv_data['gift_requests'] ?? $csv_data['wishes'] ?? $csv_data['interests'] ?? ''
        ];
        
        foreach ($meta_mapping as $meta_key => $meta_value) {
            if ($meta_value !== '') {
                update_post_meta($post_id, '_' . $meta_key, $meta_value);
            }
        }
        
        return $post_id;
        
    } catch (Throwable $e) {
        error_log('CFK Create Child Error: ' . $e->getMessage());
        return null;
    }
}

public static function update_child_from_csv_data(string $child_id, array $csv_data): bool {
    try {
        $child_post = self::get_child_by_id($child_id);
        if (!$child_post) {
            return false;
        }
        
        // Add post_id to validation data to allow updates
        $csv_data['post_id'] = $child_post->ID;
        
        $validation_errors = self::validate_child_data($csv_data);
        if (!empty($validation_errors)) {
            throw new Exception(implode(', ', $validation_errors));
        }
        
        // Update post title if name provided
        if (!empty($csv_data['name'])) {
            wp_update_post([
                'ID' => $child_post->ID,
                'post_title' => sanitize_text_field($csv_data['name'])
            ]);
        }
        
        // Update meta fields
        $meta_mapping = [
            'child_age' => intval($csv_data['age'] ?? 0),
            'child_gender' => $csv_data['gender'] ?? '',
            'child_family_id' => $csv_data['family_id'] ?? '',
            'child_age_range' => self::determine_age_range(intval($csv_data['age'] ?? 0)),
            'child_clothing_info' => $csv_data['clothing_info'] ?? $csv_data['clothing'] ?? '',
            'child_gift_requests' => $csv_data['gift_requests'] ?? $csv_data['wishes'] ?? ''
        ];
        
        foreach ($meta_mapping as $meta_key => $meta_value) {
            if ($meta_value !== '') {
                update_post_meta($child_post->ID, '_' . $meta_key, $meta_value);
            }
        }
        
        return true;
        
    } catch (Throwable $e) {
        error_log('CFK Update Child Error: ' . $e->getMessage());
        return false;
    }
}

private static function determine_age_range(int $age): string {
    return match(true) {
        $age <= 2 => CFK_AgeRange::INFANT->value,
        $age <= 10 => CFK_AgeRange::ELEMENTARY->value,
        $age <= 13 => CFK_AgeRange::MIDDLE_SCHOOL->value,
        default => CFK_AgeRange::HIGH_SCHOOL->value
    };
}

public static function validate_child_data(array $data): array {
    $errors = [];
    
    // Required field validation
    $required_fields = ['child_id', 'child_age', 'child_gender'];
    
    foreach ($required_fields as $field) {
        $value = $data[$field] ?? '';
        if (empty($value)) {
            $field_name = str_replace('child_', '', $field);
            $errors[] = sprintf(__('%s is required', 'cfk-sponsorship'), ucwords(str_replace('_', ' ', $field_name)));
        }
    }
    
    // Age validation
    if (!empty($data['child_age']) || !empty($data['age'])) {
        $age = intval($data['child_age'] ?? $data['age'] ?? 0);
        if ($age < 0 || $age > 18) {
            $errors[] = __('Age must be between 0 and 18', 'cfk-sponsorship');
        }
    }
    
    // Gender validation
    $gender_value = $data['child_gender'] ?? $data['gender'] ?? '';
    if (!empty($gender_value)) {
        // Normalize gender values
        $gender_normalized = match(strtolower(trim($gender_value))) {
            'male', 'm', 'boy' => 'Male',
            'female', 'f', 'girl' => 'Female',
            default => $gender_value
        };
        
        try {
            CFK_Gender::from($gender_normalized);
            $data['child_gender'] = $gender_normalized; // Update normalized value
        } catch (ValueError) {
            $errors[] = __('Gender must be Male or Female', 'cfk-sponsorship');
        }
    }
    
    // Child ID validation
    $child_id = $data['child_id'] ?? $data['id'] ?? '';
    if (!empty($child_id)) {
        // Check for duplicate child ID
        $existing = self::get_child_by_id($child_id);
        if ($existing && (!isset($data['post_id']) || $existing->ID != $data['post_id'])) {
            $errors[] = sprintf(__('Child ID "%s" already exists', 'cfk-sponsorship'), $child_id);
        }
    }
    
    return $errors;
}

// AJAX Handlers
public function handle_ajax_export(): void {
    try {
        check_ajax_referer('cfk_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'cfk-sponsorship')]);
        }
        
        $filters = [
            'sponsored' => $_POST['sponsored'] ?? null,
            'age_range' => $_POST['age_range'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'family_id' => $_POST['family_id'] ?? ''
        ];
        
        $this->export_children_csv($filters);
        
    } catch (Throwable $e) {
        error_log('CFK Export Error: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

public function handle_ajax_bulk_update(): void {
    try {
        check_ajax_referer('cfk_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'cfk-sponsorship')]);
        }
        
        $child_ids = array_map('sanitize_text_field', $_POST['child_ids'] ?? []);
        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        
        $result = match($action) {
            'mark_sponsored' => self::bulk_update_sponsored_status($child_ids, true),
            'mark_available' => self::bulk_update_sponsored_status($child_ids, false),
            default => 0
        };
        
        wp_send_json_success([
            'message' => sprintf(__('%d children updated successfully', 'cfk-sponsorship'), $result),
            'updated_count' => $result
        ]);
        
    } catch (Throwable $e) {
        error_log('CFK Bulk Update Error: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

public function export_children_csv(array $filters = []): void {
    // Build query based on filters
    $query = new CFK_ChildQuery();
    
    if (!empty($filters['age_range'])) {
        try {
            $query = new CFK_ChildQuery(age_range: CFK_AgeRange::from($filters['age_range']));
        } catch (ValueError) {
            // Invalid age range, ignore filter
        }
    }
    
    if (!empty($filters['gender'])) {
        try {
            $gender = CFK_Gender::from($filters['gender']);
            $query = new CFK_ChildQuery(
                age_range: $query->age_range,
                gender: $gender
            );
        } catch (ValueError) {
            // Invalid gender, ignore filter
        }
    }
    
    if (isset($filters['sponsored']) && $filters['sponsored'] !== '') {
        $sponsored = filter_var($filters['sponsored'], FILTER_VALIDATE_BOOLEAN);
        $query = new CFK_ChildQuery(
            age_range: $query->age_range,
            gender: $query->gender,
            sponsored: $sponsored
        );
    }
    
    if (!empty($filters['family_id'])) {
        $query = new CFK_ChildQuery(
            age_range: $query->age_range,
            gender: $query->gender,
            sponsored: $query->sponsored,
            family_id: $filters['family_id']
        );
    }
    
    $children = $query->sponsored === null 
        ? get_posts($query->to_wp_query_args())
        : self::get_children_by_status($query->sponsored);
    
    if (empty($children)) {
        wp_send_json_error(['message' => __('No children found to export', 'cfk-sponsorship')]);
    }
    
    $filename = 'cfk-children-' . date('Y-m-d-H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV headers
    fputcsv($output, [
        'Child ID',
        'Name',
        'Age',
        'Gender',
        'Family ID',
        'Age Range',
        'Clothing Info',
        'Gift Requests',
        'Sponsored',
        'Created Date',
        'Last Modified'
    ]);
    
    // CSV data
    foreach ($children as $child) {
        $details = self::get_child_details($child);
        
        if ($details) {
            fputcsv($output, [
                $details->id,
                $details->name,
                $details->age,
                $details->gender->value,
                $details->family_id,
                $details->age_range->value,
                $details->clothing_info,
                $details->gift_requests,
                $details->sponsored ? 'Yes' : 'No',
                get_the_date('Y-m-d H:i:s', $child->ID),
                get_the_modified_date('Y-m-d H:i:s', $child->ID)
            ]);
        }
    }
    
    fclose($output);
    exit;
}

// Utility Methods
public static function get_age_ranges(): array {
    $ranges = [];
    foreach (CFK_AgeRange::cases() as $range) {
        $ranges[$range->value] = $range->getLabel();
    }
    return $ranges;
}

public static function get_gender_options(): array {
    $genders = [];
    foreach (CFK_Gender::cases() as $gender) {
        $genders[$gender->value] = $gender->getLabel();
    }
    return $genders;
}

public static function get_all_family_ids(): array {
    global $wpdb;
    
    $family_ids = $wpdb->get_col("
        SELECT DISTINCT meta_value 
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_child_family_id'
        AND pm.meta_value != ''
        AND p.post_type = 'child'
        AND p.post_status = 'publish'
        ORDER BY meta_value
    ");
    
    return $family_ids;
}

public static function generate_next_child_id(string $prefix = ''): string {
    global $wpdb;
    
    if (empty($prefix)) {
        $prefix = date('Y') . '-';
    }
    
    // Find the highest number with this prefix
    $highest = $wpdb->get_var($wpdb->prepare("
        SELECT meta_value 
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        