# Docker/OrbStack Production Parity Setup

**Created:** 2025-10-24
**Purpose:** Configure Docker environment to match Nexcess production settings
**Status:** ✅ Complete and Active

---

## What Was Enhanced

### 1. Production-Like PHP Configuration

**File:** `docker/php.ini`

Matches typical Nexcess shared hosting PHP 8.2 settings:
- Error handling: `display_errors=Off`, `log_errors=On` (production mode)
- Memory limit: 256M
- Execution time: 300 seconds
- Upload limits: 50M files, 50M POST
- OPcache enabled for performance
- Session security: `httponly=On`, `strict_mode=On`
- Timezone: America/New_York

### 2. Enhanced docker-compose.yml

**Added:**
- Custom PHP configuration mount (`docker/php.ini`)
- Logs directory mount for error tracking
- Production-like environment variables
- OPcache extension enabled
- Proper log directory permissions

**Key Changes:**
```yaml
volumes:
  - ./docker/php.ini:/usr/local/etc/php/conf.d/custom.ini
  - ./logs:/var/www/html/logs

environment:
  - PHP_MEMORY_LIMIT=256M
  - PHP_MAX_EXECUTION_TIME=300
  - PHP_UPLOAD_MAX_FILESIZE=50M
  - PHP_POST_MAX_SIZE=50M
  - PHP_DISPLAY_ERRORS=Off
  - PHP_LOG_ERRORS=On
```

### 3. Automated Verification Script

**File:** `tests/v1.8-cleanup-verification.sh`

Comprehensive testing script that verifies:
- ✅ Deleted wrapper files are gone
- ✅ Autoloader loads all namespaced classes
- ✅ Backward compatibility aliases work
- ✅ No broken `require_once` statements
- ✅ Cron jobs execute without errors
- ✅ Critical functions still exist
- ✅ PHPStan static analysis (if installed)
- ✅ Functional test suite integration

**Usage:**
```bash
🐳 DOCKER:
./tests/v1.8-cleanup-verification.sh
```

**Output:** Color-coded test results with pass/fail status and detailed log

### 4. Testing Checklist

**File:** `docs/testing/v1.8-cleanup-testing-checklist.md`

Comprehensive manual testing guide with:
- Prerequisites checklist
- Automated verification steps
- Autoloader testing procedures
- Cron job testing
- Admin panel testing (dashboard, children, CSV import, reports, sponsorships)
- Public-facing pages testing (browse, reservation, sponsor portal)
- Error log review
- Functional test suite integration
- Staging environment testing (optional)
- Final approval checklist
- Rollback procedure

### 5. Production-Like .env Template

**File:** `.env.production.test`

Template for testing with production-like settings in Docker:
- Database configuration (Docker containers)
- SMTP configuration (test or production)
- `APP_DEBUG=false` (production mode)
- Error reporting off (production mode)

---

## Quick Start: Testing v1.8-cleanup

### Step 1: Verify Environment

```bash
🏠 LOCAL:
git branch --show-current  # Should be: v1.8-cleanup
docker-compose ps          # All containers should be running
```

### Step 2: Run Automated Verification

```bash
🐳 DOCKER:
./tests/v1.8-cleanup-verification.sh
```

**Expected Result:** 100% pass rate

### Step 3: Manual Testing

Follow the comprehensive checklist:
```bash
🏠 LOCAL:
open docs/testing/v1.8-cleanup-testing-checklist.md
```

**Key Areas to Test:**
1. Admin Dashboard - `http://localhost:8082/admin/`
2. CSV Import - `http://localhost:8082/admin/import_csv.php`
3. Browse Children - `http://localhost:8082/?page=children`
4. Sponsorship Flow - Complete reservation workflow
5. Cron Jobs - Execute manually and check logs

### Step 4: Check Error Logs

```bash
🐳 DOCKER:
docker-compose logs web | grep -i "error\|fatal" > logs/docker-errors.log
cat logs/docker-errors.log
```

**Expected Result:** No fatal errors or class loading failures

### Step 5: Run Functional Tests

```bash
🏠 LOCAL:
./tests/security-functional-tests.sh
```

**Expected Result:** 35/36 tests pass (same as v1.7 baseline)

---

## Docker vs Nexcess Staging Decision Matrix

### Use Docker Testing When:

✅ **Rapid iteration during development**
- Make change → test immediately (no deployment wait)
- Multiple test cycles in minutes

✅ **Autoloader/class loading verification**
- v1.8-cleanup primary risk
- Docker perfectly tests PHP autoloading

✅ **Code quality checks**
- PHPStan analysis
- Syntax validation
- Functional test suite

✅ **Cost sensitivity**
- Free (uses local resources)
- No additional hosting costs

### Use Nexcess Staging When:

✅ **Email delivery critical**
- Need to verify actual SMTP behavior
- HTML rendering in real email clients

✅ **Production-specific issues**
- File permissions differences
- Server-specific PHP extensions
- .htaccess behavior

✅ **Final sign-off before production**
- Last verification step
- Stakeholder approval

✅ **Performance testing**
- Production-like load testing
- Real database response times

---

## Current Status

### ✅ Docker Environment (Active)

- **PHP Version:** 8.2.29
- **OPcache:** ✅ Enabled
- **Memory Limit:** 256M
- **Display Errors:** Off (production mode)
- **Log Errors:** On
- **Custom php.ini:** ✅ Mounted and active

### 🔍 What's Being Tested

Branch: `v1.8-cleanup`
Changes: Removed 3,624 lines of deprecated wrapper files (9 files)
Risk: Autoloader/require failures

**Deleted Files:**
1. `includes/sponsorship_manager.php` (830 lines)
2. `includes/email_manager.php` (763 lines)
3. `includes/csv_handler.php` (561 lines)
4. `includes/archive_manager.php` (429 lines)
5. `includes/report_manager.php` (394 lines)
6. `includes/avatar_manager.php` (353 lines)
7. `includes/backup_manager.php` (236 lines)
8. `includes/import_analyzer.php` (29 lines)
9. `includes/magic_link_manager.php` (29 lines)

**Updated Files:**
1. `includes/functions.php` (removed avatar_manager require)
2. `cron/cleanup_magic_links.php` (removed magic_link_manager require)
3. `cron/cleanup_portal_tokens.php` (removed sponsorship_manager require)
4. `cron/cleanup_expired_sponsorships.php` (removed sponsorship_manager require)

---

## Verification Commands

### Check PHP Settings

```bash
🐳 DOCKER:
docker-compose exec web php -i | grep -E "memory_limit|max_execution_time|display_errors|opcache"
```

### Test Autoloader

```bash
🐳 DOCKER:
docker-compose exec web php -r "
require_once 'config/config.php';
echo (class_exists('CFK\\Sponsorship\\Manager') ? 'Autoloader works!' : 'Autoloader failed!') . PHP_EOL;
"
```

### Test Cron Jobs

```bash
🐳 DOCKER:
docker-compose exec web bash
cd /var/www/html
php cron/cleanup_magic_links.php && echo "Exit code: $?"
php cron/cleanup_portal_tokens.php && echo "Exit code: $?"
php cron/cleanup_expired_sponsorships.php && echo "Exit code: $?"
```

### Monitor Real-Time Errors

```bash
🐳 DOCKER:
docker-compose exec web tail -f /var/log/php_errors.log
```

*(Open another terminal and perform admin actions to see errors in real-time)*

---

## Troubleshooting

### Issue: Container won't start

```bash
🏠 LOCAL:
docker-compose logs web
# Look for PHP extension errors or config issues
```

### Issue: Autoloader not working

```bash
🐳 DOCKER:
docker-compose exec web php -r "
require_once 'config/config.php';
var_dump(class_exists('CFK\\Sponsorship\\Manager'));
"
```

### Issue: Custom php.ini not loading

```bash
🐳 DOCKER:
docker-compose exec web ls -la /usr/local/etc/php/conf.d/
docker-compose exec web cat /usr/local/etc/php/conf.d/custom.ini
```

### Issue: Logs directory permissions

```bash
🐳 DOCKER:
docker-compose exec web ls -la /var/www/html/ | grep logs
docker-compose exec web chown -R www-data:www-data /var/www/html/logs
```

---

## Next Steps

1. **Complete Docker Testing** ☐
   - Run automated verification script
   - Manual testing checklist
   - Error log review
   - Functional test suite

2. **Evaluate Staging Need** ☐
   - Based on Docker results
   - If 100% Docker pass: staging optional
   - If email critical: use staging

3. **Production Deployment** ☐
   - Only after 100% test pass rate
   - Tag v1.7 for rollback safety
   - Deploy v1.8-cleanup
   - Monitor production logs

---

## Files Created

1. `docker/php.ini` - Production-like PHP configuration
2. `docker-compose.yml` - Enhanced with production parity (UPDATED)
3. `tests/v1.8-cleanup-verification.sh` - Automated testing script
4. `docs/testing/v1.8-cleanup-testing-checklist.md` - Comprehensive manual testing guide
5. `.env.production.test` - Production-like environment template
6. `docs/testing/docker-production-parity-setup.md` - This document

---

## Recommendation Summary

**For v1.8-cleanup testing:**

### Primary Strategy: Docker/OrbStack (95% coverage)
- Fast iteration
- Catches all autoloader issues
- No additional cost
- Complete functional testing

### Optional: Nexcess Staging (Final 5%)
- Only if Docker testing reveals concerns
- Final sign-off before production
- Email delivery verification
- Stakeholder approval

**Most efficient approach:** Start with Docker, evaluate staging need based on results.
