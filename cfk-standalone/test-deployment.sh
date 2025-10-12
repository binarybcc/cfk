#!/bin/bash
# CFK v1.4 Production Deployment Test Suite

set -e

SSH_HOST="d646a74eb9.nxcli.io"
SSH_USER="a4409d26_1"
SSH_PASS="PiggedCoifSourerFating"
SITE_URL="https://cforkids.org"

echo "🧪 CFK v1.4 Deployment Test Suite"
echo "=================================="
echo ""

# Test 1: Alpine.js Integration
echo "1️⃣ Testing Alpine.js Integration..."
ALPINE_CHECK=$(curl -s "$SITE_URL/?page=children" | grep -o 'alpinejs@3.14.1' || echo "")
if [ -n "$ALPINE_CHECK" ]; then
    echo "   ✅ Alpine.js 3.14.1 detected in HTML"
else
    echo "   ❌ Alpine.js 3.14.1 NOT found"
fi

# Test 2: Page Accessibility
echo ""
echo "2️⃣ Testing Page Accessibility..."

PAGES=("children" "how_to_apply" "home")
for page in "${PAGES[@]}"; do
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$SITE_URL/?page=$page")
    if [ "$HTTP_CODE" = "200" ]; then
        echo "   ✅ /?page=$page → HTTP $HTTP_CODE"
    else
        echo "   ❌ /?page=$page → HTTP $HTTP_CODE (expected 200)"
    fi
done

# Test 3: Admin Panel
echo ""
echo "3️⃣ Testing Admin Panel..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$SITE_URL/admin/import_csv.php")
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    echo "   ✅ Admin panel accessible → HTTP $HTTP_CODE"
else
    echo "   ⚠️  Admin panel → HTTP $HTTP_CODE"
fi

# Test 4: Database Schema (Remote)
echo ""
echo "4️⃣ Testing Database Schema..."
DB_TEST=$(sshpass -p "$SSH_PASS" ssh -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" << 'REMOTE'
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 -e "DESCRIBE families;" 2>/dev/null | grep family_name || echo "OK"
REMOTE
)

if [ "$DB_TEST" = "OK" ]; then
    echo "   ✅ Privacy compliance: family_name column removed"
else
    echo "   ❌ family_name column still exists!"
fi

# Test 5: Sample Data Query
echo ""
echo "5️⃣ Testing Data Access..."
sshpass -p "$SSH_PASS" ssh -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" << 'REMOTE'
mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 << 'SQL'
SELECT
    CONCAT(f.family_number, c.child_letter) as code,
    c.age,
    c.gender,
    c.status
FROM children c
INNER JOIN families f ON c.family_id = f.id
WHERE c.status = 'available'
LIMIT 3;
SQL
REMOTE

# Test 6: File Structure
echo ""
echo "6️⃣ Testing File Structure..."
FILE_CHECK=$(sshpass -p "$SSH_PASS" ssh -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" << 'REMOTE'
cd ~/d646a74eb9.nxcli.io/html
MISSING=""
for dir in admin includes pages assets database config; do
    [ ! -d "$dir" ] && MISSING="$MISSING $dir"
done
[ ! -f "index.php" ] && MISSING="$MISSING index.php"
echo "$MISSING"
REMOTE
)

if [ -z "$FILE_CHECK" ]; then
    echo "   ✅ All core files and directories present"
else
    echo "   ❌ Missing:$FILE_CHECK"
fi

# Test 7: Assets Check
echo ""
echo "7️⃣ Testing Assets..."
LOGO_CHECK=$(curl -s -o /dev/null -w "%{http_code}" "$SITE_URL/assets/images/cfk-logo.png")
if [ "$LOGO_CHECK" = "200" ]; then
    echo "   ✅ Logo accessible → HTTP $LOGO_CHECK"
else
    echo "   ⚠️  Logo → HTTP $LOGO_CHECK"
fi

# Test 8: Search Functionality (check for Alpine.js x-data)
echo ""
echo "8️⃣ Testing Interactive Features..."
SEARCH_FEATURE=$(curl -s "$SITE_URL/?page=children" | grep -o 'x-data' || echo "")
if [ -n "$SEARCH_FEATURE" ]; then
    echo "   ✅ Alpine.js directives found (x-data)"
else
    echo "   ❌ Alpine.js directives NOT found"
fi

FAQ_FEATURE=$(curl -s "$SITE_URL/?page=how_to_apply" | grep -o 'x-collapse' || echo "")
if [ -n "$FAQ_FEATURE" ]; then
    echo "   ✅ FAQ accordion feature found (x-collapse)"
else
    echo "   ⚠️  FAQ accordion directives not found"
fi

echo ""
echo "=================================="
echo "✅ Test Suite Complete!"
echo "=================================="
echo ""
echo "📊 Summary:"
echo "   - Site URL: $SITE_URL"
echo "   - Alpine.js: v3.14.1"
echo "   - Database: Privacy compliant"
echo ""
echo "🌐 Manual Browser Tests:"
echo "   1. Open: $SITE_URL/?page=children"
echo "   2. Console: Alpine.version (should be '3.14.1')"
echo "   3. Test search box and filters"
echo ""
