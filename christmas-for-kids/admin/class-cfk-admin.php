<?php
declare(strict_types=1);

/**
 * Admin functionality for Christmas for Kids plugin
 * 
 * Handles admin interface, custom columns, meta boxes,
 * and administrative functionality.
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 */
class CFK_Admin {
    
    /**
     * The plugin's main instance
     * 
     * @since 1.0.0
     * @var Christmas_For_Kids
     */
    private Christmas_For_Kids $plugin;
    
    /**
     * Initialize the class and set its properties
     * 
     * @since 1.0.0
     * @param Christmas_For_Kids $plugin The main plugin instance
     */
    public function __construct(Christmas_For_Kids $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Initialize admin functionality
     * 
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        // Admin hooks
        add_action('admin_init', [$this, 'admin_init']);
        
        // Custom columns for child post type
        add_filter('manage_' . CFK_Child_Manager::get_post_type() . '_posts_columns', [$this, 'add_child_columns']);
        add_action('manage_' . CFK_Child_Manager::get_post_type() . '_posts_custom_column', [$this, 'populate_child_columns'], 10, 2);
        add_filter('manage_edit-' . CFK_Child_Manager::get_post_type() . '_sortable_columns', [$this, 'make_child_columns_sortable']);
        
        // Custom column sorting
        add_action('pre_get_posts', [$this, 'handle_child_column_sorting']);
        
        // Meta boxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_child_meta'], 10, 2);
        
        // Admin notices
        add_action('admin_notices', [$this, 'show_admin_notices']);
        
        // Bulk actions
        add_filter('bulk_actions-edit-' . CFK_Child_Manager::get_post_type(), [$this, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-edit-' . CFK_Child_Manager::get_post_type(), [$this, 'handle_bulk_actions'], 10, 3);
        
        // Quick edit
        add_action('quick_edit_custom_box', [$this, 'add_quick_edit_fields'], 10, 2);
        add_action('wp_ajax_cfk_quick_edit_save', [$this, 'handle_quick_edit_save']);
        
        // Admin footer
        add_filter('admin_footer_text', [$this, 'admin_footer_text']);
    }
    
    /**
     * Admin initialization
     * 
     * @since 1.0.0
     * @return void
     */
    public function admin_init(): void {
        // Register settings if needed in the future
        // This is where we'd register plugin settings pages
    }
    
    /**
     * Add custom columns to child post type admin list
     * 
     * @since 1.0.0
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_child_columns(array $columns): array {
        // Remove date column and add our custom columns
        unset($columns['date']);
        
        $new_columns = [];
        
        // Keep checkbox and title
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        
        // Add child photo
        $new_columns['child_photo'] = __('Photo', CFK_TEXT_DOMAIN);
        
        // Add family information (NEW)
        $new_columns['family_info'] = __('Family', CFK_TEXT_DOMAIN);
        
        // Add child details
        $new_columns['child_age'] = __('Age', CFK_TEXT_DOMAIN);
        $new_columns['child_gender'] = __('Gender', CFK_TEXT_DOMAIN);
        $new_columns['child_availability'] = __('Availability', CFK_TEXT_DOMAIN);
        $new_columns['child_interests'] = __('Interests', CFK_TEXT_DOMAIN);
        
        // Add date back at the end
        $new_columns['date'] = __('Date Added', CFK_TEXT_DOMAIN);
        
        return $new_columns;
    }
    
    /**
     * Populate custom columns with data
     * 
     * @since 1.0.0
     * @param string $column_name Name of the column
     * @param int $post_id Post ID
     * @return void
     */
    public function populate_child_columns(string $column_name, int $post_id): void {
        switch ($column_name) {
            case 'child_photo':
                if (has_post_thumbnail($post_id)) {
                    echo '<img src="' . esc_url(get_the_post_thumbnail_url($post_id, 'thumbnail')) . '" ' .
                         'style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" ' .
                         'alt="' . esc_attr(get_the_title($post_id)) . '">';
                } else {
                    echo '<span style="display: inline-block; width: 50px; height: 50px; background: #f0f0f0; ' .
                         'border-radius: 4px; text-align: center; line-height: 50px; font-size: 20px;">📷</span>';
                }
                break;
                
            case 'family_info':
                $family_id = get_post_meta($post_id, 'cfk_child_family_id', true);
                $family_name = get_post_meta($post_id, 'cfk_child_family_name', true);
                $family_number = get_post_meta($post_id, 'cfk_child_family_number', true);
                
                if (!empty($family_id)) {
                    echo '<div class="cfk-family-info">';
                    echo '<strong>' . esc_html($family_id) . '</strong><br>';
                    
                    if (!empty($family_name)) {
                        echo '<small>' . esc_html($family_name) . '</small><br>';
                    }
                    
                    // Show sibling count
                    if (!empty($family_number)) {
                        $child_manager = $this->plugin->get_component('child_manager');
                        $siblings = $child_manager->get_family_siblings($family_number);
                        $sibling_count = count($siblings) - 1; // Exclude current child
                        
                        if ($sibling_count > 0) {
                            echo '<small style="color: #666;">' . 
                                sprintf(_n('%d sibling', '%d siblings', $sibling_count, CFK_TEXT_DOMAIN), $sibling_count) . 
                                '</small>';
                        }
                    }
                    
                    echo '</div>';
                } else {
                    echo '<em style="color: #999;">' . esc_html__('No family', CFK_TEXT_DOMAIN) . '</em>';
                }
                break;
                
            case 'child_age':
                $age = get_post_meta($post_id, '_cfk_age', true);
                echo $age ? esc_html($age . ' years') : '—';
                break;
                
            case 'child_gender':
                $gender = get_post_meta($post_id, '_cfk_gender', true);
                if ($gender) {
                    $gender_label = ($gender === 'male') ? __('Boy', CFK_TEXT_DOMAIN) : __('Girl', CFK_TEXT_DOMAIN);
                    echo '<span class="cfk-gender-' . esc_attr($gender) . '">' . esc_html($gender_label) . '</span>';
                } else {
                    echo '—';
                }
                break;
                
            case 'child_availability':
                $availability = get_post_meta($post_id, '_cfk_availability_status', true);
                $status_labels = [
                    'available' => ['label' => __('Available', CFK_TEXT_DOMAIN), 'color' => '#00a32a'],
                    'selected' => ['label' => __('Selected', CFK_TEXT_DOMAIN), 'color' => '#ff8c00'],
                    'sponsored' => ['label' => __('Sponsored', CFK_TEXT_DOMAIN), 'color' => '#0073aa'],
                    'unavailable' => ['label' => __('Unavailable', CFK_TEXT_DOMAIN), 'color' => '#d63638']
                ];
                
                if (isset($status_labels[$availability])) {
                    $status = $status_labels[$availability];
                    echo '<span style="display: inline-block; padding: 3px 8px; border-radius: 3px; ' .
                         'background: ' . esc_attr($status['color']) . '; color: white; font-size: 11px; font-weight: 500;">' .
                         esc_html($status['label']) . '</span>';
                } else {
                    echo '—';
                }
                break;
                
            case 'child_interests':
                $interests = get_post_meta($post_id, '_cfk_interests', true);
                if ($interests) {
                    $truncated = wp_trim_words($interests, 5);
                    echo '<span title="' . esc_attr($interests) . '">' . esc_html($truncated) . '</span>';
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Make custom columns sortable
     * 
     * @since 1.0.0
     * @param array $columns Sortable columns
     * @return array Modified sortable columns
     */
    public function make_child_columns_sortable(array $columns): array {
        $columns['child_age'] = 'child_age';
        $columns['child_gender'] = 'child_gender';
        $columns['child_availability'] = 'child_availability';
        
        return $columns;
    }
    
    /**
     * Handle sorting for custom columns
     * 
     * @since 1.0.0
     * @param WP_Query $query The query object
     * @return void
     */
    public function handle_child_column_sorting(WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        switch ($orderby) {
            case 'child_age':
                $query->set('meta_key', '_cfk_age');
                $query->set('orderby', 'meta_value_num');
                break;
                
            case 'child_gender':
                $query->set('meta_key', '_cfk_gender');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'child_availability':
                $query->set('meta_key', '_cfk_availability_status');
                $query->set('orderby', 'meta_value');
                break;
        }
    }
    
    /**
     * Add meta boxes for child posts
     * 
     * @since 1.0.0
     * @return void
     */
    public function add_meta_boxes(): void {
        add_meta_box(
            'cfk-child-details',
            __('Child Details', CFK_TEXT_DOMAIN),
            [$this, 'render_child_details_meta_box'],
            CFK_Child_Manager::get_post_type(),
            'normal',
            'high'
        );
        
        add_meta_box(
            'cfk-child-availability',
            __('Availability Status', CFK_TEXT_DOMAIN),
            [$this, 'render_availability_meta_box'],
            CFK_Child_Manager::get_post_type(),
            'side',
            'high'
        );
        
        add_meta_box(
            'cfk-child-sponsorship',
            __('Sponsorship Information', CFK_TEXT_DOMAIN),
            [$this, 'render_sponsorship_meta_box'],
            CFK_Child_Manager::get_post_type(),
            'side',
            'default'
        );
    }
    
    /**
     * Render child details meta box
     * 
     * @since 1.0.0
     * @param WP_Post $post The post object
     * @return void
     */
    public function render_child_details_meta_box(WP_Post $post): void {
        wp_nonce_field('cfk_child_meta', 'cfk_child_meta_nonce');
        
        $age = get_post_meta($post->ID, '_cfk_age', true);
        $gender = get_post_meta($post->ID, '_cfk_gender', true);
        $school_grade = get_post_meta($post->ID, '_cfk_school_grade', true);
        $interests = get_post_meta($post->ID, '_cfk_interests', true);
        $special_needs = get_post_meta($post->ID, '_cfk_special_needs', true);
        $clothing_size = get_post_meta($post->ID, '_cfk_clothing_size', true);
        $shoe_size = get_post_meta($post->ID, '_cfk_shoe_size', true);
        
        include CFK_PLUGIN_PATH . 'admin/partials/child-details-meta-box.php';
    }
    
    /**
     * Render availability status meta box
     * 
     * @since 1.0.0
     * @param WP_Post $post The post object
     * @return void
     */
    public function render_availability_meta_box(WP_Post $post): void {
        $availability = get_post_meta($post->ID, '_cfk_availability_status', true);
        if (empty($availability)) {
            $availability = 'available';
        }
        
        $selected_at = get_post_meta($post->ID, '_cfk_selected_at', true);
        $sponsored_at = get_post_meta($post->ID, '_cfk_sponsored_at', true);
        
        include CFK_PLUGIN_PATH . 'admin/partials/availability-meta-box.php';
    }
    
    /**
     * Render sponsorship information meta box
     * 
     * @since 1.0.0
     * @param WP_Post $post The post object
     * @return void
     */
    public function render_sponsorship_meta_box(WP_Post $post): void {
        global $wpdb;
        
        // Get sponsorship records
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        $sponsorships = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE child_id = %d ORDER BY created_at DESC",
            $post->ID
        ));
        
        include CFK_PLUGIN_PATH . 'admin/partials/sponsorship-meta-box.php';
    }
    
    /**
     * Save child meta data
     * 
     * @since 1.0.0
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @return void
     */
    public function save_child_meta(int $post_id, WP_Post $post): void {
        // Check if this is a child post
        if ($post->post_type !== CFK_Child_Manager::get_post_type()) {
            return;
        }
        
        // Check nonce
        if (!isset($_POST['cfk_child_meta_nonce']) || 
            !wp_verify_nonce($_POST['cfk_child_meta_nonce'], 'cfk_child_meta')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save meta fields
        $meta_fields = [
            '_cfk_age' => 'absint',
            '_cfk_gender' => 'sanitize_text_field',
            '_cfk_school_grade' => 'sanitize_text_field',
            '_cfk_interests' => 'sanitize_textarea_field',
            '_cfk_special_needs' => 'sanitize_textarea_field',
            '_cfk_clothing_size' => 'sanitize_text_field',
            '_cfk_shoe_size' => 'sanitize_text_field',
            '_cfk_availability_status' => 'sanitize_text_field'
        ];
        
        foreach ($meta_fields as $meta_key => $sanitize_callback) {
            if (isset($_POST[$meta_key])) {
                $value = call_user_func($sanitize_callback, $_POST[$meta_key]);
                update_post_meta($post_id, $meta_key, $value);
            }
        }
        
        // Handle availability status changes
        $new_availability = sanitize_text_field($_POST['_cfk_availability_status'] ?? '');
        $old_availability = get_post_meta($post_id, '_cfk_availability_status', true);
        
        if ($new_availability !== $old_availability) {
            if ($new_availability === 'sponsored' && $old_availability !== 'sponsored') {
                update_post_meta($post_id, '_cfk_sponsored_at', current_time('mysql'));
            }
            
            // Log the change
            error_log(sprintf(
                '[CFK] Child %d availability changed from %s to %s',
                $post_id,
                $old_availability,
                $new_availability
            ));
        }
    }
    
    /**
     * Show admin notices
     * 
     * @since 1.0.0
     * @return void
     */
    public function show_admin_notices(): void {
        $screen = get_current_screen();
        
        // Show notice on child list page if no children exist
        if ($screen && $screen->id === 'edit-' . CFK_Child_Manager::get_post_type()) {
            $child_count = wp_count_posts(CFK_Child_Manager::get_post_type())->publish;
            
            if ($child_count == 0) {
                echo '<div class="notice notice-info">';
                echo '<p>' . esc_html__('No children have been added yet.', CFK_TEXT_DOMAIN) . ' ';
                echo '<a href="' . admin_url('post-new.php?post_type=' . CFK_Child_Manager::get_post_type()) . '">' . 
                     esc_html__('Add your first child', CFK_TEXT_DOMAIN) . '</a> ' .
                     esc_html__('or', CFK_TEXT_DOMAIN) . ' ';
                echo '<a href="' . admin_url('admin.php?page=cfk-import-csv') . '">' . 
                     esc_html__('import from CSV', CFK_TEXT_DOMAIN) . '</a>.';
                echo '</p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Add bulk actions
     * 
     * @since 1.0.0
     * @param array $actions Existing bulk actions
     * @return array Modified bulk actions
     */
    public function add_bulk_actions(array $actions): array {
        $actions['cfk_make_available'] = __('Make Available', CFK_TEXT_DOMAIN);
        $actions['cfk_make_unavailable'] = __('Make Unavailable', CFK_TEXT_DOMAIN);
        $actions['cfk_export_csv'] = __('Export to CSV', CFK_TEXT_DOMAIN);
        
        return $actions;
    }
    
    /**
     * Handle bulk actions
     * 
     * @since 1.0.0
     * @param string $redirect_url The redirect URL
     * @param string $action The action being taken
     * @param array $post_ids Array of post IDs
     * @return string Modified redirect URL
     */
    public function handle_bulk_actions(string $redirect_url, string $action, array $post_ids): string {
        if (empty($post_ids)) {
            return $redirect_url;
        }
        
        $count = 0;
        
        switch ($action) {
            case 'cfk_make_available':
                foreach ($post_ids as $post_id) {
                    update_post_meta($post_id, '_cfk_availability_status', 'available');
                    $count++;
                }
                $redirect_url = add_query_arg('cfk_bulk_made_available', $count, $redirect_url);
                break;
                
            case 'cfk_make_unavailable':
                foreach ($post_ids as $post_id) {
                    update_post_meta($post_id, '_cfk_availability_status', 'unavailable');
                    $count++;
                }
                $redirect_url = add_query_arg('cfk_bulk_made_unavailable', $count, $redirect_url);
                break;
                
            case 'cfk_export_csv':
                // This would trigger a CSV export
                $this->export_children_csv($post_ids);
                $redirect_url = add_query_arg('cfk_bulk_exported', count($post_ids), $redirect_url);
                break;
        }
        
        return $redirect_url;
    }
    
    /**
     * Add quick edit fields
     * 
     * @since 1.0.0
     * @param string $column_name Column name
     * @param string $post_type Post type
     * @return void
     */
    public function add_quick_edit_fields(string $column_name, string $post_type): void {
        if ($post_type !== CFK_Child_Manager::get_post_type()) {
            return;
        }
        
        switch ($column_name) {
            case 'child_age':
                echo '<fieldset class="inline-edit-col-right">';
                echo '<div class="inline-edit-col">';
                echo '<label class="alignleft">';
                echo '<span class="title">' . esc_html__('Age', CFK_TEXT_DOMAIN) . '</span>';
                echo '<input type="number" name="_cfk_age" min="0" max="25" value="">';
                echo '</label>';
                echo '</div>';
                echo '</fieldset>';
                break;
                
            case 'child_availability':
                echo '<fieldset class="inline-edit-col-right">';
                echo '<div class="inline-edit-col">';
                echo '<label class="alignleft">';
                echo '<span class="title">' . esc_html__('Availability', CFK_TEXT_DOMAIN) . '</span>';
                echo '<select name="_cfk_availability_status">';
                echo '<option value="available">' . esc_html__('Available', CFK_TEXT_DOMAIN) . '</option>';
                echo '<option value="selected">' . esc_html__('Selected', CFK_TEXT_DOMAIN) . '</option>';
                echo '<option value="sponsored">' . esc_html__('Sponsored', CFK_TEXT_DOMAIN) . '</option>';
                echo '<option value="unavailable">' . esc_html__('Unavailable', CFK_TEXT_DOMAIN) . '</option>';
                echo '</select>';
                echo '</label>';
                echo '</div>';
                echo '</fieldset>';
                break;
        }
    }
    
    /**
     * Handle quick edit save via AJAX
     * 
     * @since 1.0.0
     * @return void
     */
    public function handle_quick_edit_save(): void {
        if (!wp_verify_nonce($_POST['_inline_edit'] ?? '', 'inlineeditnonce')) {
            wp_die(__('Cheatin&#8217; uh?', CFK_TEXT_DOMAIN));
        }
        
        $post_id = absint($_POST['post_ID'] ?? 0);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die(__('Sorry, you are not allowed to edit this item.', CFK_TEXT_DOMAIN));
        }
        
        // Save age
        if (isset($_POST['_cfk_age'])) {
            $age = absint($_POST['_cfk_age']);
            update_post_meta($post_id, '_cfk_age', $age);
        }
        
        // Save availability
        if (isset($_POST['_cfk_availability_status'])) {
            $availability = sanitize_text_field($_POST['_cfk_availability_status']);
            update_post_meta($post_id, '_cfk_availability_status', $availability);
        }
        
        wp_die();
    }
    
    /**
     * Export children to CSV
     * 
     * @since 1.0.0
     * @param array $post_ids Array of post IDs to export
     * @return void
     */
    private function export_children_csv(array $post_ids): void {
        if (empty($post_ids)) {
            return;
        }
        
        $filename = 'cfk-children-export-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        $headers = [
            'ID',
            'Name',
            'Age',
            'Gender',
            'School Grade',
            'Interests',
            'Special Needs',
            'Clothing Size',
            'Shoe Size',
            'Availability Status',
            'Date Added'
        ];
        
        fputcsv($output, $headers);
        
        // Export data
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) continue;
            
            $row = [
                $post->ID,
                $post->post_title,
                get_post_meta($post_id, '_cfk_age', true),
                get_post_meta($post_id, '_cfk_gender', true),
                get_post_meta($post_id, '_cfk_school_grade', true),
                get_post_meta($post_id, '_cfk_interests', true),
                get_post_meta($post_id, '_cfk_special_needs', true),
                get_post_meta($post_id, '_cfk_clothing_size', true),
                get_post_meta($post_id, '_cfk_shoe_size', true),
                get_post_meta($post_id, '_cfk_availability_status', true),
                $post->post_date
            ];
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Customize admin footer text
     * 
     * @since 1.0.0
     * @param string $text Current footer text
     * @return string Modified footer text
     */
    public function admin_footer_text(string $text): string {
        $screen = get_current_screen();
        
        if ($screen && (strpos($screen->id, 'christmas-for-kids') !== false || 
                       strpos($screen->id, CFK_Child_Manager::get_post_type()) !== false)) {
            return sprintf(
                __('Thank you for using %s to help children in need.', CFK_TEXT_DOMAIN),
                '<strong>Christmas for Kids</strong>'
            );
        }
        
        return $text;
    }
}