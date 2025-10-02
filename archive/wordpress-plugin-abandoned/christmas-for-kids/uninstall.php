<?php
declare(strict_types=1);

/**
 * Plugin uninstall procedures
 * 
 * This file is called when the plugin is uninstalled via WordPress admin.
 * It handles complete cleanup of all plugin data including database tables,
 * options, and scheduled events.
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check user capabilities
if (!current_user_can('delete_plugins')) {
    exit;
}

/**
 * Remove all plugin data during uninstall
 * 
 * @since 1.0.0
 * @return void
 */
function cfk_uninstall_cleanup(): void {
    global $wpdb;
    
    try {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('cfk_cleanup_expired_selections');
        
        // Drop database tables
        $tables = [
            $wpdb->prefix . 'cfk_sponsorships',
            $wpdb->prefix . 'cfk_email_logs'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $table));
        }
        
        // Remove all plugin options
        $options = [
            'cfk_selection_timeout',
            'cfk_admin_email',
            'cfk_sender_name', 
            'cfk_sender_email',
            'cfk_sponsorship_open',
            'cfk_version',
            'cfk_db_version'
        ];
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Clear any cached data
        wp_cache_flush();
        
    } catch (Exception $e) {
        error_log('[CFK-Uninstall] Error during uninstall: ' . $e->getMessage());
    }
}

// Execute cleanup
cfk_uninstall_cleanup();