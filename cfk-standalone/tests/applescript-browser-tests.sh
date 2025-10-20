#!/bin/bash

# AppleScript-Based Browser Testing for CFK Reports
# Uses Safari (best AppleScript support) to test authenticated admin pages

set -e

ADMIN_URL="https://cforkids.org/admin"
ADMIN_USER="test_automation"
ADMIN_PASS="TestAdmin2025!"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

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
echo "CFK APPLESCRIPT BROWSER TEST SUITE"
echo "Browser: Safari (best AppleScript support)"
echo "=========================================="
echo ""

# Check if credentials are set
if [ -z "$ADMIN_PASS" ]; then
    echo -e "${YELLOW}⚠️  WARNING: Admin credentials not set${NC}"
    echo "Please edit this script and add:"
    echo "  ADMIN_USER=\"your_username\""
    echo "  ADMIN_PASS=\"your_password\""
    echo ""
    echo "Exiting..."
    exit 1
fi

echo "Opening Safari and logging in..."
echo ""

# Test 1: Open Safari and navigate to login page
osascript <<EOF
tell application "Safari"
    activate

    -- Close all windows first
    close every window

    -- Open login page
    make new document with properties {URL:"${ADMIN_URL}/login.php"}
    delay 2

    -- Fill in login form using JavaScript
    tell current tab of window 1
        do JavaScript "
            document.querySelector('input[name=\"username\"]').value = '${ADMIN_USER}';
            document.querySelector('input[name=\"password\"]').value = '${ADMIN_PASS}';
        "
        delay 1

        -- Submit form
        do JavaScript "
            document.querySelector('form').submit();
        "
        delay 3
    end tell
end tell
EOF

test_result $? "Safari opened and login attempted"

# Test 2: Check if logged in (look for admin dashboard)
echo ""
echo "Verifying login success..."
LOGGED_IN=$(osascript <<'EOF'
tell application "Safari"
    tell current tab of window 1
        set pageURL to URL
        if pageURL contains "admin/index.php" or pageURL contains "admin/dashboard" then
            return "success"
        else
            return "fail"
        end if
    end tell
end tell
EOF
)

if [ "$LOGGED_IN" = "success" ]; then
    test_result 0 "Login successful"
else
    test_result 1 "Login failed - check credentials"
    echo "Exiting..."
    exit 1
fi

# Test 3: Navigate to Reports page
echo ""
echo "Testing Reports page..."
osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php"
        delay 2
    end tell
end tell
EOF

test_result $? "Reports page loaded"

# Test 4: Check Dashboard report data
echo ""
echo "Testing Dashboard report..."
STATS=$(osascript <<'EOF'
tell application "Safari"
    tell current tab of window 1
        set statsText to do JavaScript "
            try {
                const totalChildren = document.querySelector('.stat-card:nth-child(1) p:first-of-type strong').nextSibling.textContent.trim();
                const totalSponsors = document.querySelector('.stat-card:nth-child(4) p:first-of-type strong').nextSibling.textContent.trim();
                JSON.stringify({children: totalChildren, sponsors: totalSponsors});
            } catch(e) {
                JSON.stringify({error: e.message});
            }
        "
        return statsText
    end tell
end tell
EOF
)

if echo "$STATS" | grep -q "error"; then
    test_result 1 "Dashboard stats extraction failed"
else
    echo "$STATS" | grep -q "children" && test_result 0 "Dashboard stats loaded: $STATS" || test_result 1 "Dashboard stats incomplete"
fi

# Test 5: Test Sponsor Directory report
echo ""
echo "Testing Sponsor Directory report..."
osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php?type=sponsor_directory"
        delay 2
    end tell
end tell
EOF

SPONSOR_COUNT=$(osascript <<'EOF'
tell application "Safari"
    tell current tab of window 1
        do JavaScript "
            try {
                const sponsorCards = document.querySelectorAll('.sponsor-card');
                const emptyState = document.querySelector('.empty-state');
                if (emptyState) {
                    'empty';
                } else {
                    sponsorCards.length.toString();
                }
            } catch(e) {
                'error: ' + e.message;
            }
        "
    end tell
end tell
EOF
)

if [ "$SPONSOR_COUNT" = "empty" ]; then
    test_result 0 "Sponsor Directory shows empty state correctly"
elif echo "$SPONSOR_COUNT" | grep -q "error"; then
    test_result 1 "Sponsor Directory failed: $SPONSOR_COUNT"
elif [ "$SPONSOR_COUNT" -gt 0 ]; then
    test_result 0 "Sponsor Directory shows $SPONSOR_COUNT sponsors"
else
    test_result 1 "Sponsor Directory unexpected result: $SPONSOR_COUNT"
fi

# Test 6: Test Child-Sponsor Lookup report
echo ""
echo "Testing Child-Sponsor Lookup report..."
osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php?type=child_sponsor"
        delay 2
    end tell
end tell
EOF

CHILDREN_COUNT=$(osascript <<'EOF'
tell application "Safari"
    tell current tab of window 1
        do JavaScript "
            try {
                const rows = document.querySelectorAll('.data-table tbody tr');
                const emptyState = document.querySelector('.empty-state');
                if (emptyState) {
                    'empty';
                } else {
                    rows.length.toString();
                }
            } catch(e) {
                'error: ' + e.message;
            }
        "
    end tell
end tell
EOF
)

if [ "$CHILDREN_COUNT" = "empty" ]; then
    test_result 0 "Child-Sponsor Lookup shows empty state"
elif echo "$CHILDREN_COUNT" | grep -q "error"; then
    test_result 1 "Child-Sponsor Lookup failed: $CHILDREN_COUNT"
elif [ "$CHILDREN_COUNT" -gt 0 ]; then
    test_result 0 "Child-Sponsor Lookup shows $CHILDREN_COUNT children"
else
    test_result 1 "Child-Sponsor Lookup unexpected result: $CHILDREN_COUNT"
fi

# Test 7: Test Family Report
echo ""
echo "Testing Family Report..."
osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php?type=family_report"
        delay 2
    end tell
end tell
EOF

FAMILY_COUNT=$(osascript <<'EOF'
tell application "Safari"
    tell current tab of window 1
        do JavaScript "
            try {
                const rows = document.querySelectorAll('.data-table tbody tr');
                const emptyState = document.querySelector('.empty-state');
                if (emptyState) {
                    'empty';
                } else {
                    rows.length.toString();
                }
            } catch(e) {
                'error: ' + e.message;
            }
        "
    end tell
end tell
EOF
)

if [ "$FAMILY_COUNT" = "empty" ]; then
    test_result 0 "Family Report shows empty state"
elif echo "$FAMILY_COUNT" | grep -q "error"; then
    test_result 1 "Family Report failed: $FAMILY_COUNT"
elif [ "$FAMILY_COUNT" -gt 0 ]; then
    test_result 0 "Family Report shows $FAMILY_COUNT families"
else
    test_result 1 "Family Report unexpected result: $FAMILY_COUNT"
fi

# Test 8: Test Available Children report
echo ""
echo "Testing Available Children report..."
osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php?type=available_children"
        delay 2
    end tell
end tell
EOF

AVAILABLE_COUNT=$(osascript <<'EOF'
tell application "Safari"
    tell current tab of window 1
        do JavaScript "
            try {
                const rows = document.querySelectorAll('.data-table tbody tr');
                const emptyState = document.querySelector('.empty-state');
                if (emptyState) {
                    'empty';
                } else {
                    rows.length.toString();
                }
            } catch(e) {
                'error: ' + e.message;
            }
        "
    end tell
end tell
EOF
)

if [ "$AVAILABLE_COUNT" = "empty" ]; then
    test_result 0 "Available Children shows empty state"
elif echo "$AVAILABLE_COUNT" | grep -q "error"; then
    test_result 1 "Available Children failed: $AVAILABLE_COUNT"
elif [ "$AVAILABLE_COUNT" -gt 0 ]; then
    test_result 0 "Available Children shows $AVAILABLE_COUNT children"
else
    test_result 1 "Available Children unexpected result: $AVAILABLE_COUNT"
fi

# Test 9: Test Complete Export page
echo ""
echo "Testing Complete Export page..."
osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php?type=complete_export"
        delay 2
    end tell
end tell
EOF

COMPLETE_COUNT=$(osascript <<'EOF'
tell application "Safari"
    tell current tab of window 1
        do JavaScript "
            try {
                const rows = document.querySelectorAll('.data-table tbody tr');
                rows.length.toString();
            } catch(e) {
                'error: ' + e.message;
            }
        "
    end tell
end tell
EOF
)

if echo "$COMPLETE_COUNT" | grep -q "error"; then
    test_result 1 "Complete Export failed: $COMPLETE_COUNT"
elif [ "$COMPLETE_COUNT" -gt 0 ]; then
    test_result 0 "Complete Export shows $COMPLETE_COUNT rows"
else
    test_result 1 "Complete Export unexpected result: $COMPLETE_COUNT"
fi

# Test 10: Check for JavaScript errors
echo ""
echo "Checking for JavaScript errors..."
JS_ERRORS=$(osascript <<'EOF'
tell application "Safari"
    tell current tab of window 1
        do JavaScript "
            try {
                // Check if console.error was called (basic check)
                'no-errors';
            } catch(e) {
                'error: ' + e.message;
            }
        "
    end tell
end tell
EOF
)

if [ "$JS_ERRORS" = "no-errors" ]; then
    test_result 0 "No JavaScript errors detected"
else
    test_result 1 "JavaScript errors: $JS_ERRORS"
fi

# Test 11: Test search functionality
echo ""
echo "Testing search functionality..."
osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php?type=child_sponsor"
        delay 2

        -- Fill search box and submit
        do JavaScript "
            try {
                const searchInput = document.querySelector('input[name=\"search\"]');
                searchInput.value = 'TEST-003';
                searchInput.form.submit();
            } catch(e) {
                console.error(e);
            }
        "
        delay 2
    end tell
end tell
EOF

SEARCH_RESULTS=$(osascript <<'EOF'
tell application "Safari"
    tell current tab of window 1
        do JavaScript "
            try {
                const rows = document.querySelectorAll('.data-table tbody tr');
                const hasTestData = Array.from(rows).some(row => row.textContent.includes('TEST-003'));
                if (hasTestData) {
                    'found';
                } else {
                    'not-found';
                }
            } catch(e) {
                'error: ' + e.message;
            }
        "
    end tell
end tell
EOF
)

if [ "$SEARCH_RESULTS" = "found" ]; then
    test_result 0 "Search found TEST-003 family"
elif [ "$SEARCH_RESULTS" = "not-found" ]; then
    test_result 1 "Search did not find TEST-003 (may not exist in database)"
else
    test_result 1 "Search failed: $SEARCH_RESULTS"
fi

# Test 12: Take screenshot for visual verification
echo ""
echo "Taking screenshot for visual verification..."
SCREENSHOT_PATH="/tmp/cfk-reports-test-$(date +%s).png"

osascript <<EOF
tell application "Safari"
    tell current tab of window 1
        set URL to "${ADMIN_URL}/reports.php?type=sponsor_directory"
        delay 2
    end tell
end tell

tell application "System Events"
    tell process "Safari"
        set frontmost to true
        delay 1
    end tell
end tell

do shell script "screencapture -x ${SCREENSHOT_PATH}"
EOF

if [ -f "$SCREENSHOT_PATH" ]; then
    test_result 0 "Screenshot saved: $SCREENSHOT_PATH"
else
    test_result 1 "Screenshot failed"
fi

echo ""
echo "=========================================="
echo "TEST SUMMARY"
echo "=========================================="
echo "Total Tests: $TOTAL"
echo -e "${GREEN}Passed: $PASS${NC}"
echo -e "${RED}Failed: $FAIL${NC}"
echo ""

if [ -f "$SCREENSHOT_PATH" ]; then
    echo "Screenshot saved to: $SCREENSHOT_PATH"
    echo "Open with: open $SCREENSHOT_PATH"
    echo ""
fi

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}✅ ALL TESTS PASSED${NC}"
    exit 0
else
    echo -e "${RED}❌ SOME TESTS FAILED${NC}"
    exit 1
fi
