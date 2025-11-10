---
description: Deploy current branch to STAGING server (cfkstaging.org) for testing
---

# Staging Deployment Skill

**Target:** Staging server (cfkstaging.org)
**Risk Level:** LOW - Safe testing environment
**Purpose:** Test changes before production deployment

---

## Deployment Protocol

You are deploying to the **STAGING** server. This is a safe testing environment.

### Step 1: Load Staging Credentials

```bash
# Load staging environment variables
source .env.staging
```

**Verify loaded credentials:**
- SSH_HOST should be: 10ce79bd48.nxcli.io
- ENVIRONMENT should be: staging
- SITE_URL should be: https://cfkstaging.org

If any of these don't match, STOP and report an error.

### Step 2: Show Deployment Info

**Display to user:**

📋 **STAGING DEPLOYMENT**

**Current branch:** `[show current git branch]`
**Target server:** 10ce79bd48.nxcli.io (cfkstaging.org)
**Remote path:** /home/ac6c9a98/10ce79bd48.nxcli.io/html

**Recent commits being deployed:**
```bash
git log -3 --oneline
```

**No confirmation required for staging - deploying now...**

### Step 3: Execute Deployment

**Use sshpass with credentials from .env.staging:**

```bash
# Deploy files using SCP
sshpass -p "$SSH_PASSWORD" scp -P $SSH_PORT -r \
  admin/ pages/ includes/ src/ config/ assets/ \
  index.php \
  ${SSH_USER}@${SSH_HOST}:${SSH_REMOTE_PATH}/
```

**Deployment steps:**
1. Upload modified files to staging server
2. Preserve server-side .env file (don't overwrite)
3. Update all changed PHP files
4. Update assets if modified

**Example for specific files:**
```bash
# If deploying specific files only
sshpass -p "$SSH_PASSWORD" scp -P $SSH_PORT \
  admin/manage_children.php \
  ${SSH_USER}@${SSH_HOST}:${SSH_REMOTE_PATH}/admin/
```

### Step 4: Post-Deployment Verification

**Via SSH, verify deployment:**

```bash
# Check if files were uploaded successfully
sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT \
  ${SSH_USER}@${SSH_HOST} \
  "ls -la ${SSH_REMOTE_PATH}/ | head -20"

# Check PHP syntax on critical files
sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT \
  ${SSH_USER}@${SSH_HOST} \
  "php -l ${SSH_REMOTE_PATH}/index.php"

# Optional: Check for PHP errors in recent logs
sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT \
  ${SSH_USER}@${SSH_HOST} \
  "tail -20 ${SSH_REMOTE_PATH}/logs/error.log 2>/dev/null || echo 'No error log found'"
```

**Report to user:**
- ✅ Files uploaded successfully
- ✅ PHP syntax valid
- 🌐 Staging site: https://cfkstaging.org

### Step 5: Testing Instructions

**Tell the user:**

```
✅ Staging deployment complete!

**Testing checklist:**

1. **Basic functionality:**
   - Visit https://cfkstaging.org
   - Verify homepage loads
   - Check navigation works

2. **Test your changes:**
   - [List specific features that were changed]
   - Verify new functionality works as expected
   - Check for PHP errors or warnings

3. **Test critical workflows:**
   - Admin login (magic link)
   - Child management (if admin changes)
   - Browse/filter children
   - Sponsorship flow (if modified)
   - CSV import (if modified)

4. **Check error logs:**
   - Look for any PHP warnings or errors
   - Verify database queries work

**When testing is complete and successful:**
- If all tests pass → Safe to deploy to production (use /deploy-production)
- If issues found → Fix locally, redeploy to staging, test again

**Staging is meant for testing - deploy here as often as needed!**
```

### Step 6: Record Deployment

**Create deployment record:**

```bash
# Add entry to deployment log
echo "[$(date)] Deployed $(git branch --show-current) to STAGING" >> deployment-log.txt
```

---

## Typical Deployment Scenarios

### Scenario 1: Full Branch Deployment (Most Common)

**When:** Testing a new feature branch before production

**Files to deploy:**
```bash
sshpass -p "$SSH_PASSWORD" scp -P $SSH_PORT -r \
  admin/ \
  pages/ \
  includes/ \
  src/ \
  config/ \
  assets/ \
  index.php \
  ${SSH_USER}@${SSH_HOST}:${SSH_REMOTE_PATH}/
```

### Scenario 2: Quick Fix Testing

**When:** Testing a small bug fix

**Files to deploy:**
```bash
# Deploy only changed files
sshpass -p "$SSH_PASSWORD" scp -P $SSH_PORT \
  [specific-file.php] \
  ${SSH_USER}@${SSH_HOST}:${SSH_REMOTE_PATH}/[path]/
```

### Scenario 3: CSS/JS Updates

**When:** Testing frontend changes only

**Files to deploy:**
```bash
sshpass -p "$SSH_PASSWORD" scp -P $SSH_PORT -r \
  assets/css/ \
  assets/js/ \
  ${SSH_USER}@${SSH_HOST}:${SSH_REMOTE_PATH}/assets/
```

---

## Database Considerations

**Important:** Staging has its own database (.env on staging server).

**If database changes are needed:**

```bash
# SSH into staging server
sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT \
  ${SSH_USER}@${SSH_HOST}

# Once connected, run migrations or updates
cd ${SSH_REMOTE_PATH}
php migrations/your-migration.php
# or manually update via mysql command line
```

**Database migration workflow:**
1. Test migration on local Docker first
2. Deploy code to staging
3. SSH into staging and run migration
4. Test thoroughly on staging
5. Document migration steps for production

---

## Error Handling

**If deployment fails:**

1. Check the error message
2. Common issues:
   - Permission denied → Check SSH credentials in .env.staging
   - Connection refused → Verify SSH_HOST and SSH_PORT
   - File not found → Check file paths exist locally
3. Fix the issue locally
4. Redeploy to staging (safe to retry)

**If staging site breaks after deployment:**

1. Check staging error logs (see Step 4 above)
2. Identify the issue
3. Fix locally
4. Redeploy to staging
5. No need to "rollback" staging - just redeploy fixes

---

## Files NOT to Deploy

**Never deploy these to staging:**
- `.env` (exists on server already with staging credentials)
- `vendor/` (run composer install on server)
- `.git/` (not needed on server)
- `node_modules/` (if any)
- Test files from `tests/` (unless testing test infrastructure)

**Server-side files that exist in staging:**
- `.env` (staging database and SMTP credentials)
- `vendor/` (Composer dependencies installed on server)
- `logs/` (server-generated logs)

---

## Quick Command Reference

**Deploy full branch:**
```bash
/deploy-staging
```

**Deploy specific files:**
Modify Step 3 to upload only changed files

**Check staging logs:**
```bash
sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT \
  ${SSH_USER}@${SSH_HOST} \
  "tail -50 ${SSH_REMOTE_PATH}/logs/error.log"
```

**SSH into staging:**
```bash
sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT \
  ${SSH_USER}@${SSH_HOST}
```

---

## Staging Best Practices

1. ✅ **Deploy early and often** - Staging is for testing
2. ✅ **Test thoroughly** - Don't skip testing steps
3. ✅ **Check logs** - Look for warnings and errors
4. ✅ **Test all affected features** - Not just what you changed
5. ✅ **Document issues** - Note any problems found
6. ✅ **Verify before production** - Staging must work before production deployment

**Remember:** Staging exists to catch issues before production. Use it liberally!
