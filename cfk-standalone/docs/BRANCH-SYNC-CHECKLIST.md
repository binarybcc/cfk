# Branch Synchronization Checklist

**Purpose:** Track which fixes/features have been merged between branches to avoid rediscovering bugs.

## How to Use This

When fixing a bug or adding a feature:
1. Fix it in the appropriate branch
2. Check this document to see which other branches need the same fix
3. Cherry-pick or merge the fix to other branches
4. Update this checklist

## Branch Strategy

- **v1.7.2-phpstan-fixes** - PHPStan compliance and bug fixes
- **v1.8-cleanup** - PSR-4 modernization + all v1.7.2 fixes
- **v1.9-modernization** - Complete OOP modernization

## Fixes Applied (October 2025)

### ✅ v1.7.2 → v1.8 Sync (Oct 27, 2025)

**Features:**
- [x] Auto-assign family numbers and child letters (`8cba020`)
- [x] Magic link email improvements (`8cba020`)
- [x] Age tracking migration age_months/age_years (`8004408`, `3aa9661`, `8aef1b1`, `d7e96aa`, `a843671`)

**Bug Fixes:**
- [x] .gitignore excluding src/Archive/ (`176cb86`)
- [x] Safe array access in year_end_reset.php (`22a157b`)
- [x] Database undefined methods - PHPStan fixes (`aaab678` in v1.7.2, `9002d0d` in v1.8 - equivalent)

**Not Needed in v1.8:**
- [ ] Backup restore fix (`b1cf548`) - v1.8 has different implementation
- [ ] Archive features (`b8a59e9`, `6b5a3fa`, `b0d4ea3`) - v1.8 has refactored Archive/Manager.php

## Future Sync Checklist

**When adding a bug fix:**
1. Identify which branch it affects
2. Apply fix to that branch
3. Check if other branches need the same fix
4. Cherry-pick using: `git cherry-pick <commit-hash>`
5. Update this document

**When merging branches:**
1. Use: `git log branchA ^branchB --oneline` to see what's missing
2. Cherry-pick important fixes
3. Test thoroughly
4. Update this document

## Commands Reference

```bash
# Find commits in v1.7.2 but not in v1.8
git log v1.7.2-phpstan-fixes ^v1.8-cleanup --oneline

# Cherry-pick specific commits
git checkout v1.8-cleanup
git cherry-pick <commit-hash>

# See what changed in a commit
git show <commit-hash>

# Compare two branches
git diff v1.7.2-phpstan-fixes v1.8-cleanup --stat
```

## Last Updated
- **Date:** October 27, 2025
- **By:** Claude Code
- **Status:** v1.8 is up-to-date with all critical v1.7.2 fixes
