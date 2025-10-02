#!/bin/bash

# Claude Development Environment Uninstaller
# Safely removes Claude Dev Environment while preserving user data
# Version: 1.0.0

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
UNINSTALLER_VERSION="1.0.0"
USER_HOME="$HOME"
CLAUDE_GLOBAL_DIR="$USER_HOME/.claude-global"
BACKUP_DIR="$USER_HOME/.claude-dev-backups/$(date +%Y%m%d-%H%M%S)"

# Directories managed by installer
MANAGED_DIRS=("$USER_HOME/docs" "$USER_HOME/src" "$USER_HOME/tests" "$USER_HOME/config" "$USER_HOME/scripts" "$USER_HOME/examples")

# MCP Servers installed by our installer
MCP_SERVERS=("github" "sequential-thinking" "memory" "context7" "flow-nexus")

# VSCode Extensions installed by our installer
VSCODE_EXTENSIONS=(
    "ms-vscode.vscode-claude-code"
    "eamodio.gitlens"
    "ms-python.python"
    "ms-vscode.vscode-json"
    "bradlc.vscode-tailwindcss"
    "esbenp.prettier-vscode"
    "ms-vscode.vscode-typescript-next"
    "formulahendry.auto-rename-tag"
    "mechatroner.rainbow-csv"
    "ms-vscode-remote.remote-containers"
    "ms-python.debugpy"
    "CoenraadS.bracket-pair-colorizer-deprecated"
    "christian-kohler.path-intellisense"
    "aaron-bond.better-comments"
)

# Files created by installer
MANAGED_FILES=(
    "$USER_HOME/CLAUDE.md"
    "$USER_HOME/.env.claude-dev-template"
    "$USER_HOME/docs/context7-library-template.md"
    "$USER_HOME/docs/claude-dev-installer-readme.md"
)

# Functions
log() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[$(date +'%H:%M:%S')] WARNING:${NC} $1"
}

error() {
    echo -e "${RED}[$(date +'%H:%M:%S')] ERROR:${NC} $1"
}

info() {
    echo -e "${BLUE}[$(date +'%H:%M:%S')] INFO:${NC} $1"
}

important() {
    echo -e "${MAGENTA}[$(date +'%H:%M:%S')] IMPORTANT:${NC} $1"
}

create_backup_system() {
    log "💾 Creating backup system..."
    
    mkdir -p "$BACKUP_DIR"
    
    # Create backup manifest
    cat > "$BACKUP_DIR/uninstall-manifest.txt" << EOF
# Claude Development Environment Uninstall Backup
# Created: $(date)
# Backup Location: $BACKUP_DIR

DIRECTORIES_TO_CHECK:
$(printf '%s\n' "${MANAGED_DIRS[@]}")

MCP_SERVERS:
$(printf '%s\n' "${MCP_SERVERS[@]}")

VSCODE_EXTENSIONS:
$(printf '%s\n' "${VSCODE_EXTENSIONS[@]}")

MANAGED_FILES:
$(printf '%s\n' "${MANAGED_FILES[@]}")
EOF

    log "✅ Backup system created at: $BACKUP_DIR"
}

backup_files() {
    log "📦 Backing up managed files..."
    
    for file in "${MANAGED_FILES[@]}"; do
        if [[ -f "$file" ]]; then
            local backup_path="$BACKUP_DIR/files$(dirname "$file")"
            mkdir -p "$backup_path"
            cp "$file" "$backup_path/"
            log "✅ Backed up: $(basename "$file")"
        fi
    done
}

backup_directories() {
    log "📁 Analyzing directories for backup..."
    
    for dir in "${MANAGED_DIRS[@]}"; do
        if [[ -d "$dir" ]]; then
            local dir_name=$(basename "$dir")
            local file_count=$(find "$dir" -type f 2>/dev/null | wc -l | tr -d ' ')
            
            if [[ $file_count -gt 0 ]]; then
                info "Found $file_count files in $dir"
                
                # Only backup if directory has non-installer content
                local has_user_content=false
                
                # Check for non-template files (indicating user content)
                if find "$dir" -type f -not -name "*template*" -not -name "*installer*" -not -name "*readme*" | head -1 | grep -q .; then
                    has_user_content=true
                fi
                
                if [[ "$has_user_content" == "true" ]]; then
                    warn "⚠️  $dir contains user content - creating backup"
                    cp -r "$dir" "$BACKUP_DIR/"
                    echo "$dir -> $BACKUP_DIR/$(basename "$dir")" >> "$BACKUP_DIR/directory-backups.log"
                else
                    info "📝 $dir contains only installer templates (safe to remove)"
                fi
            else
                info "📂 $dir is empty (safe to remove)"
            fi
        fi
    done
}

show_uninstall_options() {
    echo
    echo "🔧 Claude Development Environment Uninstaller"
    echo "=============================================="
    echo
    echo "Select uninstall mode:"
    echo "1) 🧹 Clean Uninstall (removes everything, backs up user data)"
    echo "2) 🔧 Selective Uninstall (choose what to remove)"  
    echo "3) 🔍 Analysis Only (show what would be removed)"
    echo "4) ❌ Cancel"
    echo
    read -p "Enter choice (1-4): " choice
    
    case $choice in
        1) UNINSTALL_MODE="clean" ;;
        2) UNINSTALL_MODE="selective" ;;
        3) UNINSTALL_MODE="analysis" ;;
        4) exit 0 ;;
        *) error "Invalid choice"; exit 1 ;;
    esac
}

analyze_system() {
    echo
    log "🔍 Analyzing Claude Development Environment..."
    echo
    
    # Check MCP servers
    info "MCP Servers:"
    if command -v claude >/dev/null 2>&1; then
        claude mcp list 2>/dev/null | while read -r line; do
            for server in "${MCP_SERVERS[@]}"; do
                if echo "$line" | grep -q "$server"; then
                    echo "  ✅ $server (managed by installer)"
                fi
            done
        done
    else
        warn "  Claude CLI not found"
    fi
    
    # Check VSCode extensions
    echo
    info "VSCode Extensions:"
    if command -v code >/dev/null 2>&1; then
        local installed_extensions=$(code --list-extensions 2>/dev/null)
        for ext in "${VSCODE_EXTENSIONS[@]}"; do
            if echo "$installed_extensions" | grep -q "$ext"; then
                echo "  ✅ $ext (managed by installer)"
            fi
        done
    else
        warn "  VSCode not found"
    fi
    
    # Check directories
    echo
    info "Directories:"
    for dir in "${MANAGED_DIRS[@]}"; do
        if [[ -d "$dir" ]]; then
            local file_count=$(find "$dir" -type f 2>/dev/null | wc -l | tr -d ' ')
            echo "  📁 $dir ($file_count files)"
        else
            echo "  ❌ $dir (not found)"
        fi
    done
    
    # Check files
    echo
    info "Configuration Files:"
    for file in "${MANAGED_FILES[@]}"; do
        if [[ -f "$file" ]]; then
            local size=$(ls -lh "$file" | awk '{print $5}')
            echo "  📄 $(basename "$file") ($size)"
        else
            echo "  ❌ $(basename "$file") (not found)"
        fi
    done
    
    echo
    info "Backup location would be: $BACKUP_DIR"
}

selective_uninstall_menu() {
    echo
    log "🔧 Selective Uninstall Options"
    echo "=============================="
    echo
    echo "Choose components to remove:"
    echo "1) MCP Servers only"
    echo "2) VSCode Extensions only"
    echo "3) Configuration files only (CLAUDE.md, templates)"
    echo "4) Empty directories only"
    echo "5) Everything except user data"
    echo "6) Cancel"
    echo
    read -p "Enter choice (1-6): " selective_choice
    
    case $selective_choice in
        1) remove_mcp_servers ;;
        2) remove_vscode_extensions ;;
        3) remove_config_files ;;
        4) remove_empty_directories ;;
        5) remove_all_except_user_data ;;
        6) exit 0 ;;
        *) error "Invalid choice"; exit 1 ;;
    esac
}

remove_mcp_servers() {
    log "🔧 Removing MCP servers..."
    
    if ! command -v claude >/dev/null 2>&1; then
        warn "Claude CLI not found - cannot remove MCP servers"
        return 1
    fi
    
    for server in "${MCP_SERVERS[@]}"; do
        info "Removing MCP server: $server"
        if claude mcp remove "$server" 2>/dev/null; then
            log "✅ Removed: $server"
        else
            warn "⚠️  Could not remove: $server (may not exist)"
        fi
    done
}

remove_vscode_extensions() {
    log "🎨 Removing VSCode extensions..."
    
    if ! command -v code >/dev/null 2>&1; then
        warn "VSCode not found - cannot remove extensions"
        return 1
    fi
    
    for extension in "${VSCODE_EXTENSIONS[@]}"; do
        info "Removing extension: $extension"
        if code --uninstall-extension "$extension" 2>/dev/null; then
            log "✅ Removed: $extension"
        else
            warn "⚠️  Could not remove: $extension (may not be installed)"
        fi
    done
}

remove_config_files() {
    log "📄 Removing configuration files..."
    
    for file in "${MANAGED_FILES[@]}"; do
        if [[ -f "$file" ]]; then
            info "Removing: $(basename "$file")"
            rm "$file"
            log "✅ Removed: $(basename "$file")"
        fi
    done
}

remove_empty_directories() {
    log "📁 Removing empty directories..."
    
    for dir in "${MANAGED_DIRS[@]}"; do
        if [[ -d "$dir" ]]; then
            local file_count=$(find "$dir" -type f 2>/dev/null | wc -l | tr -d ' ')
            if [[ $file_count -eq 0 ]]; then
                info "Removing empty directory: $dir"
                rmdir "$dir" 2>/dev/null || warn "Could not remove $dir (may have hidden files)"
                log "✅ Removed: $dir"
            else
                info "Skipping $dir (contains $file_count files)"
            fi
        fi
    done
}

remove_all_except_user_data() {
    log "🧹 Performing full uninstall (preserving user data)..."
    
    # Remove components in safe order
    remove_mcp_servers
    remove_vscode_extensions
    remove_config_files
    
    # Remove directories only if they contain no user data
    for dir in "${MANAGED_DIRS[@]}"; do
        if [[ -d "$dir" ]]; then
            # Check if directory has user content
            local has_user_content=false
            if find "$dir" -type f -not -name "*template*" -not -name "*installer*" -not -name "*readme*" | head -1 | grep -q .; then
                has_user_content=true
            fi
            
            if [[ "$has_user_content" == "false" ]]; then
                info "Removing installer-only directory: $dir"
                rm -rf "$dir"
                log "✅ Removed: $dir"
            else
                warn "⚠️  Preserving $dir (contains user data)"
            fi
        fi
    done
    
    # Remove global directory
    if [[ -d "$CLAUDE_GLOBAL_DIR" ]]; then
        info "Removing Claude global directory: $CLAUDE_GLOBAL_DIR"
        rm -rf "$CLAUDE_GLOBAL_DIR"
        log "✅ Removed: $CLAUDE_GLOBAL_DIR"
    fi
}

confirm_uninstall() {
    echo
    important "⚠️  CONFIRMATION REQUIRED"
    echo "========================="
    echo
    echo "This will:"
    if [[ "$UNINSTALL_MODE" == "clean" ]]; then
        echo "  - Remove all MCP servers installed by Claude Dev Environment"
        echo "  - Remove VSCode extensions installed by the installer"
        echo "  - Remove configuration files (CLAUDE.md, templates)"
        echo "  - Remove empty directories created by installer"
        echo "  - Preserve any user-created content in backups"
    fi
    echo "  - Create backups at: $BACKUP_DIR"
    echo
    warn "This action cannot be automatically undone!"
    echo
    read -p "Are you sure you want to continue? (yes/NO): " confirm
    
    if [[ "$confirm" != "yes" ]]; then
        info "Uninstall cancelled by user"
        exit 0
    fi
}

show_completion_message() {
    echo
    echo "🎉 Claude Development Environment Uninstall Complete!"
    echo "====================================================="
    echo
    log "✅ Uninstall completed successfully"
    
    if [[ -d "$BACKUP_DIR" ]]; then
        echo "📦 Backups created at:"
        echo "   $BACKUP_DIR"
        echo
        echo "💡 To restore files:"
        echo "   cp -r $BACKUP_DIR/docs ~/docs"
        echo "   cp -r $BACKUP_DIR/src ~/src"
        echo "   # etc..."
        echo
    fi
    
    echo "🧹 What was removed:"
    echo "   - MCP servers (claude, memory, context7, flow-nexus, github)"
    echo "   - VSCode extensions (Claude Code, GitLens, etc.)"
    echo "   - Configuration files (CLAUDE.md, templates)"
    echo "   - Empty installer-created directories"
    echo
    echo "✅ What was preserved:"
    echo "   - All user-created files and projects"
    echo "   - Git repositories and configuration"
    echo "   - System tools (Node.js, VSCode, etc.)"
    echo "   - User shell configuration"
    echo
    echo "🔄 To reinstall:"
    echo "   curl -fsSL https://setup.claude-dev.com | bash"
    echo
}

# Main uninstall flow
main() {
    echo "🗑️  Claude Development Environment Uninstaller v$UNINSTALLER_VERSION"
    echo "=================================================================="
    echo
    
    # Show options
    show_uninstall_options
    
    # Create backup system
    create_backup_system
    
    # Analyze current system
    analyze_system
    
    if [[ "$UNINSTALL_MODE" == "analysis" ]]; then
        echo
        log "✅ Analysis complete - no changes made"
        exit 0
    fi
    
    # Backup user data
    backup_files
    backup_directories
    
    # Confirm uninstall
    if [[ "$UNINSTALL_MODE" == "clean" ]]; then
        confirm_uninstall
        remove_all_except_user_data
    elif [[ "$UNINSTALL_MODE" == "selective" ]]; then
        selective_uninstall_menu
    fi
    
    show_completion_message
}

# Run uninstaller
main "$@"