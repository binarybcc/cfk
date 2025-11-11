#!/bin/bash
# Backup critical environment files to safe location
# Run this manually or add to your workflow

BACKUP_DIR="$HOME/.cfk-backups"
TIMESTAMP=$(date +"%Y%m%d-%H%M%S")

echo "💾 Backing up environment files..."

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Track if any files were backed up
backed_up=0

# Backup each env file
for file in .env .env.staging .env.production; do
    if [ -f "$file" ]; then
        cp "$file" "$BACKUP_DIR/${file}.backup-$TIMESTAMP"
        echo "✅ Backed up: $file → $BACKUP_DIR/${file}.backup-$TIMESTAMP"
        backed_up=1
    else
        echo "⚠️  Skipped: $file (not found)"
    fi
done

if [ $backed_up -eq 0 ]; then
    echo "❌ No environment files found to backup"
    exit 1
fi

echo ""
echo "✅ Backup complete!"
echo "   Location: $BACKUP_DIR"
echo ""
echo "To restore:"
echo "  cp $BACKUP_DIR/.env.production.backup-$TIMESTAMP .env.production"
echo "  chmod 444 .env.production"
