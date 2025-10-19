#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Create Admin Account CLI Script
 * For magic link authentication (passwordless)
 *
 * Usage:
 *   php scripts/create-admin-account.php
 *   php scripts/create-admin-account.php --email=admin@example.com --name="John Doe" --role=admin
 */

// Security check
if (PHP_SAPI !== 'cli') {
    die('This script can only be run from the command line.');
}

// Load configuration
define('CFK_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Christmas for Kids - Create Admin Account                ║\n";
echo "║  Magic Link Authentication (Passwordless)                 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Parse command line arguments
$options = getopt('', ['email:', 'name:', 'role:', 'username:']);

// Interactive mode if no options provided
if (empty($options)) {
    echo "📋 Interactive Mode - Press Ctrl+C to cancel\n\n";

    // Get email
    echo "Email address (where magic links will be sent): ";
    $email = trim(fgets(STDIN));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("❌ Error: Invalid email address\n");
    }

    // Get full name
    echo "Full name: ";
    $fullName = trim(fgets(STDIN));

    if (empty($fullName)) {
        die("❌ Error: Full name is required\n");
    }

    // Get username (default to email prefix)
    $defaultUsername = explode('@', $email)[0];
    echo "Username (default: {$defaultUsername}): ";
    $username = trim(fgets(STDIN));
    if (empty($username)) {
        $username = $defaultUsername;
    }

    // Get role
    echo "Role (admin/editor, default: admin): ";
    $role = trim(fgets(STDIN));
    if (empty($role)) {
        $role = 'admin';
    }
} else {
    // Command line mode
    $email = $options['email'] ?? null;
    $fullName = $options['name'] ?? null;
    $username = $options['username'] ?? null;
    $role = $options['role'] ?? 'admin';

    if (!$email || !$fullName) {
        die("❌ Error: --email and --name are required\n");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("❌ Error: Invalid email address\n");
    }

    // Default username from email if not provided
    if (!$username) {
        $username = explode('@', $email)[0];
    }
}

// Validate role
if (!in_array($role, ['admin', 'editor'])) {
    die("❌ Error: Role must be 'admin' or 'editor'\n");
}

// Generate a random password hash (not used, but required by database schema)
$randomPasswordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);

// Check if username already exists
$existing = Database::fetchRow(
    "SELECT id, email FROM admin_users WHERE username = ?",
    [$username]
);

if ($existing) {
    echo "\n⚠️  Warning: Username '{$username}' already exists\n";
    echo "   Current email: {$existing['email']}\n";
    echo "   Do you want to update the email to '{$email}'? (yes/no): ";

    $confirm = trim(fgets(STDIN));
    if (strtolower($confirm) !== 'yes') {
        die("\n❌ Cancelled\n");
    }

    // Update existing account
    Database::execute(
        "UPDATE admin_users SET email = ?, full_name = ?, role = ? WHERE username = ?",
        [$email, $fullName, $role, $username]
    );

    echo "\n✅ Account updated successfully!\n";
} else {
    // Create new account
    Database::execute(
        "INSERT INTO admin_users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)",
        [$username, $email, $randomPasswordHash, $fullName, $role]
    );

    echo "\n✅ Account created successfully!\n";
}

// Display account details
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Account Details                                           ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";
echo "║  Username: " . str_pad($username, 49) . "║\n";
echo "║  Email:    " . str_pad($email, 49) . "║\n";
echo "║  Name:     " . str_pad($fullName, 49) . "║\n";
echo "║  Role:     " . str_pad($role, 49) . "║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "📧 Magic link login emails will be sent to: {$email}\n";
echo "🔗 Login URL: " . baseUrl('admin/login.php') . "\n";
echo "\n";
echo "🎄 The admin can now log in using the magic link sent to their email.\n";
echo "\n";

// Verify account in database
$account = Database::fetchRow(
    "SELECT id, username, email, role, created_at FROM admin_users WHERE username = ?",
    [$username]
);

if ($account) {
    echo "✓ Verified in database (ID: {$account['id']})\n";
} else {
    echo "⚠️  Warning: Could not verify account in database\n";
}

echo "\n";
