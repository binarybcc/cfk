-- Migration: Add Reservation System Columns
-- Version: v1.7.3
-- Date: 2025-10-28
-- Description: Adds reservation tracking columns to children table and password reset to admin_users

-- Add reservation columns to children table
ALTER TABLE children
    ADD COLUMN reservation_id INT DEFAULT NULL AFTER status,
    ADD COLUMN reservation_expires_at TIMESTAMP NULL AFTER reservation_id,
    ADD INDEX idx_reservation (reservation_id),
    ADD INDEX idx_reservation_expires (reservation_expires_at);

-- Add password reset columns to admin_users table
ALTER TABLE admin_users
    ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL AFTER password_hash,
    ADD COLUMN reset_token_expiry DATETIME DEFAULT NULL AFTER reset_token,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
    ADD INDEX idx_reset_token (reset_token);

-- Update schema.sql should now match production after running this migration
