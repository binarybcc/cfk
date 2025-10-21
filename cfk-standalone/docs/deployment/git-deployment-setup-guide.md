# Git-Based Deployment Setup Guide

**Date**: October 21, 2025
**Current Method**: Manual SCP uploads (tar.gz files)
**Target Method**: Git pull deployments
**Priority**: HIGH (Professional standard practice)

---

## Why Switch to Git Deployments?

### **Current Problems with SCP Method**:
- ❌ Manual file selection (error-prone)
- ❌ No automatic tracking of deployed version
- ❌ Difficult to rollback changes
- ❌ Can't see deployment history
- ❌ No automatic backup of previous version
- ❌ Risk of partial deployments
- ❌ Hard to verify what's deployed

### **Benefits of Git Deployment**:
- ✅ One-command deployment: `git pull origin v1.7`
- ✅ Automatic tracking of deployed commit
- ✅ Easy rollback: `git reset --hard <commit>`
- ✅ Full deployment history via `git log`
- ✅ Automatic file integrity checks
- ✅ Can see exactly what changed: `git diff`
- ✅ No risk of forgetting files
- ✅ Industry standard practice

---

## Current Deployment Architecture

### **Local Development**:
```
/Users/user/Development/work/cfk/cfk/cfk-standalone/
├── .git/ (repository with all history)
├── Branch: v1.7
├── Remote: origin (GitHub)
└── 30+ commits ahead of origin
```

### **Production Server**:
```
/home/a4409d26/d646a74eb9.nxcli.io/html/
├── NO .git directory (not a git repo)
├── Files manually copied via SCP
├── No version tracking
└── No deployment history
```

---

## Setup Options

### **Option 1: Clone from GitHub (RECOMMENDED)**

**Pros**:
- Clean setup
- Official source of truth
- Easy to update
- Multiple developers can deploy

**Cons**:
- Need to push v1.7 branch to GitHub first
- Requires GitHub credentials on server

**Steps**:
1. Push v1.7 branch to GitHub
2. Backup current production files
3. Clone repository on production
4. Configure production `.env`
5. Set up git pull workflow

---

### **Option 2: Initialize Git on Production**

**Pros**:
- Keep existing files
- No GitHub dependency
- Works immediately

**Cons**:
- Production becomes source of truth (not ideal)
- Harder to manage multiple environments
- Manual sync with local required

**Not recommended** - breaks best practices

---

## Recommended Setup Process

### **Phase 1: Prepare GitHub Repository**

**Current Status**: v1.7 branch exists locally with 30 unpushed commits

**Action Required**:
```bash
# 1. Push v1.7 branch to GitHub
git push origin v1.7

# 2. Verify branch is visible on GitHub
# Visit: https://github.com/binarybcc/cfk/tree/v1.7

# 3. Create production release tag (optional but recommended)
git tag -a v1.7.0 -m "Production release v1.7 - Code quality improvements"
git push origin v1.7.0
```

---

### **Phase 2: Backup Current Production**

**Before making changes**, create safety backup:

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io

# Create backup directory
mkdir -p /home/a4409d26/backups/pre-git-deployment

# Backup current files (excluding sensitive data)
cd /home/a4409d26/d646a74eb9.nxcli.io
tar -czf /home/a4409d26/backups/pre-git-deployment/html-backup-$(date +%Y%m%d-%H%M%S).tar.gz \
  --exclude='html/vendor' \
  --exclude='html/.env' \
  html/

# Verify backup
ls -lh /home/a4409d26/backups/pre-git-deployment/
```

---

### **Phase 3: Set Up Git on Production**

**Method A: Fresh Clone (Clean Approach)**

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io

# Navigate to parent directory
cd /home/a4409d26/d646a74eb9.nxcli.io

# Rename current html directory
mv html html-old-$(date +%Y%m%d)

# Clone repository
git clone -b v1.7 https://github.com/binarybcc/cfk.git html

# Or use cfk-standalone subdirectory:
git clone -b v1.7 https://github.com/binarybcc/cfk.git cfk-repo
ln -s cfk-repo/cfk-standalone html

# Copy .env from backup
cp html-old-*/. env html/.env

# Install dependencies (if not in git)
cd html
composer install --no-dev --optimize-autoloader

# Set proper permissions
chmod 600 .env
chmod -R 755 assets/
chmod -R 755 admin/
```

---

**Method B: Initialize in Existing Directory (Migration Approach)**

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io
cd /home/a4409d26/d646a74eb9.nxcli.io/html

# Initialize git
git init

# Add GitHub remote
git remote add origin https://github.com/binarybcc/cfk.git

# Fetch branches
git fetch origin

# Checkout v1.7 branch (this will overwrite files)
git checkout -b v1.7 origin/v1.7

# Restore .env (git-ignored file)
# Make sure .env exists and is correct

# Verify git status
git status  # Should show "nothing to commit, working tree clean"
```

---

### **Phase 4: Configure Git Deployment Settings**

**Create deployment script** on production server:

```bash
# File: /home/a4409d26/d646a74eb9.nxcli.io/html/deploy.sh
#!/bin/bash

# CFK Git Deployment Script
# Usage: ./deploy.sh [branch-name]

set -e  # Exit on any error

BRANCH=${1:-v1.7}
HTML_DIR="/home/a4409d26/d646a74eb9.nxcli.io/html"
BACKUP_DIR="/home/a4409d26/backups/deployments"

echo "🚀 Starting deployment of branch: $BRANCH"
echo "================================================"

# Create backup
echo "📦 Creating backup..."
mkdir -p $BACKUP_DIR
BACKUP_FILE="$BACKUP_DIR/pre-deploy-$(date +%Y%m%d-%H%M%S).tar.gz"
tar -czf $BACKUP_FILE --exclude='.git' --exclude='vendor' --exclude='.env' $HTML_DIR/
echo "✅ Backup created: $BACKUP_FILE"

# Navigate to repository
cd $HTML_DIR

# Verify we're in a git repo
if [ ! -d .git ]; then
    echo "❌ Error: Not a git repository"
    exit 1
fi

# Stash any local changes (shouldn't be any)
echo "📝 Stashing local changes (if any)..."
git stash

# Fetch latest changes
echo "🔄 Fetching latest changes..."
git fetch origin

# Checkout and pull branch
echo "⬇️  Pulling branch: $BRANCH"
git checkout $BRANCH
git pull origin $BRANCH

# Run composer install (if composer.json changed)
if [ -f composer.json ]; then
    echo "📦 Installing composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Clear any PHP caches (if applicable)
# Add cache clearing commands here if needed

# Verify deployment
echo "✅ Deployment complete!"
echo "================================================"
git log -1 --oneline
echo "================================================"
echo ""
echo "Current branch: $(git rev-parse --abbrev-ref HEAD)"
echo "Latest commit: $(git rev-parse HEAD)"
echo "Deployed at: $(date)"
```

**Make script executable**:
```bash
chmod +x /home/a4409d26/d646a74eb9.nxcli.io/html/deploy.sh
```

---

### **Phase 5: Configure GitHub Authentication**

**Option A: Personal Access Token (Recommended)**

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io

# Configure git to use credential helper
git config --global credential.helper 'cache --timeout=3600'

# Or store credentials permanently (less secure)
git config --global credential.helper store

# On first pull, enter:
# Username: your-github-username
# Password: your-personal-access-token (not your actual password)
```

**Generate Personal Access Token**:
1. GitHub.com → Settings → Developer settings → Personal access tokens
2. Generate new token (classic)
3. Select scopes: `repo` (full control of private repos)
4. Copy token (shown only once!)
5. Use token as password when prompted

---

**Option B: SSH Keys (Most Secure)**

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io

# Generate SSH key
ssh-keygen -t ed25519 -C "production-server@cforkids.org"
# Save to: /home/a4409d26/.ssh/id_ed25519_github
# Passphrase: (optional, press Enter for none)

# Add to ssh-agent
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519_github

# Display public key
cat ~/.ssh/id_ed25519_github.pub
# Copy this entire output

# Add to GitHub:
# GitHub.com → Settings → SSH and GPG keys → New SSH key
# Paste the public key

# Configure git to use SSH
cd /home/a4409d26/d646a74eb9.nxcli.io/html
git remote set-url origin git@github.com:binarybcc/cfk.git

# Test connection
ssh -T git@github.com
# Should see: "Hi username! You've successfully authenticated..."
```

---

## Daily Deployment Workflow

### **Local Development** (Your Machine):

```bash
# 1. Make changes and commit
git add -A
git commit -m "feat: Add new feature"

# 2. Push to GitHub
git push origin v1.7

# Done! Changes are now on GitHub
```

---

### **Production Deployment** (Using Deployment Script):

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io

# Run deployment script
cd /home/a4409d26/d646a74eb9.nxcli.io/html
./deploy.sh v1.7

# That's it! Script handles:
# - Backup creation
# - Git pull
# - Composer install
# - Verification
```

---

### **Production Deployment** (Manual Commands):

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io
cd /home/a4409d26/d646a74eb9.nxcli.io/html

# Pull latest changes
git pull origin v1.7

# Install any new dependencies
composer install --no-dev --optimize-autoloader

# Verify deployment
git log -1
```

---

## Rollback Procedure

### **If Something Goes Wrong**:

**Method 1: Rollback to Previous Commit**

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io
cd /home/a4409d26/d646a74eb9.nxcli.io/html

# View recent commits
git log --oneline -10

# Rollback to specific commit
git reset --hard <commit-hash>

# Example:
git reset --hard 7b14206  # Rollback to deployment report commit
```

---

**Method 2: Restore from Backup**

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io

# List available backups
ls -lh /home/a4409d26/backups/deployments/

# Restore backup
cd /home/a4409d26/d646a74eb9.nxcli.io
rm -rf html
tar -xzf /home/a4409d26/backups/deployments/pre-deploy-YYYYMMDD-HHMMSS.tar.gz
```

---

## Git Ignore Configuration

**Ensure `.gitignore` is correct**:

```gitignore
# Environment
.env
.env.local
.env.production

# Dependencies
/vendor/

# Composer
composer.lock (optional - some include this)

# Development
.DS_Store
Thumbs.db
*.swp
*.swo
*~

# IDE
.vscode/
.idea/
*.sublime-project
*.sublime-workspace

# Logs
logs/*.log
*.log

# Uploads (if applicable)
uploads/*
!uploads/.gitkeep

# Temporary files
/tmp/
/temp/
*.tmp

# PHPUnit
.phpunit.result.cache

# Node modules (if using npm)
node_modules/

# Build artifacts
/build/
/dist/
```

---

## Verification Checklist

After setting up git deployment, verify:

- [ ] Git repository initialized on production
- [ ] Remote configured to GitHub
- [ ] v1.7 branch checked out
- [ ] `.env` file present (not in git)
- [ ] `vendor/` directory present (composer install)
- [ ] `git status` shows clean working tree
- [ ] `git pull origin v1.7` works without errors
- [ ] Deployment script exists and is executable
- [ ] Backup directory exists
- [ ] Site loads correctly after git pull

---

## Common Issues and Solutions

### **Issue: "Permission denied (publickey)"**

**Solution**: SSH key not configured
```bash
# Generate and add SSH key (see Phase 5, Option B)
```

---

### **Issue: "Username/password authentication failed"**

**Solution**: Use Personal Access Token, not GitHub password
```bash
# Generate token on GitHub (see Phase 5, Option A)
```

---

### **Issue: "Local changes would be overwritten"**

**Solution**: Stash or discard local changes
```bash
git stash  # Save changes
# or
git reset --hard origin/v1.7  # Discard changes
```

---

### **Issue: "Composer not found"**

**Solution**: Use full path to composer
```bash
/usr/local/bin/composer install --no-dev --optimize-autoloader
```

---

### **Issue: ".env file missing after git pull"**

**Solution**: `.env` should never be in git (security risk)
```bash
# Keep .env in safe location
cp /home/a4409d26/backups/.env.production /home/a4409d26/d646a74eb9.nxcli.io/html/.env
```

---

## Migration Timeline

### **Recommended Approach**:

**Week 1: Preparation**
- [ ] Push v1.7 branch to GitHub
- [ ] Create v1.7.0 release tag
- [ ] Document current production state
- [ ] Create comprehensive backup

**Week 1: Setup** (30 minutes)
- [ ] Initialize git on production
- [ ] Configure GitHub authentication
- [ ] Create deployment script
- [ ] Test deployment process

**Week 1: Verification** (15 minutes)
- [ ] Run test deployment
- [ ] Verify site functionality
- [ ] Test rollback procedure
- [ ] Update deployment documentation

**Ongoing: Use New Process**
- [ ] Deploy via `git pull` instead of SCP
- [ ] Monitor for issues
- [ ] Refine deployment script as needed

---

## Comparison: Before vs After

### **Current SCP Method**:

```bash
# Local: Package files
tar -czf deployment.tar.gz admin/ includes/ pages/ src/

# Upload
scp deployment.tar.gz user@server:/home/user/

# Production: Extract
ssh user@server
cd /path/to/html
tar -xzf ~/deployment.tar.gz

# Total time: 5-10 minutes
# Risk: High (manual file selection)
```

---

### **Git Deployment Method**:

```bash
# Local: Push changes
git push origin v1.7

# Production: Pull changes
ssh user@server
cd /path/to/html
git pull origin v1.7

# Total time: 1-2 minutes
# Risk: Low (automatic, trackable)
```

---

## Next Steps

**Immediate Actions**:

1. **Push v1.7 to GitHub**:
   ```bash
   git push origin v1.7
   ```

2. **Create Backup of Production**:
   ```bash
   ssh production
   tar -czf backup-pre-git.tar.gz html/
   ```

3. **Choose Setup Method**:
   - Option A: Fresh clone from GitHub (recommended)
   - Option B: Initialize git in existing directory

4. **Test Deployment**:
   - Make a small change locally
   - Push to GitHub
   - Pull on production
   - Verify site works

5. **Document Process**:
   - Add deployment commands to README
   - Share with team members
   - Update deployment runbook

---

## Conclusion

**Status**: Ready to implement git-based deployments

**Benefits**:
- ✅ Faster deployments (1-2 minutes vs 5-10 minutes)
- ✅ Safer deployments (automatic tracking)
- ✅ Easy rollbacks (one command)
- ✅ Full deployment history
- ✅ Industry standard practice
- ✅ Team-friendly (multiple developers)

**Next Step**: Push v1.7 branch to GitHub, then choose setup method.

---

**Guide Created**: October 21, 2025
**Author**: Claude Code (Git Deployment Setup)
**Project**: Christmas for Kids v1.7
**Status**: 📋 **READY TO IMPLEMENT**
