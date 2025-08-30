<?php
/**
 * Statistics Methods for CFK Admin Dashboard
 * Part of the modular dashboard architecture following the 300-line rule
 */

trait CFK_Dashboard_Stats_Trait {
    
    /**
     * Get comprehensive dashboard statistics
     */
    private function get_comprehensive_stats() {
        return array(
            'sponsored_children' => $this->get_sponsored_count(),
            'available_children' => $this->get_available_count(),
            'total_families' => $this->get_families_count(),
            'active_sponsors' => $this->get_sponsors_count(),
            'emails_sent' => $this->get_emails_sent_count(),
            'age_breakdown' => $this->get_age_breakdown(),
            'recent_activity' => $this->get_recent_activity(),
            'system_status' => $this->get_system_status()
        );
    }
    
    /**
     * Get count of sponsored children
     */
    private function get_sponsored_count() {
        return (int) get_posts(array(
            'post_type' => 'child',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'is_sponsored',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        ));
    }
    
    /**
     * Get count of available children
     */
    private function get_available_count() {
        return (int) get_posts(array(
            'post_type' => 'child',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'is_sponsored',
                    'value' => '1',
                    'compare' => '!='
                )
            ),
            'fields' => 'ids'
        ));
    }
    
    /**
     * Get age breakdown statistics
     */
    private function get_age_breakdown() {
        $children = get_posts(array(
            'post_type' => 'child',
            'posts_per_page' => -1
        ));
        
        $breakdown = array(
            '0-5' => array('sponsored' => 0, 'available' => 0),
            '6-10' => array('sponsored' => 0, 'available' => 0),
            '11-15' => array('sponsored' => 0, 'available' => 0),
            '16+' => array('sponsored' => 0, 'available' => 0)
        );
        
        foreach ($children as $child) {
            $age = (int) get_post_meta($child->ID, 'age', true);
            $is_sponsored = get_post_meta($child->ID, 'is_sponsored', true);
            
            $age_group = $this->get_age_group($age);
            $status = $is_sponsored ? 'sponsored' : 'available';
            
            if (isset($breakdown[$age_group])) {
                $breakdown[$age_group][$status]++;
            }
        }
        
        return $breakdown;
    }
    
    /**
     * Get recent activity data
     */
    private function get_recent_activity() {
        $activities = array();
        
        // Recent sponsorships
        $recent_sponsorships = get_posts(array(
            'post_type' => 'sponsorship',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        foreach ($recent_sponsorships as $sponsorship) {
            $child_id = get_post_meta($sponsorship->ID, 'child_id', true);
            $sponsor_name = get_post_meta($sponsorship->ID, 'sponsor_name', true);
            $child_name = get_the_title($child_id);
            
            $activities[] = array(
                'type' => 'sponsorship',
                'message' => sprintf(__('%s sponsored %s', 'cfk-sponsorship'), $sponsor_name, $child_name),
                'date' => $sponsorship->post_date
            );
        }
        
        // Recent children added
        $recent_children = get_posts(array(
            'post_type' => 'child',
            'posts_per_page' => 3,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        foreach ($recent_children as $child) {
            $activities[] = array(
                'type' => 'child_added',
                'message' => sprintf(__('New child added: %s', 'cfk-sponsorship'), $child->post_title),
                'date' => $child->post_date
            );
        }
        
        // Sort by date
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return array_slice($activities, 0, 8);
    }
    
    /**
     * Get system status information
     */
    private function get_system_status() {
        return array(
            'database_status' => 'healthy',
            'email_system' => $this->check_email_system(),
            'file_permissions' => $this->check_file_permissions(),
            'sponsorships_open' => ChristmasForKidsPlugin::get_option('cfk_sponsorships_open', false),
            'last_backup' => ChristmasForKidsPlugin::get_option('cfk_last_backup', 'Never')
        );
    }
    
    /**
     * Get count of total families
     */
    private function get_families_count() {
        return count(get_posts(array(
            'post_type' => 'child',
            'posts_per_page' => -1,
            'fields' => 'ids'
        )));
    }
    
    /**
     * Get count of active sponsors
     */
    private function get_sponsors_count() {
        $sponsorships = get_posts(array(
            'post_type' => 'sponsorship',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $unique_sponsors = array();
        foreach ($sponsorships as $sponsorship) {
            $email = get_post_meta($sponsorship->ID, 'sponsor_email', true);
            if ($email) {
                $unique_sponsors[$email] = true;
            }
        }
        
        return count($unique_sponsors);
    }
    
    /**
     * Get count of emails sent
     */
    private function get_emails_sent_count() {
        return count(get_posts(array(
            'post_type' => 'cfk_email',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'status',
                    'value' => 'sent',
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        )));
    }
    
    /**
     * Get age group for a given age
     */
    private function get_age_group($age) {
        if ($age <= 5) return '0-5';
        if ($age <= 10) return '6-10';
        if ($age <= 15) return '11-15';
        return '16+';
    }
    
    /**
     * Check email system status
     */
    private function check_email_system() {
        // Simple check - could be expanded
        return function_exists('wp_mail') ? 'working' : 'error';
    }
    
    /**
     * Check file permissions
     */
    private function check_file_permissions() {
        $upload_dir = wp_upload_dir();
        return is_writable($upload_dir['basedir']) ? 'writable' : 'error';
    }
}