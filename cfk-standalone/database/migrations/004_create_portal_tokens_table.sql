-- Migration 004: Create portal access tokens table
-- Stores sponsor portal access tokens in database for revocation capability
-- Date: October 13, 2025

CREATE TABLE IF NOT EXISTS portal_access_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    sponsor_email VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_token_hash (token_hash),
    INDEX idx_sponsor_email (sponsor_email),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add cleanup of expired tokens (run via cron)
-- DELETE FROM portal_access_tokens WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
