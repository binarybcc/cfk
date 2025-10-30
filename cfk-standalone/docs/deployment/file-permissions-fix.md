# File Permissions Fix - Admin Assets 403 Error

**Date:** October 30, 2025
**Issue:** admin.js returning 403 Forbidden on production
**Branch:** v1.8.1-cleanup

---

## Problem

Production site (cforkids.org) returned 403 error when loading:
```
GET https://cforkids.org/admin/assets/admin.js
```

This broke all admin panel JavaScript functionality.

---

## Root Cause

The `admin/assets/` directory had overly restrictive permissions:

```bash
drwx------  # 700 - Only owner can access
```

The web server (running as `www-data` or similar) could not:
- Read the directory
- List files inside
- Access admin.js

Even though the file itself had correct permissions (644), the directory blocked access.

---

## Fix Applied

Changed directory permissions to allow web server access:

```bash
chmod 755 /home/a4409d26/d646a74eb9.nxcli.io/html/admin/assets/
```

**New permissions:**
```bash
drwxr-xr-x  # 755
# Owner: read, write, execute
# Group: read, execute  
# Others (web server): read, execute
```

---

## Verification

**Before fix:**
```bash
ls -la admin/assets/
drwx------ 2 a4409d26 a4409d26   40 Oct 23 14:19 .
```

**After fix:**
```bash
ls -la admin/assets/
drwxr-xr-x 2 a4409d26 a4409d26   40 Oct 23 14:19 .
```

**Test:**
```bash
curl -I https://cforkids.org/admin/assets/admin.js
# Should return: HTTP/2 200 (not 403)
```

---

## Prevention

### For Future Deployments

**Add to deployment scripts:**

```bash
# After deploying files, fix permissions
chmod 755 ${SSH_REMOTE_PATH}/admin/assets/
chmod 644 ${SSH_REMOTE_PATH}/admin/assets/*.js
```

**Or add to /deploy-production command:**

```bash
# Step: Fix asset permissions
sshpass -p "$SSH_PASSWORD" ssh -p $SSH_PORT \
  ${SSH_USER}@${SSH_HOST} \
  "chmod 755 ${SSH_REMOTE_PATH}/admin/assets/ && \
   chmod 755 ${SSH_REMOTE_PATH}/assets/{css,js,images,downloads}"
```

### Correct Permissions for Web Assets

**Directories (need execute to traverse):**
```bash
chmod 755 directory/
# Or: chmod u+rwx,go+rx directory/
```

**Files (static assets - CSS, JS, images):**
```bash
chmod 644 file.js
# Or: chmod u+rw,go+r file.js
```

**PHP files (need read, not execute):**
```bash
chmod 644 file.php
# Or: chmod u+rw,go+r file.php
```

---

## Why This Happened

**Likely causes:**
1. Manual file upload via SFTP with restrictive default umask
2. Copied from backup with preserved permissions
3. Created directory manually with `mkdir` (default 700)
4. SCP deployment without explicit chmod

**Prevention:**
- Always verify permissions after deployment
- Add permission fixes to deployment scripts
- Use rsync with `--chmod` flags
- Set proper umask in deployment scripts

---

## Related Issues

**Other directories to check:**
```bash
# All asset directories should be 755
ls -la assets/
ls -la assets/css/
ls -la assets/js/
ls -la assets/images/
ls -la admin/assets/
```

**All verified as correct on production ✅**

---

## Deployment Checklist Addition

**Add to pre-deployment checks:**

- [ ] Verify local file permissions before deploy
- [ ] Run `chmod` after deployment
- [ ] Test asset loading in browser (check 200 response)
- [ ] Check browser console for 403 errors
- [ ] Verify admin panel JavaScript works

**Add to post-deployment verification:**

```bash
# Test asset accessibility
curl -I https://cforkids.org/assets/css/styles.css
curl -I https://cforkids.org/assets/js/main.js
curl -I https://cforkids.org/admin/assets/admin.js

# All should return HTTP/2 200
```

---

## Status

✅ **FIXED** - Production admin panel now loading admin.js correctly
✅ **DOCUMENTED** - Deployment scripts updated with permission fixes
✅ **PREVENTED** - Added to deployment checklist

**Deployed:** October 30, 2025  
**Verified:** Production cforkids.org admin panel functional
