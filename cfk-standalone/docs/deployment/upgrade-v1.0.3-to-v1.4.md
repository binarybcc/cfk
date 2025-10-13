# ⬆️ Upgrade Guide: v1.0.3 → v1.4

**Current Version:** 1.0.3 (already deployed)
**Target Version:** 1.4.0 (Alpine.js + Privacy Compliance)
**Upgrade Type:** Minor version with schema changes
**Downtime Required:** ~5-10 minutes

---

## ⚠️ CRITICAL: Schema Changes in v1.4

**Breaking Change:**
- **REMOVED:** `families.family_name` column (privacy compliance)
- **All SQL queries updated** to use `family_number` instead

**Impact:**
- 10+ files with SQL queries modified
- No data loss (only removes PII)
- All existing children and sponsorships preserved

---

## 📋 Pre-Upgrade Checklist

### 1. **MANDATORY Backups** (DO NOT SKIP!)

```bash
# SSH to production server
ssh a4409d26_1@d646a74eb9.nxcli.io
cd ~/d646a74eb9.nxcli.io/html/

# 1. Database backup with timestamp
mysqldump -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 \
  > ~/backups/v103_to_v14_db_$(date +%Y%m%d_%H%M%S).sql

# 2. Files backup
tar -czf ~/backups/v103_to_v14_files_$(date +%Y%m%d_%H%M%S).tar.gz \
  --exclude='uploads' \
  --exclude='*.log' \
  admin/ includes/ pages/ assets/ database/ config/ index.php

echo "✅ Backups complete - stored in ~/backups/"
ls -lh ~/backups/v103_to_v14_*
```

### 2. **Document Current State**

```bash
# Record current schema
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 \
  -e "SHOW COLUMNS FROM families;" > ~/backups/v103_families_schema.txt

# Count existing data
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 << 'SQLEOF'
SELECT 'Families' as entity, COUNT(*) as count FROM families
UNION ALL
SELECT 'Children', COUNT(*) FROM children
UNION ALL
SELECT 'Sponsorships', COUNT(*) FROM sponsorships;
SQLEOF
```

### 3. **Schedule Maintenance Window**

```bash
# Create maintenance mode page (optional but recommended)
cat > ~/d646a74eb9.nxcli.io/html/maintenance.html << 'MAINTENANCEEOF'
<!DOCTYPE html>
<html>
<head>
    <title>Christmas for Kids - Maintenance</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #d32f2f; }
    </style>
</head>
<body>
    <h1>🔧 System Upgrade in Progress</h1>
    <p>We're upgrading to v1.4 with exciting new features!</p>
    <p>Expected downtime: 5-10 minutes</p>
    <p>Please check back shortly.</p>
</body>
</html>
MAINTENANCEEOF

# Temporarily redirect to maintenance page (optional)
# mv index.php index.php.backup
# mv maintenance.html index.html
```

---

## 🚀 Upgrade Steps (Follow in Order!)

### Step 1: Upload v1.4 Package

```bash
# From your local machine:
scp cfk-v1.4-production.tar.gz a4409d26_1@d646a74eb9.nxcli.io:~/

# SSH back to server
ssh a4409d26_1@d646a74eb9.nxcli.io
```

### Step 2: Database Schema Migration

```bash
cd ~/d646a74eb9.nxcli.io/html/

# Connect to MySQL
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946
```

```sql
-- =========================================
-- v1.4 Schema Migration
-- =========================================

USE a4409d26_509946;

-- Step 1: Check if family_name column exists
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    DATA_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'a4409d26_509946'
  AND TABLE_NAME = 'families'
  AND COLUMN_NAME = 'family_name';

-- Step 2: If family_name exists, document sample data (for verification)
SELECT
    id,
    family_number,
    family_name,
    (SELECT COUNT(*) FROM children WHERE family_id = families.id) as child_count
FROM families
LIMIT 5;

-- Step 3: Remove family_name column (CRITICAL FOR v1.4)
ALTER TABLE families DROP COLUMN IF EXISTS family_name;

-- Step 4: Verify new schema
DESCRIBE families;
-- Expected columns: id, family_number, notes, created_at, updated_at
-- Should NOT see: family_name

-- Step 5: Test data integrity
SELECT
    f.family_number,
    COUNT(c.id) as children_count,
    SUM(CASE WHEN c.status = 'sponsored' THEN 1 ELSE 0 END) as sponsored_count
FROM families f
LEFT JOIN children c ON c.family_id = f.id
GROUP BY f.id, f.family_number
LIMIT 5;

-- Exit MySQL
exit
```

**Expected Output:**
```
Query OK, 0 rows affected (0.05 sec)
Records: 0  Duplicates: 0  Warnings: 0
```

### Step 3: Extract v1.4 Files

```bash
cd ~/d646a74eb9.nxcli.io/html/

# Extract v1.4 package (will overwrite existing files)
tar -xzf ~/cfk-v1.4-production.tar.gz

# Verify key files were updated
ls -lh includes/header.php pages/children.php admin/import_csv.php

# Set correct permissions
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 644 config/*.php
chmod 644 assets/images/*.png

echo "✅ v1.4 files deployed"
```

### Step 4: Update Configuration (if needed)

```bash
# Verify production config exists
ls -lh config/config.production.php

# If needed, update any environment-specific settings
nano config/config.production.php
# (No changes needed if config was already correct for v1.0.3)
```

### Step 5: Verify Alpine.js CDN

```bash
# Test Alpine.js CDN is accessible
curl -I https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js

# Should return:
# HTTP/2 200
# content-type: application/javascript; charset=utf-8

# Verify it's in header.php
grep -n "alpinejs@3.14.1" includes/header.php
```

### Step 6: Clear Caches

```bash
# Clear PHP OpCache (if using PHP-FPM)
touch ~/d646a74eb9.nxcli.io/html/index.php

# Clear any cached pages
rm -f ~/d646a74eb9.nxcli.io/html/cache/*.html 2>/dev/null || true

echo "✅ Caches cleared"
```

### Step 7: Restore Normal Operation

```bash
# If you enabled maintenance mode:
# rm index.html
# mv index.php.backup index.php

echo "✅ Site back online"
```

---

## ✅ Post-Upgrade Verification

### Automated Tests

```bash
# Test homepage
curl -I https://cforkids.org/sponsor/
# Expect: HTTP/2 200

# Test children page
curl -s https://cforkids.org/sponsor/?page=children | grep -o "alpinejs@3.14.1"
# Should return: alpinejs@3.14.1

# Test database queries work
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 << 'SQLEOF'
-- Test query without family_name (v1.4 pattern)
SELECT
    CONCAT(f.family_number, c.child_letter) as display_id,
    c.age,
    c.gender,
    c.status
FROM children c
INNER JOIN families f ON c.family_id = f.id
LIMIT 3;
SQLEOF
```

### Manual Browser Tests

**1. Children Page (Instant Search):**
- Visit: https://cforkids.org/sponsor/?page=children
- ✅ Open browser console (F12)
- ✅ Type: `Alpine.version` → Should return `"3.14.1"`
- ✅ Type in search box → Results filter instantly
- ✅ Change gender filter → Updates instantly
- ✅ Move age slider → Updates instantly
- ✅ Only family codes shown (e.g., "175A"), no names
- ✅ Generic avatars display

**2. How to Apply Page (FAQ Accordion):**
- Visit: https://cforkids.org/sponsor/?page=how_to_apply
- ✅ Scroll to FAQ section
- ✅ Click a question → Expands smoothly
- ✅ Icon changes from + to −
- ✅ Click another question → First collapses, second expands

**3. Admin CSV Import (Live Validation):**
- Visit: https://cforkids.org/sponsor/admin/import_csv.php
- ✅ Click "Analyze CSV" without file → Error shows
- ✅ Select .txt file → Error shows
- ✅ Select valid .csv → No errors, button enables

**4. Privacy Compliance:**
- Check all pages for any displayed names
- ✅ Children page → Only "Family Code: 175A"
- ✅ Sponsor portal → Only "Family 175"
- ✅ Admin pages → No name columns

### Database Integrity Check

```sql
-- Connect to database
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946

-- Verify all data preserved
SELECT
    'BEFORE (from backup)' as version,
    COUNT(*) as family_count
FROM (SELECT 1) as dummy
UNION ALL
SELECT
    'AFTER (current)',
    COUNT(*)
FROM families;

-- Verify sponsorships intact
SELECT
    status,
    COUNT(*) as count
FROM sponsorships
GROUP BY status;

-- Verify children intact
SELECT
    c.status,
    COUNT(*) as count
FROM children c
GROUP BY c.status;

exit
```

**Expected:** All counts should match pre-upgrade numbers.

---

## 🐛 Troubleshooting Upgrade Issues

### Issue: SQL Error "Unknown column 'family_name'"

**Cause:** v1.4 files deployed but database not migrated

**Fix:**
```sql
-- Run migration manually
ALTER TABLE families DROP COLUMN family_name;
```

### Issue: Alpine.js Not Loading (Search/FAQ Not Working)

**Diagnosis:**
```bash
# Check header.php has Alpine.js script
grep alpinejs includes/header.php

# Test CDN directly
curl -I https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js
```

**Fix:**
```bash
# Re-extract header.php from package
tar -xzf ~/cfk-v1.4-production.tar.gz includes/header.php
chmod 644 includes/header.php
```

### Issue: Children Page Shows Errors

**Diagnosis:**
```bash
# Check PHP error log
tail -50 ~/logs/error_log | grep -i error

# Test database connection
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 \
  -e "SELECT COUNT(*) FROM children;"
```

**Fix:** See rollback procedure below if errors persist

### Issue: Sponsorships Lost

**This should NEVER happen, but if it does:**

```bash
# IMMEDIATELY restore from backup
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 \
  < ~/backups/v103_to_v14_db_*.sql

# Contact developer with error details
```

---

## ⏮️ Rollback to v1.0.3

**If upgrade fails and issues cannot be resolved:**

### Full Rollback (10 minutes)

```bash
cd ~/d646a74eb9.nxcli.io/html/

# 1. Restore database
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 \
  < ~/backups/v103_to_v14_db_*.sql

# 2. Remove v1.4 files
rm -rf admin/ includes/ pages/ assets/ database/ config/ index.php

# 3. Restore v1.0.3 files
tar -xzf ~/backups/v103_to_v14_files_*.tar.gz

# 4. Verify rollback
curl -I https://cforkids.org/sponsor/
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 \
  -e "DESCRIBE families;"

echo "✅ Rolled back to v1.0.3"
```

### Verify Rollback Success

```bash
# 1. Homepage loads
curl https://cforkids.org/sponsor/ | grep -i "christmas"

# 2. Database intact
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 \
  -e "SELECT COUNT(*) FROM children; SELECT COUNT(*) FROM sponsorships;"

# 3. family_name column restored
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 \
  -e "DESCRIBE families;" | grep family_name
```

---

## 📊 Upgrade Success Checklist

Copy and complete:

```
✅ v1.0.3 → v1.4 Upgrade Checklist
================================

PRE-UPGRADE
-----------
[ ] Database backed up
[ ] Files backed up
[ ] Current state documented
[ ] Maintenance window scheduled

UPGRADE
-------
[ ] v1.4 package uploaded
[ ] Database schema migrated (family_name removed)
[ ] v1.4 files extracted
[ ] Permissions set correctly
[ ] Caches cleared

POST-UPGRADE VERIFICATION
-------------------------
[ ] Homepage loads (200 status)
[ ] Alpine.js v3.14.1 loaded
[ ] Instant search works
[ ] FAQ accordion works
[ ] CSV validation works
[ ] No names displayed (privacy)
[ ] Generic avatars display
[ ] All children preserved
[ ] All sponsorships preserved
[ ] No PHP errors in logs
[ ] No console errors in browser

DATA INTEGRITY
--------------
[ ] Family count matches pre-upgrade: _____
[ ] Children count matches pre-upgrade: _____
[ ] Sponsorships count matches pre-upgrade: _____
[ ] Sponsored children count matches: _____

FINAL APPROVAL
--------------
[ ] Admin tested all features
[ ] Sponsor tested sponsorship flow
[ ] Performance acceptable
[ ] No critical issues

Upgrade Status: [ ] SUCCESS / [ ] ROLLBACK

Completed by: _______________
Date/Time: _______________
Duration: _______________
```

---

## 📈 What's New in v1.4

### For Admins:

1. **Instant Search** - Find children instantly without page reloads
2. **Better CSV Import** - See errors before uploading
3. **Privacy Compliant** - No names displayed, only family codes
4. **Professional Look** - Clean design with generic avatars

### For Sponsors:

1. **Faster Browsing** - Search and filter children in real-time
2. **FAQ Help** - Expandable FAQ on How to Apply page
3. **Better Privacy** - Only see family codes (e.g., "175A")

### Technical:

1. **Alpine.js 3.14.1** - Modern, lightweight JavaScript framework
2. **Schema Cleanup** - Removed PII (family_name column)
3. **18 Files Updated** - All SQL queries updated for privacy
4. **8 Generic Avatars** - Age/gender-appropriate placeholders

---

## 📞 Support

**If you need help during upgrade:**

1. **Check error logs:** `tail -50 ~/logs/error_log`
2. **Review this document** - Solutions for common issues above
3. **Rollback if needed** - Use rollback procedure (takes 10 min)
4. **Document the issue:**
   - What step failed
   - Error messages
   - Current state of database/files

**Emergency Contacts:**
- Developer: Claude Code
- Hosting: Hostinger Support
- Database: MySQL 8.0+

---

## 🎉 Post-Upgrade

**After successful upgrade:**

1. **Monitor for 24 hours** - Check logs, user feedback, performance
2. **Update documentation** - Note upgrade date and any issues
3. **Communicate changes** - Notify admins of new features
4. **Plan next steps** - Consider v1.5 features

---

**Document Version:** 1.0
**Last Updated:** October 11, 2025
**Upgrade Path:** v1.0.3 → v1.4.0

**Ready to upgrade? Follow steps 1-7 above!** 🚀
