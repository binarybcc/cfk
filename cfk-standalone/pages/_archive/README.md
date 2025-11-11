# Legacy Pages Archive

**Date Archived:** November 11, 2025
**Migration:** Week 8 - Legacy Cleanup

## What Happened

All public-facing pages have been migrated to **Slim Framework** with clean, RESTful routes. These legacy files are no longer used by the application but are preserved here for reference.

## Archived Files

### Content Pages (Week 7)
- `about.php` → `/about` (ContentController)
- `donate.php` → `/donate` (ContentController)
- `home.php` → `/` (ContentController)
- `how_to_apply.php` → `/how-to-apply` (ContentController)

### Children Pages (Week 2-3)
- `children.php` → `/children` (ChildController)
- `child.php` → `/children/{id}` (ChildController)

### Sponsorship Pages (Week 6)
- `sponsor.php` → `/sponsor/child/{id}` (SponsorController - Phase 1)
- `family.php` → `/sponsor/family/{id}` (SponsorController - Phase 2)
- `reservation_review.php` → `/cart/review` (CartController - Phase 3)
- `reservation_success.php` → `/cart/success` (CartController - Phase 3)
- `sponsor_portal.php` → `/portal` (PortalController - Phase 4)
- `my_sponsorships.php` → `/portal` (PortalController - Phase 4)

### Sponsor Lookup (Week 4)
- `sponsor_lookup.php` → `/sponsor/lookup` (SponsorController)

### Obsolete
- `confirm_sponsorship.php` → Replaced by integrated workflow (no direct equivalent)

## Redirects

All old query string routes (`?page=...`) are automatically redirected to new Slim routes via **301 permanent redirects** in `index.php`:

```php
?page=children → /children
?page=child&id=123 → /children/123
?page=sponsor&child_id=123 → /sponsor/child/123
?page=about → /about
// etc...
```

## Migration Status

**✅ Complete:** All public-facing pages migrated to Slim Framework
**⏳ Remaining:** Admin panel pages (to be migrated in Week 8 Part 2)
**📁 Active Legacy:** `temp_landing.php` (expires Oct 31, 2025)

## Can These Be Deleted?

**Not yet.** Keep these files for:
1. **Reference** - Understanding original logic during migration
2. **Rollback** - Emergency fallback if issues discovered
3. **Comparison** - Verifying feature parity

**After production deployment and testing:** These files can be safely deleted (except preserve one copy in git history).

## Technical Notes

- All Slim routes use **PSR-7** HTTP message handling
- Controllers use **dependency injection** via Symfony DI
- Templates use **Twig 3.x** with component inheritance
- Database access via **namespaced repositories** (src/Repository/)
- Business logic in **namespaced managers** (src/*/Manager.php)

---

**See:** `docs/technical/week6-sponsorship-workflow-migration.md` for detailed migration documentation
