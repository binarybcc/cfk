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
