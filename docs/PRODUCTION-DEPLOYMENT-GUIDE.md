# Christmas for Kids - Production Deployment Guide

## 🚀 **PHASE 4 COMPLETE**: Production-Ready Deployment

### System Overview
This standalone PHP application provides a dignified, maintainable Christmas child sponsorship system designed to run on Nexcess hosting without conflicts.

---

## **PRE-DEPLOYMENT CHECKLIST**

### ✅ **System Requirements**
- **PHP**: 8.2 or higher
- **MySQL**: 8.0 or higher  
- **Web Server**: Apache/Nginx with URL rewriting
- **Memory**: Minimum 256MB PHP memory limit
- **Storage**: 500MB minimum (for database and uploads)
- **Email**: SMTP access or working mail() function

### ✅ **Features Completed**
- ✅ **Avatar System**: 7-category age/gender-based silhouettes
- ✅ **Single-Sponsor Logic**: Race condition prevention
- ✅ **Admin Workflow**: Complete sponsorship processing
- ✅ **Email Notifications**: PHPMailer integration with templates
- ✅ **CSV Import/Export**: Comprehensive data management
- ✅ **Zeffy Integration**: Donation button on all key pages
- ✅ **Timeout Cleanup**: Automated expired sponsorship cleanup
- ✅ **Database Integrity**: Comprehensive error handling and validation

---

## **PRODUCTION DEPLOYMENT STEPS**

### 1. **File Upload and Configuration**

#### Upload Files
```bash
# Upload entire cfk-standalone/ directory to your web root
# Recommended path: /public_html/sponsor/ or /public_html/cfk/
```

#### Set File Permissions
```bash
chmod -R 755 /path/to/cfk-standalone/
chmod -R 777 /path/to/cfk-standalone/uploads/
chmod -R 777 /path/to/cfk-standalone/uploads/photos/
chmod 600 /path/to/cfk-standalone/config/config.php
```

### 2. **Database Setup**

#### Create Database and User
```sql
CREATE DATABASE cfk_sponsorship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cfk_user'@'localhost' IDENTIFIED BY 'SECURE_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON cfk_sponsorship.* TO 'cfk_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Import Database Schema
```bash
mysql -u cfk_user -p cfk_sponsorship < database/schema.sql
mysql -u cfk_user -p cfk_sponsorship < database/email_log_table.sql
```

### 3. **Configuration File Setup**

#### Edit `/config/config.php`:

```php
// Database Configuration (UPDATE THESE VALUES)
$dbConfig = [
    'host' => 'localhost',
    'database' => 'cfk_sponsorship',
    'username' => 'cfk_user',
    'password' => 'SECURE_PASSWORD_HERE'
];

// Application Settings (UPDATE THESE VALUES)
$appConfig = [
    'base_url' => 'https://www.cforkids.org/sponsor/',
    'admin_email' => 'admin@cforkids.org',
    'from_email' => 'noreply@cforkids.org',
    'from_name' => 'Christmas for Kids',
    'debug' => false, // IMPORTANT: Set to false in production
    
    // Email SMTP Configuration (OPTIONAL - uses mail() by default)
    'email_use_smtp' => false,
    'smtp_host' => 'mail.cforkids.org',
    'smtp_username' => 'noreply@cforkids.org',
    'smtp_password' => 'SMTP_PASSWORD_HERE',
    'smtp_port' => 587,
    'smtp_encryption' => 'tls'
];
```

### 4. **Create Admin Account**

Run this script once to create your admin account:

```php
<?php
// Create this as create_admin.php, run once, then delete
define('CFK_APP', true);
require_once 'config/config.php';

$username = 'admin';
$password = 'CHOOSE_SECURE_PASSWORD';
$email = 'admin@cforkids.org';

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

Database::insert('admin_users', [
    'username' => $username,
    'password_hash' => $hashedPassword,
    'email' => $email,
    'full_name' => 'Site Administrator',
    'role' => 'admin'
]);

echo "Admin account created successfully!\n";
echo "Username: $username\n";
echo "Password: $password\n";
echo "IMPORTANT: Delete this file immediately!\n";
?>
```

### 5. **Web Server Configuration**

#### For Apache (`.htaccess`):
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?page=$1 [QSA,L]

# Security headers
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Hide sensitive files
<Files "config.php">
    Require all denied
</Files>
<Files "*.sql">
    Require all denied
</Files>
```

#### For Nginx:
```nginx
location / {
    try_files $uri $uri/ /index.php?page=$uri&$args;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}

# Security
location ~ /config/ {
    deny all;
}
location ~ \.sql$ {
    deny all;
}
```

---

## **POST-DEPLOYMENT CONFIGURATION**

### 1. **Test System Functionality**

#### Basic System Test:
- Visit: `https://yoursite.com/sponsor/`
- Check: Homepage loads with proper styling
- Test: Children listing page
- Verify: Admin login at `/admin/`

#### Email System Test:
```php
// Run via admin panel or create test script:
require_once 'includes/email_manager.php';
$result = CFK_Email_Manager::testEmailConfig();
echo $result['message'];
```

### 2. **Set Up Automated Tasks**

#### Add Cron Job for Cleanup:
```bash
# Add to crontab (crontab -e):
0 * * * * /usr/bin/php /path/to/cfk-standalone/cron/cleanup_expired_sponsorships.php >> /var/log/cfk-cleanup.log 2>&1
```

### 3. **Import Initial Data**

#### Using Admin Interface:
1. Login to `/admin/`
2. Go to "Import CSV"
3. Download template
4. Upload your child data
5. Use "Preview" mode first to validate

#### CSV Format Requirements:
```csv
family_id,child_letter,age,gender,grade,shirt_size,pant_size,shoe_size,jacket_size,interests,greatest_need,wish_list,special_needs,family_situation
"001","A",8,"M","3rd","Boys 8","Boys 8","Youth 3","Boys 8","Soccer","Shoes","Soccer ball","None","Single parent"
```

---

## **SECURITY HARDENING**

### 1. **File Security**
- Ensure config files are not web-accessible
- Set restrictive file permissions
- Remove any development/test files

### 2. **Database Security**  
- Use strong database passwords
- Limit database user privileges
- Enable MySQL slow query log monitoring

### 3. **Application Security**
- Change default admin credentials immediately
- Enable HTTPS (SSL certificate required)
- Configure firewall to allow only necessary ports
- Regular security updates

### 4. **Backup Strategy**
```bash
# Database backup (run daily)
mysqldump -u cfk_user -p cfk_sponsorship > cfk_backup_$(date +%Y%m%d).sql

# File backup (run weekly)
tar -czf cfk_files_$(date +%Y%m%d).tar.gz /path/to/cfk-standalone/
```

---

## **MAINTENANCE & MONITORING**

### Daily Tasks:
- Check admin sponsorship requests
- Monitor error logs
- Verify email deliverability

### Weekly Tasks:
- Review database for orphaned records
- Check disk space usage
- Update child statuses as needed

### Monthly Tasks:
- Full system backup
- Security log review
- Performance optimization review

---

## **TROUBLESHOOTING COMMON ISSUES**

### Database Connection Issues:
```bash
# Check database connectivity
mysql -u cfk_user -p cfk_sponsorship -e "SELECT COUNT(*) FROM children;"
```

### Email Delivery Issues:
- Check SMTP credentials in config.php
- Verify mail server whitelist
- Check error logs for email-related errors

### Permission Issues:
```bash
# Fix file permissions
find /path/to/cfk-standalone -type f -exec chmod 644 {} \;
find /path/to/cfk-standalone -type d -exec chmod 755 {} \;
chmod -R 777 uploads/
```

### Performance Issues:
- Enable PHP opcache
- Optimize MySQL queries
- Consider CDN for static assets

---

## **SUPPORT & CONTACT**

### System Documentation:
- **Architecture**: See `/docs/IMPLEMENTATION-PLAN.md`
- **CSV Guide**: See `/templates/CSV-IMPORT-GUIDE.md`
- **Progress Notes**: See `/SESSION-PROGRESS-NOTES.md`

### Technical Support:
- All core functionality is complete and tested
- Email integration uses standard PHPMailer
- Database schema is thoroughly documented
- Admin interface is non-technical-user friendly

---

## **SUCCESS CRITERIA ✅**

### Functional Requirements:
- ✅ Non-coder can add/edit child information via web interface
- ✅ Visitors can easily browse and search children  
- ✅ Family relationships are clearly displayed
- ✅ Sponsorship process is smooth and respectful
- ✅ Zeffy donation integration works seamlessly
- ✅ Single-sponsor-per-child enforcement prevents conflicts
- ✅ Email notifications work for sponsors and admins

### Technical Requirements:
- ✅ System performs well on Nexcess hosting
- ✅ Code is documented and maintainable
- ✅ Avatar system protects privacy (no real photos)
- ✅ CSV import/export handles bulk operations
- ✅ Automated cleanup prevents data inconsistencies
- ✅ Comprehensive error handling and validation

---

**🎄 DEPLOYMENT STATUS: PRODUCTION READY**

The Christmas for Kids sponsorship system is complete and ready for production use. All Phase 4 features have been implemented and tested.