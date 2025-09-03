<?php
declare(strict_types=1);

/**
 * Admin partial for child details meta box
 * 
 * This template renders the child details form in the WordPress admin
 * when editing a child post. It provides a user-friendly interface
 * for entering child information.
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 * 
 * Variables available in this template:
 * @var array $meta_values Child meta values
 * @var WP_Post $post Current post object
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$fields = CFK_Child_Manager::get_meta_fields();
?>

<div class="cfk-child-meta-box">
    <style>
        .cfk-child-meta-box {
            padding: 10px 0;
        }
        .cfk-field-group {
            margin-bottom: 15px;
        }
        .cfk-field-label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .cfk-field-label.required::after {
            content: " *";
            color: #d63638;
        }
        .cfk-field-input {
            width: 100%;
            max-width: 400px;
        }
        .cfk-field-textarea {
            width: 100%;
            max-width: 600px;
            height: 80px;
            resize: vertical;
        }
        .cfk-field-description {
            font-style: italic;
            color: #666;
            font-size: 13px;
            margin-top: 3px;
        }
        .cfk-required-note {
            color: #d63638;
            font-size: 13px;
            margin-bottom: 15px;
            font-style: italic;
        }
        .cfk-field-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .cfk-field-column {
            flex: 1;
            min-width: 250px;
        }
    </style>
    
    <div class="cfk-required-note">
        <?php _e('Fields marked with * are required', CFK_TEXT_DOMAIN); ?>
    </div>
    
    <div class="cfk-field-row">
        <div class="cfk-field-column">
            <!-- Age Field -->
            <div class="cfk-field-group">
                <label for="cfk_child_age" class="cfk-field-label<?php echo $fields['age']['required'] ? ' required' : ''; ?>">
                    <?php echo esc_html($fields['age']['label']); ?>
                </label>
                <input 
                    type="number" 
                    id="cfk_child_age" 
                    name="cfk_child_age" 
                    value="<?php echo esc_attr($meta_values['age'] ?? ''); ?>"
                    min="<?php echo esc_attr($fields['age']['min']); ?>"
                    max="<?php echo esc_attr($fields['age']['max']); ?>"
                    class="cfk-field-input"
                    <?php echo $fields['age']['required'] ? 'required' : ''; ?>
                />
                <div class="cfk-field-description">
                    <?php printf(__('Age between %d and %d years', CFK_TEXT_DOMAIN), $fields['age']['min'], $fields['age']['max']); ?>
                </div>
            </div>
            
            <!-- Gender Field -->
            <div class="cfk-field-group">
                <label for="cfk_child_gender" class="cfk-field-label<?php echo $fields['gender']['required'] ? ' required' : ''; ?>">
                    <?php echo esc_html($fields['gender']['label']); ?>
                </label>
                <select 
                    id="cfk_child_gender" 
                    name="cfk_child_gender" 
                    class="cfk-field-input"
                    <?php echo $fields['gender']['required'] ? 'required' : ''; ?>
                >
                    <option value=""><?php _e('Select Gender', CFK_TEXT_DOMAIN); ?></option>
                    <?php foreach ($fields['gender']['options'] as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" 
                                <?php selected($meta_values['gender'] ?? '', $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="cfk-field-column">
            <!-- Shirt Size Field -->
            <div class="cfk-field-group">
                <label for="cfk_child_shirt_size" class="cfk-field-label">
                    <?php echo esc_html($fields['shirt_size']['label']); ?>
                </label>
                <select id="cfk_child_shirt_size" name="cfk_child_shirt_size" class="cfk-field-input">
                    <option value=""><?php _e('Select Shirt Size', CFK_TEXT_DOMAIN); ?></option>
                    <?php foreach ($fields['shirt_size']['options'] as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" 
                                <?php selected($meta_values['shirt_size'] ?? '', $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Pants Size Field -->
            <div class="cfk-field-group">
                <label for="cfk_child_pants_size" class="cfk-field-label">
                    <?php echo esc_html($fields['pants_size']['label']); ?>
                </label>
                <input 
                    type="text" 
                    id="cfk_child_pants_size" 
                    name="cfk_child_pants_size" 
                    value="<?php echo esc_attr($meta_values['pants_size'] ?? ''); ?>"
                    class="cfk-field-input"
                    placeholder="<?php _e('e.g., 28, 30, 32', CFK_TEXT_DOMAIN); ?>"
                />
            </div>
        </div>
    </div>
    
    <div class="cfk-field-row">
        <div class="cfk-field-column">
            <!-- Shoe Size Field -->
            <div class="cfk-field-group">
                <label for="cfk_child_shoe_size" class="cfk-field-label">
                    <?php echo esc_html($fields['shoe_size']['label']); ?>
                </label>
                <input 
                    type="text" 
                    id="cfk_child_shoe_size" 
                    name="cfk_child_shoe_size" 
                    value="<?php echo esc_attr($meta_values['shoe_size'] ?? ''); ?>"
                    class="cfk-field-input"
                    placeholder="<?php _e('e.g., 7, 8.5, 10', CFK_TEXT_DOMAIN); ?>"
                />
            </div>
        </div>
        
        <div class="cfk-field-column">
            <!-- Coat Size Field -->
            <div class="cfk-field-group">
                <label for="cfk_child_coat_size" class="cfk-field-label">
                    <?php echo esc_html($fields['coat_size']['label']); ?>
                </label>
                <select id="cfk_child_coat_size" name="cfk_child_coat_size" class="cfk-field-input">
                    <option value=""><?php _e('Select Coat Size', CFK_TEXT_DOMAIN); ?></option>
                    <?php foreach ($fields['coat_size']['options'] as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" 
                                <?php selected($meta_values['coat_size'] ?? '', $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Interests Field -->
    <div class="cfk-field-group">
        <label for="cfk_child_interests" class="cfk-field-label">
            <?php echo esc_html($fields['interests']['label']); ?>
        </label>
        <textarea 
            id="cfk_child_interests" 
            name="cfk_child_interests" 
            class="cfk-field-textarea"
            placeholder="<?php _e('e.g., Sports, Reading, Art, Music, Video Games...', CFK_TEXT_DOMAIN); ?>"
        ><?php echo esc_textarea($meta_values['interests'] ?? ''); ?></textarea>
        <div class="cfk-field-description">
            <?php _e('List the child\'s interests and hobbies to help sponsors choose appropriate gifts', CFK_TEXT_DOMAIN); ?>
        </div>
    </div>
    
    <!-- Family Situation Field -->
    <div class="cfk-field-group">
        <label for="cfk_child_family_situation" class="cfk-field-label">
            <?php echo esc_html($fields['family_situation']['label']); ?>
        </label>
        <textarea 
            id="cfk_child_family_situation" 
            name="cfk_child_family_situation" 
            class="cfk-field-textarea"
            placeholder="<?php _e('Brief description of the family situation...', CFK_TEXT_DOMAIN); ?>"
        ><?php echo esc_textarea($meta_values['family_situation'] ?? ''); ?></textarea>
        <div class="cfk-field-description">
            <?php _e('Brief, appropriate description to help sponsors understand the family context', CFK_TEXT_DOMAIN); ?>
        </div>
    </div>
    
    <!-- Special Needs Field -->
    <div class="cfk-field-group">
        <label for="cfk_child_special_needs" class="cfk-field-label">
            <?php echo esc_html($fields['special_needs']['label']); ?>
        </label>
        <textarea 
            id="cfk_child_special_needs" 
            name="cfk_child_special_needs" 
            class="cfk-field-textarea"
            placeholder="<?php _e('Any special needs or considerations...', CFK_TEXT_DOMAIN); ?>"
        ><?php echo esc_textarea($meta_values['special_needs'] ?? ''); ?></textarea>
        <div class="cfk-field-description">
            <?php _e('Any special needs, allergies, or considerations sponsors should be aware of', CFK_TEXT_DOMAIN); ?>
        </div>
    </div>
    
    <div class="cfk-field-group">
        <div style="background: #f0f0f1; padding: 10px; border-left: 4px solid #00a32a; margin-top: 20px;">
            <strong><?php _e('Tip:', CFK_TEXT_DOMAIN); ?></strong>
            <?php _e('Save the child profile as a draft first, then you can add a photo using the "Set featured image" option in the sidebar.', CFK_TEXT_DOMAIN); ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add basic form validation
    const form = document.querySelector('#post');
    if (form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = ['cfk_child_age', 'cfk_child_gender'];
            let hasErrors = false;
            
            requiredFields.forEach(function(fieldName) {
                const field = document.getElementById(fieldName);
                if (field && !field.value.trim()) {
                    field.style.borderColor = '#d63638';
                    hasErrors = true;
                } else if (field) {
                    field.style.borderColor = '';
                }
            });
            
            if (hasErrors) {
                alert('<?php _e('Please fill in all required fields (marked with *)', CFK_TEXT_DOMAIN); ?>');
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>