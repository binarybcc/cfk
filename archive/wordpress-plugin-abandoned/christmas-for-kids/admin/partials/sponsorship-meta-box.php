<?php
/**
 * Sponsorship Information Meta Box Template
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 * @var array $sponsorships
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="cfk-sponsorship-info">
    <?php if (empty($sponsorships)): ?>
        <p style="color: #6c757d; font-style: italic;">
            <?php esc_html_e('No sponsorship activity yet.', CFK_TEXT_DOMAIN); ?>
        </p>
    <?php else: ?>
        <div class="cfk-sponsorship-history">
            <h4 style="margin-top: 0;"><?php esc_html_e('Sponsorship History', CFK_TEXT_DOMAIN); ?></h4>
            
            <?php foreach ($sponsorships as $sponsorship): ?>
                <div class="cfk-sponsorship-record" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #fafafa;">
                    
                    <!-- Status Badge -->
                    <?php
                    $status_colors = [
                        'selected' => '#ff8c00',
                        'confirmed' => '#00a32a',
                        'cancelled' => '#d63638'
                    ];
                    $status_color = $status_colors[$sponsorship->status] ?? '#6c757d';
                    ?>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <strong><?php echo esc_html($sponsorship->sponsor_name); ?></strong>
                        <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; 
                                     background: <?php echo esc_attr($status_color); ?>; color: white; 
                                     font-size: 11px; font-weight: 500; text-transform: uppercase;">
                            <?php echo esc_html(ucfirst($sponsorship->status)); ?>
                        </span>
                    </div>
                    
                    <!-- Sponsor Details -->
                    <div style="font-size: 13px; color: #555; line-height: 1.4;">
                        <div><strong><?php esc_html_e('Email:', CFK_TEXT_DOMAIN); ?></strong> 
                             <a href="mailto:<?php echo esc_attr($sponsorship->sponsor_email); ?>"><?php echo esc_html($sponsorship->sponsor_email); ?></a></div>
                        
                        <?php if (!empty($sponsorship->sponsor_phone)): ?>
                            <div><strong><?php esc_html_e('Phone:', CFK_TEXT_DOMAIN); ?></strong> 
                                 <a href="tel:<?php echo esc_attr($sponsorship->sponsor_phone); ?>"><?php echo esc_html($sponsorship->sponsor_phone); ?></a></div>
                        <?php endif; ?>
                        
                        <div><strong><?php esc_html_e('Date:', CFK_TEXT_DOMAIN); ?></strong> 
                             <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($sponsorship->created_at))); ?></div>
                        
                        <?php if (!empty($sponsorship->selection_token)): ?>
                            <div><strong><?php esc_html_e('Token:', CFK_TEXT_DOMAIN); ?></strong> 
                                 <code style="font-size: 11px; background: #e9ecef; padding: 2px 4px; border-radius: 2px;"><?php echo esc_html(substr($sponsorship->selection_token, 0, 8) . '...'); ?></code></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Actions for pending sponsorships -->
                    <?php if ($sponsorship->status === 'selected'): ?>
                        <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #ddd;">
                            <small style="color: #856404; background: #fff3cd; padding: 4px 6px; border-radius: 2px; display: inline-block;">
                                <strong><?php esc_html_e('Awaiting Confirmation', CFK_TEXT_DOMAIN); ?></strong> - 
                                <?php
                                $time_left = 2 * 3600 - (time() - strtotime($sponsorship->created_at)); // 2 hours in seconds
                                if ($time_left > 0) {
                                    $hours = floor($time_left / 3600);
                                    $minutes = floor(($time_left % 3600) / 60);
                                    printf(esc_html__('Expires in %dh %dm', CFK_TEXT_DOMAIN), $hours, $minutes);
                                } else {
                                    esc_html_e('Selection expired', CFK_TEXT_DOMAIN);
                                }
                                ?>
                            </small>
                        </div>
                    <?php endif; ?>
                    
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Summary Stats -->
        <div class="cfk-sponsorship-stats" style="margin-top: 20px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
            <?php
            $total_attempts = count($sponsorships);
            $confirmed = array_filter($sponsorships, function($s) { return $s->status === 'confirmed'; });
            $confirmed_count = count($confirmed);
            $cancelled = array_filter($sponsorships, function($s) { return $s->status === 'cancelled'; });
            $cancelled_count = count($cancelled);
            $pending = array_filter($sponsorships, function($s) { return $s->status === 'selected'; });
            $pending_count = count($pending);
            ?>
            
            <h5 style="margin: 0 0 8px 0;"><?php esc_html_e('Summary Statistics', CFK_TEXT_DOMAIN); ?></h5>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; font-size: 12px;">
                <div style="text-align: center;">
                    <strong style="display: block; font-size: 18px; color: #0073aa;"><?php echo esc_html($total_attempts); ?></strong>
                    <span><?php esc_html_e('Total Attempts', CFK_TEXT_DOMAIN); ?></span>
                </div>
                
                <?php if ($confirmed_count > 0): ?>
                    <div style="text-align: center;">
                        <strong style="display: block; font-size: 18px; color: #00a32a;"><?php echo esc_html($confirmed_count); ?></strong>
                        <span><?php esc_html_e('Confirmed', CFK_TEXT_DOMAIN); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($pending_count > 0): ?>
                    <div style="text-align: center;">
                        <strong style="display: block; font-size: 18px; color: #ff8c00;"><?php echo esc_html($pending_count); ?></strong>
                        <span><?php esc_html_e('Pending', CFK_TEXT_DOMAIN); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($cancelled_count > 0): ?>
                    <div style="text-align: center;">
                        <strong style="display: block; font-size: 18px; color: #d63638;"><?php echo esc_html($cancelled_count); ?></strong>
                        <span><?php esc_html_e('Cancelled', CFK_TEXT_DOMAIN); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div class="cfk-sponsorship-actions" style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">
        <h5 style="margin: 0 0 10px 0;"><?php esc_html_e('Quick Actions', CFK_TEXT_DOMAIN); ?></h5>
        
        <p style="margin-bottom: 10px;">
            <button type="button" class="button button-small cfk-view-emails" data-child-id="<?php echo esc_attr(get_the_ID()); ?>">
                <?php esc_html_e('View Email History', CFK_TEXT_DOMAIN); ?>
            </button>
            
            <button type="button" class="button button-small cfk-export-sponsorships" data-child-id="<?php echo esc_attr(get_the_ID()); ?>">
                <?php esc_html_e('Export Data', CFK_TEXT_DOMAIN); ?>
            </button>
        </p>
        
        <p style="font-size: 12px; color: #6c757d; margin: 0;">
            <strong><?php esc_html_e('Note:', CFK_TEXT_DOMAIN); ?></strong> 
            <?php esc_html_e('Sponsorship records are maintained for audit purposes and cannot be deleted.', CFK_TEXT_DOMAIN); ?>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle view emails button
    $('.cfk-view-emails').on('click', function() {
        const childId = $(this).data('child-id');
        // In a full implementation, this would open a modal or navigate to email logs
        alert('<?php echo esc_js(__('Email history feature coming soon!', CFK_TEXT_DOMAIN)); ?>');
    });
    
    // Handle export button
    $('.cfk-export-sponsorships').on('click', function() {
        const childId = $(this).data('child-id');
        // In a full implementation, this would trigger a CSV download
        alert('<?php echo esc_js(__('Export feature coming soon!', CFK_TEXT_DOMAIN)); ?>');
    });
});
</script>