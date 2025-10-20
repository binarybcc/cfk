# Development Tools Guide

This document explains the development tools installed for the CFK Sponsorship System and how to use them.

## Installed Tools

### 1. Rector - Automated Refactoring
**Purpose**: Automatically modernize PHP code and add namespaces

**Usage**:
```bash
# Preview changes (dry-run)
composer rector

# Apply changes
composer rector:fix

# Process specific directory
vendor/bin/rector process includes --dry-run
vendor/bin/rector process includes
```

**Configuration**: `rector.php`

**What it does**:
- Converts old PHP syntax to PHP 8.2 modern syntax
- Automatically adds type hints
- Removes dead code
- Converts long arrays to short syntax `[]`
- Inlines constructor properties
- Adds strict types declarations

### 2. PHPStan - Static Analysis
**Purpose**: Find bugs without running code

**Usage**:
```bash
# Analyze code (level 8 - strictest)
composer phpstan

# Analyze specific directory
vendor/bin/phpstan analyse includes --level=8

# Generate baseline (ignore current errors)
vendor/bin/phpstan analyse --generate-baseline
```

**Configuration**: `phpstan.neon`

**What it catches**:
- Type mismatches
- Undefined variables
- Calling methods that don't exist
- Null pointer issues
- Dead code
- Missing type hints

### 3. PHP CodeSniffer (PHPCS) - Code Style
**Purpose**: Enforce PSR-12 coding standards

**Usage**:
```bash
# Check code style
composer cs-check

# Auto-fix code style issues
composer cs-fix

# Check specific file
vendor/bin/phpcs includes/archive_manager.php

# Fix specific file
vendor/bin/phpcbf includes/archive_manager.php
```

**Configuration**: `phpcs.xml`

**What it checks**:
- Indentation (4 spaces)
- Line length
- Brace placement
- Naming conventions
- PSR-12 compliance

### 4. PHP-CS-Fixer - Advanced Formatting
**Purpose**: Auto-format code beyond PHPCS capabilities

**Usage**:
```bash
# Preview formatting changes
composer php-cs-fixer

# Apply formatting
composer php-cs-fixer:fix

# Fix specific directory
vendor/bin/php-cs-fixer fix includes
```

**Configuration**: `.php-cs-fixer.php`

**What it does**:
- Sorts imports alphabetically
- Adds trailing commas in arrays
- Enforces strict types
- Binary operator spacing
- Method argument spacing

### 5. Dotenv (phpdotenv) - Environment Variables
**Purpose**: Robust .env file handling

**Usage in code**:
```php
<?php
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get required variable (throws if missing)
$dbHost = $_ENV['DB_HOST'];

// With validation
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']);
$dotenv->required('DB_PORT')->isInteger();
```

**Benefits**:
- Validates required variables exist
- Type casting (int, bool, etc.)
- Better error messages
- Multiple .env file support
- Used by Laravel, Symfony

### 6. Monolog - Logging Library
**Purpose**: Professional structured logging

**Usage in code**:
```php
<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

// Create logger
$log = new Logger('cfk');
$log->pushHandler(new RotatingFileHandler(__DIR__ . '/logs/app.log', 30, Logger::WARNING));

// Log messages
$log->debug('Debug info');
$log->info('User action', ['user_id' => 123]);
$log->warning('Something unusual');
$log->error('Error occurred', ['exception' => $e]);
$log->critical('System failure!');

// Contextual data
$log->info('Sponsorship created', [
    'child_id' => $childId,
    'sponsor_email' => $email,
    'ip_address' => $_SERVER['REMOTE_ADDR']
]);
```

**Benefits**:
- Multiple log handlers (file, email, database)
- Log rotation
- Structured logging (JSON format)
- Context data
- PSR-3 compliant

## Recommended Workflow

### Pre-Migration (Current State)

1. **Run Rector dry-run** to see what will change:
   ```bash
   composer rector
   ```

2. **Run PHPStan** on existing code:
   ```bash
   # Will fail on src/ for now, that's OK
   vendor/bin/phpstan analyse includes --level=6
   ```

3. **Check code style**:
   ```bash
   composer cs-check
   ```

### During Composer Migration

1. **Create namespaced class** in `src/`
2. **Run tools on new class**:
   ```bash
   vendor/bin/rector process src/YourNewClass.php
   vendor/bin/phpstan analyse src/YourNewClass.php --level=8
   vendor/bin/php-cs-fixer fix src/YourNewClass.php
   ```

3. **Verify no errors**:
   ```bash
   php -l src/YourNewClass.php
   ```

### Post-Migration

Run full test suite:
```bash
composer rector         # Should show no changes
composer phpstan        # Should pass level 8
composer cs-check       # Should pass PSR-12
composer test           # Run PHPUnit tests
```

## Integration with Git

### Pre-commit Hook (Optional)

Create `.git/hooks/pre-commit`:
```bash
#!/bin/bash

echo "Running code quality checks..."

# Run PHP-CS-Fixer
composer php-cs-fixer:fix

# Run PHPStan
composer phpstan
if [ $? -ne 0 ]; then
    echo "PHPStan failed. Fix errors before committing."
    exit 1
fi

# Add any auto-fixed files
git add -u

exit 0
```

Make executable:
```bash
chmod +x .git/hooks/pre-commit
```

## CI/CD Integration

For GitHub Actions, add to `.github/workflows/code-quality.yml`:
```yaml
name: Code Quality

on: [push, pull_request]

jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install --no-interaction

      - name: Run PHPStan
        run: composer phpstan

      - name: Check code style
        run: composer cs-check

      - name: Run Rector (dry-run)
        run: composer rector
```

## Tool Priorities

**Immediate Use (Pre-Migration)**:
1. ✅ **Rector** - See what will change during migration
2. ✅ **PHPStan** - Catch bugs in existing code
3. ✅ **PHPCS** - Standardize code style

**During Migration**:
4. ✅ **Dotenv** - Replace custom `getenv()` logic
5. ✅ **Monolog** - Standardize logging
6. ✅ **PHP-CS-Fixer** - Format new namespaced classes

## Troubleshooting

### "Class not found" errors in PHPStan
- Expected during migration
- Add to `phpstan.neon` ignoreErrors if needed
- Will resolve once migration complete

### Rector making too many changes
- Adjust rules in `rector.php`
- Process one directory at a time
- Review each change in git diff

### PHPCS conflicts with PHP-CS-Fixer
- Both tools configured to work together
- Run PHP-CS-Fixer first, then PHPCS
- PHPCS is for checking, PHP-CS-Fixer is for fixing

## Resources

- [Rector Documentation](https://getrector.com/documentation)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHP-CS-Fixer Documentation](https://cs.symfony.com/)
- [Monolog Documentation](https://github.com/Seldaek/monolog)
- [PHP dotenv Documentation](https://github.com/vlucas/phpdotenv)

## Next Steps

1. Review Rector's suggested changes: `composer rector`
2. Create first migrated class in `src/`
3. Run all tools on that class
4. Iterate and refine tool configurations
5. Add tools to CI/CD pipeline
