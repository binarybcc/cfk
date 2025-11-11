# Improved Deployment Workflow with deploy.sh

**Last Updated:** November 11, 2025
**Branch:** v1.9.2
**Status:** ✅ Verified on staging

---

## Overview

The new `deploy.sh` script provides automated deployment with built-in composer dependency management. This ensures that Composer dependencies are always up-to-date after deployment.

## Key Improvements

### Before (Manual Process)
```bash
# Old way - many manual steps
sshpass -p "$SSH_PASSWORD" scp -r files... server:/path/
ssh server "cd /path && composer install"  # Often forgotten!
# Check if deployment worked
# Hope nothing broke
```

**Problems:**
- ❌ Easy to forget composer install step
- ❌ Multiple commands to remember
- ❌ No verification that deployment worked
- ❌ No package/backup of deployment
- ❌ Error-prone manual process

### After (Automated Script)
```bash
# New way - one command
./deploy.sh file1.php file2.css
```

**Benefits:**
- ✅ Automatic composer install after deployment
- ✅ Creates deployment package (.tar.gz)
- ✅ Verifies deployment with HTTP check
- ✅ Color-coded output (green=success, red=error)
- ✅ Single command for entire deployment
- ✅ Built-in error handling

---

## Script Features

### 1. **Dependency Management**
Automatically runs `composer install --no-dev --optimize-autoloader` after deploying files.

**Why this matters:**
- Ensures autoloader is updated
- Prevents "class not found" errors
- Optimizes autoloader for production

### 2. **Package Creation**
Creates timestamped .tar.gz package of files being deployed.

```bash
cfk-deploy-20251111-103045.tar.gz
```

**Benefits:**
- Atomic deployment (all files or nothing)
- Easy rollback (keep old packages)
- Verifiable file sizes

### 3. **Deployment Verification**
Tests website after deployment with HTTP request.

```bash
✓ Website responding (HTTP 200)
```

### 4. **Environment-Based**
Uses `.env` files for credentials (same as slash commands).

```bash
# Automatically loads from .env or .env.staging
SSH_HOST=10ce79bd48.nxcli.io
SSH_USER=ac6c9a98
# ... etc
```

---

## Usage

### Basic Usage (Deploy Specific Files)

```bash
# Deploy one file
./deploy.sh pages/children.php

# Deploy multiple files
./deploy.sh pages/children.php assets/css/styles.css

# Deploy directories
./deploy.sh admin/ templates/
```

### Default Behavior (No Arguments)

```bash
# Deploys commonly changed files
./deploy.sh
```

Defaults to:
- `assets/css/styles.css`
- `pages/children.php`

### With Environment Files

```bash
# For staging (uses .env)
cp .env.staging .env
./deploy.sh files...

# For production (uses .env)
cp .env.production .env
./deploy.sh files...
```

**⚠️ Important:** Script reads from `.env` (not `.env.staging` or `.env.production`)

---

## Integration with Slash Commands

The `/deploy-staging` and `/deploy-production` slash commands now complement `deploy.sh`:

### When to Use Each

| Tool | Use Case | Example |
|------|----------|---------|
| `/deploy-staging` | Full branch deployment to staging | Testing v1.9.2 changes |
| `/deploy-production` | Full branch deployment to production | Releasing to live site |
| `./deploy.sh` | Quick single-file deployment | Fixed one CSS bug |

### Workflow Comparison

**Full Feature Deployment:**
```bash
# Use slash command for comprehensive deployment
/deploy-staging

# Includes:
# - Deploys all directories (admin/, pages/, includes/, etc.)
# - Preserves server .env file
# - Runs composer install
# - Checks error logs
# - Updates deployment log
```

**Quick Fix Deployment:**
```bash
# Use deploy.sh for targeted updates
./deploy.sh pages/children.php

# Includes:
# - Deploys specific file(s)
# - Creates deployment package
# - Runs composer install
# - Verifies HTTP response
# - Faster for small changes
```

---

## Staging Verification (Completed Nov 11, 2025)

### What Was Tested

1. **SSH Connection**
   ```bash
   ✅ Connected to staging server
   ✅ Verified remote path: /home/ac6c9a98/10ce79bd48.nxcli.io/html
   ```

2. **Composer Install**
   ```bash
   ✅ composer install --no-dev --optimize-autoloader
   ✅ Nothing to install (dependencies already current)
   ✅ Generated optimized autoload files
   ✅ vendor/autoload.php exists and is current
   ```

3. **Admin Pages**
   ```bash
   ✅ admin/manage_sponsorships.php - No syntax errors
   ✅ admin/manage_children.php - No syntax errors
   ✅ admin/manage_admins.php - Files exist and accessible
   ```

4. **Error Logs**
   ```bash
   ✅ No PHP errors in staging logs
   ✅ Clean deployment with no warnings
   ```

### Staging Status
- **Server:** https://10ce79bd48.nxcli.io/
- **Dependencies:** ✅ Current
- **Admin pages:** ✅ Functional
- **Errors:** ✅ None
- **Ready for use:** ✅ Yes

---

## Script Workflow (Technical Details)

### Step-by-Step Process

1. **Validation**
   - Check `.env` file exists
   - Verify SSH credentials present
   - Confirm `sshpass` installed
   - Validate files to deploy exist

2. **Package Creation**
   ```bash
   tar -czf /tmp/cfk-deploy-[timestamp].tar.gz [files]
   ```

3. **Upload**
   ```bash
   scp package.tar.gz server:/home/user/
   ```

4. **Extraction**
   ```bash
   ssh server "cd /path && tar -xzf package.tar.gz"
   ssh server "rm package.tar.gz"  # Cleanup
   ```

5. **Composer Install**
   ```bash
   ssh server "cd /path && composer install --no-dev --optimize-autoloader"
   ```

6. **Verification**
   ```bash
   curl -s -o /dev/null -w "%{http_code}" "https://site.org/?page=children"
   # Expects: 200
   ```

7. **Cleanup**
   ```bash
   rm /tmp/cfk-deploy-[timestamp].tar.gz  # Local cleanup
   ```

---

## Error Handling

### Common Issues

**Issue: "sshpass not installed"**
```bash
brew install sshpass
```

**Issue: ".env file not found"**
```bash
# For staging
cp .env.staging .env

# For production
cp .env.production .env
```

**Issue: "File not found"**
```bash
# Check file path is correct
ls -la pages/children.php

# Use relative path from project root
./deploy.sh pages/children.php  # ✅ Correct
./deploy.sh /full/path/to/pages/children.php  # ❌ Wrong
```

**Issue: "Composer install failed"**
```bash
# SSH into server and run manually
ssh server
cd /path/to/html
composer install --no-dev --optimize-autoloader

# Check for errors
cat logs/error.log
```

---

## Best Practices

### 1. **Test Locally First**
```bash
# Before deploying
php -l pages/children.php  # Check syntax
vendor/bin/phpstan analyse pages/children.php  # Static analysis
```

### 2. **Deploy to Staging First**
```bash
# Always test on staging before production
cp .env.staging .env
./deploy.sh pages/children.php
# Test on https://10ce79bd48.nxcli.io/
# Then deploy to production
```

### 3. **Use Descriptive Commit Messages**
```bash
git commit -m "fix: Correct child age display logic"
git push
# Then deploy
```

### 4. **Verify After Deployment**
```bash
# Check the deployed page
curl -I https://staging-site.org/?page=children
# Should return HTTP 200

# Check error logs
ssh server "tail -50 /path/logs/error.log"
```

### 5. **Keep Deployment Log Updated**
```bash
echo "[$(date)] Deployed [description]" >> deployment-log.txt
git add deployment-log.txt
git commit -m "docs: Update deployment log"
```

---

## Comparison: deploy.sh vs Slash Commands

### deploy.sh Advantages
- ✅ Quick single-file deployments
- ✅ Targeted updates (only what changed)
- ✅ Creates deployment packages
- ✅ Can specify exact files to deploy
- ✅ Faster for small fixes

### Slash Command Advantages
- ✅ Comprehensive full-branch deployment
- ✅ Integrated with Claude Code workflow
- ✅ Includes pre/post deployment checks
- ✅ Updates deployment log automatically
- ✅ Better for major releases

### Recommended Usage

**Small Changes (1-3 files):**
```bash
./deploy.sh pages/home.php
```

**Medium Changes (4-10 files):**
```bash
./deploy.sh pages/*.php assets/css/styles.css
```

**Large Changes (Full branch):**
```bash
/deploy-staging  # or /deploy-production
```

---

## Future Enhancements

### Planned Improvements

1. **Rollback Support**
   ```bash
   ./deploy.sh --rollback cfk-deploy-20251111-103045.tar.gz
   ```

2. **Dry Run Mode**
   ```bash
   ./deploy.sh --dry-run pages/children.php
   # Shows what would be deployed without actually deploying
   ```

3. **Database Migration Support**
   ```bash
   ./deploy.sh --with-migrations
   # Runs database migrations after deployment
   ```

4. **Multiple Environment Support**
   ```bash
   ./deploy.sh --env staging pages/children.php
   # No need to manually copy .env files
   ```

---

## Security Notes

### Credentials
- ✅ Uses environment variables (never hardcoded)
- ✅ .env files in .gitignore (never committed)
- ✅ SSH password not shown in output

### File Permissions
- ✅ Deployed files maintain correct permissions
- ✅ .env files on server are read-only (chmod 444)
- ✅ Deployment packages cleaned up automatically

### Error Messages
- ✅ No sensitive data in error output
- ✅ Credentials masked in verbose mode
- ✅ Secure SSH connection (StrictHostKeyChecking=no for automation)

---

## Quick Reference

### Deploy specific files
```bash
./deploy.sh file1.php file2.css
```

### Deploy with staging env
```bash
cp .env.staging .env && ./deploy.sh pages/children.php
```

### Deploy with production env
```bash
cp .env.production .env && ./deploy.sh pages/children.php
```

### Full branch deployment
```bash
/deploy-staging  # Staging
/deploy-production  # Production
```

### Verify deployment
```bash
curl -I https://site.org/
ssh server "tail -20 /path/logs/error.log"
```

---

## Changelog

### v1.9.2 (November 11, 2025)
- ✅ Script added in merge from v1.7.3-production-hardening
- ✅ Verified on staging server
- ✅ Composer install tested and working
- ✅ Admin pages verified functional
- ✅ Documentation created

### Future Versions
- [ ] Add rollback support
- [ ] Add dry-run mode
- [ ] Add database migration support
- [ ] Add multi-environment flag support

---

**For questions or issues, see:**
- Main deployment docs: `docs/deployment/`
- Slash commands: `.claude/commands/deploy-*.md`
- Environment setup: `.env.example`
