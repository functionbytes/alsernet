# Mailing Helpers - Documentation Index

Quick navigation to all helper-related documentation.

## 📚 Documentation Files

### 1. Migration Report
**File:** [HELPERS_MIGRATION_REPORT.md](./HELPERS_MIGRATION_REPORT.md)

Complete migration documentation from Acelle global functions to Laravel 12 static methods.

**Contents:**
- Detailed method mapping tables
- Before/after code comparisons
- Breaking changes
- Migration checklist
- Benefits and recommendations

**Use when:** You need to understand what changed and how to migrate existing code.

---

### 2. Quick Reference Guide
**File:** [helpers-quick-reference.md](./helpers-quick-reference.md)

Developer quick reference with practical examples.

**Contents:**
- Import statements
- Common use cases
- Blade template examples
- Controller examples
- Performance tips
- Troubleshooting

**Use when:** You need quick code examples or syntax reference.

---

### 3. Helpers README
**File:** [../app/Helpers/README.md](../app/Helpers/README.md)

Overview of all helper classes with design principles.

**Contents:**
- Class descriptions
- Usage patterns
- Design principles
- Testing guidelines
- Contributing guide

**Use when:** You need to understand the architecture or add new helpers.

---

### 4. Implementation Summary
**File:** [helpers-implementation-summary.md](./helpers-implementation-summary.md)

Complete implementation status and statistics.

**Contents:**
- Completion status
- Code metrics
- Feature list
- Next steps
- Risk assessment

**Use when:** You need project status or planning information.

---

## 🔧 Helper Classes

### MailingHelper
**File:** `/modules/Mailing/app/Helpers/MailingHelper.php`
**Methods:** 13

Email processing, URL generation, content manipulation.

**Quick Examples:**
```php
MailingHelper::extractEmail($string);
MailingHelper::generateUnsubscribeUrl($campaign, $subscriber);
MailingHelper::parseEmailList($emails);
```

---

### QuotaHelper
**File:** `/modules/Mailing/app/Helpers/QuotaHelper.php`
**Methods:** 11

Quota management, rate limiting, usage tracking.

**Quick Examples:**
```php
QuotaHelper::hasQuota($customer, 100);
QuotaHelper::getRemainingQuota($customer);
QuotaHelper::getQuotaUsagePercentage($customer);
```

---

### DateHelper
**File:** `/modules/Mailing/app/Helpers/DateHelper.php`
**Methods:** 12

Date formatting, timezone conversion, scheduling.

**Quick Examples:**
```php
DateHelper::formatDateForHumans($date);
DateHelper::toCustomerTimezone($date, 'America/New_York');
DateHelper::getDateRange('this_month');
```

---

### TemplateHelper
**File:** `/modules/Mailing/app/Helpers/TemplateHelper.php`
**Methods:** 11

Template processing, tag replacement, content transformation.

**Quick Examples:**
```php
TemplateHelper::processSubscriberTags($content, $subscriber, $campaign);
TemplateHelper::addTrackingToLinks($content, $campaign, $subscriber);
TemplateHelper::validateTemplate($content);
```

---

### StatisticsHelper
**File:** `/modules/Mailing/app/Helpers/StatisticsHelper.php`
**Methods:** 17

Campaign metrics, analytics, performance grading.

**Quick Examples:**
```php
StatisticsHelper::getCampaignSummary($campaign);
StatisticsHelper::calculateOpenRate($opened, $delivered);
StatisticsHelper::getPerformanceGrade($metrics);
```

---

### ValidationHelper
**File:** `/modules/Mailing/app/Helpers/ValidationHelper.php`
**Methods:** 12

Email validation, content sanitization, spam detection.

**Quick Examples:**
```php
ValidationHelper::hasValidDomain($email);
ValidationHelper::isDisposableEmail($email);
ValidationHelper::checkSpamIndicators($content);
```

---

## 🎯 Common Tasks

### Finding a Method

1. **Know the category?** → Check specific helper class file
2. **Know the old function name?** → Check [HELPERS_MIGRATION_REPORT.md](./HELPERS_MIGRATION_REPORT.md) mapping tables
3. **Need examples?** → Check [helpers-quick-reference.md](./helpers-quick-reference.md)
4. **Want to explore?** → Read [../app/Helpers/README.md](../app/Helpers/README.md)

### Using in Code

```php
// 1. Import the helper
use Modules\Mailing\App\Helpers\MailingHelper;

// 2. Call static method
$email = MailingHelper::extractEmail($string);
```

### Using in Blade

```blade
@php
use Modules\Mailing\App\Helpers\StatisticsHelper;

$stats = StatisticsHelper::getCampaignSummary($campaign);
@endphp

<div>Open Rate: {{ StatisticsHelper::formatPercentage($stats['open_rate']) }}</div>
```

---

## 📊 Quick Stats

| Metric | Value |
|--------|-------|
| Helper Classes | 6 |
| Total Methods | 76 |
| Lines of Code | ~2,500 |
| Documentation Files | 4 |
| Code Coverage | TBD |

---

## 🔍 Search Guide

### By Functionality

| What do you need? | Use this helper |
|-------------------|-----------------|
| Parse/extract emails | MailingHelper |
| Check sending quota | QuotaHelper |
| Format dates | DateHelper |
| Process email templates | TemplateHelper |
| Calculate campaign metrics | StatisticsHelper |
| Validate email/content | ValidationHelper |

### By Common Operations

| Operation | Method |
|-----------|--------|
| Extract email from string | `MailingHelper::extractEmail()` |
| Check if can send | `QuotaHelper::hasQuota()` |
| Format date nicely | `DateHelper::formatDateForHumans()` |
| Replace template tags | `TemplateHelper::replaceTags()` |
| Calculate open rate | `StatisticsHelper::calculateOpenRate()` |
| Validate email domain | `ValidationHelper::hasValidDomain()` |

---

## 🚀 Getting Started

### New to the helpers?

1. Read [HELPERS_MIGRATION_REPORT.md](./HELPERS_MIGRATION_REPORT.md) - Overview section
2. Browse [helpers-quick-reference.md](./helpers-quick-reference.md) - Common use cases
3. Try examples in your code
4. Refer back as needed

### Migrating existing code?

1. Read [HELPERS_MIGRATION_REPORT.md](./HELPERS_MIGRATION_REPORT.md) - Full migration guide
2. Use method mapping tables to convert functions
3. Test thoroughly
4. Check [helpers-quick-reference.md](./helpers-quick-reference.md) - Troubleshooting section

### Adding new helpers?

1. Read [../app/Helpers/README.md](../app/Helpers/README.md) - Design principles
2. Follow existing patterns
3. Add PHPDoc blocks
4. Update documentation
5. Add tests

---

## 📞 Support

### Questions?
- Check the appropriate documentation file above
- Review PHPDoc blocks in helper class files
- Look for similar usage in examples

### Issues?
- Check [helpers-quick-reference.md](./helpers-quick-reference.md) - Troubleshooting section
- Review code with Laravel Pint formatting
- Ensure proper imports

### Contributing?
- Read [../app/Helpers/README.md](../app/Helpers/README.md) - Contributing section
- Follow Laravel coding standards
- Add comprehensive tests
- Update documentation

---

## 🗂️ File Structure

```
modules/Mailing/
├── app/
│   └── Helpers/
│       ├── MailingHelper.php
│       ├── QuotaHelper.php
│       ├── DateHelper.php
│       ├── TemplateHelper.php
│       ├── StatisticsHelper.php
│       ├── ValidationHelper.php
│       └── README.md
└── docs/
    ├── HELPERS_INDEX.md (this file)
    ├── HELPERS_MIGRATION_REPORT.md
    ├── helpers-quick-reference.md
    └── helpers-implementation-summary.md
```

---

## 📝 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-01-29 | Initial implementation |

---

**Last Updated:** 2026-01-29
**Status:** ✅ Documentation Complete
**Maintainer:** Alsernet Development Team
