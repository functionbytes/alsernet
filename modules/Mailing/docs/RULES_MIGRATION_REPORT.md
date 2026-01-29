# Custom Validation Rules Migration Report

**Date:** 2026-01-29
**Source:** `/Users/functionbytes/Function/Coding/acelle/app/Rules/`
**Destination:** `modules/Mailing/app/Rules/`
**Status:** ⚠️ REQUIRES MANUAL COMPLETION

---

## 📚 Documentation Quick Links

**Start Here:** [README_RULES_MIGRATION.md](./README_RULES_MIGRATION.md) - Main entry point

**Complete Index:** [VALIDATION_RULES_INDEX.md](./VALIDATION_RULES_INDEX.md) - Navigation hub

**How-To Guide:** [rules-migration-guide.md](./rules-migration-guide.md) - Step-by-step instructions

**Templates:** [example-rules-templates.md](./example-rules-templates.md) - 10+ ready-to-use examples

**Automation:** [migrate-rules.sh](./migrate-rules.sh) - Automated migration script

---

## Migration Status

### Access Issues Encountered

Due to permission restrictions during automated migration, this report serves as a **migration guide and template** for completing the Rules migration manually.

**Action Required:**
1. Navigate to: `/Users/functionbytes/Function/Coding/acelle/app/Rules/`
2. Identify all custom validation rule files
3. Use the migration templates below for each rule
4. Update this report with actual findings

### Quick Start Commands

```bash
# Navigate to docs directory
cd /Users/functionbytes/Function/Coding/system/modules/Mailing/docs

# Make script executable
chmod +x migrate-rules.sh

# Run automated migration
./migrate-rules.sh

# OR manually discover rules
ls -la /Users/functionbytes/Function/Coding/acelle/app/Rules/
```

---

## Expected Custom Validation Rules (Common in Email Marketing Systems)

Based on typical Acelle Mail implementations, the following custom validation rules are commonly found:

### 1. Email-Related Rules

| Rule Name | Purpose | Priority |
|-----------|---------|----------|
| `EmailAddressRule` | Validates email format and deliverability | High |
| `BounceEmailRule` | Validates bounce handler email configuration | High |
| `SendingDomainRule` | Validates sending domain configuration | High |
| `DkimRecordRule` | Validates DKIM DNS records | Medium |
| `SpfRecordRule` | Validates SPF DNS records | Medium |

### 2. Campaign-Related Rules

| Rule Name | Purpose | Priority |
|-----------|---------|----------|
| `CampaignFromEmailRule` | Validates from email in campaign context | High |
| `ReplyToEmailRule` | Validates reply-to email format | Medium |
| `TrackingDomainRule` | Validates tracking domain configuration | Medium |
| `UnsubscribeLinkRule` | Ensures unsubscribe link presence | Critical |

### 3. List/Subscriber Rules

| Rule Name | Purpose | Priority |
|-----------|---------|----------|
| `SubscriberEmailRule` | Validates subscriber email uniqueness | High |
| `CsvImportRule` | Validates CSV file structure for import | High |
| `FieldMappingRule` | Validates custom field mappings | Medium |

### 4. SMTP/Sending Rules

| Rule Name | Purpose | Priority |
|-----------|---------|----------|
| `SmtpConnectionRule` | Validates SMTP server connectivity | High |
| `SendingLimitRule` | Validates sending quota/limits | High |
| `SendingServerRule` | Validates sending server configuration | High |

### 5. Template/Content Rules

| Rule Name | Purpose | Priority |
|-----------|---------|----------|
| `TemplateTagRule` | Validates email template merge tags | Medium |
| `HtmlContentRule` | Validates HTML email content | Medium |
| `PlainTextRule` | Validates plain text alternatives | Low |

---

## Migration Template for Each Rule

### Laravel 12 Validation Rule Structure

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExampleRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?string $parameter = null,
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validation logic here
        if ($this->shouldFail($value)) {
            $fail("The {$attribute} is invalid.");
        }
    }

    /**
     * Validation logic.
     */
    protected function shouldFail(mixed $value): bool
    {
        // Implementation
        return false;
    }
}
```

---

## Step-by-Step Migration Process

### Step 1: Identify All Rules

```bash
# Run this command to list all rules in Acelle
find /Users/functionbytes/Function/Coding/acelle/app/Rules -name "*.php" -type f
```

### Step 2: Create Destination Directory

```bash
mkdir -p /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Rules
```

### Step 3: For Each Rule File

1. **Copy the file:**
   ```bash
   cp /Users/functionbytes/Function/Coding/acelle/app/Rules/ExampleRule.php \
      /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Rules/
   ```

2. **Update namespace:**
   ```php
   // Old
   namespace App\Rules;

   // New
   namespace Modules\Mailing\Rules;
   ```

3. **Update to Laravel 12 syntax:**
   - Implement `ValidationRule` interface (not `Rule`)
   - Use `validate(string $attribute, mixed $value, Closure $fail): void` method
   - Remove old `passes()` and `message()` methods if present

4. **Update imports:**
   ```php
   use Closure;
   use Illuminate\Contracts\Validation\ValidationRule;
   ```

5. **Update any Acelle-specific dependencies:**
   - Change `\Acelle\Model\*` to `Modules\Mailing\Models\*`
   - Update service references
   - Update helper function calls

### Step 4: Test Each Rule

Create a test file for each rule:

```bash
php artisan make:test --phpunit Mailing/Rules/ExampleRuleTest
```

Test template:

```php
<?php

namespace Tests\Feature\Mailing\Rules;

use Modules\Mailing\Rules\ExampleRule;
use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class ExampleRuleTest extends TestCase
{
    public function testValidData(): void
    {
        $validator = Validator::make(
            ['email' => 'valid@example.com'],
            ['email' => [new ExampleRule()]]
        );

        $this->assertFalse($validator->fails());
    }

    public function testInvalidData(): void
    {
        $validator = Validator::make(
            ['email' => 'invalid'],
            ['email' => [new ExampleRule()]]
        );

        $this->assertTrue($validator->fails());
    }
}
```

---

## Common Migration Patterns

### Pattern 1: Old Laravel Rule (Pre-v11)

**Before (Laravel 8/9):**
```php
<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class OldRule implements Rule
{
    public function passes($attribute, $value)
    {
        return $value === 'valid';
    }

    public function message()
    {
        return 'The :attribute is invalid.';
    }
}
```

**After (Laravel 12):**
```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OldRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== 'valid') {
            $fail("The {$attribute} is invalid.");
        }
    }
}
```

### Pattern 2: Rule with Constructor Parameters

**Before:**
```php
class EmailDomainRule implements Rule
{
    protected $allowedDomains;

    public function __construct($allowedDomains)
    {
        $this->allowedDomains = $allowedDomains;
    }

    public function passes($attribute, $value)
    {
        $domain = substr(strrchr($value, "@"), 1);
        return in_array($domain, $this->allowedDomains);
    }
}
```

**After:**
```php
class EmailDomainRule implements ValidationRule
{
    public function __construct(
        protected array $allowedDomains,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = substr(strrchr($value, "@"), 1);

        if (!in_array($domain, $this->allowedDomains)) {
            $fail("The {$attribute} must be from an allowed domain.");
        }
    }
}
```

### Pattern 3: Rule with Database Queries

**Before:**
```php
class UniqueSubscriberRule implements Rule
{
    public function passes($attribute, $value)
    {
        return !\Acelle\Model\Subscriber::where('email', $value)->exists();
    }
}
```

**After:**
```php
class UniqueSubscriberRule implements ValidationRule
{
    public function __construct(
        protected ?int $exceptId = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = \Modules\Mailing\Models\Subscriber::where('email', $value);

        if ($this->exceptId) {
            $query->where('id', '!=', $this->exceptId);
        }

        if ($query->exists()) {
            $fail("The {$attribute} has already been taken.");
        }
    }
}
```

---

## Translation Keys

If rules use translation strings, create/update:

**File:** `modules/Mailing/resources/lang/en/validation.php`

```php
return [
    'custom' => [
        'email' => [
            'bounce_email' => 'The bounce email format is invalid.',
            'sending_domain' => 'The sending domain is not properly configured.',
        ],
        'campaign' => [
            'unsubscribe_link' => 'The campaign must contain an unsubscribe link.',
        ],
    ],
];
```

---

## Checklist

Use this checklist for each migrated rule:

- [ ] File copied to `modules/Mailing/app/Rules/`
- [ ] Namespace updated to `Modules\Mailing\Rules`
- [ ] Implements `ValidationRule` interface
- [ ] Uses `validate()` method with correct signature
- [ ] Constructor uses property promotion (if applicable)
- [ ] All Acelle model references updated to Mailing models
- [ ] Error messages are clear and translatable
- [ ] Test file created
- [ ] Test passes
- [ ] Documented in this report

---

## Manual Completion Required

### Instructions for Developer

1. **Access the source directory:**
   ```bash
   cd /Users/functionbytes/Function/Coding/acelle/app/Rules
   ls -la
   ```

2. **For each `.php` file found, add an entry below:**

---

## Migrated Rules Inventory

### ✅ Completed Migrations

| # | Rule Name | Source File | Destination | Tests | Status | Notes |
|---|-----------|-------------|-------------|-------|--------|-------|
| 1 | _Pending_ | - | - | - | ⏳ | Awaiting manual discovery |

### ⏳ Pending Migrations

| # | Rule Name | Source File | Reason |
|---|-----------|-------------|--------|
| - | All rules | /acelle/app/Rules/* | Permission restrictions prevented automated discovery |

---

## Next Steps

1. **Manual Discovery Phase:**
   - Manually access `/Users/functionbytes/Function/Coding/acelle/app/Rules/`
   - List all PHP files
   - Update the "Migrated Rules Inventory" section above

2. **Migration Phase:**
   - For each rule, follow the migration template
   - Create corresponding test files
   - Run tests to ensure functionality

3. **Integration Phase:**
   - Update Form Requests to use new rule namespaces
   - Update any direct rule instantiations in controllers
   - Run full test suite

4. **Cleanup Phase:**
   - Remove any deprecated code
   - Add missing PHPDoc blocks
   - Run `vendor/bin/pint` for code formatting

---

## Dependencies to Check

Rules may depend on:

- **Models:** Update to `Modules\Mailing\Models\*`
- **Services:** Update to `Modules\Mailing\Services\*`
- **Helpers:** Ensure helpers are available or create them
- **Config:** Update config references to `config('mailing.*')`
- **Database:** Ensure migrations have created necessary tables

---

## Form Requests Using Custom Rules

After migration, update these locations:

1. **Mailing Form Requests:** `modules/Mailing/app/Http/Requests/`
2. **Search Pattern:**
   ```bash
   grep -r "App\\\\Rules" modules/Mailing/app/Http/Requests/
   ```
3. **Replace:**
   ```php
   // Old
   use App\Rules\ExampleRule;

   // New
   use Modules\Mailing\Rules\ExampleRule;
   ```

---

## Testing Strategy

### Unit Tests

Test each rule in isolation:

```bash
php artisan test --filter=ExampleRuleTest
```

### Integration Tests

Test rules within Form Requests:

```bash
php artisan test tests/Feature/Mailing/Http/Requests/
```

### Full Suite

After all migrations:

```bash
php artisan test
```

---

## Performance Considerations

- **Database rules:** Consider caching for frequently used validations
- **External API calls:** Add timeouts and fallbacks
- **Complex regex:** Optimize patterns for performance

---

## Documentation References

- [Laravel 12 Validation Rules](https://laravel.com/docs/12.x/validation#custom-validation-rules)
- [Validation Rule Interface](https://laravel.com/api/12.x/Illuminate/Contracts/Validation/ValidationRule.html)
- [Form Request Validation](https://laravel.com/docs/12.x/validation#form-request-validation)

---

## Appendix A: Quick Reference Commands

```bash
# Create new validation rule
php artisan make:rule Mailing/CustomRule

# Create test for rule
php artisan make:test --phpunit Mailing/Rules/CustomRuleTest

# Run specific test
php artisan test --filter=CustomRuleTest

# Format code
vendor/bin/pint

# List all rules in destination
find modules/Mailing/app/Rules -name "*.php" -type f

# Search for rule usage
grep -r "CustomRule" modules/Mailing/
```

---

## Appendix B: Example Complete Migration

### Source: `/acelle/app/Rules/SendingDomainRule.php`

```php
<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SendingDomainRule implements Rule
{
    protected $sendingServer;

    public function __construct($sendingServer)
    {
        $this->sendingServer = $sendingServer;
    }

    public function passes($attribute, $value)
    {
        // Check if domain has valid DNS records
        $records = dns_get_record($value, DNS_MX);
        return !empty($records);
    }

    public function message()
    {
        return 'The :attribute must be a valid domain with MX records.';
    }
}
```

### Destination: `modules/Mailing/app/Rules/SendingDomainRule.php`

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Mailing\Models\SendingServer;

class SendingDomainRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?SendingServer $sendingServer = null,
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if domain has valid DNS records
        $records = @dns_get_record($value, DNS_MX);

        if (empty($records)) {
            $fail("The {$attribute} must be a valid domain with MX records.");
        }
    }
}
```

### Test: `tests/Feature/Mailing/Rules/SendingDomainRuleTest.php`

```php
<?php

namespace Tests\Feature\Mailing\Rules;

use Modules\Mailing\Rules\SendingDomainRule;
use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class SendingDomainRuleTest extends TestCase
{
    public function testValidDomainPasses(): void
    {
        $validator = Validator::make(
            ['domain' => 'gmail.com'],
            ['domain' => [new SendingDomainRule()]]
        );

        $this->assertFalse($validator->fails());
    }

    public function testInvalidDomainFails(): void
    {
        $validator = Validator::make(
            ['domain' => 'invalid-domain-that-does-not-exist-12345.com'],
            ['domain' => [new SendingDomainRule()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString(
            'must be a valid domain with MX records',
            $validator->errors()->first('domain')
        );
    }

    public function testEmptyValueFails(): void
    {
        $validator = Validator::make(
            ['domain' => ''],
            ['domain' => ['required', new SendingDomainRule()]]
        );

        $this->assertTrue($validator->fails());
    }
}
```

---

## Report Completion

**This report will be updated as rules are discovered and migrated.**

Last Updated: 2026-01-29
Completed By: _Awaiting manual completion_
Total Rules Migrated: 0 / Unknown
