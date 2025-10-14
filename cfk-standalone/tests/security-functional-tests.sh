#!/bin/bash
# Security Functional Tests for CFK Sponsorship System
# Tests authentication, session management, and critical security flows
# Environment: Docker (http://localhost:8082)

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test configuration
BASE_URL="http://localhost:8082"
ADMIN_URL="$BASE_URL/admin"
COOKIES_FILE="/tmp/cfk_test_cookies.txt"
TEST_RESULTS="/tmp/cfk_test_results.txt"

# Clean up from previous runs
rm -f "$COOKIES_FILE" "$TEST_RESULTS"

echo "🔍 CFK Security Functional Tests"
echo "=================================="
echo ""

# Test counter
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Helper functions
pass_test() {
    echo -e "${GREEN}✅ PASS:${NC} $1"
    PASSED_TESTS=$((PASSED_TESTS + 1))
    echo "PASS: $1" >> "$TEST_RESULTS"
}

fail_test() {
    echo -e "${RED}❌ FAIL:${NC} $1"
    echo -e "   ${YELLOW}Expected: $2${NC}"
    echo -e "   ${YELLOW}Got: $3${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
    echo "FAIL: $1 | Expected: $2 | Got: $3" >> "$TEST_RESULTS"
}

warn_test() {
    echo -e "${YELLOW}⚠️  WARN:${NC} $1"
    echo "WARN: $1" >> "$TEST_RESULTS"
}

info_test() {
    echo -e "${BLUE}ℹ️  INFO:${NC} $1"
}

# Test 1: Homepage loads
echo "Test 1: Homepage Accessibility"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/")
if [ "$RESPONSE" -eq 200 ]; then
    pass_test "Homepage returns 200 OK"
else
    fail_test "Homepage accessibility" "200" "$RESPONSE"
fi
echo ""

# Test 2: Admin login page loads
echo "Test 2: Admin Login Page"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$ADMIN_URL/login.php")
if [ "$RESPONSE" -eq 200 ]; then
    pass_test "Admin login page returns 200 OK"
else
    fail_test "Admin login page accessibility" "200" "$RESPONSE"
fi
echo ""

# Test 3: Critical files exist
echo "Test 3: Critical File Existence"
CRITICAL_FILES=(
    "/admin/login.php"
    "/admin/logout.php"
    "/admin/index.php"
    "/admin/change_password.php"
    "/includes/rate_limiter.php"
    "/includes/sponsorship_manager.php"
    "/includes/functions.php"
    "/config/config.php"
)

for file in "${CRITICAL_FILES[@]}"; do
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL$file")
    if [ "$RESPONSE" -ne 404 ]; then
        pass_test "File exists: $file"
    else
        fail_test "File missing: $file" "200/30x" "404"
    fi
done
echo ""

# Test 4: Check for broken links in admin files
echo "Test 4: Admin Link Integrity"
info_test "Scanning admin PHP files for broken links..."
BROKEN_LINKS=0

# Extract links from admin files
docker exec cfk-web bash -c "cd /var/www/html/admin && grep -roh 'href=\"[^\"]*\.php\"' . | sed 's/href=\"//;s/\"//' | sort -u" > /tmp/cfk_admin_links.txt || true

while IFS= read -r link; do
    # Skip external links and anchors
    if [[ "$link" == http* ]] || [[ "$link" == \#* ]]; then
        continue
    fi

    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$ADMIN_URL/$link")

    if [ "$RESPONSE" -eq 404 ]; then
        fail_test "Broken link in admin: $link" "200/30x" "404"
        BROKEN_LINKS=$((BROKEN_LINKS + 1))
    else
        pass_test "Link OK: $link"
    fi
done < /tmp/cfk_admin_links.txt

if [ $BROKEN_LINKS -eq 0 ]; then
    info_test "No broken links found"
fi
echo ""

# Test 5: Session security headers
echo "Test 5: Session Security Configuration"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
HEADERS=$(curl -s -I "$ADMIN_URL/login.php")

if echo "$HEADERS" | grep -qi "Set-Cookie.*HttpOnly"; then
    pass_test "HttpOnly flag present in session cookie"
else
    warn_test "HttpOnly flag missing (check PHP session config)"
fi

if echo "$HEADERS" | grep -qi "Set-Cookie.*SameSite"; then
    pass_test "SameSite attribute present"
else
    warn_test "SameSite attribute missing (check PHP 7.3+ session config)"
fi
echo ""

# Test 6: CSRF token generation
echo "Test 6: CSRF Token Generation"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
LOGIN_PAGE=$(curl -s "$ADMIN_URL/login.php")

if echo "$LOGIN_PAGE" | grep -q "csrf_token"; then
    pass_test "CSRF token field present in login form"
else
    fail_test "CSRF token generation" "csrf_token field present" "not found"
fi
echo ""

# Test 7: Rate limiting functionality
echo "Test 7: Rate Limiting (Simulated)"
info_test "Testing rate limiter class existence..."
TOTAL_TESTS=$((TOTAL_TESTS + 1))

RATE_LIMITER_CHECK=$(docker exec cfk-web php -r "
define('CFK_APP', true);
session_start();
require_once '/var/www/html/includes/rate_limiter.php';
echo class_exists('RateLimiter') ? 'OK' : 'MISSING';
" 2>&1)

if echo "$RATE_LIMITER_CHECK" | grep -q "OK"; then
    pass_test "RateLimiter class loads successfully"
else
    fail_test "RateLimiter class" "OK" "$RATE_LIMITER_CHECK"
fi
echo ""

# Test 8: Database connection
echo "Test 8: Database Connection"
TOTAL_TESTS=$((TOTAL_TESTS + 1))

DB_TEST=$(docker exec cfk-web php -r "
define('CFK_APP', true);
try {
    require_once '/var/www/html/config/config.php';
    \$conn = Database::getConnection();
    echo 'OK';
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" 2>&1)

if echo "$DB_TEST" | grep -q "OK"; then
    pass_test "Database connection successful"
else
    fail_test "Database connection" "OK" "$DB_TEST"
fi
echo ""

# Test 9: Required database tables exist
echo "Test 9: Database Schema Validation"
REQUIRED_TABLES=(
    "admin_users"
    "children"
    "families"
    "sponsorships"
    "reservations"
    "portal_access_tokens"
)

for table in "${REQUIRED_TABLES[@]}"; do
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    TABLE_CHECK=$(docker exec cfk-mysql mysql -ucfk_user -pcfk_pass cfk_sponsorship_dev -e "SHOW TABLES LIKE '$table';" 2>&1)

    if echo "$TABLE_CHECK" | grep -q "$table"; then
        pass_test "Table exists: $table"
    else
        fail_test "Table missing: $table" "table exists" "not found"
    fi
done
echo ""

# Test 10: Password change page accessible
echo "Test 10: Password Change Functionality"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$ADMIN_URL/change_password.php")

if [ "$RESPONSE" -eq 200 ] || [ "$RESPONSE" -eq 302 ]; then
    pass_test "Password change page accessible"
else
    fail_test "Password change page" "200 or 302" "$RESPONSE"
fi
echo ""

# Test 11: Logout functionality exists
echo "Test 11: Logout Endpoint"
TOTAL_TESTS=$((TOTAL_TESTS + 1))
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$ADMIN_URL/logout.php")

if [ "$RESPONSE" -eq 302 ] || [ "$RESPONSE" -eq 200 ]; then
    pass_test "Logout endpoint exists and responds"
else
    fail_test "Logout endpoint" "200 or 302" "$RESPONSE (404 = missing file)"
fi
echo ""

# Test 12: Session timeout configuration
echo "Test 12: Session Configuration"
TOTAL_TESTS=$((TOTAL_TESTS + 1))

SESSION_CONFIG=$(docker exec cfk-web php -r "
echo 'Lifetime: ' . ini_get('session.gc_maxlifetime') . PHP_EOL;
echo 'Cookie Lifetime: ' . ini_get('session.cookie_lifetime') . PHP_EOL;
echo 'Cookie HttpOnly: ' . ini_get('session.cookie_httponly') . PHP_EOL;
echo 'Cookie Secure: ' . ini_get('session.cookie_secure') . PHP_EOL;
echo 'Cookie SameSite: ' . ini_get('session.cookie_samesite') . PHP_EOL;
" 2>&1)

info_test "Session configuration:"
echo "$SESSION_CONFIG" | while IFS= read -r line; do
    echo "   $line"
done

if echo "$SESSION_CONFIG" | grep -q "HttpOnly: 1"; then
    pass_test "Session HttpOnly enabled"
else
    warn_test "Session HttpOnly disabled (security risk)"
fi
echo ""

# Test 13: Environment file handling
echo "Test 13: Environment Configuration"
TOTAL_TESTS=$((TOTAL_TESTS + 1))

ENV_TEST=$(docker exec cfk-web bash -c "
if [ -f /var/www/html/.env ]; then
    echo 'EXISTS'
    ls -l /var/www/html/.env | awk '{print \$1}'
else
    echo 'MISSING'
fi
" 2>&1)

if echo "$ENV_TEST" | grep -q "EXISTS"; then
    pass_test ".env file exists"

    PERMS=$(echo "$ENV_TEST" | grep -o "^-.*" | head -1)
    if [ "$PERMS" = "-rw-------" ] || [ "$PERMS" = "-r--------" ]; then
        pass_test ".env file permissions secure ($PERMS)"
    else
        warn_test ".env file permissions should be 600 or 400 (got: $PERMS)"
    fi
else
    warn_test ".env file missing (using fallback values)"
fi
echo ""

# Test 14: Verify no hardcoded credentials in config
echo "Test 14: Credential Security Check"
TOTAL_TESTS=$((TOTAL_TESTS + 1))

HARDCODED_CHECK=$(docker exec cfk-web grep -n "password.*=.*['\"].*['\"]" /var/www/html/config/config.php | grep -v "getenv\|DB_PASSWORD" || echo "OK")

if [ "$HARDCODED_CHECK" = "OK" ]; then
    pass_test "No obvious hardcoded passwords in config.php"
else
    warn_test "Potential hardcoded credentials found (review manually)"
    echo "$HARDCODED_CHECK"
fi
echo ""

# Final Summary
echo "=================================="
echo "📊 Test Summary"
echo "=================================="
echo -e "Total Tests: ${BLUE}$TOTAL_TESTS${NC}"
echo -e "Passed: ${GREEN}$PASSED_TESTS${NC}"
echo -e "Failed: ${RED}$FAILED_TESTS${NC}"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}✅ All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}❌ $FAILED_TESTS test(s) failed${NC}"
    echo ""
    echo "📋 Full results saved to: $TEST_RESULTS"
    exit 1
fi
