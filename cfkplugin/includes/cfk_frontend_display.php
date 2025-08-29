<?php
/**
 * Frontend Display Class
 * Handles all public-facing shortcodes and user interface
 *
 * @package ChristmasForKids
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CFK_Frontend_Display {
    
    private $sponsorship_manager;
    
    public function __construct() {
        // Initialize sponsorship manager for frontend use
        if (class_exists('CFK_Sponsorship_Manager')) {
            $this->sponsorship_manager = new CFK_Sponsorship_Manager();
        }
        
        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));
        
        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Add AJAX actions for frontend
        add_action('wp_footer', array($this, 'add_frontend_javascript'));
    }
    
    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('cfk_children_grid', array($this, 'children_grid_shortcode'));
        add_shortcode('cfk_sponsorship_cart', array($this, 'sponsorship_cart_shortcode'));
        add_shortcode('cfk_sponsorship_form', array($this, 'sponsorship_form_shortcode'));
        add_shortcode('cfk_thank_you_page', array($this, 'thank_you_page_shortcode'));
        add_shortcode('cfk_sponsorship_status', array($this, 'sponsorship_status_shortcode'));
        add_shortcode('cfk_family_grid', array($this, 'family_grid_shortcode'));
    }
    
    /**
     * Enqueue frontend styles and scripts
     */
    public function enqueue_frontend_assets() {
        // Only enqueue on pages that might have our shortcodes
        if ($this->should_enqueue_assets()) {
            wp_enqueue_script('jquery');
            
            // Add our frontend CSS
            wp_add_inline_style('wp-block-library', $this->get_frontend_css());
        }
    }
    
    /**
     * Check if we should enqueue assets on current page
     */
    private function should_enqueue_assets() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Check if page contains our shortcodes
        $shortcodes = array(
            'cfk_children_grid',
            'cfk_sponsorship_cart', 
            'cfk_sponsorship_form',
            'cfk_thank_you_page',
            'cfk_sponsorship_status',
            'cfk_family_grid'
        );
        
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Children grid shortcode
     */
    public function children_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'age_range' => '',
            'gender' => '',
            'per_page' => 12,
            'show_filters' => 'yes',
            'columns' => 3
        ), $atts);
        
        // Check if sponsorships are open
        if (!ChristmasForKidsPlugin::get_option('cfk_sponsorships_open', false)) {
            return $this->get_sponsorships_closed_message();
        }
        
        // Get available children
        $children = CFK_Children_Manager::get_available_children(array(
            'age_range' => $atts['age_range'],
            'gender' => $atts['gender'],
            'per_page' => intval($atts['per_page']),
            'exclude_selected' => true
        ));
        
        ob_start();
        ?>
        <div class="cfk-children-grid" id="cfk-children-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
            <?php if ($atts['show_filters'] === 'yes'): ?>
            <div class="cfk-filters">
                <div class="cfk-filter-group">
                    <label for="cfk-age-filter"><?php _e('Age Range:', 'cfk-sponsorship'); ?></label>
                    <select id="cfk-age-filter">
                        <option value=""><?php _e('All Ages', 'cfk-sponsorship'); ?></option>
                        <option value="Infant" <?php selected($atts['age_range'], 'Infant'); ?>><?php _e('Infant', 'cfk-sponsorship'); ?></option>
                        <option value="Elementary" <?php selected($atts['age_range'], 'Elementary'); ?>><?php _e('Elementary', 'cfk-sponsorship'); ?></option>
                        <option value="Middle School" <?php selected($atts['age_range'], 'Middle School'); ?>><?php _e('Middle School', 'cfk-sponsorship'); ?></option>
                        <option value="High School" <?php selected($atts['age_range'], 'High School'); ?>><?php _e('High School', 'cfk-sponsorship'); ?></option>
                    </select>
                </div>
                
                <div class="cfk-filter-group">
                    <label for="cfk-gender-filter"><?php _e('Gender:', 'cfk-sponsorship'); ?></label>
                    <select id="cfk-gender-filter">
                        <option value=""><?php _e('All', 'cfk-sponsorship'); ?></option>
                        <option value="Male" <?php selected($atts['gender'], 'Male'); ?>><?php _e('Male', 'cfk-sponsorship'); ?></option>
                        <option value="Female" <?php selected($atts['gender'], 'Female'); ?>><?php _e('Female', 'cfk-sponsorship'); ?></option>
                    </select>
                </div>
                
                <button id="cfk-filter-apply" class="cfk-btn cfk-btn-secondary">
                    <?php _e('Apply Filters', 'cfk-sponsorship'); ?>
                </button>
                
                <button id="cfk-filter-clear" class="cfk-btn cfk-btn-link">
                    <?php _e('Clear All', 'cfk-sponsorship'); ?>
                </button>
            </div>
            <?php endif; ?>
            
            <div class="cfk-children-container" style="--cfk-columns: <?php echo esc_attr($atts['columns']); ?>">
                <?php if (empty($children)): ?>
                <div class="cfk-no-children">
                    <h3><?php _e('No Available Children', 'cfk-sponsorship'); ?></h3>
                    <p><?php _e('All children have been sponsored or are currently being processed. Thank you for your interest!', 'cfk-sponsorship'); ?></p>
                </div>
                <?php else: ?>
                    <?php foreach ($children as $child): 
                        echo $this->render_child_card($child);
                    endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (count($children) >= intval($atts['per_page'])): ?>
            <div class="cfk-load-more-container">
                <button id="cfk-load-more" class="cfk-btn cfk-btn-secondary">
                    <?php _e('Load More Children', 'cfk-sponsorship'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render individual child card
     */
    private function render_child_card($child) {
        $child_id = get_post_meta($child->ID, '_child_id', true);
        $age = get_post_meta($child->ID, '_child_age', true);
        $gender = get_post_meta($child->ID, '_child_gender', true);
        $family_id = get_post_meta($child->ID, '_child_family_id', true);
        $gift_requests = get_post_meta($child->ID, '_child_gift_requests', true);
        $age_range = get_post_meta($child->ID, '_child_age_range', true);
        $clothing_info = get_post_meta($child->ID, '_child_clothing_info', true);
        $avatar = get_the_post_thumbnail_url($child->ID, 'medium');
        
        ob_start();
        ?>
        <div class="cfk-child-card" data-child-id="<?php echo esc_attr($child_id); ?>" data-age-range="<?php echo esc_attr($age_range); ?>" data-gender="<?php echo esc_attr($gender); ?>">
            <div class="cfk-child-avatar">
                <?php if ($avatar): ?>
                    <img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($child->post_title); ?>" loading="lazy">
                <?php else: ?>
                    <div class="cfk-avatar-placeholder">
                        <span class="cfk-gender-icon"><?php echo $gender === 'Male' ? '♂' : '♀'; ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="cfk-child-info">
                <h3 class="cfk-child-title"><?php echo esc_html($child->post_title); ?></h3>
                
                <div class="cfk-child-details">
                    <span class="cfk-detail-item">
                        <strong><?php _e('Age:', 'cfk-sponsorship'); ?></strong> 
                        <?php printf(__('%s (%s)', 'cfk-sponsorship'), esc_html($age), esc_html($age_range)); ?>
                    </span>
                    
                    <span class="cfk-detail-item">
                        <strong><?php _e('Family:', 'cfk-sponsorship'); ?></strong> 
                        <?php echo esc_html($family_id); ?>
                    </span>
                </div>
                
                <?php if ($clothing_info): ?>
                <div class="cfk-clothing-info">
                    <strong><?php _e('Clothing Sizes:', 'cfk-sponsorship'); ?></strong>
                    <p><?php echo esc_html(wp_trim_words($clothing_info, 8)); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($gift_requests): ?>
                <div class="cfk-gift-preview">
                    <strong><?php _e('Wishes for:', 'cfk-sponsorship'); ?></strong>
                    <p><?php echo esc_html(wp_trim_words($gift_requests, 12)); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="cfk-child-actions">
                <button class="cfk-btn cfk-btn-primary cfk-select-child" data-child-id="<?php echo esc_attr($child_id); ?>">
                    <span class="cfk-btn-text"><?php _e('Sponsor This Child', 'cfk-sponsorship'); ?></span>
                    <span class="cfk-btn-loading" style="display: none;"><?php _e('Adding...', 'cfk-sponsorship'); ?></span>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Sponsorship cart shortcode
     */
    public function sponsorship_cart_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Your Selected Children', 'cfk-sponsorship')
        ), $atts);
        
        if (!$this->sponsorship_manager) {
            return '<p>' . __('Sponsorship system not available.', 'cfk-sponsorship') . '</p>';
        }
        
        $selected_children = $this->sponsorship_manager->get_current_selected_children();
        $cart_count = count($selected_children);
        
        ob_start();
        ?>
        <div class="cfk-sponsorship-cart" id="cfk-sponsorship-cart">
            <h3 class="cfk-cart-title">
                <?php echo esc_html($atts['title']); ?>
                <span id="cfk-cart-counter" class="cfk-counter"><?php echo $cart_count; ?></span>
            </h3>
            
            <div class="cfk-cart-content" id="cfk-cart-content">
                <?php if (empty($selected_children)): ?>
                <div class="cfk-empty-cart" id="cfk-empty-cart">
                    <div class="cfk-empty-icon">🎄</div>
                    <h4><?php _e('No Children Selected Yet', 'cfk-sponsorship'); ?></h4>
                    <p><?php _e('Browse the children above and click "Sponsor This Child" to add them to your sponsorship list.', 'cfk-sponsorship'); ?></p>
                    <a href="#cfk-children-grid" class="cfk-btn cfk-btn-primary cfk-smooth-scroll">
                        <?php _e('Browse Available Children', 'cfk-sponsorship'); ?>
                    </a>
                </div>
                <?php else: ?>
                <div class="cfk-selected-children" id="cfk-selected-children">
                    <?php foreach ($selected_children as $child): ?>
                    <div class="cfk-selected-child" data-child-id="<?php echo esc_attr($child['id']); ?>">
                        <div class="cfk-child-summary">
                            <h4><?php echo esc_html($child['name']); ?></h4>
                            <div class="cfk-summary-details">
                                <span><?php printf(__('Age: %s (%s)', 'cfk-sponsorship'), esc_html($child['age']), esc_html($child['age_range'])); ?></span>
                                <span><?php printf(__('Family: %s', 'cfk-sponsorship'), esc_html($child['family_id'])); ?></span>
                            </div>
                            <?php if (!empty($child['gift_requests'])): ?>
                            <div class="cfk-summary-gifts">
                                <strong><?php _e('Wishes for:', 'cfk-sponsorship'); ?></strong>
                                <?php echo esc_html(wp_trim_words($child['gift_requests'], 8)); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="cfk-child-actions">
                            <button class="cfk-btn cfk-btn-remove cfk-remove-child" data-child-id="<?php echo esc_attr($child['id']); ?>">
                                <span class="cfk-btn-text"><?php _e('Remove', 'cfk-sponsorship'); ?></span>
                                <span class="cfk-btn-loading" style="display: none;"><?php _e('Removing...', 'cfk-sponsorship'); ?></span>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cfk-cart-actions">
                    <div class="cfk-cart-summary">
                        <p class="cfk-summary-text">
                            <?php printf(
                                _n('You are sponsoring %d child.', 'You are sponsoring %d children.', $cart_count, 'cfk-sponsorship'),
                                $cart_count
                            ); ?>
                        </p>
                    </div>
                    
                    <button id="cfk-proceed-button" class="cfk-btn cfk-btn-primary cfk-btn-large">
                        <?php _e('Proceed to Sponsor Information', 'cfk-sponsorship'); ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Sponsorship form shortcode
     */
    public function sponsorship_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Sponsor Information', 'cfk-sponsorship')
        ), $atts);
        
        if (!$this->sponsorship_manager) {
            return '<p>' . __('Sponsorship system not available.', 'cfk-sponsorship') . '</p>';
        }
        
        $selected_children = $this->sponsorship_manager->get_current_selected_children();
        
        if (empty($selected_children)) {
            return '<div class="cfk-form-notice">' . __('Please select children to sponsor before filling out this form.', 'cfk-sponsorship') . '</div>';
        }
        
        ob_start();
        ?>
        <div class="cfk-sponsorship-form" id="cfk-sponsorship-form">
            <h3 class="cfk-form-title"><?php echo esc_html($atts['title']); ?></h3>
            <p class="cfk-form-description">
                <?php _e('Please provide your contact information so we can send you details about the children you\'re sponsoring and important updates about gift delivery.', 'cfk-sponsorship'); ?>
            </p>
            
            <form id="cfk-sponsor-form" class="cfk-form" novalidate>
                <?php wp_nonce_field('cfk_sponsorship_nonce', 'cfk_sponsorship_nonce'); ?>
                
                <div class="cfk-form-section">
                    <div class="cfk-form-row">
                        <div class="cfk-form-group cfk-form-group-required">
                            <label for="sponsor_name"><?php _e('Full Name', 'cfk-sponsorship'); ?> <span class="cfk-required">*</span></label>
                            <input type="text" id="sponsor_name" name="sponsor_name" required 
                                   placeholder="<?php _e('Enter your full name', 'cfk-sponsorship'); ?>">
                            <div class="cfk-field-error"></div>
                        </div>
                        
                        <div class="cfk-form-group cfk-form-group-required">
                            <label for="sponsor_email"><?php _e('Email Address', 'cfk-sponsorship'); ?> <span class="cfk-required">*</span></label>
                            <input type="email" id="sponsor_email" name="sponsor_email" required 
                                   placeholder="<?php _e('your.email@example.com', 'cfk-sponsorship'); ?>">
                            <div class="cfk-field-error"></div>
                        </div>
                    </div>
                    
                    <div class="cfk-form-row">
                        <div class="cfk-form-group">
                            <label for="sponsor_phone"><?php _e('Phone Number', 'cfk-sponsorship'); ?></label>
                            <input type="tel" id="sponsor_phone" name="sponsor_phone" 
                                   placeholder="<?php _e('(555) 123-4567', 'cfk-sponsorship'); ?>">
                            <div class="cfk-field-error"></div>
                        </div>
                    </div>
                    
                    <div class="cfk-form-group">
                        <label for="sponsor_address"><?php _e('Address', 'cfk-sponsorship'); ?></label>
                        <textarea id="sponsor_address" name="sponsor_address" rows="3" 
                                  placeholder="<?php _e('Street address, City, State, ZIP (optional - for delivery updates)', 'cfk-sponsorship'); ?>"></textarea>
                        <div class="cfk-field-error"></div>
                    </div>
                    
                    <div class="cfk-form-group">
                        <label for="sponsor_notes"><?php _e('Additional Notes or Questions', 'cfk-sponsorship'); ?></label>
                        <textarea id="sponsor_notes" name="sponsor_notes" rows="3" 
                                  placeholder="<?php _e('Any special requests, questions, or comments about your sponsorship...', 'cfk-sponsorship'); ?>"></textarea>
                        <div class="cfk-field-error"></div>
                    </div>
                </div>
                
                <div class="cfk-summary-section">
                    <h4 class="cfk-summary-title"><?php _e('Sponsorship Summary', 'cfk-sponsorship'); ?></h4>
                    <div class="cfk-sponsor-summary">
                        <?php foreach ($selected_children as $child): ?>
                        <div class="cfk-summary-item">
                            <span class="cfk-child-id"><?php echo esc_html($child['id']); ?></span>
                            <span class="cfk-child-name"><?php echo esc_html($child['name']); ?></span>
                            <span class="cfk-child-family"><?php printf(__('(Family %s)', 'cfk-sponsorship'), esc_html($child['family_id'])); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cfk-summary-total">
                        <?php printf(
                            _n('You are sponsoring %d child', 'You are sponsoring %d children', count($selected_children), 'cfk-sponsorship'),
                            count($selected_children)
                        ); ?>
                    </div>
                </div>
                
                <div class="cfk-form-actions">
                    <button type="submit" class="cfk-btn cfk-btn-primary cfk-btn-large" id="cfk-submit-sponsorship">
                        <span class="cfk-btn-text"><?php _e('Confirm My Sponsorship', 'cfk-sponsorship'); ?></span>
                        <span class="cfk-btn-loading" style="display: none;">
                            <span class="cfk-spinner"></span>
                            <?php _e('Processing...', 'cfk-sponsorship'); ?>
                        </span>
                    </button>
                    
                    <p class="cfk-form-note">
                        <?php _e('By confirming your sponsorship, you agree to purchase and deliver gifts for the selected children by the specified deadline.', 'cfk-sponsorship'); ?>
                    </p>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Thank you page shortcode
     */
    public function thank_you_page_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Thank You for Your Sponsorship!', 'cfk-sponsorship')
        ), $atts);
        
        $organization_name = ChristmasForKidsPlugin::get_option('cfk_email_from_name', 'Christmas for Kids');
        $deadline_date = ChristmasForKidsPlugin::get_option('cfk_deadline_date', '[DEADLINE DATE]');
        $drop_off_locations = ChristmasForKidsPlugin::get_option('cfk_drop_off_locations', array());
        
        if (is_string($drop_off_locations)) {
            $drop_off_locations = explode("\n", $drop_off_locations);
        }
        
        ob_start();
        ?>
        <div class="cfk-thank-you-page">
            <div class="cfk-thank-you-header">
                <div class="cfk-success-icon">🎄</div>
                <h1 class="cfk-thank-you-title"><?php echo esc_html($atts['title']); ?></h1>
                <p class="cfk-thank-you-subtitle">
                    <?php printf(__('Your generous heart will bring joy to children in our community this Christmas!', 'cfk-sponsorship')); ?>
                </p>
            </div>
            
            <div class="cfk-thank-you-content">
                <div class="cfk-next-steps">
                    <h3><?php _e('What Happens Next?', 'cfk-sponsorship'); ?></h3>
                    <ol class="cfk-steps-list">
                        <li>
                            <strong><?php _e('Check Your Email', 'cfk-sponsorship'); ?></strong>
                            <p><?php _e('You should receive a confirmation email within a few minutes with detailed information about your sponsored children, including their wish lists and clothing sizes.', 'cfk-sponsorship'); ?></p>
                        </li>
                        <li>
                            <strong><?php _e('Shop for Gifts', 'cfk-sponsorship'); ?></strong>
                            <p><?php _e('Use the wish lists and clothing size information in your email to shop for meaningful gifts that will bring joy to your sponsored children.', 'cfk-sponsorship'); ?></p>
                        </li>
                        <li>
                            <strong><?php _e('Wrap and Label', 'cfk-sponsorship'); ?></strong>
                            <p><?php _e('Wrap your gifts and include the child ID tags (provided in your email) so we can ensure each gift reaches the right child.', 'cfk-sponsorship'); ?></p>
                        </li>
                        <li>
                            <strong><?php _e('Drop Off Gifts', 'cfk-sponsorship'); ?></strong>
                            <p><?php printf(__('Bring your wrapped gifts to one of our drop-off locations by %s.', 'cfk-sponsorship'), '<strong>' . esc_html($deadline_date) . '</strong>'); ?></p>
                        </li>
                    </ol>
                </div>
                
                <?php if (!empty($drop_off_locations)): ?>
                <div class="cfk-drop-off-info">
                    <h3><?php _e('Gift Drop-Off Locations', 'cfk-sponsorship'); ?></h3>
                    <ul class="cfk-locations-list">
                        <?php foreach ($drop_off_locations as $location): ?>
                        <li><?php echo esc_html(trim($location)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="cfk-contact-info">
                    <h3><?php _e('Questions or Changes?', 'cfk-sponsorship'); ?></h3>
                    <p><?php _e('If you have any questions about your sponsorship or need to make changes, please don\'t hesitate to contact us. We\'re here to help make this process as smooth as possible for you.', 'cfk-sponsorship'); ?></p>
                </div>
                
                <div class="cfk-social-share">
                    <h3><?php _e('Spread the Joy!', 'cfk-sponsorship'); ?></h3>
                    <p><?php _e('Help us reach more families by sharing our sponsorship program with your friends and family.', 'cfk-sponsorship'); ?></p>
                    <div class="cfk-share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(home_url()); ?>" 
                           target="_blank" class="cfk-share-btn cfk-share-facebook">
                            <?php _e('Share on Facebook', 'cfk-sponsorship'); ?>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode(__('I just sponsored children for Christmas! Join me in spreading joy to local families.', 'cfk-sponsorship')); ?>&url=<?php echo urlencode(home_url()); ?>" 
                           target="_blank" class="cfk-share-btn cfk-share-twitter">
                            <?php _e('Share on Twitter', 'cfk-sponsorship'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="cfk-thank-you-footer">
                <p class="cfk-gratitude-message">
                    <?php printf(__('Thank you for being a Christmas angel and making the holidays magical for children in our community. Your kindness truly makes a difference!', 'cfk-sponsorship')); ?>
                </p>
                <p class="cfk-signature">
                    <?php printf(__('With heartfelt gratitude,<br>The %s Team', 'cfk-sponsorship'), esc_html($organization_name)); ?>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Sponsorship status shortcode (shows if sponsorships are open/closed)
     */
    public function sponsorship_status_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_stats' => 'yes'
        ), $atts);
        
        $sponsorships_open = ChristmasForKidsPlugin::get_option('cfk_sponsorships_open', false);
        $stats = CFK_Children_Manager::get_sponsorship_stats();
        
        ob_start();
        ?>
        <div class="cfk-sponsorship-status">
            <?php if ($sponsorships_open): ?>
            <div class="cfk-status-open">
                <div class="cfk-status-icon">🎄</div>
                <h3><?php _e('Sponsorships Are Open!', 'cfk-sponsorship'); ?></h3>
                <p><?php _e('Help bring Christmas joy to local families by sponsoring children in need.', 'cfk-sponsorship'); ?></p>
            </div>
            <?php else: ?>
            <div class="cfk-status-closed">
                <div class="cfk-status-icon">🎁</div>
                <h3><?php _e('Sponsorships Are Currently Closed', 'cfk-sponsorship'); ?></h3>
                <p><?php _e('Thank you for your interest! Sponsorships typically open in early November. Please check back next year.', 'cfk-sponsorship'); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_stats'] === 'yes'): ?>
            <div class="cfk-stats-display">
                <div class="cfk-stat">
                    <span class="cfk-stat-number"><?php echo $stats['sponsored_children']; ?></span>
                    <span class="cfk-stat-label"><?php _e('Children Sponsored', 'cfk-sponsorship'); ?