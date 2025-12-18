# CLAUDE.md - v1.9.3 Slim Framework Migration

## 🎯 BRANCH PURPOSE

**v1.9.3 = Complete Slim Framework Migration with Modern Architecture**

**Branch Status:** ✅ **COMPLETE - Weeks 8-9 Admin Panel Migration Finished**

**What's Done:**
- ✅ Complete admin panel migrated to Slim Framework 4.x + Twig 3.x
- ✅ Professional, DRY, elegant architecture throughout
- ✅ Modern MVC patterns and PSR standards
- ✅ 98% code reduction (16,000 lines → 400 lines of redirects)
- ✅ All 13 major admin pages modernized
- ✅ Enhanced security (CSRF, rate limiting, constant-time responses)

**Architecture Standards:**
- ✅ Component-based templates (modular, reusable)
- ✅ PSR-7 HTTP message handling
- ✅ Dependency injection container
- ✅ Single source of truth (DRY principle)
- ✅ Professional naming conventions
- ✅ Clean separation of concerns

---

**Base Branch:** v1.7.3-production-hardening
**Current Status:** Production-ready, needs final validation
**Deployment:** Staging (https://10ce79bd48.nxcli.io/)

---

## 📋 Migration Progress

### ✅ **COMPLETED: Week 8-9 Admin Panel**
- Admin authentication system
- Children management (CRUD)
- Sponsorship management
- CSV import/export
- Year-end reset & archiving
- User management
- Reports dashboard

**See:** `docs/migration/WEEK8-9-MIGRATION-COMPLETE.md`

### 🎯 **Next Steps:**
- Final production validation
- Performance optimization
- Documentation updates
- Security audit

---

## 🚨 Critical Development Rules

### 1. Professional Architecture ALWAYS

**Priority Order:**
1. ✅ **DRY (Don't Repeat Yourself)** - Single source of truth
2. ✅ **Component-based architecture** - Modular, reusable
3. ✅ **Best practices** - PSR standards, design patterns
4. ✅ **Elegant code** - Clean, readable, maintainable
5. ✅ **Testing** - Verify on staging

### 2. Template Architecture Standards (MANDATORY)

**EVERY template must follow component pattern:**
- ✅ Extract header/footer to `templates/components/`
- ✅ Layouts use `{% include 'components/header.twig' %}`
- ✅ Feature templates `{% extends 'layouts/base.twig' %}`
- ❌ NEVER duplicate header/footer code
- ❌ NEVER inline header/footer in templates

**See:** `docs/technical/slim-template-architecture.md`

### 3. Branch Sync Workflow

**At start of session:**
```bash
/sync-check           # Check current branch vs GitHub
/check-branches       # Check for updates in other branches
```

**Merge immediately:**
- ✅ Production bug fixes
- ✅ Security patches
- ✅ Parent branch updates (v1.7.3-production-hardening)

### 4. Quality Standards

**Before deployment:**
```bash
# 1. Static analysis (no new errors)
vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6

# 2. Functional tests (must pass)
./tests/security-functional-tests.sh
```

**Current baseline:**
- PHPStan: 161 errors (no increase acceptable)
- Functional tests: 36/36 passing (100%)

---

## 🔧 Environment Configuration

**Always use .env file for credentials:**
- Never hardcode credentials
- See `.env.example` for template
- Production/staging credentials stay on servers

---

## 🔍 Quality Tools Suite

**Quick Access:** `/quality-check` for detailed guide

**Essential Tools:**
- PHPStan: Type safety, critical bugs
- Functional Tests: Security & workflow validation
- PHPMD: Code smells
- PHPCS: PSR-12 compliance
- PHP CS Fixer: Auto-format
- Rector: Auto-refactoring
- Psalm: Strict type analysis
- PHPMetrics: Visual metrics dashboard

**Auto-fixers (safe anytime):**
```bash
vendor/bin/php-cs-fixer fix
vendor/bin/phpcbf --standard=phpcs.xml
vendor/bin/rector process
```

---

## 📚 Key Documentation

**Migration docs:**
- `docs/migration/WEEK8-9-MIGRATION-COMPLETE.md` - Current status
- `docs/migration/QUICK-REFERENCE.md` - Quick guide
- `docs/technical/slim-template-architecture.md` - Template patterns

**Project reference:**
- Parent `CLAUDE.md` - Architecture, database schema
- `docs/V1.9.3-QUICK-DEPLOY-GUIDE.md` - Deployment guide

**External:**
- [Slim Framework](https://www.slimframework.com/docs/v4/)
- [Twig Templates](https://twig.symfony.com/doc/3.x/)

---

## 🎯 Current Status

**v1.9.3 Status:** ✅ Migration complete, ready for production validation

**Completed:**
- Week 8-9 admin panel migration
- Component-based architecture
- Security enhancements
- Testing infrastructure

**Next Steps:**
- Production validation
- Performance optimization
- Final documentation pass

---

## 🔄 Git Workflow

```bash
# Feature branches
git checkout -b feature/description

# Descriptive commits
git commit -m "feat: Clear description of what changed"

# Quality checks before push
vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6
./tests/security-functional-tests.sh

# Push when clean
git push origin feature/description
```

---

## 📞 Need Help?

**Check these resources:**
1. `docs/migration/WEEK8-9-MIGRATION-COMPLETE.md` - Migration status
2. `docs/testing/week8-9-migration-test-plan.md` - Testing guide
3. `docs/V1.9.3-QUICK-DEPLOY-GUIDE.md` - Deployment guide
4. `/quality-check` command - Quality tools guide

---

**Remember:** v1.9.3 represents professional, modern PHP development. Every decision prioritizes maintainability, security, and elegant architecture.
