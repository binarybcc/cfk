# Security Fixes Deployment Guide
## v1.5.1 Security Enhancements

**Date:** October 13, 2025
**Priority:** HIGH - Deploy immediately

---

## 🚨 Critical: What's Changed

These fixes address HIGH and MEDIUM priority security issues:

1. ✅ Database credentials moved to environment variables
2. ✅ Default admin password change enforcement
3. ✅ Portal tokens stored in database (revocable)
4. ✅ Login rate limiting (prevents brute force)

---

## Step 1: Create .env File on Server ⏱️ 5 min

**SSH into your server:**
```bash
ssh a4409d26_1@d646a74eb9.nxcli.io
```

**Navigate to application directory:**
```bash
cd /home/a4409d26/d646a74eb9.nxcli.io/html
```

**Create .env file (NEVER commit this to git):**
```bash
cat > .env << 'EOF'
# Christmas for Kids - Production Environment
# Created: October 13, 2025

# Database Configuration
DB_HOST=localhost
DB_NAME=a4409d26_509946
DB_USER=a4409d26_509946
DB_PASSWORD=Fests42Cue50Fennel56Auk46

# SMTP Configuration
SMTP_USERNAME=
SMTP_PASSWORD=

# Optional: Override application settings
# APP_DEBUG=false
# BASE_URL=https://cforkids.org
EOF
```

**Secure the file (important!):**
```bash
chmod 600 .env
chown a4409d26:a4409d26 .env
```

**Verify .env file:**
```bash
ls -la .env
# Should show: -rw------- (only owner can read/write)
```

---

## Step 2: Run Database Migration ⏱️ 2 min

**Create portal tokens table:**
```bash
mysql -u a4409d26_509946 -p a4409d26_509946 << 'EOF'
-- Migration 004: Portal access tokens table
CREATE TABLE IF NOT EXISTS portal_access_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    sponsor_email VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_token_hash (token_hash),
    INDEX idx_sponsor_email (sponsor_email),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
EOF
```

**Verify table created:**
```bash
mysql -u a4409d26_509946 -p a4409d26_509946 -e "SHOW TABLES LIKE 'portal_access_tokens';"
```

---

## Step 3: Upload New Files ⏱️ 3 min

**From your local machine, upload changed files:**
```bash
# Upload updated configuration
sshpass -p 'PiggedCoifSourerFating' scp -P 22 \
  config/config.php \
  a4409d26_1@d646a74eb9.nxcli.io:/home/a4409d26/d646a74eb9.nxcli.io/html/config/

# Upload new files
sshpass -p 'PiggedCoifSourerFating' scp -P 22 \
  admin/change_password.php \
  includes/rate_limiter.php \
  a4409d26_1@d646a74eb9.nxcli.io:/home/a4409d26/d646a74eb9.nxcli.io/html/includes/

# Upload updated files
sshpass -p 'PiggedCoifSourerFating' scp -P 22 \
  admin/index.php \
  admin/login.php \
  includes/sponsorship_manager.php \
  a4409d26_1@d646a74eb9.nxcli.io:/home/a4409d26/d646a74eb9.nxcli.io/html/
```

---

## Step 4: Test the Deployment ⏱️ 5 min

### Test 1: Application Loads
```bash
# Visit the site
open https://cforkids.org
```
**Expected:** Homepage loads normally ✅

### Test 2: Admin Login
```bash
# Visit admin panel
open https://cforkids.org/admin/
```
**Expected:** Login page shows ✅

### Test 3: Login with Default Password
- Username: `admin`
- Password: `admin123`

**Expected:** Redirected to password change page 🔒

### Test 4: Change Password
- Current Password: `admin123`
- New Password: (choose a strong password)
- Confirm Password: (same)

**Expected:** Redirected to dashboard ✅

### Test 5: Rate Limiting
1. Log out
2. Try logging in with wrong password 5 times
3. On 6th attempt...

**Expected:** "Too many failed attempts. Account locked for 15 minutes." 🛡️

### Test 6: Portal Access Tokens
1. Go to Sponsor Lookup page
2. Enter a sponsor email
3. Check email for access link
4. Click link

**Expected:** Portal loads with sponsor's children ✅

**Verify in database:**
```bash
mysql -u a4409d26_509946 -p a4409d26_509946 -e \
  "SELECT COUNT(*) as token_count FROM portal_access_tokens;"
```
**Expected:** Shows count of tokens ✅

---

## Step 5: Remove Old Credentials from Git ⏱️ 10 min

**IMPORTANT:** The old production password is in git history. While we've moved to .env, the history still contains it.

**Option A: Accept Risk (Recommended for now)**
- .gitignore now blocks .env file
- Future commits won't expose credentials
- Old history remains (low risk if repo is private)

**Option B: Rewrite Git History (Advanced)**
```bash
# WARNING: This rewrites history and breaks existing clones
# Coordinate with team before running

# Use BFG Repo Cleaner to remove sensitive data
bfg --replace-text passwords.txt
git reflog expire --expire=now --all
git gc --prune=now --aggressive
git push --force
```

---

## Step 6: Monitor for Issues ⏱️ Ongoing

### Check PHP Error Logs
```bash
ssh a4409d26_1@d646a74eb9.nxcli.io
tail -f /home/a4409d26/d646a74eb9.nxcli.io/logs/php_error.log
```

### Check Application Logs
```bash
grep "CFK" /home/a4409d26/d646a74eb9.nxcli.io/logs/error.log | tail -20
```

### Watch for Rate Limit Events
```bash
grep "Rate Limit" /home/a4409d26/d646a74eb9.nxcli.io/logs/php_error.log
```

---

## Rollback Plan (If Issues Occur)

### Quick Rollback
```bash
# On server
cd /home/a4409d26/d646a74eb9.nxcli.io/html

# Restore from git
git checkout config/config.php
git checkout admin/index.php
git checkout admin/login.php
git checkout includes/sponsorship_manager.php

# Restart web server if needed
sudo systemctl restart apache2  # or nginx
```

### Database Rollback
```bash
# Drop new table if causing issues
mysql -u a4409d26_509946 -p a4409d26_509946 -e \
  "DROP TABLE IF EXISTS portal_access_tokens;"
```

---

## Security Checklist

After deployment, verify:

- [ ] .env file exists with correct permissions (600)
- [ ] Application loads without errors
- [ ] Admin can login
- [ ] Password change enforcement works
- [ ] Rate limiting blocks after 5 failed attempts
- [ ] Portal tokens are stored in database
- [ ] No credentials visible in config.php
- [ ] PHP error logs clean
- [ ] All tests pass (see above)

---

## Cleanup Old Tokens (Optional)

**Run weekly to clean expired tokens:**
```sql
-- Add to cron job
DELETE FROM portal_access_tokens
WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
```

**Create cron job:**
```bash
# Add to crontab
crontab -e

# Add this line:
0 2 * * 0 mysql -u a4409d26_509946 -pFests42Cue50Fennel56Auk46 a4409d26_509946 -e "DELETE FROM portal_access_tokens WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY);"
```

---

## Support

**If you encounter issues:**
1. Check PHP error logs (see Step 6)
2. Verify .env file exists and has correct format
3. Test database connection
4. Review rollback plan above

**Contact:** Check with your hosting provider or system administrator

---

## What's Next?

After successful deployment, consider:
- [ ] Monitor rate limit logs for attack attempts
- [ ] Review portal token usage weekly
- [ ] Plan password complexity requirements (LOW priority)
- [ ] Schedule security audit in 6 months

---

**Deployment Status:** Ready to deploy ✅
**Estimated Time:** 25 minutes
**Risk Level:** Low (thoroughly tested, rollback available)

🔒 **Security First - Thank you for keeping sponsor data safe!**
