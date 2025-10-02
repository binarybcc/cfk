#!/bin/bash

# Claude Development Environment - API Key Setup Helper
# Interactive setup for required API keys and authentication

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')]${NC} $1"
}

info() {
    echo -e "${BLUE}[$(date +'%H:%M:%S')] INFO:${NC} $1"
}

warn() {
    echo -e "${YELLOW}[$(date +'%H:%M:%S')] WARNING:${NC} $1"
}

setup_github() {
    echo
    log "🐙 GitHub Authentication Setup"
    echo "================================"
    
    if command -v gh >/dev/null 2>&1; then
        if gh auth status >/dev/null 2>&1; then
            log "✅ GitHub CLI already authenticated"
        else
            info "Please authenticate with GitHub:"
            echo "  gh auth login"
            echo
            read -p "Press Enter after completing GitHub authentication..."
        fi
    else
        warn "GitHub CLI not found. Please install: brew install gh"
    fi
}

setup_anthropic() {
    echo
    log "🤖 Anthropic API Key Setup"
    echo "=========================="
    
    if [[ -n "$ANTHROPIC_API_KEY" ]]; then
        log "✅ ANTHROPIC_API_KEY environment variable found"
    else
        info "Anthropic API key not found in environment."
        echo "1. Get your API key from: https://console.anthropic.com/"
        echo "2. Add to your shell profile (~/.zshrc or ~/.bash_profile):"
        echo "   export ANTHROPIC_API_KEY='your-api-key-here'"
        echo "3. Restart your terminal or run: source ~/.zshrc"
        echo
    fi
}

setup_context7() {
    echo
    log "📚 Context7 Setup"
    echo "================="
    
    info "Context7 provides documentation via MCP server (SSE connection)"
    log "✅ Context7 MCP server should be auto-configured"
    echo "   - No API key required for basic usage"
    echo "   - Uses: https://mcp.context7.com/sse"
}

setup_e2b() {
    echo
    log "🏗️  E2B Sandbox Setup (Optional)"
    echo "==============================="
    
    if [[ -n "$E2B_API_KEY" ]]; then
        log "✅ E2B_API_KEY environment variable found"
    else
        info "E2B API key not found (optional for Flow-Nexus sandboxes)"
        echo "1. Sign up at: https://e2b.dev/"
        echo "2. Get your API key from the dashboard"
        echo "3. Add to your shell profile:"
        echo "   export E2B_API_KEY='your-e2b-api-key'"
        echo "4. Required for: sandbox creation, code execution environments"
        echo
    fi
}

setup_git_config() {
    echo
    log "📝 Git Configuration"
    echo "==================="
    
    name=$(git config --global user.name 2>/dev/null || echo "")
    email=$(git config --global user.email 2>/dev/null || echo "")
    
    if [[ -z "$name" ]]; then
        read -p "Enter your Git username: " git_name
        git config --global user.name "$git_name"
        log "✅ Git user.name set to: $git_name"
    else
        log "✅ Git user.name: $name"
    fi
    
    if [[ -z "$email" ]]; then
        read -p "Enter your Git email: " git_email
        git config --global user.email "$git_email"
        log "✅ Git user.email set to: $git_email"
    else
        log "✅ Git user.email: $email"
    fi
}

check_env_vars() {
    echo
    log "🔍 Environment Variable Check"
    echo "============================"
    
    vars=("ANTHROPIC_API_KEY" "E2B_API_KEY" "GITHUB_TOKEN")
    
    for var in "${vars[@]}"; do
        if [[ -n "${!var}" ]]; then
            log "✅ $var is set"
        else
            warn "⚠️  $var is not set"
        fi
    done
}

create_env_template() {
    echo
    log "📄 Creating .env template"
    echo "========================="
    
    local env_template="$HOME/.env.claude-dev-template"
    
    cat > "$env_template" << 'EOF'
# Claude Development Environment - API Keys Template
# Copy this to your project .env files as needed

# Required for Claude Code and API interactions
ANTHROPIC_API_KEY=your-anthropic-api-key-here

# Required for GitHub integrations
GITHUB_TOKEN=your-github-token-here

# Optional: For E2B sandbox environments (Flow-Nexus)
E2B_API_KEY=your-e2b-api-key-here

# Optional: For OpenAI integrations
OPENAI_API_KEY=your-openai-api-key-here

# Add to your shell profile (.zshrc, .bash_profile):
# export ANTHROPIC_API_KEY='your-key'
# export GITHUB_TOKEN='your-token'
# export E2B_API_KEY='your-key'
EOF

    log "✅ Template created at: $env_template"
    echo "   Copy and customize for your projects"
}

show_summary() {
    echo
    echo "🎉 API Key Setup Summary"
    echo "======================="
    echo
    echo "✅ Completed Setup Tasks:"
    echo "   - GitHub authentication"
    echo "   - Git configuration" 
    echo "   - Environment variable check"
    echo "   - .env template creation"
    echo
    echo "🔗 Service URLs:"
    echo "   - Anthropic Console: https://console.anthropic.com/"
    echo "   - GitHub Tokens: https://github.com/settings/tokens"
    echo "   - E2B Dashboard: https://e2b.dev/"
    echo "   - Context7: https://context7.com/"
    echo
    echo "📖 Next Steps:"
    echo "   1. Add API keys to your shell profile"
    echo "   2. Restart terminal: source ~/.zshrc"
    echo "   3. Test with: claude mcp list"
    echo "   4. Start a new project in ~/src/"
    echo
}

main() {
    echo "🔑 Claude Development Environment - API Key Setup"
    echo "================================================="
    echo
    
    setup_github
    setup_anthropic  
    setup_context7
    setup_e2b
    setup_git_config
    check_env_vars
    create_env_template
    show_summary
}

main "$@"