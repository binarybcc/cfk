<?php
declare(strict_types=1);

/**
 * Custom List Table for Children Management
 * 
 * This class extends WordPress WP_List_Table to provide a custom
 * admin interface for managing child records.
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 */
class CFK_Children_List_Table extends WP_List_Table {
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'child',
            'plural' => 'children',
            'ajax' => false
        ]);
    }
    
    /**
     * Get table columns
     * 
     * @since 1.0.0
     * @return array<string, string> Column definitions
     */
    public function get_columns(): array {
        return [
            'cb' => '<input type="checkbox" />',
            'name' => __('Name', CFK_TEXT_DOMAIN),
            'age' => __('Age', CFK_TEXT_DOMAIN),
            'gender' => __('Gender', CFK_TEXT_DOMAIN),
            'grade' => __('Grade', CFK_TEXT_DOMAIN),
            'family' => __('Family', CFK_TEXT_DOMAIN),
            'status' => __('Status', CFK_TEXT_DOMAIN),
            'date' => __('Date Added', CFK_TEXT_DOMAIN)
        ];
    }
    
    /**
     * Get sortable columns
     * 
     * @since 1.0.0
     * @return array<string, array<string|bool>> Sortable column definitions
     */
    public function get_sortable_columns(): array {
        return [
            'name' => ['title', false],
            'age' => ['age', false],
            'gender' => ['gender', false],
            'grade' => ['grade', false],
            'family' => ['family_number', false],
            'date' => ['date', true]
        ];
    }
    
    /**
     * Get bulk actions
     * 
     * @since 1.0.0
     * @return array<string, string> Bulk action definitions
     */
    public function get_bulk_actions(): array {
        return [
            'delete' => __('Delete', CFK_TEXT_DOMAIN),
            'mark_sponsored' => __('Mark as Sponsored', CFK_TEXT_DOMAIN),
            'mark_available' => __('Mark as Available', CFK_TEXT_DOMAIN)
        ];
    }
    
    /**
     * Prepare items for display
     * 
     * @since 1.0.0
     * @return void
     */
    public function prepare_items(): void {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = [$columns, $hidden, $sortable];
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        // Get query parameters
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'date';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'desc';
        
        // Build query args
        $query_args = [
            'post_type' => 'cfk_child',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $current_page,
            'orderby' => $this->get_orderby_field($orderby),
            'order' => $order
        ];
        
        // Add search if provided
        if (!empty($search)) {
            $query_args['s'] = $search;
        }
        
        // Execute query
        $query = new WP_Query($query_args);
        
        $this->items = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $this->items[] = $this->prepare_item_data($post);
            }
        }
        
        wp_reset_postdata();
        
        // Set pagination
        $this->set_pagination_args([
            'total_items' => $query->found_posts,
            'per_page' => $per_page,
            'total_pages' => ceil($query->found_posts / $per_page)
        ]);
    }
    
    /**
     * Prepare individual item data
     * 
     * @since 1.0.0
     * @param WP_Post $post The post object
     * @return array<string, mixed> Item data
     */
    private function prepare_item_data(WP_Post $post): array {
        $child_manager = Christmas_For_Kids::get_instance()->get_component('child_manager');
        
        return [
            'id' => $post->ID,
            'name' => $post->post_title,
            'age' => get_post_meta($post->ID, 'cfk_child_age', true),
            'gender' => get_post_meta($post->ID, 'cfk_child_gender', true),
            'grade' => get_post_meta($post->ID, 'cfk_child_grade', true),
            'school' => get_post_meta($post->ID, 'cfk_child_school', true),
            'family_number' => get_post_meta($post->ID, 'cfk_child_family_number', true),
            'family_name' => get_post_meta($post->ID, 'cfk_child_family_name', true),
            'is_sponsored' => $child_manager ? $child_manager->is_child_sponsored($post->ID) : false,
            'date' => $post->post_date
        ];
    }
    
    /**
     * Get orderby field for query
     * 
     * @since 1.0.0
     * @param string $orderby The requested orderby parameter
     * @return string The actual orderby field for the query
     */
    private function get_orderby_field(string $orderby): string {
        $orderby_map = [
            'title' => 'title',
            'date' => 'date',
            'age' => 'meta_value_num',
            'gender' => 'meta_value',
            'grade' => 'meta_value',
            'family_number' => 'meta_value'
        ];
        
        return $orderby_map[$orderby] ?? 'date';
    }
    
    /**
     * Display checkbox column
     * 
     * @since 1.0.0
     * @param array $item Item data
     * @return string Checkbox HTML
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="children[]" value="%d" />',
            $item['id']
        );
    }
    
    /**
     * Display name column with actions
     * 
     * @since 1.0.0
     * @param array $item Item data
     * @return string Name column HTML
     */
    public function column_name($item) {
        $edit_url = admin_url('admin.php?page=cfk-edit-child&child_id=' . $item['id']);
        $delete_url = wp_nonce_url(
            admin_url('admin.php?page=cfk-all-children&action=delete&child=' . $item['id']),
            'delete_child_' . $item['id']
        );
        
        $actions = [
            'edit' => sprintf('<a href="%s">%s</a>', esc_url($edit_url), __('Edit', CFK_TEXT_DOMAIN)),
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'%s\')">%s</a>', 
                esc_url($delete_url),
                esc_js(__('Are you sure you want to delete this child?', CFK_TEXT_DOMAIN)),
                __('Delete', CFK_TEXT_DOMAIN)
            )
        ];
        
        return sprintf(
            '<strong><a href="%s">%s</a></strong>%s',
            esc_url($edit_url),
            esc_html($item['name']),
            $this->row_actions($actions)
        );
    }
    
    /**
     * Display age column
     * 
     * @since 1.0.0
     * @param array $item Item data
     * @return string Age HTML
     */
    public function column_age($item) {
        return esc_html($item['age'] ?: '—');
    }
    
    /**
     * Display gender column
     * 
     * @since 1.0.0
     * @param array $item Item data
     * @return string Gender HTML
     */
    public function column_gender($item) {
        return esc_html($item['gender'] ?: '—');
    }
    
    /**
     * Display grade column
     * 
     * @since 1.0.0
     * @param array $item Item data
     * @return string Grade HTML
     */
    public function column_grade($item) {
        return esc_html($item['grade'] ?: '—');
    }
    
    /**
     * Display family column
     * 
     * @since 1.0.0
     * @param array $item Item data
     * @return string Family HTML
     */
    public function column_family($item) {
        $family_info = [];
        
        if (!empty($item['family_number'])) {
            $family_info[] = sprintf(__('Family #%s', CFK_TEXT_DOMAIN), esc_html($item['family_number']));
        }
        
        if (!empty($item['family_name'])) {
            $family_info[] = esc_html($item['family_name']);
        }
        
        return !empty($family_info) ? implode('<br>', $family_info) : '—';
    }
    
    /**
     * Display status column
     * 
     * @since 1.0.0
     * @param array $item Item data
     * @return string Status HTML
     */
    public function column_status($item) {
        if ($item['is_sponsored']) {
            return '<span class="cfk-status-sponsored" style="color: #00a32a; font-weight: bold;">' . 
                   esc_html__('Sponsored', CFK_TEXT_DOMAIN) . '</span>';
        } else {
            return '<span class="cfk-status-available" style="color: #0073aa; font-weight: bold;">' . 
                   esc_html__('Available', CFK_TEXT_DOMAIN) . '</span>';
        }
    }
    
    /**
     * Display date column
     * 
     * @since 1.0.0
     * @param array $item Item data
     * @return string Date HTML
     */
    public function column_date($item) {
        return date_i18n(get_option('date_format'), strtotime($item['date']));
    }
    
    /**
     * Default column display
     * 
     * @since 1.0.0
     * @param array $item Item data
     * @param string $column_name Column name
     * @return string Column HTML
     */
    public function column_default($item, $column_name) {
        return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
    }
    
    /**
     * Display when no items found
     * 
     * @since 1.0.0
     * @return void
     */
    public function no_items(): void {
        esc_html_e('No children found.', CFK_TEXT_DOMAIN);
    }
}