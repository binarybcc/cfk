# Christmas for Kids - Installation Guide

Version 1.9.4

## Overview

Christmas for Kids uses a WordPress-style web installer that guides you through the complete setup process in just a few minutes.

## Prerequisites

Before you begin, make sure you have:

1. **PHP 8.1 or higher** with the following extensions:
   - PDO
   - PDO MySQL
   - mbstring
   - JSON
   - Session
   - OpenSSL

2. **MySQL 5.7+ or MariaDB 10.2+**
   - You must create the database before running the installer
   - The installer will create all tables automatically

3. **Web Server**
   - Apache with mod_rewrite (recommended)
   - Nginx with proper PHP configuration

4. **Email Address**
   - A valid email address that can receive mail
   - Required for passwordless magic link authentication

## Installation Steps

### 1. Upload Files

Upload all files to your web server via FTP or your hosting control panel.

### 2. Create Database

Using phpMyAdmin, cPanel, or MySQL command line, create a new database:

```sql
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Create a database user (or use existing):

```sql
CREATE USER 'your_db_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON your_database_name.* TO 'your_db_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Run the Installer

Visit your site in a web browser:

```
https://yourdomain.com/install.php
```

The installer will guide you through 5 steps:

#### Step 1: Welcome Screen
- Review what the installer will do
- Click "Get Started"

#### Step 2: Environment Check
- The installer checks your server for compatibility
- All required items must pass before continuing
- If any checks fail, fix them and refresh the page

#### Step 3: Database Configuration
- Enter your database credentials:
  - **Host:** Usually `localhost`
  - **Database Name:** The database you created
  - **Username:** Database user
  - **Password:** Database password
- Click "Test Connection" to verify
- Click "Install Database" to create tables

#### Step 4: Site Configuration
- **Site URL:** Your full site URL (e.g., `https://yourdomain.com/`)
- **Admin Email:** Email for system notifications
- **SMTP Settings (Optional):**
  - Default settings work with Nexcess hosting (MailChannels)
  - Customize if using different email provider

#### Step 5: Admin Account Creation
- Enter your full name
- Enter your email address (must be valid!)
- Confirm email address
- This creates your first admin account

**Important:** This system uses **passwordless authentication**. You log in by clicking a magic link sent to your email - no passwords needed!

#### Step 6: Installation Complete
- The installer creates a `.installed` lock file
- You'll see next steps and login instructions
- Click "Go to Admin Login"

### 4. First Login

1. Go to `/admin/login`
2. Enter your email address
3. Check your email for a magic link (expires in 5 minutes)
4. Click the magic link to log in
5. You're now in the admin panel!

## Post-Installation

After logging in, you should:

1. **Configure Site Settings**
   - Go to Settings in the admin panel
   - Review and customize as needed

2. **Upload Child Profiles**
   - Manually add children via the admin panel
   - Or use CSV import for bulk uploads

3. **Test Email System**
   - Request a test magic link
   - Ensure emails are being delivered

4. **Set Up Avatars**
   - Upload avatar images to `uploads/avatars/`
   - Organize by category (baby-boy, elementary-girl, etc.)

5. **Review Security**
   - Ensure `.env` file is not accessible via browser
   - Verify `.installed` file was created
   - Check that installer is locked

## File Permissions

Ensure these directories are writable by the web server:

- `uploads/` (755)
- `uploads/photos/` (755)
- `.env` (600 - readable only by owner)

## Security Notes

### Environment File
Your `.env` file contains sensitive database credentials and should:
- Have 600 permissions (owner read/write only)
- Never be committed to version control
- Never be accessible via web browser

### Installer Lock
After installation, a `.installed` file is created. To run the installer again:
1. Delete the `.installed` file from your server
2. Delete the `.env` file (or back it up)
3. Visit `/install.php` again

**Warning:** Re-running the installer will reset your database!

### Magic Link Authentication
- Magic links expire in 5 minutes
- Each link can only be used once
- Links are tied to IP address and user agent
- Rate limiting: 5 attempts per 15 minutes

## Troubleshooting

### "Environment Check Failed"
- Verify PHP version is 8.1 or higher
- Check that all required PHP extensions are installed
- Ensure `uploads/` directories exist and are writable

### "Database Connection Failed"
- Verify database credentials
- Check that database exists
- Ensure database user has proper permissions
- Try connecting manually with credentials

### "Email Not Received"
- Check spam/junk folder
- Verify SMTP settings in `.env`
- Test email server connectivity
- Check email logs in database

### "Installer Already Run"
- Delete `.installed` file to run installer again
- Backup your `.env` file first if needed

### "Permission Denied" Errors
- Check file permissions on `uploads/` directory
- Ensure web server user can write to directory
- Run `chmod 755 uploads/` if needed

## Advanced Configuration

### Custom SMTP Settings

Edit `.env` file after installation:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
```

### Database Prefix

The installer creates tables without a prefix. To add a prefix, you'll need to:
1. Manually modify `install/schema.sql`
2. Update all model files to use prefixed table names

### Multi-Environment Setup

For staging/production environments:
1. Use different `.env` files for each
2. Set `APP_DEBUG=true` for staging
3. Set `APP_DEBUG=false` for production

## Getting Help

If you encounter issues:

1. Check server error logs
2. Enable debug mode in `.env`: `APP_DEBUG=true`
3. Review `admin_login_log` table for authentication issues
4. Check database for email_log entries

## Upgrade Path

When upgrading from a previous version:
- **Do not** run the installer
- Use migration scripts in `database/migrations/`
- Always backup your database first

## Uninstallation

To completely remove the application:

1. Delete all files from your server
2. Drop the database:
   ```sql
   DROP DATABASE your_database_name;
   ```
3. Remove database user (optional):
   ```sql
   DROP USER 'your_db_user'@'localhost';
   ```

---

**Version:** 1.9.4
**Last Updated:** 2025-11-15
**Support:** See PROJECT-STATUS.md for current development status
