---
description: Deploy current branch to PRODUCTION server (cforkids.org) with safety checks
---

# Production Deployment Skill

**Target:** Production server (cforkids.org)
**Risk Level:** HIGH - This deploys to the live production site
**Requires:** User confirmation before deployment

---

## Deployment Protocol

You are deploying to the **PRODUCTION** server. Follow these steps exactly:

### Step 1: Load Production Credentials

```bash
# Load production environment variables
source .env.production
```

**Verify loaded credentials:**
- SSH_HOST should be: d646a74eb9.nxcli.io
- ENVIRONMENT should be: production
- SITE_URL should be: https://cforkids.org

If any of these don't match, STOP and report an error.

### Step 2: Pre-Deployment Safety Checks

**Ask the user to confirm:**

⚠️ **PRODUCTION DEPLOYMENT WARNING** ⚠️

You are about to deploy to the LIVE PRODUCTION site: **cforkids.org**

**Current branch:** `[show current git branch]`
**Target server:** d646a74eb9.nxcli.io
**Remote path:** /home/a4409d26/d646a74eb9.nxcli.io/html

**Before proceeding, verify:**
1. All tests are passing locally
2. Changes have been tested in staging
3. This is the correct branch to deploy
4. You have created a git tag for this release (if applicable)

**Type 'DEPLOY TO PRODUCTION' to confirm, or anything else to cancel.**

If the user doesn't type exactly "DEPLOY TO PRODUCTION", cancel and exit.

### Step 3: Show What Will Be Deployed

**Display:**
- Current git branch name
- Last 3 commits (git log -3 --oneline)
- Files that will be uploaded (if deploying specific files)

**Ask:** "Proceed with deployment? (yes/no)"

If not "yes", cancel and exit.

### Step 4: Create Backup Reference

**Show the user:**
```
📋 Backup Information:
Before deploying, note the current production state:
- Current production branch: [check remote if possible]
- Deployment timestamp: [current timestamp]
- Local branch being deployed: [current branch]

To rollback if needed, you can redeploy from the previous branch.
```

### Step 5: Execute Deployment

**Use sshpass with credentials from .env.production:**

```bash
# Deploy files using SCP
sshpass -p "$SSH_PASSWORD" scp -P $SSH_PORT -r \
  [files to deploy] \
  ${SSH_USER}@${SSH_HOST}:${SSH_REMOTE_PATH}/

# Example for full deployment:
sshpass -p "$SSH_PASSWORD" scp -P $SSH_PORT -r \
  admin/ pages/ includes/ src/ config/ assets/ \
  index.php \
  ${SSH_USER}@${SSH_HOST}:${SSH_REMOTE_PATH}/
```

**Note:** Adjust the file list based on what needs to be deployed.

### Step 6: Post-Deployment Verification

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
```

**Report to user:**
- ✅ Files uploaded successfully
- ✅ PHP syntax valid
- 🌐 Site URL: https://cforkids.org

### Step 7: Manual Verification Instructions

**Tell the user:**

```
✅ Production deployment complete!

Please verify manually:
1. Visit https://cforkids.org
2. Check the homepage loads correctly
3. Test critical functionality:
   - Browse children page
   - Admin login (if applicable)
   - Any new features deployed

If issues are found, use /rollback-production skill (if available) or redeploy previous version.
```

### Step 8: Record Deployment

**Create deployment record:**

```bash
# Add entry to deployment log
echo "[$(date)] Deployed $(git branch --show-current) to PRODUCTION" >> deployment-log.txt
```

---

## Error Handling

**If any step fails:**

1. STOP immediately
2. Report the exact error to the user
3. Do NOT continue to next steps
4. Provide rollback instructions
5. Show the command that failed

---

## Rollback Procedure

**If deployment causes issues:**

```bash
# Option 1: Redeploy previous branch
git checkout [previous-stable-branch]
# Then run /deploy-production again

# Option 2: Restore from backup (if available on server)
sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT \
  ${SSH_USER}@${SSH_HOST} \
  "cd ${SSH_REMOTE_PATH} && [restore commands]"
```

---

## Safety Reminders

- ⚠️ Always test in staging first
- ⚠️ Never deploy untested code to production
- ⚠️ Always verify credentials are for production before deploying
- ⚠️ Create git tags for production releases
- ⚠️ Keep deployment-log.txt updated

---

## Files Typically Deployed

**Full deployment (rare):**
- All directories: admin/, pages/, includes/, src/, config/, assets/
- index.php
- .htaccess (if changed)

**Partial deployment (common):**
- Specific files that changed
- Modified directories only

**Never deploy:**
- .env files (these exist on server already)
- vendor/ (composer install on server)
- tests/
- docs/ (unless documentation site)
- .git/

---

**Remember:** Production deployments should be rare and carefully planned. Most changes should go through staging first.
