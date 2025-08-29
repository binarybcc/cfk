# Tool Inventory & Discovery System

**Purpose**: Track available tools, discover new capabilities, and maintain awareness of our development arsenal.

## Current Tool Inventory

### System Tools (Always Available)
- ✅ **File Operations**: Read, Write, Edit, MultiEdit, Glob, Grep, LS
- ✅ **Shell Access**: Bash commands with extensive permissions
- ✅ **macOS Integration**: osascript (AppleScript), open commands
- ✅ **Web Access**: WebFetch, WebSearch for research

### Development Environment
- ✅ **VS Code Integration**: Diagnostics, code execution
- ✅ **Node.js Ecosystem**: npm, npx, node commands
- ✅ **Python Support**: python3 execution, Jupyter notebook support
- ✅ **Browser Automation**: Puppeteer MCP for testing and screenshots

### Documentation & Research
- ✅ **Context7**: Library documentation and up-to-date framework info
- ✅ **Firecrawl**: Web scraping and content research
- ✅ **Library Documentation**: Up-to-date docs for any framework/library

## Tool Discovery Protocol

### Claude's Responsibilities
When I encounter a task, I should:
1. Check: "Do I have tools that could help with this I haven't mentioned?"
2. Explore: Use available commands to discover capabilities
3. Report: "I found these additional tools: [list]"
4. Ask: "Should I add these to our tool inventory?"

### Discovery Commands I Should Run
```bash
# Check available MCP servers
claude code mcp list --verbose

# Check for development tools
which git node python3 php ruby go rust 2>/dev/null

# Check for build tools  
which webpack vite parcel rollup gulp grunt 2>/dev/null

# Check for testing frameworks
which jest cypress playwright mocha vitest 2>/dev/null

# Check for deployment tools
which vercel netlify aws gcloud firebase 2>/dev/null
```

### User's Discovery Process
When you install new tools:
1. Add them to this inventory with ✅ or 🆕 status
2. Update .claude/settings.local.json if needed
3. Tell me about the tool's capabilities
4. Ask me to test/explore the tool

## Tool Status Tracking

### Legend
- ✅ **Confirmed Available**: Tool tested and working
- 🆕 **Recently Added**: New tool, needs exploration  
- ❓ **Unconfirmed**: Might be available, needs checking
- ❌ **Not Available**: Confirmed not installed/accessible
- 🔄 **Needs Update**: Tool available but outdated

## Communication Protocol

### When I Discover New Tools
Format: "🔍 TOOL DISCOVERY: Found [tool-name]
- Capability: [what it does]
- Status: [available/needs-setup/permission-needed]
- Potential Use: [how it could help our projects]
- Action Needed: [what you should do]"

### When You Add New Tools  
Format: "🆕 NEW TOOL: [tool-name]
- Purpose: [why you installed it]
- Capabilities: [what it can do]
- Integration: [how I should use it]
- Permissions: [what access I need]"

---

**Goal**: Maintain complete awareness of our development capabilities and continuously improve our toolset.
