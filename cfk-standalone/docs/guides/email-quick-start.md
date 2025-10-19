# Email Setup Quick Start Guide

**TL;DR:** Use Google Workspace SMTP Relay - it's the easiest and most reliable option.

---

## 🚀 Quick Setup (5 Minutes)

### Option 1: Google Workspace SMTP Relay (RECOMMENDED)

**What you need:**
- Google Workspace admin access
- Your Nexcess server IP address

**Steps:**
1. Get server IP: SSH to server and run `curl ifconfig.me`
2. Go to admin.google.com → Apps → Gmail → Routing → SMTP relay
3. Add your server IP to allowed list
4. Update config.php:

```php
'email_use_smtp' => true,
'smtp_host' => 'smtp-relay.gmail.com',
'smtp_port' => 587,
'smtp_auth' => false,
'smtp_encryption' => 'tls',
```

**Done!** No passwords, OAuth, or complex setup needed.

---

### Option 2: Nexcess SMTP (SIMPLE ALTERNATIVE)

**What you need:**
- noreply@cforkids.org email account
- Email password

**Steps:**
1. Create email account in Nexcess portal
2. Update config.php:

```php
'email_use_smtp' => true,
'smtp_host' => 'mail.cforkids.org',
'smtp_port' => 587,
'smtp_auth' => true,
'smtp_username' => 'noreply@cforkids.org',
'smtp_password' => 'your_password',
'smtp_encryption' => 'tls',
```

**Done!** Basic but functional.

---

## 📊 Quick Comparison

| Feature | Google Relay | Nexcess |
|---------|--------------|---------|
| Setup Time | 5 min | 5 min |
| Deliverability | Excellent | Good |
| Daily Limit | 10,000 | Varies |
| Cost | Workspace | Free |
| Maintenance | None | Minimal |

---

## 🔍 Current Status

✅ **Working:** Email system sends successfully using PHP mail() fallback
⚠️ **Issue:** Using fallback may have deliverability issues
🎯 **Recommendation:** Implement Google SMTP Relay for production

---

## 📝 Testing

After setup, test with:
```bash
# Visit in browser:
https://cforkids.org/?page=my_sponsorships

# Enter email: johncorbin@icloud.com
# Click "Send Access Link"
# Check inbox for email
```

---

## 🆘 Need Help?

See full guide: `/docs/EMAIL_IMPLEMENTATION_OPTIONS.md`

**Quick troubleshooting:**
- Emails not sending? Check SMTP credentials
- Going to spam? Add SPF record to DNS
- Connection timeout? Check firewall on port 587

---

**Current working emails:** ✅
- Sponsorship confirmation emails (using fallback)
- Access link emails (using fallback)

**To improve deliverability:** Implement Google SMTP Relay
