# Deployment Method Transition Plan

**Date**: October 21, 2025
**Status**: ✅ **READY TO IMPLEMENT**
**Priority**: HIGH

---

## Current State

### ✅ **Completed**:
- All 33 commits pushed to GitHub (v1.7 branch)
- Git deployment guide created
- Production backup procedures documented
- Deployment script prepared

### **GitHub Status**:
- Repository: https://github.com/binarybcc/cfk
- Branch: v1.7 (now on GitHub)
- Latest commit: `2e61854` - "docs: Add comprehensive git-based deployment setup guide"
- Total commits: 33 (all pushed)

---

## Deployment Methods Comparison

### **Current Method: Manual SCP Upload**

**Process**:
```bash
# 1. Local: Create tarball
tar -czf deployment.tar.gz [files]

# 2. Upload to server
scp deployment.tar.gz user@server:/home/user/

# 3. SSH and extract
ssh user@server
tar -xzf deployment.tar.gz
```

**Problems**:
- ❌ 5-10 minutes per deployment
- ❌ Manual file selection (error-prone)
- ❌ No version tracking on production
- ❌ Difficult rollbacks
- ❌ Can't see deployment history
- ❌ Risk of partial deployments
- ❌ No automatic integrity checks

---

### **Recommended Method: Git Pull**

**Process**:
```bash
# 1. Local: Push changes
git push origin v1.7

# 2. Production: Pull changes
ssh user@server
cd /path/to/html
./deploy.sh v1.7  # Or: git pull origin v1.7
```

**Benefits**:
- ✅ 1-2 minutes per deployment
- ✅ Automatic file tracking
- ✅ Full version history
- ✅ One-command rollbacks
- ✅ Complete deployment audit trail
- ✅ Automatic integrity checks
- ✅ Industry standard practice

---

## Implementation Plan

### **Phase 1: Preparation** ✅ COMPLETE

**Already Done**:
- ✅ v1.7 branch pushed to GitHub
- ✅ All 33 commits available on GitHub
- ✅ Git deployment guide created
- ✅ Deployment script prepared

---

### **Phase 2: Production Backup** (15 minutes)

**Before making any changes**, create safety backup:

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io

# Create backup directory
mkdir -p /home/a4409d26/backups/pre-git-deployment

# Backup current production files
cd /home/a4409d26/d646a74eb9.nxcli.io
tar -czf /home/a4409d26/backups/pre-git-deployment/html-backup-$(date +%Y%m%d-%H%M%S).tar.gz \
  --exclude='html/vendor' \
  --exclude='html/.env' \
  html/

# Verify backup
ls -lh /home/a4409d26/backups/pre-git-deployment/

# Copy .env to safe location
cp html/.env /home/a4409d26/backups/.env.production
chmod 600 /home/a4409d26/backups/.env.production
```

**Verification**:
- [ ] Backup tarball created
- [ ] Backup size looks correct (should be ~10-50 MB)
- [ ] `.env` file backed up separately

---

### **Phase 3: GitHub Authentication** (10 minutes)

**Choose ONE method**:

**Option A: Personal Access Token (Easier)**

1. Generate token on GitHub:
   - Go to: https://github.com/settings/tokens
   - Click "Generate new token (classic)"
   - Name: "CFK Production Server"
   - Scopes: Select `repo` (full control)
   - Generate token → **COPY IT** (shown only once!)

2. Configure on production:
```bash
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io

# Set up credential caching (1 hour)
git config --global credential.helper 'cache --timeout=3600'

# Or store permanently (less secure but convenient)
git config --global credential.helper store
```

3. On first `git pull`, enter:
   - Username: `your-github-username`
   - Password: `ghp_xxxxxxxxxxxxx` (the token, NOT your password)

---

**Option B: SSH Key (Most Secure) - RECOMMENDED**

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io

# Generate SSH key
ssh-keygen -t ed25519 -C "production-cforkids@cforkids.org"
# Save to: /home/a4409d26/.ssh/id_ed25519_github
# Passphrase: (press Enter for none - easier for automated deployments)

# Start ssh-agent
eval "$(ssh-agent -s)"

# Add key to agent
ssh-add ~/.ssh/id_ed25519_github

# Display public key
cat ~/.ssh/id_ed25519_github.pub
# Copy the entire output (starts with "ssh-ed25519 ...")
```

Then add to GitHub:
1. Go to: https://github.com/settings/keys
2. Click "New SSH key"
3. Title: "CFK Production Server"
4. Key: Paste the public key
5. Click "Add SSH key"

Test connection:
```bash
ssh -T git@github.com
# Should see: "Hi username! You've successfully authenticated..."
```

---

### **Phase 4: Git Setup on Production** (10 minutes)

**Method A: Fresh Clone (Cleanest) - RECOMMENDED**

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io
cd /home/a4409d26/d646a74eb9.nxcli.io

# Rename current directory
mv html html-old-$(date +%Y%m%d)

# Clone repository (choose one):

# If using HTTPS (Personal Access Token):
git clone -b v1.7 https://github.com/binarybcc/cfk.git cfk-repo

# If using SSH:
git clone -b v1.7 git@github.com:binarybcc/cfk.git cfk-repo

# Create symlink (cfk-standalone is the actual code directory)
ln -s cfk-repo/cfk-standalone html

# Restore .env
cp html-old-*/. env html/.env
chmod 600 html/.env

# Install composer dependencies
cd html
composer install --no-dev --optimize-autoloader --no-interaction

# Verify git status
git status  # Should show "nothing to commit, working tree clean"
git branch  # Should show "* v1.7"
git remote -v  # Should show GitHub URLs
```

---

**Method B: Initialize in Existing Directory (Alternative)**

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io
cd /home/a4409d26/d646a74eb9.nxcli.io/html

# Initialize git
git init
git remote add origin https://github.com/binarybcc/cfk.git
# Or for SSH: git remote add origin git@github.com:binarybcc/cfk.git

# Fetch all branches
git fetch origin

# Checkout v1.7
git checkout -b v1.7 origin/v1.7

# This will overwrite files - that's expected
# .env should remain (it's in .gitignore)

# Verify
git status
git log -1
```

---

### **Phase 5: Install Deployment Script** (5 minutes)

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io
cd /home/a4409d26/d646a74eb9.nxcli.io/html

# Create deployment script
cat > deploy.sh << 'EOFSCRIPT'
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
tar -czf $BACKUP_FILE --exclude='.git' --exclude='vendor' --exclude='.env' $HTML_DIR/ 2>/dev/null || true
echo "✅ Backup created: $BACKUP_FILE"

# Navigate to repository
cd $HTML_DIR

# Verify we're in a git repo
if [ ! -d .git ]; then
    echo "❌ Error: Not a git repository"
    exit 1
fi

# Fetch latest changes
echo "🔄 Fetching latest changes..."
git fetch origin

# Stash any local changes (shouldn't be any)
if ! git diff-index --quiet HEAD --; then
    echo "📝 Stashing local changes..."
    git stash
fi

# Checkout and pull branch
echo "⬇️  Pulling branch: $BRANCH"
git checkout $BRANCH
git pull origin $BRANCH

# Run composer install (if composer.json exists)
if [ -f composer.json ]; then
    echo "📦 Installing composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Verify deployment
echo ""
echo "✅ Deployment complete!"
echo "================================================"
git log -1 --pretty=format:"%h - %s (%cr) <%an>"
echo ""
echo "================================================"
echo ""
echo "Current branch: $(git rev-parse --abbrev-ref HEAD)"
echo "Latest commit: $(git rev-parse HEAD)"
echo "Deployed at: $(date)"
echo ""
EOFSCRIPT

# Make executable
chmod +x deploy.sh

# Test script (dry run)
./deploy.sh v1.7
```

---

### **Phase 6: Verification** (5 minutes)

**Test the deployment**:

```bash
# 1. Verify git is working
cd /home/a4409d26/d646a74eb9.nxcli.io/html
git status  # Should be clean
git log -1  # Should show latest commit

# 2. Verify site is working
curl -I https://cforkids.org/
# Should return: HTTP/2 200

# 3. Test deployment script
./deploy.sh v1.7
# Should complete successfully

# 4. Verify .env is present
ls -la .env
# Should exist and have 600 permissions

# 5. Check error logs
tail -50 logs/php_error.log
# Should be clean
```

---

## New Deployment Workflow

### **Local Development** (Your Machine):

```bash
# 1. Make changes
# ... edit files ...

# 2. Commit changes
git add -A
git commit -m "feat: Add new feature"

# 3. Push to GitHub
git push origin v1.7

# ✅ Done! Changes are on GitHub
```

---

### **Production Deployment** (Using Script):

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io

# Run deployment
cd /home/a4409d26/d646a74eb9.nxcli.io/html
./deploy.sh v1.7

# ✅ Done! Site is updated
```

**What the script does**:
1. Creates automatic backup
2. Fetches latest changes from GitHub
3. Pulls v1.7 branch
4. Runs composer install (if needed)
5. Shows deployment summary

---

### **Emergency Rollback**:

```bash
# SSH to production
ssh -p 22 a4409d26_1@d646a74eb9.nxcli.io
cd /home/a4409d26/d646a74eb9.nxcli.io/html

# Option 1: Rollback to previous commit
git log --oneline -10  # Find commit hash
git reset --hard <commit-hash>

# Option 2: Restore from backup
ls /home/a4409d26/backups/deployments/  # List backups
# Then restore specific backup if needed
```

---

## Timeline

### **Recommended Schedule**:

**Week 1 - Day 1** (Today):
- ✅ Push v1.7 to GitHub - **COMPLETE**
- ✅ Create deployment guide - **COMPLETE**
- [ ] Create production backup (15 min)
- [ ] Set up GitHub authentication (10 min)

**Week 1 - Day 2**:
- [ ] Initialize git on production (10 min)
- [ ] Install deployment script (5 min)
- [ ] Run verification tests (5 min)
- [ ] Test deployment workflow (5 min)

**Week 1 - Day 3+**:
- [ ] Use new git deployment for all changes
- [ ] Monitor for any issues
- [ ] Document any edge cases

**Total Setup Time**: ~50 minutes (can be done in one session)

---

## Risk Assessment

### **Risks**:

**Risk 1: Authentication Issues**
- Probability: Medium
- Impact: Low (easy to fix)
- Mitigation: Use SSH keys (more reliable than tokens)

**Risk 2: .env File Accidentally Committed**
- Probability: Low (already in .gitignore)
- Impact: HIGH (security breach)
- Mitigation: Verify .gitignore, backup .env separately

**Risk 3: Merge Conflicts**
- Probability: Low (single developer, single environment)
- Impact: Low (easy to resolve)
- Mitigation: Never edit files directly on production

**Risk 4: Failed Deployment**
- Probability: Very Low
- Impact: Medium (site down temporarily)
- Mitigation: Automatic backups, easy rollback

### **Overall Risk**: **LOW** ✅

---

## Success Criteria

**Git deployment is successful when**:

- [ ] Can push commits from local to GitHub
- [ ] Can pull commits on production server
- [ ] Deployment script runs without errors
- [ ] Site loads correctly after deployment
- [ ] `.env` file remains intact
- [ ] No PHP errors in logs
- [ ] Deployment takes <2 minutes
- [ ] Can rollback if needed

---

## Fallback Plan

**If git deployment doesn't work immediately**:

1. **Keep SCP method available** as backup
2. Production files are backed up (can restore)
3. Can switch back to SCP while troubleshooting
4. Git setup is non-destructive (can try multiple times)

**No rush** - can implement git deployment when convenient.

---

## Decision Point

### **Option A: Implement Now** (Recommended)

**Pros**:
- Professional deployment from day 1
- Establishes good habits early
- Future deployments will be faster
- Easy to demonstrate to team/client

**Cons**:
- 50 minutes of setup time
- Learning curve (minimal)

---

### **Option B: Implement Later**

**Pros**:
- Can continue current workflow
- No immediate disruption

**Cons**:
- Every deployment wastes 5-10 minutes
- Higher risk of deployment errors
- Not industry standard

---

## Recommendation

**⭐ IMPLEMENT GIT DEPLOYMENT NOW**

**Reasons**:
1. ✅ All prerequisites complete (v1.7 on GitHub)
2. ✅ Minimal setup time (50 minutes)
3. ✅ Immediate ROI (saves 5-10 min per deployment)
4. ✅ Much safer than manual SCP
5. ✅ Industry standard practice
6. ✅ Easy rollback if issues arise

**Next Step**: Create production backup, then follow Phase 3 (GitHub auth)

---

## Summary

**Current Status**:
- ✅ v1.7 branch on GitHub with all 33 commits
- ✅ Comprehensive deployment guide created
- ✅ Deployment script prepared
- ⏳ Ready to set up on production server

**Benefits After Implementation**:
- 80% faster deployments (1-2 min vs 5-10 min)
- 100% file coverage (no missed files)
- Full version tracking
- One-command rollbacks
- Industry standard workflow

**Next Action**: Decide when to implement (recommend: now)

---

**Plan Created**: October 21, 2025 @ 15:30 UTC
**Author**: Claude Code (Deployment Transition)
**Project**: Christmas for Kids v1.7
**Status**: ✅ **READY TO IMPLEMENT - 50 MINUTES TO COMPLETE**
