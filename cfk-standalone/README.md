# Christmas for Kids - Standalone Sponsorship System

A dignified, maintainable PHP application for managing child sponsorship programs. Built to replace WooCommerce-based systems with a respectful, user-focused approach.

## 🎯 Project Goals

- **Respectful Approach**: Children are individuals seeking support, not products
- **Gift-Delivery Model**: Sponsors buy and deliver physical gifts, not monetary donations
- **Non-Coder Maintainable**: Simple PHP with clear documentation
- **Visitor-Focused UX**: Easy browsing, search, and sponsorship process
- **Complete Shopping Info**: Sponsors receive detailed email with sizes, wishes, interests
- **Nexcess Compatible**: Pure PHP without framework conflicts

## 🚀 Quick Start

### Prerequisites

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx)
- Basic file upload permissions

### Installation

1. **Upload Files**
   ```bash
   # Upload the entire cfk-standalone folder to your web server
   # Example: /public_html/sponsor/cfk-standalone/
   ```

2. **Create Database**
   ```sql
   CREATE DATABASE cfk_sponsorship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import Database Schema**
   ```bash
   mysql -u your_username -p cfk_sponsorship < database/schema.sql
   ```

4. **Configure Database Connection**
   Edit `config/config.php`:
   ```php
   $dbConfig = [
       'host' => 'localhost',
       'database' => 'cfk_sponsorship',
       'username' => 'your_db_user',
       'password' => 'your_db_password'
   ];
   ```

5. **Set File Permissions**
   ```bash
   chmod 755 cfk-standalone/
   chmod -R 755 uploads/
   chmod 644 config/*.php
   ```

6. **Access the System**
   - Public site: `https://yoursite.com/sponsor/`
   - Admin panel: `https://yoursite.com/sponsor/admin/`
   - Default login: `admin` / `admin123` (CHANGE THIS!)

## 📁 File Structure

```
cfk-standalone/
├── index.php              # Main entry point
├── config/
│   ├── config.php         # Main configuration
│   └── database.php       # Database connection
├── includes/
│   ├── functions.php      # Helper functions
│   ├── header.php         # Site header
│   └── footer.php         # Site footer
├── pages/
│   ├── home.php           # Homepage
│   ├── children.php       # Children listing
│   ├── child.php          # Individual child profile
│   └── sponsor.php        # Sponsorship form
├── admin/
│   ├── index.php          # Admin dashboard
│   ├── login.php          # Admin login
│   ├── manage_children.php # Child management
│   └── includes/          # Admin templates
├── assets/
│   ├── css/styles.css     # Main stylesheet
│   └── js/main.js         # JavaScript
├── database/
│   └── schema.sql         # Database structure
└── uploads/
    └── photos/            # Child photos
```

## 👥 User Roles & Access

### Public Visitors (Sponsors)
- Browse children profiles with photos
- Search and filter children by age, interests, wishes
- Submit sponsorship requests (individuals or entire families)
- Receive detailed shopping email with:
  - Complete child information (name, age, grade, gender)
  - All clothing sizes (shirt, pants, shoes, jacket)
  - Interests and hobbies (full text)
  - Christmas wishlist items (full text)
  - Special needs or notes
- Access sponsor portal to view sponsorships and add more children

### Admin Users
- Confirm sponsorship requests (triggers automated detailed email to sponsor)
- Manage children and families
- Upload photos and update information
- View comprehensive reports:
  - Sponsor directory (who sponsored which children)
  - Gift delivery tracking
  - Family sponsorship status
  - Available children
- Import/export data via CSV
- Perform year-end reset with automatic archiving

## 🔧 Configuration

### Essential Settings (config/config.php)

```php
// Database
$dbConfig = [
    'host' => 'localhost',
    'database' => 'cfk_sponsorship',
    'username' => 'your_username',
    'password' => 'your_password'
];

// Site URLs
'base_url' => 'https://yoursite.com/sponsor/',

// Email Settings
'admin_email' => 'admin@yoursite.com',

// Upload Settings
'photo_max_width' => 800,
'photo_max_height' => 600,
'max_file_size' => 5 * 1024 * 1024, // 5MB
```

### Security Settings

1. **Change Default Admin Password**
   - Login to admin panel
   - Go to user management
   - Update password immediately

2. **Set Strong Database Credentials**
   - Create dedicated database user
   - Use strong password
   - Limit permissions to necessary operations

3. **File Permissions**
   - Config files: 644
   - Upload directories: 755
   - PHP files: 644

## 📊 Database Schema

### Core Tables

- **families**: Family groupings (family_number: 175, 176, etc.)
- **children**: Individual child profiles with complete information
  - Demographics (name, age, grade, gender)
  - Clothing sizes (shirt, pants, shoes, jacket)
  - Interests, hobbies, Christmas wishes
  - Special needs
- **sponsorships**: Sponsorship requests and tracking
  - Status: pending, confirmed, completed, cancelled
  - Sponsor contact information
  - Gift preferences
- **email_log**: All automated email communications logged
- **admin_users**: Admin access management

### Key Relationships

- Children belong to families (siblings grouped together)
- Sponsorships link children to sponsors (one sponsor per child)
- Email log tracks all sponsor communications
- All data maintains referential integrity

## 🎨 Customization

### Styling
- Main CSS: `assets/css/styles.css`
- Color scheme based on Christmas green theme
- Fully responsive design
- Print-friendly styles included

### Templates
- Header: `includes/header.php`
- Footer: `includes/footer.php`
- Admin header: `admin/includes/admin_header.php`

### Sponsorship Workflow

**How It Works**:
1. Visitor browses available children on public site
2. Sponsor selects child (or entire family) and submits request form
3. Admin confirms sponsorship request in admin panel
4. System automatically sends detailed email to sponsor with:
   - All child information needed for shopping
   - Clothing sizes in easy-to-read format
   - Interests and Christmas wishes
   - Delivery instructions
5. Sponsor purchases gifts and delivers unwrapped to CFK
6. Admin marks sponsorship complete when gifts delivered

**Sponsor Portal**:
- Sponsors can access portal with email address
- View all their current sponsorships
- Add more children to existing sponsorship
- Receive updated comprehensive email with all children

## 🔍 Search & Filtering

### Public Features
- Text search across names, interests, wishes
- Age category filtering (Birth-4, Elementary, etc.)
- Gender filtering
- Family grouping display

### Admin Features
- Advanced filtering by status
- Bulk operations
- CSV export capabilities
- Statistical reporting

## 📈 Monitoring & Maintenance

### Regular Tasks
1. **Daily** (during season): Review and confirm pending sponsorships
2. **Weekly**: Export data backups, generate reports
3. **Monthly**: Update child photos, verify information accuracy
4. **End of Season**: Mark all sponsorships complete
5. **Annual**: Perform year-end reset with automatic archiving

### Logs & Monitoring
- All automated emails logged (email_log table)
- Failed login attempts logged
- Database errors logged to PHP error log
- Admin actions tracked
- Sponsorship status changes recorded

### Reports Available
- Dashboard statistics (real-time overview)
- Sponsor directory (all sponsors and their children)
- Child-sponsor lookup (find who sponsored specific child)
- Family sponsorship report (family-level status)
- Gift delivery tracking (confirmed sponsorships awaiting delivery)
- Available children (filterable by age, gender, family)

## 🆘 Troubleshooting

### Common Issues

**1. Database Connection Failed**
- Check config/config.php settings
- Verify database server is running
- Confirm user permissions

**2. Photos Not Uploading**
- Check uploads/ directory permissions (755)
- Verify PHP file upload settings
- Check file size limits

**3. Admin Login Not Working**
- Verify default credentials: admin/admin123
- Check database admin_users table
- Clear browser cache/cookies

**4. Search Not Working**
- Check MySQL full-text search setup
- Verify database charset (utf8mb4)
- Check for special characters in search

### Support Files
- Error logs: Check your server's PHP error log
- Database: Use provided schema.sql for reference
- Backups: Regular database dumps recommended

## 🔐 Security Best Practices

1. **Immediately change default admin password**
2. **Keep PHP and MySQL updated**
3. **Use HTTPS for all admin access**
4. **Regular database backups**
5. **Monitor failed login attempts**
6. **Restrict admin panel IP access if possible**

## 📚 Documentation

### For Administrators
- **ADMIN-GUIDE.md**: Complete admin workflow, email system, reports, year-end reset
- **CSV-IMPORT-GUIDE.md**: Format and process for importing child data
- **EMAIL-DEPLOYMENT-GUIDE.md**: Email configuration and troubleshooting

### For Sponsors
- **SPONSOR-WORKFLOW.md**: Complete sponsor experience from selection to delivery
- **Public Site**: Browse and select children, access sponsor portal

### For Developers
- **README.md**: This file - installation, configuration, architecture
- **IMPLEMENTATION-PLAN.md**: Original development planning
- Code follows modern PHP 8.2+ standards
- Uses PDO for database operations with prepared statements
- Comprehensive error handling and logging
- Fully commented codebase

## 📞 Getting Help

### For Non-Technical Users
- Review **ADMIN-GUIDE.md** for complete admin workflows
- Use the admin dashboard for daily operations
- CSV import for bulk child additions (see CSV-IMPORT-GUIDE.md)
- Contact your developer for configuration changes

### For Sponsors
- Review **SPONSOR-WORKFLOW.md** for complete process
- Use "My Sponsorships" portal to view your sponsorships
- Contact CFK administration for questions

### For Developers
- Check PHP error log for server issues
- Review database error messages in admin panel
- All code documented with inline comments
- Database schema in database/schema.sql

---

**Version**: 1.0.3
**Last Updated**: October 2025
**Minimum PHP Version**: 8.2
**License**: Custom - For Christmas for Kids use only

*Built with dignity, respect, and the goal of making every child's Christmas magical.* 🎄