#!/bin/bash
# Quick verification script for LOGGED status functionality
# Run this after testing in admin panel

echo "=== LOGGED Status Verification ==="
echo ""

echo "1. Current sponsorship status:"
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "SELECT id, sponsor_name, status, logged_date, confirmation_date
   FROM sponsorships
   WHERE sponsor_email = 'test@example.com';" 2>&1 | grep -v "Warning"

echo ""
echo "2. Statistics by status:"
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "SELECT status, COUNT(*) as count
   FROM sponsorships
   GROUP BY status;" 2>&1 | grep -v "Warning"

echo ""
echo "3. Check if ENUM includes 'logged':"
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "SELECT COLUMN_TYPE
   FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_NAME = 'sponsorships' AND COLUMN_NAME = 'status';" 2>&1 | grep -v "Warning"

echo ""
echo "4. Check logged_date column exists:"
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "SELECT COLUMN_NAME, COLUMN_TYPE
   FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_NAME = 'sponsorships' AND COLUMN_NAME = 'logged_date';" 2>&1 | grep -v "Warning"

echo ""
echo "=== Verification Complete ==="
echo ""
echo "Expected Results:"
echo "  - Status ENUM should include: 'logged'"
echo "  - logged_date column should exist (datetime)"
echo "  - If you clicked 'Mark Logged', status should be 'logged' with timestamp"
echo ""
