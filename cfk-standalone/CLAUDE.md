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

---

## 🔧 Environment Configuration

**Always use local .env file for credentials:**
- Production SSH: See .env (SSH_HOST, SSH_USER, SSH_PASSWORD)
- Staging SSH: See .env (staging credentials)
- Database: See .env (DB_HOST, DB_USER, DB_PASSWORD)

**Never hardcode credentials in source files**

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
