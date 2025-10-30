---
description: Run complete test suite (PHPStan, functional tests, Docker checks)
---

# Full Test Suite

**Purpose:** Comprehensive testing before commits, deployments, or releases
**When to use:** Before committing, before deploying, after major changes
**Duration:** 2-5 minutes depending on codebase size

---

## Testing Protocol

Run the complete test suite to ensure code quality and functionality.

### Step 1: Environment Check

**Verify Docker is running:**
```bash
# Check if Docker/OrbStack is running
docker --version 2>/dev/null
DOCKER_STATUS=$?

if [ $DOCKER_STATUS -eq 0 ]; then
    # Check if containers are running
    docker-compose ps | grep -q "Up"
    CONTAINERS_STATUS=$?

    if [ $CONTAINERS_STATUS -eq 0 ]; then
        echo "✅ Docker containers running"
    else
        echo "⚠️ Docker containers not running"
        echo "Starting containers..."
        docker-compose up -d
    fi
else
    echo "❌ Docker not installed or not running"
fi
```

**Report:**
```
🐳 ENVIRONMENT CHECK
====================
Docker: [✅ Running / ❌ Not available]
Containers: [✅ Up / ⚠️ Starting / ❌ Down]
```

### Step 2: PHPStan Analysis (All Production Code)

**Run PHPStan on ALL production directories:**
```bash
# Critical: Analyze ALL production code (Production First Principle!)
vendor/bin/phpstan analyse \
  admin/ \
  includes/ \
  pages/ \
  cron/ \
  src/ \
  --level 6 \
  --no-progress \
  2>&1 | tee phpstan-test-results.txt
```

**Capture results:**
```bash
# Count errors
ERROR_COUNT=$(grep -c "\[ERROR\]" phpstan-test-results.txt || echo "0")

# Extract critical errors (if any)
CRITICAL_ERRORS=$(grep -A 2 "ERROR" phpstan-test-results.txt | head -20)
```

**Report:**
```
🔍 PHPSTAN ANALYSIS
===================
Scope: admin/, includes/, pages/, cron/, src/
Level: 6 (Strict type checking)

Results:
Total Errors: [N]

[If errors > 0, show first 5-10 critical errors]
[If errors = 0, show: ✅ No errors found - code is clean!]
```

**Thresholds:**
- ✅ 0 errors: PASS
- ⚠️ 1-10 errors: REVIEW (acceptable for WIP)
- ❌ 10+ errors: FAIL (must fix before commit)

### Step 3: Functional Test Suite

**Run the comprehensive functional tests:**
```bash
# Run security and functional tests
./tests/security-functional-tests.sh 2>&1 | tee functional-test-results.txt

# Capture exit code
TEST_EXIT_CODE=$?
```

**Parse results:**
```bash
# Count passing/failing tests
TOTAL_TESTS=$(grep -c "Test:" functional-test-results.txt || echo "0")
PASSED_TESTS=$(grep -c "✅" functional-test-results.txt || echo "0")
FAILED_TESTS=$(grep -c "❌" functional-test-results.txt || echo "0")
```

**Report:**
```
🧪 FUNCTIONAL TESTS
===================
Test Suite: tests/security-functional-tests.sh

Results:
Total Tests: [N]
Passed: [N] ✅
Failed: [N] ❌

Pass Rate: [N]%

[If failures, show which tests failed and why]
```

**Baseline comparison:**
```
Expected Baseline: 35/36 tests passing (97.2%)
Current Results: [N]/36 tests passing ([N]%)

Status: [✅ At baseline / ⚠️ Below baseline / ❌ Regression detected]
```

**Thresholds:**
- ✅ 35-36 passing: PASS (at baseline)
- ⚠️ 32-34 passing: REVIEW (minor regression)
- ❌ <32 passing: FAIL (significant regression)

### Step 4: Docker Environment Tests

**Test Docker environment functionality:**
```bash
# Test web container
docker-compose exec -T web php -v
WEB_PHP=$?

# Test database connection
docker-compose exec -T web php -r "
require_once 'config/config.php';
try {
    \$pdo = new PDO('mysql:host=db;dbname=cfk_sponsorship_dev', 'cfk_user', 'cfk_pass');
    echo 'Database connection: OK';
} catch (PDOException \$e) {
    echo 'Database connection: FAILED - ' . \$e->getMessage();
}
"
DB_CONNECTION=$?

# Check if site is accessible
curl -s -o /dev/null -w "%{http_code}" http://localhost:8082
HTTP_STATUS=$?
```

**Report:**
```
🐳 DOCKER ENVIRONMENT
=====================
Web Container: [✅ Running / ❌ Down]
PHP Version: [version]
Database: [✅ Connected / ❌ Connection failed]
HTTP Access: [✅ Responding / ❌ Not accessible]
Site URL: http://localhost:8082
```

### Step 5: Code Quality Checks

**Check for common issues:**
```bash
# Check for debug statements
grep -r "var_dump\|print_r\|dd(" admin/ pages/ includes/ src/ --color=never | wc -l
DEBUG_COUNT=$(grep -r "var_dump\|print_r\|dd(" admin/ pages/ includes/ src/ --color=never 2>/dev/null | wc -l)

# Check for TODO/FIXME comments
TODO_COUNT=$(grep -r "TODO\|FIXME" admin/ pages/ includes/ src/ --color=never 2>/dev/null | wc -l)

# Check for .env files that might be committed
git ls-files | grep "\.env$" | grep -v "\.env\.example"
ENV_FILES=$?
```

**Report:**
```
🔎 CODE QUALITY CHECKS
======================
Debug Statements: [N] [⚠️ if > 0]
TODO/FIXME Comments: [N]
.env Files in Git: [✅ None / ❌ Found (security risk!)]
```

### Step 6: Test Results Summary

**Aggregate all results:**
```
═══════════════════════════════════════
📊 TEST SUITE SUMMARY
═══════════════════════════════════════

Environment:
  Docker:        [✅/❌]
  Database:      [✅/❌]
  HTTP:          [✅/❌]

Code Analysis:
  PHPStan:       [✅ 0 errors / ⚠️ N errors / ❌ N errors]
  Debug Code:    [✅ None / ⚠️ N found]
  TODOs:         [N items]

Functional Tests:
  Pass Rate:     [N]/36 ([N]%)
  Status:        [✅ At baseline / ⚠️ Below / ❌ Failing]

═══════════════════════════════════════
OVERALL STATUS: [✅ PASS / ⚠️ REVIEW / ❌ FAIL]
═══════════════════════════════════════
```

### Step 7: Recommendations

**Based on results, provide actionable guidance:**

#### ✅ All Tests Pass (PASS)
```
✅ ALL TESTS PASSED!

Your code is ready for:
- ✅ Committing to git
- ✅ Deploying to staging
- ✅ Creating pull request

Next steps:
1. Review changes: git diff
2. Commit: git commit -m "descriptive message"
3. Push: git push origin [branch]
4. Deploy to staging: /deploy-staging
```

#### ⚠️ Minor Issues (REVIEW)
```
⚠️ TESTS PASSED WITH WARNINGS

Issues to review:
[List specific warnings]

Recommendation:
- Address warnings before production deployment
- OK to commit for staging testing
- Must fix before production

Next steps:
1. Review warnings above
2. Fix critical issues
3. Re-run: /test-full
4. Deploy to staging for further testing
```

#### ❌ Tests Failed (FAIL)
```
❌ TESTS FAILED - DO NOT DEPLOY

Critical failures:
[List specific failures]

Action required:
1. Fix critical errors listed above
2. Re-run tests: /test-full
3. DO NOT commit or deploy until tests pass

Common fixes:
- PHPStan errors: Check type hints, null safety
- Functional test failures: Check business logic
- Docker issues: Restart containers, check .env
```

---

## Quick Mode (Faster Tests)

**For rapid iteration during development:**

```bash
# Run only critical checks
# 1. PHPStan on changed files only
git diff --name-only | grep "\.php$" | xargs vendor/bin/phpstan analyse --level 6

# 2. Smoke test (basic functionality)
curl -s http://localhost:8082 > /dev/null && echo "✅ Site accessible"

# 3. Database connection
docker-compose exec -T web php -r "new PDO('mysql:host=db;dbname=cfk_sponsorship_dev', 'cfk_user', 'cfk_pass');" && echo "✅ DB connected"
```

---

## When to Run Full Tests

**Always run before:**
- ✅ Committing to git (especially production branches)
- ✅ Deploying to staging
- ✅ Deploying to production
- ✅ Creating pull requests
- ✅ Merging feature branches

**Good practice to run after:**
- Major refactoring
- Dependency updates
- Database schema changes
- Configuration changes

---

## Test Artifacts

**Tests create these files:**
```
phpstan-test-results.txt          # PHPStan output
functional-test-results.txt       # Functional test output
```

**Review artifacts:**
```bash
# View detailed PHPStan results
cat phpstan-test-results.txt

# View detailed functional test results
cat functional-test-results.txt

# Clean up artifacts
rm phpstan-test-results.txt functional-test-results.txt
```

---

## Integration with Git Workflow

**Recommended workflow:**
```
1. Make code changes
2. /test-full                    → Verify everything works
3. Fix any issues found
4. /test-full                    → Confirm fixes work
5. git add .
6. git commit -m "message"
7. /sync-check                   → Check repository sync
8. git push
9. /deploy-staging               → Test in staging
```

---

## Troubleshooting

### Docker Issues:
```bash
# Restart Docker containers
docker-compose down
docker-compose up -d

# Check container logs
docker-compose logs web
docker-compose logs db
```

### PHPStan Issues:
```bash
# Clear PHPStan cache
rm -rf .phpunit.cache/

# Run with more detail
vendor/bin/phpstan analyse --level 6 --debug
```

### Functional Test Issues:
```bash
# Run tests with verbose output
bash -x ./tests/security-functional-tests.sh

# Check specific test
# [edit test file to run only one test]
```

---

## Success Criteria

**Tests are considered PASSED when:**
- ✅ PHPStan: 0 errors (or <10 for WIP)
- ✅ Functional tests: 35-36 passing (97%+ pass rate)
- ✅ Docker: All services running
- ✅ Database: Connection successful
- ✅ HTTP: Site accessible
- ✅ No debug code in production files
- ✅ No .env files in git

**Ready to deploy when:**
- ✅ All tests PASS
- ✅ No critical warnings
- ✅ Code reviewed (if team project)
- ✅ Branch up to date with remote

---

**Remember:** Testing is not optional. It protects production and saves debugging time later.
