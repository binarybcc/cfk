<?php
declare(strict_types=1);

/**
 * Public-facing functionality of the plugin
 * 
 * Handles frontend display of children available for sponsorship,
 * shortcode registration, and public interactions.
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 */
class CFK_Public {
    
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
     * Initialize public functionality
     * 
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        // Register shortcodes
        add_action('init', [$this, 'register_shortcodes']);
        
        // Enqueue public scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Handle AJAX for non-logged in users
        add_action('wp_ajax_nopriv_cfk_sponsor_child', [$this, 'handle_sponsor_child']);
        add_action('wp_ajax_cfk_sponsor_child', [$this, 'handle_sponsor_child']);
    }
    
    /**
     * Register shortcodes
     * 
     * @since 1.0.0
     * @return void
     */
    public function register_shortcodes(): void {
        add_shortcode('cfk_children', [$this, 'render_children_shortcode']);
    }
    
    /**
     * Enqueue public-facing stylesheets and scripts
     * 
     * @since 1.0.0
     * @return void
     */
    public function enqueue_scripts(): void {
        // Only enqueue on pages that contain our shortcode or are known to need it
        global $post;
        
        $should_enqueue = false;
        
        // Check if current post contains our shortcode
        if ($post && has_shortcode($post->post_content, 'cfk_children')) {
            $should_enqueue = true;
        }
        
        // Check if this is a page that might have our shortcode in a template
        if (is_front_page() || is_home()) {
            $should_enqueue = true;
        }
        
        if (!$should_enqueue) {
            return;
        }
        
        // Enqueue public styles
        wp_enqueue_style(
            'cfk-public-styles',
            CFK_PLUGIN_URL . 'public/css/cfk-public.css',
            [],
            $this->plugin->get_version()
        );
        
        // Enqueue public scripts
        wp_enqueue_script(
            'cfk-public-scripts',
            CFK_PLUGIN_URL . 'public/js/cfk-public.js',
            ['jquery'],
            $this->plugin->get_version(),
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('cfk-public-scripts', 'cfk_public_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cfk_public_nonce'),
            'messages' => [
                'loading' => __('Loading...', CFK_TEXT_DOMAIN),
                'error' => __('An error occurred. Please try again.', CFK_TEXT_DOMAIN),
                'success' => __('Thank you! Your sponsorship selection has been submitted.', CFK_TEXT_DOMAIN),
                'confirm_sponsor' => __('Are you ready to sponsor this child?', CFK_TEXT_DOMAIN)
            ]
        ]);
    }
    
    /**
     * Render the children shortcode
     * 
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string HTML output
     */
    public function render_children_shortcode(array $atts = [], string $content = ''): string {
        // Parse shortcode attributes with defaults
        $atts = shortcode_atts([
            'columns' => 3,
            'per_page' => 12,
            'show_filters' => 'true',
            'show_search' => 'true',
            'order' => 'random',
            'age_min' => 0,
            'age_max' => 18,
            'gender' => '', // empty means all genders
            'class' => 'cfk-children-grid'
        ], $atts, 'cfk_children');
        
        // Sanitize attributes
        $columns = absint($atts['columns']);
        $per_page = absint($atts['per_page']);
        $show_filters = filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN);
        $show_search = filter_var($atts['show_search'], FILTER_VALIDATE_BOOLEAN);
        $order = sanitize_text_field($atts['order']);
        $age_min = absint($atts['age_min']);
        $age_max = absint($atts['age_max']);
        $gender = sanitize_text_field($atts['gender']);
        $css_class = sanitize_html_class($atts['class']);
        
        // Ensure valid columns (1-6)
        $columns = max(1, min(6, $columns));
        
        // Ensure valid per_page (1-50)
        $per_page = max(1, min(50, $per_page));
        
        // Get current page for pagination
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        
        try {
            // Get available children
            $children_query = $this->get_children_query([
                'posts_per_page' => $per_page,
                'paged' => $paged,
                'order' => $order,
                'age_min' => $age_min,
                'age_max' => $age_max,
                'gender' => $gender
            ]);
            
            ob_start();
            
            echo '<div class="cfk-children-wrapper ' . esc_attr($css_class) . '">';
            
            // Render filters if enabled
            if ($show_filters || $show_search) {
                $this->render_children_filters($show_search, $show_filters);
            }
            
            // Render children grid
            if ($children_query->have_posts()) {
                echo '<div class="cfk-children-grid" data-columns="' . esc_attr($columns) . '">';
                
                while ($children_query->have_posts()) {
                    $children_query->the_post();
                    $this->render_child_card(get_the_ID());
                }
                
                echo '</div>';
                
                // Render pagination
                $this->render_pagination($children_query, $paged);
                
            } else {
                echo '<div class="cfk-no-children">';
                echo '<p>' . esc_html__('No children are currently available for sponsorship.', CFK_TEXT_DOMAIN) . '</p>';
                echo '</div>';
            }
            
            echo '</div>';
            
            wp_reset_postdata();
            
            return ob_get_clean();
            
        } catch (Exception $e) {
            // Log error and show user-friendly message
            error_log('[CFK] Error rendering children shortcode: ' . $e->getMessage());
            
            return '<div class="cfk-error">' . 
                   '<p>' . esc_html__('Unable to load children at this time. Please try again later.', CFK_TEXT_DOMAIN) . '</p>' .
                   '</div>';
        }
    }
    
    /**
     * Get children query based on parameters
     * 
     * @since 1.0.0
     * @param array $args Query arguments
     * @return WP_Query
     */
    private function get_children_query(array $args): WP_Query {
        $query_args = [
            'post_type' => CFK_Child_Manager::get_post_type(),
            'post_status' => 'publish',
            'posts_per_page' => $args['posts_per_page'],
            'paged' => $args['paged'],
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_cfk_availability_status',
                    'value' => 'available',
                    'compare' => '='
                ]
            ]
        ];
        
        // Handle ordering
        switch ($args['order']) {
            case 'age_asc':
                $query_args['meta_key'] = '_cfk_age';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'ASC';
                break;
                
            case 'age_desc':
                $query_args['meta_key'] = '_cfk_age';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'DESC';
                break;
                
            case 'name':
                $query_args['orderby'] = 'title';
                $query_args['order'] = 'ASC';
                break;
                
            case 'random':
            default:
                $query_args['orderby'] = 'rand';
                break;
        }
        
        // Add age range filter
        if ($args['age_min'] > 0 || $args['age_max'] < 18) {
            $query_args['meta_query'][] = [
                'key' => '_cfk_age',
                'value' => [$args['age_min'], $args['age_max']],
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            ];
        }
        
        // Add gender filter
        if (!empty($args['gender'])) {
            $query_args['meta_query'][] = [
                'key' => '_cfk_gender',
                'value' => $args['gender'],
                'compare' => '='
            ];
        }
        
        return new WP_Query($query_args);
    }
    
    /**
     * Render filters and search for children
     * 
     * @since 1.0.0
     * @param bool $show_search Whether to show search
     * @param bool $show_filters Whether to show filters
     * @return void
     */
    private function render_children_filters(bool $show_search, bool $show_filters): void {
        echo '<div class="cfk-children-filters">';
        
        if ($show_search) {
            echo '<div class="cfk-search-container">';
            echo '<input type="text" id="cfk-child-search" placeholder="' . 
                 esc_attr__('Search children by name...', CFK_TEXT_DOMAIN) . '">';
            echo '</div>';
        }
        
        if ($show_filters) {
            echo '<div class="cfk-filter-container">';
            
            // Age filter
            echo '<select id="cfk-age-filter">';
            echo '<option value="">' . esc_html__('All Ages', CFK_TEXT_DOMAIN) . '</option>';
            echo '<option value="0-5">' . esc_html__('0-5 years', CFK_TEXT_DOMAIN) . '</option>';
            echo '<option value="6-10">' . esc_html__('6-10 years', CFK_TEXT_DOMAIN) . '</option>';
            echo '<option value="11-15">' . esc_html__('11-15 years', CFK_TEXT_DOMAIN) . '</option>';
            echo '<option value="16-18">' . esc_html__('16-18 years', CFK_TEXT_DOMAIN) . '</option>';
            echo '</select>';
            
            // Gender filter
            echo '<select id="cfk-gender-filter">';
            echo '<option value="">' . esc_html__('All Genders', CFK_TEXT_DOMAIN) . '</option>';
            echo '<option value="male">' . esc_html__('Boys', CFK_TEXT_DOMAIN) . '</option>';
            echo '<option value="female">' . esc_html__('Girls', CFK_TEXT_DOMAIN) . '</option>';
            echo '</select>';
            
            // Sort filter
            echo '<select id="cfk-sort-filter">';
            echo '<option value="random">' . esc_html__('Random Order', CFK_TEXT_DOMAIN) . '</option>';
            echo '<option value="age_asc">' . esc_html__('Youngest First', CFK_TEXT_DOMAIN) . '</option>';
            echo '<option value="age_desc">' . esc_html__('Oldest First', CFK_TEXT_DOMAIN) . '</option>';
            echo '<option value="name">' . esc_html__('Name (A-Z)', CFK_TEXT_DOMAIN) . '</option>';
            echo '</select>';
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render a single child card
     * 
     * @since 1.0.0
     * @param int $child_id The child post ID
     * @return void
     */
    private function render_child_card(int $child_id): void {
        $child_data = $this->get_child_display_data($child_id);
        
        echo '<div class="cfk-child-card" data-child-id="' . esc_attr($child_id) . '">';
        
        // Child photo
        echo '<div class="cfk-child-photo">';
        if ($child_data['photo_url']) {
            echo '<img src="' . esc_url($child_data['photo_url']) . '" ' .
                 'alt="' . esc_attr($child_data['name']) . '" ' .
                 'loading="lazy">';
        } else {
            echo '<div class="cfk-child-photo-placeholder">';
            echo '<span class="cfk-photo-icon">📷</span>';
            echo '</div>';
        }
        echo '</div>';
        
        // Child information
        echo '<div class="cfk-child-info">';
        echo '<h3 class="cfk-child-name">' . esc_html($child_data['name']) . '</h3>';
        
        echo '<div class="cfk-child-details">';
        echo '<span class="cfk-child-age">' . 
             sprintf(esc_html__('Age: %d', CFK_TEXT_DOMAIN), $child_data['age']) . 
             '</span>';
        
        if (!empty($child_data['interests'])) {
            echo '<div class="cfk-child-interests">';
            echo '<strong>' . esc_html__('Interests:', CFK_TEXT_DOMAIN) . '</strong> ';
            echo esc_html($child_data['interests']);
            echo '</div>';
        }
        echo '</div>';
        
        // Sponsor button
        echo '<div class="cfk-child-actions">';
        echo '<button type="button" class="cfk-sponsor-btn" data-child-id="' . esc_attr($child_id) . '">';
        echo esc_html__('Sponsor This Child', CFK_TEXT_DOMAIN);
        echo '</button>';
        echo '</div>';
        
        echo '</div>'; // .cfk-child-info
        echo '</div>'; // .cfk-child-card
    }
    
    /**
     * Get display data for a child
     * 
     * @since 1.0.0
     * @param int $child_id The child post ID
     * @return array Child display data
     */
    private function get_child_display_data(int $child_id): array {
        $post = get_post($child_id);
        
        $data = [
            'name' => get_the_title($child_id),
            'age' => (int) get_post_meta($child_id, '_cfk_age', true),
            'gender' => get_post_meta($child_id, '_cfk_gender', true),
            'interests' => get_post_meta($child_id, '_cfk_interests', true),
            'photo_url' => get_the_post_thumbnail_url($child_id, 'medium'),
            'description' => wp_trim_words($post->post_content, 30)
        ];
        
        // Ensure we have a valid age
        if ($data['age'] <= 0) {
            $data['age'] = 0;
        }
        
        return $data;
    }
    
    /**
     * Render pagination for children listing
     * 
     * @since 1.0.0
     * @param WP_Query $query The children query
     * @param int $paged Current page
     * @return void
     */
    private function render_pagination(WP_Query $query, int $paged): void {
        if ($query->max_num_pages <= 1) {
            return;
        }
        
        echo '<div class="cfk-pagination">';
        
        echo paginate_links([
            'total' => $query->max_num_pages,
            'current' => $paged,
            'format' => '?paged=%#%',
            'show_all' => false,
            'end_size' => 1,
            'mid_size' => 2,
            'prev_next' => true,
            'prev_text' => '&laquo; ' . __('Previous', CFK_TEXT_DOMAIN),
            'next_text' => __('Next', CFK_TEXT_DOMAIN) . ' &raquo;',
            'type' => 'list'
        ]);
        
        echo '</div>';
    }
    
    /**
     * Handle sponsor child AJAX request
     * 
     * @since 1.0.0
     * @return void
     */
    public function handle_sponsor_child(): void {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cfk_public_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', CFK_TEXT_DOMAIN)]);
        }
        
        // Sanitize input
        $child_id = absint($_POST['child_id'] ?? 0);
        $sponsor_name = sanitize_text_field($_POST['sponsor_name'] ?? '');
        $sponsor_email = sanitize_email($_POST['sponsor_email'] ?? '');
        $sponsor_phone = sanitize_text_field($_POST['sponsor_phone'] ?? '');
        
        // Validate input
        if (empty($child_id) || empty($sponsor_name) || empty($sponsor_email)) {
            wp_send_json_error([
                'message' => __('Please provide all required information.', CFK_TEXT_DOMAIN)
            ]);
        }
        
        if (!is_email($sponsor_email)) {
            wp_send_json_error([
                'message' => __('Please provide a valid email address.', CFK_TEXT_DOMAIN)
            ]);
        }
        
        // Check if child exists and is available
        $child_post = get_post($child_id);
        if (!$child_post || $child_post->post_type !== CFK_Child_Manager::get_post_type()) {
            wp_send_json_error([
                'message' => __('Child not found.', CFK_TEXT_DOMAIN)
            ]);
        }
        
        $availability = get_post_meta($child_id, '_cfk_availability_status', true);
        if ($availability !== 'available') {
            wp_send_json_error([
                'message' => __('This child is no longer available for sponsorship.', CFK_TEXT_DOMAIN)
            ]);
        }
        
        try {
            // Create sponsorship record (this would typically involve the Sponsorship Manager)
            $sponsorship_data = [
                'child_id' => $child_id,
                'sponsor_name' => $sponsor_name,
                'sponsor_email' => $sponsor_email,
                'sponsor_phone' => $sponsor_phone,
                'status' => 'selected',
                'selection_token' => wp_generate_password(32, false)
            ];
            
            // For now, we'll simulate this - in a full implementation,
            // this would use the Sponsorship Manager
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'cfk_sponsorships';
            $inserted = $wpdb->insert(
                $table_name,
                $sponsorship_data,
                ['%d', '%s', '%s', '%s', '%s', '%s']
            );
            
            if ($inserted === false) {
                throw new Exception('Failed to create sponsorship record');
            }
            
            // Mark child as temporarily reserved
            update_post_meta($child_id, '_cfk_availability_status', 'selected');
            update_post_meta($child_id, '_cfk_selected_at', current_time('mysql'));
            
            wp_send_json_success([
                'message' => __('Thank you! Your sponsorship selection has been submitted. You will receive a confirmation email shortly.', CFK_TEXT_DOMAIN),
                'child_name' => get_the_title($child_id)
            ]);
            
        } catch (Exception $e) {
            error_log('[CFK] Error creating sponsorship: ' . $e->getMessage());
            
            wp_send_json_error([
                'message' => __('An error occurred while processing your request. Please try again.', CFK_TEXT_DOMAIN)
            ]);
        }
    }
}