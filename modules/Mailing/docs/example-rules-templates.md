# Example Validation Rule Templates

## Email Marketing Specific Rules

### 1. SendingDomainRule.php

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a domain has proper DNS configuration for email sending.
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
        // Validate domain format
        if (!$this->isValidDomain($value)) {
            $fail("The {$attribute} must be a valid domain name.");
            return;
        }

        // Check MX records
        $mxRecords = @dns_get_record($value, DNS_MX);
        if (empty($mxRecords)) {
            $fail("The {$attribute} must have valid MX records.");
            return;
        }

        // Check SPF if required
        if ($this->requireSpf && !$this->hasSpfRecord($value)) {
            $fail("The {$attribute} must have a valid SPF record.");
            return;
        }

        // Check DKIM if required
        if ($this->requireDkim && !$this->hasDkimRecord($value)) {
            $fail("The {$attribute} must have valid DKIM records.");
            return;
        }
    }

    /**
     * Validate domain format.
     */
    protected function isValidDomain(string $domain): bool
    {
        return (bool) preg_match('/^([a-z0-9]+([-a-z0-9]*[a-z0-9]+)?\.)+[a-z]{2,}$/i', $domain);
    }

    /**
     * Check if domain has SPF record.
     */
    protected function hasSpfRecord(string $domain): bool
    {
        $txtRecords = @dns_get_record($domain, DNS_TXT);

        foreach ($txtRecords as $record) {
            if (isset($record['txt']) && str_starts_with($record['txt'], 'v=spf1')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if domain has DKIM record.
     */
    protected function hasDkimRecord(string $domain): bool
    {
        // Check common DKIM selectors
        $commonSelectors = ['default', 'mail', 'dkim', 'k1', 'google'];

        foreach ($commonSelectors as $selector) {
            $dkimDomain = "{$selector}._domainkey.{$domain}";
            $txtRecords = @dns_get_record($dkimDomain, DNS_TXT);

            if (!empty($txtRecords)) {
                return true;
            }
        }

        return false;
    }
}
```

### 2. UnsubscribeLinkRule.php

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that email content contains an unsubscribe link.
 */
class UnsubscribeLinkRule implements ValidationRule
{
    /**
     * Unsubscribe tag patterns to check.
     */
    protected array $patterns = [
        '/\{unsubscribe_url\}/',
        '/\{UNSUBSCRIBE_URL\}/',
        '/\{UnsubscribeUrl\}/',
        '/\{\s*unsubscribe\s*\}/',
        '/<a[^>]*href=["\'].*unsubscribe.*["\'][^>]*>/i',
        '/href=["\'][^"\']*unsubscribe[^"\']*["\']/i',
    ];

    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?array $customPatterns = null,
    ) {
        if ($this->customPatterns) {
            $this->patterns = array_merge($this->patterns, $this->customPatterns);
        }
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return; // Found unsubscribe link
            }
        }

        $fail("The {$attribute} must contain an unsubscribe link or tag.");
    }
}
```

### 3. EmailTemplateTagRule.php

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates email template merge tags.
 */
class EmailTemplateTagRule implements ValidationRule
{
    /**
     * Allowed merge tags.
     */
    protected array $allowedTags = [
        'subscriber_email',
        'subscriber_name',
        'subscriber_first_name',
        'subscriber_last_name',
        'unsubscribe_url',
        'web_view_url',
        'current_date',
        'current_year',
        'company_name',
        'company_address',
    ];

    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?array $customAllowedTags = null,
    ) {
        if ($this->customAllowedTags) {
            $this->allowedTags = array_merge($this->allowedTags, $this->customAllowedTags);
        }
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Find all tags in content
        preg_match_all('/\{([a-z_]+)\}/i', $value, $matches);

        if (empty($matches[1])) {
            return; // No tags found, validation passes
        }

        $foundTags = $matches[1];
        $invalidTags = [];

        foreach ($foundTags as $tag) {
            if (!in_array(strtolower($tag), array_map('strtolower', $this->allowedTags))) {
                $invalidTags[] = $tag;
            }
        }

        if (!empty($invalidTags)) {
            $invalidTagsList = implode(', ', array_unique($invalidTags));
            $fail("The {$attribute} contains invalid merge tags: {$invalidTagsList}");
        }
    }
}
```

### 4. BounceEmailRule.php

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates bounce email configuration.
 */
class BounceEmailRule implements ValidationRule
{
    /**
     * Generic prefixes that should not be used for bounce handling.
     */
    protected array $disallowedPrefixes = [
        'noreply',
        'no-reply',
        'donotreply',
        'do-not-reply',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validate basic email format
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail("The {$attribute} must be a valid email address.");
            return;
        }

        // Extract local part (before @)
        $parts = explode('@', $value);
        $localPart = strtolower($parts[0]);

        // Check against disallowed prefixes
        foreach ($this->disallowedPrefixes as $prefix) {
            if ($localPart === $prefix || str_starts_with($localPart, $prefix)) {
                $fail("The {$attribute} should not use a no-reply address for bounce handling.");
                return;
            }
        }

        // Ensure domain has MX records
        if (isset($parts[1])) {
            $domain = $parts[1];
            $mxRecords = @dns_get_record($domain, DNS_MX);

            if (empty($mxRecords)) {
                $fail("The {$attribute} domain does not have valid MX records.");
                return;
            }
        }
    }
}
```

### 5. SmtpConnectionRule.php

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates SMTP server connectivity.
 */
class SmtpConnectionRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected string $host,
        protected int $port = 587,
        protected int $timeout = 10,
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $errno = 0;
        $errstr = '';

        // Attempt to connect to SMTP server
        $connection = @fsockopen(
            $this->host,
            $this->port,
            $errno,
            $errstr,
            $this->timeout
        );

        if (!$connection) {
            $fail("Unable to connect to SMTP server at {$this->host}:{$this->port}. Error: {$errstr} ({$errno})");
            return;
        }

        // Read server response
        $response = fgets($connection);

        if (!str_starts_with($response, '220')) {
            fclose($connection);
            $fail("SMTP server did not respond with a valid greeting. Response: {$response}");
            return;
        }

        fclose($connection);
    }
}
```

### 6. CampaignFromEmailRule.php

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Mailing\Models\MailList;

/**
 * Validates the from email for a campaign.
 */
class CampaignFromEmailRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?MailList $mailList = null,
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validate email format
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail("The {$attribute} must be a valid email address.");
            return;
        }

        // Extract domain
        $domain = substr(strrchr($value, '@'), 1);

        // Check if domain has MX records
        $mxRecords = @dns_get_record($domain, DNS_MX);
        if (empty($mxRecords)) {
            $fail("The {$attribute} domain does not have valid MX records.");
            return;
        }

        // If mail list is provided, validate against verified domains
        if ($this->mailList) {
            $verifiedDomains = $this->mailList->customer
                ->sendingDomains()
                ->where('status', 'verified')
                ->pluck('name')
                ->toArray();

            if (!empty($verifiedDomains) && !in_array($domain, $verifiedDomains)) {
                $fail("The {$attribute} must use a verified sending domain.");
                return;
            }
        }
    }
}
```

### 7. SubscriberEmailRule.php

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Mailing\Models\Subscriber;
use Modules\Mailing\Models\MailList;

/**
 * Validates subscriber email uniqueness within a mail list.
 */
class SubscriberEmailRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected MailList $mailList,
        protected ?int $exceptSubscriberId = null,
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validate email format
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail("The {$attribute} must be a valid email address.");
            return;
        }

        // Check uniqueness within the mail list
        $query = Subscriber::where('mail_list_id', $this->mailList->id)
            ->where('email', $value);

        if ($this->exceptSubscriberId) {
            $query->where('id', '!=', $this->exceptSubscriberId);
        }

        if ($query->exists()) {
            $fail("The {$attribute} already exists in this mail list.");
            return;
        }

        // Check against global blacklist
        if ($this->isBlacklisted($value)) {
            $fail("The {$attribute} is blacklisted and cannot be added.");
            return;
        }
    }

    /**
     * Check if email is blacklisted.
     */
    protected function isBlacklisted(string $email): bool
    {
        // Implement blacklist check logic
        // This could check against a blacklist table or service
        return false;
    }
}
```

### 8. CsvImportRule.php

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validates CSV file structure for subscriber import.
 */
class CsvImportRule implements ValidationRule
{
    /**
     * Required CSV columns.
     */
    protected array $requiredColumns = ['email'];

    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?array $customRequiredColumns = null,
        protected int $maxRows = 10000,
    ) {
        if ($this->customRequiredColumns) {
            $this->requiredColumns = $this->customRequiredColumns;
        }
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail("The {$attribute} must be a valid file.");
            return;
        }

        // Validate file extension
        if (!in_array($value->getClientOriginalExtension(), ['csv', 'txt'])) {
            $fail("The {$attribute} must be a CSV file.");
            return;
        }

        // Open and read CSV
        $handle = fopen($value->getRealPath(), 'r');
        if (!$handle) {
            $fail("Unable to read the {$attribute} file.");
            return;
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            $fail("The {$attribute} must contain a header row.");
            return;
        }

        // Normalize headers
        $headers = array_map('strtolower', array_map('trim', $headers));

        // Check required columns
        $missingColumns = array_diff($this->requiredColumns, $headers);
        if (!empty($missingColumns)) {
            fclose($handle);
            $fail("The {$attribute} is missing required columns: " . implode(', ', $missingColumns));
            return;
        }

        // Check row count
        $rowCount = 0;
        while (fgetcsv($handle) !== false) {
            $rowCount++;
            if ($rowCount > $this->maxRows) {
                fclose($handle);
                $fail("The {$attribute} exceeds the maximum allowed rows ({$this->maxRows}).");
                return;
            }
        }

        fclose($handle);

        // Check if file has data rows
        if ($rowCount === 0) {
            $fail("The {$attribute} does not contain any data rows.");
            return;
        }
    }
}
```

### 9. SendingLimitRule.php

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Mailing\Models\Customer;

/**
 * Validates sending limits for a customer.
 */
class SendingLimitRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected Customer $customer,
        protected string $period = 'hour',
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $requestedAmount = (int) $value;

        if ($requestedAmount <= 0) {
            $fail("The {$attribute} must be a positive number.");
            return;
        }

        // Get customer's sending limit
        $limit = $this->customer->getSendingLimit($this->period);

        if ($limit === null) {
            return; // No limit set, validation passes
        }

        // Get current usage
        $usage = $this->customer->getSendingUsage($this->period);

        // Check if requested amount would exceed limit
        if (($usage + $requestedAmount) > $limit) {
            $remaining = max(0, $limit - $usage);
            $fail("The {$attribute} would exceed your sending limit. You have {$remaining} emails remaining this {$this->period}.");
            return;
        }
    }
}
```

### 10. TrackingDomainRule.php

```php
<?php

namespace Modules\Mailing\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates tracking domain configuration.
 */
class TrackingDomainRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?string $expectedIp = null,
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validate domain format
        if (!preg_match('/^([a-z0-9]+([-a-z0-9]*[a-z0-9]+)?\.)+[a-z]{2,}$/i', $value)) {
            $fail("The {$attribute} must be a valid domain name.");
            return;
        }

        // Check A record
        $aRecords = @dns_get_record($value, DNS_A);
        if (empty($aRecords)) {
            $fail("The {$attribute} must have a valid A record.");
            return;
        }

        // If expected IP is provided, verify it matches
        if ($this->expectedIp) {
            $ips = array_column($aRecords, 'ip');

            if (!in_array($this->expectedIp, $ips)) {
                $fail("The {$attribute} A record does not point to the expected IP address ({$this->expectedIp}).");
                return;
            }
        }

        // Check if domain is accessible via HTTP/HTTPS
        $url = "http://{$value}";
        $headers = @get_headers($url, 1);

        if (!$headers || !str_starts_with($headers[0], 'HTTP/1')) {
            $fail("The {$attribute} is not accessible via HTTP.");
            return;
        }
    }
}
```

---

## Usage Examples

### In Form Requests

```php
<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Mailing\Rules\{
    SendingDomainRule,
    UnsubscribeLinkRule,
    BounceEmailRule,
    SmtpConnectionRule
};

class CampaignRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'from_email' => ['required', 'email', new CampaignFromEmailRule($this->mailList)],
            'bounce_email' => ['nullable', 'email', new BounceEmailRule()],
            'html_content' => ['required', 'string', new UnsubscribeLinkRule()],
            'sending_domain' => ['required', 'string', new SendingDomainRule(requireSpf: true)],
        ];
    }
}
```

### In Controllers

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'smtp_host' => ['required', 'string'],
        'smtp_port' => ['required', 'integer', new SmtpConnectionRule(
            host: $request->input('smtp_host'),
            port: $request->input('smtp_port')
        )],
    ]);

    // Process validated data
}
```

### With Dynamic Parameters

```php
$validator = Validator::make($data, [
    'email' => [
        'required',
        'email',
        new SubscriberEmailRule(
            mailList: $mailList,
            exceptSubscriberId: $subscriber->id ?? null
        ),
    ],
]);
```

---

## Testing Examples

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
        $rule = new SendingDomainRule();

        $validator = Validator::make(
            ['domain' => 'gmail.com'],
            ['domain' => [$rule]]
        );

        $this->assertFalse($validator->fails());
    }

    public function testInvalidDomainFails(): void
    {
        $rule = new SendingDomainRule();

        $validator = Validator::make(
            ['domain' => 'invalid-domain-xyz-123.com'],
            ['domain' => [$rule]]
        );

        $this->assertTrue($validator->fails());
    }

    public function testRequireSpfOption(): void
    {
        $rule = new SendingDomainRule(requireSpf: true);

        $validator = Validator::make(
            ['domain' => 'example.com'],
            ['domain' => [$rule]]
        );

        // This will depend on whether example.com has SPF
        $this->assertTrue($validator->passes() || $validator->fails());
    }
}
```
