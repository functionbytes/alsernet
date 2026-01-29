# Mailing Helpers - Quick Reference Guide

## Import Statements

Add these at the top of your files:

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

## Common Use Cases

### Email Processing

```php
// Extract email from string
$email = MailingHelper::extractEmail("John Doe <john@example.com>");
// Result: "john@example.com"

// Validate email
if (MailingHelper::isValidEmail($email)) {
    // Valid email
}

// Get email domain
$domain = MailingHelper::getEmailDomain("user@example.com");
// Result: "example.com"

// Parse email list
$emails = MailingHelper::parseEmailList("user1@ex.com, user2@ex.com; user3@ex.com");
// Result: ["user1@ex.com", "user2@ex.com", "user3@ex.com"]
```

### Campaign URLs

```php
// Generate unsubscribe URL
$unsubUrl = MailingHelper::generateUnsubscribeUrl($campaign, $subscriber);

// Generate web version URL
$webUrl = MailingHelper::generateWebVersionUrl($campaign, $subscriber);

// Generate tracking pixel URL
$pixelUrl = MailingHelper::generateTrackingPixelUrl($campaign, $subscriber);
```

### Quota Management

```php
// Check if customer has quota
if (QuotaHelper::hasQuota($customer, 100)) {
    // Can send 100 emails
}

// Get remaining quota
$remaining = QuotaHelper::getRemainingQuota($customer);
echo "You can send {$remaining} more emails";

// Get usage percentage
$usage = QuotaHelper::getQuotaUsagePercentage($customer);
echo "Quota used: {$usage}%";

// Format quota display
$display = QuotaHelper::formatQuotaDisplay(5000, 10000);
echo $display; // "5,000 / 10,000"

// Get quota status color
$color = QuotaHelper::getQuotaStatusColor($usage);
// Returns: 'success', 'warning', or 'danger'
```

### Date & Time

```php
// Format date
$formatted = DateHelper::formatDate($campaign->created_at);

// Human-readable date
$human = DateHelper::formatDateForHumans($campaign->created_at);
echo $human; // "2 hours ago"

// Convert to customer timezone
$localTime = DateHelper::toCustomerTimezone($date, 'America/New_York');

// Check if scheduled in future
if (DateHelper::isScheduledInFuture($campaign)) {
    echo "Campaign will be sent later";
}

// Get date range
$range = DateHelper::getDateRange('this_month');
// Returns: ['start' => Carbon, 'end' => Carbon]

// Format duration
$duration = DateHelper::formatDuration(3665);
echo $duration; // "1h 1m 5s"
```

### Template Processing

```php
// Replace template tags
$content = TemplateHelper::replaceTags($template, [
    'FIRST_NAME' => 'John',
    'COMPANY' => 'Acme Corp'
]);

// Process subscriber tags
$content = TemplateHelper::processSubscriberTags(
    $template,
    $subscriber,
    $campaign
);

// Convert HTML to plain text
$plainText = TemplateHelper::htmlToPlainText($htmlContent);

// Add tracking to links
$tracked = TemplateHelper::addTrackingToLinks($content, $campaign, $subscriber);

// Add tracking pixel
$withPixel = TemplateHelper::addTrackingPixel($content, $campaign, $subscriber);

// Validate template
$validation = TemplateHelper::validateTemplate($content);
if ($validation['valid']) {
    // Template is valid
} else {
    foreach ($validation['errors'] as $error) {
        echo "Error: {$error}\n";
    }
}

// Minify HTML
$minified = TemplateHelper::minifyHtml($htmlContent);
```

### Campaign Statistics

```php
// Calculate rates
$openRate = StatisticsHelper::calculateOpenRate($opened, $delivered);
$clickRate = StatisticsHelper::calculateClickRate($clicked, $delivered);
$bounceRate = StatisticsHelper::calculateBounceRate($bounced, $sent);

// Get full campaign summary
$stats = StatisticsHelper::getCampaignSummary($campaign);
/*
Returns:
[
    'sent' => 10000,
    'delivered' => 9800,
    'opened' => 2940,
    'clicked' => 588,
    'open_rate' => 30.0,
    'click_rate' => 6.0,
    'performance_grade' => 'B',
    'engagement_score' => 85.5
]
*/

// Get performance grade
$grade = StatisticsHelper::getPerformanceGrade($stats);
echo "Campaign grade: {$grade}"; // A, B, C, D, or F

// Compare campaigns
$comparison = StatisticsHelper::compareCampaigns($stats1, $stats2);

// Get top performing links
$topLinks = StatisticsHelper::getTopLinks($campaign, 5);

// Format percentage
$formatted = StatisticsHelper::formatPercentage(30.5678, 2);
echo $formatted; // "30.57%"

// Get badge color based on rate
$color = StatisticsHelper::getRateBadgeColor(30.5, 'open');
// Returns: 'success', 'info', 'warning', or 'danger'
```

### Validation

```php
// Validate email format
if (ValidationHelper::isValidEmail($email)) {
    // Valid format
}

// Check domain validity (DNS)
if (ValidationHelper::hasValidDomain($email)) {
    // Domain exists
}

// Check if disposable email
if (ValidationHelper::isDisposableEmail($email)) {
    echo "Please use a permanent email address";
}

// Validate import data
$validation = ValidationHelper::validateImportData([
    'email' => 'user@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe'
]);

if ($validation['valid']) {
    // Data is valid
} else {
    foreach ($validation['errors'] as $error) {
        echo "Error: {$error}\n";
    }
}

// Sanitize email content
$clean = ValidationHelper::sanitizeEmailContent($htmlContent);

// Check spam indicators
$spamCheck = ValidationHelper::checkSpamIndicators($content);
if ($spamCheck['is_spam']) {
    echo "Spam score: {$spamCheck['score']}\n";
    foreach ($spamCheck['indicators'] as $indicator) {
        echo "- {$indicator}\n";
    }
}

// Check for unsubscribe link
if (!ValidationHelper::hasUnsubscribeLink($content)) {
    echo "Warning: No unsubscribe link found";
}

// Analyze subject line
$analysis = ValidationHelper::analyzeSubjectLine($subject);
echo "Subject quality score: {$analysis['score']}/10\n";
foreach ($analysis['suggestions'] as $suggestion) {
    echo "- {$suggestion}\n";
}
```

## Blade Template Examples

### Display Quota

```blade
@php
use Modules\Mailing\App\Helpers\QuotaHelper;

$remaining = QuotaHelper::getRemainingQuota($customer);
$usage = QuotaHelper::getQuotaUsagePercentage($customer);
$color = QuotaHelper::getQuotaStatusColor($usage);
$display = QuotaHelper::formatQuotaDisplay($used, $limit);
@endphp

<div class="card">
    <div class="card-body">
        <h6>Sending Quota</h6>
        <div class="progress">
            <div class="progress-bar bg-{{ $color }}"
                 style="width: {{ $usage }}%">
                {{ number_format($usage, 1) }}%
            </div>
        </div>
        <p class="mt-2">{{ $display }}</p>
    </div>
</div>
```

### Display Campaign Stats

```blade
@php
use Modules\Mailing\App\Helpers\StatisticsHelper;

$stats = StatisticsHelper::getCampaignSummary($campaign);
@endphp

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Open Rate</h6>
                <h3>{{ StatisticsHelper::formatPercentage($stats['open_rate']) }}</h3>
                <span class="badge bg-{{ StatisticsHelper::getRateBadgeColor($stats['open_rate'], 'open') }}">
                    {{ $stats['opened'] }} opens
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Click Rate</h6>
                <h3>{{ StatisticsHelper::formatPercentage($stats['click_rate']) }}</h3>
                <span class="badge bg-{{ StatisticsHelper::getRateBadgeColor($stats['click_rate'], 'click') }}">
                    {{ $stats['clicked'] }} clicks
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Performance</h6>
                <h3>Grade {{ $stats['performance_grade'] }}</h3>
                <small>Score: {{ number_format($stats['engagement_score'], 1) }}</small>
            </div>
        </div>
    </div>
</div>
```

### Format Dates

```blade
@php
use Modules\Mailing\App\Helpers\DateHelper;
@endphp

<p>
    Created: {{ DateHelper::formatDate($campaign->created_at, 'M d, Y H:i') }}
</p>
<p>
    Last activity: {{ DateHelper::formatDateForHumans($campaign->updated_at) }}
</p>

@if($campaign->scheduled_at)
    <p>
        Scheduled: {{ DateHelper::getTimeUntilSend($campaign) }}
    </p>
@endif
```

## Controller Examples

### Validate and Save Subscriber

```php
use Modules\Mailing\App\Helpers\ValidationHelper;
use Modules\Mailing\App\Helpers\MailingHelper;

public function store(Request $request)
{
    // Validate subscriber data
    $validation = ValidationHelper::validateSubscriberData($request->all());

    if (!$validation['valid']) {
        return back()->withErrors($validation['errors']);
    }

    // Create subscriber
    $subscriber = Subscriber::create([
        'email' => $request->email,
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'uid' => MailingHelper::generateSubscriberUid(),
    ]);

    return redirect()->route('subscribers.index')
        ->with('success', 'Subscriber added successfully');
}
```

### Check Quota Before Sending

```php
use Modules\Mailing\App\Helpers\QuotaHelper;

public function sendCampaign(Campaign $campaign)
{
    $customer = auth()->user()->customer;
    $recipientCount = $campaign->recipients()->count();

    // Check quota
    if (!QuotaHelper::hasQuota($customer, $recipientCount)) {
        $remaining = QuotaHelper::getRemainingQuota($customer);

        return back()->withErrors([
            "Insufficient quota. You have {$remaining} emails remaining, but need {$recipientCount}."
        ]);
    }

    // Check rate limit
    if (!QuotaHelper::canSendWithinRateLimit($customer)) {
        $waitTime = QuotaHelper::getTimeUntilNextSlot($customer);

        return back()->withErrors([
            "Rate limit exceeded. Please wait {$waitTime} seconds before sending."
        ]);
    }

    // Proceed with sending
    dispatch(new SendCampaignJob($campaign));

    return redirect()->route('campaigns.show', $campaign)
        ->with('success', 'Campaign is being sent');
}
```

## Performance Tips

### 1. Use Static Methods Efficiently

```php
// ❌ Don't call repeatedly in loops
foreach ($subscribers as $subscriber) {
    $email = MailingHelper::extractEmail($subscriber->raw_email);
}

// ✅ Do process in bulk or cache
$emails = array_map(
    fn($s) => MailingHelper::extractEmail($s->raw_email),
    $subscribers
);
```

### 2. Cache Expensive Calculations

```php
// Cache campaign statistics
$stats = Cache::remember(
    "campaign.{$campaign->id}.stats",
    now()->addMinutes(10),
    fn() => StatisticsHelper::getCampaignSummary($campaign)
);
```

### 3. Validate Once

```php
// ❌ Don't validate same data multiple times
if (ValidationHelper::isValidEmail($email)) {
    if (ValidationHelper::hasValidDomain($email)) {
        // ...
    }
}

// ✅ Do use comprehensive validation
$validation = ValidationHelper::validateSubscriberData(['email' => $email]);
if ($validation['valid']) {
    // All checks passed
}
```

## Testing Examples

```php
use Tests\TestCase;
use Modules\Mailing\App\Helpers\MailingHelper;

class MailingHelperTest extends TestCase
{
    public function test_extract_email()
    {
        $this->assertEquals(
            'user@example.com',
            MailingHelper::extractEmail('John <user@example.com>')
        );
    }

    public function test_format_bytes()
    {
        $this->assertEquals('1.00 KB', MailingHelper::formatBytes(1024));
        $this->assertEquals('1.00 MB', MailingHelper::formatBytes(1048576));
    }

    public function test_parse_email_list()
    {
        $result = MailingHelper::parseEmailList('a@ex.com, b@ex.com; c@ex.com');
        $this->assertCount(3, $result);
        $this->assertEquals(['a@ex.com', 'b@ex.com', 'c@ex.com'], $result);
    }
}
```

## Troubleshooting

### Issue: Class not found

```
Error: Class 'Modules\Mailing\App\Helpers\MailingHelper' not found
```

**Solution:** Add use statement at top of file:
```php
use Modules\Mailing\App\Helpers\MailingHelper;
```

### Issue: Method not found

```
Error: Call to undefined method extractEmail()
```

**Solution:** Use static method syntax:
```php
// ❌ Wrong
$email = extractEmail($string);

// ✅ Correct
$email = MailingHelper::extractEmail($string);
```

---

For complete documentation, see [HELPERS_MIGRATION_REPORT.md](./HELPERS_MIGRATION_REPORT.md)
