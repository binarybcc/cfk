<?php
/**
 * AJAX Handler Methods for CFK Admin Dashboard
 * Part of the modular dashboard architecture following the 300-line rule
 */

trait CFK_Dashboard_Ajax_Trait {
    
    /**
     * Handle AJAX request to toggle sponsorships
     */
    public function ajax_toggle_sponsorships() {
        check_ajax_referer('cfk_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'cfk-sponsorship'));
        }

        $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
        $result = update_option('cfk_sponsorships_open', $status);
        
        if ($result) {
            $message = $status ? 
                __('Sponsorships are now open', 'cfk-sponsorship') : 
                __('Sponsorships are now closed', 'cfk-sponsorship');
            wp_send_json_success($message);
        } else {
            wp_send_json_error(__('Failed to update sponsorship status', 'cfk-sponsorship'));
        }
    }
    
    /**
     * Handle AJAX request to get updated dashboard stats
     */
    public function ajax_get_dashboard_stats() {
        check_ajax_referer('cfk_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'cfk-sponsorship'));
        }
        
        $stats = $this->get_comprehensive_stats();
        wp_send_json_success($stats);
    }
    
    /**
     * Handle AJAX export requests
     */
    public function ajax_export_data() {
        check_ajax_referer('cfk_admin_nonce', 'nonce');
        
        if (!current_user_can('export')) {
            wp_die(__('Unauthorized access', 'cfk-sponsorship'));
        }
        
        $export_type = sanitize_text_field($_POST['export_type']);
        
        switch ($export_type) {
            case 'sponsorships':
                $this->export_sponsorships();
                break;
            case 'children':
                $this->export_children();
                break;
            case 'emails':
                $this->export_emails();
                break;
            default:
                wp_send_json_error(__('Invalid export type', 'cfk-sponsorship'));
        }
    }
}