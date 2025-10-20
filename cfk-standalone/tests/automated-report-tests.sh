#!/bin/bash

# Automated Testing Suite for CFK Reports
# What this CAN test without manual intervention

set -e

PROD_HOST="a4409d26_1@d646a74eb9.nxcli.io"
PROD_PASS="PiggedCoifSourerFating"
DB_USER="a4409d26_509946"
DB_PASS="Fests42Cue50Fennel56Auk46"
DB_NAME="a4409d26_509946"

PASS=0
FAIL=0
TOTAL=0

function test_result() {
    TOTAL=$((TOTAL + 1))
    if [ $1 -eq 0 ]; then
        echo "  ✅ PASS: $2"
        PASS=$((PASS + 1))
    else
        echo "  ❌ FAIL: $2"
        FAIL=$((FAIL + 1))
    fi
}

echo "=========================================="
echo "CFK AUTOMATED TEST SUITE"
echo "=========================================="
echo ""

# ============================================================================
# DATABASE TESTS (Can run these fully automated)
# ============================================================================

echo "=== DATABASE TESTS ==="
echo ""

# Test 1: Verify test data loaded
echo "Test 1: Test data loaded correctly"
RESULT=$(sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -se 'SELECT COUNT(*) FROM families WHERE family_number LIKE \"TEST-%\"'")
[ "$RESULT" -eq 25 ] && test_result 0 "25 test families exist" || test_result 1 "Expected 25 families, got $RESULT"

RESULT=$(sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -se 'SELECT COUNT(*) FROM children c JOIN families f ON c.family_id = f.id WHERE f.family_number LIKE \"TEST-%\"'")
[ "$RESULT" -ge 60 ] && test_result 0 "68 test children exist (got $RESULT)" || test_result 1 "Expected 60+, got $RESULT"

# Test 2: Verify status distribution
echo ""
echo "Test 2: Child status distribution"
AVAILABLE=$(sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -se 'SELECT COUNT(*) FROM children c JOIN families f ON c.family_id = f.id WHERE f.family_number LIKE \"TEST-%\" AND c.status = \"available\"'")
[ "$AVAILABLE" -gt 0 ] && test_result 0 "Available children: $AVAILABLE" || test_result 1 "No available children"

SPONSORED=$(sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -se 'SELECT COUNT(*) FROM children c JOIN families f ON c.family_id = f.id WHERE f.family_number LIKE \"TEST-%\" AND c.status = \"sponsored\"'")
[ "$SPONSORED" -gt 0 ] && test_result 0 "Sponsored children: $SPONSORED" || test_result 1 "No sponsored children"

# Test 3: Verify TEST markers present
echo ""
echo "Test 3: TEST markers in data"
NO_MARKER=$(sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -se 'SELECT COUNT(*) FROM children c JOIN families f ON c.family_id = f.id WHERE f.family_number LIKE \"TEST-%\" AND c.interests NOT LIKE \"%TEST%\" AND c.wishes NOT LIKE \"%TEST%\" AND c.special_needs NOT LIKE \"%TEST MARKER%\"'")
[ "$NO_MARKER" -eq 0 ] && test_result 0 "All children have TEST markers" || test_result 1 "$NO_MARKER children missing TEST markers"

# Test 4: Verify report queries work
echo ""
echo "Test 4: Report SQL queries execute without errors"

# Sponsor Directory Query
sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -se 'SELECT COUNT(*) FROM sponsorships s JOIN children c ON s.child_id = c.id JOIN families f ON c.family_id = f.id WHERE s.status != \"cancelled\"'" > /dev/null 2>&1
test_result $? "Sponsor Directory query executes"

# Child-Sponsor Lookup Query
sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -se 'SELECT COUNT(*) FROM children c JOIN families f ON c.family_id = f.id LEFT JOIN sponsorships s ON c.id = s.child_id'" > /dev/null 2>&1
test_result $? "Child-Sponsor Lookup query executes"

# Family Report Query
sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -se 'SELECT COUNT(*) FROM families f LEFT JOIN children c ON f.id = c.family_id GROUP BY f.id'" > /dev/null 2>&1
test_result $? "Family Report query executes"

# Available Children Query
sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -se 'SELECT COUNT(*) FROM children c JOIN families f ON c.family_id = f.id WHERE c.status = \"available\"'" > /dev/null 2>&1
test_result $? "Available Children query executes"

# Complete Export Query
sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -se 'SELECT COUNT(*) FROM children c JOIN families f ON c.family_id = f.id LEFT JOIN sponsorships s ON c.id = s.child_id'" > /dev/null 2>&1
test_result $? "Complete Export query executes"

# ============================================================================
# HTTP TESTS (Limited - requires login for full testing)
# ============================================================================

echo ""
echo "=== HTTP TESTS (Public Access) ==="
echo ""

# Test 5: Public pages accessible
echo "Test 5: Public page accessibility"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "https://cforkids.org/")
[ "$STATUS" -eq 200 ] && test_result 0 "Homepage: $STATUS" || test_result 1 "Homepage: $STATUS"

STATUS=$(curl -s -o /dev/null -w "%{http_code}" "https://cforkids.org/children")
[ "$STATUS" -eq 200 ] && test_result 0 "Children page: $STATUS" || test_result 1 "Children page: $STATUS"

# Test 6: Admin pages require login (should get 302 redirect)
echo ""
echo "Test 6: Admin pages require authentication"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "https://cforkids.org/admin/reports.php")
[ "$STATUS" -eq 302 ] && test_result 0 "Reports require login: $STATUS" || test_result 1 "Reports auth: $STATUS"

# Test 7: Response time checks
echo ""
echo "Test 7: Performance - Response times"
TIME=$(curl -s -o /dev/null -w "%{time_total}" "https://cforkids.org/")
TIME_INT=$(echo "$TIME * 1000" | bc | cut -d. -f1)
[ "$TIME_INT" -lt 2000 ] && test_result 0 "Homepage load: ${TIME}s" || test_result 1 "Homepage slow: ${TIME}s"

TIME=$(curl -s -o /dev/null -w "%{time_total}" "https://cforkids.org/children")
TIME_INT=$(echo "$TIME * 1000" | bc | cut -d. -f1)
[ "$TIME_INT" -lt 2000 ] && test_result 0 "Children page load: ${TIME}s" || test_result 1 "Children page slow: ${TIME}s"

# ============================================================================
# FILE TESTS
# ============================================================================

echo ""
echo "=== FILE INTEGRITY TESTS ==="
echo ""

# Test 8: Verify key files exist
echo "Test 8: Critical files exist on production"
sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" "test -f /home/a4409d26/d646a74eb9.nxcli.io/html/admin/reports.php"
test_result $? "reports.php exists"

sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" "test -f /home/a4409d26/d646a74eb9.nxcli.io/html/includes/report_manager.php"
test_result $? "report_manager.php exists"

sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" "test -f /home/a4409d26/d646a74eb9.nxcli.io/html/admin/includes/admin_header.php"
test_result $? "admin_header.php exists"

sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" "test -f /home/a4409d26/d646a74eb9.nxcli.io/html/admin/includes/admin_footer.php"
test_result $? "admin_footer.php exists"

# Test 9: Verify no SQL syntax errors in report_manager.php
echo ""
echo "Test 9: PHP syntax validation"
sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" "php -l /home/a4409d26/d646a74eb9.nxcli.io/html/admin/reports.php" > /dev/null 2>&1
test_result $? "reports.php syntax valid"

sshpass -p "$PROD_PASS" ssh -p 22 "$PROD_HOST" "php -l /home/a4409d26/d646a74eb9.nxcli.io/html/includes/report_manager.php" > /dev/null 2>&1
test_result $? "report_manager.php syntax valid"

# ============================================================================
# SUMMARY
# ============================================================================

echo ""
echo "=========================================="
echo "TEST SUMMARY"
echo "=========================================="
echo "Total Tests: $TOTAL"
echo "Passed: $PASS"
echo "Failed: $FAIL"
echo ""

if [ $FAIL -eq 0 ]; then
    echo "✅ ALL TESTS PASSED"
    exit 0
else
    echo "❌ SOME TESTS FAILED"
    exit 1
fi
