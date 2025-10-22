# LOGGED Status - Local Testing Guide

**Date:** October 22, 2025
**Environment:** Local Docker (http://localhost:8082)
**Test Database:** cfk_sponsorship_dev

---

## ✅ Pre-Test Setup Complete

- [x] Docker containers running
- [x] Database migration applied successfully
- [x] Test sponsorship created (ID 4, Child 10A)
- [x] Status: CONFIRMED (ready for testing)

---

## 🧪 Test Scenarios

### **Test 1: Mark Sponsorship as LOGGED**

#### Steps:
1. Access: http://localhost:8082/admin/manage_sponsorships.php
2. Login with admin credentials
3. Find sponsorship for "Test Sponsor" (Child 10A)
4. Verify you see two buttons:
   - 📋 Mark Logged (teal/info color)
   - ✓ Mark Complete (green/primary color)
5. Click "📋 Mark Logged"

#### Expected Results:
- ✅ Page reloads with success message
- ✅ Status badge shows "Logged" (teal background)
- ✅ "Logged Externally" stat card shows count: 1
- ✅ Two new buttons appear:
  - ↩ Unlog (yellow/warning)
  - ✓ Mark Complete (green/primary)

#### Database Check:
```bash
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "SELECT id, status, logged_date FROM sponsorships WHERE id = 4;"
```

**Expected:**
- status: `logged`
- logged_date: Current timestamp (not NULL)

---

### **Test 2: Filter by LOGGED Status**

#### Steps:
1. In admin panel, find "Status" dropdown filter
2. Select "Logged in External System"
3. Click apply/auto-filter

#### Expected Results:
- ✅ Only logged sponsorships displayed
- ✅ Test sponsorship (ID 4) should be visible
- ✅ Count shows "Showing X of Y sponsorships"

---

### **Test 3: Unlog Sponsorship (Undo)**

#### Steps:
1. Find the logged sponsorship (Child 10A)
2. Click "↩ Unlog" button
3. Confirm action (if modal appears)

#### Expected Results:
- ✅ Status reverts to "Confirmed"
- ✅ Buttons change back to:
  - 📋 Mark Logged
  - ✓ Mark Complete
- ✅ "Logged Externally" count decreases to 0

#### Database Check:
```bash
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "SELECT id, status, logged_date FROM sponsorships WHERE id = 4;"
```

**Expected:**
- status: `confirmed`
- logged_date: NULL

---

### **Test 4: Mark LOGGED → COMPLETE**

#### Steps:
1. Mark sponsorship as "Logged" again (📋 Mark Logged)
2. Click "✓ Mark Complete" button
3. Confirm action

#### Expected Results:
- ✅ Status changes to "Completed"
- ✅ "Gifts Delivered" count increases
- ✅ No action buttons appear (completed is final state)

#### Database Check:
```bash
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "SELECT id, status, logged_date, completion_date FROM sponsorships WHERE id = 4;"
```

**Expected:**
- status: `completed`
- logged_date: Previous timestamp (preserved)
- completion_date: Current timestamp

---

### **Test 5: Sponsor Portal Access (CRITICAL)**

#### Setup:
```bash
# Generate access token for test sponsor
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "INSERT INTO portal_access_tokens (email, token, expires_at, created_at) \
   VALUES ('test@example.com', 'test-token-12345', DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW());"
```

#### Steps:
1. Reset sponsorship to CONFIRMED:
   ```bash
   docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
     "UPDATE sponsorships SET status = 'confirmed', completion_date = NULL WHERE id = 4;"
   ```

2. Access My Sponsorships with CONFIRMED status:
   ```
   http://localhost:8082/?page=my_sponsorships&token=test-token-12345
   ```

3. Mark as LOGGED in admin panel

4. Access My Sponsorships again with LOGGED status (same URL)

#### Expected Results (Both CONFIRMED and LOGGED):
- ✅ Sponsorship displays on the page
- ✅ Child details visible (name, age, gender, wishes, sizes)
- ✅ No errors or "no sponsorships found" message
- ✅ Status badge shows "Confirmed" (we hide "logged" from sponsors)

#### Expected Result for COMPLETED:
```bash
# Mark as completed
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "UPDATE sponsorships SET status = 'completed', completion_date = NOW() WHERE id = 4;"
```

- ❌ Should NOT display on My Sponsorships page
- ✅ Shows "No sponsorships found" message

---

### **Test 6: Statistics Accuracy**

#### Steps:
1. Create multiple sponsorships in different statuses
2. Check statistics cards on admin dashboard

#### Database Setup:
```bash
# Create sponsorships in various states
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev << 'EOF'
-- Add a confirmed sponsorship
INSERT INTO sponsorships (child_id, sponsor_name, sponsor_email, sponsor_phone, sponsor_address, gift_preference, status, request_date, confirmation_date)
VALUES (1866, 'Sponsor Two', 'sponsor2@example.com', '555-2222', '456 Test Ave', 'gift_card', 'confirmed', NOW(), NOW());

-- Add a logged sponsorship
INSERT INTO sponsorships (child_id, sponsor_name, sponsor_email, sponsor_phone, sponsor_address, gift_preference, status, request_date, confirmation_date, logged_date)
VALUES (1867, 'Sponsor Three', 'sponsor3@example.com', '555-3333', '789 Test Blvd', 'cash_donation', 'logged', NOW(), NOW(), NOW());

-- Update children status
UPDATE children SET status = 'sponsored' WHERE id IN (1866, 1867);
EOF
```

#### Expected Statistics:
- Active Sponsorships (confirmed): 1
- Logged Externally (logged): 1
- Total Sponsored (confirmed + logged + completed): 2+

---

### **Test 7: Error Handling**

#### Test 7.1: Try to LOG a PENDING sponsorship
```bash
# Create a pending sponsorship
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "INSERT INTO sponsorships (child_id, sponsor_name, sponsor_email, sponsor_phone, sponsor_address, gift_preference, status, request_date) \
   VALUES (1868, 'Pending Sponsor', 'pending@example.com', '555-4444', '999 Test Rd', 'shopping', 'pending', NOW());"
```

**Steps:**
1. Find pending sponsorship in admin panel
2. Try to mark as logged (should not have the button)

**Expected:**
- ❌ No "Mark Logged" button visible for pending sponsorships
- ✅ Only "Cancel" button available

#### Test 7.2: Try to LOG a COMPLETED sponsorship
```bash
# Mark as completed
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "UPDATE sponsorships SET status = 'completed', completion_date = NOW() WHERE id = 4;"
```

**Expected:**
- ❌ No action buttons visible for completed sponsorships

---

### **Test 8: Cancel from LOGGED Status**

#### Steps:
1. Mark a sponsorship as LOGGED
2. Click "Cancel" button
3. Enter cancellation reason: "Testing cancel from logged status"
4. Confirm

#### Expected Results:
- ✅ Status changes to "Cancelled"
- ✅ Child released back to "available" status
- ✅ Cancellation reason stored in notes

#### Database Check:
```bash
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "SELECT id, status, notes FROM sponsorships WHERE id = 4; \
   SELECT id, status FROM children WHERE id = 1865;"
```

**Expected:**
- Sponsorship status: `cancelled`
- Child status: `available`
- Notes field: Contains cancellation reason

---

### **Test 9: CSS Styling**

#### Visual Check:
1. View a LOGGED sponsorship in admin panel
2. Verify status badge styling:
   - Background color: Teal (#17a2b8)
   - Text color: White
   - Border radius: Rounded
   - Text: "Logged"

---

### **Test 10: Complete Workflow End-to-End**

#### Full Lifecycle Test:
```
PENDING → CONFIRMED → LOGGED → COMPLETE
```

**Steps:**
1. Create new sponsorship (starts as PENDING)
2. Admin confirms → Status: CONFIRMED
3. Admin marks as logged → Status: LOGGED
   - ✅ Sponsor can access portal
4. Admin marks as complete → Status: COMPLETED
   - ❌ Sponsor can NOT access portal

#### Verify Each Step:
- Database status updates correctly
- logged_date and completion_date populated
- Sponsor portal access works/blocks appropriately
- Statistics update in real-time

---

## 🐛 Troubleshooting

### Issue: "Mark Logged" button not appearing
**Check:**
```bash
# Verify code is loaded
curl -s http://localhost:8082/admin/manage_sponsorships.php | grep "Mark Logged"
```

### Issue: Sponsor cannot access portal when LOGGED
**Check:**
```bash
# Verify query includes 'logged' status
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "SELECT * FROM sponsorships WHERE sponsor_email = 'test@example.com' AND status IN ('confirmed', 'logged');"
```

### Issue: Statistics not updating
**Check:**
```bash
# Verify getStats() includes logged count
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev -e \
  "SELECT status, COUNT(*) as count FROM sponsorships GROUP BY status;"
```

---

## ✅ Final Verification Checklist

Before deploying to production, verify ALL tests pass:

- [ ] Test 1: Mark as Logged ✅
- [ ] Test 2: Filter by Logged ✅
- [ ] Test 3: Unlog (Undo) ✅
- [ ] Test 4: Mark Logged → Complete ✅
- [ ] Test 5: Sponsor Portal Access (CRITICAL) ✅
- [ ] Test 6: Statistics Accuracy ✅
- [ ] Test 7: Error Handling ✅
- [ ] Test 8: Cancel from Logged ✅
- [ ] Test 9: CSS Styling ✅
- [ ] Test 10: Complete Workflow ✅

### Additional Checks:
- [ ] No PHP errors in Docker logs: `docker-compose logs web | grep -i error`
- [ ] No JavaScript errors in browser console
- [ ] All buttons have proper tooltips/titles
- [ ] CSRF tokens working correctly
- [ ] Page reloads show success/error messages

---

## 📊 Test Results Template

```
Test Date: _______________
Tester: __________________

Test 1: Mark as Logged         [ PASS / FAIL ] Notes: ________________
Test 2: Filter by Logged       [ PASS / FAIL ] Notes: ________________
Test 3: Unlog (Undo)           [ PASS / FAIL ] Notes: ________________
Test 4: Mark Logged → Complete [ PASS / FAIL ] Notes: ________________
Test 5: Sponsor Portal Access  [ PASS / FAIL ] Notes: ________________
Test 6: Statistics Accuracy    [ PASS / FAIL ] Notes: ________________
Test 7: Error Handling         [ PASS / FAIL ] Notes: ________________
Test 8: Cancel from Logged     [ PASS / FAIL ] Notes: ________________
Test 9: CSS Styling            [ PASS / FAIL ] Notes: ________________
Test 10: Complete Workflow     [ PASS / FAIL ] Notes: ________________

Overall Result: [ READY FOR PRODUCTION / NEEDS FIXES ]
```

---

## 🚀 Next Steps After Successful Testing

1. ✅ All tests passed locally
2. Commit changes to git
3. Deploy to production using: `docs/deployment/LOGGED-STATUS-DEPLOYMENT.md`
4. Run same tests on production
5. Train staff on new workflow

---

**Testing Guide Version:** 1.0
**Last Updated:** October 22, 2025
**Environment:** Local Docker (cfk_sponsorship_dev)
