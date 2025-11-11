#!/bin/bash

# Check All Branches for Updates
# Run this to see if any branches have new commits since your last merge

echo "🔄 Checking all branches for new commits..."
echo ""

# Fetch latest from remote
git fetch --all --prune 2>/dev/null

# Get current branch
CURRENT_BRANCH=$(git branch --show-current)
echo "📍 Current branch: $CURRENT_BRANCH"
echo ""

# List of branches to check (add more as needed)
BRANCHES_TO_CHECK=(
    "claude/v1.9.2-architecture-review-011CUtxqyytmMP363MuDLrGW"
    "claude/week5-children-refactor-011CUtxqyytmMP363MuDLrGW"
    "v1.7.3-production-hardening"
)

echo "Checking for new commits on feature branches..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

for BRANCH in "${BRANCHES_TO_CHECK[@]}"; do
    # Check if branch exists on remote
    if git rev-parse --verify "origin/$BRANCH" >/dev/null 2>&1; then
        # Count new commits since last merge
        NEW_COMMITS=$(git log $CURRENT_BRANCH..origin/$BRANCH --oneline 2>/dev/null | wc -l | tr -d ' ')

        if [ "$NEW_COMMITS" -gt 0 ]; then
            echo ""
            echo "⚠️  $BRANCH"
            echo "   → $NEW_COMMITS new commit(s) since last merge"
            echo ""
            echo "   Recent commits:"
            git log $CURRENT_BRANCH..origin/$BRANCH --oneline -3 | sed 's/^/   /'
            echo ""
            echo "   To merge: git merge origin/$BRANCH"
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        else
            echo "✅ $BRANCH (up to date)"
        fi
    else
        echo "❌ $BRANCH (not found on remote)"
    fi
done

echo ""
echo "✅ Check complete!"
