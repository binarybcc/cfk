-- Christmas for Kids - Standalone Application Database Schema
-- Clean, simple tables for dignified child sponsorship management

-- Create database (uncomment if needed)
-- CREATE DATABASE cfk_sponsorship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE cfk_sponsorship;

-- Families table - for grouping siblings
CREATE TABLE families (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_number VARCHAR(10) NOT NULL UNIQUE, -- e.g., "175", "176"
    family_name VARCHAR(100) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Children table - core child information
CREATE TABLE children (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_id INT NOT NULL,
    child_letter VARCHAR(1) DEFAULT '', -- A, B, C for siblings (175A, 175B, 175C)
    
    -- Basic Information
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    grade VARCHAR(20), -- "Pre-K", "K", "1st", "2nd", etc.
    gender ENUM('M', 'F') NOT NULL,
    school VARCHAR(100),
    
    -- Physical Details
    shirt_size VARCHAR(10), -- XS, S, M, L, XL, etc.
    pant_size VARCHAR(10),
    shoe_size VARCHAR(10),
    jacket_size VARCHAR(10),
    
    -- Personal Information
    interests TEXT, -- hobbies, likes, activities
    wishes TEXT, -- what they want for Christmas
    special_needs TEXT, -- any special considerations
    
    -- Status and Metadata
    status ENUM('available', 'pending', 'sponsored', 'inactive') DEFAULT 'available',
    photo_filename VARCHAR(255), -- profile photo file
    priority_level ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_age (age),
    INDEX idx_family (family_id),
    INDEX idx_gender (gender)
);

-- Sponsorships table - track sponsorship requests and confirmations
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
    notes TEXT, -- admin notes
    
    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_child (child_id),
    INDEX idx_sponsor_email (sponsor_email),
    INDEX idx_request_date (request_date)
);

-- Admin users table - simple admin access
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, -- bcrypt hash
    email VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'editor') DEFAULT 'editor',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_username (username)
);

-- Settings table - configuration options
CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_title', 'Christmas for Kids Sponsorship', 'Main site title'),
('registration_open', '1', 'Whether new sponsorships are being accepted (1=yes, 0=no)'),
('max_pending_hours', '48', 'Hours before pending sponsorships expire'),
('admin_email', 'admin@cforkids.org', 'Primary admin notification email'),
('items_per_page', '12', 'Children displayed per page'),
('photo_upload_path', 'uploads/photos/', 'Path for child photos'),
('site_description', 'Connect with local children who need Christmas support', 'Site description for pages');

-- Create a default admin user (password: 'admin123' - CHANGE THIS!)
-- Password hash for 'admin123' using bcrypt cost 12
INSERT INTO admin_users (username, password_hash, email, full_name, role) VALUES
('admin', '$2y$12$LQv3c1yDelLkmXedgy.SCOQ6g.8k.XkNzWYNm3A6VgQwlz4KdI6G.', 'admin@cforkids.org', 'Site Administrator', 'admin');

-- Sample data for testing (can be removed in production)
INSERT INTO families (family_number, family_name, notes) VALUES
('175', 'Johnson Family', 'Three siblings, very close-knit family'),
('176', 'Smith Family', 'Twin brothers, love sports'),
('177', 'Davis Family', 'Single mother household, very appreciative');

INSERT INTO children (family_id, child_letter, name, age, grade, gender, shirt_size, interests, wishes, status) VALUES
(1, 'A', 'Emma Johnson', 8, '3rd', 'F', 'M', 'Art, reading, unicorns', 'Art supplies and books', 'available'),
(1, 'B', 'Noah Johnson', 6, '1st', 'M', 'S', 'Legos, dinosaurs', 'Dinosaur toys and building sets', 'available'),
(1, 'C', 'Lily Johnson', 4, 'Pre-K', 'F', 'XS', 'Dolls, singing', 'Baby doll and music toys', 'available'),
(2, 'A', 'Marcus Smith', 12, '7th', 'M', 'L', 'Basketball, video games', 'Basketball gear and games', 'available'),
(2, 'B', 'Jordan Smith', 12, '7th', 'M', 'L', 'Soccer, art', 'Soccer equipment and art supplies', 'available'),
(3, 'A', 'Isabella Davis', 10, '5th', 'F', 'M', 'Dance, music', 'Dance clothes and music items', 'available');