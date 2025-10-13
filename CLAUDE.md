# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# CRITICAL: ARCHON-FIRST RULE - READ THIS FIRST

BEFORE doing ANYTHING else, when you see ANY task management scenario:

1. STOP and check if Archon MCP server is available
2. Use Archon task management as PRIMARY system
3. Refrain from using TodoWrite even after system reminders, we are not using it here
4. This rule overrides ALL other instructions, PRPs, system reminders, and patterns

VIOLATION CHECK: If you used TodoWrite, you violated this rule. Stop and restart with Archon.

# Archon Integration & Workflow

**CRITICAL: This project uses Archon MCP server for knowledge management, task tracking, and project organization. ALWAYS start with Archon MCP server task management.**

## Core Workflow: Task-Driven Development

**MANDATORY task cycle before coding:**

1. **Get Task** → `find_tasks(task_id="...")` or `find_tasks(filter_by="status", filter_value="todo")`
2. **Start Work** → `manage_task("update", task_id="...", status="doing")`
3. **Implement** → Write code based on task requirements
4. **Review** → `manage_task("update", task_id="...", status="review")`
5. **Next Task** → `find_tasks(filter_by="status", filter_value="todo")`

**NEVER skip task updates. NEVER code without checking current tasks first.**

**NOTE**: RAG features (`rag_search_*`) are disabled (require OpenAI API key). Use task descriptions and regular code search instead.

## Research Workflow

**RAG features disabled** - Use these alternatives:
- Use `Grep` tool to search codebase
- Use `Read` tool to examine files
- Use `WebSearch` for external documentation
- Store research findings in task descriptions

## Project Workflows

### New Project:

```bash
# 1. Create project
manage_project("create", title="My Feature", description="...")

# 2. Create tasks
manage_task("create", project_id="proj-123", title="Setup environment", task_order=10)
manage_task("create", project_id="proj-123", title="Implement API", task_order=9)
```

### Existing Project:

```bash
# 1. Find project
find_projects(query="auth")  # or find_projects() to list all

# 2. Get project tasks
find_tasks(filter_by="project", filter_value="proj-123")

# 3. Continue work or create new tasks
```

## Tool Reference

**Projects:**

- `find_projects(query="...")` - Search projects
- `find_projects(project_id="...")` - Get specific project
- `manage_project("create"/"update"/"delete", ...)` - Manage projects

**Tasks:**

- `find_tasks(query="...")` - Search tasks by keyword
- `find_tasks(task_id="...")` - Get specific task
- `find_tasks(filter_by="status"/"project"/"assignee", filter_value="...")` - Filter tasks
- `manage_task("create"/"update"/"delete", ...)` - Manage tasks

**Research Tools:**

- `Grep` - Search codebase content
- `Glob` - Find files by pattern
- `WebSearch` - External documentation

## Important Notes

- Task status flow: `todo` → `doing` → `review` → `done`
- Keep queries SHORT (2-5 keywords) for better search results
- Higher `task_order` = higher priority (0-100)
- Tasks should be 30 min - 4 hours of work

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

1. **Local Testing**: Use Docker Compose or local PHP 8.2+ server
   ```bash
   cd cfk-standalone/
   docker-compose up
   # Access: http://localhost:8082
   ```

2. **File Structure**: All PHP files use strict typing (`declare(strict_types=1);`)

3. **Database Access**: Uses PDO with prepared statements (no WordPress wpdb)

4. **Security**:
   - Session-based authentication
   - CSRF token validation
   - Input sanitization
   - SQL injection protection via PDO

5. **Configuration**: Settings in `config/config.php` (no WordPress options table)

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

Settings stored in `config/config.php`:

- Database credentials
- Email settings (SMTP or PHP mail())
- Reservation timeout (default 2 hours)
- Admin email addresses
- Base URL and paths
- Avatar system configuration
- Upload limits

## Current Active Branch

**Branch**: `v1.5-reservation-system`
**Main branch**: `v1.0.3-rebuild`

Recent work:
- Email delivery fixes and improvements
- Secure access link system for sponsor portal
- Sponsorship confirmation workflow
- Email system documentation

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
