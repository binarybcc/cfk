# Test Data Guide

## Overview

Comprehensive test data has been created to validate the Christmas for Kids sponsorship system functionality, performance, and user experience.

## Test Data Summary

- **25 test families** (TEST-001 through TEST-025)
- **80+ children total** (1-5 children per family)
- **Ages**: 3-18 years (full spectrum)
- **16 sponsors** with various sponsorship patterns
- **ALL data marked with "TEST"** for easy identification and cleanup

## Sponsorship Patterns

### Complete Family Sponsorships (5 sponsors)
- TEST Sarah Johnson → TEST-001 (1 child) - CONFIRMED
- TEST Emily Rodriguez → TEST-003 (3 children) - CONFIRMED
- TEST Jennifer Martinez → TEST-005 (5 children) - CONFIRMED
- TEST Lisa Anderson → TEST-007 (3 children) - CONFIRMED
- TEST Kevin Young → TEST-015 (3 children) - CONFIRMED
- TEST Steven Scott → TEST-018 (3 children) - CONFIRMED

### Partial Family Sponsorships (9 sponsors)
- TEST Michael Chen → TEST-002 (1 of 2) - CONFIRMED
- TEST David Williams → TEST-004 (2 of 4) - CONFIRMED
- TEST Robert Taylor → TEST-006 (1 of 2) - PENDING
- TEST Maria Garcia → TEST-009 (2 of 4) - CONFIRMED
- TEST Christopher Lee → TEST-010 (2 of 2) - PENDING
- TEST Patricia White → TEST-011 (1 of 3) - CONFIRMED
- TEST Daniel Harris → TEST-012 (3 of 5) - CONFIRMED
- TEST Nancy Clark → TEST-013 (1 of 2) - CONFIRMED
- TEST Barbara King → TEST-016 (2 of 4) - PENDING

### Single Child Sponsorships (2 sponsors)
- TEST James Thompson → TEST-008 (1 child) - COMPLETED

### Unsponsored Families (10 families)
- TEST-014, TEST-017, TEST-019, TEST-020, TEST-021, TEST-022, TEST-023, TEST-024, TEST-025
- Total: ~20-25 available children

## Testing Checklist

### Report Testing

#### Dashboard Report
- [ ] Statistics display correctly with test data
- [ ] Counts match actual data (children, sponsorships, families)
- [ ] Quick action links work
- [ ] CSV exports generate properly

#### Sponsor Directory Report
- [ ] All 16 sponsors listed
- [ ] Grouped by sponsor email
- [ ] Children correctly associated with sponsors
- [ ] Sizes and wishes display properly
- [ ] CSV export includes all fields
- [ ] Sort order is correct

#### Child-Sponsor Lookup Report
- [ ] All 80+ children visible
- [ ] Search by child ID works (try "TEST-003A")
- [ ] Sponsored vs unsponsored status shows correctly
- [ ] Sponsor information displays for sponsored children
- [ ] CSV export works
- [ ] Empty search returns all children

#### Family Report
- [ ] All 25 test families listed
- [ ] Child counts accurate (1-5 per family)
- [ ] Available/Pending/Sponsored counts correct
- [ ] Family status badges (Complete/Partial/None) accurate
- [ ] CSV export works
- [ ] Sort by family number works

#### Available Children Report
- [ ] Shows only children with status = 'available'
- [ ] Filter by age works (try 5-10, 11-15, 16-18)
- [ ] Filter by gender works
- [ ] Family information displayed
- [ ] Siblings count accurate
- [ ] CSV export works

#### Complete Export
- [ ] All 80+ children included
- [ ] Sponsor data included for sponsored children
- [ ] NULL/empty for unsponsored children
- [ ] All fields present (size, wishes, special needs, etc.)
- [ ] CSV export generates large file successfully
- [ ] Performance acceptable (should be < 5 seconds)

### Search & Filter Testing

#### Search Functionality
- [ ] Search "TEST-001" returns family 001
- [ ] Search "TEST-" returns all test families
- [ ] Search partial IDs works (e.g., "003")
- [ ] Case insensitive search works
- [ ] Special characters handled properly

#### Filter Functionality
- [ ] Age filter: 0-5, 6-10, 11-15, 16-18
- [ ] Gender filter: Boys, Girls, All
- [ ] Status filter: Available, Pending, Sponsored
- [ ] Multiple filters combined work correctly

### CSV Export Testing

#### Export Quality
- [ ] Headers match data fields
- [ ] No missing columns
- [ ] Special characters handled (commas, quotes)
- [ ] Line breaks in text fields handled
- [ ] File downloads with correct filename
- [ ] File opens in Excel/Google Sheets properly

#### Large Export Performance
- [ ] Complete export (~80 children) completes in < 5 seconds
- [ ] No timeout errors
- [ ] No memory errors
- [ ] File size reasonable (< 1MB for this dataset)

### UI/UX Testing

#### Empty States
- [ ] Dashboard with no data shows stats as 0
- [ ] Reports with no data show empty state messages
- [ ] Empty state styling matches design
- [ ] Empty state messages are helpful

#### Populated States
- [ ] Tables render correctly with full data
- [ ] Pagination works if implemented
- [ ] Sorting works correctly
- [ ] Status badges display with correct colors
- [ ] No layout breaks with long text

#### Performance
- [ ] Reports load in < 2 seconds
- [ ] No visible lag when switching reports
- [ ] CSV exports respond immediately
- [ ] Search/filter updates are instant
- [ ] No JavaScript errors in console

### Edge Cases

#### Data Validation
- [ ] Mixed sponsorship statuses display correctly
- [ ] Partial family sponsorships show accurately
- [ ] Multi-child sponsors group correctly
- [ ] Children without sponsors show "-" or "N/A"
- [ ] Age range 3-18 all display properly

#### Privacy Compliance
- [ ] No real names displayed (using family_number + child_letter)
- [ ] All TEST data clearly marked
- [ ] Sensitive fields (if any) are protected
- [ ] Export files don't expose unnecessary info

#### Error Handling
- [ ] Invalid search terms handled gracefully
- [ ] Corrupt CSV uploads rejected (if applicable)
- [ ] SQL errors don't expose system info
- [ ] User-friendly error messages displayed

## Loading Test Data

### On Production Server

```bash
# SSH into production
ssh user@server

# Navigate to database directory
cd /home/a4409d26/d646a74eb9.nxcli.io/html/database

# Upload test_data.sql file first

# Run the SQL script
mysql -u a4409d26_509946 -p a4409d26_509946 < test_data.sql
```

### Using SCP (from local machine)

```bash
# Upload SQL file
sshpass -p 'PASSWORD' scp -P 22 database/test_data.sql user@server:/path/

# Connect and run
sshpass -p 'PASSWORD' ssh -p 22 user@server "mysql -u USER -pPASS DB < /path/test_data.sql"
```

## Cleaning Up Test Data

When testing is complete:

```bash
# Upload cleanup script
scp database/cleanup_test_data.sql user@server:/path/

# Run cleanup
mysql -u a4409d26_509946 -p a4409d26_509946 < cleanup_test_data.sql
```

**IMPORTANT:** Verify real data is intact after cleanup by checking the "REAL DATA CHECK" output.

## Test Scenarios

### Scenario 1: New Sponsor Workflow
1. Browse available children (should see ~20-25 unsponsored)
2. Select children from TEST-020 family
3. Add to cart
4. Complete sponsorship request
5. Verify pending status
6. Admin confirms sponsorship
7. Verify confirmed status in reports

### Scenario 2: Admin Dashboard
1. View statistics (should show ~80 children, ~40 sponsored)
2. Export complete database
3. Review sponsor directory (16 sponsors)
4. Check family report for partial families
5. Identify available children for outreach

### Scenario 3: Sponsor Portal
1. Login as TEST sponsor (use email link)
2. View sponsored children
3. Download shopping list CSV
4. Verify all child information present
5. Check sizing information accurate

### Scenario 4: Search & Filter
1. Search for specific family (TEST-015)
2. Filter children by age group (6-10)
3. Filter by gender (Boys)
4. Combine filters (Boys, age 10-15, available)
5. Export filtered results

### Scenario 5: Reporting
1. Generate all 6 report types
2. Export each to CSV
3. Verify data accuracy in exports
4. Check performance with full dataset
5. Validate empty vs populated states

## Performance Benchmarks

With 80+ children and 40+ sponsorships:

- **Dashboard load**: < 1 second
- **Report load**: < 2 seconds
- **CSV export**: < 5 seconds
- **Search results**: < 1 second
- **Filter updates**: < 500ms

## Known Issues

- None currently - test data should work with all features
- If issues found, document here for tracking

## Notes

- All test data includes "TEST" marker in multiple fields
- Cleanup script verifies real data remains intact
- Can regenerate test data by running script again (use cleanup first)
- Test data designed to match realistic use cases
- Age distribution matches typical program demographics
