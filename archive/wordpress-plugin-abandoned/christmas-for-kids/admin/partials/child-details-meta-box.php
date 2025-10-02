<?php
/**
 * Child Details Meta Box Template
 * 
 * @package ChristmasForKids
 * @since 1.0.0
 * @var int $age
 * @var string $gender
 * @var string $school_grade
 * @var string $interests
 * @var string $special_needs
 * @var string $clothing_size
 * @var string $shoe_size
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<table class="form-table">
    <tbody>
        <tr>
            <th scope="row">
                <label for="cfk_age"><?php esc_html_e('Age', CFK_TEXT_DOMAIN); ?> *</label>
            </th>
            <td>
                <input type="number" 
                       id="cfk_age" 
                       name="_cfk_age" 
                       value="<?php echo esc_attr($age); ?>" 
                       min="0" 
                       max="25" 
                       class="small-text" 
                       required>
                <p class="description"><?php esc_html_e('Child\'s age in years', CFK_TEXT_DOMAIN); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cfk_gender"><?php esc_html_e('Gender', CFK_TEXT_DOMAIN); ?> *</label>
            </th>
            <td>
                <select id="cfk_gender" name="_cfk_gender" required>
                    <option value=""><?php esc_html_e('Select gender...', CFK_TEXT_DOMAIN); ?></option>
                    <option value="male" <?php selected($gender, 'male'); ?>><?php esc_html_e('Male', CFK_TEXT_DOMAIN); ?></option>
                    <option value="female" <?php selected($gender, 'female'); ?>><?php esc_html_e('Female', CFK_TEXT_DOMAIN); ?></option>
                </select>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cfk_school_grade"><?php esc_html_e('School Grade', CFK_TEXT_DOMAIN); ?></label>
            </th>
            <td>
                <select id="cfk_school_grade" name="_cfk_school_grade">
                    <option value=""><?php esc_html_e('Select grade...', CFK_TEXT_DOMAIN); ?></option>
                    <option value="pre-k" <?php selected($school_grade, 'pre-k'); ?>><?php esc_html_e('Pre-K', CFK_TEXT_DOMAIN); ?></option>
                    <option value="k" <?php selected($school_grade, 'k'); ?>><?php esc_html_e('Kindergarten', CFK_TEXT_DOMAIN); ?></option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php selected($school_grade, (string)$i); ?>>
                            <?php printf(esc_html__('Grade %d', CFK_TEXT_DOMAIN), $i); ?>
                        </option>
                    <?php endfor; ?>
                    <option value="graduated" <?php selected($school_grade, 'graduated'); ?>><?php esc_html_e('Graduated', CFK_TEXT_DOMAIN); ?></option>
                </select>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cfk_clothing_size"><?php esc_html_e('Clothing Size', CFK_TEXT_DOMAIN); ?></label>
            </th>
            <td>
                <input type="text" 
                       id="cfk_clothing_size" 
                       name="_cfk_clothing_size" 
                       value="<?php echo esc_attr($clothing_size); ?>" 
                       class="regular-text">
                <p class="description"><?php esc_html_e('e.g., Youth M, Adult L, Size 10, etc.', CFK_TEXT_DOMAIN); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cfk_shoe_size"><?php esc_html_e('Shoe Size', CFK_TEXT_DOMAIN); ?></label>
            </th>
            <td>
                <input type="text" 
                       id="cfk_shoe_size" 
                       name="_cfk_shoe_size" 
                       value="<?php echo esc_attr($shoe_size); ?>" 
                       class="regular-text">
                <p class="description"><?php esc_html_e('e.g., 7, 10.5, Youth 5, etc.', CFK_TEXT_DOMAIN); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cfk_interests"><?php esc_html_e('Interests & Hobbies', CFK_TEXT_DOMAIN); ?></label>
            </th>
            <td>
                <textarea id="cfk_interests" 
                          name="_cfk_interests" 
                          rows="4" 
                          class="large-text"><?php echo esc_textarea($interests); ?></textarea>
                <p class="description"><?php esc_html_e('List the child\'s interests, hobbies, and favorite activities.', CFK_TEXT_DOMAIN); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cfk_special_needs"><?php esc_html_e('Special Needs / Notes', CFK_TEXT_DOMAIN); ?></label>
            </th>
            <td>
                <textarea id="cfk_special_needs" 
                          name="_cfk_special_needs" 
                          rows="3" 
                          class="large-text"><?php echo esc_textarea($special_needs); ?></textarea>
                <p class="description"><?php esc_html_e('Any special needs, dietary restrictions, or important notes about this child.', CFK_TEXT_DOMAIN); ?></p>
            </td>
        </tr>
    </tbody>
</table>

<style>
.form-table th {
    width: 150px;
    vertical-align: top;
    padding-top: 15px;
}

.form-table td {
    vertical-align: top;
    padding-top: 10px;
}

.form-table input[required]:invalid {
    border-color: #d63638;
    box-shadow: 0 0 2px rgba(214, 54, 56, 0.8);
}

.form-table select[required]:invalid {
    border-color: #d63638;
    box-shadow: 0 0 2px rgba(214, 54, 56, 0.8);
}

.form-table .description {
    margin-top: 5px;
    color: #646970;
    font-style: italic;
}
</style>