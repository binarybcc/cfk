# Quality Tools Check - Code Quality Analysis Workflow

**Purpose:** Run comprehensive code quality checks before commits, deployments, or releases
**When to use:** Before staging deployment, before production deployment, weekly quality audits
**Time:** 2-5 minutes depending on scope

---

## Quick Quality Check (Pre-Commit)

**Use before committing code changes:**

```bash
# 1. Static Analysis (Critical errors only)
vendor/bin/phpstan analyse [changed-directory] --level 6 --error-format=table

# 2. Code Standards (Quick check)
vendor/bin/phpcs [changed-file.php] --standard=phpcs.xml

# 3. Syntax Check
php -l [changed-file.php]
```

**Expected:** Zero critical errors, clean syntax

---

## Full Quality Suite (Pre-Deployment)

**Run before deploying to staging or production:**

### Step 1: Static Analysis (PHPStan)

```bash
# Analyze all production code
vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6

# Expected: 161 errors (baseline - no increase acceptable)
# Review any NEW errors and fix before deploying
```

**What it finds:** Type errors, undefined methods, null pointer risks, unreachable code

**Action items:**
- ✅ No new errors since baseline → Safe to deploy
- ❌ New errors found → Fix before deploying

### Step 2: Functional Tests

```bash
# Run security and functional test suite
./tests/security-functional-tests.sh
```

**Expected:** 35/36 tests passing (one known skip for sponsor deletion)

**Action items:**
- ✅ 35/36 pass → Deploy
- ❌ Failures → Fix before deploying

---

## Advanced Analysis (Weekly/Monthly)

**Run during maintenance windows for deeper insights:**

### Code Smells (PHPMD)

```bash
# Find code smells and potential bugs
vendor/bin/phpmd admin/,includes/,pages/,cron/,src/ text phpmd.xml | head -50
```

**What it finds:** Unused variables, complex functions, naming issues

### Code Formatting (PHP CS Fixer)

```bash
# Apply formatting
vendor/bin/php-cs-fixer fix
```

### Automated Refactoring (Rector)

```bash
# Apply refactoring
vendor/bin/rector process
```

### Metrics Report (PHPMetrics)

```bash
# Generate visual quality dashboard
vendor/bin/phpmetrics --report-html=docs/metrics/ admin/ includes/ pages/ cron/ src/
open docs/metrics/index.html
```

---

## Recommended Workflow by Scenario

### Before Staging Deployment

**Time: 2-3 minutes**

```bash
# Full quality check
vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6
./tests/security-functional-tests.sh
```

**Must pass:** PHPStan (no new errors), Functional tests (35/36)

### Weekly Cleanup Session

**Time: 30-60 minutes**

```bash
# Run all auto-fixers
vendor/bin/php-cs-fixer fix
vendor/bin/phpcbf --standard=phpcs.xml
vendor/bin/rector process

# Review and commit
git add -A
git commit -m "style: Weekly code quality improvements"
```

### Monthly Quality Audit

**Time: 1-2 hours**

```bash
# Full analysis suite + regenerate metrics
vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6
vendor/bin/psalm
vendor/bin/phpmd admin/,includes/,pages/,cron/,src/ text phpmd.xml
vendor/bin/phpmetrics --report-html=docs/metrics/ admin/ includes/ pages/ cron/ src/

# Review metrics dashboard
open docs/metrics/index.html
```

---

## Quick Commands for Copy/Paste

```bash
# Before staging deploy
vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6 && ./tests/security-functional-tests.sh

# Before production deploy  
vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6 && ./tests/security-functional-tests.sh && vendor/bin/psalm --show-info=false

# Weekly cleanup
vendor/bin/php-cs-fixer fix && vendor/bin/phpcbf --standard=phpcs.xml && vendor/bin/rector process

# Monthly metrics
vendor/bin/phpmetrics --report-html=docs/metrics/ admin/ includes/ pages/ cron/ src/ && open docs/metrics/index.html
```

---

## Priority Guide

**MUST RUN before any deployment:**
1. PHPStan static analysis
2. Functional test suite

**SHOULD RUN weekly:**
3. Auto-fixers (PHP CS Fixer, PHPCBF, Rector)

**NICE TO RUN monthly:**
4. Psalm stricter analysis
5. PHPMetrics dashboard
6. PHPMD code smell detection

**Goal:** Continuous improvement, not perfection. Maintain baseline, prevent regressions, improve gradually.
