# Custom Validation Rules Migration - README

## Overview

This directory contains comprehensive documentation and tools for migrating custom validation rules from Acelle Mail to the Mailing module in Laravel 12 format.

---

## Quick Start

### For Automated Migration

```bash
cd /Users/functionbytes/Function/Coding/system/modules/Mailing/docs
chmod +x migrate-rules.sh
./migrate-rules.sh
```

### For Manual Migration

1. Read `VALIDATION_RULES_INDEX.md` (start here)
2. Follow `rules-migration-guide.md`
3. Use templates from `example-rules-templates.md`
4. Track progress in `RULES_MIGRATION_REPORT.md`

---

## Documentation Files

| File | Purpose | When to Use |
|------|---------|-------------|
| **VALIDATION_RULES_INDEX.md** | Main index and navigation | Start here - overview of all docs |
| **RULES_MIGRATION_REPORT.md** | Migration status tracking | Track progress, document issues |
| **rules-migration-guide.md** | Step-by-step migration guide | Migrating rules, solving issues |
| **example-rules-templates.md** | Ready-to-use rule templates | Need a working example to copy |
| **migrate-rules.sh** | Automated migration script | Bulk migrate all rules at once |

---

## What Are Custom Validation Rules?

Custom validation rules are reusable validation logic that extends Laravel's built-in validation system. In email marketing systems like Acelle, they validate:

- Email addresses and domains
- SMTP configurations
- Campaign content (unsubscribe links, merge tags)
- Subscriber data
- Sending limits and quotas
- DNS records (SPF, DKIM, MX)

---

## Migration Status

**Current Status:** ⚠️ Awaiting Manual Completion

**Reason:** Permission restrictions prevented automated discovery of source files.

**Action Required:**
1. Manually access `/Users/functionbytes/Function/Coding/acelle/app/Rules/`
2. Run migration script or migrate manually
3. Update `RULES_MIGRATION_REPORT.md` with findings

---

## Laravel 12 Changes

### Old Way (Laravel 8-10)

```php
use Illuminate\Contracts\Validation\Rule;

class OldRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        return $value === 'valid';
    }

    public function message(): string
    {
        return 'The :attribute is invalid.';
    }
}
```

### New Way (Laravel 12)

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

**Key Differences:**
- Interface changed from `Rule` to `ValidationRule`
- Single `validate()` method instead of `passes()` + `message()`
- Call `$fail()` closure instead of returning boolean
- Strict type hints required

---

## Recommended Workflow

### Phase 1: Setup (5 minutes)

```bash
# 1. Navigate to docs directory
cd /Users/functionbytes/Function/Coding/system/modules/Mailing/docs

# 2. Make migration script executable
chmod +x migrate-rules.sh

# 3. Review the index
cat VALIDATION_RULES_INDEX.md
```

### Phase 2: Discovery (10 minutes)

```bash
# 4. Check what rules exist in Acelle
ls -la /Users/functionbytes/Function/Coding/acelle/app/Rules/

# 5. Count the rules
find /Users/functionbytes/Function/Coding/acelle/app/Rules/ -name "*.php" | wc -l
```

### Phase 3: Migration (Variable time)

**Option A: Automated (Recommended)**
```bash
# Run the migration script
./migrate-rules.sh

# Review output and update each rule manually
```

**Option B: Manual (More control)**
```bash
# For each rule:
# 1. Copy file
cp /path/to/acelle/app/Rules/ExampleRule.php \
   /Users/functionbytes/Function/Coding/system/modules/Mailing/app/Rules/

# 2. Update namespace and syntax (see guide)

# 3. Create test

# 4. Run test
php artisan test --filter=ExampleRuleTest
```

### Phase 4: Testing (30 minutes)

```bash
# Run all rules tests
php artisan test tests/Feature/Mailing/Rules

# Format code
vendor/bin/pint modules/Mailing/app/Rules/

# Run full test suite
php artisan test
```

### Phase 5: Integration (20 minutes)

```bash
# Find Form Requests using old rules
grep -r "App\\\\Rules" modules/Mailing/app/Http/Requests/

# Update to new namespace
# From: use App\Rules\ExampleRule;
# To:   use Modules\Mailing\Rules\ExampleRule;

# Test Form Requests
php artisan test tests/Feature/Mailing/Http/Requests/
```

---

## Common Rules to Expect

Based on typical Acelle installations:

### High Priority (Critical for Sending)
- `SendingDomainRule` - Validates DNS for email sending
- `UnsubscribeLinkRule` - Ensures compliance with unsubscribe
- `BounceEmailRule` - Validates bounce handling
- `SmtpConnectionRule` - Tests SMTP connectivity

### Medium Priority (Important but not blocking)
- `CampaignFromEmailRule` - Validates campaign sender
- `EmailTemplateTagRule` - Validates merge tags
- `SubscriberEmailRule` - Validates subscriber uniqueness
- `TrackingDomainRule` - Validates tracking setup

### Low Priority (Optional features)
- `CsvImportRule` - CSV validation
- `SendingLimitRule` - Quota enforcement
- Various field validation rules

---

## Example: Complete Migration of One Rule

### 1. Source File (Acelle)

`/acelle/app/Rules/SendingDomainRule.php`

```php
<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SendingDomainRule implements Rule
{
    public function passes($attribute, $value)
    {
        $records = dns_get_record($value, DNS_MX);
        return !empty($records);
    }

    public function message()
    {
        return 'The :attribute must have valid MX records.';
    }
}
```

### 2. Migrated File (Laravel 12)

`modules/Mailing/app/Rules/SendingDomainRule.php`

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a domain has proper DNS configuration.
 */
class SendingDomainRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected bool $requireSpf = false,
        protected bool $requireDkim = false,
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $records = @dns_get_record($value, DNS_MX);

        if (empty($records)) {
            $fail("The {$attribute} must have valid MX records.");
        }
    }
}
```

### 3. Test File

`tests/Feature/Mailing/Rules/SendingDomainRuleTest.php`

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
            ['domain' => 'invalid-domain-xyz.com'],
            ['domain' => [new SendingDomainRule()]]
        );

        $this->assertTrue($validator->fails());
    }
}
```

### 4. Run Test

```bash
php artisan test --filter=SendingDomainRuleTest
```

### 5. Update Form Request

```php
use Modules\Mailing\Rules\SendingDomainRule;

public function rules(): array
{
    return [
        'domain' => ['required', new SendingDomainRule()],
    ];
}
```

---

## File Locations

```
Source (Acelle):
/Users/functionbytes/Function/Coding/acelle/app/Rules/

Destination (Mailing Module):
/Users/functionbytes/Function/Coding/system/modules/Mailing/app/Rules/

Tests:
/Users/functionbytes/Function/Coding/system/modules/Mailing/tests/Feature/Rules/

Documentation:
/Users/functionbytes/Function/Coding/system/modules/Mailing/docs/
```

---

## Success Criteria

Migration is complete when:

- [ ] All rules from Acelle are identified
- [ ] All rules are copied to `modules/Mailing/app/Rules/`
- [ ] All rules implement `ValidationRule` interface
- [ ] All rules use `validate()` method
- [ ] All Acelle references are updated
- [ ] All rules have corresponding test files
- [ ] All tests pass
- [ ] All Form Requests are updated
- [ ] Code is formatted with Pint
- [ ] `RULES_MIGRATION_REPORT.md` is complete

---

## Troubleshooting

### Cannot Access Acelle Directory

**Issue:** Permission denied or directory not found

**Solution:**
1. Verify Acelle installation path
2. Check file permissions
3. Update path in migration script if needed

### Rules Not Auto-Loading

**Issue:** Class not found errors

**Solution:**
```bash
composer dump-autoload
```

### Tests Failing

**Issue:** Validation not working as expected

**Solution:**
1. Check rule logic is correct
2. Verify test data is appropriate
3. Review error messages from validator
4. Check for missing dependencies

### Old Syntax Errors

**Issue:** Method not found or interface errors

**Solution:**
1. Ensure using `ValidationRule` interface
2. Implement `validate()` method only
3. Remove `passes()` and `message()` methods
4. Add proper type hints

---

## Getting Help

1. **Read the docs:**
   - Start with `VALIDATION_RULES_INDEX.md`
   - Consult `rules-migration-guide.md` for how-to
   - Use `example-rules-templates.md` for examples

2. **Check examples:**
   - Look at `example-rules-templates.md` for 10+ complete examples
   - Review Laravel 12 documentation

3. **Test incrementally:**
   - Migrate one rule at a time
   - Test immediately after migration
   - Don't move to next until current works

---

## Tips for Success

1. **Start small** - Migrate 1-2 rules first to get comfortable
2. **Test early** - Write tests before considering rule complete
3. **Use templates** - Don't reinvent the wheel
4. **Follow conventions** - Match existing code style
5. **Document issues** - Update tracking report as you go
6. **Ask for help** - If stuck, consult the guides

---

## Additional Resources

### Laravel Documentation
- [Validation](https://laravel.com/docs/12.x/validation)
- [Custom Rules](https://laravel.com/docs/12.x/validation#custom-validation-rules)
- [Form Requests](https://laravel.com/docs/12.x/validation#form-request-validation)

### Tools
- **Laravel Pint** - Code formatter (`vendor/bin/pint`)
- **PHPUnit** - Test runner (`php artisan test`)
- **Composer** - Autoloader (`composer dump-autoload`)

### Internal Docs
- Mailing module README
- API documentation
- Form Request documentation

---

## Contact

For questions or issues related to this migration:

1. Check existing documentation first
2. Review example templates
3. Consult Laravel 12 validation documentation
4. Update `RULES_MIGRATION_REPORT.md` with findings

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-01-29 | Initial migration documentation created |

---

**Ready to begin?** Start with `VALIDATION_RULES_INDEX.md` for a complete overview, or run `./migrate-rules.sh` to begin automated migration.
