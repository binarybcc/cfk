# CLAUDE.md - v1.9.2 Slim Framework Migration

## 🎯 BRANCH PURPOSE

**v1.9.2 = Professional Slim Framework Migration with Modern Architecture**

**Branch Mission:**
- Migrate legacy PHP pages to Slim Framework 4.x + Twig 3.x
- Build with professional, DRY, elegant architecture from day one
- Use modern best practices and design patterns
- Create maintainable, scalable, testable code

**Architecture Standards:**
- ✅ Component-based templates (modular, reusable)
- ✅ PSR-7 HTTP message handling
- ✅ Dependency injection
- ✅ Single source of truth (DRY principle)
- ✅ Professional naming conventions
- ✅ Clean separation of concerns

**NOT v1.8.1 cleanup philosophy:** This is not cautious refactoring - this is building things RIGHT.

---

**Base Branch:** v1.7.3-production-hardening
**Created:** November 2025
**Deployment:** Staging only (https://10ce79bd48.nxcli.io/)

---

## 📋 Slim Framework Migration Plan

**Weeks 1-3:** ✅ COMPLETED
- Infrastructure setup (Composer, Twig, DI container, routing)
- Basic routes and controllers
- Template system foundation

**Week 4:** ✅ COMPLETED (In Progress)
- Simple forms (sponsor email lookup)
- Modular component architecture established
- Professional template patterns documented

**Week 5:** Next Up
- Children browse/profile pages
- Search and filtering
- Individual child profiles

**Week 6+:** Future
- Complex workflows (cart, reservations, sponsorships)
- Admin pages
- Full feature parity with legacy system

---

## 🚨 Critical Rules for v1.9.2

### 1. Professional Architecture First

**Priority Order:**
1. ✅ **DRY (Don't Repeat Yourself)** - Single source of truth always
2. ✅ **Component-based architecture** - Modular, reusable pieces
3. ✅ **Best practices** - Follow PSR standards, design patterns
4. ✅ **Elegant code** - Clean, readable, maintainable
5. ✅ **Testing** - Verify functionality on staging

**We are building it RIGHT, not just making it work.**

### 2. Template Architecture Standards (MANDATORY)

**EVERY template must follow component pattern:**
- ✅ Extract header/footer to `templates/components/`
- ✅ Layouts use `{% include 'components/header.twig' %}`
- ✅ Feature templates `{% extends 'layouts/base.twig' %}`
- ❌ NEVER duplicate header/footer code
- ❌ NEVER inline header/footer in templates

**See:** `docs/technical/slim-template-architecture.md`

### 3. Development Workflow

**For every feature:**
1. Design with professional architecture
2. Implement with modern patterns
3. Test on staging
4. Document in code and markdown
5. Commit with descriptive messages

**Quality over speed. Build once, build right.**

### 4. Testing Protocol

After implementing feature:
1. Test locally (if possible)
2. Deploy to staging: `sshpass -p ... scp files...`
3. Manual testing on staging: https://10ce79bd48.nxcli.io/
4. Verify all functionality works
5. Hard refresh browser to clear cache

### 5. Deployment

**Staging Only:**
- ✅ Deploy v1.9.2 to staging anytime
- ✅ Test all changes on staging
- ✅ Iterate and improve

**Production:**
- Will be merged to production branch when complete
- After full testing and validation
- When feature parity achieved

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

### Slim Framework Migration:
- **Template Architecture**: `docs/technical/slim-template-architecture.md` (CRITICAL)
- **Slim Framework Docs**: https://www.slimframework.com/docs/v4/
- **Twig Template Docs**: https://twig.symfony.com/doc/3.x/

### Project Reference:
- **Project Overview**: Parent `CLAUDE.md` (Architecture, Database Schema)
- **Security Model**: Magic-link only (no password auth)
- **Current Production**: v1.7.3-production-hardening branch

---

## 🎯 Current Status

**Week 4: Simple Forms** ✅ COMPLETED
- ✅ Sponsor email lookup form migrated
- ✅ Modular component architecture established
- ✅ Header/footer extracted to components
- ✅ Professional template patterns documented
- ✅ Deployed and tested on staging

**Next Up: Week 5** 🎯
- Children browse page (search, filter, pagination)
- Individual child profile pages
- Reusable child card component

---

## 🎓 Lessons Learned & Best Practices

**From Week 4 Migration:**

1. ✅ **Extract components from the start** - Don't inline header/footer
2. ✅ **Use component includes** - `{% include 'components/header.twig' %}`
3. ✅ **Single source of truth** - Update once, applies everywhere
4. ✅ **Professional architecture first** - Build it right, not just working
5. ✅ **Test on staging after every feature** - Catch issues early

---

## ✅ Migration Success Criteria

**This migration is successful when:**

- [ ] All public pages migrated to Slim/Twig
- [ ] All admin pages migrated to Slim/Twig
- [ ] Feature parity with v1.7.3 achieved
- [ ] Component-based architecture throughout
- [ ] Clean, maintainable, DRY codebase
- [ ] Fully tested on staging
- [ ] Ready for production merge

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

## 🎨 Slim Framework Template Architecture (CRITICAL)

**ALL Slim templates MUST use modular component pattern.**

**Documentation:** `docs/technical/slim-template-architecture.md`

**Structure:**
```
templates/
├── components/       # Reusable pieces (header.twig, footer.twig)
├── layouts/          # Page structures (base.twig, admin.twig)
└── {feature}/        # Feature templates (extend layouts)
```

**Rules:**
- ✅ Extract header/footer to `components/`
- ✅ Layouts use `{% include 'components/header.twig' %}`
- ✅ Feature templates `{% extends 'layouts/base.twig' %}`
- ❌ NEVER duplicate header/footer code in templates
- ❌ NEVER inline header/footer in feature templates

**Single Source of Truth:** Update header once → applies everywhere

**This is the professional standard. No exceptions.**

---

**Remember:** v1.9.2 is about professional, elegant, DRY architecture. We're building it RIGHT from the start, not just making it work. Every decision prioritizes best practices, maintainability, and clean code.
