# Quick Start Guide - New Machine Setup

**For when you clone this project on your other Mac**

---

## 📥 Step 1: Clone Repository

```bash
cd /Users/user/Development/work/cfk/cfk
git clone https://github.com/binarybcc/cfk.git
cd cfk-standalone
git checkout v1.5-reservation-system
```

---

## 🔑 Step 2: Configure Environment (CRITICAL!)

```bash
# Copy template
cp .env.example .env

# Edit with your settings
nano .env  # or vim, or your editor

# Set secure permissions
chmod 600 .env

# Verify it's not tracked
git status  # Should NOT show .env
```

**Your `.env` should look like:**
```ini
DB_HOST=db
DB_NAME=cfk_sponsorship_dev
DB_USER=cfk_user
DB_PASSWORD=cfk_pass

SMTP_USERNAME=
SMTP_PASSWORD=

APP_DEBUG=true
BASE_URL=http://localhost:8082
```

---

## 🐳 Step 3: Start Docker

```bash
docker-compose up -d

# Verify containers running
docker-compose ps
```

**Expected Output:**
```
cfk-web        Up   0.0.0.0:8082->80/tcp
cfk-mysql      Up   0.0.0.0:3307->3306/tcp
cfk-phpmyadmin Up   0.0.0.0:8081->80/tcp
```

---

## ✅ Step 4: Run Tests

```bash
./tests/security-functional-tests.sh
```

**Expected:**
```
Total Tests: 36
Passed: 35
Failed: 0
✅ All tests passed!
```

---

## 🌐 Step 5: Access Application

- **Main Site:** http://localhost:8082
- **Admin Panel:** http://localhost:8082/admin/
- **phpMyAdmin:** http://localhost:8081

**Default Admin Login:**
- Username: `admin`
- Password: `admin123` (will force password change)

---

## 📚 Step 6: Read These Files

1. **PROJECT-STATUS.md** - Current project state, what to do next
2. **CLAUDE.md** (parent directory) - Full project instructions for Claude Code
3. **docs/audits/v1.5.1-functional-testing-report.md** - Recent work details

---

## 🎯 What You'll Know Immediately

When you clone on the new Mac, Claude Code will automatically:

✅ **Understand the project** (reads CLAUDE.md)
- This is a standalone PHP 8.2+ app (NOT WordPress)
- Uses environment variables (.env files) for all secrets
- Has functional testing infrastructure
- Uses environment notation: 🏠 LOCAL, 🐳 DOCKER, 🌐 PRODUCTION

✅ **Know the current status** (reads PROJECT-STATUS.md)
- v1.5.1 deployed and stable
- 35/36 tests passing
- No known issues
- Ready for use

✅ **Have access to all documentation**
- Security audits in `docs/audits/`
- Deployment guides in `docs/deployment/`
- Component docs in `docs/components/`

---

## ❌ What Claude WON'T Know

❌ **Conversation history from other Mac**
- Won't remember our discussions
- Won't know specific decisions not documented

**Solution:** Tell Claude what you're working on:
```
"I'm continuing work on CFK from another Mac.
 Last work: Security audit complete (see PROJECT-STATUS.md)
 Current need: [what you're doing now]"
```

---

## 💡 Pro Tips

### Daily Workflow
```bash
# Start work
git pull origin v1.5-reservation-system
docker-compose up -d

# During work
./tests/security-functional-tests.sh  # Run tests frequently

# End of day
git add -A
git commit -m "description"
git push origin v1.5-reservation-system
```

### Common Commands
```bash
# View logs
docker-compose logs web -f

# SSH to production
ssh a4409d26_1@d646a74eb9.nxcli.io

# Deploy file to production
sshpass -p 'PiggedCoifSourerFating' scp -P 22 \
  file.php \
  a4409d26_1@d646a74eb9.nxcli.io:/home/a4409d26/d646a74eb9.nxcli.io/html/
```

### Update Status File
After completing major work, update `PROJECT-STATUS.md` so your other Mac knows what's been done.

---

## 🔒 Security Reminders

- **NEVER commit .env files** (they're in .gitignore)
- **ALWAYS use environment variables** for credentials
- **CHECK .env permissions** should be 600
- **PRODUCTION .env** lives only on production server

---

## ✅ Success Checklist

After setup, you should be able to:
- [ ] Clone repository successfully
- [ ] Create and configure .env file
- [ ] Start Docker containers
- [ ] Pass 35/36 automated tests
- [ ] Access http://localhost:8082
- [ ] Login to admin panel
- [ ] See PROJECT-STATUS.md in repository

---

**Total Setup Time:** ~10 minutes

**Questions?** Check `PROJECT-STATUS.md` for detailed information.
