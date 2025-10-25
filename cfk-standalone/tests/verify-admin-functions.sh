#!/bin/bash

# Verify all admin page functions are working on staging
# Tests: Database structure, class loading, and critical operations

set -e

echo "=== Staging Admin Functions Verification ==="
echo ""

# SSH credentials from .env
SSH_USER="ac6c9a98_1"
SSH_HOST="10ce79bd48.nxcli.io"
SSH_PASS="WhitFezMunchWooer"
DB_USER="ac6c9a98_509946"
DB_PASS="GlobeCofferLeafedAstral"
DB_NAME="ac6c9a98_509946"

echo "1. Testing Database Tables..."
sshpass -p "$SSH_PASS" ssh -p 22 ${SSH_USER}@${SSH_HOST} \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -e 'SHOW TABLES;' 2>&1" | grep -E "children|families|sponsorships|admin_users" || echo "❌ Missing tables"

echo ""
echo "2. Testing Children Table Structure..."
sshpass -p "$SSH_PASS" ssh -p 22 ${SSH_USER}@${SSH_HOST} \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -e 'DESCRIBE children;' 2>&1" | grep -E "status|sponsored_by" || echo "❌ Missing columns"

echo ""
echo "3. Testing Sponsorships Table Structure..."
sshpass -p "$SSH_PASS" ssh -p 22 ${SSH_USER}@${SSH_HOST} \
    "mysql -u $DB_USER -p$DB_PASS $DB_NAME -e 'DESCRIBE sponsorships;' 2>&1" | grep -E "status|logged_date" || echo "❌ Missing columns"

echo ""
echo "4. Testing PHP Autoloader..."
sshpass -p "$SSH_PASS" ssh -p 22 ${SSH_USER}@${SSH_HOST} \
    "cd 10ce79bd48.nxcli.io/html && php -r \"
define('CFK_APP', true);
require 'config/config.php';
\\\$classes = [
    'CFK\\\Sponsorship\\\Manager',
    'CFK\\\Database\\\Connection',
    'CFK\\\CSV\\\Handler',
    'CFK\\\Avatar\\\Manager'
];
foreach (\\\$classes as \\\$class) {
    echo \\\$class . ': ' . (class_exists(\\\$class) ? '✓' : '✗') . PHP_EOL;
}
\""

echo ""
echo "5. Testing Sponsorship Manager Methods..."
sshpass -p "$SSH_PASS" ssh -p 22 ${SSH_USER}@${SSH_HOST} \
    "cd 10ce79bd48.nxcli.io/html && php -r \"
define('CFK_APP', true);
require 'config/config.php';
\\\$methods = get_class_methods('CFK\\\Sponsorship\\\Manager');
echo 'Available methods: ' . count(\\\$methods) . PHP_EOL;
\\\$required = ['logSponsorship', 'unlogSponsorship', 'completeSponsorship', 'cancelSponsorship'];
foreach (\\\$required as \\\$method) {
    echo '  ' . \\\$method . ': ' . (in_array(\\\$method, \\\$methods) ? '✓' : '✗') . PHP_EOL;
}
\""

echo ""
echo "6. Testing CSV Handler..."
sshpass -p "$SSH_PASS" ssh -p 22 ${SSH_USER}@${SSH_HOST} \
    "cd 10ce79bd48.nxcli.io/html && php -r \"
define('CFK_APP', true);
require 'config/config.php';
\\\$methods = get_class_methods('CFK\\\CSV\\\Handler');
echo 'CSV Handler methods: ' . count(\\\$methods) . PHP_EOL;
\""

echo ""
echo "7. Checking Admin Pages Exist..."
for page in manage_children.php manage_sponsorships.php import_csv.php reports.php
do
    sshpass -p "$SSH_PASS" ssh -p 22 ${SSH_USER}@${SSH_HOST} \
        "test -f 10ce79bd48.nxcli.io/html/admin/$page && echo '✓ $page' || echo '✗ $page missing'"
done

echo ""
echo "=== Verification Complete ==="
