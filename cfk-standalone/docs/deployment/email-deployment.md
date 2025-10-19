# Email Deployment Guide - MailChannels via Nexcess

Complete guide for configuring professional email delivery using MailChannels through Nexcess hosting.

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [MailChannels Configuration](#mailchannels-configuration)
3. [DNS Setup](#dns-setup)
4. [Application Configuration](#application-configuration)
5. [Testing](#testing)
6. [Monitoring](#monitoring)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### From Nexcess Support

Contact Nexcess support to get:
- ✅ SMTP username (usually your domain or email address)
- ✅ SMTP password (API key or generated password)
- ✅ Confirmation that MailChannels is enabled on your account
- ✅ Any IP restrictions or authentication requirements

### Your SPF Record (Already Configured)
```
v=spf1 +a +mx +ip4:199.189.224.131 include:relay.mailchannels.net ~all
```
✅ This includes MailChannels relay service

---

## MailChannels Configuration

### 1. SMTP Settings

**Already configured in `config/config.php`:**

```php
'email_use_smtp' => $isProduction, // Automatic: SMTP in production
'smtp_host' => 'relay.mailchannels.net',
'smtp_port' => 587,
'smtp_auth' => true,
'smtp_encryption' => 'tls',
```

### 2. Set Credentials (Choose One Method)

#### Method A: Environment Variables (Recommended ✅)

Create `.env` file in project root:
```bash
SMTP_USERNAME=your_username_from_nexcess
SMTP_PASSWORD=your_password_from_nexcess
```

Add to `.gitignore`:
```
.env
```

#### Method B: Direct Configuration (Less Secure)

Edit `config/config.php`:
```php
'smtp_username' => 'your_username@cforkids.org',
'smtp_password' => 'your_mailchannels_api_key',
```

⚠️ **Never commit credentials to version control!**

---

## DNS Setup

### Current SPF Record ✅
Your SPF is already configured:
```
v=spf1 +a +mx +ip4:199.189.224.131 include:relay.mailchannels.net ~all
```

### Add DKIM Record (Required for Best Deliverability)

**Get DKIM keys from Nexcess:**
1. Contact Nexcess support
2. Request DKIM TXT records for your domain
3. They'll provide something like:

```
Host: default._domainkey.cforkids.org
Type: TXT
Value: v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBA...
```

**Add to DNS:**
- Log into your DNS provider (Nexcess, Cloudflare, etc.)
- Create new TXT record with provided values
- Wait 24-48 hours for propagation

### Add DMARC Record (Recommended)

**Create DMARC policy:**
```
Host: _dmarc.cforkids.org
Type: TXT
Value: v=DMARC1; p=quarantine; rua=mailto:dmarc@cforkids.org; pct=100; adkim=r; aspf=r
```

**What this means:**
- `p=quarantine` - Put suspicious emails in spam (use `p=reject` once confident)
- `rua=mailto:dmarc@cforkids.org` - Send aggregate reports here
- `pct=100` - Apply to 100% of emails
- `adkim=r` - Relaxed DKIM alignment
- `aspf=r` - Relaxed SPF alignment

---

## Application Configuration

### 1. Update Production Settings

Edit `config/config.php` for production:

```php
// Use your actual domain
'base_url' => 'https://www.cforkids.org/sponsor/',

// Use your verified email addresses
'from_email' => 'noreply@cforkids.org', // Must be @cforkids.org
'admin_email' => 'admin@cforkids.org',
'from_name' => 'Christmas for Kids',
```

### 2. Set Up Cron Job

**On Nexcess server, add to crontab:**

```bash
# Edit crontab
crontab -e

# Add this line (adjust paths):
*/5 * * * * /usr/bin/php /home/username/public_html/sponsor/cron/process_email_queue.php >> /home/username/logs/email_queue.log 2>&1

# Cleanup old emails daily
0 2 * * * /usr/bin/php /home/username/public_html/sponsor/cron/cleanup_emails.php >> /home/username/logs/email_cleanup.log 2>&1
```

### 3. Create Cleanup Script

Create `cron/cleanup_emails.php`:
```php
<?php
define('CFK_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/email_queue.php';

echo "[" . date('Y-m-d H:i:s') . "] Cleaning up old emails\n";

// Delete sent/failed emails older than 30 days
$deleted = CFK_Email_Queue::cleanup(30);

echo "Deleted {$deleted} old email records\n";
echo "[" . date('Y-m-d H:i:s') . "] Cleanup complete\n";
```

### 4. File Permissions

```bash
chmod 755 cron/process_email_queue.php
chmod 755 cron/cleanup_emails.php
chmod 600 .env  # If using .env file
```

---

## Testing

### 1. Test SMTP Connection

Create `test_email.php` in project root:

```php
<?php
define('CFK_APP', true);
require_once 'config/config.php';
require_once 'includes/email_manager.php';

echo "Testing MailChannels SMTP connection...\n\n";

$result = CFK_Email_Manager::testEmailConfig();

if ($result['success']) {
    echo "✅ SUCCESS: {$result['message']}\n";
    echo "Check your admin email inbox: " . config('admin_email') . "\n";
} else {
    echo "❌ FAILED: {$result['message']}\n";
}
```

Run test:
```bash
php test_email.php
```

### 2. Test Email Queue

```php
<?php
define('CFK_APP', true);
require_once 'config/config.php';
require_once 'includes/email_queue.php';

// Queue test email
$queueId = CFK_Email_Queue::queue(
    'your@email.com',
    'Test Email from CFK',
    '<h1>Test Email</h1><p>This is a test from the queue system.</p>',
    ['priority' => 'high']
);

echo "Email queued with ID: {$queueId}\n";
echo "Run the cron processor to send it.\n";
```

### 3. Manual Queue Processing

```bash
php cron/process_email_queue.php
```

Expected output:
```
[2025-10-03 18:30:00] Email Queue Processing Started
Processed: 1
Sent: 1
Failed: 0

Queue Status:
  Queued: 0
  Sent: 1
  Failed: 0
```

---

## Monitoring

### Check Queue Status

Create `admin/email_status.php`:

```php
<?php
// Admin authentication required
require_once '../includes/email_queue.php';

$stats = CFK_Email_Queue::getStats();
?>
<h2>Email Queue Status</h2>
<table>
    <tr><td>Queued:</td><td><?= $stats['queued'] ?></td></tr>
    <tr><td>Processing:</td><td><?= $stats['processing'] ?></td></tr>
    <tr><td>Sent:</td><td><?= $stats['sent'] ?></td></tr>
    <tr><td>Failed:</td><td><?= $stats['failed'] ?></td></tr>
</table>
```

### Monitor Cron Logs

```bash
# View email queue log
tail -f /home/username/logs/email_queue.log

# View cleanup log
tail -f /home/username/logs/email_cleanup.log
```

### Check Failed Emails

```sql
-- In database
SELECT * FROM email_queue
WHERE status = 'failed'
ORDER BY queued_at DESC
LIMIT 10;
```

### Retry Failed Emails

```php
// In admin panel or CLI
CFK_Email_Queue::retryFailed(10); // Retry up to 10 failed emails
```

---

## Email Deliverability Best Practices

### ✅ DO:

1. **Use Your Domain**
   - FROM: `noreply@cforkids.org` ✅
   - NOT: `noreply@gmail.com` ❌

2. **Consistent Sender Info**
   - Same "from" address for all emails
   - Same "from" name

3. **Proper Email Structure**
   - Include plain text alternative
   - Valid HTML structure
   - Unsubscribe link (if marketing)

4. **Warm Up Sending**
   - Start with low volume
   - Gradually increase over 2-4 weeks
   - Monitor bounce rates

5. **Clean Lists**
   - Remove bounced emails
   - Validate email addresses
   - Honor unsubscribes

### ❌ DON'T:

1. Send from free email providers (Gmail, Yahoo, etc.)
2. Use misleading subject lines
3. Include too many links
4. Use spammy words (FREE, $$$, Click Here!)
5. Send without proper DNS records

---

## Troubleshooting

### Issue: "Authentication failed"

**Cause:** Wrong SMTP credentials

**Solution:**
1. Verify username/password from Nexcess
2. Check environment variables are loaded
3. Test with hardcoded values temporarily

### Issue: "Connection timeout"

**Cause:** Firewall blocking port 587

**Solution:**
1. Try port 2525 (MailChannels alternative)
2. Contact Nexcess to verify port access
3. Check server firewall rules

### Issue: Emails going to spam

**Causes:**
- Missing DKIM record ❌
- SPF not aligned ❌
- Poor sender reputation ❌

**Solutions:**
1. ✅ Add DKIM record (get from Nexcess)
2. ✅ Verify SPF includes MailChannels
3. ✅ Add DMARC record
4. ✅ Warm up sending gradually
5. ✅ Use reputable content (no spam words)

### Issue: "Sender address rejected"

**Cause:** FROM address not verified

**Solution:**
- Use only `@cforkids.org` addresses
- Verify domain ownership with Nexcess
- Ensure domain DNS is properly configured

---

## Production Checklist

Before going live:

- [ ] MailChannels credentials configured
- [ ] SMTP settings verified in production
- [ ] SPF record confirmed (already done ✅)
- [ ] DKIM record added
- [ ] DMARC record added
- [ ] Cron job configured (every 5 minutes)
- [ ] Cleanup cron configured (daily)
- [ ] Test email sent successfully
- [ ] Queue processing tested
- [ ] Monitoring set up
- [ ] Log rotation configured
- [ ] Admin dashboard shows queue stats
- [ ] FROM address uses @cforkids.org
- [ ] Bounce handling configured

---

## Quick Reference

### Configuration Files
- **Config:** `config/config.php`
- **Email Manager:** `includes/email_manager.php`
- **Queue System:** `includes/email_queue.php`
- **Cron Processor:** `cron/process_email_queue.php`

### Key Functions

```php
// Queue email (recommended)
CFK_Email_Queue::queue($email, $subject, $body, $options);

// Queue sponsor confirmation
CFK_Email_Queue::queueSponsorConfirmation($sponsorship);

// Queue admin notification
CFK_Email_Queue::queueAdminNotification($subject, $message);

// Get queue stats
$stats = CFK_Email_Queue::getStats();

// Retry failed
CFK_Email_Queue::retryFailed(10);

// Cleanup old emails
CFK_Email_Queue::cleanup(30);
```

### Support Contacts

- **Nexcess Support:** [Support Portal](https://www.nexcess.net/support/)
- **MailChannels Docs:** [MailChannels Documentation](https://mailchannels.zendesk.com/)
- **DNS Help:** Your DNS provider support

---

## Next Steps

1. ✅ Get SMTP credentials from Nexcess
2. ✅ Configure credentials in production
3. ✅ Add DKIM record to DNS
4. ✅ Add DMARC record to DNS
5. ✅ Set up cron jobs
6. ✅ Run test emails
7. ✅ Monitor for 24-48 hours
8. ✅ Gradually increase email volume

**Your email system is production-ready!** 🚀
