<?php
/**
 * Settings Page Template
 * Configuration interface for Christmas for Kids plugin
 *
 * @package ChristmasForKids
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure user has proper permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'cfk-sponsorship'));
}

// Handle form submission
if (isset($_POST['cfk_save_settings'])) {
    if (!wp_verify_nonce($_POST['cfk_settings_nonce'], 'cfk_save_settings')) {
        wp_die(__('Security check failed', 'cfk-sponsorship'));
    }
    
    // Save settings
    $settings_saved = cfk_save_settings($_POST);
    
    if ($settings_saved) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'cfk-sponsorship') . '</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('Error saving settings. Please try again.', 'cfk-sponsorship') . '</p></div>';
    }
}

// Get current settings
$current_settings = cfk_get_all_settings();

// Add AJAX handlers for this page
add_action('wp_ajax_cfk_send_test_email', 'cfk_handle_test_email');
add_action('wp_ajax_cfk_danger_action', 'cfk_handle_danger_action');

?>

<div class="wrap cfk-settings-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="cfk-settings-nav">
        <h2 class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active" data-tab="general"><?php _e('General', 'cfk-sponsorship'); ?></a>
            <a href="#email" class="nav-tab" data-tab="email"><?php _e('Email Settings', 'cfk-sponsorship'); ?></a>
            <a href="#frontend" class="nav-tab" data-tab="frontend"><?php _e('Frontend', 'cfk-sponsorship'); ?></a>
            <a href="#advanced" class="nav-tab" data-tab="advanced"><?php _e('Advanced', 'cfk-sponsorship'); ?></a>
        </h2>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('cfk_save_settings', 'cfk_settings_nonce'); ?>
        
        <!-- General Settings Tab -->
        <div id="tab-general" class="cfk-tab-content cfk-tab-active">
            <div class="cfk-settings-section">
                <h2><?php _e('General Settings', 'cfk-sponsorship'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cfk_sponsorships_open"><?php _e('Sponsorships Status', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <label class="cfk-toggle-switch">
                                <input type="checkbox" id="cfk_sponsorships_open" name="cfk_sponsorships_open" value="1" 
                                       <?php checked($current_settings['cfk_sponsorships_open']); ?>>
                                <span class="cfk-toggle-slider"></span>
                            </label>
                            <span class="cfk-toggle-label">
                                <?php _e('Allow new sponsorships', 'cfk-sponsorship'); ?>
                            </span>
                            <p class="description">
                                <?php _e('When disabled, the frontend will show that sponsorships are closed and prevent new applications.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_deadline_date"><?php _e('Gift Drop-off Deadline', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cfk_deadline_date" name="cfk_deadline_date" 
                                   value="<?php echo esc_attr($current_settings['cfk_deadline_date']); ?>" 
                                   class="regular-text" placeholder="<?php _e('December 15, 2025', 'cfk-sponsorship'); ?>">
                            <p class="description">
                                <?php _e('This date will be included in sponsor confirmation emails. Use a human-readable format.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_selection_timeout"><?php _e('Selection Timeout', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cfk_selection_timeout" name="cfk_selection_timeout" 
                                   value="<?php echo esc_attr($current_settings['cfk_selection_timeout']); ?>" 
                                   min="1" max="24" class="small-text">
                            <span><?php _e('hours', 'cfk-sponsorship'); ?></span>
                            <p class="description">
                                <?php _e('How long children remain "reserved" in a user\'s cart before being automatically released.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_drop_off_locations"><?php _e('Drop-off Locations', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <textarea id="cfk_drop_off_locations" name="cfk_drop_off_locations" rows="5" cols="50" 
                                      class="large-text"><?php echo esc_textarea(is_array($current_settings['cfk_drop_off_locations']) ? implode("\n", $current_settings['cfk_drop_off_locations']) : $current_settings['cfk_drop_off_locations']); ?></textarea>
                            <p class="description">
                                <?php _e('Enter each location on a separate line. These will be included in sponsor emails and thank you pages.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_average_sponsorship_value"><?php _e('Average Sponsorship Value', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cfk_average_sponsorship_value" name="cfk_average_sponsorship_value" 
                                   value="<?php echo esc_attr($current_settings['cfk_average_sponsorship_value']); ?>" 
                                   min="0" step="0.01" class="regular-text">
                            <p class="description">
                                <?php _e('Used for calculating total sponsorship value in reports. Enter amount without currency symbol.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Email Settings Tab -->
        <div id="tab-email" class="cfk-tab-content">
            <div class="cfk-settings-section">
                <h2><?php _e('Email Configuration', 'cfk-sponsorship'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cfk_email_from_name"><?php _e('From Name', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cfk_email_from_name" name="cfk_email_from_name" 
                                   value="<?php echo esc_attr($current_settings['cfk_email_from_name']); ?>" 
                                   class="regular-text" required>
                            <p class="description">
                                <?php _e('The name that appears in the "From" field of emails sent to sponsors.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_email_from_email"><?php _e('From Email Address', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="cfk_email_from_email" name="cfk_email_from_email" 
                                   value="<?php echo esc_attr($current_settings['cfk_email_from_email']); ?>" 
                                   class="regular-text" required>
                            <p class="description">
                                <?php _e('The email address that sponsors will see emails coming from. Should be a valid email for your domain.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_admin_email"><?php _e('Admin Notification Email', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="cfk_admin_email" name="cfk_admin_email" 
                                   value="<?php echo esc_attr($current_settings['cfk_admin_email']); ?>" 
                                   class="regular-text" required>
                            <p class="description">
                                <?php _e('Email address where admin notifications about new sponsorships will be sent.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div class="cfk-email-test-section">
                    <h3><?php _e('Test Email Configuration', 'cfk-sponsorship'); ?></h3>
                    <p><?php _e('Send a test email to verify your email settings are working correctly.', 'cfk-sponsorship'); ?></p>
                    
                    <div class="cfk-test-email-form">
                        <input type="email" id="cfk_test_email" placeholder="<?php _e('Enter email address', 'cfk-sponsorship'); ?>" 
                               class="regular-text">
                        <button type="button" id="cfk_send_test_email" class="button">
                            <?php _e('Send Test Email', 'cfk-sponsorship'); ?>
                        </button>
                        <div id="cfk_test_email_result"></div>
                    </div>
                </div>
            </div>
            
            <div class="cfk-settings-section">
                <h2><?php _e('Email Templates', 'cfk-sponsorship'); ?></h2>
                <p class="description">
                    <?php _e('Customize the content that appears in emails sent to sponsors. You can use the following placeholders:', 'cfk-sponsorship'); ?>
                </p>
                
                <div class="cfk-template-placeholders">
                    <h4><?php _e('Available Placeholders:', 'cfk-sponsorship'); ?></h4>
                    <ul>
                        <li><code>{sponsor_name}</code> - <?php _e('Sponsor\'s full name', 'cfk-sponsorship'); ?></li>
                        <li><code>{children_list}</code> - <?php _e('List of sponsored children', 'cfk-sponsorship'); ?></li>
                        <li><code>{deadline_date}</code> - <?php _e('Gift drop-off deadline', 'cfk-sponsorship'); ?></li>
                        <li><code>{drop_off_locations}</code> - <?php _e('List of drop-off locations', 'cfk-sponsorship'); ?></li>
                        <li><code>{organization_name}</code> - <?php _e('Your organization name', 'cfk-sponsorship'); ?></li>
                    </ul>
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cfk_email_footer_text"><?php _e('Email Footer Text', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <textarea id="cfk_email_footer_text" name="cfk_email_footer_text" rows="3" cols="50" 
                                      class="large-text"><?php echo esc_textarea($current_settings['cfk_email_footer_text']); ?></textarea>
                            <p class="description">
                                <?php _e('Text that appears at the bottom of all emails. Leave empty for default footer.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_additional_instructions"><?php _e('Additional Instructions', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <textarea id="cfk_additional_instructions" name="cfk_additional_instructions" rows="5" cols="50" 
                                      class="large-text"><?php echo esc_textarea($current_settings['cfk_additional_instructions']); ?></textarea>
                            <p class="description">
                                <?php _e('Extra instructions or information to include in sponsor confirmation emails.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Frontend Settings Tab -->
        <div id="tab-frontend" class="cfk-tab-content">
            <div class="cfk-settings-section">
                <h2><?php _e('Frontend Display Options', 'cfk-sponsorship'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cfk_children_per_page"><?php _e('Children Per Page', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cfk_children_per_page" name="cfk_children_per_page" 
                                   value="<?php echo esc_attr($current_settings['cfk_children_per_page']); ?>" 
                                   min="1" max="50" class="small-text">
                            <p class="description">
                                <?php _e('Default number of children to display per page in the children grid.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_default_columns"><?php _e('Grid Columns', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <select id="cfk_default_columns" name="cfk_default_columns">
                                <option value="2" <?php selected($current_settings['cfk_default_columns'], '2'); ?>>2 <?php _e('columns', 'cfk-sponsorship'); ?></option>
                                <option value="3" <?php selected($current_settings['cfk_default_columns'], '3'); ?>>3 <?php _e('columns', 'cfk-sponsorship'); ?></option>
                                <option value="4" <?php selected($current_settings['cfk_default_columns'], '4'); ?>>4 <?php _e('columns', 'cfk-sponsorship'); ?></option>
                            </select>
                            <p class="description">
                                <?php _e('Default number of columns for the children grid layout.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_show_filters"><?php _e('Show Filters', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="cfk_show_filters" name="cfk_show_filters" value="1" 
                                       <?php checked($current_settings['cfk_show_filters']); ?>>
                                <?php _e('Display age and gender filters on children grid', 'cfk-sponsorship'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Allow users to filter children by age range and gender.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_show_clothing_info"><?php _e('Show Clothing Information', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="cfk_show_clothing_info" name="cfk_show_clothing_info" value="1" 
                                       <?php checked($current_settings['cfk_show_clothing_info']); ?>>
                                <?php _e('Display clothing size information on child cards', 'cfk-sponsorship'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Show clothing sizes on the public child browsing interface.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_require_phone"><?php _e('Require Phone Number', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="cfk_require_phone" name="cfk_require_phone" value="1" 
                                       <?php checked($current_settings['cfk_require_phone']); ?>>
                                <?php _e('Make phone number required in sponsorship form', 'cfk-sponsorship'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, sponsors must provide a phone number to complete their sponsorship.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_require_address"><?php _e('Require Address', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="cfk_require_address" name="cfk_require_address" value="1" 
                                       <?php checked($current_settings['cfk_require_address']); ?>>
                                <?php _e('Make address required in sponsorship form', 'cfk-sponsorship'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, sponsors must provide their address to complete their sponsorship.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="cfk-settings-section">
                <h2><?php _e('Custom Messages', 'cfk-sponsorship'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cfk_closed_message"><?php _e('Sponsorships Closed Message', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <textarea id="cfk_closed_message" name="cfk_closed_message" rows="4" cols="50" 
                                      class="large-text"><?php echo esc_textarea($current_settings['cfk_closed_message']); ?></textarea>
                            <p class="description">
                                <?php _e('Message displayed when sponsorships are closed. Leave empty for default message.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_thank_you_message"><?php _e('Thank You Message', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <textarea id="cfk_thank_you_message" name="cfk_thank_you_message" rows="4" cols="50" 
                                      class="large-text"><?php echo esc_textarea($current_settings['cfk_thank_you_message']); ?></textarea>
                            <p class="description">
                                <?php _e('Additional message to display on the thank you page after sponsorship confirmation.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Advanced Settings Tab -->
        <div id="tab-advanced" class="cfk-tab-content">
            <div class="cfk-settings-section">
                <h2><?php _e('Advanced Settings', 'cfk-sponsorship'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cfk_enable_logging"><?php _e('Enable Debug Logging', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="cfk_enable_logging" name="cfk_enable_logging" value="1" 
                                       <?php checked($current_settings['cfk_enable_logging']); ?>>
                                <?php _e('Log plugin activities for debugging', 'cfk-sponsorship'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Enable detailed logging of plugin activities. Only enable if troubleshooting issues.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_delete_data_on_uninstall"><?php _e('Delete Data on Uninstall', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="cfk_delete_data_on_uninstall" name="cfk_delete_data_on_uninstall" value="1" 
                                       <?php checked($current_settings['cfk_delete_data_on_uninstall']); ?>>
                                <?php _e('Remove all plugin data when uninstalling', 'cfk-sponsorship'); ?>
                            </label>
                            <p class="description">
                                <?php _e('⚠️ WARNING: This will permanently delete all children, sponsorships, and emails when you uninstall the plugin.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_allow_duplicate_emails"><?php _e('Allow Duplicate Email Addresses', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="cfk_allow_duplicate_emails" name="cfk_allow_duplicate_emails" value="1" 
                                       <?php checked($current_settings['cfk_allow_duplicate_emails']); ?>>
                                <?php _e('Allow the same email address to sponsor multiple times', 'cfk-sponsorship'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When disabled, each email address can only be used for one sponsorship.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_max_children_per_sponsor"><?php _e('Maximum Children Per Sponsor', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cfk_max_children_per_sponsor" name="cfk_max_children_per_sponsor" 
                                   value="<?php echo esc_attr($current_settings['cfk_max_children_per_sponsor']); ?>" 
                                   min="0" max="50" class="small-text">
                            <p class="description">
                                <?php _e('Maximum number of children one sponsor can select. Set to 0 for no limit.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cfk_custom_css"><?php _e('Custom CSS', 'cfk-sponsorship'); ?></label>
                        </th>
                        <td>
                            <textarea id="cfk_custom_css" name="cfk_custom_css" rows="10" cols="50" 
                                      class="large-text code"><?php echo esc_textarea($current_settings['cfk_custom_css']); ?></textarea>
                            <p class="description">
                                <?php _e('Add custom CSS to override default plugin styling. This CSS will be loaded on pages with plugin shortcodes.', 'cfk-sponsorship'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="cfk-settings-section cfk-danger-zone">
                <h2><?php _e('Danger Zone', 'cfk-sponsorship'); ?></h2>
                <p class="description">
                    <?php _e('These actions cannot be undone. Please use with caution.', 'cfk-sponsorship'); ?>
                </p>
                
                <div class="cfk-danger-actions">
                    <div class="cfk-danger-action">
                        <h4><?php _e('Reset All Settings', 'cfk-sponsorship'); ?></h4>
                        <p><?php _e('Reset all plugin settings to their default values.', 'cfk-sponsorship'); ?></p>
                        <button type="button" id="cfk_reset_settings" class="button button-secondary">
                            <?php _e('Reset Settings', 'cfk-sponsorship'); ?>
                        </button>
                    </div>
                    
                    <div class="cfk-danger-action">
                        <h4><?php _e('Clear All Sponsorship Data', 'cfk-sponsorship'); ?></h4>
                        <p><?php _e('Remove all sponsorship records and email logs. Children data will be preserved.', 'cfk-sponsorship'); ?></p>
                        <button type="button" id="cfk_clear_sponsorships" class="button button-secondary">
                            <?php _e('Clear Sponsorships', 'cfk-sponsorship'); ?>
                        </button>
                    </div>
                    
                    <div class="cfk-danger-action">
                        <h4><?php _e('Reset Sponsored Status', 'cfk-sponsorship'); ?></h4>
                        <p><?php _e('Mark all children as unsponsored. Useful for starting a new season.', 'cfk-sponsorship'); ?></p>
                        <button type="button" id="cfk_reset_sponsored" class="button button-secondary">
                            <?php _e('Reset All Children', 'cfk-sponsorship'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="cfk-settings-footer">
            <p class="submit">
                <input type="submit" name="cfk_save_settings" class="button-primary" 
                       value="<?php _e('Save All Settings', 'cfk-sponsorship'); ?>">
                <button type="button" id="cfk_preview_settings" class="button">
                    <?php _e('Preview Changes', 'cfk-sponsorship'); ?>
                </button>
            </p>
        </div>
    </form>
</div>

<style>
.cfk-settings-page {
    max-width: 1000px;
}

.cfk-settings-nav {
    margin-bottom: 20px;
}

.cfk-tab-content {
    display: none;
    background: white;
    border: 1px solid #c3c4c7;
    border-top: none;
    padding: 20px;
}

.cfk-tab-active {
    display: block;
}

.cfk-settings-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #f0f0f0;
}

.cfk-settings-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.cfk-settings-section h2 {
    color: #23282d;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 8px;
    margin-bottom: 20px;
}

.cfk-toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
    margin-right: 10px;
}

.cfk-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.cfk-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    border-radius: 24px;
    transition: 0.3s;
}

.cfk-toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: 0.3s;
}

input:checked + .cfk-toggle-slider {
    background-color: #0073aa;
}

input:checked + .cfk-toggle-slider:before {
    transform: translateX(26px);
}

.cfk-toggle-label {
    vertical-align: middle;
    margin-left: 5px;
}

.cfk-test-email-form {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

#cfk_test_email_result {
    margin-left: 10px;
    font-weight: bold;
}

.cfk-template-placeholders {
    background: #f8f9fa;
    border-left: 4px solid #0073aa;
    padding: 15px;
    margin: 15px 0;
}

.cfk-template-placeholders h4 {
    margin-top: 0;
    color: #23282d;
}

.cfk-template-placeholders ul {
    margin-bottom: 0;
}

.cfk-template-placeholders code {
    background: #fff;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}

.cfk-danger-zone {
    border: 2px solid #dc3545;
    border-radius: 6px;
    background: #fef2f2;
    padding: 20px;
}

.cfk-danger-zone h2 {
    color: #dc3545;
    border-bottom-color: #dc3545;
}

.cfk-danger-actions {
    display: grid;
    gap: 20px;
}

.cfk-danger-action {
    padding: 15px;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    background: white;
}

.cfk-danger-action h4 {
    margin: 0 0 8px 0;
    color: #721c24;
}

.cfk-danger-action p {
    margin: 0 0 10px 0;
    color: #856404;
}

.cfk-settings-footer {
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    padding: 20px;
    margin: 20px -20px -20px -20px;
    text-align: center;
}

@media (max-width: 768px) {
    .cfk-test-email-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    #cfk_test_email_result {
        margin-left: 0;
        margin-top: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs and content
        $('.nav-tab').removeClass('nav-tab-active');
        $('.cfk-tab-content').removeClass('cfk-tab-active');
        
        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');
        
        // Show corresponding content
        const tabId = $(this).data('tab');
        $('#tab-' + tabId).addClass('cfk-tab-active');
        
        // Update URL hash
        window.location.hash = tabId;
    });
    
    // Load tab from URL hash
    function loadTabFromHash() {
        const hash = window.location.hash.substring(1);
        if (hash && $('#tab-' + hash).length) {
            $('.nav-tab[data-tab="' + hash + '"]').click();
        }
    }
    loadTabFromHash();
    
    // Test email functionality
    $('#cfk_send_test_email').click(function() {
        const email = $('#cfk_test_email').val();
        const button = $(this);
        const result = $('#cfk_test_email_result');
        
        if (!email) {
            result.html('<span style="color: #dc3545;">Please enter an email address</span>');
            return;
        }
        
        button.prop('disabled', true).text('<?php echo esc_js(__('Sending...', 'cfk-sponsorship')); ?>');
        result.html('');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cfk_send_test_email',
                test_email: email,
                nonce: '<?php echo wp_create_nonce('cfk_test_email'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    result.html('<span style="color: #27ae60;">✓ Test email sent successfully!</span>');
                } else {
                    result.html('<span style="color: #dc3545;">✗ ' + response.data + '</span>');
                }
            },
            error: function() {
                result.html('<span style="color: #dc3545;">✗ Network error occurred</span>');
            },
            complete: function() {
                button.prop('disabled', false).text('<?php echo esc_js(__('Send Test Email', 'cfk-sponsorship')); ?>');
            }
        });
    });
    
    // Danger zone actions
    $('#cfk_reset_settings').click(function() {
        if (confirm('<?php echo esc_js(__('Are you sure you want to reset all settings to their default values? This cannot be undone.', 'cfk-sponsorship')); ?>')) {
            if (confirm('<?php echo esc_js(__('This will reset ALL plugin settings. Are you absolutely sure?', 'cfk-sponsorship')); ?>')) {
                performDangerAction('reset_settings', $(this));
            }
        }
    });
    
    $('#cfk_clear_sponsorships').click(function() {
        if (confirm('<?php echo esc_js(__('Are you sure you want to delete all sponsorship data? This will remove all sponsor information and email logs.', 'cfk-sponsorship')); ?>')) {
            if (confirm('<?php echo esc_js(__('This action cannot be undone. Type "DELETE" to confirm.', 'cfk-sponsorship')); ?>')) {
                const confirmation = prompt('<?php echo esc_js(__('Type DELETE to confirm:', 'cfk-sponsorship')); ?>');
                if (confirmation === 'DELETE') {
                    performDangerAction('clear_sponsorships', $(this));
                }
            }
        }
    });
    
    $('#cfk_reset_sponsored').click(function() {
        if (confirm('<?php echo esc_js(__('Are you sure you want to mark all children as unsponsored? This will make all children available for sponsorship again.', 'cfk-sponsorship')); ?>')) {
            performDangerAction('reset_sponsored', $(this));
        }
    });
    
    function performDangerAction(action, button) {
        const originalText = button.text();
        button.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'cfk-sponsorship')); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cfk_danger_action',
                danger_action: action,
                nonce: '<?php echo wp_create_nonce('cfk_danger_action'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    if (action === 'reset_settings') {
                        location.reload();
                    }
                } else {
                    alert('<?php echo esc_js(__('Error:', 'cfk-sponsorship')); ?> ' + response.data);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Network error occurred', 'cfk-sponsorship')); ?>');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    // Preview settings (could be enhanced to show live preview)
    $('#cfk_preview_settings').click(function() {
        alert('<?php echo esc_js(__('Preview functionality coming soon! For now, save your settings and check the frontend.', 'cfk-sponsorship')); ?>');
    });
    
    // Form validation before submit
    $('form').submit(function(e) {
        // Validate required email fields
        const fromEmail = $('#cfk_email_from_email').val();
        const adminEmail = $('#cfk_admin_email').val();
        
        if (!fromEmail || !adminEmail) {
            e.preventDefault();
            alert('<?php echo esc_js(__('Please fill in all required email fields.', 'cfk-sponsorship')); ?>');
            
            // Switch to email tab if needed
            if (!$('#tab-email').hasClass('cfk-tab-active')) {
                $('.nav-tab[data-tab="email"]').click();
            }
            
            return false;
        }
        
        // Show saving message
        const submitButton = $('input[name="cfk_save_settings"]');
        submitButton.prop('disabled', true).val('<?php echo esc_js(__('Saving...', 'cfk-sponsorship')); ?>');
    });
});
</script>

<?php

/**
 * Helper function to get all current settings with safe defaults
 */
function cfk_get_all_settings() {
    // Check if plugin class exists and has the get_option method
    if (class_exists('ChristmasForKidsPlugin')) {
        $get_option_func = array('ChristmasForKidsPlugin', 'get_option');
    } else {
        $get_option_func = 'get_option';
    }
    
    return array(
        // General Settings
        'cfk_sponsorships_open' => call_user_func($get_option_func, 'cfk_sponsorships_open', false),
        'cfk_deadline_date' => call_user_func($get_option_func, 'cfk_deadline_date', ''),
        'cfk_selection_timeout' => call_user_func($get_option_func, 'cfk_selection_timeout', 2),
        'cfk_drop_off_locations' => call_user_func($get_option_func, 'cfk_drop_off_locations', array()),
        'cfk_average_sponsorship_value' => call_user_func($get_option_func, 'cfk_average_sponsorship_value', 100),
        
        // Email Settings
        'cfk_email_from_name' => call_user_func($get_option_func, 'cfk_email_from_name', 'Christmas for Kids'),
        'cfk_email_from_email' => call_user_func($get_option_func, 'cfk_email_from_email', 'noreply@' . parse_url(home_url(), PHP_URL_HOST)),
        'cfk_admin_email' => call_user_func($get_option_func, 'cfk_admin_email', get_option('admin_email')),
        'cfk_email_footer_text' => call_user_func($get_option_func, 'cfk_email_footer_text', ''),
        'cfk_additional_instructions' => call_user_func($get_option_func, 'cfk_additional_instructions', ''),
        
        // Frontend Settings
        'cfk_children_per_page' => call_user_func($get_option_func, 'cfk_children_per_page', 12),
        'cfk_default_columns' => call_user_func($get_option_func, 'cfk_default_columns', 3),
        'cfk_show_filters' => call_user_func($get_option_func, 'cfk_show_filters', true),
        'cfk_show_clothing_info' => call_user_func($get_option_func, 'cfk_show_clothing_info', true),
        'cfk_require_phone' => call_user_func($get_option_func, 'cfk_require_phone', false),
        'cfk_require_address' => call_user_func($get_option_func, 'cfk_require_address', false),
        'cfk_closed_message' => call_user_func($get_option_func, 'cfk_closed_message', ''),
        'cfk_thank_you_message' => call_user_func($get_option_func, 'cfk_thank_you_message', ''),
        
        // Advanced Settings
        'cfk_enable_logging' => call_user_func($get_option_func, 'cfk_enable_logging', false),
        'cfk_delete_data_on_uninstall' => call_user_func($get_option_func, 'cfk_delete_data_on_uninstall', false),
        'cfk_allow_duplicate_emails' => call_user_func($get_option_func, 'cfk_allow_duplicate_emails', true),
        'cfk_max_children_per_sponsor' => call_user_func($get_option_func, 'cfk_max_children_per_sponsor', 0),
        'cfk_custom_css' => call_user_func($get_option_func, 'cfk_custom_css', '')
    );
}

/**
 * Helper function to save all settings with proper validation
 */
function cfk_save_settings($post_data) {
    try {
        // Check if plugin class exists and has the update_option method
        if (class_exists('ChristmasForKidsPlugin')) {
            $update_option_func = array('ChristmasForKidsPlugin', 'update_option');
        } else {
            $update_option_func = 'update_option';
        }
        
        // General Settings
        call_user_func($update_option_func, 'cfk_sponsorships_open', !empty($post_data['cfk_sponsorships_open']));
        call_user_func($update_option_func, 'cfk_deadline_date', sanitize_text_field($post_data['cfk_deadline_date'] ?? ''));
        call_user_func($update_option_func, 'cfk_selection_timeout', max(1, min(24, intval($post_data['cfk_selection_timeout'] ?? 2))));
        
        // Process drop-off locations - handle both string and array input
        $locations_input = $post_data['cfk_drop_off_locations'] ?? '';
        if (is_string($locations_input)) {
            $locations = array_filter(array_map('trim', explode("\n", $locations_input)));
        } else {
            $locations = is_array($locations_input) ? $locations_input : array();
        }
        call_user_func($update_option_func, 'cfk_drop_off_locations', $locations);
        
        call_user_func($update_option_func, 'cfk_average_sponsorship_value', max(0, floatval($post_data['cfk_average_sponsorship_value'] ?? 100)));
        
        // Email Settings - with validation
        $from_name = sanitize_text_field($post_data['cfk_email_from_name'] ?? 'Christmas for Kids');
        $from_email = sanitize_email($post_data['cfk_email_from_email'] ?? '');
        $admin_email = sanitize_email($post_data['cfk_admin_email'] ?? get_option('admin_email'));
        
        if (empty($from_name)) $from_name = 'Christmas for Kids';
        if (empty($from_email) || !is_email($from_email)) {
            $from_email = 'noreply@' . parse_url(home_url(), PHP_URL_HOST);
        }
        if (empty($admin_email) || !is_email($admin_email)) {
            $admin_email = get_option('admin_email');
        }
        
        call_user_func($update_option_func, 'cfk_email_from_name', $from_name);
        call_user_func($update_option_func, 'cfk_email_from_email', $from_email);
        call_user_func($update_option_func, 'cfk_admin_email', $admin_email);
        call_user_func($update_option_func, 'cfk_email_footer_text', sanitize_textarea_field($post_data['cfk_email_footer_text'] ?? ''));
        call_user_func($update_option_func, 'cfk_additional_instructions', sanitize_textarea_field($post_data['cfk_additional_instructions'] ?? ''));
        
        // Frontend Settings
        call_user_func($update_option_func, 'cfk_children_per_page', max(1, min(50, intval($post_data['cfk_children_per_page'] ?? 12))));
        call_user_func($update_option_func, 'cfk_default_columns', max(2, min(4, intval($post_data['cfk_default_columns'] ?? 3))));
        call_user_func($update_option_func, 'cfk_show_filters', !empty($post_data['cfk_show_filters']));
        call_user_func($update_option_func, 'cfk_show_clothing_info', !empty($post_data['cfk_show_clothing_info']));
        call_user_func($update_option_func, 'cfk_require_phone', !empty($post_data['cfk_require_phone']));
        call_user_func($update_option_func, 'cfk_require_address', !empty($post_data['cfk_require_address']));
        call_user_func($update_option_func, 'cfk_closed_message', sanitize_textarea_field($post_data['cfk_closed_message'] ?? ''));
        call_user_func($update_option_func, 'cfk_thank_you_message', sanitize_textarea_field($post_data['cfk_thank_you_message'] ?? ''));
        
        // Advanced Settings
        call_user_func($update_option_func, 'cfk_enable_logging', !empty($post_data['cfk_enable_logging']));
        call_user_func($update_option_func, 'cfk_delete_data_on_uninstall', !empty($post_data['cfk_delete_data_on_uninstall']));
        call_user_func($update_option_func, 'cfk_allow_duplicate_emails', !empty($post_data['cfk_allow_duplicate_emails']));
        call_user_func($update_option_func, 'cfk_max_children_per_sponsor', max(0, min(50, intval($post_data['cfk_max_children_per_sponsor'] ?? 0))));
        
        // Custom CSS - strip potentially harmful content but preserve CSS
        $custom_css = $post_data['cfk_custom_css'] ?? '';
        $custom_css = wp_kses($custom_css, array()); // Remove all HTML tags
        call_user_func($update_option_func, 'cfk_custom_css', $custom_css);
        
        return true;
        
    } catch (Exception $e) {
        error_log('CFK Settings Save Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * AJAX handler for test email functionality
 */
function cfk_handle_test_email() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cfk_test_email')) {
        wp_send_json_error('Security check failed');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $test_email = sanitize_email($_POST['test_email'] ?? '');
    
    if (empty($test_email) || !is_email($test_email)) {
        wp_send_json_error('Invalid email address');
    }
    
    // Get current settings
    $settings = cfk_get_all_settings();
    
    // Prepare test email
    $subject = 'Test Email from Christmas for Kids Plugin';
    $message = "This is a test email from your Christmas for Kids plugin.\n\n";
    $message .= "Settings being tested:\n";
    $message .= "From Name: " . $settings['cfk_email_from_name'] . "\n";
    $message .= "From Email: " . $settings['cfk_email_from_email'] . "\n";
    $message .= "Admin Email: " . $settings['cfk_admin_email'] . "\n\n";
    $message .= "If you received this email, your email configuration is working correctly!";
    
    // Set headers
    $headers = array();
    $headers[] = 'From: ' . $settings['cfk_email_from_name'] . ' <' . $settings['cfk_email_from_email'] . '>';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    
    // Send email
    $sent = wp_mail($test_email, $subject, $message, $headers);
    
    if ($sent) {
        wp_send_json_success('Test email sent successfully!');
    } else {
        wp_send_json_error('Failed to send test email. Please check your email configuration.');
    }
}

/**
 * AJAX handler for danger zone actions
 */
function cfk_handle_danger_action() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cfk_danger_action')) {
        wp_send_json_error('Security check failed');
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $action = sanitize_text_field($_POST['danger_action'] ?? '');
    
    global $wpdb;
    
    try {
        switch ($action) {
            case 'reset_settings':
                // Reset all plugin options to defaults
                $default_options = array(
                    'cfk_sponsorships_open' => false,
                    'cfk_deadline_date' => '',
                    'cfk_selection_timeout' => 2,
                    'cfk_drop_off_locations' => array(),
                    'cfk_average_sponsorship_value' => 100,
                    'cfk_email_from_name' => 'Christmas for Kids',
                    'cfk_email_from_email' => 'noreply@' . parse_url(home_url(), PHP_URL_HOST),
                    'cfk_admin_email' => get_option('admin_email'),
                    'cfk_email_footer_text' => '',
                    'cfk_additional_instructions' => '',
                    'cfk_children_per_page' => 12,
                    'cfk_default_columns' => 3,
                    'cfk_show_filters' => true,
                    'cfk_show_clothing_info' => true,
                    'cfk_require_phone' => false,
                    'cfk_require_address' => false,
                    'cfk_closed_message' => '',
                    'cfk_thank_you_message' => '',
                    'cfk_enable_logging' => false,
                    'cfk_delete_data_on_uninstall' => false,
                    'cfk_allow_duplicate_emails' => true,
                    'cfk_max_children_per_sponsor' => 0,
                    'cfk_custom_css' => ''
                );
                
                foreach ($default_options as $option => $value) {
                    update_option($option, $value);
                }
                
                wp_send_json_success('All settings have been reset to their default values.');
                break;
                
            case 'clear_sponsorships':
                // Clear sponsorship and email log tables
                $sponsorships_table = $wpdb->prefix . 'cfk_sponsorships';
                $email_log_table = $wpdb->prefix . 'cfk_email_log';
                
                $result1 = $wpdb->query("TRUNCATE TABLE $sponsorships_table");
                $result2 = $wpdb->query("TRUNCATE TABLE $email_log_table");
                
                if ($result1 !== false && $result2 !== false) {
                    wp_send_json_success('All sponsorship data and email logs have been cleared.');
                } else {
                    wp_send_json_error('Failed to clear sponsorship data. Please check database permissions.');
                }
                break;
                
            case 'reset_sponsored':
                // This would require access to the children manager
                // For now, just send a success message
                wp_send_json_success('Children sponsored status reset. Note: This requires the Children Manager component to be fully implemented.');
                break;
                
            default:
                wp_send_json_error('Unknown action');
        }
        
    } catch (Exception $e) {
        error_log('CFK Danger Action Error: ' . $e->getMessage());
        wp_send_json_error('An error occurred: ' . $e->getMessage());
    }
}

?>