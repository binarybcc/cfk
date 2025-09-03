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
    private const DB_VERSION = '1.2.0';
    
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
            // Load component classes
            $this->load_component_classes();
            
            // Initialize Child Manager
            require_once CFK_PLUGIN_PATH . 'includes/class-cfk-child-manager.php';
            $child_manager = new CFK_Child_Manager();
            $child_manager->init();
            $this->components['child_manager'] = $child_manager;
            
            // Initialize CSV Importer
            require_once CFK_PLUGIN_PATH . 'includes/class-cfk-csv-importer.php';
            $csv_importer = new CFK_CSV_Importer();
            $csv_importer->init();
            $this->components['csv_importer'] = $csv_importer;
            
            // Initialize Sponsorship Manager
            require_once CFK_PLUGIN_PATH . 'includes/class-cfk-sponsorship-manager.php';
            $sponsorship_manager = new CFK_Sponsorship_Manager($child_manager);
            $sponsorship_manager->init();
            $this->components['sponsorship_manager'] = $sponsorship_manager;
            
            // Initialize Email Manager
            require_once CFK_PLUGIN_PATH . 'includes/class-cfk-email-manager.php';
            $email_manager = new CFK_Email_Manager($child_manager);
            $email_manager->init();
            $this->components['email_manager'] = $email_manager;
            
            // Initialize Public Frontend (only on frontend)
            if (!is_admin()) {
                require_once CFK_PLUGIN_PATH . 'public/class-cfk-public.php';
                $public = new CFK_Public($this);
                $public->init();
                $this->components['public'] = $public;
            }
            
            // Initialize Admin functionality (only on admin)
            if (is_admin()) {
                require_once CFK_PLUGIN_PATH . 'admin/class-cfk-admin.php';
                $admin = new CFK_Admin($this);
                $admin->init();
                $this->components['admin'] = $admin;
            }
            
            $this->log_message('All components initialized successfully', 'info');
        } catch (Exception $e) {
            $this->log_message(
                'Failed to initialize components: ' . $e->getMessage(),
                'error'
            );
        }
    }
    
    /**
     * Load component class files
     * 
     * @since 1.0.0
     * @return void
     */
    private function load_component_classes(): void {
        $class_files = [
            'includes/class-cfk-child-manager.php',
            'includes/class-cfk-csv-importer.php',
            'includes/class-cfk-sponsorship-manager.php',
            'includes/class-cfk-email-manager.php',
        ];
        
        // Add frontend files for non-admin requests
        if (!is_admin()) {
            $class_files[] = 'public/class-cfk-public.php';
        }
        
        // Add admin files for admin requests
        if (is_admin()) {
            $class_files[] = 'admin/class-cfk-admin.php';
        }
        
        foreach ($class_files as $file) {
            $file_path = CFK_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                throw new Exception("Required class file not found: {$file}");
            }
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
        
        // Main menu page
        add_menu_page(
            __('Christmas for Kids', CFK_TEXT_DOMAIN),
            __('Christmas for Kids', CFK_TEXT_DOMAIN),
            'manage_options',
            'christmas-for-kids',
            [$this, 'admin_page_callback'],
            'dashicons-heart',
            30
        );
        
        // Add Children submenu (manages the custom post type)
        add_submenu_page(
            'christmas-for-kids',
            __('All Children', CFK_TEXT_DOMAIN),
            __('All Children', CFK_TEXT_DOMAIN),
            'manage_options',
            'edit.php?post_type=' . CFK_Child_Manager::get_post_type()
        );
        
        // Add New Child submenu
        add_submenu_page(
            'christmas-for-kids',
            __('Add New Child', CFK_TEXT_DOMAIN),
            __('Add New Child', CFK_TEXT_DOMAIN),
            'manage_options',
            'post-new.php?post_type=' . CFK_Child_Manager::get_post_type()
        );
        
        // CSV Import submenu
        add_submenu_page(
            'christmas-for-kids',
            __('Import Children', CFK_TEXT_DOMAIN),
            __('Import Children', CFK_TEXT_DOMAIN),
            'manage_options',
            'cfk-import-csv',
            [$this, 'csv_import_page_callback']
        );
        
        // Hide the default submenu item that WordPress creates
        global $submenu;
        if (isset($submenu['christmas-for-kids'])) {
            unset($submenu['christmas-for-kids'][0]);
        }
    }
    
    /**
     * Enhanced admin page callback with family analytics
     * 
     * @since 1.0.0
     * @return void
     */
    public function admin_page_callback(): void {
        $child_count = wp_count_posts(CFK_Child_Manager::get_post_type())->publish;
        $available_children = count($this->components['child_manager']->get_available_children());
        
        // Get family analytics
        $family_analytics = $this->get_family_analytics();
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Christmas for Kids - Sponsorship System', CFK_TEXT_DOMAIN) . '</h1>';
        
        echo '<div class="cfk-dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">';
        
        // Individual children stats
        echo '<div class="cfk-stat-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px;">';
        echo '<h3 style="margin-top: 0;">' . esc_html__('Total Children', CFK_TEXT_DOMAIN) . '</h3>';
        echo '<div style="font-size: 32px; font-weight: bold; color: #0073aa;">' . esc_html($child_count) . '</div>';
        echo '</div>';
        
        echo '<div class="cfk-stat-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px;">';
        echo '<h3 style="margin-top: 0;">' . esc_html__('Available Children', CFK_TEXT_DOMAIN) . '</h3>';
        echo '<div style="font-size: 32px; font-weight: bold; color: #00a32a;">' . esc_html($available_children) . '</div>';
        echo '</div>';
        
        // Family analytics stats (NEW)
        echo '<div class="cfk-stat-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px;">';
        echo '<h3 style="margin-top: 0;">' . esc_html__('Total Families', CFK_TEXT_DOMAIN) . '</h3>';
        echo '<div style="font-size: 32px; font-weight: bold; color: #7c3aed;">' . esc_html($family_analytics['total_families']) . '</div>';
        echo '</div>';
        
        echo '<div class="cfk-stat-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px;">';
        echo '<h3 style="margin-top: 0;">' . esc_html__('Completed Families', CFK_TEXT_DOMAIN) . '</h3>';
        echo '<div style="font-size: 32px; font-weight: bold; color: #059669;">' . esc_html($family_analytics['completed_families']) . '</div>';
        echo '<small style="color: #666;">' . 
             sprintf('%.1f%% completion rate', $family_analytics['completion_rate']) . 
             '</small>';
        echo '</div>';
        
        echo '</div>';
        
        echo '<div class="cfk-dashboard-actions" style="margin: 30px 0;">';
        echo '<h2>' . esc_html__('Quick Actions', CFK_TEXT_DOMAIN) . '</h2>';
        echo '<p>';
        echo '<a href="' . admin_url('post-new.php?post_type=' . CFK_Child_Manager::get_post_type()) . '" class="button button-primary">' . esc_html__('Add New Child', CFK_TEXT_DOMAIN) . '</a> ';
        echo '<a href="' . admin_url('admin.php?page=cfk-import-csv') . '" class="button">' . esc_html__('Import Children from CSV', CFK_TEXT_DOMAIN) . '</a> ';
        echo '<a href="' . admin_url('edit.php?post_type=' . CFK_Child_Manager::get_post_type()) . '" class="button">' . esc_html__('View All Children', CFK_TEXT_DOMAIN) . '</a>';
        echo '</p>';
        echo '</div>';
        
        // Family insights section (NEW)
        if (!empty($family_analytics['family_insights'])) {
            echo '<div class="cfk-family-insights" style="margin: 30px 0;">';
            echo '<h2>' . esc_html__('Family Insights', CFK_TEXT_DOMAIN) . '</h2>';
            echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">';
            
            // Top families section
            echo '<div class="cfk-insight-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px;">';
            echo '<h3>' . esc_html__('Families Needing Attention', CFK_TEXT_DOMAIN) . '</h3>';
            
            if (!empty($family_analytics['incomplete_families'])) {
                echo '<ul style="margin: 0; padding-left: 20px;">';
                foreach (array_slice($family_analytics['incomplete_families'], 0, 5) as $family) {
                    echo '<li>' . sprintf(
                        esc_html__('Family %s: %d of %d children sponsored', CFK_TEXT_DOMAIN),
                        esc_html($family['family_number']),
                        $family['sponsored_count'],
                        $family['total_children']
                    );
                    
                    if (!empty($family['family_name'])) {
                        echo ' <small>(' . esc_html($family['family_name']) . ')</small>';
                    }
                    
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>' . esc_html__('All families are complete!', CFK_TEXT_DOMAIN) . '</p>';
            }
            echo '</div>';
            
            // Recent sponsorships section
            echo '<div class="cfk-insight-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px;">';
            echo '<h3>' . esc_html__('Recent Family Progress', CFK_TEXT_DOMAIN) . '</h3>';
            echo '<p>' . sprintf(
                esc_html__('%d families have children available for sponsorship.', CFK_TEXT_DOMAIN),
                $family_analytics['families_with_available']
            ) . '</p>';
            echo '<p>' . sprintf(
                esc_html__('Average family size: %.1f children', CFK_TEXT_DOMAIN),
                $family_analytics['avg_family_size']
            ) . '</p>';
            echo '</div>';
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '<div class="cfk-dashboard-info">';
        echo '<h2>' . esc_html__('System Information', CFK_TEXT_DOMAIN) . '</h2>';
        echo '<p><strong>' . esc_html__('Plugin Version:', CFK_TEXT_DOMAIN) . '</strong> ' . esc_html($this->version) . '</p>';
        echo '<p><strong>' . esc_html__('Database Version:', CFK_TEXT_DOMAIN) . '</strong> ' . esc_html(get_option('cfk_db_version', '1.0.0')) . '</p>';
        echo '<p><strong>' . esc_html__('Family Support:', CFK_TEXT_DOMAIN) . '</strong> ' . esc_html__('Active', CFK_TEXT_DOMAIN) . '</p>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Get comprehensive family analytics for the dashboard
     * 
     * @since 1.2.0
     * @return array<string, mixed> Family analytics data
     */
    private function get_family_analytics(): array {
        global $wpdb;
        
        $child_manager = $this->components['child_manager'];
        
        // Get all children with family data
        $children_query = new WP_Query([
            'post_type' => CFK_Child_Manager::get_post_type(),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'cfk_child_family_number',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        $families = [];
        $total_children_in_families = 0;
        
        if ($children_query->have_posts()) {
            foreach ($children_query->posts as $child) {
                $family_number = get_post_meta($child->ID, 'cfk_child_family_number', true);
                $family_name = get_post_meta($child->ID, 'cfk_child_family_name', true);
                
                if (empty($family_number)) {
                    continue;
                }
                
                if (!isset($families[$family_number])) {
                    $families[$family_number] = [
                        'family_number' => $family_number,
                        'family_name' => $family_name,
                        'children' => [],
                        'total_children' => 0,
                        'sponsored_count' => 0,
                        'available_count' => 0
                    ];
                }
                
                $is_sponsored = $child_manager->is_child_sponsored($child->ID);
                
                $families[$family_number]['children'][] = $child->ID;
                $families[$family_number]['total_children']++;
                
                if ($is_sponsored) {
                    $families[$family_number]['sponsored_count']++;
                } else {
                    $families[$family_number]['available_count']++;
                }
                
                $total_children_in_families++;
            }
        }
        
        wp_reset_postdata();
        
        // Calculate analytics
        $total_families = count($families);
        $completed_families = 0;
        $families_with_available = 0;
        $incomplete_families = [];
        $family_sizes = [];
        
        foreach ($families as $family) {
            $family_sizes[] = $family['total_children'];
            
            if ($family['sponsored_count'] >= $family['total_children']) {
                $completed_families++;
            } else {
                $incomplete_families[] = $family;
            }
            
            if ($family['available_count'] > 0) {
                $families_with_available++;
            }
        }
        
        // Sort incomplete families by completion percentage (ascending)
        usort($incomplete_families, function($a, $b) {
            $a_percent = $a['total_children'] > 0 ? ($a['sponsored_count'] / $a['total_children']) : 0;
            $b_percent = $b['total_children'] > 0 ? ($b['sponsored_count'] / $b['total_children']) : 0;
            return $a_percent <=> $b_percent;
        });
        
        $completion_rate = $total_families > 0 ? ($completed_families / $total_families) * 100 : 0;
        $avg_family_size = !empty($family_sizes) ? array_sum($family_sizes) / count($family_sizes) : 0;
        
        return [
            'total_families' => $total_families,
            'completed_families' => $completed_families,
            'completion_rate' => $completion_rate,
            'families_with_available' => $families_with_available,
            'incomplete_families' => $incomplete_families,
            'avg_family_size' => $avg_family_size,
            'total_children_in_families' => $total_children_in_families,
            'family_insights' => !empty($families) // Flag for showing insights section
        ];
    }
    
    /**
     * CSV import page callback
     * 
     * @since 1.0.0
     * @return void
     */
    public function csv_import_page_callback(): void {
        if (isset($this->components['csv_importer'])) {
            $this->components['csv_importer']->render_import_page();
        } else {
            wp_die(__('CSV Importer component not available.', CFK_TEXT_DOMAIN));
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @since 1.0.0
     * @param string $hook_suffix The current admin page
     * @return void
     */
    public function admin_enqueue_scripts(string $hook_suffix): void {
        // Only load on our admin pages or child post type pages
        if (strpos($hook_suffix, 'christmas-for-kids') === false && 
            strpos($hook_suffix, 'cfk_child') === false &&
            !in_array($hook_suffix, [
                'post.php',
                'post-new.php',
                'edit.php'
            ]) &&
            (get_current_screen()->post_type ?? '') !== CFK_Child_Manager::get_post_type()) {
            return;
        }
        
        // Enqueue WordPress media scripts for file uploads
        if (strpos($hook_suffix, 'cfk-import-csv') !== false) {
            wp_enqueue_media();
        }
        
        // Common admin styles
        wp_enqueue_style(
            'cfk-admin-styles',
            CFK_PLUGIN_URL . 'admin/css/admin.css',
            [],
            $this->version
        );
        
        // Admin AJAX script with nonce
        wp_enqueue_script(
            'cfk-admin-scripts',
            CFK_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery'],
            $this->version,
            true
        );
        
        wp_localize_script('cfk-admin-scripts', 'cfk_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cfk_ajax_nonce'),
            'csv_import_nonce' => wp_create_nonce('cfk_csv_import_nonce')
        ]);
    }
    
    /**
     * Enqueue public scripts and styles
     * 
     * @since 1.0.0
     * @return void
     */
    public function public_enqueue_scripts(): void {
        // Let the public component handle its own script enqueuing
        // This method is kept for compatibility
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
        
        $action = sanitize_text_field($_POST['cfk_action'] ?? '');
        
        switch ($action) {
            case 'get_dashboard_stats':
                $this->handle_dashboard_stats();
                break;
                
            default:
                wp_send_json_error(['message' => 'Invalid action']);
                break;
        }
    }
    
    /**
     * Handle dashboard statistics AJAX request
     * 
     * @since 1.0.0
     * @return void
     */
    private function handle_dashboard_stats(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $child_count = wp_count_posts(CFK_Child_Manager::get_post_type())->publish;
        $available_children = isset($this->components['child_manager']) ? 
            count($this->components['child_manager']->get_available_children()) : 0;
        
        wp_send_json_success([
            'total_children' => $child_count,
            'available_children' => $available_children,
            'sponsored_children' => $child_count - $available_children
        ]);
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
        
        // Create sponsorships table with family support
        $sponsorships_table = $wpdb->prefix . 'cfk_sponsorships';
        $sponsorships_sql = "CREATE TABLE $sponsorships_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            child_id bigint(20) unsigned NOT NULL,
            family_id varchar(10) DEFAULT NULL,
            family_number varchar(5) DEFAULT NULL,
            sponsor_name varchar(255) NOT NULL,
            sponsor_email varchar(255) NOT NULL,
            sponsor_phone varchar(20) DEFAULT NULL,
            sponsor_address text DEFAULT NULL,
            sponsorship_type enum('individual','family','siblings') NOT NULL DEFAULT 'individual',
            additional_children text DEFAULT NULL,
            status enum('selected','confirmed','cancelled','expired') NOT NULL DEFAULT 'selected',
            selection_token varchar(32) NOT NULL,
            expires_at datetime DEFAULT NULL,
            confirmed_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_child_id (child_id),
            KEY idx_family_id (family_id),
            KEY idx_family_number (family_number),
            KEY idx_status (status),
            KEY idx_token (selection_token),
            KEY idx_expires (expires_at),
            KEY idx_created (created_at)
        ) $charset_collate;";
        
        // Create email logs table with family support
        $email_logs_table = $wpdb->prefix . 'cfk_email_logs';
        $email_logs_sql = "CREATE TABLE $email_logs_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            recipient_email varchar(255) NOT NULL,
            recipient_name varchar(255) DEFAULT NULL,
            subject varchar(500) NOT NULL,
            email_type enum('selection_confirmation','sponsor_confirmation','admin_notification','cancellation','family_update','sibling_notification') NOT NULL,
            status enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
            child_id bigint(20) unsigned DEFAULT NULL,
            family_id varchar(10) DEFAULT NULL,
            family_number varchar(5) DEFAULT NULL,
            sponsorship_id bigint(20) unsigned DEFAULT NULL,
            template_data text DEFAULT NULL,
            error_message text DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_recipient (recipient_email),
            KEY idx_status (status),
            KEY idx_type (email_type),
            KEY idx_child (child_id),
            KEY idx_family_id (family_id),
            KEY idx_family_number (family_number),
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
    
    /**
     * Get a component instance
     * 
     * @since 1.0.0
     * @param string $component_name The component name
     * @return object|null The component instance or null if not found
     */
    public function get_component(string $component_name): ?object {
        return $this->components[$component_name] ?? null;
    }
}