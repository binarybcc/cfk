#!/bin/bash

# Claude Development Environment Installer
# Portable setup for any Mac with standardized structure
# Version: 1.0.0

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
INSTALLER_VERSION="1.0.0"
USER_HOME="$HOME"
CLAUDE_GLOBAL_DIR="$USER_HOME/.claude-global"
DOCS_DIR="$USER_HOME/docs"
SRC_DIR="$USER_HOME/src"
TESTS_DIR="$USER_HOME/tests"
CONFIG_DIR="$USER_HOME/config"
SCRIPTS_DIR="$USER_HOME/scripts"
EXAMPLES_DIR="$USER_HOME/examples"

# Required tools
REQUIRED_TOOLS=("node" "npm" "git" "gh" "code")
OPTIONAL_TOOLS=("brew" "python3" "pip3")

# MCP Servers to install
MCP_SERVERS=(
    "github:npx -y @modelcontextprotocol/server-github"
    "sequential-thinking:npx -y @modelcontextprotocol/server-sequential-thinking" 
    "memory:npx -y @modelcontextprotocol/server-memory"
    "context7:https://mcp.context7.com/sse"
    "flow-nexus:npx flow-nexus@latest mcp start"
)

# VSCode Extensions
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

check_macos() {
    if [[ "$OSTYPE" != "darwin"* ]]; then
        error "This installer is designed for macOS only"
        exit 1
    fi
    log "✅ macOS detected"
}

check_dependencies() {
    log "🔍 Checking required dependencies..."
    
    for tool in "${REQUIRED_TOOLS[@]}"; do
        if command -v "$tool" >/dev/null 2>&1; then
            log "✅ $tool found"
        else
            error "❌ $tool not found. Please install $tool first."
            case "$tool" in
                "node"|"npm")
                    echo "  Install Node.js from https://nodejs.org/"
                    ;;
                "git")
                    echo "  Install via: xcode-select --install"
                    ;;
                "gh")
                    echo "  Install via: brew install gh"
                    ;;
                "code")
                    echo "  Install Visual Studio Code from https://code.visualstudio.com/"
                    ;;
            esac
            exit 1
        fi
    done
    
    for tool in "${OPTIONAL_TOOLS[@]}"; do
        if command -v "$tool" >/dev/null 2>&1; then
            log "✅ $tool found (optional)"
        else
            warn "⚠️  $tool not found (optional but recommended)"
        fi
    done
}

create_directory_structure() {
    log "📁 Creating standardized directory structure..."
    
    directories=("$DOCS_DIR" "$SRC_DIR" "$TESTS_DIR" "$CONFIG_DIR" "$SCRIPTS_DIR" "$EXAMPLES_DIR" "$CLAUDE_GLOBAL_DIR")
    
    for dir in "${directories[@]}"; do
        if [[ ! -d "$dir" ]]; then
            mkdir -p "$dir"
            log "✅ Created $dir"
        else
            info "📁 $dir already exists"
        fi
    done
    
    # Create subdirectories
    mkdir -p "$CLAUDE_GLOBAL_DIR/tools"
    mkdir -p "$CLAUDE_GLOBAL_DIR/config" 
    mkdir -p "$CLAUDE_GLOBAL_DIR/legacy"
    mkdir -p "$DOCS_DIR/context7-library"
    mkdir -p "$SRC_DIR/projects"
    mkdir -p "$TESTS_DIR/integration"
    mkdir -p "$TESTS_DIR/unit"
}

install_mcp_servers() {
    log "🔧 Installing MCP servers..."
    
    # Check if Claude is installed
    if ! command -v claude >/dev/null 2>&1; then
        warn "Claude CLI not found. Please install Claude Code first."
        return 1
    fi
    
    for server_config in "${MCP_SERVERS[@]}"; do
        IFS=':' read -r server_name server_command <<< "$server_config"
        
        info "Installing MCP server: $server_name"
        
        # Add MCP server using Claude CLI
        if claude mcp add "$server_name" $server_command 2>/dev/null; then
            log "✅ $server_name installed"
        else
            warn "⚠️  Failed to install $server_name (may already exist)"
        fi
    done
}

install_vscode_extensions() {
    log "🎨 Installing VSCode extensions..."
    
    if ! command -v code >/dev/null 2>&1; then
        warn "VSCode not found in PATH. Skipping extension installation."
        return 1
    fi
    
    for extension in "${VSCODE_EXTENSIONS[@]}"; do
        info "Installing extension: $extension"
        if code --install-extension "$extension" --force 2>/dev/null; then
            log "✅ $extension installed"
        else
            warn "⚠️  Failed to install $extension"
        fi
    done
}

generate_claude_md() {
    log "📝 Generating CLAUDE.md template..."
    
    local claude_md_path="$USER_HOME/CLAUDE.md"
    
    cat > "$claude_md_path" << 'EOF'
# Claude Code Configuration - SPARC Development Environment

## 🚨 CRITICAL: CONCURRENT EXECUTION & FILE MANAGEMENT

**ABSOLUTE RULES**:
1. ALL operations MUST be concurrent/parallel in a single message
2. **NEVER save working files, text/mds and tests to the root folder**
3. ALWAYS organize files in appropriate subdirectories
4. **USE CLAUDE CODE'S TASK TOOL** for spawning agents concurrently

## 🍎 APPLESCRIPT ACCESS ENABLED

**SYSTEM-WIDE APPLESCRIPT CONTROL GRANTED**:
- Full AppleScript access via `osascript` command
- Can control macOS applications, windows, and system functions
- Use Bash tool with `osascript -e 'script here'` syntax

### ⚡ GOLDEN RULE: "1 MESSAGE = ALL RELATED OPERATIONS"

**MANDATORY PATTERNS:**
- **TodoWrite**: ALWAYS batch ALL todos in ONE call (5-10+ todos minimum)
- **Task tool (Claude Code)**: ALWAYS spawn ALL agents in ONE message with full instructions
- **File operations**: ALWAYS batch ALL reads/writes/edits in ONE message
- **Bash commands**: ALWAYS batch ALL terminal operations in ONE message
- **Memory operations**: ALWAYS batch ALL memory store/retrieve in ONE message

### 📁 File Organization Rules

**NEVER save to root folder. Use these directories:**
- `/src` - Source code files
- `/tests` - Test files
- `/docs` - Documentation and markdown files
- `/config` - Configuration files
- `/scripts` - Utility scripts
- `/examples` - Example code

## Project Overview

This project uses SPARC (Specification, Pseudocode, Architecture, Refinement, Completion) methodology with Flow-Nexus orchestration for systematic Test-Driven Development.

## SPARC Commands

### Core Commands (via standalone SPARC tools)
- `npx create-sparc init <project>` - Initialize SPARC project
- `npx @agentics.org/sparc2` - SPARC 2.0 agentic development
- Use Claude Code's Task tool with SPARC-specialized agents (`specification`, `pseudocode`, `architecture`, `refinement`, `coder`)
- Use Flow-Nexus MCP for coordination when needed

### Build Commands
- `npm run build` - Build project
- `npm run test` - Run tests
- `npm run lint` - Linting
- `npm run typecheck` - Type checking

## SPARC Workflow Phases

1. **Specification** - Requirements analysis (use `specification` agent)
2. **Pseudocode** - Algorithm design (use `pseudocode` agent)
3. **Architecture** - System design (use `architecture` agent)
4. **Refinement** - TDD implementation (use `refinement` agent)
5. **Completion** - Integration (use `coder` agent)

## 🚀 Available Agents (54 Total)

### Core Development
`coder`, `reviewer`, `tester`, `planner`, `researcher`

### SPARC Methodology
`specification`, `pseudocode`, `architecture`, `refinement`

### GitHub & Repository
`github-modes`, `pr-manager`, `code-review-swarm`, `issue-tracker`, `release-manager`

### Testing & Validation
`tdd-london-swarm`, `production-validator`

## 🚀 Quick Setup

```bash
# MCP servers are already configured
# Use: claude mcp list to see active servers
# Current: github, sequential-thinking, memory, context7, flow-nexus
```

## 🚀 Agent Execution Flow with Claude Code

### The Correct Pattern:

1. **Optional**: Use MCP tools to set up coordination topology
2. **REQUIRED**: Use Claude Code's Task tool to spawn agents that do actual work
3. **REQUIRED**: Batch all operations in single messages

---

Remember: **Flow-Nexus coordinates, Claude Code creates!**

## 🔍 TOKEN-SAVING KNOWLEDGE LIBRARY

Your token-saving library is at: `~/docs/context7-library-template.md`

EOF

    log "✅ CLAUDE.md generated at $claude_md_path"
}

create_context7_library() {
    log "📚 Creating Context7 knowledge library..."
    
    local library_path="$DOCS_DIR/context7-library-template.md"
    
    cat > "$library_path" << 'EOF'
# Context7 Knowledge Library Template

## Purpose
Save commonly-used Context7 responses to avoid token consumption on repeated requests.

## Usage Pattern
1. Make Context7 request once
2. Save useful responses to this library  
3. Reference library instead of making duplicate requests

## Library Sections

### React Hooks - useState Basics
**Source:** Context7 `/reactjs/react.dev` - "useState basics" (2000 tokens)  
**Date Cached:** [Date when cached]

**Key Patterns:**
```javascript
// Basic useState declaration
const [state, setState] = useState(initialValue);

// Counter pattern
const [count, setCount] = useState(0);
const increment = () => setCount(count + 1);
```

### Package Analysis - Security Patterns
**Source:** Manual research + npm audit patterns  
**Date Cached:** [Date when cached]

**Security Check Commands:**
```bash
npm audit --audit-level moderate
npm audit fix
license-checker --onlyAllow 'MIT;Apache-2.0;BSD-3-Clause'
```

## Library Maintenance

### Update Schedule:
- **Monthly:** Review cached entries for freshness
- **Before major projects:** Verify patterns are still current
- **When frameworks update:** Re-cache breaking changes

EOF

    log "✅ Context7 library created at $library_path"
}

setup_git_configuration() {
    log "🔧 Setting up Git configuration..."
    
    # Check if git is configured
    if [[ -z "$(git config --global user.name)" ]]; then
        warn "Git user.name not configured. Please run:"
        echo "  git config --global user.name 'Your Name'"
    fi
    
    if [[ -z "$(git config --global user.email)" ]]; then
        warn "Git user.email not configured. Please run:"
        echo "  git config --global user.email 'your.email@example.com'"
    fi
    
    # Set up GitHub CLI if not authenticated
    if command -v gh >/dev/null 2>&1; then
        if ! gh auth status >/dev/null 2>&1; then
            warn "GitHub CLI not authenticated. Please run: gh auth login"
        else
            log "✅ GitHub CLI authenticated"
        fi
    fi
}

setup_permissions() {
    log "🔐 Setting up permissions..."
    
    # Make scripts executable
    find "$SCRIPTS_DIR" -name "*.sh" -exec chmod +x {} \; 2>/dev/null || true
    
    warn "⚠️  MANUAL ACTION REQUIRED:"
    echo "   1. Open System Preferences > Security & Privacy > Privacy"
    echo "   2. Grant Terminal full disk access for AppleScript functionality"
    echo "   3. Grant VSCode accessibility permissions if prompted"
}

install_uninstaller() {
    log "🗑️  Installing uninstaller..."
    
    local uninstaller_url="https://raw.githubusercontent.com/binarybcc/claude-dev-installer/main/claude-dev-installer-package/uninstall.sh"
    local uninstaller_path="$SCRIPTS_DIR/claude-dev-uninstaller.sh"
    
    # Copy from package directory if available, otherwise download
    if [[ -f "$(dirname "$0")/uninstall.sh" ]]; then
        cp "$(dirname "$0")/uninstall.sh" "$uninstaller_path"
        log "✅ Uninstaller copied from package"
    else
        # Download uninstaller (when hosted)
        if command -v curl >/dev/null 2>&1; then
            if curl -fsSL "$uninstaller_url" -o "$uninstaller_path" 2>/dev/null; then
                log "✅ Uninstaller downloaded"
            else
                warn "Could not download uninstaller (will need to get manually)"
                return 1
            fi
        else
            warn "curl not available - cannot download uninstaller"
            return 1
        fi
    fi
    
    chmod +x "$uninstaller_path" 2>/dev/null || true
}

show_completion_message() {
    echo
    echo "🎉 Claude Development Environment Installation Complete!"
    echo
    echo "📁 Directory Structure:"
    echo "   ~/docs/           - Documentation and Context7 library"
    echo "   ~/src/            - Source code projects"
    echo "   ~/tests/          - Test files" 
    echo "   ~/config/         - Configuration files"
    echo "   ~/scripts/        - Utility scripts"
    echo "   ~/examples/       - Example code"
    echo "   ~/.claude-global/ - Claude global configuration"
    echo
    echo "🔧 Installed Components:"
    echo "   ✅ MCP Servers (github, memory, context7, flow-nexus, sequential-thinking)"
    echo "   ✅ VSCode Extensions (Claude Code, GitLens, etc.)"
    echo "   ✅ CLAUDE.md configuration file"
    echo "   ✅ Context7 knowledge library template"
    echo
    echo "🚀 Next Steps:"
    echo "   1. Restart VSCode to load extensions"
    echo "   2. Run: claude mcp list (to verify MCP servers)"
    echo "   3. Set up API keys for services you'll use"
    echo "   4. Review ~/CLAUDE.md for usage patterns"
    echo
    echo "💡 Quick Start:"
    echo "   cd ~/src && npx create-sparc init my-project"
    echo
    echo "📖 Documentation:"
    echo "   - SPARC: https://github.com/ruvnet/sparc"
    echo "   - Flow-Nexus: https://github.com/ruvnet/flow-nexus"
    echo
    echo "🗑️  Uninstall:"
    echo "   ~/scripts/claude-dev-uninstaller.sh (safe removal with backups)"
    echo
}

# Main installation flow
main() {
    echo "🚀 Claude Development Environment Installer v$INSTALLER_VERSION"
    echo "=================================================="
    echo
    
    check_macos
    check_dependencies
    create_directory_structure
    install_mcp_servers
    install_vscode_extensions
    generate_claude_md
    create_context7_library
    setup_git_configuration
    setup_permissions
    install_uninstaller
    show_completion_message
}

# Run installer
main "$@"