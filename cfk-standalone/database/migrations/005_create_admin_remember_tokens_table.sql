-- Migration: Create admin_remember_tokens table
-- Date: 2025-10-18
-- Purpose: Secure, database-backed "Remember Me" token storage

-- Drop table if exists (for clean migrations)
DROP TABLE IF EXISTS admin_remember_tokens;

-- Create remember tokens table
CREATE TABLE admin_remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),

    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment for documentation
ALTER TABLE admin_remember_tokens COMMENT = 'Stores hashed remember-me tokens for admin users';
