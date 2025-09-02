<?php
/**
 * Admin Dashboard Class
 * Provides comprehensive overview and management interface for administrators
 *
 * @package ChristmasForKids
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include trait files for modular architecture
require_once CFK_PLUGIN_PATH . 'includes/traits/cfk_dashboard_ajax_trait.php';
require_once CFK_PLUGIN_PATH . 'includes/traits/cfk_dashboard_export_trait.php';
require_once CFK_PLUGIN_PATH . 'includes/traits/cfk_dashboard_stats_trait.php';

class CFK_Admin_Dashboard {
    use CFK_Dashboard_Ajax_Trait;
    use CFK_Dashboard_Export_Trait;
    use CFK_Dashboard_Stats_Trait;
    
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_cfk_toggle_sponsorships', array($this, 'ajax_toggle_sponsorships'));
        add_action('wp_ajax_cfk_dashboard_stats', array($this, 'ajax_get_dashboard_stats'));
        add_action('wp_ajax_cfk_export_data', array($this, 'ajax_export_data'));
        add_action('admin_post_cfk_bulk_reminder', array($this, 'handle_bulk_reminder'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }
    
    /**
     * Enqueue admin dashboard assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_cfk-dashboard') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', array(), '3.9.1', true);
        
        wp_enqueue_style(
            'cfk-admin-dashboard',
            CFK_PLUGIN_URL . 'assets/css/admin-dashboard.css',
            array(),
            CFK_PLUGIN_VERSION,
            'all'
        );
        
        wp_enqueue_script(
            'cfk-admin-dashboard',
            CFK_PLUGIN_URL . 'assets/js/admin-dashboard.js',
            array('jquery', 'chart-js'),
            CFK_PLUGIN_VERSION,
            true
        );
        
        $stats = $this->get_dashboard_stats();
        wp_localize_script('cfk-admin-dashboard', 'cfkDashboardConfig', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cfk_admin_nonce'),
            'chartData' => array(
                'progress' => array(
                    'sponsored' => $stats['sponsored_children'],
                    'available' => $stats['available_children']
                ),
                'ageBreakdown' => $stats['age_breakdown']
            ),
            'labels' => array(
                'sponsored' => __('Sponsored', 'cfk-sponsorship'),
                'available' => __('Available', 'cfk-sponsorship'),
                'exporting' => __('Exporting...', 'cfk-sponsorship'),
                'refreshing' => __('Refreshing...', 'cfk-sponsorship'),
                'sending' => __('Sending...', 'cfk-sponsorship'),
                'error' => __('An error occurred', 'cfk-sponsorship'),
                'networkError' => __('Network error occurred', 'cfk-sponsorship'),
                'exportSuccess' => __('Export completed successfully', 'cfk-sponsorship'),
                'statsUpdated' => __('Statistics updated successfully', 'cfk-sponsorship'),
                'confirmBulkReminder' => __('Are you sure you want to send bulk reminders? This action cannot be undone.', 'cfk-sponsorship')
            )
        ));
    }
    
    /**
     * Display the main dashboard
     */
    public function display_dashboard() {
        // Get comprehensive statistics
        $stats = $this->get_comprehensive_stats();
        $recent_activity = $this->get_recent_activity();
        $system_status = $this->get_system_status();
        
        ?>
        <div class="wrap cfk-dashboard">
            <h1 class="cfk-dashboard__title">
                <?php _e('Christmas for Kids Dashboard', 'cfk-sponsorship'); ?>
                <span class="cfk-dashboard__version">v<?php echo CFK_PLUGIN_VERSION; ?></span>
            </h1>
            
            <?php $this->render_quick_actions(); ?>
            
            <div class="cfk-dashboard__grid">
                <!-- Main Statistics Cards -->
                <div class="cfk-section">
                    <h2 class="cfk-section__title"><?php _e('Overview', 'cfk-sponsorship'); ?></h2>
                    <div class="cfk-stats__grid">
                        <?php $this->render_stat_card(__('Total Children', 'cfk-sponsorship'), $stats['total_children'], 'children'); ?>
                        <?php $this->render_stat_card(__('Children Sponsored', 'cfk-sponsorship'), $stats['sponsored_children'], 'sponsored'); ?>
                        <?php $this->render_stat_card(__('Available Children', 'cfk-sponsorship'), $stats['available_children'], 'available'); ?>
                        <?php $this->render_stat_card(__('Total Families', 'cfk-sponsorship'), $stats['total_families'], 'families'); ?>
                        <?php $this->render_stat_card(__('Active Sponsors', 'cfk-sponsorship'), $stats['total_sponsors'], 'sponsors'); ?>
                        <?php $this->render_stat_card(__('Emails Sent', 'cfk-sponsorship'), $stats['emails_sent'], 'emails'); ?>
                    </div>
                </div>
                
                <!-- Progress and Charts -->
                <div class="cfk-charts-section">
                    <div class="cfk-chart-container">
                        <h3><?php _e('Sponsorship Progress', 'cfk-sponsorship'); ?></h3>
                        <div class="cfk-progress-chart">
                            <canvas id="cfk-progress-chart" width="400" height="200"></canvas>
                        </div>
                        <div class="cfk-progress-details">
                            <div class="cfk-progress-item">
                                <span class="cfk-progress-label"><?php _e('Completion Rate:', 'cfk-sponsorship'); ?></span>
                                <span class="cfk-progress-value"><?php echo $stats['sponsorship_percentage']; ?>%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cfk-chart-container">
                        <h3><?php _e('Age Range Distribution', 'cfk-sponsorship'); ?></h3>
                        <div class="cfk-age-chart">
                            <canvas id="cfk-age-chart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- System Status -->
                <div class="cfk-status-section">
                    <h3><?php _e('System Status', 'cfk-sponsorship'); ?></h3>
                    <div class="cfk-status-grid">
                        <?php foreach ($system_status as $status): ?>
                        <div class="cfk-status-item cfk-status-<?php echo esc_attr($status['status']); ?>">
                            <div class="cfk-status-icon"><?php echo $status['icon']; ?></div>
                            <div class="cfk-status-content">
                                <h4><?php echo esc_html($status['title']); ?></h4>
                                <p><?php echo esc_html($status['message']); ?></p>
                                <?php if (!empty($status['action'])): ?>
                                <a href="<?php echo esc_url($status['action']['url']); ?>" class="cfk-status-action">
                                    <?php echo esc_html($status['action']['text']); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="cfk-activity-section">
                    <h3><?php _e('Recent Activity', 'cfk-sponsorship'); ?></h3>
                    <div class="cfk-activity-feed">
                        <?php if (empty($recent_activity)): ?>
                        <div class="cfk-no-activity">
                            <p><?php _e('No recent activity found.', 'cfk-sponsorship'); ?></p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($recent_activity as $activity): ?>
                            <div class="cfk-activity-item">
                                <div class="cfk-activity-icon"><?php echo $activity['icon']; ?></div>
                                <div class="cfk-activity-content">
                                    <h4><?php echo esc_html($activity['title']); ?></h4>
                                    <p><?php echo esc_html($activity['description']); ?></p>
                                    <span class="cfk-activity-time"><?php echo esc_html($activity['time']); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="cfk-activity-footer">
                            <a href="<?php echo admin_url('admin.php?page=cfk-sponsorships'); ?>" class="button">
                                <?php _e('View All Activity', 'cfk-sponsorship'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats by Age Range -->
                <div class="cfk-breakdown-section">
                    <h3><?php _e('Children by Age Range', 'cfk-sponsorship'); ?></h3>
                    <div class="cfk-breakdown-grid">
                        <?php foreach ($stats['age_breakdown'] as $age_range => $data): ?>
                        <div class="cfk-breakdown-item">
                            <h4><?php echo esc_html($age_range); ?></h4>
                            <div class="cfk-breakdown-numbers">
                                <span class="cfk-breakdown-total"><?php echo $data['total']; ?></span>
                                <span class="cfk-breakdown-label"><?php _e('total', 'cfk-sponsorship'); ?></span>
                            </div>
                            <div class="cfk-breakdown-progress">
                                <?php 
                                $percentage = $data['total'] > 0 ? round(($data['sponsored'] / $data['total']) * 100) : 0;
                                ?>
                                <div class="cfk-progress-bar">
                                    <div class="cfk-progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="cfk-breakdown-percentage"><?php echo $percentage; ?>% sponsored</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Export and Tools -->
                <div class="cfk-tools-section">
                    <h3><?php _e('Tools & Export', 'cfk-sponsorship'); ?></h3>
                    <div class="cfk-tools-grid">
                        <div class="cfk-tool-card">
                            <h4><?php _e('Export Data', 'cfk-sponsorship'); ?></h4>
                            <p><?php _e('Download sponsorship data in various formats.', 'cfk-sponsorship'); ?></p>
                            <div class="cfk-export-buttons">
                                <button class="button cfk-export-btn" data-type="sponsorships">
                                    <?php _e('Export Sponsorships', 'cfk-sponsorship'); ?>
                                </button>
                                <button class="button cfk-export-btn" data-type="children">
                                    <?php _e('Export Children', 'cfk-sponsorship'); ?>
                                </button>
                                <button class="button cfk-export-btn" data-type="emails">
                                    <?php _e('Export Email Log', 'cfk-sponsorship'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="cfk-tool-card">
                            <h4><?php _e('Bulk Actions', 'cfk-sponsorship'); ?></h4>
                            <p><?php _e('Perform actions on multiple items at once.', 'cfk-sponsorship'); ?></p>
                            <div class="cfk-bulk-actions">
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                                    <?php wp_nonce_field('cfk_bulk_reminder', 'bulk_reminder_nonce'); ?>
                                    <input type="hidden" name="action" value="cfk_bulk_reminder">
                                    <select name="reminder_type">
                                        <option value="deadline"><?php _e('Deadline Reminder', 'cfk-sponsorship'); ?></option>
                                        <option value="thank_you"><?php _e('Thank You Email', 'cfk-sponsorship'); ?></option>
                                    </select>
                                    <input type="submit" class="button" value="<?php _e('Send to All Sponsors', 'cfk-sponsorship'); ?>">
                                </form>
                            </div>
                        </div>
                        
                        <div class="cfk-tool-card">
                            <h4><?php _e('System Maintenance', 'cfk-sponsorship'); ?></h4>
                            <p><?php _e('Clean up and optimize system data.', 'cfk-sponsorship'); ?></p>
                            <div class="cfk-maintenance-actions">
                                <button class="button cfk-cleanup-btn" data-action="abandoned">
                                    <?php _e('Clean Abandoned Selections', 'cfk-sponsorship'); ?>
                                </button>
                                <button class="button cfk-refresh-stats-btn">
                                    <?php _e('Refresh Statistics', 'cfk-sponsorship'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Render quick actions bar
     */
    private function render_quick_actions() {
        $sponsorships_open = CFK_Config_Manager::get('sponsorships_open');
        
        ?>
        <div class="cfk-quick-actions">
            <div class="cfk-sponsorship-toggle">
                <label class="cfk-toggle-switch">
                    <input type="checkbox" id="cfk-toggle-sponsorships" <?php checked($sponsorships_open); ?>>
                    <span class="cfk-toggle-slider"></span>
                </label>
                <span class="cfk-toggle-label">
                    <?php if ($sponsorships_open): ?>
                        <strong style="color: #27ae60;"><?php _e('Sponsorships Open', 'cfk-sponsorship'); ?></strong>
                    <?php else: ?>
                        <strong style="color: #e74c3c;"><?php _e('Sponsorships Closed', 'cfk-sponsorship'); ?></strong>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="cfk-quick-links">
                <a href="<?php echo admin_url('edit.php?post_type=child'); ?>" class="button button-primary">
                    <?php _e('Manage Children', 'cfk-sponsorship'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=cfk-import-csv'); ?>" class="button">
                    <?php _e('Import CSV', 'cfk-sponsorship'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=cfk-sponsorships'); ?>" class="button">
                    <?php _e('View Sponsorships', 'cfk-sponsorship'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=cfk-emails'); ?>" class="button">
                    <?php _e('Email Log', 'cfk-sponsorship'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=cfk-settings'); ?>" class="button">
                    <?php _e('Settings', 'cfk-sponsorship'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render individual stat card
     */
    private function render_stat_card($title, $value, $type) {
        ?>
        <div class="cfk-stat-card cfk-stat-card--<?php echo esc_attr($type); ?>">
            <div class="cfk-stat-card__content">
                <h3><?php echo esc_html($title); ?></h3>
                <div class="cfk-stat-card__number"><?php echo number_format($value); ?></div>
            </div>
            <div class="cfk-stat-card__icon">
                <?php echo $this->get_stat_icon($type); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get icon for stat type
     */
    private function get_stat_icon($type) {
        $icons = array(
            'children' => '<span class="dashicons dashicons-groups"></span>',
            'sponsored' => '<span class="dashicons dashicons-yes-alt"></span>',
            'available' => '<span class="dashicons dashicons-clock"></span>',
            'families' => '<span class="dashicons dashicons-admin-home"></span>',
            'sponsors' => '<span class="dashicons dashicons-businessman"></span>',
            'emails' => '<span class="dashicons dashicons-email-alt"></span>'
        );
        
        return isset($icons[$type]) ? $icons[$type] : '<span class="dashicons dashicons-chart-bar"></span>';
    }
    
    /**
     * Handle bulk reminder emails
     */
    public function handle_bulk_reminder() {
        if (!wp_verify_nonce($_POST['bulk_reminder_nonce'], 'cfk_bulk_reminder') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'cfk-sponsorship'));
        }
        
        $reminder_type = sanitize_text_field($_POST['reminder_type']);
        
        if (class_exists('CFK_Email_Manager')) {
            $email_manager = new CFK_Email_Manager();
            $results = $email_manager->send_bulk_reminders($reminder_type);
            
            $message = sprintf(
                __('Bulk emails sent: %d successful, %d failed out of %d total sponsors.', 'cfk-sponsorship'),
                $results['sent'],
                $results['failed'],
                $results['total']
            );
            
            wp_redirect(add_query_arg(array(
                'page' => 'cfk-dashboard',
                'bulk_reminder' => '1',
                'message' => urlencode($message)
            ), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array(
                'page' => 'cfk-dashboard',
                'error' => __('Email manager not available', 'cfk-sponsorship')
            ), admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        if (isset($_GET['bulk_reminder']) && $_GET['bulk_reminder'] == '1' && isset($_GET['message'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(urldecode($_GET['message'])) . '</p></div>';
        }
        
        if (isset($_GET['error'])) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($_GET['error']) . '</p></div>';
        }
    }
    
}

?>