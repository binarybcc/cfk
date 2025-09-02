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
            'includes/cfk_config_manager.php',
            'includes/cfk_settings_helper.php',
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
    private bool $hooks_registered = false;
    
    private function __construct(
        private readonly CFK_Config $config = new CFK_Config()
    ) {
        // Constructor should be private for singleton pattern
        // No hooks registered here to prevent race conditions
    }
    
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->setup_hooks();
        }
        return self::$instance;
    }
    
    private function setup_hooks(): void {
        if ($this->hooks_registered) {
            return;
        }
        
        // WordPress 6.8.2 compatible hook priorities
        add_action('plugins_loaded', [$this, 'init'], 10);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        add_action('init', [$this, 'check_plugin_health'], 20);
        
        $this->hooks_registered = true;
    }
    
    public function init(): void {
        if ($this->is_initialized) {
            return;
        }
        
        try {
            // WordPress 6.8.2 compatibility: Check if WordPress is fully loaded
            if (!did_action('plugins_loaded')) {
                error_log('CFK: Attempted to initialize before plugins_loaded');
                return;
            }
            
            $this->load_text_domain();
            $this->load_required_files();
            $this->init_components();
            $this->register_plugin_hooks();
            
            $this->is_initialized = true;
            do_action('cfk_plugin_initialized', $this);
            
        } catch (Throwable $e) {
            error_log('CFK Plugin initialization failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            add_action('admin_notices', [$this, 'show_init_error']);
            
            // Prevent partial initialization
            $this->is_initialized = false;
            $this->components = [];
        }
    }
    
    private function load_text_domain(): void {
        load_plugin_textdomain(
            $this->config->text_domain, 
            false, 
            dirname(CFK_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    private function load_required_files(): void {
        $missing_files = [];
        
        foreach ($this->config->required_files as $file) {
            $file_path = $this->config->plugin_path . $file;
            
            if (!file_exists($file_path)) {
                $missing_files[] = "$file (file not found)";
                continue;
            }
            
            if (!$this->safe_include($file_path)) {
                $missing_files[] = "$file (include error)";
            }
        }
        
        if (!empty($missing_files)) {
            update_option('cfk_missing_files', $missing_files);
            add_action('admin_notices', [$this, 'show_missing_files_notice']);
            throw new RuntimeException('Required plugin files are missing: ' . implode(', ', $missing_files));
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
        $failed_components = [];
        
        foreach ($this->config->component_classes as $key => $class_name) {
            try {
                if (!class_exists($class_name)) {
                    $failed_components[] = "$key ($class_name class not found)";
                    continue;
                }
                
                // Initialize component with dependency injection if needed
                $component = new $class_name();
                
                // Verify component implements expected interface (if applicable)
                if (method_exists($component, 'init') && is_callable([$component, 'init'])) {
                    $component->init();
                }
                
                $this->components[$key] = $component;
                
            } catch (Throwable $e) {
                $error_msg = "Failed to initialize $class_name: " . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
                error_log("CFK: $error_msg");
                $failed_components[] = "$key ($error_msg)";
            }
        }
        
        if (!empty($failed_components)) {
            update_option('cfk_failed_components', $failed_components);
            add_action('admin_notices', [$this, 'show_failed_components_notice']);
        } else {
            delete_option('cfk_failed_components');
        }
        
        // Log successful component initialization
        if (!empty($this->components)) {
            $loaded_components = array_keys($this->components);
            error_log('CFK: Successfully loaded components: ' . implode(', ', $loaded_components));
        }
    }
    
    private function register_plugin_hooks(): void {
        // WordPress 6.8.2 compatible hook registration
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        $this->register_ajax_handlers();
        $this->schedule_cleanup_tasks();
    }
    
    public function enqueue_frontend_assets(): void {
        if (!$this->is_initialized) {
            return;
        }
        
        wp_enqueue_script('cfk-frontend', CFK_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], CFK_PLUGIN_VERSION, true);
        wp_enqueue_style('cfk-frontend', CFK_PLUGIN_URL . 'assets/css/frontend.css', [], CFK_PLUGIN_VERSION);
        
        wp_localize_script('cfk-frontend', 'cfk_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cfk_nonce')
        ]);
    }
    
    public function enqueue_admin_assets(): void {
        if (!$this->is_initialized) {
            return;
        }
        
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'cfk') !== false) {
            wp_enqueue_script('cfk-admin', CFK_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], CFK_PLUGIN_VERSION, true);
            wp_enqueue_style('cfk-admin', CFK_PLUGIN_URL . 'assets/css/admin.css', [], CFK_PLUGIN_VERSION);
            
            wp_localize_script('cfk-admin', 'cfk_admin_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cfk_admin_nonce')
            ]);
        }
    }
    
    private function register_ajax_handlers(): void {
        $ajax_handlers = [
            'cfk_select_child' => 'handle_ajax_select_child',
            'cfk_confirm_sponsorship' => 'handle_ajax_confirm_sponsorship', 
            'cfk_import_csv' => 'handle_ajax_import_csv',
            'cfk_cancel_sponsorship' => 'handle_ajax_cancel_sponsorship'
        ];
        
        foreach ($ajax_handlers as $action => $method) {
            add_action("wp_ajax_$action", [$this, $method]);
            // Only allow public access for select and confirm actions
            if (in_array($action, ['cfk_select_child', 'cfk_confirm_sponsorship'], true)) {
                add_action("wp_ajax_nopriv_$action", [$this, $method]);
            }
        }
    }
    
    private function schedule_cleanup_tasks(): void {
        if (!wp_next_scheduled('cfk_cleanup_abandoned_selections')) {
            wp_schedule_event(time(), 'hourly', 'cfk_cleanup_abandoned_selections');
        }
        
        add_action('cfk_cleanup_abandoned_selections', [$this, 'cleanup_abandoned_selections']);
    }
    
    public function handle_ajax_select_child(): void {
        try {
            check_ajax_referer('cfk_nonce', 'nonce');
            
            if (!$this->is_initialized) {
                wp_send_json_error('Plugin not properly initialized');
                return;
            }
            
            if (isset($this->components['sponsorship_manager'])) {
                $this->components['sponsorship_manager']->handle_child_selection();
            } else {
                wp_send_json_error('Sponsorship manager not available');
            }
        } catch (Throwable $e) {
            error_log('CFK AJAX Error (select_child): ' . $e->getMessage());
            wp_send_json_error('An error occurred while processing your request');
        }
    }
    
    public function handle_ajax_confirm_sponsorship(): void {
        try {
            check_ajax_referer('cfk_nonce', 'nonce');
            
            if (!$this->is_initialized) {
                wp_send_json_error('Plugin not properly initialized');
                return;
            }
            
            if (isset($this->components['sponsorship_manager'])) {
                $this->components['sponsorship_manager']->handle_sponsorship_confirmation();
            } else {
                wp_send_json_error('Sponsorship manager not available');
            }
        } catch (Throwable $e) {
            error_log('CFK AJAX Error (confirm_sponsorship): ' . $e->getMessage());
            wp_send_json_error('An error occurred while processing your request');
        }
    }
    
    public function handle_ajax_import_csv(): void {
        try {
            check_ajax_referer('cfk_admin_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Insufficient permissions');
                return;
            }
            
            if (!$this->is_initialized) {
                wp_send_json_error('Plugin not properly initialized');
                return;
            }
            
            if (isset($this->components['csv_importer'])) {
                $this->components['csv_importer']->handle_ajax_import();
            } else {
                wp_send_json_error('CSV importer not available');
            }
        } catch (Throwable $e) {
            error_log('CFK AJAX Error (import_csv): ' . $e->getMessage());
            wp_send_json_error('An error occurred while processing your request');
        }
    }
    
    public function handle_ajax_cancel_sponsorship(): void {
        try {
            check_ajax_referer('cfk_admin_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Insufficient permissions');
                return;
            }
            
            if (!$this->is_initialized) {
                wp_send_json_error('Plugin not properly initialized');
                return;
            }
            
            if (isset($this->components['sponsorship_manager'])) {
                $this->components['sponsorship_manager']->handle_cancellation();
            } else {
                wp_send_json_error('Sponsorship manager not available');
            }
        } catch (Throwable $e) {
            error_log('CFK AJAX Error (cancel_sponsorship): ' . $e->getMessage());
            wp_send_json_error('An error occurred while processing your request');
        }
    }
    
    public function cleanup_abandoned_selections(): void {
        global $wpdb;
        
        try {
            $timeout_hours = self::get_option('cfk_selection_timeout', 2);
            $timeout_time = date('Y-m-d H:i:s', strtotime("-{$timeout_hours} hours"));
            
            $table = $wpdb->prefix . 'cfk_sponsorships';
            
            // WordPress wpdb->delete doesn't support WHERE clauses with time comparison
            // Use wpdb->query instead for complex WHERE conditions
            $result = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table} WHERE status = %s AND selected_time < %s",
                CFK_Status::SELECTED->value,
                $timeout_time
            ));
            
            if ($result !== false && $result > 0) {
                error_log("CFK: Cleaned up {$result} abandoned selections");
            }
        } catch (Throwable $e) {
            error_log('CFK: Error during cleanup: ' . $e->getMessage());
        }
    }
    
    public function check_plugin_health(): void {
        global $wpdb;
        
        // Only run health check if plugin is initialized
        if (!$this->is_initialized) {
            return;
        }
        
        try {
            $required_tables = [
                $wpdb->prefix . 'cfk_sponsorships',
                $wpdb->prefix . 'cfk_email_log'
            ];
            
            $missing_tables = [];
            foreach ($required_tables as $table) {
                $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
                if ($table_exists !== $table) {
                    $missing_tables[] = $table;
                }
            }
            
            if (empty($missing_tables)) {
                delete_option('cfk_missing_tables');
            } else {
                update_option('cfk_missing_tables', $missing_tables);
                add_action('admin_notices', [$this, 'show_missing_tables_notice']);
            }
        } catch (Throwable $e) {
            error_log('CFK: Error during health check: ' . $e->getMessage());
        }
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
        if (empty($missing_tables)) return;
        
        echo '<div class="notice notice-error"><p><strong>Christmas for Kids Plugin:</strong> Missing database tables. Please deactivate and reactivate the plugin.</p></div>';
    }
    
    public function show_failed_components_notice(): void {
        $failed_components = get_option('cfk_failed_components', []);
        if (empty($failed_components)) return;
        
        echo '<div class="notice notice-error"><p><strong>Christmas for Kids Plugin:</strong> Failed to load components:</p><ul>';
        foreach ($failed_components as $component) {
            echo '<li>' . esc_html($component) . '</li>';
        }
        echo '</ul><p>Please check error logs for details.</p></div>';
    }
    
    public function add_admin_menu(): void {
        // Add main menu
        add_menu_page(
            __('Christmas for Kids', 'cfk-sponsorship'),  // page_title
            __('Christmas for Kids', 'cfk-sponsorship'),  // menu_title
            'manage_options',                             // capability
            'cfk-dashboard',                              // menu_slug
            [$this, 'dashboard_page'],                    // callback
            'dashicons-heart',                            // icon_url
            30                                            // position
        );
        
        // Add submenus
        add_submenu_page('cfk-dashboard', __('Dashboard', 'cfk-sponsorship'), __('Dashboard', 'cfk-sponsorship'), 'manage_options', 'cfk-dashboard', [$this, 'dashboard_page']);
        add_submenu_page('cfk-dashboard', __('Import Children', 'cfk-sponsorship'), __('Import Children', 'cfk-sponsorship'), 'manage_options', 'cfk-import', [$this, 'import_page']);
        add_submenu_page('cfk-dashboard', __('Sponsorships', 'cfk-sponsorship'), __('Sponsorships', 'cfk-sponsorship'), 'manage_options', 'cfk-sponsorships', [$this, 'sponsorships_page']);
        add_submenu_page('cfk-dashboard', __('Settings', 'cfk-sponsorship'), __('Settings', 'cfk-sponsorship'), 'manage_options', 'cfk-settings', [$this, 'settings_page']);
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
        
        if (!$this->is_initialized) {
            $this->show_component_error('Settings (Plugin not initialized)');
            return;
        }
        
        if (file_exists($settings_file)) {
            try {
                include $settings_file;
            } catch (Throwable $e) {
                error_log('CFK: Error loading settings page: ' . $e->getMessage());
                $this->show_component_error('Settings (Loading error - check logs)');
            }
        } else {
            $this->show_component_error('Settings (File not found)');
        }
    }
    
    private function render_component_page(string $component_key, string $component_name, string $method): void {
        if (!$this->is_initialized) {
            $this->show_component_error($component_name . ' (Plugin not initialized)');
            return;
        }
        
        if (!isset($this->components[$component_key])) {
            $this->show_component_error($component_name . ' (Component not loaded)');
            return;
        }
        
        $component = $this->components[$component_key];
        
        if (!method_exists($component, $method)) {
            $this->show_component_error($component_name . " (Method '$method' not found)");
            return;
        }
        
        try {
            $component->$method();
        } catch (Throwable $e) {
            error_log("CFK: Error rendering $component_name: " . $e->getMessage());
            $this->show_component_error($component_name . ' (Rendering error - check logs)');
        }
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
            // Load required files for activation
            $config_file = $this->config->plugin_path . 'includes/cfk_config_manager.php';
            if (file_exists($config_file)) {
                require_once $config_file;
            }
            
            $this->create_database_tables();
            
            // Only initialize defaults if config manager is available
            if (class_exists('CFK_Config_Manager')) {
                CFK_Config_Manager::initialize_defaults();
            }
            
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
        
        // WordPress 6.8.2 compatible SQL - proper formatting is critical for dbDelta()
        // Each line must have exact spacing, data types must be precise
        $sql_sponsorships = "CREATE TABLE {$wpdb->prefix}cfk_sponsorships (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  session_id varchar(100) NOT NULL,
  child_id varchar(20) NOT NULL,
  sponsor_name varchar(100) NOT NULL,
  sponsor_email varchar(100) NOT NULL,
  sponsor_phone varchar(20) NOT NULL,
  sponsor_address text NOT NULL,
  sponsor_notes text NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'selected',
  selected_time datetime NOT NULL,
  confirmed_time datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY session_id (session_id),
  KEY child_id (child_id),
  KEY status (status),
  KEY selected_time (selected_time)
) $charset_collate;";
        
        $sql_email_log = "CREATE TABLE {$wpdb->prefix}cfk_email_log (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  session_id varchar(100) NOT NULL,
  email_type varchar(20) NOT NULL,
  recipient_email varchar(100) NOT NULL,
  subject varchar(255) NOT NULL,
  message longtext NOT NULL,
  sent_time datetime NOT NULL,
  delivery_status varchar(20) NOT NULL DEFAULT 'sent',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY session_id (session_id),
  KEY email_type (email_type),
  KEY delivery_status (delivery_status),
  KEY sent_time (sent_time)
) $charset_collate;";
        
        // Ensure upgrade functions are available
        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        
        error_log("CFK: Starting database table creation...");
        error_log("CFK: WordPress version: " . get_bloginfo('version'));
        error_log("CFK: PHP version: " . PHP_VERSION);
        error_log("CFK: Charset collate: " . $charset_collate);
        
        $tables_to_create = [
            'cfk_sponsorships' => $sql_sponsorships,
            'cfk_email_log' => $sql_email_log
        ];
        
        $creation_results = [];
        $verification_results = [];
        
        foreach ($tables_to_create as $table_name => $sql) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            try {
                // Log the SQL being executed
                error_log("CFK: Creating table $full_table_name with SQL: " . preg_replace('/\s+/', ' ', trim($sql)));
                
                // Execute dbDelta
                $result = dbDelta($sql);
                $creation_results[$table_name] = $result;
                
                // Log dbDelta results
                if (is_array($result) && !empty($result)) {
                    error_log("CFK: dbDelta result for $table_name: " . print_r($result, true));
                } else {
                    error_log("CFK: dbDelta returned empty result for $table_name");
                }
                
                // Verify table exists
                $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $full_table_name));
                
                if ($table_exists === $full_table_name) {
                    // Get table structure to verify creation
                    $columns = $wpdb->get_results("DESCRIBE $full_table_name");
                    $column_count = count($columns);
                    
                    $verification_results[$table_name] = [
                        'exists' => true,
                        'columns' => $column_count,
                        'structure' => array_column($columns, 'Field')
                    ];
                    
                    error_log("CFK: Table $full_table_name created successfully with $column_count columns");
                    error_log("CFK: Columns: " . implode(', ', array_column($columns, 'Field')));
                } else {
                    $verification_results[$table_name] = ['exists' => false];
                    error_log("CFK: ERROR - Table $full_table_name was not created");
                }
                
            } catch (Throwable $e) {
                error_log("CFK: ERROR creating table $table_name: " . $e->getMessage());
                error_log("CFK: Stack trace: " . $e->getTraceAsString());
                $verification_results[$table_name] = [
                    'exists' => false, 
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Final verification and comprehensive logging
        $successfully_created = array_filter(
            $verification_results, 
            fn($result) => $result['exists'] ?? false
        );
        
        $failed_tables = array_filter(
            $verification_results,
            fn($result) => !($result['exists'] ?? false)
        );
        
        if (!empty($successfully_created)) {
            error_log("CFK: Successfully created tables: " . implode(', ', array_keys($successfully_created)));
        }
        
        if (!empty($failed_tables)) {
            $error_message = "CFK: Failed to create tables: " . implode(', ', array_keys($failed_tables));
            error_log($error_message);
            
            // Store failed table info for admin notice
            update_option('cfk_table_creation_errors', $failed_tables);
            
            throw new RuntimeException($error_message);
        } else {
            // Clear any previous errors
            delete_option('cfk_table_creation_errors');
        }
        
        // Store creation timestamp and version for future upgrades
        update_option('cfk_db_version', CFK_PLUGIN_VERSION);
        update_option('cfk_db_created_time', current_time('mysql'));
        
        error_log("CFK: Database table creation completed successfully");
    }
    
    
    public static function get_option(string $option_name, mixed $default = false): mixed {
        // Support both old cfk_ prefixed keys and new clean keys
        $clean_key = str_replace('cfk_', '', $option_name);
        
        // If config manager is available and it's a known config key, use it
        if (class_exists('CFK_Config_Manager')) {
            try {
                $schema = CFK_Config_Manager::get_schema();
                if (array_key_exists($clean_key, $schema)) {
                    return CFK_Config_Manager::get($clean_key, $default);
                }
            } catch (Throwable $e) {
                error_log('CFK: Config manager error in get_option: ' . $e->getMessage());
            }
        }
        
        // Fall back to direct WordPress option for unknown keys or if config manager unavailable
        return get_option($option_name, $default);
    }
    
    public static function update_option(string $option_name, mixed $value): bool {
        // Support both old cfk_ prefixed keys and new clean keys
        $clean_key = str_replace('cfk_', '', $option_name);
        
        // If config manager is available and it's a known config key, use it
        if (class_exists('CFK_Config_Manager')) {
            try {
                $schema = CFK_Config_Manager::get_schema();
                if (array_key_exists($clean_key, $schema)) {
                    return CFK_Config_Manager::set($clean_key, $value);
                }
            } catch (Throwable $e) {
                error_log('CFK: Config manager error in update_option: ' . $e->getMessage());
            }
        }
        
        // Fall back to direct WordPress option for unknown keys or if config manager unavailable
        return update_option($option_name, $value);
    }
    
    public function get_component(string $component_name): ?object {
        return $this->components[$component_name] ?? null;
    }
    
    public function is_component_loaded(string $component_name): bool {
        return isset($this->components[$component_name]);
    }
}

// Initialize the plugin - single entry point to prevent race conditions
function cfk_init(): ChristmasForKidsPlugin {
    return ChristmasForKidsPlugin::instance();
}

// WordPress 6.8.2 compatible initialization - priority 5 ensures early initialization
add_action('plugins_loaded', 'cfk_init', 5);

// Emergency deactivation function for debugging
function cfk_emergency_deactivate(): void {
    if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['cfk_emergency_deactivate'])) {
        deactivate_plugins(CFK_PLUGIN_BASENAME);
        wp_redirect(admin_url('plugins.php?deactivated=true'));
        exit;
    }
}
add_action('admin_init', 'cfk_emergency_deactivate');

?>