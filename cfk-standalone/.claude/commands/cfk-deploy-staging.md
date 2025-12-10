# CFK Staging Deployment

Deploy Christmas for Kids changes to the staging/testing server for preview before production

## Environment
- **Credentials**: `.env.staging`
- **Purpose**: Test changes before production deployment
- **Note**: Staging server details loaded from .env.staging file

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

### Step 2: Load Staging Credentials

Read staging credentials from `.env.staging`:

```bash
source .env.staging
echo "✅ Loaded staging credentials"
echo "Deploying to: $SSH_HOST"
```

Verify credentials are loaded:
- SSH_HOST
- SSH_USER
- SSH_PASSWORD
- SSH_REMOTE_PATH
- SSH_PORT (default: 22)

---

### Step 3: Create Deployment Package

```bash
DEPLOY_DATE=$(date +%Y%m%d-%H%M%S)
PACKAGE_NAME="cfk-staging-deploy-${DEPLOY_DATE}.tar.gz"
PACKAGE_PATH="/tmp/${PACKAGE_NAME}"

# Create tarball with specified files
tar -czf "$PACKAGE_PATH" [FILES_TO_DEPLOY]

# Verify package
ls -lh "$PACKAGE_PATH"
```

Expected: Package size should be reasonable (1-100K for typical changes)

---

### Step 4: Upload to Staging

```bash
# Extract base username (remove suffix if present)
BASE_USER=$(echo "$SSH_USER" | cut -d'_' -f1)

# Upload package
sshpass -p "$SSH_PASSWORD" scp -o StrictHostKeyChecking=no -P ${SSH_PORT:-22} \
  "$PACKAGE_PATH" \
  "${SSH_USER}@${SSH_HOST}:/home/${BASE_USER}/"
```

Expected: No errors, silent success

---

### Step 5: Extract Files on Staging

```bash
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no -p ${SSH_PORT:-22} \
  "${SSH_USER}@${SSH_HOST}" \
  "cd ${SSH_REMOTE_PATH} && tar -xzf /home/${BASE_USER}/${PACKAGE_NAME} && rm /home/${BASE_USER}/${PACKAGE_NAME}"
```

Note: Tar may show warning about `LIBARCHIVE.xattr.com.apple.provenance` - this is harmless macOS metadata

---

### Step 6: Verify Deployment

Check that files were extracted:
```bash
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no -p ${SSH_PORT:-22} \
  "${SSH_USER}@${SSH_HOST}" \
  "ls -lh ${SSH_REMOTE_PATH}/[FIRST_DEPLOYED_FILE]"
```

Expected: File timestamp should be recent (today's date)

---

### Step 7: Cleanup

```bash
# Remove local package
rm "$PACKAGE_PATH"
```

---

## Success Report Template

After successful deployment, report to user:

```
✅ Staging Deployment Complete!

**Deployed Files:**
- [list files]

**Deployment Details:**
- Package: [size]
- Server: [from .env.staging]
- Extracted to: [SSH_REMOTE_PATH]

**Staging URL:** [SITE_URL from .env.staging]

**Deployment Time:** [timestamp]

**Next Steps:**
1. Test changes on staging environment
2. If everything looks good, run /cfk-deploy-production
3. If issues found, fix and redeploy to staging
```

---

## Safety Checklist

Before deploying to staging:
- ✅ Changes committed locally
- ✅ No sensitive data in files
- ✅ Ready for testing
- ✅ Correct branch

---

## Testing on Staging

After deployment, test:
1. Navigate to staging URL
2. Verify deployed changes appear
3. Test functionality
4. Check for errors in browser console
5. Test on mobile if relevant

---

## Common Workflow

**Typical deployment flow:**
1. Make changes locally
2. Test in Docker (localhost:8082)
3. Deploy to staging: `/cfk-deploy-staging`
4. Test on staging server
5. If good, deploy to production: `/cfk-deploy-production`
6. Verify on live site

---

## Common Issues

**Issue**: Upload fails with permission denied
**Solution**: Verify credentials in .env.staging are current

**Issue**: Can't access staging URL
**Solution**: Check SITE_URL in .env.staging, may need VPN or IP whitelist

**Issue**: Files not updating
**Solution**: Clear browser cache, check file timestamps on server
