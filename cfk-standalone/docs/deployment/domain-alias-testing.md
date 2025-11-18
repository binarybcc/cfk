# Domain Alias Email Testing Guide

**Scenario:** cforkids.org is a Google Workspace domain alias for upstatetoday.com

---

## Pre-Deployment Testing Checklist

### Test 1: Verify Domain Alias Status

**Google Admin Console:**
1. Go to Admin Console → Account → Domains
2. Confirm cforkids.org is listed as:
   - [ ] Domain alias
   - [ ] Secondary domain
   - [ ] Primary domain

**Result:** __________________

---

### Test 2: Check DKIM Availability

**Admin Console → Apps → Gmail → Authenticate email:**

1. Look for domain selector dropdown
2. Is "cforkids.org" listed?
   - [ ] Yes - Generate separate DKIM key
   - [ ] No - Must use shared DKIM or convert to secondary domain

**Result:** __________________

**If available, generate DKIM:**
- Selected domain: cforkids.org
- Key size: 2048-bit
- Generated TXT record: __________________

---

### Test 3: DNS Record Verification

**Check current DNS for cforkids.org:**

```bash
# SPF check
dig cforkids.org TXT | grep spf

# DKIM check
dig google._domainkey.cforkids.org TXT

# DMARC check
dig _dmarc.cforkids.org TXT
```

**Current records:**
- SPF: __________________
- DKIM: __________________
- DMARC: __________________

---

### Test 4: Send Test Email from Gmail

**Before deploying code changes:**

1. Log into Gmail as a user in upstatetoday.com workspace
2. Compose new email
3. Try to send FROM: noreply@cforkids.org
   - Can you select this as "From" address?
   - [ ] Yes - Alias allows sending
   - [ ] No - Alias may be receive-only

**Result:** __________________

---

### Test 5: SMTP Relay Test (After Google Console Setup)

**After adding IP 199.189.224.131 to SMTP relay:**

```bash
# Test SMTP connection from server
telnet smtp-relay.gmail.com 587

# Expected: Connection established
```

**Result:** __________________

---

### Test 6: Email Authentication Analysis

**After sending test email, check headers:**

Use [mail-tester.com](https://www.mail-tester.com/) or check email headers:

```
Look for:
- SPF: PASS or FAIL
- DKIM: PASS or FAIL
- DMARC: PASS or FAIL

From: noreply@cforkids.org
DKIM signature domain (d=): __________ (should be cforkids.org)
SPF check: __________
DMARC alignment: __________
```

---

## Troubleshooting Decision Tree

### Scenario 1: DKIM Not Available for cforkids.org

**Symptoms:**
- No domain selector in Admin Console
- Cannot generate separate DKIM key

**Solutions (in order of preference):**

1. **Convert to Secondary Domain** (BEST)
   - Remove alias
   - Add as secondary domain
   - Generate DKIM
   - Configure all DNS records

2. **Use CNAME for Shared DKIM**
   - Add CNAME: google._domainkey.cforkids.org → google._domainkey.upstatetoday.com
   - Use relaxed DMARC alignment
   - Accept DKIM domain mismatch

3. **Use Different Sending Domain** (FALLBACK)
   - Send from noreply@upstatetoday.com instead
   - Keep cforkids.org for receive only

---

### Scenario 2: SMTP Relay Doesn't Work

**Symptoms:**
- "Relay access denied" errors
- Connection timeouts

**Check:**
- [ ] IP 199.189.224.131 in allowed list
- [ ] "Only addresses in my domains" selected (includes aliases)
- [ ] Waited 15 minutes after config
- [ ] Sending FROM @cforkids.org (not random domain)

---

### Scenario 3: Emails Pass SPF but Fail DKIM

**Symptoms:**
- SPF: PASS
- DKIM: FAIL or NEUTRAL
- DMARC: FAIL

**Cause:** DKIM not configured for cforkids.org

**Solution:**
- Add DKIM record for cforkids.org (if available)
- OR use CNAME to share upstatetoday.com DKIM
- OR relax DMARC policy: `p=none; adkim=r`

---

### Scenario 4: DKIM Passes but DMARC Fails

**Symptoms:**
- SPF: PASS
- DKIM: PASS (d=upstatetoday.com)
- DMARC: FAIL (alignment)

**Cause:** DKIM domain (upstatetoday.com) doesn't match FROM domain (cforkids.org)

**Solution:**
- Use relaxed DMARC: `adkim=r` (relaxed alignment)
- Change policy: `p=none` (monitoring mode)
- OR generate separate DKIM for cforkids.org

---

## Recommended Configuration Based on Alias Type

### If cforkids.org CAN have separate DKIM:

**DNS Records:**
```
SPF:   v=spf1 include:_spf.google.com ~all
DKIM:  [Generate from Google Admin Console]
DMARC: v=DMARC1; p=quarantine; rua=mailto:admin@cforkids.org; adkim=s; aspf=s
```

**DMARC Policy:** Strict alignment (`adkim=s`)

---

### If cforkids.org CANNOT have separate DKIM:

**DNS Records:**
```
SPF:   v=spf1 include:_spf.google.com ~all
DKIM:  CNAME → google._domainkey.upstatetoday.com
DMARC: v=DMARC1; p=none; rua=mailto:admin@cforkids.org; adkim=r; aspf=r
```

**DMARC Policy:** Relaxed alignment (`adkim=r`), monitoring mode (`p=none`)

---

## Email Header Examples

### Good Example (Separate DKIM):
```
From: noreply@cforkids.org
DKIM-Signature: d=cforkids.org; s=google; ...
SPF: PASS (google.com: domain of noreply@cforkids.org designates ...)
DMARC: PASS (alignment mode: strict)
```

### Acceptable Example (Shared DKIM):
```
From: noreply@cforkids.org
DKIM-Signature: d=upstatetoday.com; s=google; ...
SPF: PASS
DMARC: PASS (alignment mode: relaxed)
```

### Failing Example:
```
From: noreply@cforkids.org
DKIM-Signature: d=upstatetoday.com; s=google; ...
SPF: PASS
DMARC: FAIL (DKIM domain mismatch, strict alignment required)
```

---

## Final Recommendation

**BEST PATH:** Convert cforkids.org to secondary domain if business requirements allow.

**ACCEPTABLE PATH:** Use alias with shared DKIM and relaxed DMARC.

**TESTING REQUIRED:** Before production, send test emails and analyze headers to confirm authentication passes.

---

**Document Version:** 1.0
**Date:** November 18, 2025
