-- Add password reset columns to admin_users table
-- Run this migration to enable password reset functionality

-- Add reset_token column if it doesn't exist
ALTER TABLE admin_users
ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) DEFAULT NULL AFTER password_hash,
ADD COLUMN IF NOT EXISTS reset_token_expiry DATETIME DEFAULT NULL AFTER reset_token;

-- Add index for faster token lookups
CREATE INDEX IF NOT EXISTS idx_reset_token ON admin_users(reset_token);

-- Verify the changes
SELECT 'Password reset columns added successfully' AS status;
