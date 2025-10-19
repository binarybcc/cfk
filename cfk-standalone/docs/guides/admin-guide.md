# Christmas for Kids - Administrator Guide

## Overview

The Christmas for Kids Sponsorship System helps administrators coordinate gift delivery for children during the holiday season. Sponsors select children, receive complete shopping information via email, purchase and deliver gifts directly.

**Important**: This is a **gift-delivery coordination system**, not a donation platform. Sponsors buy and deliver physical gifts to children, not monetary donations.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Daily Operations](#daily-operations)
3. [Managing Sponsorships](#managing-sponsorships)
4. [Reports & Analytics](#reports--analytics)
5. [Email System](#email-system)
6. [Year-End Reset](#year-end-reset)
7. [Troubleshooting](#troubleshooting)

---

## Getting Started

### Initial Login

**URL**: `https://yoursite.com/sponsor/admin/`

**Default Credentials**:
- Username: `admin`
- Password: `admin123`

**⚠️ CRITICAL**: Change the default password immediately after first login!

### Admin Dashboard

The dashboard provides a quick overview:
- **Total Children**: All children in the database
- **Available Children**: Currently available for sponsorship
- **Pending Sponsorships**: Awaiting admin confirmation
- **Active Sponsorships**: Confirmed sponsorships in progress
- **Completed**: Gifts delivered and marked complete

---

## Daily Operations

### Morning Routine (5-10 minutes)

1. **Check Pending Sponsorships**
   - Navigate to "Sponsorships" menu
   - Review new sponsorship requests (highlighted in yellow)
   - Confirm legitimate requests (click "Confirm" button)
   - System automatically sends detailed email to sponsor with:
     - Child's complete information (name, age, grade, gender)
     - All clothing sizes (shirt, pants, shoes, jacket)
     - Interests and hobbies
     - Christmas wish list items
     - Special needs or notes

2. **Monitor Email Log**
   - Check that confirmation emails were sent successfully
   - Follow up on any failed sends

3. **Release Abandoned Selections**
   - System automatically releases selections after 2 hours
   - Manual release available for stuck records

### Weekly Tasks

1. **Update Child Information**
   - Verify photos are current
   - Update clothing sizes (kids grow fast!)
   - Add new wishes or interests

2. **Contact Sponsors**
   - Follow up on confirmed sponsorships
   - Answer questions about gift delivery
   - Coordinate drop-off times

3. **Generate Reports**
   - Sponsor directory (who sponsored which children)
   - Available children report
   - Family sponsorship status

---

## Managing Sponsorships

### Sponsorship Lifecycle

```
Pending → Confirmed → Completed
   ↓
Cancelled (if needed)
```

### Confirming Sponsorships

**When to Confirm**:
- Valid sponsor information (real name and email)
- No duplicate requests for same child
- Child still available

**How to Confirm**:
1. Go to "Manage Sponsorships"
2. Find pending sponsorship
3. Click "Confirm" button
4. System sends comprehensive email to sponsor automatically

**What the Sponsor Receives**:
- **IMPORTANT - Save This Email!** box
- Complete child information
- Shopping details (clothing sizes in easy-to-read format)
- Interests and Christmas wishes
- Special needs (if any)
- Instructions for gift delivery
- Contact information for questions

### Marking Complete

When sponsor confirms gift delivery:
1. Go to "Manage Sponsorships"
2. Filter by "Confirmed"
3. Find the sponsorship
4. Click "Mark Complete"
5. Child status updates to "sponsored"

### Cancelling Sponsorships

**Valid Reasons**:
- Sponsor requested cancellation
- Sponsor non-responsive after multiple attempts
- Duplicate request
- Fraudulent request

**How to Cancel**:
1. Click "Cancel" button on sponsorship
2. Enter detailed reason (required)
3. Submit cancellation
4. Child automatically returns to "available" status
5. System logs the cancellation with reason

---

## Reports & Analytics

### Available Reports

**1. Dashboard Statistics**
- Real-time overview of system status
- Quick metrics for decision making

**2. Sponsor Directory** (`/admin/reports.php?type=sponsor_directory`)
- Lists all sponsors with contact information
- Shows which children each sponsor selected
- Export to CSV for mail merges or printing

**3. Child-Sponsor Lookup** (`/admin/reports.php?type=child_sponsor`)
- Search for specific child to see their sponsor
- Useful for answering "Who sponsored Child 175A?"
- Includes sponsor contact information

**4. Family Report** (`/admin/reports.php?type=family_report`)
- Shows sponsorship status for entire families
- Identifies families that are:
  - Fully sponsored (all children have sponsors)
  - Partially sponsored (some children still available)
  - Not sponsored (no children selected yet)

**5. Gift Delivery Tracking** (`/admin/reports.php?type=delivery_tracking`)
- Lists confirmed sponsorships awaiting delivery
- Shows days since confirmation
- Export for delivery coordination

**6. Available Children** (`/admin/reports.php?type=available_children`)
- Filterable by age, gender, family
- Shows children still needing sponsors
- Export for promotional materials

### Exporting Data

All reports have "Export to CSV" buttons:
- Click "Export to CSV"
- File downloads automatically with timestamp
- Open in Excel, Google Sheets, or any CSV viewer
- Use for:
  - Printing shopping lists
  - Email campaigns
  - Year-end records
  - Board reports

---

## Email System

### Automated Emails

The system sends emails automatically at these points:

**1. Sponsor Confirmation Email** (Pending → Confirmed)
- Triggered when admin confirms sponsorship
- Contains complete child details for shopping
- Print-friendly format
- Includes all clothing sizes, wishes, interests

**2. Portal Access Email** (Sponsor requests lookup)
- Sent when sponsor enters email on "My Sponsorships" page
- Contains secure 30-minute access link
- Allows sponsor to view their sponsorships and add more children

**3. Additional Children Email** (Sponsor adds more children)
- Sent when sponsor adds more children via portal
- Lists all children (including previously sponsored)
- Complete shopping details for all children

### Email Template Details

All sponsor emails include:
- **Prominent "Save This Email" box** with importance message
- **Child Information Card** with demographics
- **Clothing Sizes Table** in easy-to-read grid format
- **Interests Section** for personal gift ideas
- **Christmas Wishes** prominently displayed
- **Special Needs** highlighted when present
- **Delivery Instructions** for unwrapped gifts
- **Contact Information** for questions

### Troubleshooting Email Issues

**Email Not Sending?**
1. Check email_log table for error messages
2. Verify SMTP settings in `includes/email_manager.php`
3. Test with a simple confirmation to your own email
4. Check spam folders

**Sponsor Didn't Receive Email?**
1. Verify correct email address in sponsorship record
2. Check email_log for successful send
3. Ask sponsor to check spam/junk folder
4. Manually forward copy from email_log if needed

---

## Year-End Reset

### Purpose

At the end of each Christmas season, clear all data to start fresh for the next year while preserving historical records.

### Safety Features

The system has **multiple layers of protection**:
1. ✅ Full database backup created automatically
2. ✅ All data exported to CSV files
3. ✅ Archive summary document generated
4. ✅ Confirmation code required (must type "RESET [YEAR]")
5. ✅ JavaScript double-confirmation dialog
6. ✅ Cannot be undone warning prominently displayed

### When to Perform Reset

**Timing**: January after all gifts delivered and thank-you notes sent

**Prerequisites**:
- [ ] All sponsorships marked complete
- [ ] Year-end reports generated and saved externally
- [ ] Board reports submitted
- [ ] Thank you notes sent
- [ ] Any financial reconciliation complete

### Reset Process

**URL**: `/admin/year_end_reset.php` (red link in admin navigation)

**Steps**:
1. **Review Current Stats**
   - Page shows current counts (children, families, sponsorships, emails)
   - Verify these numbers match your records

2. **Read Warnings Carefully**
   - Yellow warning box lists exactly what will happen
   - Red "THIS ACTION CANNOT BE UNDONE!" banner
   - Understand the impact before proceeding

3. **Enter Year to Archive**
   - Enter 4-digit year (e.g., "2025")
   - This creates archive folder: `archives/2025/`

4. **Enter Confirmation Code**
   - Type exactly: `RESET [YEAR]` (e.g., "RESET 2025")
   - Must match exactly (case-sensitive, include space)

5. **Click "Perform Year-End Reset"**
   - JavaScript confirms: "Are you sure?"
   - Click OK to proceed

6. **Wait for Completion**
   - Process takes 10-15 seconds
   - Success message shows deleted counts

### What Gets Archived

The system creates in `archives/[YEAR]/`:
- `database_backup_[timestamp].sql` - Full database backup
- `children_[timestamp].csv` - All children data with family info
- `families_[timestamp].csv` - All families data
- `sponsorships_[timestamp].csv` - All sponsorships with sponsor and child info
- `email_log_[timestamp].csv` - All email communications
- `ARCHIVE_SUMMARY.txt` - Statistics and documentation

### What Gets Deleted

**Cleared Tables** (data removed):
- All children records
- All families records
- All sponsorship records
- All email log entries

**Preserved Data**:
- Admin user accounts
- System settings
- Database structure (tables, indexes)
- Archive files

### Starting Fresh for New Season

**After Reset**:
1. Import new CSV with updated children (see CSV-IMPORT-GUIDE.md)
2. Verify import success
3. Test sponsorship workflow
4. Open site to public

---

## Troubleshooting

### Common Issues

**1. Child Stuck in "Pending" Status**

**Symptoms**: Child showing as pending but no active sponsorship

**Solution**:
```
1. Go to "Manage Sponsorships"
2. Find the problematic sponsorship
3. Either confirm or cancel it
4. If no sponsorship exists, use "Release Child" button
```

**2. Duplicate Sponsorship Requests**

**Symptoms**: Two sponsors selected same child simultaneously

**Solution**:
```
1. Confirm the first valid request
2. Cancel the second request with reason: "Child already sponsored by another family"
3. Email second sponsor suggesting alternative children
4. Provide link to available children
```

**3. Sponsor Can't Find Confirmation Email**

**Solution**:
```
1. Check email_log for successful send
2. Verify correct email address
3. Ask sponsor to check spam folder
4. Resend via admin (forward from email_log)
5. Or have sponsor use "My Sponsorships" portal
```

**4. Photo Upload Fails**

**Solution**:
```
1. Check file size (max 5MB)
2. Verify uploads/ directory permissions (755)
3. Try different image format (JPG recommended)
4. Resize image if too large
```

**5. CSV Import Errors**

**Solution**:
```
1. Review CSV-IMPORT-GUIDE.md for format
2. Check for special characters in data
3. Verify family numbers are unique
4. Test with small sample first
```

### Getting Help

**For Technical Issues**:
- Check PHP error log on server
- Review database error messages
- Contact your web developer/hosting provider

**For Process Questions**:
- Review this guide
- Check sponsor workflow documentation
- Contact CFK administration

---

## Best Practices

### Data Management

1. **Regular Backups**
   - Export data weekly during active season
   - Store backups off-site
   - Test restoration process

2. **Photo Management**
   - Use clear, recent photos
   - Maintain consistent naming (child_id.jpg)
   - Keep backup of all photos

3. **Communication**
   - Respond to sponsor questions within 24 hours
   - Keep contact information updated
   - Document all phone conversations in sponsorship notes

### Security

1. **Password Management**
   - Change admin password regularly
   - Use strong passwords (12+ characters)
   - Never share admin credentials

2. **Access Control**
   - Limit admin access to trusted staff only
   - Log out when leaving computer
   - Access admin panel only from secure networks

3. **Data Privacy**
   - Protect child and family information
   - Only share necessary details with sponsors
   - Follow organizational privacy policies

### Seasonal Workflow

**November - December** (Peak Season):
- Check pending sponsorships twice daily
- Respond to sponsor questions quickly
- Monitor available children counts
- Generate weekly status reports

**January** (Year-End):
- Mark all sponsorships complete
- Generate final reports
- Perform year-end reset
- Archive all records

**February - October** (Prep Season):
- Import updated child data
- Verify family information
- Update photos
- Test system functionality
- Prepare promotional materials

---

## Quick Reference

### Admin URLs

- **Dashboard**: `/admin/index.php`
- **Children**: `/admin/manage_children.php`
- **Families**: `/admin/manage_families.php`
- **Sponsorships**: `/admin/manage_sponsorships.php`
- **Reports**: `/admin/reports.php`
- **Year-End Reset**: `/admin/year_end_reset.php`
- **CSV Import**: `/admin/import_csv.php`

### Keyboard Shortcuts

- `Ctrl/Cmd + Click` on child = Open in new tab
- Browser back button = Safe to use (no data loss)

### Support Contacts

**Technical Support**: [Your web developer contact]
**CFK Administration**: [Your admin contact]
**Hosting Support**: [Your hosting provider]

---

**Document Version**: 1.0
**Last Updated**: October 2025
**Maintained By**: CFK Technology Team
