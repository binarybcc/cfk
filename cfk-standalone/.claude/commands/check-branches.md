---
description: Check all feature branches for new commits since last merge
---

# Check All Branches for Updates

**Purpose:** Find new commits on feature/parent branches that haven't been merged yet
**When to use:** Daily or when you suspect branches have new work
**Risk:** None - read-only operation

---

## Branch Check Protocol

This command checks multiple branches to see if they have new commits that aren't in your current branch yet.

### Step 1: Show Current Context

**Display current status:**
```bash
# Show current branch
CURRENT_BRANCH=$(git branch --show-current)
echo "📍 Current branch: $CURRENT_BRANCH"

# Show latest commit
git log -1 --oneline
```

### Step 2: Fetch Latest from Remote

**Fetch without modifying local files:**
```bash
# Fetch all branches
git fetch --all --prune 2>/dev/null
```

### Step 3: Check All Relevant Branches

**Run the check-all-branches script:**
```bash
./check-all-branches.sh
```

**What it checks:**
- v1.7.3-production-hardening (parent/production branch)
- Any active feature branches

**Output format:**
```
🔄 Checking all branches for new commits...

📍 Current branch: v1.9.3

Checking for new commits on feature branches...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⚠️  v1.7.3-production-hardening
   → 5 new commit(s) since last merge

   Recent commits:
   abc1234 fix: Important production fix
   def5678 feat: New feature added
   ghi9012 docs: Updated documentation

   To merge: git merge origin/v1.7.3-production-hardening
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ Check complete!
```

### Step 4: Interpret Results

**For each branch:**

#### ✅ Up to Date
```
✅ branch-name (up to date)
```
**Meaning:** No action needed - this branch has been fully merged

#### ⚠️ New Commits Available
```
⚠️ branch-name
   → N new commit(s) since last merge

   Recent commits:
   [list of new commits]

   To merge: git merge origin/branch-name
```

**Meaning:** This branch has new work you haven't merged yet

**Action:** Decide whether to merge:
- **Production fixes?** → Merge them (keep in sync with production)
- **Feature work?** → Review commits, merge if relevant
- **Experimental work?** → Wait until stable

#### ❌ Not Found
```
❌ branch-name (not found on remote)
```
**Meaning:** Branch doesn't exist or was deleted

### Step 5: Merge Recommendations

**When to merge a branch with new commits:**

**Always merge:**
- ✅ Production bug fixes
- ✅ Security patches
- ✅ Critical hotfixes

**Usually merge:**
- ✅ Parent branch updates (v1.7.3-production-hardening)
- ✅ Related feature work from other branches
- ✅ Shared dependency updates

**Evaluate first:**
- ⚠️ Experimental features
- ⚠️ Breaking changes
- ⚠️ Large refactors

**Before merging, ask yourself:**
1. Are these changes relevant to my current work?
2. Will these break my current branch?
3. Are these production-tested or experimental?
4. Do I need these changes now or can I wait?

### Step 6: Merging Updates

**If you decide to merge:**

```bash
# Merge specific branch
git merge origin/branch-name

# If conflicts occur, resolve them
# (edit conflicted files, then:)
git add .
git commit

# Push merged changes
git push origin $(git branch --show-current)
```

**After merging:**
- Test your code still works
- Run quality checks if significant changes
- Deploy to staging to verify

---

## Customizing Checked Branches

**To add/remove branches from checking:**

Edit `check-all-branches.sh`:

```bash
BRANCHES_TO_CHECK=(
    "v1.7.3-production-hardening"
    "your-new-branch-here"  # Add new branch
)
```

---

## Typical Scenarios

### Scenario 1: Daily Check (Most Common)

**When:** Start of work session

**Workflow:**
```bash
/check-branches

# If branches are up to date:
→ Continue working

# If new commits found:
→ Review commits
→ Decide whether to merge
→ Merge if beneficial
```

### Scenario 2: After Team Push

**When:** Someone else pushed to a branch

**Workflow:**
```bash
/check-branches

# See what changed:
git log HEAD..origin/branch-name

# Merge if needed:
git merge origin/branch-name
```

### Scenario 3: Before Major Work

**When:** Starting a big feature

**Workflow:**
```bash
/check-branches

# Make sure all branches are merged before starting
# This prevents conflicts later
```

---

## Comparison with /sync-check

**Both commands check sync status, but different scope:**

| Command | What It Checks | When to Use |
|---------|---------------|-------------|
| `/sync-check` | Current branch vs its remote | Every session start |
| `/check-branches` | Multiple branches vs current | Daily or when expecting updates |

**Recommended workflow:**
```bash
# 1. Check your branch is synced
/sync-check

# 2. Check if other branches have updates
/check-branches

# 3. Merge any important updates
git merge origin/branch-name

# 4. Start working
```

---

## Output Examples

### Example 1: All Up to Date
```
🔄 Checking all branches for new commits...
📍 Current branch: v1.9.3

✅ v1.7.3-production-hardening (up to date)

✅ Check complete!
```
**Action:** None needed - continue working

### Example 2: Updates Available
```
🔄 Checking all branches for new commits...
📍 Current branch: v1.9.3

⚠️  v1.7.3-production-hardening
   → 3 new commit(s) since last merge

   Recent commits:
   abc1234 fix: SQL injection vulnerability patch
   def5678 fix: Session timeout issue
   ghi9012 docs: Update security guidelines

   To merge: git merge origin/v1.7.3-production-hardening
```
**Action:** Review commits → Merge if important

---

## Integration with Development Workflow

**Daily routine:**
```
Morning:
1. /sync-check          → Am I in sync?
2. /check-branches      → Any updates from others?
3. Merge if needed      → Stay current
4. Start working        → Build features

Before committing:
1. /quality-check       → Code quality OK?
2. git commit           → Save work
3. git push             → Share work

Before deploying:
1. /check-branches      → All updates merged?
2. /sync-check          → Branch in sync?
3. /deploy-staging      → Test changes
```

---

## Quick Reference

**Check all branches:**
```bash
/check-branches
```

**Manual check:**
```bash
./check-all-branches.sh
```

**Add branch to check list:**
Edit `check-all-branches.sh` and add to `BRANCHES_TO_CHECK` array

**Merge updates:**
```bash
git merge origin/branch-name
```

---

## Important Notes

**This command is read-only:**
- ✅ Fetches from remote
- ✅ Compares commits
- ✅ Shows what's new
- ❌ Doesn't modify your code
- ❌ Doesn't merge automatically

**You control what gets merged** - the command just tells you what's available.

---

**Remember:** Staying in sync with parent/production branches prevents divergence and makes merges easier later!
