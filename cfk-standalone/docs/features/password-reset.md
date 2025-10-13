# Admin Password Reset Feature

**Version:** 1.4
**Date Added:** 2025-10-12
**Status:** ✅ DEPLOYED

---

## 🔐 Overview

The admin panel now includes a secure password reset system that allows administrators to reset their passwords via email when they forget their login credentials.

---

## ✨ Features

### 1. **Forgot Password Link**
- Added "Forgot Password?" link on admin login page
- Location: `/admin/login.php`

### 2. **Password Reset Request**
- URL: `/admin/forgot_password.php`
- Requires: Username + Email address
- Sends secure reset link via email
- Reset tokens expire after 1 hour

### 3. **Password Reset**
- URL: `/admin/reset_password.php?token=...&user=...`
- Validates reset token and expiry
- Real-time password strength indicator
- Requires 8+ character passwords
- Confirms password match before submission

---

## 🔧 Technical Implementation

### Database Schema

New columns added to `admin_users` table:

```sql
reset_token VARCHAR(255) DEFAULT NULL
reset_token_expiry DATETIME DEFAULT NULL
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

**Index added:**
```sql
INDEX idx_reset_token (reset_token)
```

### Security Features

1. **Token Security:**
   - 64-character random tokens (bin2hex + random_bytes)
   - Tokens stored hashed in database (password_hash)
   - 1-hour expiration window
   - Tokens cleared after successful reset

2. **Rate Limiting:**
   - Failed attempts logged with IP address
   - No user enumeration (same message for valid/invalid users)

3. **CSRF Protection:**
   - All forms include CSRF tokens
   - Validated on submission

4. **Email Validation:**
   - Requires matching username + email
   - Prevents unauthorized reset requests

---

## 📧 Email Configuration

Password reset emails are sent using PHP's `mail()` function.

**Email Content:**
- From: Site admin email (from config)
- Subject: "Password Reset Request - CFK Admin"
- Contains: Reset link with token
- Expiration notice: 1 hour

**Example Email:**
```
Hello admin_username,

You requested a password reset for your Christmas for Kids admin account.

Click the link below to reset your password:

https://cforkids.org/admin/reset_password.php?token=...&user=...

This link will expire in 1 hour.

If you didn't request this, please ignore this email.

Best regards,
Christmas for Kids Team
```

---

## 🚀 Usage Instructions

### For Administrators:

1. **Navigate to admin login:**
   ```
   https://cforkids.org/admin/login.php
   ```

2. **Click "Forgot Password?" link**

3. **Enter your username and email address**
   - Must match the email registered with your account

4. **Check your email for reset link**
   - Link expires in 1 hour
   - Contains secure token

5. **Click the reset link**

6. **Enter new password:**
   - Minimum 8 characters
   - Password strength indicator guides you
   - Confirm password to prevent typos

7. **Submit and log in**
   - Redirected to login page
   - Use new password to access admin panel

---

## 🛠️ Troubleshooting

### Email Not Received

**Check:**
1. Spam/junk folder
2. Email address is correct in admin_users table
3. Server mail() function is configured
4. Check server logs: `tail ~/logs/error_log`

**Manual Fix:**
```bash
# SSH to server
ssh a4409d26_1@d646a74eb9.nxcli.io

# Update admin email if needed
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 \
  -e "UPDATE admin_users SET email='new@email.com' WHERE username='admin';"
```

### Token Expired

- Reset tokens expire after 1 hour for security
- Request a new reset link if expired

### Reset Link Not Working

**Verify:**
1. Link hasn't been used already (tokens are single-use)
2. Token hasn't expired (check `reset_token_expiry`)
3. Username in URL matches database

**Manual Token Clearance:**
```sql
UPDATE admin_users
SET reset_token = NULL, reset_token_expiry = NULL
WHERE username = 'username';
```

---

## 🔒 Security Considerations

### Password Requirements

- **Minimum length:** 8 characters
- **Recommended:** Mix of letters, numbers, symbols
- **Enforced:** Password must match confirmation
- **Strength indicator:** Real-time feedback

### Token Security

- Tokens are 256-bit random values
- Stored hashed in database (not plaintext)
- 1-hour expiration window
- Single-use (cleared after reset)
- No user enumeration (security through obscurity)

### Logging

All password reset activities are logged:
```
CFK Admin: Password reset requested for username: admin from IP: 1.2.3.4
CFK Admin: Password reset successful for username: admin from IP: 1.2.3.4
```

---

## 📁 Files Added/Modified

### New Files:
- `/admin/forgot_password.php` - Password reset request page
- `/admin/reset_password.php` - Password reset form
- `/database/add_password_reset_columns.sql` - Database migration

### Modified Files:
- `/admin/login.php` - Added "Forgot Password?" link
- `/database/schema.sql` - Updated admin_users table structure

---

## 🧪 Testing Checklist

- [ ] Visit `/admin/login.php` - "Forgot Password?" link visible
- [ ] Click link - Redirects to forgot_password.php
- [ ] Submit with invalid username/email - Shows success message (security)
- [ ] Submit with valid username/email - Email sent
- [ ] Check email - Reset link received
- [ ] Click reset link - Password reset form displays
- [ ] Enter weak password - Strength indicator shows "Weak"
- [ ] Enter strong password - Strength indicator shows "Strong"
- [ ] Submit mismatched passwords - Error displayed
- [ ] Submit valid new password - Success message shown
- [ ] Log in with new password - Access granted
- [ ] Try old reset link - Shows "Invalid or expired" error

---

## 📊 Database Migration

**Production migration ran on:** 2025-10-12

```sql
ALTER TABLE admin_users
ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) DEFAULT NULL AFTER password_hash,
ADD COLUMN IF NOT EXISTS reset_token_expiry DATETIME DEFAULT NULL AFTER reset_token,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS idx_reset_token ON admin_users(reset_token);
```

**Verify migration:**
```bash
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 \
  -e "DESC admin_users;"
```

---

## 🔗 URLs

**Production:**
- Login: https://cforkids.org/admin/login.php
- Forgot Password: https://cforkids.org/admin/forgot_password.php
- Reset Password: https://cforkids.org/admin/reset_password.php?token=...&user=...

**Local Development:**
- Login: http://localhost:8082/admin/login.php
- Forgot Password: http://localhost:8082/admin/forgot_password.php
- Reset Password: http://localhost:8082/admin/reset_password.php?token=...&user=...

---

## 📞 Support

If administrators cannot reset their passwords:

1. **Check email configuration:**
   ```bash
   php -r "mail('test@example.com', 'Test', 'Test message');"
   ```

2. **Manual password reset via database:**
   ```bash
   # Generate new password hash
   php -r "echo password_hash('NewPassword123', PASSWORD_DEFAULT);"

   # Update in database
   mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 \
     -e "UPDATE admin_users SET password_hash='$2y$10$...' WHERE username='admin';"
   ```

3. **Clear stuck reset tokens:**
   ```sql
   UPDATE admin_users
   SET reset_token = NULL, reset_token_expiry = NULL
   WHERE reset_token_expiry < NOW();
   ```

---

**Deployment Status:** ✅ **LIVE ON PRODUCTION**

Last updated: 2025-10-12
