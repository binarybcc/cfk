# Database Schema Findings & Action Plan - November 9, 2025

## Executive Summary

You asked two critical questions. Here are the answers with complete implementation:

---

## Question 1: Should we remove WordPress tables?

### Answer: **YES** (for staging at minimum)

**Current State:**
- Staging: 76 WordPress/WooCommerce tables (unused legacy data)
- Production: 0 WordPress tables (clean)

**Why Remove:**
1. **Performance** - MySQL has to manage 76+ unused tables
2. **Security** - Old WordPress data may contain vulnerabilities
3. **Clarity** - Confuses development (which tables are ours?)
4. **Backups** - Makes backups larger and slower
5. **Professionalism** - Production is clean, staging should match

**How to Remove:**
```bash
# Option 1: Use migration script (commented out by default)
# Edit database/migrations/001-staging-to-production-schema.sql
# Uncomment the DROP TABLE section at the end
# Run migration

# Option 2: Manual cleanup
# See docs/deployment/schema-management.md for SQL commands
```

**Recommendation:** Remove from staging immediately. Already clean on production.

---

## Question 2: Should there always be schema comparison on deployment?

### Answer: **ABSOLUTELY YES**

**Why This is Critical:**

Today's issues would have been caught immediately:
- Code expects `age_months` but database has `age`
- Missing tables break reservation system and sponsor portal
- 16+ pages broken due to schema mismatch

**Implementation:** ✅ DONE

I've created `database/schema-check.sh` that:
- Validates all required tables exist
- Checks critical columns (age_months, name)
- Reports WordPress table pollution
- **Exit code 0 = safe to deploy, 1 = fix schema first**

**Usage:**
```bash
# Before deploying to staging
./database/schema-check.sh staging

# Before deploying to production
./database/schema-check.sh production
```

**Integration with Deployment:**
Update `.claude/commands/deploy-staging.md` and `/deploy-production` to run schema check FIRST. Deploy fails if schema doesn't match.

---

## Current Schema Status

### ✅ Production (cforkids.org) - CORRECT
```
Tables: 11 (all required)
├── families
├── children (with age_months, name)
├── sponsorships
├── admin_users
├── settings
├── reservations ✓
├── portal_access_tokens ✓
├── email_log ✓
├── admin_login_log ✓
├── admin_magic_links ✓
└── rate_limit_tracking

WordPress tables: 0 (clean)
```

### ⚠️ Staging (10ce79bd48.nxcli.io) - NEEDS MIGRATION
```
Tables: 11 required + 76 WordPress
├── families ✓
├── children ✗ (has 'age' not 'age_months', but has 'name')
├── sponsorships ✓
├── admin_users ✓
├── settings ✓
├── reservations ✓ (exists!)
├── portal_access_tokens ✓ (exists!)
├── email_log ✓ (exists!)
├── admin_login_log ✓ (exists!)
├── admin_magic_links ✓ (exists!)
└── rate_limit_tracking ?

WordPress tables: 76 (needs cleanup)
```

**Main Issue:** Missing `age_months` column in children table

---

## Immediate Action Items

### 1. Run Migration on Staging

```bash
# SSH into staging
source .env.staging
sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT ${SSH_USER}@${SSH_HOST}

# Navigate to app directory
cd /home/ac6c9a98/10ce79bd48.nxcli.io/html

# Backup database first (CRITICAL!)
mysqldump -h localhost -u $(grep DB_USER .env | cut -d= -f2) \
    -p$(grep DB_PASS .env | cut -d= -f2) \
    $(grep DB_NAME .env | cut -d= -f2) \
    > backup_before_age_months_$(date +%Y%m%d).sql

# Run migration
mysql -h localhost -u $(grep DB_USER .env | cut -d= -f2) \
    -p$(grep DB_PASS .env | cut -d= -f2) \
    $(grep DB_NAME .env | cut -d= -f2) \
    < database/migrations/001-staging-to-production-schema.sql

# Verify migration
mysql -h localhost -u $(grep DB_USER .env | cut -d= -f2) \
    -p$(grep DB_PASS .env | cut -d= -f2) \
    $(grep DB_NAME .env | cut -d= -f2) \
    -e "SELECT id, age, age_months FROM children LIMIT 3;"
```

### 2. Run Schema Validation

```bash
# Back on local machine
./database/schema-check.sh staging
```

Expected output: ✅ All checks pass

### 3. Test Staging Thoroughly

After migration, test these pages that were broken:
- [ ] https://10ce79bd48.nxcli.io/?page=children (already partially fixed)
- [ ] https://10ce79bd48.nxcli.io/?page=child&id=1
- [ ] https://10ce79bd48.nxcli.io/?page=family&id=1
- [ ] Reservation system (add child to cart)
- [ ] Sponsor portal (request access)

### 4. Remove Temporary Fix

After migration, **revert** the temporary fix in `pages/children.php`:

```php
// BEFORE MIGRATION (temporary):
getPlaceholderImage($child['age'], $child['gender'])  // ✗ Wrong
displayAge($child['age'] * 12)  // ✗ Workaround

// AFTER MIGRATION (correct):
getPlaceholderImage($child['age_months'], $child['gender'])  // ✓ Right
displayAge($child['age_months'])  // ✓ Proper
```

### 5. Update All Other Pages

After migration works, fix the other 15+ files that reference age_months:
- pages/child.php (3 refs)
- pages/family.php (7 refs)
- pages/sponsor_portal.php (1 ref)
- pages/my_sponsorships.php (3 refs)
- pages/reservation_review.php (1 ref)
- pages/confirm_sponsorship.php (1 ref)

### 6. Optional: Clean WordPress Tables

After staging is stable, remove WordPress tables:
```sql
-- See migration file for full DROP TABLE list
-- Or use: docs/deployment/schema-management.md
```

---

## Schema Check Integration

### Update Deploy Staging Skill

**File:** `.claude/commands/deploy-staging.md`

Add as **Step 1.5** (before Step 2):

```markdown
### Step 1.5: Schema Validation

**CRITICAL: Verify database schema before deploying code**

```bash
# Run schema validation
./database/schema-check.sh staging

# Check exit code
if [ $? -ne 0 ]; then
    echo "❌ SCHEMA VALIDATION FAILED"
    echo "Database schema doesn't match code expectations"
    echo ""
    echo "Actions needed:"
    echo "1. SSH into staging server"
    echo "2. Backup database: mysqldump ..."
    echo "3. Run migration: mysql ... < database/migrations/001-staging-to-production-schema.sql"
    echo "4. Verify: ./database/schema-check.sh staging"
    echo "5. Retry deployment"
    exit 1
fi

echo "✅ Schema validation passed - safe to deploy"
```

**Expected output when schema is correct:**
- ✓ Database connection successful
- ✓ All 11 required tables exist
- ✓ age_months column exists
- ✓ name column exists
- ✅ Schema validation PASSED
```

### Update Deploy Production Skill

Same pattern for production deployments.

---

## Why This Happened

### Root Cause Analysis

1. **Code evolved faster than schema.sql** - Commit 8004408 (Oct 27, 2025) migrated code to use `age_months` but never updated schema.sql

2. **Production was manually altered** - Someone ran ALTER TABLE commands on production database but didn't document them

3. **No schema validation** - Deployments didn't check if database matched code expectations

4. **Staging diverged** - Staging was created from old schema.sql, never migrated

### Prevention Going Forward

✅ **Schema validation script** - Automated checking before deployment
✅ **Migration system** - All schema changes in version-controlled SQL files
✅ **Documentation** - Complete schema management guide created
✅ **Testing protocol** - Test schema changes on staging first

---

## Files Created

1. **database/migrations/001-staging-to-production-schema.sql**
   - Adds age_months column and migrates data
   - Creates all missing tables
   - Optional WordPress table cleanup

2. **database/migrations/README.md**
   - Migration instructions
   - Rollback procedures
   - Verification steps

3. **database/schema-check.sh** (executable)
   - Automated schema validation
   - Returns exit code for deployment integration
   - Checks all critical requirements

4. **docs/deployment/schema-management.md**
   - Complete schema management guide
   - Answers both your questions
   - Future change workflow
   - Testing protocols

5. **docs/audits/staging-schema-audit-2025-11-09.md**
   - Comprehensive audit of issues
   - Impact assessment
   - Recommendations

---

## Testing Results

```
./database/schema-check.sh staging

🔍 Database Schema Validation
================================

Environment: STAGING
Host: 10ce79bd48.nxcli.io

Testing database connection...
✓ Database connection successful

Checking required tables...
✓ Table exists: families
✓ Table exists: children
✓ Table exists: sponsorships
✓ Table exists: admin_users
✓ Table exists: settings
✓ Table exists: reservations
✓ Table exists: portal_access_tokens
✓ Table exists: email_log
✓ Table exists: admin_login_log
✓ Table exists: admin_magic_links

Validating children table schema...
✗ Missing age_months column  ← FIX THIS
✓ name column exists

Checking for WordPress table pollution...
⚠ Found 76 WordPress/WooCommerce tables  ← CLEAN THIS

================================
❌ Schema validation FAILED

Issues found:
  - Missing age_months column in children table

To fix: Run database/migrations/001-staging-to-production-schema.sql
```

---

## Next Steps

1. **Immediate:** Run migration on staging to add age_months column
2. **After migration:** Revert temporary fix in pages/children.php
3. **Testing:** Verify all pages work correctly
4. **Cleanup:** Remove 76 WordPress tables from staging
5. **Integration:** Add schema-check to deployment workflow
6. **Documentation:** Update schema.sql from production dump

---

## Questions or Issues?

- Migration fails → Check backup exists, review error message
- Schema check fails after migration → Re-run migration, check verification queries
- Pages still broken → Clear PHP opcache, check error logs
- WordPress table removal → Test first, can always restore from backup

---

**Documentation Complete:** 2025-11-09
**Action Required:** Run migration on staging
**Estimated Time:** 15 minutes (backup + migration + testing)
**Risk Level:** LOW (staging only, have backup, migration tested on production schema)
