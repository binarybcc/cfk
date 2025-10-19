# Magic Link Admin Account Setup

**Version**: 1.6.1
**Date**: October 19, 2025
**Authentication**: Passwordless (Magic Link Only)

---

## Overview

As of v1.6.1, the admin login system uses **magic link authentication only**. There is no password login option. This provides:

- ✅ Enhanced security (no password reuse, automatic expiration)
- ✅ Better user experience (no passwords to remember)
- ✅ Simplified codebase (no tab switching, no Safari compatibility issues)
- ✅ Automatic 2FA (email verification required)

---

## Initial Setup Requirements

### Prerequisites

1. **Database** must be initialized with all migrations
2. **Email system** must be configured (SMTP or PHP mail())
3. **Valid admin email addresses** that can receive emails

### Critical Configuration

Verify your `.env` file has proper email configuration:

```ini
# SMTP Configuration (recommended for production)
EMAIL_USE_SMTP=true
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=noreply@example.com
SMTP_PASSWORD=your_smtp_password
SMTP_FROM_EMAIL=noreply@example.com
SMTP_FROM_NAME=Christmas for Kids
```

**Test email sending BEFORE creating admin accounts!**

---

## Method 1: SQL Migration (Fastest)

Best for initial deployment with known admin emails.

### Step 1: Edit the Migration File

```bash
nano database/migrations/007_create_initial_admin_accounts.sql
```

Change the email address on line 22:

```sql
INSERT INTO admin_users (username, email, password_hash, full_name, role)
VALUES (
    'admin',
    'your-email@example.com',  -- CHANGE THIS to actual admin email
    '$2y$10$92IXUNpkjO0rJVMpTKq5weLXMO8zKkB6jD3Gq5Y5YqJ5pGqMYqJ5e',
    'System Administrator',
    'admin'
);
```

### Step 2: Run the Migration

**Local/Docker:**
```bash
docker exec cfk-standalone-db-1 mysql -u root -proot cfk_sponsorship_dev < database/migrations/007_create_initial_admin_accounts.sql
```

**Production:**
```bash
mysql -u a4409d26_509946 -p a4409d26_509946 < database/migrations/007_create_initial_admin_accounts.sql
```

### Step 3: Verify Account Created

```sql
SELECT id, username, email, role FROM admin_users;
```

Expected output:
```
+----+----------+------------------------+-------+
| id | username | email                  | role  |
+----+----------+------------------------+-------+
|  1 | admin    | your-email@example.com | admin |
+----+----------+------------------------+-------+
```

---

## Method 2: CLI Script (Interactive)

Best for adding accounts after initial deployment.

### Basic Usage (Interactive)

```bash
php scripts/create-admin-account.php
```

You'll be prompted for:
- Email address (where magic links will be sent)
- Full name
- Username (default: email prefix)
- Role (admin/editor, default: admin)

**Example Session:**
```
Email address (where magic links will be sent): john.doe@cforkids.org
Full name: John Doe
Username (default: john.doe):
Role (admin/editor, default: admin): admin

✅ Account created successfully!

╔════════════════════════════════════════════════════════════╗
║  Account Details                                           ║
╠════════════════════════════════════════════════════════════╣
║  Username: john.doe                                        ║
║  Email:    john.doe@cforkids.org                          ║
║  Name:     John Doe                                        ║
║  Role:     admin                                           ║
╚════════════════════════════════════════════════════════════╝

📧 Magic link login emails will be sent to: john.doe@cforkids.org
```

### Command Line Mode (Non-Interactive)

```bash
php scripts/create-admin-account.php \
  --email="john.doe@cforkids.org" \
  --name="John Doe" \
  --role=admin \
  --username="john.doe"
```

**Parameters:**
- `--email` (required): Email address for magic links
- `--name` (required): Full name
- `--role` (optional): `admin` or `editor` (default: `admin`)
- `--username` (optional): Username (default: email prefix)

---

## Method 3: Direct Database Insert

For advanced users or scripted deployments.

```sql
INSERT INTO admin_users (username, email, password_hash, full_name, role)
VALUES (
    'admin',
    'admin@cforkids.org',
    '$2y$10$92IXUNpkjO0rJVMpTKq5weLXMO8zKkB6jD3Gq5Y5YqJ5pGqMYqJ5e',  -- Placeholder hash
    'System Administrator',
    'admin'
);
```

**Important Notes:**
- `password_hash` is required by database schema but NOT used for authentication
- Use the placeholder hash shown above (it's a random bcrypt hash)
- Email MUST be valid and able to receive emails

---

## Testing the Magic Link Flow

### Step 1: Navigate to Admin Login

```
https://cforkids.org/admin/login.php
```

or locally:
```
http://localhost:8082/admin/login.php
```

### Step 2: Enter Admin Email

Enter the email address you created the account with.

### Step 3: Check Email

You should receive an email within 30-60 seconds with subject:
```
Magic Link Login - Christmas for Kids
```

### Step 4: Click Login Button

The email will contain a button labeled "Login to Admin Panel". Click it to be automatically logged in.

### Step 5: Verify Login

You should be redirected to:
```
https://cforkids.org/admin/
```

And see the admin dashboard with your username displayed.

---

## Troubleshooting

### Issue: "No email received"

**Check rate limiting:**
```sql
SELECT * FROM rate_limit_tracking ORDER BY last_request DESC LIMIT 10;
```

**Clear rate limits if needed:**
```sql
DELETE FROM rate_limit_tracking;
```

**Check email logs:**
```sql
SELECT * FROM admin_login_log WHERE event_type LIKE '%magic_link%' ORDER BY timestamp DESC LIMIT 10;
```

**Verify email configuration:**
```bash
php -r "require 'config/config.php'; var_dump(config('email_use_smtp'), config('smtp_host'));"
```

### Issue: "Email not in admin_users table"

**Verify account exists:**
```sql
SELECT id, username, email, role FROM admin_users WHERE email = 'your-email@example.com';
```

**If missing, create account:**
```bash
php scripts/create-admin-account.php --email="your-email@example.com" --name="Your Name"
```

### Issue: "Magic link expired"

Magic links expire after **5 minutes** for security. Request a new one.

### Issue: "Page blank in Safari"

This was fixed in v1.6.1. If you're still seeing this:
1. Clear browser cache
2. Verify you deployed the latest `admin/login.php`
3. Check browser console for JavaScript errors

---

## Security Considerations

### Rate Limiting

Current production limits (v1.6.1):
- **Email**: 4 requests per 5 minutes, 12 per hour
- **IP**: 20 requests per 5 minutes, 20 per hour

These limits accommodate:
- Multiple admins from same office IP
- Browser switching for convenience
- Email delivery retries

### Token Security

- **Token generation**: 256-bit random tokens (`random_bytes(32)`)
- **Storage**: SHA256 hashed (never plaintext)
- **Validation**: Constant-time comparison (`hash_equals()`)
- **Expiration**: 5 minutes
- **Single-use**: Deleted immediately after validation
- **Race condition protection**: Database row-level locking

### Audit Logging

All login events are logged in `admin_login_log`:

```sql
SELECT
    event_type,
    result,
    ip_address,
    timestamp
FROM admin_login_log
ORDER BY timestamp DESC
LIMIT 50;
```

**Event Types:**
- `magic_link_sent` - Link sent successfully
- `magic_link_email_failed` - Email delivery failed
- `magic_link_requested_nonexistent_email` - Invalid email attempt
- `magic_link_validation_failed` - Invalid/expired token
- `admin_login_success` - Successful login
- `rate_limit_exceeded` - Rate limited request

---

## Adding Multiple Admins

### Option 1: Run Script Multiple Times

```bash
php scripts/create-admin-account.php --email="admin1@example.com" --name="Admin One"
php scripts/create-admin-account.php --email="admin2@example.com" --name="Admin Two"
php scripts/create-admin-account.php --email="editor@example.com" --name="Editor User" --role=editor
```

### Option 2: Batch SQL Insert

```sql
INSERT INTO admin_users (username, email, password_hash, full_name, role)
VALUES
    ('admin1', 'admin1@example.com', '$2y$10$92IXUNpkjO0rJVMpTKq5weLXMO8zKkB6jD3Gq5Y5YqJ5pGqMYqJ5e', 'Admin One', 'admin'),
    ('admin2', 'admin2@example.com', '$2y$10$92IXUNpkjO0rJVMpTKq5weLXMO8zKkB6jD3Gq5Y5YqJ5pGqMYqJ5e', 'Admin Two', 'admin'),
    ('editor1', 'editor@example.com', '$2y$10$92IXUNpkjO0rJVMpTKq5weLXMO8zKkB6jD3Gq5Y5YqJ5pGqMYqJ5e', 'Editor User', 'editor');
```

---

## Role Permissions

### Admin Role
- Full access to all admin features
- Can manage children, families, sponsorships
- Can import/export CSV data
- Can view all reports
- Can manage other admin accounts (via database)

### Editor Role
- Can view and edit children/families
- Can view sponsorships (read-only)
- Cannot import/export CSV
- Cannot view sensitive reports
- Cannot manage admin accounts

---

## Production Deployment Checklist

Before deploying to production:

- [ ] Email system configured and tested
- [ ] SMTP credentials in `.env` file
- [ ] At least one admin account created
- [ ] Test magic link flow end-to-end
- [ ] Verify rate limiting is active
- [ ] Check audit logging is working
- [ ] Backup database before migration
- [ ] Document admin email addresses securely
- [ ] Test from multiple browsers (Chrome, Safari, Firefox)
- [ ] Verify email deliverability (check spam folders)

---

## Migration from Password-Based System

If upgrading from a previous version with password authentication:

### Existing Admin Accounts

Existing accounts will continue to work with magic link authentication:
- Username and email already in database ✓
- Password hash ignored (not used) ✓
- Magic links sent to existing email addresses ✓

### No Action Required

Admins can immediately start using magic link authentication with their existing email addresses.

### Optional: Update Email Addresses

If admin emails changed:

```sql
UPDATE admin_users
SET email = 'new-email@example.com'
WHERE username = 'admin';
```

---

## FAQ

**Q: Can I still use password login?**
A: No. As of v1.6.1, password login has been removed. Magic link is the only authentication method.

**Q: What if I don't receive the magic link email?**
A: Check spam folder, verify email configuration, check rate limiting, verify admin account exists with correct email.

**Q: How long is the magic link valid?**
A: 5 minutes for security. Request a new one if expired.

**Q: Can multiple admins use the same email?**
A: No. Each admin account must have a unique email address.

**Q: What happens to the password_hash field?**
A: It's still in the database (required by schema) but not used for authentication. Set to a random hash.

**Q: Can I create admin accounts from the web interface?**
A: Not currently. Use the CLI script or SQL migration for security.

---

## Related Documentation

- [Security Hardening Report](../audits/v1.6.1-security-hardening-report.md)
- [Magic Link Email Templates](../components/email-system.md)
- [Rate Limiting Configuration](../technical/rate-limiting.md)

---

**Document Version**: 1.0
**Last Updated**: October 19, 2025
**Next Review**: After any authentication changes
