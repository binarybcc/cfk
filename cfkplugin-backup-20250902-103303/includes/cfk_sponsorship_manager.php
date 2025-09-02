<?php
/**
 * Sponsorship Manager Class
 * Handles child selection, temporary reservations, and sponsor confirmations
 *
 * @package ChristmasForKids
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Readonly DTOs for data transfer
readonly class CFK_SponsorData {
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public string $address,
        public string $notes
    ) {}
    
    public static function from_post(array $post_data): self {
        return new self(
            name: sanitize_text_field($post_data['sponsor_name'] ?? ''),
            email: sanitize_email($post_data['sponsor_email'] ?? ''),
            phone: sanitize_text_field($post_data['sponsor_phone'] ?? ''),
            address: sanitize_textarea_field($post_data['sponsor_address'] ?? ''),
            notes: sanitize_textarea_field($post_data['sponsor_notes'] ?? '')
        );
    }
    
    public function validate(): array {
        $errors = [];
        
        if (empty($this->name)) {
            $errors[] = __('Name is required.', 'cfk-sponsorship');
        }
        
        if (empty($this->email) || !is_email($this->email)) {
            $errors[] = __('Valid email address is required.', 'cfk-sponsorship');
        }
        
        return $errors;
    }
    
    public function to_array(): array {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'notes' => $this->notes
        ];
    }
}

readonly class CFK_SelectedChild {
    public function __construct(
        public string $id,
        public int $post_id,
        public string $name,
        public int $age,
        public CFK_Gender $gender,
        public string $family_id,
        public string $clothing_info,
        public string $gift_requests,
        public CFK_AgeRange $age_range
    ) {}
    
    public static function from_post(WP_Post $post): ?self {
        try {
            $child_id = get_post_meta($post->ID, '_child_id', true);
            $gender = CFK_Gender::from(get_post_meta($post->ID, '_child_gender', true));
            $age_range = CFK_AgeRange::from(get_post_meta($post->ID, '_child_age_range', true));
            
            return new self(
                id: $child_id,
                post_id: $post->ID,
                name: $post->post_title,
                age: intval(get_post_meta($post->ID, '_child_age', true)),
                gender: $gender,
                family_id: get_post_meta($post->ID, '_child_family_id', true),
                clothing_info: get_post_meta($post->ID, '_child_clothing_info', true),
                gift_requests: get_post_meta($post->ID, '_child_gift_requests', true),
                age_range: $age_range
            );
        } catch (ValueError $e) {
            error_log('CFK: Invalid child data for post ' . $post->ID . ': ' . $e->getMessage());
            return null;
        }
    }
    
    public function to_array(): array {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'name' => $this->name,
            'age' => $this->age,
            'gender' => $this->gender->value,
            'family_id' => $this->family_id,
            'clothing_info' => $this->clothing_info,
            'gift_requests' => $this->gift_requests,
            'age_range' => $this->age_range->value
        ];
    }
}

readonly class CFK_SponsorStats {
    public function __construct(
        public int $total_sponsors,
        public int $children_sponsored,
        public int $pending_selections,
        public float $average_children_per_sponsor,
        public float $total_value
    ) {}
}

readonly class CFK_SponsorshipConfig {
    public function __construct(
        public int $selection_timeout_hours = 2,
        public bool $require_phone = false,
        public bool $require_address = false,
        public int $max_children_per_sponsor = 0,
        public bool $allow_duplicate_emails = true,
        public float $average_sponsorship_value = 100.0
    ) {}
    
    public static function from_options(): self {
        return new self(
            selection_timeout_hours: intval(ChristmasForKidsPlugin::get_option('cfk_selection_timeout', 2)),
            require_phone: boolval(ChristmasForKidsPlugin::get_option('cfk_require_phone', false)),
            require_address: boolval(ChristmasForKidsPlugin::get_option('cfk_require_address', false)),
            max_children_per_sponsor: intval(ChristmasForKidsPlugin::get_option('cfk_max_children_per_sponsor', 0)),
            allow_duplicate_emails: boolval(ChristmasForKidsPlugin::get_option('cfk_allow_duplicate_emails', true)),
            average_sponsorship_value: floatval(ChristmasForKidsPlugin::get_option('cfk_average_sponsorship_value', 100))
        );
    }
}

class CFK_Sponsorship_Manager {
    private readonly CFK_SponsorshipConfig $config;
    
    public function __construct() {
        $this->config = CFK_SponsorshipConfig::from_options();
        $this->register_hooks();
        $this->schedule_cleanup();
    }
    
    private function register_hooks(): void {
        add_action('init', $this->init_session(...));
        
        // AJAX handlers
        $ajax_handlers = [
            'cfk_select_child' => $this->ajax_select_child(...),
            'cfk_remove_child' => $this->ajax_remove_child(...),
            'cfk_confirm_sponsorship' => $this->ajax_confirm_sponsorship(...)
        ];
        
        foreach ($ajax_handlers as $action => $handler) {
            add_action("wp_ajax_$action", $handler);
            add_action("wp_ajax_nopriv_$action", $handler);
        }
        
        // Admin hooks
        add_action('admin_menu', $this->add_admin_menus(...));
        add_action('admin_post_cfk_resend_sponsor_email', $this->resend_sponsor_email(...));
        add_action('admin_post_cfk_cancel_sponsorship', $this->cancel_sponsorship(...));
        add_action('admin_post_cfk_export_sponsorships', $this->export_sponsorships_csv(...));
        
        // Cleanup hook
        add_action('cfk_cleanup_abandoned_selections', $this->cleanup_abandoned_selections(...));
    }
    
    private function schedule_cleanup(): void {
        if (!wp_next_scheduled('cfk_cleanup_abandoned_selections')) {
            wp_schedule_event(time(), 'hourly', 'cfk_cleanup_abandoned_selections');
        }
    }
    
    public function init_session(): void {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }
    
    public function ajax_select_child(): void {
        try {
            check_ajax_referer('cfk_sponsorship_nonce', 'nonce');
            
            $child_id = sanitize_text_field($_POST['child_id'] ?? '');
            $session_id = $this->get_session_id();
            
            $this->validate_sponsorship_state();
            $child_post = $this->validate_child_selection($child_id, $session_id);
            
            $result = $this->add_child_to_selection($session_id, $child_id);
            
            match($result) {
                true => wp_send_json_success([
                    'message' => __('Child added to your sponsorship list', 'cfk-sponsorship'),
                    'cart_count' => $this->get_selection_count($session_id)
                ]),
                false => wp_send_json_error(__('Failed to select child. Please try again.', 'cfk-sponsorship'))
            };
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function ajax_remove_child(): void {
        try {
            check_ajax_referer('cfk_sponsorship_nonce', 'nonce');
            
            $child_id = sanitize_text_field($_POST['child_id'] ?? '');
            $session_id = $this->get_session_id();
            
            $result = $this->remove_child_from_selection($session_id, $child_id);
            
            match($result) {
                true => wp_send_json_success([
                    'message' => __('Child removed from your sponsorship list', 'cfk-sponsorship'),
                    'cart_count' => $this->get_selection_count($session_id)
                ]),
                false => wp_send_json_error(__('Failed to remove child. Please try again.', 'cfk-sponsorship'))
            };
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function ajax_confirm_sponsorship(): void {
        try {
            check_ajax_referer('cfk_sponsorship_nonce', 'nonce');
            
            $session_id = $this->get_session_id();
            $sponsor_data = CFK_SponsorData::from_post($_POST);
            
            // Validate sponsor data
            $validation_errors = $sponsor_data->validate();
            if ($validation_errors !== []) {
                wp_send_json_error(implode(' ', $validation_errors));
            }
            
            $this->validate_sponsorship_state();
            $selected_children = $this->validate_selected_children($session_id);
            
            $result = $this->confirm_sponsorship($session_id, $sponsor_data);
            
            if ($result) {
                $this->send_confirmation_emails($session_id, $sponsor_data, $selected_children);
                
                wp_send_json_success([
                    'message' => __('Thank you! Your sponsorship has been confirmed. You should receive a confirmation email shortly.', 'cfk-sponsorship'),
                    'redirect' => $this->get_thank_you_page_url()
                ]);
            } else {
                wp_send_json_error(__('Failed to confirm sponsorship. Please try again.', 'cfk-sponsorship'));
            }
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    private function validate_sponsorship_state(): void {
        if (!$this->are_sponsorships_open()) {
            throw new Exception(__('Sponsorships are currently closed.', 'cfk-sponsorship'));
        }
    }
    
    private function validate_child_selection(string $child_id, string $session_id): WP_Post {
        $child_post = CFK_Children_Manager::get_child_by_id($child_id);
        
        match(true) {
            !$child_post => throw new Exception(__('Child not found.', 'cfk-sponsorship')),
            $this->is_child_sponsored($child_post) => throw new Exception(__('This child has already been sponsored.', 'cfk-sponsorship')),
            $this->is_child_selected($child_id, $session_id) => throw new Exception(__('This child has already been selected by another sponsor.', 'cfk-sponsorship')),
            default => null
        };
        
        return $child_post;
    }
    
    private function validate_selected_children(string $session_id): array {
        $selected_children = $this->get_selected_children($session_id);
        
        if ($selected_children === []) {
            throw new Exception(__('No children selected for sponsorship.', 'cfk-sponsorship'));
        }
        
        // Verify children are still available
        foreach ($selected_children as $child) {
            $child_post = CFK_Children_Manager::get_child_by_id($child->id);
            if ($this->is_child_sponsored($child_post)) {
                throw new Exception(sprintf(
                    __('Child %s has already been sponsored by someone else. Please refresh the page and select different children.', 'cfk-sponsorship'),
                    $child->name
                ));
            }
        }
        
        return $selected_children;
    }
    
    private function send_confirmation_emails(string $session_id, CFK_SponsorData $sponsor_data, array $selected_children): void {
        if (class_exists('CFK_Email_Manager')) {
            $email_manager = new CFK_Email_Manager();
            $email_manager->send_sponsor_confirmation($session_id, $sponsor_data->to_array(), array_map(fn($child) => $child->to_array(), $selected_children));
            $email_manager->send_admin_notification($session_id, $sponsor_data->to_array(), array_map(fn($child) => $child->to_array(), $selected_children));
        }
    }
    
    private function get_session_id(): string {
        $_SESSION['cfk_session_id'] ??= 'cfk_' . uniqid() . '_' . time();
        return $_SESSION['cfk_session_id'];
    }
    
    private function are_sponsorships_open(): bool {
        return boolval(ChristmasForKidsPlugin::get_option('cfk_sponsorships_open', false));
    }
    
    private function is_child_sponsored(?WP_Post $child_post): bool {
        return $child_post ? get_post_meta($child_post->ID, '_child_sponsored', true) === '1' : false;
    }
    
    private function is_child_selected(string $child_id, string $current_session_id = ''): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        $where_clause = "child_id = %s AND status IN ('selected', 'confirmed')";
        $params = [$child_id];
        
        if ($current_session_id !== '') {
            $where_clause .= " AND session_id != %s";
            $params[] = $current_session_id;
        }
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE $where_clause",
            $params
        ));
        
        return $count > 0;
    }
    
    private function add_child_to_selection(string $session_id, string $child_id): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'session_id' => $session_id,
                'child_id' => $child_id,
                'status' => CFK_Status::SELECTED->value,
                'selected_time' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    private function remove_child_from_selection(string $session_id, string $child_id): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        $result = $wpdb->delete(
            $table_name,
            [
                'session_id' => $session_id,
                'child_id' => $child_id,
                'status' => CFK_Status::SELECTED->value
            ],
            ['%s', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    private function get_selection_count(string $session_id): int {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE session_id = %s AND status = %s",
            $session_id,
            CFK_Status::SELECTED->value
        )));
    }
    
    public function get_selected_children(string $session_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        $child_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT child_id FROM $table_name WHERE session_id = %s AND status = %s",
            $session_id,
            CFK_Status::SELECTED->value
        ));
        
        if ($child_ids === []) {
            return [];
        }
        
        $children = [];
        foreach ($child_ids as $child_id) {
            $child_post = CFK_Children_Manager::get_child_by_id($child_id);
            
            if ($child_post) {
                $selected_child = CFK_SelectedChild::from_post($child_post);
                if ($selected_child) {
                    $children[] = $selected_child;
                }
            }
        }
        
        return $children;
    }
    
    private function confirm_sponsorship(string $session_id, CFK_SponsorData $sponsor_data): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        $result = $wpdb->update(
            $table_name,
            [
                'sponsor_name' => $sponsor_data->name,
                'sponsor_email' => $sponsor_data->email,
                'sponsor_phone' => $sponsor_data->phone,
                'sponsor_address' => $sponsor_data->address,
                'sponsor_notes' => $sponsor_data->notes,
                'status' => CFK_Status::CONFIRMED->value,
                'confirmed_time' => current_time('mysql')
            ],
            [
                'session_id' => $session_id,
                'status' => CFK_Status::SELECTED->value
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s'],
            ['%s', '%s']
        );
        
        if ($result !== false) {
            $selected_children = $this->get_confirmed_children($session_id);
            foreach ($selected_children as $child) {
                CFK_Children_Manager::mark_as_sponsored($child->id, true);
            }
        }
        
        return $result !== false;
    }
    
    public function cleanup_abandoned_selections(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        $timeout_time = date('Y-m-d H:i:s', strtotime("-{$this->config->selection_timeout_hours} hours"));
        
        $result = $wpdb->delete(
            $table_name,
            ['status' => CFK_Status::SELECTED->value],
            ['%s'],
            "selected_time < %s",
            [$timeout_time]
        );
        
        if ($result !== false && $result > 0) {
            error_log("CFK: Cleaned up {$result} abandoned selections");
        }
    }
    
    private function get_thank_you_page_url(): string {
        $thank_you_page = get_page_by_path('sponsorship-thank-you');
        return $thank_you_page ? get_permalink($thank_you_page->ID) : home_url('/');
    }
    
    public function add_admin_menus(): void {
        add_submenu_page(
            'cfk-dashboard',
            __('Sponsorships', 'cfk-sponsorship'),
            __('Sponsorships', 'cfk-sponsorship'),
            'manage_options',
            'cfk-sponsorships',
            $this->sponsorships_admin_page(...)
        );
    }
    
    public function display_sponsorships_page(): void {
        $this->sponsorships_admin_page();
    }
    
    public function sponsorships_admin_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cfk-sponsorship'));
        }
        
        $this->handle_bulk_actions();
        $this->render_admin_page();
    }
    
    private function handle_bulk_actions(): void {
        if (isset($_POST['action']) && $_POST['action'] === 'bulk_cancel' && !empty($_POST['sponsorships'])) {
            if (wp_verify_nonce($_POST['cfk_bulk_nonce'] ?? '', 'cfk_bulk_action')) {
                $this->handle_bulk_cancel($_POST['sponsorships']);
            }
        }
    }
    
    private function render_admin_page(): void {
        $stats = $this->get_sponsorship_stats();
        $pagination_data = $this->get_paginated_sponsorships();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Sponsorship Management', 'cfk-sponsorship'); ?></h1>
            
            <?php $this->render_stats_grid($stats); ?>
            <?php $this->render_sponsorships_table($pagination_data); ?>
        </div>
        
        <?php $this->render_admin_styles_and_scripts(); ?>
        <?php
    }
    
    private function render_stats_grid(CFK_SponsorStats $stats): void {
        ?>
        <div class="cfk-stats-grid">
            <div class="cfk-stat-card">
                <div class="cfk-stat-number"><?php echo $stats->total_sponsors; ?></div>
                <div class="cfk-stat-label"><?php _e('Total Sponsors', 'cfk-sponsorship'); ?></div>
            </div>
            <div class="cfk-stat-card">
                <div class="cfk-stat-number"><?php echo $stats->children_sponsored; ?></div>
                <div class="cfk-stat-label"><?php _e('Children Sponsored', 'cfk-sponsorship'); ?></div>
            </div>
            <div class="cfk-stat-card">
                <div class="cfk-stat-number"><?php echo $stats->pending_selections; ?></div>
                <div class="cfk-stat-label"><?php _e('Pending Selections', 'cfk-sponsorship'); ?></div>
            </div>
            <div class="cfk-stat-card">
                <div class="cfk-stat-number"><?php echo $stats->average_children_per_sponsor; ?></div>
                <div class="cfk-stat-label"><?php _e('Avg Children/Sponsor', 'cfk-sponsorship'); ?></div>
            </div>
            <div class="cfk-stat-card">
                <div class="cfk-stat-number">$<?php echo number_format($stats->total_value); ?></div>
                <div class="cfk-stat-label"><?php _e('Total Value', 'cfk-sponsorship'); ?></div>
            </div>
        </div>
        <?php
    }
    
    private function render_sponsorships_table(array $pagination_data): void {
        ['sponsorships' => $sponsorships, 'total_pages' => $total_pages, 'current_page' => $page] = $pagination_data;
        
        ?>
        <form method="post">
            <?php wp_nonce_field('cfk_bulk_action', 'cfk_bulk_nonce'); ?>
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="action">
                        <option value="-1"><?php _e('Bulk Actions', 'cfk-sponsorship'); ?></option>
                        <option value="bulk_cancel"><?php _e('Cancel Sponsorships', 'cfk-sponsorship'); ?></option>
                    </select>
                    <input type="submit" class="button action" value="<?php _e('Apply', 'cfk-sponsorship'); ?>">
                </div>
                <div class="alignright actions">
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=cfk_export_sponsorships'), 'cfk_export_sponsorships'); ?>" 
                       class="button"><?php _e('Export CSV', 'cfk-sponsorship'); ?></a>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all">
                        </td>
                        <th><?php _e('Sponsor Name', 'cfk-sponsorship'); ?></th>
                        <th><?php _e('Email', 'cfk-sponsorship'); ?></th>
                        <th><?php _e('Phone', 'cfk-sponsorship'); ?></th>
                        <th><?php _e('Children', 'cfk-sponsorship'); ?></th>
                        <th><?php _e('Count', 'cfk-sponsorship'); ?></th>
                        <th><?php _e('Date Confirmed', 'cfk-sponsorship'); ?></th>
                        <th><?php _e('Actions', 'cfk-sponsorship'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sponsorships as $sponsorship): ?>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" name="sponsorships[]" value="<?php echo esc_attr($sponsorship->session_id); ?>">
                        </th>
                        <td><strong><?php echo esc_html($sponsorship->sponsor_name); ?></strong></td>
                        <td><?php echo esc_html($sponsorship->sponsor_email); ?></td>
                        <td><?php echo esc_html($sponsorship->sponsor_phone); ?></td>
                        <td><?php echo esc_html($sponsorship->children); ?></td>
                        <td><?php echo esc_html($sponsorship->child_count); ?></td>
                        <td><?php echo esc_html(mysql2date('M j, Y g:i a', $sponsorship->confirmed_time)); ?></td>
                        <td>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=cfk_resend_sponsor_email&session_id=' . urlencode($sponsorship->session_id)), 'cfk_resend_email'); ?>" 
                               class="button button-small"><?php _e('Resend Email', 'cfk-sponsorship'); ?></a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=cfk_cancel_sponsorship&session_id=' . urlencode($sponsorship->session_id)), 'cfk_cancel_sponsorship'); ?>" 
                               class="button button-small"
                               onclick="return confirm('<?php _e('Are you sure you want to cancel this sponsorship?', 'cfk-sponsorship'); ?>')"><?php _e('Cancel', 'cfk-sponsorship'); ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if ($sponsorships === []): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <?php _e('No sponsorships found.', 'cfk-sponsorship'); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'current' => $page,
                        'total' => $total_pages
                    ]);
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </form>
        <?php
    }
    
    private function get_paginated_sponsorships(): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        $page = max(1, intval($_GET['paged'] ?? 1));
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $sponsorships = $wpdb->get_results($wpdb->prepare("
            SELECT session_id, sponsor_name, sponsor_email, sponsor_phone, confirmed_time,
                   GROUP_CONCAT(child_id ORDER BY child_id SEPARATOR ', ') as children,
                   COUNT(child_id) as child_count
            FROM $table_name 
            WHERE status = %s
            GROUP BY session_id 
            ORDER BY confirmed_time DESC 
            LIMIT %d OFFSET %d
        ", CFK_Status::CONFIRMED->value, $per_page, $offset));
        
        $total_sponsorships = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT session_id) 
            FROM $table_name 
            WHERE status = %s
        ", CFK_Status::CONFIRMED->value));
        
        return [
            'sponsorships' => $sponsorships,
            'total_pages' => ceil($total_sponsorships / $per_page),
            'current_page' => $page
        ];
    }
    
    private function render_admin_styles_and_scripts(): void {
        ?>
        <style>
        .cfk-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
        </style>
        
        <script>
        document.getElementById('cb-select-all')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="sponsorships[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
        </script>
        <?php
    }
    
    private function get_sponsorship_stats(): CFK_SponsorStats {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        $total_sponsors = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE status = %s",
            CFK_Status::CONFIRMED->value
        )));
        
        $children_sponsored = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE status = %s",
            CFK_Status::CONFIRMED->value
        )));
        
        $pending_selections = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE status = %s",
            CFK_Status::SELECTED->value
        )));
        
        $avg_children = $total_sponsors > 0 ? round($children_sponsored / $total_sponsors, 1) : 0.0;
        $total_value = $children_sponsored * $this->config->average_sponsorship_value;
        
        return new CFK_SponsorStats(
            total_sponsors: $total_sponsors,
            children_sponsored: $children_sponsored,
            pending_selections: $pending_selections,
            average_children_per_sponsor: $avg_children,
            total_value: $total_value
        );
    }
    
    private function handle_bulk_cancel(array $session_ids): void {
        foreach ($session_ids as $session_id) {
            $this->cancel_sponsorship_by_session(sanitize_text_field($session_id));
        }
        
        wp_redirect(add_query_arg('cancelled', count($session_ids), wp_get_referer()));
        exit;
    }
    
    public function resend_sponsor_email(): void {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cfk_resend_email') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'cfk-sponsorship'));
        }
        
        $session_id = sanitize_text_field($_GET['session_id'] ?? '');
        
        try {
            $sponsorship_data = $this->get_sponsorship_data($session_id);
            $selected_children = $this->get_confirmed_children($session_id);
            
            if ($sponsorship_data && class_exists('CFK_Email_Manager')) {
                $sponsor_data = [
                    'name' => $sponsorship_data->sponsor_name,
                    'email' => $sponsorship_data->sponsor_email,
                    'phone' => $sponsorship_data->sponsor_phone,
                    'address' => $sponsorship_data->sponsor_address,
                    'notes' => $sponsorship_data->sponsor_notes
                ];
                
                $email_manager = new CFK_Email_Manager();
                $email_manager->send_sponsor_confirmation($session_id, $sponsor_data, array_map(fn($child) => $child->to_array(), $selected_children));
            }
            
            wp_redirect(add_query_arg('resent', '1', wp_get_referer()));
            
        } catch (Exception $e) {
            wp_redirect(add_query_arg('error', 'resend_failed', wp_get_referer()));
        }
        
        exit;
    }
    
    public function cancel_sponsorship(): void {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cfk_cancel_sponsorship') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'cfk-sponsorship'));
        }
        
        $session_id = sanitize_text_field($_GET['session_id'] ?? '');
        $this->cancel_sponsorship_by_session($session_id);
        
        wp_redirect(add_query_arg('cancelled', '1', wp_get_referer()));
        exit;
    }
    
    private function cancel_sponsorship_by_session(string $session_id): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        // Get children in this sponsorship
        $children = $this->get_confirmed_children($session_id);
        
        // Mark children as unsponsored
        foreach ($children as $child) {
            CFK_Children_Manager::mark_as_sponsored($child->id, false);
        }
        
        // Update sponsorship status
        $wpdb->update(
            $table_name,
            ['status' => CFK_Status::CANCELLED->value],
            ['session_id' => $session_id, 'status' => CFK_Status::CONFIRMED->value],
            ['%s'],
            ['%s', '%s']
        );
    }
    
    private function get_sponsorship_data(string $session_id): ?object {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s AND status = %s LIMIT 1",
            $session_id,
            CFK_Status::CONFIRMED->value
        ));
    }
    
    private function get_confirmed_children(string $session_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        $child_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT child_id FROM $table_name WHERE session_id = %s AND status = %s",
            $session_id,
            CFK_Status::CONFIRMED->value
        ));
        
        $children = [];
        foreach ($child_ids as $child_id) {
            $child_post = CFK_Children_Manager::get_child_by_id($child_id);
            if ($child_post) {
                $selected_child = CFK_SelectedChild::from_post($child_post);
                if ($selected_child) {
                    $children[] = $selected_child;
                }
            }
        }
        
        return $children;
    }
    
    // Public interface methods for frontend integration
    public function get_current_selection_count(): int {
        if (!session_id()) {
            return 0;
        }
        
        $session_id = $this->get_session_id();
        return $this->get_selection_count($session_id);
    }
    
    public function get_current_selected_children(): array {
        if (!session_id()) {
            return [];
        }
        
        $session_id = $this->get_session_id();
        return $this->get_selected_children($session_id);
    }
    
    public function is_child_selected_by_current_user(string $child_id): bool {
        if (!session_id()) {
            return false;
        }
        
        $session_id = $this->get_session_id();
        $selected_children = $this->get_selected_children($session_id);
        
        return array_filter($selected_children, fn($child) => $child->id === $child_id) !== [];
    }
    
    public function get_all_selected_child_ids(): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        return $wpdb->get_col($wpdb->prepare(
            "SELECT child_id FROM $table_name WHERE status IN (%s, %s)",
            CFK_Status::SELECTED->value,
            CFK_Status::CONFIRMED->value
        ));
    }
    
    public function force_cleanup(): void {
        $this->cleanup_abandoned_selections();
    }
    
    public function get_sponsorship_by_session(string $session_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s ORDER BY selected_time DESC",
            $session_id
        ));
    }
    
    public function get_total_sponsorship_value(): float {
        $stats = $this->get_sponsorship_stats();
        return $stats->total_value;
    }
    
    public function get_recent_activity(int $limit = 10): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT s.*, 
                   COUNT(s2.child_id) as total_children
            FROM $table_name s
            LEFT JOIN $table_name s2 ON s.session_id = s2.session_id AND s2.status = %s
            WHERE s.status = %s
            GROUP BY s.session_id
            ORDER BY s.confirmed_time DESC
            LIMIT %d
        ", CFK_Status::CONFIRMED->value, CFK_Status::CONFIRMED->value, $limit));
    }
    
    public function export_sponsorships_csv(): void {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cfk_export_sponsorships') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'cfk-sponsorship'));
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        $sponsorships = $wpdb->get_results($wpdb->prepare("
            SELECT s.session_id, s.sponsor_name, s.sponsor_email, s.sponsor_phone, 
                   s.sponsor_address, s.confirmed_time, s.child_id
            FROM $table_name s
            WHERE s.status = %s
            ORDER BY s.confirmed_time DESC, s.session_id
        ", CFK_Status::CONFIRMED->value));
        
        $filename = 'cfk-sponsorships-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'Session ID',
            'Sponsor Name',
            'Sponsor Email',
            'Sponsor Phone',
            'Sponsor Address',
            'Confirmed Date',
            'Child ID'
        ]);
        
        // CSV data
        foreach ($sponsorships as $sponsorship) {
            fputcsv($output, [
                $sponsorship->session_id,
                $sponsorship->sponsor_name,
                $sponsorship->sponsor_email,
                $sponsorship->sponsor_phone,
                $sponsorship->sponsor_address,
                $sponsorship->confirmed_time,
                $sponsorship->child_id
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    // Handler methods for compatibility with main plugin AJAX registration
    public function handle_child_selection(): void {
        $this->ajax_select_child();
    }
    
    public function handle_sponsorship_confirmation(): void {
        $this->ajax_confirm_sponsorship();
    }
    
    public function handle_cancellation(): void {
        try {
            check_ajax_referer('cfk_admin_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => __('Insufficient permissions', 'cfk-sponsorship')]);
            }
            
            $session_id = sanitize_text_field($_POST['session_id'] ?? '');
            
            if (empty($session_id)) {
                wp_send_json_error(['message' => __('Invalid session ID', 'cfk-sponsorship')]);
            }
            
            $this->cancel_sponsorship_by_session($session_id);
            
            wp_send_json_success([
                'message' => __('Sponsorship cancelled successfully', 'cfk-sponsorship')
            ]);
            
        } catch (Exception $e) {
            error_log('CFK Sponsorship Cancellation Error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    // Advanced analytics methods
    public function get_sponsorship_analytics(): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        // Monthly sponsorship trends
        $monthly_data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE_FORMAT(confirmed_time, '%%Y-%%m') as month,
                COUNT(DISTINCT session_id) as sponsors,
                COUNT(*) as children
            FROM $table_name 
            WHERE status = %s 
            AND confirmed_time >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month
        ", CFK_Status::CONFIRMED->value));
        
        // Top sponsors by children count
        $top_sponsors = $wpdb->get_results($wpdb->prepare("
            SELECT 
                sponsor_name,
                sponsor_email,
                COUNT(*) as children_count,
                confirmed_time
            FROM $table_name 
            WHERE status = %s
            GROUP BY session_id, sponsor_name, sponsor_email
            ORDER BY children_count DESC, confirmed_time DESC
            LIMIT 10
        ", CFK_Status::CONFIRMED->value));
        
        // Peak selection times
        $peak_times = $wpdb->get_results($wpdb->prepare("
            SELECT 
                HOUR(confirmed_time) as hour,
                COUNT(*) as confirmations
            FROM $table_name 
            WHERE status = %s
            GROUP BY hour
            ORDER BY confirmations DESC
        ", CFK_Status::CONFIRMED->value));
        
        return [
            'monthly_trends' => $monthly_data,
            'top_sponsors' => $top_sponsors,
            'peak_times' => $peak_times,
            'stats' => $this->get_sponsorship_stats()
        ];
    }
    
    // Validation methods
    public function validate_sponsorship_limits(string $session_id, CFK_SponsorData $sponsor_data): array {
        $errors = [];
        
        // Check maximum children per sponsor
        if ($this->config->max_children_per_sponsor > 0) {
            $current_count = $this->get_selection_count($session_id);
            if ($current_count > $this->config->max_children_per_sponsor) {
                $errors[] = sprintf(
                    __('Maximum %d children allowed per sponsor', 'cfk-sponsorship'),
                    $this->config->max_children_per_sponsor
                );
            }
        }
        
        // Check duplicate emails if not allowed
        if (!$this->config->allow_duplicate_emails) {
            $existing = $this->check_existing_sponsor_email($sponsor_data->email);
            if ($existing) {
                $errors[] = __('This email address has already been used for a sponsorship', 'cfk-sponsorship');
            }
        }
        
        return $errors;
    }
    
    private function check_existing_sponsor_email(string $email): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE sponsor_email = %s AND status = %s",
            $email,
            CFK_Status::CONFIRMED->value
        ));
        
        return $count > 0;
    }
    
    // Session management improvements
    public function clear_session_selections(string $session_id): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        $result = $wpdb->delete(
            $table_name,
            [
                'session_id' => $session_id,
                'status' => CFK_Status::SELECTED->value
            ],
            ['%s', '%s']
        );
        
        return $result !== false;
    }
    
    public function get_session_summary(string $session_id): array {
        $selected_children = $this->get_selected_children($session_id);
        $total_value = count($selected_children) * $this->config->average_sponsorship_value;
        
        return [
            'children_count' => count($selected_children),
            'children' => array_map(fn($child) => $child->to_array(), $selected_children),
            'estimated_value' => $total_value,
            'session_id' => $session_id
        ];
    }
}

?>