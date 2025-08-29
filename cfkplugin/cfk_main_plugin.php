<?php
/**
 * Plugin Name: Christmas for Kids - Sponsorship System
 * Plugin URI: https://cforkids.org
 * Description: Complete sponsorship management system for Christmas for Kids charity. Handles child profiles, CSV imports, sponsorship tracking, and automated emails.
 * Version: 1.0.2
 * Author: Christmas for Kids
 * Author URI: https://cforkids.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cfk-sponsorship
 * Domain Path: /languages
 * Requires PHP: 8.2
 *
 * @package ChristmasForKids
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// PHP version check
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Christmas for Kids plugin requires PHP 8.2 or higher. Current version: ' . PHP_VERSION . '</p></div>';
    });
    return;
}

// Define plugin constants
define('CFK_PLUGIN_VERSION', '1.0.2');
define('CFK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CFK_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CFK_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Enums for type safety and memory efficiency
enum CFK_Status: string {
    case SELECTED = 'selected';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
}

enum CFK_EmailType: string {
    case SPONSOR = 'sponsor';
    case ADMIN = 'admin';
    case REMINDER = 'reminder';
}

enum CFK_DeliveryStatus: string {
    case SENT = 'sent';
    case FAILED = 'failed';
    case PENDING = 'pending';
}

// Readonly configuration class
readonly class CFK_Config {
    public function __construct(
        public string $plugin_path = CFK_PLUGIN_PATH,
        public string $plugin_url = CFK_PLUGIN_URL,
        public string $version = CFK_PLUGIN_VERSION,
        public string $text_domain = 'cfk-sponsorship',
        public array $required_files = [
            'includes/cfk_children_manager.php',
            'includes/cfk_csv_importer.php', 
            'includes/cfk_sponsorship_manager.php',
            'includes/cfk_email_manager.php',
            'includes/cfk_admin_dashboard.php',
            'includes/cfk_frontend_display.php',
            'includes/cfk_helper_functions.php'
        ],
        public array $component_classes = [
            'children_manager' => 'CFK_Children_Manager',
            'csv_importer' => 'CFK_CSV_Importer',
            'sponsorship_manager' => 'CFK_Sponsorship_Manager',
            'email_manager' => 'CFK_Email_Manager',
            'admin_dashboard' => 'CFK_Admin_Dashboard',
            'frontend_display' => 'CFK_Frontend_Display'
        ]
    ) {}
}

class ChristmasForKidsPlugin {
    private static ?self $instance = null;
    private array $components = [];
    private bool $is_initialized = false;
    
    public function __construct(
        private readonly CFK_Config $config = new CFK_Config()
    ) {
        add_action('plugins_loaded', $this->init(...), 10);
        register_activation_hook(__FILE__, $this->activate(...));
        register_deactivation_hook(__FILE__, $this->deactivate(...));
        add_action('admin_init', $this->check_plugin_health(...));
    }
    
    public static function instance(): self {
        return self::$instance ??= new self();
    }
    
    public function init(): void {
        if ($this->is_initialized) {
            return;
        }
        
        try {
            $this->load_text_domain();
            $this->includes();
            $this->init_components();
            $this->register_hooks();
            $this->is_initialized = true;
            
        } catch (Throwable $e) {
            error_log('CFK Plugin initialization failed: ' . $e->getMessage());
            add_action('admin_notices', $this->show_init_error(...));
        }
    }
    
    private function load_text_domain(): void {
        load_plugin_textdomain(
            $this->config->text_domain, 
            false, 
            dirname(CFK_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    private function includes(): void {
        $missing_files = [];
        
        foreach ($this->config->required_files as $file) {
            $file_path = $this->config->plugin_path . $file;
            
            match(true) {
                !file_exists($file_path) => $missing_files[] = "$file (file not found)",
                !$this->safe_include($file_path) => $missing_files[] = "$file (include error)",
                default => null
            };
        }
        
        if ($missing_files !== []) {
            update_option('cfk_missing_files', $missing_files);
            add_action('admin_notices', $this->show_missing_files_notice(...));
        } else {
            delete_option('cfk_missing_files');
        }
    }
    
    private function safe_include(string $file_path): bool {
        try {
            require_once $file_path;
            return true;
        } catch (Throwable $e) {
            error_log("Failed to include $file_path: " . $e->getMessage());
            return false;
        }
    }
    
    private function init_components(): void {
        foreach ($this->config->component_classes as $key => $class_name) {
            if (class_exists($class_name)) {
                try {
                    $this->components[$key] = new $class_name();
                } catch (Throwable $e) {
                    error_log("Failed to initialize $class_name: " . $e->getMessage());
                }
            }
        }
    }
    
    private function register_hooks(): void {
        add_action('admin_menu', $this->add_admin_menu(...));
        $this->register_ajax_handlers();
        $this->schedule_cleanup_tasks();
    }
    
    private function register_ajax_handlers(): void {
        $ajax_handlers = [
            'cfk_select_child' => $this->handle_ajax_select_child(...),
            'cfk_confirm_sponsorship' => $this->handle_ajax_confirm_sponsorship(...),
            'cfk_import_csv' => $this->handle_ajax_import_csv(...),
            'cfk_cancel_sponsorship' => $this->handle_ajax_cancel_sponsorship(...)
        ];
        
        foreach ($ajax_handlers as $action => $handler) {
            add_action("wp_ajax_$action", $handler);
            add_action("wp_ajax_nopriv_$action", $handler);
        }
    }
    
    private function schedule_cleanup_tasks(): void {
        if (!wp_next_scheduled('cfk_cleanup_abandoned_selections')) {
            wp_schedule_event(time(), 'hourly', 'cfk_cleanup_abandoned_selections');
        }
        
        add_action('cfk_cleanup_abandoned_selections', $this->cleanup_abandoned_selections(...));
    }
    
    public function handle_ajax_select_child(): void {
        check_ajax_referer('cfk_nonce', 'nonce');
        
        match(true) {
            isset($this->components['sponsorship_manager']) => 
                $this->components['sponsorship_manager']->handle_child_selection(),
            default => wp_send_json_error('Sponsorship manager not available')
        };
    }
    
    public function handle_ajax_confirm_sponsorship(): void {
        check_ajax_referer('cfk_nonce', 'nonce');
        
        match(true) {
            isset($this->components['sponsorship_manager']) => 
                $this->components['sponsorship_manager']->handle_sponsorship_confirmation(),
            default => wp_send_json_error('Sponsorship manager not available')
        };
    }
    
    public function handle_ajax_import_csv(): void {
        check_ajax_referer('cfk_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        match(true) {
            isset($this->components['csv_importer']) => 
                $this->components['csv_importer']->handle_ajax_import(),
            default => wp_send_json_error('CSV importer not available')
        };
    }
    
    public function handle_ajax_cancel_sponsorship(): void {
        check_ajax_referer('cfk_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        match(true) {
            isset($this->components['sponsorship_manager']) => 
                $this->components['sponsorship_manager']->handle_cancellation(),
            default => wp_send_json_error('Sponsorship manager not available')
        };
    }
    
    public function cleanup_abandoned_selections(): void {
        global $wpdb;
        
        $timeout_hours = $this->get_option('cfk_selection_timeout', 2);
        $timeout_time = date('Y-m-d H:i:s', strtotime("-{$timeout_hours} hours"));
        
        $table = $wpdb->prefix . 'cfk_sponsorships';
        
        $result = $wpdb->delete(
            $table,
            ['status' => CFK_Status::SELECTED->value],
            ['%s'],
            "selected_time < %s",
            [$timeout_time]
        );
        
        if ($result !== false) {
            error_log("CFK: Cleaned up {$result} abandoned selections");
        }
    }
    
    public function check_plugin_health(): void {
        global $wpdb;
        
        $required_tables = [
            $wpdb->prefix . 'cfk_sponsorships',
            $wpdb->prefix . 'cfk_email_log'
        ];
        
        $missing_tables = array_filter(
            $required_tables,
            fn($table) => $wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table
        );
        
        match($missing_tables) {
            [] => delete_option('cfk_missing_tables'),
            default => [
                update_option('cfk_missing_tables', $missing_tables),
                add_action('admin_notices', $this->show_missing_tables_notice(...))
            ]
        };
    }
    
    public function show_init_error(): void {
        echo '<div class="notice notice-error"><p><strong>Christmas for Kids Plugin:</strong> Failed to initialize properly. Check error logs for details.</p></div>';
    }
    
    public function show_missing_files_notice(): void {
        $missing_files = get_option('cfk_missing_files', []);
        if ($missing_files === []) return;
        
        echo '<div class="notice notice-error"><p><strong>Christmas for Kids Plugin:</strong> Missing required files:</p><ul>';
        foreach ($missing_files as $file) {
            echo '<li>' . esc_html($file) . '</li>';
        }
        echo '</ul></div>';
    }
    
    public function show_missing_tables_notice(): void {
        $missing_tables = get_option('cfk_missing_tables', []);
        if ($missing_tables === []) return;
        
        echo '<div class="notice notice-error"><p><strong>Christmas for Kids Plugin:</strong> Missing database tables. Please deactivate and reactivate the plugin.</p></div>';
    }
    
    public function add_admin_menu(): void {
        $menu_items = [
            [
                'page_title' => __('Christmas for Kids', 'cfk-sponsorship'),
                'menu_title' => __('Christmas for Kids', 'cfk-sponsorship'),
                'capability' => 'manage_options',
                'menu_slug' => 'cfk-dashboard',
                'callback' => $this->dashboard_page(...),
                'icon' => 'dashicons-heart',
                'position' => 30
            ]
        ];
        
        $submenu_items = [
            ['cfk-dashboard', __('Dashboard', 'cfk-sponsorship'), __('Dashboard', 'cfk-sponsorship'), 'manage_options', 'cfk-dashboard', $this->dashboard_page(...)],
            ['cfk-dashboard', __('Import Children', 'cfk-sponsorship'), __('Import Children', 'cfk-sponsorship'), 'manage_options', 'cfk-import', $this->import_page(...)],
            ['cfk-dashboard', __('Sponsorships', 'cfk-sponsorship'), __('Sponsorships', 'cfk-sponsorship'), 'manage_options', 'cfk-sponsorships', $this->sponsorships_page(...)],
            ['cfk-dashboard', __('Settings', 'cfk-sponsorship'), __('Settings', 'cfk-sponsorship'), 'manage_options', 'cfk-settings', $this->settings_page(...)]
        ];
        
        // Add main menu
        add_menu_page(...$menu_items[0]);
        
        // Add submenus
        foreach ($submenu_items as $item) {
            add_submenu_page(...$item);
        }
    }
    
    public function dashboard_page(): void {
        $this->render_component_page('admin_dashboard', 'Dashboard', 'display_dashboard');
    }
    
    public function import_page(): void {
        $this->render_component_page('csv_importer', 'CSV Importer', 'display_import_page');
    }
    
    public function sponsorships_page(): void {
        $this->render_component_page('sponsorship_manager', 'Sponsorship Manager', 'display_sponsorships_page');
    }
    
    public function settings_page(): void {
        $settings_file = $this->config->plugin_path . 'admin/cfk_settings_page.php';
        
        match(file_exists($settings_file)) {
            true => include $settings_file,
            false => $this->show_component_error('Settings')
        };
    }
    
    private function render_component_page(string $component_key, string $component_name, string $method): void {
        match(isset($this->components[$component_key])) {
            true => $this->components[$component_key]->$method(),
            false => $this->show_component_error($component_name)
        };
    }
    
    private function show_component_error(string $component_name): void {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($component_name) . '</h1>';
        echo '<div class="notice notice-error"><p>';
        printf(
            esc_html__('%s component is not available. Please check that all plugin files are properly installed.', 'cfk-sponsorship'),
            esc_html($component_name)
        );
        echo '</p></div>';
        echo '</div>';
    }
    
    public function activate(): void {
        try {
            $this->create_database_tables();
            $this->set_default_options();
            wp_clear_scheduled_hook('cfk_cleanup_abandoned_selections');
            flush_rewrite_rules();
            update_option('cfk_plugin_activated', time());
            
        } catch (Throwable $e) {
            error_log('CFK Plugin activation failed: ' . $e->getMessage());
            deactivate_plugins(CFK_PLUGIN_BASENAME);
            wp_die('Plugin activation failed: ' . $e->getMessage());
        }
    }
    
    public function deactivate(): void {
        wp_clear_scheduled_hook('cfk_cleanup_abandoned_selections');
        flush_rewrite_rules();
        update_option('cfk_plugin_deactivated', time());
    }
    
    private function create_database_tables(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = [
            'sponsorships' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cfk_sponsorships (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                session_id varchar(100) NOT NULL,
                child_id varchar(20) NOT NULL,
                sponsor_name varchar(100) DEFAULT '',
                sponsor_email varchar(100) DEFAULT '',
                sponsor_phone varchar(20) DEFAULT '',
                sponsor_address text DEFAULT '',
                sponsor_notes text DEFAULT '',
                status enum('selected','confirmed','cancelled') DEFAULT 'selected',
                selected_time datetime DEFAULT CURRENT_TIMESTAMP,
                confirmed_time datetime DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY session_id (session_id),
                KEY child_id (child_id),
                KEY status (status),
                KEY selected_time (selected_time)
            ) $charset_collate;",
            
            'email_log' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cfk_email_log (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                session_id varchar(100) NOT NULL,
                email_type enum('sponsor','admin','reminder') NOT NULL,
                recipient_email varchar(100) NOT NULL,
                subject varchar(255) NOT NULL,
                message longtext NOT NULL,
                sent_time datetime DEFAULT CURRENT_TIMESTAMP,
                delivery_status enum('sent','failed','pending') DEFAULT 'sent',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY session_id (session_id),
                KEY email_type (email_type),
                KEY delivery_status (delivery_status),
                KEY sent_time (sent_time)
            ) $charset_collate;"
        ];
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $name => $sql) {
            $result = dbDelta($sql);
            error_log("CFK Table '$name' created: " . print_r($result, true));
        }
    }
    
    private function set_default_options(): void {
        $defaults = [
            'cfk_admin_email' => get_option('admin_email'),
            'cfk_selection_timeout' => 2,
            'cfk_email_from_name' => 'Christmas for Kids',
            'cfk_email_from_email' => 'noreply@' . parse_url(home_url(), PHP_URL_HOST),
            'cfk_sponsorships_open' => false,
            'cfk_plugin_version' => $this->config->version
        ];
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
    
    public static function get_option(string $option_name, mixed $default = false): mixed {
        return get_option($option_name, $default);
    }
    
    public static function update_option(string $option_name, mixed $value): bool {
        return update_option($option_name, $value);
    }
    
    public function get_component(string $component_name): ?object {
        return $this->components[$component_name] ?? null;
    }
    
    public function is_component_loaded(string $component_name): bool {
        return isset($this->components[$component_name]);
    }
}

// Initialize the plugin
function cfk_init(): ChristmasForKidsPlugin {
    return ChristmasForKidsPlugin::instance();
}

// Start the plugin
add_action('plugins_loaded', cfk_init(...), 5);

// Emergency deactivation function
function cfk_emergency_deactivate(): void {
    if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['cfk_emergency_deactivate'])) {
        deactivate_plugins(CFK_PLUGIN_BASENAME);
        wp_redirect(admin_url('plugins.php?deactivated=true'));
        exit;
    }
}
add_action('admin_init', cfk_emergency_deactivate(...));

?>