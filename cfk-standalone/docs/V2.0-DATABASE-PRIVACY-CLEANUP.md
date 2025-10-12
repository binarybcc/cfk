# v2.0 Database Privacy Cleanup

## Issue
The database schema contains a `name` field that should not exist since:
- CSV imports never include child names (only family codes)
- Privacy policy prohibits storing or displaying child names
- Field is unused in actual imports but exists in schema

## Current State (v1.4)
✅ **Fixed**: No names displayed publicly (using family codes everywhere)
❌ **Remaining**: Database still has `name` column (legacy/unused)

## v2.0 Tasks

### 1. Database Schema Changes
**File**: `database/schema.sql`

```sql
-- REMOVE this line (line 25):
name VARCHAR(100) NOT NULL,

-- UPDATE sample data to use family codes instead of names (lines 131-137)
```

### 2. Migration Script
**New File**: `database/migrations/002_remove_name_column.sql`

```sql
-- Migration to remove name column
-- Run this on existing installations

-- Step 1: Verify no critical dependencies
SELECT COUNT(*) as name_usage_count FROM children WHERE name IS NOT NULL;

-- Step 2: Drop the column
ALTER TABLE children DROP COLUMN name;

-- Step 3: Verify
SHOW COLUMNS FROM children;
```

### 3. Code Cleanup

**Files that reference `$child['name']`** (to be updated or removed):

- ✅ `includes/components/child_card.php` - Fixed in v1.4
- ✅ `pages/child.php` - Fixed in v1.4
- `includes/email_manager.php` - Check email templates
- `includes/sponsorship_manager.php` - Update references
- `admin/manage_sponsorships.php` - Admin interface
- `admin/reports.php` - Report generation
- `includes/report_manager.php` - Report data
- `includes/archive_manager.php` - Archiving
- `pages/sponsor_portal.php` - Sponsor view

### 4. CSV Import Update
**File**: `includes/csv_importer.php`

Verify the importer:
- Does not expect a name column
- Uses only family code (first column)
- Handles family_id + child_letter correctly

### 5. Testing Checklist

- [ ] Import CSV without names - verify success
- [ ] Check all public pages - no names shown
- [ ] Check all admin pages - confirm codes used
- [ ] Check email templates - use codes not names
- [ ] Run migration on test database
- [ ] Verify existing children still accessible
- [ ] Verify sponsorships still linked correctly

## Impact Analysis

### Database
- **Breaking Change**: Removes column from existing installations
- **Risk**: Medium - requires migration script
- **Benefit**: Aligns schema with privacy policy

### Code
- **Breaking Change**: Any custom code referencing `$child['name']` will break
- **Risk**: Low - most references already fixed in v1.4
- **Benefit**: Cleaner codebase, enforces privacy

### CSV Import
- **Breaking Change**: None (import already doesn't use names)
- **Risk**: None
- **Benefit**: Schema matches CSV format

## Rollout Plan

1. **Preparation**: Audit all `$child['name']` references
2. **Testing**: Test migration on dev database
3. **Documentation**: Update admin docs about privacy
4. **Migration**: Create automated migration script
5. **Deployment**: Include migration in v2.0 upgrade process
6. **Verification**: Post-deployment checks

## Related Documents

- Privacy policy documentation
- CSV import template (`templates/cfk-import-template.csv`)
- Database schema (`database/schema.sql`)
- v1.4 privacy fixes (commit f25ddea)

---

**Status**: Planned for v2.0
**Priority**: Medium (functional works, but schema cleanup needed)
**Assigned**: Future sprint
