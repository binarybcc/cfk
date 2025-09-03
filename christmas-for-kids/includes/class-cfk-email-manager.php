<?php
declare(strict_types=1);

/**
 * Email Management System for Christmas for Kids
 * 
 * Handles all automated email communications with sponsors and administrators,
 * including family-aware templates, delivery tracking, and comprehensive
 * email logging for audit purposes.
 * 
 * @package ChristmasForKids
 * @since 1.2.0
 */
class CFK_Email_Manager {
    
    /**
     * Email types for template selection
     * 
     * @since 1.2.0
     * @var array<string, string>
     */
    private const EMAIL_TYPES = [
        'selection_confirmation' => 'Selection Confirmation',
        'sponsor_confirmation' => 'Sponsorship Confirmation',
        'admin_notification' => 'Admin Notification',
        'cancellation' => 'Cancellation Notice',
        'family_update' => 'Family Update',
        'sibling_notification' => 'Sibling Sponsorship Notice'
    ];
    
    /**
     * Child manager instance
     * 
     * @since 1.2.0
     * @var CFK_Child_Manager
     */
    private CFK_Child_Manager $child_manager;
    
    /**
     * Constructor
     * 
     * @since 1.2.0
     * @param CFK_Child_Manager $child_manager Child manager instance
     */
    public function __construct(CFK_Child_Manager $child_manager) {
        $this->child_manager = $child_manager;
    }
    
    /**
     * Initialize email manager
     * 
     * @since 1.2.0
     * @return void
     */
    public function init(): void {
        // Hook into sponsorship events
        add_action('cfk_sponsorship_selected', [$this, 'send_selection_confirmation'], 10, 2);
        add_action('cfk_sponsorship_confirmed', [$this, 'send_confirmation_emails'], 10, 2);
        add_action('cfk_sponsorship_cancelled', [$this, 'send_cancellation_notification'], 10, 2);
        
        // Schedule email queue processing
        add_action('cfk_process_email_queue', [$this, 'process_email_queue']);
        
        if (!wp_next_scheduled('cfk_process_email_queue')) {
            wp_schedule_event(time(), 'every_five_minutes', 'cfk_process_email_queue');
        }
        
        // Add custom cron interval
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);
    }
    
    /**
     * Send selection confirmation email to sponsor
     * 
     * @since 1.2.0
     * @param int $sponsorship_id Sponsorship ID
     * @param object $sponsorship_data Sponsorship data
     * @return void
     */
    public function send_selection_confirmation(int $sponsorship_id, object $sponsorship_data): void {
        try {
            $child_data = $this->get_child_data($sponsorship_data->child_id);
            $template_data = [
                'sponsor_name' => $sponsorship_data->sponsor_name,
                'child_name' => $child_data['name'],
                'child_age' => $child_data['age'],
                'family_info' => $this->get_family_email_data($sponsorship_data->family_number),
                'selection_expires' => $sponsorship_data->expires_at,
                'confirmation_url' => $this->get_confirmation_url($sponsorship_data->selection_token),
                'cancellation_url' => $this->get_cancellation_url($sponsorship_data->selection_token)
            ];
            
            $this->queue_email(
                $sponsorship_data->sponsor_email,
                $sponsorship_data->sponsor_name,
                'selection_confirmation',
                $sponsorship_data->child_id,
                $sponsorship_data->family_id,
                $sponsorship_id,
                $template_data
            );
            
        } catch (Exception $e) {
            error_log('CFK Email selection confirmation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Send confirmation emails to both sponsor and admin
     * 
     * @since 1.2.0
     * @param int $sponsorship_id Sponsorship ID
     * @param object $sponsorship_data Sponsorship data
     * @return void
     */
    public function send_confirmation_emails(int $sponsorship_id, object $sponsorship_data): void {
        try {
            $child_data = $this->get_child_data($sponsorship_data->child_id);
            $family_data = $this->get_family_email_data($sponsorship_data->family_number);
            
            // Sponsor confirmation email
            $sponsor_template_data = [
                'sponsor_name' => $sponsorship_data->sponsor_name,
                'child_name' => $child_data['name'],
                'child_age' => $child_data['age'],
                'family_info' => $family_data,
                'sponsorship_type' => $sponsorship_data->sponsorship_type,
                'organization_contact' => get_option('cfk_admin_email', get_option('admin_email'))
            ];
            
            $this->queue_email(
                $sponsorship_data->sponsor_email,
                $sponsorship_data->sponsor_name,
                'sponsor_confirmation',
                $sponsorship_data->child_id,
                $sponsorship_data->family_id,
                $sponsorship_id,
                $sponsor_template_data
            );
            
            // Admin notification email
            $admin_template_data = [
                'sponsor_name' => $sponsorship_data->sponsor_name,
                'sponsor_email' => $sponsorship_data->sponsor_email,
                'sponsor_phone' => $sponsorship_data->sponsor_phone,
                'child_name' => $child_data['name'],
                'family_info' => $family_data,
                'sponsorship_type' => $sponsorship_data->sponsorship_type,
                'confirmed_at' => $sponsorship_data->confirmed_at
            ];
            
            $admin_email = get_option('cfk_admin_email', get_option('admin_email'));
            $this->queue_email(
                $admin_email,
                'Christmas for Kids Admin',
                'admin_notification',
                $sponsorship_data->child_id,
                $sponsorship_data->family_id,
                $sponsorship_id,
                $admin_template_data
            );
            
            // Send sibling notification if applicable
            if ($sponsorship_data->sponsorship_type !== 'individual' && !empty($sponsorship_data->family_number)) {
                $this->send_family_update_notifications($sponsorship_data);
            }
            
        } catch (Exception $e) {
            error_log('CFK Email confirmation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Send family update notifications
     * 
     * @since 1.2.0
     * @param object $sponsorship_data Sponsorship data
     * @return void
     */
    private function send_family_update_notifications(object $sponsorship_data): void {
        $family_stats = $this->child_manager->get_family_stats($sponsorship_data->family_number);
        
        if ($family_stats['completion_percentage'] >= 100) {
            // Family is now fully sponsored - send special notification
            $this->send_family_completion_notification($sponsorship_data, $family_stats);
        }
    }
    
    /**
     * Send family completion notification
     * 
     * @since 1.2.0
     * @param object $sponsorship_data Sponsorship data
     * @param array<string, mixed> $family_stats Family statistics
     * @return void
     */
    private function send_family_completion_notification(object $sponsorship_data, array $family_stats): void {
        $template_data = [
            'sponsor_name' => $sponsorship_data->sponsor_name,
            'family_number' => $sponsorship_data->family_number,
            'total_children' => $family_stats['total_children'],
            'family_children' => $this->format_family_children_list($family_stats['children'])
        ];
        
        $this->queue_email(
            $sponsorship_data->sponsor_email,
            $sponsorship_data->sponsor_name,
            'family_update',
            $sponsorship_data->child_id,
            $sponsorship_data->family_id,
            null,
            $template_data
        );
    }
    
    /**
     * Send cancellation notification
     * 
     * @since 1.2.0
     * @param int $sponsorship_id Sponsorship ID
     * @param object $sponsorship_data Sponsorship data
     * @return void
     */
    public function send_cancellation_notification(int $sponsorship_id, object $sponsorship_data): void {
        try {
            $child_data = $this->get_child_data($sponsorship_data->child_id);
            $template_data = [
                'sponsor_name' => $sponsorship_data->sponsor_name,
                'child_name' => $child_data['name'],
                'cancelled_at' => current_time('mysql'),
                'browse_url' => home_url('/sponsor-a-child/')
            ];
            
            $this->queue_email(
                $sponsorship_data->sponsor_email,
                $sponsorship_data->sponsor_name,
                'cancellation',
                $sponsorship_data->child_id,
                $sponsorship_data->family_id,
                $sponsorship_id,
                $template_data
            );
            
        } catch (Exception $e) {
            error_log('CFK Email cancellation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Queue email for delivery
     * 
     * @since 1.2.0
     * @param string $recipient_email Recipient email address
     * @param string $recipient_name Recipient name
     * @param string $email_type Email type from EMAIL_TYPES
     * @param int $child_id Child ID
     * @param string|null $family_id Family ID
     * @param int|null $sponsorship_id Sponsorship ID
     * @param array<string, mixed> $template_data Template data
     * @return int|false Email log ID or false on failure
     */
    public function queue_email(
        string $recipient_email,
        string $recipient_name,
        string $email_type,
        int $child_id,
        ?string $family_id = null,
        ?int $sponsorship_id = null,
        array $template_data = []
    ): int|false {
        global $wpdb;
        
        $subject = $this->generate_subject($email_type, $template_data);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'cfk_email_logs',
            [
                'recipient_email' => $recipient_email,
                'recipient_name' => $recipient_name,
                'subject' => $subject,
                'email_type' => $email_type,
                'status' => 'pending',
                'child_id' => $child_id,
                'family_id' => $family_id,
                'family_number' => $family_id ? $this->child_manager::parse_family_id($family_id)['family_number'] : null,
                'sponsorship_id' => $sponsorship_id,
                'template_data' => wp_json_encode($template_data)
            ],
            [
                '%s', '%s', '%s', '%s', '%s', 
                '%d', '%s', '%s', '%d', '%s'
            ]
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Process email queue
     * 
     * @since 1.2.0
     * @return void
     */
    public function process_email_queue(): void {
        global $wpdb;
        
        $pending_emails = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}cfk_email_logs 
             WHERE status = 'pending' 
             ORDER BY created_at ASC 
             LIMIT 10"
        );
        
        foreach ($pending_emails as $email) {
            $this->send_queued_email($email);
        }
    }
    
    /**
     * Send a queued email
     * 
     * @since 1.2.0
     * @param object $email_data Email data from queue
     * @return void
     */
    private function send_queued_email(object $email_data): void {
        global $wpdb;
        
        try {
            $template_data = json_decode($email_data->template_data, true) ?? [];
            $message = $this->generate_email_content($email_data->email_type, $template_data);
            
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_option('cfk_from_name', 'Christmas for Kids') . ' <' . get_option('cfk_from_email', get_option('admin_email')) . '>'
            ];
            
            $sent = wp_mail(
                $email_data->recipient_email,
                $email_data->subject,
                $message,
                $headers
            );
            
            $wpdb->update(
                $wpdb->prefix . 'cfk_email_logs',
                [
                    'status' => $sent ? 'sent' : 'failed',
                    'sent_at' => $sent ? current_time('mysql') : null,
                    'error_message' => $sent ? null : 'wp_mail() returned false'
                ],
                ['id' => $email_data->id],
                ['%s', '%s', '%s'],
                ['%d']
            );
            
        } catch (Exception $e) {
            $wpdb->update(
                $wpdb->prefix . 'cfk_email_logs',
                [
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ],
                ['id' => $email_data->id],
                ['%s', '%s'],
                ['%d']
            );
        }
    }
    
    /**
     * Generate email subject line
     * 
     * @since 1.2.0
     * @param string $email_type Email type
     * @param array<string, mixed> $template_data Template data
     * @return string Subject line
     */
    private function generate_subject(string $email_type, array $template_data): string {
        $org_name = get_option('cfk_organization_name', 'Christmas for Kids');
        
        return match ($email_type) {
            'selection_confirmation' => "Your Christmas for Kids Selection - Action Required",
            'sponsor_confirmation' => "Thank You for Sponsoring {$template_data['child_name']} - {$org_name}",
            'admin_notification' => "New Sponsorship: {$template_data['child_name']} - {$org_name}",
            'cancellation' => "Selection Cancelled - {$org_name}",
            'family_update' => "Family Update: The {$template_data['family_number']} Family - {$org_name}",
            'sibling_notification' => "Sibling Sponsorship Update - {$org_name}",
            default => "{$org_name} - Update"
        };
    }
    
    /**
     * Generate email content from template
     * 
     * @since 1.2.0
     * @param string $email_type Email type
     * @param array<string, mixed> $template_data Template data
     * @return string HTML email content
     */
    private function generate_email_content(string $email_type, array $template_data): string {
        $template_file = CFK_PLUGIN_PATH . "includes/email-templates/{$email_type}.php";
        
        if (file_exists($template_file)) {
            ob_start();
            extract($template_data);
            include $template_file;
            return ob_get_clean();
        }
        
        // Fallback to basic template
        return $this->generate_basic_template($email_type, $template_data);
    }
    
    /**
     * Generate basic email template
     * 
     * @since 1.2.0
     * @param string $email_type Email type
     * @param array<string, mixed> $data Template data
     * @return string HTML content
     */
    private function generate_basic_template(string $email_type, array $data): string {
        $org_name = get_option('cfk_organization_name', 'Christmas for Kids');
        $child_name = $data['child_name'] ?? 'a child';
        $sponsor_name = $data['sponsor_name'] ?? 'Friend';
        
        return match ($email_type) {
            'selection_confirmation' => $this->get_selection_confirmation_template($data),
            'sponsor_confirmation' => $this->get_sponsor_confirmation_template($data),
            'admin_notification' => $this->get_admin_notification_template($data),
            'cancellation' => $this->get_cancellation_template($data),
            'family_update' => $this->get_family_update_template($data),
            default => "<html><body><h1>{$org_name}</h1><p>Thank you for your interest in our program.</p></body></html>"
        };
    }
    
    /**
     * Get child data for emails
     * 
     * @since 1.2.0
     * @param int $child_id Child ID
     * @return array<string, mixed> Child data
     */
    private function get_child_data(int $child_id): array {
        $child = get_post($child_id);
        return [
            'name' => $child ? $child->post_title : 'Unknown Child',
            'age' => get_post_meta($child_id, 'cfk_child_age', true),
            'gender' => get_post_meta($child_id, 'cfk_child_gender', true),
            'interests' => get_post_meta($child_id, 'cfk_child_interests', true)
        ];
    }
    
    /**
     * Get family data for emails
     * 
     * @since 1.2.0
     * @param string $family_number Family number
     * @return array<string, mixed> Family data
     */
    private function get_family_email_data(string $family_number): array {
        if (empty($family_number)) {
            return [];
        }
        
        $family_stats = $this->child_manager->get_family_stats($family_number);
        return [
            'family_number' => $family_number,
            'total_children' => $family_stats['total_children'],
            'available_children' => $family_stats['available_count'],
            'sponsored_children' => $family_stats['sponsored_count'],
            'children_list' => $this->format_family_children_list($family_stats['children'])
        ];
    }
    
    /**
     * Format family children list for emails
     * 
     * @since 1.2.0
     * @param array<WP_Post> $children Children posts
     * @return string Formatted list
     */
    private function format_family_children_list(array $children): string {
        $list = [];
        foreach ($children as $child) {
            $age = get_post_meta($child->ID, 'cfk_child_age', true);
            $family_id = get_post_meta($child->ID, 'cfk_child_family_id', true);
            $list[] = "{$child->post_title} ({$age} years old, #{$family_id})";
        }
        return implode(', ', $list);
    }
    
    /**
     * Get confirmation URL
     * 
     * @since 1.2.0
     * @param string $token Selection token
     * @return string Confirmation URL
     */
    private function get_confirmation_url(string $token): string {
        $base_url = get_option('cfk_confirmation_page_url', home_url('/sponsor-confirmation/'));
        return add_query_arg(['token' => $token], $base_url);
    }
    
    /**
     * Get cancellation URL
     * 
     * @since 1.2.0
     * @param string $token Selection token
     * @return string Cancellation URL
     */
    private function get_cancellation_url(string $token): string {
        $base_url = get_option('cfk_cancellation_page_url', home_url('/cancel-selection/'));
        return add_query_arg(['token' => $token], $base_url);
    }
    
    /**
     * Basic email templates (inline methods for brevity)
     */
    
    private function get_selection_confirmation_template(array $data): string {
        $child_name = $data['child_name'] ?? 'a child';
        $sponsor_name = $data['sponsor_name'] ?? 'Friend';
        $confirmation_url = $data['confirmation_url'] ?? '#';
        $selection_expires = $data['selection_expires'] ?? 'soon';
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h1 style='color: #c41e3a;'>Thank You for Your Selection!</h1>
            <p>Dear {$sponsor_name},</p>
            <p>Thank you for selecting <strong>{$child_name}</strong> to sponsor through Christmas for Kids!</p>
            <p>Your selection has been temporarily reserved until <strong>{$selection_expires}</strong>.</p>
            <p style='margin: 20px 0;'>
                <a href='{$confirmation_url}' style='background: #c41e3a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>Confirm Your Sponsorship</a>
            </p>
            <p>If you cannot confirm by the expiration time, this child will become available for other sponsors.</p>
            <p>Thank you for making a difference!</p>
        </body>
        </html>";
    }
    
    private function get_sponsor_confirmation_template(array $data): string {
        $child_name = $data['child_name'] ?? 'a child';
        $sponsor_name = $data['sponsor_name'] ?? 'Friend';
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h1 style='color: #c41e3a;'>Sponsorship Confirmed!</h1>
            <p>Dear {$sponsor_name},</p>
            <p>Your sponsorship of <strong>{$child_name}</strong> has been confirmed!</p>
            <p>Thank you for your generous heart and for making Christmas special for this child.</p>
            <p>You will receive additional information about your sponsored child soon.</p>
            <p>Blessings,<br>The Christmas for Kids Team</p>
        </body>
        </html>";
    }
    
    private function get_admin_notification_template(array $data): string {
        $sponsor_name = $data['sponsor_name'] ?? 'Unknown';
        $sponsor_email = $data['sponsor_email'] ?? 'unknown@example.com';
        $child_name = $data['child_name'] ?? 'Unknown Child';
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h1 style='color: #c41e3a;'>New Sponsorship Confirmed</h1>
            <p><strong>Sponsor:</strong> {$sponsor_name} ({$sponsor_email})</p>
            <p><strong>Child:</strong> {$child_name}</p>
            <p><strong>Confirmed:</strong> {$data['confirmed_at']}</p>
            <p>Please follow up with sponsor information and next steps.</p>
        </body>
        </html>";
    }
    
    private function get_cancellation_template(array $data): string {
        $sponsor_name = $data['sponsor_name'] ?? 'Friend';
        $child_name = $data['child_name'] ?? 'a child';
        $browse_url = $data['browse_url'] ?? home_url();
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h1 style='color: #c41e3a;'>Selection Cancelled</h1>
            <p>Dear {$sponsor_name},</p>
            <p>Your selection of {$child_name} has been cancelled as requested.</p>
            <p>The child is now available for other sponsors to select.</p>
            <p><a href='{$browse_url}'>Browse available children</a> if you'd like to make a new selection.</p>
            <p>Thank you for your interest in Christmas for Kids.</p>
        </body>
        </html>";
    }
    
    private function get_family_update_template(array $data): string {
        $sponsor_name = $data['sponsor_name'] ?? 'Friend';
        $family_number = $data['family_number'] ?? 'Unknown';
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h1 style='color: #c41e3a;'>Family Update - Family {$family_number}</h1>
            <p>Dear {$sponsor_name},</p>
            <p>We're excited to share that Family {$family_number} is now fully sponsored!</p>
            <p>Your generosity has helped complete this family's Christmas. All {$data['total_children']} children will have a wonderful holiday.</p>
            <p>Thank you for being part of this blessing!</p>
        </body>
        </html>";
    }
    
    /**
     * Add custom cron intervals
     * 
     * @since 1.2.0
     * @param array<string, array<string, mixed>> $schedules Existing schedules
     * @return array<string, array<string, mixed>> Updated schedules
     */
    public function add_cron_intervals(array $schedules): array {
        $schedules['every_five_minutes'] = [
            'interval' => 5 * 60,
            'display' => 'Every 5 Minutes'
        ];
        
        return $schedules;
    }
    
    /**
     * Get all email types
     * 
     * @since 1.2.0
     * @return array<string, string> Email types
     */
    public static function get_email_types(): array {
        return self::EMAIL_TYPES;
    }
}