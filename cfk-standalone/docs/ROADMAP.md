# CFK Sponsorship System - Roadmap

## Post-Launch Technical Debt & Improvements

### High Priority

#### Database Schema Improvements
**Status**: Planned for v1.8+
**Risk Level**: Low (data migration required)

**Issue**: Confusing database field names don't match their display purpose
- `children.interests` field displays as "Essential Needs" but should be named `essential_needs`
- `children.wishes` field correctly displays as "Christmas Wishes"
- `children.grade` field is unused (age groups calculated from age)

**Current Workaround**:
- CSV import correctly maps: `greatest_need` → DB `interests` → displays as "Essential Needs"
- CSV import correctly maps: `interests` + `wish_list` → DB `wishes` → displays as "Christmas Wishes"
- `grade` field left in database but set to empty string on import

**Proposed Fix** (v1.8):
```sql
-- Migration 1: Rename column for clarity
ALTER TABLE children CHANGE COLUMN interests essential_needs TEXT;

-- Migration 2: Remove unused grade column
ALTER TABLE children DROP COLUMN grade;
```

**Impact**:
- All references to `children.interests` must be updated to `children.essential_needs`
- Update display code (pages/child.php, pages/children.php, pages/sponsor_portal.php, etc.)
- Update CSV export to rename column back to `greatest_need` for consistency
- Test all pages that display child information
- Test CSV import/export cycle

**Files to Update** (search for `child\['interests'\]`):
- pages/child.php
- pages/children.php
- pages/sponsor_portal.php
- pages/family.php
- pages/reservation_review.php
- admin/manage_children.php
- src/CSV/Handler.php export functions

**Testing Checklist**:
- [ ] Verify "Essential Needs" displays correctly on all pages
- [ ] CSV import works with new schema
- [ ] CSV export produces correct column headers
- [ ] Admin interface shows correct data
- [ ] No references to old column names remain

---

### Medium Priority

#### Additional Future Improvements
- Consider adding `age_group` calculated field for better performance
- Archive system for old sponsorships (annual cleanup)
- Enhanced search with full-text indexing
- Bulk edit capabilities in admin interface

---

**Last Updated**: October 23, 2025
**Next Review**: Post-launch (December 2025)
