# Zeffy Donation Modal Feature

## Overview

An informational modal that appears before users access the Zeffy donation form, educating them about how Zeffy works and emphasizing that tips are optional.

## Purpose

**Problem**: Zeffy suggests tip amounts that may be higher than donors expect, and many donors don't realize these tips are completely optional.

**Solution**: Before opening the Zeffy donation form, show an educational modal that:
- Explains that 100% of donations go to Christmas for Kids
- Clarifies that Zeffy tips are completely optional
- Emphasizes that donors can change or decline the suggested tip amount
- Provides transparency about the donation process

## User Experience Flow

1. **User clicks "Donate" button** anywhere on the site
2. **Modal appears** with donation information
3. **User reads** about Zeffy and optional tips
4. **User clicks "Continue to Donate Now"** → Opens Zeffy form
5. **OR User clicks "Cancel"** → Closes modal, no form opens

## Technical Implementation

### Files Modified

1. **`assets/js/main.js`**
   - Added `showZeffyInfoModal()` function
   - Added `closeZeffyInfoModal()` function
   - Added `openZeffyDonationForm()` function
   - Modified `initializeZeffyButtons()` to intercept clicks

2. **`assets/css/styles.css`**
   - Added complete modal styling (`.zeffy-info-modal` and child classes)
   - Responsive design for mobile
   - Accessibility features (focus states, reduced motion support)

3. **HTML Files Updated**:
   - `includes/header.php` - Top nav Donate button
   - `pages/home.php` - Hero section Donate button
   - `includes/footer.php` - Footer Donate button

### How It Works

```javascript
// 1. Intercepts clicks on buttons with zeffy-form-link attribute
button.addEventListener('click', function(e) {
    e.preventDefault();
    showZeffyInfoModal(zeffyLink);
});

// 2. Shows custom modal with information
showZeffyInfoModal(link);

// 3. When user clicks "Continue to Donate Now"
openZeffyDonationForm(link);
    // Closes our modal
    // Creates temporary button with zeffy-form-link
    // Lets Zeffy's script handle the rest
```

## Modal Content

### Key Messages

1. **100% Goes to Charity**
   - "100% of Your Donation Goes to Christmas for Kids"
   - Explains Zeffy is free for nonprofits

2. **Tips Are Optional** (highlighted)
   - Warning icon and yellow background for emphasis
   - Clear bullet points:
     - Tips are completely optional - you can choose $0
     - Zeffy may suggest a tip amount - you can change it
     - Your donation is separate from any tip
     - Declining the tip doesn't affect your donation

3. **Warm Closing**
   - "Every dollar makes a difference in a child's Christmas!"

## Accessibility Features

### WCAG 2.1 Compliance

- **Keyboard Navigation**: Full keyboard support (Tab, Escape)
- **Screen Readers**: Proper ARIA attributes (`role="dialog"`, `aria-modal="true"`, `aria-labelledby`)
- **Focus Management**: Automatic focus to primary button when modal opens
- **Color Contrast**: High contrast text for readability
- **Reduced Motion**: Respects `prefers-reduced-motion` for users with motion sensitivity

### Keyboard Controls

- **Escape**: Closes modal
- **Tab**: Navigate between Cancel and Donate buttons
- **Enter/Space**: Activate focused button
- **Click outside**: Closes modal (on overlay)

## Styling Details

### Color Scheme

- **Primary Green**: `#2c5f2d` (brand color)
- **Warning Yellow**: `#fff3cd` with `#ffc107` border (important notice)
- **Success Green**: `#e7f5e8` (closing note background)

### Responsive Design

- **Desktop**: 600px wide modal, centered
- **Mobile**: 95% width, stacked buttons
- **Max Height**: 90vh with scroll if needed

## Testing Checklist

- [ ] Modal appears when clicking any Donate button
- [ ] Modal closes on Cancel button
- [ ] Modal closes on X button (top right)
- [ ] Modal closes when clicking outside (overlay)
- [ ] Modal closes on Escape key
- [ ] "Continue to Donate Now" opens Zeffy form
- [ ] Only ONE Zeffy form opens (no duplicates)
- [ ] Mobile responsive (buttons stack vertically)
- [ ] Keyboard navigation works
- [ ] Screen reader announces modal properly
- [ ] Focus trap keeps users in modal until closed

## Future Enhancements

### Potential Improvements

1. **Session Storage**: Remember if user has seen modal this session (optional - may reduce effectiveness)
2. **A/B Testing**: Track conversion rates with/without modal
3. **Translation**: Multi-language support
4. **Video**: Short video explanation option
5. **Analytics**: Track how many users continue vs. cancel

### Configuration Options

Could add these to `config.php` if needed:
```php
'zeffy_modal_enabled' => true,
'zeffy_modal_show_once_per_session' => false,
'zeffy_form_url' => 'https://www.zeffy.com/embed/donation-form/...'
```

## Browser Compatibility

- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Mobile browsers (iOS Safari, Chrome Android)

## Notes

- Modal is **always shown** before Zeffy form (no "don't show again" option)
- This is intentional to maximize donor education
- Content is hardcoded but can be easily modified in `main.js`
- No external dependencies (pure JavaScript and CSS)

## Support

For issues or modifications, contact the development team or refer to:
- JavaScript: `assets/js/main.js` lines 20-150
- CSS: `assets/css/styles.css` (Zeffy Information Modal section)
- HTML: Search for `zeffy-form-link` attribute in template files

---

**Last Updated**: 2025-10-07  
**Version**: 1.0  
**Status**: Production Ready
