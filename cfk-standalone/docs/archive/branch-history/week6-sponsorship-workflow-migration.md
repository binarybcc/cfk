# Week 6+ Sponsorship Workflow Migration Plan

**Created:** 2025-11-11
**Branch:** v1.9.2
**Status:** 🎯 Ready to Begin

---

## 📊 Migration Overview

### Scope: Complex Sponsorship Workflows

Migrate the entire user-facing sponsorship workflow from legacy routing to Slim Framework with professional architecture.

**Estimated Complexity:** High
**Estimated Time:** 3-4 days
**Priority:** Critical path for feature parity

---

## 🎯 Goals

1. ✅ **Professional Architecture** - DRY, component-based, reusable
2. ✅ **Modern Patterns** - PSR-7, dependency injection, repository pattern
3. ✅ **User Experience** - Maintain or improve UX
4. ✅ **Security** - CSRF protection, input validation, SQL injection prevention
5. ✅ **Feature Parity** - All legacy functionality preserved

---

## 📁 Pages to Migrate

### Priority 1: Core Sponsorship Flow

**1. sponsor.php → /sponsor/child/{id}**
- Single child sponsorship form
- Family sponsorship support
- Form validation and submission
- **Lines:** 352
- **Complexity:** Medium-High
- **Dependencies:** SponsorshipManager (✅ exists)

**2. reservation_review.php → /cart/review**
- Shopping cart review (if used)
- Multi-child selection review
- Reservation confirmation
- **Lines:** 15,082
- **Complexity:** High
- **Dependencies:** ReservationManager, EmailManager

**3. confirm_sponsorship.php → /sponsorship/confirm**
- Final sponsorship confirmation
- Payment/gift preference selection
- Success messaging
- **Lines:** 14,539
- **Complexity:** Medium
- **Dependencies:** SponsorshipManager

**4. reservation_success.php → /sponsorship/success**
- Success page after confirmation
- Display sponsored children
- Next steps information
- **Lines:** 10,226
- **Complexity:** Low
- **Dependencies:** None (just display)

### Priority 2: Sponsor Portal

**5. sponsor_portal.php → /portal/access**
- Magic link/token access
- Portal authentication
- **Lines:** 12,079
- **Complexity:** Medium
- **Dependencies:** MagicLinkManager

**6. my_sponsorships.php → /portal/sponsorships**
- View sponsored children
- Edit sponsorship details
- Add more children
- **Lines:** 29,904
- **Complexity:** High
- **Dependencies:** SponsorshipManager

---

## 🏗️ Architecture Plan

### Component Structure

```
src/
├── Controller/
│   ├── SponsorController.php (✅ exists - expand)
│   ├── CartController.php (NEW)
│   └── PortalController.php (NEW)
│
├── Repository/
│   ├── ChildRepository.php (✅ exists)
│   └── SponsorshipRepository.php (NEW)
│
├── Sponsorship/
│   └── Manager.php (✅ exists - has admin methods)
│
└── Reservation/
    └── Manager.php (✅ exists)

templates/
├── components/
│   ├── child-card.twig (✅ exists)
│   ├── sponsorship-form.twig (NEW)
│   ├── cart-item.twig (NEW)
│   └── success-message.twig (NEW)
│
├── layouts/
│   ├── base.twig (✅ exists)
│   └── sponsor.twig (NEW - extends base)
│
└── sponsor/
    ├── form.twig (NEW)
    ├── review.twig (NEW)
    ├── confirm.twig (NEW)
    ├── success.twig (NEW)
    └── portal.twig (NEW)
```

### Routes to Add

```php
// Sponsorship Routes (Week 6)
$app->get('/sponsor/child/{id:\d+}', [SponsorController::class, 'showSponsorForm']);
$app->post('/sponsor/child/{id:\d+}', [SponsorController::class, 'submitSponsorship']);
$app->get('/sponsor/family/{id:\d+}', [SponsorController::class, 'showFamilyForm']);
$app->post('/sponsor/family/{id:\d+}', [SponsorController::class, 'submitFamilySponsorship']);

// Cart Routes (Week 6)
$app->get('/cart', [CartController::class, 'show']);
$app->post('/cart/add', [CartController::class, 'add']);
$app->post('/cart/remove', [CartController::class, 'remove']);
$app->get('/cart/review', [CartController::class, 'review']);
$app->post('/cart/confirm', [CartController::class, 'confirm']);

// Success Route (Week 6)
$app->get('/sponsorship/success', [SponsorController::class, 'success']);

// Portal Routes (Week 7)
$app->get('/portal/access', [PortalController::class, 'access']);
$app->get('/portal/sponsorships', [PortalController::class, 'showSponsorships']);
$app->post('/portal/add-child', [PortalController::class, 'addChild']);
```

---

## 📝 Implementation Phases

### Phase 1: Single Child Sponsorship (Days 1-2)

**Step 1.1: Create Sponsorship Form Template**
- Extract form HTML from sponsor.php
- Create reusable sponsorship-form component
- Build sponsor/form.twig template
- Add validation display

**Step 1.2: Expand SponsorController**
- Add showSponsorForm() method
- Add submitSponsorship() method
- Handle single child logic
- Integrate with existing SponsorshipManager

**Step 1.3: Create Success Page**
- Build sponsor/success.twig template
- Add success() method to controller
- Display sponsored children details
- Next steps messaging

**Step 1.4: Add Routes**
- Register /sponsor/child/{id} (GET/POST)
- Register /sponsorship/success (GET)
- Test on staging

**Deliverable:** Single child sponsorship fully functional in Slim

---

### Phase 2: Family Sponsorship (Day 2)

**Step 2.1: Family Form Template**
- Adapt sponsorship form for multiple children
- Show all family members
- Calculate total commitment

**Step 2.2: Controller Methods**
- Add showFamilyForm() method
- Add submitFamilySponsorship() method
- Handle multi-child creation
- Transaction safety

**Step 2.3: Add Routes**
- Register /sponsor/family/{id} (GET/POST)
- Test family workflow

**Deliverable:** Family sponsorship fully functional

---

### Phase 3: Shopping Cart (Day 3)

**Step 3.1: Create CartController**
- Session-based cart management
- Add/remove operations
- Cart display

**Step 3.2: Cart Templates**
- cart-item.twig component
- cart/show.twig template
- cart/review.twig template

**Step 3.3: Cart Integration**
- Add to cart buttons on child cards
- Cart badge in header
- Review and confirm flow

**Step 3.4: Add Routes**
- /cart (GET)
- /cart/add (POST)
- /cart/remove (POST)
- /cart/review (GET)
- /cart/confirm (POST)

**Deliverable:** Complete shopping cart workflow

---

### Phase 4: Sponsor Portal (Day 4)

**Step 4.1: Create PortalController**
- Token/magic link authentication
- Portal access method
- Sponsorship display

**Step 4.2: Portal Templates**
- portal/access.twig (login)
- portal/sponsorships.twig (dashboard)
- Show all sponsored children
- Add child functionality

**Step 4.3: Add Routes**
- /portal/access (GET)
- /portal/sponsorships (GET)
- /portal/add-child (POST)

**Deliverable:** Full sponsor portal functionality

---

## 🎨 Component Design Patterns

### Reusable Components

**1. sponsorship-form.twig**
```twig
{#
  Sponsorship Form Component
  @param child - Child object
  @param children - Array of children (for family)
  @param formData - Pre-filled form data
  @param errors - Validation errors
  @param csrfToken - CSRF token
#}
```

**2. cart-item.twig**
```twig
{#
  Cart Item Component
  @param child - Child object
  @param removable - Show remove button
#}
```

**3. success-message.twig**
```twig
{#
  Success Message Component
  @param sponsorships - Array of completed sponsorships
  @param nextSteps - Array of next step instructions
#}
```

---

## 🔐 Security Considerations

### CSRF Protection
- ✅ All forms include CSRF tokens
- ✅ Verify tokens on POST requests

### Input Validation
- ✅ Sanitize all user inputs
- ✅ Validate email addresses
- ✅ Validate phone numbers (optional field)
- ✅ Limit text field lengths

### SQL Injection Prevention
- ✅ Use PDO prepared statements (via Repository/Manager)
- ✅ Never concatenate user input in SQL

### Session Security
- ✅ Regenerate session IDs after form submission
- ✅ Clear cart after successful sponsorship
- ✅ Timeout expired sessions

---

## 📊 Testing Checklist

### Functional Tests

**Single Child Sponsorship:**
- [ ] Load form for available child
- [ ] Submit with valid data → success
- [ ] Submit with invalid email → error
- [ ] Submit with missing required fields → errors
- [ ] CSRF token validation → security error
- [ ] Already sponsored child → unavailable message

**Family Sponsorship:**
- [ ] Load form for family
- [ ] Submit for all family members → success
- [ ] Partial availability handling
- [ ] Transaction rollback on error

**Shopping Cart:**
- [ ] Add child to cart
- [ ] Add multiple children
- [ ] Remove from cart
- [ ] Review cart items
- [ ] Confirm cart → creates sponsorships
- [ ] Clear cart after confirmation

**Sponsor Portal:**
- [ ] Access with valid token
- [ ] View sponsored children
- [ ] Add additional child
- [ ] Invalid/expired token → error

### Edge Cases

- [ ] Child becomes unavailable during form fill
- [ ] Concurrent sponsorship attempts (race condition)
- [ ] Session expiration mid-workflow
- [ ] Network errors during submission
- [ ] Database errors (rollback testing)

---

## 📈 Performance Considerations

### Database Queries
- Use eager loading for family members
- Cache child availability checks
- Index on child_id, status, family_id

### Session Management
- Keep cart data minimal
- Clear expired cart sessions
- Use Redis for high traffic (future)

### Template Rendering
- Component caching (Twig)
- Asset bundling (future)
- CDN for images (future)

---

## 🚀 Deployment Strategy

### Week 6 Deployment
1. ✅ Complete Phase 1 (Single child)
2. ✅ Test on staging
3. ✅ Complete Phase 2 (Family)
4. ✅ Test on staging
5. ✅ Complete Phase 3 (Cart)
6. ✅ Test comprehensive workflow
7. ✅ Deploy to staging for user testing

### Week 7 Deployment
1. ✅ Complete Phase 4 (Portal)
2. ✅ Full integration testing
3. ✅ Performance testing
4. ✅ Security audit
5. ✅ Deploy to production (when ready)

---

## 🎯 Success Metrics

### Code Quality
- ✅ 0 new PHPStan errors
- ✅ DRY: No code duplication
- ✅ Component reuse: 80%+
- ✅ Test coverage: 35/36 functional tests passing

### Performance
- ✅ Page load: <2s
- ✅ Form submission: <1s
- ✅ No N+1 queries

### User Experience
- ✅ Clear error messages
- ✅ Progress indicators
- ✅ Confirmation emails sent
- ✅ Mobile responsive

---

## 📚 References

**Existing Code:**
- `src/Sponsorship/Manager.php` - Sponsorship logic
- `src/Reservation/Manager.php` - Reservation logic
- `src/Controller/ChildController.php` - Pattern reference
- `templates/children/` - Template pattern reference

**Documentation:**
- `docs/technical/slim-template-architecture.md`
- `docs/components/button-system.md`
- `docs/features/` (various features)

---

## ✅ Definition of Done

**Week 6+ is complete when:**
1. ✅ All sponsorship workflows migrated to Slim
2. ✅ All templates follow component architecture
3. ✅ All routes registered and functional
4. ✅ All tests passing
5. ✅ PHPStan clean (no new errors)
6. ✅ Deployed to staging and tested
7. ✅ User acceptance testing complete
8. ✅ Documentation updated

---

**Let's build this RIGHT! 🚀**
