-- Migration: Bring Staging Database to Match Production Schema
-- Created: 2025-11-09
-- Purpose: Fix schema mismatch between staging and production
--
-- CRITICAL: This migration brings staging database to match production
-- Run ONLY on staging database (10ce79bd48.nxcli.io)
--
-- Changes:
-- 1. Add age_months column and migrate data from age
-- 2. Add name column to children table
-- 3. Create missing tables: reservations, portal_access_tokens, email_log, admin_login_log, admin_magic_links
-- 4. Remove WordPress tables (optional)

-- ===========================================================================
-- STEP 1: Modify children table
-- ===========================================================================

-- Add age_months column and populate from age
ALTER TABLE children ADD COLUMN age_months INT NOT NULL DEFAULT 0 AFTER child_letter;

-- Migrate data: convert age (years) to age_months
UPDATE children SET age_months = age * 12;

-- Add index on age_months
ALTER TABLE children ADD INDEX idx_age_months (age_months);

-- Add name column (for privacy-compliant display)
ALTER TABLE children ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT '' AFTER child_letter;

-- Note: We keep the 'age' column for backward compatibility
-- Production has both age and age_months

-- ===========================================================================
-- STEP 2: Create reservations table
-- ===========================================================================

CREATE TABLE IF NOT EXISTS reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_token VARCHAR(64) NOT NULL UNIQUE,
    sponsor_name VARCHAR(255) NOT NULL,
    sponsor_email VARCHAR(255) NOT NULL,
    sponsor_phone VARCHAR(20),
    sponsor_address TEXT,
    children_ids TEXT NOT NULL, -- JSON array or comma-separated IDs
    total_children INT NOT NULL DEFAULT 0,
    status ENUM('pending', 'confirmed', 'expired', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
    confirmed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    notes TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),

    INDEX idx_email (sponsor_email),
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
-- STEP 3: Create portal_access_tokens table
-- ===========================================================================

CREATE TABLE IF NOT EXISTS portal_access_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    sponsor_email VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_email (sponsor_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
-- STEP 4: Create email_log table
-- ===========================================================================

CREATE TABLE IF NOT EXISTS email_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255),
    subject VARCHAR(500) NOT NULL,
    email_type VARCHAR(100), -- 'reservation_confirmation', 'admin_notification', etc.
    status ENUM('sent', 'failed', 'queued') DEFAULT 'queued',
    error_message TEXT,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_recipient (recipient_email),
    INDEX idx_type (email_type),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
-- STEP 5: Create admin_login_log table
-- ===========================================================================

CREATE TABLE IF NOT EXISTS admin_login_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_user_id INT,
    email VARCHAR(255) NOT NULL,
    action ENUM('login_attempt', 'login_success', 'login_failed', 'logout', 'password_reset') NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user (admin_user_id),
    INDEX idx_email (email),
    INDEX idx_action (action),
    INDEX idx_created (created_at),

    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
-- STEP 6: Create admin_magic_links table
-- ===========================================================================

CREATE TABLE IF NOT EXISTS admin_magic_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user (admin_user_id),
    INDEX idx_expires (expires_at),

    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
-- VERIFICATION QUERIES (Run after migration to verify)
-- ===========================================================================

-- Verify children table has age_months column
-- SELECT COUNT(*) FROM children WHERE age_months > 0;

-- Verify all children have age_months populated
-- SELECT id, age, age_months FROM children LIMIT 5;

-- Verify new tables exist
-- SHOW TABLES LIKE 'reservations';
-- SHOW TABLES LIKE 'portal_access_tokens';
-- SHOW TABLES LIKE 'email_log';
-- SHOW TABLES LIKE 'admin_login_log';
-- SHOW TABLES LIKE 'admin_magic_links';

-- ===========================================================================
-- OPTIONAL: Remove WordPress tables
-- ===========================================================================

-- WARNING: This will delete all WordPress data
-- Only run if you're CERTAIN you don't need the WordPress tables

/*
-- List of WordPress tables to drop (uncomment to execute)
DROP TABLE IF EXISTS cfk_actionscheduler_actions;
DROP TABLE IF EXISTS cfk_actionscheduler_claims;
DROP TABLE IF EXISTS cfk_actionscheduler_groups;
DROP TABLE IF EXISTS cfk_actionscheduler_logs;
DROP TABLE IF EXISTS cfk_aws_cache;
DROP TABLE IF EXISTS cfk_aws_index;
DROP TABLE IF EXISTS cfk_commentmeta;
DROP TABLE IF EXISTS cfk_comments;
DROP TABLE IF EXISTS cfk_e_events;
DROP TABLE IF EXISTS cfk_e_notes;
DROP TABLE IF EXISTS cfk_e_notes_users_relations;
DROP TABLE IF EXISTS cfk_e_submissions;
DROP TABLE IF EXISTS cfk_e_submissions_actions_log;
DROP TABLE IF EXISTS cfk_e_submissions_values;
DROP TABLE IF EXISTS cfk_links;
DROP TABLE IF EXISTS cfk_options;
DROP TABLE IF EXISTS cfk_postmeta;
DROP TABLE IF EXISTS cfk_posts;
DROP TABLE IF EXISTS cfk_rsssl_csp_log;
DROP TABLE IF EXISTS cfk_snippets;
DROP TABLE IF EXISTS cfk_term_relationships;
DROP TABLE IF EXISTS cfk_term_taxonomy;
DROP TABLE IF EXISTS cfk_termmeta;
DROP TABLE IF EXISTS cfk_terms;
DROP TABLE IF EXISTS cfk_usermeta;
DROP TABLE IF EXISTS cfk_users;
DROP TABLE IF EXISTS cfk_wc_admin_note_actions;
DROP TABLE IF EXISTS cfk_wc_admin_notes;
DROP TABLE IF EXISTS cfk_wc_category_lookup;
DROP TABLE IF EXISTS cfk_wc_customer_lookup;
DROP TABLE IF EXISTS cfk_wc_download_log;
DROP TABLE IF EXISTS cfk_wc_order_addresses;
DROP TABLE IF EXISTS cfk_wc_order_coupon_lookup;
DROP TABLE IF EXISTS cfk_wc_order_operational_data;
DROP TABLE IF EXISTS cfk_wc_order_product_lookup;
DROP TABLE IF EXISTS cfk_wc_order_stats;
DROP TABLE IF EXISTS cfk_wc_order_tax_lookup;
DROP TABLE IF EXISTS cfk_wc_orders;
DROP TABLE IF EXISTS cfk_wc_orders_meta;
DROP TABLE IF EXISTS cfk_wc_product_attributes_lookup;
DROP TABLE IF EXISTS cfk_wc_product_download_directories;
DROP TABLE IF EXISTS cfk_wc_product_meta_lookup;
DROP TABLE IF EXISTS cfk_wc_rate_limits;
DROP TABLE IF EXISTS cfk_wc_reserved_stock;
DROP TABLE IF EXISTS cfk_wc_spm_checkpoints;
DROP TABLE IF EXISTS cfk_wc_tax_rate_classes;
DROP TABLE IF EXISTS cfk_wc_webhooks;
DROP TABLE IF EXISTS cfk_woocommerce_api_keys;
DROP TABLE IF EXISTS cfk_woocommerce_attribute_taxonomies;
DROP TABLE IF EXISTS cfk_woocommerce_downloadable_product_permissions;
DROP TABLE IF EXISTS cfk_woocommerce_log;
DROP TABLE IF EXISTS cfk_woocommerce_order_itemmeta;
DROP TABLE IF EXISTS cfk_woocommerce_order_items;
DROP TABLE IF EXISTS cfk_woocommerce_payment_tokenmeta;
DROP TABLE IF EXISTS cfk_woocommerce_payment_tokens;
DROP TABLE IF EXISTS cfk_woocommerce_sessions;
DROP TABLE IF EXISTS cfk_woocommerce_shipping_zone_locations;
DROP TABLE IF EXISTS cfk_woocommerce_shipping_zone_methods;
DROP TABLE IF EXISTS cfk_woocommerce_shipping_zones;
DROP TABLE IF EXISTS cfk_woocommerce_tax_rate_locations;
DROP TABLE IF EXISTS cfk_woocommerce_tax_rates;
DROP TABLE IF EXISTS cfk_wpmailsmtp_debug_events;
DROP TABLE IF EXISTS cfk_wpmailsmtp_tasks_meta;
DROP TABLE IF EXISTS cfk_wpml_mails;
DROP TABLE IF EXISTS wp_commentmeta;
DROP TABLE IF EXISTS wp_comments;
DROP TABLE IF EXISTS wp_links;
DROP TABLE IF EXISTS wp_options;
DROP TABLE IF EXISTS wp_postmeta;
DROP TABLE IF EXISTS wp_posts;
DROP TABLE IF EXISTS wp_term_relationships;
DROP TABLE IF EXISTS wp_term_taxonomy;
DROP TABLE IF EXISTS wp_termmeta;
DROP TABLE IF EXISTS wp_terms;
DROP TABLE IF EXISTS wp_usermeta;
DROP TABLE IF EXISTS wp_users;
*/

-- ===========================================================================
-- Migration Complete
-- ===========================================================================
