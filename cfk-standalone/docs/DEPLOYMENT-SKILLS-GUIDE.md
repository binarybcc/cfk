# Deployment Skills Guide

**Created:** October 30, 2025
**Purpose:** Safe, automated deployment to staging and production environments

---

## 🎯 Overview

This project uses **Claude Code deployment skills** to safely deploy code to staging and production servers. These skills prevent accidental deployments to the wrong environment and eliminate the need to remember credentials.

---

## 🔐 Environment Configuration

### Three Separate .env Files:

1. **`.env`** - Local Docker development
   - Database credentials for local Docker
   - No server SSH credentials (removed for safety)
   - Used for: Local development and testing

2. **`.env.production`** - Production deployment credentials
   - SSH credentials for production server (cforkids.org)
   - Loaded automatically by `/deploy-production` skill
   - **High security:** Protected by .gitignore, never committed

3. **`.env.staging`** - Staging deployment credentials
   - SSH credentials for staging server (cfkstaging.org)
   - Loaded automatically by `/deploy-staging` skill
   - **Medium security:** Protected by .gitignore, never committed

### Security Features:

- ✅ All `.env*` files blocked by `.gitignore`
- ✅ Credentials never committed to repository
- ✅ Each environment isolated (can't mix credentials)
- ✅ Skills load correct credentials automatically
- ✅ Production requires explicit confirmation

---

## 🚀 Deployment Skills

### `/deploy-staging` - Deploy to Staging

**Purpose:** Test changes in staging environment before production

**Usage:**
```
/deploy-staging
```

**What it does:**
1. Loads `.env.staging` credentials automatically
2. Verifies target is staging server (10ce79bd48.nxcli.io)
3. Shows current branch and recent commits
4. Deploys without confirmation (staging is safe)
5. Verifies deployment success
6. Provides testing checklist
7. Logs deployment to `deployment-log.txt`

**When to use:**
- Testing new features before production
- Validating bug fixes
- Verifying database migrations
- Testing any code changes
- **Use liberally** - staging is meant for testing!

**Risk Level:** 🟢 LOW - Safe testing environment

---

### `/deploy-production` - Deploy to Production

**Purpose:** Deploy tested code to live production site (cforkids.org)

**Usage:**
```
/deploy-production
```

**What it does:**
1. Loads `.env.production` credentials automatically
2. Verifies target is production server (d646a74eb9.nxcli.io)
3. Shows **WARNING** about deploying to production
4. **Requires user to type:** "DEPLOY TO PRODUCTION"
5. Shows current branch and recent commits
6. Asks for final confirmation
7. Creates backup reference point
8. Deploys files via SCP
9. Verifies deployment success (PHP syntax check)
10. Provides manual verification checklist
11. Logs deployment to `deployment-log.txt`

**When to use:**
- After thorough testing in staging
- When deploying critical bug fixes
- For scheduled feature releases
- **Use sparingly** - production should be stable

**Risk Level:** 🔴 HIGH - Live production site

---

## 📋 Deployment Workflow

### Standard Workflow (Feature Development):

```
1. Develop locally
   └─ Test in Docker (http://localhost:8082)

2. Commit to git
   └─ Push to GitHub

3. Deploy to staging
   └─ /deploy-staging

4. Test in staging
   └─ Visit https://cfkstaging.org
   └─ Test all affected features
   └─ Check error logs

5. If tests pass → Deploy to production
   └─ /deploy-production
   └─ Type "DEPLOY TO PRODUCTION"
   └─ Confirm again

6. Verify production
   └─ Visit https://cforkids.org
   └─ Test critical functionality
   └─ Monitor for issues
```

### Quick Fix Workflow:

```
1. Fix bug locally
   └─ Test in Docker

2. Deploy to staging
   └─ /deploy-staging

3. Test fix in staging
   └─ Verify bug is fixed
   └─ No regressions

4. Deploy to production immediately
   └─ /deploy-production
```

---

## 🛡️ Safety Features

### Production Deployment Safeguards:

1. **Credential Isolation**
   - Production credentials only in `.env.production`
   - Can't accidentally use staging credentials

2. **Dual Confirmation**
   - Must type exact phrase: "DEPLOY TO PRODUCTION"
   - Must confirm again: "yes" to proceed

3. **Pre-Deployment Checks**
   - Shows current branch
   - Shows recent commits
   - Shows target server
   - Lists pre-deployment checklist

4. **Backup Reference**
   - Records current state before deployment
   - Provides rollback instructions

5. **Post-Deployment Verification**
   - PHP syntax check on deployed files
   - Manual verification checklist
   - Error log monitoring

### Staging Deployment Simplicity:

1. **No Confirmation Needed**
   - Staging is meant for testing
   - Safe to deploy frequently

2. **Quick Iteration**
   - Deploy → Test → Fix → Repeat
   - No barriers to testing

3. **Comprehensive Testing Checklist**
   - Guides thorough testing
   - Ensures nothing is missed

---

## 🔧 Manual Deployment (If Needed)

If you need to deploy manually (skills not available):

### Staging:
```bash
# Load staging credentials
source .env.staging

# Deploy via SCP
sshpass -p "$SSH_PASSWORD" scp -P $SSH_PORT -r \
  admin/ pages/ includes/ src/ config/ assets/ index.php \
  ${SSH_USER}@${SSH_HOST}:${SSH_REMOTE_PATH}/
```

### Production:
```bash
# Load production credentials
source .env.production

# Deploy via SCP
sshpass -p "$SSH_PASSWORD" scp -P $SSH_PORT -r \
  admin/ pages/ includes/ src/ config/ assets/ index.php \
  ${SSH_USER}@${SSH_HOST}:${SSH_REMOTE_PATH}/
```

**Note:** Manual deployment skips safety checks. Use skills when possible.

---

## 📂 What Gets Deployed

### Typical Full Deployment:
- `admin/` - Admin interface files
- `pages/` - Public-facing pages
- `includes/` - Legacy includes (being phased out)
- `src/` - Modern PSR-4 classes
- `config/` - Configuration files
- `assets/` - CSS, JavaScript, images
- `index.php` - Main entry point
- `.htaccess` - Apache configuration (if changed)

### Never Deploy:
- ❌ `.env*` files (exist on server already)
- ❌ `vendor/` (run composer install on server)
- ❌ `.git/` (not needed on server)
- ❌ `tests/` (test files)
- ❌ `docs/` (documentation - unless needed)
- ❌ `node_modules/` (if any)

---

## 🚨 Emergency Rollback

### If Production Deployment Causes Issues:

**Option 1: Redeploy Previous Version**
```bash
# Switch to previous stable branch
git checkout v1.7.3-production-hardening

# Deploy to production
/deploy-production
```

**Option 2: Quick Fix**
```bash
# Fix the issue locally
# Test in staging
/deploy-staging

# Deploy fix to production
/deploy-production
```

---

## 📝 Deployment Log

All deployments are logged to: `deployment-log.txt`

**Example log entries:**
```
[Thu Oct 30 10:45:23 EDT 2025] Deployed v1.8.1-cleanup to STAGING
[Thu Oct 30 14:22:15 EDT 2025] Deployed v1.8.1-cleanup to PRODUCTION
```

**Review log:**
```bash
cat deployment-log.txt
```

---

## ✅ Pre-Deployment Checklist

**Before deploying to production:**

- [ ] All changes tested locally in Docker
- [ ] Code committed to git
- [ ] Deployed to staging and tested thoroughly
- [ ] All tests passing (functional test suite)
- [ ] No PHP errors in staging logs
- [ ] Database migrations documented (if any)
- [ ] Rollback plan prepared
- [ ] Stakeholders notified (if major change)

---

## 🎓 Best Practices

### 1. Test First, Deploy Later
- Always test in staging before production
- Never deploy untested code to production
- Staging should mirror production

### 2. Deploy Incrementally
- Small, frequent deployments are safer
- Easier to identify issues
- Faster rollback if needed

### 3. Monitor After Deployment
- Check error logs immediately
- Test critical functionality
- Watch for user reports

### 4. Document Deployments
- Note what was deployed and why
- Document any manual steps taken
- Record any issues encountered

### 5. Use Skills, Not Manual Commands
- Skills prevent mistakes
- Skills include safety checks
- Skills log deployments automatically

---

## 🔍 Troubleshooting

### "Permission denied" error:
- Check SSH credentials in `.env.production` or `.env.staging`
- Verify SSH_PASSWORD is correct
- Verify SSH_USER has access

### "Connection refused" error:
- Check SSH_HOST and SSH_PORT
- Verify server is accessible
- Try manual SSH connection to test

### Files not uploading:
- Check file paths exist locally
- Verify SSH_REMOTE_PATH is correct
- Check disk space on server

### Deployment succeeds but site broken:
- Check PHP error logs on server
- Verify .env file exists on server (database credentials)
- Check file permissions on server
- Verify database migrations ran (if needed)

---

## 📞 Need Help?

**For deployment issues:**
1. Check this guide first
2. Review skill files in `.claude/commands/`
3. Check deployment-log.txt for history
4. Test manual SSH connection
5. Verify .env files have correct credentials

**Quick reference:**
- Staging URL: https://cfkstaging.org
- Production URL: https://cforkids.org
- Skills location: `.claude/commands/deploy-*.md`
- Credentials: `.env.production`, `.env.staging`

---

**Remember:** Staging is for testing. Production is sacred. Always test first, deploy carefully, verify thoroughly.
