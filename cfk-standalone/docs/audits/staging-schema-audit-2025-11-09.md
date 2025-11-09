# Staging Database Schema Audit - November 9, 2025

## Executive Summary

Staging database schema does NOT match codebase expectations. Critical mismatches found in table structure, missing tables, and database pollution from legacy WordPress installation.

## Critical Issues Found

### 1. Schema Mismatch: age vs age_months

**Current State:**
- Database column: `age` (INT, years)
- Code expects: `age_months` (INT, months)
- Affected files: 16+ files reference `age_months`

**Impact:**
- Children page was completely broken (fixed with temporary workaround)
- Other pages likely broken: child.php, family.php, sponsor_portal.php, etc.
- Affects: avatar selection, age display, filtering, CSV import/export

**Root Cause:**
- Git commit 8004408 (Oct 27, 2025) claims "Complete age tracking migration from single age to age_months/age_years"
- But schema.sql was never updated to reflect this change
- No migration script was run on staging database
- Database still has original `age INT` column

**Temporary Fix Applied:**
- `pages/children.php` modified to use `$child['age']` instead of `$child['age_months']`
- Converts years → months: `displayAge($child['age'] * 12)`
- This is NOT a proper fix, just allows staging to function

---

### 2. Missing Tables

**Code expects but database lacks:**

1. **`reservations` table**
   - Expected by: `includes/reservation_functions.php`, `src/Reservation/Manager.php`
   - Queries: `SELECT * FROM reservations WHERE reservation_token = :token`
   - Purpose: Time-limited child selection system (2-hour window)

2. **`portal_access_tokens` table**
   - Expected by: `src/Sponsorship/Manager.php`
   - Queries: `SELECT * FROM portal_access_tokens WHERE token = :token`
   - Purpose: Magic-link sponsor portal access

**Current workaround:**
- `children` table has `reservation_id` and `reservation_expires_at` columns
- Unclear if separate `reservations` table is additional feature or replacement
- Code may fail when trying to use reservation system

**Testing needed:**
- Can users select children for sponsorship?
- Does reservation timeout work?
- Can sponsors access portal via magic link?

---

### 3. Database Pollution

**Staging database has 85 tables:**
- ✅ 5 standalone app tables: `families`, `children`, `sponsorships`, `admin_users`, `settings`
- ❌ 80+ legacy tables from WordPress/WooCommerce installation

**Legacy table groups:**
- `cfk_*` tables (62): WooCommerce, ActionScheduler, AWS integrations, snippets
- `wp_*` tables (18): WordPress core (posts, comments, users, terms, etc.)

**Issues:**
- Database is NOT a clean standalone installation
- Appears to be WordPress database with standalone tables retrofitted
- May cause:
  - Performance degradation (extra tables to scan)
  - Confusion during development
  - Security concerns (unused WP tables with old data)
  - Backup/restore complexity

---

### 4. Schema.sql is Outdated

**Last updated:** Commit 99ab280 (development tools feature)
**Does not include:**
- `age_months` column (vs current `age`)
- `reservations` table (referenced by code)
- `portal_access_tokens` table (referenced by code)
- Any migration scripts

**Current schema.sql defines only 5 tables:**
1. families
2. children (with `age INT`, not `age_months`)
3. sponsorships
4. admin_users
5. settings

---

## Impact Assessment

### Pages Known to be Broken/Affected:

1. **pages/children.php** - ✅ Temporarily fixed (workaround)
2. **pages/child.php** - ❌ Likely broken (3 `age_months` references)
3. **pages/family.php** - ❌ Likely broken (7 `age_months` references)
4. **pages/sponsor_portal.php** - ❌ Likely broken (1 `age_months` reference)
5. **pages/my_sponsorships.php** - ❌ Likely broken (3 `age_months` references)
6. **pages/reservation_review.php** - ❌ Likely broken (1 `age_months` reference)
7. **pages/confirm_sponsorship.php** - ❌ Likely broken (1 `age_months` reference)

### Features Potentially Non-Functional:

1. **Reservation System**
   - Selecting children may fail (no `reservations` table)
   - Shopping cart functionality unclear
   - 2-hour timeout mechanism unknown

2. **Sponsor Portal**
   - Magic link access may fail (no `portal_access_tokens` table)
   - Existing sponsors can't view their sponsorships

3. **CSV Import/Export**
   - If CSV uses `age_months`, import will fail
   - Export may produce incorrect data

4. **Admin Functions**
   - Age-based filtering/reporting may fail
   - Child management (add/edit) may fail validation

---

## Immediate Actions Required

### Decision Point 1: Age Field Strategy

**Option A: Migrate Database to age_months**
- Create ALTER TABLE migration script
- Update all existing data (years * 12 = months)
- Run on staging first, then production
- PRO: Matches code expectations
- CON: Requires database migration

**Option B: Update Code to Use age**
- Revert all `age_months` references to `age`
- Update helper functions (displayAge, getPlaceholderImage, etc.)
- PRO: No database changes needed
- CON: Large code refactor (16+ files)

**Recommendation:** Option A (migrate to age_months)
- Code is already written for `age_months`
- Provides more precision for babies/toddlers
- Matches avatar system expectations

### Decision Point 2: Missing Tables

**Need to determine:**
1. Are `reservations` and `portal_access_tokens` tables required features?
2. Or were they removed in favor of simpler implementation?
3. Check production: do these tables exist there?

**Action:** Compare production database schema

### Decision Point 3: Database Cleanup

**Recommendation:** Clean staging database
1. Export standalone tables (5 tables)
2. Drop all WordPress/WooCommerce tables
3. Reimport with clean schema
4. PRO: Clean testing environment
5. CON: Loses any historical WordPress data (if needed)

---

## Testing Protocol

After schema fixes applied:

### Phase 1: Core Pages
- [ ] Homepage loads
- [ ] Browse children page (already fixed)
- [ ] Individual child page
- [ ] Family view page

### Phase 2: Sponsorship Flow
- [ ] Select child (add to cart)
- [ ] View selections page
- [ ] Confirm reservation
- [ ] Submit sponsorship

### Phase 3: Admin Functions
- [ ] Admin login
- [ ] View children list
- [ ] Edit child details
- [ ] Run reports
- [ ] CSV import/export

### Phase 4: Sponsor Portal
- [ ] Request portal access
- [ ] Magic link email sent
- [ ] Access portal via link
- [ ] View sponsored children

---

## Recommended Next Steps

1. **Compare with Production**
   - Dump production database schema
   - Document what production actually has
   - Use that as source of truth

2. **Create Migration Scripts**
   - `001-add-age-months.sql` (if needed)
   - `002-add-reservations-table.sql` (if needed)
   - `003-add-portal-tokens-table.sql` (if needed)
   - Test on staging first

3. **Update schema.sql**
   - Match production reality
   - Include all required tables
   - Document schema version

4. **Clean Staging Database**
   - Remove WordPress/WooCommerce tables
   - Create clean baseline

5. **Document Schema Management**
   - Create migration workflow
   - Version control schema changes
   - Document deployment procedures

---

## Files Modified (Temporary Fixes)

- `pages/children.php` (lines 315, 325) - Changed `age_months` → `age` (WORKAROUND, not proper fix)

---

## Questions for Stakeholder

1. **What branch/version is deployed to production?**
   - Need to compare production schema
   - Determine source of truth

2. **Is WordPress data still needed?**
   - Can we drop 80+ legacy tables?
   - Or is there data we need to preserve?

3. **What's the migration strategy preference?**
   - Database migration (add age_months column)
   - Code refactor (use age instead)
   - Fresh start (rebuild from schema.sql)

4. **Are reservation and portal features active?**
   - Do users currently use shopping cart?
   - Do sponsors use portal access?

---

## Attachments

- Full table list: See staging database SHOW TABLES output
- Schema comparison: schema.sql vs actual database DESCRIBE output
- Code references: grep results for age_months, reservations, portal_access_tokens

---

**Audited by:** Claude Code (AI Assistant)
**Date:** November 9, 2025
**Branch:** v1.8.1-cleanup
**Staging URL:** https://10ce79bd48.nxcli.io/
