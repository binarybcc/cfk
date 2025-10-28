# Temporary Landing Page - Feature Documentation

**Feature:** Pre-Launch Landing Page with Countdown Timer
**Status:** ✅ Implemented and Tested
**Active:** Until November 1, 2025 at 12:01 AM ET
**Version:** v1.7.3+

---

## Overview

A temporary landing page that displays before the official launch of child sponsorships on November 1, 2025 at 12:01 AM Eastern Time. The page includes:

- Live countdown timer to launch date
- Zeffy donation form (embedded iframe)
- Information about applying for assistance
- PDF download links for application forms

The page automatically switches to the normal home page when the countdown reaches zero.

---

## User Experience

### What Visitors See (Before Nov 1, 2025)

**Hero Section:**
- 🎄 Christmas tree emoji animation
- "Christmas for Kids 2025" heading
- Live countdown showing Days, Hours, Minutes, Seconds
- Launch date/time: "Saturday, November 1, 2025 at 12:01 AM ET"

**Three Main Sections:**

1. **💝 Monetary Donations Are Always Welcome**
   - Explains donations accepted now
   - "Donate Now" button scrolls to donation form
   - 100% goes to families message

2. **🎁 Child Sponsorships Begin November 1st**
   - Explains when sponsorships open
   - "How to Apply" button scrolls to application info
   - Target audience: families in need

3. **📋 How To Apply For Assistance**
   - Important dates (Oct 28 - Nov 25)
   - Application hours (Tues/Thurs/Fri)
   - Location details (Seneca Industrial Complex)
   - What to bring (DSS profile or proof of income)
   - Eligibility requirements
   - PDF download cards (Application + Wish Lists)

**Zeffy Donation Form:**
- Embedded iframe at bottom of page
- Full donation form from Zeffy
- Secured by CSP headers

---

## Technical Implementation

### File Structure

```
cfk-standalone/
├── index.php                     # Modified: Routing logic
├── pages/
│   └── temp_landing.php         # New: Landing page content
└── includes/
    ├── header_temp.php          # New: Simplified header
    └── footer_temp.php          # New: Simplified footer
```

### Routing Logic (index.php)

**Smart Date-Based Routing:**

```php
// Check if we should show temporary landing page
$showTempLanding = false;
$previewMode = $_GET['preview'] ?? null;

if ($page === 'home') {
    if ($previewMode === 'temp') {
        // Force show temporary landing page
        $showTempLanding = true;
    } elseif ($previewMode === 'normal') {
        // Force show normal home page
        $showTempLanding = false;
    } else {
        // Automatic mode - check current date/time
        $launchTime = new DateTime('2025-11-01 00:01:00', new DateTimeZone('America/New_York'));
        $now = new DateTime('now', new DateTimeZone('America/New_York'));
        $showTempLanding = ($now < $launchTime);
    }
}
```

**Key Features:**
- Only affects home page (`?page=home` or `/`)
- Other pages (children, admin, etc.) work normally
- Timezone-aware (Eastern Time)
- Supports preview modes for testing

---

## Preview Modes

### For Testing/Development

**Force Temporary Landing Page:**
```
http://localhost:8082/?preview=temp
https://cforkids.org/?preview=temp
```

**Force Normal Home Page:**
```
http://localhost:8082/?preview=normal
https://cforkids.org/?preview=normal
```

**Automatic Mode (Production):**
```
http://localhost:8082/
https://cforkids.org/
```

Uses current date/time to determine which page to show.

---

## Countdown Timer

### Features

- **Live Updates:** JavaScript updates every second
- **Accurate Calculation:** Uses client-side timezone conversion
- **Auto-Reload:** When countdown reaches zero, page refreshes to show normal home
- **Server + Client Sync:** Initial values from PHP, updates via JavaScript

### Implementation

**Server-Side (PHP):**
```php
$launchTime = new DateTime('2025-11-01 00:01:00', new DateTimeZone('America/New_York'));
$now = new DateTime('now', new DateTimeZone('America/New_York'));
$interval = $now->diff($launchTime);
```

**Client-Side (JavaScript):**
```javascript
const launchDate = new Date('2025-11-01T00:01:00-05:00');

function updateCountdown() {
    const now = new Date();
    const diff = launchDate - now;

    if (diff <= 0) {
        // Launch time reached - reload to show normal homepage
        window.location.reload();
        return;
    }

    // Calculate and update display...
}

setInterval(updateCountdown, 1000);
```

---

## Zeffy Integration

### Embedded Donation Form

**iframe Source:**
```html
<iframe
    src="https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids"
    style="width: 100%; height: 900px; border: none;"
    sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox"
    allow="payment">
</iframe>
```

### Content Security Policy (CSP)

Special CSP headers in `header_temp.php` to allow Zeffy while maintaining security:

```php
$csp = implode('; ', [
    "default-src 'self'",
    "script-src 'self' 'nonce-{$cspNonce}' https://zeffy-scripts.s3.ca-central-1.amazonaws.com/",
    "frame-src https://www.zeffy.com",
    "connect-src 'self' https://www.zeffy.com https://*.zeffy.com",
    "form-action 'self' https://www.zeffy.com https://*.zeffy.com",
    // ... other directives
]);
```

---

## PDF Downloads

### Application Forms

**Files:**
- `assets/downloads/cfk-application-2025.pdf` (205 KB)
- `assets/downloads/cfk-family-wish-lists-2025.pdf` (136 KB)

**Links:**
```php
<a href="<?php echo baseUrl('assets/downloads/cfk-application-2025.pdf'); ?>"
   class="download-btn" download>
    ⬇️ Download Application PDF
</a>
```

**Feature:** Direct download with `download` attribute

---

## Styling & Design

### Visual Hierarchy

**Color Scheme:**
- Primary Green: `#2c5530` (Christmas for Kids brand)
- Accent Red: `#c41e3a` (Donate button)
- Background: White with green accents
- Gradients: Green gradient in hero section

**Typography:**
- Headings: Large, bold, green
- Body: Readable, line-height 1.6
- CTA Buttons: 1.2rem, bold, prominent

### Animations

**Floating Christmas Tree:**
```css
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}
```

**Fade-In Sections:**
```css
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

**Staggered Animation:**
- Section 1: No delay
- Section 2: 0.1s delay
- Section 3: 0.2s delay

### Responsive Design

**Breakpoints:**
- Desktop: 1200px+ (full layout)
- Tablet: 768px - 1199px (2-column grid)
- Mobile: < 768px (single column, reduced fonts)

**Mobile Adjustments:**
- Countdown units: Smaller padding
- Font sizes: Reduced by ~20%
- Grid: Single column layout
- Navigation: Compact spacing

---

## Navigation

### Header (Temporary)

**Simplified Navigation:**
- Logo (left)
- "How to Apply" link (scrolls to section)
- "Admin" link (admin panel)

**No Browse Children Link:**
Since sponsorships aren't open yet, the "Browse Children" link is removed.

### Footer (Temporary)

**Content:**
- Logo and mission statement
- "How It Works" bullet points
- "Coming November 1st" message
- Contact information
- Version number

---

## Smooth Scrolling

### Donate Button

**JavaScript Function:**
```javascript
function smoothScrollTo(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}
```

**Usage:**
```html
<button class="cta-button" onclick="smoothScrollTo('donate-form')">
    Donate Now
</button>
```

**Scroll Margins:**
```css
.how-to-apply-section,
.zeffy-section {
    scroll-margin-top: 80px; /* Account for sticky header */
}
```

---

## Accessibility

### WCAG Compliance

**Features:**
- Skip to main content link
- ARIA live regions for announcements
- Alt text on all images
- Proper heading hierarchy (H1 → H2 → H3)
- Color contrast ratios meet WCAG AA
- Keyboard navigation support
- Focus indicators on interactive elements

**Screen Reader Support:**
- Semantic HTML structure
- Descriptive link text
- Image fallbacks with text
- Form labels properly associated

---

## Testing Checklist

### Visual Testing

- [x] Countdown displays correctly
- [x] All sections visible and styled
- [x] Zeffy iframe loads
- [x] PDF download links work
- [x] Responsive on mobile (< 768px)
- [x] Responsive on tablet (768px - 1199px)
- [x] Responsive on desktop (1200px+)

### Functional Testing

- [x] Countdown updates every second
- [x] Countdown shows correct time remaining
- [x] Auto-reload at countdown zero
- [x] Preview mode `?preview=temp` works
- [x] Preview mode `?preview=normal` works
- [x] Automatic date-based routing works
- [x] Smooth scroll to donate form
- [x] Smooth scroll to "How to Apply"
- [x] Other pages still accessible (admin, etc.)

### Security Testing

- [x] CSP headers allow Zeffy iframe
- [x] CSP blocks unauthorized scripts
- [x] XSS protection headers present
- [x] iframe sandbox attributes correct
- [x] No hardcoded secrets in code
- [x] Nonces used for inline scripts/styles

---

## Deployment

### Local/Docker Testing

```bash
🐳 DOCKER:
# Start containers
docker-compose up -d

# Access temporary landing page
http://localhost:8082/?preview=temp

# Access normal home page
http://localhost:8082/?preview=normal

# Test automatic routing
http://localhost:8082/
```

### Production Deployment

```bash
🌐 PRODUCTION:
# Deploy modified index.php
scp -P 22 index.php \
  a4409d26_1@d646a74eb9.nxcli.io:/home/a4409d26/d646a74eb9.nxcli.io/html/

# Deploy new pages
scp -P 22 pages/temp_landing.php \
  a4409d26_1@d646a74eb9.nxcli.io:/home/a4409d26/d646a74eb9.nxcli.io/html/pages/

# Deploy new includes
scp -P 22 includes/header_temp.php includes/footer_temp.php \
  a4409d26_1@d646a74eb9.nxcli.io:/home/a4409d26/d646a74eb9.nxcli.io/html/includes/
```

### Verification After Deployment

1. **Test automatic mode:** Visit `https://cforkids.org/` (should show temp landing)
2. **Test preview=temp:** Visit `https://cforkids.org/?preview=temp`
3. **Test preview=normal:** Visit `https://cforkids.org/?preview=normal`
4. **Test countdown:** Verify time displays correctly
5. **Test Zeffy iframe:** Verify donation form loads
6. **Test PDF downloads:** Click both download buttons
7. **Test admin access:** Visit `https://cforkids.org/admin/`

---

## Automatic Transition

### What Happens on Nov 1, 2025 at 00:01 AM ET

**Without Manual Intervention:**

1. **User is on temp landing page:**
   - JavaScript countdown reaches zero
   - Page auto-reloads
   - Normal home page displays (sponsorships available)

2. **User visits site fresh:**
   - PHP routing checks current date/time
   - `$now >= $launchTime` evaluates to `true`
   - `$showTempLanding = false`
   - Normal home page displays

**No Code Changes Needed!**

The transition is fully automatic based on date/time comparison.

---

## Cleanup (After Nov 1, 2025)

### Optional File Removal

Once sponsorships are open and stable for a few days, these files can be removed:

**Files to Remove:**
- `pages/temp_landing.php`
- `includes/header_temp.php`
- `includes/footer_temp.php`

**Code to Clean Up:**
- Routing logic in `index.php` (lines ~39-65)
- Can simplify back to original header/footer includes

**When to Clean Up:**
- After Nov 5, 2025 (few days past launch)
- After verifying normal home page is stable
- Before starting next major feature

**Benefits of Cleaning Up:**
- Reduced file count
- Simpler codebase
- Faster routing logic
- Less maintenance

---

## Troubleshooting

### Issue: Temp landing page not showing

**Check:**
1. Current date/time (must be before Nov 1, 2025 00:01 ET)
2. Preview mode parameter (`?preview=temp`)
3. Page parameter (must be `home` or not set)

**Solution:**
- Use `?preview=temp` to force display
- Check server timezone configuration
- Verify `index.php` routing logic

### Issue: Zeffy iframe not loading

**Check:**
1. CSP headers in `header_temp.php`
2. Browser console for CSP violations
3. Network tab shows iframe request

**Solution:**
- Verify CSP `frame-src` includes `https://www.zeffy.com`
- Check `script-src` includes Zeffy scripts domain
- Ensure nonce is passed to header

### Issue: Countdown not updating

**Check:**
1. Browser console for JavaScript errors
2. Nonce on countdown script tag
3. CSP allows inline scripts with nonce

**Solution:**
- Verify nonce: `<script nonce="<?php echo $cspNonce; ?>">`
- Check JavaScript syntax
- Ensure date format is correct

### Issue: PDF downloads fail (404)

**Check:**
1. Files exist: `assets/downloads/*.pdf`
2. File permissions (readable by web server)
3. Path in `baseUrl()` function

**Solution:**
```bash
# Verify files exist
ls -lh assets/downloads/*.pdf

# Check permissions
chmod 644 assets/downloads/*.pdf

# Test URL directly
http://localhost:8082/assets/downloads/cfk-application-2025.pdf
```

---

## Related Documentation

- **Zeffy Integration:** `docs/features/zeffy-donation-modal.md`
- **Button System:** `docs/components/button-system.md`
- **Security Headers:** `docs/technical/php-82-compliance.md`
- **Deployment Guide:** `docs/deployment/SECURITY-DEPLOYMENT.md`

---

## Change Log

**v1.7.3 (October 28, 2025):**
- Initial implementation of temporary landing page
- Smart routing with date-based toggle
- Live countdown timer
- Zeffy donation form integration
- How to Apply section with PDF downloads
- Preview modes for testing
- Simplified header/footer for pre-launch

---

**Last Updated:** October 28, 2025
**Next Review:** November 5, 2025 (after launch)
**Maintenance:** Low (automatic transition, minimal intervention needed)
