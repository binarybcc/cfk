# Tracy Debugging Guide

Tracy is a powerful debugging tool for PHP that provides beautiful error pages and debugging panels.

## Installation

✅ Already installed: `tracy/tracy: ^2.10`

## Setup

### Enable Tracy in Development Only

Add to `config/config.php` (or create a new `config/tracy.php`):

```php
<?php
declare(strict_types=1);

use Tracy\Debugger;

// Only enable Tracy in development mode
if (config('app_debug', false)) {
    Debugger::enable(Debugger::Development, __DIR__ . '/../logs');

    // Optional: Disable strict mode if it's too noisy during migration
    Debugger::$strictMode = false;

    // Optional: Configure email for production errors
    // Debugger::$email = 'errors@cforkids.org';
} else {
    // Production mode - minimal logging
    Debugger::enable(Debugger::Production, __DIR__ . '/../logs');
    Debugger::$email = 'admin@cforkids.org'; // Get notified of errors
}
```

### Update index.php

Add near the top of `index.php` (after config.php is loaded):

```php
<?php
declare(strict_types=1);

define('CFK_APP', true);

require_once __DIR__ . '/config/config.php';

// Enable Tracy debugging (development only)
if (config('app_debug', false)) {
    require_once __DIR__ . '/config/tracy.php';
}

// Rest of your code...
```

## Features

### 1. Beautiful Error Pages

Instead of PHP's ugly error messages, you get:
- Full stack trace with highlighted code
- All variables in scope
- SQL queries that were executed
- Request/response information
- Environment details

**Before (PHP default)**:
```
Fatal error: Call to undefined function foo() in /path/to/file.php on line 123
```

**After (Tracy)**:
Beautiful color-coded page with:
- Exact error location
- Variable values at that moment
- Full call stack
- Suggestions for fixing

### 2. Debug Bar (Only in Development)

Tracy adds a debugging bar to bottom of page showing:
- Execution time
- Memory usage
- Database queries
- Session data
- Error/warning count

### 3. Dump Variables

Instead of `var_dump()`:

```php
<?php
// Old way
var_dump($child);
print_r($sponsorships);

// New way - much better output
\Tracy\Debugger::dump($child);
bdump($sponsorships); // "bar dump" - dumps to debug bar

// Shorthand (if enabled)
dump($child); // Same as Debugger::dump()
```

### 4. Logging

```php
<?php
use Tracy\Debugger;

// Log messages
Debugger::log('User logged in', 'info');
Debugger::log('Payment failed', 'error');
Debugger::log($exception); // Logs exception with full trace

// Logs go to: logs/info.log, logs/error.log, logs/exception.log
```

### 5. Profiling Code

```php
<?php
use Tracy\Debugger;

Debugger::timer();

// Your code here
$result = expensiveOperation();

$elapsed = Debugger::timer();
Debugger::barDump($elapsed, 'Execution time');
```

### 6. Detect Production Errors

Tracy can email you when errors occur in production:

```php
<?php
// config/tracy.php for production
Debugger::enable(Debugger::Production, __DIR__ . '/../logs');
Debugger::$email = 'admin@cforkids.org';
Debugger::$emailSnooze = '2 hours'; // Don't spam - max 1 email per 2 hours
```

## Common Use Cases

### Debug Database Queries

```php
<?php
// In your database wrapper
public static function execute(string $sql, array $params = []): bool
{
    try {
        \Tracy\Debugger::barDump($sql, 'SQL Query');
        \Tracy\Debugger::barDump($params, 'Parameters');

        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute($params);
    } catch (\PDOException $e) {
        \Tracy\Debugger::log($e);
        throw $e;
    }
}
```

### Debug Email Sending

```php
<?php
// In email manager
public function send(): bool
{
    \Tracy\Debugger::barDump([
        'to' => $this->to,
        'subject' => $this->subject,
        'body_length' => strlen($this->body),
    ], 'Email Attempt');

    $result = $this->mailer->send();

    \Tracy\Debugger::barDump($result, 'Email Result');

    return $result;
}
```

### Debug Reservation Flow

```php
<?php
// In reservation functions
function createReservation(int $childId, string $email): array
{
    \Tracy\Debugger::barDump($childId, 'Child ID');
    \Tracy\Debugger::barDump($email, 'Sponsor Email');

    $result = Database::insert('reservations', [
        'child_id' => $childId,
        'email' => $email,
        'reserved_at' => date('Y-m-d H:i:s'),
    ]);

    \Tracy\Debugger::barDump($result, 'Reservation Created');

    return $result;
}
```

## Production Safety

Tracy is **safe for production**:
- In production mode, shows generic error page to users
- Emails detailed errors to admin
- Logs everything to files
- No sensitive data exposed to visitors

## Configuration Options

```php
<?php
use Tracy\Debugger;

// Development settings
Debugger::$maxDepth = 4;           // How deep to dump arrays
Debugger::$maxLength = 150;        // Max string length in dumps
Debugger::$showLocation = true;    // Show file/line in dumps
Debugger::$strictMode = true;      // Treat warnings as errors

// Production settings
Debugger::$productionMode = true;  // Hide details from visitors
Debugger::$logSeverity = E_WARNING; // What to log
```

## Integration with Monolog (Optional)

Tracy can work alongside Monolog:

```php
<?php
// Use Tracy for development debugging
if (config('app_debug')) {
    \Tracy\Debugger::log($message);
}

// Use Monolog for production logging
$logger->info($message);
```

## Best Practices

### DO:
- ✅ Enable Tracy in development
- ✅ Use `bdump()` for quick debugging
- ✅ Configure email notifications for production
- ✅ Review Tracy logs regularly
- ✅ Remove debug dumps before committing

### DON'T:
- ❌ Commit code with `dump()` calls
- ❌ Disable Tracy in production entirely (you lose error logging)
- ❌ Set `$strictMode = true` during migration (too noisy)
- ❌ Forget to set email for production errors

## Keyboard Shortcuts

When Tracy error page is visible:
- **Alt + Left/Right**: Navigate through stack trace
- **Alt + Double-click**: Expand/collapse section
- **Escape**: Close Tracy bar

## Comparison to Other Tools

| Feature | Tracy | Xdebug | var_dump() |
|---------|-------|--------|------------|
| Setup | Easy | Complex | Built-in |
| Performance | Fast | Slow | Fast |
| Features | Rich | Very Rich | Basic |
| Production | Safe | No | No |
| Visual | Beautiful | IDE-dependent | Ugly |

## Migration Strategy

During your v1.7 Composer migration:

1. **Phase 1 - Setup**: Enable Tracy in development
2. **Phase 2 - Replace var_dump**: Change all `var_dump()` to `dump()`
3. **Phase 3 - Add logging**: Use Tracy to log refactoring issues
4. **Phase 4 - Production**: Enable production mode with email alerts

## Example: Debug Year-End Reset

```php
<?php
// admin/year_end_reset.php

use Tracy\Debugger;

Debugger::barDump($currentStats, 'Current Stats');

try {
    $result = CFK_Archive_Manager::performYearEndReset($year);
    Debugger::barDump($result, 'Reset Result');

    if ($result['success']) {
        setMessage('Reset completed successfully', 'success');
    }
} catch (\Exception $e) {
    Debugger::log($e); // Logs full exception details
    setMessage('Reset failed: ' . $e->getMessage(), 'error');
}
```

## Resources

- [Tracy Documentation](https://tracy.nette.org/)
- [Tracy on GitHub](https://github.com/nette/tracy)
- [Tracy Examples](https://tracy.nette.org/en/guide)

## Next Steps

1. Add Tracy configuration to `config/tracy.php`
2. Enable in `index.php` for development
3. Start using `bdump()` for debugging
4. Remove `var_dump()` calls
5. Configure production email alerts

---

**Tracy is now installed and ready to use!**

Start debugging with:
```php
bdump($variable, 'Debug Label');
```
