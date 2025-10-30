# CLAUDE.md - v1.8.1 Cleanup Branch

**Branch Purpose:** Systematic code modernization using Production-First methodology
**Base Branch:** v1.7.3-production-hardening
**Created:** October 30, 2025

---

## 🎯 Branch Mission

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

## 🚨 Critical Rules for This Branch

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

**Expected:** 35/36 functional tests pass (v1.7.3 baseline)

### 3. One Change at a Time

- Delete ONE deprecated file per commit
- Fix ONE critical error per commit
- Test after EVERY change
- Document as you go (not afterward)

### 4. Testing Protocol

After ANY code change:
1. Run PHPStan (verify no new errors)
2. Run functional test suite
3. Check Docker logs for errors
4. Manual testing (if user-facing change)

### 5. 🚨 PRODUCTION DEPLOYMENT RULE (MANDATORY)

**⛔ NEVER DEPLOY TO PRODUCTION WITHOUT EXPLICIT USER APPROVAL ⛔**

**ABSOLUTE RULE:**
- ✅ **ALWAYS ask** before deploying ANY change to production (cforkids.org)
- ✅ **STAGING ONLY** for testing and verification
- ✅ **WAIT for explicit approval** ("deploy to production" or "yes, push to prod")
- ❌ **NEVER assume** a fix is "safe enough" to skip approval

**Approved Workflow:**
```
1. Make changes locally
2. Commit to git
3. Deploy to STAGING only (/deploy-staging)
4. Tell user: "Deployed to staging - please test at cfkstaging.org"
5. WAIT for user to test
6. WAIT for user to say "deploy to production"
7. Only then use /deploy-production
```

**Even for "safe" changes:**
- CSS/styling fixes → Ask first
- Bug fixes → Ask first
- Documentation updates → Ask first
- Permission fixes → Ask first
- ANY file change → Ask first

**The ONLY exception:**
- Emergency security fixes (must explain urgency and get retroactive confirmation)

**If you deployed to production without asking:**
1. Immediately inform user
2. Offer to revert
3. Document what was deployed
4. Apologize for the process violation

**Remember:** The user owns production. You are a helper, not a decision-maker.

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

# 2. Functional tests (must pass - 35/36)
./tests/security-functional-tests.sh
```

**Expected baseline:**
- PHPStan: 161 errors (no increase acceptable)
- Functional tests: 35/36 passing

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
- Functional tests: 35/36 passing
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
- [ ] Docker environment running
- [ ] PHPStan baseline created
- [ ] Functional tests passing

---

## 🚫 What NOT to Do

**Mistakes from v1.8-cleanup to avoid:**

1. ❌ **Don't exclude admin/ from quality checks** - This hid 9 critical bugs
2. ❌ **Don't make multiple changes without testing** - One at a time only
3. ❌ **Don't merge conflicting security models** - v1.7.3 uses magic-link only
4. ❌ **Don't document afterward** - Document as you code
5. ❌ **Don't prioritize architecture over production safety** - Production first, always

---

## ✅ Success Criteria

**This branch is ready for production when:**

- [ ] All deprecated wrapper files deleted (3,624 lines)
- [ ] PHPStan critical errors reduced by 50%+
- [ ] Functional tests: 35/36 pass (no regression)
- [ ] All production code scanned and clean
- [ ] Documentation complete and current
- [ ] Deployment plan documented
- [ ] Rollback procedure tested

---

## 🔄 Git Workflow

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

---

**Remember:** This cleanup follows the Production-First methodology. Every decision prioritizes production stability over architectural elegance.
