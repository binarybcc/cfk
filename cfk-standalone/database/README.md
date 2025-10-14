# Database Schema Documentation

**Application:** Christmas for Kids - Sponsorship System
**Database Engine:** MySQL 5.7+ / MariaDB 10.2+
**Character Set:** utf8mb4 (full Unicode support)

---

## Quick Start

### Fresh Installation

For new installations, run schema and migrations in order:

```bash
# 1. Create database
mysql -u root -p -e "CREATE DATABASE cfk_sponsorship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Run main schema
mysql -u user -p cfk_sponsorship < database/schema.sql

# 3. Run migrations (v1.5+)
mysql -u user -p cfk_sponsorship < migrations/002_create_reservations_table.sql
mysql -u user -p cfk_sponsorship < migrations/003_update_email_log_for_reservations.sql
```

### Existing Installation

Check which migrations have been run, then run only new ones:

```bash
# Check if reservations table exists
mysql -u user -p cfk_sponsorship -e "SHOW TABLES LIKE 'reservations';"

# Check if children has reservation columns
mysql -u user -p cfk_sponsorship -e "DESCRIBE children;" | grep reservation

# Run missing migrations as needed
```

---

## Schema Files

### Primary Schema

**database/schema.sql** - Core application tables (v1.0)
- `families` - Family groupings for siblings
- `children` - Child profiles and information
- `sponsorships` - Confirmed sponsorships
- `admin_users` - Admin authentication (includes password reset)
- `settings` - Application configuration
- Sample data for testing

**Important:** Contains all core tables with proper indexes and foreign keys.

### Migrations

**migrations/002_create_reservations_table.sql** - v1.5 Reservation System
- Creates `reservations` table (time-limited selections)
- Adds `reservation_id` and `reservation_expires_at` to `children` table
- Critical for current reservation workflow

**migrations/003_update_email_log_for_reservations.sql** - v1.5 Email Log Updates
- Updates `email_log` table for reservation-related emails
- Depends on: `email_log` table existing

### Additional Tables (Separate Files)

**database/email_queue_table.sql** - Email Queue System
- Optional: Queued email sending with retry logic
- Status: Used by `includes/email_queue.php`

**database/email_log_table.sql** - Email History Tracking
- Required: All email delivery history
- Status: Used by `includes/email_manager.php` and `includes/reservation_emails.php`

---

## Tables Overview

### Core Tables (v1.0)

| Table | Purpose | Records | Key Relationships |
|-------|---------|---------|-------------------|
| **families** | Sibling groupings | Hundreds | → children (1:many) |
| **children** | Child profiles | Thousands | ← families, → sponsorships, → reservations |
| **sponsorships** | Confirmed sponsors | Thousands | ← children |
| **admin_users** | Admin accounts | <10 | None |
| **settings** | Configuration | ~10 | None |

### Reservation System (v1.5)

| Table | Purpose | Records | Key Relationships |
|-------|---------|---------|-------------------|
| **reservations** | Time-limited selections | Active only | → children (1:many) |

**Note:** `children` table includes `reservation_id` and `reservation_expires_at` columns (added via migration).

### Email System

| Table | Purpose | Records | Key Relationships |
|-------|---------|---------|-------------------|
| **email_log** | Email history | Growing | None (reference via email_type/related_id) |
| **email_queue** | Pending emails | Active only | None |

---

## Schema Details

### families

```sql
CREATE TABLE families (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_number VARCHAR(10) NOT NULL UNIQUE,  -- "175", "176", etc.
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Indexes:** PRIMARY KEY (id), UNIQUE (family_number)

---

### children

```sql
CREATE TABLE children (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_id INT NOT NULL,
    child_letter VARCHAR(1) DEFAULT '',  -- A, B, C for siblings

    -- Basic Information
    age INT NOT NULL,
    grade VARCHAR(20),
    gender ENUM('M', 'F') NOT NULL,
    school VARCHAR(100),

    -- Physical Details (clothing sizes)
    shirt_size VARCHAR(10),
    pant_size VARCHAR(10),
    shoe_size VARCHAR(10),
    jacket_size VARCHAR(10),

    -- Personal Information
    interests TEXT,
    wishes TEXT,
    special_needs TEXT,

    -- Status
    status ENUM('available', 'pending', 'sponsored', 'inactive') DEFAULT 'available',
    photo_filename VARCHAR(255),  -- Note: Avatar system, not real photos
    priority_level ENUM('normal', 'high', 'urgent') DEFAULT 'normal',

    -- v1.5 Reservation System (added via migration)
    reservation_id INT DEFAULT NULL,
    reservation_expires_at TIMESTAMP NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,

    -- Indexes
    INDEX idx_status (status),
    INDEX idx_age (age),
    INDEX idx_family (family_id),
    INDEX idx_gender (gender),
    INDEX idx_reservation (reservation_id)
);
```

**Critical Indexes:**
- `idx_status` - Child browsing queries
- `idx_reservation` - Reservation cleanup cron job

---

### sponsorships

```sql
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
);
```

**Critical Indexes:**
- `idx_sponsor_email` - Sponsor lookup functionality
- `idx_status` - Admin sponsorship management

---

### reservations (v1.5)

```sql
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
    expires_at TIMESTAMP NOT NULL,  -- 24-48 hours from creation
    confirmed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,

    -- Additional Information
    notes TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,

    INDEX idx_token (reservation_token),
    INDEX idx_email (sponsor_email),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at),  -- CRITICAL for cron cleanup
    INDEX idx_created (created_at)
);
```

**Critical Indexes:**
- `idx_expires` - Used by `cron/cleanup_reservations.php` (runs hourly)
- `idx_token` - Used by sponsor portal access

---

### admin_users

```sql
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,  -- bcrypt cost 12

    -- Password Reset (v1.0)
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,

    email VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'editor') DEFAULT 'editor',
    last_login TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_username (username),
    INDEX idx_reset_token (reset_token)
);
```

**Security:**
- Passwords hashed with bcrypt (cost factor 12)
- Password reset tokens expire automatically
- Default admin password in schema.sql MUST be changed in production

---

### settings

```sql
CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Default Settings:**
- `site_title` - Main site title
- `registration_open` - Whether sponsorships are being accepted
- `max_pending_hours` - Hours before pending sponsorships expire
- `admin_email` - Primary admin notification email
- `items_per_page` - Children displayed per page
- `photo_upload_path` - Path for child photos (note: uses avatar system)
- `site_description` - Site description for pages

---

### email_log

```sql
CREATE TABLE email_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255),
    subject VARCHAR(255) NOT NULL,

    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed', 'bounced') DEFAULT 'sent',
    error_message TEXT,

    email_type VARCHAR(50),  -- e.g., 'reservation_confirmation', 'access_link'
    related_id INT,  -- ID of related record (reservation_id, sponsorship_id, etc.)
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),

    INDEX idx_email (recipient_email),
    INDEX idx_sent (sent_at),
    INDEX idx_status (status),
    INDEX idx_type (email_type)
);
```

**Used For:**
- Email delivery tracking
- Debugging email issues
- Resending failed emails
- Audit trail

---

### email_queue (Optional)

```sql
CREATE TABLE email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255),
    subject VARCHAR(255) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT,

    priority ENUM('high', 'normal', 'low') DEFAULT 'normal',
    scheduled_for TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    last_attempt_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    error_message TEXT,

    email_type VARCHAR(50),
    related_id INT,
    headers JSON,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_for),
    INDEX idx_priority (priority),
    INDEX idx_type (email_type)
);
```

**Used For:**
- Queuing emails for batch sending
- Retry logic for failed sends
- Priority-based delivery
- Rate limiting email sending

---

## Foreign Key Relationships

```
families (1) ──< children (many)
  └─ id ──> family_id [ON DELETE CASCADE]

children (1) ──< sponsorships (many)
  └─ id ──> child_id [ON DELETE CASCADE]

reservations (1) ──< children (many)
  └─ id ──> reservation_id [ON DELETE SET NULL]
```

**Cascade Behaviors:**
- Delete family → Deletes all children in that family
- Delete child → Deletes all sponsorships for that child
- Delete reservation → Sets children's reservation_id to NULL (frees them)

---

## Verification Commands

### Check Database Structure

```bash
# Show all tables
mysql -u user -p cfk_sponsorship -e "SHOW TABLES;"

# Check children table structure (includes reservation columns?)
mysql -u user -p cfk_sponsorship -e "DESCRIBE children;"

# Verify reservations table exists
mysql -u user -p cfk_sponsorship -e "SHOW CREATE TABLE reservations\G"

# Check indexes on children table
mysql -u user -p cfk_sponsorship -e "SHOW INDEX FROM children;"
```

### Check Data

```bash
# Count records
mysql -u user -p cfk_sponsorship -e "
    SELECT
        (SELECT COUNT(*) FROM families) AS families,
        (SELECT COUNT(*) FROM children) AS children,
        (SELECT COUNT(*) FROM sponsorships) AS sponsorships,
        (SELECT COUNT(*) FROM reservations WHERE status='pending') AS active_reservations;
"
```

---

## Maintenance

### Regular Tasks

**Daily:**
- Check email_log for failed sends
- Monitor reservation expirations

**Weekly:**
- Review database size growth
- Check for orphaned records

**Monthly:**
- Archive old email_log entries (>90 days)
- Review and cleanup expired reservations

### Cleanup Queries

```sql
-- Remove old email log entries (>90 days)
DELETE FROM email_log WHERE sent_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Find expired reservations
SELECT * FROM reservations
WHERE status = 'pending' AND expires_at < NOW();

-- Find children with expired reservations
SELECT * FROM children
WHERE reservation_expires_at < NOW() AND reservation_id IS NOT NULL;
```

---

## Backup & Restore

### Backup Database

```bash
# Full backup
mysqldump -u user -p cfk_sponsorship > backup_$(date +%Y%m%d).sql

# Schema only (no data)
mysqldump -u user -p --no-data cfk_sponsorship > schema_backup.sql

# Specific tables
mysqldump -u user -p cfk_sponsorship children sponsorships > critical_data.sql
```

### Restore Database

```bash
# Full restore
mysql -u user -p cfk_sponsorship < backup_20251013.sql

# Restore specific tables
mysql -u user -p cfk_sponsorship < critical_data.sql
```

---

## Migration History

| Version | Date | Description | Files |
|---------|------|-------------|-------|
| v1.0 | Oct 2, 2024 | Initial schema | schema.sql |
| v1.5 | Oct 10, 2024 | Reservation system | 002_create_reservations_table.sql |
| v1.5 | Oct 12, 2024 | Email log updates | 003_update_email_log_for_reservations.sql |
| v2.0 | TBD | Privacy cleanup | 002_remove_name_column.sql (removes PII) |

---

## Troubleshooting

### Common Issues

**Issue:** Foreign key constraint fails when deleting family
**Solution:** This is expected - deletes all children in family (CASCADE)

**Issue:** Can't find reservations table
**Solution:** Run migration 002_create_reservations_table.sql

**Issue:** children table missing reservation_id column
**Solution:** Run migration 002_create_reservations_table.sql (includes ALTER TABLE)

**Issue:** Expired reservations not cleaning up
**Solution:** Verify cron job `cron/cleanup_reservations.php` is running hourly

---

## Performance Notes

### Indexed Queries ✅

All critical queries have proper indexes:
- Child browsing: `idx_status`, `idx_age`, `idx_gender`
- Reservation cleanup: `idx_expires`, `idx_status`
- Sponsor lookup: `idx_sponsor_email`
- Family queries: `idx_family`

### Slow Query Candidates ⚠️

Monitor these queries:
- Full-text search in `interests` or `wishes` (consider FULLTEXT index)
- Large pagination (offset >1000)
- Complex reporting queries

---

## Security Considerations

### Data Protection

- ✅ **No real child photos** (avatar system used)
- ✅ **Password hashing** (bcrypt cost 12)
- ✅ **Prepared statements** (SQL injection protection)
- ⚠️ **Email addresses stored** (consider data retention policy)
- ⚠️ **IP addresses logged** (privacy implications)

### Compliance Notes

- GDPR: Consider data retention policies for email_log and reservations
- COPPA: No child PII collected (privacy-first design)
- PCI: No payment data stored (donations handled externally)

---

## Development vs Production

### Development (docker-compose.yml)

- Root password: `rootpassword`
- Database: `cfk_dev`
- Runs sample data from schema.sql

### Production

- ⚠️ Change default admin password
- ⚠️ Use strong database password
- ⚠️ Restrict database access to application only
- ⚠️ Enable slow query log
- ⚠️ Regular backups (daily recommended)

---

**Last Updated:** October 13, 2025 (v1.5.1)
**Maintainer:** See CLAUDE.md for project information
