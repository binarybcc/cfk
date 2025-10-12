# 🚀 Deploy v1.4 to Production - Quick Start

**Current Status:** v1.0.3 deployed on production
**Target:** Upgrade to v1.4 (Alpine.js + Privacy Compliance)
**Estimated Time:** 10-15 minutes
**Downtime:** 5-10 minutes

---

## 🔐 Server Credentials & Configuration

**SSH Connection:**
```bash
# Credentials (from docs/cfkssh.txt)
Host: d646a74eb9.nxcli.io
Port: 22
User: a4409d26_1
Pass: PiggedCoifSourerFating

# SSH Command (sshpass required)
sshpass -p "PiggedCoifSourerFating" ssh -o StrictHostKeyChecking=no a4409d26_1@d646a74eb9.nxcli.io

# SCP Upload Command
sshpass -p "PiggedCoifSourerFating" scp -o StrictHostKeyChecking=no <file> a4409d26_1@d646a74eb9.nxcli.io:~/
```

**Site Configuration:**
```bash
Web Root: ~/d646a74eb9.nxcli.io/html/
Base URL: https://cforkids.org/  # ⚠️ NO /sponsor/ suffix!
```

**Database:**
```bash
DB User: a4409d26_509946
DB Pass: Fests42Cue50Fennel56Auk46
DB Name: a4409d26_509946
```

**Important Notes:**
- ⚠️ **NEVER use `/sponsor/` in base URLs** - Site is at root
- Config files: `config/config.php` and `config/config.production.php`
- Both must have: `'base_url' => 'https://cforkids.org/'`

---

## 📦 What's Ready

✅ **Deployment package:** `cfk-v1.4-production.tar.gz` (1.4 MB)
✅ **Upgrade guide:** `docs/UPGRADE-v1.0.3-to-v1.4.md`
✅ **Full deployment docs:** `docs/V1.4-PRODUCTION-DEPLOYMENT.md`
✅ **Testing guide:** `docs/QUICK-TEST-GUIDE.md`

---

## ⚡ Quick Deploy (3 Steps)

### Step 1: Upload Package (2 min)

```bash
# From your local machine (in cfk-standalone directory):
scp cfk-v1.4-production.tar.gz a4409d26_1@d646a74eb9.nxcli.io:~/
```

### Step 2: Run Upgrade Script (5 min)

```bash
# SSH to server
ssh a4409d26_1@d646a74eb9.nxcli.io

# Create upgrade script
cat > upgrade-to-v1.4.sh << 'UPGRADEEOF'
#!/bin/bash
set -e
echo "🚀 CFK v1.0.3 → v1.4 Upgrade"
echo ""

# Configuration
DB_USER="a4409d26_509946"
DB_PASS="Fests42Cue50Fennel56Auk46"
DB_NAME="a4409d26_509946"
WEB_ROOT="$HOME/d646a74eb9.nxcli.io/html"

cd "$WEB_ROOT"

# Step 1: Backup
echo "1️⃣ Creating backups..."
mkdir -p ~/backups
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > ~/backups/pre-v14-db-$(date +%Y%m%d_%H%M%S).sql
tar -czf ~/backups/pre-v14-files-$(date +%Y%m%d_%H%M%S).tar.gz --exclude='uploads' admin/ includes/ pages/ assets/ database/ config/ index.php
echo "✅ Backups created in ~/backups/"

# Step 2: Database migration
echo ""
echo "2️⃣ Migrating database schema..."
mysql -u $DB_USER -p$DB_PASS $DB_NAME << SQLEOF
ALTER TABLE families DROP COLUMN IF EXISTS family_name;
SELECT 'Schema updated' AS status;
SQLEOF
echo "✅ Database migrated (family_name removed)"

# Step 3: Deploy files
echo ""
echo "3️⃣ Deploying v1.4 files..."
tar -xzf ~/cfk-v1.4-production.tar.gz
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
echo "✅ Files deployed"

# Step 4: Verify
echo ""
echo "4️⃣ Verifying deployment..."
grep -q "alpinejs@3.14.1" includes/header.php && echo "✅ Alpine.js integrated" || echo "❌ Alpine.js missing"
mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "DESCRIBE families;" | grep -q family_name && echo "❌ Migration incomplete" || echo "✅ Schema correct"

echo ""
echo "=========================================="
echo "✅ Upgrade Complete!"
echo "=========================================="
echo ""
echo "Next: Test in browser"
echo "URL: https://cforkids.org/sponsor/?page=children"
echo "Console: Alpine.version should return '3.14.1'"
UPGRADEEOF

chmod +x upgrade-to-v1.4.sh

# Run upgrade
./upgrade-to-v1.4.sh
```

### Step 3: Test (3 min)

**Browser Tests:**

1. Visit: https://cforkids.org/sponsor/?page=children
   - Open console (F12), type: `Alpine.version`
   - Should return: `"3.14.1"`
   - Try search box → Should filter instantly
   - Try gender filter → Should update instantly

2. Visit: https://cforkids.org/sponsor/?page=how_to_apply
   - Scroll to FAQ
   - Click questions → Should expand/collapse smoothly

3. Visit: https://cforkids.org/sponsor/admin/import_csv.php
   - Click "Analyze CSV" without file → Should show error

**Database Test:**

```bash
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 << 'SQLEOF'
SELECT
    CONCAT(f.family_number, c.child_letter) as display_id,
    c.age,
    c.status
FROM children c
INNER JOIN families f ON c.family_id = f.id
LIMIT 5;
SQLEOF
```

---

## ⚠️ If Something Goes Wrong

### Quick Rollback (2 min)

```bash
cd ~/d646a74eb9.nxcli.io/html/

# Restore database
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 \
  < ~/backups/pre-v14-db-*.sql

# Restore files
rm -rf admin/ includes/ pages/ assets/ database/ config/ index.php
tar -xzf ~/backups/pre-v14-files-*.tar.gz

echo "✅ Rolled back to v1.0.3"
```

---

## 📋 Success Checklist

```
[ ] Package uploaded to server
[ ] Upgrade script ran successfully
[ ] Alpine.js version shows 3.14.1 in console
[ ] Instant search works on children page
[ ] FAQ accordion works on how to apply page
[ ] CSV validation works in admin
[ ] Only family codes displayed (no names)
[ ] All children count matches pre-upgrade
[ ] All sponsorships preserved
```

---

## 📚 Detailed Documentation

**For complete instructions:**
- **Upgrade Guide:** `docs/UPGRADE-v1.0.3-to-v1.4.md` (step-by-step from v1.0.3)
- **Full Deployment:** `docs/V1.4-PRODUCTION-DEPLOYMENT.md` (comprehensive guide)
- **Testing Guide:** `docs/QUICK-TEST-GUIDE.md` (15-minute test plan)
- **Feature Docs:** `docs/V1.4-ALPINE-JS-COMPLETE.md` (what's new)

---

## 🎯 What Gets Deployed

### New Features:
- ✅ Alpine.js 3.14.1 (instant search, FAQ accordion, CSV validation)
- ✅ Privacy compliance (no names, only family codes)
- ✅ Generic age/gender avatars
- ✅ Mobile-optimized UI

### Database Changes:
- ❌ Remove `families.family_name` column
- ✅ All data preserved (children, sponsorships, etc.)

### Files Updated:
- 18 PHP files modified
- 8 avatar images added
- 1 config file added

---

## 📞 Need Help?

1. **Check error logs:** `tail -50 ~/logs/error_log`
2. **Review upgrade guide:** `docs/UPGRADE-v1.0.3-to-v1.4.md`
3. **Rollback if needed:** See "Quick Rollback" above
4. **Contact support:** Document the issue and error messages

---

**Ready? Run Step 1 now!** ⬆️
