# Staging vs Production Deployment Workflow

**Complete guide for managing deployments across Local → Staging → Production environments**

---

## 📋 Environment Overview

### 🏠 Local Development (Your Mac)
- **Purpose**: Development and testing
- **Location**: `/Users/johncorbin/Desktop/projs/cfk/cfk-standalone/`
- **Docker**: OrbStack (PHP 8.2-apache + MySQL)
- **URL**: http://localhost:8082
- **Debug**: ON
- **Database**: cfk_sponsorship_dev (Docker container)

### 🧪 Staging Environment (Nexcess)
- **Purpose**: Pre-production testing with real-world conditions
- **Location**: Nexcess hosting account
- **URL**: https://staging.cforkids.org (or your Nexcess staging URL)
- **Debug**: ON (to catch issues before production)
- **Database**: Separate staging database
- **SMTP**: Real email sending (Nexcess MailChannels)

### 🌐 Production Environment (Nexcess)
- **Purpose**: Live site for public use
- **Location**: Nexcess hosting account
- **URL**: https://cforkids.org
- **Debug**: OFF
- **Database**: Production database
- **SMTP**: Real email sending (Nexcess MailChannels)

---

## 🔐 Environment Configuration (.env Files)

### Overview
Each environment has its own `.env` file **stored only on that server** (never in git).

### Local Development (.env)
**Location**: `/Users/johncorbin/Desktop/projs/cfk/cfk-standalone/.env`

```ini
# Local Development Environment
ENVIRONMENT=local

DB_HOST=db
DB_NAME=cfk_sponsorship_dev
DB_USER=cfk_user
DB_PASSWORD=cfk_pass

SMTP_USERNAME=
SMTP_PASSWORD=

APP_DEBUG=true
BASE_URL=http://localhost:8082
```

### Staging Server (.env)
**Location**: `/home/username/public_html/cfk-standalone/.env` (on Nexcess)

**Setup Instructions**:
1. SSH into your Nexcess staging server
2. Navigate to cfk-standalone directory
3. Copy `.env.staging.example` to `.env`
4. Edit with your staging credentials (see below)
5. Set permissions: `chmod 600 .env`

```ini
# Staging Environment
ENVIRONMENT=staging

# Get these from Nexcess cPanel > MySQL Databases
DB_HOST=localhost
DB_NAME=staging_db_name        # Your staging database name
DB_USER=staging_db_user        # Your staging database user
DB_PASSWORD=staging_db_pass    # Your staging database password

# Get these from Nexcess > Email Accounts or MailChannels
SMTP_HOST=relay.mailchannels.net
SMTP_PORT=587
SMTP_USERNAME=your_staging_smtp_user
SMTP_PASSWORD=your_staging_smtp_pass
SMTP_ENCRYPTION=tls

APP_DEBUG=true                              # Keep debug ON for staging
BASE_URL=https://staging.cforkids.org/      # Your actual staging URL
ADMIN_EMAIL=your-email+staging@example.com  # Use +staging for filtering
```

### Production Server (.env)
**Location**: `/home/username/public_html/cfk-standalone/.env` (on Nexcess)

**Setup Instructions**:
1. SSH into your Nexcess production server
2. Navigate to cfk-standalone directory
3. Copy `.env.example` to `.env`
4. Edit with your production credentials
5. Set permissions: `chmod 600 .env`

```ini
# Production Environment
ENVIRONMENT=production

DB_HOST=localhost
DB_NAME=a4409d26_509946
DB_USER=a4409d26_509946
DB_PASSWORD=Fests42Cue50Fennel56Auk46

SMTP_HOST=relay.mailchannels.net
SMTP_PORT=587
SMTP_USERNAME=your_smtp_user
SMTP_PASSWORD=your_smtp_pass
SMTP_ENCRYPTION=tls

APP_DEBUG=false                  # CRITICAL: Debug OFF in production!
BASE_URL=https://cforkids.org/
ADMIN_EMAIL=christmasforkids@upstatetoday.com
```

---

## 🔄 Standard Deployment Workflow

### Phase 1: Local Development
```bash
🏠 LOCAL:

# 1. Create feature branch
git checkout -b feature/my-new-feature

# 2. Make changes and test locally
docker-compose up -d
# Test at http://localhost:8082

# 3. Run tests
./tests/security-functional-tests.sh

# 4. Commit changes
git add -A
git commit -m "feat: Add new feature"

# 5. Push to GitHub
git push origin feature/my-new-feature
```

### Phase 2: Deploy to Staging
```bash
🧪 STAGING (Nexcess):

# 1. SSH into staging server
ssh your-user@staging.cforkids.org

# 2. Navigate to application directory
cd ~/public_html/cfk-standalone

# 3. Pull latest code
git fetch origin
git checkout feature/my-new-feature
git pull origin feature/my-new-feature

# 4. Update dependencies (if needed)
composer install --no-dev

# 5. Clear any caches (if applicable)
# (Add your cache clearing commands here)

# 6. Test staging site
# Visit https://staging.cforkids.org in browser
# Test all functionality thoroughly
```

### Phase 3: Merge to Main Branch
```bash
🏠 LOCAL:

# After staging testing succeeds:

# 1. Merge feature to main branch (v1.0.3-rebuild)
git checkout v1.0.3-rebuild
git pull origin v1.0.3-rebuild
git merge feature/my-new-feature

# 2. Push to GitHub
git push origin v1.0.3-rebuild

# 3. Delete feature branch (optional)
git branch -d feature/my-new-feature
git push origin --delete feature/my-new-feature
```

### Phase 4: Deploy to Production
```bash
🌐 PRODUCTION (Nexcess):

# 1. SSH into production server
ssh your-user@cforkids.org

# 2. Navigate to application directory
cd ~/public_html/cfk-standalone

# 3. Pull latest code from main branch
git fetch origin
git checkout v1.0.3-rebuild
git pull origin v1.0.3-rebuild

# 4. Update dependencies (if needed)
composer install --no-dev --optimize-autoloader

# 5. Clear any caches (if applicable)
# (Add your cache clearing commands here)

# 6. Verify production site
# Visit https://cforkids.org in browser
# Test critical functionality

# 7. Monitor error logs
tail -f ~/logs/error_log
```

---

## 🔍 Getting Nexcess Credentials

### Database Credentials
1. Log into Nexcess cPanel
2. Go to **Databases → MySQL Databases**
3. Find your database name and user
4. Use **phpMyAdmin** or **Remote MySQL** if you need to verify

### SMTP Credentials
1. Log into Nexcess cPanel
2. Go to **Email → Email Accounts**
3. Create or use existing email account
4. Note: Nexcess typically uses **MailChannels** relay:
   - Host: `relay.mailchannels.net`
   - Port: `587`
   - Encryption: `TLS`

### FTP/SSH Access
1. Log into Nexcess Portal (portal.nexcess.net)
2. Select your site
3. Go to **Dev Tools → SSH/SFTP**
4. Get your SSH credentials and connection info

---

## 📝 Pre-Deployment Checklist

### Before Staging Deployment
- [ ] All tests pass locally (`./tests/security-functional-tests.sh`)
- [ ] Docker environment working correctly
- [ ] Git branch pushed to GitHub
- [ ] Staging `.env` file configured correctly
- [ ] Database backup created (if schema changes)

### Before Production Deployment
- [ ] All features tested on staging environment
- [ ] No errors in staging logs
- [ ] Stakeholder approval obtained
- [ ] Production `.env` file configured correctly
- [ ] **Database backup created** (critical!)
- [ ] Maintenance window scheduled (if needed)
- [ ] Rollback plan prepared

---

## 🚨 Emergency Rollback Procedure

### Quick Rollback (Production)
```bash
🌐 PRODUCTION:

# 1. SSH into production
ssh your-user@cforkids.org

# 2. Navigate to app directory
cd ~/public_html/cfk-standalone

# 3. Check available tags/branches
git tag
git branch -a

# 4. Rollback to previous version
git checkout v1.7  # or whatever the previous stable version was

# 5. Update dependencies
composer install --no-dev --optimize-autoloader

# 6. Verify site is working
# Visit https://cforkids.org
```

### Database Rollback
```bash
# Restore from backup (adjust paths as needed)
mysql -u username -p database_name < backup_file.sql
```

---

## 🔐 Security Best Practices

### .env File Security
1. **NEVER commit `.env` files to git**
   - Already in `.gitignore`
   - Each server has its own `.env`

2. **Set restrictive permissions**
   ```bash
   chmod 600 .env
   ```

3. **Use strong, unique passwords**
   - Different passwords for staging and production
   - Use password manager

4. **Verify .gitignore**
   ```bash
   git check-ignore .env
   # Should output: .env
   ```

### Credential Management
- Store production credentials in secure location (1Password, LastPass, etc.)
- Never share credentials via email or chat
- Rotate passwords periodically
- Use separate admin emails for staging (+staging suffix)

---

## 🐛 Troubleshooting

### Environment Not Detected Correctly
**Problem**: Site shows wrong environment badge or behaves incorrectly

**Solution**:
1. Check `.env` file exists: `ls -la .env`
2. Verify `ENVIRONMENT` variable is set correctly in `.env`
3. Check file permissions: `ls -l .env` (should be 600)
4. Verify hostname matches detection rules in `config/config.php`
5. Clear any PHP opcache: Add `<?php opcache_reset();` to a test file

### SMTP Not Working on Staging/Production
**Problem**: Emails not sending

**Solution**:
1. Verify SMTP credentials in `.env`
2. Check Nexcess MailChannels is enabled
3. Test with simple PHP mail script
4. Check error logs: `tail -f ~/logs/error_log`
5. Verify port 587 is not blocked

### Database Connection Failed
**Problem**: Can't connect to database

**Solution**:
1. Verify database credentials in `.env`
2. Check database exists in cPanel
3. Verify database user has privileges
4. Test connection: `mysql -u username -p database_name`
5. Check MySQL is running

### Files Changed on Server
**Problem**: Git shows modified files after deployment

**Solution**:
```bash
# Check what changed
git status

# If .env or other config files:
# These should NOT be in git, add to .gitignore

# Reset any accidental changes
git checkout -- filename

# Or discard all changes (careful!)
git reset --hard HEAD
```

---

## 📊 Environment Comparison

| Feature | Local | Staging | Production |
|---------|-------|---------|------------|
| Debug Mode | ✅ ON | ✅ ON | ❌ OFF |
| SMTP | ❌ Disabled | ✅ Real emails | ✅ Real emails |
| Database | Docker | Nexcess | Nexcess |
| URL | localhost:8082 | staging.* | cforkids.org |
| Error Display | ✅ Visible | ✅ Visible | ❌ Hidden (logged) |
| Git Branch | Any | Feature/test | Main (v1.0.3-rebuild) |

---

## 📚 Related Documentation

- **Main Deployment Guide**: `docs/deployment/deployment-guide.md`
- **Security Deployment**: `docs/deployment/SECURITY-DEPLOYMENT.md`
- **Git Deployment Setup**: `docs/deployment/git-deployment-setup-guide.md`
- **Environment Examples**: `.env.example`, `.env.staging.example`

---

## 🎯 Quick Reference

### Where to Put .env Files

```
🏠 LOCAL (Your Mac):
/Users/johncorbin/Desktop/projs/cfk/cfk-standalone/.env

🧪 STAGING (Nexcess Server):
/home/username/public_html/cfk-standalone/.env

🌐 PRODUCTION (Nexcess Server):
/home/username/public_html/cfk-standalone/.env
```

### Environment Detection Rules

The app automatically detects environment based on hostname:
- **staging**: Contains 'staging', 'stage', or '.test'
- **production**: Exactly 'cforkids.org' or 'www.cforkids.org'
- **local**: Everything else (localhost, *.local, with port numbers)

You can override by setting `ENVIRONMENT=` in `.env` file.

### Environment Badge Display

In admin area, you'll see a colored badge:
- 🟢 **PRODUCTION** (green)
- 🟡 **STAGING** (yellow)
- ⚫ **LOCAL** (gray)

---

**Last Updated**: 2025-10-24
**Version**: 1.8
