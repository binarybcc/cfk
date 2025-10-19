# Quick Testing Guide - Alpine.js v1.4

**Branch:** v1.4-alpine-js-enhancement
**Testing Time:** ~15 minutes

---

## 🚀 Quick Start

1. **Start Docker environment:**
   ```bash
   docker compose up -d
   ```

2. **Access application:**
   ```
   http://localhost:8080
   ```

3. **Admin login:**
   ```
   Username: admin
   Password: admin123
   ```

---

## ✅ Test Checklist (3 Features)

### Test 1: Instant Search (5 min)

**Location:** Children page (http://localhost:8080?page=children)

1. **Search by family code:**
   - Type "175A" → Should show matching child
   - Clear search → Should show all

2. **Search by interests:**
   - Type "art" → Should filter to children with art interests
   - Type "basketball" → Should filter accordingly

3. **Gender filter:**
   - Select "Boys" → Should show only boys
   - Select "Girls" → Should show only girls
   - Select "Both" → Should show all

4. **Age range:**
   - Set min=5, max=10 → Should filter age range
   - Reset to 0-18 → Should show all

5. **Multi-criteria:**
   - Combine search + gender + age
   - Verify all filters work together

6. **Results counter:**
   - Check "Showing X of Y children" updates live

**✅ Expected:** Instant filtering with no page reloads

---

### Test 2: FAQ Accordion (3 min)

**Location:** How to Apply page (http://localhost:8080?page=how_to_apply)

1. **Scroll to FAQ section** (bottom of page)

2. **Click first question:**
   - Should expand smoothly
   - Icon should change from + to −

3. **Click second question:**
   - First should collapse
   - Second should expand
   - Only one open at a time

4. **Click same question twice:**
   - Should open then close

5. **Test all 8 questions:**
   - Each should expand/collapse correctly

**✅ Expected:** Smooth animations, no jerky transitions

---

### Test 3: CSV Import Validation (5 min)

**Location:** Admin → Import CSV (http://localhost:8080/admin/import_csv.php)

1. **No file selected:**
   - Click "Analyze CSV" without selecting file
   - Should show error: "Please select a file to upload"
   - Submit button should be disabled

2. **Invalid file type:**
   - Select a .txt or .xlsx file
   - Should show error: "File must be a CSV file"
   - Submit button should be disabled

3. **Large file warning:**
   - If you have a >1MB CSV:
     - Should show warning: "Large file detected"
     - Submit button should still be enabled (warning only)

4. **Valid file:**
   - Select a .csv file < 5MB
   - Should show no errors
   - Submit button should be enabled

5. **File size validation:**
   - Try to validate behavior with different file sizes
   - 5MB+ should show error and disable submit

**✅ Expected:** Real-time validation before clicking submit

---

## 🔒 Privacy Compliance Tests (2 min)

### Check No Names Displayed

1. **Children page:**
   - Verify only "Family Code: 175A" format shown
   - No "Emma Johnson" or similar names

2. **Child detail page:**
   - Click any child card
   - Verify "Family 175A" in title
   - No individual names

3. **Sponsor portal:**
   - Create test sponsorship
   - Check portal shows "Family 175" not "Johnson Family"

4. **Admin pages:**
   - Check children management
   - Verify no name columns

**✅ Expected:** Only numeric/letter codes (175A, 101B, etc.)

---

## 🎨 Visual Tests (2 min)

### Generic Avatars

1. **Children page:**
   - Verify age-appropriate avatars display
   - Young child (4) → Should show baby/toddler avatar
   - Elementary (8) → Should show elementary avatar
   - Teen (15) → Should show teen avatar

2. **Gender-appropriate:**
   - Boys should have blue/masculine avatars
   - Girls should have pink/feminine avatars

**✅ Expected:** 8 different avatar types based on age/gender

### Clean UI

1. **Filter labels:**
   - Verify NO emojis on "Search", "Gender", "Age Range"
   - Should be plain text labels

**✅ Expected:** Professional, clean appearance

---

## 🐛 Common Issues & Fixes

### Alpine.js Not Loading

**Symptom:** Filters don't work, FAQ doesn't expand

**Check:**
```javascript
// Open browser console and type:
Alpine.version
// Should return: "3.14.1"
```

**Fix:** Clear browser cache and reload

---

### Search Not Filtering

**Symptom:** Typing in search doesn't filter results

**Check:**
1. Open browser console → Look for errors
2. Verify `window.childrenData` exists
3. Check network tab → Alpine.js CDN loaded

**Fix:** Hard refresh (Cmd+Shift+R / Ctrl+F5)

---

### Database Errors

**Symptom:** SQL errors mentioning "family_name" or "name"

**Problem:** Old database schema still has removed columns

**Fix:**
```sql
-- Remove family_name column
ALTER TABLE families DROP COLUMN family_name;

-- Or reimport fresh schema
mysql -u root -p cfk_sponsorship < database/schema.sql
```

---

## 📊 Test Results Template

Copy and fill out:

```
✅ Alpine.js v1.4 Testing Results
Date: ___________
Tester: ___________

Test 1: Instant Search
[ ] Search by family code works
[ ] Search by interests works
[ ] Gender filter works
[ ] Age range filter works
[ ] Results counter updates
[ ] No page reloads

Test 2: FAQ Accordion
[ ] Questions expand/collapse
[ ] Smooth animations
[ ] Icons toggle (+/−)
[ ] Only one open at a time

Test 3: CSV Validation
[ ] No file error works
[ ] Invalid type error works
[ ] Large file warning works
[ ] Submit button enables/disables
[ ] Real-time validation

Privacy Tests
[ ] No child names displayed
[ ] No family names displayed
[ ] Only family codes shown
[ ] Generic avatars only

Visual Tests
[ ] Age-appropriate avatars
[ ] Gender-appropriate avatars
[ ] No emojis on labels
[ ] Clean, professional UI

OVERALL STATUS: [ ] PASS / [ ] FAIL

Issues Found:
_______________________________
_______________________________
```

---

## 🎯 Success Criteria

**Feature is PASSING if:**
- ✅ All interactions work as expected
- ✅ No console errors
- ✅ Smooth animations/transitions
- ✅ No page reloads on filtering
- ✅ Privacy compliance verified

**Feature is FAILING if:**
- ❌ JavaScript errors in console
- ❌ Features don't respond to interaction
- ❌ Page reloads when it shouldn't
- ❌ Names displayed anywhere

---

## 📞 Reporting Issues

If you find bugs, report with:
1. **Feature affected** (search, FAQ, validation)
2. **Steps to reproduce**
3. **Expected behavior**
4. **Actual behavior**
5. **Browser/version** (Chrome 120, Safari 17, etc.)
6. **Console errors** (screenshot)

---

**Testing Complete?**
→ Mark all checkboxes ✅
→ Document any issues
→ Proceed to production deployment

---

**Document Version:** 1.0
**Last Updated:** October 11, 2025
