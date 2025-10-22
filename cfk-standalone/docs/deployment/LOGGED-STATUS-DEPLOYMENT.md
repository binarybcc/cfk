# LOGGED Status Deployment Guide

**Feature:** Add "LOGGED" sponsorship status for external tracking
**Date:** October 22, 2025
**Version:** v1.7-logged-status
**Priority:** HIGH - Staff operational need

---

## 📋 Pre-Deployment Checklist

### ✅ Required Files Ready:
- [x] Database migration: `database/migrations/2025-10-22-add-logged-status.sql`
- [x] Rollback script: `database/migrations/2025-10-22-add-logged-status-ROLLBACK.sql`
- [x] Updated: `src/Sponsorship/Manager.php`
- [x] Updated: `admin/manage_sponsorships.php`
- [x] Updated: `pages/my_sponsorships.php`
- [x] Updated: `assets/css/styles.css`
- [x] Documentation: `docs/features/logged-status-implementation.md`

---

## 🚀 Deployment Steps

### Step 1: Backup Production Database (CRITICAL!)

```bash
# SSH to production
sshpass -p 'PiggedCoifSourerFating' ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io

# Create backup
mysqldump -u a4409d26_509946 -p a4409d26_509946 > ~/backups/cfk_before_logged_status_$(date +%Y%m%d_%H%M%S).sql
# Password: Fests42Cue50Fennel56Auk46

# Verify backup exists
ls -lh ~/backups/cfk_before_logged_status_*.sql
```

### Step 2: Run Database Migration

```bash
# Still on production server
cd /home/a4409d26/d646a74eb9.nxcli.io/html

# Upload migration file (from local)
# (From local terminal)
sshpass -p 'PiggedCoifSourerFating' scp -P 22 \
  database/migrations/2025-10-22-add-logged-status.sql \
  a4409d26_1@d646a74eb9.nxcli.io:/home/a4409d26/d646a74eb9.nxcli.io/html/database/migrations/

# Run migration
mysql -u a4409d26_509946 -p a4409d26_509946 < database/migrations/2025-10-22-add-logged-status.sql
# Password: Fests42Cue50Fennel56Auk46
```

### Step 3: Verify Migration Success

```bash
# Check that ENUM includes 'logged'
mysql -u a4409d26_509946 -p a4409d26_509946 -e "
  SELECT COLUMN_TYPE
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_NAME = 'sponsorships' AND COLUMN_NAME = 'status';
"

# Expected output should include: enum('pending','confirmed','logged','completed','cancelled')

# Check that logged_date column exists
mysql -u a4409d26_509946 -p a4409d26_509946 -e "
  DESCRIBE sponsorships logged_date;
"

# Expected output: logged_date | datetime | YES | | NULL |
```

### Step 4: Deploy Application Code

```bash
# From local terminal
cd /Users/johncorbin/Desktop/projs/cfk/cfk-standalone

# Create deployment archive
tar -czf /tmp/logged-status-deploy.tar.gz \
  src/Sponsorship/Manager.php \
  admin/manage_sponsorships.php \
  pages/my_sponsorships.php \
  assets/css/styles.css

# Upload to production
sshpass -p 'PiggedCoifSourerFating' scp -P 22 \
  /tmp/logged-status-deploy.tar.gz \
  a4409d26_1@d646a74eb9.nxcli.io:/tmp/

# Extract on production
sshpass -p 'PiggedCoifSourerFating' ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io \
  "cd /home/a4409d26/d646a74eb9.nxcli.io/html && tar -xzf /tmp/logged-status-deploy.tar.gz"
```

### Step 5: Verify Deployment

```bash
# Check file timestamps
sshpass -p 'PiggedCoifSourerFating' ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io \
  "cd /home/a4409d26/d646a74eb9.nxcli.io/html && ls -la src/Sponsorship/Manager.php admin/manage_sponsorships.php pages/my_sponsorships.php assets/css/styles.css"
```

### Step 6: Test the Feature

1. **Access Admin Panel:**
   - https://cforkids.org/admin/manage_sponsorships.php

2. **Test LOGGED Status:**
   - Find a sponsorship with status "Confirmed"
   - Click "📋 Mark Logged" button
   - Verify status changes to "Logged"
   - Verify "Logged Externally" count increases in statistics

3. **Test Unlog Feature:**
   - Click "↩ Unlog" button on a logged sponsorship
   - Verify status reverts to "Confirmed"

4. **Test Mark Complete from Logged:**
   - Mark a sponsorship as "Logged"
   - Click "✓ Mark Complete" button
   - Verify status changes to "Completed"

5. **Test My Sponsorships Page:**
   - Mark a sponsorship as "Logged"
   - Access the sponsor's "My Sponsorships" link (from email)
   - Verify the sponsorship still displays correctly
   - Verify child details are visible

6. **Test Filter:**
   - Select "Logged in External System" from status filter
   - Verify only logged sponsorships appear

---

## ✅ Post-Deployment Verification

### Database Check:
```bash
# Count sponsorships by status
mysql -u a4409d26_509946 -p a4409d26_509946 -e "
  SELECT status, COUNT(*) as count
  FROM sponsorships
  GROUP BY status;
"
```

### Admin Panel Check:
- [ ] "Logged" filter option appears
- [ ] "Mark Logged" button appears for confirmed sponsorships
- [ ] "Unlog" button appears for logged sponsorships
- [ ] "Mark Complete" button appears for logged sponsorships
- [ ] Statistics show "Logged Externally" count
- [ ] Status badge shows "Logged" with teal background

### Sponsor Portal Check:
- [ ] Sponsors can access My Sponsorships when status is "logged"
- [ ] Child details display correctly
- [ ] No errors in browser console

### Error Log Check:
```bash
# Check for any errors
tail -50 /home/a4409d26/logs/php_error.log
```

---

## 🔄 Workflow Testing

### Complete Workflow Test:
```
1. Sponsor submits sponsorship → Status: PENDING
2. Admin clicks "Confirm" → Status: CONFIRMED
3. Admin clicks "Mark Logged" → Status: LOGGED
   ✓ Verify sponsor can still access My Sponsorships
4. Admin clicks "Mark Complete" → Status: COMPLETED
   ✓ Verify sponsor can no longer access My Sponsorships
```

### Unlog Workflow Test:
```
1. Find LOGGED sponsorship
2. Click "Unlog" → Status reverts to: CONFIRMED
3. Can mark as LOGGED again
```

---

## 🚨 Rollback Procedure (If Needed)

### If Issues Occur:

```bash
# SSH to production
sshpass -p 'PiggedCoifSourerFating' ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io

cd /home/a4409d26/d646a74eb9.nxcli.io/html

# Run rollback migration
mysql -u a4409d26_509946 -p a4409d26_509946 < database/migrations/2025-10-22-add-logged-status-ROLLBACK.sql

# Restore previous code from backup
# (Assuming you have a pre-deployment backup)
```

### Note on Rollback:
- The rollback script automatically moves any LOGGED sponsorships back to CONFIRMED
- No data is lost during rollback
- Sponsors maintain access throughout rollback process

---

## 📊 Expected Changes

### Database:
- **ENUM updated:** sponsorships.status now includes 'logged'
- **New column:** sponsorships.logged_date (DATETIME NULL)
- **New index:** idx_sponsorships_status_logged

### Admin Panel:
- **New filter:** "Logged in External System" option
- **New button:** "📋 Mark Logged" for confirmed sponsorships
- **New button:** "↩ Unlog" for logged sponsorships
- **New stat card:** "Logged Externally" count
- **Updated:** "Mark Complete" now available for logged sponsorships
- **Updated:** Cancel button now works for logged sponsorships

### My Sponsorships Page:
- **Query updated:** Now includes both 'confirmed' AND 'logged' statuses
- **No visual changes:** Sponsors see same interface

### CSS:
- **New class:** `.status-logged` with teal background (#17a2b8)

---

## 📝 Staff Training Notes

### How to Use the LOGGED Status:

**When to Mark as LOGGED:**
1. Sponsor confirms and pays ✅
2. Admin clicks "Confirm" button ✅
3. **Admin adds to external spreadsheet** → Click "📋 Mark Logged"
4. Gifts are delivered → Click "✓ Mark Complete"

**Key Points:**
- ✅ "Logged" means you've recorded it in your external spreadsheet
- ✅ Sponsors can still access their details when status is "logged"
- ✅ Use "Unlog" if you marked it logged by mistake
- ✅ "Mark Complete" should only be used when gifts are delivered (final step)

**Buttons You'll See:**
- **📋 Mark Logged**: For confirmed sponsorships (after adding to spreadsheet)
- **↩ Unlog**: For logged sponsorships (undo button)
- **✓ Mark Complete**: For logged sponsorships (when gifts delivered)

---

## 🎯 Success Criteria

### ✅ Deployment is Successful When:
- [ ] Database migration completed without errors
- [ ] All 4 updated files deployed successfully
- [ ] Admin can mark confirmed sponsorships as "logged"
- [ ] Admin can unlog sponsorships (undo)
- [ ] Admin can mark logged sponsorships as "complete"
- [ ] Sponsors can access My Sponsorships when status is "logged"
- [ ] Statistics show correct "Logged Externally" count
- [ ] Filter shows logged sponsorships correctly
- [ ] No errors in PHP error log
- [ ] Staff successfully tests the complete workflow

---

## 📞 Support

**If Issues Occur:**
1. Check PHP error log: `/home/a4409d26/logs/php_error.log`
2. Verify database migration completed successfully
3. Check file permissions (should be 644 for PHP files)
4. Use rollback procedure if needed
5. Contact development team with error messages

**Emergency Rollback Contact:**
- Always have database backup available
- Rollback script tested and ready
- Can revert to previous code via git or file backup

---

## 📈 Metrics to Track

### After Deployment:
- Count of sponsorships marked as "logged"
- Average time from "confirmed" to "logged"
- Average time from "logged" to "completed"
- Staff feedback on new workflow
- Any issues or confusion reported by staff

---

**Deployment Status:** 📝 READY FOR DEPLOYMENT
**Estimated Deployment Time:** 15-20 minutes
**Risk Level:** 🟢 LOW (additive feature, well-tested, rollback available)
**Staff Impact:** 🟢 POSITIVE (solves external tracking problem)

---

**Prepared By:** Claude Code Development Team
**Date:** October 22, 2025
**Version:** v1.7-logged-status
