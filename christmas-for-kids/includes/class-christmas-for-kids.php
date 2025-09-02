<?php
declare(strict_types=1);

/**
 * Main plugin class for Christmas for Kids Sponsorship System
 * 
 * This class implements the singleton pattern to ensure only one instance
 * of the plugin is running at any time. It handles plugin initialization,
 * component loading, and manages the plugin lifecycle.
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 */
class Christmas_For_Kids {
    
    /**
     * Single instance of the plugin
     * 
     * @since 1.0.0
     * @var Christmas_For_Kids|null
     */
    private static ?Christmas_For_Kids $instance = null;
    
    /**
     * Plugin version
     * 
     * @since 1.0.0
     * @var string
     */
    private string $version;
    
    /**
     * Database version for schema updates
     * 
     * @since 1.0.0
     * @var string
     */
    private const DB_VERSION = '1.1.0';
    
    /**
     * Plugin components container
     * 
     * @since 1.0.0
     * @var array<string, object>
     */
    private array $components = [];
    
    /**
     * Constructor - Private to enforce singleton pattern
     * 
     * @since 1.0.0
     */
    private function __construct() {
        $this->version = CFK_VERSION;
        $this->init();
    }
    
    /**
     * Get the single instance of the plugin
     * 
     * @since 1.0.0
     * @return Christmas_For_Kids The plugin instance
     */
    public static function get_instance(): Christmas_For_Kids {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Prevent cloning of the instance
     * 
     * @since 1.0.0
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of the instance
     * 
     * @since 1.0.0
     */
    public function __wakeup(): void {
        throw new Exception('Cannot unserialize singleton');
    }
    
    /**
     * Initialize the plugin
     * 
     * @since 1.0.0
     * @return void
     */
    private function init(): void {
        // Load text domain for internationalization
        add_action('init', [$this, 'load_textdomain']);
        
        // Initialize components
        add_action('init', [$this, 'init_components'], 10);
        
        // Register AJAX handlers
        add_action('wp_ajax_cfk_ajax_handler', [$this, 'handle_ajax_requests']);
        add_action('wp_ajax_nopriv_cfk_ajax_handler', [$this, 'handle_ajax_requests']);
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'public_enqueue_scripts']);
        
        // Schedule cleanup cron job
        add_action('cfk_cleanup_expired_selections', [$this, 'cleanup_expired_selections']);
        
        // Log successful initialization
        $this->log_message('Plugin initialized successfully', 'info');
    }
    
    /**
     * Load plugin text domain for internationalization
     * 
     * @since 1.0.0
     * @return void
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            CFK_TEXT_DOMAIN,
            false,
            dirname(CFK_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Initialize plugin components
     * 
     * @since 1.0.0
     * @return void
     */
    public function init_components(): void {
        try {
            // Components will be loaded here in future phases
            // This method is prepared for modular component loading
            
            $this->log_message('All components initialized successfully', 'info');
        } catch (Exception $e) {
            $this->log_message(
                'Failed to initialize components: ' . $e->getMessage(),
                'error'
            );
        }
    }
    
    /**
     * Add admin menu pages
     * 
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        add_menu_page(
            __('Christmas for Kids', CFK_TEXT_DOMAIN),
            __('Christmas for Kids', CFK_TEXT_DOMAIN),
            'manage_options',
            'christmas-for-kids',
            [$this, 'admin_page_callback'],
            'dashicons-heart',
            30
        );
    }
    
    /**
     * Admin page callback placeholder
     * 
     * @since 1.0.0
     * @return void
     */
    public function admin_page_callback(): void {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Christmas for Kids - Sponsorship System', CFK_TEXT_DOMAIN) . '</h1>';
        echo '<p>' . esc_html__('Plugin successfully initialized. Administrative interface coming in Phase 2.', CFK_TEXT_DOMAIN) . '</p>';
        echo '</div>';
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @since 1.0.0
     * @param string $hook_suffix The current admin page
     * @return void
     */
    public function admin_enqueue_scripts(string $hook_suffix): void {
        // Only load on our admin pages
        if (strpos($hook_suffix, 'christmas-for-kids') === false) {
            return;
        }
        
        // Admin scripts and styles will be enqueued here in future phases
    }
    
    /**
     * Enqueue public scripts and styles
     * 
     * @since 1.0.0
     * @return void
     */
    public function public_enqueue_scripts(): void {
        // Public scripts and styles will be enqueued here in future phases
    }
    
    /**
     * Handle AJAX requests
     * 
     * @since 1.0.0
     * @return void
     */
    public function handle_ajax_requests(): void {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cfk_ajax_nonce')) {
            wp_die('Security check failed');
        }
        
        // AJAX handlers will be implemented in future phases
        wp_die('AJAX functionality coming in Phase 2');
    }
    
    /**
     * Cleanup expired child selections via cron job
     * 
     * @since 1.0.0
     * @return void
     */
    public function cleanup_expired_selections(): void {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'cfk_sponsorships';
            
            // Remove selections older than 2 hours that haven't been confirmed
            $deleted = $wpdb->delete(
                $table_name,
                [
                    'status' => 'selected',
                ],
                [
                    'created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)'
                ]
            );
            
            if ($deleted !== false && $deleted > 0) {
                $this->log_message("Cleaned up {$deleted} expired selections", 'info');
            }
        } catch (Exception $e) {
            $this->log_message(
                'Error during cleanup: ' . $e->getMessage(),
                'error'
            );
        }
    }
    
    /**
     * Plugin activation procedures
     * 
     * @since 1.0.0
     * @return void
     */
    public static function activate(): void {
        $instance = self::get_instance();
        
        try {
            // Create database tables
            $instance->create_database_tables();
            
            // Set default options
            $instance->set_default_options();
            
            // Schedule cron job
            $instance->schedule_cron_jobs();
            
            // Log successful activation
            $instance->log_message('Plugin activated successfully', 'info');
            
        } catch (Exception $e) {
            $instance->log_message(
                'Plugin activation failed: ' . $e->getMessage(),
                'error'
            );
            throw $e;
        }
    }
    
    /**
     * Plugin deactivation procedures
     * 
     * @since 1.0.0
     * @return void
     */
    public static function deactivate(): void {
        $instance = self::get_instance();
        
        try {
            // Clear scheduled cron jobs
            $instance->clear_cron_jobs();
            
            // Log deactivation
            $instance->log_message('Plugin deactivated', 'info');
            
        } catch (Exception $e) {
            $instance->log_message(
                'Error during deactivation: ' . $e->getMessage(),
                'error'
            );
        }
    }
    
    /**
     * Create database tables using WordPress dbDelta
     * 
     * @since 1.0.0
     * @return void
     * @throws Exception If table creation fails
     */
    private function create_database_tables(): void {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create sponsorships table
        $sponsorships_table = $wpdb->prefix . 'cfk_sponsorships';
        $sponsorships_sql = "CREATE TABLE $sponsorships_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            child_id bigint(20) unsigned NOT NULL,
            sponsor_name varchar(255) NOT NULL,
            sponsor_email varchar(255) NOT NULL,
            sponsor_phone varchar(20) DEFAULT NULL,
            status enum('selected','confirmed','cancelled') NOT NULL DEFAULT 'selected',
            selection_token varchar(32) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_child_id (child_id),
            KEY idx_status (status),
            KEY idx_token (selection_token),
            KEY idx_created (created_at)
        ) $charset_collate;";
        
        // Create email logs table
        $email_logs_table = $wpdb->prefix . 'cfk_email_logs';
        $email_logs_sql = "CREATE TABLE $email_logs_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            recipient_email varchar(255) NOT NULL,
            subject varchar(500) NOT NULL,
            email_type enum('selection_confirmation','sponsor_confirmation','admin_notification','cancellation') NOT NULL,
            status enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
            child_id bigint(20) unsigned DEFAULT NULL,
            sponsorship_id bigint(20) unsigned DEFAULT NULL,
            error_message text DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_recipient (recipient_email),
            KEY idx_status (status),
            KEY idx_type (email_type),
            KEY idx_child (child_id),
            KEY idx_sponsorship (sponsorship_id)
        ) $charset_collate;";
        
        // Execute table creation
        $result1 = dbDelta($sponsorships_sql);
        $result2 = dbDelta($email_logs_sql);
        
        // Update database version
        update_option('cfk_db_version', self::DB_VERSION);
        
        // Verify tables were created
        if ($wpdb->get_var("SHOW TABLES LIKE '$sponsorships_table'") !== $sponsorships_table) {
            throw new Exception('Failed to create sponsorships table');
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$email_logs_table'") !== $email_logs_table) {
            throw new Exception('Failed to create email logs table');
        }
        
        $this->log_message('Database tables created successfully', 'info');
    }
    
    /**
     * Set default plugin options
     * 
     * @since 1.0.0
     * @return void
     */
    private function set_default_options(): void {
        $defaults = [
            'cfk_selection_timeout' => 2, // hours
            'cfk_admin_email' => get_option('admin_email'),
            'cfk_sender_name' => get_bloginfo('name'),
            'cfk_sender_email' => get_option('admin_email'),
            'cfk_sponsorship_open' => true,
            'cfk_version' => $this->version,
        ];
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Schedule cron jobs
     * 
     * @since 1.0.0
     * @return void
     */
    private function schedule_cron_jobs(): void {
        if (!wp_next_scheduled('cfk_cleanup_expired_selections')) {
            wp_schedule_event(time(), 'hourly', 'cfk_cleanup_expired_selections');
        }
    }
    
    /**
     * Clear scheduled cron jobs
     * 
     * @since 1.0.0
     * @return void
     */
    private function clear_cron_jobs(): void {
        wp_clear_scheduled_hook('cfk_cleanup_expired_selections');
    }
    
    /**
     * Log messages for debugging and monitoring
     * 
     * @since 1.0.0
     * @param string $message The message to log
     * @param string $level Log level: 'info', 'warning', 'error'
     * @return void
     */
    private function log_message(string $message, string $level = 'info'): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[CFK-{$level}] " . $message);
        }
        
        // Store log in database for admin viewing (future phase)
        // This could be expanded to store logs in the database
    }
    
    /**
     * Get plugin version
     * 
     * @since 1.0.0
     * @return string The plugin version
     */
    public function get_version(): string {
        return $this->version;
    }
    
    /**
     * Check if database needs updating
     * 
     * @since 1.0.0
     * @return bool True if database needs updating
     */
    public function needs_database_update(): bool {
        $current_version = get_option('cfk_db_version', '0');
        return version_compare($current_version, self::DB_VERSION, '<');
    }
}