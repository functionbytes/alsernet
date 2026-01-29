# Validation Rules - Complete Documentation Index

## Quick Navigation

- [Migration Report](#migration-report) - Status and tracking
- [Migration Guide](#migration-guide) - How to migrate rules
- [Example Templates](#example-templates) - Ready-to-use rule examples
- [Migration Script](#migration-script) - Automated migration tool

---

## Migration Report

**File:** `RULES_MIGRATION_REPORT.md`

**Purpose:** Central tracking document for the entire validation rules migration project.

**Contents:**
- Migration status overview
- Expected custom validation rules inventory
- Step-by-step migration process
- Common migration patterns
- Checklist for each rule
- Next steps and dependencies

**When to use:**
- To track overall progress
- To document what has been migrated
- To identify what remains to be migrated
- To record issues and solutions

**Status:** ⚠️ Requires manual completion due to access restrictions

---

## Migration Guide

**File:** `rules-migration-guide.md`

**Purpose:** Comprehensive guide for migrating validation rules from Acelle to Laravel 12.

**Contents:**
- Quick start instructions
- Laravel 12 validation rule syntax
- Common migration patterns
- Email marketing specific rules
- Testing strategies
- Form request updates
- Troubleshooting common issues

**When to use:**
- When migrating a new rule
- When updating old Laravel syntax to Laravel 12
- When creating tests for rules
- When encountering migration issues

**Key Sections:**
1. **Interface Change** - From `Rule` to `ValidationRule`
2. **Pattern Library** - 5 common migration patterns
3. **Testing Guide** - How to test validation rules
4. **Troubleshooting** - Solutions to common issues

---

## Example Templates

**File:** `example-rules-templates.md`

**Purpose:** Production-ready validation rule templates for email marketing features.

**Contents:**
- 10+ complete validation rule implementations
- Usage examples in Form Requests and Controllers
- PHPUnit test examples
- Real-world email marketing scenarios

**Validation Rules Included:**

### Email Configuration Rules
1. **SendingDomainRule** - Validates DNS configuration (MX, SPF, DKIM)
2. **BounceEmailRule** - Validates bounce handler email
3. **CampaignFromEmailRule** - Validates campaign sender email

### Content Validation Rules
4. **UnsubscribeLinkRule** - Ensures unsubscribe link presence
5. **EmailTemplateTagRule** - Validates merge tags
6. **CsvImportRule** - Validates CSV file structure

### Infrastructure Rules
7. **SmtpConnectionRule** - Tests SMTP server connectivity
8. **TrackingDomainRule** - Validates tracking domain setup

### Business Logic Rules
9. **SubscriberEmailRule** - Validates subscriber uniqueness
10. **SendingLimitRule** - Validates sending quota compliance

**When to use:**
- As templates for new custom rules
- As reference implementations
- To understand best practices
- To copy-paste and customize

---

## Migration Script

**File:** `migrate-rules.sh`

**Purpose:** Automated bash script to migrate rules from Acelle to Mailing module.

**Features:**
- Automatic file copying
- Namespace updates
- Basic code transformations
- Test file generation
- Progress reporting

**Usage:**

```bash
# Make executable
chmod +x /Users/functionbytes/Function/Coding/system/modules/Mailing/docs/migrate-rules.sh

# Run migration
./migrate-rules.sh
```

**What it does:**
1. Checks if source directory exists
2. Creates destination directories
3. Counts and lists all rules to migrate
4. For each rule:
   - Copies file to destination
   - Updates namespace
   - Updates Acelle model references
   - Creates basic test file
5. Provides summary and next steps

**Limitations:**
- Does not update rule syntax (manual step required)
- Does not update Form Requests (manual step required)
- Basic pattern replacements only

**Post-Script Actions:**
1. Review migrated files
2. Update to Laravel 12 `ValidationRule` interface
3. Add proper type hints
4. Update test files with real test cases
5. Run `vendor/bin/pint` for formatting
6. Run tests to verify functionality

---

## Directory Structure

```
modules/Mailing/
├── app/
│   └── Rules/                          # Destination for migrated rules
│       ├── SendingDomainRule.php
│       ├── UnsubscribeLinkRule.php
│       ├── BounceEmailRule.php
│       └── ...
├── tests/
│   └── Feature/
│       └── Rules/                      # Tests for validation rules
│           ├── SendingDomainRuleTest.php
│           ├── UnsubscribeLinkRuleTest.php
│           └── ...
└── docs/
    ├── RULES_MIGRATION_REPORT.md       # Main tracking document
    ├── rules-migration-guide.md        # How-to guide
    ├── example-rules-templates.md      # Template library
    ├── VALIDATION_RULES_INDEX.md       # This file
    └── migrate-rules.sh                # Migration script
```

---

## Migration Workflow

### Phase 1: Discovery

1. **Run migration script** to identify all rules
   ```bash
   ./migrate-rules.sh
   ```

2. **Review RULES_MIGRATION_REPORT.md** for complete inventory

3. **Prioritize rules** based on:
   - Usage frequency
   - Criticality to email sending
   - Dependencies

### Phase 2: Migration

For each rule:

1. **Copy rule** (script does this automatically)

2. **Update to Laravel 12 syntax** using `rules-migration-guide.md`
   - Change interface to `ValidationRule`
   - Update `passes()` and `message()` to `validate()`
   - Add proper type hints

3. **Update dependencies**
   - Change `\Acelle\Model\*` to `\Modules\Mailing\Models\*`
   - Update service references
   - Update config references

4. **Create tests** using examples from `example-rules-templates.md`

5. **Run tests**
   ```bash
   php artisan test --filter=RuleNameTest
   ```

6. **Format code**
   ```bash
   vendor/bin/pint modules/Mailing/app/Rules/RuleName.php
   ```

7. **Update tracking** in RULES_MIGRATION_REPORT.md

### Phase 3: Integration

1. **Find Form Requests** using rules
   ```bash
   grep -r "RuleName" modules/Mailing/app/Http/Requests/
   ```

2. **Update namespaces** in Form Requests
   ```php
   // Old
   use App\Rules\RuleName;

   // New
   use Modules\Mailing\Rules\RuleName;
   ```

3. **Test Form Requests**
   ```bash
   php artisan test tests/Feature/Mailing/Http/Requests/
   ```

4. **Update controllers** if rules used directly

### Phase 4: Verification

1. **Run full test suite**
   ```bash
   php artisan test
   ```

2. **Check code quality**
   ```bash
   vendor/bin/pint --test
   ```

3. **Review documentation** completeness

4. **Final update** to RULES_MIGRATION_REPORT.md

---

## Laravel 12 Validation Rule Quick Reference

### Old Interface (Laravel 8-10)

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

### New Interface (Laravel 12)

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

### Key Changes

| Aspect | Old | New |
|--------|-----|-----|
| Interface | `Rule` | `ValidationRule` |
| Method | `passes()` + `message()` | `validate()` |
| Return Type | `bool` | `void` |
| Failure | Return `false` | Call `$fail()` |
| Success | Return `true` | Do nothing |
| Error Message | Return from `message()` | Pass to `$fail()` |

---

## Common Validation Scenarios

### 1. Email Domain Validation

```php
use Modules\Mailing\Rules\SendingDomainRule;

$request->validate([
    'domain' => ['required', new SendingDomainRule(requireSpf: true)],
]);
```

### 2. SMTP Configuration

```php
use Modules\Mailing\Rules\SmtpConnectionRule;

$request->validate([
    'smtp_port' => [
        'required',
        new SmtpConnectionRule(
            host: $request->smtp_host,
            port: $request->smtp_port
        ),
    ],
]);
```

### 3. Campaign Content

```php
use Modules\Mailing\Rules\{UnsubscribeLinkRule, EmailTemplateTagRule};

$request->validate([
    'html_content' => [
        'required',
        new UnsubscribeLinkRule(),
        new EmailTemplateTagRule(),
    ],
]);
```

### 4. Subscriber Import

```php
use Modules\Mailing\Rules\CsvImportRule;

$request->validate([
    'csv_file' => [
        'required',
        'file',
        new CsvImportRule(
            customRequiredColumns: ['email', 'first_name'],
            maxRows: 5000
        ),
    ],
]);
```

### 5. Sending Limits

```php
use Modules\Mailing\Rules\SendingLimitRule;

$request->validate([
    'recipient_count' => [
        'required',
        'integer',
        new SendingLimitRule(
            customer: $customer,
            period: 'hour'
        ),
    ],
]);
```

---

## Testing Quick Reference

### Basic Test Structure

```php
use Modules\Mailing\Rules\RuleName;
use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class RuleNameTest extends TestCase
{
    public function testValidDataPasses(): void
    {
        $validator = Validator::make(
            ['field' => 'valid-value'],
            ['field' => [new RuleName()]]
        );

        $this->assertFalse($validator->fails());
    }

    public function testInvalidDataFails(): void
    {
        $validator = Validator::make(
            ['field' => 'invalid-value'],
            ['field' => [new RuleName()]]
        );

        $this->assertTrue($validator->fails());
    }
}
```

### Running Tests

```bash
# All rules tests
php artisan test tests/Feature/Mailing/Rules

# Specific test
php artisan test --filter=SendingDomainRuleTest

# With coverage
php artisan test --coverage
```

---

## Troubleshooting

### Issue: Class Not Found

**Symptom:**
```
Class 'Modules\Mailing\Rules\RuleName' not found
```

**Solution:**
```bash
composer dump-autoload
```

### Issue: Old Method Names

**Symptom:**
```
Method passes() not found
```

**Solution:**
Update to `ValidationRule` interface and implement `validate()` method.

### Issue: Model References

**Symptom:**
```
Class 'Acelle\Model\User' not found
```

**Solution:**
Replace all `\Acelle\Model\*` with `\Modules\Mailing\Models\*`

---

## Checklist Template

Use for each rule migration:

```markdown
## RuleName Migration

- [ ] File copied to modules/Mailing/app/Rules/
- [ ] Namespace updated
- [ ] Implements ValidationRule interface
- [ ] validate() method implemented
- [ ] Old passes()/message() methods removed
- [ ] Constructor uses property promotion
- [ ] Acelle model references updated
- [ ] Type hints added
- [ ] PHPDoc block added
- [ ] Test file created
- [ ] Tests written and passing
- [ ] Code formatted with Pint
- [ ] Form Requests updated
- [ ] Documented in migration report
```

---

## Resources

### Internal Documentation
- `RULES_MIGRATION_REPORT.md` - Migration tracking
- `rules-migration-guide.md` - Migration how-to
- `example-rules-templates.md` - Template library
- `migrate-rules.sh` - Migration script

### Laravel Documentation
- [Validation Documentation](https://laravel.com/docs/12.x/validation)
- [Custom Validation Rules](https://laravel.com/docs/12.x/validation#custom-validation-rules)
- [Form Requests](https://laravel.com/docs/12.x/validation#form-request-validation)
- [Testing](https://laravel.com/docs/12.x/testing)

### Tools
- Laravel Pint - Code formatting
- PHPUnit - Testing framework
- Composer - Autoloading

---

## Support and Contribution

### Getting Help

1. Check this documentation index
2. Review the migration guide
3. Look at example templates
4. Consult Laravel 12 documentation

### Reporting Issues

When encountering issues, document in RULES_MIGRATION_REPORT.md:
- Rule name
- Issue description
- Error messages
- Solution attempted
- Final resolution

### Best Practices

1. **One rule at a time** - Complete each rule fully before moving to next
2. **Test immediately** - Write and run tests before considering rule complete
3. **Document everything** - Update tracking report as you go
4. **Follow conventions** - Use existing code style and patterns
5. **Use Context7** - Leverage official Laravel documentation

---

## Next Steps

1. **Run migration script:**
   ```bash
   chmod +x migrate-rules.sh
   ./migrate-rules.sh
   ```

2. **Review migrated files**

3. **Update to Laravel 12 syntax** using guide

4. **Create comprehensive tests**

5. **Update Form Requests**

6. **Run full test suite**

7. **Complete migration report**

---

Last Updated: 2026-01-29
Documentation Version: 1.0
