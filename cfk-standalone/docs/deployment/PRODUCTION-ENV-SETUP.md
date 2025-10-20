# Production Environment Setup Guide

**Date:** October 18, 2025
**Version:** 1.6+
**Priority:** CRITICAL for production deployment

---

## Overview

The Christmas for Kids application uses **environment variables** for all sensitive configuration. This ensures credentials are NEVER committed to version control.

## Quick Setup

### 1. Create `.env` File on Production Server

```bash
# On production server (cforkids.org)
cd /home/a4409d26/d646a74eb9.nxcli.io/html

# Create .env file
cat > .env << 'EOF'
# Database Configuration
DB_HOST=localhost
DB_NAME=a4409d26_509946
DB_USER=a4409d26_509946
DB_PASSWORD=YOUR_ACTUAL_DATABASE_PASSWORD_HERE

# SMTP Configuration (optional)
SMTP_USERNAME=
SMTP_PASSWORD=

# Application Settings
APP_DEBUG=false
BASE_URL=https://cforkids.org
EOF

# Set secure permissions (CRITICAL!)
chmod 600 .env
chown a4409d26_1:a4409d26_1 .env
```

### 2. Verify Permissions

```bash
# Should show: -rw------- (600)
ls -la .env

# Owner should be your user
# File should NOT be readable by other users
```

### 3. Test Database Connection

```bash
# Test that environment variables are loaded
php -r "
require_once 'config/config.php';
echo 'DB Host: ' . getenv('DB_HOST') . PHP_EOL;
echo 'DB Name: ' . getenv('DB_NAME') . PHP_EOL;
echo 'DB User: ' . getenv('DB_USER') . PHP_EOL;
echo 'Password set: ' . (getenv('DB_PASSWORD') ? 'YES' : 'NO') . PHP_EOL;
"
```

---

## How It Works

### Main Configuration File

The application uses **`config/config.php`** which automatically:

1. **Loads `.env` file** (if it exists)
2. **Uses environment variables** for sensitive data
3. **Falls back to defaults** for development

```php
// config/config.php automatically loads .env
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = parse_ini_file(__DIR__ . '/../.env');
    if ($envFile) {
        foreach ($envFile as $key => $value) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Database uses environment variables
$dbConfig = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'database' => getenv('DB_NAME') ?: 'cfk_sponsorship',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: ''
];
```

### Production-Specific Configuration

**`config/config.production.php` is OPTIONAL and NOT REQUIRED.**

- The main `config.php` already handles production vs development
- If you have `config.production.php`, it should ALSO use environment variables
- This file is now in `.gitignore` to prevent credential leaks

---

## Security Checklist

### ✅ Before Deployment

- [ ] `.env` file created on production server
- [ ] `.env` has correct database credentials
- [ ] `.env` permissions set to `600` (owner read/write only)
- [ ] `.env` is in `.gitignore` (already done)
- [ ] `config/config.production.php` is in `.gitignore` (already done)
- [ ] No hardcoded passwords in any PHP files
- [ ] Test database connection works
- [ ] Verify `.env` is NOT in git: `git status .env` (should be ignored)

### ✅ After Deployment

- [ ] Application loads successfully
- [ ] Database connections work
- [ ] Admin login works
- [ ] Check error logs for credential issues: `tail -50 /home/a4409d26/logs/php_error.log`

---

## Environment Variables Reference

### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DB_HOST` | Database server hostname | `localhost` |
| `DB_NAME` | Database name | `a4409d26_509946` |
| `DB_USER` | Database username | `a4409d26_509946` |
| `DB_PASSWORD` | Database password | `StrongP@ssw0rd!` |

### Optional Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `SMTP_USERNAME` | SMTP authentication username | (empty) |
| `SMTP_PASSWORD` | SMTP authentication password | (empty) |
| `APP_DEBUG` | Enable debug mode | `false` in production |
| `BASE_URL` | Application base URL | Detected automatically |

---

## Troubleshooting

### Database Connection Fails

**Symptom:** "Database connection failed" error

**Solution:**
```bash
# 1. Verify .env file exists
ls -la .env

# 2. Check .env has correct values
cat .env

# 3. Test environment variables are loaded
php -r "var_dump(getenv('DB_PASSWORD'));"

# 4. Test database connection manually
mysql -u a4409d26_509946 -p a4409d26_509946
# Enter password from .env file
```

### Environment Variables Not Loading

**Symptom:** Application uses default values instead of .env values

**Solution:**
```bash
# 1. Check .env file location
# Should be in: /home/a4409d26/d646a74eb9.nxcli.io/html/.env
pwd
ls -la .env

# 2. Verify file is readable by web server
ls -la .env
# Should show: -rw------- user user

# 3. Check for syntax errors in .env
cat .env
# Format: KEY=value (no spaces around =)
```

### Permission Denied

**Symptom:** "Permission denied" reading .env file

**Solution:**
```bash
# Fix permissions
chmod 600 .env
chown a4409d26_1:a4409d26_1 .env

# Verify
ls -la .env
```

---

## Migration from Hardcoded Credentials

If you're migrating from hardcoded credentials in `config.production.php`:

### Step 1: Create .env File (see above)

### Step 2: Remove Old Config (Optional)

```bash
# Since config.production.php is now in .gitignore and uses .env,
# you can either:

# Option A: Keep it (uses .env now, no hardcoded passwords)
# - Already updated to use environment variables
# - Safe to keep as reference

# Option B: Delete it (main config.php is sufficient)
rm config/config.production.php
# Application will work fine without it
```

### Step 3: Verify No Hardcoded Passwords

```bash
# Search for hardcoded passwords
grep -r "password.*=.*['\"]" config/*.php

# Should NOT find any hardcoded database passwords
# Only configuration keys like 'password_min_length'
```

---

## Git History Cleanup (CRITICAL!)

If hardcoded passwords were previously committed to git, you MUST purge them from history:

### Option 1: BFG Repo-Cleaner (Recommended)

```bash
# Install BFG
# Download from: https://rtyley.github.io/bfg-repo-cleaner/

# Backup repository first!
git clone --mirror https://github.com/binarybcc/cfk.git cfk-backup.git

# Remove hardcoded password from all history
bfg --replace-text passwords.txt cfk.git
# passwords.txt should contain: Fests42Cue50Fennel56Auk46

# Clean up
cd cfk.git
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# Force push (DANGEROUS - coordinate with team!)
git push origin --force --all
```

### Option 2: git-filter-repo (Alternative)

```bash
# Install git-filter-repo
pip install git-filter-repo

# Remove file from history
git filter-repo --path config/config.production.php --invert-paths

# Force push
git push origin --force --all
```

### ⚠️ WARNING

- Force pushing rewrites history
- Coordinate with all team members
- Everyone must re-clone repository
- Rotate database password FIRST
- Document the change

---

## Production Deployment Checklist

### Pre-Deployment

- [ ] Create `.env` file with production credentials
- [ ] Set `.env` permissions to `600`
- [ ] Verify `.gitignore` excludes `.env` and `config.production.php`
- [ ] Test database connection locally
- [ ] Rotate database password if previously exposed

### Deployment

- [ ] Upload code via git pull or FTP
- [ ] Ensure `.env` file is in place (NOT uploaded from git)
- [ ] Run database migrations if needed
- [ ] Test application loads
- [ ] Check error logs

### Post-Deployment

- [ ] Verify admin login works
- [ ] Test sponsorship workflow
- [ ] Check email sending
- [ ] Monitor error logs for 24 hours
- [ ] Backup `.env` file to secure location

---

## Best Practices

### DO ✅

- **Use `.env` files** for all sensitive configuration
- **Set `.env` permissions to `600`** (owner read/write only)
- **Keep `.env` in `.gitignore`**
- **Use different `.env` files** for development/staging/production
- **Backup `.env` file** to secure password manager
- **Use strong passwords** (minimum 16 characters, mixed case, numbers, symbols)
- **Rotate passwords** quarterly or after exposure

### DON'T ❌

- **NEVER commit `.env` to git**
- **NEVER hardcode credentials in PHP files**
- **NEVER share `.env` via email or Slack**
- **NEVER use same password** for development and production
- **NEVER store `.env` in public directories**
- **NEVER set `.env` permissions to `644` or `777`**

---

## Support

**Questions?**
- Review this guide first
- Check `docs/deployment/` for deployment guides
- See `.env.example` for template

**Security Issues?**
- Rotate credentials immediately
- Review access logs
- Contact repository owner

---

**Document Version:** 1.0
**Last Updated:** October 18, 2025
**Related Docs:**
- `.env.example` - Environment variable template
- `docs/deployment/v1.4-deployment.md` - Full deployment guide
- `docs/audits/v1.6-technical-evaluation.md` - Security audit
