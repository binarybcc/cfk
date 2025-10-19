-- Migration: Create Initial Admin Accounts for Magic Link Authentication
-- Version: 1.6.1
-- Date: 2025-10-19
--
-- INSTRUCTIONS:
-- 1. Edit the email addresses below to match your actual admin emails
-- 2. Run this migration: mysql -u <user> -p <database> < 007_create_initial_admin_accounts.sql
-- 3. Verify accounts created: SELECT id, username, email, role FROM admin_users;
--
-- IMPORTANT:
-- - Email addresses MUST be valid and able to receive emails (magic links sent here)
-- - Password hashes are random placeholders (not used for magic link auth)
-- - Only one admin account is created by default (add more as needed)

-- Create initial admin account
INSERT INTO admin_users (username, email, password_hash, full_name, role)
VALUES (
    'admin',
    'your-email@example.com',  -- CHANGE THIS to your actual admin email
    '$2y$10$92IXUNpkjO0rJVMpTKq5weLXMO8zKkB6jD3Gq5Y5YqJ5pGqMYqJ5e',  -- Random hash (not used)
    'System Administrator',
    'admin'
)
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    full_name = VALUES(full_name),
    role = VALUES(role);

-- Example: Add additional admin accounts (uncomment and edit as needed)
/*
INSERT INTO admin_users (username, email, password_hash, full_name, role)
VALUES (
    'john.doe',
    'john.doe@example.com',  -- CHANGE THIS
    '$2y$10$92IXUNpkjO0rJVMpTKq5weLXMO8zKkB6jD3Gq5Y5YqJ5pGqMYqJ5e',
    'John Doe',
    'admin'
);

INSERT INTO admin_users (username, email, password_hash, full_name, role)
VALUES (
    'jane.smith',
    'jane.smith@example.com',  -- CHANGE THIS
    '$2y$10$92IXUNpkjO0rJVMpTKq5weLXMO8zKkB6jD3Gq5Y5YqJ5pGqMYqJ5e',
    'Jane Smith',
    'editor'
);
*/

-- Verify accounts created
SELECT
    id,
    username,
    email,
    full_name,
    role,
    created_at
FROM admin_users
ORDER BY created_at DESC;
