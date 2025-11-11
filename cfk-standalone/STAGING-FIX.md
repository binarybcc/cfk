# Emergency Fix for Staging 500 Errors

**Date:** 2025-11-11
**Issue:** manage_sponsorships.php and other admin pages showing 500 errors
**Root Cause:** Missing Composer autoloader (vendor/ directory)

---

## The Problem

The application uses namespaced PHP classes (`CFK\Sponsorship\Manager`, `CFK\Database\Connection`, etc.) that require Composer's autoloader to work. The `vendor/` directory is:

- ✅ Excluded from git (proper PHP workflow)
- ❌ NOT being deployed to staging
- ❌ NOT being generated on staging via `composer install`

**Result:** Pages using namespaced classes fail with HTTP 500 errors.

---

## Immediate Fix (Manual)

**On Desktop Claude Code (with SSH access):**

```bash
# SSH into staging server
ssh your-user@10ce79bd48.nxcli.io

# Navigate to app directory
cd /path/to/cfk-standalone

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Verify
ls -la vendor/  # Should show autoload.php and other files
```

**Expected Result:**
- ✅ manage_sponsorships.php loads correctly
- ✅ manage_children.php saves work correctly
- ✅ All namespaced classes autoload properly

---

## Permanent Fix (Automated)

The `deploy.sh` script has been updated (commit 0f00484) to automatically run `composer install` after deploying files.

**Next deployment will:**
1. Upload files via scp
2. Extract on remote server
3. **Run `composer install --no-dev --optimize-autoloader`** ← NEW
4. Verify deployment

**No manual intervention needed after this fix is deployed.**

---

## Affected Pages

**Currently Broken (500 errors):**
- ❌ `/admin/manage_sponsorships.php` (uses `CFK\Sponsorship\Manager`)
- ❌ Any page using namespaced classes directly

**Partially Working:**
- ⚠️ `/admin/manage_children.php` (loads via `Database` alias, but save might fail)

**After Fix:**
- ✅ All pages will work correctly

---

## Technical Details

**In config/config.php (line 155):**
```php
if (!class_exists('CFK\Database\Connection')) {
    die('Composer autoloader not loaded. Run: composer install');
}
```

This check ensures dependencies are installed, but it also **blocks the app** if they're missing.

**Solution Options:**
1. ✅ **Run composer install on server** (recommended - automated in deploy.sh)
2. ❌ Deploy vendor/ directory (bloated, bad practice)
3. ❌ Remove namespace requirement (backward step, negates modernization)

---

## How to Prevent

**Always run after deployment:**
```bash
composer install --no-dev --optimize-autoloader
```

**Or use the updated deploy.sh script** which now does this automatically.

---

## Questions?

- What is Composer? → PHP dependency manager (like npm for Node.js)
- What is autoloader? → Maps class names to file paths automatically
- What is vendor/? → Directory where Composer installs dependencies
- Why exclude from git? → Standard PHP practice (like node_modules)

---

**Status:**
- 🔧 **Manual fix required NOW on staging**
- ✅ **Automated fix ready for future deployments**
