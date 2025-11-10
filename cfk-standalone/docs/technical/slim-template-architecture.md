# Slim Framework - Template Architecture & Conventions

**Document Version:** 1.0
**Created:** 2025-11-10
**Last Updated:** 2025-11-10

---

## 🎯 Core Principle: Modular, Reusable Components

**ALL templates MUST follow the component-based architecture pattern.**

This is the professional standard for template systems and MUST be applied throughout the entire Slim migration.

---

## 📁 Directory Structure

```
templates/
├── components/              # Reusable template pieces (ALWAYS use includes)
│   ├── header.twig         # Public header
│   ├── footer.twig         # Shared footer (all pages)
│   ├── admin_header.twig   # Admin header (future)
│   ├── navigation.twig     # Navigation menus (future)
│   └── alerts.twig         # Flash messages (future)
│
├── layouts/                 # Page structure templates
│   ├── base.twig           # Public pages layout
│   └── admin.twig          # Admin pages layout (future)
│
├── sponsor/                 # Feature-specific templates
│   └── lookup.twig
│
├── children/                # Children feature templates
│   ├── index.twig
│   ├── show.twig
│   └── card.twig
│
└── admin/                   # Admin feature templates
    ├── dashboard.twig
    └── manage_children.twig
```

---

## ✅ Template Pattern Standards

### 1. Layout Templates (base.twig, admin.twig)

**Purpose:** Define overall page structure (HTML skeleton, head, body wrapper)

**Must Include:**
- HTML doctype and structure
- `<head>` with meta tags, CSS, scripts
- Include header component: `{% include 'components/header.twig' %}`
- Main content block: `{% block content %}{% endblock %}`
- Include footer component: `{% include 'components/footer.twig' %}`

**Example:**
```twig
{# templates/layouts/base.twig #}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{% block title %}{% endblock %}</title>
    <link rel="stylesheet" href="{{ baseUrl('assets/css/styles.css') }}">
</head>
<body>
    {# Include header component #}
    {% include 'components/header.twig' %}

    <main id="main-content" class="main-content">
        <div class="container">
            {% block content %}{% endblock %}
        </div>
    </main>

    {# Include footer component #}
    {% include 'components/footer.twig' %}
</body>
</html>
```

---

### 2. Component Templates (header.twig, footer.twig)

**Purpose:** Reusable UI pieces used across multiple pages

**Characteristics:**
- ✅ No `<!DOCTYPE>` or `<html>` tags (not a complete page)
- ✅ Self-contained HTML fragments
- ✅ Can be included in multiple layouts
- ✅ Single source of truth

**Example:**
```twig
{# templates/components/header.twig #}
<header class="main-header">
    <div class="container">
        <div class="logo">
            <img src="{{ baseUrl('assets/images/cfk-horizontal.png') }}" alt="CFK">
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="{{ baseUrl('?page=home') }}">Home</a></li>
                <li><a href="{{ baseUrl('?page=children') }}">Children</a></li>
            </ul>
        </nav>
    </div>
</header>
```

---

### 3. Feature Templates (lookup.twig, index.twig)

**Purpose:** Page-specific content that extends a layout

**Must:**
- ✅ Extend a layout: `{% extends 'layouts/base.twig' %}`
- ✅ Override blocks: `{% block content %}...{% endblock %}`
- ✅ Never include header/footer directly (layout handles it)

**Example:**
```twig
{# templates/sponsor/lookup.twig #}
{% extends 'layouts/base.twig' %}

{% block title %}Access Your Sponsorships{% endblock %}

{% block content %}
<div class="lookup-page">
    <h1>Access Your Sponsorships</h1>
    <form method="POST">
        {# Form content #}
    </form>
</div>
{% endblock %}
```

---

## 🚫 Anti-Patterns (DO NOT DO THIS)

### ❌ Inline Headers/Footers in Page Templates

**WRONG:**
```twig
{# templates/sponsor/lookup.twig #}
<!DOCTYPE html>
<html>
<head>...</head>
<body>
    <header>...</header>  {# ❌ Don't duplicate header #}
    <main>Content</main>
    <footer>...</footer>  {# ❌ Don't duplicate footer #}
</body>
</html>
```

**RIGHT:**
```twig
{# templates/sponsor/lookup.twig #}
{% extends 'layouts/base.twig' %}

{% block content %}
    {# Only page-specific content #}
{% endblock %}
```

---

### ❌ Duplicated Header/Footer Code

**WRONG:**
```twig
{# Multiple files with same header code #}
templates/sponsor/lookup.twig   - Has header code
templates/children/index.twig   - Has header code (duplicate)
templates/admin/dashboard.twig  - Has header code (duplicate)
```

**RIGHT:**
```twig
{# Single source of truth #}
templates/components/header.twig     - Header code ONCE
templates/layouts/base.twig          - Includes header
All feature templates                - Extend base.twig
```

---

## 📝 Conventions & Best Practices

### Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Layouts | `{purpose}.twig` | `base.twig`, `admin.twig` |
| Components | `{component_name}.twig` | `header.twig`, `footer.twig`, `navigation.twig` |
| Features | `{feature}/{action}.twig` | `sponsor/lookup.twig`, `children/index.twig` |

### Component Reusability

**Components should be:**
- ✅ Self-contained (no external dependencies)
- ✅ Reusable across multiple layouts
- ✅ Parameterizable via Twig variables when needed
- ✅ Single responsibility (header does header, footer does footer)

**Example with Parameters:**
```twig
{# templates/components/alert.twig #}
<div class="alert alert-{{ type }}">
    {{ message }}
</div>

{# Usage in feature template #}
{% include 'components/alert.twig' with {
    'type': 'success',
    'message': 'Email sent successfully!'
} %}
```

---

## 🔄 Migration Checklist

When migrating pages from legacy PHP to Slim/Twig:

- [ ] Does this page need a new layout? (Usually no - extend base.twig)
- [ ] Does this page introduce new reusable UI? (Extract to component)
- [ ] Does header/footer need to be different? (Create new component, don't duplicate)
- [ ] Are there repeated elements? (Extract to components)
- [ ] Does it extend a layout properly?
- [ ] Does it avoid duplicating header/footer code?

---

## 📚 Benefits of This Architecture

1. **DRY (Don't Repeat Yourself)**
   - Update header once → applies to all pages
   - Fix footer bug once → fixed everywhere

2. **Single Source of Truth**
   - Header code exists in ONE place only
   - Impossible for pages to have different headers by accident

3. **Maintainability**
   - Change logo → edit `components/header.twig` only
   - Add navigation item → edit once, appears everywhere

4. **Consistency**
   - All pages guaranteed to have same header/footer
   - Prevents drift between pages

5. **Team Collaboration**
   - Multiple developers can work on different components
   - No merge conflicts from duplicate header code

6. **Testing**
   - Test header component once
   - All pages using it are covered

---

## 🎓 Lessons Learned

**From Week 4 (Sponsor Lookup Migration):**

Initial mistake: Put entire header/footer code inline in `base.twig`

**Problem:**
- Hard to maintain (change requires editing large file)
- Can't easily swap headers for admin vs public
- Not following professional standards

**Solution:**
- Extracted header to `templates/components/header.twig`
- Extracted footer to `templates/components/footer.twig`
- Updated `base.twig` to use `{% include %}`

**Result:**
- Clean separation of concerns
- Easy to create admin-specific header later
- Professional, maintainable architecture

---

## 🚀 Future Enhancements

**When We Need:**

### Different Header for Admin
```twig
{# templates/components/admin_header.twig #}
<header class="admin-header">
    {# Admin-specific header #}
</header>

{# templates/layouts/admin.twig #}
{% include 'components/admin_header.twig' %}
```

### Conditional Components
```twig
{# templates/layouts/base.twig #}
{% if isAdmin %}
    {% include 'components/admin_header.twig' %}
{% else %}
    {% include 'components/header.twig' %}
{% endif %}
```

### Nested Components
```twig
{# templates/components/header.twig #}
<header>
    {% include 'components/navigation.twig' %}
    {% include 'components/user_menu.twig' %}
</header>
```

---

## ✅ Apply Throughout Entire Project

**MANDATORY:** All future Slim Framework pages MUST follow this architecture.

- Week 5: Children browse/profile pages → Use component pattern
- Week 6: Admin pages → Create admin layout, reuse footer component
- Week 7: Forms → Extract form components if reusable
- Future features → Always extract reusable pieces

**No exceptions. This is the professional standard.**

---

**Last Updated:** 2025-11-10
**Applies To:** All Slim Framework migrations (Week 4 onward)
**Status:** Active architectural standard
