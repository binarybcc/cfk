# Development Skills - Complete ✅

**Date:** October 30, 2025
**Branch:** v1.8.1-cleanup
**Status:** 6 project-specific skills created

---

## ✅ Skills Created

### 1. `/sync-check` - Repository Sync Verification

**Purpose:** Check if local branch is synchronized with remote
**When:** At session start, before starting work
**Duration:** 5-10 seconds

**What it does:**
- Shows current branch and commit
- Fetches remote changes (read-only)
- Compares local vs remote (ahead/behind/diverged)
- Detects uncommitted changes
- Shows recent commit history
- Provides sync recommendations

**Status indicators:**
- ✅ Up to date - Ready to work
- ⚠️ Behind remote - Pull recommended
- ⚠️ Ahead of remote - Push when ready
- ❌ Diverged - Merge needed
- 🆕 New local branch - Push to create remote

**Benefits:**
- Prevents working on stale code
- Avoids merge conflicts
- Clear sync status at session start
- Automatic recommendations

---

### 2. `/test-full` - Comprehensive Test Suite

**Purpose:** Complete quality and functionality testing
**When:** Before commits, before deployments, after major changes
**Duration:** 2-5 minutes

**What it does:**
- Docker environment check
- PHPStan analysis (admin, includes, pages, cron, src)
- Functional test suite (35/36 baseline)
- Code quality checks (debug code, TODOs)
- Security checks (.env files, secrets)
- Comprehensive results summary

**Pass criteria:**
- ✅ PHPStan: 0 errors (or <10 for WIP)
- ✅ Functional tests: 35-36 passing
- ✅ Docker: All services running
- ✅ No debug code in production files
- ✅ No .env files in git

**Benefits:**
- Catches issues before deployment
- Prevents regressions
- Ensures production readiness
- Comprehensive quality assurance

---

### 3. `/quality-check` - Pre-Commit Verification

**Purpose:** Fast quality checks before committing
**When:** Before every git commit
**Duration:** 30 seconds - 2 minutes

**What it does:**
- Shows staged changes
- PHPStan on changed files only (fast)
- Debug code detection (var_dump, print_r)
- Security scan (secrets, hardcoded credentials)
- .env file detection (critical security)
- TODO/FIXME tracking

**Checks:**
- 🔍 PHPStan: Changed files only
- 🐛 Debug code: var_dump, print_r, dd()
- 🔐 Security: Hardcoded secrets, credentials
- 📄 .env files: Must never be committed
- 📌 TODOs: Informational tracking

**Benefits:**
- Catches issues before commit
- Fast iteration (only changed files)
- Prevents security leaks
- Enforces commit message standards

---

### 4. `/start-phase-1` - Begin Cleanup Phase 1

**Purpose:** Create baseline before v1.8.1 cleanup begins
**When:** Starting Phase 1 of cleanup
**Duration:** 2-3 hours

**What it does:**
- Prerequisites check (branch, Docker, working tree)
- PHPStan baseline on ALL production code
- Dead code analysis review
- Dependency mapping
- Baseline report generation

**Outputs:**
- `phpstan-v1.8.1-baseline.txt` - Complete error baseline
- `dependency-map.txt` - All production dependencies
- `docs/v1.8.1-baseline-report.md` - Comprehensive report

**Benefits:**
- Establishes starting point
- Identifies all issues before fixes
- Measures cleanup progress
- Production First Principle applied
- Safe (read-only, no modifications)

---

### 5. `/deploy-staging` - Staging Deployment

**Purpose:** Deploy to staging server for testing
**When:** Testing changes before production
**Duration:** 1-2 minutes

**What it does:**
- Loads `.env.staging` credentials
- Verifies target is staging server
- Shows deployment details
- Deploys via SCP
- Verifies deployment success
- Provides testing checklist

**Safety:**
- ✅ No confirmation needed (staging is safe)
- ✅ Isolated credentials (.env.staging)
- ✅ Can't accidentally deploy to production
- ✅ Deployment logging

**Benefits:**
- Safe testing environment
- Fast iteration (deploy often)
- Comprehensive testing checklist
- Clear deployment process

---

### 6. `/deploy-production` - Production Deployment

**Purpose:** Deploy to live production site
**When:** After thorough staging testing
**Duration:** 2-3 minutes

**What it does:**
- Loads `.env.production` credentials
- Shows WARNING about production deployment
- Requires typing: "DEPLOY TO PRODUCTION"
- Requires confirmation: "yes"
- Creates backup reference point
- Deploys via SCP
- Verifies deployment success
- Provides verification checklist

**Safety:**
- ⚠️ Dual confirmation required
- ⚠️ Isolated credentials (.env.production)
- ⚠️ Can't mix with staging
- ⚠️ Backup reference created
- ⚠️ Deployment logging

**Benefits:**
- Prevents accidental production deployment
- Multiple safety checkpoints
- Rollback instructions provided
- Clear verification steps

---

## 🎯 Workflow Integration

### Standard Development Workflow:

```
1. Session Start
   └─ /sync-check                    ← Verify repo sync

2. Development
   └─ Make changes locally
   └─ Test in Docker

3. Pre-Commit
   └─ /quality-check                 ← Fast quality checks
   └─ Fix any issues
   └─ git commit

4. Pre-Deployment
   └─ /test-full                     ← Comprehensive testing
   └─ Fix any failures
   └─ /sync-check                    ← Verify still in sync
   └─ git push

5. Staging Deployment
   └─ /deploy-staging                ← Deploy to staging
   └─ Test thoroughly

6. Production Deployment
   └─ /deploy-production             ← Deploy to production
   └─ Verify manually
```

### Cleanup Workflow (v1.8.1):

```
1. Session Start
   └─ /sync-check

2. Phase 1 Start
   └─ /start-phase-1                 ← Create baseline
   └─ Review reports

3. Phase 2 (Critical Fixes)
   └─ Fix PHPStan errors
   └─ /quality-check before commit
   └─ /test-full after fixes

4. Phase 3 (Dead Code Removal)
   └─ Delete one file
   └─ /test-full
   └─ Repeat

5. Phase 4 (Documentation)
   └─ Update docs
   └─ /test-full
   └─ /deploy-staging
```

---

## 📋 Skill Reference Quick Guide

| Skill | When | Duration | Safety |
|-------|------|----------|--------|
| `/sync-check` | Session start | 10 sec | Read-only |
| `/quality-check` | Before commit | 30 sec - 2 min | Read-only |
| `/test-full` | Before deploy | 2-5 min | Read-only |
| `/start-phase-1` | Cleanup start | 2-3 hours | Read-only |
| `/deploy-staging` | Testing | 1-2 min | Safe (staging) |
| `/deploy-production` | Production | 2-3 min | HIGH RISK |

---

## 🎓 Skill Purposes by Category

### Quality Assurance:
- `/quality-check` - Fast pre-commit checks
- `/test-full` - Comprehensive testing

### Repository Management:
- `/sync-check` - Repository synchronization

### Deployment:
- `/deploy-staging` - Staging deployment
- `/deploy-production` - Production deployment

### Project Management:
- `/start-phase-1` - Cleanup Phase 1 baseline

---

## 💡 Usage Tips

### Run `/sync-check` Every Session:
```bash
# First thing when starting work
/sync-check

# Shows if you need to pull/push
# Prevents merge conflicts
# Quick and harmless
```

### Use `/quality-check` Before Every Commit:
```bash
# Before committing
/quality-check

# Fast checks on changed files only
# Catches common issues early
# Prevents bad commits
```

### Run `/test-full` Before Deployments:
```bash
# Before deploying to staging
/test-full

# Before deploying to production
/test-full

# After major refactoring
# Comprehensive validation
```

### Deploy Workflow:
```bash
# Test in staging first
/deploy-staging

# Test thoroughly in staging
# [manual testing]

# Deploy to production only after staging success
/deploy-production
```

---

## 🔄 How to Make Skills Available

**Skills require VS Code reload to appear:**

**Option 1: Reload Window (Recommended)**
1. Press `Cmd+Shift+P` (Mac) or `Ctrl+Shift+P` (Windows/Linux)
2. Type: "Reload Window"
3. Select: "Developer: Reload Window"
4. Skills will be available after reload

**Option 2: Restart Claude Code**
1. Close Claude Code
2. Reopen it
3. Navigate to project
4. Skills available

**Verify skills loaded:**
- Type `/` in Claude Code chat
- Skills should appear in autocomplete

---

## 📂 Skill Files Location

```
.claude/commands/
├── deploy-production.md      # Production deployment
├── deploy-staging.md         # Staging deployment
├── quality-check.md          # Pre-commit checks
├── start-phase-1.md          # Cleanup Phase 1
├── sync-check.md             # Repository sync
└── test-full.md              # Complete test suite
```

All skills are:
- ✅ Committed to git
- ✅ Version controlled
- ✅ Shared with team
- ✅ Project-specific
- ✅ Available after reload

---

## 🎯 Benefits Summary

### Automation:
- ✅ Consistent quality checks
- ✅ Automated testing
- ✅ Safe deployment process
- ✅ Baseline creation

### Safety:
- ✅ Prevents bad commits
- ✅ Catches security issues
- ✅ Production safeguards
- ✅ Deployment verification

### Efficiency:
- ✅ Fast pre-commit checks
- ✅ Comprehensive testing
- ✅ Quick sync verification
- ✅ Structured cleanup process

### Quality:
- ✅ PHPStan enforcement
- ✅ Debug code detection
- ✅ Secret scanning
- ✅ Test baseline tracking

---

## 📚 Documentation

**Complete guides:**
- Skills themselves (in `.claude/commands/`)
- `docs/DEPLOYMENT-SKILLS-GUIDE.md` - Deployment details
- `docs/v1.8.1-cleanup-plan.md` - Cleanup methodology
- `CLAUDE.md` - Branch-specific guidance

---

## ✅ Ready to Use!

**All 6 skills are:**
- ✅ Created and documented
- ✅ Committed to git
- ✅ Pushed to remote
- ✅ Available after VS Code reload
- ✅ Integrated with project workflow

**To start using:**
1. Reload VS Code window
2. Type `/` in Claude Code chat
3. Select skill from autocomplete
4. Follow skill instructions

---

**Status:** ✅ COMPLETE AND READY

**Created:** October 30, 2025
**Branch:** v1.8.1-cleanup
**Skills:** 6 total (4 workflow + 2 deployment)
