# PHPStan Critical Issues Report

**Generated**: October 25, 2025
**Scan Level**: 8 (strictest)
**Files Scanned**: admin/, includes/, pages/, src/

---

## 🚨 CRITICAL: Undefined Database Methods (Would Cause Fatal Errors)

These would have caught the issues we just fixed manually!

### 1. **ajax_handler.php:135** - `Database::fetchOne()` doesn't exist
```php
Call to an undefined static method Database::fetchOne().
```
**Impact**: Fatal error when toggling child status
**Fix**: Change to `Database::fetchRow()`

### 2. **import_csv.php:221, 224, 228** - `Database::query()` doesn't exist (3 occurrences)
```php
Call to an undefined static method Database::query().
```
**Impact**: Fatal error when deleting all children during import
**Fix**: Change to `Database::execute()`
**Note**: This is the SAME issue we just fixed in reports.php!

### 3. **request-magic-link.php:100** - Unknown class `CFK_Email_Manager`
```php
Call to static method getMailer() on an unknown class CFK_Email_Manager.
```
**Impact**: Fatal error when requesting magic link
**Fix**: Verify class exists or update to correct namespace

### 4. **verify-magic-link.php:258** - Unknown class `CFK_Email_Manager`
```php
Call to static method getMailer() on an unknown class CFK_Email_Manager.
```
**Impact**: Fatal error when verifying magic link
**Fix**: Verify class exists or update to correct namespace

---

## ⚠️ HIGH PRIORITY: Null Safety Issues

### admin/change_password.php:46
```php
Offset 'password_hash' does not exist on array|null.
```
**Impact**: Potential crash if user query returns null

### admin/manage_admins.php:129-130
```php
Offset 'username' does not exist on array|null.
```
**Impact**: Potential crash if admin query returns null

### admin/reset_password.php:78, 85, 88
```php
Offset 'id' does not exist on array|null.
Offset 'username' does not exist on array|null.
```
**Impact**: Potential crash during password reset

---

## 📊 Summary Statistics

**Total Issues Found**: 200+
**Critical (Fatal Errors)**: 7 issues
**High Priority (Null Safety)**: 15+ issues
**Medium (Type Hints)**: 150+ issues
**Low (Code Style)**: 30+ issues

---

## 🎯 Recommended Actions

### Immediate (Fix Now - Would Cause Fatal Errors)

1. **Fix Database::fetchOne() in ajax_handler.php**
   ```php
   // Line 135 - WRONG
   $child = Database::fetchOne("SELECT status FROM children WHERE id = ?", [$childId]);

   // CORRECT
   $child = Database::fetchRow("SELECT status FROM children WHERE id = ?", [$childId]);
   ```

2. **Fix Database::query() in import_csv.php (3 places)**
   ```php
   // Lines 221, 224, 228 - WRONG
   Database::query("TRUNCATE TABLE children");

   // CORRECT
   Database::execute("TRUNCATE TABLE children");
   ```

3. **Verify CFK_Email_Manager class exists** or fix magic link pages

### High Priority (Prevent Crashes)

4. Add null checks before accessing array offsets in:
   - change_password.php
   - manage_admins.php
   - reset_password.php
   - year_end_reset.php

### Medium Priority (Code Quality)

5. Add array type hints to function parameters (150+ places)
6. Fix unreachable code in reports.php

---

## 🔧 Available Static Analysis Tools

You have excellent tools already installed:

### 1. **PHPStan** (What we just ran)
- **Purpose**: Find bugs, type errors, undefined methods
- **Command**: `composer phpstan`
- **Level**: Currently set to 8 (strictest)
- **Result**: Would have caught Database::query() and Database::fetchOne() issues!

### 2. **Rector** (Code Modernization)
- **Purpose**: Auto-upgrade PHP code to modern standards
- **Command**: `composer rector` (dry-run) or `composer rector:fix`
- **Use**: Automatically fix deprecated patterns

### 3. **PHP_CodeSniffer** (Style)
- **Purpose**: Enforce coding standards
- **Command**: `composer cs-check` or `composer cs-fix`

### 4. **PHP-CS-Fixer** (Style)
- **Purpose**: Auto-fix coding style
- **Command**: `composer php-cs-fixer` or `composer php-cs-fixer:fix`

### 5. **PHPUnit** (Testing)
- **Purpose**: Run automated tests
- **Command**: `composer test`

---

## 🚀 CI/CD Integration Recommendation

Add to your deployment workflow:

```bash
# Before deploying to production:
composer phpstan              # Catch fatal errors
composer rector               # Check for deprecated code
composer test                 # Run test suite

# If all pass, then deploy
```

This would have prevented the Database::query() and Database::fetchOne() issues from reaching production!

---

## 📝 Next Steps

1. **Fix the 7 critical issues** (Database methods, Email Manager)
2. **Add null safety checks** for database queries
3. **Consider adding PHPStan to CI/CD** to catch issues automatically
4. **Run PHPStan before each production deployment**

**Note**: These tools are already configured and ready to use. We just need to enable them in the workflow!
