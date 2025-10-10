<?php
/**
 * Child Card Component
 * Reusable component for displaying child profile cards
 *
 * @param array $child - Child data array with all necessary information
 * @param array $options - Optional configuration:
 *   - show_wishes (bool): Display wishes preview (default: true)
 *   - show_interests (bool): Display interests preview (default: false)
 *   - show_id (bool): Display child ID (default: false)
 *   - show_siblings (bool): Display sibling information (default: false)
 *   - siblings (array): Pre-loaded sibling data to prevent N+1 queries
 *   - card_class (string): Additional CSS classes for the card
 *   - button_text (string): Custom button text (default: "Learn More")
 *   - show_actions (bool): Show action buttons section (default: true for simple button in info)
 *   - show_family_button (bool): Show "View Family" button if siblings exist (default: false)
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Validate required parameter
if (!isset($child) || !is_array($child)) {
    trigger_error('Child card component requires $child array parameter', E_USER_WARNING);
    return;
}

// Default options
$options = $options ?? [];
$showWishes = $options['show_wishes'] ?? true;
$showInterests = $options['show_interests'] ?? false;
$showId = $options['show_id'] ?? false;
$showSiblings = $options['show_siblings'] ?? false;
$siblings = $options['siblings'] ?? [];
$cardClass = $options['card_class'] ?? 'child-card';
$buttonText = $options['button_text'] ?? 'Learn More';
$showActions = $options['show_actions'] ?? false;
$showFamilyButton = $options['show_family_button'] ?? false;
?>

<div class="<?php echo sanitizeString($cardClass); ?>">
    <div class="child-photo">
        <img src="<?php echo getPhotoUrl($child['photo_filename'], $child); ?>"
             alt="Avatar for <?php echo sanitizeString($child['name']); ?>">
    </div>

    <div class="child-info">
        <h3 class="child-name"><?php echo sanitizeString($child['name']); ?></h3>

        <?php if ($showId && !empty($child['display_id'])): ?>
            <p class="child-id">ID: <?php echo sanitizeString($child['display_id']); ?></p>
        <?php endif; ?>

        <p class="child-gender">
            <?php echo ($child['gender'] === 'M') ? 'Boy' : 'Girl'; ?>
        </p>

        <p class="child-age">
            <?php echo formatAge($child['age']); ?>
        </p>

        <?php if ($showInterests && !empty($child['interests'])): ?>
            <div class="child-interests">
                <strong>Likes:</strong>
                <?php echo sanitizeString(substr($child['interests'], 0, 100)); ?>
                <?php if (strlen($child['interests']) > 100): ?>...<?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($showWishes && !empty($child['wishes'])): ?>
            <div class="child-wishes">
                <?php if ($showInterests): ?>
                    <strong>Wishes for:</strong>
                    <?php echo sanitizeString(substr($child['wishes'], 0, 100)); ?>
                    <?php if (strlen($child['wishes']) > 100): ?>...<?php endif; ?>
                <?php else: ?>
                    "<?php echo sanitizeString(substr($child['wishes'], 0, 80)); ?><?php echo strlen($child['wishes']) > 80 ? '...' : ''; ?>"
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($showSiblings && !empty($siblings)): ?>
            <div class="family-info">
                <strong>Has <?php echo count($siblings); ?> sibling<?php echo count($siblings) > 1 ? 's' : ''; ?>:</strong>
                <?php
                $siblingNames = array_map(function($s) {
                    return sanitizeString($s['name']) . ' (' . sanitizeString($s['display_id']) . ')';
                }, $siblings);
                echo implode(', ', $siblingNames);
                ?>
            </div>
        <?php endif; ?>

        <?php if (!$showActions): ?>
            <?php echo renderButton(
                $buttonText,
                baseUrl('?page=child&id=' . $child['id']),
                'primary'
            ); ?>
        <?php endif; ?>
    </div>

    <?php if ($showActions): ?>
        <div class="child-actions">
            <?php echo renderButton(
                $buttonText,
                baseUrl('?page=child&id=' . $child['id']),
                'primary'
            ); ?>

            <?php if ($showFamilyButton && !empty($siblings)): ?>
                <?php echo renderButton(
                    'View Family',
                    baseUrl('?page=children&family_id=' . $child['family_id']),
                    'secondary',
                    ['size' => 'small']
                ); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
