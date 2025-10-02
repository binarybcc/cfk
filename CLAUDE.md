# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin called "Christmas for Kids - Sponsorship System" that manages child sponsorship programs for the Christmas for Kids charity. The plugin handles child profiles, CSV imports, sponsorship tracking, and automated email communications.

## Architecture

The plugin follows a modular component-based architecture with these core components:

- **Main Plugin Class** (`ChristmasForKidsPlugin`): Singleton pattern initialization and component orchestration
- **Children Manager**: Custom post type management for child profiles
- **CSV Importer**: Bulk import functionality for child data
- **Sponsorship Manager**: Handles child selection, temporary reservations, and sponsor confirmations
- **Email Manager**: Automated email notifications for sponsors and admins
- **Admin Dashboard**: Administrative interface and reporting
- **Frontend Display**: Public-facing sponsorship interface

## Key Design Patterns

- **Modern PHP 8.2+ features**: Uses typed enums, readonly classes, match expressions, and constructor property promotion
- **Readonly DTOs**: Immutable data transfer objects for type safety (e.g., `CFK_ChildDetails`, `CFK_SponsorData`)
- **Enums for type safety**: Status enums (`CFK_Status`, `CFK_EmailType`, `CFK_DeliveryStatus`) prevent invalid states
- **Component initialization**: All components are loaded via the main plugin class using dependency injection
- **Error handling**: Comprehensive error logging and graceful degradation

## Database Schema

Two main tables:
- `wp_cfk_sponsorships`: Tracks sponsorship selections, confirmations, and cancellations
- `wp_cfk_email_log`: Logs all email communications for audit trail

## Development Workflow

Since this is a WordPress plugin:

1. **Testing**: Install in WordPress development environment
2. **Activation**: Plugin creates database tables and sets default options
3. **File Structure**: All PHP files use strict typing (`declare(strict_types=1);`)
4. **Security**: Proper nonce verification and capability checks throughout

## Plugin Entry Points

- **Admin Menu**: Located under "Christmas for Kids" in WordPress admin
- **AJAX Handlers**: Four main endpoints for child selection, confirmation, CSV import, and cancellation
- **Cron Jobs**: Hourly cleanup of abandoned child selections
- **Emergency Deactivation**: Available via URL parameter for debugging

## Configuration

Plugin settings stored in WordPress options table with `cfk_` prefix. Key settings include:
- Selection timeout (default 2 hours)
- Admin email notifications
- Email templates and sender information
- Sponsorship open/closed status

## File Organization

- `cfk_main_plugin.php`: Main plugin file and initialization
- `includes/`: Core functionality classes
- `admin/`: Administrative interface files

## Development Guidelines

### Claude Code Task Tool Usage
- Use the Task tool with `general-purpose` agents for complex multi-step operations
- Batch all related operations in single messages for optimal performance
- Provide detailed task descriptions for autonomous execution

### File Management Rules
- Never save working files to the root directory
- Organize documentation in appropriate subdirectories
- Use concurrent execution patterns when possible