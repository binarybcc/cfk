<?php
declare(strict_types=1);

/**
 * Child Management System for Christmas for Kids
 * 
 * Handles the custom post type registration for children profiles,
 * manages custom meta fields, and provides admin interface for
 * child data management.
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 */
class CFK_Child_Manager {
    
    /**
     * Custom post type name
     * 
     * @since 1.0.0
     * @var string
     */
    private const POST_TYPE = 'cfk_child';
    
    /**
     * Meta fields for child data
     * 
     * @since 1.0.0
     * @var array<string, array<string, mixed>>
     */
    private const META_FIELDS = [
        'age' => [
            'type' => 'number',
            'label' => 'Age',
            'required' => true,
            'min' => 0,
            'max' => 18
        ],
        'gender' => [
            'type' => 'select',
            'label' => 'Gender',
            'required' => true,
            'options' => ['M' => 'Male', 'F' => 'Female']
        ],
        'shirt_size' => [
            'type' => 'select',
            'label' => 'Shirt Size',
            'required' => false,
            'options' => [
                'Youth XS' => 'Youth XS',
                'Youth S' => 'Youth S',
                'Youth M' => 'Youth M',
                'Youth L' => 'Youth L',
                'Youth XL' => 'Youth XL',
                'Adult XS' => 'Adult XS',
                'Adult S' => 'Adult S',
                'Adult M' => 'Adult M',
                'Adult L' => 'Adult L',
                'Adult XL' => 'Adult XL'
            ]
        ],
        'pants_size' => [
            'type' => 'text',
            'label' => 'Pants Size',
            'required' => false
        ],
        'shoe_size' => [
            'type' => 'text',
            'label' => 'Shoe Size',
            'required' => false
        ],
        'coat_size' => [
            'type' => 'select',
            'label' => 'Coat Size',
            'required' => false,
            'options' => [
                '2T' => '2T', '3T' => '3T', '4T' => '4T',
                '4/5' => '4/5', '6/7' => '6/7', '8/10' => '8/10',
                '10/12' => '10/12', '14/16' => '14/16', '18/20' => '18/20',
                'Youth S' => 'Youth S', 'Youth M' => 'Youth M', 
                'Youth L' => 'Youth L', 'Youth XL' => 'Youth XL'
            ]
        ],
        'interests' => [
            'type' => 'textarea',
            'label' => 'Interests',
            'required' => false
        ],
        'family_situation' => [
            'type' => 'textarea',
            'label' => 'Family Situation',
            'required' => false
        ],
        'special_needs' => [
            'type' => 'textarea',
            'label' => 'Special Needs',
            'required' => false
        ]
    ];
    
    /**
     * Initialize the child manager
     * 
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_data'], 10, 2);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'add_list_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'populate_list_columns'], 10, 2);
    }
    
    /**
     * Register the child custom post type
     * 
     * @since 1.0.0
     * @return void
     */
    public function register_post_type(): void {
        $labels = [
            'name' => __('Children', CFK_TEXT_DOMAIN),
            'singular_name' => __('Child', CFK_TEXT_DOMAIN),
            'menu_name' => __('Children', CFK_TEXT_DOMAIN),
            'add_new' => __('Add New Child', CFK_TEXT_DOMAIN),
            'add_new_item' => __('Add New Child', CFK_TEXT_DOMAIN),
            'edit_item' => __('Edit Child', CFK_TEXT_DOMAIN),
            'new_item' => __('New Child', CFK_TEXT_DOMAIN),
            'view_item' => __('View Child', CFK_TEXT_DOMAIN),
            'search_items' => __('Search Children', CFK_TEXT_DOMAIN),
            'not_found' => __('No children found', CFK_TEXT_DOMAIN),
            'not_found_in_trash' => __('No children found in trash', CFK_TEXT_DOMAIN),
            'all_items' => __('All Children', CFK_TEXT_DOMAIN),
        ];
        
        $args = [
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => false, // We'll add it to our custom menu
            'show_in_admin_bar' => false,
            'capability_type' => 'post',
            'capabilities' => [
                'edit_post' => 'manage_options',
                'read_post' => 'manage_options',
                'delete_post' => 'manage_options',
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'delete_posts' => 'manage_options',
                'publish_posts' => 'manage_options',
                'read_private_posts' => 'manage_options',
            ],
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'thumbnail'],
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'menu_icon' => 'dashicons-groups',
        ];
        
        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Add meta boxes for child details
     * 
     * @since 1.0.0
     * @return void
     */
    public function add_meta_boxes(): void {
        add_meta_box(
            'cfk_child_details',
            __('Child Details', CFK_TEXT_DOMAIN),
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }
    
    /**
     * Render the child details meta box
     * 
     * @since 1.0.0
     * @param WP_Post $post Current post object
     * @return void
     */
    public function render_meta_box(WP_Post $post): void {
        // Security nonce
        wp_nonce_field('cfk_save_child_meta', 'cfk_child_meta_nonce');
        
        // Include the meta box template
        $meta_values = $this->get_child_meta($post->ID);
        include CFK_PLUGIN_DIR . '/admin/partials/child-meta-box.php';
    }
    
    /**
     * Save child meta data
     * 
     * @since 1.0.0
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @return void
     */
    public function save_meta_data(int $post_id, WP_Post $post): void {
        // Skip for non-child posts
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['cfk_child_meta_nonce']) || 
            !wp_verify_nonce($_POST['cfk_child_meta_nonce'], 'cfk_save_child_meta')) {
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Skip autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        
        $this->save_child_meta_fields($post_id);
    }
    
    /**
     * Save individual meta fields with validation
     * 
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return void
     */
    private function save_child_meta_fields(int $post_id): void {
        foreach (self::META_FIELDS as $field_name => $field_config) {
            $field_key = 'cfk_child_' . $field_name;
            $value = $_POST[$field_key] ?? '';
            
            // Validate and sanitize the value
            $sanitized_value = $this->sanitize_field_value($value, $field_config);
            
            // Validate required fields
            if ($field_config['required'] && empty($sanitized_value)) {
                continue; // Skip saving empty required fields
            }
            
            // Save the meta value
            update_post_meta($post_id, $field_key, $sanitized_value);
        }
        
        // Log successful save
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[CFK-info] Child meta data saved for post ID: {$post_id}");
        }
    }
    
    /**
     * Sanitize field value based on field type
     * 
     * @since 1.0.0
     * @param mixed $value Field value
     * @param array $field_config Field configuration
     * @return string Sanitized value
     */
    private function sanitize_field_value($value, array $field_config): string {
        switch ($field_config['type']) {
            case 'number':
                $sanitized = intval($value);
                if (isset($field_config['min']) && $sanitized < $field_config['min']) {
                    return '';
                }
                if (isset($field_config['max']) && $sanitized > $field_config['max']) {
                    return '';
                }
                return (string) $sanitized;
                
            case 'select':
                if (isset($field_config['options'][$value])) {
                    return sanitize_text_field($value);
                }
                return '';
                
            case 'textarea':
                return sanitize_textarea_field($value);
                
            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Get child meta data
     * 
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return array<string, mixed> Meta values
     */
    public function get_child_meta(int $post_id): array {
        $meta = [];
        
        foreach (self::META_FIELDS as $field_name => $field_config) {
            $field_key = 'cfk_child_' . $field_name;
            $meta[$field_name] = get_post_meta($post_id, $field_key, true);
        }
        
        return $meta;
    }
    
    /**
     * Add custom columns to children list table
     * 
     * @since 1.0.0
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_list_columns(array $columns): array {
        // Remove date column and add our custom columns
        unset($columns['date']);
        
        $columns['age'] = __('Age', CFK_TEXT_DOMAIN);
        $columns['gender'] = __('Gender', CFK_TEXT_DOMAIN);
        $columns['sponsored'] = __('Sponsored', CFK_TEXT_DOMAIN);
        $columns['date'] = __('Date', CFK_TEXT_DOMAIN);
        
        return $columns;
    }
    
    /**
     * Populate custom columns in children list table
     * 
     * @since 1.0.0
     * @param string $column Column name
     * @param int $post_id Post ID
     * @return void
     */
    public function populate_list_columns(string $column, int $post_id): void {
        switch ($column) {
            case 'age':
                echo esc_html(get_post_meta($post_id, 'cfk_child_age', true));
                break;
                
            case 'gender':
                $gender = get_post_meta($post_id, 'cfk_child_gender', true);
                echo esc_html($gender === 'M' ? 'Male' : ($gender === 'F' ? 'Female' : ''));
                break;
                
            case 'sponsored':
                $sponsored = $this->is_child_sponsored($post_id);
                echo $sponsored ? 
                    '<span style="color: green;">✓ ' . __('Yes', CFK_TEXT_DOMAIN) . '</span>' : 
                    '<span style="color: #999;">○ ' . __('No', CFK_TEXT_DOMAIN) . '</span>';
                break;
        }
    }
    
    /**
     * Check if a child is sponsored
     * 
     * @since 1.0.0
     * @param int $child_id Child post ID
     * @return bool True if sponsored
     */
    public function is_child_sponsored(int $child_id): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE child_id = %d AND status = 'confirmed'",
                $child_id
            )
        );
        
        return (int) $result > 0;
    }
    
    /**
     * Get all available children for sponsorship
     * 
     * @since 1.0.0
     * @return array<WP_Post> Available children
     */
    public function get_available_children(): array {
        $args = [
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'cfk_child_age',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        
        $children = get_posts($args);
        
        // Filter out sponsored children
        return array_filter($children, function($child) {
            return !$this->is_child_sponsored($child->ID);
        });
    }
    
    /**
     * Get meta field configuration
     * 
     * @since 1.0.0
     * @return array<string, array<string, mixed>> Meta fields configuration
     */
    public static function get_meta_fields(): array {
        return self::META_FIELDS;
    }
    
    /**
     * Get post type name
     * 
     * @since 1.0.0
     * @return string Post type name
     */
    public static function get_post_type(): string {
        return self::POST_TYPE;
    }
}