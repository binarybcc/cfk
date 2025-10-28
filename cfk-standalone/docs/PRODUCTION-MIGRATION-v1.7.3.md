# Production Database Migration - v1.7.3
## Adding Reservation System Columns

**Date:** 2025-10-28
**Status:** READY TO EXECUTE
**Risk Level:** LOW (additive only, no data changes)

---

## ✅ Pre-Migration Checklist

- [x] Migration tested successfully in local Docker
- [x] Migration file created: `database/migrations/v1.7.3_add_reservation_columns.sql`
- [x] No existing data will be modified (columns have DEFAULT NULL)
- [x] Backup strategy confirmed
- [ ] Production backup completed
- [ ] Migration executed in production
- [ ] Verification completed

---

## 🎯 What This Migration Does

**Adds columns to `children` table:**
- `reservation_id` (INT, NULL) - Tracks which reservation holds this child
- `reservation_expires_at` (TIMESTAMP, NULL) - When the reservation expires
- Indexes for performance

**Adds columns to `admin_users` table:**
- `reset_token` (VARCHAR 255, NULL) - Password reset token
- `reset_token_expiry` (DATETIME, NULL) - Token expiration
- `updated_at` (TIMESTAMP) - Last update tracking
- Index for reset token lookups

**Impact:** Enables the reservation system to prevent race conditions where two sponsors select the same child.

---

## 📋 Step-by-Step Execution

### Step 1: Upload Migration File to Production

```bash
sshpass -p 'HangerAbodeFicesMoved' scp -o StrictHostKeyChecking=no \
  database/migrations/v1.7.3_add_reservation_columns.sql \
  a4409d26_1@d646a74eb9.nxcli.io:~/
```

### Step 2: Backup Production Database

```bash
sshpass -p 'HangerAbodeFicesMoved' ssh a4409d26_1@d646a74eb9.nxcli.io \
  "mysqldump -h localhost -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' \
   a4409d26_509946 > backup_v1.7.3_$(date +%Y%m%d_%H%M%S).sql"
```

**Expected Output:**
```
(No output = success)
```

### Step 3: Verify Backup Exists

```bash
sshpass -p 'HangerAbodeFicesMoved' ssh a4409d26_1@d646a74eb9.nxcli.io \
  "ls -lh backup_v1.7.3_*.sql | tail -1"
```

**Expected Output:**
```
-rw-r--r-- 1 a4409d26 a4409d26 [SIZE] [DATE] backup_v1.7.3_20251028_HHMMSS.sql
```

### Step 4: Run Migration

```bash
sshpass -p 'HangerAbodeFicesMoved' ssh a4409d26_1@d646a74eb9.nxcli.io \
  "mysql -h localhost -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' \
   a4409d26_509946 < v1.7.3_add_reservation_columns.sql"
```

**Expected Output:**
```
mysql: [Warning] Using a password on the command line interface can be insecure.
(No errors = success)
```

### Step 5: Verify Columns Added

```bash
sshpass -p 'HangerAbodeFicesMoved' ssh a4409d26_1@d646a74eb9.nxcli.io \
  "mysql -h localhost -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' \
   a4409d26_509946 -e 'DESCRIBE children;' | grep -E 'reservation_id|reservation_expires'"
```

**Expected Output:**
```
reservation_id         int         YES  MUL  NULL
reservation_expires_at timestamp   YES  MUL  NULL
```

### Step 6: Verify Indexes Created

```bash
sshpass -p 'HangerAbodeFicesMoved' ssh a4409d26_1@d646a74eb9.nxcli.io \
  "mysql -h localhost -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' \
   a4409d26_509946 -e 'SHOW INDEX FROM children WHERE Key_name LIKE \"%reservation%\";'"
```

**Expected Output:**
```
children  1  idx_reservation           1  reservation_id         ...
children  1  idx_reservation_expires   1  reservation_expires_at ...
```

### Step 7: Verify Admin Columns Added

```bash
sshpass -p 'HangerAbodeFicesMoved' ssh a4409d26_1@d646a74eb9.nxcli.io \
  "mysql -h localhost -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' \
   a4409d26_509946 -e 'DESCRIBE admin_users;' | grep -E 'reset_token|updated_at'"
```

**Expected Output:**
```
reset_token        varchar(255)  YES  MUL  NULL
reset_token_expiry datetime      YES       NULL
updated_at         timestamp     YES       CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
```

---

## 🧪 Post-Migration Testing

### Test 1: Check Existing Children Unaffected

```bash
sshpass -p 'HangerAbodeFicesMoved' ssh a4409d26_1@d646a74eb9.nxcli.io \
  "mysql -h localhost -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' \
   a4409d26_509946 -e 'SELECT id, status, reservation_id, reservation_expires_at FROM children LIMIT 5;'"
```

**Expected Output:**
```
All children should show NULL for reservation_id and reservation_expires_at
Status should be unchanged (available/sponsored/etc)
```

### Test 2: Test Reservation System Works

Visit the site and try to reserve a child:
1. Browse to https://cforkids.org/?page=children
2. Add a child to cart
3. Proceed to checkout
4. Should create reservation with expiration timestamp

---

## 🚨 Rollback Procedure (If Needed)

**If migration causes issues:**

```bash
# Restore from backup
sshpass -p 'HangerAbodeFicesMoved' ssh a4409d26_1@d646a74eb9.nxcli.io \
  "mysql -h localhost -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' \
   a4409d26_509946 < backup_v1.7.3_YYYYMMDD_HHMMSS.sql"
```

**Note:** Rollback will restore to pre-migration state. No data loss possible because:
- Migration only ADDS columns with DEFAULT NULL
- No existing data is modified
- No columns are dropped or changed

---

## 📊 Migration Impact

**Downtime:** None (additive ALTER TABLE is non-blocking for MyISAM/InnoDB)

**Performance Impact:** Minimal
- Adding indexed columns is fast
- Default NULL means no data population needed
- Existing queries unaffected

**Application Impact:**
- ✅ Existing code continues to work (ignores new columns)
- ✅ Reservation system code starts working properly
- ✅ Race conditions prevented

---

## ✅ Success Criteria

Migration is successful when:
- [x] Local Docker migration tested
- [ ] Backup created in production
- [ ] All DESCRIBE commands show new columns
- [ ] All SHOW INDEX commands show new indexes
- [ ] Existing children records unchanged
- [ ] Reservation flow works on production site
- [ ] No PHP errors in production logs

---

## 📝 Notes

**Why This Migration is Safe:**
1. Additive only (no destructive changes)
2. All new columns DEFAULT NULL (no data population)
3. Existing queries ignore new columns
4. Easy rollback via backup restore
5. Tested successfully in local environment

**Database Credentials:**
- Host: localhost (from production server)
- User: a4409d26_509946
- Database: a4409d26_509946
- Password: (in .env file)

**Migration Duration:** < 5 seconds (tested in Docker)
