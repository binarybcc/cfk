# Web Installer System

**Version:** 1.9.4
**Status:** Complete
**Created:** 2025-11-15

## Overview

The web installer provides a WordPress-style "5-minute installation" experience for deploying the Christmas for Kids application. It guides users through environment checks, database setup, configuration, and first admin account creation.

## Architecture

### File Structure

```
cfk-standalone/
├── install.php                  # Entry point (routing & state)
├── install/
│   ├── Installer.php            # Core installer class
│   ├── schema.sql              # Complete database schema
│   ├── .htaccess               # Protects installer files
│   └── templates/              # UI templates
│       ├── welcome.php         # Step 1: Welcome screen
│       ├── environment.php     # Step 2: Environment check
│       ├── database.php        # Step 3: Database setup
│       ├── config.php          # Step 4: Site configuration
│       ├── admin.php           # Step 5: Admin account
│       └── complete.php        # Step 6: Completion
├── .installed                   # Lock file (created after install)
└── INSTALL.md                   # User installation guide
```

### Installation Flow

```
1. Welcome Screen
   └─> Displays feature list and requirements
       └─> User clicks "Get Started"

2. Environment Check
   └─> Validates PHP version (>= 8.1)
   └─> Checks required extensions (PDO, mbstring, etc.)
   └─> Verifies directory permissions (uploads/)
   └─> All checks must pass to continue

3. Database Setup
   └─> User enters credentials
   └─> Test connection (AJAX)
   └─> Install schema (creates all tables)
   └─> Stores credentials in session

4. Site Configuration
   └─> Base URL (auto-detected)
   └─> Admin email for notifications
   └─> SMTP settings (optional)
   └─> Stored in sessionStorage (JS)

5. Admin Account Creation
   └─> Admin name and email
   └─> Email confirmation
   └─> Creates admin_users record
   └─> Generates .env file
   └─> Creates upload directories

6. Installation Complete
   └─> Creates .installed lock file
   └─> Displays next steps
   └─> Links to admin login
```

## Key Features

### 1. Environment Validation

**Required PHP Version:** 8.1.0 or higher

**Required Extensions:**
- PDO (database connections)
- PDO MySQL (MySQL driver)
- mbstring (string handling)
- JSON (data serialization)
- session (state management)
- openssl (security features)

**Writable Directories:**
- `uploads/` (file uploads)
- `uploads/photos/` (child photos)

### 2. Database Setup

**User Responsibility:**
- User must create database before running installer
- Follows WordPress pattern (app doesn't create DB)

**Installer Creates:**
- All application tables (families, children, sponsorships, etc.)
- Admin authentication tables (admin_users, admin_magic_links, etc.)
- Email and logging tables
- Default settings

**Complete Schema:**
- 12 tables total
- Full UTF-8 support (utf8mb4)
- Proper foreign keys and indexes
- Default settings inserted

### 3. Configuration Management

**Generated .env File:**
```env
DB_HOST=localhost
DB_NAME=your_database
DB_USER=your_user
DB_PASSWORD=your_password
BASE_URL=https://yourdomain.com/
APP_DEBUG=false
ADMIN_EMAIL=admin@example.com
SMTP_HOST=relay.mailchannels.net
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
```

**File Permissions:**
- `.env` set to 600 (owner read/write only)
- Secure by default

### 4. First Admin Account

**Critical Feature:**
- Creates initial admin user for passwordless auth
- Without this, no one can access admin panel
- Email must be valid (receives magic links)

**Database Record:**
```sql
INSERT INTO admin_users (
    username,
    email,
    password_hash,  -- Random placeholder (not used)
    full_name,
    role
) VALUES (
    'admin',
    'user@example.com',
    '$2y$12$random...',
    'User Name',
    'admin'
);
```

### 5. Security Measures

**Installation Lock:**
- Creates `.installed` file after completion
- Installer checks for this file on load
- Must delete file to run installer again

**File Protection:**
- `.htaccess` prevents direct access to installer files
- Only `install.php` in root is accessible

**Session Security:**
- Database credentials stored in PHP session
- Session destroyed after completion
- CSRF protection (could be added)

**Environment File:**
- Sensitive data stored in `.env`
- File permissions: 600 (secure)
- Listed in `.gitignore`

## UI/UX Design

### Visual Design

**Color Scheme:**
- Primary gradient: Purple to violet (#667eea → #764ba2)
- Success: Green gradient (#28a745 → #20c997)
- Alerts: Bootstrap-inspired colors

**Typography:**
- System fonts (native OS look)
- Clear hierarchy (headings, body, hints)

**Components:**
- Material-style input fields
- Gradient buttons with hover effects
- Color-coded status indicators (✓/✗)
- Responsive layout (mobile-friendly)

### User Experience

**Progress Indicator:**
- "Step X of 5" shown in header
- Clear progression through process

**Form Validation:**
- Client-side validation (instant feedback)
- Server-side validation (security)
- Clear error messages

**Success Feedback:**
- Animated checkmark on completion
- Color-coded alerts (success/error/warning/info)
- Clear next steps

**Helpful Hints:**
- Form field hints explain purpose
- Defaults provided where sensible
- Warnings for critical steps

## Technical Implementation

### Session Management

```php
session_start();

// Store data across steps
$_SESSION['install']['db_host'] = $host;
$_SESSION['install']['db_name'] = $name;
$_SESSION['install']['db_user'] = $user;
$_SESSION['install']['db_pass'] = $pass;

// Clear after completion
unset($_SESSION['install']);
```

### AJAX Communication

**Request Format:**
```javascript
const formData = new FormData();
formData.append('action', 'test_database');
formData.append('db_host', host);
// ... other fields

const response = await fetch('install.php', {
    method: 'POST',
    body: formData
});
```

**Response Format:**
```json
{
    "success": true,
    "message": "Database connection successful!",
    "data": {
        "redirect": "install.php?step=config"
    }
}
```

### Database Installation

**Schema Execution:**
```php
// Read schema file
$schema = file_get_contents(__DIR__ . '/install/schema.sql');

// Split into statements
$statements = array_filter(
    array_map('trim', explode(';', $schema)),
    fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
);

// Execute each statement
foreach ($statements as $statement) {
    $pdo->exec($statement);
}
```

### .env File Generation

**Template:**
```php
$envContent = <<<ENV
# CFK Sponsorship System - Environment Configuration
# Generated by installer on {date}

DB_HOST={$host}
DB_NAME={$name}
DB_USER={$user}
DB_PASSWORD={$password}

BASE_URL={$baseUrl}
APP_DEBUG=false

ADMIN_EMAIL={$adminEmail}
SMTP_HOST={$smtpHost}
SMTP_PORT={$smtpPort}
SMTP_USERNAME={$smtpUser}
SMTP_PASSWORD={$smtpPass}
ENV;

file_put_contents('.env', $envContent);
chmod('.env', 0600);
```

## Error Handling

### Environment Check Failures

**PHP Version Too Old:**
```
Error: PHP Version
Required: >= 8.1.0
Current: 7.4.x
Action: Upgrade PHP
```

**Missing Extension:**
```
Error: PHP Extension: pdo_mysql
Status: Missing
Action: Install extension via hosting control panel
```

**Directory Not Writable:**
```
Error: Directory writable: uploads/photos
Status: Not writable
Action: Set permissions to 755
```

### Database Errors

**Connection Failed:**
```
Database connection failed: SQLSTATE[HY000] [2002]
Connection refused

Common causes:
- Wrong host (try 'localhost' or '127.0.0.1')
- Database doesn't exist
- Incorrect credentials
- MySQL service not running
```

**Table Creation Failed:**
```
Database installation failed: SQLSTATE[42000]
Table 'children' already exists

Action: Drop existing tables or use different database
```

### Admin Creation Errors

**Email Invalid:**
```
Error: Please provide a valid email address
Email: not-an-email
```

**Email Mismatch:**
```
Error: Email addresses do not match
Entered: user@example.com
Confirm: admin@example.com
```

## Testing

### Manual Test Checklist

- [ ] Environment check displays all requirements
- [ ] Failed checks prevent progression
- [ ] Database connection test works
- [ ] Invalid credentials show error
- [ ] Schema installs without errors
- [ ] .env file created with correct values
- [ ] .env has 600 permissions
- [ ] Admin account created in database
- [ ] .installed lock file created
- [ ] Completion screen displays
- [ ] Second run blocked by .installed file
- [ ] Can delete .installed and re-run
- [ ] Upload directories created automatically
- [ ] SMTP settings optional (can be empty)

### Automated Testing

**PHPStan Analysis:**
```bash
vendor/bin/phpstan analyse install/Installer.php --level 6
```

**Security Scan:**
- File permissions validation
- SQL injection prevention (prepared statements)
- XSS prevention (htmlspecialchars on output)
- Session security

## Deployment Considerations

### Pre-Installation

**Server Requirements Document:**
- Share INSTALL.md with users
- List PHP/MySQL requirements
- Explain database creation step

**Hosting Compatibility:**
- Shared hosting: ✓ Full support
- VPS/Dedicated: ✓ Full support
- Cloud platforms: ✓ Full support
- cPanel: ✓ Works great
- Plesk: ✓ Works great

### Post-Installation

**Delete Installer?**
- No, keep for future reinstalls
- Lock file prevents misuse
- .htaccess protects files

**Backup Strategy:**
- Backup database before reinstalling
- Backup .env file
- Delete .installed to re-run

## Future Enhancements

### Potential Improvements

1. **Multi-Step Database Import**
   - Import sample data
   - Skip sample data option
   - Progress bar for large imports

2. **Email Testing**
   - Send test email during setup
   - Verify SMTP settings work
   - Show email preview

3. **Security Hardening**
   - CSRF tokens on all forms
   - Rate limiting on installer
   - IP whitelist option

4. **Enhanced Validation**
   - Check URL reachability
   - Validate email deliverability
   - Test file upload functionality

5. **Rollback Capability**
   - Previous step navigation
   - Edit previous settings
   - Cancel installation safely

6. **Logging**
   - Log installation attempts
   - Track errors during install
   - Helpful for debugging

## Troubleshooting Guide

### Common Issues

**Issue:** "Already Installed" message
**Solution:** Delete `.installed` file from root directory

**Issue:** "Environment check failed"
**Solution:** Fix failed requirements, refresh page

**Issue:** "Database connection failed"
**Solution:** Verify credentials, ensure DB exists

**Issue:** "Permission denied" writing .env
**Solution:** Check root directory write permissions

**Issue:** "Email not received" after completion
**Solution:** Check SMTP settings, spam folder

**Issue:** "Can't access admin panel"
**Solution:** Use magic link login, not password

## Documentation

**User-Facing:**
- `INSTALL.md` - Complete installation guide
- Installer UI includes inline help
- Completion screen lists next steps

**Developer-Facing:**
- This document (technical reference)
- Code comments in Installer.php
- Schema documentation in schema.sql

## Related Features

- **Magic Link Authentication** (docs/features/magic-link-auth.md)
- **Email System** (docs/components/email-system.md)
- **Database Schema** (database/migrations/)
- **.env Configuration** (.env.example)

## Version History

### v1.9.4 (2025-11-15)
- Initial installer implementation
- 5-step installation wizard
- Complete database schema
- First admin account creation
- .env file generation
- Security lock mechanism

---

**Last Updated:** 2025-11-15
**Author:** CFK Development Team
**Status:** Production Ready
