-- CFK Sponsorship System - Complete Database Schema
-- Version: 1.9.4
-- This schema creates all tables needed for a fresh installation

-- Families table - for grouping siblings
CREATE TABLE families (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_number VARCHAR(10) NOT NULL UNIQUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_family_number (family_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Children table - core child information
CREATE TABLE children (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_id INT NOT NULL,
    child_letter VARCHAR(1) DEFAULT '',

    -- Basic Information
    age INT NOT NULL,
    grade VARCHAR(20),
    gender ENUM('M', 'F') NOT NULL,
    school VARCHAR(100),

    -- Physical Details
    shirt_size VARCHAR(10),
    pant_size VARCHAR(10),
    shoe_size VARCHAR(10),
    jacket_size VARCHAR(10),

    -- Personal Information
    interests TEXT,
    wishes TEXT,
    special_needs TEXT,

    -- Status and Metadata
    status ENUM('available', 'pending', 'sponsored', 'inactive') DEFAULT 'available',
    reservation_id INT DEFAULT NULL,
    reservation_expires_at TIMESTAMP NULL,
    photo_filename VARCHAR(255),
    priority_level ENUM('normal', 'high', 'urgent') DEFAULT 'normal',

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_age (age),
    INDEX idx_family (family_id),
    INDEX idx_gender (gender),
    INDEX idx_reservation (reservation_id),
    INDEX idx_reservation_expires (reservation_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reservations table - temporary child selections
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_token VARCHAR(64) UNIQUE NOT NULL,

    -- Sponsor Information
    sponsor_name VARCHAR(255) NOT NULL,
    sponsor_email VARCHAR(255) NOT NULL,
    sponsor_phone VARCHAR(20) DEFAULT NULL,
    sponsor_address TEXT DEFAULT NULL,

    -- Reservation Data
    children_ids TEXT NOT NULL COMMENT 'JSON array of child IDs',
    total_children INT NOT NULL DEFAULT 0,

    -- Status and Timestamps
    status ENUM('pending', 'confirmed', 'expired', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    confirmed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,

    -- Additional Information
    notes TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,

    INDEX idx_token (reservation_token),
    INDEX idx_email (sponsor_email),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key for children.reservation_id
ALTER TABLE children
ADD CONSTRAINT fk_child_reservation
FOREIGN KEY (reservation_id) REFERENCES reservations(id)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- Sponsorships table - confirmed sponsorships
CREATE TABLE sponsorships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    child_id INT NOT NULL,

    -- Sponsor Information
    sponsor_name VARCHAR(100) NOT NULL,
    sponsor_email VARCHAR(255) NOT NULL,
    sponsor_phone VARCHAR(20),
    sponsor_address TEXT,

    -- Sponsorship Details
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    amount_pledged DECIMAL(10,2),
    gift_preference ENUM('shopping', 'gift_card', 'cash_donation') DEFAULT 'shopping',
    special_message TEXT,

    -- Tracking
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmation_date TIMESTAMP NULL,
    completion_date TIMESTAMP NULL,
    notes TEXT,

    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_child (child_id),
    INDEX idx_sponsor_email (sponsor_email),
    INDEX idx_request_date (request_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin users table
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    email VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'editor') DEFAULT 'editor',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin magic links table (passwordless authentication)
CREATE TABLE admin_magic_links (
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

-- Admin login log (audit trail)
CREATE TABLE admin_login_log (
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

-- Rate limiting table
CREATE TABLE rate_limit_tracking (
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

-- Email log table
CREATE TABLE email_log (
    id INT NOT NULL AUTO_INCREMENT,
    recipient VARCHAR(255) NOT NULL,
    type ENUM('sponsor_confirmation', 'admin_notification', 'sponsorship_update', 'system_alert', 'magic_link') NOT NULL,
    status ENUM('sent', 'failed', 'pending') NOT NULL DEFAULT 'pending',
    sponsorship_id INT DEFAULT NULL,
    subject VARCHAR(255) DEFAULT NULL,
    error_message TEXT,
    sent_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_recipient (recipient),
    INDEX idx_type_status (type, status),
    INDEX idx_sent_date (sent_date),
    FOREIGN KEY (sponsorship_id) REFERENCES sponsorships(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table
CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_title', 'Christmas for Kids Sponsorship', 'Main site title'),
('registration_open', '1', 'Whether new sponsorships are being accepted'),
('max_pending_hours', '48', 'Hours before pending sponsorships expire'),
('admin_email', 'admin@cforkids.org', 'Primary admin notification email'),
('items_per_page', '12', 'Children displayed per page'),
('photo_upload_path', 'uploads/photos/', 'Path for child photos'),
('site_description', 'Connect with local children who need Christmas support', 'Site description');
