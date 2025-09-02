<?php
/**
 * Children Manager Class - Part 3: Admin Interface
 * Custom columns, sorting, and admin list functionality
 *
 * @package ChristmasForKids
 */

declare(strict_types=1);

// This file extends the CFK_Children_Manager class
// Add these methods to the class:

public function custom_child_columns(array $columns): array {
    return [
        'cb' => $columns['cb'],
        'child_photo' => __('Photo', 'cfk-sponsorship'),
        'title' => $columns['title'],
        'child_id' => __('Child ID', 'cfk-sponsorship'),
        'age_gender' => __('Age/Gender', 'cfk-sponsorship'),
        'family_id' => __('Family', 'cfk-sponsorship'),
        'age_range' => __('Age Range', 'cfk-sponsorship'),
        'sponsored' => __('Status', 'cfk-sponsorship'),
        'last_updated' => __('Updated', 'cfk-sponsorship'),
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
        default => match($column) {
            'last_updated' => $this->render_last_updated_column($post_id),
            default => null
        }
    };
}

private function render_photo_column(int $post_id): void {
    if (has_post_thumbnail($post_id)) {
        $thumbnail = get_the_post_thumbnail($post_id, [50, 50], [
            'style' => 'border-radius: 50%; object-fit: cover;'
        ]);
        echo '<div style="width: 50px; height: 50px; overflow: hidden; border-radius: 50%;">' . $thumbnail . '</div>';
    } else {
        $gender_meta = get_post_meta($post_id, '_child_gender', true);
        $gender = CFK_Gender::tryFrom($gender_meta);
        $icon = $gender?->getIcon() ?? '👤';
        $color = $gender === CFK_Gender::MALE ? '#4a90e2' : ($gender === CFK_Gender::FEMALE ? '#e24a90' : '#666');
        
        echo '<div style="width:50px;height:50px;background:' . $color . ';display:flex;align-items:center;justify-content:center;border-radius:50%;color:white;font-size:20px;font-weight:bold;">' . $icon . '</div>';
    }
}

private function render_child_id_column(int $post_id): void {
    $child_id = get_post_meta($post_id, '_child_id', true);
    if ($child_id) {
        echo '<strong>' . esc_html($child_id) . '</strong>';
    } else {
        echo '<span style="color: #dc3232;">⚠ Missing ID</span>';
    }
}

private function render_age_gender_column(int $post_id): void {
    $age = get_post_meta($post_id, '_child_age', true);
    $gender_meta = get_post_meta($post_id, '_child_gender', true);
    $gender = CFK_Gender::tryFrom($gender_meta);
    
    if ($age && $gender) {
        $icon = $gender->getIcon();
        echo '<span style="font-size: 14px;">' . esc_html($age) . ' years</span><br>';
        echo '<span style="color: #666; font-size: 18px;">' . $icon . ' ' . esc_html($gender->value) . '</span>';
    } else {
        echo '<span style="color: #dc3232;">Incomplete</span>';
    }
}

private function render_family_id_column(int $post_id): void {
    $family_id = get_post_meta($post_id, '_child_family_id', true);
    
    if ($family_id) {
        $family_children = self::get_children_by_family($family_id);
        $family_count = count($family_children);
        
        echo '<strong>' . esc_html($family_id) . '</strong>';
        if ($family_count > 1) {
            echo '<br><small style="color: #666;">' . 
                 sprintf(__('%d siblings', 'cfk-sponsorship'), $family_count - 1) . 
                 '</small>';
        }
    } else {
        echo '<span style="color: #999;">—</span>';
    }
}

private function render_age_range_column(int $post_id): void {
    $age_range_meta = get_post_meta($post_id, '_child_age_range', true);
    $age = intval(get_post_meta($post_id, '_child_age', true));
    
    if ($age_range_meta) {
        $age_range = CFK_AgeRange::tryFrom($age_range_meta);
        if ($age_range) {
            $age_group = $age_range->getAgeGroup();
            $is_correct = $age >= $age_group['min'] && $age <= $age_group['max'];
            
            echo '<span style="' . ($is_correct ? '' : 'color: #dc3232;') . '">';
            echo esc_html($age_range->value);
            echo '</span>';
            
            if (!$is_correct) {
                echo '<br><small style="color: #dc3232;">⚠ Age mismatch</small>';
            }
        }
    } else {
        echo '<span style="color: #dc3232;">Missing</span>';
    }
}

private function render_sponsored_column(int $post_id): void {
    $sponsored = get_post_meta($post_id, '_child_sponsored', true) === '1';
    $child_id = get_post_meta($post_id, '_child_id', true);
    
    if ($sponsored) {
        // Get sponsor info
        $sponsor_info = $this->get_sponsor_info($post_id);
        if ($sponsor_info) {
            echo '<span style="color: #46b450; font-weight: bold;">✓ Sponsored</span><br>';
            echo '<small style="color: #666;">' . esc_html($sponsor_info->sponsor_name) . '</small>';
        } else {
            echo '<span style="color: #46b450; font-weight: bold;">✓ Sponsored</span>';
        }
    } else {
        // Check if currently selected
        global $wpdb;
        $table_name = $wpdb->prefix . 'cfk_sponsorships';
        $selected = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE child_id = %s AND status = 'selected'",
            $child_id
        ));
        
        if ($selected > 0) {
            echo '<span style="color: #e67e22; font-weight: bold;">⏳ Selected</span>';
        } else {
            echo '<span style="color: #27ae60; font-weight: bold;">✓ Available</span>';
        }
    }
}

private function render_last_updated_column(int $post_id): void {
    $modified = get_post_modified_time('U', false, $post_id);
    $now = current_time('timestamp');
    $diff = $now - $modified;
    
    if ($diff < DAY_IN_SECONDS) {
        $time_ago = sprintf(__('%s ago', 'cfk-sponsorship'), human_time_diff($modified, $now));
        echo '<span style="color: #46b450;">' . esc_html($time_ago) . '</span>';
    } elseif ($diff < WEEK_IN_SECONDS) {
        echo '<span style="color: #e67e22;">' . 
             esc_html(sprintf(__('%d days ago', 'cfk-sponsorship'), floor($diff / DAY_IN_SECONDS))) . 
             '</span>';
    } else {
        echo '<span style="color: #666;">' . 
             esc_html(get_post_modified_time('M j, Y', false, $post_id)) . 
             '</span>';
    }
}

public function sortable_child_columns(array $columns): array {
    return array_merge($columns, [
        'child_id' => 'child_id',
        'age_gender' => 'age',
        'family_id' => 'family_id',
        'age_range' => 'age_range',
        'sponsored' => 'sponsored',
        'last_updated' => 'modified'
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

public function add_admin_filters(): void {
    global $typenow;
    
    if ($typenow !== 'child') {
        return;
    }
    
    // Age range filter
    $selected_age_range = $_GET['age_range'] ?? '';
    echo '<select name="age_range">';
    echo '<option value="">' . __('All Age Ranges', 'cfk-sponsorship') . '</option>';
    
    foreach (CFK_AgeRange::cases() as $age_range) {
        $selected = selected($selected_age_range, $age_range->value, false);
        echo '<option value="' . esc_attr($age_range->value) . '" ' . $selected . '>';
        echo esc_html($age_range->getLabel());
        echo '</option>';
    }
    echo '</select>';
    
    // Gender filter
    $selected_gender = $_GET['gender'] ?? '';
    echo '<select name="gender">';
    echo '<option value="">' . __('All Genders', 'cfk-sponsorship') . '</option>';
    
    foreach (CFK_Gender::cases() as $gender) {
        $selected = selected($selected_gender, $gender->value, false);
        echo '<option value="' . esc_attr($gender->value) . '" ' . $selected . '>';
        echo esc_html($gender->getLabel());
        echo '</option>';
    }
    echo '</select>';
    
    // Sponsored status filter
    $selected_sponsored = $_GET['sponsored'] ?? '';
    echo '<select name="sponsored">';
    echo '<option value="">' . __('All Statuses', 'cfk-sponsorship') . '</option>';
    echo '<option value="1" ' . selected($selected_sponsored, '1', false) . '>' . __('Sponsored', 'cfk-sponsorship') . '</option>';
    echo '<option value="0" ' . selected($selected_sponsored, '0', false) . '>' . __('Available', 'cfk-sponsorship') . '</option>';
    echo '</select>';
}

public function filter_children_by_admin_filters(WP_Query $query): void {
    global $pagenow, $typenow;
    
    if (!is_admin() || $pagenow !== 'edit.php' || $typenow !== 'child' || !$query->is_main_query()) {
        return;
    }
    
    $meta_query = ['relation' => 'AND'];
    
    // Age range filter
    if (!empty($_GET['age_range'])) {
        $meta_query[] = [
            'key' => '_child_age_range',
            'value' => sanitize_text_field($_GET['age_range']),
            'compare' => '='
        ];
    }
    
    // Gender filter
    if (!empty($_GET['gender'])) {
        $meta_query[] = [
            'key' => '_child_gender',
            'value' => sanitize_text_field($_GET['gender']),
            'compare' => '='
        ];
    }
    
    // Sponsored status filter
    if (isset($_GET['sponsored']) && $_GET['sponsored'] !== '') {
        $sponsored = $_GET['sponsored'] === '1';
        
        if ($sponsored) {
            $meta_query[] = [
                'key' => '_child_sponsored',
                'value' => '1',
                'compare' => '='
            ];
        } else {
            $meta_query[] = [
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
            ];
        }
    }
    
    if (count($meta_query) > 1) {
        $query->set('meta_query', $meta_query);
    }
}

public function add_bulk_actions(array $actions): array {
    $actions['mark_sponsored'] = __('Mark as Sponsored', 'cfk-sponsorship');
    $actions['mark_available'] = __('Mark as Available', 'cfk-sponsorship');
    $actions['export_selected'] = __('Export Selected', 'cfk-sponsorship');
    
    return $actions;
}

public function handle_bulk_actions(string $redirect_to, string $doaction, array $post_ids): string {
    match($doaction) {
        'mark_sponsored' => $this->bulk_mark_sponsored($post_ids, true),
        'mark_available' => $this->bulk_mark_sponsored($post_ids, false),
        'export_selected' => $this->bulk_export_children($post_ids),
        default => null
    };
    
    return $redirect_to;
}

private function bulk_mark_sponsored(array $post_ids, bool $sponsored): void {
    $updated = 0;
    
    foreach ($post_ids as $post_id) {
        $child_id = get_post_meta($post_id, '_child_id', true);
        if ($child_id && self::mark_as_sponsored($child_id, $sponsored)) {
            $updated++;
        }
    }
    
    $message = $sponsored 
        ? sprintf(__('%d children marked as sponsored', 'cfk-sponsorship'), $updated)
        : sprintf(__('%d children marked as available', 'cfk-sponsorship'), $updated);
    
    add_action('admin_notices', function() use ($message) {
        echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
    });
}

private function bulk_export_children(array $post_ids): void {
    $children = get_posts([
        'post_type' => 'child',
        'post__in' => $post_ids,
        'posts_per_page' => -1
    ]);
    
    if (empty($children)) {
        return;
    }
    
    $filename = 'cfk-selected-children-' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'Child ID', 'Name', 'Age', 'Gender', 'Family ID', 
        'Age Range', 'Clothing Info', 'Gift Requests', 'Sponsored'
    ]);
    
    // CSV data
    foreach ($children as $child) {
        $details = self::get_child_details($child);
        if ($details) {
            fputcsv($output, [
                $details->id, $details->name, $details->age, 
                $details->gender->value, $details->family_id,
                $details->age_range->value, $details->clothing_info,
                $details->gift_requests, $details->sponsored ? 'Yes' : 'No'
            ]);
        }
    }
    
    fclose($output);
    exit;
}

// Register additional admin hooks
private function register_admin_hooks(): void {
    add_action('restrict_manage_posts', $this->add_admin_filters(...));
    add_action('pre_get_posts', $this->filter_children_by_admin_filters(...));
    add_filter('bulk_actions-edit-child', $this->add_bulk_actions(...));
    add_filter('handle_bulk_actions-edit-child', $this->handle_bulk_actions(...), 10, 3);
}

?>
