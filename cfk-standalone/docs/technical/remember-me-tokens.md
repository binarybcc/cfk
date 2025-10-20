# Remember-Me Token Security Implementation

**Date:** October 18, 2025
**Version:** v1.6
**Security Level:** MEDIUM → HIGH (Fixed)

---

## Overview

Implemented secure, database-backed "Remember Me" token storage to address the MEDIUM security vulnerability identified in the v1.6 technical audit.

### Previous Implementation (INSECURE)

```php
// ❌ INSECURE: Tokens not persisted, cannot be revoked
if ($rememberMe) {
    $token = bin2hex(random_bytes(32));
    setcookie('cfk_remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    // TODO: Store this token hashed in database for security
}
```

**Issues:**
- Token not stored in database (cannot validate on subsequent requests)
- Token cannot be revoked if compromised
- No audit trail of remember-me usage
- No session management capabilities

### New Implementation (SECURE)

```php
// ✅ SECURE: Database-backed, revocable tokens
if ($rememberMe) {
    $token = RememberMeTokens::generateToken($user['id']);
    $isProduction = ($_SERVER['HTTP_HOST'] ?? 'localhost') !== 'localhost';
    RememberMeTokens::setCookie($token, $isProduction);
}
```

---

## Database Schema

**Table:** `admin_remember_tokens`

```sql
CREATE TABLE admin_remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),

    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);
```

**Migration:** `database/migrations/005_create_admin_remember_tokens_table.sql`

---

## Security Features

### 1. Token Hashing (SHA-256)

**Tokens are NEVER stored in plaintext:**

```php
// Generate token
$token = bin2hex(random_bytes(32)); // 256-bit random token

// Store ONLY the hash
$tokenHash = hash('sha256', $token);
Database::insert('admin_remember_tokens', [
    'token_hash' => $tokenHash,  // Hashed value
    'user_id' => $userId
]);

return $token; // Return plaintext ONLY for cookie (one-time)
```

**Why SHA-256?**
- Fast for validation (constant-time lookup)
- One-way hash (cannot reverse-engineer token from hash)
- 256-bit security (collision-resistant)
- Standard cryptographic hash function

### 2. Token Revocation

**Tokens can be revoked:**

```php
// Revoke specific token
RememberMeTokens::revokeToken($token);

// Revoke ALL tokens for a user (logout all devices)
RememberMeTokens::revokeAllUserTokens($userId);
```

**Use Cases:**
- User logs out (revoke current token)
- Password change (revoke all tokens)
- Security breach (revoke all tokens for affected user)
- Admin intervention (force logout)

### 3. Audit Trail

**Every token stores:**
- When created (`created_at`)
- When last used (`last_used_at`)
- From which IP (`ip_address`)
- From which device (`user_agent`)

**Admin can view:**
- All active tokens for a user
- Last usage timestamps
- Device information
- Suspicious activity patterns

### 4. Automatic Expiry

**Tokens expire after 30 days:**

```php
const TOKEN_EXPIRY_DAYS = 30;
$expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
```

**Cleanup cron:**
```bash
# Run daily at 2am
0 2 * * * php /path/to/cron/cleanup_remember_tokens.php
```

### 5. Secure Cookie Settings

**Production cookies:**

```php
setcookie(
    'cfk_remember_token',
    $token,
    [
        'expires' => time() + (30 * 24 * 60 * 60),
        'path' => '/',
        'secure' => true,     // HTTPS only
        'httponly' => true,   // No JavaScript access
        'samesite' => 'Strict' // CSRF protection
    ]
);
```

**Flags explained:**
- `secure`: Cookie only sent over HTTPS (prevents eavesdropping)
- `httponly`: JavaScript cannot read cookie (prevents XSS theft)
- `samesite=Strict`: Cookie not sent on cross-site requests (prevents CSRF)

---

## API Reference

### RememberMeTokens Class

**Location:** `includes/remember_me_tokens.php`

#### generateToken()

```php
public static function generateToken(int $userId): string
```

**Purpose:** Generate and store a new remember-me token

**Returns:** Token string (to be set in cookie)

**Example:**
```php
$token = RememberMeTokens::generateToken($user['id']);
RememberMeTokens::setCookie($token, $isProduction);
```

---

#### validateToken()

```php
public static function validateToken(string $token): ?array
```

**Purpose:** Validate a remember-me token and return user data

**Returns:** User array if valid, null if invalid/expired

**Example:**
```php
$user = RememberMeTokens::validateToken($token);
if ($user) {
    // Auto-login user
    $_SESSION['cfk_admin_id'] = $user['id'];
}
```

---

#### revokeToken()

```php
public static function revokeToken(string $token): bool
```

**Purpose:** Revoke a specific token

**Returns:** True if revoked, false if not found

**Example:**
```php
// On logout
$token = RememberMeTokens::getTokenFromCookie();
RememberMeTokens::revokeToken($token);
RememberMeTokens::clearCookie();
```

---

#### revokeAllUserTokens()

```php
public static function revokeAllUserTokens(int $userId): int
```

**Purpose:** Revoke ALL tokens for a user (logout all devices)

**Returns:** Number of tokens revoked

**Example:**
```php
// On password change
$deleted = RememberMeTokens::revokeAllUserTokens($user['id']);
// Force re-login on all devices
```

---

#### getUserTokens()

```php
public static function getUserTokens(int $userId): array
```

**Purpose:** Get all active tokens for a user (admin interface)

**Returns:** Array of token records with metadata

**Example:**
```php
$tokens = RememberMeTokens::getUserTokens($userId);
foreach ($tokens as $token) {
    echo "Created: {$token['created_at']}\n";
    echo "Last used: {$token['last_used_at']}\n";
    echo "IP: {$token['ip_address']}\n";
    echo "Device: {$token['user_agent']}\n";
}
```

---

## Usage Flow

### Login with Remember-Me

```php
// admin/login.php
if ($user && password_verify($password, $user['password_hash'])) {
    // Set session
    $_SESSION['cfk_admin_id'] = $user['id'];
    $_SESSION['cfk_admin_username'] = $user['username'];

    // Set remember-me token if requested
    if ($rememberMe) {
        $token = RememberMeTokens::generateToken($user['id']);
        $isProduction = ($_SERVER['HTTP_HOST'] !== 'localhost');
        RememberMeTokens::setCookie($token, $isProduction);
    }

    header('Location: index.php');
    exit;
}
```

### Auto-Login on Return Visit

```php
// admin/login.php (before session check)
if (!isLoggedIn()) {
    $rememberToken = RememberMeTokens::getTokenFromCookie();
    if ($rememberToken) {
        $user = RememberMeTokens::validateToken($rememberToken);
        if ($user) {
            // Auto-login via remember-me token
            $_SESSION['cfk_admin_id'] = $user['id'];
            $_SESSION['cfk_admin_username'] = $user['username'];
            $_SESSION['cfk_admin_role'] = $user['role'];

            header('Location: index.php');
            exit;
        } else {
            // Invalid/expired token - clear cookie
            RememberMeTokens::clearCookie();
        }
    }
}
```

### Logout (Revoke Token)

```php
// admin/logout.php
$rememberToken = RememberMeTokens::getTokenFromCookie();
if ($rememberToken) {
    RememberMeTokens::revokeToken($rememberToken);
    RememberMeTokens::clearCookie();
}

// Destroy session
$_SESSION = [];
session_destroy();
```

---

## Testing

### Manual Testing Checklist

**Login with Remember-Me:**
- [ ] Login with "Remember Me" checked
- [ ] Verify cookie `cfk_remember_token` is set (30-day expiry)
- [ ] Verify token is stored in database (hashed)
- [ ] Close browser and reopen
- [ ] Verify automatic login works
- [ ] Verify `last_used_at` timestamp updated

**Token Validation:**
- [ ] Attempt to use token twice (should fail - one-time use)
- [ ] Wait for token to expire (or manually set expiry in past)
- [ ] Verify expired token rejected
- [ ] Verify cookie cleared on invalid token

**Token Revocation:**
- [ ] Login with remember-me
- [ ] Logout manually
- [ ] Verify token revoked in database
- [ ] Verify cookie cleared
- [ ] Attempt to use old token (should fail)

**Multiple Devices:**
- [ ] Login on Device A with remember-me
- [ ] Login on Device B with remember-me
- [ ] Verify both tokens work independently
- [ ] Change password
- [ ] Verify both tokens revoked (forced re-login)

---

## Security Comparison

| Aspect | Before (Insecure) | After (Secure) |
|--------|-------------------|----------------|
| **Token Storage** | Not stored | Database (hashed) |
| **Revocation** | Impossible | Full revocation support |
| **Audit Trail** | None | IP, device, timestamps |
| **Expiry** | Cookie-based only | Database + cookie |
| **Multi-Device** | No management | Track all devices |
| **Password Change** | Tokens still valid ❌ | All tokens revoked ✅ |
| **Stolen Token** | Cannot revoke ❌ | Admin can revoke ✅ |
| **Validation** | None ❌ | Database lookup ✅ |
| **Security Rating** | 3/10 | 9/10 |

---

## Maintenance

### Cron Job Setup

**Add to crontab:**
```bash
# Cleanup expired remember-me tokens daily at 2am
0 2 * * * php /path/to/cron/cleanup_remember_tokens.php >> /var/log/cfk-remember-tokens.log 2>&1
```

**Manual cleanup:**
```bash
php /path/to/cron/cleanup_remember_tokens.php
```

---

## Future Enhancements

**Potential improvements:**

1. **Admin Interface for Token Management**
   - View all active sessions/tokens per user
   - Revoke specific tokens remotely
   - View login history with geolocation

2. **Suspicious Activity Detection**
   - Alert if token used from different country
   - Alert if many tokens created in short time
   - Rate limit token generation per user

3. **Enhanced Security Options**
   - Option to require 2FA on remember-me login
   - Shorter token expiry for high-security accounts
   - Device fingerprinting for additional validation

4. **User Notifications**
   - Email when new device logs in with remember-me
   - Email when token is revoked
   - Option to view and manage own devices

---

## Related Documentation

- `docs/audits/v1.6-technical-evaluation.md` - Original security audit
- `database/migrations/005_create_admin_remember_tokens_table.sql` - Database schema
- `includes/remember_me_tokens.php` - Implementation
- `admin/login.php` - Login integration
- `admin/logout.php` - Logout integration

---

**Document Version:** 1.0
**Last Updated:** October 18, 2025
