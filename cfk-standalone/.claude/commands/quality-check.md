---
description: Quick pre-commit quality checks (PHPStan, debug code, security)
---

# Quality Check (Pre-Commit)

**Purpose:** Fast quality verification before committing code
**When to use:** Before every git commit
**Duration:** 30 seconds - 2 minutes

---

## Quick Quality Check Protocol

Lightweight checks that catch common issues before they reach the repository.

### Step 1: Show What's Being Committed

**Display changes that will be committed:**
```bash
# Show staged files
git diff --name-only --cached

# Count staged changes
STAGED_COUNT=$(git diff --name-only --cached | wc -l)

# Show brief diff summary
git diff --cached --stat
```

**Report:**
```
📝 STAGED CHANGES
=================
Files to commit: [N]

[List files being committed]

Summary:
[Show git diff --stat output]
```

### Step 2: PHPStan on Changed Files Only

**Run PHPStan only on staged PHP files:**
```bash
# Get staged PHP files
STAGED_PHP=$(git diff --name-only --cached | grep "\.php$")

if [ -n "$STAGED_PHP" ]; then
    echo "Running PHPStan on changed files..."
    echo "$STAGED_PHP" | xargs vendor/bin/phpstan analyse --level 6 --no-progress
    PHPSTAN_EXIT=$?
else
    echo "No PHP files staged"
    PHPSTAN_EXIT=0
fi
```

**Report:**
```
🔍 PHPSTAN QUICK CHECK
======================
Files analyzed: [N PHP files]

Results:
[✅ No errors / ⚠️ N warnings / ❌ N errors]

[If errors, show first 5 with line numbers]
```

**Threshold:**
- ✅ 0 errors: PASS
- ⚠️ 1-5 errors: WARN (review before commit)
- ❌ 6+ errors: FAIL (must fix before commit)

### Step 3: Check for Debug Code

**Search for debug statements in staged files:**
```bash
# Check staged files for debug code
git diff --cached | grep -E "^\+.*(var_dump|print_r|dd\(|dump\(|console\.log)" --color=always

DEBUG_COUNT=$(git diff --cached | grep -E "^\+.*(var_dump|print_r|dd\(|dump\(|console\.log)" | wc -l)
```

**Report:**
```
🐛 DEBUG CODE CHECK
===================
Debug statements found: [N]

[If found, show each occurrence with file and line number]

⚠️ Debug code should not be committed to production branches!
```

**Action if found:**
- ❌ FAIL if on production branch (v1.7.3, v1.8.1, main)
- ⚠️ WARN if on feature branch (can be OK for development)

### Step 4: Check for Sensitive Data

**Scan for potential secrets or credentials:**
```bash
# Check for common secret patterns
git diff --cached | grep -iE "^\+.*(password|secret|api_key|token|credentials)" --color=always

# Check for hardcoded credentials
git diff --cached | grep -E "^\+.*=.*['\"].*password.*['\"]" -i --color=always

SECRET_COUNT=$(git diff --cached | grep -iE "^\+.*(password|secret|api_key|token|credentials)" | wc -l)
```

**Report:**
```
🔐 SECURITY CHECK
=================
Potential secrets found: [N]

[If found, show each line for review]

⚠️ Review these carefully! Are they actual secrets or just variable names?
```

**Whitelist (safe patterns):**
- `$password` (variable name - OK)
- `PASSWORD_HASH` (constant - OK)
- `password_verify()` (function - OK)

**Blocklist (dangerous patterns):**
- `password = "actual_password"` (hardcoded - NOT OK)
- `API_KEY = "sk_live_..."` (hardcoded - NOT OK)

### Step 5: Check for .env Files

**Ensure no .env files are being committed:**
```bash
# Check if any .env files are staged
git diff --name-only --cached | grep "\.env"
ENV_STAGED=$?

# Also check if .env is tracked by git at all
git ls-files | grep "\.env$" | grep -v "\.env\.example"
ENV_TRACKED=$?
```

**Report:**
```
📄 ENV FILES CHECK
==================
.env files staged: [✅ None / ❌ Found!]
.env files tracked: [✅ None / ❌ Found!]

[If found, list the .env files]

❌ CRITICAL: .env files contain secrets and should NEVER be committed!
```

**Action if found:**
- ❌ FAIL immediately
- Provide unstaging commands
- Remind about .gitignore

### Step 6: Check for TODO/FIXME Comments

**Count TODO/FIXME in staged changes:**
```bash
# Count new TODOs being added
git diff --cached | grep -E "^\+.*(TODO|FIXME)" --color=always
TODO_COUNT=$(git diff --cached | grep -E "^\+.*(TODO|FIXME)" | wc -l)
```

**Report:**
```
📌 TODO/FIXME CHECK
===================
New TODOs added: [N]

[If found, list them]

ℹ️ TODOs are OK, but make sure they're tracked in project documentation
```

**This is informational only - not a failure.**

### Step 7: Verify Commit Message Required

**Remind about commit message guidelines:**
```
📝 COMMIT MESSAGE REMINDER
==========================

Your commit message should:
✅ Start with type: feat, fix, docs, refactor, test, chore
✅ Be concise but descriptive
✅ Explain WHY, not just WHAT
✅ Reference issues if applicable

Examples:
  ✅ feat: Add CSV import validation for age fields
  ✅ fix: Prevent null pointer in child edit modal
  ✅ docs: Update deployment guide for v1.8.1
  ❌ update files
  ❌ changes
  ❌ wip
```

### Step 8: Quality Check Summary

**Aggregate results:**
```
═══════════════════════════════════════
📊 QUALITY CHECK SUMMARY
═══════════════════════════════════════

Changes:
  Files staged:     [N]
  PHP files:        [N]

Code Quality:
  PHPStan:          [✅ Pass / ⚠️ Warnings / ❌ Errors]
  Debug code:       [✅ None / ⚠️ Found]
  TODOs added:      [N]

Security:
  Secrets check:    [✅ Pass / ⚠️ Review / ❌ Found]
  .env files:       [✅ None / ❌ Found!]

═══════════════════════════════════════
STATUS: [✅ READY TO COMMIT / ⚠️ REVIEW / ❌ BLOCKED]
═══════════════════════════════════════
```

### Step 9: Recommendations

**Based on results:**

#### ✅ All Checks Pass (READY TO COMMIT)
```
✅ ALL QUALITY CHECKS PASSED!

Your changes are ready to commit.

Next steps:
1. Write descriptive commit message
2. Commit: git commit -m "type: description"
3. Check sync: /sync-check
4. Push: git push origin [branch]

Example commit:
git commit -m "feat: Add environment-specific deployment system with safety checks"
```

#### ⚠️ Warnings Found (REVIEW BEFORE COMMIT)
```
⚠️ QUALITY CHECKS PASSED WITH WARNINGS

Issues to review:
[List specific warnings]

Recommendations:
- Review warnings above carefully
- Consider fixing before committing
- If intentional, document in commit message

Still OK to commit if:
- Warnings are intentional (e.g., dev debug code on feature branch)
- Documented in commit message why they're OK

Proceed with caution!
```

#### ❌ Checks Failed (DO NOT COMMIT)
```
❌ QUALITY CHECKS FAILED - DO NOT COMMIT

Critical issues:
[List failures]

Action required:
1. Fix critical issues listed above
2. Re-run: /quality-check
3. DO NOT commit until checks pass

Common fixes:
- PHPStan errors: Fix type hints, null safety
- Debug code: Remove var_dump, print_r
- .env files: Unstage and add to .gitignore
- Secrets: Remove hardcoded credentials

To unstage files:
git restore --staged [filename]
```

---

## Special Cases

### Feature Branch (Development)

**More lenient on:**
- Debug code (OK for development)
- TODOs (expected during development)
- Warning-level PHPStan issues

**Still strict on:**
- .env files (never OK)
- Hardcoded secrets (never OK)
- Critical PHPStan errors

### Production Branch (v1.7.3, v1.8.1, main)

**Strict on everything:**
- ❌ No debug code
- ❌ No PHPStan errors
- ❌ No secrets
- ❌ No .env files
- ⚠️ Minimal TODOs

### Hotfix Branch

**Fast-tracked but still secure:**
- ✅ Security checks still required
- ✅ PHPStan on changed files only
- ⚠️ Can skip comprehensive tests (use /test-full after)

---

## How to Fix Common Issues

### PHPStan Errors:
```bash
# View error details
vendor/bin/phpstan analyse [file] --level 6

# Common fixes:
# - Add type hints: function foo(string $param): int
# - Check for null: $var ?? 'default'
# - Fix return types
```

### Debug Code:
```bash
# Find all debug statements
grep -r "var_dump\|print_r\|dd(" [file]

# Remove them manually or use sed
sed -i '' '/var_dump\|print_r/d' [file]
```

### .env Files Staged:
```bash
# Unstage .env file
git restore --staged .env

# Ensure in .gitignore
echo ".env" >> .gitignore
echo ".env.production" >> .gitignore
echo ".env.staging" >> .gitignore

# Remove from git history if already committed (DANGER!)
# git rm --cached .env
```

### Hardcoded Secrets:
```bash
# Move to .env file
echo "SECRET_KEY=your_secret" >> .env

# Use in code
$secretKey = getenv('SECRET_KEY');
```

---

## Integration with Git Hooks

**This skill can be used as a pre-commit hook:**

```bash
# .git/hooks/pre-commit
#!/bin/bash

# Run quality check
# (Would need to adapt for non-Claude environment)

# Exit code determines if commit proceeds
exit $?
```

---

## Quick Command Reference

**Run before commit:**
```bash
/quality-check
```

**If issues found:**
```bash
# Fix issues
# Re-run check
/quality-check

# When passing, commit
git commit -m "type: description"
```

**Skip check (NOT RECOMMENDED):**
```bash
# Force commit (bypasses checks)
git commit -m "message" --no-verify

# Only use in emergencies!
```

---

## When to Run

**Always before:**
- Every git commit
- Pushing to remote
- Creating pull requests

**Optional but recommended:**
- After resolving merge conflicts
- After large refactoring
- After dependency updates

---

**Remember:** Quality checks catch issues early. It's much easier to fix problems before they're committed than after they're in the repository!
