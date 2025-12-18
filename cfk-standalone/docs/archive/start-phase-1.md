---
description: Begin Phase 1 of v1.8.1 cleanup (Quality Baseline & Dependency Analysis)
---

# Start Phase 1 - Quality Baseline

**Purpose:** Create comprehensive baseline before cleanup work begins
**Branch:** v1.8.1-cleanup
**Duration:** 2-3 hours
**Risk:** None - read-only analysis

---

## Phase 1 Overview

**Goal:** Understand current state before making changes

**What Phase 1 Does:**
1. Run PHPStan on ALL production code
2. Review dead code analysis
3. Map dependencies
4. Document baseline state

**What Phase 1 Does NOT Do:**
- Does not modify any code
- Does not delete any files
- Does not deploy anything

---

## Prerequisites Check

**Before starting Phase 1:**

```bash
# Verify branch
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "v1.8.1-cleanup" ]; then
    echo "❌ Wrong branch: $CURRENT_BRANCH"
    echo "Switch to: git checkout v1.8.1-cleanup"
    exit 1
fi

# Verify working tree is clean
git diff --quiet
if [ $? -ne 0 ]; then
    echo "⚠️ You have uncommitted changes"
    echo "Commit or stash before starting Phase 1"
fi

# Verify Docker is running
docker --version &> /dev/null
if [ $? -ne 0 ]; then
    echo "⚠️ Docker not running"
    echo "Start Docker: docker-compose up -d"
fi
```

**Report prerequisites:**
```
📋 PREREQUISITES CHECK
======================
Branch: [current branch] [✅ v1.8.1-cleanup / ❌ wrong branch]
Working Tree: [✅ clean / ⚠️ uncommitted changes]
Docker: [✅ running / ❌ not running]

[If any failures, show corrective actions]
```

---

## Phase 1 Step 1: PHPStan Baseline (ALL Production Code)

**Critical: Apply Production First Principle**

Run PHPStan on **ALL directories that run in production:**

```bash
echo "Running PHPStan on ALL production code..."
echo "This may take 2-3 minutes..."

# IMPORTANT: Include admin/, includes/, pages/, cron/ (not just src/)
vendor/bin/phpstan analyse \
  admin/ \
  includes/ \
  pages/ \
  cron/ \
  src/ \
  --level 6 \
  --no-progress \
  --error-format table \
  > phpstan-v1.8.1-baseline.txt 2>&1

# Capture exit code
PHPSTAN_EXIT=$?

echo "PHPStan analysis complete!"
```

**Parse results:**
```bash
# Count errors by directory
echo "Analyzing results by directory..."

ADMIN_ERRORS=$(grep "admin/" phpstan-v1.8.1-baseline.txt | wc -l)
INCLUDES_ERRORS=$(grep "includes/" phpstan-v1.8.1-baseline.txt | wc -l)
PAGES_ERRORS=$(grep "pages/" phpstan-v1.8.1-baseline.txt | wc -l)
CRON_ERRORS=$(grep "cron/" phpstan-v1.8.1-baseline.txt | wc -l)
SRC_ERRORS=$(grep "src/" phpstan-v1.8.1-baseline.txt | wc -l)

TOTAL_ERRORS=$(grep "\[ERROR\]" phpstan-v1.8.1-baseline.txt | wc -l)

# Extract critical errors (first 20)
CRITICAL_ERRORS=$(grep -A 1 "\[ERROR\]" phpstan-v1.8.1-baseline.txt | head -40)
```

**Report:**
```
🔍 PHPSTAN BASELINE COMPLETE
=============================

Scope: ALL production code (Production First Principle)
Directories analyzed:
  - admin/          [N errors]
  - includes/       [N errors]
  - pages/          [N errors]
  - cron/           [N errors]
  - src/            [N errors]

Total Errors: [N]

Error Breakdown:
  Critical:  [N] (type errors, undefined methods)
  Warning:   [N] (null safety, unused variables)
  Info:      [N] (minor issues)

Top 10 Critical Errors:
[Show first 10 errors with file:line]

Full report saved to: phpstan-v1.8.1-baseline.txt
```

**Why This Matters:**
```
📊 This baseline is CRITICAL because:
1. Shows what v1.8-cleanup missed (admin was excluded!)
2. Identifies bugs that need fixing before cleanup
3. Measures improvement after Phase 2 fixes
4. Prevents regressions during Phase 3 deletions
```

---

## Phase 1 Step 2: Review Dead Code Analysis

**Load and validate the existing dead code report:**

```bash
# Check if dead code report exists
if [ -f "docs/audits/dead-code-analysis-report.md" ]; then
    echo "✅ Dead code analysis report found"

    # Extract key findings
    echo "Extracting summary..."

    # Count deprecated files
    DEPRECATED_FILES=$(grep -c "SAFE TO DELETE" docs/audits/dead-code-analysis-report.md || echo "0")

    # Extract total line count
    DEPRECATED_LINES=$(grep "Total Deprecated Lines:" docs/audits/dead-code-analysis-report.md | grep -oE "[0-9,]+" | head -1)

else
    echo "❌ Dead code analysis report not found!"
    echo "Expected location: docs/audits/dead-code-analysis-report.md"
fi
```

**Report:**
```
📄 DEAD CODE ANALYSIS REVIEW
=============================

Report: docs/audits/dead-code-analysis-report.md
Status: [✅ Found / ❌ Not found]

Summary from report:
  Deprecated files: 9
  Total lines to remove: 3,624
  Risk level: LOW (all have replacements)

Files identified for deletion:
  1. includes/archive_manager.php (429 lines)
  2. includes/avatar_manager.php (353 lines)
  3. includes/backup_manager.php (236 lines)
  4. includes/csv_handler.php (561 lines)
  5. includes/email_manager.php (763 lines)
  6. includes/import_analyzer.php (29 lines)
  7. includes/magic_link_manager.php (29 lines)
  8. includes/report_manager.php (394 lines)
  9. includes/sponsorship_manager.php (830 lines)

✅ All files verified to exist in current branch
✅ All replacement classes exist in src/
```

---

## Phase 1 Step 3: Dependency Mapping

**Map all production file dependencies:**

```bash
echo "Mapping production file dependencies..."

# Create dependency map
grep -r "require_once\|require\|include_once\|include" \
  admin/ includes/ pages/ cron/ \
  --include="*.php" \
  > dependency-map.txt

# Count total dependencies
TOTAL_DEPS=$(wc -l < dependency-map.txt)

# Identify files still using deprecated wrappers
echo "Checking for deprecated wrapper usage..."

USING_DEPRECATED=$(grep -E "(archive_manager|avatar_manager|backup_manager|csv_handler|email_manager|import_analyzer|magic_link_manager|report_manager|sponsorship_manager)" dependency-map.txt | wc -l)
```

**Report:**
```
🔗 DEPENDENCY MAPPING COMPLETE
===============================

Total dependencies found: [N]

Files using deprecated wrappers: [N]

[If N > 0, list specific files and line numbers]

Example:
  admin/year_end_reset.php:131 - uses archive_manager.php
  includes/functions.php:471 - uses avatar_manager.php

⚠️ These files must be updated in Phase 2 before deletion in Phase 3
```

**Analyze dependency map:**
```bash
# Group by directory
echo "Dependencies by directory:"
grep "^admin/" dependency-map.txt | wc -l
grep "^includes/" dependency-map.txt | wc -l
grep "^pages/" dependency-map.txt | wc -l
grep "^cron/" dependency-map.txt | wc -l
```

---

## Phase 1 Step 4: Create Baseline Report

**Generate comprehensive baseline documentation:**

```bash
# Create baseline report
cat > docs/v1.8.1-baseline-report.md << 'BASELINE_EOF'
# v1.8.1 Cleanup - Phase 1 Baseline Report

**Date:** $(date)
**Branch:** v1.8.1-cleanup
**Phase:** 1 of 4 (Quality Baseline & Dependency Analysis)

---

## Executive Summary

This baseline establishes the starting point for v1.8.1 cleanup work.

### Key Findings:

**PHPStan Analysis:**
- Total errors: [N]
- admin/ errors: [N]
- includes/ errors: [N]
- pages/ errors: [N]
- cron/ errors: [N]
- src/ errors: [N]

**Dead Code Analysis:**
- Files to delete: 9
- Total lines to remove: 3,624
- Risk level: LOW

**Dependencies:**
- Total production dependencies: [N]
- Files using deprecated wrappers: [N]
- Must fix before deletion: [list files]

---

## Production Code Quality Baseline

**PHPStan Results (Level 6):**

Full report: phpstan-v1.8.1-baseline.txt

Error breakdown by directory:
[Insert table with error counts]

Top critical errors:
[Insert list of top 10 errors]

**Why This Matters:**
This is the FIRST time admin/, includes/, pages/, and cron/ have been
included in comprehensive PHPStan analysis. Previous v1.8 cleanup
excluded these directories, which hid critical bugs.

---

## Dead Code Cleanup Plan

**Files identified for deletion (Phase 3):**

[List 9 deprecated wrapper files with line counts]

**Replacement classes (in src/):**

[List corresponding PSR-4 classes]

**Action Required Before Deletion:**

[List files that need updating to use namespaced classes]

---

## Dependency Analysis

**Production file dependency count:**
- admin/: [N] dependencies
- includes/: [N] dependencies
- pages/: [N] dependencies
- cron/: [N] dependencies

**Files using deprecated wrappers:**
[List with file:line numbers]

---

## Phase 2 Priorities

Based on this baseline, Phase 2 should focus on:

1. **Critical PHPStan Errors** (must fix):
   [List top 5-10 critical errors]

2. **Deprecated Wrapper References** (must update):
   [List files that need updating]

3. **Type Safety Issues** (should fix):
   [List null safety and type hint issues]

---

## Success Metrics

**Starting Point (Phase 1 Baseline):**
- PHPStan errors: [N]
- Deprecated code: 3,624 lines
- Production code coverage: 100%

**Target (After Phase 4 Complete):**
- PHPStan errors: <50% of baseline
- Deprecated code: 0 lines
- Production code coverage: 100%
- Functional tests: 35/36 passing (no regression)

---

## Next Steps

**Phase 2: Critical Fixes**
1. Fix critical PHPStan errors
2. Update files using deprecated wrappers
3. Test thoroughly
4. Document fixes

See: docs/v1.8.1-cleanup-plan.md for Phase 2 details

---

**Phase 1 Status:** ✅ COMPLETE
**Ready for Phase 2:** [✅ Yes / ❌ No - issues to resolve first]

BASELINE_EOF

echo "Baseline report created: docs/v1.8.1-baseline-report.md"
```

---

## Phase 1 Complete Summary

**Report to user:**
```
═══════════════════════════════════════
✅ PHASE 1 COMPLETE
═══════════════════════════════════════

Baseline Established:

📊 PHPStan Analysis:
   - ALL production code analyzed
   - [N] total errors found
   - Baseline saved: phpstan-v1.8.1-baseline.txt

📄 Dead Code Review:
   - 9 files identified for deletion
   - 3,624 lines to remove
   - All replacements verified

🔗 Dependencies Mapped:
   - [N] production dependencies found
   - [N] files using deprecated wrappers
   - Saved: dependency-map.txt

📝 Documentation:
   - Baseline report: docs/v1.8.1-baseline-report.md
   - Ready for Phase 2

═══════════════════════════════════════

Next Steps:

1. Review baseline report:
   cat docs/v1.8.1-baseline-report.md

2. Review PHPStan results:
   cat phpstan-v1.8.1-baseline.txt

3. Review dependency map:
   cat dependency-map.txt

4. When ready, start Phase 2:
   /start-phase-2

═══════════════════════════════════════
```

---

## Files Created

**Phase 1 generates these files:**
```
phpstan-v1.8.1-baseline.txt           # PHPStan results
dependency-map.txt                     # All production dependencies
docs/v1.8.1-baseline-report.md        # Comprehensive baseline report
```

**Review these files before proceeding to Phase 2.**

---

## Troubleshooting

### PHPStan Fails:
```bash
# Clear cache and retry
rm -rf .phpunit.cache/
vendor/bin/phpstan clear-result-cache
/start-phase-1
```

### Dead Code Report Missing:
```bash
# Verify file location
ls -la docs/audits/dead-code-analysis-report.md

# If missing, check git history
git log --all --full-history -- docs/audits/dead-code-analysis-report.md
```

### Dependency Map Empty:
```bash
# Verify production files exist
ls admin/ includes/ pages/ cron/

# Run grep manually
grep -r "require" admin/ --include="*.php"
```

---

## Phase 1 Checklist

Before moving to Phase 2, verify:

- [ ] PHPStan baseline created (phpstan-v1.8.1-baseline.txt)
- [ ] Dependency map created (dependency-map.txt)
- [ ] Baseline report created (docs/v1.8.1-baseline-report.md)
- [ ] Dead code analysis reviewed
- [ ] Files using deprecated wrappers identified
- [ ] No errors during analysis
- [ ] All artifacts reviewed and understood

**When all items checked:** ✅ Ready for Phase 2 (`/start-phase-2`)

---

**Remember:** Phase 1 is read-only. No code is modified or deleted. This creates a safe baseline to measure progress against.
