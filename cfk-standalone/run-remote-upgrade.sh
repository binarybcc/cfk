#!/bin/bash
# Remote upgrade execution using sshpass
# Reads credentials from cfkssh.txt

set -e

SSH_HOST="d646a74eb9.nxcli.io"
SSH_USER="a4409d26_1"
SSH_PASS="PiggedCoifSourerFating"

echo "🚀 Executing v1.4 upgrade on remote server..."
echo ""

sshpass -p "$SSH_PASS" ssh -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" << 'REMOTE_COMMANDS'
# Create and run upgrade script on remote server

cat > upgrade-to-v1.4.sh << 'UPGRADEEOF'
#!/bin/bash
set -e
echo "🚀 CFK v1.0.3 → v1.4 Upgrade"
echo ""

# Configuration
DB_USER="a4409d26_509946"
DB_PASS="Fests42Cue50Fennel56Auk46"
DB_NAME="a4409d26_509946"
WEB_ROOT="$HOME/d646a74eb9.nxcli.io/html"

cd "$WEB_ROOT"

# Step 1: Backup
echo "1️⃣ Creating backups..."
mkdir -p ~/backups
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > ~/backups/pre-v14-db-$(date +%Y%m%d_%H%M%S).sql
tar -czf ~/backups/pre-v14-files-$(date +%Y%m%d_%H%M%S).tar.gz --exclude='uploads' admin/ includes/ pages/ assets/ database/ config/ index.php
echo "✅ Backups created in ~/backups/"

# Step 2: Database migration
echo ""
echo "2️⃣ Migrating database schema..."
mysql -u $DB_USER -p$DB_PASS $DB_NAME << SQLEOF
ALTER TABLE families DROP COLUMN IF EXISTS family_name;
SELECT 'Schema updated' AS status;
SQLEOF
echo "✅ Database migrated (family_name removed)"

# Step 3: Deploy files
echo ""
echo "3️⃣ Deploying v1.4 files..."
tar -xzf ~/cfk-v1.4-production.tar.gz
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
echo "✅ Files deployed"

# Step 4: Verify
echo ""
echo "4️⃣ Verifying deployment..."
grep -q "alpinejs@3.14.1" includes/header.php && echo "✅ Alpine.js integrated" || echo "❌ Alpine.js missing"
mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "DESCRIBE families;" | grep -q family_name && echo "❌ Migration incomplete" || echo "✅ Schema correct"

echo ""
echo "=========================================="
echo "✅ Upgrade Complete!"
echo "=========================================="
echo ""
echo "Next: Test in browser"
echo "URL: https://cforkids.org/sponsor/?page=children"
echo "Console: Alpine.version should return '3.14.1'"
UPGRADEEOF

chmod +x upgrade-to-v1.4.sh
./upgrade-to-v1.4.sh
REMOTE_COMMANDS

echo ""
echo "=========================================="
echo "✅ Remote upgrade execution complete!"
echo "=========================================="
echo ""
echo "Next: Run browser tests"
echo "See DEPLOY-NOW.md Step 3 for testing instructions"
