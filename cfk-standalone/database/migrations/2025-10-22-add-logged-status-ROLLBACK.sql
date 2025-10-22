-- Rollback Migration: Remove LOGGED status from sponsorship workflow
-- Date: 2025-10-22
-- Purpose: Revert the addition of LOGGED status if needed

-- Step 1: Move any LOGGED sponsorships back to CONFIRMED
-- This ensures no data is lost during rollback
UPDATE sponsorships
SET status = 'confirmed', logged_date = NULL
WHERE status = 'logged';

-- Step 2: Remove the logged_date column
ALTER TABLE sponsorships
DROP COLUMN logged_date;

-- Step 3: Remove the index
DROP INDEX idx_sponsorships_status_logged ON sponsorships;

-- Step 4: Remove 'logged' from ENUM
ALTER TABLE sponsorships
MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled')
DEFAULT 'pending'
COMMENT 'Sponsorship status: pending → confirmed → completed';

-- Verification query
SELECT
    COLUMN_NAME,
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'sponsorships'
AND COLUMN_NAME = 'status';
