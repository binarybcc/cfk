# Tools Inventory - cfk-standalone

**Last Updated:** 2025-12-18
**Branch:** v1.9.3

---

## ✅ Installed & Available

### Quality Analysis Tools

| Tool | Status | Purpose | Command |
|------|--------|---------|---------|
| **PHPStan** | ✅ Installed | Type safety, bug detection | `vendor/bin/phpstan analyse` |
| **PHPCS** | ✅ Installed | PSR-12 compliance checking | `vendor/bin/phpcs --standard=phpcs.xml` |
| **PHP CS Fixer** | ✅ Installed | Auto-format code | `vendor/bin/php-cs-fixer fix` |
| **Rector** | ✅ Installed | Auto-refactoring to modern PHP | `vendor/bin/rector process` |
| **PHPCBF** | ✅ Installed | Auto-fix PHPCS violations | `vendor/bin/phpcbf --standard=phpcs.xml` |

### Testing Tools

| Tool | Status | Purpose | Location |
|------|--------|---------|----------|
| **Security Tests** | ✅ Available | Functional & security validation | `tests/security-functional-tests.sh` |
| **Admin Migration Tests** | ✅ Available | Smoke tests for Slim migration | `tests/smoke-test-admin-migration.sh` |
| **Browser Tests** | ✅ Available | AppleScript browser automation | `tests/applescript-browser-tests.sh` |
| **CSV Tests** | ✅ Available | CSV upload validation | `tests/applescript-csv-tests.sh` |
| **Report Tests** | ✅ Available | Admin reports validation | `tests/automated-report-tests.sh` |

---

## ❌ Documented But NOT Installed

**These tools are mentioned in CLAUDE.md but not actually installed:**

| Tool | Status | Purpose | Notes |
|------|--------|---------|-------|
| **PHPMD** | ❌ Not Installed | Code smells detection | Can install: `composer require --dev phpmd/phpmd` |
| **Psalm** | ❌ Not Installed | Stricter type analysis | Can install: `composer require --dev vimeo/psalm` |
| **PHPMetrics** | ❌ Not Installed | Visual metrics dashboard | Can install: `composer require --dev phpmetrics/phpmetrics` |

---

## 🎯 Slash Commands

**Available in `.claude/commands/`:**

1. **check-branches** - Check feature branches for updates
2. **deploy-production** - Deploy to production (cforkids.org)
3. **deploy-staging** - Deploy to staging (cfkstaging.org)
4. **quality-check** - Quality tools workflow guide
5. **sync-check** - Check local/remote sync status
6. **test-full** - Run complete test suite

---

## 📊 Current Quality Baseline (v1.9.3)

**PHPStan Analysis:**
- Current errors: 161 (no increase acceptable)
- Level: 6
- Baseline established: v1.8.1

**Functional Tests:**
- Current: 36/36 passing (100%)
- Test suite: `tests/security-functional-tests.sh`

---

## 🔧 Recommended Actions

**To complete toolset:**
```bash
# Install missing tools (if needed)
composer require --dev phpmd/phpmd
composer require --dev vimeo/psalm
composer require --dev phpmetrics/phpmetrics
```

**After installation, update:**
- `CLAUDE.md` - Verify all documented tools are accurate
- This inventory - Mark tools as installed

---

## 📝 Notes

- **PHPCBF** is part of PHPCS package (auto-fixer for PHPCS violations)
- **PHPStan** has `.phar` version also available in `vendor/bin/`
- All quality tools have configuration files in root directory:
  - `phpstan.neon` - PHPStan config
  - `phpcs.xml` - PHPCS config
  - `.php-cs-fixer.php` - PHP CS Fixer config
  - `rector.php` - Rector config
  - `psalm.xml` - Psalm config (if installed)
  - `phpmd.xml` - PHPMD config (if installed)

---

**Inventory Status:** ✅ Complete and accurate as of 2025-12-18
