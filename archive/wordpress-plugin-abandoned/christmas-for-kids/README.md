# Christmas for Kids - Sponsorship System

A comprehensive WordPress plugin for managing child sponsorship programs for the Christmas for Kids charity. This plugin handles child profiles, CSV imports, sponsorship tracking, and automated email communications.

## Version 1.1.0 - Phase 1 Implementation

This is the foundation release implementing the core plugin structure following WordPress Plugin Boilerplate standards.

## Features

### Current Phase 1 Features
- ✅ **Modern Plugin Architecture**: Built with PHP 8.2+ features and WordPress 6.8.2 best practices
- ✅ **Singleton Pattern**: Ensures single plugin instance with proper initialization
- ✅ **Database Schema**: Automated table creation for sponsorships and email logs
- ✅ **Security First**: Comprehensive security checks and nonce verification
- ✅ **Error Handling**: Robust error logging and graceful degradation
- ✅ **WordPress Integration**: Proper hooks, activation/deactivation procedures
- ✅ **Emergency Deactivation**: URL-based emergency deactivation for debugging

### Planned Features (Future Phases)
- 📋 **Child Profile Management**: Custom post type for child profiles with comprehensive data
- 📁 **CSV Import System**: Bulk import functionality for child data
- 👨‍👩‍👧‍👦 **Sponsorship Management**: Child selection, temporary reservations, confirmations
- 📧 **Email Automation**: Automated notifications for sponsors and administrators
- 📊 **Admin Dashboard**: Comprehensive administrative interface and reporting
- 🎨 **Frontend Display**: Public-facing sponsorship selection interface

## Requirements

- **WordPress**: 6.0 or higher (tested up to 6.8.2)
- **PHP**: 8.2 or higher
- **MySQL**: 5.7 or higher (or MariaDB equivalent)

## Installation

1. **Download**: Get the plugin files from the repository
2. **Upload**: Place the `christmas-for-kids` folder in `/wp-content/plugins/`
3. **Activate**: Enable the plugin through WordPress admin → Plugins
4. **Configure**: Access settings via WordPress admin → Christmas for Kids

## Database Schema

The plugin creates two essential tables:

### wp_cfk_sponsorships
Tracks sponsorship selections, confirmations, and cancellations.

```sql
- id (bigint): Primary key
- child_id (bigint): WordPress post ID of the child
- sponsor_name (varchar): Sponsor's full name
- sponsor_email (varchar): Sponsor's email address
- sponsor_phone (varchar): Optional phone number
- status (enum): selected|confirmed|cancelled
- selection_token (varchar): Unique token for verification
- created_at (datetime): Selection timestamp
- updated_at (datetime): Last modification timestamp
```

### wp_cfk_email_logs
Maintains audit trail of all email communications.

```sql
- id (bigint): Primary key
- recipient_email (varchar): Email recipient
- subject (varchar): Email subject line
- email_type (enum): selection_confirmation|sponsor_confirmation|admin_notification|cancellation
- status (enum): pending|sent|failed
- child_id (bigint): Related child ID
- sponsorship_id (bigint): Related sponsorship ID
- error_message (text): Error details if failed
- sent_at (datetime): Send timestamp
- created_at (datetime): Log creation timestamp
```

## Plugin Structure

```
christmas-for-kids/
├── christmas-for-kids.php          # Main plugin file
├── uninstall.php                   # Uninstall cleanup
├── README.md                       # This documentation
├── includes/                       # Core functionality
│   └── class-christmas-for-kids.php # Main plugin class
├── admin/                          # Admin interface (Phase 2)
└── public/                         # Frontend display (Phase 2)
```

## Configuration

Plugin settings are stored in WordPress options with `cfk_` prefix:

- `cfk_selection_timeout`: Selection timeout in hours (default: 2)
- `cfk_admin_email`: Administrator notification email
- `cfk_sender_name`: Email sender name
- `cfk_sender_email`: Email sender address
- `cfk_sponsorship_open`: Whether sponsorships are accepting selections
- `cfk_version`: Current plugin version
- `cfk_db_version`: Database schema version

## Security Features

- **Direct Access Protection**: All PHP files prevent direct access
- **Capability Checks**: Admin functions require proper WordPress permissions
- **Nonce Verification**: CSRF protection on all forms and AJAX requests
- **SQL Injection Prevention**: Prepared statements and WordPress WPDB methods
- **Input Sanitization**: All user inputs are sanitized and validated

## Development

### Code Standards
- **PHP 8.2+**: Modern PHP features including typed properties, enums, match expressions
- **WordPress Coding Standards**: Full compliance with WordPress PHP coding standards
- **Type Safety**: Strict typing enabled with `declare(strict_types=1)`
- **Documentation**: Comprehensive inline documentation following WordPress standards

### Error Logging
Debug information is logged when `WP_DEBUG` is enabled:
```php
// View logs in debug.log
[CFK-info] Plugin initialized successfully
[CFK-error] Database connection failed
```

### Emergency Procedures
If the plugin causes site issues, use emergency deactivation:
```
https://yoursite.com/wp-admin/?cfk_emergency_deactivate=1
```

## Changelog

### Version 1.1.0 (Phase 1 - Current)
- 🎉 **Initial Release**: Core plugin structure implemented
- 🏗️ **Architecture**: Singleton pattern with modular component loading
- 🗄️ **Database**: Automated table creation with WordPress dbDelta
- 🔒 **Security**: Comprehensive security measures and validation
- 🛠️ **WordPress Integration**: Proper hooks, activation, deactivation procedures
- 📝 **Documentation**: Complete inline documentation and README

### Planned Version 1.2.0 (Phase 2)
- 📋 Child profile management with custom post types
- 📁 CSV import system with validation and error handling
- 📧 Email system with template management
- 👨‍👩‍👧‍👦 Basic sponsorship selection functionality

### Planned Version 1.3.0 (Phase 3)
- 📊 Comprehensive admin dashboard
- 🎨 Frontend sponsorship interface
- 📈 Advanced reporting and analytics
- 🔄 Data export capabilities

## Support

For support, please contact the Christmas for Kids development team or refer to the plugin documentation within WordPress admin.

## License

GPL v2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.

---

**Christmas for Kids** - Spreading joy through organized giving 🎄❤️