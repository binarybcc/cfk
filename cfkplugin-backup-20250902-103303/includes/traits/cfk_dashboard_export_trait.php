<?php
/**
 * Export Handler Methods for CFK Admin Dashboard
 * Part of the modular dashboard architecture following the 300-line rule
 */

trait CFK_Dashboard_Export_Trait {
    
    /**
     * Export sponsorships data to CSV
     */
    private function export_sponsorships() {
        $data = array();
        $data[] = array('Child Name', 'Sponsor Name', 'Sponsor Email', 'Date Sponsored', 'Status');
        
        $sponsorships = get_posts(array(
            'post_type' => 'sponsorship',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'private')
        ));
        
        foreach ($sponsorships as $sponsorship) {
            $child_id = get_post_meta($sponsorship->ID, 'child_id', true);
            $sponsor_name = get_post_meta($sponsorship->ID, 'sponsor_name', true);
            $sponsor_email = get_post_meta($sponsorship->ID, 'sponsor_email', true);
            $child_name = get_the_title($child_id);
            
            $data[] = array(
                $child_name,
                $sponsor_name,
                $sponsor_email,
                $sponsorship->post_date,
                $sponsorship->post_status
            );
        }
        
        $this->output_csv('sponsorships', $data);
    }
    
    /**
     * Export children data to CSV
     */
    private function export_children() {
        $data = array();
        $data[] = array('Name', 'Age', 'Gender', 'Story', 'Status', 'Date Added');
        
        $children = get_posts(array(
            'post_type' => 'child',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'private')
        ));
        
        foreach ($children as $child) {
            $age = get_post_meta($child->ID, 'age', true);
            $gender = get_post_meta($child->ID, 'gender', true);
            $story = get_post_meta($child->ID, 'story', true);
            $is_sponsored = get_post_meta($child->ID, 'is_sponsored', true) ? 'Sponsored' : 'Available';
            
            $data[] = array(
                $child->post_title,
                $age,
                $gender,
                wp_strip_all_tags($story),
                $is_sponsored,
                $child->post_date
            );
        }
        
        $this->output_csv('children', $data);
    }
    
    /**
     * Export email log data to CSV
     */
    private function export_emails() {
        $data = array();
        $data[] = array('Subject', 'Recipient', 'Type', 'Status', 'Date Sent');
        
        $emails = get_posts(array(
            'post_type' => 'cfk_email',
            'posts_per_page' => -1
        ));
        
        foreach ($emails as $email) {
            $recipient = get_post_meta($email->ID, 'recipient', true);
            $type = get_post_meta($email->ID, 'email_type', true);
            $status = get_post_meta($email->ID, 'status', true);
            
            $data[] = array(
                $email->post_title,
                $recipient,
                $type,
                $status,
                $email->post_date
            );
        }
        
        $this->output_csv('emails', $data);
    }
    
    /**
     * Output CSV file for download
     */
    private function output_csv($filename, $data) {
        $filename = sanitize_file_name($filename . '_' . date('Y-m-d') . '.csv');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        $output = fopen('php://output', 'w');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}