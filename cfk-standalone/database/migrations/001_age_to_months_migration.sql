-- Migration: Convert age from years to months
-- Date: 2025-10-27
-- Branch: v1.7.2-phpstan-fixes
-- NOTE: This migration will need to be applied to later branches (v1.8, v1.9, etc.)

-- Step 1: Add new age_months column
ALTER TABLE children
ADD COLUMN age_months INT NOT NULL DEFAULT 0
AFTER age;

-- Step 2: Convert existing age (years) to months
-- Current data is in YEARS, so multiply by 12
UPDATE children
SET age_months = age * 12;

-- Step 3: Add index for age_months (replaces old age index)
CREATE INDEX idx_age_months ON children(age_months);

-- Step 4: Drop old age column and its index
ALTER TABLE children
DROP INDEX idx_age,
DROP COLUMN age;

-- Verification queries (run after migration):
-- SELECT MIN(age_months) as min, MAX(age_months) as max, COUNT(*) as total FROM children;
-- SELECT age_months, COUNT(*) as count FROM children GROUP BY age_months ORDER BY age_months LIMIT 10;
