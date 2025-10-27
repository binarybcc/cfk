# Database Migrations

## ⚠️ IMPORTANT: Cross-Branch Migration Tracking

When migrations are created in one branch, they MUST be applied to all active branches.

## Active Migrations

### 001: Age to Months Migration
**Created:** 2025-10-27
**Initial Branch:** v1.7.2-phpstan-fixes
**Status by Branch:**
- [ ] v1.7.2-phpstan-fixes - PENDING
- [ ] v1.8-cleanup - NEEDS APPLICATION
- [ ] v1.9-modernization - NEEDS APPLICATION
- [ ] v1.0.3-rebuild (main) - NEEDS APPLICATION

**Description:**
Converts child age storage from years to months to support toy age labeling (e.g., "18 months and older").

**Changes:**
- Adds `age_months` (INT) column
- Converts existing `age` (years) to months (multiply by 12)
- Drops old `age` column
- Updates indexes

**Files:**
- Migration: `001_age_to_months_migration.sql`
- Rollback: `001_age_to_months_rollback.sql`

**Dependencies:**
- CSV template update (add `age_months` and `age_years` columns)
- CSV import logic update
- Display logic update (all pages showing age)
- Edit forms update

**Testing Checklist:**
- [ ] Run migration on test/dev database
- [ ] Verify age_months values (multiply by 12)
- [ ] Test CSV import with new columns
- [ ] Verify age display on all pages
- [ ] Test child edit forms
- [ ] Run rollback to verify reversibility
- [ ] Apply to production

## Migration Application Process

1. **Backup database first!**
2. Run migration SQL file
3. Verify with test queries
4. Test application functionality
5. If issues occur, run rollback SQL

## Rollback Process

1. Run appropriate rollback SQL file
2. Verify data restoration
3. Test application functionality
4. Investigate and fix migration issues
