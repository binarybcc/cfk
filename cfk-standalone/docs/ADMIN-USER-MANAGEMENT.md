# Admin User Management Feature

**Version:** 1.4
**Date Added:** 2025-10-12
**Status:** ✅ DEPLOYED

---

## 🎯 Overview

The admin panel now includes comprehensive user management capabilities that allow administrators to create, edit, and manage multiple administrator accounts with different permission levels.

---

## ✨ Features

### 1. **Admin User Listing**
- View all administrator accounts
- See user details (username, email, role, last login)
- Visual indicators for current user
- Role-based badges (Admin vs Editor)

### 2. **Add New Administrators**
- Create new admin accounts from within the panel
- Set username, email, password, and role
- Real-time form validation
- Password strength requirements (8+ characters)

### 3. **Edit Administrators**
- Update email address and full name
- Change user roles (Admin ↔ Editor)
- Reset passwords for other users
- Inline editing interface

### 4. **Delete Administrators**
- Remove admin accounts
- Safety: Cannot delete your own account
- Confirmation dialog before deletion
- Activity logging for security

---

## 🔐 Roles & Permissions

### Administrator Role
**Full access to all features:**
- ✅ Manage children and families
- ✅ Manage sponsorships
- ✅ View reports
- ✅ **Manage other administrators**
- ✅ Add/edit/delete admin users
- ✅ Year-end reset

### Editor Role
**Limited access:**
- ✅ Manage children and families
- ✅ Manage sponsorships
- ✅ View reports
- ❌ Cannot manage administrators
- ❌ Cannot see "Administrators" menu
- ✅ Year-end reset

---

## 🌐 Access

**URL:**
```
https://cforkids.org/admin/manage_admins.php
```

**Navigation:**
- Only visible to users with **Administrator** role
- Located in main admin navigation bar
- Between "Reports" and "Year-End Reset"

**Access Control:**
- Requires login
- Must have `admin` role
- Editors see "Access denied" message

---

## 📋 Usage Instructions

### Adding a New Administrator

1. **Navigate to Administrators page**
   - Click "Administrators" in main navigation

2. **Click "Add New Administrator" button**
   - Opens add admin form

3. **Fill in required fields:**
   - **Username*** - Letters, numbers, underscore, hyphen only
   - **Email Address*** - Valid email format
   - Full Name - Optional display name
   - **Password*** - Minimum 8 characters
   - **Confirm Password*** - Must match
   - **Role*** - Admin or Editor

4. **Click "Create Administrator"**
   - New admin account created
   - Success message displayed
   - Activity logged

### Editing an Administrator

1. **Click "Edit" button** next to admin user

2. **Modify fields:**
   - Email address
   - Full name
   - Role (Admin/Editor)
   - New password (optional)

3. **Click "Save Changes"**
   - Updates applied
   - Success message shown

4. **Click "Cancel"** to discard changes

### Deleting an Administrator

1. **Click "Delete" button** next to admin user
   - Not available for your own account

2. **Confirm deletion** in dialog
   - "Are you sure?" warning

3. **User deleted permanently**
   - Cannot be undone
   - Activity logged

---

## 🛡️ Security Features

### Password Requirements
- Minimum 8 characters
- Client and server-side validation
- Passwords hashed with bcrypt
- Confirmation required on creation

### Access Control
- Role-based menu visibility
- Page-level permission checks
- Cannot delete own account
- CSRF token protection on all forms

### Activity Logging
All admin management actions are logged:
```
CFK Admin: New admin user created: newuser by SaintNick
CFK Admin: Admin user updated: ID 2 by SaintNick
CFK Admin: Admin user deleted: olduser by SaintNick
```

### Username Rules
- Letters, numbers, underscore, hyphen only
- Must be unique
- Cannot be changed after creation
- Validated with regex pattern

---

## 🎨 User Interface

### Modern, Clean Design
- Card-based layout
- Responsive table design
- Color-coded role badges
- Inline editing forms
- Smooth animations

### Visual Indicators
- 🟦 **"You"** badge - Current logged-in user
- 🔴 **Admin** badge - Administrator role
- 🔵 **Editor** badge - Editor role

### Form Features
- Real-time validation
- Password confirmation
- Role selection dropdown
- Disabled username field on edit
- Optional password update

---

## 📁 Files

### New Files Created:
```
/admin/manage_admins.php (24KB)
```

### Modified Files:
```
/admin/includes/admin_header.php
```

### Navigation Addition:
- "Administrators" link added (admins only)
- Conditional display based on role
- Active state highlighting

---

## 🧪 Testing Checklist

**Basic Access:**
- [ ] Admin role sees "Administrators" menu item
- [ ] Editor role does NOT see menu item
- [ ] Direct URL access blocked for editors
- [ ] Page loads without errors

**Add Administrator:**
- [ ] Click "Add New Administrator" button
- [ ] Form displays correctly
- [ ] All fields validated
- [ ] Password mismatch shows error
- [ ] Short password (< 8 chars) rejected
- [ ] Duplicate username rejected
- [ ] Success message on creation
- [ ] New admin appears in list

**Edit Administrator:**
- [ ] Click "Edit" button
- [ ] Inline form displays
- [ ] Username field disabled
- [ ] Can update email
- [ ] Can change role
- [ ] Can set new password (optional)
- [ ] Changes saved successfully
- [ ] Cancel button works

**Delete Administrator:**
- [ ] "Delete" button hidden for own account
- [ ] "Delete" button visible for other accounts
- [ ] Confirmation dialog appears
- [ ] User deleted after confirmation
- [ ] Success message displayed

**Security:**
- [ ] CSRF tokens present on all forms
- [ ] Passwords hashed in database
- [ ] Role checks on page load
- [ ] Activity logged correctly
- [ ] Cannot delete own account

---

## 🗄️ Database Schema

Uses existing `admin_users` table:

```sql
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
    INDEX idx_reset_token (reset_token)
);
```

**No database migration needed** - uses existing table structure.

---

## 📊 Current Administrator

**Production Setup:**
```
Username: SaintNick
Email:    jcorbin@upstatetoday.com
Role:     admin
```

This account has full access to all features including admin user management.

---

## 🔧 Configuration

### Role Definitions

**In code:**
```php
// Admin role check
if ($_SESSION['cfk_admin_role'] !== 'admin') {
    die('Access denied. Only administrators can manage admin users.');
}
```

**In navigation:**
```php
<?php if ($_SESSION['cfk_admin_role'] === 'admin'): ?>
<li><a href="manage_admins.php">Administrators</a></li>
<?php endif; ?>
```

### Default Role
New administrators default to **Editor** role:
```sql
role ENUM('admin', 'editor') DEFAULT 'editor'
```

---

## 🚨 Important Notes

### Cannot Delete Own Account
- Safety feature to prevent lockout
- Delete button hidden for current user
- Server-side check prevents POST manipulation

### Username Immutability
- Usernames cannot be changed after creation
- Edit form shows disabled username field
- Prevents session/authentication issues

### Role Demotion Warning
⚠️ **Be careful when changing roles:**
- Changing admin → editor removes admin panel access
- User loses "Administrators" menu visibility
- Cannot demote yourself (must use another admin)

### Password Reset Integration
- Works with forgot password feature
- Email required for password resets
- Reset tokens stored in same table

---

## 📞 Support & Troubleshooting

### "Access Denied" Message
**Issue:** User sees access denied on manage_admins.php

**Solution:**
Check user role in database:
```sql
SELECT username, role FROM admin_users WHERE username = 'username';
```

Update role if needed:
```sql
UPDATE admin_users SET role = 'admin' WHERE username = 'username';
```

### Cannot Add Admin - "Username Exists"
**Issue:** Username already in use

**Solution:**
Check existing usernames:
```sql
SELECT username FROM admin_users;
```

Delete old account if appropriate:
```sql
DELETE FROM admin_users WHERE username = 'oldusername';
```

### Forgot Admin Password
**Issue:** Admin cannot log in

**Solution:**
1. Use forgot password feature (if email set)
2. Or manually reset via database:
```bash
# Generate new hash
php -r "echo password_hash('NewPassword123', PASSWORD_DEFAULT);"

# Update in database
mysql -u a4409d26_509946 -p'...' a4409d26_509946 \
  -e "UPDATE admin_users SET password_hash='$2y$10$...' WHERE username='admin';"
```

### No Administrators Menu
**Issue:** Admin doesn't see "Administrators" link

**Solution:**
1. Clear browser cache
2. Re-upload admin_header.php
3. Check role in database is 'admin'

---

## 📈 Future Enhancements

**Possible additions:**
- [ ] Admin activity log page
- [ ] Failed login attempt tracking
- [ ] Two-factor authentication
- [ ] Admin groups/teams
- [ ] Bulk user import
- [ ] Account suspension (vs deletion)
- [ ] Password expiration policy
- [ ] Login history per user

---

## ✅ Deployment Status

**Deployed to Production:** 2025-10-12

**Files on Server:**
- ✅ `/admin/manage_admins.php`
- ✅ `/admin/includes/admin_header.php` (updated)

**Verified Working:**
- ✅ Page loads for admins
- ✅ Navigation link visible
- ✅ Forms functional
- ✅ Database operations working

---

**Last Updated:** 2025-10-12
**Version:** 1.4
**Status:** ✅ LIVE
