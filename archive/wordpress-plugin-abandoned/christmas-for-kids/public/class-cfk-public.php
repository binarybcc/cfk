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
     * Render the children shortcode (simplified version)
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
            'show_filters' => 'false',
            'show_search' => 'false',
            'order' => 'title',
            'age_min' => 0,
            'age_max' => 18,
            'gender' => '',
            'class' => 'cfk-children-grid'
        ], $atts, 'cfk_children');
        
        // Sanitize attributes
        $columns = max(1, min(6, absint($atts['columns'])));
        $per_page = max(1, min(50, absint($atts['per_page'])));
        $show_filters = filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN);
        $show_search = filter_var($atts['show_search'], FILTER_VALIDATE_BOOLEAN);
        $order = sanitize_text_field($atts['order']);
        $age_min = absint($atts['age_min']);
        $age_max = absint($atts['age_max']);
        $gender = sanitize_text_field($atts['gender']);
        $css_class = sanitize_html_class($atts['class']);
        
        // Get current page for pagination
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        
        try {
            // Get children
            $query_args = [
                'posts_per_page' => $per_page,
                'paged' => $paged,
                'order' => $order,
                'age_min' => $age_min,
                'age_max' => $age_max,
                'gender' => $gender
            ];
            
            $query = $this->get_children_query($query_args);
            
            ob_start();
            
            echo '<div class="cfk-children-wrapper ' . esc_attr($css_class) . '">';
            
            // Render filters if enabled
            if ($show_filters || $show_search) {
                $this->render_children_filters($show_search, $show_filters);
            }
            
            // Render children grid
            if ($query->have_posts()) {
                echo '<div class="cfk-children-grid" data-columns="' . esc_attr($columns) . '">';
                
                while ($query->have_posts()) {
                    $query->the_post();
                    $this->render_child_card(get_the_ID());
                }
                
                echo '</div>';
                
                // Render pagination
                $this->render_pagination($query, $paged);
                
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
            'post_type' => 'cfk_child',
            'post_status' => 'publish',
            'posts_per_page' => $args['posts_per_page'],
            'paged' => $args['paged']
        ];
        
        // Handle ordering
        switch ($args['order']) {
            case 'age_asc':
                $query_args['meta_key'] = 'cfk_child_age';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'ASC';
                break;
                
            case 'age_desc':
                $query_args['meta_key'] = 'cfk_child_age';
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
                'key' => 'cfk_child_age',
                'value' => [$args['age_min'], $args['age_max']],
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            ];
        }
        
        // Add gender filter
        if (!empty($args['gender'])) {
            $query_args['meta_query'][] = [
                'key' => 'cfk_child_gender',
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
        
        if ($child_data['age'] > 0) {
            echo '<span class="cfk-child-age">' . 
                 sprintf(esc_html__('Age: %d', CFK_TEXT_DOMAIN), $child_data['age']) . 
                 '</span>';
        }
        
        if (!empty($child_data['gender'])) {
            echo '<span class="cfk-child-gender">' . esc_html($child_data['gender']) . '</span>';
        }
        
        if (!empty($child_data['grade'])) {
            echo '<div class="cfk-child-grade">';
            echo '<strong>' . esc_html__('Grade:', CFK_TEXT_DOMAIN) . '</strong> ';
            echo esc_html($child_data['grade']);
            echo '</div>';
        }
        
        if (!empty($child_data['interests'])) {
            echo '<div class="cfk-child-interests">';
            echo '<strong>' . esc_html__('Wishes:', CFK_TEXT_DOMAIN) . '</strong> ';
            echo esc_html($child_data['interests']);
            echo '</div>';
        }
        
        // Family information
        if (!empty($child_data['family_number']) || !empty($child_data['family_name'])) {
            echo '<div class="cfk-child-family">';
            echo '<strong>' . esc_html__('Family:', CFK_TEXT_DOMAIN) . '</strong> ';
            if (!empty($child_data['family_number'])) {
                echo esc_html($child_data['family_number']);
                if (!empty($child_data['family_name'])) {
                    echo ' - ' . esc_html($child_data['family_name']);
                }
            } else {
                echo esc_html($child_data['family_name']);
            }
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
            'age' => (int) get_post_meta($child_id, 'cfk_child_age', true),
            'gender' => get_post_meta($child_id, 'cfk_child_gender', true),
            'interests' => get_post_meta($child_id, 'cfk_child_wishes', true),
            'photo_url' => get_the_post_thumbnail_url($child_id, 'medium'),
            'description' => wp_trim_words($post->post_content, 30),
            'grade' => get_post_meta($child_id, 'cfk_child_grade', true),
            'school' => get_post_meta($child_id, 'cfk_child_school', true),
            'family_number' => get_post_meta($child_id, 'cfk_child_family_number', true),
            'family_name' => get_post_meta($child_id, 'cfk_child_family_name', true)
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
    
    /**
     * Get children data with family context
     * 
     * @since 1.2.0
     * @param array<string, mixed> $args Query arguments
     * @return array<string, mixed> Children data with family context
     */
    private function get_children_with_family_context(array $args): array {
        $child_manager = $this->plugin->get_component('child_manager');
        
        // Handle family search
        if (!empty($args['family_search'])) {
            $children_posts = $child_manager->search_children_by_family(
                $args['family_search'], 
                $args['posts_per_page']
            );
        } else {
            // Standard query with family awareness
            $query_args = [
                'post_type' => CFK_Child_Manager::get_post_type(),
                'post_status' => 'publish',
                'posts_per_page' => $args['posts_per_page'],
                'paged' => $args['paged'],
                'orderby' => $args['order'] === 'random' ? 'rand' : 'title',
                'order' => $args['order'] === 'random' ? '' : 'ASC'
            ];
            
            // Add age and gender filters
            if (!empty($args['age_min']) || !empty($args['age_max'])) {
                $query_args['meta_query'][] = [
                    'key' => 'cfk_child_age',
                    'value' => [$args['age_min'], $args['age_max']],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                ];
            }
            
            if (!empty($args['gender'])) {
                $query_args['meta_query'][] = [
                    'key' => 'cfk_child_gender',
                    'value' => $args['gender'],
                    'compare' => '='
                ];
            }
            
            $query = new WP_Query($query_args);
            $children_posts = $query->posts;
        }
        
        // Process children with family context
        $children_data = [];
        $family_stats = [];
        
        foreach ($children_posts as $child) {
            $family_id = get_post_meta($child->ID, 'cfk_child_family_id', true);
            $family_number = get_post_meta($child->ID, 'cfk_child_family_number', true);
            
            $child_data = [
                'post' => $child,
                'family_id' => $family_id,
                'family_number' => $family_number,
                'family_name' => get_post_meta($child->ID, 'cfk_child_family_name', true),
                'age' => get_post_meta($child->ID, 'cfk_child_age', true),
                'gender' => get_post_meta($child->ID, 'cfk_child_gender', true),
                'interests' => get_post_meta($child->ID, 'cfk_child_interests', true),
                'is_sponsored' => $child_manager->is_child_sponsored($child->ID)
            ];
            
            // Get siblings if enabled
            if ($args['show_siblings'] && !empty($family_number)) {
                $siblings = $child_manager->get_family_siblings($family_number);
                $child_data['siblings'] = array_filter($siblings, function($sibling) use ($child) {
                    return $sibling->ID !== $child->ID;
                });
                
                // Cache family stats
                if (!isset($family_stats[$family_number])) {
                    $family_stats[$family_number] = $child_manager->get_family_stats($family_number);
                }
            }
            
            $children_data[] = $child_data;
        }
        
        return [
            'children' => $children_data,
            'family_stats' => $family_stats,
            'pagination' => [
                'total' => count($children_posts),
                'per_page' => $args['posts_per_page'],
                'current_page' => $args['paged']
            ]
        ];
    }
    
    /**
     * Render enhanced filters with family search support
     * 
     * @since 1.2.0
     * @param array<string, mixed> $filter_options Filter options
     * @return void
     */
    private function render_enhanced_filters(array $filter_options): void {
        echo '<div class="cfk-filters-wrapper">';
        
        echo '<form class="cfk-filters-form" method="get">';
        
        // Regular search
        if ($filter_options['show_search']) {
            echo '<div class="cfk-search-field">';
            echo '<label for="cfk_search">' . esc_html__('Search Children', CFK_TEXT_DOMAIN) . '</label>';
            echo '<input type="text" id="cfk_search" name="cfk_search" placeholder="' . 
                esc_attr__('Search by name, interests...', CFK_TEXT_DOMAIN) . '" value="' . 
                esc_attr($_GET['cfk_search'] ?? '') . '">';
            echo '</div>';
        }
        
        // Family search
        if ($filter_options['family_search']) {
            echo '<div class="cfk-family-search-field">';
            echo '<label for="cfk_family_search">' . esc_html__('Family Search', CFK_TEXT_DOMAIN) . '</label>';
            echo '<input type="text" id="cfk_family_search" name="cfk_family_search" placeholder="' . 
                esc_attr__('Family ID (e.g., 123A) or family name', CFK_TEXT_DOMAIN) . '" value="' . 
                esc_attr($_GET['cfk_family_search'] ?? '') . '">';
            echo '</div>';
        }
        
        // Regular filters
        if ($filter_options['show_filters']) {
            echo '<div class="cfk-filters-grid">';
            
            // Age filter
            echo '<div class="cfk-filter-field">';
            echo '<label for="cfk_age_min">' . esc_html__('Age Range', CFK_TEXT_DOMAIN) . '</label>';
            echo '<select id="cfk_age_min" name="cfk_age_min">';
            echo '<option value="">' . esc_html__('Any Age', CFK_TEXT_DOMAIN) . '</option>';
            for ($age = 0; $age <= 18; $age++) {
                $selected = selected($_GET['cfk_age_min'] ?? '', $age, false);
                echo "<option value='{$age}' {$selected}>{$age}+</option>";
            }
            echo '</select>';
            echo '</div>';
            
            // Gender filter
            echo '<div class="cfk-filter-field">';
            echo '<label for="cfk_gender">' . esc_html__('Gender', CFK_TEXT_DOMAIN) . '</label>';
            echo '<select id="cfk_gender" name="cfk_gender">';
            echo '<option value="">' . esc_html__('Any Gender', CFK_TEXT_DOMAIN) . '</option>';
            echo '<option value="M"' . selected($_GET['cfk_gender'] ?? '', 'M', false) . '>' . 
                esc_html__('Male', CFK_TEXT_DOMAIN) . '</option>';
            echo '<option value="F"' . selected($_GET['cfk_gender'] ?? '', 'F', false) . '>' . 
                esc_html__('Female', CFK_TEXT_DOMAIN) . '</option>';
            echo '</select>';
            echo '</div>';
            
            echo '</div>';
        }
        
        // View toggles for family display
        if ($filter_options['family_view'] === 'both') {
            echo '<div class="cfk-view-toggles">';
            echo '<label>' . esc_html__('Display Mode:', CFK_TEXT_DOMAIN) . '</label>';
            echo '<button type="button" class="cfk-view-toggle" data-view="individual">' . 
                esc_html__('Individual Children', CFK_TEXT_DOMAIN) . '</button>';
            echo '<button type="button" class="cfk-view-toggle" data-view="grouped">' . 
                esc_html__('Family Groups', CFK_TEXT_DOMAIN) . '</button>';
            echo '</div>';
        }
        
        echo '<div class="cfk-filter-actions">';
        echo '<button type="submit" class="cfk-filter-submit">' . esc_html__('Apply Filters', CFK_TEXT_DOMAIN) . '</button>';
        echo '<button type="button" class="cfk-filter-reset">' . esc_html__('Reset', CFK_TEXT_DOMAIN) . '</button>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
    }
    
    /**
     * Render family statistics
     * 
     * @since 1.2.0
     * @param array<string, mixed> $family_stats Family statistics
     * @return void
     */
    private function render_family_statistics(array $family_stats): void {
        if (empty($family_stats)) {
            return;
        }
        
        echo '<div class="cfk-family-stats">';
        echo '<h3>' . esc_html__('Family Statistics', CFK_TEXT_DOMAIN) . '</h3>';
        echo '<div class="cfk-stats-grid">';
        
        $total_families = count($family_stats);
        $total_children = array_sum(array_column($family_stats, 'total_children'));
        $sponsored_children = array_sum(array_column($family_stats, 'sponsored_count'));
        
        echo '<div class="cfk-stat">';
        echo '<span class="cfk-stat-number">' . esc_html($total_families) . '</span>';
        echo '<span class="cfk-stat-label">' . esc_html__('Families', CFK_TEXT_DOMAIN) . '</span>';
        echo '</div>';
        
        echo '<div class="cfk-stat">';
        echo '<span class="cfk-stat-number">' . esc_html($total_children) . '</span>';
        echo '<span class="cfk-stat-label">' . esc_html__('Children', CFK_TEXT_DOMAIN) . '</span>';
        echo '</div>';
        
        echo '<div class="cfk-stat">';
        echo '<span class="cfk-stat-number">' . esc_html($sponsored_children) . '</span>';
        echo '<span class="cfk-stat-label">' . esc_html__('Sponsored', CFK_TEXT_DOMAIN) . '</span>';
        echo '</div>';
        
        echo '<div class="cfk-stat">';
        echo '<span class="cfk-stat-number">' . esc_html($total_children - $sponsored_children) . '</span>';
        echo '<span class="cfk-stat-label">' . esc_html__('Available', CFK_TEXT_DOMAIN) . '</span>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render family-aware child card with sibling information
     * 
     * @since 1.2.0
     * @param array<string, mixed> $child_data Child data with family context
     * @param bool $show_siblings Whether to show sibling information
     * @return void
     */
    private function render_family_aware_child_card(array $child_data, bool $show_siblings = true): void {
        $child = $child_data['post'];
        $child_id = $child->ID;
        
        echo '<div class="cfk-child-card" data-child-id="' . esc_attr($child_id) . '" 
                  data-family-id="' . esc_attr($child_data['family_id']) . '">';
        
        // Child photo
        if (has_post_thumbnail($child_id)) {
            echo '<div class="cfk-child-photo">';
            echo get_the_post_thumbnail($child_id, 'medium', [
                'alt' => sprintf(__('%s - Child Photo', CFK_TEXT_DOMAIN), $child->post_title),
                'loading' => 'lazy'
            ]);
            echo '</div>';
        } else {
            echo '<div class="cfk-child-photo cfk-no-photo">';
            echo '<div class="cfk-photo-placeholder">';
            echo '<span>' . esc_html__('No Photo', CFK_TEXT_DOMAIN) . '</span>';
            echo '</div>';
            echo '</div>';
        }
        
        // Child information
        echo '<div class="cfk-child-info">';
        echo '<h3 class="cfk-child-name">' . esc_html($child->post_title) . '</h3>';
        
        // Family ID badge
        if (!empty($child_data['family_id'])) {
            echo '<div class="cfk-family-badge">';
            echo '<span class="cfk-family-id">ID: ' . esc_html($child_data['family_id']) . '</span>';
            if (!empty($child_data['family_name'])) {
                echo '<span class="cfk-family-name">' . esc_html($child_data['family_name']) . '</span>';
            }
            echo '</div>';
        }
        
        // Basic info
        echo '<div class="cfk-child-details">';
        echo '<span class="cfk-child-age">' . sprintf(__('%d years old', CFK_TEXT_DOMAIN), $child_data['age']) . '</span>';
        
        if (!empty($child_data['gender'])) {
            $gender_label = $child_data['gender'] === 'M' ? __('Male', CFK_TEXT_DOMAIN) : __('Female', CFK_TEXT_DOMAIN);
            echo '<span class="cfk-child-gender">' . esc_html($gender_label) . '</span>';
        }
        echo '</div>';
        
        // Interests
        if (!empty($child_data['interests'])) {
            echo '<div class="cfk-child-interests">';
            echo '<strong>' . esc_html__('Interests:', CFK_TEXT_DOMAIN) . '</strong> ';
            echo esc_html($child_data['interests']);
            echo '</div>';
        }
        
        // Sibling information
        if ($show_siblings && !empty($child_data['siblings'])) {
            echo '<div class="cfk-siblings-info">';
            echo '<strong>' . esc_html__('Siblings:', CFK_TEXT_DOMAIN) . '</strong>';
            echo '<ul class="cfk-siblings-list">';
            
            foreach ($child_data['siblings'] as $sibling) {
                $sibling_age = get_post_meta($sibling->ID, 'cfk_child_age', true);
                $sibling_family_id = get_post_meta($sibling->ID, 'cfk_child_family_id', true);
                $is_sponsored = $this->plugin->get_component('child_manager')->is_child_sponsored($sibling->ID);
                
                $status_class = $is_sponsored ? 'sponsored' : 'available';
                
                echo '<li class="cfk-sibling ' . esc_attr($status_class) . '">';
                echo esc_html($sibling->post_title) . ' (' . esc_html($sibling_age) . ', ' . 
                     esc_html($sibling_family_id) . ')';
                if ($is_sponsored) {
                    echo ' <span class="cfk-sponsored-badge">' . esc_html__('Sponsored', CFK_TEXT_DOMAIN) . '</span>';
                }
                echo '</li>';
            }
            
            echo '</ul>';
            echo '</div>';
        }
        
        echo '</div>'; // .cfk-child-info
        
        // Action buttons
        echo '<div class="cfk-child-actions">';
        
        if ($child_data['is_sponsored']) {
            echo '<div class="cfk-sponsored-notice">';
            echo '<span>' . esc_html__('Already Sponsored', CFK_TEXT_DOMAIN) . '</span>';
            echo '</div>';
        } else {
            echo '<button type="button" class="cfk-sponsor-btn" data-child-id="' . esc_attr($child_id) . '">';
            echo esc_html__('Sponsor This Child', CFK_TEXT_DOMAIN);
            echo '</button>';
            
            // Show family sponsorship option if applicable
            if (!empty($child_data['siblings'])) {
                $available_siblings = array_filter($child_data['siblings'], function($sibling) {
                    return !$this->plugin->get_component('child_manager')->is_child_sponsored($sibling->ID);
                });
                
                if (!empty($available_siblings)) {
                    echo '<button type="button" class="cfk-family-sponsor-btn" data-family-number="' . 
                         esc_attr($child_data['family_number']) . '">';
                    echo esc_html__('Sponsor Family', CFK_TEXT_DOMAIN);
                    echo '</button>';
                }
            }
        }
        
        echo '</div>'; // .cfk-child-actions
        echo '</div>'; // .cfk-child-card
    }
    
    /**
     * Render family groups view
     * 
     * @since 1.2.0
     * @param array<string, mixed> $children_data Children data with family context
     * @param int $columns Number of columns
     * @param bool $show_siblings Whether to show sibling details
     * @return void
     */
    private function render_family_groups(array $children_data, int $columns, bool $show_siblings): void {
        // Group children by family
        $families = [];
        foreach ($children_data['children'] as $child_data) {
            $family_number = $child_data['family_number'];
            if (empty($family_number)) {
                $family_number = 'no_family';
            }
            
            if (!isset($families[$family_number])) {
                $families[$family_number] = [
                    'family_number' => $family_number,
                    'family_name' => $child_data['family_name'],
                    'children' => []
                ];
            }
            
            $families[$family_number]['children'][] = $child_data;
        }
        
        echo '<div class="cfk-family-groups" data-columns="' . esc_attr($columns) . '">';
        
        foreach ($families as $family) {
            echo '<div class="cfk-family-group" data-family-number="' . esc_attr($family['family_number']) . '">';
            
            // Family header
            echo '<div class="cfk-family-header">';
            echo '<h3 class="cfk-family-title">';
            if ($family['family_number'] !== 'no_family') {
                echo esc_html__('Family', CFK_TEXT_DOMAIN) . ' ' . esc_html($family['family_number']);
                if (!empty($family['family_name'])) {
                    echo ' - ' . esc_html($family['family_name']);
                }
            } else {
                echo esc_html__('Individual Children', CFK_TEXT_DOMAIN);
            }
            echo '</h3>';
            
            // Family stats
            $total_children = count($family['children']);
            $sponsored_count = count(array_filter($family['children'], function($child) {
                return $child['is_sponsored'];
            }));
            
            echo '<div class="cfk-family-stats-inline">';
            echo esc_html($total_children) . ' ' . _n('child', 'children', $total_children, CFK_TEXT_DOMAIN);
            if ($sponsored_count > 0) {
                echo ' (' . esc_html($sponsored_count) . ' ' . esc_html__('sponsored', CFK_TEXT_DOMAIN) . ')';
            }
            echo '</div>';
            echo '</div>';
            
            // Family children
            echo '<div class="cfk-family-children-grid">';
            foreach ($family['children'] as $child_data) {
                $this->render_family_aware_child_card($child_data, false); // No siblings in family view
            }
            echo '</div>';
            
            echo '</div>'; // .cfk-family-group
        }
        
        echo '</div>'; // .cfk-family-groups
    }
    
    /**
     * Render family-aware pagination
     * 
     * @since 1.2.0
     * @param array<string, mixed> $pagination Pagination data
     * @param int $current_page Current page
     * @return void
     */
    private function render_family_aware_pagination(array $pagination, int $current_page): void {
        $total_pages = ceil($pagination['total'] / $pagination['per_page']);
        
        if ($total_pages <= 1) {
            return;
        }
        
        echo '<div class="cfk-pagination">';
        
        if ($current_page > 1) {
            echo '<a href="' . esc_url(add_query_arg('paged', $current_page - 1)) . '" class="cfk-prev-page">';
            echo esc_html__('Previous', CFK_TEXT_DOMAIN);
            echo '</a>';
        }
        
        for ($i = 1; $i <= $total_pages; $i++) {
            $class = $i === $current_page ? 'cfk-page-current' : 'cfk-page';
            echo '<a href="' . esc_url(add_query_arg('paged', $i)) . '" class="' . esc_attr($class) . '">';
            echo esc_html($i);
            echo '</a>';
        }
        
        if ($current_page < $total_pages) {
            echo '<a href="' . esc_url(add_query_arg('paged', $current_page + 1)) . '" class="cfk-next-page">';
            echo esc_html__('Next', CFK_TEXT_DOMAIN);
            echo '</a>';
        }
        
        echo '</div>';
    }
}