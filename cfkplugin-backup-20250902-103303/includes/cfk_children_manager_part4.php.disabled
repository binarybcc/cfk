<?php
/**
 * Children Manager Class - Part 4: Query & Search Methods
 * Database queries, search functionality, and child retrieval methods
 *
 * @package ChristmasForKids
 */

declare(strict_types=1);

// This file extends the CFK_Children_Manager class
// Add these static and instance methods to the class:

public static function get_available_children(CFK_ChildQuery $query = new CFK_ChildQuery()): array {
    $query_args = $query->to_wp_query_args();
    
    // Ensure we only get unsponsored children
    if ($query->sponsored === null) {
        $query_args['meta_query'][] = [
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
    
    $children = get_posts($query_args);
    
    // Exclude currently selected children if requested
    if ($query->exclude_selected) {
        $children = self::filter_selected_children($children);
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
    if (empty($child_id)) {
        return null;
    }
    
    $posts = get_posts([
        'post_type' => 'child',
        'meta_key' => '_child_id',
        'meta_value' => $child_id,
        'posts_per_page' => 1,
        'post_status' => ['publish', 'draft']
    ]);
    
    return $posts[0] ?? null;
}

public static function get_children_by_family(string $family_id): array {
    if (empty($family_id)) {
        return [];
    }
    
    return get_posts([
        'post_type' => 'child',
        'meta_key' => '_child_family_id',
        'meta_value' => $family_id,
        'posts_per_page' => -1,
        'orderby' => 'meta_value_num',
        'meta_key' => '_child_age',
        'order' => 'DESC',
        'post_status' => 'publish'
    ]);
}

public static function get_children_by_age_range(CFK_AgeRange $age_range): array {
    return get_posts([
        'post_type' => 'child',
        'meta_key' => '_child_age_range',
        'meta_value' => $age_range->value,
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
}

public static function get_children_by_status(?bool $sponsored = null): array {
    $query_args = [
        'post_type' => 'child',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
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
        ];
    }
    
    return get_posts($query_args);
}

public static function search_children(string $search_term, CFK_ChildQuery $query = new CFK_ChildQuery()): array {
    $query_args = $query->to_wp_query_args();
    $query_args['s'] = $search_term;
    
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
    
    if (!str_contains($join, $wpdb->postmeta)) {
        $join .= " LEFT JOIN {$wpdb->postmeta} pm ON {$wpdb->posts}.ID = pm.post_id ";
    }
    
    return $join;
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

public static function get_children_count_by_age_range(): array {
    $counts = [];
    
    foreach (CFK_AgeRange::cases() as $age_range) {
        $count = get_posts([
            'post_type' => 'child',
            'meta_key' => '_child_age_range',
            'meta_value' => $age_range->value,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish'
        ]);
        
        $counts[$age_range->value] = count($count);
    }
    
    return $counts;
}

public static function get_children_count_by_gender(): array {
    $counts = [];
    
    foreach (CFK_Gender::cases() as $gender) {
        $count = get_posts([
            'post_type' => 'child',
            'meta_key' => '_child_gender',
            'meta_value' => $gender->value,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish'
        ]);
        
        $counts[$gender->value] = count($count);
    }
    
    return $counts;
}

public static function get_family_statistics(): array {
    global $wpdb;
    
    // Get families with multiple children
    $family_stats = $wpdb->get_results("
        SELECT 
            pm.meta_value as family_id,
            COUNT(*) as child_count,
            GROUP_CONCAT(p.post_title ORDER BY pm2.meta_value + 0 DESC SEPARATOR ', ') as children_names,
            GROUP_CONCAT(pm3.meta_value ORDER BY pm2.meta_value + 0 DESC SEPARATOR ', ') as children_ages
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_child_age'
        LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_child_id'
        WHERE pm.meta_key = '_child_family_id'
        AND pm.meta_value != ''
        AND p.post_type = 'child'
        AND p.post_status = 'publish'
        GROUP BY pm.meta_value
        ORDER BY child_count DESC, pm.meta_value ASC
    ");
    
    $single_children = $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_child_family_id'
        WHERE p.post_type = 'child'
        AND p.post_status = 'publish'
        AND (pm.meta_value IS NULL OR pm.meta_value = '')
    ");
    
    return [
        'families' => $family_stats,
        'single_children' => intval($single_children),
        'total_families' => count($family_stats),
        'largest_family' => !empty($family_stats) ? max(array_column($family_stats, 'child_count')) : 0
    ];
}

public static function get_recently_added_children(int $limit = 10): array {
    return get_posts([
        'post_type' => 'child',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_status' => 'publish'
    ]);
}

public static function get_recently_sponsored_children(int $limit = 10): array {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'cfk_sponsorships';
    
    $recent_sponsorships = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT child_id, confirmed_time
        FROM $table_name 
        WHERE status = 'confirmed'
        ORDER BY confirmed_time DESC
        LIMIT %d
    ", $limit));
    
    $children = [];
    foreach ($recent_sponsorships as $sponsorship) {
        $child_post = self::get_child_by_id($sponsorship->child_id);
        if ($child_post) {
            $children[] = $child_post;
        }
    }
    
    return $children;
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
    
    // Log the change
    error_log(sprintf(
        'CFK: Child %s (ID: %s) marked as %s',
        $child_post->post_title,
        $child_id,
        $sponsored ? 'sponsored' : 'available'
    ));
    
    return true;
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

public static function get_children_needing_attention(): array {
    $issues = [
        'missing_photos' => [],
        'missing_info' => [],
        'age_mismatch' => [],
        'incomplete_data' => []
    ];
    
    $all_children = get_posts([
        'post_type' => 'child',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    foreach ($all_children as $child) {
        $child_details = self::get_child_details($child);
        
        if (!$child_details) {
            $issues['incomplete_data'][] = $child;
            continue;
        }
        
        // Check for missing photo
        if (!has_post_thumbnail($child->ID)) {
            $issues['missing_photos'][] = $child;
        }
        
        // Check for missing important info
        if (empty($child_details->clothing_info) || empty($child_details->gift_requests)) {
            $issues['missing_info'][] = $child;
        }
        
        // Check age vs age range mismatch
        $age_group = $child_details->age_range->getAgeGroup();
        if ($child_details->age < $age_group['min'] || $child_details->age > $age_group['max']) {
            $issues['age_mismatch'][] = $child;
        }
    }
    
    return $issues;
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

public static function find_duplicate_child_ids(): array {
    global $wpdb;
    
    $duplicates = $wpdb->get_results("
        SELECT pm.meta_value as child_id, COUNT(*) as count, 
               GROUP_CONCAT(p.ID) as post_ids,
               GROUP_CONCAT(p.post_title SEPARATOR ' | ') as titles
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_child_id'
        AND pm.meta_value != ''
        AND p.post_type = 'child'
        GROUP BY pm.meta_value
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ");
    
    return $duplicates;
}

?>
