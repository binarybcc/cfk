# Environment File Safety Guide

**Problem:** Environment credential files (.env.production, .env.staging) can be silently deleted during cleanup operations, and you won't know until you need them.

**Solution:** Multiple layers of protection + automatic backups.

---

## 🛡️ Protection Layers

### 1. **Automatic Backups (Your Safety Net)**

**Backup your env files regularly:**
```bash
./scripts/backup-env-files.sh
```

**Backups stored in:** `~/.cfk-backups/` (outside git repo)

**Run this:**
- ✅ Weekly (part of routine maintenance)
- ✅ Before major cleanups
- ✅ After updating credentials
- ✅ Before switching to unfamiliar branches

**Restore from backup:**
```bash
# List available backups
ls -lah ~/.cfk-backups/

# Restore specific backup
cp ~/.cfk-backups/.env.production.backup-20251109-193906 .env.production
chmod 444 .env.production
```

---

### 2. **Read-Only Protection (Prevents Accidental Edits)**

Files are marked read-only (444):
```bash
-r--r--r-- .env.production
-r--r--r-- .env.staging
```

**To edit when needed:**
```bash
chmod 644 .env.production  # Make writable
vim .env.production        # Edit
chmod 444 .env.production  # Make read-only again
```

---

### 3. **Safe Cleanup Commands (Prevents Deletion)**

**✅ ALWAYS USE:**
```bash
./scripts/safe-cleanup.sh  # Shows what's protected, asks confirmation
git clean -fd              # Skips gitignored files
```

**❌ NEVER USE:**
```bash
git clean -fdx             # DELETES gitignored files!
git clean -fX              # DELETES ONLY gitignored files!
rm -rf *                   # Nuclear option
```

---

### 4. **Gitignore (Prevents Commits)**

Files are in `.gitignore`:
```
.env
.env.staging
.env.production
```

**This means:**
- ✅ Never pushed to GitHub
- ✅ Never committed to git
- ✅ Persist across branch switches
- ❌ Can be deleted by `git clean -fdx`

---

## 🔔 Detection: Know When Files Are Missing

### Manual Check
```bash
ls -la .env.production .env.staging
```

### Automated Check (Before Deployment)
The deployment scripts will fail if files are missing:
```bash
source .env.production   # Fails if file doesn't exist
```

---

## 📋 Recommended Workflow

### **Weekly Routine:**
```bash
# 1. Backup environment files
./scripts/backup-env-files.sh

# 2. Do your cleanup
./scripts/safe-cleanup.sh

# 3. Verify files still exist
ls -la .env.production .env.staging
```

### **After Silent Loss (Recovery):**
```bash
# 1. Check backup directory
ls -lah ~/.cfk-backups/

# 2. Restore latest backup
cp ~/.cfk-backups/.env.production.backup-[LATEST] .env.production
cp ~/.cfk-backups/.env.staging.backup-[LATEST] .env.staging

# 3. Set permissions
chmod 444 .env.production .env.staging

# 4. Verify restoration
cat .env.production | grep SSH_HOST
```

---

## 🚨 What Happened Tonight?

**Likely cause:** Either:
1. `git clean -fdx` was run (deletes gitignored files)
2. Manual deletion (rm .env.*)
3. IDE cleanup feature that removes untracked files

**Why you didn't know:**
- Gitignored files are "invisible" to git
- No warning when they're deleted
- Git status doesn't show them

**Prevention:**
- ✅ Backup regularly with `./scripts/backup-env-files.sh`
- ✅ Only use safe cleanup commands
- ✅ Add backup to weekly routine

---

## 💡 Quick Reference

| Task | Command |
|------|---------|
| **Backup env files** | `./scripts/backup-env-files.sh` |
| **List backups** | `ls -lah ~/.cfk-backups/` |
| **Restore backup** | `cp ~/.cfk-backups/.env.production.backup-[DATE] .env.production` |
| **Safe cleanup** | `./scripts/safe-cleanup.sh` |
| **Check if files exist** | `ls -la .env.production .env.staging` |
| **Edit protected file** | `chmod 644 .env.production && vim .env.production && chmod 444 .env.production` |

---

## 🎯 Bottom Line

**You're right** - you need to pay attention. But now you have:

1. ✅ **Automatic backups** - Can recover from deletion
2. ✅ **Safe cleanup script** - Won't delete accidentally
3. ✅ **Read-only protection** - Can't edit accidentally
4. ✅ **Documentation** - Know what to avoid
5. ✅ **Recovery procedure** - Know how to restore

**Make backup part of your weekly routine**, and you'll never lose credentials permanently.

---

**Created:** 2025-11-09
**Last Updated:** 2025-11-09
