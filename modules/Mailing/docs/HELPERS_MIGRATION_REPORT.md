# Helpers Migration Report

## Overview

This document maps the original Acelle helper functions to their new static class methods in the Mailing module. All functions have been converted to static methods to avoid namespace collisions with Laravel 12's global helpers.

## Migration Date

**Date:** 2026-01-29

## Architecture Changes

### Before (Acelle)
- Global helper functions in `app/Helpers/helpers.php`
- Namespaced functions in `app/Helpers/namespaced_helpers.php`
- Risk of function name collisions
- No autocompletion support in IDEs

### After (Laravel 12 Mailing Module)
- Static class methods in organized helper classes
- Full IDE autocompletion and type hints
- PSR-4 autoloading
- Namespace: `Modules\Mailing\App\Helpers`

## Helper Classes Created

### 1. MailingHelper
**File:** `/modules/Mailing/app/Helpers/MailingHelper.php`

General mailing utilities for email processing, URL generation, and content manipulation.

#### Method Mapping

| Original Function | New Method | Description |
|-------------------|------------|-------------|
| `extract_email($string)` | `MailingHelper::extractEmail(string $string): ?string` | Extract email from string |
| `generate_unsubscribe_url($campaign, $subscriber)` | `MailingHelper::generateUnsubscribeUrl($campaign, $subscriber): string` | Generate unsubscribe URL |
| `generate_web_version_url($campaign, $subscriber)` | `MailingHelper::generateWebVersionUrl($campaign, $subscriber): string` | Generate web version URL |
| `generate_tracking_pixel_url($campaign, $subscriber)` | `MailingHelper::generateTrackingPixelUrl($campaign, $subscriber): string` | Generate tracking pixel |
| `format_bytes($bytes, $precision)` | `MailingHelper::formatBytes(int $bytes, int $precision = 2): string` | Format file size |
| `parse_email_list($emails)` | `MailingHelper::parseEmailList(string $emails): array` | Parse email list |
| `is_valid_email($email)` | `MailingHelper::isValidEmail(string $email): bool` | Validate email |
| `generate_subscriber_uid()` | `MailingHelper::generateSubscriberUid(): string` | Generate subscriber UID |
| `generate_campaign_uid()` | `MailingHelper::generateCampaignUid(): string` | Generate campaign UID |
| `get_email_domain($email)` | `MailingHelper::getEmailDomain(string $email): ?string` | Extract domain |
| `clean_email_content($content)` | `MailingHelper::cleanEmailContent(string $content): string` | Remove tracking |
| `generate_message_id($domain)` | `MailingHelper::generateMessageId(string $domain = 'localhost'): string` | Generate message ID |
| `parse_email_headers($headers)` | `MailingHelper::parseEmailHeaders(string $headers): array` | Parse headers |

---

### 2. QuotaHelper
**File:** `/modules/Mailing/app/Helpers/QuotaHelper.php`

Manages sending quotas, rate limits, and subscription constraints.

#### Method Mapping

| Original Function | New Method | Description |
|-------------------|------------|-------------|
| `has_quota($customer, $count)` | `QuotaHelper::hasQuota($customer, int $count = 1): bool` | Check quota availability |
| `get_remaining_quota($customer)` | `QuotaHelper::getRemainingQuota($customer): int` | Get remaining sends |
| `get_quota_usage_percentage($customer)` | `QuotaHelper::getQuotaUsagePercentage($customer): float` | Quota usage % |
| `can_send_within_rate_limit($customer, $interval)` | `QuotaHelper::canSendWithinRateLimit($customer, int $interval = 60): bool` | Check rate limit |
| `get_time_until_next_slot($customer)` | `QuotaHelper::getTimeUntilNextSlot($customer): int` | Time to next slot |
| `has_reached_subscriber_limit($customer)` | `QuotaHelper::hasReachedSubscriberLimit($customer): bool` | Check subscriber limit |
| `get_remaining_subscriber_quota($customer)` | `QuotaHelper::getRemainingSubscriberQuota($customer): int` | Remaining subscribers |
| `has_reached_list_limit($customer)` | `QuotaHelper::hasReachedListLimit($customer): bool` | Check list limit |
| `get_remaining_list_quota($customer)` | `QuotaHelper::getRemainingListQuota($customer): int` | Remaining lists |
| `format_quota_display($used, $limit)` | `QuotaHelper::formatQuotaDisplay(int $used, int $limit): string` | Format quota text |
| `get_quota_status_color($percentage)` | `QuotaHelper::getQuotaStatusColor(float $percentage): string` | Get badge color |

---

### 3. DateHelper
**File:** `/modules/Mailing/app/Helpers/DateHelper.php`

Handles date formatting, timezone conversions, and scheduling calculations.

#### Method Mapping

| Original Function | New Method | Description |
|-------------------|------------|-------------|
| `format_date($date, $format)` | `DateHelper::formatDate($date, string $format = 'Y-m-d H:i:s'): string` | Format date |
| `format_date_for_humans($date)` | `DateHelper::formatDateForHumans($date): string` | Human-readable date |
| `to_customer_timezone($date, $timezone)` | `DateHelper::toCustomerTimezone($date, string $timezone = 'UTC'): Carbon` | Convert to customer TZ |
| `to_utc($date, $timezone)` | `DateHelper::toUtc($date, string $timezone = 'UTC'): Carbon` | Convert to UTC |
| `get_scheduled_sending_time($campaign)` | `DateHelper::getScheduledSendingTime($campaign): ?Carbon` | Get scheduled time |
| `is_scheduled_in_future($campaign)` | `DateHelper::isScheduledInFuture($campaign): bool` | Check if future |
| `calculate_next_run($frequency, $lastRun)` | `DateHelper::calculateNextRun(string $frequency, ?Carbon $lastRun = null): Carbon` | Next run time |
| `get_time_until_send($campaign)` | `DateHelper::getTimeUntilSend($campaign): string` | Time until send |
| `format_duration($seconds)` | `DateHelper::formatDuration(int $seconds): string` | Format seconds |
| `get_date_range($period, $startDate, $endDate)` | `DateHelper::getDateRange(string $period, ?Carbon $startDate = null, ?Carbon $endDate = null): array` | Get date range |
| `is_within_business_hours($date, $startHour, $endHour)` | `DateHelper::isWithinBusinessHours(?Carbon $date = null, int $startHour = 9, int $endHour = 17): bool` | Check business hours |
| `get_next_business_day($date)` | `DateHelper::getNextBusinessDay(?Carbon $date = null): Carbon` | Next business day |

---

### 4. TemplateHelper
**File:** `/modules/Mailing/app/Helpers/TemplateHelper.php`

Processes email templates, handles variable replacement, and content transformation.

#### Method Mapping

| Original Function | New Method | Description |
|-------------------|------------|-------------|
| `replace_tags($content, $tags)` | `TemplateHelper::replaceTags(string $content, array $tags): string` | Replace template tags |
| `get_available_tags()` | `TemplateHelper::getAvailableTags(): array` | List available tags |
| `process_subscriber_tags($content, $subscriber, $campaign)` | `TemplateHelper::processSubscriberTags(string $content, $subscriber, $campaign): string` | Process subscriber tags |
| `html_to_plain_text($html)` | `TemplateHelper::htmlToPlainText(string $html): string` | Convert HTML to text |
| `add_tracking_to_links($content, $campaign, $subscriber)` | `TemplateHelper::addTrackingToLinks(string $content, $campaign, $subscriber): string` | Add link tracking |
| `add_tracking_pixel($content, $campaign, $subscriber)` | `TemplateHelper::addTrackingPixel(string $content, $campaign, $subscriber): string` | Add tracking pixel |
| `validate_template($content)` | `TemplateHelper::validateTemplate(string $content): array` | Validate template |
| `minify_html($html)` | `TemplateHelper::minifyHtml(string $html): string` | Minify HTML |
| `inline_css($html)` | `TemplateHelper::inlineCss(string $html): string` | Inline CSS styles |
| `get_template_size($content)` | `TemplateHelper::getTemplateSize(string $content): int` | Get template size |
| `exceeds_size_limit($content, $maxSize)` | `TemplateHelper::exceedsSizeLimit(string $content, int $maxSize = 102400): bool` | Check size limit |

---

### 5. StatisticsHelper
**File:** `/modules/Mailing/app/Helpers/StatisticsHelper.php`

Calculates campaign metrics, rates, and performance analytics.

#### Method Mapping

| Original Function | New Method | Description |
|-------------------|------------|-------------|
| `calculate_open_rate($opened, $delivered)` | `StatisticsHelper::calculateOpenRate(int $opened, int $delivered): float` | Open rate % |
| `calculate_click_rate($clicked, $delivered)` | `StatisticsHelper::calculateClickRate(int $clicked, int $delivered): float` | Click rate % |
| `calculate_click_to_open_rate($clicked, $opened)` | `StatisticsHelper::calculateClickToOpenRate(int $clicked, int $opened): float` | CTOR % |
| `calculate_bounce_rate($bounced, $sent)` | `StatisticsHelper::calculateBounceRate(int $bounced, int $sent): float` | Bounce rate % |
| `calculate_unsubscribe_rate($unsubscribed, $delivered)` | `StatisticsHelper::calculateUnsubscribeRate(int $unsubscribed, int $delivered): float` | Unsubscribe rate % |
| `calculate_delivery_rate($delivered, $sent)` | `StatisticsHelper::calculateDeliveryRate(int $delivered, int $sent): float` | Delivery rate % |
| `calculate_complaint_rate($complaints, $delivered)` | `StatisticsHelper::calculateComplaintRate(int $complaints, int $delivered): float` | Complaint rate % |
| `get_performance_grade($metrics)` | `StatisticsHelper::getPerformanceGrade(array $metrics): string` | Grade A-F |
| `calculate_engagement_score($metrics)` | `StatisticsHelper::calculateEngagementScore(array $metrics): float` | Engagement score |
| `get_campaign_summary($campaign)` | `StatisticsHelper::getCampaignSummary($campaign): array` | Full stats summary |
| `compare_campaigns($campaign1Stats, $campaign2Stats)` | `StatisticsHelper::compareCampaigns(array $campaign1Stats, array $campaign2Stats): array` | Compare campaigns |
| `get_top_links($campaign, $limit)` | `StatisticsHelper::getTopLinks($campaign, int $limit = 10): array` | Top performing links |
| `get_average_time_to_open($campaign)` | `StatisticsHelper::getAverageTimeToOpen($campaign): float` | Avg time to open |
| `get_hourly_open_distribution($campaign)` | `StatisticsHelper::getHourlyOpenDistribution($campaign): array` | Opens by hour |
| `get_daily_open_distribution($campaign)` | `StatisticsHelper::getDailyOpenDistribution($campaign): array` | Opens by day |
| `format_percentage($percentage, $decimals)` | `StatisticsHelper::formatPercentage(float $percentage, int $decimals = 2): string` | Format % |
| `get_rate_badge_color($rate, $type)` | `StatisticsHelper::getRateBadgeColor(float $rate, string $type): string` | Badge color |

---

### 6. ValidationHelper
**File:** `/modules/Mailing/app/Helpers/ValidationHelper.php`

Validates emails, domains, content, and data structures.

#### Method Mapping

| Original Function | New Method | Description |
|-------------------|------------|-------------|
| `is_valid_email($email)` | `ValidationHelper::isValidEmail(string $email): bool` | Validate email format |
| `has_valid_domain($email)` | `ValidationHelper::hasValidDomain(string $email): bool` | Check DNS records |
| `is_disposable_email($email)` | `ValidationHelper::isDisposableEmail(string $email): bool` | Check disposable |
| `is_blacklisted($email)` | `ValidationHelper::isBlacklisted(string $email): bool` | Check blacklist |
| `validate_import_data($data)` | `ValidationHelper::validateImportData(array $data): array` | Validate import row |
| `validate_campaign_data($data)` | `ValidationHelper::validateCampaignData(array $data): array` | Validate campaign |
| `validate_subscriber_data($data)` | `ValidationHelper::validateSubscriberData(array $data): array` | Validate subscriber |
| `validate_smtp_settings($settings)` | `ValidationHelper::validateSmtpSettings(array $settings): array` | Validate SMTP |
| `sanitize_email_content($content)` | `ValidationHelper::sanitizeEmailContent(string $content): string` | Remove dangerous tags |
| `check_spam_indicators($content)` | `ValidationHelper::checkSpamIndicators(string $content): array` | Spam check |
| `has_unsubscribe_link($content)` | `ValidationHelper::hasUnsubscribeLink(string $content): bool` | Check unsubscribe |
| `analyze_subject_line($subject)` | `ValidationHelper::analyzeSubjectLine(string $subject): array` | Subject quality |

---

## Usage Examples

### Before (Global Functions)

```php
// Old Acelle way
$email = extract_email($string);
$url = generate_unsubscribe_url($campaign, $subscriber);
$quota = get_remaining_quota($customer);
$rate = calculate_open_rate($opened, $delivered);
```

### After (Static Methods)

```php
use Modules\Mailing\App\Helpers\MailingHelper;
use Modules\Mailing\App\Helpers\QuotaHelper;
use Modules\Mailing\App\Helpers\StatisticsHelper;

// New Laravel 12 way
$email = MailingHelper::extractEmail($string);
$url = MailingHelper::generateUnsubscribeUrl($campaign, $subscriber);
$quota = QuotaHelper::getRemainingQuota($customer);
$rate = StatisticsHelper::calculateOpenRate($opened, $delivered);
```

## Benefits of Migration

### 1. Type Safety
All methods have explicit return types and parameter types, reducing runtime errors.

```php
// Before: No type hints
function extract_email($string) { ... }

// After: Full type safety
public static function extractEmail(string $string): ?string { ... }
```

### 2. IDE Support
Full autocompletion and inline documentation in modern IDEs.

### 3. Namespace Safety
No risk of function name collisions with Laravel or other packages.

### 4. Testability
Static methods can be easily mocked and tested.

### 5. Organization
Related functions grouped into logical helper classes.

### 6. Documentation
Each class has comprehensive PHPDoc blocks with parameter descriptions.

## Breaking Changes

### Function Calls Must Be Updated

All code using the old global functions must be updated to use the new static methods:

```php
// ❌ OLD - Will throw "Function not found" error
$email = extract_email($string);

// ✅ NEW - Correct usage
use Modules\Mailing\App\Helpers\MailingHelper;
$email = MailingHelper::extractEmail($string);
```

### Import Statements Required

Add use statements at the top of files:

```php
use Modules\Mailing\App\Helpers\{
    MailingHelper,
    QuotaHelper,
    DateHelper,
    TemplateHelper,
    StatisticsHelper,
    ValidationHelper
};
```

## Migration Checklist

- [x] Create MailingHelper class
- [x] Create QuotaHelper class
- [x] Create DateHelper class
- [x] Create TemplateHelper class
- [x] Create StatisticsHelper class
- [x] Create ValidationHelper class
- [ ] Update all existing code to use new static methods
- [ ] Create unit tests for all helper methods
- [ ] Update documentation references
- [ ] Remove old helper files (after verification)

## Recommendations

### 1. Gradual Migration
Don't remove old helper files immediately. Keep them with deprecation notices while updating code.

### 2. Create Facades (Optional)
For frequently used helpers, consider creating Laravel facades:

```php
// config/app.php
'aliases' => [
    'Mailing' => Modules\Mailing\App\Facades\MailingHelper::class,
];

// Usage
Mailing::extractEmail($string);
```

### 3. Add Unit Tests
Each helper class should have comprehensive test coverage:

```php
namespace Tests\Unit\Helpers;

class MailingHelperTest extends TestCase
{
    public function test_extract_email()
    {
        $this->assertEquals(
            'user@example.com',
            MailingHelper::extractEmail('Contact: user@example.com')
        );
    }
}
```

### 4. Performance Monitoring
Monitor performance after migration to ensure no regressions.

## Support and Questions

For questions about the migration, contact the development team or refer to:
- Laravel 12 Documentation
- Module documentation in `/modules/Mailing/docs/`
- Helper class PHPDoc blocks

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-01-29 | Initial migration from Acelle helpers |

---

**Status:** ✅ Helper classes created and documented

**Next Steps:**
1. Review and test all helper methods
2. Update existing codebase to use new helpers
3. Add comprehensive unit tests
4. Deprecate old global functions
