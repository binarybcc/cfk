# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 📦 Version & Engineering Standards

**Current Version:** v1.9.3
**Version Location:** `VERSION` file (root)
**Changelog:** `CHANGELOG.md` (root)

### Versioning Protocol (MANDATORY)

This project follows **Semantic Versioning (SemVer)**:
- PATCH (1.9.x): Bug fixes, typos
- MINOR (1.x.0): New features, backwards compatible
- MAJOR (x.0.0): Breaking changes

**After ANY code changes, Claude MUST:**
1. Update version in `VERSION` file
2. Update `CHANGELOG.md` with changes under appropriate section
3. Create git tag: `git tag -a vX.Y.Z -m "Release vX.Y.Z: description"`

### Commit Message Format (MANDATORY)

All commits use **Conventional Commits**:
```
<type>(<scope>): <description>

feat(sponsorship): add bulk confirmation feature
fix(email): correct template rendering
docs(guide): update admin documentation
refactor(db): optimize child queries
```

**Types:** feat, fix, docs, style, refactor, test, chore, perf, security

### Branch Strategy

- `main` / versioned branches (e.g., `v1.9.3`) - Stable releases
- `feature/*` - New features
- `fix/*` - Bug fixes

### Code Conventions

- **PHP:** Modern PHP 8.2+ with strict typing
- **Naming:** snake_case for variables/functions, PascalCase for classes
- **Files:** snake_case.php

---

# 📍 Current Project Status

**Active Branch:** v1.9.3 (Slim Framework migration complete)
**Status:** Production-ready, final validation needed
**See:** `cfk-standalone/CLAUDE.md` for branch-specific details

---

# Task Management

**Preferred approach:** Use TodoWrite tool for task tracking when working on multi-step tasks.

## Code Analysis & Research Tools

**PRIMARY: Serena MCP** (semantic code analysis - use FIRST!)
- `get_symbols_overview` - Understand file structure
- `find_symbol` - Find classes, methods, functions by name
- `find_referencing_symbols` - Find where code is used
- `search_for_pattern` - Semantic search with regex
- `read_file` - Read with line numbers and chunking

**Serena is MUCH more powerful than basic Grep/Read tools for code exploration!**

**Fallback Tools:**
- `Grep` - Basic text search (use only when Serena isn't appropriate)
- `Read` - Basic file reading (use only for non-code files)
- `WebSearch` - External documentation and research

## Project Overview

⚠️ **CRITICAL: This is a STANDALONE PHP APPLICATION, NOT a WordPress plugin!**

This is a web app called "Christmas for Kids - Sponsorship System" that manages child sponsorship programs for the Christmas for Kids charity. It's a pure PHP 8.2+ application that handles:

- Child profiles with avatar-based privacy system (no real photos)
- CSV import/export for bulk data management
- Time-limited reservation system for child selection
- Automated email notifications for sponsors and admins
- Complete sponsorship workflow from selection to confirmation
- Admin dashboard with comprehensive reporting

## Architecture

**Location**: All code is in `cfk-standalone/` directory

The application follows a modular component-based architecture:

### Core Components

- **Email Manager** (`includes/email_manager.php`): PHPMailer integration with fallback system
- **Reservation System** (`includes/reservation_functions.php`, `includes/reservation_emails.php`): Time-limited child selection management
- **Sponsorship Manager** (`includes/sponsorship_manager.php`): Complete sponsorship workflow
- **CSV Handler** (`includes/csv_handler.php`): Import/export with validation
- **Avatar Manager** (`includes/avatar_manager.php`): Privacy-focused avatar system (7 categories)
- **Report Manager** (`includes/report_manager.php`): Admin reporting and analytics
- **Archive Manager** (`includes/archive_manager.php`): Year-end archiving and reset

### File Structure

```
cfk-standalone/
├── index.php              # Main entry point (routing)
├── config/
│   ├── config.php         # Database and settings
│   └── database.php       # PDO connection
├── includes/              # Core functionality (see above)
├── pages/                 # Public-facing pages
│   ├── children.php       # Browse children
│   ├── child.php          # Individual profile
│   ├── selections.php     # Shopping cart
│   ├── reservation_review.php # Confirm reservation
│   ├── my_sponsorships.php    # Sponsor portal
│   └── sponsor_portal.php     # Access link entry
├── admin/                 # Admin interface
│   ├── index.php          # Dashboard
│   ├── manage_children.php    # Child management
│   ├── manage_sponsorships.php # Sponsorship processing
│   ├── import_csv.php     # Bulk import
│   └── reports.php        # Analytics
├── assets/
│   ├── css/styles.css     # Main styles
│   └── images/avatars/    # Avatar images
├── database/
│   └── schema.sql         # Database structure
└── cron/
    └── cleanup_reservations.php # Automated cleanup
```

## Key Design Patterns

- **Modern PHP 8.2+ features**: Typed properties, enums, match expressions, constructor property promotion
- **Component-based architecture**: Each major feature is a separate component
- **Session-based cart system**: Sponsors can select multiple children before confirming
- **Time-limited reservations**: 2-hour window to complete sponsorship
- **Automated cleanup**: Cron job releases expired reservations
- **Email queueing**: Reliable delivery with logging
- **Avatar privacy system**: 7-category age/gender-based avatars (no real photos)
- **PDO prepared statements**: SQL injection protection
- **CSRF protection**: Form token validation
- **Comprehensive error handling**: Graceful degradation with logging

## Database Schema

**Five main tables:**

- `families`: Family groupings (family_number: 175, 176, etc.)
- `children`: Individual child profiles with complete information
  - Demographics, clothing sizes, interests, wishes, special needs
  - Avatar category assignment (baby-boy, elementary-girl, etc.)
- `sponsorships`: Confirmed sponsorships (sponsor info, status tracking)
- `reservations`: Time-limited child selections (2-hour window)
- `email_log`: All automated email communications with delivery status

## Development Workflow

**This is NOT a WordPress plugin. It's a standalone application.**

### Environment Notation System 🏷️

To prevent confusion about which environment we're working in:

- 🏠 **LOCAL** = Your development machine (code editing only)
- 🧪 **STAGING** = https://10ce79bd48.nxcli.io/ (testing environment)
- 🌐 **PRODUCTION** = Live cforkids.org server

Always use these markers when working on files or running commands.

### Initial Setup (New Machine)

1. **Clone and Checkout**
   ```bash
   🏠 LOCAL:
   git clone https://github.com/binarybcc/cfk.git
   cd cfk/cfk-standalone
   git checkout v1.5-reservation-system
   ```

2. **Configure Environment Variables (CRITICAL!)**
   ```bash
   🏠 LOCAL:
   cp .env.example .env
   # Edit .env with your local settings
   chmod 600 .env

   # Verify .env is NOT tracked by git
   git status  # Should not show .env
   ```

3. **Testing Setup**
   - All testing done on staging environment: https://10ce79bd48.nxcli.io/
   - Deploy changes to staging using `/deploy-staging` command
   - Manual verification required for all changes

### Daily Development Workflow

1. **Before Starting Work**
   ```bash
   🏠 LOCAL:
   git pull origin v1.7.3-production-hardening
   ```

2. **During Development**
   - Make changes locally
   - Run PHPStan for static analysis
   - Deploy to staging for testing: `/deploy-staging`
   - Test on staging: https://10ce79bd48.nxcli.io/

3. **Before Committing**
   ```bash
   🏠 LOCAL:
   vendor/bin/phpstan analyse admin/ includes/ pages/ cron/ src/ --level 6
   git add -A
   git commit -m "description"
   git push origin [branch-name]
   ```

### Technical Details

1. **File Structure**: All PHP files use strict typing (`declare(strict_types=1);`)

2. **Database Access**: Uses PDO with prepared statements (no WordPress wpdb)

3. **Security**:
   - Environment variables for all secrets (.env files)
   - Session-based authentication
   - CSRF token validation
   - Input sanitization
   - SQL injection protection via PDO
   - Rate limiting (5 attempts, 15-min lockout)

4. **Configuration**:
   - Environment variables in `.env` files
   - Application settings in `config/config.php`
   - No WordPress options table

## Application Entry Points

### Public Pages (via `index.php` routing)
- `/` - Homepage
- `/children` - Browse children with search/filter
- `/child?id=123` - Individual child profile
- `/selections` - View shopping cart (selected children)
- `/reservation_review` - Confirm reservation
- `/my_sponsorships` - Sponsor portal (access via email link)

### Admin Interface
- `/admin/` - Dashboard with statistics
- `/admin/manage_children.php` - Add/edit/delete children
- `/admin/manage_sponsorships.php` - Process sponsorship requests
- `/admin/import_csv.php` - Bulk import children
- `/admin/reports.php` - Generate reports

### Automated Tasks
- Cron job: Hourly cleanup of expired reservations (`cron/cleanup_reservations.php`)
- Email queue processing (built into email_manager.php)

## Configuration

### ⚠️  CRITICAL: Environment Variables (.env Files)

**ALWAYS USE .env FILES FOR SENSITIVE DATA**

This project uses environment variables for all sensitive configuration. **NEVER hardcode credentials in source files.**

**Required Setup (Every Machine):**
1. Copy `.env.example` to `.env`
2. Configure your environment-specific values
3. Set secure permissions: `chmod 600 .env`
4. Verify `.env` is in `.gitignore` (NEVER commit!)

**Local Development (.env):**
```ini
DB_HOST=localhost                 # Local database host
DB_NAME=cfk_sponsorship_dev
DB_USER=cfk_user
DB_PASSWORD=cfk_pass              # Local dev password

SMTP_USERNAME=                    # Optional for local
SMTP_PASSWORD=

APP_DEBUG=true
BASE_URL=http://localhost:8082
```

**Production Server (.env):**
```ini
DB_HOST=localhost
DB_NAME=a4409d26_509946
DB_USER=a4409d26_509946
DB_PASSWORD=Fests42Cue50Fennel56Auk46

SMTP_USERNAME=your_smtp_user
SMTP_PASSWORD=your_smtp_pass

APP_DEBUG=false
BASE_URL=https://cforkids.org
```

**How It Works:**
- `config/config.php` loads `.env` file at startup
- Uses `getenv()` to read environment variables
- Falls back to safe defaults if not set
- Production credentials stay on server, never in git

**Security Checklist:**
- [ ] `.env` file exists with correct values
- [ ] `.env` has 600 permissions (owner read/write only)
- [ ] `.env` is listed in `.gitignore`
- [ ] No hardcoded credentials in `config/config.php`
- [ ] Production `.env` only exists on production server

**Files That Use Environment Variables:**
- `config/config.php` - Database connection, SMTP settings
- All includes that access database
- Email manager for SMTP credentials

### Application Settings

Additional settings in `config/config.php`:

- Email settings (SMTP or PHP mail())
- Reservation timeout (default 2 hours)
- Admin email addresses
- Base URL and paths
- Avatar system configuration
- Upload limits

## Current Active Branch & Status

**Branch**: `v1.5-reservation-system`
**Main branch**: `v1.0.3-rebuild`
**Status**: ✅ STABLE - Ready for use

**For detailed status, see:** `PROJECT-STATUS.md`

### Recent Major Work (Oct 13-14, 2025)
- ✅ Security audit complete (v1.5.1)
- ✅ Security fixes deployed (9.5/10 score)
- ✅ Logout functionality fixed
- ✅ Functional testing infrastructure (35/36 tests passing)
- ✅ Environment variable system (.env files)
- ✅ Comprehensive documentation

### Critical Files to Reference
- `PROJECT-STATUS.md` - Current project state and next steps
- `docs/audits/v1.5.1-functional-testing-report.md` - Testing results
- `docs/audits/v1.5.1-security-audit.md` - Security analysis
- `tests/security-functional-tests.sh` - Automated testing

## Documentation Location

**All project documentation is located in `cfk-standalone/docs/`**

### Documentation Structure (Organized by Category)

```
cfk-standalone/docs/
├── README.md              # Main documentation index
├── features/              # Feature implementation docs (7 files)
│   ├── zeffy-donation-modal.md
│   ├── donation-page.md
│   └── ...
├── components/            # Component documentation (7 files)
│   ├── button-system.md
│   ├── email-system.md
│   └── ...
├── guides/                # User guides (6 files)
│   ├── admin-guide.md
│   ├── csv-import-guide.md
│   └── ...
├── technical/             # Technical specifications (5 files)
│   ├── alpine-js-patterns.md
│   ├── php-82-compliance.md
│   └── ...
├── audits/                # Audit reports (6 files)
├── deployment/            # Deployment guides (5 files)
├── releases/              # Release notes (6 files)
└── archive/               # Historical docs (1 file)
```

### Quick Access

- **For feature implementation details**: `cfk-standalone/docs/features/`
- **For component API reference**: `cfk-standalone/docs/components/`
- **For user guides**: `cfk-standalone/docs/guides/`
- **For technical specs**: `cfk-standalone/docs/technical/`
- **For deployment**: `cfk-standalone/docs/deployment/`

### When to Reference Documentation

1. **Understanding a feature**: Check `features/` directory
2. **Using a component**: Check `components/` directory
3. **Deploying changes**: Check `deployment/` directory
4. **Learning the architecture**: Check `technical/` directory

## Development Guidelines

### Slim Framework Template Architecture (v1.9+)

**CRITICAL: ALL Slim templates MUST use modular component pattern.**

**Full Documentation:** `cfk-standalone/docs/technical/slim-template-architecture.md`

**Quick Reference:**
- ✅ Header/footer in `templates/components/` (reusable)
- ✅ Layouts in `templates/layouts/` (use `{% include %}`)
- ✅ Feature templates extend layouts (use `{% extends %}`)
- ❌ NEVER duplicate header/footer code
- ❌ NEVER inline header/footer in feature templates

**Single Source of Truth:** Update once, applies everywhere.

**This is the professional standard for template systems. No exceptions.**

### Working with This Codebase

1. **Remember**: This is NOT WordPress. No hooks, no shortcodes, no WP functions.
2. **Testing**: Always test in `cfk-standalone/` directory
3. **Database**: Use PDO, not wpdb
4. **Sessions**: PHP sessions, not WordPress user system
5. **Email**: PHPMailer with custom wrapper, not wp_mail()
6. **Documentation**: All docs organized in `cfk-standalone/docs/` by category

### Claude Code Task Tool Usage

- Use the Task tool with `general-purpose` agents for complex multi-step operations
- Batch all related operations in single messages for optimal performance
- Provide detailed task descriptions for autonomous execution

### File Management Rules

- Never save working files to the root directory
- Organize documentation in appropriate subdirectories (`cfk-standalone/docs/`)
- Use concurrent execution patterns when possible
- All development happens in `cfk-standalone/` directory
