#!/bin/bash
# Install git hooks for this project

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
GIT_HOOKS_DIR="$(git rev-parse --git-dir)/hooks"

echo "Installing git hooks..."

# Copy commit-msg hook
cp "$SCRIPT_DIR/commit-msg" "$GIT_HOOKS_DIR/commit-msg"
chmod +x "$GIT_HOOKS_DIR/commit-msg"

echo "✅ commit-msg hook installed"
echo ""
echo "Hooks installed! Commit messages must now follow Conventional Commits format:"
echo "  type(scope): description"
echo ""
echo "Types: feat, fix, docs, style, refactor, perf, test, chore, security, revert"
