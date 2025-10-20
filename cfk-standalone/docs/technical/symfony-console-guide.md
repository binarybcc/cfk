# Symfony Console Guide

Symfony Console is a powerful framework for creating CLI commands. It's used by Laravel's Artisan, Symfony's console, and many other PHP projects.

## Installation

✅ Already installed: `symfony/console: ^7.0`

## Console Application Setup

The console application is located at `bin/console` and is already executable.

### Usage

```bash
# List all available commands
php bin/console list

# Get help for a command
php bin/console help cfk:cleanup:reservations

# Run a command
php bin/console cfk:cleanup:reservations

# With options
php bin/console cfk:cleanup:reservations --dry-run
php bin/console cfk:cleanup:reservations --timeout=180
```

## Creating Commands

### Basic Command Structure

All commands should be in `src/Command/` directory with namespace `CFK\Command`.

Example: `src/Command/ExampleCommand.php`

```php
<?php

declare(strict_types=1);

namespace CFK\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'cfk:example',
    description: 'Example command description',
)]
class ExampleCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Your name')
            ->addOption('uppercase', 'u', InputOption::VALUE_NONE, 'Uppercase the output')
            ->setHelp('This command shows an example...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');
        $uppercase = $input->getOption('uppercase');

        $message = "Hello, {$name}!";
        if ($uppercase) {
            $message = strtoupper($message);
        }

        $io->success($message);

        return Command::SUCCESS;
    }
}
```

### Register Command

Add to `bin/console`:

```php
<?php
// bin/console

$application->add(new CFK\Command\ExampleCommand());
```

Or use auto-discovery with Composer autoloader (recommended after migration).

## Migrating Cron Jobs to Commands

### Before: cron/cleanup_reservations.php

```php
<?php
// Simple PHP script
require_once __DIR__ . '/../config/config.php';

// Find expired reservations
$expirationTime = date('Y-m-d H:i:s', strtotime('-120 minutes'));
$expired = Database::fetchAll("SELECT ...");

foreach ($expired as $reservation) {
    // Delete reservation
    Database::execute("DELETE FROM reservations WHERE id = ?", [$reservation['id']]);
}

echo "Cleaned up " . count($expired) . " reservations\n";
```

### After: src/Command/CleanupReservationsCommand.php

See `src/Command/CleanupReservationsCommand.php` for full example with:
- Argument parsing (`--dry-run`, `--timeout`)
- Pretty output with SymfonyStyle
- Error handling
- Help documentation
- Return codes

## Command Features

### 1. Arguments and Options

```php
protected function configure(): void
{
    $this
        // Required argument
        ->addArgument('year', InputArgument::REQUIRED, 'Archive year')

        // Optional argument
        ->addArgument('name', InputArgument::OPTIONAL, 'Name')

        // Option with value
        ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format', 'csv')

        // Option without value (flag)
        ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview only');
}
```

### 2. User Interaction

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    // Ask question
    $email = $io->ask('What is your email?');

    // Ask with validation
    $year = $io->ask('Archive year?', null, function ($value) {
        if (!is_numeric($value)) {
            throw new \RuntimeException('Year must be numeric');
        }
        return (int) $value;
    });

    // Confirm
    if (!$io->confirm('Are you sure?', false)) {
        $io->warning('Operation cancelled');
        return Command::FAILURE;
    }

    // Choice
    $format = $io->choice('Select format', ['csv', 'json', 'xml'], 'csv');

    return Command::SUCCESS;
}
```

### 3. Output Formatting

```php
$io = new SymfonyStyle($input, $output);

// Messages
$io->title('Year-End Archive');
$io->section('Processing Data');
$io->text('Regular text');
$io->comment('A comment');
$io->success('Operation completed!');
$io->warning('This is a warning');
$io->error('An error occurred');
$io->note('FYI: something to note');

// Lists
$io->listing(['Item 1', 'Item 2', 'Item 3']);

// Tables
$io->table(
    ['Child ID', 'Name', 'Status'],
    [
        [1, 'John', 'Available'],
        [2, 'Jane', 'Sponsored'],
    ]
);

// Progress bar
$progressBar = $io->createProgressBar(100);
$progressBar->start();
for ($i = 0; $i < 100; $i++) {
    $progressBar->advance();
    usleep(10000);
}
$progressBar->finish();
```

### 4. Error Handling

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    try {
        // Your code

        return Command::SUCCESS;
    } catch (\Exception $e) {
        $io->error('Operation failed: ' . $e->getMessage());

        if ($output->isVerbose()) {
            $io->text($e->getTraceAsString());
        }

        return Command::FAILURE;
    }
}
```

## Planned Commands for CFK

### 1. Cleanup Commands

```bash
php bin/console cfk:cleanup:reservations       # Release expired reservations
php bin/console cfk:cleanup:magic-links        # Remove expired magic links
php bin/console cfk:cleanup:portal-tokens      # Remove expired portal tokens
php bin/console cfk:cleanup:remember-tokens    # Remove expired remember tokens
```

### 2. Archive Commands

```bash
php bin/console cfk:archive:year-end --year=2024   # Year-end reset
php bin/console cfk:archive:export --year=2024     # Export archive to CSV
php bin/console cfk:archive:list                   # List available archives
```

### 3. Data Management

```bash
php bin/console cfk:import:children data.csv       # Import children from CSV
php bin/console cfk:export:children --status=all   # Export children to CSV
php bin/console cfk:validate:data                  # Validate database integrity
```

### 4. Email Commands

```bash
php bin/console cfk:email:test user@example.com    # Test email configuration
php bin/console cfk:email:queue:process            # Process email queue
php bin/console cfk:email:queue:clear              # Clear email queue
```

### 5. Maintenance Commands

```bash
php bin/console cfk:maintenance:enable             # Enable maintenance mode
php bin/console cfk:maintenance:disable            # Disable maintenance mode
php bin/console cfk:cache:clear                    # Clear application cache
php bin/console cfk:stats                          # Show system statistics
```

## Cron Job Integration

### Old Way

```cron
# Crontab
0 * * * * cd /path/to/cfk && php cron/cleanup_reservations.php >> /dev/null 2>&1
```

### New Way

```cron
# Crontab
0 * * * * cd /path/to/cfk && php bin/console cfk:cleanup:reservations >> /var/log/cfk-cleanup.log 2>&1
0 2 * * * cd /path/to/cfk && php bin/console cfk:cleanup:magic-links >> /var/log/cfk-cleanup.log 2>&1
```

### Benefits of New Way

- ✅ Better logging (structured output)
- ✅ Error handling and reporting
- ✅ Dry-run mode for testing
- ✅ Consistent interface
- ✅ Easy to test manually

## Testing Commands

### Unit Testing

```php
<?php
namespace CFK\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use CFK\Command\CleanupReservationsCommand;

class CleanupReservationsCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $command = new CleanupReservationsCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--dry-run' => true]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('DRY RUN', $tester->getDisplay());
    }
}
```

### Manual Testing

```bash
# Test with dry-run first
php bin/console cfk:cleanup:reservations --dry-run

# Test with verbose output
php bin/console cfk:cleanup:reservations -vvv

# Test with different timeout
php bin/console cfk:cleanup:reservations --timeout=60 --dry-run
```

## Migration Checklist

### Phase 1: Create Commands (v1.7)
- [x] Create `bin/console` application
- [x] Create example command (CleanupReservationsCommand)
- [ ] Create remaining cleanup commands
- [ ] Create archive commands
- [ ] Test all commands

### Phase 2: Update Cron Jobs (v1.7)
- [ ] Update crontab to use new commands
- [ ] Monitor logs for errors
- [ ] Remove old cron PHP scripts

### Phase 3: Add Advanced Features (v1.8)
- [ ] Email notification commands
- [ ] Data validation commands
- [ ] Maintenance mode commands
- [ ] Statistics and reporting

## Best Practices

### DO:
- ✅ Use SymfonyStyle for consistent output
- ✅ Provide `--dry-run` option for destructive operations
- ✅ Add help text for all commands
- ✅ Return proper exit codes (SUCCESS/FAILURE)
- ✅ Validate input arguments
- ✅ Log important actions

### DON'T:
- ❌ Connect to database in `configure()` (too early)
- ❌ Echo directly (use $io methods)
- ❌ Exit with die() or exit() (return codes instead)
- ❌ Hardcode values (use arguments/options)
- ❌ Skip error handling

## Resources

- [Symfony Console Documentation](https://symfony.com/doc/current/components/console.html)
- [Console Commands Tutorial](https://symfony.com/doc/current/console.html)
- [Console Testing](https://symfony.com/doc/current/console.html#testing-commands)

## Next Steps

1. Register CleanupReservationsCommand in `bin/console`
2. Implement remaining cleanup commands
3. Implement archive commands
4. Update crontab
5. Remove old cron scripts

---

**Symfony Console is ready!** Start creating commands in `src/Command/`
