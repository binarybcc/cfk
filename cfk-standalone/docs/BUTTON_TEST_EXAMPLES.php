<?php
/**
 * Button System Test Examples
 *
 * This file demonstrates all features of the renderButton() helper function.
 * Use this as a reference for implementing buttons throughout the application.
 *
 * NOTE: This is a documentation/test file - not meant to be executed directly.
 */

// Ensure this file is not executed directly
if (!defined('CFK_APP')) {
    die('This is a documentation file. Do not execute directly.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Button System Test Examples</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .test-section {
            margin: 2rem 0;
            padding: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .test-section h2 {
            margin-top: 0;
            color: #2c5530;
        }
        .button-row {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }
        .code-example {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Button System Test Examples</h1>
        <p>This page demonstrates all features of the <code>renderButton()</code> helper function.</p>

        <!-- Basic Button Types -->
        <div class="test-section">
            <h2>1. Button Types</h2>
            <p>All available button types with default styling:</p>

            <div class="button-row">
                <?php echo renderButton('Primary', '#', 'primary'); ?>
                <?php echo renderButton('Secondary', '#', 'secondary'); ?>
                <?php echo renderButton('Success', '#', 'success'); ?>
                <?php echo renderButton('Danger', '#', 'danger'); ?>
                <?php echo renderButton('Outline', '#', 'outline'); ?>
                <?php echo renderButton('Info', '#', 'info'); ?>
                <?php echo renderButton('Warning', '#', 'warning'); ?>
            </div>

            <div class="code-example">
&lt;?php echo renderButton('Primary', '#', 'primary'); ?&gt;
&lt;?php echo renderButton('Secondary', '#', 'secondary'); ?&gt;
&lt;?php echo renderButton('Success', '#', 'success'); ?&gt;
            </div>
        </div>

        <!-- Button Sizes -->
        <div class="test-section">
            <h2>2. Button Sizes</h2>
            <p>Small, default, and large button sizes:</p>

            <div class="button-row">
                <?php echo renderButton('Small Button', '#', 'primary', ['size' => 'small']); ?>
                <?php echo renderButton('Default Button', '#', 'primary'); ?>
                <?php echo renderButton('Large Button', '#', 'primary', ['size' => 'large']); ?>
            </div>

            <div class="code-example">
&lt;?php echo renderButton('Small', '#', 'primary', ['size' => 'small']); ?&gt;
&lt;?php echo renderButton('Default', '#', 'primary'); ?&gt;
&lt;?php echo renderButton('Large', '#', 'primary', ['size' => 'large']); ?&gt;
            </div>
        </div>

        <!-- Link vs Button Elements -->
        <div class="test-section">
            <h2>3. Link Buttons vs Form Buttons</h2>
            <p>Links have URLs, buttons don't:</p>

            <div class="button-row">
                <?php echo renderButton('Link Button (has URL)', baseUrl('?page=children'), 'primary'); ?>
                <?php echo renderButton('Form Button (no URL)', null, 'secondary'); ?>
                <?php echo renderButton('Submit Button', null, 'success', ['submit' => true]); ?>
            </div>

            <div class="code-example">
// Renders &lt;a&gt; tag
&lt;?php echo renderButton('Link', baseUrl('?page=children'), 'primary'); ?&gt;

// Renders &lt;button type="button"&gt;
&lt;?php echo renderButton('Button', null, 'secondary'); ?&gt;

// Renders &lt;button type="submit"&gt;
&lt;?php echo renderButton('Submit', null, 'success', ['submit' => true]); ?&gt;
            </div>
        </div>

        <!-- Block Buttons -->
        <div class="test-section">
            <h2>4. Block (Full-Width) Buttons</h2>
            <p>Buttons that span the full container width:</p>

            <?php echo renderButton('Full Width Block Button', '#', 'primary', ['block' => true, 'size' => 'large']); ?>

            <div class="code-example">
&lt;?php echo renderButton('Full Width', '#', 'primary', ['block' => true]); ?&gt;
            </div>
        </div>

        <!-- Custom ID and Classes -->
        <div class="test-section">
            <h2>5. Custom IDs and Classes</h2>
            <p>Buttons with custom IDs and additional CSS classes:</p>

            <div class="button-row">
                <?php echo renderButton('Button with ID', '#', 'primary', ['id' => 'customButton']); ?>
                <?php echo renderButton('Custom Class', '#', 'secondary', ['class' => 'my-custom-class']); ?>
            </div>

            <div class="code-example">
&lt;?php echo renderButton('With ID', '#', 'primary', ['id' => 'customButton']); ?&gt;
&lt;?php echo renderButton('With Class', '#', 'secondary', ['class' => 'my-class']); ?&gt;
            </div>
        </div>

        <!-- Zeffy Donation Modal -->
        <div class="test-section">
            <h2>6. Zeffy Donation Modal Integration</h2>
            <p>Buttons that trigger Zeffy donation modals:</p>

            <?php echo renderButton(
                'Donate Now',
                null,
                'success',
                [
                    'size' => 'large',
                    'id' => 'donate-button',
                    'attributes' => [
                        'zeffy-form-link' => 'https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true'
                    ]
                ]
            ); ?>

            <div class="code-example">
&lt;?php echo renderButton('Donate Now', null, 'success', [
    'size' => 'large',
    'id' => 'donate-button',
    'attributes' => [
        'zeffy-form-link' => 'https://www.zeffy.com/embed/donation-form/...'
    ]
]); ?&gt;
            </div>
        </div>

        <!-- Data Attributes -->
        <div class="test-section">
            <h2>7. Data Attributes for JavaScript</h2>
            <p>Buttons with data-* attributes for JavaScript interaction:</p>

            <div class="button-row">
                <?php echo renderButton(
                    'Select Child',
                    null,
                    'primary',
                    [
                        'attributes' => [
                            'data-child-id' => '123',
                            'data-action' => 'select',
                            'data-name' => 'Test Child'
                        ]
                    ]
                ); ?>
            </div>

            <div class="code-example">
&lt;?php echo renderButton('Select', null, 'primary', [
    'attributes' => [
        'data-child-id' => '123',
        'data-action' => 'select'
    ]
]); ?&gt;
            </div>
        </div>

        <!-- External Links -->
        <div class="test-section">
            <h2>8. External Links</h2>
            <p>Buttons that open in new tabs/windows:</p>

            <?php echo renderButton(
                'View Documentation',
                'https://example.com/docs',
                'secondary',
                ['target' => '_blank']
            ); ?>

            <div class="code-example">
&lt;?php echo renderButton('View Docs', 'https://example.com', 'secondary', [
    'target' => '_blank'
]); ?&gt;
            </div>
        </div>

        <!-- OnClick Handlers -->
        <div class="test-section">
            <h2>9. JavaScript OnClick Handlers</h2>
            <p>Buttons with inline JavaScript handlers:</p>

            <?php echo renderButton(
                'Show Alert',
                null,
                'warning',
                ['onclick' => 'alert("Button clicked!")']
            ); ?>

            <div class="code-example">
&lt;?php echo renderButton('Show Alert', null, 'warning', [
    'onclick' => 'alert("Clicked!")'
]); ?&gt;
            </div>
        </div>

        <!-- Common Patterns -->
        <div class="test-section">
            <h2>10. Common Usage Patterns</h2>

            <h3>Hero Section CTAs</h3>
            <div class="button-row">
                <?php echo renderButton('View Children', baseUrl('?page=children'), 'primary', ['size' => 'large']); ?>
                <?php echo renderButton('Make Donation', baseUrl('?page=donate'), 'success', ['size' => 'large']); ?>
            </div>

            <h3>Form Actions</h3>
            <div class="button-row">
                <?php echo renderButton('Submit', null, 'primary', ['submit' => true, 'size' => 'large']); ?>
                <?php echo renderButton('Cancel', baseUrl('?page=back'), 'secondary', ['size' => 'large']); ?>
            </div>

            <h3>Filter Bar</h3>
            <div class="button-row">
                <?php echo renderButton('Apply Filters', null, 'primary', ['submit' => true]); ?>
                <?php echo renderButton('Clear', baseUrl('?page=children'), 'secondary'); ?>
            </div>

            <h3>Card Actions</h3>
            <div class="button-row">
                <?php echo renderButton('View Details', baseUrl('?page=child&id=1'), 'primary'); ?>
                <?php echo renderButton('View Family', baseUrl('?page=family&id=1'), 'secondary', ['size' => 'small']); ?>
            </div>

            <h3>Admin Actions</h3>
            <div class="button-row">
                <?php echo renderButton('Edit', baseUrl('admin/edit.php?id=1'), 'info', ['size' => 'small']); ?>
                <?php echo renderButton('Delete', baseUrl('admin/delete.php?id=1'), 'danger', ['size' => 'small']); ?>
            </div>

            <div class="code-example">
// See BUTTON_SYSTEM.md for complete examples
            </div>
        </div>

        <!-- Accessibility Features -->
        <div class="test-section">
            <h2>11. Accessibility Features</h2>
            <p>All buttons support keyboard navigation and have focus indicators:</p>

            <ul>
                <li>✅ Proper semantic HTML (&lt;a&gt; for links, &lt;button&gt; for actions)</li>
                <li>✅ Keyboard accessible (Tab to focus, Enter to activate)</li>
                <li>✅ Visible focus indicators (3px yellow outline)</li>
                <li>✅ Color contrast meets WCAG 2.1 AA standards</li>
                <li>✅ Text automatically sanitized for security</li>
            </ul>

            <p><strong>Test:</strong> Use Tab key to navigate through buttons above, then press Enter to activate.</p>
        </div>

        <!-- Security Features -->
        <div class="test-section">
            <h2>12. Security Features</h2>
            <p>The button helper automatically handles security:</p>

            <ul>
                <li>✅ Text sanitization via <code>sanitizeString()</code></li>
                <li>✅ URL escaping with <code>htmlspecialchars()</code></li>
                <li>✅ Attribute validation and whitelisting</li>
                <li>✅ Type validation with fallback to 'primary'</li>
                <li>✅ XSS prevention through proper escaping</li>
            </ul>

            <div class="code-example">
// User input is automatically sanitized:
$userText = $_GET['button_text'] ?? 'Default'; // Could contain malicious code
echo renderButton($userText, '#', 'primary'); // Safe - automatically sanitized
            </div>
        </div>

        <!-- Combined Options -->
        <div class="test-section">
            <h2>13. Complex Example - All Options Combined</h2>
            <p>A button using multiple options simultaneously:</p>

            <?php echo renderButton(
                'Complex Button',
                baseUrl('?page=test'),
                'success',
                [
                    'size' => 'large',
                    'id' => 'complexButton',
                    'class' => 'custom-animation',
                    'target' => '_blank',
                    'onclick' => 'console.log("Clicked!")',
                    'attributes' => [
                        'data-test' => 'value',
                        'data-complex' => 'true'
                    ]
                ]
            ); ?>

            <div class="code-example">
&lt;?php echo renderButton('Complex Button', baseUrl('?page=test'), 'success', [
    'size' => 'large',
    'id' => 'complexButton',
    'class' => 'custom-animation',
    'target' => '_blank',
    'onclick' => 'console.log("Clicked!")',
    'attributes' => [
        'data-test' => 'value',
        'data-complex' => 'true'
    ]
]); ?&gt;
            </div>
        </div>

        <!-- Documentation Links -->
        <div class="test-section">
            <h2>Documentation & Resources</h2>
            <ul>
                <li><strong>Complete Documentation:</strong> <code>/docs/BUTTON_SYSTEM.md</code></li>
                <li><strong>Quick Reference:</strong> <code>/docs/BUTTON_QUICK_REFERENCE.md</code></li>
                <li><strong>Implementation Summary:</strong> <code>/docs/BUTTON_SYSTEM_SUMMARY.md</code></li>
                <li><strong>Helper Function:</strong> <code>/includes/functions.php</code> (line 420)</li>
                <li><strong>CSS Styles:</strong> <code>/assets/css/styles.css</code> (lines 209-300)</li>
            </ul>

            <h3>Example Implementations:</h3>
            <ul>
                <li><code>/pages/home.php</code> - 5 buttons refactored</li>
                <li><code>/pages/children.php</code> - 4 buttons refactored</li>
                <li><code>/includes/components/child_card.php</code> - 3 buttons refactored</li>
            </ul>
        </div>
    </div>

    <script>
        // Example: Log data attributes on click
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-action]')) {
                console.log('Button clicked:', {
                    action: e.target.dataset.action,
                    childId: e.target.dataset.childId,
                    name: e.target.dataset.name
                });
            }
        });
    </script>
</body>
</html>
