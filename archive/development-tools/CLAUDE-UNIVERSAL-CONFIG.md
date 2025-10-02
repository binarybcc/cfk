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
