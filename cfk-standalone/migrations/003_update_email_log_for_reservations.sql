-- Migration: Update email_log table for reservation system
-- Version: 1.5
-- Description: Adds reservation_id column to email_log table

-- Add reservation_id column if it doesn't exist
ALTER TABLE email_log
ADD COLUMN IF NOT EXISTS reservation_id INT DEFAULT NULL AFTER sponsorship_id,
ADD INDEX IF NOT EXISTS idx_reservation (reservation_id);

-- Add foreign key if it doesn't exist (MySQL will ignore if it exists)
ALTER TABLE email_log
ADD CONSTRAINT fk_email_reservation
FOREIGN KEY (reservation_id) REFERENCES reservations(id)
ON DELETE SET NULL
ON UPDATE CASCADE;
