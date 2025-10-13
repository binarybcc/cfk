# Email Implementation Options for Christmas for Kids

**Date:** October 13, 2025
**Status:** Research & Planning

## Executive Summary

Two viable options for reliable email delivery:
1. **Google Workspace SMTP** - Professional, reliable, requires OAuth2 (2025 requirement)
2. **Nexcess Native SMTP** - Simpler setup, uses hosting provider's infrastructure

---

## Option 1: Google Workspace SMTP

### Overview
Google Workspace provides enterprise-grade email delivery with high deliverability rates and advanced features.

### 🚨 CRITICAL 2025 CHANGES

**Effective March 14, 2025:**
- ❌ Basic authentication (username/password) **NO LONGER SUPPORTED**
- ❌ "Less secure apps" feature **REMOVED**
- ✅ OAuth2 authentication **REQUIRED** for most use cases
- ✅ App Passwords available for devices/apps that can't use OAuth2
- ✅ SMTP Relay with IP authentication available (no password needed)

### Three Implementation Methods

#### Method 1A: SMTP Relay with IP Authentication (RECOMMENDED)
**Best for: Server-to-server communication**

**Advantages:**
- ✅ No password/OAuth required
- ✅ Authenticates by IP address
- ✅ Simplest to implement
- ✅ Most reliable for automated systems
- ✅ Up to 10,000 recipients per day per user

**Configuration:**
```
SMTP Host: smtp-relay.gmail.com
Port: 587 (TLS) or 465 (SSL) or 25 (non-encrypted)
Authentication: None (IP-based)
Encryption: TLS (recommended)
```

**Requirements:**
1. Google Workspace Admin access
2. Add your Nexcess server IP to allowed relay list in Google Admin Console
3. Configure "Allowed senders" in Gmail SMTP relay settings

**Setup Steps:**
1. Go to Google Admin Console → Apps → Google Workspace → Gmail → Routing
2. Enable SMTP relay service
3. Add Nexcess server IP address to allowed IPs
4. Choose "Only addresses in my domains" for allowed senders
5. Enable "Require TLS encryption"

**PHP Configuration:**
```php
$appConfig = [
    'email_use_smtp' => true,
    'smtp_host' => 'smtp-relay.gmail.com',
    'smtp_port' => 587,
    'smtp_auth' => false, // IP authentication
    'smtp_encryption' => 'tls',
    'smtp_username' => '', // Not needed for IP auth
    'smtp_password' => '', // Not needed for IP auth
    'from_email' => 'noreply@cforkids.org',
    'from_name' => 'Christmas for Kids',
];
```

---

#### Method 1B: OAuth2 Authentication (Most Secure)
**Best for: User-initiated actions, interactive applications**

**Advantages:**
- ✅ Most secure method
- ✅ Complies with 2025 Google security requirements
- ✅ Supports user delegation
- ✅ Granular permission control

**Disadvantages:**
- ❌ Complex implementation
- ❌ Requires token refresh logic
- ❌ Need to manage OAuth credentials

**Requirements:**
1. Google Cloud Project
2. OAuth 2.0 Client ID and Secret
3. PHPMailer with OAuth2 support
4. `league/oauth2-google` package

**Implementation Steps:**
1. Create Google Cloud Project
2. Enable Gmail API
3. Create OAuth 2.0 credentials (Web application)
4. Add authorized redirect URIs
5. Install dependencies: `composer require league/oauth2-google`
6. Implement token management

**Code Example:**
```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->Port = 587;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->SMTPAuth = true;
$mail->AuthType = 'XOAUTH2';

$provider = new Google([
    'clientId' => 'YOUR_CLIENT_ID',
    'clientSecret' => 'YOUR_CLIENT_SECRET',
]);

$mail->setOAuth(
    new OAuth([
        'provider' => $provider,
        'clientId' => 'YOUR_CLIENT_ID',
        'clientSecret' => 'YOUR_CLIENT_SECRET',
        'refreshToken' => 'YOUR_REFRESH_TOKEN',
        'userName' => 'noreply@cforkids.org',
    ])
);
```

---

#### Method 1C: App Passwords (Legacy Support)
**Best for: Temporary solution, devices that don't support OAuth2**

**Advantages:**
- ✅ Simpler than OAuth2
- ✅ Works with existing PHPMailer setup
- ✅ No code changes needed

**Disadvantages:**
- ❌ Less secure than OAuth2
- ❌ Requires 2-Step Verification enabled
- ❌ Each user needs individual app password
- ❌ Google may deprecate this method in future

**Requirements:**
1. Google Workspace account with 2-Step Verification enabled
2. Generate 16-character app password from Google Account settings

**Setup Steps:**
1. Go to Google Account → Security → 2-Step Verification
2. Scroll to "App passwords"
3. Select "Mail" and "Other (Custom name)"
4. Generate password (16 characters, no spaces)
5. Use this password in SMTP settings

**Configuration:**
```php
$appConfig = [
    'email_use_smtp' => true,
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_auth' => true,
    'smtp_username' => 'noreply@cforkids.org',
    'smtp_password' => 'xxxx xxxx xxxx xxxx', // 16-char app password
    'smtp_encryption' => 'tls',
];
```

---

## Option 2: Nexcess Native SMTP

### Overview
Use Nexcess hosting provider's built-in email infrastructure.

### Advantages
- ✅ Already available (included with hosting)
- ✅ Simple configuration
- ✅ No external dependencies
- ✅ Standard SMTP authentication
- ✅ Works with PHP mail() function

### Disadvantages
- ❌ May have lower deliverability than Google
- ❌ Shared IP reputation with other customers
- ❌ May have sending limits
- ❌ SPF/DKIM configuration required for best delivery

### Configuration

**SMTP Settings:**
```
SMTP Host: mail.cforkids.org (or host.cforkids.org)
Port: 587 (TLS recommended) or 465 (SSL)
Authentication: Required
Username: Full email address (noreply@cforkids.org)
Password: Email account password
Encryption: TLS (port 587) or SSL (port 465)
```

**PHP Configuration:**
```php
$appConfig = [
    'email_use_smtp' => true,
    'smtp_host' => 'mail.cforkids.org',
    'smtp_port' => 587,
    'smtp_auth' => true,
    'smtp_username' => 'noreply@cforkids.org',
    'smtp_password' => 'your_email_password',
    'smtp_encryption' => 'tls',
    'from_email' => 'noreply@cforkids.org',
    'from_name' => 'Christmas for Kids',
];
```

### PHP mail() Function Alternative

**If SMTP doesn't work, use PHP's native mail() function:**

```php
// In email_manager.php, keep the fallback mailer
private static function getFallbackMailer(): object {
    return new class {
        public $Subject = '';
        public $Body = '';
        public $AltBody = '';
        private $to = [];
        private $from = ['email' => '', 'name' => ''];

        public function send(): bool {
            $headers = "From: " . ($this->from['name'] ?
                $this->from['name'] . ' <' . $this->from['email'] . '>' :
                $this->from['email']) . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "MIME-Version: 1.0\r\n";

            foreach ($this->to as $recipient) {
                $toAddress = $recipient['name'] ?
                    $recipient['name'] . ' <' . $recipient['email'] . '>' :
                    $recipient['email'];

                if (!mail($toAddress, $this->Subject, $this->Body, $headers)) {
                    return false;
                }
            }
            return true;
        }
    };
}
```

### SPF Record Configuration
**To improve deliverability, add SPF record to DNS:**

```
TXT Record:
v=spf1 include:_spf.google.com include:relay.mailchannels.net ~all
```

Or for Nexcess only:
```
v=spf1 mx a ~all
```

---

## Comparison Matrix

| Feature | Google SMTP Relay | Google OAuth2 | Google App Pass | Nexcess SMTP |
|---------|------------------|---------------|-----------------|--------------|
| **Setup Complexity** | Low | High | Medium | Low |
| **Deliverability** | Excellent | Excellent | Excellent | Good |
| **Security** | High | Highest | Medium | Medium |
| **Reliability** | Excellent | Excellent | Excellent | Good |
| **Cost** | Workspace cost | Workspace cost | Workspace cost | Included |
| **Maintenance** | Low | Medium | Low | Low |
| **2025 Compliant** | ✅ Yes | ✅ Yes | ⚠️ Temporary | ✅ Yes |
| **Daily Limit** | 10,000 | 2,000 | 2,000 | Varies |
| **Best For** | Automated emails | User actions | Legacy apps | Simple sites |

---

## Recommended Implementation Plan

### Immediate (Best Option): Google Workspace SMTP Relay

**Why:**
1. ✅ No password management needed
2. ✅ Highest deliverability
3. ✅ Simplest to implement
4. ✅ Most reliable
5. ✅ 2025-compliant
6. ✅ 10,000 emails/day limit

**Action Items:**
1. Contact Google Workspace admin for cforkids.org
2. Get Nexcess server IP address
3. Add IP to Gmail SMTP relay allowed list
4. Update config.php with smtp-relay.gmail.com settings
5. Test email sending
6. Monitor delivery rates

### Fallback: Nexcess Native SMTP

**If Google Workspace not available:**
1. Create noreply@cforkids.org email account in Nexcess
2. Get SMTP credentials from Nexcess
3. Configure mail.cforkids.org as SMTP host
4. Add SPF records to DNS
5. Test and monitor

---

## Implementation Steps (Google SMTP Relay)

### Step 1: Get Server IP
```bash
# SSH into Nexcess server
curl ifconfig.me
# Save this IP address
```

### Step 2: Configure Google Admin Console
1. Log into admin.google.com
2. Navigate to Apps → Google Workspace → Gmail → Routing
3. Click "SMTP relay service"
4. Click "Add another" or edit existing
5. Add Nexcess IP to "Allowed senders"
6. Select "Only addresses in my domains"
7. Check "Require SMTP Authentication" (optional)
8. Check "Require TLS encryption"
9. Save

### Step 3: Update config.php
```php
// In config/config.php
$appConfig = [
    // ... other settings ...

    'email_use_smtp' => true,
    'smtp_host' => 'smtp-relay.gmail.com',
    'smtp_port' => 587,
    'smtp_auth' => false, // IP authentication
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',

    'from_email' => 'noreply@cforkids.org',
    'from_name' => 'Christmas for Kids',
    'admin_email' => 'admin@cforkids.org',
];
```

### Step 4: Test Email Sending
```bash
# Create test script at admin/test_final_email.php
```

### Step 5: Monitor & Verify
1. Check email delivery logs
2. Test spam folder placement
3. Verify SPF/DKIM alignment
4. Monitor bounce rates

---

## Troubleshooting

### Google SMTP Relay Issues

**"Relay access denied"**
- Verify server IP is in allowed list
- Check "Allowed senders" settings
- Ensure sending from domain email address

**"Connection timeout"**
- Verify port 587 is not blocked by firewall
- Try port 465 (SSL) or 25 (non-encrypted)
- Check Nexcess firewall settings

**"Authentication required"**
- Verify "Require SMTP Authentication" setting
- Add credentials if auth is enabled
- Use service account if needed

### Nexcess SMTP Issues

**"SMTP Error: Could not connect"**
- Verify SMTP host (mail.cforkids.org or host.cforkids.org)
- Check port (587 or 465)
- Verify email account exists

**Emails going to spam**
- Add SPF record to DNS
- Configure DKIM
- Use consistent "From" address
- Avoid spam trigger words

---

## Security Best Practices

1. **Never commit credentials to git**
   - Use environment variables
   - Store in config files outside web root

2. **Rotate passwords regularly**
   - Change SMTP passwords every 90 days
   - Regenerate app passwords annually

3. **Monitor for abuse**
   - Log all email sends
   - Watch for unusual patterns
   - Set up alerts for failures

4. **Use rate limiting**
   - Prevent email bombing
   - Limit sends per IP/session

---

## Cost Analysis

### Google Workspace
- **Cost:** $6-18/user/month (Business plans)
- **Includes:** Email, Drive, Meet, Calendar
- **Value:** Professional email infrastructure

### Nexcess Email
- **Cost:** Included with hosting
- **Limits:** May vary by plan
- **Value:** No additional cost

---

## Next Steps

1. **Decision Point:** Choose Google SMTP Relay or Nexcess SMTP
2. **Get Access:** Gather admin credentials needed
3. **Implement:** Follow step-by-step guide above
4. **Test:** Verify email delivery works
5. **Monitor:** Track delivery rates and issues
6. **Document:** Record configuration for team

---

## Contact Information

**Google Workspace Support:**
- Admin Console: admin.google.com
- Support: support.google.com/a

**Nexcess Support:**
- Portal: portal.nexcess.net
- Support: support.nexcess.net
- Phone: Available in portal

---

**Document Version:** 1.0
**Last Updated:** October 13, 2025
**Prepared by:** Claude Code
