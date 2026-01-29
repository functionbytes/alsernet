# Custom Validation Rules Migration Guide

## Quick Start

### Automated Migration (Recommended)

```bash
# Navigate to docs directory
cd /Users/functionbytes/Function/Coding/system/modules/Mailing/docs

# Make script executable
chmod +x migrate-rules.sh

# Run migration
./migrate-rules.sh
```

### Manual Migration

If automated migration fails or for individual rules:

```bash
# 1. Copy rule file
cp /Users/functionbytes/Function/Coding/acelle/app/Rules/ExampleRule.php \
   /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Rules/

# 2. Edit the file and update namespace
# 3. Update to Laravel 12 syntax
# 4. Create test file
# 5. Run tests
```

---

## Laravel 12 Validation Rule Syntax

### Interface Change

Laravel 12 uses the `ValidationRule` interface instead of the old `Rule` interface.

**Old (Laravel 8-10):**
```php
use Illuminate\Contracts\Validation\Rule;

class OldRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        return $value === 'valid';
    }

    public function message(): string|array
    {
        return 'The :attribute is invalid.';
    }
}
```

**New (Laravel 12):**
```php
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NewRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== 'valid') {
            $fail("The {$attribute} is invalid.");
        }
    }
}
```

### Key Differences

1. **Interface:** `ValidationRule` instead of `Rule`
2. **Method:** `validate()` instead of `passes()` and `message()`
3. **Return Type:** `void` instead of `bool`
4. **Failure Handling:** Call `$fail()` closure instead of returning false
5. **Typed Parameters:** Strict typing for `$attribute` and `$value`

---

## Common Migration Patterns

### Pattern 1: Simple Validation

**Before:**
```php
class SimpleRule implements Rule
{
    public function passes($attribute, $value)
    {
        return strlen($value) > 5;
    }

    public function message()
    {
        return 'The :attribute must be longer than 5 characters.';
    }
}
```

**After:**
```php
class SimpleRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) <= 5) {
            $fail("The {$attribute} must be longer than 5 characters.");
        }
    }
}
```

### Pattern 2: Constructor Parameters

**Before:**
```php
class ParameterizedRule implements Rule
{
    protected $minLength;

    public function __construct($minLength)
    {
        $this->minLength = $minLength;
    }

    public function passes($attribute, $value)
    {
        return strlen($value) >= $this->minLength;
    }
}
```

**After (Laravel 12 with Property Promotion):**
```php
class ParameterizedRule implements ValidationRule
{
    public function __construct(
        protected int $minLength,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) < $this->minLength) {
            $fail("The {$attribute} must be at least {$this->minLength} characters.");
        }
    }
}
```

### Pattern 3: Database Validation

**Before:**
```php
class DatabaseRule implements Rule
{
    public function passes($attribute, $value)
    {
        return \Acelle\Model\User::where('email', $value)->exists();
    }
}
```

**After:**
```php
class DatabaseRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!\Modules\Mailing\Models\User::where('email', $value)->exists()) {
            $fail("The {$attribute} does not exist in our records.");
        }
    }
}
```

### Pattern 4: Multiple Error Messages

**Before:**
```php
class MultipleErrorsRule implements Rule
{
    protected $errorMessage;

    public function passes($attribute, $value)
    {
        if (empty($value)) {
            $this->errorMessage = 'The :attribute cannot be empty.';
            return false;
        }

        if (strlen($value) < 5) {
            $this->errorMessage = 'The :attribute must be at least 5 characters.';
            return false;
        }

        return true;
    }

    public function message()
    {
        return $this->errorMessage;
    }
}
```

**After:**
```php
class MultipleErrorsRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            $fail("The {$attribute} cannot be empty.");
            return;
        }

        if (strlen($value) < 5) {
            $fail("The {$attribute} must be at least 5 characters.");
            return;
        }
    }
}
```

### Pattern 5: Dependency Injection

**Before:**
```php
class ServiceRule implements Rule
{
    protected $service;

    public function __construct(SomeService $service)
    {
        $this->service = $service;
    }

    public function passes($attribute, $value)
    {
        return $this->service->validate($value);
    }
}
```

**After:**
```php
class ServiceRule implements ValidationRule
{
    public function __construct(
        protected SomeService $service,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->service->validate($value)) {
            $fail("The {$attribute} failed service validation.");
        }
    }
}
```

---

## Email Marketing Specific Rules

### SendingDomainRule

Validates that a domain has proper DNS records for email sending.

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SendingDomainRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check MX records
        $mxRecords = @dns_get_record($value, DNS_MX);

        if (empty($mxRecords)) {
            $fail("The {$attribute} must have valid MX records.");
            return;
        }

        // Additional validation can be added here
        // - SPF records
        // - DKIM records
        // - Domain reputation
    }
}
```

### EmailBounceRule

Validates bounce email configuration.

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailBounceRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validate email format
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail("The {$attribute} must be a valid email address.");
            return;
        }

        // Check if it's not a generic address
        $genericPrefixes = ['noreply', 'no-reply', 'donotreply'];
        $localPart = explode('@', $value)[0];

        if (in_array(strtolower($localPart), $genericPrefixes)) {
            $fail("The {$attribute} should not be a generic no-reply address.");
            return;
        }
    }
}
```

### UnsubscribeLinkRule

Ensures campaign content includes unsubscribe link.

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UnsubscribeLinkRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $unsubscribePatterns = [
            '/\{unsubscribe_url\}/',
            '/\{UNSUBSCRIBE_URL\}/',
            '/<a[^>]*href=["\'].*unsubscribe.*["\'][^>]*>/',
        ];

        foreach ($unsubscribePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return; // Valid - contains unsubscribe link
            }
        }

        $fail("The {$attribute} must contain an unsubscribe link.");
    }
}
```

### SmtpConnectionRule

Validates SMTP server connectivity.

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SmtpConnectionRule implements ValidationRule
{
    public function __construct(
        protected string $host,
        protected int $port,
        protected ?string $username = null,
        protected ?string $password = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $timeout = 10;
        $errno = 0;
        $errstr = '';

        $connection = @fsockopen(
            $this->host,
            $this->port,
            $errno,
            $errstr,
            $timeout
        );

        if (!$connection) {
            $fail("Unable to connect to SMTP server: {$errstr} ({$errno})");
            return;
        }

        fclose($connection);
    }
}
```

---

## Testing Validation Rules

### Basic Test Structure

```php
<?php

namespace Tests\Feature\Mailing\Rules;

use Modules\Mailing\Rules\ExampleRule;
use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class ExampleRuleTest extends TestCase
{
    public function testPassesWithValidData(): void
    {
        $rule = new ExampleRule();

        $validator = Validator::make(
            ['field' => 'valid-value'],
            ['field' => [$rule]]
        );

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    public function testFailsWithInvalidData(): void
    {
        $rule = new ExampleRule();

        $validator = Validator::make(
            ['field' => 'invalid-value'],
            ['field' => [$rule]]
        );

        $this->assertTrue($validator->fails());
        $this->assertNotEmpty($validator->errors()->get('field'));
    }

    public function testErrorMessageIsCorrect(): void
    {
        $rule = new ExampleRule();

        $validator = Validator::make(
            ['field' => 'invalid-value'],
            ['field' => [$rule]]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString(
            'expected error message',
            $validator->errors()->first('field')
        );
    }
}
```

### Test with Constructor Parameters

```php
public function testRuleWithParameters(): void
{
    $rule = new ParameterizedRule(minLength: 10);

    $validator = Validator::make(
        ['field' => 'short'],
        ['field' => [$rule]]
    );

    $this->assertTrue($validator->fails());
}
```

### Test with Database

```php
public function testDatabaseValidation(): void
{
    // Arrange
    $user = User::factory()->create(['email' => 'test@example.com']);

    // Act
    $validator = Validator::make(
        ['email' => 'test@example.com'],
        ['email' => [new DatabaseRule()]]
    );

    // Assert
    $this->assertFalse($validator->fails());
}
```

### Running Tests

```bash
# Run all rule tests
php artisan test tests/Feature/Mailing/Rules

# Run specific test
php artisan test --filter=ExampleRuleTest

# Run with coverage
php artisan test --coverage

# Run single test method
php artisan test --filter=testPassesWithValidData
```

---

## Updating Form Requests

After migrating rules, update Form Requests to use new namespaces:

### Before

```php
<?php

namespace App\Http\Requests;

use App\Rules\SendingDomainRule;
use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'domain' => ['required', new SendingDomainRule()],
        ];
    }
}
```

### After

```php
<?php

namespace Modules\Mailing\Http\Requests;

use Modules\Mailing\Rules\SendingDomainRule;
use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'domain' => ['required', new SendingDomainRule()],
        ];
    }
}
```

### Finding All Form Requests Using Rules

```bash
# Search for old namespace usage
grep -r "App\\\\Rules" modules/Mailing/app/Http/Requests/

# Search for specific rule usage
grep -r "SendingDomainRule" modules/Mailing/

# List all Form Requests
find modules/Mailing/app/Http/Requests -name "*.php" -type f
```

---

## Common Issues and Solutions

### Issue 1: Namespace Not Found

**Error:**
```
Class 'Modules\Mailing\Rules\ExampleRule' not found
```

**Solution:**
- Verify file exists in `modules/Mailing/app/Rules/ExampleRule.php`
- Check namespace declaration matches directory structure
- Run `composer dump-autoload`

### Issue 2: Old Interface Implementation

**Error:**
```
Method passes() not found
```

**Solution:**
Update to Laravel 12 ValidationRule interface:

```php
// Remove old methods
// public function passes($attribute, $value) { }
// public function message() { }

// Add new method
public function validate(string $attribute, mixed $value, Closure $fail): void
{
    // Implementation
}
```

### Issue 3: Model References

**Error:**
```
Class 'Acelle\Model\User' not found
```

**Solution:**
Update all Acelle model references:

```bash
# Find all Acelle model references
grep -r "Acelle\\\\Model" modules/Mailing/app/Rules/

# Replace with Mailing models
# \Acelle\Model\User → \Modules\Mailing\Models\User
```

### Issue 4: Service Dependencies

**Error:**
```
Service not found in container
```

**Solution:**
Ensure services are registered in `MailingServiceProvider`:

```php
public function register(): void
{
    $this->app->singleton(SomeService::class, function ($app) {
        return new SomeService();
    });
}
```

---

## Checklist for Each Rule

- [ ] File copied to `modules/Mailing/app/Rules/`
- [ ] Namespace updated to `Modules\Mailing\Rules`
- [ ] Implements `ValidationRule` interface (not `Rule`)
- [ ] Uses `validate()` method with correct signature
- [ ] Old `passes()` and `message()` methods removed
- [ ] Constructor uses property promotion (PHP 8.4)
- [ ] All `\Acelle\Model\*` references updated
- [ ] Proper type hints added
- [ ] PHPDoc block added
- [ ] Test file created in `tests/Feature/Mailing/Rules/`
- [ ] All tests pass
- [ ] Code formatted with Pint: `vendor/bin/pint`
- [ ] Added to migration report

---

## Resources

- [Laravel 12 Validation Documentation](https://laravel.com/docs/12.x/validation)
- [Custom Validation Rules](https://laravel.com/docs/12.x/validation#custom-validation-rules)
- [Form Request Validation](https://laravel.com/docs/12.x/validation#form-request-validation)
- [PHPUnit Testing](https://phpunit.de/documentation.html)
- [Laravel Testing](https://laravel.com/docs/12.x/testing)

---

## Support

For issues or questions:

1. Check the migration report: `RULES_MIGRATION_REPORT.md`
2. Review this guide thoroughly
3. Consult Laravel 12 documentation
4. Check existing migrated rules for examples
