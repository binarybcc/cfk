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
