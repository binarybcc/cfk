# Framework Investigation Report - NestJS and PHP Frameworks (2025)

**Date:** November 6, 2025
**Branch:** v1.8.1-cleanup
**Codebase Size:** ~26,500 lines of PHP
**Current Status:** Production-ready, stable architecture

---

## Executive Summary

**Key Finding:** This project already attempted and rejected a modern framework-like architecture in 2024. The current procedural approach with namespaced components is **well-suited for this project's scale and needs**.

**Recommendation:** **Do not adopt a framework at this time.** Continue with current architecture, consider targeted refactoring for specific pain points only.

---

## Current Architecture Analysis

### What You Have Now (v1.8.1)

**Architecture Type:** Hybrid procedural with namespaced components

```
Current Structure:
├── src/ (Namespaced PHP 8.2+ classes)
│   ├── CFK\Database\Connection       - Static PDO wrapper
│   ├── CFK\Sponsorship\Manager       - Business logic
│   ├── CFK\Email\Manager             - PHPMailer wrapper
│   ├── CFK\CSV\Handler               - Import/export
│   ├── CFK\Archive\Manager           - Year-end operations
│   └── [9 more namespaced components]
│
├── index.php (Router)                - Query string based (?page=children)
├── includes/ (Utilities)             - Helper functions, globals
├── pages/ (Templates)                - Public-facing pages
├── admin/ (Admin UI)                 - Admin interface
└── config/ (Configuration)           - Database, settings, .env

Routing: ?page=children, ?page=child&id=123
Dependencies: Static injection (Connection::fetchAll())
State: PHP sessions
```

### Strengths of Current Architecture

✅ **Simple and Direct**
- No framework learning curve
- Easy to understand for any PHP developer
- Quick to modify and deploy

✅ **Modern PHP 8.2+ Features**
- Strict typing (`declare(strict_types=1)`)
- Type hints and return types
- Constructor property promotion
- Namespaced classes (PSR-4 in src/)

✅ **Well-Organized Components**
- Clear separation of concerns
- Modular managers (Email, Sponsorship, CSV, etc.)
- Single responsibility principle

✅ **Production-Proven**
- Currently running in production
- Stable and reliable
- Security hardened (CSRF, PDO, rate limiting)

✅ **Lightweight**
- ~26,500 lines total
- Minimal dependencies (PHPMailer, PHPDotenv, Monolog)
- Fast deployment and startup

### Weaknesses of Current Architecture

❌ **Static Method Dependencies**
```php
// Hard to test, tightly coupled
Connection::fetchAll($sql, $params);
```

❌ **Global Functions**
```php
// includes/functions.php - procedural helpers
```

❌ **No Dependency Injection**
- Services instantiate dependencies directly
- Difficult to mock for unit testing
- Hard to swap implementations

❌ **Query String Routing**
```php
// index.php
?page=children vs /children
```

❌ **Mixed Architecture**
- Namespaced classes in src/
- Procedural code in includes/
- Pages mix HTML and logic

---

## NestJS Comparison (Node.js Framework)

**Important:** NestJS is a **Node.js/TypeScript** framework, not PHP. To use it would require **rewriting the entire application in TypeScript**.

### NestJS Architecture Patterns

NestJS provides:
- **Dependency Injection** (DI container)
- **Decorator-based routing** (`@Controller`, `@Get`, `@Post`)
- **Modular architecture** (modules, controllers, services)
- **TypeScript** (static typing)
- **Built-in validation** (class-validator)
- **Enterprise patterns** (repositories, DTOs, guards)

### PHP Equivalent: Symfony

**Symfony** is the closest architectural match to NestJS in PHP:

| Feature | NestJS (Node.js) | Symfony (PHP) |
|---------|------------------|---------------|
| Dependency Injection | ✅ Yes | ✅ Yes (Service Container) |
| Modularity | ✅ Modules | ✅ Bundles |
| Routing | ✅ Decorators | ✅ Annotations/Attributes |
| Type Safety | ✅ TypeScript | ✅ PHP 8.2+ |
| Enterprise Patterns | ✅ Yes | ✅ Yes |
| Learning Curve | Steep | Steep |
| Best For | Large APIs | Enterprise apps |

---

## PHP Framework Options (2025)

### Option 1: Laravel (Most Popular)

**Market Share:** 75% of PHP framework market

**Strengths:**
- Rapid development
- Elegant syntax ("magic" methods)
- Massive ecosystem (packages, tutorials)
- Eloquent ORM (Active Record)
- Built-in auth, queues, caching
- Beginner-friendly
- Great documentation

**Weaknesses:**
- "Magic" methods can be confusing
- Performance overhead (60ms average)
- Opinionated structure
- Large framework (many features you won't use)

**Best For:**
- Startups needing rapid development
- Small to medium projects
- Teams new to frameworks
- Standard CRUD applications

**Migration Effort:** 🔴 **HIGH** (3-4 weeks)
- Complete rewrite of routing
- Convert to Eloquent models
- Restructure entire application
- Learn Laravel conventions

---

### Option 2: Symfony (Enterprise Framework)

**NestJS Equivalent:** Closest match in PHP ecosystem

**Strengths:**
- Dependency injection container
- Highly modular (use only what you need)
- Enterprise-grade
- Excellent for complex applications
- Best performance at scale
- Flexible and customizable
- Component-based (can use pieces independently)

**Weaknesses:**
- Steep learning curve
- More setup required
- Less "magic" (more explicit code)
- Smaller community than Laravel

**Best For:**
- Enterprise applications
- Complex, long-term projects
- Performance-critical systems
- Teams with strong PHP skills

**Migration Effort:** 🔴 **HIGH** (4-6 weeks)
- Complete architectural rewrite
- Learn Symfony components
- Configure service container
- Restructure all routes and controllers

---

### Option 3: Slim Framework (Microframework)

**Type:** Minimal routing and middleware framework

**Strengths:**
- Lightweight (~1MB)
- Minimal learning curve
- Just routing + middleware
- Bring your own components
- Perfect for APIs
- Fast and simple

**Weaknesses:**
- No built-in ORM, auth, validation
- Must build/integrate everything yourself
- Not suitable for large applications

**Best For:**
- RESTful APIs
- Microservices
- Small projects
- Prototypes

**Migration Effort:** 🟡 **MEDIUM** (1-2 weeks)
- Add Slim routing
- Keep existing managers
- Minimal architectural changes
- Gradual migration possible

---

### Option 4: Stay Procedural (Current Approach)

**Type:** Custom architecture with namespaced components

**Strengths:**
- Already working in production
- Zero migration effort
- Team knows the codebase
- Proven stability
- Easy to modify

**Weaknesses:**
- Static dependencies (testing difficulty)
- No DI container
- Query string routing
- Mixed procedural/OOP

**Best For:**
- Current project size (~26K lines)
- Charity projects with limited resources
- Teams without framework experience

**Migration Effort:** 🟢 **NONE**

---

## Historical Context: The PSR-4 Attempt (2024)

**Critical Finding:** This project **already tried a modern architecture** and rejected it.

### What Happened

**October 2, 2024:** Built complete modern PSR-4 architecture
- Dependency injection container
- Repository pattern
- Service layer
- URL-based routing (/child/123)
- Type-safe models
- Interface contracts
- 19 files, 2,556 lines of code

**Result:** **Never used.** Removed in October 2025 after sitting unused for 18 months.

### Why It Failed

1. **Simpler approach won** - Procedural code was faster to develop
2. **Time pressure** - Features needed to ship quickly
3. **Premature architecture** - Built complexity before it was needed
4. **Parallel development** - Two complete systems confusing developers

### Lesson Learned

From `docs/technical/unused-psr4-architecture.md`:

> This is a textbook example of **premature architecture**:
> - Built infrastructure before proving it was needed
> - Created complexity that wasn't immediately valuable
> - Simpler approach won due to time pressure and practicality

**The Right Approach:**
```
1. Build simple (procedural) ✅
2. Ship features (v1.0-v1.5) ✅
3. Learn pain points ✅
4. Refactor when needed (v2.0 if needed)
```

---

## Decision Framework

### When to Adopt a Framework

✅ **Consider a framework if:**
- Team size > 5 developers
- Codebase > 100,000 lines
- Multiple concurrent features
- Complex business logic
- High testing requirements
- Long-term (5+ years) project
- Need to onboard many developers

❌ **Don't adopt a framework if:**
- Project works well as-is
- Small team (1-3 developers)
- Limited development time
- Budget constraints
- Simple business logic
- Existing architecture is stable

### Current Project Assessment

| Criterion | Status | Framework Needed? |
|-----------|--------|-------------------|
| Team Size | 1-2 developers | ❌ No |
| Codebase Size | 26,500 lines | ❌ No |
| Complexity | Moderate | ❌ No |
| Timeline | Stable, production | ❌ No |
| Budget | Charity (limited) | ❌ No |
| Testing | Functional tests | ⚠️ Could improve |
| Pain Points | Minimal | ❌ No |

**Verdict:** **Framework not needed for this project.**

---

## Targeted Improvements (Instead of Framework)

Rather than adopting a full framework, consider these **surgical improvements**:

### 1. Add Simple Dependency Injection

**Problem:** Static method calls hard to test

**Solution:** Lightweight DI container (use Symfony's alone)

```php
// composer require symfony/dependency-injection

// Before:
Connection::fetchAll($sql, $params);

// After:
class SponsorshipManager {
    public function __construct(
        private DatabaseInterface $db
    ) {}
}
```

**Effort:** 1-2 days
**Benefit:** Testability improves dramatically

---

### 2. Add URL-Based Routing

**Problem:** Query string routing (?page=children)

**Solution:** Add Slim or FastRoute

```php
// composer require slim/slim

// Before:
?page=child&id=123

// After:
/child/123

// Routes file:
$app->get('/child/{id}', ChildController::class);
```

**Effort:** 2-3 days
**Benefit:** Cleaner URLs, better SEO

---

### 3. Add Service Layer

**Problem:** Business logic mixed with data access

**Solution:** Separate concerns

```php
// Before (Manager does everything):
class SponsorshipManager {
    public static function reserveChild($id) {
        // Business logic + database calls mixed
    }
}

// After (separated):
class SponsorshipService {
    public function __construct(
        private SponsorshipRepository $repo
    ) {}

    public function reserveChild(int $id): Result {
        // Only business logic
    }
}

class SponsorshipRepository {
    public function find(int $id): ?Sponsorship {
        // Only database access
    }
}
```

**Effort:** 1 week
**Benefit:** Better separation, easier testing

---

### 4. Add Template Engine

**Problem:** PHP mixed with HTML in pages/

**Solution:** Add Twig or Plates

```php
// composer require twig/twig

// Before:
<?php foreach ($children as $child): ?>
    <div><?= htmlspecialchars($child['name']) ?></div>
<?php endforeach; ?>

// After:
{% for child in children %}
    <div>{{ child.name }}</div>
{% endfor %}
```

**Effort:** 3-4 days
**Benefit:** Cleaner templates, auto-escaping

---

## Recommendation

### Primary Recommendation: **Stay with Current Architecture**

**Reasons:**

1. **Historical Precedent** - Already rejected modern architecture once
2. **Project Scale** - 26,500 lines is manageable without framework
3. **Team Size** - 1-2 developers don't need framework overhead
4. **Stability** - Current code is production-proven
5. **Budget** - Charity project with limited resources
6. **Time** - Migration takes 3-6 weeks with zero new features

### If You Insist on a Framework: **Slim Framework**

**Why Slim:**
- Minimal migration effort (1-2 weeks)
- Keep existing managers
- Add clean routing
- Lightweight (~1MB)
- Gradual migration possible
- Lower risk than full framework

**Migration Path:**
```
Week 1: Install Slim, migrate routing
Week 2: Refactor index.php, test thoroughly
Week 3: Update documentation, deploy
```

### If You Want Enterprise Features: **Symfony Components**

**Why Symfony Components (not full Symfony):**
- Use only what you need
- Add DI container alone
- Add routing component alone
- No full framework migration
- Incremental improvements

**Example:**
```bash
composer require symfony/dependency-injection
composer require symfony/routing
# Use only these two components
```

---

## Alternative: Wait for Pain Points

**The Smart Approach:**

Instead of preemptively adopting a framework, **wait for specific pain points** to emerge:

| Pain Point | Solution | Timing |
|------------|----------|--------|
| Testing difficult | Add DI container | When writing unit tests |
| URLs ugly | Add routing library | When SEO becomes priority |
| Templates messy | Add Twig | When frontend work increases |
| Code duplication | Add service layer | When refactoring needed |

**This approach:**
- ✅ Solves real problems, not hypothetical ones
- ✅ Minimizes risk and disruption
- ✅ Keeps development velocity high
- ✅ Matches the "right approach" from historical lesson

---

## Migration Cost Analysis

### Framework Migration Costs

| Framework | Time | Risk | Features Gained | Worth It? |
|-----------|------|------|-----------------|-----------|
| **Laravel** | 3-4 weeks | High | Many (ORM, auth, queues) | ❌ Overkill |
| **Symfony** | 4-6 weeks | High | Enterprise patterns | ❌ Too complex |
| **Slim** | 1-2 weeks | Medium | Clean routing | ⚠️ Maybe |
| **Stay Current** | 0 weeks | None | None | ✅ Yes |

### Targeted Improvements Cost

| Improvement | Time | Risk | Benefit | Worth It? |
|-------------|------|------|---------|-----------|
| DI Container | 1-2 days | Low | Testability | ✅ Yes (if testing) |
| URL Routing | 2-3 days | Low | Clean URLs | ✅ Yes (if SEO) |
| Service Layer | 1 week | Low | Separation | ⚠️ Maybe |
| Templates | 3-4 days | Low | Cleaner views | ⚠️ Maybe |

---

## Conclusion

### Key Findings

1. **NestJS is Node.js** - Would require complete rewrite (not recommended)
2. **Symfony is closest to NestJS** - But too complex for this project
3. **Laravel is most popular** - But unnecessary for this scale
4. **Slim is lightest** - Best option if you must use a framework
5. **Current architecture works** - Already production-proven

### Final Recommendation

**Do not adopt a framework.**

Instead:
1. ✅ Continue with current architecture (it works!)
2. ✅ Make targeted improvements only when pain points emerge
3. ✅ Focus on shipping features, not rewriting infrastructure
4. ✅ Remember the 2024 lesson: simple beats complex for this project

### If You Disagree and Want a Framework

**Choose Slim Framework:**
- Minimal migration (1-2 weeks)
- Low risk
- Keep existing components
- Add clean routing only

**Avoid:**
- ❌ Complete rewrites (Laravel, Symfony)
- ❌ Node.js migration (NestJS)
- ❌ Premature complexity

---

## Next Steps

### Option A: Stay Current (Recommended)

1. Document current architecture patterns
2. Add code quality tools (already done in v1.8.1)
3. Focus on feature development
4. Monitor for pain points

### Option B: Targeted Improvements

1. Identify specific pain point
2. Add minimal solution (DI container, routing, etc.)
3. Measure improvement
4. Iterate

### Option C: Adopt Slim Framework

1. Create v1.9-slim-migration branch
2. Install Slim + PSR-7
3. Migrate routing incrementally
4. Test thoroughly
5. Deploy when stable

---

**Author:** Claude Code
**Date:** November 6, 2025
**Document:** framework-investigation-2025.md
**Status:** Analysis complete, awaiting decision
