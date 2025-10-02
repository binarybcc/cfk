#!/bin/bash
# Claude Code Project System Bootstrap
# Drop this file anywhere and run to create complete AI dev environment
# Usage: chmod +x claude-init.sh && ./claude-init.sh

set -e

echo "🤖 Claude Code Project System Setup"
echo "=================================="

# Get current directory
ROOT_DIR="$(pwd)"
echo "📁 Setting up in: $ROOT_DIR"

# Create universal files directory structure
echo "📋 Creating universal methodology files..."

# Create CLAUDE-UNIVERSAL-CONFIG.md
cat > CLAUDE-UNIVERSAL-CONFIG.md << 'EOF'
# Claude Universal Configuration
**AI collaboration standards for all projects**

## Architecture Standards

### CSS Requirements
```css
/* ALWAYS use these patterns */

/* 1. Design tokens (not hard-coded values) */
:root {
  --component-padding: 1rem;
  --component-gap: 0.75rem;
  --component-radius: 0.5rem;
}

/* 2. Layout-agnostic components */
.component {
  /* Self-contained, works anywhere */
  display: grid;
  gap: var(--component-gap);
  padding: var(--component-padding);
}

/* 3. NO absolute positioning unless absolutely necessary */
/* Use CSS Grid/Flexbox instead */

/* 4. Container queries over media queries when possible */
@container (max-width: 400px) {
  .component { grid-template-columns: 1fr; }
}
```

### JavaScript Requirements
```javascript
// 1. Always use namespaces (no global pollution)
window.ProjectName = window.ProjectName || {};

// 2. Class-based modules for reusability
class ComponentManager {
  constructor(options = {}) {
    this.options = { ...this.defaults, ...options };
  }
}

// 3. Event delegation over individual listeners
document.addEventListener('click', this.handleClick.bind(this));

// 4. Defensive programming
const element = document.getElementById('target');
if (!element) {
  console.warn('Element not found');
  return;
}
```

### HTML Requirements
```html
<!-- 1. Semantic, accessible structure -->
<section class="component" role="region" aria-label="Description">
  <h2 class="component__title">Title</h2>
  <div class="component__content">Content</div>
</section>

<!-- 2. BEM or component-based naming -->
<!-- 3. ARIA attributes for accessibility -->
<!-- 4. No inline styles (use classes) -->
```

## Systematic Interrogation Protocol

### Claude's Required Questions (Ask These for Every Feature)
BEFORE I provide any solution, I must ask:

1. **Context Check:**
   - "What assumptions am I making about [scale/users/performance/browsers]?"
   - "Are you seeing my full reasoning, or should I explain my thought process?"

2. **Alternative Analysis:**
   - "Should I show you 2-3 different approaches with tradeoffs?"
   - "What's your preference: simple/scalable/maintainable approach?"

3. **Future Impact:**
   - "What technical debt does this approach create?"
   - "How will this break if the project grows 10x?"

4. **Validation Strategy:**
   - "How do we test this works correctly?"
   - "What are the most likely failure modes?"

5. **Security & Performance:**
   - "What security risks does this introduce?"
   - "What are the performance bottlenecks at scale?"

### User's Required Questions (Ask Claude These)

#### Before Writing Code:
1. "Given this project scope, what's the right architecture complexity level?"
2. "What assumptions are you making that I should validate?"
3. "Show me 3 different approaches with tradeoffs"
4. "What will break first when this scales?"

#### During Development:
1. "What technical debt am I taking on with this approach?"
2. "How do I validate this works across different browsers/devices?"
3. "What are you NOT telling me about the downsides?"
4. "What patterns are emerging that we should extract or standardize?"

#### Before Major Changes:
1. "What other components will be affected by this change?"
2. "What's the rollback strategy if this approach fails?"
3. "How does this change our established architecture patterns?"

## Decision Framework

### Before Adding New Features:
1. **Can existing components handle this?** (Reuse first)
2. **Will this work on mobile?** (Mobile-first thinking)
3. **Is it accessible?** (Screen reader friendly)
4. **Can it be tested?** (Isolated components)

### CSS Cascade Sensitivity Rules:
- ❌ **Avoid:** `.parent .child .grandchild` (fragile cascade)
- ✅ **Use:** `.component__element` (predictable naming)
- ❌ **Avoid:** `position: absolute` for layout
- ✅ **Use:** CSS Grid, Flexbox for positioning

### Responsive Strategy:
```css
/* Mobile-first, progressive enhancement */
.component {
  /* Mobile styles (base) */
  grid-template-columns: 1fr;
}

@media (min-width: 768px) {
  .component {
    /* Desktop enhancement */
    grid-template-columns: 2fr 1fr;
  }
}
```

## Testing Requirements
- [ ] Works on mobile viewport (390px width minimum)
- [ ] Keyboard navigation functional
- [ ] Screen reader accessible
- [ ] No horizontal scrolling on mobile
- [ ] Performance: animations at 60fps

## Usage
Reference this file from project-specific CLAUDE.md files:
`See ../CLAUDE-UNIVERSAL-CONFIG.md for methodology standards`
EOF

# Create PROJECT-STARTUP-CHECKLIST.md  
cat > PROJECT-STARTUP-CHECKLIST.md << 'EOF'
# Project Startup Checklist
**Expert Team Standards for Scalable Development**

## Pre-Development Questions (Answer These First!)

### Project Scope & Growth
- [ ] **What's the MVP?** (Minimum viable version)
- [ ] **What's the 2-year vision?** (How big could this get?)
- [ ] **Expected user volume?** (10 users vs 10,000 users = different architecture)
- [ ] **Team size trajectory?** (Solo → small team → larger team)
- [ ] **Technical debt tolerance?** (Move fast vs. build right)

### Technology Context
- [ ] **Existing tech stack constraints?** (Must integrate with X system)
- [ ] **Performance requirements?** (Sub-second loads, mobile data usage)
- [ ] **Browser/device support?** (IE11 vs modern browsers only)
- [ ] **Accessibility requirements?** (WCAG compliance level needed)
- [ ] **Internationalization needed?** (Multi-language, RTL support)

### Team & Maintenance
- [ ] **Who maintains this long-term?** (You, team, client, vendor)
- [ ] **Skill level of maintainers?** (Beginner vs expert developers)
- [ ] **Documentation requirements?** (Self-documenting vs heavy docs)
- [ ] **Testing strategy?** (Manual testing vs automated test suite)

## Architecture Decisions (Make These Early)

### Code Organization Strategy
Choose your philosophy BEFORE writing code:

**OPTION A: Monolithic (Small-Medium projects)**
```
/src/
  styles.css
  main.js
  index.html
```

**OPTION B: Feature-Based (Growing projects)**
```
/src/
  /components/
    /navigation/
    /dashboard/
  /shared/
    /styles/
    /utils/
```

**OPTION C: Domain-Driven (Large projects)**
```
/src/
  /user-management/
  /billing/
  /reporting/
  /shared/
```

### CSS Architecture Decision
Choose ONE approach - DON'T MIX:

```css
/* Option A: Utility-First (Tailwind-like) */
<div class="grid grid-cols-3 gap-4 p-6 bg-white rounded-lg">

/* Option B: Component-First (BEM-like) */
<div class="dashboard-card dashboard-card--large">

/* Option C: CSS-in-JS (Styled Components) */
const Card = styled.div`
  display: grid;
  grid-template-columns: repeat(3, 1fr);
`;
```

### State Management Strategy
```javascript
// SIMPLE PROJECTS: Vanilla JS + localStorage
const state = {
  user: JSON.parse(localStorage.getItem('user')) || null
};

// MEDIUM PROJECTS: Custom state manager
class AppState {
  constructor() { this.subscribers = []; }
  setState(newState) { /* notify subscribers */ }
}

// COMPLEX PROJECTS: Redux/Zustand/Context
// (But NOT for simple projects - over-engineering!)
```

## Scalability Patterns (Implement From Day 1)

### Configuration Management
```javascript
// BAD: Hard-coded values scattered everywhere
const API_URL = 'https://api.mysite.com';
const MAX_ITEMS = 50;

// GOOD: Centralized config
const CONFIG = {
  API: {
    BASE_URL: process.env.API_URL || 'https://api.mysite.com',
    TIMEOUT: 5000,
    RETRY_ATTEMPTS: 3
  },
  UI: {
    MAX_ITEMS_PER_PAGE: 50,
    ANIMATION_DURATION: 300
  }
};
```

### Error Handling Strategy
```javascript
// BAD: Inconsistent error handling
try { await fetch('/api/data'); } catch (e) { console.log('oops'); }

// GOOD: Centralized error handling
class ErrorHandler {
  static handle(error, context) {
    // Log to service
    // Show user-friendly message
    // Track for debugging
  }
}
```

### Performance Patterns
```javascript
// Implement these patterns early:

// Debouncing for user input
const debounce = (fn, delay) => {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
};

// Lazy loading for large datasets
const lazyLoad = (callback) => {
  const observer = new IntersectionObserver(callback);
  return observer;
};

// Caching for expensive operations
const cache = new Map();
const memoize = (fn) => (...args) => {
  const key = JSON.stringify(args);
  if (cache.has(key)) return cache.get(key);
  const result = fn(...args);
  cache.set(key, result);
  return result;
};
```

## Technical Debt Prevention

### Refactoring Triggers
Set these rules and stick to them:
- [ ] **File > 300 lines** → Split into smaller modules  
- [ ] **Function > 50 lines** → Break into smaller functions
- [ ] **Duplicated code 3+ times** → Extract to shared utility
- [ ] **Magic numbers used** → Move to configuration
- [ ] **No tests for critical paths** → Add tests before continuing

### Regular Architecture Reviews
Schedule monthly "architecture debt" reviews:
- What patterns are emerging that weren't planned?
- What assumptions have changed?
- What components need refactoring?
- What new tools/patterns should we adopt?

## The Golden Rules

1. **Start simple, architect for growth** - Don't over-engineer, but plan for scale
2. **Consistency beats perfection** - Pick patterns and stick to them
3. **Document decisions, not just code** - Future you will thank present you
4. **Automate the boring stuff** - Linting, formatting, testing, deployment
5. **Plan for team growth** - Code should be readable by others
6. **Measure what matters** - Performance, user experience, maintainability

---

**Usage:** Review this checklist before starting any project larger than a simple landing page.
EOF

# Create TOOL-INVENTORY-TEMPLATE.md
cat > TOOL-INVENTORY-TEMPLATE.md << 'EOF'
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
EOF

# Create universal Claude settings
mkdir -p .claude
cat > .claude/settings.local.json << 'EOF'
{
  "permissions": {
    "allow": [
      "Bash(git:*)",
      "Bash(node:*)",
      "Bash(npm:*)",
      "Bash(npx:*)",
      "Bash(open:*)",
      "Bash(osascript:*)",
      "Bash(mkdir:*)",
      "Bash(mv:*)",
      "Bash(cp:*)",
      "Bash(rm:*)",
      "Bash(find:*)",
      "Bash(grep:*)",
      "Bash(ls:*)",
      "Bash(which:*)",
      "Bash(chmod:*)"
    ],
    "deny": []
  }
}
EOF

# Create project initialization script
cat > init-project.sh << 'EOF'
#!/bin/bash
# Project Initialization Script
# Usage: ./init-project.sh [project-type] [project-name]

PROJECT_TYPE=${1:-"general"}
PROJECT_NAME=${2:-"new-project"}

echo "🚀 Initializing new project: $PROJECT_NAME"
echo "📊 Project type: $PROJECT_TYPE"

# Validate project name
if [[ "$PROJECT_NAME" =~ [^a-zA-Z0-9_-] ]]; then
    echo "❌ Project name can only contain letters, numbers, hyphens, and underscores"
    exit 1
fi

# Create project directory
if [ -d "$PROJECT_NAME" ]; then
    echo "❌ Directory $PROJECT_NAME already exists"
    exit 1
fi

mkdir -p "$PROJECT_NAME"
cd "$PROJECT_NAME"

# Create project-specific CLAUDE.md
cat > CLAUDE.md << EOL
# CLAUDE.md - $PROJECT_NAME

## Universal Methodologies
Reference these files in parent directory:
- \`../CLAUDE-UNIVERSAL-CONFIG.md\` - Interrogation protocols & architecture standards  
- \`../PROJECT-STARTUP-CHECKLIST.md\` - Startup decisions & best practices
- \`../TOOL-INVENTORY-TEMPLATE.md\` - Tool discovery system

## Project Context
**Type:** $PROJECT_TYPE
**Purpose:** [Brief description of project goals]
**Timeline:** [Short/Medium/Long-term]

## Project-Specific Constraints
- Browser support: [Modern/Legacy/Mobile-first]
- Performance: [Critical/Normal/Not critical]  
- Team size: [Solo/Small/Large] → [Expected growth]
- Maintenance: [Long-term/Short-term/Handoff]
- Accessibility: [WCAG level required]

## Interrogation Protocol Active
- [ ] Claude must ask context check questions before solutions
- [ ] Claude must provide 2-3 alternatives with tradeoffs
- [ ] Claude must identify technical debt for each approach
- [ ] User must validate assumptions before implementation

## Architecture Decisions Made
- [ ] Code organization strategy: [Monolithic/Feature-based/Domain-driven]
- [ ] CSS approach: [Utility-first/Component-first/CSS-in-JS]
- [ ] State management: [Vanilla/Custom/Framework]
- [ ] Testing strategy: [Manual/Unit/Integration/E2E]

## Next Steps
1. Update project context above
2. Run startup checklist decisions (see ../PROJECT-STARTUP-CHECKLIST.md)
3. Initialize development environment
4. Set up CI/CD if needed
EOL

# Create project-specific Claude settings
mkdir -p .claude
cat > .claude/settings.local.json << EOL
{
  "permissions": {
    "allow": [
      "Bash(npm run:*)",
      "Bash(npm install:*)",
      "Bash(npm test:*)",
      "Bash(npm build:*)",
      "Bash(open *.html)",
      "Bash(git:*)"
    ],
    "deny": []
  }
}
EOL

# Create basic README
cat > README.md << EOL
# $PROJECT_NAME

Brief description of the project.

## Setup
1. Review \`CLAUDE.md\` for AI collaboration guidelines
2. Check \`../PROJECT-STARTUP-CHECKLIST.md\` for architecture decisions
3. Install dependencies (if applicable)

## Development
- This project uses AI-assisted development patterns
- Reference universal methodologies in parent directory
- Follow interrogation protocols for major decisions

## Architecture
- **Type:** $PROJECT_TYPE
- **AI Collaboration:** Active (see CLAUDE.md)
- **Methodologies:** Universal standards (see parent directory)

## Generated
🤖 Created with Claude Code project initialization system

Co-Authored-By: Claude <noreply@anthropic.com>
EOL

# Create appropriate .gitignore based on project type
case $PROJECT_TYPE in
    "web-app"|"website"|"frontend")
        cat > .gitignore << EOL
# Dependencies
node_modules/
bower_components/

# Build outputs
dist/
build/
.next/
.nuxt/

# Environment files
.env
.env.local
.env.production
.env.staging

# OS files
.DS_Store
Thumbs.db

# Logs
*.log
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# Runtime data
pids
*.pid
*.seed
*.pid.lock

# Coverage directory used by tools like istanbul
coverage/
.nyc_output

# IDE files
.vscode/
.idea/
*.swp
*.swo

# Temporary files
.tmp/
.cache/
EOL
        ;;
    "node"|"cli-tool"|"backend")
        cat > .gitignore << EOL
# Dependencies
node_modules/

# Build outputs
dist/
lib/
build/

# Environment files
.env
.env.local
.env.production

# OS files
.DS_Store

# Logs
*.log
npm-debug.log*
logs

# Runtime data
pids
*.pid
*.seed
*.pid.lock

# Coverage directory
coverage/
.nyc_output

# IDE files
.vscode/
.idea/

# Temporary files
.tmp/
EOL
        ;;
    "python"|"data-science")
        cat > .gitignore << EOL
# Byte-compiled / optimized / DLL files
__pycache__/
*.py[cod]
*$py.class

# Distribution / packaging
.Python
build/
develop-eggs/
dist/
downloads/
eggs/
.eggs/
lib/
lib64/
parts/
sdist/
var/
wheels/

# PyInstaller
*.manifest
*.spec

# Installer logs
pip-log.txt
pip-delete-this-directory.txt

# Unit test / coverage reports
htmlcov/
.tox/
.coverage
.coverage.*
.cache
nosetests.xml
coverage.xml
*.cover
.hypothesis/
.pytest_cache/

# Jupyter Notebook
.ipynb_checkpoints

# Environment
.env
.venv
env/
venv/
ENV/

# IDE
.vscode/
.idea/

# OS files
.DS_Store
EOL
        ;;
    *)
        cat > .gitignore << EOL
# OS files
.DS_Store
Thumbs.db

# Logs
*.log

# Environment files
.env
.env.local

# IDE files
.vscode/
.idea/
*.swp
*.swo

# Temporary files
.tmp/
.cache/
EOL
        ;;
esac

# Initialize git if not already in a repo
if [ ! -d .git ] && [ ! -d ../.git ]; then
    git init
    git add .
    git commit -m "Initial project setup for $PROJECT_NAME

🤖 Generated with Claude Code project initialization system

Type: $PROJECT_TYPE
Features:
- AI collaboration protocols active
- Universal methodology references
- Proper project structure
- Architecture decision framework

Co-Authored-By: Claude <noreply@anthropic.com>"
    echo "📚 Git repository initialized with initial commit"
fi

echo ""
echo "✅ Project '$PROJECT_NAME' initialized successfully!"
echo "📂 Location: $(pwd)"
echo ""
echo "📋 Created Files:"
echo "   - CLAUDE.md (AI collaboration context)"
echo "   - README.md (project documentation)"
echo "   - .claude/settings.local.json (Claude permissions)"
echo "   - .gitignore (appropriate for $PROJECT_TYPE)"
echo ""
echo "🎯 Next steps:"
echo "1. Edit CLAUDE.md with project-specific details"
echo "2. Review ../PROJECT-STARTUP-CHECKLIST.md for architecture decisions"
echo "3. Set up development environment"
echo "4. Start development with AI collaboration protocols active"
echo ""
echo "💡 Remember: Universal methodologies are in parent directory"
EOF

chmod +x init-project.sh

# Create usage documentation
cat > README.md << 'EOF'
# Claude Code Project System

Complete AI-assisted development environment with universal methodologies and project initialization.

## Quick Start

### 1. Bootstrap System (One Time)
Drop `claude-init.sh` into any directory and run:
```bash
chmod +x claude-init.sh
./claude-init.sh
```

### 2. Initialize New Projects
```bash
./init-project.sh web-app my-website
./init-project.sh cli-tool my-automation  
./init-project.sh client-project acme-corp
./init-project.sh python data-analysis
./init-project.sh backend api-server
```

### 3. Start Development
```bash
cd my-website
# CLAUDE.md contains project-specific context
# Universal methodologies available in parent directory
```

## System Structure
```
your-projects-root/
├── CLAUDE-UNIVERSAL-CONFIG.md       # Universal AI collaboration standards
├── PROJECT-STARTUP-CHECKLIST.md    # Architecture decision frameworks
├── TOOL-INVENTORY-TEMPLATE.md       # Tool discovery system
├── .claude/settings.local.json     # Universal Claude permissions
├── init-project.sh                 # Project creation script
├── README.md                       # This documentation
├── project-1/
│   ├── CLAUDE.md                    # Project-specific context
│   ├── .claude/settings.local.json # Project permissions
│   ├── README.md                    # Project documentation
│   └── .gitignore                   # Project-appropriate ignores
└── project-2/
    ├── CLAUDE.md
    ├── .claude/settings.local.json
    ├── README.md
    └── .gitignore
```

## Features
- ✅ Universal AI collaboration methodologies
- ✅ Systematic interrogation protocols  
- ✅ Project startup checklists and decision frameworks
- ✅ Automated project initialization with proper Git setup
- ✅ Project-type-specific configurations
- ✅ Proper Claude Code integration and permissions
- ✅ Tool discovery and capability management
- ✅ Completely portable - works in any directory

## Project Types Supported
- `web-app` / `website` / `frontend` - Frontend web applications
- `node` / `cli-tool` / `backend` - Node.js applications and tools
- `python` / `data-science` - Python projects and data analysis
- `general` - General purpose projects

## Generated Files Per Project
Each new project gets:
- `CLAUDE.md` - Project context and AI collaboration guidelines
- `.claude/settings.local.json` - Project-specific Claude permissions
- `README.md` - Basic project documentation with AI collaboration notes
- `.gitignore` - Appropriate ignore patterns for project type
- Git initialization with proper initial commit

## Universal Methodologies
- **Architecture Standards** - CSS, JavaScript, HTML best practices
- **Interrogation Protocols** - Systematic questions for better AI collaboration
- **Decision Frameworks** - Startup checklists and scalability patterns
- **Tool Discovery** - Capability management and tool awareness
- **Quality Gates** - Testing, accessibility, and performance requirements

## Distribution
This entire system is contained in a single `claude-init.sh` script that can be:
- Downloaded and run in any directory
- Shared with team members
- Version controlled and updated
- Extended with additional project types

🤖 This system embodies systematic AI-developer collaboration practices developed through real project experience.

## Contributing
To add new project types or improve methodologies:
1. Update the relevant universal files
2. Test with `./init-project.sh new-type test-project`
3. Verify all generated files and folder structure
4. Update documentation

---
**Created with Claude Code - AI-Assisted Development**
EOF

echo ""
echo "✅ Claude Code Project System Setup Complete!"
echo ""
echo "📋 Universal Files Created:"
echo "   - CLAUDE-UNIVERSAL-CONFIG.md (AI collaboration standards)"
echo "   - PROJECT-STARTUP-CHECKLIST.md (architecture frameworks)" 
echo "   - TOOL-INVENTORY-TEMPLATE.md (tool discovery system)"
echo "   - .claude/settings.local.json (universal permissions)"
echo "   - init-project.sh (project initialization)"
echo "   - README.md (complete documentation)"
echo ""
echo "🚀 Project Initialization Available:"
echo "   ./init-project.sh [type] [name]"
echo ""
echo "📚 Supported Project Types:"
echo "   - web-app, website, frontend"
echo "   - node, cli-tool, backend"  
echo "   - python, data-science"
echo "   - general (default)"
echo ""
echo "🎯 Create your first project:"
echo "   ./init-project.sh web-app my-first-project"
echo ""
echo "💡 Everything is now ready for AI-assisted development!"
EOF

chmod +x claude-init.sh

echo ""
echo "🎉 SUCCESS! Created portable Claude Code bootstrap system!"
echo ""
echo "📦 Distribution ready:"
echo "   - Single file: claude-init.sh"
echo "   - Drop anywhere and run to create complete AI dev environment"
echo "   - Zero dependencies except bash and Claude Code"
echo ""
echo "🧪 Test it yourself:"
echo "1. Create test directory: mkdir /tmp/test-claude-system"
echo "2. Copy bootstrap: cp claude-init.sh /tmp/test-claude-system/"
echo "3. Run setup: cd /tmp/test-claude-system && chmod +x claude-init.sh && ./claude-init.sh"
echo "4. Create test project: ./init-project.sh web-app test-project"
echo ""
echo "🌍 Ready for distribution via:"
echo "   - GitHub repository"
echo "   - Direct download"
echo "   - Package managers (future)"