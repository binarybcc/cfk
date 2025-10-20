# v1.7 Composer Migration Plan

**Generated**: October 20, 2025
**Status**: Ready to Execute
**Branch**: v1.7

---

## 🎉 Phase 1 Complete! (Tools Setup)

✅ **Rector Analysis**: 37 files analyzed and modernized
✅ **PHPStan Analysis**: 150+ type issues identified
✅ **Safe Fixes Applied**: All Rector improvements applied
✅ **Directory Structure**: `src/` created with subdirectories

---

## 📊 What Rector Fixed Automatically

### Applied Improvements (37 files):

1. **Empty Array Checks** (`empty($arr)` → `$arr === []`)
   - More explicit and type-safe
   - 50+ occurrences fixed

2. **Arrow Functions** (closures → `fn()` syntax)
   - Modern PHP 8.2 syntax
   - Cleaner, more readable

3. **Type Casting** (added `(string)` casts)
   - Prevents type errors
   - 100+ casts added

4. **Braces** (single-line if → proper braces)
   - PSR-12 compliant
   - Better readability

5. **Cookie Functions** (modern array syntax)
   - PHP 8.2 style
   - More secure options

6. **Ternary Switches** (negative → positive logic)
   - Easier to read
   - Reduced cognitive load

### Files Modified by Rector:
```
pages/children.php
pages/family.php
pages/my_sponsorships.php
pages/sponsor.php
pages/sponsor_lookup.php
pages/sponsor_portal.php
admin/change_password.php
admin/forgot_password.php
admin/import_csv.php
admin/includes/admin_header.php
admin/index.php
admin/login.php
admin/logout.php
admin/manage_admins.php
admin/manage_children.php
admin/manage_sponsorships.php
admin/reports.php
admin/reset_password.php
admin/year_end_reset.php
... and 18 more files
```

---

## 🔍 PHPStan Findings Summary

### Critical Issues (Must Fix):

1. **Missing Array Type Hints** (100+ occurrences)
   ```php
   // ❌ Bad
   public function getChildren(): array

   // ✅ Good
   public function getChildren(): array<int, array<string, mixed>>
   ```

2. **Undefined Methods** (8 occurrences)
   - `Database::query()` called but not defined
   - Need to add or remove these calls

3. **Unknown Classes** (2 occurrences)
   - `PHPMailer\PHPMailer\PHPMailer` not found
   - Need to add to composer.json or use interfaces

4. **Undefined Variables** (5 occurrences)
   - `$familyNumber` used before definition
   - Easy fixes

### Files Needing Most Work:

**csv_handler.php**: 29 issues
- Mostly missing array type hints
- 1 undefined variable

**database_wrapper.php**: 10 issues
- All array type hints missing
- Core infrastructure file

**email_manager.php**: 15 issues
- PHPMailer class not found
- Array type hints missing

**functions.php**: 40+ issues
- Deprecated PHP 8.4 functions
- Many missing type hints

---

## 🗺️ Migration Strategy

### Phase 2: Class Migration (Priority Order)

#### Step 1: Database Layer (HIGHEST PRIORITY)
**Why first**: Everything depends on this

**Tasks**:
1. Migrate `includes/database_wrapper.php` → `src/Database/Connection.php`
2. Add array type hints (10 methods)
3. Fix or remove `Database::query()` calls
4. Update all `Database::` references to `Connection::`

**Time**: 1-2 hours
**Risk**: MEDIUM (core infrastructure)
**Automation**: Rector can help with namespaces

---

#### Step 2: Manager Classes (Core Business Logic)

**Migration Order** (by dependency):

1. **Sponsorship Manager** (task_order: 79)
   ```
   includes/sponsorship_manager.php → src/Sponsorship/Manager.php
   ```
   - Remove `CFK_` prefix
   - Add namespace `CFK\Sponsorship`
   - Fix array type hints (15+)
   - Keep all reservation logic intact

2. **Archive Manager** (already started!)
   ```
   includes/archive_manager.php → src/Archive/Manager.php
   ```
   - Already uses env variables ✅
   - Just needs namespace + type hints
   - 7 array type hints needed

3. **CSV Handler**
   ```
   includes/csv_handler.php → src/CSV/Handler.php
   ```
   - Most issues (29)
   - Fix `$familyNumber` undefined variable
   - Add 25+ array type hints

4. **Avatar Manager**
   ```
   includes/avatar_manager.php → src/Avatar/Manager.php
   ```
   - 5 array type hints
   - 2 always-true comparisons to fix

5. **Report Manager**
   ```
   includes/report_manager.php → src/Report/Manager.php
   ```
   - Fewer issues
   - Straightforward migration

6. **Reservation Functions**
   ```
   includes/reservation_functions.php → src/Reservation/Functions.php
   ```
   - May need restructuring (functions → class)

7. **Email Manager**
   ```
   includes/email_manager.php → src/Email/Manager.php
   ```
   - Add PHPMailer to composer
   - Fix PHPMailer type hints

8. **Backup Manager**
   ```
   includes/backup_manager.php → src/Backup/Manager.php
   ```
   - Fix `Database::query()` calls
   - Add array type hints

**Time**: 4-6 hours total
**Risk**: MEDIUM (well-tested functionality)
**Automation**: Rector + manual type hints

---

#### Step 3: Integration (Update References)

**Tasks** (by priority):

1. **Update index.php** (task_order: 61)
   - Add `require_once __DIR__ . '/vendor/autoload.php'`
   - Remove individual class requires
   - Add `use` statements
   - **Critical**: Main entry point

2. **Update admin files** (task_order: 55)
   - admin/index.php
   - admin/year_end_reset.php
   - admin/manage_children.php
   - admin/manage_sponsorships.php
   - admin/import_csv.php
   - admin/reports.php
   - Replace `CFK_ClassName` with namespaced imports

3. **Update page files** (task_order: 49)
   - pages/children.php
   - pages/child.php
   - pages/my_sponsorships.php
   - pages/sponsor_portal.php
   - pages/confirm_sponsorship.php
   - pages/family.php
   - Add `use` statements

4. **Update cron jobs** (task_order: 43)
   - cron/cleanup_reservations.php
   - cron/cleanup_magic_links.php
   - cron/cleanup_portal_tokens.php
   - cron/cleanup_remember_tokens.php
   - OR: Migrate to Symfony Console commands (recommended)

**Time**: 2-3 hours
**Risk**: LOW (mostly find & replace)
**Automation**: Can use regex find & replace

---

### Phase 3: Testing & Validation

1. **Local Docker Testing** (task_order: 37)
   ```bash
   docker-compose up -d
   # Access http://localhost:8082
   # Run full test suite
   ./tests/security-functional-tests.sh
   ```
   **Goal**: 35/36 tests passing

2. **PHPStan Validation**
   ```bash
   composer phpstan
   # Goal: 0 errors at level 8
   ```

3. **Manual Testing Checklist**:
   - [ ] Browse children page
   - [ ] View child profile
   - [ ] Add to cart
   - [ ] Complete sponsorship
   - [ ] Admin login
   - [ ] Admin dashboard
   - [ ] CSV import/export
   - [ ] Year-end reset
   - [ ] Email notifications

**Time**: 2-3 hours
**Risk**: CRITICAL (catch regressions)

---

### Phase 4: Documentation & Deployment

1. **Update Documentation** (task_order: 25)
   - README.md - Composer installation steps
   - CLAUDE.md - New architecture
   - docs/architecture/composer-migration.md
   - Deployment guides

2. **Production Deployment**
   - Create deployment checklist
   - Backup production database
   - Deploy to staging first
   - Monitor for errors
   - Deploy to production

**Time**: 1-2 hours
**Risk**: LOW

---

## 🎯 Recommended Execution Order

### Today (While You're Away):

**✅ Phase 2.1: Database Layer** (DONE when you return)
- Migrate Database wrapper
- Fix type hints
- Test thoroughly

**✅ Phase 2.2: Archive Manager** (Quick win - already started)
- Add namespace
- Fix type hints
- You already fixed env variables!

**✅ Phase 2.3: Sponsorship Manager** (Most critical business logic)
- Full migration
- Preserve all logic
- Add type hints

### Tomorrow (With Your Input):

**Phase 2.4-2.8**: Remaining managers
- CSV, Avatar, Report, Email, Backup
- Similar pattern to Sponsorship

**Phase 3**: Integration
- Update all references
- index.php last (critical)

### Day 3:

**Phase 4**: Testing & Documentation
- Full test suite
- Documentation updates
- Deployment planning

---

## 🤖 What Can Be Automated

### High Automation (Rector):
- ✅ Adding namespaces to classes
- ✅ Converting class names in strings
- ✅ Updating `new ClassName()` calls
- ✅ Modern PHP syntax

### Medium Automation (Find & Replace):
- Updating `CFK_ClassName` → `use CFK\Namespace\ClassName`
- Adding `use` statements
- Updating static calls `Database::` → `Connection::`

### Manual Required:
- Array type hints (Rector doesn't infer types)
- Fixing undefined methods/variables
- Testing each component
- Reviewing business logic

---

## 📁 New Directory Structure

```
src/
├── Archive/
│   └── Manager.php (CFK\Archive\Manager)
├── Avatar/
│   └── Manager.php (CFK\Avatar\Manager)
├── Backup/
│   └── Manager.php (CFK\Backup\Manager)
├── CSV/
│   └── Handler.php (CFK\CSV\Handler)
├── Command/ (Symfony Console)
│   └── CleanupReservationsCommand.php
├── Database/
│   └── Connection.php (CFK\Database\Connection)
├── Email/
│   └── Manager.php (CFK\Email\Manager)
├── Report/
│   └── Manager.php (CFK\Report\Manager)
├── Reservation/
│   └── Functions.php (CFK\Reservation\Functions)
└── Sponsorship/
    └── Manager.php (CFK\Sponsorship\Manager)
```

---

## 🚨 Risk Assessment

### LOW RISK:
- Rector syntax improvements (already applied) ✅
- Adding type hints (doesn't change logic)
- Directory structure creation ✅
- Documentation updates

### MEDIUM RISK:
- Database wrapper migration (everything uses it)
- Sponsorship manager (core business logic)
- Integration updates (lots of files)

### HIGH RISK:
- index.php update (main entry point)
- Production deployment
- Cron job updates (automated processes)

### Mitigation:
- ✅ Test after each migration
- ✅ Keep old files until verified
- ✅ Use git to track every change
- ✅ Docker testing before production

---

## 📝 Success Criteria

### Phase 2 Complete When:
- [ ] All classes in `src/` with proper namespaces
- [ ] PHPStan errors reduced to < 20
- [ ] All `CFK_` prefixes removed
- [ ] Composer autoloader working

### Phase 3 Complete When:
- [ ] All files using namespaced classes
- [ ] Docker tests passing (35/36)
- [ ] Manual testing checklist complete
- [ ] No PHP fatal errors

### Phase 4 Complete When:
- [ ] Documentation updated
- [ ] Deployment checklist created
- [ ] Staging deployment successful
- [ ] Production deployment successful

---

## 🎁 Bonus: Optional Improvements

While migrating, consider:

1. **Add PHPMailer via Composer**
   ```bash
   composer require phpmailer/phpmailer
   ```

2. **Replace reservation_functions.php with class**
   - Convert global functions to static methods
   - Better organization

3. **Add more Console commands**
   - Year-end reset command
   - CSV import command
   - Email test command

4. **Improve type hints beyond arrays**
   - Union types where needed
   - Nullable types where appropriate

---

## 📊 Time Estimates

| Phase | Task | Time | Total |
|-------|------|------|-------|
| **2.1** | Database Migration | 1-2h | 1-2h |
| **2.2** | Archive Manager | 30m | 1.5-2.5h |
| **2.3** | Sponsorship Manager | 1h | 2.5-3.5h |
| **2.4-2.8** | Other Managers | 3h | 5.5-6.5h |
| **3** | Integration | 2-3h | 7.5-9.5h |
| **4** | Testing | 2-3h | 9.5-12.5h |
| **5** | Documentation | 1-2h | 10.5-14.5h |

**Total Estimated Time**: 10-15 hours of focused work

**Breakdown**:
- Autonomous work (while away): 6-8 hours
- With your input: 4-6 hours

---

## 🚀 Next Steps (Autonomous Work Plan)

### 1. Database Migration (NOW)
- Create `src/Database/Connection.php`
- Add namespace and type hints
- Test thoroughly
- Document changes

### 2. Archive Manager (QUICK WIN)
- Migrate `includes/archive_manager.php`
- You already fixed env variables!
- Just needs namespace + types

### 3. Sponsorship Manager (CORE)
- Full migration to `src/Sponsorship/Manager.php`
- Preserve all business logic
- Add comprehensive type hints

### 4. Create Detailed Report
- Migration progress
- Issues encountered
- Decisions needed from you
- Before/after examples
- Testing results

---

## 📧 Handoff Document (For Your Return)

Will include:
- ✅ What was completed
- ⚠️ What needs your decision
- 🔍 What was discovered
- 📝 Next steps recommendation
- 🧪 Testing status
- ⚡ Quick wins achieved
- 🐛 Bugs found/fixed

---

**Ready to proceed!** Starting with Database migration now.

Have a great day! The codebase will be significantly modernized when you return. 🚀
