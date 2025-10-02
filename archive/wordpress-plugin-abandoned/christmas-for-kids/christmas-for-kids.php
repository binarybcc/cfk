<?php
declare(strict_types=1);

/**
 * Plugin Name: Christmas for Kids - Sponsorship System
 * Plugin URI: https://christmasforkids.org
 * Description: A comprehensive sponsorship management system for Christmas for Kids charity. Handles child profiles, CSV imports, sponsorship tracking, and automated email communications.
 * Version: 1.1.0
 * Author: Christmas for Kids
 * Author URI: https://christmasforkids.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: christmas-for-kids
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.8.2
 * Requires PHP: 8.2
 * Network: false
 * 
 * @package ChristmasForKids
 * @version 1.1.0
 * @since 1.0.0
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Don't run during WordPress maintenance or updates
if (defined('WP_INSTALLING') && WP_INSTALLING) {
    return;
}

/**
 * Plugin version constant
 * 
 * @since 1.0.0
 */
define('CFK_VERSION', '1.1.0');

/**
 * Plugin file path constant
 * 
 * @since 1.0.0
 */
define('CFK_PLUGIN_FILE', __FILE__);

/**
 * Plugin directory path constant
 * 
 * @since 1.0.0
 */
define('CFK_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin directory path constant (alternative name for compatibility)
 * 
 * @since 1.0.0
 */
define('CFK_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL constant
 * 
 * @since 1.0.0
 */
define('CFK_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename constant
 * 
 * @since 1.0.0
 */
define('CFK_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Minimum WordPress version required
 * 
 * @since 1.0.0
 */
define('CFK_MIN_WP_VERSION', '6.0');

/**
 * Minimum PHP version required
 * 
 * @since 1.0.0
 */
define('CFK_MIN_PHP_VERSION', '8.2');

/**
 * Plugin text domain for internationalization
 * 
 * @since 1.0.0
 */
define('CFK_TEXT_DOMAIN', 'christmas-for-kids');

/**
 * Check system requirements before loading the plugin
 * 
 * @since 1.0.0
 * @return bool True if requirements are met, false otherwise
 */
function cfk_check_requirements(): bool {
    global $wp_version;
    
    // Check WordPress version
    if (version_compare($wp_version, CFK_MIN_WP_VERSION, '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            printf(
                esc_html__('Christmas for Kids requires WordPress %s or higher. You are running version %s. Please update WordPress.', CFK_TEXT_DOMAIN),
                esc_html(CFK_MIN_WP_VERSION),
                esc_html($GLOBALS['wp_version'])
            );
            echo '</p></div>';
        });
        return false;
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, CFK_MIN_PHP_VERSION, '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            printf(
                esc_html__('Christmas for Kids requires PHP %s or higher. You are running version %s. Please update PHP.', CFK_TEXT_DOMAIN),
                esc_html(CFK_MIN_PHP_VERSION),
                esc_html(PHP_VERSION)
            );
            echo '</p></div>';
        });
        return false;
    }
    
    return true;
}

/**
 * Load the main plugin class
 * 
 * @since 1.0.0
 * @return void
 */
function cfk_load_plugin(): void {
    // Include the main plugin class
    require_once CFK_PLUGIN_PATH . 'includes/class-christmas-for-kids.php';
    
    // Initialize the plugin
    Christmas_For_Kids::get_instance();
}

/**
 * Plugin activation hook callback
 * 
 * @since 1.0.0
 * @return void
 */
function cfk_activate_plugin(): void {
    // Check requirements before activation
    if (!cfk_check_requirements()) {
        wp_die(
            esc_html__('Plugin activation failed due to system requirements not being met.', CFK_TEXT_DOMAIN),
            esc_html__('Plugin Activation Error', CFK_TEXT_DOMAIN),
            array('back_link' => true)
        );
    }
    
    // Load the plugin class for activation
    cfk_load_plugin();
    
    // Run activation procedures
    Christmas_For_Kids::activate();
}

/**
 * Plugin deactivation hook callback
 * 
 * @since 1.0.0
 * @return void
 */
function cfk_deactivate_plugin(): void {
    // Load the plugin class for deactivation
    cfk_load_plugin();
    
    // Run deactivation procedures
    Christmas_For_Kids::deactivate();
}

/**
 * Plugin uninstall hook callback
 * 
 * @since 1.0.0
 * @return void
 */
function cfk_uninstall_plugin(): void {
    // Include uninstall procedures
    require_once CFK_PLUGIN_PATH . 'uninstall.php';
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'cfk_activate_plugin');
register_deactivation_hook(__FILE__, 'cfk_deactivate_plugin');

// Initialize the plugin if requirements are met
if (cfk_check_requirements()) {
    add_action('init', 'cfk_load_plugin');
}

/**
 * Emergency deactivation functionality for debugging
 * Allows deactivation via URL parameter: ?cfk_emergency_deactivate=1
 * 
 * @since 1.0.0
 * @return void
 */
add_action('init', function(): void {
    if (isset($_GET['cfk_emergency_deactivate']) && $_GET['cfk_emergency_deactivate'] === '1') {
        if (current_user_can('manage_options')) {
            deactivate_plugins(CFK_PLUGIN_BASENAME);
            wp_redirect(admin_url('plugins.php?deactivate=true'));
            exit;
        }
    }
});