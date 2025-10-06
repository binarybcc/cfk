# Christmas for Kids - Test Suite

Comprehensive PHPUnit test suite for the CFK Sponsorship System.

## Setup

### Install Dependencies

```bash
composer install --dev
```

This will install:
- PHPUnit 10.x
- PHPStan (static analysis)
- PHP_CodeSniffer (code style)

## Running Tests

### Run All Tests

```bash
vendor/bin/phpunit
```

### Run Specific Test Suites

```bash
# Unit tests only
vendor/bin/phpunit --testsuite=Unit

# Integration tests only
vendor/bin/phpunit --testsuite=Integration

# Functional tests only
vendor/bin/phpunit --testsuite=Functional
```

### Run Specific Test Files

```bash
vendor/bin/phpunit tests/Unit/ChildValidatorTest.php
vendor/bin/phpunit tests/Integration/SponsorshipFlowTest.php
```

### Run With Coverage

```bash
vendor/bin/phpunit --coverage-html coverage-report
```

Then open `coverage-report/index.html` in your browser.

## Test Structure

### Unit Tests (`tests/Unit/`)
Tests individual classes and methods in isolation:
- `ChildValidatorTest.php` - Validates child data, CSV imports
- `EagerLoadingTest.php` - Tests N+1 query optimization

### Integration Tests (`tests/Integration/`)
Tests components working together:
- `SponsorshipFlowTest.php` - Complete sponsorship workflow
- Email queue processing
- Database interactions

### Functional Tests (`tests/Functional/`)
End-to-end tests simulating user workflows:
- Child browsing and selection
- Family sponsorship flow
- Admin management tasks

## Test Coverage Goals

Target: **80% code coverage** on core classes

Priority areas:
1. ✅ Validator classes
2. ✅ Sponsorship flow
3. ✅ Eager loading functions
4. 🔄 Email queue logic (TODO)
5. 🔄 Session security (TODO)

## Writing Tests

### Test Naming Convention

```php
/** @test */
public function it_validates_email_format(): void
{
    // Arrange
    $email = 'test@example.com';

    // Act
    $isValid = validateEmail($email);

    // Assert
    $this->assertTrue($isValid);
}
```

### Use Data Providers for Multiple Test Cases

```php
/**
 * @test
 * @dataProvider emailProvider
 */
public function it_validates_various_email_formats(string $email, bool $expected): void
{
    $isValid = validateEmail($email);
    $this->assertEquals($expected, $isValid);
}

public function emailProvider(): array
{
    return [
        ['test@example.com', true],
        ['invalid', false],
        ['user@domain.co.uk', true],
    ];
}
```

## Code Quality Tools

### Static Analysis (PHPStan)

```bash
vendor/bin/phpstan analyse src includes
```

### Code Style (PHP_CodeSniffer)

```bash
# Check code style
vendor/bin/phpcs src includes

# Fix code style automatically
vendor/bin/phpcbf src includes
```

## Continuous Integration

Tests should be run:
- Before every commit
- In CI/CD pipeline before deployment
- After any database schema changes
- When adding new features

## Test Database

Tests use a separate test database (`cfk_test`) to avoid affecting production data.

Configure in `phpunit.xml`:
```xml
<php>
    <env name="DB_NAME" value="cfk_test"/>
</php>
```

## Troubleshooting

### PHPUnit Not Found
```bash
composer install --dev
```

### Memory Limit Errors
```bash
php -d memory_limit=512M vendor/bin/phpunit
```

### Database Connection Errors
Ensure test database exists:
```bash
mysql -u root -p -e "CREATE DATABASE cfk_test;"
```

## Contributing

When adding new features:
1. Write tests FIRST (TDD)
2. Aim for 80%+ coverage on new code
3. Run full test suite before committing
4. Update this README if adding new test categories
