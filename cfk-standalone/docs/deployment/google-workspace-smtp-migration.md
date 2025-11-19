# Google Workspace SMTP Migration Guide

**Date:** November 18, 2025
**From:** MailChannels → Google Workspace SMTP Relay
**Server IP:** 199.189.224.131

---

## 🎯 Migration Overview

**Why migrate:**
- ✅ Better email deliverability
- ✅ Professional Google infrastructure
- ✅ 10,000 emails/day limit (vs MailChannels limits)
- ✅ Built-in spam filtering and reputation
- ✅ Integration with existing Google Workspace

**Method:** SMTP Relay with IP Authentication (no password needed)

---

## ✅ Pre-Migration Checklist

- [ ] Google Workspace admin access to cforkids.org
- [ ] DNS management access (for SPF, DKIM, DMARC)
- [ ] Server access to production (199.189.224.131)
- [ ] Test email address for verification
- [ ] Backup of current email logs

---

## 📋 Step-by-Step Migration

### Step 1: Google Admin Console Configuration

**Access:** [admin.google.com](https://admin.google.com)

**Navigate to:**
Apps → Google Workspace → Gmail → Routing

**Configure SMTP Relay:**
1. Click "SMTP relay service" (or "Add another")
2. Enter configuration:

```
Allowed senders: Only addresses in my domains
Authentication: UNCHECKED (we use IP authentication)
IP addresses: 199.189.224.131
Encryption: CHECKED (Require TLS encryption)
```

3. Click "Save"
4. **Wait 10-15 minutes** for propagation

**Screenshot location to verify:**
- Should see "199.189.224.131" in allowed IPs
- "Only addresses in my domains" selected
- TLS encryption enabled

---

### Step 2: DNS Records Setup

**Go to your DNS provider** (Nexcess, Cloudflare, etc.)

#### SPF Record

**Type:** TXT
**Name:** `@` (or leave blank for root domain)
**Value:**
```
v=spf1 include:_spf.google.com ~all
```

**If you have existing SPF record:**
```
v=spf1 include:_spf.google.com include:relay.mailchannels.net ~all
```

**TTL:** 3600 (1 hour)

---

#### DKIM Record

**Generate from Google:**

1. Go to Google Admin Console → Apps → Gmail → Authenticate email
2. Click "Generate new record"
3. Select domain: **cforkids.org**
4. Key size: **2048-bit** (recommended)
5. Click "Generate"

Google will show you:

**Type:** TXT
**Name:** `google._domainkey`
**Value:** `v=DKIM1; k=rsa; p=VERY_LONG_KEY_HERE...`

6. **Copy the EXACT value** to your DNS provider
7. **Wait 24-48 hours** for DKIM to activate

**Verify DKIM:**
```bash
dig google._domainkey.cforkids.org TXT
```

Should return the DKIM key.

---

#### DMARC Record

**Type:** TXT
**Name:** `_dmarc`
**Value (for testing - quarantine mode):**
```
v=DMARC1; p=quarantine; rua=mailto:admin@cforkids.org; ruf=mailto:admin@cforkids.org; pct=100; adkim=r; aspf=r
```

**Explanation:**
- `p=quarantine` - Quarantine suspicious emails (not reject yet)
- `rua=` - Send aggregate reports here
- `ruf=` - Send forensic reports here
- `pct=100` - Apply to 100% of emails
- `adkim=r` - Relaxed DKIM alignment
- `aspf=r` - Relaxed SPF alignment

**After 30 days of testing, switch to strict:**
```
v=DMARC1; p=reject; rua=mailto:admin@cforkids.org; pct=100; adkim=s; aspf=s
```

**TTL:** 3600

---

### Step 3: Update Application Configuration

**File:** `config/config.php`

**Changes made:**
```php
// SMTP Configuration - Google Workspace SMTP Relay
// Server IP 199.189.224.131 must be authorized in Google Admin Console
'email_use_smtp' => $isProduction,
'smtp_host' => 'smtp-relay.gmail.com',  // Changed from relay.mailchannels.net
'smtp_port' => 587,
'smtp_auth' => false,  // IP-based auth (changed from true)
'smtp_username' => '',  // Not needed (changed from env var)
'smtp_password' => '',  // Not needed (changed from env var)
'smtp_encryption' => 'tls',
```

**Status:** ✅ Already updated in this branch

---

### Step 4: Testing Phase

#### Local Testing (Development)

1. Commit changes to git:
```bash
git add config/config.php admin/test_google_smtp.php
git commit -m "feat: Migrate to Google Workspace SMTP Relay

- Switch from MailChannels to smtp-relay.gmail.com
- Use IP-based authentication (199.189.224.131)
- Add test script for SMTP verification
- Update documentation with DNS records"
```

2. Push to branch:
```bash
git push origin v1.7.3-production-hardening
```

#### Production Testing

1. **Deploy to production:**
```bash
# Use deployment script or manual SCP
scp config/config.php user@199.189.224.131:/path/to/app/
scp admin/test_google_smtp.php user@199.189.224.131:/path/to/app/admin/
```

2. **Access test page:**
```
https://cforkids.org/admin/test_google_smtp.php
```

3. **Run tests:**
   - Enter your email address
   - Click "Send Test Email"
   - Check all three test results:
     - ✅ SMTP Connection Test
     - ✅ Email Send Test
     - ✅ Queue Status

4. **Check your inbox:**
   - Should receive test email within 1-2 minutes
   - Check spam folder if not in inbox
   - Verify email headers show Google servers

---

### Step 5: Verification & Monitoring

#### Verify DNS Records

**Tool:** [mxtoolbox.com](https://mxtoolbox.com/SuperTool.aspx)

Test these:
```
SPF:   mxtoolbox.com/spf.aspx?domain=cforkids.org
DKIM:  mxtoolbox.com/dkim.aspx?domain=cforkids.org&selector=google
DMARC: mxtoolbox.com/dmarc.aspx?domain=cforkids.org
```

**Expected results:**
- SPF: ✅ Pass (includes Google servers)
- DKIM: ✅ Pass (after 24-48 hours)
- DMARC: ✅ Pass (policy: quarantine or reject)

#### Monitor Email Delivery

**Check email queue:**
```sql
-- Queued emails
SELECT status, COUNT(*) FROM email_queue GROUP BY status;

-- Recent emails
SELECT * FROM email_log ORDER BY created_at DESC LIMIT 20;

-- Failed emails
SELECT * FROM email_queue WHERE status = 'failed';
```

**Watch for:**
- 🔴 Increased bounce rates
- 🔴 Emails stuck in queue
- 🔴 Delivery failures
- 🟢 Improved delivery speed
- 🟢 Better inbox placement

#### Google Workspace Monitoring

**Email Log Search** (admin.google.com):
1. Go to Reports → Audit → Email Log Search
2. Search for: `noreply@cforkids.org`
3. Verify emails are being sent
4. Check delivery status

---

## 🚨 Troubleshooting

### Issue: "Relay access denied"

**Cause:** Server IP not authorized in Google Admin

**Fix:**
1. Verify IP 199.189.224.131 is in allowed list
2. Check "Only addresses in my domains" is selected
3. Wait 15 minutes after changes
4. Restart web server: `sudo systemctl restart apache2`

---

### Issue: "Connection timeout"

**Cause:** Firewall blocking port 587

**Fix:**
```bash
# Check if port is open
telnet smtp-relay.gmail.com 587

# If fails, check firewall
sudo iptables -L | grep 587

# Allow port if blocked
sudo iptables -A OUTPUT -p tcp --dport 587 -j ACCEPT
```

---

### Issue: Emails going to spam

**Cause:** DNS records not configured or not propagated

**Fix:**
1. Verify SPF, DKIM, DMARC records exist
2. Wait 24-48 hours for DNS propagation
3. Check DKIM status in Google Admin Console
4. Use email header analyzer to debug

**Tool:** [mail-tester.com](https://www.mail-tester.com/)

---

### Issue: DKIM verification fails

**Cause:** DKIM record not propagated or incorrect

**Fix:**
1. Check DNS record exists:
   ```bash
   dig google._domainkey.cforkids.org TXT
   ```
2. Verify exact value matches Google Admin Console
3. Wait 24-48 hours
4. Regenerate DKIM if still failing

---

## 📊 Success Metrics

**After 7 days, check:**

- [ ] Email delivery rate >95%
- [ ] Bounce rate <2%
- [ ] Spam placement rate <5%
- [ ] Average delivery time <5 minutes
- [ ] Zero "relay access denied" errors
- [ ] DKIM passing 100%
- [ ] SPF passing 100%
- [ ] DMARC passing 100%

---

## 🔄 Rollback Plan

**If migration fails, revert to MailChannels:**

1. **Update config.php:**
```php
'smtp_host' => 'relay.mailchannels.net',
'smtp_auth' => true,
'smtp_username' => getenv('SMTP_USERNAME') ?: '',
'smtp_password' => getenv('SMTP_PASSWORD') ?: '',
```

2. **Redeploy to production**

3. **Restore .env credentials**

4. **Test email delivery**

**Time to rollback:** ~5 minutes

---

## 📝 Post-Migration Tasks

**Week 1:**
- [ ] Monitor email delivery daily
- [ ] Check DMARC reports (admin@cforkids.org)
- [ ] Verify all email types work (confirmations, magic links, admin notifications)
- [ ] Test from multiple email clients (Gmail, Outlook, Yahoo)

**Week 2:**
- [ ] Review bounce reports
- [ ] Adjust DMARC policy if needed
- [ ] Document any issues encountered
- [ ] Update runbook with lessons learned

**Week 4:**
- [ ] Switch DMARC to `p=reject` if all passing
- [ ] Remove MailChannels from SPF record (optional)
- [ ] Archive old email logs
- [ ] Final security review

---

## 🔐 Security Notes

**Best practices:**
- ✅ Never commit SMTP credentials to git (IP auth = no credentials!)
- ✅ Keep DKIM keys private (don't share DNS TXT value publicly)
- ✅ Monitor DMARC reports for spoofing attempts
- ✅ Use TLS encryption always (port 587)
- ✅ Restrict noreply@cforkids.org to system use only

**Email authentication stack:**
```
SPF    → Verify sending server IP
DKIM   → Cryptographic signature verification
DMARC  → Policy enforcement (quarantine/reject)
```

All three must pass for optimal deliverability.

---

## 📞 Support Resources

**Google Workspace Support:**
- Admin Console: [admin.google.com](https://admin.google.com)
- Documentation: [support.google.com/a](https://support.google.com/a)
- SMTP Relay Guide: [support.google.com/a/answer/2956491](https://support.google.com/a/answer/2956491)

**DNS Tools:**
- MX Toolbox: [mxtoolbox.com](https://mxtoolbox.com)
- Mail Tester: [mail-tester.com](https://www.mail-tester.com)
- DNS Checker: [dnschecker.org](https://dnschecker.org)

**Email Testing:**
- Test script: `/admin/test_google_smtp.php`
- Cron job: `/cron/process_email_queue.php`
- Email logs: Query `email_queue` and `email_log` tables

---

## ✅ Final Checklist

**Before going live:**

- [ ] Google Admin Console: IP 199.189.224.131 authorized
- [ ] DNS: SPF record added
- [ ] DNS: DKIM record added (wait 24-48h for activation)
- [ ] DNS: DMARC record added
- [ ] Code: config.php updated
- [ ] Code: Changes committed to git
- [ ] Production: Files deployed
- [ ] Testing: Test email sent successfully
- [ ] Testing: Email received in inbox (not spam)
- [ ] Monitoring: Email queue checked
- [ ] Documentation: This guide reviewed

**Status:** Ready to deploy! 🚀

---

**Document Version:** 1.0
**Last Updated:** November 18, 2025
**Prepared by:** Claude Code
**Branch:** v1.7.3-production-hardening
