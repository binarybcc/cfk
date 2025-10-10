# Button System Quick Reference Card

## Function Signature

```php
renderButton(string $text, ?string $url, string $type, array $options): string
```

## Quick Examples

### Link Button (Navigation)
```php
// Basic link
<?php echo renderButton('View Profile', baseUrl('?page=child&id=5'), 'primary'); ?>

// Large link
<?php echo renderButton('View All', baseUrl('?page=children'), 'secondary', ['size' => 'large']); ?>

// External link
<?php echo renderButton('Help', 'https://example.com', 'info', ['target' => '_blank']); ?>
```

### Form Button (No Navigation)
```php
// Submit button
<?php echo renderButton('Submit', null, 'primary', ['submit' => true]); ?>

// Regular button
<?php echo renderButton('Cancel', null, 'secondary'); ?>

// Button with ID
<?php echo renderButton('Delete', null, 'danger', ['id' => 'deleteBtn']); ?>
```

### Special Features
```php
// Zeffy donation modal
<?php echo renderButton('Donate', null, 'success', [
    'size' => 'large',
    'id' => 'donate-btn',
    'attributes' => [
        'zeffy-form-link' => 'https://www.zeffy.com/embed/donation-form/...'
    ]
]); ?>

// Data attributes
<?php echo renderButton('Select', null, 'primary', [
    'attributes' => [
        'data-child-id' => '123',
        'data-action' => 'select'
    ]
]); ?>

// Full-width button
<?php echo renderButton('Continue', baseUrl('?page=next'), 'primary', ['block' => true]); ?>
```

## Button Types (Parameter 3)

| Type | Color | Use Case |
|------|-------|----------|
| `'primary'` | Dark Green | Main CTAs, primary actions |
| `'secondary'` | Gray | Cancel, back, alternative |
| `'success'` | Green | Donations, confirmations |
| `'danger'` | Red | Delete, cancel sponsorship |
| `'outline'` | Transparent | Secondary CTAs |
| `'info'` | - | Admin informational |
| `'warning'` | - | Admin warnings |

## Options Array (Parameter 4)

```php
[
    'size' => 'small' | 'large',     // Button size
    'id' => 'button-id',              // Element ID
    'class' => 'custom-class',        // Additional classes
    'block' => true,                  // Full-width
    'submit' => true,                 // type="submit" (buttons only)
    'target' => '_blank',             // Target (links only)
    'onclick' => 'doSomething()',     // JS handler
    'attributes' => [                 // Custom attributes
        'data-*' => 'value',
        'zeffy-form-link' => 'url'
    ]
]
```

## Common Patterns

### Breadcrumb Actions
```php
<?php echo renderButton('Back to List', baseUrl('?page=children'), 'secondary', ['size' => 'small']); ?>
```

### Hero CTAs
```php
<div class="hero-actions">
    <?php echo renderButton('Get Started', baseUrl('?page=children'), 'primary', ['size' => 'large']); ?>
    <?php echo renderButton('Learn More', baseUrl('?page=about'), 'outline', ['size' => 'large']); ?>
</div>
```

### Form Actions
```php
<div class="form-actions">
    <?php echo renderButton('Submit', null, 'primary', ['submit' => true, 'size' => 'large']); ?>
    <?php echo renderButton('Cancel', baseUrl('?page=back'), 'secondary', ['size' => 'large']); ?>
</div>
```

### Filter Bar
```php
<?php echo renderButton('Apply Filters', null, 'primary', ['submit' => true]); ?>
<?php echo renderButton('Clear', baseUrl('?page=children'), 'secondary'); ?>
```

### Card Actions
```php
<?php echo renderButton('View Details', baseUrl('?page=child&id=' . $id), 'primary'); ?>
<?php echo renderButton('View Family', baseUrl('?page=family&id=' . $fid), 'secondary', ['size' => 'small']); ?>
```

## Remember

✅ **DO**:
- Use for all new buttons
- Pass URL for navigation (links)
- Pass null for actions (buttons)
- Use appropriate type/color
- Leverage options for customization

❌ **DON'T**:
- Manually write button HTML
- Forget to sanitize (helper does it)
- Mix link and button behavior
- Use invalid type names

## Files

- **Helper**: `/includes/functions.php` (line 420)
- **Docs**: `/docs/BUTTON_SYSTEM.md`
- **Examples**: `/pages/home.php`, `/pages/children.php`, `/includes/components/child_card.php`

## CSS Classes Generated

```
.btn .btn-{type} .btn-{size} .btn-block {custom-classes}
```

Example: `class="btn btn-primary btn-large"`
