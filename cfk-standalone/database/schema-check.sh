#!/bin/bash
# Schema Validation Script
# Compares database schema against expected production schema
# Run before deployment to catch schema drift

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "🔍 Database Schema Validation"
echo "================================"
echo ""

# Detect environment
if [[ -f ".env.staging" ]] && [[ "$1" == "staging" ]]; then
    echo "Environment: STAGING"
    source .env.staging
elif [[ -f ".env" ]]; then
    echo "Environment: PRODUCTION"
    source .env
else
    echo -e "${RED}ERROR: No .env file found${NC}"
    exit 1
fi

echo "Host: $SSH_HOST"
echo ""

# Check if we can connect
echo "Testing database connection..."
CONN_TEST=$(sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT ${SSH_USER}@${SSH_HOST} "cd ${SSH_REMOTE_PATH} && mysql -h localhost -u \$(grep DB_USER .env | cut -d= -f2) -p\$(grep DB_PASS .env | cut -d= -f2) \$(grep DB_NAME .env | cut -d= -f2) -e 'SELECT 1;' 2>&1" || echo "FAILED")

if [[ "$CONN_TEST" == *"FAILED"* ]]; then
    echo -e "${RED}✗ Cannot connect to database${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Database connection successful${NC}"
echo ""

# Required tables
REQUIRED_TABLES=(
    "families"
    "children"
    "sponsorships"
    "admin_users"
    "settings"
    "reservations"
    "portal_access_tokens"
    "email_log"
    "admin_login_log"
    "admin_magic_links"
)

echo "Checking required tables..."
MISSING_TABLES=()

for table in "${REQUIRED_TABLES[@]}"; do
    TABLE_EXISTS=$(sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT ${SSH_USER}@${SSH_HOST} "cd ${SSH_REMOTE_PATH} && mysql -h localhost -u \$(grep DB_USER .env | cut -d= -f2) -p\$(grep DB_PASS .env | cut -d= -f2) \$(grep DB_NAME .env | cut -d= -f2) -e \"SHOW TABLES LIKE '$table';\"" 2>/dev/null | grep -c "$table" || echo "0")

    if [[ "$TABLE_EXISTS" == "0" ]]; then
        echo -e "${RED}✗ Missing table: $table${NC}"
        MISSING_TABLES+=("$table")
    else
        echo -e "${GREEN}✓ Table exists: $table${NC}"
    fi
done

echo ""

# Check children table schema
echo "Validating children table schema..."
CHILDREN_SCHEMA=$(sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT ${SSH_USER}@${SSH_HOST} "cd ${SSH_REMOTE_PATH} && mysql -h localhost -u \$(grep DB_USER .env | cut -d= -f2) -p\$(grep DB_PASS .env | cut -d= -f2) \$(grep DB_NAME .env | cut -d= -f2) -e 'DESCRIBE children;'" 2>/dev/null)

# Check for age_months column
if echo "$CHILDREN_SCHEMA" | grep -q "age_months"; then
    echo -e "${GREEN}✓ age_months column exists${NC}"
else
    echo -e "${RED}✗ Missing age_months column${NC}"
    echo -e "${YELLOW}  Action: Run migration 001-staging-to-production-schema.sql${NC}"
fi

# Check for name column
if echo "$CHILDREN_SCHEMA" | grep -q "name"; then
    echo -e "${GREEN}✓ name column exists${NC}"
else
    echo -e "${RED}✗ Missing name column${NC}"
    echo -e "${YELLOW}  Action: Run migration 001-staging-to-production-schema.sql${NC}"
fi

echo ""

# Check for WordPress table pollution
echo "Checking for WordPress table pollution..."
WP_COUNT=$(sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT ${SSH_USER}@${SSH_HOST} "cd ${SSH_REMOTE_PATH} && mysql -h localhost -u \$(grep DB_USER .env | cut -d= -f2) -p\$(grep DB_PASS .env | cut -d= -f2) \$(grep DB_NAME .env | cut -d= -f2) -e \"SHOW TABLES;\"" 2>/dev/null | grep -c -E "^(cfk_|wp_)" || echo "0")

if [[ "$WP_COUNT" -gt "0" ]]; then
    echo -e "${YELLOW}⚠ Found $WP_COUNT WordPress/WooCommerce tables${NC}"
    echo -e "${YELLOW}  Consider removing these tables for cleaner database${NC}"
else
    echo -e "${GREEN}✓ No WordPress table pollution${NC}"
fi

echo ""
echo "================================"

# Summary
if [[ ${#MISSING_TABLES[@]} -eq 0 ]] && echo "$CHILDREN_SCHEMA" | grep -q "age_months"; then
    echo -e "${GREEN}✅ Schema validation PASSED${NC}"
    echo "Database schema matches production requirements"
    exit 0
else
    echo -e "${RED}❌ Schema validation FAILED${NC}"
    echo ""
    echo "Issues found:"
    if [[ ${#MISSING_TABLES[@]} -gt 0 ]]; then
        echo "  - Missing tables: ${MISSING_TABLES[*]}"
    fi
    if ! echo "$CHILDREN_SCHEMA" | grep -q "age_months"; then
        echo "  - Missing age_months column in children table"
    fi
    if ! echo "$CHILDREN_SCHEMA" | grep -q "name"; then
        echo "  - Missing name column in children table"
    fi
    echo ""
    echo "To fix: Run database/migrations/001-staging-to-production-schema.sql"
    exit 1
fi
