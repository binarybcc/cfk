<?php
/**
 * Children Manager Class
 * Handles the Child custom post type and related functionality
 *
 * @package ChristmasForKids
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enums for type safety
enum CFK_Gender: string {
    case MALE = 'Male';
    case FEMALE = 'Female';
    
    public function getIcon(): string {
        return match($this) {
            self::MALE => '♂',
            self::FEMALE => '♀'
        };
    }
}

enum CFK_AgeRange: string {
    case INFANT = 'Infant';
    case ELEMENTARY = 'Elementary';
    case MIDDLE_SCHOOL = 'Middle School';
    case HIGH_SCHOOL = 'High School';
}

enum CFK_ColumnType: string {
    case PHOTO = 'child_photo';
    case CHILD_ID = 'child_id';
    case AGE_GENDER = 'age_gender';
    case FAMILY_ID = 'family_id';
    case AGE_RANGE = 'age_range';
    case SPONSORED = 'sponsored';
}

// Readonly data transfer objects
readonly class CFK_ChildDetails {
    public function __construct(
        public string $id,
        public string $name,
        public int $age,
        public CFK_Gender $gender,
        public string $family_id,
        public CFK_AgeRange $age_range,
        public string $clothing_info,
        public string $gift_requests,
        public bool $sponsored,
        public ?string $avatar_url,
        public string $edit_url
    ) {}
}

readonly class CFK_SponsorshipStats {
    public function __construct(
        public int $total_children,
        public int $sponsored_children,
        public int $available_children,
        public int $total_families,
        public float $sponsorship_percentage
    ) {}
}

readonly class CFK_ChildQuery {
    public function __construct(
        public ?CFK_AgeRange $age_range = null,
        public ?CFK_Gender $gender = null,
        public ?bool $sponsored = null,
        public int $per_page = 12,
        public bool $exclude_selected = true,
        public string $search_term = ''
    ) {}
}

class CFK_Children_Manager {
    private array $meta_fields_config;
    
    public function __construct() {
        $this->init_meta_fields_config();
        $this->register_hooks();
    }
    
    private function init_meta_fields_config(): void {
        $this->meta_fields_config = [
            'child_id' => ['sanitize' => 'sanitize_text_field', 'required' => true],
            'child_age' => ['sanitize' => 'intval', 'required' => true],
            'child_gender' => ['sanitize' => 'sanitize_text_field', 'required' => true],
            'child_family_id' => ['sanitize' => 'sanitize_text_field', 'required' => false],
            'child_age_range' => ['sanitize' => 'sanitize_text_field', 'required' => true],
            'child_clothing_info' => ['sanitize' => 'sanitize_textarea_field', 'required' => false],
            'child_gift_requests' => ['sanitize' => 'sanitize_textarea_field', 'required' => false],
            'child_sponsored' => ['sanitize' => 'sanitize_text_field', 'required' => false]
        ];
    }
    
    private function register_hooks(): void {
        add_action('init', $this->register_child_post_type(...));
        add_action('add_meta_boxes', $this->add_child_meta_boxes(...));
        add_action('save_post', $this->save_child_meta(...));
        add_action('manage_child_posts_columns', $this->custom_child_columns(...));
        add_action('manage_child_posts_custom_column', $this->custom_child_column_content(...), 10, 2);
        add_filter('manage_edit-child_sortable_columns', $this->sortable_child_columns(...));
        add_action('pre_get_posts', $this->child_custom_orderby(...));
        
        // AJAX handlers
        add_action('wp_ajax_cfk_export_children', $this->handle_ajax_export(...));
        add_action('wp_ajax_cfk_bulk_update_status', $this->handle_ajax_bulk_update(...));
    }
    
    public function register_child_post_type(): void {
        $labels = [
            'name' => _x('Children', 'Post type general name', 'cfk-sponsorship'),
            'singular_name' => _x('Child', 'Post type singular name', 'cfk-sponsorship'),
            'menu_name' => _x('Children', 'Admin Menu text', 'cfk-sponsorship'),
            'name_admin_bar' => _x('Child', 'Add New on Toolbar', 'cfk-sponsorship'),
            'add_new' => __('Add New', 'cfk-sponsorship'),
            'add_new_item' => __('Add New Child', 'cfk-sponsorship'),
            'new_item' => __('New Child', 'cfk-sponsorship'),
            'edit_item' => __('Edit Child', 'cfk-sponsorship'),
            'view_item' => __('View Child', 'cfk-sponsorship'),
            'all_items' => __('All Children', 'cfk-sponsorship'),
            'search_items' => __('Search Children', 'cfk-sponsorship'),
            'parent_item_colon' => __('Parent Children:', 'cfk-sponsorship'),
            'not_found' => __('No children found.', 'cfk-sponsorship'),
            'not_found_in_trash' => __('No children found in Trash.', 'cfk-sponsorship'),
            'featured_image' => _x('Child Photo', 'Overrides the "Featured Image" phrase', 'cfk-sponsorship'),
            'set_featured_image' => _x('Set child photo', 'Overrides the "Set featured image" phrase', 'cfk-sponsorship'),
            'remove_featured_image' => _x('Remove child photo', 'Overrides the "Remove featured image" phrase', 'cfk-sponsorship'),
            'use_featured_image' => _x('Use as child photo', 'Overrides the "Use as featured image" phrase', 'cfk-sponsorship'),
            'archives' => _x('Child archives', 'The post type archive label', 'cfk-sponsorship'),
            'insert_into_item' => _x('Insert into child', 'Overrides the "Insert into post" phrase', 'cfk-sponsorship'),
            'uploaded_to_this_item' => _x('Uploaded to this child', 'Overrides the "Uploaded to this post" phrase', 'cfk-sponsorship'),
            'filter_items_list' => _x('Filter children list', 'Screen reader text for the filter links', 'cfk-sponsorship'),
            'items_list_navigation' => _x('Children list navigation', 'Screen reader text for the pagination', 'cfk-sponsorship'),
            'items_list' => _x('Children list', 'Screen reader text for the items list', 'cfk-sponsorship'),
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'cfk-dashboard',
            'query_var' => true,
            'rewrite' => ['slug' => 'children'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => ['title', 'thumbnail', 'custom-fields'],
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-groups'
        ];
        
        register_post_type('child', $args);
    }
    
    public function add_child_meta_boxes(): void {
        add_meta_box(
            'child_details',
            __('Child Details', 'cfk-sponsorship'),
            $this->child_details_meta_box(...),
            'child',
            'normal',
            'high'
        );
        
        add_meta_box(
            'child_sponsorship',
            __('Sponsorship Status', 'cfk-sponsorship'),
            $this->child_sponsorship_meta_box(...),
            'child',
            'side',
            'default'
        );
    }
    
    public function child_details_meta_box(WP_Post $post): void {
        wp_nonce_field('save_child_meta', 'child_meta_nonce');
        
        $meta_values = $this->get_meta_values($post->ID);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="child_id"><?php _e('Child ID', 'cfk-sponsorship'); ?></label></th>
                <td><input type="text" id="child_id" name="child_id" value="<?php echo esc_attr($meta_values['child_id']); ?>" placeholder="e.g., 002A" class="regular-text" /></td>
            </tr>
            
            <tr>
                <th><label for="child_age"><?php _e('Age', 'cfk-sponsorship'); ?></label></th>
                <td><input type="number" id="child_age" name="child_age" value="<?php echo esc_attr($meta_values['child_age']); ?>" min="0" max="18" class="small-text" /></td>
            </tr>
            
            <tr>
                <th><label for="child_gender"><?php _e('Gender', 'cfk-sponsorship'); ?></label></th>
                <td>
                    <select id="child_gender" name="child_gender">
                        <option value=""><?php _e('Select Gender', 'cfk-sponsorship'); ?></option>
                        <?php foreach (CFK_Gender::cases() as $gender): ?>
                            <option value="<?php echo esc_attr($gender->value); ?>" <?php selected($meta_values['child_gender'], $gender->value); ?>>
                                <?php echo esc_html($gender->value); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="child_family_id"><?php _e('Family ID', 'cfk-sponsorship'); ?></label></th>
                <td><input type="text" id="child_family_id" name="child_family_id" value="<?php echo esc_attr($meta_values['child_family_id']); ?>" placeholder="e.g., 002" class="regular-text" /></td>
            </tr>
            
            <tr>
                <th><label for="child_age_range"><?php _e('Age Range', 'cfk-sponsorship'); ?></label></th>
                <td>
                    <select id="child_age_range" name="child_age_range">
                        <option value=""><?php _e('Select Age Range', 'cfk-sponsorship'); ?></option>
                        <?php foreach (CFK_AgeRange::cases() as $age_range): ?>
                            <option value="<?php echo esc_attr($age_range->value); ?>" <?php selected($meta_values['child_age_range'], $age_range->value); ?>>
                                <?php echo esc_html($age_range->value); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="child_clothing_info"><?php _e('Clothing Information', 'cfk-sponsorship'); ?></label></th>
                <td>
                    <textarea id="child_clothing_info" name="child_clothing_info" rows="3" cols="50" class="large-text" placeholder="<?php _e('Pants: Size. Shirt: Size. Shoes: Size.', 'cfk-sponsorship'); ?>"><?php echo esc_textarea($meta_values['child_clothing_info']); ?></textarea>
                    <p class="description"><?php _e('Include clothing sizes (pants, shirts, shoes, etc.)', 'cfk-sponsorship'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="child_gift_requests"><?php _e('Gift Requests', 'cfk-sponsorship'); ?></label></th>
                <td>
                    <textarea id="child_gift_requests" name="child_gift_requests" rows="4" cols="50" class="large-text" placeholder="<?php _e('List of requested gifts and toys', 'cfk-sponsorship'); ?>"><?php echo esc_textarea($meta_values['child_gift_requests']); ?></textarea>
                    <p class="description"><?php _e('Child\'s wish list and gift suggestions', 'cfk-sponsorship'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    private function get_meta_values(int $post_id): array {
        $values = [];
        foreach (array_keys($this->meta_fields_config) as $field) {
            $values[$field] = get_post_meta($post_id, '_' . $field, true);
        }
        return $values;
    }
    
    public function child_sponsorship_meta_box(WP_Post $post): void {
        $sponsored = get_post_meta($post->ID, '_child_sponsored', true);
        $sponsor_info = $this->get_sponsor_info($post->ID);
        
        ?>
        <div class="cfk-sponsorship-status">
            <p>
                <label>
                    <input type="checkbox" id="child_sponsored" name="child_sponsored" value="1" <?php checked($sponsored, 1); ?> />
                    <?php _e('This child has been sponsored', 'cfk-sponsorship'); ?>
                </label>
            </p>
            
            <?php if ($sponsor_info): ?>
            <div class="cfk-sponsor-details">
                <h4><?php _e('Sponsor Information', 'cfk-sponsorship'); ?></h4>
                <p><strong><?php _e('Name:', 'cfk-sponsorship'); ?></strong> <?php echo esc_html($sponsor_info->sponsor_name); ?></p>
                <p><strong><?php _e('Email:', 'cfk-sponsorship'); ?></strong> <?php echo esc_html($sponsor_info->sponsor_email); ?></p>
                <?php if ($sponsor_info->sponsor_phone): ?>
                <p><strong><?php _e('Phone:', 'cfk-sponsorship'); ?></strong> <?php echo esc_html($sponsor_info->sponsor_phone); ?></p>
                <?php endif; ?>
                <p><strong><?php _e('Confirmed:', 'cfk-sponsorship'); ?></strong> <?php echo esc_html($sponsor_info->confirmed_time); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
        .cfk-sponsorship-status {
            padding: 10px 0;
        }
        .cfk-sponsor-details {
            background: #f0f6fc;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .cfk-sponsor-details h4 {
            margin: 0 0 10px 0;
            color: #0073aa;
        }
        .cfk-sponsor-details p {
            margin: 5px 0;
            font-size: 13px;
        }
        </style>
        <?php
    }
    
    private function get_sponsor_info(int $post_id): ?object {
        global $wpdb;
        
        $child_id = get_post_meta($post_id, '_child_id', true);
        if (!$child_id) {
            return null;
        }
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE child_id = %s AND status = 'confirmed' ORDER BY confirmed_time DESC LIMIT 1",
            $child_id
        ));
    }
    
    public function save_child_meta(int $post_id): void {
        // Verify nonce
        if (!isset($_POST['child_meta_nonce']) || !wp_verify_nonce($_POST['child_meta_nonce'], 'save_child_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'child') {
            return;
        }
        
        // Validate and save meta fields
        $this->save_validated_meta_fields($post_id);
    }
    
    private function save_validated_meta_fields(int $post_id): void {
        foreach ($this->meta_fields_config as $field => $config) {
            $value = $_POST[$field] ?? '';
            
            match($field) {
                'child_sponsored' => $this->handle_checkbox_field($post_id, $field, $value),
                default => $this->handle_regular_field($post_id, $field, $value, $config['sanitize'])
            };
        }
    }
    
    private function handle_checkbox_field(int $post_id, string $field, mixed $value): void {
        if ($value) {
            update_post_meta($post_id, '_' . $field, '1');
        } else {
            delete_post_meta($post_id, '_' . $field);
        }
    }
    
    private function handle_regular_field(int $post_id, string $field, mixed $value, string $sanitize_function): void {
        $sanitized_value = call_user_func($sanitize_function, $value);
        update_post_meta($post_id, '_' . $field, $sanitized_value);
    }
    
    public function custom_child_columns(array $columns): array {
        return [
            'cb' => $columns['cb'],
            'child_photo' => __('Photo', 'cfk-sponsorship'),
            'title' => $columns['title'],
            'child_id' => __('Child ID', 'cfk-sponsorship'),
            'age_gender' => __('Age/Gender', 'cfk-sponsorship'),
            'family_id' => __('Family', 'cfk-sponsorship'),
            'age_range' => __('Age Range', 'cfk-sponsorship'),
            'sponsored' => __('Sponsored', 'cfk-sponsorship'),
            'date' => $columns['date']
        ];
    }
    
    public function custom_child_column_content(string $column, int $post_id): void {
        $column_type = CFK_ColumnType::tryFrom($column);
        
        match($column_type) {
            CFK_ColumnType::PHOTO => $this->render_photo_column($post_id),
            CFK_ColumnType::CHILD_ID => $this->render_child_id_column($post_id),
            CFK_ColumnType::AGE_GENDER => $this->render_age_gender_column($post_id),
            CFK_ColumnType::FAMILY_ID => $this->render_family_id_column($post_id),
            CFK_ColumnType::AGE_RANGE => $this->render_age_range_column($post_id),
            CFK_ColumnType::SPONSORED => $this->render_sponsored_column($post_id),
            default => null
        };
    }
    
    private function render_photo_column(int $post_id): void {
        if (has_post_thumbnail($post_id)) {
            echo get_the_post_thumbnail($post_id, [50, 50]);
        } else {
            $gender_meta = get_post_meta($post_id, '_child_gender', true);
            $gender = CFK_Gender::tryFrom($gender_meta);
            $icon = $gender?->getIcon() ?? '👤';
            echo '<div style="width:50px;height:50px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border-radius:50%;color:#666;font-size:20px;">' . $icon . '</div>';
        }
    }
    
    private function render_age_gender_column(int $post_id): void {
        $age = get_post_meta($post_id, '_child_age', true);
        $gender = get_post_meta($post_id, '_child_gender', true);
        echo esc_html("$age / $gender");
    }
    
    private function render_child_id_column(int $post_id): void {
        echo esc_html(get_post_meta($post_id, '_child_id', true));
    }
    
    private function render_family_id_column(int $post_id): void {
        echo esc_html(get_post_meta($post_id, '_child_family_id', true));
    }
    
    private function render_age_range_column(int $post_id): void {
        echo esc_html(get_post_meta($post_id, '_child_age_range', true));
    }
    
    private function render_sponsored_column(int $post_id): void {
        $sponsored = get_post_meta($post_id, '_child_sponsored', true);
        
        if ($sponsored === '1') {
            echo '<span style="color: #46b450; font-weight: bold;">✓ ' . __('Yes', 'cfk-sponsorship') . '</span>';
        } else {
            echo '<span style="color: #dc3232;">✗ ' . __('Available', 'cfk-sponsorship') . '</span>';
        }
    }
    
    public function sortable_child_columns(array $columns): array {
        return array_merge($columns, [
            'child_id' => 'child_id',
            'age_gender' => 'age',
            'family_id' => 'family_id',
            'age_range' => 'age_range',
            'sponsored' => 'sponsored'
        ]);
    }
    
    public function child_custom_orderby(WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        $meta_mapping = [
            'child_id' => ['key' => '_child_id', 'type' => 'meta_value'],
            'age' => ['key' => '_child_age', 'type' => 'meta_value_num'],
            'family_id' => ['key' => '_child_family_id', 'type' => 'meta_value'],
            'age_range' => ['key' => '_child_age_range', 'type' => 'meta_value'],
            'sponsored' => ['key' => '_child_sponsored', 'type' => 'meta_value']
        ];
        
        if (isset($meta_mapping[$orderby])) {
            $mapping = $meta_mapping[$orderby];
            $query->set('meta_key', $mapping['key']);
            $query->set('orderby', $mapping['type']);
        }
    }
    
    public static function get_available_children(CFK_ChildQuery $query = new CFK_ChildQuery()): array {
        global $wpdb;
        
        $query_args = [
            'post_type' => 'child',
            'post_status' => 'publish',
            'posts_per_page' => $query->per_page,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    [
                        'key' => '_child_sponsored',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => '_child_sponsored',
                        'value' => '1',
                        'compare' => '!='
                    ]
                ]
            ]
        ];
        
        // Add filters
        if ($query->age_range) {
            $query_args['meta_query'][] = [
                'key' => '_child_age_range',
                'value' => $query->age_range->value,
                'compare' => '='
            ];
        }
        
        if ($query->gender) {
            $query_args['meta_query'][] = [
                'key' => '_child_gender',
                'value' => $query->gender->value,
                'compare' => '='
            ];
        }
        
        if ($query->search_term) {
            $query_args['s'] = $query->search_term;
        }
        
        $children = get_posts($query_args);
        
        // Exclude currently selected children if requested
        if ($query->exclude_selected) {
            $children = $this->filter_selected_children($children);
        }
        
        return $children;
    }
    
    private static function filter_selected_children(array $children): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        $selected_child_ids = $wpdb->get_col(
            "SELECT child_id FROM $table_name WHERE status IN ('selected', 'confirmed')"
        );
        
        if (empty($selected_child_ids)) {
            return $children;
        }
        
        return array_filter($children, function($child) use ($selected_child_ids) {
            $child_id = get_post_meta($child->ID, '_child_id', true);
            return !in_array($child_id, $selected_child_ids, true);
        });
    }
    
    public static function get_child_by_id(string $child_id): ?WP_Post {
        $posts = get_posts([
            'post_type' => 'child',
            'meta_key' => '_child_id',
            'meta_value' => $child_id,
            'posts_per_page' => 1
        ]);
        
        return $posts[0] ?? null;
    }
    
    public static function get_children_by_family(string $family_id): array {
        return get_posts([
            'post_type' => 'child',
            'meta_key' => '_child_family_id',
            'meta_value' => $family_id,
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => '_child_age',
            'order' => 'DESC'
        ]);
    }
    
    public static function get_sponsorship_stats(): CFK_SponsorshipStats {
        $total_children = wp_count_posts('child')->publish;
        
        $sponsored_children = get_posts([
            'post_type' => 'child',
            'meta_key' => '_child_sponsored',
            'meta_value' => '1',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        $sponsored_count = count($sponsored_children);
        $available_count = $total_children - $sponsored_count;
        
        // Get family statistics
        global $wpdb;
        $family_count = $wpdb->get_var("
            SELECT COUNT(DISTINCT meta_value) 
            FROM {$wpdb->postmeta} pm 
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE pm.meta_key = '_child_family_id' 
            AND p.post_type = 'child' 
            AND p.post_status = 'publish'
            AND pm.meta_value != ''
        ");
        
        return new CFK_SponsorshipStats(
            total_children: $total_children,
            sponsored_children: $sponsored_count,
            available_children: $available_count,
            total_families: intval($family_count),
            sponsorship_percentage: $total_children > 0 ? round(($sponsored_count / $total_children) * 100, 1) : 0.0
        );
    }
    
    public static function mark_as_sponsored(string $child_id, bool $sponsored = true): bool {
        $child_post = self::get_child_by_id($child_id);
        
        if (!$child_post) {
            return false;
        }
        
        match($sponsored) {
            true => update_post_meta($child_post->ID, '_child_sponsored', '1'),
            false => delete_post_meta($child_post->ID, '_child_sponsored')
        };
        
        return true;
    }
    
    public static function get_children_by_status(?bool $sponsored = null): array {
        $query_args = [
            'post_type' => 'child',
            'post_status' => 'publish',
            'posts_per_page' => -1
        ];
        
        if ($sponsored !== null) {
            $query_args['meta_query'] = match($sponsored) {
                true => [
                    [
                        'key' => '_child_sponsored',
                        'value' => '1',
                        'compare' => '='
                    ]
                ],
                false => [
                    'relation' => 'OR',
                    [
                        'key' => '_child_sponsored',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => '_child_sponsored',
                        'value' => '1',
                        'compare' => '!='
                    ]
                ]
            };
        }
        
        return get_posts($query_args);
    }
    
    public static function get_child_details(?WP_Post $child_post): ?CFK_ChildDetails {
        if (!$child_post) {
            return null;
        }
        
        $gender_meta = get_post_meta($child_post->ID, '_child_gender', true);
        $age_range_meta = get_post_meta($child_post->ID, '_child_age_range', true);
        
        try {
            $gender = CFK_Gender::from($gender_meta);
            $age_range = CFK_AgeRange::from($age_range_meta);
            
            return new CFK_ChildDetails(
                id: get_post_meta($child_post->ID, '_child_id', true),
                name: $child_post->post_title,
                age: intval(get_post_meta($child_post->ID, '_child_age', true)),
                gender: $gender,
                family_id: get_post_meta($child_post->ID, '_child_family_id', true),
                age_range: $age_range,
                clothing_info: get_post_meta($child_post->ID, '_child_clothing_info', true),
                gift_requests: get_post_meta($child_post->ID, '_child_gift_requests', true),
                sponsored: get_post_meta($child_post->ID, '_child_sponsored', true) === '1',
                avatar_url: get_the_post_thumbnail_url($child_post->ID, 'medium') ?: null,
                edit_url: admin_url('post.php?post=' . $child_post->ID . '&action=edit')
            );
            
        } catch (ValueError $e) {
            error_log('CFK: Invalid enum value for child ' . $child_post->ID . ': ' . $e->getMessage());
            return null;
        }
    }
    
    public static function bulk_update_sponsored_status(array $child_ids, bool $sponsored = true): int {
        $updated = 0;
        
        foreach ($child_ids as $child_id) {
            if (self::mark_as_sponsored($child_id, $sponsored)) {
                $updated++;
            }
        }
        
        return $updated;
    }
    
    public static function search_children(string $search_term, CFK_ChildQuery $query = new CFK_ChildQuery()): array {
        $query_args = [
            'post_type' => 'child',
            'post_status' => 'publish',
            'posts_per_page' => $query->per_page,
            's' => $search_term,
            'meta_query' => ['relation' => 'AND']
        ];
        
        // Add filters
        if ($query->age_range) {
            $query_args['meta_query'][] = [
                'key' => '_child_age_range',
                'value' => $query->age_range->value,
                'compare' => '='
            ];
        }
        
        if ($query->gender) {
            $query_args['meta_query'][] = [
                'key' => '_child_gender',
                'value' => $query->gender->value,
                'compare' => '='
            ];
        }
        
        if ($query->sponsored !== null) {
            $query_args['meta_query'][] = match($query->sponsored) {
                true => [
                    'key' => '_child_sponsored',
                    'value' => '1',
                    'compare' => '='
                ],
                false => [
                    'relation' => 'OR',
                    [
                        'key' => '_child_sponsored',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => '_child_sponsored',
                        'value' => '1',
                        'compare' => '!='
                    ]
                ]
            };
        }
        
        // Enhanced search with meta fields
        add_filter('posts_join', [__CLASS__, 'search_join']);
        add_filter('posts_where', [__CLASS__, 'search_where']);
        add_filter('posts_groupby', [__CLASS__, 'search_groupby']);
        
        $children = get_posts($query_args);
        
        // Remove filters
        remove_filter('posts_join', [__CLASS__, 'search_join']);
        remove_filter('posts_where', [__CLASS__, 'search_where']);
        remove_filter('posts_groupby', [__CLASS__, 'search_groupby']);
        
        return $children;
    }
    
    public static function search_join(string $join): string {
        global $wpdb;
        return $join . " LEFT JOIN {$wpdb->postmeta} pm ON {$wpdb->posts}.ID = pm.post_id ";
    }
    
    public static function search_where(string $where): string {
        global $wpdb;
        
        $search_term = get_search_query();
        if ($search_term) {
            $where = preg_replace(
                "/\(\s*{$wpdb->posts}.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
                "({$wpdb->posts}.post_title LIKE $1) OR (pm.meta_value LIKE $1)",
                $where
            );
        }
        
        return $where;
    }
    
    public static function search_groupby(string $groupby): string {
        global $wpdb;
        return "{$wpdb->posts}.ID";
    }
    
    public function handle_ajax_export(): void {
        try {
            check_ajax_referer('cfk_admin_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => __('Insufficient permissions', 'cfk-sponsorship')]);
            }
            
            $filters = [
                'sponsored' => $_POST['sponsored'] ?? null,
                'age_range' => $_POST['age_range'] ?? '',
                'gender' => $_POST['gender'] ?? ''
            ];
            
            $this->export_children_csv($filters);
            
        } catch (Throwable $e) {
            error_log('CFK Export Error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public function handle_ajax_bulk_update(): void {
        try {
            check_ajax_referer('cfk_admin_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => __('Insufficient permissions', 'cfk-sponsorship')]);
            }
            
            $child_ids = array_map('sanitize_text_field', $_POST['child_ids'] ?? []);
            $sponsored = filter_var($_POST['sponsored'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            $updated = self::bulk_update_sponsored_status($child_ids, $sponsored);
            
            wp_send_json_success([
                'message' => sprintf(__('%d children updated successfully', 'cfk-sponsorship'), $updated),
                'updated_count' => $updated
            ]);
            
        } catch (Throwable $e) {
            error_log('CFK Bulk Update Error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public function export_children_csv(array $filters = []): void {
        $sponsored_filter = match($filters['sponsored'] ?? null) {
            'true' => true,
            'false' => false,
            default => null
        };
        
        $children = self::get_children_by_status($sponsored_filter);
        
        if (empty($children)) {
            wp_send_json_error(['message' => __('No children found to export', 'cfk-sponsorship')]);
        }
        
        $filename = 'cfk-children-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'Child ID',
            'Name',
            'Age',
            'Gender',
            'Family ID',
            'Age Range',
            'Clothing Info',
            'Gift Requests',
            'Sponsored',
            'Created Date'
        ]);
        
        // CSV data
        foreach ($children as $child) {
            $details = self::get_child_details($child);
            
            if ($details) {
                fputcsv($output, [
                    $details->id,
                    $details->name,
                    $details->age,
                    $details->gender->value,
                    $details->family_id,
                    $details->age_range->value,
                    $details->clothing_info,
                    $details->gift_requests,
                    $details->sponsored ? 'Yes' : 'No',
                    get_the_date('Y-m-d H:i:s', $child->ID)
                ]);
            }
        }
        
        fclose($output);
        exit;
    }
    
    public static function validate_child_data(array $data): array {
        $errors = [];
        
        // Required field validation
        $required_fields = ['child_id', 'child_age', 'child_gender', 'child_age_range'];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $field_name = str_replace('child_', '', $field);
                $errors[] = sprintf(__('%s is required', 'cfk-sponsorship'), ucwords(str_replace('_', ' ', $field_name)));
            }
        }
        
        // Age validation
        if (!empty($data['child_age'])) {
            $age = intval($data['child_age']);
            if ($age < 0 || $age > 18) {
                $errors[] = __('Age must be between 0 and 18', 'cfk-sponsorship');
            }
        }
        
        // Gender validation
        if (!empty($data['child_gender'])) {
            try {
                CFK_Gender::from($data['child_gender']);
            } catch (ValueError) {
                $errors[] = __('Invalid gender value', 'cfk-sponsorship');
            }
        }
        
        // Age range validation
        if (!empty($data['child_age_range'])) {
            try {
                CFK_AgeRange::from($data['child_age_range']);
            } catch (ValueError) {
                $errors[] = __('Invalid age range value', 'cfk-sponsorship');
            }
        }
        
        // Check for duplicate child ID
        if (!empty($data['child_id'])) {
            $existing = self::get_child_by_id($data['child_id']);
            if ($existing && (!isset($data['post_id']) || $existing->ID != $data['post_id'])) {
                $errors[] = sprintf(__('Child ID "%s" already exists', 'cfk-sponsorship'), $data['child_id']);
            }
        }
        
        return $errors;
    }
    
    public static function get_age_ranges(): array {
        $ranges = [];
        foreach (CFK_AgeRange::cases() as $range) {
            $ranges[$range->value] = __($range->value, 'cfk-sponsorship');
        }
        return $ranges;
    }
    
    public static function get_gender_options(): array {
        $genders = [];
        foreach (CFK_Gender::cases() as $gender) {
            $genders[$gender->value] = __($gender->value, 'cfk-sponsorship');
        }
        return $genders;
    }
    
    // CSV Import Integration Methods
    public static function create_child_from_csv_data(array $csv_data): ?int {
        try {
            // Validate data first
            $validation_errors = self::validate_child_data($csv_data);
            if (!empty($validation_errors)) {
                throw new Exception(implode(', ', $validation_errors));
            }
            
            // Create post
            $post_data = [
                'post_title' => sanitize_text_field($csv_data['name'] ?? ''),
                'post_type' => 'child',
                'post_status' => 'publish',
                'post_content' => ''
            ];
            
            $post_id = wp_insert_post($post_data);
            
            if (is_wp_error($post_id)) {
                throw new Exception('Failed to create child post: ' . $post_id->get_error_message());
            }
            
            // Save meta fields
            $meta_mapping = [
                'child_id' => $csv_data['child_id'] ?? '',
                'child_age' => intval($csv_data['age'] ?? 0),
                'child_gender' => $csv_data['gender'] ?? '',
                'child_family_id' => $csv_data['family_id'] ?? '',
                'child_age_range' => self::determine_age_range(intval($csv_data['age'] ?? 0)),
                'child_clothing_info' => $csv_data['clothing_info'] ?? '',
                'child_gift_requests' => $csv_data['gift_requests'] ?? ''
            ];
            
            foreach ($meta_mapping as $meta_key => $meta_value) {
                if ($meta_value !== '') {
                    update_post_meta($post_id, '_' . $meta_key, $meta_value);
                }
            }
            
            return $post_id;
            
        } catch (Throwable $e) {
            error_log('CFK Create Child Error: ' . $e->getMessage());
            return null;
        }
    }
    
    private static function determine_age_range(int $age): string {
        return match(true) {
            $age <= 2 => CFK_AgeRange::INFANT->value,
            $age <= 10 => CFK_AgeRange::ELEMENTARY->value,
            $age <= 13 => CFK_AgeRange::MIDDLE_SCHOOL->value,
            default => CFK_AgeRange::HIGH_SCHOOL->value
        };
    }
    
    public static function update_child_from_csv_data(string $child_id, array $csv_data): bool {
        try {
            $child_post = self::get_child_by_id($child_id);
            if (!$child_post) {
                return false;
            }
            
            // Add post_id to validation data to allow updates
            $csv_data['post_id'] = $child_post->ID;
            
            $validation_errors = self::validate_child_data($csv_data);
            if (!empty($validation_errors)) {
                throw new Exception(implode(', ', $validation_errors));
            }
            
            // Update post title if name provided
            if (!empty($csv_data['name'])) {
                wp_update_post([
                    'ID' => $child_post->ID,
                    'post_title' => sanitize_text_field($csv_data['name'])
                ]);
            }
            
            // Update meta fields
            $meta_mapping = [
                'child_age' => intval($csv_data['age'] ?? 0),
                'child_gender' => $csv_data['gender'] ?? '',
                'child_family_id' => $csv_data['family_id'] ?? '',
                'child_age_range' => self::determine_age_range(intval($csv_data['age'] ?? 0)),
                'child_clothing_info' => $csv_data['clothing_info'] ?? '',
                'child_gift_requests' => $csv_data['gift_requests'] ?? ''
            ];
            
            foreach ($meta_mapping as $meta_key => $meta_value) {
                if ($meta_value !== '') {
                    update_post_meta($child_post->ID, '_' . $meta_key, $meta_value);
                }
            }
            
            return true;
            
        } catch (Throwable $e) {
            error_log('CFK Update Child Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function get_child_count_by_status(): array {
        $stats = self::get_sponsorship_stats();
        
        return [
            'total' => $stats->total_children,
            'sponsored' => $stats->sponsored_children,
            'available' => $stats->available_children,
            'families' => $stats->total_families
        ];
    }
}

?>