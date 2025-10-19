-- Migration: Create magic link authentication tables
-- Purpose: Support magic link-based admin authentication with audit logging
-- Created: 2025-10-19

-- Admin Magic Links Table
CREATE TABLE IF NOT EXISTS admin_magic_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_user_id INT,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,

    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_email (email),
    INDEX idx_expires_at (expires_at),
    INDEX idx_admin_user_id (admin_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Login Log Table (Audit Trail)
CREATE TABLE IF NOT EXISTS admin_login_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_user_id INT,
    event_type VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    result VARCHAR(20),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    details JSON,

    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_admin_user_id (admin_user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_event_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate Limiting Tracking Table
CREATE TABLE IF NOT EXISTS rate_limit_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255),
    ip_address VARCHAR(45),
    last_request DATETIME,
    request_count INT DEFAULT 1,
    window_start DATETIME,

    INDEX idx_email (email),
    INDEX idx_ip (ip_address),
    INDEX idx_window_start (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
