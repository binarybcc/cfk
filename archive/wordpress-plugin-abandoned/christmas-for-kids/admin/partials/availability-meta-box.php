<?php
/**
 * Availability Status Meta Box Template
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 * @var string $availability
 * @var string $selected_at
 * @var string $sponsored_at
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="cfk-availability-meta">
    <p>
        <label for="cfk_availability_status"><strong><?php esc_html_e('Current Status:', CFK_TEXT_DOMAIN); ?></strong></label>
        <select id="cfk_availability_status" name="_cfk_availability_status" style="width: 100%; margin-top: 5px;">
            <option value="available" <?php selected($availability, 'available'); ?>>
                <?php esc_html_e('Available for Sponsorship', CFK_TEXT_DOMAIN); ?>
            </option>
            <option value="selected" <?php selected($availability, 'selected'); ?>>
                <?php esc_html_e('Selected (Pending Confirmation)', CFK_TEXT_DOMAIN); ?>
            </option>
            <option value="sponsored" <?php selected($availability, 'sponsored'); ?>>
                <?php esc_html_e('Sponsored', CFK_TEXT_DOMAIN); ?>
            </option>
            <option value="unavailable" <?php selected($availability, 'unavailable'); ?>>
                <?php esc_html_e('Temporarily Unavailable', CFK_TEXT_DOMAIN); ?>
            </option>
        </select>
    </p>
    
    <div class="cfk-status-details">
        <?php
        // Status badge
        $status_colors = [
            'available' => '#00a32a',
            'selected' => '#ff8c00',
            'sponsored' => '#0073aa',
            'unavailable' => '#d63638'
        ];
        
        $status_labels = [
            'available' => __('Available', CFK_TEXT_DOMAIN),
            'selected' => __('Selected', CFK_TEXT_DOMAIN),
            'sponsored' => __('Sponsored', CFK_TEXT_DOMAIN),
            'unavailable' => __('Unavailable', CFK_TEXT_DOMAIN)
        ];
        
        $current_color = $status_colors[$availability] ?? '#6c757d';
        $current_label = $status_labels[$availability] ?? __('Unknown', CFK_TEXT_DOMAIN);
        ?>
        
        <div class="cfk-status-badge" style="margin: 10px 0;">
            <span style="display: inline-block; padding: 8px 12px; border-radius: 4px; 
                         background: <?php echo esc_attr($current_color); ?>; color: white; 
                         font-weight: 600; text-align: center; width: 100%; box-sizing: border-box;">
                <?php echo esc_html($current_label); ?>
            </span>
        </div>
        
        <?php if ($availability === 'selected' && $selected_at): ?>
            <div class="cfk-status-info" style="margin: 10px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                <strong><?php esc_html_e('Selected On:', CFK_TEXT_DOMAIN); ?></strong><br>
                <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($selected_at))); ?>
                <br><small style="color: #856404;">
                    <?php esc_html_e('Selection will expire after 2 hours if not confirmed.', CFK_TEXT_DOMAIN); ?>
                </small>
            </div>
        <?php endif; ?>
        
        <?php if ($availability === 'sponsored' && $sponsored_at): ?>
            <div class="cfk-status-info" style="margin: 10px 0; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px;">
                <strong><?php esc_html_e('Sponsored On:', CFK_TEXT_DOMAIN); ?></strong><br>
                <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($sponsored_at))); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="cfk-status-actions" style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
        <p><strong><?php esc_html_e('Quick Actions:', CFK_TEXT_DOMAIN); ?></strong></p>
        
        <?php if ($availability !== 'available'): ?>
            <button type="button" class="button button-small" onclick="document.getElementById('cfk_availability_status').value='available'; document.getElementById('cfk_availability_status').dispatchEvent(new Event('change'));">
                <?php esc_html_e('Make Available', CFK_TEXT_DOMAIN); ?>
            </button>
        <?php endif; ?>
        
        <?php if ($availability !== 'sponsored'): ?>
            <button type="button" class="button button-small" onclick="document.getElementById('cfk_availability_status').value='sponsored'; document.getElementById('cfk_availability_status').dispatchEvent(new Event('change'));">
                <?php esc_html_e('Mark as Sponsored', CFK_TEXT_DOMAIN); ?>
            </button>
        <?php endif; ?>
        
        <?php if ($availability !== 'unavailable'): ?>
            <button type="button" class="button button-small" onclick="document.getElementById('cfk_availability_status').value='unavailable'; document.getElementById('cfk_availability_status').dispatchEvent(new Event('change'));">
                <?php esc_html_e('Make Unavailable', CFK_TEXT_DOMAIN); ?>
            </button>
        <?php endif; ?>
    </div>
    
    <div class="cfk-visibility-info" style="margin-top: 15px; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; font-size: 12px; color: #6c757d;">
        <strong><?php esc_html_e('Visibility:', CFK_TEXT_DOMAIN); ?></strong><br>
        <?php if ($availability === 'available'): ?>
            <?php esc_html_e('This child will appear on the public sponsorship page.', CFK_TEXT_DOMAIN); ?>
        <?php else: ?>
            <?php esc_html_e('This child will NOT appear on the public sponsorship page.', CFK_TEXT_DOMAIN); ?>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Update status badge when selection changes
    $('#cfk_availability_status').on('change', function() {
        const statusColors = {
            'available': '#00a32a',
            'selected': '#ff8c00', 
            'sponsored': '#0073aa',
            'unavailable': '#d63638'
        };
        
        const statusLabels = {
            'available': '<?php echo esc_js(__('Available', CFK_TEXT_DOMAIN)); ?>',
            'selected': '<?php echo esc_js(__('Selected', CFK_TEXT_DOMAIN)); ?>',
            'sponsored': '<?php echo esc_js(__('Sponsored', CFK_TEXT_DOMAIN)); ?>',
            'unavailable': '<?php echo esc_js(__('Unavailable', CFK_TEXT_DOMAIN)); ?>'
        };
        
        const selectedValue = $(this).val();
        const $badge = $('.cfk-status-badge span');
        
        $badge.css('background', statusColors[selectedValue] || '#6c757d')
              .text(statusLabels[selectedValue] || 'Unknown');
    });
});
</script>