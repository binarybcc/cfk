# Christmas for Kids - Pages & Shortcodes Documentation

This document explains how to create pages and use shortcodes with the Christmas for Kids (CFK) plugin system.

## Overview

The CFK plugin provides two approaches for creating pages:

1. **Current System (v1.0.3+)**: Modern `[cfk_children]` shortcode with family-aware features
2. **Legacy System**: Multiple specialized shortcodes for different functions

## Current System: `[cfk_children]` Shortcode

The main shortcode for displaying available children with advanced family-aware functionality.

### Basic Usage

```wordpress
[cfk_children]
```

### Full Parameter Example

```wordpress
[cfk_children 
    columns="3" 
    per_page="12" 
    show_filters="true" 
    show_search="true" 
    order="random" 
    age_min="0" 
    age_max="18" 
    gender="" 
    class="cfk-children-grid"
    family_grouping="false"
    show_siblings="true" 
    family_view="individual" 
    family_search="true" 
    show_family_stats="true"
]
```

### Parameters

#### Display Parameters
- **`columns`** (default: `3`) - Number of columns in grid (1-6)
- **`per_page`** (default: `12`) - Children per page (1-50)
- **`class`** (default: `cfk-children-grid`) - CSS class for wrapper

#### Filter & Search Parameters
- **`show_filters`** (default: `true`) - Show age/gender/sort filters
- **`show_search`** (default: `true`) - Show name/interests search
- **`order`** (default: `random`) - Sort order: `random`, `age_asc`, `age_desc`, `name`

#### Age & Gender Filters
- **`age_min`** (default: `0`) - Minimum age filter
- **`age_max`** (default: `18`) - Maximum age filter  
- **`gender`** (default: `""`) - Gender filter: `M`, `F`, or empty for all

#### Family-Aware Features (NEW in v1.2+)
- **`family_grouping`** (default: `false`) - Group children by families
- **`show_siblings`** (default: `true`) - Show sibling information on child cards
- **`family_view`** (default: `individual`) - Display mode: `individual`, `grouped`, or `both`
- **`family_search`** (default: `true`) - Enable family ID/name search
- **`show_family_stats`** (default: `true`) - Display family statistics

## Creating Pages

### 1. Standard Children Listing Page

**Page Title:** "Available Children" or "Sponsor a Child"

**Content:**
```wordpress
<h2>Children Available for Sponsorship</h2>
<p>Browse our wonderful children who are looking for sponsors this Christmas season.</p>

[cfk_children columns="3" per_page="12" show_filters="true" show_search="true"]
```

### 2. Family-Focused Page

**Page Title:** "Sponsor Families"

**Content:**
```wordpress
<h2>Sponsor Entire Families</h2>
<p>Help keep families together by sponsoring multiple children from the same family.</p>

[cfk_children 
    family_grouping="true" 
    family_view="grouped" 
    show_siblings="true" 
    show_family_stats="true"
    columns="2"
    per_page="8"
]
```

### 3. Homepage Integration

**Content:**
```wordpress
<h2>Help Make Christmas Special</h2>
<p>Every child deserves a magical Christmas. Browse available children below.</p>

[cfk_children 
    columns="4" 
    per_page="8" 
    show_filters="false" 
    show_search="true"
    order="random"
]

<p><a href="/sponsor-a-child/" class="button">View All Available Children</a></p>
```

### 4. Filtered Views

#### Boys Only Page
```wordpress
[cfk_children gender="M" show_filters="false"]
```

#### Younger Children Page
```wordpress
[cfk_children age_max="10" show_filters="true"]
```

#### Simple Grid (No Filters)
```wordpress
[cfk_children columns="4" per_page="16" show_filters="false" show_search="false"]
```

## Legacy Shortcodes (Pre-v1.0.3)

These shortcodes are available in older plugin versions:

### Core Shortcodes
- **`[cfk_children_grid]`** - Basic children display grid
- **`[cfk_sponsorship_cart]`** - Shopping cart for sponsorships
- **`[cfk_sponsorship_form]`** - Sponsor information form
- **`[cfk_thank_you_page]`** - Post-sponsorship thank you
- **`[cfk_sponsorship_status]`** - Show if sponsorships are open/closed
- **`[cfk_family_grid]`** - Family-grouped children display

### Legacy Parameters (cfk_children_grid)
- `age_range` - Age range filter
- `gender` - Gender filter
- `per_page` - Children per page
- `show_filters` - Show filter controls
- `columns` - Grid columns

## Page Setup Best Practices

### 1. WordPress Page Creation
1. Go to **Pages > Add New** in WordPress admin
2. Enter page title and content with shortcode
3. Set appropriate page template if needed
4. Publish the page

### 2. Menu Integration
1. Go to **Appearance > Menus**
2. Add your CFK pages to navigation
3. Organize in logical order (e.g., "Sponsor a Child" → "View Families")

### 3. SEO Considerations
- Use descriptive page titles
- Add meta descriptions
- Include relevant keywords about child sponsorship
- Ensure pages load quickly

### 4. Mobile Responsiveness
The CFK shortcodes are mobile-responsive, but test on various devices:
- Use `columns="2"` or `columns="1"` for mobile-friendly layouts
- Consider `per_page="6"` for faster mobile loading

## Styling & Customization

### CSS Classes
The shortcode generates these CSS classes for styling:

```css
.cfk-children-wrapper { /* Main container */ }
.cfk-children-filters { /* Filter controls */ }
.cfk-children-grid { /* Children grid container */ }
.cfk-child-card { /* Individual child card */ }
.cfk-family-group { /* Family group container */ }
.cfk-family-stats { /* Statistics display */ }
```

### Custom CSS Example
```css
/* Customize child cards */
.cfk-child-card {
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.cfk-child-card:hover {
    transform: translateY(-5px);
}

/* Style sponsor buttons */
.cfk-sponsor-btn {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
}
```

## JavaScript Integration

The shortcode automatically enqueues necessary JavaScript for:
- AJAX child sponsorship
- Filter interactions  
- Family search functionality
- Modal dialogs

### Custom JavaScript Hooks
```javascript
// Listen for child selection events
document.addEventListener('cfk_child_selected', function(e) {
    console.log('Child selected:', e.detail.childId);
});

// Listen for family sponsorship events
document.addEventListener('cfk_family_selected', function(e) {
    console.log('Family selected:', e.detail.familyNumber);
});
```

## Security Notes

All shortcodes include:
- WordPress nonce verification
- Input sanitization  
- Capability checks
- SQL injection protection

## Troubleshooting

### Common Issues

1. **Shortcode displays as text**
   - Check plugin is activated
   - Verify shortcode spelling

2. **No children showing**
   - Check if children are published
   - Verify availability status
   - Check filter parameters

3. **Styling issues**  
   - Clear cache
   - Check CSS conflicts
   - Verify theme compatibility

4. **AJAX errors**
   - Check browser console for errors
   - Verify WordPress AJAX setup
   - Check nonce configuration

### Debug Mode
Add this to wp-config.php for debugging:
```php
define('CFK_DEBUG', true);
```

## Migration Notes

### Upgrading from Legacy Shortcodes

To migrate from legacy shortcodes to the new `[cfk_children]` system:

1. **Replace `[cfk_children_grid]`** with `[cfk_children]`
2. **Update parameters:**
   - `age_range` → use `age_min` and `age_max`
   - Other parameters largely compatible
3. **Test family features** with new parameters
4. **Update custom CSS** if needed

### Backward Compatibility
The current plugin maintains backward compatibility with legacy shortcodes where possible.

## Support

For additional help:
- Check WordPress admin under "Christmas for Kids"
- Review plugin documentation in `/christmas-for-kids/` folder
- Enable debug mode for detailed error messages
- Check WordPress error logs

---

**Last Updated:** September 2025  
**Plugin Version:** v1.0.3+  
**WordPress Compatibility:** 5.0+