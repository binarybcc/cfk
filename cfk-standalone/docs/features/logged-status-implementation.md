# LOGGED Status Implementation Plan

**Date:** October 22, 2025
**Feature:** Add "LOGGED" sponsorship status for external tracking
**Priority:** HIGH - Staff operational need

---

## 📋 Business Requirements

### Problem Statement
Staff need to track when sponsorships have been added to their external spreadsheet (paper/Excel logging system). Currently, they must mark sponsorships as COMPLETE to indicate processing, but this:
- ❌ Removes sponsor's ability to access "My Sponsorship" email
- ❌ Indicates the entire process is finished (gifts delivered)
- ❌ Doesn't accurately reflect the actual workflow stage

### Solution
Add a new "LOGGED" status that:
- ✅ Comes between CONFIRMED and COMPLETE in the workflow
- ✅ Only visible in admin panel (not shown to sponsors)
- ✅ Maintains all functionality of CONFIRMED (My Sponsorship emails work)
- ✅ Allows staff to mark when they've logged the sponsorship externally
- ✅ Keeps COMPLETE for its original purpose (gifts delivered, process ended)

---

## 🔄 Updated Workflow

### Before:
```
PENDING → CONFIRMED → COMPLETE
```

### After:
```
PENDING → CONFIRMED → LOGGED → COMPLETE
              ↓
          (My Sponsorship email works)
              ↓
          (My Sponsorship email works)
              ↓
          (My Sponsorship email stops)
```

---

## 📊 Status Comparison Matrix

| Status | Sponsor Can Access Portal | Admin View | Staff External Log | Gifts Delivered |
|--------|---------------------------|------------|-------------------|-----------------|
| **PENDING** | ❌ No | ✅ Yes | ❌ Not yet | ❌ No |
| **CONFIRMED** | ✅ Yes | ✅ Yes | ❌ Not yet | ❌ No |
| **LOGGED** | ✅ Yes | ✅ Yes | ✅ **Completed** | ❌ No |
| **COMPLETE** | ❌ No | ✅ Yes | ✅ Completed | ✅ **Yes** |

---

## 🛠️ Technical Implementation

### 1. Database Schema Changes

#### 1.1 Alter sponsorships Table ENUM
**File:** New migration file
**Action:** Add 'logged' to status ENUM

```sql
-- Migration: Add LOGGED status to sponsorships
-- File: database/migrations/add-logged-status.sql

ALTER TABLE sponsorships
MODIFY COLUMN status ENUM('pending', 'confirmed', 'logged', 'completed', 'cancelled')
DEFAULT 'pending';

-- Add timestamp for when sponsorship was logged
ALTER TABLE sponsorships
ADD COLUMN logged_date DATETIME NULL
AFTER confirmation_date;

-- Add index for better query performance
CREATE INDEX idx_sponsorships_status_logged
ON sponsorships(status, logged_date);
```

**Rollback:**
```sql
-- Remove logged status (must move any logged sponsorships first)
UPDATE sponsorships SET status = 'confirmed' WHERE status = 'logged';

ALTER TABLE sponsorships
MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled')
DEFAULT 'pending';

ALTER TABLE sponsorships DROP COLUMN logged_date;
DROP INDEX idx_sponsorships_status_logged ON sponsorships;
```

---

### 2. Application Code Changes

#### 2.1 Add STATUS_LOGGED Constant
**File:** `src/Sponsorship/Manager.php`
**Lines:** Add after line 25

```php
class Manager
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SPONSORED = 'confirmed';
    public const STATUS_LOGGED = 'logged';        // NEW
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_INACTIVE = 'inactive';

    // ... rest of class
}
```

#### 2.2 Add logSponsorship() Method
**File:** `src/Sponsorship/Manager.php`
**Location:** Add after completeSponsorship() method (around line 333)

```php
/**
 * Mark a confirmed sponsorship as logged in external system
 * This is an admin-only status for tracking external spreadsheet logging
 *
 * @param int $sponsorshipId The sponsorship ID to log
 * @return array Success status and message
 */
public static function logSponsorship(int $sponsorshipId): array
{
    try {
        // Verify sponsorship exists and is in confirmed status
        $sponsorship = Connection::fetchOne(
            'SELECT * FROM sponsorships WHERE id = ?',
            [$sponsorshipId]
        );

        if (!$sponsorship) {
            return [
                'success' => false,
                'message' => 'Sponsorship not found'
            ];
        }

        // Only allow transition from CONFIRMED to LOGGED
        if ($sponsorship['status'] !== self::STATUS_SPONSORED) {
            return [
                'success' => false,
                'message' => 'Can only log confirmed sponsorships. Current status: ' . $sponsorship['status']
            ];
        }

        // Update sponsorship status to logged
        Connection::update(
            'sponsorships',
            [
                'status' => self::STATUS_LOGGED,
                'logged_date' => date('Y-m-d H:i:s')
            ],
            ['id' => $sponsorshipId]
        );

        // Log the action
        error_log(sprintf(
            'Sponsorship #%d marked as logged in external system',
            $sponsorshipId
        ));

        return [
            'success' => true,
            'message' => 'Sponsorship marked as logged successfully'
        ];

    } catch (Exception $e) {
        error_log('Error logging sponsorship: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to log sponsorship: ' . $e->getMessage()
        ];
    }
}

/**
 * Mark a logged sponsorship back to confirmed (undo logging)
 * Allows staff to undo if they logged something by mistake
 *
 * @param int $sponsorshipId The sponsorship ID to unlog
 * @return array Success status and message
 */
public static function unlogSponsorship(int $sponsorshipId): array
{
    try {
        $sponsorship = Connection::fetchOne(
            'SELECT * FROM sponsorships WHERE id = ?',
            [$sponsorshipId]
        );

        if (!$sponsorship) {
            return [
                'success' => false,
                'message' => 'Sponsorship not found'
            ];
        }

        // Only allow transition from LOGGED back to CONFIRMED
        if ($sponsorship['status'] !== self::STATUS_LOGGED) {
            return [
                'success' => false,
                'message' => 'Can only unlog sponsorships in logged status'
            ];
        }

        // Revert to confirmed status
        Connection::update(
            'sponsorships',
            [
                'status' => self::STATUS_SPONSORED,
                'logged_date' => null
            ],
            ['id' => $sponsorshipId]
        );

        error_log(sprintf(
            'Sponsorship #%d unmarked from logged status',
            $sponsorshipId
        ));

        return [
            'success' => true,
            'message' => 'Sponsorship unmarked from logged status'
        ];

    } catch (Exception $e) {
        error_log('Error unlogging sponsorship: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to unlog sponsorship: ' . $e->getMessage()
        ];
    }
}
```

---

### 3. Admin Panel Updates

#### 3.1 Update Filter Dropdown
**File:** `admin/manage_sponsorships.php`
**Lines:** Around 369-374

```php
<select name="filter" id="filter">
    <option value="all">All Sponsorships</option>
    <option value="pending">Pending</option>
    <option value="sponsored">Confirmed</option>
    <option value="logged">Logged in External System</option>  <!-- NEW -->
    <option value="completed">Completed</option>
    <option value="cancelled">Cancelled</option>
</select>
```

#### 3.2 Add Action Buttons for LOGGED Status
**File:** `admin/manage_sponsorships.php`
**Lines:** Around 462-478 (in the action buttons section)

```php
<?php if ($sponsorship['status'] === 'confirmed') : ?>
    <!-- Mark as Logged button -->
    <form method="post" action="" style="display: inline;">
        <input type="hidden" name="action" value="log">
        <input type="hidden" name="sponsorship_id" value="<?php echo $sponsorship['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <button type="submit" class="btn btn-info btn-small"
                title="Mark as logged in external spreadsheet">
            📋 Mark Logged
        </button>
    </form>

    <!-- Mark Complete button (existing) -->
    <form method="post" action="" style="display: inline;">
        <input type="hidden" name="action" value="complete">
        <input type="hidden" name="sponsorship_id" value="<?php echo $sponsorship['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <button type="submit" class="btn btn-primary btn-small"
                title="Mark as complete (gifts delivered)">
            ✓ Mark Complete
        </button>
    </form>
<?php endif; ?>

<?php if ($sponsorship['status'] === 'logged') : ?>
    <!-- Unlog button (undo) -->
    <form method="post" action="" style="display: inline;">
        <input type="hidden" name="action" value="unlog">
        <input type="hidden" name="sponsorship_id" value="<?php echo $sponsorship['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <button type="submit" class="btn btn-warning btn-small"
                title="Unmark from logged status">
            ↩ Unlog
        </button>
    </form>

    <!-- Mark Complete button -->
    <form method="post" action="" style="display: inline;">
        <input type="hidden" name="action" value="complete">
        <input type="hidden" name="sponsorship_id" value="<?php echo $sponsorship['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <button type="submit" class="btn btn-primary btn-small"
                title="Mark as complete (gifts delivered)">
            ✓ Mark Complete
        </button>
    </form>
<?php endif; ?>

<?php if (in_array($sponsorship['status'], ['pending', 'confirmed', 'logged'])) : ?>
    <!-- Cancel button (existing, now includes logged) -->
    <button class="btn btn-danger btn-small"
            onclick="showCancelModal(<?php echo $sponsorship['id']; ?>)">
        Cancel
    </button>
<?php endif; ?>
```

#### 3.3 Add Status Badge Display
**File:** `admin/manage_sponsorships.php`
**Lines:** Around 440-450 (status badge display)

```php
<?php
$statusClass = match($sponsorship['status']) {
    'pending' => 'status-pending',
    'confirmed' => 'status-confirmed',
    'logged' => 'status-logged',      // NEW
    'completed' => 'status-completed',
    'cancelled' => 'status-cancelled',
    default => 'status-pending'
};

$statusText = match($sponsorship['status']) {
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'logged' => 'Logged',             // NEW
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    default => ucfirst($sponsorship['status'])
};
?>
<span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
```

#### 3.4 Add POST Handler for Log/Unlog Actions
**File:** `admin/manage_sponsorships.php`
**Lines:** Add to POST handling section (around line 50-150)

```php
// Handle sponsorship logging actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = 'Invalid security token';
        header('Location: manage_sponsorships.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $sponsorshipId = (int)($_POST['sponsorship_id'] ?? 0);

    // ... existing confirm and complete handlers ...

    // NEW: Handle log action
    if ($action === 'log' && $sponsorshipId > 0) {
        $result = \CFK\Sponsorship\Manager::logSponsorship($sponsorshipId);

        if ($result['success']) {
            $_SESSION['success_message'] = 'Sponsorship marked as logged in external system';
        } else {
            $_SESSION['error_message'] = $result['message'];
        }

        header('Location: manage_sponsorships.php');
        exit;
    }

    // NEW: Handle unlog action
    if ($action === 'unlog' && $sponsorshipId > 0) {
        $result = \CFK\Sponsorship\Manager::unlogSponsorship($sponsorshipId);

        if ($result['success']) {
            $_SESSION['success_message'] = 'Sponsorship unmarked from logged status';
        } else {
            $_SESSION['error_message'] = $result['message'];
        }

        header('Location: manage_sponsorships.php');
        exit;
    }
}
```

#### 3.5 Update Statistics Count
**File:** `admin/manage_sponsorships.php`
**Lines:** Around 318-335 (statistics section)

```php
<!-- Add new stat card for LOGGED sponsorships -->
<div class="stat-card">
    <div class="stat-label">Logged</div>
    <span class="stat-number"><?php echo $stats['sponsorships']['logged'] ?? 0; ?></span>
    <span class="stat-description">External system</span>
</div>
```

**Update stats query (around line 290-310):**
```php
$stats = [
    'sponsorships' => [
        'total' => (int)$db->fetchOne("SELECT COUNT(*) FROM sponsorships")['COUNT(*)'],
        'pending' => (int)$db->fetchOne("SELECT COUNT(*) FROM sponsorships WHERE status = 'pending'")['COUNT(*)'],
        'confirmed' => (int)$db->fetchOne("SELECT COUNT(*) FROM sponsorships WHERE status = 'confirmed'")['COUNT(*)'],
        'logged' => (int)$db->fetchOne("SELECT COUNT(*) FROM sponsorships WHERE status = 'logged'")['COUNT(*)'],  // NEW
        'completed' => (int)$db->fetchOne("SELECT COUNT(*) FROM sponsorships WHERE status = 'completed'")['COUNT(*)'],
        'cancelled' => (int)$db->fetchOne("SELECT COUNT(*) FROM sponsorships WHERE status = 'cancelled'")['COUNT(*)']
    ]
];
```

---

### 4. My Sponsorships Page Updates

#### 4.1 Update Status Query to Include LOGGED
**File:** `pages/my_sponsorships.php`
**Lines:** Around 37-48

**CRITICAL:** This ensures sponsors can still access their sponsorship details when status is LOGGED.

```php
// OLD (only shows confirmed):
$sponsorships = Database::fetchAll(
    "SELECT s.*, ...
     FROM sponsorships s
     WHERE s.sponsor_email = ?
     AND s.status = 'confirmed'
     ORDER BY s.confirmation_date DESC",
    [$verifiedEmail]
);

// NEW (shows both confirmed and logged):
$sponsorships = Database::fetchAll(
    "SELECT s.*, ...
     FROM sponsorships s
     WHERE s.sponsor_email = ?
     AND s.status IN ('confirmed', 'logged')  -- Include LOGGED status
     ORDER BY s.confirmation_date DESC",
    [$verifiedEmail]
);
```

#### 4.2 Update Status Badge Display (Optional - for sponsors)
**File:** `pages/my_sponsorships.php`
**Lines:** Around 236

**Note:** Since LOGGED is admin-only, you can choose to:
- **Option A:** Show "Confirmed" to sponsors regardless of logged status
- **Option B:** Show "Logged" badge to sponsors (transparent about internal process)

```php
<!-- Option A: Hide logged status from sponsors -->
<?php
$displayStatus = ($sponsorship['status'] === 'logged') ? 'confirmed' : $sponsorship['status'];
?>
<span class="status-badge status-confirmed">Confirmed</span>

<!-- Option B: Show logged status to sponsors -->
<?php if ($sponsorship['status'] === 'logged') : ?>
    <span class="status-badge status-logged">Confirmed & Logged</span>
<?php else : ?>
    <span class="status-badge status-confirmed">Confirmed</span>
<?php endif; ?>
```

**Recommendation:** Use Option A (hide from sponsors) to keep it admin-only.

---

### 5. CSS Styling Updates

#### 5.1 Add LOGGED Status Badge Styling
**File:** `assets/css/styles.css`
**Location:** Add to status badge section

```css
/* Status badge for LOGGED state */
.status-logged {
    background-color: #17a2b8;  /* Info/teal color */
    color: white;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
}

/* Hover effect for logged status */
.status-logged:hover {
    background-color: #138496;
}

/* Optional: Add icon support for logged status */
.status-logged::before {
    content: "📋 ";
    margin-right: 4px;
}
```

---

### 6. Email Logic Updates (Optional)

#### 6.1 Optional: Send Notification When Logged
**File:** `src/Sponsorship/Manager.php`
**In:** `logSponsorship()` method

```php
// Optional: Notify admin when sponsorship is logged
if ($result['success']) {
    \CFK\Email\Manager::sendAdminNotification(
        'Sponsorship Logged',
        sprintf(
            'Sponsorship #%d has been marked as logged in external system by admin.',
            $sponsorshipId
        )
    );
}
```

**Note:** This is optional - staff may not need email notifications for this internal tracking.

---

## 📝 Testing Checklist

### Database Tests:
- [ ] Migration runs successfully without errors
- [ ] ENUM includes 'logged' status
- [ ] logged_date column exists and accepts NULL
- [ ] Index created for performance
- [ ] Rollback script works correctly

### Admin Panel Tests:
- [ ] "Logged" filter option appears in dropdown
- [ ] Filter works correctly (shows only logged sponsorships)
- [ ] "Mark Logged" button appears for CONFIRMED sponsorships
- [ ] "Mark Logged" button successfully transitions to LOGGED
- [ ] "Unlog" button appears for LOGGED sponsorships
- [ ] "Unlog" button successfully reverts to CONFIRMED
- [ ] "Mark Complete" button appears for LOGGED sponsorships
- [ ] Status badge displays correctly for LOGGED status
- [ ] Statistics card shows correct count of logged sponsorships
- [ ] Cancel button works for LOGGED sponsorships

### Sponsor Portal Tests:
- [ ] Sponsors can access "My Sponsorships" when status is LOGGED
- [ ] Child details display correctly for LOGGED sponsorships
- [ ] Status badge shows correctly (or hidden, per implementation choice)
- [ ] No errors when viewing logged sponsorships

### Business Logic Tests:
- [ ] Cannot log a PENDING sponsorship (error message shown)
- [ ] Cannot log a COMPLETED sponsorship (error message shown)
- [ ] Cannot log a CANCELLED sponsorship (error message shown)
- [ ] Can only log CONFIRMED sponsorships
- [ ] logged_date is set when transitioning to LOGGED
- [ ] logged_date is cleared when unlogging

### Edge Cases:
- [ ] Multiple rapid clicks don't create duplicate status changes
- [ ] CSRF protection works for log/unlog actions
- [ ] Error handling works gracefully
- [ ] Child status remains "sponsored" during LOGGED state

---

## 🚀 Deployment Steps

### Pre-Deployment:
1. ✅ Review and test all code changes in development
2. ✅ Run migration on development database
3. ✅ Test complete workflow: PENDING → CONFIRMED → LOGGED → COMPLETE
4. ✅ Verify My Sponsorships page works with LOGGED status
5. ✅ Get staff approval of admin panel UI changes

### Deployment:
1. **Backup production database**
   ```bash
   mysqldump -u user -p database > backup_before_logged_status.sql
   ```

2. **Run database migration**
   ```bash
   mysql -u user -p database < database/migrations/add-logged-status.sql
   ```

3. **Deploy application code changes:**
   - `src/Sponsorship/Manager.php`
   - `admin/manage_sponsorships.php`
   - `pages/my_sponsorships.php`
   - `assets/css/styles.css`

4. **Verify deployment:**
   - Check admin panel loads without errors
   - Test "Mark Logged" button on a confirmed sponsorship
   - Verify sponsor can still access My Sponsorships page

### Post-Deployment:
1. ✅ Monitor error logs for any issues
2. ✅ Have staff test the new workflow
3. ✅ Verify existing CONFIRMED sponsorships still work
4. ✅ Document new workflow for staff training

---

## 📊 Staff Training Materials

### Quick Reference: LOGGED Status

**What is the LOGGED status?**
- LOGGED indicates the sponsorship has been added to your external spreadsheet/paper system
- It's a tracking marker to prevent duplicate logging
- Sponsors can still access their sponsorship details (unlike COMPLETE)

**When to use LOGGED:**
1. Sponsor confirms and pays ✅
2. Admin clicks "Confirm" button ✅
3. **Admin adds to external spreadsheet** → Click "Mark Logged" 📋
4. Gifts are purchased and delivered → Click "Mark Complete" ✅

**Workflow:**
```
PENDING → CONFIRMED → LOGGED → COMPLETE
          (sponsor active)  (sponsor active)  (sponsor inactive)
                           (📋 logged externally)
```

**Buttons in Admin Panel:**
- **Mark Logged**: Click this AFTER adding sponsorship to your spreadsheet
- **Unlog**: Click this if you marked it logged by mistake
- **Mark Complete**: Click this AFTER gifts are delivered (final step)

---

## 🔄 Rollback Plan

If issues occur after deployment:

### Quick Rollback:
```sql
-- Move all LOGGED sponsorships back to CONFIRMED
UPDATE sponsorships
SET status = 'confirmed', logged_date = NULL
WHERE status = 'logged';

-- Revert database schema
ALTER TABLE sponsorships
MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled')
DEFAULT 'pending';

ALTER TABLE sponsorships DROP COLUMN logged_date;
```

### Code Rollback:
1. Revert code changes via git
2. Restart web server
3. Clear cache if needed

---

## 📈 Future Enhancements

### Phase 2 Ideas (optional):
1. **Bulk Operations**: Mark multiple sponsorships as logged at once
2. **Auto-logging**: Integrate with external spreadsheet API (if available)
3. **Reminder System**: Email reminder if sponsorship is confirmed >24hrs without logging
4. **Audit Trail**: Track who logged each sponsorship and when
5. **Export**: CSV export of logged sponsorships for reconciliation

---

**Implementation Status:** 📝 PLANNED (Ready for development)
**Estimated Development Time:** 4-6 hours
**Risk Level:** 🟢 Low (additive change, no existing data affected)
