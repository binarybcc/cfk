#!/bin/bash

###############################################################################
# v1.8-cleanup Branch Verification Script
# Tests autoloader functionality and ensures all deleted wrapper files
# are properly replaced by namespaced classes
###############################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counters
TESTS_RUN=0
TESTS_PASSED=0
TESTS_FAILED=0

# Log file
LOG_FILE="logs/v1.8-cleanup-verification.log"
mkdir -p logs
echo "=== v1.8-cleanup Verification Test Run: $(date) ===" > "$LOG_FILE"

###############################################################################
# Helper Functions
###############################################################################

print_header() {
    echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

print_test() {
    echo -e "${YELLOW}→${NC} $1"
    echo "TEST: $1" >> "$LOG_FILE"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
    echo "SUCCESS: $1" >> "$LOG_FILE"
    ((TESTS_PASSED++))
}

print_failure() {
    echo -e "${RED}✗${NC} $1"
    echo "FAILURE: $1" >> "$LOG_FILE"
    ((TESTS_FAILED++))
}

run_test() {
    ((TESTS_RUN++))
}

###############################################################################
# Test 1: Verify Deleted Files Don't Exist
###############################################################################

print_header "TEST 1: Verify Deleted Wrapper Files Are Gone"

DELETED_FILES=(
    "includes/sponsorship_manager.php"
    "includes/email_manager.php"
    "includes/csv_handler.php"
    "includes/archive_manager.php"
    "includes/report_manager.php"
    "includes/avatar_manager.php"
    "includes/backup_manager.php"
    "includes/import_analyzer.php"
    "includes/magic_link_manager.php"
)

for file in "${DELETED_FILES[@]}"; do
    run_test
    print_test "Checking $file is deleted"

    if [ -f "$file" ]; then
        print_failure "$file still exists (should be deleted)"
    else
        print_success "$file correctly deleted"
    fi
done

###############################################################################
# Test 2: Verify Autoloader Can Load All Namespaced Classes
###############################################################################

print_header "TEST 2: Autoloader Class Loading"

# Test via PHP CLI
print_test "Testing autoloader with PHP CLI"

php -r "
require_once 'config/config.php';

\$classes = [
    'CFK\\\\Sponsorship\\\\Manager',
    'CFK\\\\Auth\\\\MagicLinkManager',
    'CFK\\\\Avatar\\\\Manager',
    'CFK\\\\CSV\\\\Handler',
    'CFK\\\\Archive\\\\Manager',
    'CFK\\\\Report\\\\Manager',
    'CFK\\\\Backup\\\\Manager',
    'CFK\\\\Import\\\\Analyzer'
];

\$allLoaded = true;
foreach (\$classes as \$class) {
    if (class_exists(\$class)) {
        echo \"✓ \$class\n\";
    } else {
        echo \"✗ \$class FAILED\n\";
        \$allLoaded = false;
    }
}

exit(\$allLoaded ? 0 : 1);
" 2>> "$LOG_FILE"

if [ $? -eq 0 ]; then
    run_test
    print_success "All namespaced classes loaded successfully"
else
    run_test
    print_failure "Some classes failed to load via autoloader"
fi

###############################################################################
# Test 3: Verify Class Aliases Work
###############################################################################

print_header "TEST 3: Backward Compatibility Aliases"

print_test "Testing class_alias() backward compatibility"

php -r "
require_once 'config/config.php';

\$aliases = [
    'CFK_Sponsorship_Manager',
    'MagicLinkManager',
    'CFK_Avatar_Manager',
    'CFK_CSV_Handler',
    'CFK_Archive_Manager',
    'CFK_Report_Manager',
    'CFK_Backup_Manager',
    'CFK_Import_Analyzer'
];

\$allAliased = true;
foreach (\$aliases as \$alias) {
    if (class_exists(\$alias)) {
        echo \"✓ \$alias\n\";
    } else {
        echo \"✗ \$alias FAILED\n\";
        \$allAliased = false;
    }
}

exit(\$allAliased ? 0 : 1);
" 2>> "$LOG_FILE"

if [ $? -eq 0 ]; then
    run_test
    print_success "All backward compatibility aliases working"
else
    run_test
    print_failure "Some class aliases not working"
fi

###############################################################################
# Test 4: Verify No Broken Requires
###############################################################################

print_header "TEST 4: Check for Broken require_once Statements"

print_test "Searching for references to deleted files"

BROKEN_REQUIRES=0

for file in "${DELETED_FILES[@]}"; do
    # Search for require/include statements referencing deleted files
    MATCHES=$(grep -r "require.*$file" --include="*.php" --exclude-dir=vendor --exclude-dir=.git . 2>/dev/null | wc -l)

    if [ $MATCHES -gt 0 ]; then
        print_failure "Found $MATCHES references to deleted file: $file"
        grep -r "require.*$file" --include="*.php" --exclude-dir=vendor --exclude-dir=.git . 2>/dev/null
        ((BROKEN_REQUIRES++))
    fi
done

run_test
if [ $BROKEN_REQUIRES -eq 0 ]; then
    print_success "No broken require statements found"
else
    print_failure "Found $BROKEN_REQUIRES broken require statements"
fi

###############################################################################
# Test 5: Verify Cron Jobs Load Correctly
###############################################################################

print_header "TEST 5: Cron Job Execution"

CRON_JOBS=(
    "cron/cleanup_magic_links.php"
    "cron/cleanup_portal_tokens.php"
    "cron/cleanup_expired_sponsorships.php"
)

for cron_job in "${CRON_JOBS[@]}"; do
    run_test
    print_test "Dry-run: $cron_job"

    # Execute with syntax check only
    php -l "$cron_job" > /dev/null 2>&1

    if [ $? -eq 0 ]; then
        print_success "$cron_job syntax valid"
    else
        print_failure "$cron_job has syntax errors"
    fi
done

###############################################################################
# Test 6: Verify Key Functions Still Exist
###############################################################################

print_header "TEST 6: Critical Function Availability"

print_test "Checking critical static methods"

php -r "
require_once 'config/config.php';

\$methods = [
    ['CFK\\\\Sponsorship\\\\Manager', 'cleanupExpiredPendingSponsorships'],
    ['CFK\\\\Auth\\\\MagicLinkManager', 'cleanupExpiredTokens'],
    ['CFK\\\\Avatar\\\\Manager', 'getAvatarForChild'],
    ['CFK\\\\CSV\\\\Handler', 'validateCSVStructure'],
    ['CFK\\\\Report\\\\Manager', 'generateReport']
];

\$allExist = true;
foreach (\$methods as [\$class, \$method]) {
    if (method_exists(\$class, \$method)) {
        echo \"✓ \$class::\$method\n\";
    } else {
        echo \"✗ \$class::\$method NOT FOUND\n\";
        \$allExist = false;
    }
}

exit(\$allExist ? 0 : 1);
" 2>> "$LOG_FILE"

if [ $? -eq 0 ]; then
    run_test
    print_success "All critical methods available"
else
    run_test
    print_failure "Some critical methods missing"
fi

###############################################################################
# Test 7: Code Quality Check (PHPStan)
###############################################################################

print_header "TEST 7: Static Analysis (PHPStan)"

if command -v phpstan &> /dev/null; then
    run_test
    print_test "Running PHPStan analysis"

    phpstan analyze --level=5 --no-progress src/ 2>&1 | tee -a "$LOG_FILE"

    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        print_success "PHPStan analysis passed"
    else
        print_failure "PHPStan found issues"
    fi
else
    print_test "PHPStan not installed - skipping static analysis"
    echo "SKIPPED: PHPStan not available" >> "$LOG_FILE"
fi

###############################################################################
# Test 8: Integration Test - Run Existing Functional Tests
###############################################################################

print_header "TEST 8: Functional Test Suite"

if [ -f "tests/security-functional-tests.sh" ]; then
    run_test
    print_test "Running existing functional test suite"

    bash tests/security-functional-tests.sh 2>&1 | tee -a "$LOG_FILE"

    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        print_success "Functional tests passed"
    else
        print_failure "Some functional tests failed"
    fi
else
    print_test "Functional test suite not found - skipping"
    echo "SKIPPED: Functional tests not found" >> "$LOG_FILE"
fi

###############################################################################
# Summary Report
###############################################################################

print_header "TEST SUMMARY"

echo -e "Total Tests Run:    ${BLUE}${TESTS_RUN}${NC}"
echo -e "Tests Passed:       ${GREEN}${TESTS_PASSED}${NC}"
echo -e "Tests Failed:       ${RED}${TESTS_FAILED}${NC}"

PASS_RATE=$((TESTS_PASSED * 100 / TESTS_RUN))
echo -e "Pass Rate:          ${BLUE}${PASS_RATE}%${NC}"

echo -e "\nLog saved to: ${LOG_FILE}"

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "\n${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}  ✓ ALL TESTS PASSED - v1.8-cleanup Ready for Staging${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
    exit 0
else
    echo -e "\n${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${RED}  ✗ TESTS FAILED - Review errors before proceeding${NC}"
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
    exit 1
fi
