<?php
/**
 * Email Manager Class
 * Handles all email functionality for the sponsorship system
 *
 * @package ChristmasForKids
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CFK_Email_Manager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_cfk_send_test_email', array($this, 'send_test_email'));
        add_filter('wp_mail_from', array($this, 'custom_wp_mail_from'));
        add_filter('wp_mail_from_name', array($this, 'custom_wp_mail_from_name'));
    }
    
    /**
     * Add admin menu for email management
     */
    public function add_admin_menu() {
        add_submenu_page(
            'cfk-dashboard',
            __('Email Management', 'cfk-sponsorship'),
            __('Emails', 'cfk-sponsorship'),
            'manage_options',
            'cfk-emails',
            array($this, 'emails_admin_page')
        );
    }
    
    /**
     * Custom from email address
     */
    public function custom_wp_mail_from($email) {
        $custom_email = ChristmasForKidsPlugin::get_option('cfk_email_from_email');
        return !empty($custom_email) ? $custom_email : $email;
    }
    
    /**
     * Custom from name
     */
    public function custom_wp_mail_from_name($name) {
        $custom_name = ChristmasForKidsPlugin::get_option('cfk_email_from_name');
        return !empty($custom_name) ? $custom_name : $name;
    }
    
    /**
     * Send sponsor confirmation email
     */
    public function send_sponsor_confirmation($session_id, $sponsor_data, $children) {
        // Get family context for children
        $family_context = $this->get_family_context_for_children($children);
        
        $subject = sprintf(__('Thank You for Your %s Sponsorship!', 'cfk-sponsorship'), 
                          ChristmasForKidsPlugin::get_option('cfk_email_from_name', 'Christmas for Kids'));
        
        // Add family context to subject if sponsoring complete families
        if (!empty($family_context['complete_families'])) {
            $family_names = array_column($family_context['complete_families'], 'family_name');
            $subject .= ' - ' . implode(', ', $family_names);
        }
        
        $message = $this->get_sponsor_email_template($sponsor_data, $children, $family_context);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8'
        );
        
        $sent = wp_mail($sponsor_data['email'], $subject, $message, $headers);
        
        // Log the email
        $this->log_email_sent($session_id, 'sponsor', $sponsor_data['email'], $subject, $message, $sent);
        
        return $sent;
    }
    
    /**
     * Send admin notification email
     */
    public function send_admin_notification($session_id, $sponsor_data, $children) {
        $admin_email = ChristmasForKidsPlugin::get_option('cfk_admin_email', get_option('admin_email'));
        
        $subject = sprintf(__('New Sponsorship Confirmed - %s', 'cfk-sponsorship'), $sponsor_data['name']);
        
        $message = $this->get_admin_email_template($sponsor_data, $children);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $sponsor_data['name'] . ' <' . $sponsor_data['email'] . '>'
        );
        
        $sent = wp_mail($admin_email, $subject, $message, $headers);
        
        // Log the email
        $this->log_email_sent($session_id, 'admin', $admin_email, $subject, $message, $sent);
        
        return $sent;
    }
    
    /**
     * Get sponsor email template
     */
    private function get_sponsor_email_template($sponsor_data, $children) {
        $organization_name = ChristmasForKidsPlugin::get_option('cfk_email_from_name', 'Christmas for Kids');
        $deadline_date = ChristmasForKidsPlugin::get_option('cfk_deadline_date', '[DEADLINE DATE]');
        $drop_off_locations = ChristmasForKidsPlugin::get_option('cfk_drop_off_locations', array());
        
        if (is_string($drop_off_locations)) {
            $drop_off_locations = explode("\n", $drop_off_locations);
        }
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($organization_name); ?> - Sponsorship Confirmation</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #c41e3a, #8b1538);
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                }
                .header p {
                    margin: 10px 0 0 0;
                    font-size: 16px;
                    opacity: 0.9;
                }
                .content { 
                    padding: 30px; 
                }
                .child-card { 
                    border: 1px solid #e1e5e9; 
                    margin: 20px 0; 
                    padding: 20px; 
                    border-radius: 8px;
                    background: #f8f9fa;
                }
                .child-header { 
                    font-weight: bold; 
                    color: #c41e3a; 
                    margin-bottom: 15px; 
                    font-size: 18px;
                    border-bottom: 2px solid #c41e3a;
                    padding-bottom: 8px;
                }
                .child-detail {
                    margin: 8px 0;
                }
                .child-detail strong {
                    color: #2c3e50;
                }
                .gift-section { 
                    margin-top: 15px; 
                    padding: 15px;
                    background: white;
                    border-radius: 4px;
                    border-left: 4px solid #c41e3a;
                }
                .section-title {
                    color: #c41e3a;
                    font-size: 20px;
                    margin: 25px 0 15px 0;
                    border-bottom: 2px solid #c41e3a;
                    padding-bottom: 8px;
                }
                .next-steps {
                    background: #e8f4fd;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                }
                .next-steps ul {
                    margin: 10px 0;
                    padding-left: 20px;
                }
                .next-steps li {
                    margin: 8px 0;
                }
                .locations {
                    background: #f0f6fc;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                }
                .locations ul {
                    margin: 10px 0;
                    padding-left: 20px;
                }
                .locations li {
                    margin: 8px 0;
                    font-weight: 500;
                }
                .footer { 
                    background: #2c3e50; 
                    color: white;
                    padding: 20px; 
                    text-align: center; 
                    font-size: 12px;
                }
                .footer p {
                    margin: 5px 0;
                }
                .highlight {
                    background: #fff3cd;
                    padding: 15px;
                    border-radius: 4px;
                    border: 1px solid #ffeaa7;
                    margin: 15px 0;
                }
                @media (max-width: 600px) {
                    .email-container {
                        margin: 10px;
                        border-radius: 0;
                    }
                    .content {
                        padding: 20px;
                    }
                    .header {
                        padding: 20px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <h1><?php echo esc_html($organization_name); ?></h1>
                    <p><?php _e('Thank you for your generous sponsorship!', 'cfk-sponsorship'); ?></p>
                </div>
                
                <div class="content">
                    <p><?php printf(__('Dear %s,', 'cfk-sponsorship'), esc_html($sponsor_data['name'])); ?></p>
                    
                    <p><?php _e('Thank you so much for your generous heart and willingness to sponsor children this Christmas! Your kindness will bring joy and hope to families in our community.', 'cfk-sponsorship'); ?></p>
                    
                    <h3 class="section-title"><?php _e('Children You\'re Sponsoring:', 'cfk-sponsorship'); ?></h3>
                    
                    <?php foreach ($children as $child): ?>
                    <div class="child-card">
                        <div class="child-header">
                            <?php echo esc_html($child['name']); ?> - <?php printf(__('Family %s', 'cfk-sponsorship'), esc_html($child['family_id'])); ?>
                        </div>
                        
                        <div class="child-detail">
                            <strong><?php _e('Age:', 'cfk-sponsorship'); ?></strong> 
                            <?php printf(__('%s years old (%s)', 'cfk-sponsorship'), esc_html($child['age']), esc_html($child['age_range'])); ?>
                        </div>
                        
                        <div class="child-detail">
                            <strong><?php _e('Gender:', 'cfk-sponsorship'); ?></strong> 
                            <?php echo esc_html($child['gender']); ?>
                        </div>
                        
                        <?php if (!empty($child['clothing_info'])): ?>
                        <div class="child-detail">
                            <strong><?php _e('Clothing Sizes:', 'cfk-sponsorship'); ?></strong> 
                            <?php echo esc_html($child['clothing_info']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($child['gift_requests'])): ?>
                        <div class="gift-section">
                            <strong><?php _e('Gift Requests:', 'cfk-sponsorship'); ?></strong>
                            <p><?php echo esc_html($child['gift_requests']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="highlight">
                        <strong><?php _e('Important:', 'cfk-sponsorship'); ?></strong>
                        <?php printf(__('Please include a gift tag with the child\'s ID (%s) on each gift for proper distribution.', 'cfk-sponsorship'), 
                                    esc_html(implode(', ', array_column($children, 'id')))); ?>
                    </div>
                    
                    <div class="next-steps">
                        <h3 class="section-title"><?php _e('Next Steps:', 'cfk-sponsorship'); ?></h3>
                        <ul>
                            <li><?php _e('Shop for gifts based on the wish lists above', 'cfk-sponsorship'); ?></li>
                            <li><?php _e('Wrap gifts and include a gift tag with the child\'s ID', 'cfk-sponsorship'); ?></li>
                            <li><?php printf(__('Drop off gifts at one of our collection locations by %s', 'cfk-sponsorship'), esc_html($deadline_date)); ?></li>
                        </ul>
                    </div>
                    
                    <?php if (!empty($drop_off_locations)): ?>
                    <div class="locations">
                        <h3 class="section-title"><?php _e('Drop-Off Locations:', 'cfk-sponsorship'); ?></h3>
                        <ul>
                            <?php foreach ($drop_off_locations as $location): ?>
                            <li><?php echo esc_html(trim($location)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <p><?php _e('If you have any questions or need to make changes to your sponsorship, please don\'t hesitate to contact us.', 'cfk-sponsorship'); ?></p>
                    
                    <p><?php _e('Thank you again for making Christmas magical for these children!', 'cfk-sponsorship'); ?></p>
                    
                    <p><?php _e('With gratitude,', 'cfk-sponsorship'); ?><br>
                    <?php printf(__('The %s Team', 'cfk-sponsorship'), esc_html($organization_name)); ?></p>
                </div>
                
                <div class="footer">
                    <p><?php echo esc_html($organization_name); ?> | <?php _e('Spreading joy, one family at a time', 'cfk-sponsorship'); ?></p>
                    <p><?php _e('This email was sent to confirm your sponsorship. Please keep this information for your records.', 'cfk-sponsorship'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get admin email template
     */
    private function get_admin_email_template($sponsor_data, $children) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #c41e3a; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .sponsor-info { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; }
                .children-list { background: #e8f4fd; padding: 15px; border-radius: 4px; margin: 15px 0; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                td { padding: 8px; border-bottom: 1px solid #ddd; }
                .label { font-weight: bold; width: 150px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php _e('New Sponsorship Confirmed', 'cfk-sponsorship'); ?></h1>
            </div>
            
            <div class="content">
                <div class="sponsor-info">
                    <h3><?php _e('Sponsor Information:', 'cfk-sponsorship'); ?></h3>
                    <table>
                        <tr>
                            <td class="label"><?php _e('Name:', 'cfk-sponsorship'); ?></td>
                            <td><?php echo esc_html($sponsor_data['name']); ?></td>
                        </tr>
                        <tr>
                            <td class="label"><?php _e('Email:', 'cfk-sponsorship'); ?></td>
                            <td><a href="mailto:<?php echo esc_attr($sponsor_data['email']); ?>"><?php echo esc_html($sponsor_data['email']); ?></a></td>
                        </tr>
                        <?php if (!empty($sponsor_data['phone'])): ?>
                        <tr>
                            <td class="label"><?php _e('Phone:', 'cfk-sponsorship'); ?></td>
                            <td><?php echo esc_html($sponsor_data['phone']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($sponsor_data['address'])): ?>
                        <tr>
                            <td class="label"><?php _e('Address:', 'cfk-sponsorship'); ?></td>
                            <td><?php echo nl2br(esc_html($sponsor_data['address'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($sponsor_data['notes'])): ?>
                        <tr>
                            <td class="label"><?php _e('Notes:', 'cfk-sponsorship'); ?></td>
                            <td><?php echo nl2br(esc_html($sponsor_data['notes'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                
                <div class="children-list">
                    <h3><?php _e('Sponsored Children:', 'cfk-sponsorship'); ?></h3>
                    <ul>
                    <?php foreach ($children as $child): ?>
                        <li>
                            <strong><?php echo esc_html($child['id']); ?>:</strong> 
                            <?php echo esc_html($child['name']); ?> 
                            (<?php printf(__('Family %s', 'cfk-sponsorship'), esc_html($child['family_id'])); ?>)
                            - <?php echo esc_html($child['age']); ?> <?php _e('years old', 'cfk-sponsorship'); ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                    
                    <p><strong><?php _e('Total Children Sponsored:', 'cfk-sponsorship'); ?></strong> <?php echo count($children); ?></p>
                </div>
                
                <p><strong><?php _e('Sponsorship confirmed at:', 'cfk-sponsorship'); ?></strong> <?php echo current_time('F j, Y g:i a'); ?></p>
                
                <p><strong><?php _e('Admin Actions:', 'cfk-sponsorship'); ?></strong></p>
                <ul>
                    <li><a href="<?php echo admin_url('admin.php?page=cfk-sponsorships'); ?>"><?php _e('View All Sponsorships', 'cfk-sponsorship'); ?></a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=child'); ?>"><?php _e('Manage Children', 'cfk-sponsorship'); ?></a></li>
                </ul>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Log email for tracking and resend capability
     */
    private function log_email_sent($session_id, $type, $email, $subject, $message, $success = true) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_email_log';
        
        $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'email_type' => $type,
                'recipient_email' => $email,
                'subject' => $subject,
                'message' => $message,
                'sent_time' => current_time('mysql'),
                'delivery_status' => $success ? 'sent' : 'failed'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Emails admin page
     */
    public function emails_admin_page() {
        global $wpdb;
        
        $email_log_table = $wpdb->prefix . 'cfk_email_log';
        
        // Handle test email
        if (isset($_GET['test_sent'])) {
            echo '<div class="notice notice-success"><p>' . __('Test email sent successfully!', 'cfk-sponsorship') . '</p></div>';
        }
        
        // Get recent emails with pagination
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $recent_emails = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $email_log_table 
            ORDER BY sent_time DESC 
            LIMIT %d OFFSET %d
        ", $per_page, $offset));
        
        $total_emails = $wpdb->get_var("SELECT COUNT(*) FROM $email_log_table");
        $total_pages = ceil($total_emails / $per_page);
        
        // Get email statistics
        $email_stats = $this->get_email_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Email Management', 'cfk-sponsorship'); ?></h1>
            
            <div class="cfk-stats-grid">
                <div class="cfk-stat-card">
                    <div class="cfk-stat-number"><?php echo $email_stats['total_sent']; ?></div>
                    <div class="cfk-stat-label"><?php _e('Total Emails Sent', 'cfk-sponsorship'); ?></div>
                </div>
                <div class="cfk-stat-card">
                    <div class="cfk-stat-number"><?php echo $email_stats['sponsor_emails']; ?></div>
                    <div class="cfk-stat-label"><?php _e('Sponsor Confirmations', 'cfk-sponsorship'); ?></div>
                </div>
                <div class="cfk-stat-card">
                    <div class="cfk-stat-number"><?php echo $email_stats['admin_emails']; ?></div>
                    <div class="cfk-stat-label"><?php _e('Admin Notifications', 'cfk-sponsorship'); ?></div>
                </div>
                <div class="cfk-stat-card">
                    <div class="cfk-stat-number"><?php echo $email_stats['failed_emails']; ?></div>
                    <div class="cfk-stat-label"><?php _e('Failed Deliveries', 'cfk-sponsorship'); ?></div>
                </div>
            </div>
            
            <div class="cfk-email-actions">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;">
                    <?php wp_nonce_field('cfk_send_test_email', 'test_email_nonce'); ?>
                    <input type="hidden" name="action" value="cfk_send_test_email">
                    <input type="email" name="test_email" placeholder="<?php _e('Enter email address', 'cfk-sponsorship'); ?>" required style="margin-right: 10px;">
                    <input type="submit" class="button button-secondary" value="<?php _e('Send Test Email', 'cfk-sponsorship'); ?>">
                </form>
            </div>
            
            <h2><?php _e('Recent Emails', 'cfk-sponsorship'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Type', 'cfk-sponsorship'); ?></th>
                        <th><?php _e('Recipient', 'cfk-sponsorship'); ?></th>
                        <th><?php _e('Subject', 'cfk-sponsorship'); ?></th>
                        <th><?php _e('Status', 'cfk-sponsorship'); ?></th>
                        <th><?php _e('Sent Time', 'cfk-sponsorship'); ?></th>
                        <th><?php _e('Actions', 'cfk-sponsorship'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_emails as $email): ?>
                    <tr>
                        <td>
                            <span class="cfk-email-type cfk-email-type-<?php echo esc_attr($email->email_type); ?>">
                                <?php echo esc_html(ucfirst($email->email_type)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($email->recipient_email); ?></td>
                        <td><?php echo esc_html($email->subject); ?></td>
                        <td>
                            <?php if ($email->delivery_status === 'sent'): ?>
                                <span class="cfk-status-success">✓ <?php _e('Sent', 'cfk-sponsorship'); ?></span>
                            <?php else: ?>
                                <span class="cfk-status-failed">✗ <?php _e('Failed', 'cfk-sponsorship'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(mysql2date('M j, Y g:i a', $email->sent_time)); ?></td>
                        <td>
                            <button class="button button-small cfk-preview-email" 
                                    data-email-id="<?php echo esc_attr($email->id); ?>"
                                    data-subject="<?php echo esc_attr($email->subject); ?>"
                                    data-message="<?php echo esc_attr($email->message); ?>">
                                <?php _e('Preview', 'cfk-sponsorship'); ?>
                            </button>
                            <?php if ($email->delivery_status === 'failed'): ?>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=cfk_resend_email&email_id=' . $email->id), 'cfk_resend_email'); ?>" 
                               class="button button-small"><?php _e('Retry', 'cfk-sponsorship'); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($recent_emails)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <?php _e('No emails found.', 'cfk-sponsorship'); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'current' => $page,
                        'total' => $total_pages
                    ));
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Email Preview Modal -->
        <div id="cfk-email-modal" style="display: none;">
            <div class="cfk-modal-overlay">
                <div class="cfk-modal-content">
                    <div class="cfk-modal-header">
                        <h3 id="cfk-modal-subject"></h3>
                        <button class="cfk-modal-close">&times;</button>
                    </div>
                    <div class="cfk-modal-body">
                        <iframe id="cfk-email-preview" width="100%" height="500" frameborder="0"></iframe>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .cfk-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .cfk-stat-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        .cfk-stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
            line-height: 1;
        }
        .cfk-stat-label {
            margin-top: 8px;
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .cfk-email-actions {
            margin: 20px 0;
            padding: 15px;
            background: #f0f6fc;
            border-radius: 4px;
        }
        .cfk-email-type {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .cfk-email-type-sponsor {
            background: #d1ecf1;
            color: #0c5460;
        }
        .cfk-email-type-admin {
            background: #f8d7da;
            color: #721c24;
        }
        .cfk-status-success {
            color: #46b450;
            font-weight: bold;
        }
        .cfk-status-failed {
            color: #dc3232;
            font-weight: bold;
        }
        .cfk-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cfk-modal-content {
            background: white;
            border-radius: 8px;
            max-width: 90%;
            max-height: 90%;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .cfk-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cfk-modal-header h3 {
            margin: 0;
        }
        .cfk-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cfk-modal-body {
            padding: 0;
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const previewButtons = document.querySelectorAll('.cfk-preview-email');
            const modal = document.getElementById('cfk-email-modal');
            const modalSubject = document.getElementById('cfk-modal-subject');
            const modalPreview = document.getElementById('cfk-email-preview');
            const closeButton = document.querySelector('.cfk-modal-close');
            
            previewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const subject = this.getAttribute('data-subject');
                    const message = this.getAttribute('data-message');
                    
                    modalSubject.textContent = subject;
                    modalPreview.srcdoc = message;
                    modal.style.display = 'block';
                });
            });
            
            closeButton.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            modal.addEventListener('click', function(e) {
                if (e.target === modal.querySelector('.cfk-modal-overlay')) {
                    modal.style.display = 'none';
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get email statistics
     */
    private function get_email_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_email_log';
        
        $total_sent = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE delivery_status = 'sent'");
        $sponsor_emails = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE email_type = 'sponsor' AND delivery_status = 'sent'");
        $admin_emails = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE email_type = 'admin' AND delivery_status = 'sent'");
        $failed_emails = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE delivery_status = 'failed'");
        
        return array(
            'total_sent' => intval($total_sent),
            'sponsor_emails' => intval($sponsor_emails),
            'admin_emails' => intval($admin_emails),
            'failed_emails' => intval($failed_emails)
        );
    }
    
    /**
     * Send test email
     */
    public function send_test_email() {
        if (!wp_verify_nonce($_POST['test_email_nonce'], 'cfk_send_test_email') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'cfk-sponsorship'));
        }
        
        $test_email = sanitize_email($_POST['test_email']);
        
        if (!is_email($test_email)) {
            wp_die(__('Invalid email address', 'cfk-sponsorship'));
        }
        
        // Create sample data for test email
        $sample_sponsor_data = array(
            'name' => 'Test Sponsor',
            'email' => $test_email,
            'phone' => '(555) 123-4567',
            'address' => '123 Test Street\nTest City, TS 12345',
            'notes' => 'This is a test email from the Christmas for Kids system.'
        );
        
        $sample_children = array(
            array(
                'id' => '001A',
                'name' => '001A: Female 8',
                'age' => '8',
                'gender' => 'Female',
                'family_id' => '001',
                'clothing_info' => 'Pants: Girls 8. Shirt: Girls 8. Shoes: Youth 3.',
                'gift_requests' => 'Art supplies, Books, Dolls, Hair accessories',
                'age_range' => 'Elementary'
            ),
            array(
                'id' => '001B',
                'name' => '001B: Male 6',
                'age' => '6',
                'gender' => 'Male',
                'family_id' => '001',
                'clothing_info' => 'Pants: Boys 6. Shirt: Boys 6. Shoes: Youth 1.',
                'gift_requests' => 'Lego sets, Action figures, Books, Toy cars',
                'age_range' => 'Elementary'
            )
        );
        
        $subject = sprintf(__('[TEST] Thank You for Your %s Sponsorship!', 'cfk-sponsorship'), 
                          ChristmasForKidsPlugin::get_option('cfk_email_from_name', 'Christmas for Kids'));
        
        $message = $this->get_sponsor_email_template($sample_sponsor_data, $sample_children);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8'
        );
        
        $sent = wp_mail($test_email, $subject, $message, $headers);
        
        if ($sent) {
            wp_redirect(add_query_arg('test_sent', '1', wp_get_referer()));
        } else {
            wp_die(__('Failed to send test email. Please check your email configuration.', 'cfk-sponsorship'));
        }
        exit;
    }
    
    /**
     * Send reminder email to sponsors
     */
    public function send_reminder_email($session_id, $reminder_type = 'deadline') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        // Get sponsorship data
        $sponsorship_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s AND status = 'confirmed' LIMIT 1",
            $session_id
        ));
        
        if (!$sponsorship_data) {
            return false;
        }
        
        $sponsor_data = array(
            'name' => $sponsorship_data->sponsor_name,
            'email' => $sponsorship_data->sponsor_email,
            'phone' => $sponsorship_data->sponsor_phone,
            'address' => $sponsorship_data->sponsor_address,
            'notes' => $sponsorship_data->sponsor_notes
        );
        
        $children = $this->get_confirmed_children_for_session($session_id);
        
        $subject = '';
        $template_type = '';
        
        switch ($reminder_type) {
            case 'deadline':
                $subject = sprintf(__('Reminder: %s Gift Deadline Approaching', 'cfk-sponsorship'), 
                                 ChristmasForKidsPlugin::get_option('cfk_email_from_name', 'Christmas for Kids'));
                $template_type = 'deadline_reminder';
                break;
            case 'thank_you':
                $subject = sprintf(__('Thank You from %s', 'cfk-sponsorship'), 
                                 ChristmasForKidsPlugin::get_option('cfk_email_from_name', 'Christmas for Kids'));
                $template_type = 'thank_you';
                break;
        }
        
        if (empty($subject)) {
            return false;
        }
        
        $message = $this->get_reminder_email_template($sponsor_data, $children, $template_type);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8'
        );
        
        $sent = wp_mail($sponsor_data['email'], $subject, $message, $headers);
        
        // Log the email
        $this->log_email_sent($session_id, 'reminder', $sponsor_data['email'], $subject, $message, $sent);
        
        return $sent;
    }
    
    /**
     * Get reminder email template
     */
    private function get_reminder_email_template($sponsor_data, $children, $template_type) {
        $organization_name = ChristmasForKidsPlugin::get_option('cfk_email_from_name', 'Christmas for Kids');
        $deadline_date = ChristmasForKidsPlugin::get_option('cfk_deadline_date', '[DEADLINE DATE]');
        
        ob_start();
        
        if ($template_type === 'deadline_reminder') {
            ?>
            <p><?php printf(__('Dear %s,', 'cfk-sponsorship'), esc_html($sponsor_data['name'])); ?></p>
            
            <p><?php printf(__('This is a friendly reminder that the gift drop-off deadline for %s is approaching on %s.', 'cfk-sponsorship'), 
                           esc_html($organization_name), esc_html($deadline_date)); ?></p>
            
            <p><?php _e('Don\'t forget to include gift tags with the child IDs on your packages:', 'cfk-sponsorship'); ?></p>
            <ul>
            <?php foreach ($children as $child): ?>
                <li><strong><?php echo esc_html($child['id']); ?>:</strong> <?php echo esc_html($child['name']); ?></li>
            <?php endforeach; ?>
            </ul>
            
            <p><?php _e('Thank you again for your generosity!', 'cfk-sponsorship'); ?></p>
            <?php
        } elseif ($template_type === 'thank_you') {
            ?>
            <p><?php printf(__('Dear %s,', 'cfk-sponsorship'), esc_html($sponsor_data['name'])); ?></p>
            
            <p><?php printf(__('On behalf of everyone at %s, thank you so much for your generous sponsorship this year. Your kindness has made Christmas magical for the children you sponsored.', 'cfk-sponsorship'), 
                           esc_html($organization_name)); ?></p>
            
            <p><?php _e('We hope you\'ll consider sponsoring families again next year. Together, we can continue spreading joy throughout our community.', 'cfk-sponsorship'); ?></p>
            
            <p><?php _e('With heartfelt gratitude,', 'cfk-sponsorship'); ?><br>
            <?php printf(__('The %s Team', 'cfk-sponsorship'), esc_html($organization_name)); ?></p>
            <?php
        }
        
        return ob_get_clean();
    }
    
    /**
     * Get confirmed children for a session (helper method)
     */
    private function get_confirmed_children_for_session($session_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        $child_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT child_id FROM $table_name WHERE session_id = %s AND status = 'confirmed'",
            $session_id
        ));
        
        $children = array();
        foreach ($child_ids as $child_id) {
            $child_post = CFK_Children_Manager::get_child_by_id($child_id);
            if ($child_post) {
                $children[] = array(
                    'id' => $child_id,
                    'name' => $child_post->post_title,
                    'age' => get_post_meta($child_post->ID, '_child_age', true),
                    'gender' => get_post_meta($child_post->ID, '_child_gender', true),
                    'family_id' => get_post_meta($child_post->ID, '_child_family_id', true)
                );
            }
        }
        
        return $children;
    }
    
    /**
     * Send bulk reminder emails
     */
    public function send_bulk_reminders($reminder_type = 'deadline') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        // Get all confirmed sponsorships
        $sessions = $wpdb->get_col(
            "SELECT DISTINCT session_id FROM $table_name WHERE status = 'confirmed'"
        );
        
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($sessions as $session_id) {
            if ($this->send_reminder_email($session_id, $reminder_type)) {
                $sent_count++;
            } else {
                $failed_count++;
            }
            
            // Small delay to avoid overwhelming the mail server
            usleep(100000); // 0.1 second delay
        }
        
        return array(
            'sent' => $sent_count,
            'failed' => $failed_count,
            'total' => count($sessions)
        );
    }
}
?>