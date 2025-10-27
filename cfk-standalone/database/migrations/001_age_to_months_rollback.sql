-- Rollback: Convert age_months back to age (years)
-- ONLY USE IF MIGRATION FAILS OR NEEDS TO BE REVERSED

-- Step 1: Add back age column
ALTER TABLE children
ADD COLUMN age INT NOT NULL DEFAULT 0
AFTER id;

-- Step 2: Convert months back to years (divide by 12, round down)
UPDATE children
SET age = FLOOR(age_months / 12);

-- Step 3: Add back age index
CREATE INDEX idx_age ON children(age);

-- Step 4: Drop age_months column and index
ALTER TABLE children
DROP INDEX idx_age_months,
DROP COLUMN age_months;
