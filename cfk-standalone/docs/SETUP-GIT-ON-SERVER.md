# ⚠️ IMPORTANT: Setup Git on Live Server

## 🔴 ACTION REQUIRED - Not Yet Completed

We discussed setting up git on the live server but haven't done it yet.

## Why This Matters

Currently deploying via manual SCP file uploads. Setting up git would provide:

1. ✅ Easy deployments with `git pull`
2. ✅ Safe rollbacks if something breaks
3. ✅ See exactly what version is deployed
4. ✅ Atomic deployments (all files update together)
5. ✅ Audit trail with `git log`

## What Needs to Be Done

```bash
# SSH into live server
ssh a4409d26_1@d646a74eb9.nxcli.io

# Navigate to web root
cd /home/a4409d26/public_html

# Initialize git
git init

# Add GitHub as remote
git remote add origin https://github.com/binarybcc/cfk.git

# Fetch all branches
git fetch origin

# Checkout current branch
git checkout v1.5-reservation-system

# Set tracking
git branch --set-upstream-to=origin/v1.5-reservation-system

# Verify
git status
```

## After Setup

Future deployments become super simple:

```bash
# On your local machine - commit and push
git add .
git commit -m "Your changes"
git push origin v1.5-reservation-system

# On live server - deploy
ssh a4409d26_1@d646a74eb9.nxcli.io
cd /home/a4409d26/public_html
git pull origin v1.5-reservation-system
```

## Important Notes

- **Live server should be READ-ONLY** - Never commit/push from server
- **Always work locally** - Push to GitHub, then pull on server
- **GitHub is the authority** - All changes flow through GitHub
- Add `.env` to `.gitignore` so server secrets aren't tracked

## Server Credentials

See: `/Users/johncorbin/Desktop/projs/cfk/cfk-standalone/docs/cfkssh.txt`

---

**Date Created**: October 14, 2025
**Status**: Pending Setup
**Priority**: Medium (current SCP method works, but git would be better)
