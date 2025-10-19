# Component Library Documentation
## Christmas for Kids - Reusable UI Components

**Version**: 1.0
**Last Updated**: 2025-10-08
**Maintainer**: Development Team

---

## Table of Contents

1. [Overview](#overview)
2. [Component Architecture](#component-architecture)
3. [Components](#components)
   - [Page Header Component](#1-page-header-component)
   - [Child Card Component](#2-child-card-component)
   - [Button System](#3-button-system)
   - [Avatar System](#4-avatar-system)
4. [Development Guidelines](#development-guidelines)
5. [Examples Directory](#examples-directory)
6. [Component Checklist](#component-checklist)
7. [Troubleshooting](#troubleshooting)

---

## Overview

The Christmas for Kids application uses a component-based architecture to ensure consistency, maintainability, and reusability across the codebase. All components follow security best practices with automatic sanitization and validation.

### Component Philosophy

- **Reusability**: Components can be used across multiple pages
- **Security**: All inputs are sanitized automatically
- **Accessibility**: WCAG 2.1 AA compliance built-in
- **Consistency**: Standardized styling and behavior
- **Maintainability**: Single source of truth for UI elements

### Component Types

1. **Include Components**: PHP files loaded via `require_once`
2. **Helper Functions**: Utility functions that return HTML strings
3. **Class-Based Components**: Object-oriented components with methods

---

## Component Architecture

### File Structure

```
cfk-standalone/
├── includes/
│   ├── components/          # Reusable UI components
│   │   ├── page_header.php  # Page header with gradient
│   │   └── child_card.php   # Child profile cards
│   ├── functions.php        # Helper functions (renderButton)
│   └── avatar_manager.php   # Avatar generation system
└── assets/
    └── images/              # Avatar image files
```

### Component Loading Patterns

#### Pattern 1: Variable-Based Include
```php
<?php
$title = 'Page Title';
$description = 'Description text';
require_once __DIR__ . '/../includes/components/page_header.php';
?>
```

#### Pattern 2: Helper Function
```php
<?php
echo renderButton('Click Me', baseUrl('?page=home'), 'primary');
?>
```

#### Pattern 3: Static Class Method
```php
<?php
$avatarUrl = CFK_Avatar_Manager::getAvatarForChild($child);
?>
```

---

## Components

## 1. Page Header Component

### Description
Reusable page header with gradient background, title, optional description, and additional content area. Provides consistent branding across all pages.

### Location
`/includes/components/page_header.php`

### Visual Appearance
```
┌─────────────────────────────────────────┐
│                                         │
│         Page Title (Large)              │
│                                         │
│    Optional description text that       │
│    explains the page purpose            │
│                                         │
│    [Optional Additional Content]        │
│                                         │
└─────────────────────────────────────────┘
    Gradient: Dark Green → Forest Green
```

### Parameters

All parameters are passed as variables before including the component:

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `$title` | string | Yes | `''` | Main page heading |
| `$description` | string | No | `''` | Subtitle/description text |
| `$additionalClasses` | string | No | `''` | Extra CSS classes |
| `$additionalContent` | string | No | `''` | HTML content to append |

### Security Features

- All text automatically sanitized with `sanitizeString()`
- `$additionalContent` allows raw HTML (use with caution)
- Direct access protection via `CFK_APP` constant check

### Usage Examples

#### Example 1: Basic Header
```php
<?php
$title = 'About Christmas for Kids';
$description = 'Our mission is to ensure every child experiences the magic of Christmas.';
require_once __DIR__ . '/../includes/components/page_header.php';
?>
```

**Output:**
```html
<div class="page-header">
    <h1>About Christmas for Kids</h1>
    <p class="page-description">Our mission is to ensure every child experiences the magic of Christmas.</p>
</div>
```

#### Example 2: Header with Additional Classes
```php
<?php
$title = 'Admin Dashboard';
$description = 'Manage sponsorships and children';
$additionalClasses = 'admin-header highlighted';
require_once __DIR__ . '/../includes/components/page_header.php';
?>
```

#### Example 3: Header with Custom Content
```php
<?php
$title = 'Children Needing Sponsorship';
$description = 'Browse children and select someone to sponsor.';

// Build additional content
ob_start();
?>
<div class="results-summary">
    <p>Showing <?php echo count($children); ?> of <?php echo $totalCount; ?> children</p>
</div>
<?php
$additionalContent = ob_get_clean();

require_once __DIR__ . '/../includes/components/page_header.php';
?>
```

#### Example 4: Title-Only Header
```php
<?php
$title = 'Donate Now';
// No description needed
require_once __DIR__ . '/../includes/components/page_header.php';
?>
```

### Styling

The component includes embedded CSS with:
- Gradient background: `#2c5530` → `#4a7c59`
- White text with high contrast
- Responsive font sizes (2.5rem → 2rem on mobile)
- Centered layout with max-width description
- Rounded corners (8px border-radius)

### Best Practices

1. **Always provide a title** - Required for accessibility
2. **Keep descriptions concise** - Aim for 1-2 sentences
3. **Use semantic titles** - Describe the page purpose clearly
4. **Sanitize dynamic content** - Component handles basic sanitization
5. **Test responsive** - Verify on mobile devices

### Common Patterns

**Pattern 1: Standard Page**
```php
$title = 'Page Name';
$description = 'What this page does';
require_once __DIR__ . '/../includes/components/page_header.php';
```

**Pattern 2: With Results Count**
```php
ob_start();
echo '<p>Found ' . count($items) . ' results</p>';
$additionalContent = ob_get_clean();
require_once __DIR__ . '/../includes/components/page_header.php';
```

**Pattern 3: Admin Pages**
```php
$title = 'Admin: ' . $sectionName;
$additionalClasses = 'admin-header';
require_once __DIR__ . '/../includes/components/page_header.php';
```

### Troubleshooting

**Issue**: Variables not appearing in header
- **Solution**: Ensure variables are set BEFORE `require_once`

**Issue**: HTML in description showing as plain text
- **Solution**: Use `$additionalContent` for HTML, not `$description`

**Issue**: Styling conflicts
- **Solution**: Use `$additionalClasses` instead of inline styles

---

## 2. Child Card Component

### Description
Displays child profile information in a consistent, attractive card format. Supports various display modes, action buttons, and sibling information. Optimized to prevent N+1 query problems.

### Location
`/includes/components/child_card.php`

### Visual Appearance
```
┌─────────────────────────────┐
│    ┌─────────────────┐      │
│    │                 │      │
│    │  Child Avatar   │      │
│    │     (Image)     │      │
│    │                 │      │
│    └─────────────────┘      │
│                              │
│    Child Name (Bold)         │
│    8 years old • 3rd Grade   │
│                              │
│    "I wish for art supplies  │
│    and books..."             │
│                              │
│    [Learn More]              │
└─────────────────────────────┘
```

### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `$child` | array | Yes | - | Child data array |
| `$options` | array | No | `[]` | Configuration options |

### Child Array Structure

```php
$child = [
    'id' => 123,                    // Database ID
    'display_id' => 'F001A',        // Family + child letter
    'name' => 'Alex',               // Child name
    'age' => 8,                     // Age in years
    'grade' => '3',                 // School grade
    'gender' => 'M',                // M or F
    'photo_filename' => 'photo.jpg',// Photo filename (unused - avatars used)
    'wishes' => 'Art supplies...',  // Wish list text
    'interests' => 'Drawing...',    // Interests text
    'family_id' => 1,               // Family database ID
    'status' => 'available'         // available|pending|sponsored
];
```

### Options Array

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `show_wishes` | bool | `true` | Display wishes preview (80 chars) |
| `show_interests` | bool | `false` | Display interests preview (100 chars) |
| `show_id` | bool | `false` | Display child ID (F001A format) |
| `show_siblings` | bool | `false` | Display sibling information |
| `siblings` | array | `[]` | Pre-loaded sibling data (prevents N+1) |
| `card_class` | string | `'child-card'` | CSS class for card wrapper |
| `button_text` | string | `'Learn More'` | Text for primary button |
| `show_actions` | bool | `false` | Show actions section at bottom |
| `show_family_button` | bool | `false` | Show "View Family" button |

### Security Features

- All text sanitized with `sanitizeString()`
- Avatar system prevents exposure of real photos
- Validation of required `$child` parameter
- Protection against N+1 queries via eager loading

### Usage Examples

#### Example 1: Basic Child Card
```php
<?php
// Simple display with default options
include __DIR__ . '/../includes/components/child_card.php';
?>
```

**Output:** Card with name, age, grade, wishes preview, and "Learn More" button

#### Example 2: Card with Custom Button Text
```php
<?php
$options = [
    'button_text' => 'View Full Profile'
];
include __DIR__ . '/../includes/components/child_card.php';
?>
```

#### Example 3: Card Showing ID and Interests
```php
<?php
$options = [
    'show_id' => true,
    'show_interests' => true,
    'show_wishes' => true
];
include __DIR__ . '/../includes/components/child_card.php';
?>
```

#### Example 4: Card with Sibling Information
```php
<?php
// Pre-load siblings to prevent N+1 queries
$siblingsByFamily = eagerLoadFamilyMembers($children);

// For each child
$siblings = $siblingsByFamily[$child['family_id']] ?? [];
$filteredSiblings = array_filter($siblings, fn($s) => $s['id'] !== $child['id']);

$options = [
    'show_siblings' => true,
    'siblings' => $filteredSiblings
];
include __DIR__ . '/../includes/components/child_card.php';
?>
```

#### Example 5: Card with Action Buttons
```php
<?php
$options = [
    'show_actions' => true,
    'show_family_button' => true,
    'siblings' => $siblings,
    'button_text' => 'View Details'
];
include __DIR__ . '/../includes/components/child_card.php';
?>
```

**Output:** Card with two buttons in actions section: "View Details" and "View Family"

#### Example 6: Admin Card (Full Information)
```php
<?php
$options = [
    'show_id' => true,
    'show_interests' => true,
    'show_wishes' => true,
    'show_siblings' => true,
    'siblings' => $siblings,
    'card_class' => 'child-card admin-card',
    'button_text' => 'Edit Child'
];
include __DIR__ . '/../includes/components/child_card.php';
?>
```

#### Example 7: Grid of Children Cards
```php
<div class="children-grid">
    <?php foreach ($children as $child): ?>
        <?php
        // Get siblings for this child
        $siblings = $siblingsByFamily[$child['family_id']] ?? [];
        $filteredSiblings = array_filter($siblings, fn($s) => $s['id'] !== $child['id']);

        $options = [
            'show_siblings' => !empty($filteredSiblings),
            'siblings' => $filteredSiblings
        ];

        include __DIR__ . '/../includes/components/child_card.php';
        ?>
    <?php endforeach; ?>
</div>
```

### Performance Optimization

**CRITICAL: Prevent N+1 Queries**

When displaying multiple children cards, ALWAYS eager load siblings:

```php
// ✅ CORRECT - Eager load all siblings at once
$children = getChildren($filters, $page, $limit);
$siblingsByFamily = eagerLoadFamilyMembers($children);

foreach ($children as $child) {
    $siblings = $siblingsByFamily[$child['family_id']] ?? [];
    // Use siblings in options
}

// ❌ WRONG - Triggers N+1 queries
foreach ($children as $child) {
    $siblings = getFamilyMembers($child['family_id']); // Query per child!
}
```

### Styling

The component relies on CSS from `/assets/css/styles.css`:

- `.child-card` - Card container with border and shadow
- `.child-photo` - Avatar image container
- `.child-info` - Text content area
- `.child-name` - Bold child name
- `.child-details` - Age and grade inline text
- `.child-wishes` - Italicized quote-style wishes
- `.child-actions` - Button container at bottom

### Best Practices

1. **Always pass valid child array** - Component validates but will warn
2. **Eager load siblings** - Use `eagerLoadFamilyMembers()` for lists
3. **Choose appropriate options** - Don't show all info everywhere
4. **Use consistent button text** - Standard is "Learn More"
5. **Test truncation** - Wishes/interests are limited to prevent overflow
6. **Mobile responsive** - Cards stack on small screens

### Common Patterns

**Pattern 1: Public Browse Page**
```php
$options = ['show_wishes' => true];
```

**Pattern 2: Family View**
```php
$options = [
    'show_siblings' => true,
    'show_family_button' => false, // Already viewing family
    'siblings' => $siblings
];
```

**Pattern 3: Search Results**
```php
$options = [
    'show_interests' => true,
    'show_wishes' => true,
    'button_text' => 'View Match'
];
```

### Troubleshooting

**Issue**: Warning "Child card component requires $child array"
- **Solution**: Ensure `$child` variable is set before including component

**Issue**: N+1 queries (slow page load)
- **Solution**: Use `eagerLoadFamilyMembers()` before loop

**Issue**: Sibling info not showing
- **Solution**: Check `show_siblings => true` AND `siblings` array is not empty

**Issue**: Button not appearing
- **Solution**: Check `show_actions` setting - default is `false`

**Issue**: Long text overflowing card
- **Solution**: Component auto-truncates; check CSS for max-width

---

## 3. Button System

### Description
Standardized button rendering system using the `renderButton()` helper function. Ensures consistent styling, accessibility, and security across all buttons in the application.

### Location
`/includes/functions.php` (lines 420-555)

### Function Signature

```php
function renderButton(
    string $text,           // Button text (auto-sanitized)
    ?string $url = null,    // URL for <a> tag, null for <button> tag
    string $type = 'primary', // Button style type
    array $options = []     // Additional configuration
): string
```

### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `$text` | string | Yes | - | Button display text (auto-sanitized) |
| `$url` | string\|null | No | `null` | Link URL or null for button element |
| `$type` | string | No | `'primary'` | Button style type |
| `$options` | array | No | `[]` | Additional options |

### Button Types

| Type | Color | Use Case | Example |
|------|-------|----------|---------|
| `primary` | Dark Green (#2c5530) | Main actions, CTAs | Sponsor, Submit |
| `secondary` | Gray (#6c757d) | Secondary actions | Back, Cancel |
| `success` | Green (#28a745) | Success actions | Donate, Confirm |
| `danger` | Red (#dc3545) | Destructive actions | Delete, Logout |
| `outline` | Outlined Green | Alternative CTAs | Learn More |
| `info` | Blue | Informational | View Details |
| `warning` | Orange | Warning actions | Review, Pending |

### Options Array

| Option | Type | Description | Example |
|--------|------|-------------|---------|
| `size` | string | `'small'` or `'large'` | `'large'` |
| `id` | string | Element ID attribute | `'submitBtn'` |
| `class` | string | Additional CSS classes | `'custom-class'` |
| `block` | bool | Full-width button | `true` |
| `submit` | bool | Use `type="submit"` for buttons | `true` |
| `target` | string | Link target attribute | `'_blank'` |
| `onclick` | string | JavaScript onclick handler | `'alert("Hi")'` |
| `attributes` | array | Custom HTML attributes | `['data-id' => '123']` |

### Security Features

- Automatic text sanitization with `sanitizeString()`
- URL escaping with `htmlspecialchars()`
- Attribute whitelisting (allows `data-*` and `zeffy-*`)
- Type validation (falls back to 'primary' if invalid)

### Usage Examples

#### Example 1: Basic Link Button
```php
<?php
echo renderButton('View Profile', baseUrl('?page=child&id=5'), 'primary');
?>
```

**Output:**
```html
<a href="?page=child&id=5" class="btn btn-primary">View Profile</a>
```

#### Example 2: Large Success Button
```php
<?php
echo renderButton('Make a Donation', baseUrl('?page=donate'), 'success', [
    'size' => 'large'
]);
?>
```

**Output:**
```html
<a href="?page=donate" class="btn btn-success btn-large">Make a Donation</a>
```

#### Example 3: Form Submit Button
```php
<?php
echo renderButton('Submit Sponsorship', null, 'primary', [
    'size' => 'large',
    'submit' => true,
    'id' => 'sponsorshipSubmit'
]);
?>
```

**Output:**
```html
<button type="submit" id="sponsorshipSubmit" class="btn btn-primary btn-large">Submit Sponsorship</button>
```

#### Example 4: Button with Zeffy Donation Modal
```php
<?php
echo renderButton('Donate Now', null, 'success', [
    'id' => 'donate-btn',
    'attributes' => [
        'zeffy-form-link' => 'https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true'
    ]
]);
?>
```

**Output:**
```html
<button type="button" id="donate-btn" zeffy-form-link="https://..." class="btn btn-success">Donate Now</button>
```

#### Example 5: Small Secondary Button with Custom Class
```php
<?php
echo renderButton('Back', baseUrl('?page=children'), 'secondary', [
    'size' => 'small',
    'class' => 'back-button'
]);
?>
```

**Output:**
```html
<a href="?page=children" class="btn btn-secondary btn-small back-button">Back</a>
```

#### Example 6: Full-Width Block Button
```php
<?php
echo renderButton('Continue', baseUrl('?page=next'), 'primary', [
    'block' => true
]);
?>
```

**Output:**
```html
<a href="?page=next" class="btn btn-primary btn-block">Continue</a>
```

#### Example 7: Button with Data Attributes
```php
<?php
echo renderButton('Select Child', null, 'primary', [
    'attributes' => [
        'data-child-id' => '123',
        'data-action' => 'select'
    ]
]);
?>
```

**Output:**
```html
<button type="button" data-child-id="123" data-action="select" class="btn btn-primary">Select Child</button>
```

#### Example 8: External Link with Target
```php
<?php
echo renderButton('View Documentation', 'https://example.com/docs', 'secondary', [
    'target' => '_blank'
]);
?>
```

**Output:**
```html
<a href="https://example.com/docs" class="btn btn-secondary" target="_blank">View Documentation</a>
```

#### Example 9: Danger Button with Confirmation
```php
<?php
echo renderButton('Delete Child', null, 'danger', [
    'onclick' => 'return confirm("Are you sure?");',
    'attributes' => ['data-child-id' => $childId]
]);
?>
```

#### Example 10: Outline Button
```php
<?php
echo renderButton('Learn More', baseUrl('?page=about'), 'outline', [
    'size' => 'large'
]);
?>
```

### Button Size Reference

```php
// Small button (compact)
['size' => 'small']

// Default button (standard)
// No size option needed

// Large button (prominent CTAs)
['size' => 'large']

// Full-width button (mobile forms)
['block' => true]

// Large full-width button
['size' => 'large', 'block' => true]
```

### Accessibility Compliance

The button system supports WCAG 2.1 AA compliance:

1. **Focus Indicators**: 3px yellow outline on keyboard focus
2. **Semantic HTML**: Correct use of `<a>` vs `<button>`
3. **Color Contrast**: All colors meet 4.5:1 contrast ratio
4. **Keyboard Navigation**: Tab and Enter key support
5. **Text Sanitization**: Prevents XSS attacks

### Best Practices

1. **Use link buttons for navigation** - Pass URL as second parameter
2. **Use form buttons for actions** - Pass `null` for URL
3. **Choose semantic button types** - `danger` for destructive, `success` for positive
4. **Always provide clear text** - Avoid vague labels like "Click Here"
5. **Use size appropriately** - Large for main CTAs, small for secondary
6. **Test keyboard navigation** - Ensure all buttons are Tab-accessible
7. **Sanitize dynamic content** - Helper does this automatically

### Common Patterns

**Pattern 1: Navigation**
```php
renderButton('Text', baseUrl('?page=name'), 'primary')
```

**Pattern 2: Form Submission**
```php
renderButton('Submit', null, 'primary', ['submit' => true])
```

**Pattern 3: Secondary Action**
```php
renderButton('Cancel', baseUrl('?page=back'), 'secondary')
```

**Pattern 4: Destructive Action**
```php
renderButton('Delete', null, 'danger', [
    'onclick' => 'return confirm("Sure?");'
])
```

### Troubleshooting

**Issue**: Button not rendering
- **Solution**: Check that function is echoed: `echo renderButton(...)`

**Issue**: URL not working
- **Solution**: Use `baseUrl()` helper for relative URLs

**Issue**: Custom attributes not showing
- **Solution**: Use `attributes` option array, not root level options

**Issue**: Button type color wrong
- **Solution**: Verify type is valid; invalid types default to 'primary'

**Issue**: Submit button not submitting form
- **Solution**: Set `'submit' => true` in options

For complete button system documentation, see `/docs/BUTTON_SYSTEM.md`

---

## 4. Avatar System

### Description
Privacy-preserving avatar system that generates age and gender-appropriate silhouetted avatars for children. Uses pre-rendered PNG images to protect child privacy while maintaining visual appeal.

### Location
`/includes/avatar_manager.php`

### Class Name
`CFK_Avatar_Manager`

### Purpose

The avatar system serves three critical functions:
1. **Privacy Protection**: Never uses real child photos
2. **Visual Consistency**: Standardized avatars across the application
3. **Age Appropriateness**: Different avatars for different age groups

### Avatar Categories

| Category | Age Range | Gender | Image File |
|----------|-----------|--------|------------|
| Male Toddler | 0-4 | Male | `b-4boysm.png` |
| Female Toddler | 0-4 | Female | `b-4girlsm.png` |
| Male Elementary | 5-10 | Male | `elementaryboysm.png` |
| Female Elementary | 5-10 | Female | `elementarygirlsm.png` |
| Male Middle | 11-13 | Male | `middleboysm.png` |
| Female Middle | 11-13 | Female | `middlegirlsm.png` |
| Male High School | 14+ | Male | `hsboysm.png` |
| Female High School | 14+ | Female | `hsgirlsm.png` |

### Public Methods

#### 1. `getAvatarForChild(array $child): string`

Returns avatar URL for a child based on age and gender.

**Parameters:**
- `$child` - Array with at least `age` and `gender` keys

**Returns:** String URL to avatar image

**Example:**
```php
<?php
$child = [
    'age' => 8,
    'gender' => 'F'
];

$avatarUrl = CFK_Avatar_Manager::getAvatarForChild($child);
// Returns: https://example.com/cfk-standalone/assets/images/elementarygirlsm.png
?>
```

#### 2. `getAvailableCategories(): array`

Returns array of all available avatar categories.

**Returns:** Associative array of categories

**Example:**
```php
<?php
$categories = CFK_Avatar_Manager::getAvailableCategories();
/*
Returns:
[
    'infant' => 'Infant (0-2)',
    'male_toddler' => 'Male Toddler (3-5)',
    'female_toddler' => 'Female Toddler (3-5)',
    ...
]
*/
?>
```

### Usage Examples

#### Example 1: Basic Avatar in HTML
```php
<img src="<?php echo CFK_Avatar_Manager::getAvatarForChild($child); ?>"
     alt="Avatar for <?php echo sanitizeString($child['name']); ?>">
```

#### Example 2: Using getPhotoUrl Helper
```php
<?php
// The getPhotoUrl() helper automatically uses avatar manager
$avatarUrl = getPhotoUrl($child['photo_filename'], $child);
?>
<img src="<?php echo $avatarUrl; ?>" alt="Child avatar">
```

#### Example 3: Preloading Avatar in Data
```php
<?php
$children = getChildren();

foreach ($children as &$child) {
    $child['avatar_url'] = CFK_Avatar_Manager::getAvatarForChild($child);
}
?>

<!-- Later in HTML -->
<img src="<?php echo $child['avatar_url']; ?>" alt="Avatar">
```

#### Example 4: Conditional Avatar Display
```php
<?php
if (isset($child['age']) && isset($child['gender'])) {
    $avatar = CFK_Avatar_Manager::getAvatarForChild($child);
} else {
    $avatar = baseUrl('assets/images/b-4girlsm.png'); // Fallback
}
?>
```

#### Example 5: Avatar in Child Card Component
```php
<!-- Already integrated in child_card.php component -->
<div class="child-photo">
    <img src="<?php echo getPhotoUrl($child['photo_filename'], $child); ?>"
         alt="Avatar for <?php echo sanitizeString($child['name']); ?>"
         loading="lazy">
</div>
```

### Age Category Logic

The system determines avatar category using this logic:

```php
Age 0-4:
    Male → 'male_toddler' → b-4boysm.png
    Female → 'female_toddler' → b-4girlsm.png

Age 5-10:
    Male → 'male_elementary' → elementaryboysm.png
    Female → 'female_elementary' → elementarygirlsm.png

Age 11-13:
    Male → 'male_middle' → middleboysm.png
    Female → 'female_middle' → middlegirlsm.png

Age 14+:
    Male → 'male_highschool' → hsboysm.png
    Female → 'female_highschool' → hsgirlsm.png
```

### Image Specifications

All avatar images are:
- **Format**: PNG with transparency
- **Size**: Optimized for web (< 50KB each)
- **Aspect Ratio**: Square (1:1)
- **Style**: Silhouette/minimalist design
- **Color**: Christmas green (#2c5530) theme
- **Location**: `/assets/images/` directory

### Security Features

1. **No Real Photos**: System explicitly prevents use of real child photos
2. **Automatic Fallback**: Returns default avatar if child data invalid
3. **URL Generation**: Uses `baseUrl()` helper for proper path resolution
4. **Type Safety**: Expects specific array keys (age, gender)

### SVG Generation (Legacy)

The class includes SVG generation methods (currently unused):
- `generateSilhouettedAvatar()` - Creates data URI SVG
- `getSvgData()` - Returns SVG markup for each category
- Various `get*Svg()` methods for each age/gender combination

These methods are preserved for future use if PNG avatars need to be replaced with dynamic SVGs.

### Integration Points

The avatar system is integrated at:

1. **`getPhotoUrl()` function** - Automatically uses avatars
2. **Child Card Component** - Displays child avatars
3. **Child Detail Pages** - Shows larger avatar
4. **Admin Interface** - Previews child profiles

### Best Practices

1. **Always provide age and gender** - Required for proper avatar selection
2. **Use getPhotoUrl() helper** - Don't call avatar manager directly
3. **Add alt text** - Include child name or "Avatar" description
4. **Lazy load images** - Use `loading="lazy"` attribute
5. **Test fallback** - Ensure default avatar works if data missing

### Common Patterns

**Pattern 1: Standard Usage**
```php
<img src="<?php echo getPhotoUrl(null, $child); ?>" alt="Avatar">
```

**Pattern 2: With Lazy Loading**
```php
<img src="<?php echo getPhotoUrl(null, $child); ?>"
     alt="Avatar for <?php echo $child['name']; ?>"
     loading="lazy">
```

**Pattern 3: Direct Avatar Manager**
```php
<?php
$avatarUrl = CFK_Avatar_Manager::getAvatarForChild([
    'age' => 12,
    'gender' => 'M'
]);
?>
```

### Troubleshooting

**Issue**: Wrong avatar showing for child
- **Solution**: Verify `age` and `gender` fields are correct in database

**Issue**: Avatar not loading (404 error)
- **Solution**: Check image files exist in `/assets/images/` directory

**Issue**: All children showing same avatar
- **Solution**: Ensure child array includes `age` and `gender` keys

**Issue**: Avatar URL incorrect
- **Solution**: Verify `baseUrl()` function is configured correctly

**Issue**: Fallback avatar not working
- **Solution**: Check `b-4girlsm.png` exists (default fallback image)

---

## Development Guidelines

### Creating New Components

Follow these steps when creating a new reusable component:

#### 1. Determine Component Type

**Include Component** - Use when:
- Component needs multiple variables
- Component has significant HTML structure
- Component is used in 3+ places

**Helper Function** - Use when:
- Component is simple (1-3 elements)
- Component returns HTML string
- Component has fixed structure with parameters

**Class Component** - Use when:
- Component has complex logic
- Component maintains state
- Component needs multiple methods

#### 2. File Naming Conventions

```
Include Components: snake_case.php
  ✅ page_header.php
  ✅ child_card.php
  ✅ filter_form.php

Helper Functions: camelCase
  ✅ renderButton()
  ✅ formatAge()
  ✅ getPhotoUrl()

Classes: PascalCase with CFK_ prefix
  ✅ CFK_Avatar_Manager
  ✅ CFK_Email_Manager
  ✅ CFK_Database
```

#### 3. Required Component Elements

Every component must include:

1. **Security Check**
   ```php
   if (!defined('CFK_APP')) {
       http_response_code(403);
       die('Direct access not permitted');
   }
   ```

2. **Documentation Header**
   ```php
   /**
    * Component Name
    * Brief description
    *
    * @param type $param - Description
    */
   ```

3. **Parameter Validation**
   ```php
   if (!isset($requiredParam)) {
       trigger_error('Component requires $requiredParam', E_USER_WARNING);
       return;
   }
   ```

4. **Default Values**
   ```php
   $optionalParam = $optionalParam ?? 'default_value';
   ```

5. **Sanitization**
   ```php
   echo sanitizeString($userInput);
   ```

#### 4. Component Template

```php
<?php
/**
 * Component Name
 * Detailed description of what this component does
 *
 * Required variables:
 * - $requiredParam: Description (type)
 *
 * Optional variables:
 * - $optionalParam: Description (type, default: value)
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Validate required parameters
if (!isset($requiredParam)) {
    trigger_error('Component requires $requiredParam', E_USER_WARNING);
    return;
}

// Set defaults
$optionalParam = $optionalParam ?? 'default';
?>

<div class="component-wrapper">
    <!-- Component HTML here -->
    <h2><?php echo sanitizeString($requiredParam); ?></h2>
    <?php if ($optionalParam): ?>
        <p><?php echo sanitizeString($optionalParam); ?></p>
    <?php endif; ?>
</div>

<style>
/* Component-specific styles */
.component-wrapper {
    /* Styles here */
}
</style>
```

### Testing Components

#### Manual Testing Checklist

- [ ] Component renders without errors
- [ ] All required parameters validated
- [ ] Default values work correctly
- [ ] Sanitization prevents XSS
- [ ] Responsive on mobile devices
- [ ] Accessible via keyboard
- [ ] Proper semantic HTML
- [ ] CSS doesn't leak to other elements
- [ ] Works in all supported browsers
- [ ] Performance acceptable with many instances

#### Test Cases to Create

1. **Minimal Usage** - Test with only required parameters
2. **Full Options** - Test with all options set
3. **Edge Cases** - Test with empty strings, null values
4. **Invalid Data** - Test with wrong types, missing data
5. **Security** - Test with malicious input (XSS attempts)

#### Example Test File

```php
// tests/component_name_test.php
<?php
require_once __DIR__ . '/../config.php';

// Test 1: Minimal usage
$requiredParam = 'Test Value';
require __DIR__ . '/../includes/components/component_name.php';

// Test 2: With options
$requiredParam = 'Test Value';
$optionalParam = 'Optional Value';
require __DIR__ . '/../includes/components/component_name.php';

// Test 3: XSS prevention
$requiredParam = '<script>alert("XSS")</script>';
require __DIR__ . '/../includes/components/component_name.php';
// Should output: &lt;script&gt;alert("XSS")&lt;/script&gt;
?>
```

### Documentation Requirements

Every component must have documentation including:

1. **Description** - What the component does
2. **Location** - File path
3. **Parameters** - All parameters with types and defaults
4. **Usage Examples** - At least 3 examples
5. **Security Notes** - Sanitization and validation details
6. **Best Practices** - Recommended usage patterns
7. **Troubleshooting** - Common issues and solutions

### Code Review Checklist

Before merging new components:

- [ ] Follows naming conventions
- [ ] Includes security checks
- [ ] Has parameter validation
- [ ] Uses proper sanitization
- [ ] Documented in COMPONENTS.md
- [ ] Has usage examples
- [ ] Tested on multiple pages
- [ ] Mobile responsive
- [ ] Accessible (WCAG AA)
- [ ] No duplicate CSS
- [ ] Performance optimized
- [ ] Follows existing patterns

---

## Examples Directory

### Live Examples in Codebase

#### Page Header Component

**File**: `pages/about.php` (line 21)
```php
$title = 'About Christmas for Kids';
$description = 'Our mission is to ensure every child experiences the magic of Christmas.';
require_once __DIR__ . '/../includes/components/page_header.php';
```

**File**: `pages/children.php` (line 70)
```php
$title = 'Children Needing Christmas Sponsorship';
$description = 'Browse the children below and select someone to sponsor.';
$additionalContent = $resultsHtml; // Dynamic content
require_once __DIR__ . '/../includes/components/page_header.php';
```

**File**: `pages/sponsor_portal.php` (line 100)
```php
$title = 'Sponsor Portal';
require_once __DIR__ . '/../includes/components/page_header.php';
```

#### Child Card Component

**File**: `pages/children.php` (line 152)
```php
foreach ($children as $child) {
    $siblings = $siblingsByFamily[$child['family_id']] ?? [];
    $filteredSiblings = array_filter($siblings, fn($s) => $s['id'] !== $child['id']);

    $options = [
        'show_siblings' => !empty($filteredSiblings),
        'siblings' => $filteredSiblings
    ];

    include __DIR__ . '/../includes/components/child_card.php';
}
```

**File**: `pages/home.php` (line 114)
```php
foreach ($featuredChildren as $child) {
    $options = ['show_wishes' => true];
    include __DIR__ . '/../includes/components/child_card.php';
}
```

#### Button System

**File**: `pages/home.php` (hero section)
```php
echo renderButton(
    'View Children Needing Sponsorship',
    baseUrl('?page=children'),
    'primary',
    ['size' => 'large']
);

echo renderButton(
    'Make a Donation',
    baseUrl('?page=donate'),
    'success',
    ['size' => 'large']
);
```

**File**: `pages/child.php` (sponsor form)
```php
echo renderButton(
    'Submit Sponsorship Request',
    null,
    'primary',
    [
        'size' => 'large',
        'submit' => true,
        'id' => 'sponsorshipSubmit'
    ]
);
```

**File**: `pages/donate.php` (Zeffy button)
```php
echo renderButton(
    'Make a General Donation',
    null,
    'outline',
    [
        'size' => 'large',
        'id' => 'final-donate-btn',
        'attributes' => [
            'zeffy-form-link' => 'https://www.zeffy.com/embed/...'
        ]
    ]
);
```

#### Avatar System

**File**: `includes/functions.php` (line 355)
```php
function getPhotoUrl(string $filename = null, array $child = null): string {
    if ($child && isset($child['age']) && isset($child['gender'])) {
        require_once __DIR__ . '/avatar_manager.php';
        return CFK_Avatar_Manager::getAvatarForChild($child);
    }
    return baseUrl('assets/images/b-4girlsm.png');
}
```

**File**: `includes/components/child_card.php` (line 46)
```php
<img src="<?php echo getPhotoUrl($child['photo_filename'], $child); ?>"
     alt="Avatar for <?php echo sanitizeString($child['name']); ?>"
     loading="lazy">
```

### Example Pattern Catalog

#### Pattern 1: Simple Page with Header
```php
<?php
$title = 'Page Title';
$description = 'Page description';
require_once __DIR__ . '/../includes/components/page_header.php';
?>

<div class="page-content">
    <!-- Page content here -->
</div>
```

#### Pattern 2: Grid of Children
```php
<?php
$children = getChildren($filters, $page);
$siblingsByFamily = eagerLoadFamilyMembers($children);
?>

<div class="children-grid">
    <?php foreach ($children as $child): ?>
        <?php
        $siblings = $siblingsByFamily[$child['family_id']] ?? [];
        $options = ['show_siblings' => !empty($siblings), 'siblings' => $siblings];
        include __DIR__ . '/../includes/components/child_card.php';
        ?>
    <?php endforeach; ?>
</div>
```

#### Pattern 3: Form with Buttons
```php
<form method="POST" action="<?php echo baseUrl(); ?>">
    <!-- Form fields -->

    <div class="form-actions">
        <?php echo renderButton('Submit', null, 'primary', ['submit' => true]); ?>
        <?php echo renderButton('Cancel', baseUrl('?page=back'), 'secondary'); ?>
    </div>
</form>
```

---

## Component Checklist

Use this checklist when creating or reviewing components:

### Security & Validation
- [ ] Direct access protection (`CFK_APP` check)
- [ ] Required parameters validated
- [ ] All user input sanitized
- [ ] SQL injection prevention (if applicable)
- [ ] XSS prevention (htmlspecialchars/sanitizeString)
- [ ] CSRF protection (if form component)

### Code Quality
- [ ] Follows naming conventions
- [ ] Proper indentation and formatting
- [ ] No duplicate code
- [ ] No hard-coded values (use config)
- [ ] DRY principle followed
- [ ] Comments explain complex logic

### Documentation
- [ ] File header with description
- [ ] Parameter documentation
- [ ] Usage examples in COMPONENTS.md
- [ ] Inline comments for complex code
- [ ] Best practices documented
- [ ] Troubleshooting section

### Accessibility
- [ ] Semantic HTML elements
- [ ] Proper heading hierarchy
- [ ] Alt text for images
- [ ] ARIA labels where needed
- [ ] Keyboard navigation support
- [ ] Focus indicators visible
- [ ] Color contrast meets WCAG AA

### Performance
- [ ] No N+1 query issues
- [ ] Images lazy loaded
- [ ] CSS scoped to component
- [ ] No unnecessary database queries
- [ ] Efficient loops and logic
- [ ] Proper caching (if applicable)

### Responsive Design
- [ ] Mobile-first CSS
- [ ] Breakpoints for tablet/mobile
- [ ] Touch-friendly (buttons 44px min)
- [ ] Readable text sizes
- [ ] No horizontal scroll
- [ ] Images scale properly

### Testing
- [ ] Tested with minimum parameters
- [ ] Tested with all options
- [ ] Tested with invalid data
- [ ] Tested on mobile devices
- [ ] Tested in all browsers
- [ ] XSS attack prevention tested
- [ ] Performance tested with many instances

### Browser Compatibility
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Android

---

## Troubleshooting

### Common Issues Across All Components

#### Issue: Component not rendering
**Symptoms:** Blank space where component should be
**Causes:**
1. PHP error preventing render
2. Missing required variables
3. File path incorrect

**Solutions:**
```php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check file path
var_dump(__DIR__ . '/../includes/components/component.php');

// Verify variables set
var_dump($requiredVariable);
```

#### Issue: Styles not applying
**Symptoms:** Component renders but looks unstyled
**Causes:**
1. CSS not loaded
2. CSS specificity issue
3. Class name typo

**Solutions:**
```php
// Verify class names
<div class="child-card"> <!-- Correct -->
<div class="childcard">  <!-- Wrong -->

// Check CSS file loaded
<link rel="stylesheet" href="<?php echo baseUrl('assets/css/styles.css'); ?>">

// Increase specificity
.page-header h1 { } /* Instead of just h1 { } */
```

#### Issue: XSS vulnerability
**Symptoms:** Scripts executing from user input
**Causes:**
1. Missing sanitization
2. Using wrong output function

**Solutions:**
```php
// ✅ CORRECT
echo sanitizeString($userInput);
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ❌ WRONG
echo $userInput; // Dangerous!
```

#### Issue: Variables not passing to component
**Symptoms:** Component shows empty/default values
**Causes:**
1. Variables set after include
2. Variable scope issue
3. Typo in variable name

**Solutions:**
```php
// ✅ CORRECT - Set before include
$title = 'My Title';
include 'component.php';

// ❌ WRONG - Set after include
include 'component.php';
$title = 'My Title'; // Too late!

// Check variable names match exactly
$pageTitle vs $pagetitle // Different!
```

#### Issue: Component breaks on mobile
**Symptoms:** Layout broken on small screens
**Causes:**
1. Fixed widths instead of responsive
2. Missing media queries
3. Text too large

**Solutions:**
```css
/* ✅ CORRECT - Responsive */
.component {
    width: 100%;
    max-width: 600px;
    font-size: 1rem;
}

@media (max-width: 768px) {
    .component {
        padding: 1rem;
        font-size: 0.9rem;
    }
}

/* ❌ WRONG - Fixed */
.component {
    width: 600px; /* Breaks on mobile */
}
```

### Performance Issues

#### Issue: Page slow with many components
**Symptoms:** Long page load time, high database queries
**Causes:**
1. N+1 query problem
2. Not using eager loading
3. Too many database calls in loop

**Solutions:**
```php
// ✅ CORRECT - Eager load
$children = getChildren();
$siblingsByFamily = eagerLoadFamilyMembers($children); // One query

foreach ($children as $child) {
    $siblings = $siblingsByFamily[$child['family_id']] ?? [];
    // Use siblings
}

// ❌ WRONG - N+1 queries
foreach ($children as $child) {
    $siblings = getFamilyMembers($child['family_id']); // Query per child!
}
```

### Debug Mode

Enable debug mode to see detailed error messages:

```php
// In config.php
define('DEBUG_MODE', true);

// Shows:
// - PHP errors
// - SQL queries
// - Component warnings
// - Performance metrics
```

### Getting Help

1. **Check this documentation first**
2. **Review component source code**
3. **Check browser console for errors**
4. **Enable PHP error reporting**
5. **Test in isolation** - Create minimal test page
6. **Contact development team**

---

## Summary

This documentation covers **4 main components**:

1. **Page Header Component** - Consistent page titles with gradient
2. **Child Card Component** - Reusable child profile cards
3. **Button System** - Standardized button rendering
4. **Avatar System** - Privacy-preserving child avatars

### Key Takeaways

- **Security First**: All components auto-sanitize inputs
- **Consistency**: Reuse components instead of duplicating HTML
- **Performance**: Eager load data to prevent N+1 queries
- **Accessibility**: All components support WCAG 2.1 AA
- **Documentation**: Always document new components

### Quick Reference Card

```php
// Page Header
$title = 'Title'; $description = 'Desc';
require_once 'includes/components/page_header.php';

// Child Card
$options = ['show_wishes' => true];
include 'includes/components/child_card.php';

// Button
echo renderButton('Text', 'url', 'primary', ['size' => 'large']);

// Avatar
$url = CFK_Avatar_Manager::getAvatarForChild($child);
// Or use helper:
$url = getPhotoUrl(null, $child);
```

---

**For More Information:**
- Button System: `/docs/BUTTON_SYSTEM.md`
- Refactoring Guide: `/docs/REFACTORING_AUDIT.md`
- Main Documentation: `/docs/README.md`

**Last Updated**: 2025-10-08
**Version**: 1.0
**Component Count**: 4 major components documented
