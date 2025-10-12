-- Migration: Create Reservations Table
-- Version: 1.5
-- Description: Adds reservation system for temporary child selections with 24-48 hour expiration

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_token VARCHAR(64) UNIQUE NOT NULL COMMENT 'Unique token for reservation lookup',

    -- Sponsor Information
    sponsor_name VARCHAR(255) NOT NULL,
    sponsor_email VARCHAR(255) NOT NULL,
    sponsor_phone VARCHAR(20) DEFAULT NULL,
    sponsor_address TEXT DEFAULT NULL,

    -- Reservation Data
    children_ids TEXT NOT NULL COMMENT 'JSON array of child IDs in this reservation',
    total_children INT NOT NULL DEFAULT 0,

    -- Status and Timestamps
    status ENUM('pending', 'confirmed', 'expired', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL COMMENT 'Reservation expires after 24-48 hours',
    confirmed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,

    -- Additional Information
    notes TEXT DEFAULT NULL COMMENT 'Admin notes or special requests',
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,

    -- Indexes for performance
    INDEX idx_token (reservation_token),
    INDEX idx_email (sponsor_email),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add reservation status tracking to children table
-- This prevents the same child from being reserved by multiple sponsors
ALTER TABLE children
ADD COLUMN reservation_id INT DEFAULT NULL AFTER status,
ADD COLUMN reservation_expires_at TIMESTAMP NULL AFTER reservation_id,
ADD INDEX idx_reservation (reservation_id),
ADD FOREIGN KEY fk_child_reservation (reservation_id)
    REFERENCES reservations(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;
