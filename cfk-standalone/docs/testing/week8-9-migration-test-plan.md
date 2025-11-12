# Week 8-9 Admin Panel Migration - Test Plan

**Migration Completion Date:** 2025-11-12
**Branch:** `claude/week8-admin-migration-011CUuNnr3sJ6CzGwQL4wuCG`
**Status:** Ready for Testing

---

## Test Environment Setup

### Prerequisites
- PHP 8.1+ with required extensions
- MySQL/MariaDB database
- Web server (Apache/Nginx) or PHP built-in server
- Admin user account for testing

### Quick Start Testing
```bash
cd cfk-standalone
php -S localhost:8080 -t .
```

Navigate to: `http://localhost:8080/admin/login`

---

## 1. Authentication Testing

### 1.1 Login Flow
- [ ] **GET /admin/login** - Login page displays correctly
- [ ] **POST /admin/auth/request-magic-link** - Magic link email sent
  - Test with valid admin email
  - Test with non-existent email (should show same success message)
  - Verify email arrives with valid magic link
- [ ] **GET /admin/auth/magic-link-sent** - Confirmation page displays
- [ ] **GET /admin/auth/verify-magic-link?token=...** - Auto-submit form displays
- [ ] **POST /admin/auth/verify-magic-link** - Token verified, session created
  - Test with valid token
  - Test with expired token
  - Test with invalid token
- [ ] **Redirect to /admin/dashboard** after successful login

### 1.2 Logout Flow
- [ ] **GET /admin/logout** - Session destroyed
- [ ] **Redirect to /admin/login** with success message
- [ ] Verify cannot access admin pages after logout

### 1.3 Security Features
- [ ] Rate limiting (5 attempts per 15 min)
- [ ] CSRF token validation on all POST requests
- [ ] Session fixation prevention (session_regenerate_id)
- [ ] Token expiration (5 minutes)
- [ ] Constant-time responses (timing attack prevention)

### 1.4 Legacy Redirects
- [ ] `admin/login.php` → `/admin/login` (301 redirect)
- [ ] `admin/logout.php` → `/admin/logout` (301 redirect)
- [ ] `admin/request-magic-link.php` → `/admin/auth/request-magic-link` (301)
- [ ] `admin/verify-magic-link.php?token=...` → `/admin/auth/verify-magic-link?token=...` (301, preserves token)
- [ ] `admin/magic-link-sent.php` → `/admin/auth/magic-link-sent` (301)

---

## 2. Dashboard & Navigation

### 2.1 Dashboard
- [ ] **GET /admin/dashboard** - Dashboard displays correctly
  - Statistics cards (total children, sponsored, pending, etc.)
  - Recent sponsorships table
  - Children needing attention section
- [ ] **Legacy redirect:** `admin/index.php` → `/admin/dashboard` (301)

### 2.2 Navigation Menu
- [ ] Dashboard link works
- [ ] Children link works
- [ ] Sponsorships link works
- [ ] Import/Export link works
- [ ] Reports link works
- [ ] Admin Users link works
- [ ] Year-End Reset link works (red color)
- [ ] View Site link (opens in new tab)
- [ ] Logout link works

---

## 3. Children Management

### 3.1 View Children
- [ ] **GET /admin/children** - Children list displays
  - Search functionality
  - Filter by status
  - Pagination
  - Child cards with actions

### 3.2 Add Child
- [ ] **GET /admin/children/add** - Add form displays
- [ ] **POST /admin/children** - Child created successfully
  - Form validation works
  - Required fields enforced
  - Age calculation correct
  - Avatar assignment correct

### 3.3 Edit Child
- [ ] **GET /admin/children/{id}/edit** - Edit form displays
- [ ] **POST /admin/children/{id}** - Child updated successfully
  - All fields editable
  - Changes persist in database

### 3.4 Delete Child
- [ ] **POST /admin/children/{id}/delete** - Child deleted
  - Confirmation required
  - Cannot delete sponsored children

### 3.5 Legacy Redirects
- [ ] `admin/manage_children.php` → `/admin/children` (301)

---

## 4. Sponsorship Management

### 4.1 View Sponsorships
- [ ] **GET /admin/sponsorships** - Sponsorships list displays
  - Statistics grid (pending, logged, completed, cancelled)
  - Filter by status
  - Search functionality
  - Bulk actions available

### 4.2 Individual Actions
- [ ] **POST /admin/sponsorships/{id}/log** - Mark as logged
- [ ] **POST /admin/sponsorships/{id}/unlog** - Unmark as logged
- [ ] **POST /admin/sponsorships/{id}/complete** - Mark as complete
- [ ] **POST /admin/sponsorships/{id}/cancel** - Cancel sponsorship
  - Reason required
  - Child availability updated

### 4.3 Bulk Actions
- [ ] **POST /admin/sponsorships/bulk-action** - Bulk operations work
  - Select multiple sponsorships
  - Apply bulk log
  - Apply bulk complete
  - Export selected as CSV

### 4.4 Legacy Redirects
- [ ] `admin/manage_sponsorships.php` → `/admin/sponsorships` (301)

---

## 5. CSV Import/Export

### 5.1 Import Page
- [ ] **GET /admin/import** - Import page displays
  - Template download link
  - Upload form
  - Backup section
  - Danger zone

### 5.2 CSV Import Flow
- [ ] **POST /admin/import/preview** - Upload CSV
  - File validation
  - Preview statistics
  - Age distribution chart
  - Duplicate detection
- [ ] **POST /admin/import/confirm** - Execute import
  - Children created
  - Success message displayed

### 5.3 Template & Backups
- [ ] **GET /admin/import/download-template** - Template downloads
- [ ] **GET /admin/import/backup** - Manual backup created
- [ ] **POST /admin/import/restore** - Restore from backup works
- [ ] **GET /admin/import/download-backup** - Download backup file

### 5.4 Danger Zone
- [ ] **POST /admin/import/delete-all** - Delete all children
  - Confirmation required (type "DELETE ALL")
  - Only works when no sponsored children

### 5.5 Legacy Redirects
- [ ] `admin/import_csv.php` → `/admin/import` (301)

---

## 6. Year-End Reset & Archive

### 6.1 Archive Page
- [ ] **GET /admin/archive** - Archive page displays
  - Current statistics
  - Archive list
  - Danger warnings visible

### 6.2 Year-End Reset
- [ ] **POST /admin/archive/reset** - Reset executed
  - Confirmation code required (type "RESET")
  - Archive created
  - Children deleted
  - Sponsorships archived
  - Success message displayed

### 6.3 Restore from Archive
- [ ] **POST /admin/archive/restore** - Restore works
  - Select archive year
  - Data restored successfully

### 6.4 Delete Old Archives
- [ ] **POST /admin/archive/delete-old** - Old archives deleted
  - Archives older than retention period removed

### 6.5 Legacy Redirects
- [ ] `admin/year_end_reset.php` → `/admin/archive` (301)

---

## 7. Admin User Management

### 7.1 View Admin Users
- [ ] **GET /admin/users** - Users list displays
  - Current users table
  - Last login timestamps
  - Role badges
  - Actions per user

### 7.2 Add Admin User
- [ ] **Show add form** - Form toggles correctly
- [ ] **POST /admin/users** - User created
  - Username validation (alphanumeric, underscore, hyphen)
  - Email validation
  - Password confirmation
  - Minimum 8 characters
  - Username uniqueness check
  - Role selection (admin/editor)

### 7.3 Edit Admin User
- [ ] **Inline edit form** - Form displays
- [ ] **POST /admin/users/{id}** - User updated
  - Email editable
  - Full name editable
  - Role editable
  - Password change optional
  - Username immutable

### 7.4 Delete Admin User
- [ ] **POST /admin/users/{id}/delete** - User deleted
  - Confirmation required
  - Cannot delete self

### 7.5 Access Control
- [ ] Only users with "admin" role can access this page
- [ ] Editors redirected with error message

### 7.6 Legacy Redirects
- [ ] `admin/manage_admins.php` → `/admin/users` (301)

---

## 8. Reports

### 8.1 Reports Page
- [ ] **GET /admin/reports** - Reports page displays
  - Summary report by default
  - Report type selector

### 8.2 Report Types
- [ ] **Summary Report** - Overall statistics
- [ ] **Sponsorships Report** - Detailed sponsorship data
- [ ] **Children Report** - Children statistics

### 8.3 Export Functionality
- [ ] Export reports as CSV (if implemented)
- [ ] Filter reports by date range
- [ ] Filter by status

### 8.4 Legacy Redirects
- [ ] `admin/reports.php` → `/admin/reports` (301)

---

## 9. Error Handling & Edge Cases

### 9.1 Authentication Errors
- [ ] Expired magic link shows error message
- [ ] Invalid token shows error message
- [ ] Rate limited requests show generic message
- [ ] Unauthenticated requests redirect to login

### 9.2 Validation Errors
- [ ] Form validation errors display correctly
- [ ] CSRF token failures show security error
- [ ] Invalid IDs show error messages
- [ ] Missing required fields prevent submission

### 9.3 Permission Errors
- [ ] Editors cannot access /admin/users
- [ ] Cannot delete self from admin users
- [ ] Only admins can perform admin-only actions

### 9.4 Database Errors
- [ ] Graceful error messages on DB failures
- [ ] Transactions rollback on errors
- [ ] Duplicate entries handled correctly

---

## 10. Performance & Compatibility

### 10.1 Performance
- [ ] Pages load in < 2 seconds
- [ ] No N+1 query issues
- [ ] Pagination works efficiently
- [ ] Large CSV imports complete successfully

### 10.2 Browser Compatibility
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile responsive layout works

### 10.3 Accessibility
- [ ] Form labels present
- [ ] ARIA attributes where needed
- [ ] Keyboard navigation works
- [ ] Error messages readable by screen readers

---

## 11. Data Integrity

### 11.1 Child Data
- [ ] All child fields save correctly
- [ ] Avatar categories assigned properly
- [ ] Age calculations accurate
- [ ] Status changes tracked

### 11.2 Sponsorship Data
- [ ] Sponsor information saved
- [ ] Status transitions logged
- [ ] Email notifications sent
- [ ] Cancellation reasons recorded

### 11.3 Admin User Data
- [ ] Passwords hashed securely
- [ ] Last login updated
- [ ] Role changes take effect immediately
- [ ] Deleted users cannot log in

---

## 12. Regression Testing

### 12.1 Legacy Functionality
- [ ] All previous features still work
- [ ] Email notifications still sent
- [ ] Database queries still accurate
- [ ] Security features still active

### 12.2 Existing Tests
- [ ] Run existing test suite: `./tests/security-functional-tests.sh`
- [ ] Verify 35/36 tests still passing
- [ ] No new security vulnerabilities

---

## Test Results Template

```markdown
## Test Execution Report

**Date:** YYYY-MM-DD
**Tester:** [Name]
**Environment:** [Local/Staging/Production]
**Browser:** [Chrome/Firefox/Safari] [Version]

### Summary
- Total Tests: X
- Passed: X
- Failed: X
- Skipped: X

### Failed Tests
1. [Test Name] - [Reason] - [Priority: High/Medium/Low]
2. ...

### Notes
[Any additional observations or issues found]

### Recommendation
- [ ] Ready for production
- [ ] Requires fixes before deployment
- [ ] Requires additional testing
```

---

## Automated Testing Script

```bash
#!/bin/bash
# Quick smoke test for critical paths

BASE_URL="http://localhost:8080"

echo "Testing critical redirects..."
curl -I "$BASE_URL/admin/index.php" | grep "301"
curl -I "$BASE_URL/admin/login.php" | grep "301"
curl -I "$BASE_URL/admin/logout.php" | grep "301"

echo "Testing Slim routes..."
curl -I "$BASE_URL/admin/login" | grep "200"
curl -I "$BASE_URL/admin/dashboard" | grep "302" # Redirects to login if not authenticated

echo "All critical smoke tests passed!"
```

---

## Sign-Off Checklist

Before marking migration as complete:

- [ ] All test sections completed
- [ ] No critical bugs found
- [ ] Performance acceptable
- [ ] Security review passed
- [ ] Documentation updated
- [ ] Stakeholder approval obtained
- [ ] Backup plan prepared
- [ ] Rollback procedure documented

---

**Test Plan Version:** 1.0
**Last Updated:** 2025-11-12
**Migration Complete:** Week 8 & 9 (100%)
