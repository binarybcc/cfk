#!/bin/bash

################################################################################
# Admin Panel Migration - Smoke Test
#
# Quick verification that critical routes are working after Week 8-9 migration
# Tests: redirects, route accessibility, basic responses
################################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="${BASE_URL:-http://localhost:8080}"
PASSED=0
FAILED=0
TESTS=()

# Helper functions
pass() {
    echo -e "${GREEN}✓${NC} $1"
    ((PASSED++))
}

fail() {
    echo -e "${RED}✗${NC} $1"
    ((FAILED++))
}

info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

warn() {
    echo -e "${YELLOW}⚠${NC} $1"
}

header() {
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# Test HTTP redirect
test_redirect() {
    local url="$1"
    local expected_status="$2"
    local description="$3"

    local response=$(curl -s -o /dev/null -w "%{http_code}" -L "$url")

    if [ "$response" = "$expected_status" ]; then
        pass "$description (HTTP $response)"
    else
        fail "$description (Expected $expected_status, got $response)"
    fi
}

# Test URL accessibility
test_url() {
    local url="$1"
    local expected_status="$2"
    local description="$3"

    local response=$(curl -s -o /dev/null -w "%{http_code}" "$url")

    if [ "$response" = "$expected_status" ]; then
        pass "$description (HTTP $response)"
    else
        fail "$description (Expected $expected_status, got $response)"
    fi
}

# Test content contains string
test_content() {
    local url="$1"
    local search_string="$2"
    local description="$3"

    local content=$(curl -s "$url")

    if echo "$content" | grep -q "$search_string"; then
        pass "$description"
    else
        fail "$description (String not found: $search_string)"
    fi
}

################################################################################
# BEGIN TESTS
################################################################################

header "Admin Panel Migration - Smoke Test"
info "Testing against: $BASE_URL"
info "Date: $(date)"
echo ""

# Check if server is running
info "Checking if server is accessible..."
if ! curl -s "$BASE_URL" > /dev/null; then
    echo ""
    fail "Server not accessible at $BASE_URL"
    echo ""
    echo "Please start the server first:"
    echo "  cd cfk-standalone && php -S localhost:8080 -t ."
    echo ""
    exit 1
fi
pass "Server is accessible"

################################################################################
header "1. Legacy Redirects (301 Permanent)"
################################################################################

test_redirect "$BASE_URL/admin/index.php" "301" "Dashboard redirect"
test_redirect "$BASE_URL/admin/login.php" "301" "Login redirect"
test_redirect "$BASE_URL/admin/logout.php" "301" "Logout redirect"
test_redirect "$BASE_URL/admin/manage_children.php" "301" "Children management redirect"
test_redirect "$BASE_URL/admin/manage_sponsorships.php" "301" "Sponsorships management redirect"
test_redirect "$BASE_URL/admin/manage_admins.php" "301" "Admin users redirect"
test_redirect "$BASE_URL/admin/import_csv.php" "301" "CSV import redirect"
test_redirect "$BASE_URL/admin/year_end_reset.php" "301" "Year-end reset redirect"
test_redirect "$BASE_URL/admin/reports.php" "301" "Reports redirect"
test_redirect "$BASE_URL/admin/request-magic-link.php" "301" "Request magic link redirect"
test_redirect "$BASE_URL/admin/magic-link-sent.php" "301" "Magic link sent redirect"

################################################################################
header "2. Slim Routes (Unauthenticated)"
################################################################################

# These should return 200 (login page) or 302 (redirect to login)
test_url "$BASE_URL/admin/login" "200" "Login page accessible"
test_url "$BASE_URL/admin/dashboard" "302" "Dashboard redirects when not authenticated"
test_url "$BASE_URL/admin/children" "302" "Children page redirects when not authenticated"
test_url "$BASE_URL/admin/sponsorships" "302" "Sponsorships page redirects when not authenticated"
test_url "$BASE_URL/admin/users" "302" "Admin users page redirects when not authenticated"
test_url "$BASE_URL/admin/import" "302" "Import page redirects when not authenticated"
test_url "$BASE_URL/admin/archive" "302" "Archive page redirects when not authenticated"
test_url "$BASE_URL/admin/reports" "302" "Reports page redirects when not authenticated"

################################################################################
header "3. Authentication Pages"
################################################################################

test_url "$BASE_URL/admin/login" "200" "Login page loads"
test_content "$BASE_URL/admin/login" "Magic Link" "Login page shows magic link text"
test_content "$BASE_URL/admin/login" "Passwordless Login" "Login page shows passwordless text"

test_url "$BASE_URL/admin/auth/magic-link-sent" "200" "Magic link sent page loads"
test_content "$BASE_URL/admin/auth/magic-link-sent" "Check Your Email" "Magic link sent page shows instruction"

################################################################################
header "4. Public Routes"
################################################################################

test_url "$BASE_URL/" "200" "Homepage loads"
test_url "$BASE_URL/children" "200" "Public children page loads"

################################################################################
header "5. Static Assets"
################################################################################

test_url "$BASE_URL/assets/css/styles.css" "200" "Main CSS file loads"

################################################################################
header "6. API Endpoints (Should require authentication)"
################################################################################

# POST endpoints should reject unauthenticated requests
test_url "$BASE_URL/admin/auth/request-magic-link" "405" "Request magic link rejects GET (requires POST)"

################################################################################
header "7. File Structure Validation"
################################################################################

# Check that migrated controllers exist
if [ -f "src/Controller/AdminChildController.php" ]; then
    pass "AdminChildController exists"
else
    fail "AdminChildController not found"
fi

if [ -f "src/Controller/AdminSponsorshipController.php" ]; then
    pass "AdminSponsorshipController exists"
else
    fail "AdminSponsorshipController not found"
fi

if [ -f "src/Controller/AdminUserController.php" ]; then
    pass "AdminUserController exists"
else
    fail "AdminUserController not found"
fi

if [ -f "src/Controller/AdminAuthController.php" ]; then
    pass "AdminAuthController exists"
else
    fail "AdminAuthController not found"
fi

if [ -f "src/Controller/AdminImportController.php" ]; then
    pass "AdminImportController exists"
else
    fail "AdminImportController not found"
fi

if [ -f "src/Controller/AdminArchiveController.php" ]; then
    pass "AdminArchiveController exists"
else
    fail "AdminArchiveController not found"
fi

# Check that legacy files have been converted to redirects
if [ $(wc -l < "admin/index.php") -lt 30 ]; then
    pass "admin/index.php is a redirect (< 30 lines)"
else
    fail "admin/index.php is still full file"
fi

if [ $(wc -l < "admin/login.php") -lt 30 ]; then
    pass "admin/login.php is a redirect (< 30 lines)"
else
    fail "admin/login.php is still full file"
fi

# Check that obsolete files were removed
if [ ! -f "admin/ajax_handler.php" ]; then
    pass "admin/ajax_handler.php removed"
else
    fail "admin/ajax_handler.php still exists"
fi

if [ ! -f "admin/ajax_sponsorship_action.php" ]; then
    pass "admin/ajax_sponsorship_action.php removed"
else
    fail "admin/ajax_sponsorship_action.php still exists"
fi

if [ ! -d "admin/includes" ]; then
    pass "admin/includes directory removed"
else
    fail "admin/includes directory still exists"
fi

################################################################################
# SUMMARY
################################################################################

echo ""
header "Test Summary"
echo ""
echo -e "Total Tests: $((PASSED + FAILED))"
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✓ All smoke tests passed!${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    info "Migration appears successful. Proceed with full testing."
    echo ""
    exit 0
else
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${RED}✗ Some tests failed${NC}"
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    warn "Please review failures above and fix issues."
    echo ""
    exit 1
fi
