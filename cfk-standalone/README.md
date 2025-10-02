# Christmas for Kids - Standalone Sponsorship System

A dignified, maintainable PHP application for managing child sponsorship programs. Built to replace WooCommerce-based systems with a respectful, user-focused approach.

## 🎯 Project Goals

- **Respectful Approach**: Children are individuals seeking support, not products
- **Non-Coder Maintainable**: Simple PHP with clear documentation
- **Visitor-Focused UX**: Easy browsing, search, and sponsorship process
- **Nexcess Compatible**: Pure PHP without framework conflicts
- **Zeffy Integration**: Built-in donation system integration

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

### Public Visitors
- Browse children profiles
- Search and filter children
- Submit sponsorship requests
- Make donations via Zeffy

### Admin Users
- Manage children and families
- Process sponsorship requests
- Upload photos
- View reports and statistics
- Import data via CSV

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

- **families**: Family groupings (175A, 175B, etc.)
- **children**: Individual child profiles
- **sponsorships**: Sponsorship requests and tracking  
- **admin_users**: Admin access management
- **settings**: Configuration options

### Key Relationships

- Children belong to families (siblings grouped together)
- Sponsorships link children to sponsors
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

### Zeffy Integration
Donation buttons are integrated throughout the site using:
```html
<button zeffy-form-link="https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true">
    Donate Now
</button>
```

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
1. **Weekly**: Review pending sponsorships
2. **Monthly**: Export data backups
3. **Seasonal**: Update child photos and information
4. **Annual**: Archive completed sponsorships

### Logs & Monitoring
- Failed login attempts logged
- Database errors logged to PHP error log
- Admin actions tracked

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

## 📞 Getting Help

### For Non-Technical Users
- Focus on the admin dashboard
- Use the "Quick Actions" for common tasks
- CSV import for bulk child additions
- Contact your developer for configuration changes

### For Developers
- Code follows modern PHP 8.2+ standards
- Uses PDO for database operations
- Comprehensive error handling
- Fully commented codebase

---

**Version**: 1.0.0  
**Last Updated**: 2025  
**Minimum PHP Version**: 8.2  
**License**: Custom - For Christmas for Kids use only

*Built with dignity, respect, and the goal of making every child's Christmas magical.* 🎄