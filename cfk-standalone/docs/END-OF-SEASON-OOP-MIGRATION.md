# End of Season - OOP Migration Plan

**Document Version:** 1.0.0
**Created:** 2025-12-01
**Target Version:** v1.9.x / v2.0.x
**Current Version:** v1.7.3 (procedural)
**Migration Timeline:** January/February 2026

---

## Executive Summary

This document provides a complete migration plan for converting the end-of-season functionality from procedural PHP (v1.7.3) to object-oriented PHP (v1.9.x/2.0.x).

**Current State (v1.7.3):**
- End-of-season functionality implemented as procedural functions
- Uses OOP Database layer but procedural business logic
- Deployed to production December 2025
- Will be used for 2025 season end operations

**Target State (v1.9.x/2.0.x):**
- Fully object-oriented architecture
- Follows existing `CFK\*\Manager` pattern
- Integration with existing OOP infrastructure
- Replaces v1.7.3 in January/February 2026

---

## Table of Contents

1. [Current Architecture (v1.7.3)](#current-architecture-v173)
2. [Target Architecture (v1.9.x)](#target-architecture-v19x)
3. [Migration Tasks](#migration-tasks)
4. [Code Transformation Examples](#code-transformation-examples)
5. [Testing Requirements](#testing-requirements)
6. [Deployment Plan](#deployment-plan)
7. [Rollback Strategy](#rollback-strategy)
8. [Timeline](#timeline)

---

## Current Architecture (v1.7.3)

### File Structure

```
cfk-standalone/
├── admin/
│   ├── sponsor-remaining-children.php      # CLI script (procedural)
│   ├── rollback-cfk-sponsorships.php       # CLI script (procedural)
│   ├── year_end_reset.php                  # Admin page (uses functions)
│   └── includes/
│       └── end_of_season_functions.php     # Core logic (procedural)
├── pages/
│   ├── cuny-home.php                       # End-of-season homepage
│   ├── cuny-my_sponsorships.php            # End-of-season my sponsorships
│   ├── old-home.php                        # Backup of original
│   └── old-my_sponsorships.php             # Backup of original
└── includes/
    ├── cuny-header.php                     # Simplified header
    ├── cuny-footer.php                     # Updated footer
    └── old-header.php                      # Backup of original
```

### Core Functions (Procedural)

**File:** `admin/includes/end_of_season_functions.php`

```php
function deployEndOfSeasonPages(): array
function restoreActiveSeasonPages(): array
function sponsorRemainingChildren(bool $dryRun = false): array
function rollbackAutoSponsorships(bool $dryRun = false): array
```

### Database Usage

```php
use CFK\Database\Connection as Database;

// Direct database calls in functions
$unsponsored = Database::fetchAll("SELECT ...");
```

### Key Configuration

```php
// Hardcoded in functions
$CFK_SPONSOR = [
    'name' => 'C-F-K Auto-Sponsor',
    'email' => 'end-of-season@christmasforkids.org',
    // ...
];
```

---

## Target Architecture (v1.9.x)

### File Structure (New)

```
cfk-standalone/
├── src/
│   └── EndOfSeason/
│       ├── Manager.php                     # Main business logic
│       ├── PageDeployer.php                # Page deployment logic
│       ├── AutoSponsor.php                 # Auto-sponsorship logic
│       └── Config.php                      # Configuration class
├── admin/
│   ├── sponsor-remaining-children.php      # CLI (uses Manager)
│   ├── rollback-cfk-sponsorships.php       # CLI (uses Manager)
│   └── year_end_reset.php                  # Admin page (uses Manager)
└── pages/
    └── (same structure as v1.7.3)
```

### Class Structure (OOP)

**Namespace:** `CFK\EndOfSeason`

#### 1. Manager.php (Main Controller)

```php
<?php

declare(strict_types=1);

namespace CFK\EndOfSeason;

use CFK\Database\Connection as Database;
use CFK\Sponsorship\Manager as SponsorshipManager;

/**
 * End of Season Manager
 * Handles all end-of-season operations
 */
class Manager
{
    private Database $db;
    private PageDeployer $pageDeployer;
    private AutoSponsor $autoSponsor;
    private Config $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->pageDeployer = new PageDeployer();
        $this->autoSponsor = new AutoSponsor();
        $this->config = new Config();
    }

    /**
     * Deploy end-of-season pages
     *
     * @return array{success: bool, message: string}
     */
    public function deployEndOfSeasonPages(): array
    {
        return $this->pageDeployer->deployPages([
            'pages/cuny-home.php' => 'pages/home.php',
            'pages/cuny-my_sponsorships.php' => 'pages/my_sponsorships.php',
            'includes/cuny-header.php' => 'includes/header.php',
            'includes/cuny-footer.php' => 'includes/footer.php',
        ]);
    }

    /**
     * Restore active-season pages
     *
     * @return array{success: bool, message: string}
     */
    public function restoreActiveSeasonPages(): array
    {
        return $this->pageDeployer->restorePages([
            'pages/old-home.php' => 'pages/home.php',
            'pages/old-my_sponsorships.php' => 'pages/my_sponsorships.php',
            'includes/old-header.php' => 'includes/header.php',
        ]);
    }

    /**
     * Sponsor all remaining unsponsored children
     *
     * @param bool $dryRun Preview mode
     * @return array{success: bool, message: string, count: int, children?: array, dry_run?: bool, success_count?: int, error_count?: int, errors?: array}
     */
    public function sponsorRemainingChildren(bool $dryRun = false): array
    {
        return $this->autoSponsor->sponsorRemaining($dryRun);
    }

    /**
     * Rollback auto-sponsorships
     *
     * @param bool $dryRun Preview mode
     * @return array{success: bool, message: string, count: int, sponsorships?: array, dry_run?: bool, success_count?: int, error_count?: int}
     */
    public function rollbackAutoSponsorships(bool $dryRun = false): array
    {
        return $this->autoSponsor->rollback($dryRun);
    }

    /**
     * Get count of unsponsored children
     */
    public function getUnsponsoredCount(): int
    {
        $result = $this->db->fetchRow(
            "SELECT COUNT(*) as count FROM children c
             WHERE c.id NOT IN (
                 SELECT child_id FROM sponsorships
                 WHERE status IN ('confirmed', 'logged')
             )"
        );

        return (int)($result['count'] ?? 0);
    }

    /**
     * Get count of auto-sponsorships
     */
    public function getAutoSponsorshipCount(): int
    {
        $result = $this->db->fetchRow(
            "SELECT COUNT(*) as count FROM sponsorships
             WHERE sponsor_email = ?",
            [$this->config->getAutoSponsorEmail()]
        );

        return (int)($result['count'] ?? 0);
    }
}
```

#### 2. PageDeployer.php

```php
<?php

declare(strict_types=1);

namespace CFK\EndOfSeason;

use Exception;

/**
 * Page Deployment Handler
 * Manages deployment and restoration of end-of-season pages
 */
class PageDeployer
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = __DIR__ . '/../../';
    }

    /**
     * Deploy pages with backups
     *
     * @param array<string, string> $pageMap Map of source => destination
     * @return array{success: bool, message: string}
     */
    public function deployPages(array $pageMap): array
    {
        try {
            $backupTimestamp = date('Y-m-d-His');

            // Create backups first
            foreach ($pageMap as $dest) {
                $this->createBackup($dest, $backupTimestamp);
            }

            // Deploy new pages
            foreach ($pageMap as $source => $dest) {
                $this->copyFile($source, $dest);
            }

            return [
                'success' => true,
                'message' => "Pages deployed successfully! Backups saved with timestamp {$backupTimestamp}."
            ];
        } catch (Exception $e) {
            error_log("Failed to deploy pages: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to deploy pages: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Restore pages from backups
     *
     * @param array<string, string> $pageMap Map of source => destination
     * @return array{success: bool, message: string}
     */
    public function restorePages(array $pageMap): array
    {
        try {
            $backupTimestamp = date('Y-m-d-His');

            // Backup current pages before restoring
            foreach ($pageMap as $dest) {
                $this->createBackup($dest, $backupTimestamp);
            }

            // Restore from old- pages
            foreach ($pageMap as $source => $dest) {
                $this->copyFile($source, $dest);
            }

            return [
                'success' => true,
                'message' => "Pages restored successfully! Previous version backed up with timestamp {$backupTimestamp}."
            ];
        } catch (Exception $e) {
            error_log("Failed to restore pages: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore pages: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create backup of a file
     */
    private function createBackup(string $file, string $timestamp): void
    {
        $fullPath = $this->basePath . $file;
        if (!file_exists($fullPath)) {
            return; // Skip if file doesn't exist
        }

        $pathInfo = pathinfo($fullPath);
        $backupPath = $pathInfo['dirname'] . '/' .
                      $pathInfo['filename'] . '-backup-' . $timestamp . '.' .
                      $pathInfo['extension'];

        copy($fullPath, $backupPath);
    }

    /**
     * Copy file with validation
     */
    private function copyFile(string $source, string $dest): void
    {
        $sourcePath = $this->basePath . $source;
        $destPath = $this->basePath . $dest;

        if (!file_exists($sourcePath)) {
            throw new Exception("Source file not found: {$source}");
        }

        if (!copy($sourcePath, $destPath)) {
            throw new Exception("Failed to copy {$source} to {$dest}");
        }
    }
}
```

#### 3. AutoSponsor.php

```php
<?php

declare(strict_types=1);

namespace CFK\EndOfSeason;

use CFK\Database\Connection as Database;
use CFK\Sponsorship\Manager as SponsorshipManager;
use Exception;

/**
 * Auto-Sponsorship Handler
 * Manages automatic sponsorship of unsponsored children
 */
class AutoSponsor
{
    private Database $db;
    private SponsorshipManager $sponsorshipManager;
    private Config $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->sponsorshipManager = new SponsorshipManager();
        $this->config = new Config();
    }

    /**
     * Sponsor remaining unsponsored children
     *
     * @param bool $dryRun Preview mode
     * @return array{success: bool, message: string, count: int, children?: array, dry_run?: bool, success_count?: int, error_count?: int, errors?: array}
     */
    public function sponsorRemaining(bool $dryRun = false): array
    {
        try {
            $unsponsored = $this->getUnsponsoredChildren();
            $count = count($unsponsored);

            if ($count === 0) {
                return [
                    'success' => true,
                    'message' => 'All children are already sponsored!',
                    'count' => 0,
                    'children' => []
                ];
            }

            if ($dryRun) {
                return [
                    'success' => true,
                    'message' => "Preview: {$count} children would be auto-sponsored",
                    'count' => $count,
                    'children' => $unsponsored,
                    'dry_run' => true
                ];
            }

            return $this->createSponsorships($unsponsored);
        } catch (Exception $e) {
            error_log("Failed to sponsor remaining children: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed: ' . $e->getMessage(),
                'count' => 0
            ];
        }
    }

    /**
     * Rollback auto-sponsorships
     *
     * @param bool $dryRun Preview mode
     * @return array{success: bool, message: string, count: int, sponsorships?: array, dry_run?: bool, success_count?: int, error_count?: int}
     */
    public function rollback(bool $dryRun = false): array
    {
        try {
            $autoSponsorships = $this->getAutoSponsorships();
            $count = count($autoSponsorships);

            if ($count === 0) {
                return [
                    'success' => true,
                    'message' => 'No auto-sponsorships found to remove.',
                    'count' => 0
                ];
            }

            if ($dryRun) {
                return [
                    'success' => true,
                    'message' => "Preview: {$count} auto-sponsorships would be removed",
                    'count' => $count,
                    'sponsorships' => $autoSponsorships,
                    'dry_run' => true
                ];
            }

            return $this->removeSponsorships($autoSponsorships);
        } catch (Exception $e) {
            error_log("Failed to rollback auto-sponsorships: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed: ' . $e->getMessage(),
                'count' => 0
            ];
        }
    }

    /**
     * Get all unsponsored children
     *
     * @return array<int, array<string, mixed>>
     */
    private function getUnsponsoredChildren(): array
    {
        return $this->db->fetchAll(
            "SELECT c.id, c.family_id, CONCAT(f.family_number, c.child_letter) as display_id,
                    c.age_months, c.gender, c.grade, f.family_number
             FROM children c
             JOIN families f ON c.family_id = f.id
             WHERE c.id NOT IN (
                 SELECT child_id FROM sponsorships
                 WHERE status IN ('confirmed', 'logged')
             )
             ORDER BY f.family_number, c.child_letter"
        );
    }

    /**
     * Get all auto-sponsorships
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAutoSponsorships(): array
    {
        return $this->db->fetchAll(
            "SELECT s.id, s.child_id, s.sponsor_name, s.sponsor_email,
                    CONCAT(f.family_number, c.child_letter) as display_id,
                    s.confirmation_date
             FROM sponsorships s
             JOIN children c ON s.child_id = c.id
             JOIN families f ON c.family_id = f.id
             WHERE s.sponsor_email = ?
             AND s.status = 'confirmed'
             ORDER BY s.confirmation_date DESC",
            [$this->config->getAutoSponsorEmail()]
        );
    }

    /**
     * Create sponsorships for children
     *
     * @param array<int, array<string, mixed>> $children
     * @return array{success: bool, message: string, count: int, success_count: int, error_count: int, errors: array}
     */
    private function createSponsorships(array $children): array
    {
        $count = count($children);
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        $sponsorData = $this->config->getAutoSponsorData();

        foreach ($children as $child) {
            try {
                $result = $this->db->execute(
                    "INSERT INTO sponsorships (
                        child_id, sponsor_name, sponsor_email, sponsor_phone,
                        sponsor_address, sponsor_city, sponsor_state, sponsor_zip,
                        confirmation_date, status, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'confirmed', ?)",
                    [
                        $child['id'],
                        $sponsorData['name'],
                        $sponsorData['email'],
                        $sponsorData['phone'],
                        $sponsorData['address'],
                        $sponsorData['city'],
                        $sponsorData['state'],
                        $sponsorData['zip'],
                        'Auto-sponsored by CFK - End of season unsponsored child'
                    ]
                );

                if ($result) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = "Failed to sponsor child {$child['display_id']}";
                }
            } catch (Exception $e) {
                $errorCount++;
                $errors[] = "Error sponsoring child {$child['display_id']}: " . $e->getMessage();
            }
        }

        return [
            'success' => $errorCount === 0,
            'message' => "Successfully sponsored {$successCount} of {$count} children.",
            'count' => $count,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ];
    }

    /**
     * Remove sponsorships
     *
     * @param array<int, array<string, mixed>> $sponsorships
     * @return array{success: bool, message: string, count: int, success_count: int, error_count: int}
     */
    private function removeSponsorships(array $sponsorships): array
    {
        $count = count($sponsorships);
        $successCount = 0;
        $errorCount = 0;

        foreach ($sponsorships as $sponsorship) {
            try {
                $result = $this->db->execute(
                    "DELETE FROM sponsorships WHERE id = ? AND sponsor_email = ?",
                    [$sponsorship['id'], $this->config->getAutoSponsorEmail()]
                );

                if ($result) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } catch (Exception $e) {
                $errorCount++;
            }
        }

        return [
            'success' => $errorCount === 0,
            'message' => "Successfully removed {$successCount} of {$count} auto-sponsorships.",
            'count' => $count,
            'success_count' => $successCount,
            'error_count' => $errorCount
        ];
    }
}
```

#### 4. Config.php

```php
<?php

declare(strict_types=1);

namespace CFK\EndOfSeason;

/**
 * End of Season Configuration
 * Centralizes configuration for end-of-season operations
 */
class Config
{
    private const AUTO_SPONSOR_NAME = 'C-F-K Auto-Sponsor';
    private const AUTO_SPONSOR_EMAIL = 'end-of-season@christmasforkids.org';

    /**
     * Get auto-sponsor email address
     */
    public function getAutoSponsorEmail(): string
    {
        return self::AUTO_SPONSOR_EMAIL;
    }

    /**
     * Get auto-sponsor data
     *
     * @return array{name: string, email: string, phone: string, address: string, city: string, state: string, zip: string}
     */
    public function getAutoSponsorData(): array
    {
        return [
            'name' => self::AUTO_SPONSOR_NAME,
            'email' => self::AUTO_SPONSOR_EMAIL,
            'phone' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'zip' => ''
        ];
    }
}
```

---

## Migration Tasks

### Phase 1: Create OOP Classes (Week 1)

- [ ] **Task 1.1:** Create `src/EndOfSeason/` directory
- [ ] **Task 1.2:** Create `src/EndOfSeason/Manager.php`
  - Copy logic from `end_of_season_functions.php`
  - Convert to class methods
  - Add proper type hints and PHPDoc
- [ ] **Task 1.3:** Create `src/EndOfSeason/PageDeployer.php`
  - Extract page deployment logic
  - Add validation and error handling
- [ ] **Task 1.4:** Create `src/EndOfSeason/AutoSponsor.php`
  - Extract auto-sponsorship logic
  - Integrate with existing `SponsorshipManager`
- [ ] **Task 1.5:** Create `src/EndOfSeason/Config.php`
  - Move configuration constants
  - Make easily configurable

**Deliverable:** All OOP classes created and unit-testable

### Phase 2: Update Admin Integration (Week 1-2)

- [ ] **Task 2.1:** Update `admin/year_end_reset.php`
  - Replace function calls with Manager class
  - Update error handling
  - Test all 4 operations
- [ ] **Task 2.2:** Update `admin/sponsor-remaining-children.php`
  - Instantiate Manager class
  - Replace function calls
  - Maintain CLI output formatting
- [ ] **Task 2.3:** Update `admin/rollback-cfk-sponsorships.php`
  - Instantiate Manager class
  - Replace function calls
  - Maintain CLI output formatting
- [ ] **Task 2.4:** Delete `admin/includes/end_of_season_functions.php`
  - Verify no references remain
  - Remove from git

**Deliverable:** Admin integration complete and tested

### Phase 3: Testing (Week 2)

- [ ] **Task 3.1:** Unit tests for `Manager` class
- [ ] **Task 3.2:** Unit tests for `PageDeployer` class
- [ ] **Task 3.3:** Unit tests for `AutoSponsor` class
- [ ] **Task 3.4:** Integration tests for full workflow
- [ ] **Task 3.5:** Manual testing in development
- [ ] **Task 3.6:** PHPStan level 8 compliance
- [ ] **Task 3.7:** PHPCS PSR-12 compliance

**Deliverable:** All tests passing, code quality verified

### Phase 4: Documentation (Week 2)

- [ ] **Task 4.1:** Update `docs/admin-end-of-season-integration.md`
  - Reference new OOP classes
  - Update code examples
- [ ] **Task 4.2:** Create `docs/technical/end-of-season-architecture.md`
  - Document OOP design
  - Class diagrams
  - Sequence diagrams
- [ ] **Task 4.3:** Update CLI script documentation
  - Reflect OOP usage
- [ ] **Task 4.4:** Add inline PHPDoc to all methods

**Deliverable:** Complete documentation

### Phase 5: Deployment Preparation (Week 3)

- [ ] **Task 5.1:** Create staging deployment
- [ ] **Task 5.2:** Test in staging environment
- [ ] **Task 5.3:** Create deployment checklist
- [ ] **Task 5.4:** Create rollback plan
- [ ] **Task 5.5:** Schedule production deployment

**Deliverable:** Ready for production

### Phase 6: Production Deployment (Week 3-4)

- [ ] **Task 6.1:** Deploy to production
- [ ] **Task 6.2:** Verify all functionality
- [ ] **Task 6.3:** Monitor for errors
- [ ] **Task 6.4:** Document deployment
- [ ] **Task 6.5:** Archive v1.7.3

**Deliverable:** v1.9.x/2.0.x in production

---

## Code Transformation Examples

### Example 1: Admin Page Integration

**Before (v1.7.3):**
```php
// admin/year_end_reset.php
require_once __DIR__ . '/includes/end_of_season_functions.php';

if ($_POST['eos_operation'] === 'deploy_eos_pages') {
    $eosResult = deployEndOfSeasonPages();
    if ($eosResult['success']) {
        $success = $eosResult['message'];
    } else {
        $errors[] = $eosResult['message'];
    }
}
```

**After (v1.9.x):**
```php
// admin/year_end_reset.php
use CFK\EndOfSeason\Manager as EndOfSeasonManager;

$eosManager = new EndOfSeasonManager();

if ($_POST['eos_operation'] === 'deploy_eos_pages') {
    $eosResult = $eosManager->deployEndOfSeasonPages();
    if ($eosResult['success']) {
        $success = $eosResult['message'];
    } else {
        $errors[] = $eosResult['message'];
    }
}
```

### Example 2: CLI Script

**Before (v1.7.3):**
```php
// admin/sponsor-remaining-children.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// ... CLI argument parsing ...

try {
    $unsponsored = Database::fetchAll("SELECT ...");
    // ... sponsorship logic ...
} catch (Exception $e) {
    // ...
}
```

**After (v1.9.x):**
```php
// admin/sponsor-remaining-children.php
use CFK\EndOfSeason\Manager as EndOfSeasonManager;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// ... CLI argument parsing ...

$manager = new EndOfSeasonManager();
$result = $manager->sponsorRemainingChildren($isDryRun);

// ... process result for CLI output ...
```

### Example 3: Preview/Execute Pattern

**Before (v1.7.3):**
```php
$result = sponsorRemainingChildren($dryRun);
```

**After (v1.9.x):**
```php
$manager = new EndOfSeasonManager();
$result = $manager->sponsorRemainingChildren($dryRun);
```

The interface remains the same, but implementation is cleaner and testable.

---

## Testing Requirements

### Unit Tests

**Location:** `tests/Unit/EndOfSeason/`

**Required Test Files:**
- `ManagerTest.php`
- `PageDeployerTest.php`
- `AutoSponsorTest.php`
- `ConfigTest.php`

**Test Coverage:**
- Minimum 80% code coverage
- All public methods tested
- Edge cases covered
- Error conditions tested

### Integration Tests

**Location:** `tests/Integration/EndOfSeason/`

**Required Tests:**
- Full deployment workflow
- Full auto-sponsor workflow
- Rollback workflow
- Admin panel integration
- CLI script execution

### Manual Testing Checklist

**Admin Panel (year_end_reset.php):**
- [ ] Preview unsponsored children (dry run)
- [ ] Preview auto-sponsorships to rollback (dry run)
- [ ] Deploy end-of-season pages (execute)
- [ ] Verify pages deployed correctly
- [ ] Restore active-season pages (execute)
- [ ] Verify pages restored correctly
- [ ] Auto-sponsor children (execute)
- [ ] Verify sponsorships created
- [ ] Rollback auto-sponsorships (execute)
- [ ] Verify sponsorships removed

**CLI Scripts:**
- [ ] `php admin/sponsor-remaining-children.php --dry-run`
- [ ] `php admin/sponsor-remaining-children.php --execute`
- [ ] `php admin/rollback-cfk-sponsorships.php --dry-run`
- [ ] `php admin/rollback-cfk-sponsorships.php --execute`

**Code Quality:**
- [ ] PHPStan level 8 passes
- [ ] PHPCS PSR-12 passes
- [ ] No deprecation warnings
- [ ] All type hints correct

---

## Deployment Plan

### Pre-Deployment

1. **Backup current production**
   - Full database backup
   - Full file backup
   - Document current branch/commit

2. **Verify staging**
   - All tests passing
   - Manual verification complete
   - Performance acceptable

3. **Communication**
   - Notify stakeholders
   - Schedule maintenance window (if needed)

### Deployment Steps

1. **Deploy to production** (January/February 2026)
   ```bash
   # Switch to v1.9.x branch
   git checkout v1.9.x  # or v2.0.x

   # Deploy to production
   /deploy-production
   ```

2. **Post-deployment verification**
   - Homepage loads
   - Admin panel accessible
   - Year-End Reset page shows end-of-season operations
   - Preview buttons work
   - No PHP errors in logs

3. **Monitor**
   - Check error logs hourly for first 24 hours
   - Verify no user-reported issues

### Rollback Plan

**If issues occur:**

```bash
# Option 1: Redeploy v1.7.3
git checkout v1.7.3-production-hardening
/deploy-production

# Option 2: Restore from backup
# (Use server backup restoration process)
```

**Rollback triggers:**
- Critical functionality broken
- PHP errors preventing site use
- Admin panel inaccessible
- Data corruption

---

## Timeline

### 2025 Season End (December 2025)
- ✅ v1.7.3 deployed to production (December 1, 2025)
- ⏳ Use v1.7.3 for end-of-season operations (late December 2025)
- ⏳ Complete season-end tasks
- ⏳ Verify all data correct

### Migration Period (January/February 2026)

**Week 1 (Early January):**
- Create OOP classes
- Begin admin integration
- Start testing

**Week 2 (Mid January):**
- Complete admin integration
- Comprehensive testing
- Code quality verification
- Documentation updates

**Week 3 (Late January):**
- Staging deployment
- Final testing
- Deployment preparation

**Week 4 (Early February):**
- Production deployment
- Monitoring
- Documentation finalization

**Week 5+ (Mid February onwards):**
- Archive v1.7.3
- Monitor v1.9.x/2.0.x
- Address any issues

### Post-Migration

**February 2026:**
- v1.9.x/2.0.x stable in production
- v1.7.3 retired
- Documentation complete

**Throughout 2026:**
- Use OOP version for all operations
- Prepare for next season (2026 end-of-season)

---

## Success Criteria

**Migration is successful when:**

1. ✅ All OOP classes created and tested
2. ✅ Admin panel fully functional with OOP classes
3. ✅ CLI scripts work with OOP classes
4. ✅ All tests passing (unit, integration, manual)
5. ✅ PHPStan level 8 compliance
6. ✅ PHPCS PSR-12 compliance
7. ✅ Deployed to production without errors
8. ✅ Documentation updated
9. ✅ v1.7.3 archived
10. ✅ Team comfortable with new architecture

---

## Dependencies and Integration Points

### Existing OOP Classes to Integrate With

1. **CFK\Sponsorship\Manager**
   - Consider using existing methods instead of direct DB calls
   - Integrate sponsorship creation workflow

2. **CFK\Database\Connection**
   - Already using (no changes needed)

3. **CFK\Email\Manager**
   - Consider email notifications for operations

4. **CFK\Archive\Manager**
   - Potential integration for season archival

### Configuration Files

- `config/config.php` - No changes needed
- `.env.production` - No changes needed

### Database Schema

- No schema changes required
- Existing tables sufficient

---

## Risk Assessment

### Low Risk
- Database operations (already tested in v1.7.3)
- Page deployment (simple file operations)

### Medium Risk
- Class instantiation in admin panel (new pattern)
- CLI script integration (behavioral changes)

### Mitigation Strategies

1. **Comprehensive testing** - Catch issues before production
2. **Staging environment** - Test in production-like environment
3. **Rollback plan** - Quick revert if needed
4. **Gradual deployment** - Can deploy to staging for extended period
5. **Documentation** - Clear instructions for usage

---

## Notes and Considerations

### Why OOP is Better Here

1. **Testability** - Can unit test each class independently
2. **Maintainability** - Separation of concerns
3. **Reusability** - Classes can be used in different contexts
4. **Type Safety** - Better IDE support and error detection
5. **Architecture Consistency** - Matches rest of v1.9.x codebase

### Breaking Changes

**None expected for users:**
- Admin UI remains the same
- CLI interface remains the same
- Functionality identical

**For developers:**
- Must use OOP classes instead of functions
- Different file locations

### Future Enhancements (Post-Migration)

Consider adding:
- Email notifications when operations complete
- Detailed audit logs
- Scheduled operations (cron integration)
- Webhook support for external integrations
- API endpoints for programmatic access

---

## References

**Current Implementation (v1.7.3):**
- `admin/includes/end_of_season_functions.php`
- `docs/admin-end-of-season-integration.md`
- `docs/end-of-season-pages.md`
- `docs/sponsor-remaining-children.md`

**Related OOP Classes (Examples):**
- `src/Sponsorship/Manager.php`
- `src/Archive/Manager.php`
- `src/Email/Manager.php`

**Standards:**
- PSR-12 Coding Standard
- PHPStan Level 8
- PHP 8.2+ features

---

## Appendix A: File Checklist

### Files to Create
- [ ] `src/EndOfSeason/Manager.php`
- [ ] `src/EndOfSeason/PageDeployer.php`
- [ ] `src/EndOfSeason/AutoSponsor.php`
- [ ] `src/EndOfSeason/Config.php`
- [ ] `tests/Unit/EndOfSeason/ManagerTest.php`
- [ ] `tests/Unit/EndOfSeason/PageDeployerTest.php`
- [ ] `tests/Unit/EndOfSeason/AutoSponsorTest.php`
- [ ] `tests/Integration/EndOfSeason/WorkflowTest.php`

### Files to Modify
- [ ] `admin/year_end_reset.php`
- [ ] `admin/sponsor-remaining-children.php`
- [ ] `admin/rollback-cfk-sponsorships.php`
- [ ] `docs/admin-end-of-season-integration.md`

### Files to Delete
- [ ] `admin/includes/end_of_season_functions.php` (after migration complete)

### Files to Keep Unchanged
- ✅ `pages/cuny-home.php`
- ✅ `pages/cuny-my_sponsorships.php`
- ✅ `pages/old-home.php`
- ✅ `pages/old-my_sponsorships.php`
- ✅ `includes/cuny-header.php`
- ✅ `includes/cuny-footer.php`
- ✅ `includes/old-header.php`

---

## Appendix B: Quick Reference Commands

### Development Commands

```bash
# Run PHPStan
vendor/bin/phpstan analyse src/EndOfSeason/ --level=8

# Run PHPCS
vendor/bin/phpcs src/EndOfSeason/ --standard=PSR12

# Run unit tests
vendor/bin/phpunit tests/Unit/EndOfSeason/

# Run all tests
vendor/bin/phpunit
```

### Deployment Commands

```bash
# Switch to v1.9.x
git checkout v1.9.x

# Deploy to production
/deploy-production

# Monitor logs
tail -f /path/to/error.log
```

---

**Document Status:** Draft - Ready for Implementation
**Next Review:** After v1.7.3 season-end operations complete (January 2026)
**Owner:** Development Team
**Approver:** Project Lead
