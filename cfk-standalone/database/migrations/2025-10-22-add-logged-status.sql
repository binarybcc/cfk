-- Migration: Add LOGGED status to sponsorship workflow
-- Date: 2025-10-22
-- Purpose: Allow staff to track when sponsorships are logged in external spreadsheet
--          This status comes between CONFIRMED and COMPLETE
--          Sponsors maintain access to "My Sponsorships" when in LOGGED status

-- Step 1: Add 'logged' to status ENUM
ALTER TABLE sponsorships
MODIFY COLUMN status ENUM('pending', 'confirmed', 'logged', 'completed', 'cancelled')
DEFAULT 'pending'
COMMENT 'Sponsorship status: pending (new) → confirmed (approved) → logged (in external system) → completed (gifts delivered)';

-- Step 2: Add timestamp for when sponsorship was marked as logged
ALTER TABLE sponsorships
ADD COLUMN logged_date DATETIME NULL
COMMENT 'Timestamp when sponsorship was marked as logged in external system'
AFTER confirmation_date;

-- Step 3: Add index for better query performance on status filtering
CREATE INDEX idx_sponsorships_status_logged
ON sponsorships(status, logged_date);

-- Verification query
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'sponsorships'
AND COLUMN_NAME IN ('status', 'logged_date');
