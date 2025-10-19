# Phase 1 Accessibility Testing Guide

**Test Date**: October 18, 2025
**Phase**: Phase 1 - Critical Accessibility Fixes
**Environment**: Local Docker (http://localhost:8082)

---

## Pre-Testing Setup

### 1. Deploy Changes to Local Docker

```bash
# Ensure Docker is running
docker-compose ps

# Restart web container to pick up changes
docker-compose restart web

# Verify site is accessible
open http://localhost:8082
```

### 2. Install Testing Tools

**Browser Extensions** (Chrome/Edge):
- [Lighthouse](https://developers.google.com/web/tools/lighthouse) (built into Chrome DevTools)
- [axe DevTools](https://chrome.google.com/webstore/detail/axe-devtools-web-accessib/lhdoppojpmngadmnindnejefpokejbdd)
- [WAVE](https://chrome.google.com/webstore/detail/wave-evaluation-tool/jbbplnpkjmmeebjpijfedlgcdilocofh)

**Screen Readers** (Optional but Recommended):
- **macOS**: VoiceOver (built-in, Cmd+F5 to toggle)
- **Windows**: NVDA (free, https://www.nvaccess.org/download/)

---

## Test 1: Skip Link (WCAG 2.4.1)

### Purpose
Keyboard users can bypass navigation and jump directly to main content.

### Test Steps

1. **Open homepage**: http://localhost:8082
2. **Press Tab key once** (don't click anything first)
3. **Expected Result**:
   - Skip link appears at top-left: "Skip to main content"
   - Link has yellow outline (focus indicator)
   - Link is clearly visible

4. **Press Enter** while skip link is focused
5. **Expected Result**:
   - Page scrolls to main content area
   - Navigation is bypassed

6. **Test on multiple pages**:
   - Children page: http://localhost:8082/?page=children
   - About page: http://localhost:8082/?page=about
   - How to Apply: http://localhost:8082/?page=how_to_apply

### Pass Criteria
- ✅ Skip link appears on first Tab press
- ✅ Skip link is visible and styled correctly
- ✅ Activating skip link jumps to main content
- ✅ Works consistently on all pages

### Results
- [ ] PASS
- [ ] FAIL - Describe issue: _________________________

---

## Test 2: Form Labels (WCAG 3.3.2)

### Purpose
All form inputs have associated labels for screen reader users.

### Test Steps

#### A. Header Search Form

1. **Open any page**: http://localhost:8082
2. **Tab to search input** (in header)
3. **Right-click search input → Inspect**
4. **Expected in HTML**:
   ```html
   <label for="header-search-input" class="visually-hidden">Search for children</label>
   <input id="header-search-input" ... aria-describedby="header-search-hint">
   <span id="header-search-hint" class="visually-hidden">Search by family number...</span>
   ```

5. **Visual Check**:
   - Label is NOT visible on screen (visually-hidden class)
   - Placeholder text is visible: "Search children..."

#### B. Sponsor Lookup Form

1. **Open**: http://localhost:8082/?page=sponsor_lookup
2. **Inspect email input**
3. **Expected in HTML**:
   ```html
   <label for="sponsor_email">Your Email Address <span aria-label="required">*</span></label>
   <input id="sponsor_email" ... aria-required="true" aria-describedby="email-help">
   <div id="email-help">Enter the email address you used when sponsoring</div>
   ```

4. **Visual Check**:
   - Label is visible: "Your Email Address *"
   - Helper text is visible below input
   - Required indicator (*) is present

### Pass Criteria
- ✅ All inputs have associated labels (for/id match)
- ✅ ARIA attributes present (aria-describedby, aria-required)
- ✅ Visually-hidden labels are hidden but accessible
- ✅ Helper text is properly linked

### Results
- [ ] PASS
- [ ] FAIL - Describe issue: _________________________

---

## Test 3: ARIA Live Regions (WCAG 4.1.3)

### Purpose
Screen readers announce when children are added/removed from cart.

### Test Steps (Visual Verification)

1. **Open children page**: http://localhost:8082/?page=children
2. **Right-click page → Inspect**
3. **Search in HTML for**: `id="a11y-announcements"`
4. **Expected to find**:
   ```html
   <div id="a11y-announcements" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>
   ```

5. **Open browser console** (F12 → Console tab)
6. **Test announcement function**:
   ```javascript
   SelectionsManager.announce("Test announcement - you should hear this");
   ```

7. **Expected Result**: No visual change (announcement is for screen readers)

### Test Steps (Functional - Cart Updates)

1. **Open children page**: http://localhost:8082/?page=children
2. **Click "Add to Sponsorship" on any child**
3. **Inspect `a11y-announcements` element**
4. **Expected text content** (example):
   ```
   "Added child 175A to your selections. You have 1 child selected."
   ```

5. **Remove the child** (go to My Sponsorships → Remove)
6. **Expected text content**:
   ```
   "Removed child 175A from your selections. You have 0 children remaining."
   ```

7. **Test "Add Entire Family"** button
8. **Expected text content**:
   ```
   "Added 3 children to your selections. You now have 3 total selected."
   ```

### Pass Criteria
- ✅ aria-live region exists in HTML
- ✅ Announcement text updates when cart changes
- ✅ Messages are clear and descriptive
- ✅ Region is visually hidden (no visual output)

### Results
- [ ] PASS
- [ ] FAIL - Describe issue: _________________________

---

## Test 4: Heading Hierarchy (WCAG 1.3.1)

### Purpose
Pages have logical heading structure for screen reader navigation.

### Test Steps

#### A. Children Page Headings

1. **Open**: http://localhost:8082/?page=children
2. **Right-click → Inspect**
3. **Search for headings** (Ctrl+F: `<h`)
4. **Expected hierarchy**:
   - `<h1>` from page_header component (page title)
   - `<h2>No Children Found</h2>` (if no results)
   - `<h2>Family X</h2>` (in family modal)
   - `<h2>Ready to Make a Difference?</h2>` (bottom section)

5. **Verify**: No h3 appears before h2

#### B. Confirm Sponsorship Page Headings

1. **Add children to cart** (go to children page, add some)
2. **Open**: http://localhost:8082/?page=confirm_sponsorship
3. **Inspect headings**
4. **Expected hierarchy**:
   - `<h1>Confirm Your Sponsorship</h1>`
   - `<h2>Your Selections</h2>`
   - `<h2>Your Contact Information</h2>`

5. **Verify**: No heading level skips (h1 → h3)

#### C. Use Heading Outline Tool

1. **Install browser extension**: [HeadingsMap](https://chrome.google.com/webstore/detail/headingsmap/flbjommegcjonpdmenkdiocclhjacmbi)
2. **Open any page**
3. **Click HeadingsMap extension**
4. **Expected**: Tree structure shows logical hierarchy

### Pass Criteria
- ✅ All pages have exactly one h1
- ✅ No heading level skips (e.g., h1 → h3)
- ✅ Headings follow logical document structure
- ✅ HeadingsMap shows proper tree structure

### Results

**Children Page**:
- [ ] PASS
- [ ] FAIL - Describe issue: _________________________

**Confirm Sponsorship Page**:
- [ ] PASS
- [ ] FAIL - Describe issue: _________________________

---

## Test 5: Visually Hidden Utility Class

### Purpose
Screen reader content is hidden visually but accessible.

### Test Steps

1. **Inspect CSS file**: `assets/css/styles.css`
2. **Search for**: `.visually-hidden`
3. **Expected CSS**:
   ```css
   .visually-hidden {
       position: absolute;
       width: 1px;
       height: 1px;
       margin: -1px;
       overflow: hidden;
       clip: rect(0, 0, 0, 0);
       white-space: nowrap;
       border-width: 0;
   }
   ```

4. **Test elements using this class**:
   - Skip link (before focus)
   - Search form label
   - ARIA live region
   - Form helper text

5. **Visual Check**: None of these should be visible on screen

6. **Tab to skip link**
7. **Expected**: Skip link BECOMES visible when focused

### Pass Criteria
- ✅ visually-hidden class exists in CSS
- ✅ Elements with this class are not visible
- ✅ Skip link becomes visible on focus
- ✅ Content is still in DOM (accessible to screen readers)

### Results
- [ ] PASS
- [ ] FAIL - Describe issue: _________________________

---

## Test 6: Automated Accessibility Testing

### A. Lighthouse Audit

1. **Open homepage**: http://localhost:8082
2. **Open Chrome DevTools** (F12)
3. **Click "Lighthouse" tab**
4. **Select**:
   - ✅ Accessibility
   - Device: Desktop
5. **Click "Analyze page load"**
6. **Wait for results**

7. **Record scores**:
   - Accessibility Score: ______ / 100
   - Look for improvements in:
     - "Bypass Blocks" (skip link)
     - "Form elements have labels"
     - "Heading elements appear in sequentially-descending order"

8. **Repeat for key pages**:
   - Children: http://localhost:8082/?page=children
   - Sponsor Lookup: http://localhost:8082/?page=sponsor_lookup
   - About: http://localhost:8082/?page=about

### B. axe DevTools Scan

1. **Install**: [axe DevTools](https://chrome.google.com/webstore/detail/axe-devtools-web-accessib/lhdoppojpmngadmnindnejefpokejbdd)
2. **Open any page**
3. **Open DevTools → axe DevTools tab**
4. **Click "Scan ALL of my page"**
5. **Review results**:
   - Issues found: ______
   - Critical: ______
   - Serious: ______
   - Moderate: ______

6. **Check for Phase 1 fixes**:
   - ✅ No "bypass blocks" errors
   - ✅ No "form label" errors
   - ✅ No "heading order" errors

### C. WAVE Browser Extension

1. **Install**: [WAVE](https://chrome.google.com/webstore/detail/wave-evaluation-tool/jbbplnpkjmmeebjpijfedlgcdilocofh)
2. **Open any page**
3. **Click WAVE extension icon**
4. **Review summary**:
   - Errors: ______
   - Alerts: ______
   - Features: ______
   - Structural Elements: ______

5. **Click "Structure" tab**
6. **Verify**:
   - ✅ Heading order is correct
   - ✅ Skip link is detected
   - ✅ Form labels are present

### Pass Criteria

**Lighthouse**:
- ✅ Score improved from baseline (target: 85-90)
- ✅ No critical accessibility errors
- ✅ Bypass blocks check passes
- ✅ Form labels check passes

**axe DevTools**:
- ✅ Zero critical issues
- ✅ Zero serious issues related to Phase 1 fixes
- ✅ Skip link detected and working

**WAVE**:
- ✅ No structural errors
- ✅ Heading order correct
- ✅ All form labels detected

### Results

**Lighthouse Scores**:
- Homepage: ______ / 100
- Children: ______ / 100
- Sponsor Lookup: ______ / 100

**axe DevTools**:
- [ ] PASS (0 critical, 0 serious Phase 1 issues)
- [ ] FAIL - Describe issues: _________________________

**WAVE**:
- [ ] PASS (no structural errors)
- [ ] FAIL - Describe issues: _________________________

---

## Test 7: Screen Reader Testing (Optional)

**Note**: This requires a screen reader. Skip if not available.

### macOS VoiceOver Test

1. **Enable VoiceOver**: Press Cmd+F5
2. **Open**: http://localhost:8082
3. **Press Tab**
4. **Expected**: VoiceOver announces "Skip to main content, link"
5. **Press Tab again** (navigate to search)
6. **Expected**: VoiceOver announces "Search for children, edit text" + helper text

7. **Navigate to children page**
8. **Add child to cart**
9. **Expected**: VoiceOver announces "Added child 175A to your selections..."

10. **Navigate by headings**: Press VO+Cmd+H (VoiceOver+Command+H)
11. **Expected**: Can jump between headings in logical order

### Pass Criteria
- ✅ Skip link is announced correctly
- ✅ Form labels are announced
- ✅ Cart updates are announced
- ✅ Headings can be navigated logically

### Results
- [ ] PASS
- [ ] FAIL - Describe issue: _________________________
- [ ] SKIPPED (no screen reader available)

---

## Test Summary Template

### Overall Results

**Date Tested**: _______________
**Tested By**: _______________
**Environment**: Local Docker (http://localhost:8082)

| Test | Status | Notes |
|------|--------|-------|
| 1. Skip Link | ☐ Pass ☐ Fail | |
| 2. Form Labels | ☐ Pass ☐ Fail | |
| 3. ARIA Live Regions | ☐ Pass ☐ Fail | |
| 4. Heading Hierarchy | ☐ Pass ☐ Fail | |
| 5. Visually Hidden Class | ☐ Pass ☐ Fail | |
| 6. Lighthouse Audit | ☐ Pass ☐ Fail | Score: ______ |
| 6. axe DevTools | ☐ Pass ☐ Fail | Issues: ______ |
| 6. WAVE | ☐ Pass ☐ Fail | Errors: ______ |
| 7. Screen Reader | ☐ Pass ☐ Fail ☐ Skip | |

### Issues Found

1. **Issue**: _________________________
   - **Severity**: Critical / High / Medium / Low
   - **Page**: _________________________
   - **Fix Required**: _________________________

2. **Issue**: _________________________
   - **Severity**: Critical / High / Medium / Low
   - **Page**: _________________________
   - **Fix Required**: _________________________

### Recommendations

- [ ] Ready to deploy to production
- [ ] Needs minor fixes before deployment
- [ ] Needs significant fixes before deployment
- [ ] Ready to proceed with Phase 2

### Next Steps

1. _________________________
2. _________________________
3. _________________________

---

## Quick Start Testing (5 Minutes)

**If you only have 5 minutes, test these critical items**:

1. **Skip Link**: Tab on homepage → Skip link appears → Works
2. **Lighthouse**: Run audit on homepage → Score 85+
3. **Headings**: Use HeadingsMap extension → Check hierarchy
4. **Cart Announcement**: Add child → Check console → Announcement text appears

If all 4 pass → Phase 1 is working correctly ✅

---

## Troubleshooting

### Skip Link Not Appearing
- Check browser cache (Ctrl+Shift+R to hard refresh)
- Verify changes deployed: `docker-compose restart web`
- Check CSS loaded: Inspect element, verify styles applied

### Form Labels Not Working
- Check browser console for JavaScript errors
- Verify IDs match (label `for` = input `id`)
- Check aria-describedby references exist

### ARIA Announcements Not Updating
- Check console: `SelectionsManager.announce("test")`
- Verify element exists: `document.getElementById('a11y-announcements')`
- Check network tab: selections.js loaded correctly

### Headings Still Wrong
- Hard refresh browser (Ctrl+Shift+R)
- Check file was actually saved
- Verify correct page (not cached version)

---

**Created**: October 18, 2025
**Last Updated**: October 18, 2025
**Version**: 1.0
