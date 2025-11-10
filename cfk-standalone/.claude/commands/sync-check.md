---
description: Check if local repository is in sync with remote (run at session start)
---

# Repository Sync Check

**Purpose:** Verify local branch is synchronized with remote repository
**When to use:** At the start of every session before making changes
**Risk:** None - read-only operation

---

## Sync Check Protocol

Run this check at the beginning of each session to ensure you're working with the latest code.

### Step 1: Show Current Status

**Display current context:**
```bash
# Show current branch
git branch --show-current

# Show current commit
git log -1 --oneline

# Show working tree status
git status --short
```

**Report to user:**
```
📍 Current Branch: [branch-name]
📝 Latest Commit: [commit-hash] [commit-message]
📊 Working Tree: [clean/modified]
```

### Step 2: Fetch Remote Changes

**Fetch without modifying local files:**
```bash
# Fetch all remote changes
git fetch --all --prune
```

**Note:** This only downloads remote changes, doesn't modify your local files.

### Step 3: Compare Local vs Remote

**Check sync status:**
```bash
# Get current branch
BRANCH=$(git branch --show-current)

# Check if remote branch exists
git rev-parse --verify origin/$BRANCH >/dev/null 2>&1
REMOTE_EXISTS=$?

if [ $REMOTE_EXISTS -eq 0 ]; then
    # Count commits ahead/behind
    AHEAD=$(git rev-list --count origin/$BRANCH..$BRANCH)
    BEHIND=$(git rev-list --count $BRANCH..origin/$BRANCH)

    echo "Ahead: $AHEAD"
    echo "Behind: $BEHIND"
else
    echo "No remote branch"
fi
```

### Step 4: Determine Sync State

**Analyze and report:**

#### ✅ Case 1: Up to Date (Behind = 0, Ahead = 0)
```
✅ SYNCHRONIZED

Your local branch is up to date with origin.
Status: Ready to work!

Action needed: None
```

#### ⚠️ Case 2: Behind Remote (Behind > 0, Ahead = 0)
```
⚠️ BEHIND REMOTE

Your local branch is behind origin by [N] commits.

Recent remote commits you don't have:
[Show: git log --oneline HEAD..origin/$(git branch --show-current) -5]

Action needed: Pull remote changes
Command: git pull origin [branch-name]
```

#### ⚠️ Case 3: Ahead of Remote (Behind = 0, Ahead > 0)
```
⚠️ AHEAD OF REMOTE

Your local branch is ahead of origin by [N] commits.

Your unpushed commits:
[Show: git log --oneline origin/$(git branch --show-current)..HEAD -5]

Action needed: Push your commits (when ready)
Command: git push origin [branch-name]
```

#### ❌ Case 4: Diverged (Behind > 0, Ahead > 0)
```
❌ DIVERGED

Your local branch and origin have diverged.
Local ahead by: [N] commits
Remote ahead by: [N] commits

This means:
- You have local commits not on remote
- Remote has commits you don't have locally

Action needed: Merge or rebase
Options:
1. git pull origin [branch] (merge)
2. git pull --rebase origin [branch] (rebase)

⚠️ Recommend: Review commits before merging
```

#### 🆕 Case 5: No Remote Branch
```
🆕 NEW LOCAL BRANCH

This branch doesn't exist on the remote yet.

Action needed: Push to create remote branch
Command: git push -u origin [branch-name]
```

### Step 5: Check for Uncommitted Changes

**If working tree has changes:**
```bash
# Show uncommitted changes
git status --short

# Count uncommitted files
MODIFIED=$(git status --short | wc -l)
```

**Report:**
```
📝 UNCOMMITTED CHANGES DETECTED

You have [N] uncommitted changes:
[Show: git status --short]

Reminder: Commit or stash before pulling/switching branches
```

### Step 6: Show Recent Activity

**Display recent commit history:**
```bash
# Show last 5 commits
git log --oneline -5 --decorate
```

**Report:**
```
📜 Recent Commits (Last 5):
[commit list]
```

### Step 7: Recommendations

**Based on sync state, provide actionable recommendations:**

**If behind:**
```
💡 Recommendation:
1. Review remote commits: git log HEAD..origin/[branch]
2. Pull changes: git pull origin [branch]
3. Resolve any conflicts if they occur
4. Test after pulling
```

**If ahead:**
```
💡 Recommendation:
1. Review your commits: git log origin/[branch]..HEAD
2. Push when ready: git push origin [branch]
3. Consider creating a PR if on feature branch
```

**If diverged:**
```
💡 Recommendation:
1. View divergence: git log --oneline --graph --all -10
2. Decide: merge or rebase
3. If unsure, ask before proceeding
4. Consider creating backup branch first
```

**If up to date with uncommitted changes:**
```
💡 Recommendation:
1. Complete your changes
2. Run tests: /test-full
3. Commit with descriptive message
4. Push to remote
```

---

## Complete Example Output

```
🔄 REPOSITORY SYNC CHECK
========================

📍 Current Branch: v1.8.1-cleanup
📝 Latest Commit: 4e9e820 docs: Add deployment system completion summary
📊 Working Tree: clean

Fetching remote changes...
✓ Fetch complete

Checking sync status...

✅ SYNCHRONIZED

Your local branch is up to date with origin/v1.8.1-cleanup.
Status: Ready to work!

📜 Recent Commits (Last 5):
4e9e820 docs: Add deployment system completion summary
1f52624 feat: Add environment-specific deployment system
22e1b64 docs: Add v1.8.1 branch ready summary
d03f6a6 docs: Create v1.8.1-cleanup branch
e8c3b17 fix: Remove reference to non-existent childLetter field

✅ All systems ready - you can start working!
```

---

## Error Handling

**If git fetch fails:**
```
❌ Cannot connect to remote repository

Possible causes:
1. No internet connection
2. Invalid credentials
3. Remote repository unavailable

Try:
- Check internet connection
- Verify GitHub is accessible
- Check SSH keys: ssh -T git@github.com
```

**If not in a git repository:**
```
❌ Not a git repository

This command must be run from within a git repository.
Current directory: [pwd]
```

---

## When to Run This

**Always run at session start:**
- ✅ Beginning of every work session
- ✅ After being away from project for a while
- ✅ Before starting new feature work
- ✅ Before deploying to staging/production
- ✅ When returning from another branch

**Also useful:**
- After someone else pushes to the branch
- Before creating a pull request
- When resolving merge conflicts

---

## Integration with Other Skills

**Natural workflow:**
```
1. /sync-check          → Verify repository state
2. [Make changes]       → Develop your feature
3. /quality-check       → Pre-commit checks
4. git commit           → Commit changes
5. /sync-check          → Check if still in sync
6. git push             → Push to remote
7. /deploy-staging      → Test in staging
```

---

## Quick Reference

**Common scenarios:**

| Status | What It Means | What To Do |
|--------|--------------|------------|
| ✅ Up to date | Local = Remote | Start working |
| ⚠️ Behind | Remote has new commits | `git pull` |
| ⚠️ Ahead | You have unpushed commits | `git push` when ready |
| ❌ Diverged | Both have unique commits | Merge/rebase needed |
| 🆕 No remote | New local branch | `git push -u origin [branch]` |

---

**Remember:** This is a read-only check. It won't modify your code - it just tells you the sync status.
