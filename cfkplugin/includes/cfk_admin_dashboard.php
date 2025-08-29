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

class CFK_Admin_Dashboard {
    
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
        
        wp_add_inline_script('jquery', $this->get_dashboard_javascript());
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
        
        <script>
        // Chart data for JavaScript
        window.cfkChartData = {
            progress: {
                sponsored: <?php echo $stats['sponsored_children']; ?>,
                available: <?php echo $stats['available_children']; ?>
            },
            ageBreakdown: <?php echo json_encode($stats['age_breakdown']); ?>
        };
        </script>
        <?php
    }
    
    /**
     * Render quick actions bar
     */
    private function render_quick_actions() {
        $sponsorships_open = ChristmasForKidsPlugin::get_option('cfk_sponsorships_open', false);
        
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
     * Get comprehensive statistics
     */
    private function get_comprehensive_stats() {
        global $wpdb;
        
        // Basic children stats
        $children_stats = CFK_Children_Manager::get_sponsorship_stats();
        
        // Sponsorship stats
        $sponsorship_table = $wpdb->prefix . 'cfk_sponsorships';
        $total_sponsors = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM $sponsorship_table WHERE status = 'confirmed'");
        
        // Email stats
        $email_table = $wpdb->prefix . 'cfk_email_log';
        $emails_sent = $wpdb->get_var("SELECT COUNT(*) FROM $email_table WHERE delivery_status = 'sent'");
        
        // Age breakdown
        $age_breakdown = $this->get_age_breakdown();
        
        return array_merge($children_stats, array(
            'total_sponsors' => intval($total_sponsors),
            'emails_sent' => intval($emails_sent),
            'age_breakdown' => $age_breakdown
        ));
    }
    
    /**
     * Get age breakdown statistics
     */
    private function get_age_breakdown() {
        global $wpdb;
        
        $age_ranges = array('Infant', 'Elementary', 'Middle School', 'High School');
        $breakdown = array();
        
        foreach ($age_ranges as $age_range) {
            // Total children in this age range
            $total = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_child_age_range'
                AND pm.meta_value = %s
                AND p.post_type = 'child'
                AND p.post_status = 'publish'
            ", $age_range));
            
            // Sponsored children in this age range
            $sponsored = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM {$wpdb->postmeta} pm1
                INNER JOIN {$wpdb->posts} p ON pm1.post_id = p.ID
                INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
                WHERE pm1.meta_key = '_child_age_range'
                AND pm1.meta_value = %s
                AND pm2.meta_key = '_child_sponsored'
                AND pm2.meta_value = '1'
                AND p.post_type = 'child'
                AND p.post_status = 'publish'
            ", $age_range));
            
            $breakdown[$age_range] = array(
                'total' => intval($total),
                'sponsored' => intval($sponsored),
                'available' => intval($total) - intval($sponsored)
            );
        }
        
        return $breakdown;
    }
    
    /**
     * Get recent activity
     */
    private function get_recent_activity() {
        global $wpdb;
        
        $activities = array();
        
        // Recent sponsorships
        $sponsorship_table = $wpdb->prefix . 'cfk_sponsorships';
        $recent_sponsorships = $wpdb->get_results("
            SELECT sponsor_name, confirmed_time, COUNT(*) as child_count
            FROM $sponsorship_table 
            WHERE status = 'confirmed' 
            GROUP BY session_id 
            ORDER BY confirmed_time DESC 
            LIMIT 5
        ");
        
        foreach ($recent_sponsorships as $sponsorship) {
            $activities[] = array(
                'icon' => '🎄',
                'title' => sprintf(__('New Sponsorship: %s', 'cfk-sponsorship'), $sponsorship->sponsor_name),
                'description' => sprintf(
                    _n('Sponsored %d child', 'Sponsored %d children', $sponsorship->child_count, 'cfk-sponsorship'),
                    $sponsorship->child_count
                ),
                'time' => human_time_diff(strtotime($sponsorship->confirmed_time), current_time('timestamp')) . ' ' . __('ago', 'cfk-sponsorship')
            );
        }
        
        // Recent emails
        $email_table = $wpdb->prefix . 'cfk_email_log';
        $recent_emails = $wpdb->get_results("
            SELECT email_type, recipient_email, sent_time, delivery_status
            FROM $email_table 
            ORDER BY sent_time DESC 
            LIMIT 3
        ");
        
        foreach ($recent_emails as $email) {
            $activities[] = array(
                'icon' => $email->delivery_status === 'sent' ? '📧' : '❌',
                'title' => sprintf(__('%s Email Sent', 'cfk-sponsorship'), ucfirst($email->email_type)),
                'description' => sprintf(__('To: %s', 'cfk-sponsorship'), $email->recipient_email),
                'time' => human_time_diff(strtotime($email->sent_time), current_time('timestamp')) . ' ' . __('ago', 'cfk-sponsorship')
            );
        }
        
        // Sort by time
        usort($activities, function($a, $b) {
            return strcmp($b['time'], $a['time']);
        });
        
        return array_slice($activities, 0, 8);
    }
    
    /**
     * Get system status
     */
    private function get_system_status() {
        $status = array();
        
        // Sponsorships status
        $sponsorships_open = ChristmasForKidsPlugin::get_option('cfk_sponsorships_open', false);
        if ($sponsorships_open) {
            $status[] = array(
                'status' => 'good',
                'icon' => '✅',
                'title' => __('Sponsorships Active', 'cfk-sponsorship'),
                'message' => __('The sponsorship system is currently accepting new sponsors.', 'cfk-sponsorship')
            );
        } else {
            $status[] = array(
                'status' => 'warning',
                'icon' => '⚠️',
                'title' => __('Sponsorships Closed', 'cfk-sponsorship'),
                'message' => __('Sponsorships are currently closed to new applicants.', 'cfk-sponsorship'),
                'action' => array(
                    'text' => __('Open Sponsorships', 'cfk-sponsorship'),
                    'url' => '#toggle-sponsorships'
                )
            );
        }
        
        // Email configuration
        $from_email = ChristmasForKidsPlugin::get_option('cfk_email_from_email');
        if (empty($from_email)) {
            $status[] = array(
                'status' => 'error',
                'icon' => '❌',
                'title' => __('Email Not Configured', 'cfk-sponsorship'),
                'message' => __('Email settings need to be configured for proper functionality.', 'cfk-sponsorship'),
                'action' => array(
                    'text' => __('Configure Email', 'cfk-sponsorship'),
                    'url' => admin_url('admin.php?page=cfk-settings#email')
                )
            );
        } else {
            $status[] = array(
                'status' => 'good',
                'icon' => '✅',
                'title' => __('Email Configured', 'cfk-sponsorship'),
                'message' => sprintf(__('Emails will be sent from: %s', 'cfk-sponsorship'), $from_email)
            );
        }
        
        // Database status
        global $wpdb;
        $sponsorship_table = $wpdb->prefix . 'cfk_sponsorships';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$sponsorship_table'") === $sponsorship_table;
        
        if ($table_exists) {
            $status[] = array(
                'status' => 'good',
                'icon' => '✅',
                'title' => __('Database Ready', 'cfk-sponsorship'),
                'message' => __('All required database tables are properly installed.', 'cfk-sponsorship')
            );
        } else {
            $status[] = array(
                'status' => 'error',
                'icon' => '❌',
                'title' => __('Database Issue', 'cfk-sponsorship'),
                'message' => __('Required database tables are missing. Try deactivating and reactivating the plugin.', 'cfk-sponsorship')
            );
        }
        
        // Deadline check
        $deadline = ChristmasForKidsPlugin::get_option('cfk_deadline_date');
        if (empty($deadline) || $deadline === '[DEADLINE DATE]') {
            $status[] = array(
                'status' => 'warning',
                'icon' => '⚠️',
                'title' => __('Deadline Not Set', 'cfk-sponsorship'),
                'message' => __('Gift drop-off deadline should be configured for sponsor emails.', 'cfk-sponsorship'),
                'action' => array(
                    'text' => __('Set Deadline', 'cfk-sponsorship'),
                    'url' => admin_url('admin.php?page=cfk-settings#general')
                )
            );
        }
        
        return $status;
    }
    
    /**
     * AJAX handler for toggling sponsorships
     */
    public function ajax_toggle_sponsorships() {
        check_ajax_referer('cfk_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'cfk-sponsorship'));
        }
        
        $current_status = ChristmasForKidsPlugin::get_option('cfk_sponsorships_open', false);
        $new_status = !$current_status;
        
        ChristmasForKidsPlugin::update_option('cfk_sponsorships_open', $new_status);
        
        wp_send_json_success(array(
            'status' => $new_status,
            'message' => $new_status 
                ? __('Sponsorships are now open', 'cfk-sponsorship')
                : __('Sponsorships are now closed', 'cfk-sponsorship')
        ));
    }
    
    /**
     * AJAX handler for refreshing dashboard stats
     */
    public function ajax_get_dashboard_stats() {
        check_ajax_referer('cfk_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'cfk-sponsorship'));
        }
        
        $stats = $this->get_comprehensive_stats();
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX handler for data export
     */
    public function ajax_export_data() {
        check_ajax_referer('cfk_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'cfk-sponsorship'));
        }
        
        $export_type = sanitize_text_field($_POST['export_type']);
        
        switch ($export_type) {
            case 'sponsorships':
                $this->export_sponsorships();
                break;
            case 'children':
                $this->export_children();
                break;
            case 'emails':
                $this->export_emails();
                break;
            default:
                wp_send_json_error(__('Invalid export type', 'cfk-sponsorship'));
        }
    }
    
    /**
     * Export sponsorships data
     */
    private function export_sponsorships() {
        global $wpdb;
        
        $sponsorship_table = $wpdb->prefix . 'cfk_sponsorships';
        
        $data = $wpdb->get_results("
            SELECT s.session_id, s.sponsor_name, s.sponsor_email, s.sponsor_phone, 
                   s.sponsor_address, s.confirmed_time, s.child_id, s.sponsor_notes
            FROM $sponsorship_table s
            WHERE s.status = 'confirmed'
            ORDER BY s.confirmed_time DESC
        ", ARRAY_A);
        
        $this->output_csv('sponsorships-' . date('Y-m-d'), $data);
    }
    
    /**
     * Export children data
     */
    private function export_children() {
        $children = get_posts(array(
            'post_type' => 'child',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $data = array();
        foreach ($children as $child) {
            $data[] = array(
                'child_id' => get_post_meta($child->ID, '_child_id', true),
                'name' => $child->post_title,
                'age' => get_post_meta($child->ID, '_child_age', true),
                'gender' => get_post_meta($child->ID, '_child_gender', true),
                'family_id' => get_post_meta($child->ID, '_child_family_id', true),
                'age_range' => get_post_meta($child->ID, '_child_age_range', true),
                'clothing_info' => get_post_meta($child->ID, '_child_clothing_info', true),
                'gift_requests' => get_post_meta($child->ID, '_child_gift_requests', true),
                'sponsored' => get_post_meta($child->ID, '_child_sponsored', true) == '1' ? 'Yes' : 'No'
            );
        }
        
        $this->output_csv('children-' . date('Y-m-d'), $data);
    }
    
    /**
     * Export email log
     */
    private function export_emails() {
        global $wpdb;
        
        $email_table = $wpdb->prefix . 'cfk_email_log';
        
        $data = $wpdb->get_results("
            SELECT session_id, email_type, recipient_email, subject, 
                   sent_time, delivery_status
            FROM $email_table
            ORDER BY sent_time DESC
        ", ARRAY_A);
        
        $this->output_csv('email-log-' . date('Y-m-d'), $data);
    }
    
    /**
     * Output CSV file
     */
    private function output_csv($filename, $data) {
        if (empty($data)) {
            wp_send_json_error(__('No data to export', 'cfk-sponsorship'));
            return;
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        $output = fopen('php://output', 'w');
        
        // Output headers
        fputcsv($output, array_keys($data[0]));
        
        // Output data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
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
    
    /**
     * Get dashboard JavaScript
     */
    private function get_dashboard_javascript() {
        $nonce = wp_create_nonce('cfk_admin_nonce');
        
        return "
        jQuery(document).ready(function($) {
            // Initialize charts when Chart.js is loaded
            function initCharts() {
                if (typeof Chart !== 'undefined' && window.cfkChartData) {
                    // Progress Chart (Doughnut)
                    const progressCtx = document.getElementById('cfk-progress-chart');
                    if (progressCtx) {
                        new Chart(progressCtx, {
                            type: 'doughnut',
                            data: {
                                labels: ['" . esc_js(__('Sponsored', 'cfk-sponsorship')) . "', '" . esc_js(__('Available', 'cfk-sponsorship')) . "'],
                                datasets: [{
                                    data: [window.cfkChartData.progress.sponsored, window.cfkChartData.progress.available],
                                    backgroundColor: ['#27ae60', '#e74c3c'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    }
                    
                    // Age Range Chart (Bar)
                    const ageCtx = document.getElementById('cfk-age-chart');
                    if (ageCtx && window.cfkChartData.ageBreakdown) {
                        const ageData = window.cfkChartData.ageBreakdown;
                        const labels = Object.keys(ageData);
                        const sponsored = labels.map(label => ageData[label].sponsored);
                        const available = labels.map(label => ageData[label].available);
                        
                        new Chart(ageCtx, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [
                                    {
                                        label: '" . esc_js(__('Sponsored', 'cfk-sponsorship')) . "',
                                        data: sponsored,
                                        backgroundColor: '#27ae60'
                                    },
                                    {
                                        label: '" . esc_js(__('Available', 'cfk-sponsorship')) . "',
                                        data: available,
                                        backgroundColor: '#e74c3c'
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    x: {
                                        stacked: true
                                    },
                                    y: {
                                        stacked: true,
                                        beginAtZero: true
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    }
                }
            }
            
            // Wait for Chart.js to load, then initialize
            function waitForChart() {
                if (typeof Chart !== 'undefined') {
                    initCharts();
                } else {
                    setTimeout(waitForChart, 100);
                }
            }
            waitForChart();
            
            // Sponsorship toggle
            $('#cfk-toggle-sponsorships').change(function() {
                const isChecked = $(this).is(':checked');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cfk_toggle_sponsorships',
                        nonce: '$nonce'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data);
                            $('#cfk-toggle-sponsorships').prop('checked', !isChecked);
                        }
                    },
                    error: function() {
                        alert('" . esc_js(__('Network error. Please try again.', 'cfk-sponsorship')) . "');
                        $('#cfk-toggle-sponsorships').prop('checked', !isChecked);
                    }
                });
            });
            
            // Export buttons
            $('.cfk-export-btn').click(function() {
                const exportType = $(this).data('type');
                const button = $(this);
                const originalText = button.text();
                
                button.text('" . esc_js(__('Exporting...', 'cfk-sponsorship')) . "').prop('disabled', true);
                
                // Create a form and submit for file download
                const form = $('<form>', {
                    method: 'POST',
                    action: ajaxurl
                });
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'action',
                    value: 'cfk_export_data'
                }));
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'export_type',
                    value: exportType
                }));
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'nonce',
                    value: '$nonce'
                }));
                
                $('body').append(form);
                form.submit();
                form.remove();
                
                // Reset button after delay
                setTimeout(function() {
                    button.text(originalText).prop('disabled', false);
                }, 2000);
            });
            
            // Cleanup button
            $('.cfk-cleanup-btn').click(function() {
                if (confirm('" . esc_js(__('Are you sure you want to clean up abandoned selections?', 'cfk-sponsorship')) . "')) {
                    const button = $(this);
                    const originalText = button.text();
                    
                    button.text('" . esc_js(__('Cleaning...', 'cfk-sponsorship')) . "').prop('disabled', true);
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'cfk_cleanup_abandoned',
                            nonce: '$nonce'
                        },
                        success: function(response) {
                            button.text(originalText).prop('disabled', false);
                            if (response.success) {
                                alert('" . esc_js(__('Cleanup completed successfully.', 'cfk-sponsorship')) . "');
                                location.reload();
                            } else {
                                alert(response.data);
                            }
                        },
                        error: function() {
                            button.text(originalText).prop('disabled', false);
                            alert('" . esc_js(__('Network error. Please try again.', 'cfk-sponsorship')) . "');
                        }
                    });
                }
            });
            
            // Refresh stats button
            $('.cfk-refresh-stats-btn').click(function() {
                const button = $(this);
                const originalText = button.text();
                
                button.text('" . esc_js(__('Refreshing...', 'cfk-sponsorship')) . "').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cfk_dashboard_stats',
                        nonce: '$nonce'
                    },
                    success: function(response) {
                        button.text(originalText).prop('disabled', false);
                        if (response.success) {
                            alert('" . esc_js(__('Statistics refreshed successfully.', 'cfk-sponsorship')) . "');
                            location.reload();
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function() {
                        button.text(originalText).prop('disabled', false);
                        alert('" . esc_js(__('Network error. Please try again.', 'cfk-sponsorship')) . "');
                    }
                });
            });
        });
        ";
    }
}

?>