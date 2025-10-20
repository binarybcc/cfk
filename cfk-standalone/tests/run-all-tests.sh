#!/bin/bash

# Master Test Runner for CFK Reports System
# Runs all automated tests in sequence

set -e

echo "╔════════════════════════════════════════════════════════╗"
echo "║  CFK REPORTS - COMPREHENSIVE TEST SUITE               ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

TOTAL_PASS=0
TOTAL_FAIL=0
SUITE_PASS=0
SUITE_FAIL=0

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Test Suite 1: Database & HTTP Tests (No authentication required)
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST SUITE 1: DATABASE & HTTP (Automated)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo ""

if [ -f "$SCRIPT_DIR/automated-report-tests.sh" ]; then
    chmod +x "$SCRIPT_DIR/automated-report-tests.sh"
    if "$SCRIPT_DIR/automated-report-tests.sh"; then
        echo -e "${GREEN}✅ Suite 1 PASSED${NC}"
        SUITE_PASS=$((SUITE_PASS + 1))
    else
        echo -e "${RED}❌ Suite 1 FAILED${NC}"
        SUITE_FAIL=$((SUITE_FAIL + 1))
    fi
else
    echo -e "${YELLOW}⚠️  automated-report-tests.sh not found${NC}"
    SUITE_FAIL=$((SUITE_FAIL + 1))
fi

echo ""
echo ""

# Test Suite 2: Browser-Based Tests (Requires credentials)
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST SUITE 2: BROWSER TESTING (AppleScript)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo ""

# Check if credentials are configured
if [ -f "$SCRIPT_DIR/applescript-browser-tests.sh" ]; then
    # Check if credentials are set in the script
    if grep -q 'ADMIN_PASS=""' "$SCRIPT_DIR/applescript-browser-tests.sh"; then
        echo -e "${YELLOW}⚠️  SKIPPED: Admin credentials not configured${NC}"
        echo ""
        echo "To enable browser tests:"
        echo "  1. Edit: $SCRIPT_DIR/applescript-browser-tests.sh"
        echo "  2. Set ADMIN_USER and ADMIN_PASS variables"
        echo "  3. Run this script again"
        echo ""
        SUITE_FAIL=$((SUITE_FAIL + 1))
    else
        chmod +x "$SCRIPT_DIR/applescript-browser-tests.sh"
        if "$SCRIPT_DIR/applescript-browser-tests.sh"; then
            echo -e "${GREEN}✅ Suite 2 PASSED${NC}"
            SUITE_PASS=$((SUITE_PASS + 1))
        else
            echo -e "${RED}❌ Suite 2 FAILED${NC}"
            SUITE_FAIL=$((SUITE_FAIL + 1))
        fi
    fi
else
    echo -e "${YELLOW}⚠️  applescript-browser-tests.sh not found${NC}"
    SUITE_FAIL=$((SUITE_FAIL + 1))
fi

echo ""
echo ""

# Test Suite 3: CSV Export Tests (Requires credentials)
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST SUITE 3: CSV EXPORTS (AppleScript)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo ""

if [ -f "$SCRIPT_DIR/applescript-csv-tests.sh" ]; then
    # Check if credentials are set
    if grep -q 'ADMIN_PASS=""' "$SCRIPT_DIR/applescript-csv-tests.sh"; then
        echo -e "${YELLOW}⚠️  SKIPPED: Admin credentials not configured${NC}"
        echo ""
        echo "To enable CSV tests:"
        echo "  1. Edit: $SCRIPT_DIR/applescript-csv-tests.sh"
        echo "  2. Set ADMIN_USER and ADMIN_PASS variables"
        echo "  3. Run this script again"
        echo ""
        SUITE_FAIL=$((SUITE_FAIL + 1))
    else
        chmod +x "$SCRIPT_DIR/applescript-csv-tests.sh"
        if "$SCRIPT_DIR/applescript-csv-tests.sh"; then
            echo -e "${GREEN}✅ Suite 3 PASSED${NC}"
            SUITE_PASS=$((SUITE_PASS + 1))
        else
            echo -e "${RED}❌ Suite 3 FAILED${NC}"
            SUITE_FAIL=$((SUITE_FAIL + 1))
        fi
    fi
else
    echo -e "${YELLOW}⚠️  applescript-csv-tests.sh not found${NC}"
    SUITE_FAIL=$((SUITE_FAIL + 1))
fi

echo ""
echo ""

# Final Summary
echo "╔════════════════════════════════════════════════════════╗"
echo "║  FINAL SUMMARY                                         ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""
echo "Test Suites:"
echo -e "  ${GREEN}Passed: $SUITE_PASS${NC}"
echo -e "  ${RED}Failed: $SUITE_FAIL${NC}"
echo ""

if [ $SUITE_FAIL -eq 0 ]; then
    echo -e "${GREEN}╔════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║  ✅  ALL TEST SUITES PASSED  ✅                        ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════╝${NC}"
    exit 0
else
    echo -e "${RED}╔════════════════════════════════════════════════════════╗${NC}"
    echo -e "${RED}║  ❌  SOME TEST SUITES FAILED  ❌                       ║${NC}"
    echo -e "${RED}╚════════════════════════════════════════════════════════╝${NC}"

    if grep -q 'ADMIN_PASS=""' "$SCRIPT_DIR/applescript-browser-tests.sh" 2>/dev/null; then
        echo ""
        echo -e "${YELLOW}💡 TIP: Configure admin credentials to run browser and CSV tests${NC}"
    fi

    exit 1
fi
