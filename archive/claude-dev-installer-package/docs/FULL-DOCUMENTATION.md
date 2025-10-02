# Claude Development Environment Installer

🚀 **One-command setup for AI-assisted development on any Mac**

Transform any Mac into a powerful Claude-powered development environment in under 2 minutes.

## 🎯 What This Installs

### 📁 **Standardized Directory Structure**
```
~/docs/           - Documentation & Context7 knowledge library  
~/src/            - Source code projects
~/tests/          - Test files (unit & integration)
~/config/         - Configuration files  
~/scripts/        - Utility scripts
~/examples/       - Example code & templates
~/.claude-global/ - Claude global configuration & tools
```

### 🔧 **Core Components**
- **5 MCP Servers**: github, memory, context7, flow-nexus, sequential-thinking
- **14+ VSCode Extensions**: Claude Code, GitLens, Python, TypeScript, etc.
- **SPARC Methodology**: Structured development framework
- **Context7 Library**: Token-saving documentation cache
- **API Key Management**: Guided setup for all services

### 🤖 **54 Available AI Agents**
Ready-to-use specialized agents via Claude Code's Task tool:
- `specification`, `pseudocode`, `architecture`, `refinement`, `coder`
- `reviewer`, `tester`, `planner`, `researcher`
- `github-modes`, `pr-manager`, `code-review-swarm`
- And 40+ more specialized agents

## ⚡ Quick Install

### Option 1: Web Installer (Recommended)
```bash
curl -fsSL https://setup.claude-dev.com | bash
```

### Option 2: Local Install  
```bash
# Clone or download the installer
./scripts/claude-dev-installer.sh
```

### Option 3: Step-by-step
```bash
# 1. Run main installer
./scripts/claude-dev-installer.sh

# 2. Set up API keys (interactive)  
./scripts/setup-api-keys.sh

# 3. Start developing!
cd ~/src && npx create-sparc init my-project
```

## 🎬 What Happens During Install

1. **✅ System Check**: Verifies macOS + required tools (node, git, gh, vscode)
2. **📁 Directory Creation**: Sets up standardized folder structure  
3. **🔧 MCP Servers**: Installs and configures 5 MCP servers via Claude CLI
4. **🎨 VSCode Extensions**: Auto-installs 14 essential extensions
5. **📝 Configuration**: Generates optimized CLAUDE.md with all patterns
6. **📚 Knowledge Library**: Creates Context7 token-saving templates
7. **🔐 Permissions**: Guides through macOS security setup
8. **🎉 Ready to Code**: Complete AI development environment

## 📋 Prerequisites

### Required (Auto-checked)
- macOS (any recent version)
- Node.js & npm
- Git 
- GitHub CLI (`gh`)
- Visual Studio Code
- Claude Code CLI

### Optional (Recommended)
- Homebrew
- Python 3 & pip3

## 🔑 API Keys Setup

Run the interactive setup:
```bash
./scripts/setup-api-keys.sh
```

### Required Services
- **Anthropic**: Get from https://console.anthropic.com/
- **GitHub**: Personal access token from https://github.com/settings/tokens

### Optional Services  
- **E2B**: For sandbox environments (https://e2b.dev/)
- **OpenAI**: For additional AI models (https://platform.openai.com/)

## 🚀 Usage Examples

### SPARC Methodology
```bash
# Option 1: Standalone SPARC tools
npx create-sparc init my-app
npx @agentics.org/sparc2

# Option 2: Manual SPARC via Claude agents
# Use Claude Code Task tool with SPARC agents:
# Task("Specification analysis", "analyze requirements", "specification")
# Task("Architecture design", "design system", "architecture") 
# Task("Implementation", "code with TDD", "refinement")
```

### Multi-Agent Development
```javascript
// Single message with parallel agent execution
Task("Backend Developer", "Build REST API with Express", "backend-dev")  
Task("Frontend Developer", "Create React UI", "coder")
Task("Database Architect", "Design PostgreSQL schema", "code-analyzer")
Task("Test Engineer", "Write comprehensive tests", "tester")
Task("DevOps Engineer", "Setup CI/CD pipeline", "cicd-engineer")

TodoWrite { todos: [...8-10 todos...] }
```

### GitHub Integration  
```bash
# Automated PR management
claude mcp test github create_pull_request

# Code review swarms
# Use 'code-review-swarm' agent for intelligent reviews
```

## 🎯 Key Features

### 🔄 **Concurrent Execution**
- **84.8% SWE-Bench solve rate**
- **32.3% token reduction** 
- **2.8-4.4x speed improvement**
- All operations batched in single messages

### 🧠 **Smart Memory**
- Cross-session memory via Memory MCP
- Context7 knowledge library for token savings
- Agent coordination and state persistence

### 🛡️ **Production Ready**
- Security best practices built-in
- AppleScript system control
- Professional repository organization
- Automated cleanup and maintenance

## 📖 Documentation Structure

After installation, you'll have:

```
~/docs/
├── claude-dev-installer-readme.md    # This file
├── context7-library-template.md      # Token-saving knowledge base
└── ...

~/CLAUDE.md                          # Main configuration file
~/.claude-global/                    # Global Claude settings
```

## 🔧 Troubleshooting

### Common Issues

**MCP Server Connection Failed**
```bash
# Check MCP server status
claude mcp list

# Restart if needed  
claude mcp restart <server-name>
```

**VSCode Extensions Not Loading**
```bash
# Restart VSCode after installation
# Check extensions are enabled in VSCode settings
```

**API Key Issues**
```bash
# Run interactive setup
./scripts/setup-api-keys.sh

# Check environment variables
echo $ANTHROPIC_API_KEY
```

**Permission Errors**
- Grant Terminal full disk access in System Preferences > Security & Privacy
- Allow AppleScript access when prompted
- Restart Terminal after permission changes

## 🗑️ Uninstallation

### 🛡️ **Safe Removal with Comprehensive Backup System**

The Claude Dev Environment includes a **professional-grade uninstaller** that safely reverts your system while preserving all user data.

```bash
# Interactive uninstaller with multiple safety features
~/scripts/claude-dev-uninstaller.sh
```

### 🎯 **Uninstall Modes**

**1. 🧹 Clean Uninstall**
- Removes all installer-created components
- Automatically backs up ALL user data
- Preserves Git repositories and projects
- Only removes empty directories or installer templates
- **Safest option** - requires confirmation

**2. 🔧 Selective Uninstall**
Choose specific components to remove:
- **MCP Servers only** - Remove github, memory, context7, flow-nexus, sequential-thinking
- **VSCode Extensions only** - Remove Claude Code, GitLens, and 12 other extensions
- **Configuration files only** - Remove CLAUDE.md, templates, .env files
- **Empty directories only** - Remove only directories with no user content
- **Everything except user data** - Full cleanup but preserve projects

**3. 🔍 Analysis Only (Dry Run)**
- **See exactly** what would be removed before doing anything
- **Analyze directory contents** - shows file counts and types
- **Check dependencies** - shows active MCP servers and extensions
- **No changes made** - perfect for safety verification

### 💾 **Advanced Backup System**

**Automatic Backups:**
```bash
# Backup location (timestamped)
~/.claude-dev-backups/2025-01-07-143022/
├── files/           # Configuration files (CLAUDE.md, templates)
├── docs/            # User documentation and libraries  
├── src/             # Source code projects
├── tests/           # Test files
└── uninstall-manifest.txt  # Complete backup log
```

**Smart Content Detection:**
- ✅ **Preserves** all user-created files and projects
- ✅ **Preserves** Git repositories and version control
- ✅ **Preserves** custom configurations and settings
- 🧹 **Removes** only installer templates and empty directories
- 📊 **Analyzes** each directory for user vs installer content

### 🔍 **Safety Features**

**Multiple Confirmation Steps:**
1. **System Analysis** - Shows exactly what will be affected
2. **Backup Creation** - All data backed up before any changes
3. **User Confirmation** - Explicit "yes" required for destructive actions
4. **Progress Tracking** - Real-time updates during removal process

**Intelligent Detection:**
- **User Content Recognition** - Differentiates between your projects and installer files
- **Git Repository Protection** - Never removes version-controlled projects
- **Custom Config Preservation** - Backs up any modified configuration files
- **Selective Directory Cleanup** - Only removes truly empty or installer-only directories

### 🚀 **Usage Examples**

**Quick Analysis (Recommended First Step):**
```bash
~/scripts/claude-dev-uninstaller.sh
# Choose option 3: Analysis Only
# Review what would be removed before proceeding
```

**Clean Removal with Backup:**
```bash
~/scripts/claude-dev-uninstaller.sh
# Choose option 1: Clean Uninstall
# Confirm when prompted
# All user data automatically backed up
```

**Remove Just MCP Servers:**
```bash  
~/scripts/claude-dev-uninstaller.sh
# Choose option 2: Selective Uninstall
# Choose option 1: MCP Servers only
```

### 🔄 **Recovery and Restoration**

**Restore from Backups:**
```bash
# Navigate to backup directory
cd ~/.claude-dev-backups/[timestamp]

# Restore specific directories
cp -r docs/ ~/docs/
cp -r src/ ~/src/

# Restore configuration files
cp files/CLAUDE.md ~/CLAUDE.md
```

**Reinstall After Uninstall:**
```bash
# Clean reinstall after uninstall
curl -fsSL https://setup.claude-dev.com | bash

# Restore your backed up projects
cp -r ~/.claude-dev-backups/[timestamp]/src/* ~/src/
```

### ⚡ **What Gets Removed vs Preserved**

**✅ Always Preserved:**
- User-created source code projects
- Git repositories and version history  
- Custom documentation and notes
- Modified configuration files
- Any content in managed directories
- System tools (Node.js, Git, VSCode itself)
- User shell configuration and environment

**🗑️ Safely Removed:**
- MCP servers installed by the installer
- VSCode extensions added by the installer  
- Template files (context7-library-template.md, etc.)
- Installer-generated CLAUDE.md (if unmodified)
- Empty directories created by installer
- ~/.claude-global/ configuration directory

**📦 Backed Up Before Removal:**
- ALL files in managed directories (`~/docs/`, `~/src/`, etc.)
- Any configuration files that were modified
- Complete directory structure and permissions
- Timestamped manifest of all actions taken

## 🔄 Updates

```bash
# Re-run installer to update
./scripts/claude-dev-installer.sh

# Or via web installer
curl -fsSL https://setup.claude-dev.com | bash
```

## 🌐 Portability

This installer creates a **standardized Claude development environment** that works identically across any Mac:

- ✅ **Same directory structure** everywhere
- ✅ **Same tools and agents** available  
- ✅ **Same workflows and patterns**
- ✅ **Team collaboration ready**
- ✅ **Easy onboarding** for new developers

## 📊 Performance Benefits

- **2-minute setup** vs hours of manual configuration
- **Consistent environment** across all team members
- **Pre-optimized workflows** for AI-assisted development
- **Token-efficient** patterns and caching
- **Production-ready** from day one

## 🔗 Resources

- **SPARC Methodology**: https://github.com/ruvnet/sparc
- **Flow-Nexus Platform**: https://github.com/ruvnet/flow-nexus  
- **Claude Code**: https://claude.ai/code
- **Context7 Docs**: https://context7.com/
- **E2B Sandboxes**: https://e2b.dev/

## 🤝 Contributing

Found an issue or want to improve the installer?

1. Test the installer on a fresh Mac
2. Report issues with system details
3. Suggest improvements for better portability
4. Share your SPARC workflow patterns

---

**Ready to supercharge your development workflow?** 🚀

```bash
curl -fsSL https://setup.claude-dev.com | bash
```