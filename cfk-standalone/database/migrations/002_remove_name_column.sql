-- Migration: Remove unused 'name' column from children table
-- Version: 2.0
-- Date: 2025-10-11
-- Purpose: Complete privacy compliance by removing unused name column

-- IMPORTANT: Backup database before running this migration
-- Run: mysqldump -u [user] -p cfk_sponsorship > cfk_backup_$(date +%Y%m%d).sql

-- Step 1: Verify the column exists and is not being used
SELECT
    'Checking for name column usage...' as step,
    COUNT(*) as records_with_data
FROM children
WHERE name IS NOT NULL AND name != '';

-- Step 2: Create backup table (optional safety measure)
CREATE TABLE IF NOT EXISTS children_backup_20251011 AS
SELECT * FROM children;

-- Step 3: Drop the name column
-- Note: Column already removed from schema.sql for new installations
-- This migration is for existing databases only
ALTER TABLE children DROP COLUMN IF EXISTS name;

-- Step 4: Verify the change
SHOW COLUMNS FROM children;

-- Step 5: Verify data integrity
SELECT
    'Verification complete' as status,
    COUNT(*) as total_children,
    COUNT(DISTINCT family_id) as total_families
FROM children;

-- Expected columns after migration:
-- id, family_id, child_letter, age, grade, gender, school,
-- shirt_size, pant_size, shoe_size, jacket_size,
-- interests, wishes, special_needs, status, photo_filename,
-- priority_level, created_at, updated_at

-- Migration complete!
