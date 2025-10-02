<?php
declare(strict_types=1);

/**
 * Sponsorship Management System for Christmas for Kids
 * 
 * Handles sponsorship form submissions, family-aware selections,
 * temporary reservations, and sponsor confirmations with full
 * family relationship support.
 * 
 * @package ChristmasForKids
 * @since 1.2.0
 */
class CFK_Sponsorship_Manager {
    
    /**
     * Selection timeout in hours
     * 
     * @since 1.2.0
     * @var int
     */
    private const SELECTION_TIMEOUT = 2;
    
    /**
     * Sponsorship types
     * 
     * @since 1.2.0
     * @var array<string>
     */
    private const SPONSORSHIP_TYPES = [
        'individual' => 'Individual Child',
        'siblings' => 'Multiple Siblings', 
        'family' => 'Entire Family'
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
     * Initialize the sponsorship manager
     * 
     * @since 1.2.0
     * @return void
     */
    public function init(): void {
        // Register AJAX handlers for logged-in and non-logged-in users
        add_action('wp_ajax_cfk_submit_sponsorship', [$this, 'handle_sponsorship_submission']);
        add_action('wp_ajax_nopriv_cfk_submit_sponsorship', [$this, 'handle_sponsorship_submission']);
        
        add_action('wp_ajax_cfk_confirm_sponsorship', [$this, 'handle_sponsorship_confirmation']);
        add_action('wp_ajax_nopriv_cfk_confirm_sponsorship', [$this, 'handle_sponsorship_confirmation']);
        
        add_action('wp_ajax_cfk_cancel_sponsorship', [$this, 'handle_sponsorship_cancellation']);
        add_action('wp_ajax_nopriv_cfk_cancel_sponsorship', [$this, 'handle_sponsorship_cancellation']);
        
        // Schedule cleanup of expired selections
        add_action('cfk_cleanup_expired_selections', [$this, 'cleanup_expired_selections']);
        
        if (!wp_next_scheduled('cfk_cleanup_expired_selections')) {
            wp_schedule_event(time(), 'hourly', 'cfk_cleanup_expired_selections');
        }
    }
    
    /**
     * Handle sponsorship form submission
     * 
     * @since 1.2.0
     * @return void
     */
    public function handle_sponsorship_submission(): void {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cfk_sponsorship_nonce')) {
            wp_send_json_error([
                'message' => 'Security verification failed. Please refresh and try again.'
            ]);
            return;
        }
        
        try {
            $sponsorship_data = $this->validate_sponsorship_data($_POST);
            $token = $this->create_sponsorship_selection($sponsorship_data);
            
            wp_send_json_success([
                'message' => 'Thank you! Your selection has been temporarily reserved.',
                'token' => $token,
                'expires_in_minutes' => self::SELECTION_TIMEOUT * 60,
                'confirmation_url' => $this->get_confirmation_url($token)
            ]);
            
        } catch (Exception $e) {
            error_log('CFK Sponsorship submission error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Validate sponsorship form data
     * 
     * @since 1.2.0
     * @param array<string, mixed> $post_data Form data
     * @return array<string, mixed> Validated data
     * @throws Exception If validation fails
     */
    private function validate_sponsorship_data(array $post_data): array {
        $required_fields = ['sponsor_name', 'sponsor_email', 'child_id', 'sponsorship_type'];
        $validated = [];
        
        foreach ($required_fields as $field) {
            if (empty($post_data[$field])) {
                throw new Exception("Required field missing: {$field}");
            }
            $validated[$field] = sanitize_text_field($post_data[$field]);
        }
        
        // Validate email
        if (!is_email($validated['sponsor_email'])) {
            throw new Exception('Please enter a valid email address.');
        }
        
        // Validate sponsorship type
        if (!array_key_exists($validated['sponsorship_type'], self::SPONSORSHIP_TYPES)) {
            throw new Exception('Invalid sponsorship type selected.');
        }
        
        // Validate child exists and is available
        $child_id = (int) $validated['child_id'];
        $child = get_post($child_id);
        
        if (!$child || $child->post_type !== CFK_Child_Manager::get_post_type()) {
            throw new Exception('Selected child not found.');
        }
        
        if ($this->child_manager->is_child_sponsored($child_id)) {
            throw new Exception('This child has already been sponsored.');
        }
        
        $validated['child_id'] = $child_id;
        
        // Optional fields
        $validated['sponsor_phone'] = sanitize_text_field($post_data['sponsor_phone'] ?? '');
        $validated['sponsor_address'] = sanitize_textarea_field($post_data['sponsor_address'] ?? '');
        $validated['additional_children'] = sanitize_textarea_field($post_data['additional_children'] ?? '');
        
        // Get family information for the child
        $family_id = get_post_meta($child_id, 'cfk_child_family_id', true);
        $family_number = get_post_meta($child_id, 'cfk_child_family_number', true);
        
        $validated['family_id'] = $family_id;
        $validated['family_number'] = $family_number;
        
        // Validate family-specific sponsorship options
        if ($validated['sponsorship_type'] !== 'individual' && empty($family_number)) {
            throw new Exception('Family sponsorship selected but child has no family information.');
        }
        
        return $validated;
    }
    
    /**
     * Create a temporary sponsorship selection
     * 
     * @since 1.2.0
     * @param array<string, mixed> $sponsorship_data Validated sponsorship data
     * @return string Selection token
     * @throws Exception If creation fails
     */
    private function create_sponsorship_selection(array $sponsorship_data): string {
        global $wpdb;
        
        $token = wp_generate_password(32, false);
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . self::SELECTION_TIMEOUT . ' hours'));
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'cfk_sponsorships',
            [
                'child_id' => $sponsorship_data['child_id'],
                'family_id' => $sponsorship_data['family_id'],
                'family_number' => $sponsorship_data['family_number'],
                'sponsor_name' => $sponsorship_data['sponsor_name'],
                'sponsor_email' => $sponsorship_data['sponsor_email'],
                'sponsor_phone' => $sponsorship_data['sponsor_phone'],
                'sponsor_address' => $sponsorship_data['sponsor_address'],
                'sponsorship_type' => $sponsorship_data['sponsorship_type'],
                'additional_children' => $sponsorship_data['additional_children'],
                'status' => 'selected',
                'selection_token' => $token,
                'expires_at' => $expires_at
            ],
            [
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', 
                '%s', '%s', '%s', '%s', '%s'
            ]
        );
        
        if ($result === false) {
            throw new Exception('Failed to create sponsorship selection. Please try again.');
        }
        
        // Log the selection for audit trail
        $this->log_sponsorship_activity($wpdb->insert_id, 'selection_created', [
            'child_id' => $sponsorship_data['child_id'],
            'family_id' => $sponsorship_data['family_id'],
            'sponsor_email' => $sponsorship_data['sponsor_email'],
            'expires_at' => $expires_at
        ]);
        
        return $token;
    }
    
    /**
     * Handle sponsorship confirmation
     * 
     * @since 1.2.0
     * @return void
     */
    public function handle_sponsorship_confirmation(): void {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cfk_confirm_nonce')) {
            wp_send_json_error([
                'message' => 'Security verification failed.'
            ]);
            return;
        }
        
        try {
            $token = sanitize_text_field($_POST['token'] ?? '');
            if (empty($token)) {
                throw new Exception('Invalid confirmation token.');
            }
            
            $sponsorship = $this->get_sponsorship_by_token($token);
            if (!$sponsorship) {
                throw new Exception('Sponsorship selection not found or has expired.');
            }
            
            $this->confirm_sponsorship($sponsorship);
            
            wp_send_json_success([
                'message' => 'Sponsorship confirmed successfully! Thank you for your generosity.',
                'child_name' => get_the_title($sponsorship->child_id),
                'family_info' => $this->get_family_confirmation_info($sponsorship)
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get sponsorship by token
     * 
     * @since 1.2.0
     * @param string $token Selection token
     * @return object|null Sponsorship data or null
     */
    public function get_sponsorship_by_token(string $token): ?object {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cfk_sponsorships 
                 WHERE selection_token = %s 
                 AND status = 'selected' 
                 AND expires_at > NOW()",
                $token
            )
        );
    }
    
    /**
     * Confirm a sponsorship selection
     * 
     * @since 1.2.0
     * @param object $sponsorship Sponsorship data
     * @return void
     * @throws Exception If confirmation fails
     */
    private function confirm_sponsorship(object $sponsorship): void {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'cfk_sponsorships',
            [
                'status' => 'confirmed',
                'confirmed_at' => current_time('mysql')
            ],
            ['id' => $sponsorship->id],
            ['%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            throw new Exception('Failed to confirm sponsorship.');
        }
        
        $this->log_sponsorship_activity($sponsorship->id, 'sponsorship_confirmed', [
            'child_id' => $sponsorship->child_id,
            'sponsor_email' => $sponsorship->sponsor_email
        ]);
    }
    
    /**
     * Handle sponsorship cancellation
     * 
     * @since 1.2.0
     * @return void
     */
    public function handle_sponsorship_cancellation(): void {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cfk_cancel_nonce')) {
            wp_send_json_error([
                'message' => 'Security verification failed.'
            ]);
            return;
        }
        
        try {
            $token = sanitize_text_field($_POST['token'] ?? '');
            $sponsorship = $this->get_sponsorship_by_token($token);
            
            if (!$sponsorship) {
                throw new Exception('Sponsorship not found.');
            }
            
            $this->cancel_sponsorship($sponsorship);
            
            wp_send_json_success([
                'message' => 'Your selection has been cancelled and the child is now available for other sponsors.'
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Cancel a sponsorship selection
     * 
     * @since 1.2.0
     * @param object $sponsorship Sponsorship data
     * @return void
     */
    private function cancel_sponsorship(object $sponsorship): void {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'cfk_sponsorships',
            ['status' => 'cancelled'],
            ['id' => $sponsorship->id],
            ['%s'],
            ['%d']
        );
        
        $this->log_sponsorship_activity($sponsorship->id, 'sponsorship_cancelled', [
            'child_id' => $sponsorship->child_id,
            'sponsor_email' => $sponsorship->sponsor_email
        ]);
    }
    
    /**
     * Cleanup expired selections
     * 
     * @since 1.2.0
     * @return void
     */
    public function cleanup_expired_selections(): void {
        global $wpdb;
        
        $expired_count = $wpdb->query(
            "UPDATE {$wpdb->prefix}cfk_sponsorships 
             SET status = 'expired' 
             WHERE status = 'selected' 
             AND expires_at < NOW()"
        );
        
        if ($expired_count > 0) {
            error_log("CFK: Cleaned up {$expired_count} expired sponsorship selections");
        }
    }
    
    /**
     * Get family confirmation information
     * 
     * @since 1.2.0
     * @param object $sponsorship Sponsorship data
     * @return array<string, mixed> Family information
     */
    private function get_family_confirmation_info(object $sponsorship): array {
        $info = [
            'sponsorship_type' => $sponsorship->sponsorship_type,
            'primary_child' => get_the_title($sponsorship->child_id)
        ];
        
        if (!empty($sponsorship->family_number)) {
            $family_stats = $this->child_manager->get_family_stats($sponsorship->family_number);
            $info['family_stats'] = $family_stats;
        }
        
        return $info;
    }
    
    /**
     * Get confirmation URL for a token
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
     * Log sponsorship activity
     * 
     * @since 1.2.0
     * @param int $sponsorship_id Sponsorship ID
     * @param string $activity Activity type
     * @param array<string, mixed> $data Activity data
     * @return void
     */
    private function log_sponsorship_activity(int $sponsorship_id, string $activity, array $data): void {
        $message = sprintf(
            'CFK Sponsorship %s: ID=%d, Data=%s',
            $activity,
            $sponsorship_id,
            wp_json_encode($data)
        );
        
        error_log($message);
    }
    
    /**
     * Get all sponsorship types
     * 
     * @since 1.2.0
     * @return array<string, string> Sponsorship types
     */
    public static function get_sponsorship_types(): array {
        return self::SPONSORSHIP_TYPES;
    }
    
    /**
     * Get selection timeout in hours
     * 
     * @since 1.2.0
     * @return int Timeout hours
     */
    public static function get_selection_timeout(): int {
        return self::SELECTION_TIMEOUT;
    }
}