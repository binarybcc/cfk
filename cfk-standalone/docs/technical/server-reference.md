# CFK Production Server - Quick Reference

**Created:** 2025-10-12
**Purpose:** Essential server info for deployments

---

## 🔐 SSH Access

```bash
# Direct SSH
sshpass -p "PiggedCoifSourerFating" ssh -o StrictHostKeyChecking=no a4409d26_1@d646a74eb9.nxcli.io

# Quiet mode (no banners)
sshpass -p "PiggedCoifSourerFating" ssh -o StrictHostKeyChecking=no -o LogLevel=QUIET a4409d26_1@d646a74eb9.nxcli.io

# Upload file
sshpass -p "PiggedCoifSourerFating" scp -o StrictHostKeyChecking=no <file> a4409d26_1@d646a74eb9.nxcli.io:~/
```

**Credentials:**
- Host: `d646a74eb9.nxcli.io`
- User: `a4409d26_1`
- Pass: `PiggedCoifSourerFating`
- Port: `22`

---

## 📂 File System

```bash
Web Root:      ~/d646a74eb9.nxcli.io/html/
Backups:       ~/backups/
Home:          ~/
Logs:          ~/logs/error_log
```

**CFK App Structure:**
```
~/d646a74eb9.nxcli.io/html/
├── admin/           # Admin panel
├── assets/          # Images, CSS, JS
├── config/          # Configuration files ⚠️
├── database/        # DB setup scripts
├── includes/        # PHP includes, functions
├── pages/           # Page templates
├── index.php        # Main entry point
└── .htaccess        # Apache config
```

---

## 🌐 URLs & Configuration

**⚠️ CRITICAL: Site is at ROOT, NOT /sponsor/**

```bash
# Correct URLs
https://cforkids.org/
https://cforkids.org/?page=children
https://cforkids.org/?page=how_to_apply
https://cforkids.org/admin/

# WRONG (do not use)
https://cforkids.org/sponsor/  ❌
```

**Config Files to Check:**
- `config/config.php` line 36
- `config/config.production.php` line 33

Both must have:
```php
'base_url' => 'https://cforkids.org/',  // NO /sponsor/
```

---

## 🗄️ Database

```bash
# Connection Details
DB_HOST: localhost (default)
DB_USER: a4409d26_509946
DB_PASS: Fests42Cue50Fennel56Auk46
DB_NAME: a4409d26_509946

# MySQL CLI
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946

# Common Queries
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 -e "SHOW TABLES;"
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 -e "DESCRIBE families;"

# Backup Database
mysqldump -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 > backup.sql
```

---

## 🚀 Common Tasks

### Deploy New Files
```bash
# 1. Create package locally
tar -czf cfk-deploy.tar.gz admin/ includes/ pages/ assets/ database/ config/ index.php

# 2. Upload
sshpass -p "PiggedCoifSourerFating" scp -o StrictHostKeyChecking=no cfk-deploy.tar.gz a4409d26_1@d646a74eb9.nxcli.io:~/

# 3. Extract on server
ssh a4409d26_1@d646a74eb9.nxcli.io
cd ~/d646a74eb9.nxcli.io/html/
tar -xzf ~/cfk-deploy.tar.gz
```

### Backup Before Changes
```bash
# Files
cd ~/d646a74eb9.nxcli.io/html/
tar -czf ~/backups/cfk-backup-$(date +%Y%m%d_%H%M%S).tar.gz admin/ includes/ pages/

# Database
mysqldump -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 > ~/backups/db-$(date +%Y%m%d_%H%M%S).sql
```

### Check Logs
```bash
# Error log (last 50 lines)
tail -50 ~/logs/error_log

# Apache/PHP errors
tail -50 ~/d646a74eb9.nxcli.io/html/logs/error_log
```

### Verify Deployment
```bash
# Check Alpine.js
grep -c "alpinejs@3.14.1" ~/d646a74eb9.nxcli.io/html/includes/header.php

# Check config base_url
grep "base_url" ~/d646a74eb9.nxcli.io/html/config/config.php

# Check permissions
ls -la ~/d646a74eb9.nxcli.io/html/ | head -20
```

---

## 🔧 Troubleshooting

### Site redirecting to /sponsor/
```bash
# Check and fix config files
cd ~/d646a74eb9.nxcli.io/html/config
grep "base_url" config.php config.production.php

# Should show: 'base_url' => 'https://cforkids.org/'
# If shows /sponsor/, fix with:
sed -i "s|/sponsor/||g" config.php
sed -i "s|/sponsor/||g" config.production.php
```

### Alpine.js not working
```bash
# Verify files deployed
grep -c "x-data" ~/d646a74eb9.nxcli.io/html/pages/children.php
# Should return: 1 or more

# Check header
grep "alpine" ~/d646a74eb9.nxcli.io/html/includes/header.php
# Should show: alpinejs@3.14.1
```

### Database issues
```bash
# Check connection
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 -e "SELECT 1;"

# Check tables exist
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 -e "SHOW TABLES;"

# Verify privacy compliance (family_name should NOT exist)
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 -e "DESCRIBE families;"
```

---

## 📋 Pre-Deployment Checklist

- [ ] Backup current files
- [ ] Backup database
- [ ] Test package locally (extract and verify)
- [ ] Upload package to server
- [ ] Extract in web root
- [ ] Verify config files (NO /sponsor/)
- [ ] Check file permissions (644 files, 755 dirs)
- [ ] Test site in browser
- [ ] Verify Alpine.js loaded (console: Alpine.version)
- [ ] Test all interactive features

---

## 🆘 Emergency Rollback

```bash
# Restore from backup
cd ~/d646a74eb9.nxcli.io/html/

# Find latest backup
ls -lt ~/backups/

# Restore files
rm -rf admin/ includes/ pages/ assets/ config/
tar -xzf ~/backups/cfk-backup-YYYYMMDD_HHMMSS.tar.gz

# Restore database
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 < ~/backups/db-YYYYMMDD_HHMMSS.sql
```

---

**Last Updated:** 2025-10-12
**Version:** v1.4
**Maintainer:** Christmas for Kids Development Team
