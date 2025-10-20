#!/bin/bash

# AppleScript CSV Export Testing for CFK Reports
# Tests all 5 CSV export types

set -e

ADMIN_URL="https://cforkids.org/admin"
ADMIN_USER="test_automation"
ADMIN_PASS="TestAdmin2025!"
DOWNLOAD_DIR="$HOME/Downloads"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASS=0
FAIL=0
TOTAL=0

function test_result() {
    TOTAL=$((TOTAL + 1))
    if [ $1 -eq 0 ]; then
        echo -e "  ${GREEN}✅ PASS${NC}: $2"
        PASS=$((PASS + 1))
    else
        echo -e "  ${RED}❌ FAIL${NC}: $2"
        FAIL=$((FAIL + 1))
    fi
}

echo "=========================================="
echo "CFK CSV EXPORT TEST SUITE"
echo "=========================================="
echo ""

# Check credentials
if [ -z "$ADMIN_PASS" ]; then
    echo -e "${YELLOW}⚠️  WARNING: Admin credentials not set${NC}"
    echo "Please edit this script and add:"
    echo "  ADMIN_USER=\"your_username\""
    echo "  ADMIN_PASS=\"your_password\""
    echo ""
    echo "Exiting..."
    exit 1
fi

# Login first
echo "Logging in to admin panel..."
osascript <<EOF
tell application "Safari"
    activate
    close every window
    make new document with properties {URL:"${ADMIN_URL}/login.php"}
    delay 2

    tell current tab of window 1
        do JavaScript "
            document.querySelector('input[name=\"username\"]').value = '${ADMIN_USER}';
            document.querySelector('input[name=\"password\"]').value = '${ADMIN_PASS}';
            document.querySelector('form').submit();
        "
        delay 3
    end tell
end tell
EOF

echo ""
echo "Testing CSV exports..."
echo ""

# Track downloaded files
TIMESTAMP=$(date +%Y-%m-%d)
declare -a CSV_FILES=(
    "sponsor-directory-${TIMESTAMP}.csv"
    "child-sponsor-lookup-${TIMESTAMP}.csv"
    "family-report-${TIMESTAMP}.csv"
    "available-children-${TIMESTAMP}.csv"
    "complete-children-sponsors-${TIMESTAMP}.csv"
)

# Delete any existing files with today's date
for file in "${CSV_FILES[@]}"; do
    if [ -f "$DOWNLOAD_DIR/$file" ]; then
        rm "$DOWNLOAD_DIR/$file"
        echo "Cleaned up existing: $file"
    fi
done

# Test 1: Sponsor Directory CSV
echo "Test 1: Sponsor Directory CSV export..."
osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php?type=sponsor_directory&export=csv"
        delay 3
    end tell
end tell
EOF

sleep 2
if [ -f "$DOWNLOAD_DIR/${CSV_FILES[0]}" ]; then
    LINES=$(wc -l < "$DOWNLOAD_DIR/${CSV_FILES[0]}")
    test_result 0 "Sponsor Directory CSV downloaded ($LINES lines)"
else
    test_result 1 "Sponsor Directory CSV not found"
fi

# Test 2: Child-Sponsor Lookup CSV
echo ""
echo "Test 2: Child-Sponsor Lookup CSV export..."
osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php?type=child_sponsor&export=csv"
        delay 3
    end tell
end tell
EOF

sleep 2
if [ -f "$DOWNLOAD_DIR/${CSV_FILES[1]}" ]; then
    LINES=$(wc -l < "$DOWNLOAD_DIR/${CSV_FILES[1]}")
    test_result 0 "Child-Sponsor Lookup CSV downloaded ($LINES lines)"
else
    test_result 1 "Child-Sponsor Lookup CSV not found"
fi

# Test 3: Family Report CSV
echo ""
echo "Test 3: Family Report CSV export..."
osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php?type=family_report&export=csv"
        delay 3
    end tell
end tell
EOF

sleep 2
if [ -f "$DOWNLOAD_DIR/${CSV_FILES[2]}" ]; then
    LINES=$(wc -l < "$DOWNLOAD_DIR/${CSV_FILES[2]}")
    test_result 0 "Family Report CSV downloaded ($LINES lines)"
else
    test_result 1 "Family Report CSV not found"
fi

# Test 4: Available Children CSV
echo ""
echo "Test 4: Available Children CSV export..."
osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php?type=available_children&export=csv"
        delay 3
    end tell
end tell
EOF

sleep 2
if [ -f "$DOWNLOAD_DIR/${CSV_FILES[3]}" ]; then
    LINES=$(wc -l < "$DOWNLOAD_DIR/${CSV_FILES[3]}")
    test_result 0 "Available Children CSV downloaded ($LINES lines)"
else
    test_result 1 "Available Children CSV not found"
fi

# Test 5: Complete Export CSV
echo ""
echo "Test 5: Complete Export CSV..."
osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php?type=complete_export&export=csv"
        delay 3
    end tell
end tell
EOF

sleep 2
if [ -f "$DOWNLOAD_DIR/${CSV_FILES[4]}" ]; then
    LINES=$(wc -l < "$DOWNLOAD_DIR/${CSV_FILES[4]}")
    test_result 0 "Complete Export CSV downloaded ($LINES lines)"
else
    test_result 1 "Complete Export CSV not found"
fi

# Validate CSV content
echo ""
echo "Validating CSV content..."
echo ""

# Test 6: Verify headers in sponsor directory CSV
if [ -f "$DOWNLOAD_DIR/${CSV_FILES[0]}" ]; then
    HEADER=$(head -1 "$DOWNLOAD_DIR/${CSV_FILES[0]}")
    if echo "$HEADER" | grep -q "Sponsor Name"; then
        test_result 0 "Sponsor Directory has correct headers"
    else
        test_result 1 "Sponsor Directory headers incorrect: $HEADER"
    fi
else
    test_result 1 "Cannot validate Sponsor Directory (file not found)"
fi

# Test 7: Verify TEST markers in data
if [ -f "$DOWNLOAD_DIR/${CSV_FILES[1]}" ]; then
    TEST_COUNT=$(grep -c "TEST-" "$DOWNLOAD_DIR/${CSV_FILES[1]}" || true)
    if [ "$TEST_COUNT" -gt 0 ]; then
        test_result 0 "Child-Sponsor CSV contains TEST data ($TEST_COUNT rows)"
    else
        test_result 1 "Child-Sponsor CSV missing TEST data"
    fi
else
    test_result 1 "Cannot validate Child-Sponsor (file not found)"
fi

# Test 8: Verify family report structure
if [ -f "$DOWNLOAD_DIR/${CSV_FILES[2]}" ]; then
    HEADER=$(head -1 "$DOWNLOAD_DIR/${CSV_FILES[2]}")
    if echo "$HEADER" | grep -q "Family Number"; then
        test_result 0 "Family Report has correct headers"
    else
        test_result 1 "Family Report headers incorrect: $HEADER"
    fi
else
    test_result 1 "Cannot validate Family Report (file not found)"
fi

# Test 9: Verify available children has data
if [ -f "$DOWNLOAD_DIR/${CSV_FILES[3]}" ]; then
    LINES=$(wc -l < "$DOWNLOAD_DIR/${CSV_FILES[3]}")
    if [ "$LINES" -gt 1 ]; then
        test_result 0 "Available Children CSV has data ($LINES lines)"
    else
        test_result 1 "Available Children CSV is empty"
    fi
else
    test_result 1 "Cannot validate Available Children (file not found)"
fi

# Test 10: Verify complete export is largest file
if [ -f "$DOWNLOAD_DIR/${CSV_FILES[4]}" ]; then
    LINES=$(wc -l < "$DOWNLOAD_DIR/${CSV_FILES[4]}")
    if [ "$LINES" -gt 10 ]; then
        test_result 0 "Complete Export has substantial data ($LINES lines)"
    else
        test_result 1 "Complete Export seems too small ($LINES lines)"
    fi
else
    test_result 1 "Cannot validate Complete Export (file not found)"
fi

echo ""
echo "=========================================="
echo "TEST SUMMARY"
echo "=========================================="
echo "Total Tests: $TOTAL"
echo -e "${GREEN}Passed: $PASS${NC}"
echo -e "${RED}Failed: $FAIL${NC}"
echo ""

# Show downloaded files
echo "Downloaded CSV files:"
for file in "${CSV_FILES[@]}"; do
    if [ -f "$DOWNLOAD_DIR/$file" ]; then
        SIZE=$(du -h "$DOWNLOAD_DIR/$file" | cut -f1)
        LINES=$(wc -l < "$DOWNLOAD_DIR/$file")
        echo "  ✓ $file ($SIZE, $LINES lines)"
    else
        echo "  ✗ $file (not found)"
    fi
done
echo ""

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}✅ ALL CSV TESTS PASSED${NC}"
    echo ""
    echo "CSV files are in: $DOWNLOAD_DIR"
    echo "To inspect: open $DOWNLOAD_DIR"
    exit 0
else
    echo -e "${RED}❌ SOME CSV TESTS FAILED${NC}"
    exit 1
fi
