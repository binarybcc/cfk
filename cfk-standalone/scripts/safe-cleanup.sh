#!/bin/bash
# Safe Git Cleanup Script
# Cleans untracked files but protects critical environment files

echo "🧹 Safe Cleanup Script"
echo "===================="
echo ""

# Check if we're in a git repo
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo "❌ Not a git repository"
    exit 1
fi

# Show what would be cleaned (standard cleanup)
echo "📋 Files that would be removed with 'git clean -fd':"
git clean -n -fd
echo ""

# Warn about protected files
echo "🔒 Protected files (will NOT be removed):"
for file in .env .env.staging .env.production; do
    if [ -f "$file" ]; then
        echo "  ✅ $file (gitignored, preserved)"
    fi
done
echo ""

# Ask for confirmation
read -p "Proceed with cleanup? This will DELETE untracked files. (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "❌ Cleanup cancelled"
    exit 0
fi

# Perform safe cleanup (doesn't touch .gitignore files)
echo ""
echo "🧹 Running: git clean -fd"
git clean -fd

echo ""
echo "✅ Cleanup complete!"
echo ""
echo "⚠️  Note: Environment files (.env*) are preserved."
echo "   To remove them, you must delete manually."
