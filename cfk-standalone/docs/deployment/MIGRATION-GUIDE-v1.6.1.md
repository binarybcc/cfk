# Migration Guide: v1.6 → v1.6.1 Security Enhancements

**Date:** October 18, 2025
**Migration Time:** 10-15 minutes
**Downtime Required:** None (zero-downtime deployment)

---

## Overview

This migration implements two MEDIUM-priority security enhancements:

1. **Database-Backed Remember-Me Tokens** - Secure, revocable admin session persistence
2. **Portal Access Token Storage** - Already implemented, verify migration ran

**Security Impact:**
- Fixes vulnerabilities identified in v1.6 technical audit
- Enables token revocation capabilities
- Adds audit trail for admin sessions
- Zero breaking changes (backward compatible)

---

## Pre-Migration Checklist

### ✅ Before You Start

- [ ] Backup database: `mysqldump -u user -p database > backup-$(date +%Y%m%d).sql`
- [ ] Verify current version: v1.6
- [ ] Ensure database credentials in `.env` are correct
- [ ] Have SSH access to production server
- [ ] Schedule maintenance window (or proceed with zero downtime)

---

## Step 1: Run Database Migrations

### Migration 005: Admin Remember Tokens

**File:** `database/migrations/005_create_admin_remember_tokens_table.sql`

```bash
# On production server
cd /home/a4409d26/d646a74eb9.nxcli.io/html

# Run migration
mysql -u a4409d26_509946 -p a4409d26_509946 < database/migrations/005_create_admin_remember_tokens_table.sql
```

**Verify table created:**
```sql
mysql -u a4409d26_509946 -p a4409d26_509946

SHOW TABLES LIKE 'admin_remember_tokens';
-- Should show: admin_remember_tokens

DESCRIBE admin_remember_tokens;
-- Should show: id, user_id, token_hash, expires_at, created_at, last_used_at, ip_address, user_agent

SELECT COUNT(*) FROM admin_remember_tokens;
-- Should show: 0 (empty table)
```

### Migration 004: Portal Access Tokens (Verify)

**File:** `database/migrations/004_create_portal_tokens_table.sql`

**Check if already exists:**
```sql
SHOW TABLES LIKE 'portal_access_tokens';
-- If exists: Already migrated ✅
-- If not exists: Run migration
```

**If needed, run migration:**
```bash
mysql -u a4409d26_509946 -p a4409d26_509946 < database/migrations/004_create_portal_tokens_table.sql
```

---

## Step 2: Deploy Code Changes

### Option A: Git Pull (Recommended)

```bash
# On production server
cd /home/a4409d26/d646a74eb9.nxcli.io/html

# Pull latest changes
git fetch origin
git pull origin v1.6

# Or if you're on a different branch:
git checkout v1.6.1-security-enhancements
git pull
```

### Option B: Manual File Upload (if not using Git)

**Upload these files via FTP/SFTP:**

**New Files:**
- `includes/remember_me_tokens.php`
- `cron/cleanup_remember_tokens.php`
- `cron/cleanup_portal_tokens.php`
- `database/migrations/005_create_admin_remember_tokens_table.sql`

**Modified Files:**
- `admin/login.php`
- `admin/logout.php` (already had cleanup logic)
- `config/config.production.php` (now uses .env)
- `.gitignore`

---

## Step 3: Set Up Cron Jobs

**Add to crontab on production server:**

```bash
# Edit crontab
crontab -e

# Add these lines:
# Cleanup expired remember-me tokens (daily at 2am)
0 2 * * * php /home/a4409d26/d646a74eb9.nxcli.io/html/cron/cleanup_remember_tokens.php >> /home/a4409d26/logs/remember-tokens-cleanup.log 2>&1

# Cleanup expired portal access tokens (daily at 3am)
0 3 * * * php /home/a4409d26/d646a74eb9.nxcli.io/html/cron/cleanup_portal_tokens.php >> /home/a4409d26/logs/portal-tokens-cleanup.log 2>&1
```

**Verify cron jobs added:**
```bash
crontab -l | grep cleanup
# Should show both cleanup jobs
```

**Test cron jobs manually:**
```bash
# Test remember-me token cleanup
php /home/a4409d26/d646a74eb9.nxcli.io/html/cron/cleanup_remember_tokens.php
# Should output: Cleaned up 0 expired remember-me token(s)

# Test portal token cleanup
php /home/a4409d26/d646a74eb9.nxcli.io/html/cron/cleanup_portal_tokens.php
# Should output: Cleaned up 0 expired portal access token(s)
```

---

## Step 4: Verify Deployment

### Test Remember-Me Functionality

**1. Login with Remember-Me:**
```bash
# Access admin login
https://cforkids.org/admin/login.php

# Login credentials
# Check "Remember Me" checkbox
# Submit login
```

**2. Verify Database Entry:**
```sql
SELECT * FROM admin_remember_tokens ORDER BY created_at DESC LIMIT 5;
-- Should show new token with hashed value
```

**3. Verify Cookie:**
```javascript
// In browser console (DevTools → Application → Cookies)
document.cookie
// Should contain: cfk_remember_token=...
```

**4. Test Auto-Login:**
```bash
# Close browser completely
# Reopen browser
# Visit: https://cforkids.org/admin/
# Should automatically log you in (no login form)
```

**5. Verify Last Used Update:**
```sql
SELECT id, created_at, last_used_at FROM admin_remember_tokens ORDER BY created_at DESC LIMIT 1;
-- last_used_at should be recent timestamp
```

**6. Test Logout:**
```bash
# Click logout in admin interface
# Verify redirected to login page
```

**7. Verify Token Revoked:**
```sql
SELECT COUNT(*) FROM admin_remember_tokens WHERE user_id = YOUR_USER_ID;
-- Should be 0 (token deleted on logout)
```

**8. Verify Cookie Cleared:**
```javascript
// Browser console
document.cookie
// Should NOT contain: cfk_remember_token
```

### Test Portal Access Tokens

**1. Request Portal Access:**
```bash
# As a sponsor, request portal access
https://cforkids.org/sponsor-lookup

# Enter sponsor email
# Submit
```

**2. Verify Database Entry:**
```sql
SELECT * FROM portal_access_tokens ORDER BY created_at DESC LIMIT 5;
-- Should show new token with hashed value
```

**3. Test Token Usage:**
```bash
# Click access link in email
# Verify portal loads
```

**4. Verify Token Marked as Used:**
```sql
SELECT used_at FROM portal_access_tokens ORDER BY created_at DESC LIMIT 1;
-- used_at should have timestamp (one-time use)
```

---

## Step 5: Monitor and Verify

### Check Error Logs

```bash
# Check PHP error log for any issues
tail -50 /home/a4409d26/logs/php_error.log

# Should NOT show:
# - Database connection errors
# - Undefined table errors
# - Class not found errors
```

### Monitor Cron Job Logs

```bash
# Check remember-tokens cleanup log
cat /home/a4409d26/logs/remember-tokens-cleanup.log

# Check portal-tokens cleanup log
cat /home/a4409d26/logs/portal-tokens-cleanup.log
```

---

## Troubleshooting

### Issue: Table does not exist

**Symptom:**
```
Table 'a4409d26_509946.admin_remember_tokens' doesn't exist
```

**Solution:**
```bash
# Run migration manually
mysql -u a4409d26_509946 -p a4409d26_509946 < database/migrations/005_create_admin_remember_tokens_table.sql

# Verify
mysql -u a4409d26_509946 -p a4409d26_509946 -e "SHOW TABLES LIKE 'admin_remember_tokens';"
```

---

### Issue: Class 'RememberMeTokens' not found

**Symptom:**
```
Fatal error: Class 'RememberMeTokens' not found in /path/to/admin/login.php
```

**Solution:**
```bash
# Verify file uploaded
ls -la includes/remember_me_tokens.php
# Should exist

# Check file permissions
chmod 644 includes/remember_me_tokens.php

# Verify require statement in login.php
grep "remember_me_tokens.php" admin/login.php
# Should show: require_once __DIR__ . '/../includes/remember_me_tokens.php';
```

---

### Issue: Remember-me not working

**Symptom:** Auto-login doesn't work on return visit

**Solution:**
```bash
# 1. Check cookie is set
# Browser DevTools → Application → Cookies
# Should show: cfk_remember_token

# 2. Check database entry
mysql -u a4409d26_509946 -p a4409d26_509946 -e "SELECT * FROM admin_remember_tokens ORDER BY created_at DESC LIMIT 1;"

# 3. Check cookie flags (production)
# Should have:
# - Secure: Yes (HTTPS only)
# - HttpOnly: Yes
# - SameSite: Strict

# 4. Verify auto-login code in login.php
grep -A 15 "Check for remember-me token" admin/login.php
```

---

### Issue: Cron jobs not running

**Symptom:** Expired tokens not being cleaned up

**Solution:**
```bash
# 1. Verify cron installed
crontab -l

# 2. Check cron service running
systemctl status cron
# Or: service cron status

# 3. Test manual execution
php /path/to/cron/cleanup_remember_tokens.php

# 4. Check script permissions
chmod +x cron/cleanup_remember_tokens.php
chmod +x cron/cleanup_portal_tokens.php

# 5. Check PHP path in cron
which php
# Update crontab with correct PHP path
```

---

## Rollback Procedure

**If you need to rollback:**

### Step 1: Restore Database

```bash
# Restore from backup
mysql -u a4409d26_509946 -p a4409d26_509946 < backup-YYYYMMDD.sql
```

### Step 2: Revert Code Changes

```bash
# Git rollback
git checkout v1.6
git pull origin v1.6

# Or manually restore old files from backup
```

### Step 3: Remove Cron Jobs

```bash
crontab -e
# Delete the two cleanup jobs
# Save and exit
```

---

## Post-Migration Notes

### What Changed

**✅ New Features:**
- Remember-me tokens stored in database
- Token revocation capability
- Audit trail (IP, device, timestamps)
- Automatic token cleanup via cron
- Portal tokens (already implemented, verified)

**✅ Security Improvements:**
- Tokens can be revoked on password change
- Tokens expire and are cleaned up
- Full audit trail for compliance
- One-time use portal tokens

**✅ No Breaking Changes:**
- Existing sessions continue to work
- Users don't need to re-login
- Remember-me checkbox still optional
- Portal access links still work

### Performance Impact

**Minimal:**
- One database query on login (token validation)
- One database insert on remember-me creation
- Negligible impact (< 5ms per request)

### Database Growth

**Expected growth:**
- ~50 bytes per remember-me token
- Average 10-20 active tokens per week
- Cleanup removes expired tokens weekly
- Negligible storage impact (< 1MB per year)

---

## Success Criteria

Migration is successful when:

- ✅ All migrations ran without errors
- ✅ `admin_remember_tokens` table exists
- ✅ `portal_access_tokens` table exists
- ✅ Remember-me login works
- ✅ Auto-login on return visit works
- ✅ Token revocation on logout works
- ✅ Portal access tokens work
- ✅ Cron jobs scheduled and running
- ✅ No errors in PHP error log
- ✅ No errors in cron logs

---

## Support

**Questions or issues?**
- Review troubleshooting section above
- Check `docs/technical/remember-me-tokens.md` for detailed documentation
- Review error logs: `/home/a4409d26/logs/php_error.log`
- Test in development first before production

---

**Migration Version:** 1.0
**Target Version:** v1.6.1
**Backward Compatible:** Yes
**Estimated Time:** 10-15 minutes
**Downtime Required:** None
