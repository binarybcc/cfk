<?php
/**
 * Children Manager Class - Part 2: WordPress Integration
 * Post type registration and meta boxes
 *
 * @package ChristmasForKids
 */

declare(strict_types=1);

// This file extends the CFK_Children_Manager class from Part 1
// Add these methods to the class:

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
    
    add_meta_box(
        'child_family',
        __('Family Information', 'cfk-sponsorship'),
        $this->child_family_meta_box(...),
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
            <th scope="row">
                <label for="child_id"><?php _e('Child ID', 'cfk-sponsorship'); ?> <span class="required">*</span></label>
            </th>
            <td>
                <input type="text" id="child_id" name="child_id" 
                       value="<?php echo esc_attr($meta_values['child_id']); ?>" 
                       placeholder="<?php _e('e.g., 002A', 'cfk-sponsorship'); ?>" 
                       class="regular-text" required />
                <p class="description">
                    <?php _e('Unique identifier for this child. Must be unique across all children.', 'cfk-sponsorship'); ?>
                </p>
                <div id="child-id-validation" class="validation-message"></div>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="child_age"><?php _e('Age', 'cfk-sponsorship'); ?> <span class="required">*</span></label>
            </th>
            <td>
                <input type="number" id="child_age" name="child_age" 
                       value="<?php echo esc_attr($meta_values['child_age']); ?>" 
                       min="0" max="18" class="small-text" required />
                <span class="description"><?php _e('years old', 'cfk-sponsorship'); ?></span>
                <p class="description">
                    <?php _e('Child\'s current age (0-18 years)', 'cfk-sponsorship'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="child_gender"><?php _e('Gender', 'cfk-sponsorship'); ?> <span class="required">*</span></label>
            </th>
            <td>
                <select id="child_gender" name="child_gender" required>
                    <option value=""><?php _e('Select Gender', 'cfk-sponsorship'); ?></option>
                    <?php foreach (CFK_Gender::cases() as $gender): ?>
                        <option value="<?php echo esc_attr($gender->value); ?>" 
                                <?php selected($meta_values['child_gender'], $gender->value); ?>>
                            <?php echo esc_html($gender->getLabel()); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="child_age_range"><?php _e('Age Range', 'cfk-sponsorship'); ?> <span class="required">*</span></label>
            </th>
            <td>
                <select id="child_age_range" name="child_age_range" required>
                    <option value=""><?php _e('Select Age Range', 'cfk-sponsorship'); ?></option>
                    <?php foreach (CFK_AgeRange::cases() as $age_range): ?>
                        <option value="<?php echo esc_attr($age_range->value); ?>" 
                                <?php selected($meta_values['child_age_range'], $age_range->value); ?>
                                data-min-age="<?php echo $age_range->getAgeGroup()['min']; ?>"
                                data-max-age="<?php echo $age_range->getAgeGroup()['max']; ?>">
                            <?php echo esc_html($age_range->getLabel()); ?>
                            (<?php echo $age_range->getAgeGroup()['min']; ?>-<?php echo $age_range->getAgeGroup()['max']; ?> years)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php _e('Age range will auto-update based on the age entered above.', 'cfk-sponsorship'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="child_clothing_info"><?php _e('Clothing Information', 'cfk-sponsorship'); ?></label>
            </th>
            <td>
                <textarea id="child_clothing_info" name="child_clothing_info" 
                          rows="3" cols="50" class="large-text" 
                          placeholder="<?php _e('Pants: Size 10, Shirt: Medium, Shoes: Size 5', 'cfk-sponsorship'); ?>"><?php echo esc_textarea($meta_values['child_clothing_info']); ?></textarea>
                <p class="description">
                    <?php _e('Include all clothing sizes: pants, shirts, shoes, underwear, etc.', 'cfk-sponsorship'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="child_gift_requests"><?php _e('Gift Requests & Interests', 'cfk-sponsorship'); ?></label>
            </th>
            <td>
                <textarea id="child_gift_requests" name="child_gift_requests" 
                          rows="4" cols="50" class="large-text" 
                          placeholder="<?php _e('Loves reading, art supplies, building blocks, outdoor activities...', 'cfk-sponsorship'); ?>"><?php echo esc_textarea($meta_values['child_gift_requests']); ?></textarea>
                <p class="description">
                    <?php _e('Child\'s interests, hobbies, and specific gift requests to help sponsors choose appropriate gifts.', 'cfk-sponsorship'); ?>
                </p>
            </td>
        </tr>
    </table>
    
    <style>
    .required {
        color: #dc3232;
    }
    .validation-message {
        margin-top: 5px;
        font-weight: bold;
    }
    .validation-message.error {
        color: #dc3232;
    }
    .validation-message.success {
        color: #46b450;
    }
    .form-table th {
        vertical-align: top;
        padding-top: 15px;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Auto-update age range based on age
        $('#child_age').on('input', function() {
            const age = parseInt($(this).val());
            const ageRangeSelect = $('#child_age_range');
            
            if (age >= 0 && age <= 18) {
                ageRangeSelect.find('option').each(function() {
                    const minAge = parseInt($(this).data('min-age'));
                    const maxAge = parseInt($(this).data('max-age'));
                    
                    if (age >= minAge && age <= maxAge) {
                        ageRangeSelect.val($(this).val());
                        return false;
                    }
                });
            }
        });
        
        // Validate child ID uniqueness
        $('#child_id').on('blur', function() {
            const childId = $(this).val();
            const postId = $('#post_ID').val();
            const validationDiv = $('#child-id-validation');
            
            if (childId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cfk_validate_child_id',
                        child_id: childId,
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce('cfk_validate_child_id'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            validationDiv.removeClass('error').addClass('success')
                                         .text('✓ Child ID is available');
                        } else {
                            validationDiv.removeClass('success').addClass('error')
                                         .text('✗ ' + response.data);
                        }
                    }
                });
            } else {
                validationDiv.removeClass('error success').text('');
            }
        });
    });
    </script>
    <?php
}

public function child_sponsorship_meta_box(WP_Post $post): void {
    $sponsored = get_post_meta($post->ID, '_child_sponsored', true);
    $sponsor_info = $this->get_sponsor_info($post->ID);
    
    ?>
    <div class="cfk-sponsorship-status">
        <p>
            <label>
                <input type="checkbox" id="child_sponsored" name="child_sponsored" 
                       value="1" <?php checked($sponsored, '1'); ?> />
                <?php _e('This child has been sponsored', 'cfk-sponsorship'); ?>
            </label>
        </p>
        
        <?php if ($sponsor_info): ?>
        <div class="cfk-sponsor-details">
            <h4><?php _e('Current Sponsor', 'cfk-sponsorship'); ?></h4>
            <p><strong><?php _e('Name:', 'cfk-sponsorship'); ?></strong> <?php echo esc_html($sponsor_info->sponsor_name); ?></p>
            <p><strong><?php _e('Email:', 'cfk-sponsorship'); ?></strong> 
               <a href="mailto:<?php echo esc_attr($sponsor_info->sponsor_email); ?>">
                   <?php echo esc_html($sponsor_info->sponsor_email); ?>
               </a>
            </p>
            <?php if ($sponsor_info->sponsor_phone): ?>
            <p><strong><?php _e('Phone:', 'cfk-sponsorship'); ?></strong> 
               <a href="tel:<?php echo esc_attr($sponsor_info->sponsor_phone); ?>">
                   <?php echo esc_html($sponsor_info->sponsor_phone); ?>
               </a>
            </p>
            <?php endif; ?>
            <p><strong><?php _e('Confirmed:', 'cfk-sponsorship'); ?></strong> 
               <?php echo esc_html(mysql2date('M j, Y g:i a', $sponsor_info->confirmed_time)); ?>
            </p>
            
            <?php if ($sponsor_info->sponsor_notes): ?>
            <p><strong><?php _e('Notes:', 'cfk-sponsorship'); ?></strong><br>
               <?php echo esc_html($sponsor_info->sponsor_notes); ?>
            </p>
            <?php endif; ?>
            
            <div class="sponsor-actions">
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=cfk_resend_sponsor_email&session_id=' . urlencode($sponsor_info->session_id)), 'cfk_resend_email'); ?>" 
                   class="button button-secondary">
                    <?php _e('Resend Confirmation Email', 'cfk-sponsorship'); ?>
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="cfk-no-sponsor">
            <p class="description">
                <?php _e('This child is available for sponsorship.', 'cfk-sponsorship'); ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
    .cfk-sponsorship-status {
        padding: 10px 0;
    }
    .cfk-sponsor-details {
        background: #f0f6fc;
        padding: 15px;
        border-radius: 4px;
        margin-top: 10px;
        border-left: 4px solid #0073aa;
    }
    .cfk-sponsor-details h4 {
        margin: 0 0 10px 0;
        color: #0073aa;
    }
    .cfk-sponsor-details p {
        margin: 8px 0;
        font-size: 13px;
    }
    .cfk-sponsor-details a {
        color: #0073aa;
        text-decoration: none;
    }
    .cfk-sponsor-details a:hover {
        text-decoration: underline;
    }
    .sponsor-actions {
        margin-top: 15px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
    }
    .cfk-no-sponsor {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        margin-top: 10px;
        border-left: 4px solid #6c757d;
    }
    </style>
    <?php
}

public function child_family_meta_box(WP_Post $post): void {
    $family_id = get_post_meta($post->ID, '_child_family_id', true);
    $family_children = [];
    
    if ($family_id) {
        $family_children = self::get_children_by_family($family_id);
        // Remove current child from the list
        $family_children = array_filter($family_children, fn($child) => $child->ID !== $post->ID);
    }
    
    ?>
    <div class="cfk-family-info">
        <p>
            <label for="child_family_id"><?php _e('Family ID', 'cfk-sponsorship'); ?></label>
            <input type="text" id="child_family_id" name="child_family_id" 
                   value="<?php echo esc_attr($family_id); ?>" 
                   placeholder="<?php _e('e.g., FAM001', 'cfk-sponsorship'); ?>" 
                   class="regular-text" />
        </p>
        <p class="description">
            <?php _e('Use the same Family ID for siblings to group them together.', 'cfk-sponsorship'); ?>
        </p>
        
        <?php if ($family_children): ?>
        <div class="cfk-family-siblings">
            <h4><?php _e('Siblings in this family:', 'cfk-sponsorship'); ?></h4>
            <ul>
                <?php foreach ($family_children as $sibling): ?>
                    <?php 
                    $sibling_id = get_post_meta($sibling->ID, '_child_id', true);
                    $sibling_age = get_post_meta($sibling->ID, '_child_age', true);
                    $sibling_sponsored = get_post_meta($sibling->ID, '_child_sponsored', true) === '1';
                    ?>
                    <li>
                        <a href="<?php echo get_edit_post_link($sibling->ID); ?>">
                            <?php echo esc_html($sibling->post_title); ?>
                        </a>
                        (ID: <?php echo esc_html($sibling_id); ?>, Age: <?php echo esc_html($sibling_age); ?>)
                        <?php if ($sibling_sponsored): ?>
                            <span class="sponsored-badge">✓ Sponsored</span>
                        <?php else: ?>
                            <span class="available-badge">Available</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
    .cfk-family-info input {
        width: 100%;
        margin-top: 5px;
    }
    .cfk-family-siblings {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
    }
    .cfk-family-siblings h4 {
        margin: 0 0 10px 0;
        color: #23282d;
    }
    .cfk-family-siblings ul {
        margin: 0;
        padding-left: 20px;
    }
    .cfk-family-siblings li {
        margin-bottom: 8px;
        line-height: 1.4;
    }
    .sponsored-badge {
        background: #46b450;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
    }
    .available-badge {
        background: #6c757d;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
    }
    </style>
    <?php
}

private function get_meta_values(int $post_id): array {
    $values = [];
    foreach (array_keys($this->meta_fields_config) as $field) {
        $values[$field] = get_post_meta($post_id, '_' . $field, true);
    }
    return $values;
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
    $all_errors = [];
    
    foreach ($this->meta_fields_config as $field => $config) {
        $value = $_POST[$field] ?? '';
        
        // Validate field
        $errors = $this->validate_meta_field($field, $value);
        if (!empty($errors)) {
            $all_errors[$field] = $errors;
            continue;
        }
        
        // Save field based on type
        match($config['type']) {
            'checkbox' => $this->handle_checkbox_field($post_id, $field, $value),
            default => $this->handle_regular_field($post_id, $field, $value, $config['sanitize'])
        };
    }
    
    // Store validation errors if any
    if (!empty($all_errors)) {
        update_post_meta($post_id, '_child_validation_errors', $all_errors);
        add_action('admin_notices', function() use ($all_errors) {
            foreach ($all_errors as $field => $errors) {
                foreach ($errors as $error) {
                    echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
                }
            }
        });
    } else {
        delete_post_meta($post_id, '_child_validation_errors');
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

public function handle_ajax_validate_child_id(): void {
    try {
        check_ajax_referer('cfk_validate_child_id', 'nonce');
        
        $child_id = sanitize_text_field($_POST['child_id'] ?? '');
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (empty($child_id)) {
            wp_send_json_error(__('Child ID is required', 'cfk-sponsorship'));
        }
        
        // Check if child ID already exists
        $existing = self::get_child_by_id($child_id);
        
        if ($existing && $existing->ID !== $post_id) {
            wp_send_json_error(sprintf(
                __('Child ID "%s" is already used by another child', 'cfk-sponsorship'),
                $child_id
            ));
        }
        
        wp_send_json_success(__('Child ID is available', 'cfk-sponsorship'));
        
    } catch (Throwable $e) {
        error_log('CFK Child ID Validation Error: ' . $e->getMessage());
        wp_send_json_error(__('Validation failed', 'cfk-sponsorship'));
    }
}

?>
