#!/bin/bash
# CFK v1.4 Alpine.js - Production Deployment Script
# DO NOT run this script directly on production without testing!

set -e  # Exit on error

echo "=========================================="
echo "CFK v1.4 Alpine.js Deployment Package"
echo "=========================================="
echo ""

# Configuration
VERSION="1.4.0"
PACKAGE_NAME="cfk-v1.4-production.tar.gz"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Files and directories to include
INCLUDE_DIRS=(
    "admin"
    "assets"
    "includes"
    "pages"
    "database"
    "config"
    "src"
    "templates"
)

INCLUDE_FILES=(
    "index.php"
    "composer.json"
)

# Files to exclude (development/testing files)
EXCLUDE_PATTERNS=(
    "*.md"
    "test*.php"
    "*-test.php"
    "*.DS_Store"
    ".git*"
    ".claude-flow"
    "*.log"
    "*.tar.gz"
    "dry-run-test.csv"
    "cfksample*.csv"
    "CFK-upload*.csv"
    "docker-compose.yml"
    "phpunit.xml"
    "phinx.php"
)

echo "📦 Creating deployment package..."
echo ""

# Create temporary deployment directory
TEMP_DIR="deploy-temp-$TIMESTAMP"
mkdir -p "$TEMP_DIR"

# Copy included directories
for dir in "${INCLUDE_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "  ✓ Including $dir/"
        cp -r "$dir" "$TEMP_DIR/"
    fi
done

# Copy included files
for file in "${INCLUDE_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✓ Including $file"
        cp "$file" "$TEMP_DIR/"
    fi
done

# Remove excluded patterns from temp directory
echo ""
echo "🧹 Cleaning excluded files..."
for pattern in "${EXCLUDE_PATTERNS[@]}"; do
    find "$TEMP_DIR" -name "$pattern" -type f -delete 2>/dev/null || true
    find "$TEMP_DIR" -name "$pattern" -type d -exec rm -rf {} + 2>/dev/null || true
done

# Create deployment instructions file
cat > "$TEMP_DIR/DEPLOYMENT-INSTRUCTIONS.txt" << 'EOF'
CFK v1.4 Alpine.js - Deployment Instructions
=============================================

IMPORTANT: Read docs/V1.4-PRODUCTION-DEPLOYMENT.md for complete instructions!

Quick Deployment:
1. Backup database and files
2. Run database migration (remove family_name column)
3. Extract this package to production web root
4. Set file permissions (644 for files, 755 for directories)
5. Test Alpine.js loads: curl https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js
6. Verify in browser console: Alpine.version should return "3.14.1"

Questions? See docs/V1.4-PRODUCTION-DEPLOYMENT.md
EOF

# Create database migration SQL
mkdir -p "$TEMP_DIR/database/migrations"
cat > "$TEMP_DIR/database/migrations/v1.4_remove_family_name.sql" << 'SQLEOF'
-- v1.4 Migration: Remove family_name for privacy compliance
-- Run this BEFORE deploying v1.4 files

USE a4409d26_509946;

-- Check if family_name column exists
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE table_schema = DATABASE()
    AND table_name = 'families'
    AND column_name = 'family_name'
);

-- Drop column if it exists
SET @query = IF(@col_exists > 0,
    'ALTER TABLE families DROP COLUMN family_name;',
    'SELECT "Column family_name does not exist" AS message;'
);

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify schema
DESCRIBE families;

SELECT 'Migration complete: family_name column removed' AS status;
SQLEOF

# Create tarball
echo ""
echo "📦 Creating tarball: $PACKAGE_NAME"
tar -czf "$PACKAGE_NAME" -C "$TEMP_DIR" .

# Cleanup temp directory
rm -rf "$TEMP_DIR"

# Get package size
PACKAGE_SIZE=$(du -h "$PACKAGE_NAME" | cut -f1)

echo ""
echo "=========================================="
echo "✅ Deployment Package Created Successfully"
echo "=========================================="
echo ""
echo "Package: $PACKAGE_NAME"
echo "Size: $PACKAGE_SIZE"
echo "Version: $VERSION"
echo "Created: $TIMESTAMP"
echo ""
echo "📋 Next Steps:"
echo "1. Review: docs/V1.4-PRODUCTION-DEPLOYMENT.md"
echo "2. Test locally if possible"
echo "3. Upload to server: scp $PACKAGE_NAME user@server:~/"
echo "4. Follow deployment steps in documentation"
echo ""
echo "⚠️  IMPORTANT:"
echo "- Backup production database before deployment"
echo "- Backup production files before deployment"
echo "- Run database migration first"
echo "- Test after deployment"
echo ""
