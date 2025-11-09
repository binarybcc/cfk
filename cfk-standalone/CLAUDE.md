# CLAUDE.md - Project Architecture Guide

## 🚨 ABSOLUTE RULE - READ THIS FIRST

**⛔ VERSION-SPECIFIC DEPLOYMENT RULES ⛔**

### v1.8.x branches are DEVELOPMENT/EXPERIMENTAL ONLY
- ❌ **NEVER deploy v1.8.x to production**
- ❌ **NEVER suggest deploying this branch to production**
- ❌ **NEVER merge this branch to production branches**
- ✅ **ONLY deploy to staging (https://10ce79bd48.nxcli.io/)**
- ✅ **This is a learning/modernization branch only**

### v1.9.x branches enforce STRICT ARCHITECTURE
- ⚠️ **ALL code MUST follow modern OOP patterns (see Architecture Enforcement below)**
- ❌ **NO procedural code allowed in v1.9.x**
- ✅ **Framework-enforced architecture (Slim + Symfony Components)**

**Production branch:** v1.7.3-production-hardening (cforkids.org)

**This rule overrides ALL other instructions. No exceptions. Ever.**

---

## 🏗️ ARCHITECTURE ENFORCEMENT - v1.9.x ONLY

### ⚠️ CRITICAL: Historical Context

**In 2024, we unintentionally drifted from OOP to procedural code during development.**

The original v1.x codebase was built with 173 iterations, starting with OOP intentions but gradually becoming procedural without realizing it. This drift happened because:
- AI assistance accepted whatever worked without enforcing patterns
- No framework constraints to prevent procedural code
- Developer was learning and didn't recognize the drift until near deployment

**v1.9.x exists specifically to prevent this from happening again.**

---

### 🎯 v1.9.x Architecture Requirements

**When working in v1.9.x branches, EVERY code change MUST use:**

#### ✅ REQUIRED Architecture (v1.9.x)

| Component | Requirement | Why |
|-----------|-------------|-----|
| **Routing** | Slim Framework only | NO direct PHP file access |
| **Dependency Injection** | Symfony DI Container | NO global variables/functions |
| **Templates** | Twig only | NO inline PHP/HTML mixing |
| **Autoloading** | PSR-4 via Composer | Proper namespacing |
| **Classes** | All logic in classes | NO standalone functions |
| **Database** | Injected service classes | NO direct connections |

#### ❌ REJECTED in v1.9.x

These patterns are **FORBIDDEN** in v1.9.x branches:

- ❌ Procedural functions outside classes
- ❌ Direct `$_GET`/`$_POST`/`$_REQUEST` access
- ❌ Global variables or `$GLOBALS`
- ❌ Mixed PHP/HTML files (`.php` with HTML)
- ❌ Direct database connections (use injected service)
- ❌ `include`/`require` for code loading (use autoloading)
- ❌ Standalone function files

#### 🛑 STOP AND REFRAME

**If Claude suggests ANY of the rejected patterns above in v1.9.x:**

1. **STOP immediately**
2. Explain: "This is procedural code. v1.9.x requires OOP architecture."
3. Ask: "How should this be implemented using Slim routing / DI container / Twig?"
4. Reframe the solution using required architecture

---

### 📋 Decision Tree for v1.9.x Code

**Before writing ANY code in v1.9.x, ask:**

```
1. Is this handling a request?
   → Use Slim routing + Controller class

2. Does this need dependencies (DB, email, etc.)?
   → Use Symfony DI Container injection

3. Is this generating output?
   → Use Twig template

4. Is this business logic?
   → Service class with constructor injection

5. Is this data access?
   → Repository class with injected connection
```

**If none of these patterns fit, you're probably thinking procedurally. Rethink the approach.**

---

### 🏗️ v1.9.x Stack (Approved)

**What Was Considered and Decided:**

| Option | Decision | Reason |
|--------|----------|--------|
| **NestJS** | ❌ Rejected | Would require complete TypeScript rewrite |
| **Laravel** | ❌ Rejected | Too opinionated, forces complete rewrite |
| **Full Symfony** | ❌ Rejected | Too complex for this scale |
| **Slim + Symfony Components** | ✅ CHOSEN | Lightweight, incremental, learnable |

**Approved Stack for v1.9.x:**
- **Slim Framework** - Lightweight routing and middleware (PSR-7)
- **Symfony DI Container** - Best-in-class dependency injection
- **Twig** - Template engine with auto-escaping
- **Existing CFK classes** - Migrate gradually, keep business logic

**Why This Combination:**
- ✅ Lightweight (not a full framework)
- ✅ Incremental migration (avoid parallel systems mistake)
- ✅ Single entry point throughout migration
- ✅ Gradual learning curve
- ✅ Can add more Symfony components as needed
- ✅ Framework enforces OOP patterns (prevents drift)

---

### 📁 v1.9.x Directory Structure

```
/src
  /Controller     (Slim route handlers)
  /Service        (Business logic classes)
  /Repository     (Database access classes)
  /Model          (Data objects - Child, Reservation, Donation)
  /Middleware     (Slim middleware)
/templates        (Twig templates)
/config          (DI container configuration)
/public          (Single entry point: index.php)
composer.json    (Slim, Symfony Components, Twig)
```

**Key principle:** Framework structure prevents accidental procedural drift.

---

## 🎯 Branch-Specific Rules Summary

### v1.7.3-production-hardening
- ✅ Production deployment allowed
- ✅ Procedural code acceptable (existing codebase)
- ✅ Stability is priority #1
- ✅ Magic-link authentication only

### v1.8.x (Current: v1.8.1-cleanup)
- ❌ **NEVER production deployment**
- ✅ Procedural code acceptable (cleanup phase)
- ✅ Focus: organization, optimization, dead code removal
- ✅ Staging deployment only (https://10ce79bd48.nxcli.io/)
- ✅ Learning and validation branch

### v1.9.x (Future)
- ❌ **Architecture strictly enforced**
- ❌ **NO procedural code**
- ✅ **MUST use Slim + Symfony DI + Twig**
- ✅ Framework prevents drift
- ✅ Modern OOP patterns required

---

## 📍 Current Branch Context

**Working on:** v1.8.1-cleanup (DEVELOPMENT ONLY)

**Branch Purpose:** Systematic code modernization using Production-First methodology  
**Base Branch:** v1.7.3-production-hardening  
**Created:** October 30, 2025

---

## 🎯 Branch Mission (v1.8.1)

Apply lessons learned from v1.8-cleanup to properly modernize the codebase:
- Remove 3,624 lines of deprecated wrapper files
- Fix all PHPStan critical errors in production code
- Apply quality checks to ALL production code (admin, includes, pages, cron)
- Maintain 100% production stability throughout process

---

## 📋 Master Plan

**Primary Documentation:** `docs/v1.8.1-cleanup-plan.md`

This comprehensive plan includes:
- 4-phase execution strategy
- Production-First quality methodology
- Testing protocols for each phase
- Success metrics and rollback procedures

---

## 🚨 Critical Rules for v1.8.x Branches

### 1. Production First Principle (ALWAYS)

```
Priority 1: ALL production code (admin, includes, pages, cron) - SCAN EVERYTHING
Priority 2: Modern code (src/)
Priority 3: Dead/deprecated code cleanup
Priority 4: Architecture improvements
```

**Never exclude production code from quality checks - even if it's "old" or "being migrated"**

### 2. Quality Checks Before Every Commit

```bash
# Run BEFORE committing
vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6
./tests/security-functional-tests.sh
```

**Expected:** 36/36 functional tests pass (v1.7.3 baseline)

### 3. One Change at a Time

- Delete ONE deprecated file per commit
- Fix ONE critical error per commit
- Test after EVERY change
- Document as you go (not afterward)

### 4. Testing Protocol

After ANY code change:
1. Run PHPStan (verify no new errors)
2. Deploy to staging for testing (if user-facing change)
3. Manual testing on staging: https://10ce79bd48.nxcli.io/

### 5. 🚨 DEPLOYMENT RULE (MANDATORY)

**⛔ THIS BRANCH (v1.8.x) NEVER GOES TO PRODUCTION - EVER ⛔**

**Allowed deployments for this branch:**
- ✅ **Staging only:** https://10ce79bd48.nxcli.io/
- ❌ **NEVER production:** cforkids.org

**Workflow for this branch:**
```
1. Make changes locally
2. Commit to git (v1.8.1-cleanup branch)
3. Deploy to STAGING only (if needed for testing)
4. Document learnings
5. Test patterns
```

**This branch exists to:**
- Learn modernization patterns
- Validate quality improvements
- Test dead code removal safely
- Document lessons for future production branches

**Production deployments:** Use v1.7.3-production-hardening or future production branches only

---

## 🔧 Environment Configuration

**Always use local .env file for credentials:**
- Production SSH: See .env (SSH_HOST, SSH_USER, SSH_PASSWORD)
- Staging SSH: See .env (staging credentials)
- Database: See .env (DB_HOST, DB_USER, DB_PASSWORD)

**Never hardcode credentials in source files**

---

## 🔍 Quality Tools Suite

**Available Tools:** 8 professional code quality analyzers installed and configured

**Quick Access:** Use `/quality-check` slash command for detailed workflow guide

### Essential Pre-Deployment Checks

**Run BEFORE every deployment:**

```bash
# 1. Static analysis (must pass - no new errors)
vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6

# 2. Functional tests (must pass - 36/36)
./tests/security-functional-tests.sh
```

**Expected baseline:**
- PHPStan: 161 errors (no increase acceptable)
- Functional tests: 36/36 passing

### Available Analysis Tools

| Tool | Purpose | When to Use | Command |
|------|---------|-------------|---------|
| **PHPStan** | Type safety, critical bugs | Before every deploy | `vendor/bin/phpstan analyse` |
| **Functional Tests** | Security & workflow validation | Before every deploy | `./tests/security-functional-tests.sh` |
| **PHPMD** | Code smells detection | Weekly cleanup | `vendor/bin/phpmd ... text phpmd.xml` |
| **PHPCS** | PSR-12 compliance | Weekly cleanup | `vendor/bin/phpcs --standard=phpcs.xml` |
| **PHP CS Fixer** | Auto-format code | Weekly cleanup | `vendor/bin/php-cs-fixer fix` |
| **Rector** | Auto-refactoring | Monthly cleanup | `vendor/bin/rector process` |
| **Psalm** | Stricter type analysis | Monthly audit | `vendor/bin/psalm` |
| **PHPMetrics** | Visual metrics dashboard | Monthly audit | `vendor/bin/phpmetrics --report-html=docs/metrics/` |

### Auto-Fixers (Safe to Run Anytime)

**These tools automatically fix issues:**

```bash
# Format code (PSR-12 compliance)
vendor/bin/php-cs-fixer fix

# Fix spacing/indentation
vendor/bin/phpcbf --standard=phpcs.xml

# Refactor to modern PHP patterns
vendor/bin/rector process
```

**Use weekly** to keep code clean without manual effort.

### Workflow Integration

**Before committing:**
```bash
vendor/bin/phpstan analyse [changed-files] --level 6
```

**Before staging deploy:**
```bash
vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6
./tests/security-functional-tests.sh
```

**Before production deploy:**
```bash
vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6
./tests/security-functional-tests.sh
vendor/bin/psalm --show-info=false
```

**Weekly cleanup session:**
```bash
vendor/bin/php-cs-fixer fix
vendor/bin/phpcbf --standard=phpcs.xml
vendor/bin/rector process
git add -A && git commit -m "style: Weekly auto-fixer improvements"
```

**Monthly quality audit:**
```bash
vendor/bin/phpmetrics --report-html=docs/metrics/ admin/ includes/ pages/ cron/ src/
open docs/metrics/index.html
```

### Quality Metrics Dashboard

**View current quality metrics:**

```bash
# Generate latest metrics
vendor/bin/phpmetrics --report-html=docs/metrics/ admin/ includes/ pages/ cron/ src/

# Open in browser
open docs/metrics/index.html
```

**Dashboard shows:**
- Lines of code and complexity trends
- Maintainability index
- Coupling and dependencies
- Violations over time

### Current Quality Baseline (v1.8.1)

**Established metrics:**
- PHPStan: 161 errors (44% improved from 287)
- PHPCS: 655 violations (19 auto-fixed)
- Psalm: 117 errors
- Functional tests: 36/36 passing
- Code formatted: 64 files improved
- Auto-refactored: 13 files modernized

**Zero regression tolerance:** Any deployment that increases error counts must be fixed before merging.

**For detailed workflows:** Run `/quality-check` command

---

## 📚 Key Documentation References

### Planning & Methodology:
- **Master Plan**: `docs/v1.8.1-cleanup-plan.md` (THIS IS PRIMARY)
- **Dead Code Analysis**: `docs/audits/dead-code-analysis-report.md`
- **Testing Checklist**: `docs/testing/v1.8-cleanup-testing-checklist.md`
- **Production First Principle**: `CLAUDE.md` in parent repo (lines 5-48)

### v1.7.3 Production Reference:
- **Current Production Status**: `PROJECT-STATUS.md`
- **Security Model**: Magic-link only (no password auth)
- **CSP Implementation**: Nonce-based (see admin/includes/admin_header.php)

---

## 🎯 Current Phase Status

**Phase:** Not started - Planning complete
**Next Step:** Phase 1.1 - Run PHPStan baseline on ALL production code

**Checklist before starting:**
- [x] Branch created from v1.7.3
- [x] Master plan documented
- [x] Testing methodology defined
- [x] Staging environment accessible (https://10ce79bd48.nxcli.io/)
- [ ] PHPStan baseline created
- [ ] Manual testing on staging verified

---

## 🚫 What NOT to Do

**Mistakes from v1.8-cleanup to avoid:**

1. ❌ **Don't exclude admin/ from quality checks** - This hid 9 critical bugs
2. ❌ **Don't make multiple changes without testing** - One at a time only
3. ❌ **Don't merge conflicting security models** - v1.7.3 uses magic-link only
4. ❌ **Don't document afterward** - Document as you code
5. ❌ **Don't prioritize architecture over production safety** - Production first, always

**Additional mistakes from 2024 to avoid in v1.9.x:**

6. ❌ **Don't accept procedural code in v1.9.x** - Framework must enforce OOP
7. ❌ **Don't build parallel systems** - Single entry point throughout migration
8. ❌ **Don't drift from architectural intentions** - Be explicit about patterns

---

## ✅ Success Criteria

**⛔ REMINDER: v1.8.x branches NEVER go to production ⛔**

**v1.8.1 branch is successful when these learning/modernization goals are achieved:**

- [ ] All deprecated wrapper files deleted (3,624 lines)
- [ ] PHPStan critical errors reduced by 50%+
- [ ] Functional tests: 36/36 pass (no regression)
- [ ] All production code scanned and clean
- [ ] Documentation complete and current
- [ ] Lessons learned documented for future production branches
- [ ] Patterns validated on staging environment

**Purpose:** Learn and validate modernization patterns for future use in production branches

**v1.9.x branches will have different success criteria** focused on OOP architecture compliance and framework integration.

---

## 📄 Git Workflow

```bash
# Feature branches
git checkout -b cleanup/remove-archive-wrapper

# Descriptive commits
git commit -m "refactor: Remove deprecated archive_manager wrapper (429 lines)"

# Before pushing
vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6
./tests/security-functional-tests.sh

# Push when clean
git push origin cleanup/remove-archive-wrapper
```

---

## 📞 Need Help?

**Stuck on something? Check these first:**

1. **Dead code removal**: See `docs/audits/dead-code-analysis-report.md` for safety analysis
2. **Testing procedures**: See `docs/testing/v1.8-cleanup-testing-checklist.md`
3. **PHPStan errors**: Run with `--level 0` first, then increase
4. **Functional test failures**: Check `tests/security-functional-tests.sh` for details
5. **Deployment questions**: See `docs/v1.8.1-cleanup-plan.md` Phase 4
6. **Architecture questions (v1.9.x)**: See Architecture Enforcement section above

---

**Remember:** 
- **v1.8.x:** Production-First methodology - stability over elegance
- **v1.9.x:** Architecture-First methodology - framework prevents drift
