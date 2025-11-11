# Database Schema Management

## Overview

This document outlines the strategy for managing database schema across environments and ensuring staging/production consistency.

---

## Your Questions Answered

### 1. Should we remove WordPress tables?

**Recommendation: YES (for staging)**

**Reasons:**
- 80+ WordPress/WooCommerce tables are unused legacy data
- They clutter the database and confuse development
- May impact performance (MySQL has to track all tables)
- Security risk (old WordPress data may contain vulnerabilities)
- Makes backups larger and slower

**How to remove:**
- WordPress tables are commented out in migration script
- Uncomment the DROP TABLE statements in `database/migrations/001-staging-to-production-schema.sql`
- Or run individually if you want to be selective

**Production:**
- Production database is already clean (no WordPress tables)
- No action needed on production

---

### 2. Should there always be schema comparison on deployment?

**Recommendation: YES (automated check)**

**Benefits:**
- Prevents deploying code to database with wrong schema
- Catches schema drift early
- Documents what schema is expected
- Prevents breaking production with schema mismatches

**Implementation:**

We've created `database/schema-check.sh` script that:
1. Connects to database
2. Checks all required tables exist
3. Validates critical columns (age_months, name, etc.)
4. Reports WordPress table pollution
5. Exits with error if schema doesn't match

**Add to deployment workflow:**

```bash
# Before deploying code, check schema
./database/schema-check.sh staging
if [ $? -ne 0 ]; then
    echo "Schema validation failed - fix database before deploying"
    exit 1
fi

# If schema passes, deploy code
/deploy-staging
```

**Add to slash commands:**
- Update `/deploy-staging` to run schema check first
- Update `/deploy-production` to run schema check first
- Fail deployment if schema doesn't match

---

## Schema Version Control

### Source of Truth

**Production database (v1.7.3-production-hardening branch) is source of truth**

Current production schema:
- families
- children (with age_months, name columns)
- sponsorships
- admin_users
- settings
- reservations
- portal_access_tokens
- email_log
- admin_login_log
- admin_magic_links
- rate_limit_tracking

### Schema Documentation

**Current state:** schema.sql is outdated (doesn't match production)

**Action needed:** Update schema.sql to match production reality

Options:
1. **Dump from production** and use that as master schema
2. **Update schema.sql manually** to match production DESCRIBE output
3. **Use migrations** going forward for all schema changes

**Recommendation:** Option 1 (dump from production)

```bash
# On production
mysqldump --no-data --skip-add-drop-table \
    -h localhost \
    -u $(grep DB_USER .env | cut -d= -f2) \
    -p$(grep DB_PASS .env | cut -d= -f2) \
    $(grep DB_NAME .env | cut -d= -f2) \
    families children sponsorships admin_users settings \
    reservations portal_access_tokens email_log \
    admin_login_log admin_magic_links rate_limit_tracking \
    > database/schema.sql
```

---

## Migration Strategy

### Current Situation

**Staging:** Old schema (age instead of age_months, missing tables)
**Production:** Correct schema
**Code:** Expects production schema (age_months, all tables)

### Migration Path

**For Staging:**
1. Run `database/migrations/001-staging-to-production-schema.sql`
2. Verify with `./database/schema-check.sh staging`
3. Test application thoroughly
4. Optional: Remove WordPress tables

**For Future Changes:**
1. Create new numbered migration file
2. Test on staging first
3. Document in migration README
4. Run on production only after staging verification

---

## Deployment Workflow (Recommended)

### Before Every Deployment

```bash
# 1. Check schema matches expected
./database/schema-check.sh staging  # or 'production'

# 2. If schema validation fails
if [ $? -ne 0 ]; then
    echo "Run migrations first:"
    echo "  ssh into server"
    echo "  run database/migrations/*.sql"
    exit 1
fi

# 3. If schema passes, proceed with deployment
/deploy-staging
```

### Update Deployment Skills

**File: `.claude/commands/deploy-staging.md`**

Add schema check as Step 1.5:

```markdown
### Step 1.5: Verify Database Schema

**CRITICAL: Check schema before deploying code**

```bash
# Run schema validation
./database/schema-check.sh staging

# If schema fails, STOP deployment
if [ $? -ne 0 ]; then
    echo "❌ Schema validation failed"
    echo "Run migrations before deploying code"
    exit 1
fi
```

**Expected output:**
- ✓ All required tables exist
- ✓ age_months column exists
- ✓ name column exists

**If validation fails:**
1. SSH into staging
2. Run: `mysql ... < database/migrations/001-staging-to-production-schema.sql`
3. Verify: `./database/schema-check.sh staging`
4. Retry deployment
```

---

## Schema Comparison Tool

### Usage

```bash
# Check staging schema
./database/schema-check.sh staging

# Check production schema
./database/schema-check.sh production
```

### What it checks

✅ All required tables exist
✅ children table has age_months column
✅ children table has name column
⚠️ WordPress table pollution

### Exit codes

- `0` = Schema valid, safe to deploy
- `1` = Schema invalid, run migrations first

---

## WordPress Table Cleanup

### Option 1: Remove All WordPress Tables

**Run this on staging ONLY after migration:**

```sql
-- This is commented out in the migration script
-- Uncomment and run manually if you want to remove WordPress tables

-- Get list of all WordPress tables
SELECT CONCAT('DROP TABLE IF EXISTS ', TABLE_NAME, ';')
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND (TABLE_NAME LIKE 'cfk_%' OR TABLE_NAME LIKE 'wp_%');

-- Copy the output and run it
```

### Option 2: Keep WordPress Tables

If you need historical data or want to keep them for reference:
- Leave WordPress tables as-is
- They won't interfere with standalone application
- Just takes up database space

**Recommendation:** Remove on staging, keep on production until verified not needed

---

## Testing After Schema Changes

### Required Tests After Migration

1. **Browse children page** - Verify age displays correctly
2. **Individual child page** - Verify all child info shows
3. **Family view** - Verify family groupings work
4. **Reservation system** - Add children to cart, check timeout
5. **Sponsor portal** - Request access, use magic link
6. **Admin functions** - Login, manage children, run reports
7. **CSV import** - Import sample CSV with age data

### Automated Testing

Run functional tests after migration:

```bash
BASE_URL="https://10ce79bd48.nxcli.io" bash tests/security-functional-tests.sh
```

Should pass 14/14 remote-testable tests.

---

## Rollback Plan

### Before Running Migrations

**ALWAYS create backup:**

```bash
# SSH into server
ssh user@server

# Create backup
mysqldump -h localhost -u dbuser -p dbpass dbname > backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup
ls -lh backup_*.sql
```

### If Migration Fails

```bash
# Restore from backup
mysql -h localhost -u dbuser -p dbpass dbname < backup_20251109_174500.sql

# Verify restoration
mysql -h localhost -u dbuser -p dbpass dbname -e "DESCRIBE children;"
```

---

## Future Schema Changes

### Process

1. **Plan change** - Document what and why
2. **Create migration** - New numbered file in database/migrations/
3. **Update schema-check.sh** - Add validation for new requirements
4. **Test on staging** - Run migration, verify app works
5. **Update schema.sql** - Keep master schema current
6. **Document** - Update this guide
7. **Deploy to production** - Only after staging verification

### Migration Naming

Format: `###-descriptive-name.sql`

Examples:
- `001-staging-to-production-schema.sql`
- `002-add-gift-tracking-table.sql`
- `003-alter-sponsorship-status-enum.sql`

### Migration Template

```sql
-- Migration: [Description]
-- Created: [Date]
-- Author: [Name]
--
-- Purpose: [Why this change is needed]
--
-- Changes:
-- - [List of changes]
--
-- Rollback:
-- - [How to undo if needed]

-- Migration SQL here

-- Verification queries
-- SELECT ...
```

---

## Questions?

- Schema doesn't match: Run `./database/schema-check.sh`
- Migration failed: Check backup, restore if needed
- Need new table: Create migration, test on staging first
- WordPress tables: Safe to remove on staging, evaluate for production

---

**Last Updated:** 2025-11-09
**Maintainer:** Development Team
