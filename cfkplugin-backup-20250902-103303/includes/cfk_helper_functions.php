<?php
/**
 * Helper Functions
 * Utility functions for Christmas for Kids plugin
 *
 * @package ChristmasForKids
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler for test email
 */
add_action('wp_ajax_cfk_send_test_email', 'cfk_ajax_send_test_email');
function cfk_ajax_send_test_email() {
    if (!wp_verify_nonce($_POST['nonce'], 'cfk_test_email') || !current_user_can('manage_options')) {
        wp_send_json_error(__('Security check failed', 'cfk-sponsorship'));
    }
    
    $test_email = sanitize_email($_POST['test_email']);
    
    if (!is_email($test_email)) {
        wp_send_json_error(__('Invalid email address', 'cfk-sponsorship'));
    }
    
    $subject = __('Test Email from Christmas for Kids Plugin', 'cfk-sponsorship');
    $message = __('This is a test email to verify your email configuration is working correctly.', 'cfk-sponsorship');
    
    $sent = wp_mail($test_email, $subject, $message);
    
    if ($sent) {
        wp_send_json_success(__('Test email sent successfully!', 'cfk-sponsorship'));
    } else {
        wp_send_json_error(__('Failed to send test email. Please check your email configuration.', 'cfk-sponsorship'));
    }
}

/**
 * AJAX handler for danger zone actions
 */
add_action('wp_ajax_cfk_danger_action', 'cfk_ajax_danger_action');
function cfk_ajax_danger_action() {
    if (!wp_verify_nonce($_POST['nonce'], 'cfk_danger_action') || !current_user_can('manage_options')) {
        wp_send_json_error(__('Security check failed', 'cfk-sponsorship'));
    }
    
    $action = sanitize_text_field($_POST['danger_action']);
    
    switch ($action) {
        case 'reset_settings':
            cfk_reset_all_settings();
            wp_send_json_success(__('All settings have been reset to defaults.', 'cfk-sponsorship'));
            break;
            
        case 'clear_sponsorships':
            cfk_clear_all_sponsorships();
            wp_send_json_success(__('All sponsorship data has been cleared.', 'cfk-sponsorship'));
            break;
            
        case 'reset_sponsored':
            cfk_reset_sponsored_status();
            wp_send_json_success(__('All children have been marked as unsponsored.', 'cfk-sponsorship'));
            break;
            
        default:
            wp_send_json_error(__('Invalid action', 'cfk-sponsorship'));
    }
}

/**
 * AJAX handler for cleanup abandoned selections
 */
add_action('wp_ajax_cfk_cleanup_abandoned', 'cfk_ajax_cleanup_abandoned');
function cfk_ajax_cleanup_abandoned() {
    if (!wp_verify_nonce($_POST['nonce'], 'cfk_admin_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error(__('Security check failed', 'cfk-sponsorship'));
    }
    
    if (class_exists('CFK_Sponsorship_Manager')) {
        $sponsorship_manager = new CFK_Sponsorship_Manager();
        $sponsorship_manager->force_cleanup();
        wp_send_json_success(__('Abandoned selections cleaned up successfully.', 'cfk-sponsorship'));
    } else {
        wp_send_json_error(__('Sponsorship manager not available', 'cfk-sponsorship'));
    }
}

/**
 * Reset all plugin settings to defaults
 */
function cfk_reset_all_settings() {
    $default_settings = array(
        'cfk_sponsorships_open' => false,
        'cfk_deadline_date' => '',
        'cfk_selection_timeout' => 2,
        'cfk_drop_off_locations' => array(
            'The Journal: 210 W. North 1st Street, Seneca',
            'Edwards Group: 125 Eagles Nest Drive, Seneca',
            'South State Bank: 201 US 123, Seneca'
        ),
        'cfk_average_sponsorship_value' => 100,
        'cfk_email_from_name' => 'Christmas for Kids',
        'cfk_email_from_email' => 'noreply@' . parse_url(home_url(), PHP_URL_HOST),
        'cfk_admin_email' => get_option('admin_email'),
        'cfk_email_footer_text' => '',
        'cfk_additional_instructions' => '',
        'cfk_children_per_page' => 12,
        'cfk_default_columns' => 3,
        'cfk_show_filters' => true,
        'cfk_show_clothing_info' => true,
        'cfk_require_phone' => false,
        'cfk_require_address' => false,
        'cfk_closed_message' => '',
        'cfk_thank_you_message' => '',
        'cfk_enable_logging' => false,
        'cfk_delete_data_on_uninstall' => false,
        'cfk_allow_duplicate_emails' => true,
        'cfk_max_children_per_sponsor' => 0,
        'cfk_custom_css' => ''
    );
    
    foreach ($default_settings as $option => $value) {
        ChristmasForKidsPlugin::update_option($option, $value);
    }
}

/**
 * Clear all sponsorship data
 */
function cfk_clear_all_sponsorships() {
    global $wpdb;
    
    $sponsorship_table = $wpdb->prefix . 'cfk_sponsorships';
    $email_log_table = $wpdb->prefix . 'cfk_email_log';
    
    // Clear sponsorship data
    $wpdb->query("TRUNCATE TABLE $sponsorship_table");
    $wpdb->query("TRUNCATE TABLE $email_log_table");
    
    // Reset sponsored status on all children
    cfk_reset_sponsored_status();
}

/**
 * Reset sponsored status for all children
 */
function cfk_reset_sponsored_status() {
    global $wpdb;
    
    // Remove all sponsored meta
    $wpdb->delete(
        $wpdb->postmeta,
        array(
            'meta_key' => '_child_sponsored',
            'meta_value' => '1'
        )
    );
}

/**
 * Get child display name
 */
function cfk_get_child_display_name($child_post) {
    $child_id = get_post_meta($child_post->ID, '_child_id', true);
    $age = get_post_meta($child_post->ID, '_child_age', true);
    $gender = get_post_meta($child_post->ID, '_child_gender', true);
    
    if ($child_id && $age && $gender) {
        return sprintf('%s: %s %s', $child_id, $gender, $age);
    }
    
    return $child_post->post_title;
}

/**
 * Format child information for display
 */
function cfk_format_child_info($child_data) {
    $info = array();
    
    if (!empty($child_data['age'])) {
        $info[] = sprintf(__('%s years old', 'cfk-sponsorship'), $child_data['age']);
    }
    
    if (!empty($child_data['gender'])) {
        $info[] = $child_data['gender'];
    }
    
    if (!empty($child_data['age_range'])) {
        $info[] = $child_data['age_range'];
    }
    
    return implode(' • ', $info);
}

/**
 * Get formatted drop-off locations
 */
function cfk_get_formatted_locations($format = 'html') {
    $locations = ChristmasForKidsPlugin::get_option('cfk_drop_off_locations', array());
    
    if (empty($locations)) {
        return '';
    }
    
    if ($format === 'html') {
        return '<ul><li>' . implode('</li><li>', array_map('esc_html', $locations)) . '</li></ul>';
    } elseif ($format === 'text') {
        return implode("\n", $locations);
    } elseif ($format === 'array') {
        return $locations;
    }
    
    return implode(', ', $locations);
}

/**
 * Check if user can sponsor more children
 */
function cfk_can_sponsor_more_children($current_count) {
    $max_children = ChristmasForKidsPlugin::get_option('cfk_max_children_per_sponsor', 0);
    
    if ($max_children === 0) {
        return true; // No limit
    }
    
    return $current_count < $max_children;
}

/**
 * Check if email address is already used (if duplicates not allowed)
 */
function cfk_is_email_already_used($email) {
    if (ChristmasForKidsPlugin::get_option('cfk_allow_duplicate_emails', true)) {
        return false; // Duplicates allowed
    }
    
    global $wpdb;
    $sponsorship_table = $wpdb->prefix . 'cfk_sponsorships';
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $sponsorship_table WHERE sponsor_email = %s AND status = 'confirmed'",
        $email
    ));
    
    return $count > 0;
}

/**
 * Log plugin activity (if logging enabled)
 */
function cfk_log_activity($message, $level = 'info', $data = array()) {
    if (!ChristmasForKidsPlugin::get_option('cfk_enable_logging', false)) {
        return;
    }
    
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'level' => $level,
        'message' => $message,
        'data' => $data,
        'user_id' => get_current_user_id(),
        'ip_address' => cfk_get_user_ip()
    );
    
    // Log to WordPress debug.log if WP_DEBUG_LOG is enabled
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('CFK Plugin [' . strtoupper($level) . ']: ' . $message . ' | Data: ' . json_encode($data));
    }
    
    // Could also store in custom log table if more sophisticated logging needed
}

/**
 * Get user IP address
 */
function cfk_get_user_ip() {
    $ip_keys = array(
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    );
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, 
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Format currency amount
 */
function cfk_format_currency($amount, $currency = 'USD') {
    $formatted = number_format($amount, 2);
    
    switch ($currency) {
        case 'USD':
            return '$' . $formatted;
        case 'EUR':
            return '€' . $formatted;
        case 'GBP':
            return '£' . $formatted;
        default:
            return $formatted . ' ' . $currency;
    }
}

/**
 * Get age range from age
 */
function cfk_get_age_range_from_age($age) {
    $age = intval($age);
    
    if ($age <= 2) {
        return 'Infant';
    } elseif ($age <= 10) {
        return 'Elementary';
    } elseif ($age <= 14) {
        return 'Middle School';
    } else {
        return 'High School';
    }
}

/**
 * Generate unique session ID
 */
function cfk_generate_session_id() {
    return 'cfk_' . uniqid() . '_' . time() . '_' . wp_rand(1000, 9999);
}

/**
 * Sanitize and validate child data
 */
function cfk_sanitize_child_data($data) {
    $sanitized = array();
    
    $sanitized['child_id'] = sanitize_text_field($data['child_id'] ?? '');
    $sanitized['name'] = sanitize_text_field($data['name'] ?? '');
    $sanitized['age'] = intval($data['age'] ?? 0);
    $sanitized['gender'] = in_array($data['gender'] ?? '', array('Male', 'Female')) ? $data['gender'] : '';
    $sanitized['family_id'] = sanitize_text_field($data['family_id'] ?? '');
    $sanitized['age_range'] = sanitize_text_field($data['age_range'] ?? '');
    $sanitized['clothing_info'] = sanitize_textarea_field($data['clothing_info'] ?? '');
    $sanitized['gift_requests'] = sanitize_textarea_field($data['gift_requests'] ?? '');
    
    return $sanitized;
}

/**
 * Sanitize and validate sponsor data
 */
function cfk_sanitize_sponsor_data($data) {
    $sanitized = array();
    
    $sanitized['name'] = sanitize_text_field($data['sponsor_name'] ?? '');
    $sanitized['email'] = sanitize_email($data['sponsor_email'] ?? '');
    $sanitized['phone'] = sanitize_text_field($data['sponsor_phone'] ?? '');
    $sanitized['address'] = sanitize_textarea_field($data['sponsor_address'] ?? '');
    $sanitized['notes'] = sanitize_textarea_field($data['sponsor_notes'] ?? '');
    
    return $sanitized;
}

/**
 * Validate sponsor data
 */
function cfk_validate_sponsor_data($data) {
    $errors = array();
    
    if (empty($data['name'])) {
        $errors[] = __('Name is required', 'cfk-sponsorship');
    }
    
    if (empty($data['email'])) {
        $errors[] = __('Email address is required', 'cfk-sponsorship');
    } elseif (!is_email($data['email'])) {
        $errors[] = __('Please enter a valid email address', 'cfk-sponsorship');
    } elseif (cfk_is_email_already_used($data['email'])) {
        $errors[] = __('This email address has already been used for a sponsorship', 'cfk-sponsorship');
    }
    
    if (ChristmasForKidsPlugin::get_option('cfk_require_phone', false) && empty($data['phone'])) {
        $errors[] = __('Phone number is required', 'cfk-sponsorship');
    }
    
    if (ChristmasForKidsPlugin::get_option('cfk_require_address', false) && empty($data['address'])) {
        $errors[] = __('Address is required', 'cfk-sponsorship');
    }
    
    return $errors;
}

/**
 * Get plugin status information
 */
function cfk_get_plugin_status() {
    global $wpdb;
    
    $status = array();
    
    // Check database tables
    $required_tables = array(
        $wpdb->prefix . 'cfk_sponsorships',
        $wpdb->prefix . 'cfk_email_log'
    );
    
    $missing_tables = array();
    foreach ($required_tables as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            $missing_tables[] = $table;
        }
    }
    
    $status['database_ok'] = empty($missing_tables);
    $status['missing_tables'] = $missing_tables;
    
    // Check email configuration
    $from_email = ChristmasForKidsPlugin::get_option('cfk_email_from_email');
    $status['email_configured'] = !empty($from_email) && is_email($from_email);
    
    // Check if sponsorships are open
    $status['sponsorships_open'] = ChristmasForKidsPlugin::get_option('cfk_sponsorships_open', false);
    
    // Check deadline configuration
    $deadline = ChristmasForKidsPlugin::get_option('cfk_deadline_date');
    $status['deadline_set'] = !empty($deadline) && $deadline !== '[DEADLINE DATE]';
    
    // Get basic statistics
    $stats = CFK_Children_Manager::get_sponsorship_stats();
    $status['total_children'] = $stats['total_children'];
    $status['sponsored_children'] = $stats['sponsored_children'];
    $status['available_children'] = $stats['available_children'];
    
    return $status;
}

/**
 * Generate CSV export filename
 */
function cfk_generate_export_filename($type, $date_format = 'Y-m-d-H-i') {
    $date = date($date_format);
    $site_name = sanitize_title(get_bloginfo('name'));
    
    return sprintf('cfk-%s-%s-%s.csv', $site_name, $type, $date);
}

/**
 * Convert array to CSV string
 */
function cfk_array_to_csv($data, $headers = null) {
    if (empty($data)) {
        return '';
    }
    
    $output = fopen('php://temp', 'r+');
    
    // Add headers if provided, or use keys from first row
    if ($headers) {
        fputcsv($output, $headers);
    } elseif (is_array($data[0])) {
        fputcsv($output, array_keys($data[0]));
    }
    
    // Add data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    rewind($output);
    $csv_string = stream_get_contents($output);
    fclose($output);
    
    return $csv_string;
}

/**
 * Get system requirements status
 */
function cfk_check_system_requirements() {
    $requirements = array();
    
    // PHP version
    $requirements['php_version'] = array(
        'name' => 'PHP Version',
        'required' => '7.4',
        'current' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '7.4', '>=')
    );
    
    // WordPress version
    global $wp_version;
    $requirements['wp_version'] = array(
        'name' => 'WordPress Version',
        'required' => '5.0',
        'current' => $wp_version,
        'status' => version_compare($wp_version, '5.0', '>=')
    );
    
    // MySQL version
    global $wpdb;
    $mysql_version = $wpdb->get_var('SELECT VERSION()');
    $requirements['mysql_version'] = array(
        'name' => 'MySQL Version',
        'required' => '5.6',
        'current' => $mysql_version,
        'status' => version_compare($mysql_version, '5.6', '>=')
    );
    
    // Required PHP extensions
    $required_extensions = array('json', 'mbstring', 'curl');
    foreach ($required_extensions as $extension) {
        $requirements['ext_' . $extension] = array(
            'name' => 'PHP Extension: ' . $extension,
            'required' => 'Yes',
            'current' => extension_loaded($extension) ? 'Yes' : 'No',
            'status' => extension_loaded($extension)
        );
    }
    
    // Memory limit
    $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
    $required_memory = 64 * 1024 * 1024; // 64MB
    $requirements['memory_limit'] = array(
        'name' => 'PHP Memory Limit',
        'required' => '64M',
        'current' => ini_get('memory_limit'),
        'status' => $memory_limit >= $required_memory
    );
    
    return $requirements;
}

/**
 * Get readable file size
 */
function cfk_format_bytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

/**
 * Get time ago string
 */
function cfk_time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return __('just now', 'cfk-sponsorship');
    } elseif ($time < 3600) {
        $mins = floor($time / 60);
        return sprintf(_n('%d minute ago', '%d minutes ago', $mins, 'cfk-sponsorship'), $mins);
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return sprintf(_n('%d hour ago', '%d hours ago', $hours, 'cfk-sponsorship'), $hours);
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return sprintf(_n('%d day ago', '%d days ago', $days, 'cfk-sponsorship'), $days);
    } else {
        return date('M j, Y', strtotime($datetime));
    }
}

/**
 * Generate random child names for testing
 */
function cfk_generate_test_children($count = 10) {
    $first_names_male = array('James', 'John', 'Robert', 'Michael', 'David', 'William', 'Richard', 'Joseph', 'Thomas', 'Christopher');
    $first_names_female = array('Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara', 'Susan', 'Jessica', 'Sarah', 'Karen');
    $last_names = array('Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez');
    $gifts = array(
        'Infant' => array('Baby toys', 'Soft blanket', 'Board books', 'Teething toys'),
        'Elementary' => array('Art supplies', 'Building blocks', 'Books', 'Board games', 'Bicycles', 'Dolls', 'Action figures'),
        'Middle School' => array('Science kits', 'Sports equipment', 'Books', 'Art supplies', 'Board games', 'Video games'),
        'High School' => array('Gift cards', 'Electronics', 'Books', 'Sports equipment', 'Art supplies', 'Clothing')
    );
    
    $children = array();
    
    for ($i = 1; $i <= $count; $i++) {
        $gender = wp_rand(0, 1) ? 'Male' : 'Female';
        $age = wp_rand(1, 17);
        $age_range = cfk_get_age_range_from_age($age);
        
        $first_name = $gender === 'Male' 
            ? $first_names_male[wp_rand(0, count($first_names_male) - 1)]
            : $first_names_female[wp_rand(0, count($first_names_female) - 1)];
        
        $last_name = $last_names[wp_rand(0, count($last_names) - 1)];
        $family_id = str_pad($i, 3, '0', STR_PAD_LEFT);
        $child_id = $family_id . 'A';
        
        $gift_options = $gifts[$age_range];
        $selected_gifts = array_rand($gift_options, min(3, count($gift_options)));
        if (!is_array($selected_gifts)) {
            $selected_gifts = array($selected_gifts);
        }
        
        $gift_list = array();
        foreach ($selected_gifts as $index) {
            $gift_list[] = $gift_options[$index];
        }
        
        $children[] = array(
            'child_id' => $child_id,
            'name' => $first_name . ' ' . $last_name,
            'age' => $age,
            'gender' => $gender,
            'family_id' => $family_id,
            'age_range' => $age_range,
            'clothing_info' => cfk_generate_clothing_info($age, $gender),
            'gift_requests' => implode(', ', $gift_list)
        );
    }
    
    return $children;
}

/**
 * Generate clothing info for test data
 */
function cfk_generate_clothing_info($age, $gender) {
    $sizes = array(
        'Infant' => array('pants' => 'Infant 12M', 'shirt' => 'Infant 12M', 'shoes' => 'Infant 4'),
        'Elementary' => array('pants' => $gender === 'Male' ? 'Boys ' . $age : 'Girls ' . $age, 'shirt' => $gender === 'Male' ? 'Boys ' . $age : 'Girls ' . $age, 'shoes' => 'Youth ' . wp_rand(10, 3)),
        'Middle School' => array('pants' => $gender === 'Male' ? 'Boys ' . $age : 'Girls ' . $age, 'shirt' => $gender === 'Male' ? 'Boys ' . $age : 'Girls ' . $age, 'shoes' => 'Youth ' . wp_rand(4, 8)),
        'High School' => array('pants' => $gender === 'Male' ? 'Mens Small' : 'Ladies ' . wp_rand(8, 14), 'shirt' => $gender === 'Male' ? 'Mens Small' : 'Ladies Medium', 'shoes' => $gender === 'Male' ? 'Mens ' . wp_rand(8, 12) : 'Ladies ' . wp_rand(6, 10))
    );
    
    $age_range = cfk_get_age_range_from_age($age);
    $size_info = $sizes[$age_range];
    
    return sprintf('Pants: %s. Shirt: %s. Shoes: %s.', 
        $size_info['pants'], 
        $size_info['shirt'], 
        $size_info['shoes']
    );
}

/**
 * Cleanup function for plugin deactivation
 */
function cfk_cleanup_on_deactivation() {
    // Clear scheduled events
    wp_clear_scheduled_hook('cfk_cleanup_abandoned_selections');
    
    // Clear any transients
    delete_transient('cfk_stats_cache');
    delete_transient('cfk_activity_cache');
    
    cfk_log_activity('Plugin deactivated', 'info');
}

/**
 * Cleanup function for plugin uninstallation
 */
function cfk_cleanup_on_uninstall() {
    if (!ChristmasForKidsPlugin::get_option('cfk_delete_data_on_uninstall', false)) {
        return; // User chose to keep data
    }
    
    global $wpdb;
    
    // Delete all children posts
    $children = get_posts(array(
        'post_type' => 'child',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    foreach ($children as $child_id) {
        wp_delete_post($child_id, true);
    }
    
    // Drop plugin tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cfk_sponsorships");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cfk_email_log");
    
    // Delete all plugin options
    $option_names = array(
        'cfk_sponsorships_open', 'cfk_deadline_date', 'cfk_selection_timeout',
        'cfk_drop_off_locations', 'cfk_average_sponsorship_value', 'cfk_email_from_name',
        'cfk_email_from_email', 'cfk_admin_email', 'cfk_email_footer_text',
        'cfk_additional_instructions', 'cfk_children_per_page', 'cfk_default_columns',
        'cfk_show_filters', 'cfk_show_clothing_info', 'cfk_require_phone',
        'cfk_require_address', 'cfk_closed_message', 'cfk_thank_you_message',
        'cfk_enable_logging', 'cfk_delete_data_on_uninstall', 'cfk_allow_duplicate_emails',
        'cfk_max_children_per_sponsor', 'cfk_custom_css', 'cfk_db_version'
    );
    
    foreach ($option_names as $option) {
        delete_option($option);
    }
    
    // Clear any remaining transients
    delete_transient('cfk_stats_cache');
    delete_transient('cfk_activity_cache');
    
    cfk_log_activity('Plugin uninstalled - all data deleted', 'info');
}

/**
 * Add custom CSS to frontend if configured
 */
add_action('wp_head', 'cfk_add_custom_css');
function cfk_add_custom_css() {
    $custom_css = ChristmasForKidsPlugin::get_option('cfk_custom_css', '');
    
    if (!empty($custom_css)) {
        echo '<style type="text/css">' . wp_strip_all_tags($custom_css) . '</style>';
    }
}

/**
 * Add plugin meta links
 */
add_filter('plugin_row_meta', 'cfk_add_plugin_meta_links', 10, 2);
function cfk_add_plugin_meta_links($links, $file) {
    if ($file === CFK_PLUGIN_BASENAME) {
        $links[] = '<a href="' . admin_url('admin.php?page=cfk-settings') . '">' . __('Settings', 'cfk-sponsorship') . '</a>';
        $links[] = '<a href="' . admin_url('admin.php?page=cfk-dashboard') . '">' . __('Dashboard', 'cfk-sponsorship') . '</a>';
    }
    
    return $links;
}

/**
 * Display admin notices for important plugin status
 */
add_action('admin_notices', 'cfk_admin_notices');
function cfk_admin_notices() {
    $screen = get_current_screen();
    
    // Only show on plugin pages
    if (!$screen || strpos($screen->id, 'cfk-') === false) {
        return;
    }
    
    $status = cfk_get_plugin_status();
    
    // Database issues
    if (!$status['database_ok']) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>' . __('Christmas for Kids:', 'cfk-sponsorship') . '</strong> ';
        echo __('Database tables are missing. Try deactivating and reactivating the plugin.', 'cfk-sponsorship');
        echo '</p></div>';
    }
    
    // Email not configured
    if (!$status['email_configured']) {
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>' . __('Christmas for Kids:', 'cfk-sponsorship') . '</strong> ';
        echo sprintf(__('Email settings need configuration. <a href="%s">Configure now</a>', 'cfk-sponsorship'), 
                    admin_url('admin.php?page=cfk-settings#email'));
        echo '</p></div>';
    }
}

/**
 * Register activation hook
 */
register_activation_hook(CFK_PLUGIN_BASENAME, 'cfk_cleanup_on_deactivation');

/**
 * Register uninstall hook
 */
register_uninstall_hook(CFK_PLUGIN_BASENAME, 'cfk_cleanup_on_uninstall');

?>