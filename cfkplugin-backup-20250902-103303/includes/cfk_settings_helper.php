<?php
/**
 * Settings Helper Functions
 * Provides backward-compatible functions for existing settings management
 * while utilizing the new centralized CFK_Config_Manager
 *
 * @package ChristmasForKids
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all settings using the centralized config manager
 * 
 * @return array All plugin settings with cfk_ prefixes for backward compatibility
 */
function cfk_get_all_settings(): array {
    $settings = [];
    $config_data = CFK_Config_Manager::get_all();
    
    // Add cfk_ prefix for backward compatibility
    foreach ($config_data as $key => $value) {
        $settings['cfk_' . $key] = $value;
    }
    
    return $settings;
}

/**
 * Save settings using the centralized config manager
 * 
 * @param array $post_data Raw $_POST data from settings form
 * @return bool True on success, false on failure
 */
function cfk_save_settings(array $post_data): bool {
    try {
        // Map form fields to config keys (removing cfk_ prefix)
        $config_updates = [];
        
        // Process each known configuration field
        $schema = CFK_Config_Manager::get_schema();
        foreach (array_keys($schema) as $config_key) {
            $form_key = 'cfk_' . $config_key;
            
            if (array_key_exists($form_key, $post_data)) {
                $value = $post_data[$form_key];
                
                // Handle specific field types
                $config_updates[$config_key] = match($config_key) {
                    // Boolean fields
                    'sponsorships_open', 'allow_duplicate_emails', 'enable_logging', 'require_phone' 
                        => !empty($value),
                    
                    // Email fields
                    'email_from_email', 'admin_email' 
                        => sanitize_email($value ?: get_option('admin_email')),
                    
                    // Text fields
                    'email_from_name', 'email_footer_text', 'deadline_date' 
                        => sanitize_text_field($value),
                    
                    // Integer fields with validation
                    'selection_timeout' 
                        => max(1, min(24, intval($value ?: 2))),
                    'average_sponsorship_value' 
                        => max(10, min(1000, intval($value ?: 100))),
                    'max_children_per_sponsor' 
                        => max(0, min(50, intval($value ?: 0))),
                    
                    // Array fields (drop-off locations)
                    'drop_off_locations' => is_string($value) 
                        ? array_filter(array_map('trim', explode("\n", $value)))
                        : (is_array($value) ? $value : []),
                    
                    // Default handling
                    default => sanitize_text_field($value)
                };
            }
        }
        
        // Apply all updates
        $success = CFK_Config_Manager::set_multiple($config_updates);
        
        if ($success) {
            // Clear any caches
            CFK_Config_Manager::clear_cache();
            
            // Log successful update
            if (CFK_Config_Manager::get('enable_logging')) {
                error_log('CFK Settings updated successfully: ' . json_encode(array_keys($config_updates)));
            }
        }
        
        return $success;
        
    } catch (Exception $e) {
        error_log('CFK Settings save failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get a single setting value (backward compatible)
 * 
 * @param string $key Setting key (with or without cfk_ prefix)
 * @param mixed $default Default value
 * @return mixed Setting value
 */
function cfk_get_setting(string $key, mixed $default = null): mixed {
    // Remove cfk_ prefix if present
    $clean_key = str_replace('cfk_', '', $key);
    
    // Check if it's a known config key
    if (array_key_exists($clean_key, CFK_Config_Manager::get_schema())) {
        return CFK_Config_Manager::get($clean_key, $default);
    }
    
    // Fall back to direct WordPress option
    return get_option($key, $default);
}

/**
 * Update a single setting value (backward compatible)
 * 
 * @param string $key Setting key (with or without cfk_ prefix)
 * @param mixed $value New value
 * @return bool True on success
 */
function cfk_update_setting(string $key, mixed $value): bool {
    // Remove cfk_ prefix if present
    $clean_key = str_replace('cfk_', '', $key);
    
    // Check if it's a known config key
    if (array_key_exists($clean_key, CFK_Config_Manager::get_schema())) {
        return CFK_Config_Manager::set($clean_key, $value);
    }
    
    // Fall back to direct WordPress option
    return update_option($key, $value);
}

/**
 * Get configuration schema information for form building
 * 
 * @param string|null $key Specific key or null for all
 * @return array Schema information
 */
function cfk_get_config_schema(?string $key = null): array {
    return CFK_Config_Manager::get_schema($key);
}

/**
 * Validate settings data before saving
 * 
 * @param array $data Settings data to validate
 * @return array Array of validation errors (empty if valid)
 */
function cfk_validate_settings(array $data): array {
    $errors = [];
    $schema = CFK_Config_Manager::get_schema();
    
    foreach ($data as $key => $value) {
        $clean_key = str_replace('cfk_', '', $key);
        
        if (!isset($schema[$clean_key])) {
            continue; // Skip unknown keys
        }
        
        $field_schema = $schema[$clean_key];
        
        // Type-specific validation
        switch ($field_schema['type']) {
            case 'string':
                if (isset($field_schema['validation'])) {
                    if ($field_schema['validation'] === 'validate_email' && !empty($value) && !is_email($value)) {
                        $errors[] = sprintf(__('Invalid email format for %s', 'cfk-sponsorship'), $clean_key);
                    }
                    if ($field_schema['validation'] === 'validate_date' && !empty($value)) {
                        $date = DateTime::createFromFormat('Y-m-d', $value);
                        if (!$date || $date->format('Y-m-d') !== $value) {
                            $errors[] = sprintf(__('Invalid date format for %s (use YYYY-MM-DD)', 'cfk-sponsorship'), $clean_key);
                        }
                    }
                }
                break;
                
            case 'integer':
                if (!is_numeric($value)) {
                    $errors[] = sprintf(__('Invalid number for %s', 'cfk-sponsorship'), $clean_key);
                } else {
                    $int_value = intval($value);
                    if (isset($field_schema['min']) && $int_value < $field_schema['min']) {
                        $errors[] = sprintf(__('%s must be at least %d', 'cfk-sponsorship'), $clean_key, $field_schema['min']);
                    }
                    if (isset($field_schema['max']) && $int_value > $field_schema['max']) {
                        $errors[] = sprintf(__('%s must be at most %d', 'cfk-sponsorship'), $clean_key, $field_schema['max']);
                    }
                }
                break;
        }
    }
    
    return $errors;
}

/**
 * Export configuration for backup
 * 
 * @return array Configuration export data
 */
function cfk_export_config(): array {
    return CFK_Config_Manager::export();
}

/**
 * Import configuration from backup
 * 
 * @param array $data Configuration import data
 * @return bool True on success
 */
function cfk_import_config(array $data): bool {
    return CFK_Config_Manager::import($data);
}