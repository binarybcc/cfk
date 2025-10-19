# CSV Import System - Testing Plan

**System Status:** DEPLOYED TO PRODUCTION
**Testing URL:** https://cforkids.org/admin/import_csv.php
**Date:** October 10, 2025

---

## 🧪 Manual Testing Checklist

Since we cannot automate browser testing from CLI, here's what needs to be manually tested:

### Phase 1: Basic Upload & Preview ✓

**Test 1.1: Upload Valid CSV**
- [ ] Navigate to https://cforkids.org/admin/import_csv.php
- [ ] Click "Choose File" and select a valid CSV
- [ ] Click "Upload & Preview"
- [ ] **Expected:** Preview section appears with statistics
- [ ] **Expected:** No errors shown
- [ ] **Expected:** Statistics show correct counts

**Test 1.2: Upload Invalid File Type**
- [ ] Try uploading a .txt or .xlsx file
- [ ] **Expected:** Error message about file type
- [ ] **Expected:** No preview shown

**Test 1.3: Upload Oversized File**
- [ ] Try uploading a file > 5MB
- [ ] **Expected:** Error message about file size
- [ ] **Expected:** Upload rejected

**Test 1.4: Upload Malformed CSV**
- [ ] Try uploading CSV with wrong columns
- [ ] **Expected:** Parsing error shown
- [ ] **Expected:** Details about what's wrong

---

### Phase 2: Warning System ⚠️

**Test 2.1: Sponsored Child Removed (High Priority)**
- [ ] Have a sponsored child in database
- [ ] Upload CSV WITHOUT that child
- [ ] **Expected:** RED warning appears
- [ ] **Expected:** Shows child's name and family ID
- [ ] **Expected:** Option to "Keep inactive children" appears

**Test 2.2: Data Becoming Blank (Medium Priority)**
- [ ] Have a child with filled interests/wishes
- [ ] Upload CSV with those fields empty
- [ ] **Expected:** ORANGE warning appears
- [ ] **Expected:** Shows field name and old value

**Test 2.3: Age Decreased (Medium Priority)**
- [ ] Have a child aged 10
- [ ] Upload CSV with same child aged 8
- [ ] **Expected:** ORANGE warning appears
- [ ] **Expected:** Shows old age → new age

**Test 2.4: Gender Changed (Low Priority)**
- [ ] Have a child with gender "M"
- [ ] Upload CSV with gender "F"
- [ ] **Expected:** YELLOW warning appears
- [ ] **Expected:** Shows old → new gender

---

### Phase 3: Sponsorship Preservation 🔒

**Test 3.1: Preserve Sponsored Status**
- [ ] Set a child's status to "sponsored" in database
- [ ] Upload CSV with same child (status = available)
- [ ] Confirm import
- [ ] **Expected:** Child's status remains "sponsored"
- [ ] **Expected:** Success message shows "X sponsorships preserved"

**Test 3.2: Preserve Pending Status**
- [ ] Set a child's status to "pending"
- [ ] Upload CSV with same child
- [ ] Confirm import
- [ ] **Expected:** Child's status remains "pending"
- [ ] **Expected:** Count shows in preserved sponsorships

**Test 3.3: Multiple Sponsorships**
- [ ] Have 5 children with sponsored/pending status
- [ ] Upload CSV with all 5 children
- [ ] Confirm import
- [ ] **Expected:** All 5 preserve their status
- [ ] **Expected:** Success shows "5 sponsorships preserved"

**Test 3.4: Keep Inactive Option**
- [ ] Have 1 sponsored child
- [ ] Upload CSV WITHOUT that child
- [ ] Check "Keep inactive children"
- [ ] Confirm import
- [ ] **Expected:** Sponsored child remains in database
- [ ] **Expected:** Not shown as "removed"

**Test 3.5: Remove Inactive Option**
- [ ] Have 1 sponsored child
- [ ] Upload CSV WITHOUT that child
- [ ] UNCHECK "Keep inactive children"
- [ ] Confirm import
- [ ] **Expected:** Sponsored child is removed from database
- [ ] **Expected:** Warning was shown in preview

---

### Phase 4: Backup System 💾

**Test 4.1: Automatic Backup Creation**
- [ ] Note current number of backups
- [ ] Upload and confirm import
- [ ] Check backup count
- [ ] **Expected:** Backup count increased by 1
- [ ] **Expected:** New backup has current timestamp
- [ ] **Expected:** Backup shows correct children count

**Test 4.2: Backup Rotation (Max 2)**
- [ ] Perform 3 imports in a row
- [ ] Check backup list
- [ ] **Expected:** Only 2 backups shown
- [ ] **Expected:** Oldest backup was deleted
- [ ] **Expected:** Most recent 2 remain

**Test 4.3: Restore from Backup**
- [ ] Note current children count
- [ ] Delete all children
- [ ] Find a backup in the list
- [ ] Click "Restore This Backup"
- [ ] Confirm restore
- [ ] **Expected:** Children restored
- [ ] **Expected:** Count matches backup metadata
- [ ] **Expected:** Success message shown

**Test 4.4: Download Backup**
- [ ] Find a backup in the list
- [ ] Click "Download"
- [ ] **Expected:** CSV file downloads
- [ ] **Expected:** Opens in Excel/spreadsheet
- [ ] **Expected:** Contains children data

**Test 4.5: Pre-Restore Backup**
- [ ] Have some children in database
- [ ] Restore from an old backup
- [ ] Check backup list
- [ ] **Expected:** New "pre_restore" backup created
- [ ] **Expected:** Can restore to state before restore

---

### Phase 5: Session Handling 🔐

**Test 5.1: Preview Then Confirm (No Re-upload)**
- [ ] Upload CSV and see preview
- [ ] Don't re-upload
- [ ] Just click "Confirm Import"
- [ ] **Expected:** Import succeeds
- [ ] **Expected:** Uses file from preview step

**Test 5.2: Session Timeout**
- [ ] Upload CSV and see preview
- [ ] Wait 30+ minutes (or clear cookies)
- [ ] Click "Confirm Import"
- [ ] **Expected:** Error about no file in session
- [ ] **Expected:** Instructions to upload again

**Test 5.3: Multiple Tabs**
- [ ] Open import page in 2 tabs
- [ ] Upload different CSV in each tab
- [ ] **Expected:** Each tab maintains its own file
- [ ] **Expected:** No cross-contamination

---

### Phase 6: Edge Cases 🔬

**Test 6.1: Empty CSV (Headers Only)**
- [ ] Upload CSV with only headers, no data
- [ ] **Expected:** Error or warning about empty file
- [ ] **Expected:** Clear message about what's wrong

**Test 6.2: Duplicate Children in CSV**
- [ ] Upload CSV with same Family ID + Child Letter twice
- [ ] **Expected:** Warning about duplicates
- [ ] **Expected:** Import handles gracefully

**Test 6.3: Special Characters in Names**
- [ ] Upload CSV with names containing ñ, é, ü, etc.
- [ ] Confirm import
- [ ] **Expected:** Special characters preserved correctly
- [ ] **Expected:** No encoding issues

**Test 6.4: Very Large CSV (Near 5MB Limit)**
- [ ] Upload CSV with 1000+ children
- [ ] **Expected:** Upload succeeds
- [ ] **Expected:** Preview shows correct statistics
- [ ] **Expected:** Import completes without timeout

**Test 6.5: Missing Required Fields**
- [ ] Upload CSV missing name or family_id
- [ ] **Expected:** Parsing error
- [ ] **Expected:** Shows which fields are missing

---

### Phase 7: Integration Testing 🔗

**Test 7.1: Verify Database Updates**
- [ ] Note database state before import
- [ ] Import CSV with changes
- [ ] Check database directly
- [ ] **Expected:** All fields updated correctly
- [ ] **Expected:** No orphaned records

**Test 7.2: Verify Frontend Display**
- [ ] Import children with updated info
- [ ] Visit public sponsorship page
- [ ] **Expected:** New/updated children show correctly
- [ ] **Expected:** Removed children don't appear

**Test 7.3: CSV Export/Import Roundtrip**
- [ ] Export current children to CSV
- [ ] Import that exact CSV back
- [ ] **Expected:** 0 new, 0 removed, X unchanged
- [ ] **Expected:** No warnings or errors

---

## 📊 Test Results Template

Copy this for each test run:

```
**Test Run:** [Date/Time]
**Tester:** [Name]
**Environment:** Production / Staging

**Phase 1: Basic Upload & Preview**
- Test 1.1: ✅ / ❌ [Notes]
- Test 1.2: ✅ / ❌ [Notes]
...

**Issues Found:**
1. [Description] - Severity: High/Medium/Low
2. [Description] - Severity: High/Medium/Low

**Overall Result:** PASS / FAIL / NEEDS WORK
```

---

## 🐛 Bug Report Template

If you find issues:

```
**Bug:** [Short description]
**Severity:** Critical / High / Medium / Low

**Steps to Reproduce:**
1.
2.
3.

**Expected Behavior:**
[What should happen]

**Actual Behavior:**
[What actually happened]

**Environment:**
- Browser: [Chrome/Firefox/Safari]
- OS: [Windows/Mac/Linux]
- Date/Time: [When it occurred]

**Screenshots:**
[Attach if possible]

**Additional Notes:**
[Any other relevant info]
```

---

## ✅ Verification Checklist

Before marking system as production-ready:

**Functionality:**
- [ ] All uploads work correctly
- [ ] Warnings appear when expected
- [ ] Sponsorships are preserved
- [ ] Backups are created automatically
- [ ] Restores work correctly
- [ ] No data loss scenarios

**Performance:**
- [ ] Large files upload without timeout
- [ ] Preview generates quickly
- [ ] Import completes in reasonable time
- [ ] No memory issues

**User Experience:**
- [ ] Instructions are clear
- [ ] Error messages are helpful
- [ ] Warnings make sense
- [ ] Interface is intuitive
- [ ] Mobile responsive

**Security:**
- [ ] CSRF tokens work
- [ ] File type validation works
- [ ] Size limits enforced
- [ ] No unauthorized access
- [ ] Session security maintained

**Documentation:**
- [ ] Quick guide is accurate
- [ ] Deployment docs complete
- [ ] Testing plan followed
- [ ] Known issues documented

---

## 🎯 Success Criteria

System is ready for production when:

1. ✅ All Phase 1-4 tests pass
2. ✅ No critical or high severity bugs
3. ✅ At least one full import tested successfully
4. ✅ Backup/restore verified working
5. ✅ User feedback is positive
6. ✅ Performance is acceptable
7. ✅ Documentation is complete

---

## 📞 Need Help?

**Server Access:**
- Host: d646a74eb9.nxcli.io
- Files: ~/d646a74eb9.nxcli.io/html/

**Key Files:**
- Import page: /admin/import_csv.php
- Analyzer: /includes/import_analyzer.php
- Backups: /backups/

**Logs:**
- PHP errors: Check server error logs
- Import errors: Logged via error_log()

---

**Note:** Some tests require database access or specific data states. Coordinate with developer if needed.
