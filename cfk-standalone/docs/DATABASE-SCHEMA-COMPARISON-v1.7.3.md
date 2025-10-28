# Database Schema Comparison Report
## Production vs. v1.7.3 Local Schema

**Date:** 2025-10-28
**Branch:** v1.7.3-production-hardening
**Comparison:** Production cforkids.org vs. Local Development

---

## 🔴 CRITICAL: Schema Mismatch Detected

Production database is **missing v1.5 reservation system columns**. The application will work but reservation features will not function.

---

## Missing Columns in Production

### 1. `children` Table - Reservation System Columns

**Missing in Production:**
```sql
reservation_id INT DEFAULT NULL
reservation_expires_at TIMESTAMP NULL
INDEX idx_reservation (reservation_id)
INDEX idx_reservation_expires (reservation_expires_at)
```

**Impact:**
- ❌ Reservation system won't work
- ❌ Time-limited child selection will fail
- ❌ Expired reservation cleanup won't function

**Code That Will Fail:**
- `includes/reservation_functions.php` - All reservation operations
- `cron/cleanup_reservations.php` - Automated cleanup
- `pages/selections.php` - Cart functionality
- `pages/reservation_review.php` - Checkout flow

---

### 2. `admin_users` Table - Password Reset Columns

**Missing in Production:**
```sql
reset_token VARCHAR(255) DEFAULT NULL
reset_token_expiry DATETIME DEFAULT NULL
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
INDEX idx_reset_token (reset_token)
```

**Impact:**
- ❌ Password reset functionality won't work
- ❌ Admin forgot password flow will fail
- ⚠️ Less critical - can use direct password reset via SSH

**Code That Will Fail:**
- `admin/forgot_password.php` - Reset request
- `admin/reset_password.php` - Reset completion
- `admin/verify-magic-link.php` - Token verification

---

## Migration Strategy

### ✅ Created Migration File

**Location:** `database/migrations/v1.7.3_add_reservation_columns.sql`

This migration adds all missing columns to bring production schema up to v1.7.3 standard.

### Deployment Steps

**1. Test Migration Locally First:**
```bash
# In Docker environment
docker-compose exec db mysql -u cfk_user -pcfk_pass cfk_sponsorship_dev < database/migrations/v1.7.3_add_reservation_columns.sql
```

**2. Backup Production Database:**
```bash
# SSH into production and backup
sshpass -p 'HangerAbodeFicesMoved' ssh a4409d26_1@d646a74eb9.nxcli.io \
  "mysqldump -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 > ~/backup_before_v1.7.3_$(date +%Y%m%d_%H%M%S).sql"
```

**3. Upload Migration File:**
```bash
sshpass -p 'HangerAbodeFicesMoved' scp -o StrictHostKeyChecking=no \
  database/migrations/v1.7.3_add_reservation_columns.sql \
  a4409d26_1@d646a74eb9.nxcli.io:~/d646a74eb9.nxcli.io/html/database/migrations/
```

**4. Run Migration in Production:**
```bash
# Connect via SSH and run migration
sshpass -p 'HangerAbodeFicesMoved' ssh a4409d26_1@d646a74eb9.nxcli.io \
  "mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 < ~/d646a74eb9.nxcli.io/html/database/migrations/v1.7.3_add_reservation_columns.sql"
```

**5. Verify Migration:**
```bash
# Check columns were added
sshpass -p 'HangerAbodeFicesMoved' ssh a4409d26_1@d646a74eb9.nxcli.io \
  "mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 -e 'DESCRIBE children;'"
```

---

## Schema Alignment Checklist

- [x] Identified missing columns
- [x] Created migration file
- [ ] Tested migration in local Docker
- [ ] Backed up production database
- [ ] Uploaded migration to production
- [ ] Ran migration in production
- [ ] Verified columns exist
- [ ] Tested reservation functionality
- [ ] Updated production schema.sql

---

## Current Schema Versions

| Component | Local v1.7.3 | Production | Status |
|-----------|--------------|------------|---------|
| `families` table | ✅ Current | ✅ Current | **MATCH** |
| `children` table | ✅ v1.5 Reservation | ❌ v1.4 Pre-Reservation | **MISMATCH** |
| `sponsorships` table | ✅ Current | ✅ Current | **MATCH** |
| `admin_users` table | ✅ v1.5 Password Reset | ❌ v1.3 No Reset | **MISMATCH** |
| `settings` table | ✅ Current | ✅ Current | **MATCH** |

---

## Recommendation

**🚨 DEPLOY MIGRATION BEFORE v1.7.3 CODE**

The v1.7.3 code expects these columns to exist. Deploying code without the migration will cause:
- Reservation creation failures
- Password reset errors
- Potential SQL errors in logs

**Deployment Order:**
1. Run database migration FIRST
2. Deploy v1.7.3 code SECOND
3. Test reservation flow
4. Monitor error logs

---

## Rollback Plan

If migration causes issues:

```bash
# Restore from backup
sshpass -p 'HangerAbodeFicesMoved' ssh a4409d26_1@d646a74eb9.nxcli.io \
  "mysql -u a4409d26_509946 -p'Fests42Cue50Fennel56Auk46' a4409d26_509946 < ~/backup_before_v1.7.3_YYYYMMDD_HHMMSS.sql"
```

All added columns have `DEFAULT NULL` or safe defaults, so rollback is safe and won't cause data loss.
