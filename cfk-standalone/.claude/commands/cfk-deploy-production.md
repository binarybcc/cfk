# CFK Production Deployment

Deploy Christmas for Kids changes to the live production server at https://cforkids.org

## Environment
- **Server**: d646a74eb9.nxcli.io
- **Credentials**: `.env.production`
- **Remote Path**: `/home/a4409d26/d646a74eb9.nxcli.io/html`
- **Live URL**: https://cforkids.org

## Deployment Protocol

### Step 1: Identify Changed Files

Ask the user which files to deploy, or check git status to identify changed files:

```bash
git status --short
```

Common deployment scenarios:
- Single file: `pages/home.php`
- Multiple files: `pages/home.php assets/css/styles.css`
- All changed files: Use `git diff --name-only HEAD`

**Wait for user confirmation on which files to deploy.**

---

### Step 2: Create Deployment Package

```bash
DEPLOY_DATE=$(date +%Y%m%d-%H%M%S)
PACKAGE_NAME="cfk-deploy-${DEPLOY_DATE}.tar.gz"
PACKAGE_PATH="/tmp/${PACKAGE_NAME}"

# Create tarball with specified files
tar -czf "$PACKAGE_PATH" [FILES_TO_DEPLOY]

# Verify package
ls -lh "$PACKAGE_PATH"
```

Expected: Package size should be reasonable (1-100K for typical changes)

---

### Step 3: Upload to Production

```bash
# Load production credentials
SSH_HOST=d646a74eb9.nxcli.io
SSH_USER=a4409d26_1
SSH_PASSWORD='HangerAbodeFicesMoved'
SSH_REMOTE_PATH=/home/a4409d26/d646a74eb9.nxcli.io/html
BASE_USER=a4409d26

# Upload package
sshpass -p "$SSH_PASSWORD" scp -o StrictHostKeyChecking=no -P 22 \
  "$PACKAGE_PATH" \
  "${SSH_USER}@${SSH_HOST}:/home/${BASE_USER}/"
```

Expected: No errors, silent success

---

### Step 4: Extract Files on Production

```bash
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no -p 22 \
  "${SSH_USER}@${SSH_HOST}" \
  "cd ${SSH_REMOTE_PATH} && tar -xzf /home/${BASE_USER}/${PACKAGE_NAME} && rm /home/${BASE_USER}/${PACKAGE_NAME}"
```

Note: Tar may show warning about `LIBARCHIVE.xattr.com.apple.provenance` - this is harmless macOS metadata

---

### Step 5: Verify Deployment

Check that files were extracted:
```bash
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no -p 22 \
  "${SSH_USER}@${SSH_HOST}" \
  "ls -lh ${SSH_REMOTE_PATH}/[FIRST_DEPLOYED_FILE]"
```

Expected: File timestamp should be recent (today's date)

---

### Step 6: Update Deployment Log

Add entry to `deployment-log.txt`:
```
[DATE] Deployed v1.7.3-production-hardening (COMMIT_HASH) to PRODUCTION - [DESCRIPTION]
```

Format: `[Tue Dec 10 09:33:45 EST 2025]`

---

### Step 7: Commit and Push

```bash
# Commit deployment log update
git add deployment-log.txt
git commit -m "docs: Add deployment log entry for [description]"
git push origin v1.7.3-production-hardening
```

---

### Step 8: Cleanup

```bash
# Remove local package
rm "$PACKAGE_PATH"
```

---

## Success Report Template

After successful deployment, report to user:

```
✅ Production Deployment Complete!

**Deployed Files:**
- [list files]

**Deployment Details:**
- Package: [size]
- Server: d646a74eb9.nxcli.io
- Extracted to: /home/a4409d26/d646a74eb9.nxcli.io/html
- Commit: [hash]

**Live URL:** https://cforkids.org

**Deployment Time:** [timestamp]

All changes are now live on production!
```

---

## Safety Checklist

Before deploying, verify:
- ✅ Changes tested locally
- ✅ User has approved deployment
- ✅ No sensitive data in files
- ✅ Breaking changes assessed
- ✅ Correct branch (v1.7.3-production-hardening)

---

## Rollback Procedure

If deployment causes issues:

1. Find previous deployment package timestamp in deployment-log.txt
2. Re-extract previous version from backup
3. Restart PHP-FPM if needed (contact hosting support)

---

## Common Issues

**Issue**: Upload fails with permission denied
**Solution**: Verify credentials in .env.production are current

**Issue**: Files not updating on website
**Solution**: May need to clear PHP opcache (contact hosting support)

**Issue**: Package too large
**Solution**: Deploy in batches, avoid including large assets unnecessarily
