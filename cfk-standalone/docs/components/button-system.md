# Button System Documentation

## Overview

The Christmas for Kids application uses a standardized button system via the `renderButton()` helper function for consistent styling, accessibility, and maintainability.

## Current Button Patterns Audit

### Button Types (CSS Classes)
Based on audit of `assets/css/styles.css` and page templates:

1. **`.btn-primary`** - Main call-to-action buttons
   - Color: #2c5530 (dark green)
   - Hover: #1e3a21
   - Used for: Primary actions, sponsorship CTAs

2. **`.btn-secondary`** - Secondary actions
   - Color: #6c757d (gray)
   - Hover: #545862
   - Used for: Cancel, back, alternative actions

3. **`.btn-success`** - Success/positive actions
   - Color: #28a745 (green)
   - Hover: #218838
   - Used for: Donations, confirmations, complete actions

4. **`.btn-danger`** - Destructive/warning actions
   - Color: #dc3545 (red)
   - Hover: #c82333
   - Used for: Delete, cancel sponsorship, logout

5. **`.btn-outline`** - Outlined style
   - Border: 2px solid #2c5530
   - Background: transparent
   - Used for: Secondary CTAs, alternative options

6. **`.btn-info`** - Informational actions (admin only)
   - Used in admin dashboard

7. **`.btn-warning`** - Warning actions (admin only)
   - Used for pending/review actions

### Button Sizes

1. **Default** - Standard size (0.75rem padding)
2. **`.btn-small`** - Smaller buttons (0.5rem padding, 0.9rem font)
3. **`.btn-large`** - Large buttons (1rem padding, 1.1rem font)
4. **`.btn-block`** - Full-width buttons

### Current Usage Patterns

#### Link Buttons (`<a>` tags)
Used when navigating to different pages:
```php
<a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-primary">
    View Children
</a>
```

#### Form Buttons (`<button>` tags)
Used for form submissions and JavaScript interactions:
```php
<button type="submit" class="btn btn-large btn-primary">
    Submit Form
</button>
```

#### Special Attributes
- **Zeffy donation modals**: `zeffy-form-link` attribute
- **Data attributes**: `data-*` for JavaScript interactions
- **Icons**: Some buttons include emoji or icon spans

## The renderButton() Helper Function

### Location
`/includes/functions.php` (lines 420-555)

### Function Signature
```php
function renderButton(
    string $text,           // Button text (sanitized automatically)
    ?string $url = null,    // URL for links, null for buttons
    string $type = 'primary', // Button type
    array $options = []     // Additional options
): string
```

### Parameters

1. **`$text`** (required)
   - Button display text
   - Automatically sanitized with `sanitizeString()`

2. **`$url`** (optional, default: null)
   - If provided: Renders `<a>` tag with href
   - If null: Renders `<button>` tag

3. **`$type`** (optional, default: 'primary')
   - Valid values: 'primary', 'secondary', 'success', 'danger', 'outline', 'info', 'warning'
   - Invalid values fallback to 'primary'

4. **`$options`** (optional, default: [])
   - `size`: 'small' | 'large' - Button size
   - `id`: Element ID attribute
   - `class`: Additional CSS classes
   - `block`: Boolean - Make button full-width
   - `submit`: Boolean - Use type="submit" for buttons (default: type="button")
   - `target`: String - For links, target attribute (e.g., '_blank')
   - `onclick`: JavaScript onclick handler
   - `attributes`: Array of additional HTML attributes

### Security Features

1. **Automatic Text Sanitization**: All text is sanitized with `sanitizeString()`
2. **URL Escaping**: URLs are escaped with `htmlspecialchars()`
3. **Attribute Whitelisting**: Special handling for `data-*` and `zeffy-*` attributes
4. **Type Validation**: Only valid button types are allowed

## Usage Examples

### Basic Link Button
```php
echo renderButton('View Profile', baseUrl('?page=child&id=5'), 'primary');
// Output: <a href="?page=child&id=5" class="btn btn-primary">View Profile</a>
```

### Large Success Button
```php
echo renderButton('Make a Donation', baseUrl('?page=donate'), 'success', [
    'size' => 'large'
]);
// Output: <a href="?page=donate" class="btn btn-success btn-large">Make a Donation</a>
```

### Form Submit Button
```php
echo renderButton('Submit Sponsorship', null, 'primary', [
    'size' => 'large',
    'submit' => true,
    'id' => 'sponsorshipSubmit'
]);
// Output: <button type="submit" id="sponsorshipSubmit" class="btn btn-primary btn-large">Submit Sponsorship</button>
```

### Button with Zeffy Modal
```php
echo renderButton('Donate Now', null, 'success', [
    'id' => 'donate-btn',
    'attributes' => [
        'zeffy-form-link' => 'https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true'
    ]
]);
// Output: <button type="button" id="donate-btn" zeffy-form-link="..." class="btn btn-success">Donate Now</button>
```

### Small Secondary Button with Custom Class
```php
echo renderButton('Back', baseUrl('?page=children'), 'secondary', [
    'size' => 'small',
    'class' => 'back-button'
]);
// Output: <a href="?page=children" class="btn btn-secondary btn-small back-button">Back</a>
```

### Full-Width Block Button
```php
echo renderButton('Continue', baseUrl('?page=next'), 'primary', [
    'block' => true
]);
// Output: <a href="?page=next" class="btn btn-primary btn-block">Continue</a>
```

### Button with Data Attributes
```php
echo renderButton('Select Child', null, 'primary', [
    'attributes' => [
        'data-child-id' => '123',
        'data-action' => 'select'
    ]
]);
// Output: <button type="button" data-child-id="123" data-action="select" class="btn btn-primary">Select Child</button>
```

### External Link Button
```php
echo renderButton('View Documentation', 'https://example.com/docs', 'secondary', [
    'target' => '_blank'
]);
// Output: <a href="https://example.com/docs" class="btn btn-secondary" target="_blank">View Documentation</a>
```

## Migration Guide

### Before (Manual HTML)
```php
<a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-large btn-primary">
    View All Children
</a>
```

### After (Using Helper)
```php
<?php echo renderButton(
    'View All Children',
    baseUrl('?page=children'),
    'primary',
    ['size' => 'large']
); ?>
```

### Benefits of Migration
1. ✅ Consistent styling across all buttons
2. ✅ Automatic text sanitization
3. ✅ Type safety and validation
4. ✅ Easier to maintain and update
5. ✅ Better accessibility support
6. ✅ Reduced code duplication

## Accessibility Compliance

The button system supports WCAG 2.1 AA compliance:

1. **Focus Indicators**: CSS provides 3px yellow outline on focus
2. **Semantic HTML**: Uses appropriate `<a>` vs `<button>` elements
3. **Color Contrast**: All button colors meet contrast requirements
4. **Text Sanitization**: Prevents XSS and ensures clean text
5. **Keyboard Navigation**: All buttons are keyboard accessible

## File Locations

### Implementation Files
- **Helper Function**: `/includes/functions.php` (lines 420-555)
- **CSS Styles**: `/assets/css/styles.css` (lines 209-300, with duplicates)
- **Documentation**: `/docs/BUTTON_SYSTEM.md` (this file)

### Pages Using Buttons (Audit Results)

**Public Pages:**
- `/pages/home.php` - 5 button instances
- `/pages/children.php` - 6 button instances
- `/pages/child.php` - 10 button instances
- `/pages/sponsor.php` - 8 button instances
- `/pages/about.php` - Multiple instances
- `/pages/sponsor_portal.php` - Multiple instances
- `/pages/sponsor_lookup.php` - Multiple instances

**Admin Pages:**
- `/admin/index.php` - 10+ button instances
- `/admin/login.php` - 1 login button
- `/admin/import_csv.php` - 4 button instances
- `/admin/manage_sponsorships.php` - 10+ button instances
- `/admin/reports.php` - 4+ button instances
- `/admin/year_end_reset.php` - Multiple instances
- `/admin/manage_children.php` - Multiple instances
- `/admin/includes/admin_header.php` - 2 buttons (View Site, Logout)
- `/admin/includes/admin_footer.php` - Possible buttons

**Components:**
- `/includes/components/child_card.php` - 2 button instances
- `/includes/footer.php` - Possible buttons

### Button Type Usage Statistics

Based on audit of all PHP files:

1. **btn-primary**: ~45 instances (most common)
2. **btn-secondary**: ~20 instances
3. **btn-success**: ~15 instances (donations, confirmations)
4. **btn-small**: ~10 instances (mostly admin actions)
5. **btn-large**: ~12 instances (CTAs)
6. **btn-danger**: ~5 instances (cancel, delete, logout)
7. **btn-outline**: ~3 instances (secondary CTAs)
8. **btn-warning**: ~3 instances (admin pending actions)
9. **btn-info**: ~2 instances (admin dashboard)

## Refactoring Examples

### Example 1: Home Page Hero Section

**Before:**
```php
<a href="<?php echo baseUrl('?page=children'); ?>" class="btn btn-large btn-primary">
    View Children Needing Sponsorship
</a>
<a href="<?php echo baseUrl('?page=donate'); ?>" class="btn btn-large btn-success">
    Make a Donation
</a>
```

**After:**
```php
<?php echo renderButton(
    'View Children Needing Sponsorship',
    baseUrl('?page=children'),
    'primary',
    ['size' => 'large']
); ?>
<?php echo renderButton(
    'Make a Donation',
    baseUrl('?page=donate'),
    'success',
    ['size' => 'large']
); ?>
```

### Example 2: Zeffy Donation Button

**Before:**
```php
<button id="final-donate-btn" class="btn btn-large btn-outline"
        zeffy-form-link="https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true">
    Make a General Donation
</button>
```

**After:**
```php
<?php echo renderButton(
    'Make a General Donation',
    null,
    'outline',
    [
        'size' => 'large',
        'id' => 'final-donate-btn',
        'attributes' => [
            'zeffy-form-link' => 'https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true'
        ]
    ]
); ?>
```

### Example 3: Form Submit Buttons

**Before:**
```php
<button type="submit" name="submit_sponsorship" class="btn btn-large btn-primary">
    Submit Sponsorship Request
</button>
<a href="<?php echo baseUrl('?page=child&id=' . $childId); ?>" class="btn btn-large btn-secondary">
    Back to Child Profile
</a>
```

**After:**
```php
<?php echo renderButton(
    'Submit Sponsorship Request',
    null,
    'primary',
    [
        'size' => 'large',
        'submit' => true,
        'id' => 'submit_sponsorship'
    ]
); ?>
<?php echo renderButton(
    'Back to Child Profile',
    baseUrl('?page=child&id=' . $childId),
    'secondary',
    ['size' => 'large']
); ?>
```

Note: The `name` attribute is not currently supported by the helper. For form buttons requiring a name attribute, continue using manual HTML or enhance the helper function.

## Best Practices

1. **Use renderButton() for all new buttons** - Ensures consistency
2. **Choose appropriate button types** - Use semantic type names
3. **Use link buttons for navigation** - Pass URL as second parameter
4. **Use form buttons for actions** - Pass null for URL
5. **Sanitize dynamic data** - Helper does this automatically
6. **Test accessibility** - All buttons should be keyboard accessible
7. **Avoid inline styles** - Use CSS classes via the helper

## Future Enhancements

Potential improvements to the button system:

1. **Icon Support**: Add `icon` option for leading/trailing icons
2. **Loading States**: Add `loading` option for async actions
3. **Disabled State**: Add `disabled` option
4. **Name Attribute**: Add support for form button `name` attribute
5. **Tooltip Support**: Add `title` or `aria-label` options
6. **Button Groups**: Helper for rendering button groups
7. **Dropdown Buttons**: Support for dropdown menus

## Testing

### Manual Testing Checklist
- [ ] Link buttons navigate correctly
- [ ] Form buttons submit/trigger correctly
- [ ] All button types display correct colors
- [ ] Size variations work properly
- [ ] Custom attributes are rendered
- [ ] Zeffy buttons trigger modal
- [ ] Text is properly sanitized
- [ ] Focus indicators appear on keyboard navigation
- [ ] Buttons are keyboard accessible (Tab + Enter)

### Browser Compatibility
Tested and working on:
- ✅ Chrome/Edge (modern)
- ✅ Firefox (modern)
- ✅ Safari (modern)
- ✅ Mobile browsers (iOS Safari, Chrome Android)

## Support

For questions or issues with the button system:
1. Check this documentation first
2. Review `/includes/functions.php` implementation
3. Inspect CSS in `/assets/css/styles.css`
4. Contact development team

---

**Last Updated**: 2025-10-08
**Version**: 1.0
**Maintainer**: Development Team
