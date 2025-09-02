<?php
/**
 * Uninstall script for Christmas for Kids plugin
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

try {
    error_log('CFK: Starting uninstall process');
    
    // Use direct WordPress get_option since plugin class may not be available during uninstall
    if (!get_option('cfk_delete_data_on_uninstall', false)) {
        error_log('CFK: Uninstall - keeping data as requested');
        return; // User chose to keep data
    }
    
    error_log('CFK: Starting uninstall cleanup');
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
    
    error_log('CFK: Uninstall cleanup completed successfully');
    
} catch (Exception $e) {
    error_log('CFK: Error during uninstall - ' . $e->getMessage());
    // Don't throw exception during uninstall to prevent critical error
}