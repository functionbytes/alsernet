# Acelle Mail - Helper Functions Analysis

**Date:** 2026-01-29
**Source:** `/Users/functionbytes/Function/Coding/acelle/app/Helpers/`
**Purpose:** Document all helper functions available in Acelle for migration reference

---

## Table of Contents

1. [Overview](#overview)
2. [LicenseHelper Class](#licensehelper-class)
3. [Global Helper Functions (helpers.php)](#global-helper-functions-helpersphp)
4. [Namespaced Helper Functions (namespaced_helpers.php)](#namespaced-helper-functions-namespaced_helpersphp)
5. [Critical Functions for Migration](#critical-functions-for-migration)
6. [Migration Recommendations](#migration-recommendations)

---

## Overview

Acelle Mail includes three helper files providing extensive utility functions:

| File | Type | Functions | Purpose |
|------|------|-----------|---------|
| `LicenseHelper.php` | Class | 7 methods | License validation and management |
| `helpers.php` | Global functions | 75+ functions | Core utilities, formatting, system operations |
| `namespaced_helpers.php` | Namespaced functions | 20+ functions | Advanced utilities, file operations, SPF checking |

**Total Functions:** ~100+ helper functions

---

## LicenseHelper Class

**Namespace:** `Acelle\Helpers\LicenseHelper`
**Purpose:** License verification and management (specific to Acelle Mail commercial product)

### Methods

```php
// License validation
public static function getLicense($license): array
public static function updateLicense($licenseCode): void
public static function removeLicense(): void
public static function getCurrentLicense(): ?License
public static function hasActiveLicense(): bool
public static function refreshLicense(): void
```

### Constants

```php
const TYPE_REGULAR = 'regular';
const TYPE_EXTENDED = 'extended';
const STATUS_VALID = 'valid';
const STATUS_EXPIRED = 'expired';
```

### Migration Status

**🔴 DO NOT MIGRATE** - This is Acelle-specific commercial licensing code. Not applicable to Alsernet system.

---

## Global Helper Functions (helpers.php)

**Total:** 75+ global functions
**File Location:** `/app/Helpers/helpers.php`

### 1. Database Helpers

#### `table($name): string`
Get full table name with database prefix.

```php
table('users') // Returns: prefix_users
```

**Migration:** ✅ Use Laravel's native `DB::getTablePrefix() . $name`

---

#### `quote($value): string`
Quote value for SQL injection (basic).

```php
quote('John') // Returns: 'John'
```

**Migration:** 🔴 **DO NOT USE** - Use Laravel query builder or PDO `quote()` instead

---

#### `db_quote($value): string`
Properly quote value using PDO.

```php
db_quote("O'Brien") // Returns: 'O\'Brien'
```

**Migration:** ✅ Use `DB::connection()->getPdo()->quote($value)`

---

#### `distinctCount($builder, $column = null, $method = 'group'): int`
Optimized distinct count using subquery.

```php
distinctCount($query, 'email', 'group')
```

**Migration:** ✅ Migrate - Performance-critical for large datasets

---

#### `optimized_paginate($builder, $perPage, $columns, $pageName, $page, $total)`
Custom pagination with pre-calculated totals.

```php
optimized_paginate($query, 15, ['*'], 'page', 1, 5000)
```

**Migration:** ⚠️ Consider - Only if performance issues with standard Laravel pagination

---

### 2. Path & URL Helpers

#### `join_paths(...$paths): string`
Join filesystem paths safely.

```php
join_paths('/var', 'www', 'app') // Returns: /var/www/app
```

**Migration:** ✅ Migrate - Very useful for cross-platform path handling

---

#### `join_url(...$parts): string`
Join URL parts safely.

```php
join_url('https://example.com', 'api', 'users') // Returns: https://example.com/api/users
```

**Migration:** ✅ Migrate - Essential for URL construction

---

### 3. Array Helpers

#### `array_unique_by($array, $callback): array`
Get unique array based on custom condition.

```php
array_unique_by($users, fn($u) => $u->email)
```

**Migration:** ✅ Migrate - Common use case

---

#### `each_batch($array, $batchSize, $skipHeader, $callback): void`
Process array in batches with callback.

```php
each_batch($records, 100, true, function($batch) {
    // Process batch
});
```

**Migration:** ✅ Migrate - Critical for bulk operations

---

#### `filterSearchArray($items, $keyword): array`
Search array items by keywords with ranking.

```php
filterSearchArray($templates, 'newsletter')
```

**Migration:** ⚠️ Consider - If search functionality is needed

---

### 4. Localization & Formatting

#### `language_code(): string`
Get current language code from session/settings.

```php
language_code() // Returns: 'en' or 'es'
```

**Migration:** ✅ Migrate - Essential for multi-language support

---

#### `get_localization_config($name, $locale): mixed`
Get localization setting for specific locale.

```php
get_localization_config('number_precision', 'es')
```

**Migration:** ✅ Migrate - Required for proper formatting

---

#### `get_datetime_format($name, $locale): string`
Get datetime format for locale.

```php
get_datetime_format('date_format', 'en') // Returns: 'm/d/Y'
```

**Migration:** ✅ Migrate - Date formatting is critical

---

#### `format_datetime($datetime, $name, $locale): ?string`
Format datetime using locale settings.

```php
format_datetime($carbon, 'date_format', 'en')
```

**Migration:** ✅ Migrate - Replace with Laravel's Carbon localization

---

#### `number_to_percentage($number, $precision = 2): string`
Format number as percentage.

```php
number_to_percentage(0.1234, 2) // Returns: 12.34%
```

**Migration:** ✅ Migrate - Common formatting need

---

#### `number_with_delimiter($number, $precision, $separator, $locale): string`
Format number with locale-specific delimiters.

```php
number_with_delimiter(1234.56, 2, ',', 'es') // Returns: 1.234,56
```

**Migration:** ✅ Migrate - Essential for international users

---

#### `format_price($price, $format = '{PRICE}', $html = false): string`
Format price with currency template.

```php
format_price(99.99, '${PRICE}', true) // Returns: $ <span class="p-amount">99.99</span>
```

**Migration:** ✅ Migrate - E-commerce essential

---

#### `formatSizeUnits($bytes): string`
Convert bytes to human-readable format.

```php
formatSizeUnits(1048576) // Returns: 1.00 MB
```

**Migration:** ✅ Migrate - File upload displays

---

### 5. Email Helpers

#### `extract_email($str): ?string`
Extract email from "Name <email@domain.com>" format.

```php
extract_email('John Doe <john@example.com>') // Returns: john@example.com
```

**Migration:** ✅ **CRITICAL** - Essential for email processing

---

#### `extract_name($str): string`
Extract name from email string.

```php
extract_name('John Doe <john@example.com>') // Returns: John Doe
```

**Migration:** ✅ **CRITICAL** - Essential for email processing

---

#### `extract_domain($email): string`
Extract domain from email address.

```php
extract_domain('john@example.com') // Returns: example.com
```

**Migration:** ✅ **CRITICAL** - Domain validation, SPF checks

---

#### `checkEmail($email): bool`
Validate email format.

```php
checkEmail('john@example.com') // Returns: true
```

**Migration:** ✅ Migrate - Use Laravel validation instead

---

#### `doublequote($str): string`
Add double quotes to string.

```php
doublequote('John Doe') // Returns: "John Doe"
```

**Migration:** ✅ Migrate - Email header formatting

---

### 6. System & Environment

#### `exec_enabled(): bool`
Check if exec() function is available.

```php
if (exec_enabled()) {
    exec('ls');
}
```

**Migration:** ✅ Migrate - System command checks

---

#### `func_enabled($name): bool`
Check if specific function is enabled.

```php
func_enabled('shell_exec')
```

**Migration:** ✅ Migrate - Security checks

---

#### `app_version(): string`
Get application version from VERSION file.

```php
app_version() // Returns: 1.0.0
```

**Migration:** ✅ Migrate - Version tracking

---

#### `isInitiated(): bool`
Check if app installation is complete.

```php
if (!isInitiated()) {
    redirect('/install');
}
```

**Migration:** ⚠️ Consider - If installation wizard is needed

---

#### `isSiteDemo(): bool`
Check if site is in demo mode.

```php
if (isSiteDemo()) {
    abort(403, 'Feature disabled in demo');
}
```

**Migration:** ⚠️ Consider - If demo mode is required

---

#### `artisan_migrate(): void`
Run artisan migrate with force flag.

```php
artisan_migrate()
```

**Migration:** 🔴 **AVOID** - Use Laravel's native `Artisan::call('migrate', ['--force' => true])`

---

### 7. Configuration & Settings

#### `demo_auth(): array`
Get demo authentication credentials.

```php
demo_auth() // Returns: ['email' => 'demo@...', 'password' => '...']
```

**Migration:** 🔴 DO NOT MIGRATE - Demo-specific

---

#### `get_app_identity(): string`
Get unique app identifier based on APP_KEY.

```php
get_app_identity() // Returns: md5 hash
```

**Migration:** ⚠️ Consider - If app fingerprinting is needed

---

#### `quoteDotEnvValue($value): string`
Quote .env values containing special characters.

```php
quoteDotEnvValue('pass#word') // Returns: "pass#word"
```

**Migration:** ⚠️ Consider - If programmatic .env editing is needed

---

#### `app_profile($key): mixed`
Get configuration from profile-specific config file.

```php
app_profile('feature_flags.advanced_analytics')
```

**Migration:** ⚠️ Consider - If multi-tenant profiles are used

---

#### `get_app_name(): string`
Get application name from settings or config.

```php
get_app_name() // Returns: Alsernet or custom name
```

**Migration:** ✅ Migrate - Branding support

---

### 8. String & Utility

#### `generateRandomString($length = 10): string`
Generate random alphanumeric string.

```php
generateRandomString(16) // Returns: a7K9xM2pQ4rT8sW1
```

**Migration:** ✅ Migrate - Use `Str::random($length)` instead

---

#### `strip_tags_only($text, $allowedTags = []): string`
Strip only specific HTML tags.

```php
strip_tags_only($html, ['script', 'iframe'])
```

**Migration:** ⚠️ Consider - Security/sanitization

---

#### `rand_item($arr): mixed`
Get random item from array.

```php
rand_item(['red', 'blue', 'green'])
```

**Migration:** ⚠️ Consider - Use `Arr::random($array)` instead

---

### 9. Template & RSS

#### `parseRss($config): string`
Parse RSS feed and apply templates.

```php
parseRss(['url' => 'https://...', 'size' => 10, 'templates' => [...]])
```

**Migration:** 🔴 DO NOT MIGRATE - Complex RSS parsing (unless RSS feature is required)

---

#### `parseRssTemplate($template, $feedData): string`
Parse RSS template with data.

```php
parseRssTemplate('@feed_title - @feed_description', $data)
```

**Migration:** 🔴 DO NOT MIGRATE - RSS-specific

---

#### `rssTags(): array`
Get available RSS template tags.

```php
rssTags() // Returns: ['feed' => [...], 'item' => [...]]
```

**Migration:** 🔴 DO NOT MIGRATE - RSS-specific

---

### 10. Advanced Helpers

#### `cursorIterate($query, $orderBy, $size, $callback): void`
Iterate through query using cursor pagination.

```php
cursorIterate($query, 'id', 100, function($items, $page) {
    // Process items
});
```

**Migration:** ✅ **CRITICAL** - Memory-efficient bulk processing

---

#### `makeInlineCss($html, $cssFiles): string`
Convert external CSS to inline styles.

```php
makeInlineCss($emailHtml, ['/path/to/style.css'])
```

**Migration:** ✅ **CRITICAL** - Email rendering requires inline CSS

---

#### `resize_crop_image($max_width, $max_height, $source, $dst, $quality): void`
Resize and crop image by center.

```php
resize_crop_image(800, 600, $source, $destination, 80)
```

**Migration:** ⚠️ Consider - Use Intervention/Image instead

---

### 11. Locale & Theme

#### `getFullCodeByLanguageCode($languageCode): ?string`
Get full locale code from language code.

```php
getFullCodeByLanguageCode('es') // Returns: es-ES
```

**Migration:** ✅ Migrate - Locale resolution

---

#### `getDefaultLogoUrl($type): string`
Get default logo URL (light/dark).

```php
getDefaultLogoUrl('light') // Returns: /assets/logo-light.png
```

**Migration:** ⚠️ Consider - If theme support is needed

---

#### `getSiteLogoUrl($type): string`
Get site logo URL from settings.

```php
getSiteLogoUrl('dark')
```

**Migration:** ⚠️ Consider - If customizable logos are needed

---

#### `getThemeColor($theme): string`
Get theme color code.

```php
getThemeColor('blue') // Returns: rgba(9, 22, 28, 0.9)
```

**Migration:** 🔴 DO NOT MIGRATE - Theme-specific

---

#### `getThemeMode($mode, $auto): string`
Get effective theme mode.

```php
getThemeMode('auto', 'light') // Returns: light or dark
```

**Migration:** 🔴 DO NOT MIGRATE - Theme-specific

---

#### `getLogoMode($mode, $scheme, $daylight): string`
Get logo variant based on theme.

```php
getLogoMode('auto', 'white', 'light')
```

**Migration:** 🔴 DO NOT MIGRATE - Theme-specific

---

### 12. Time & Period

#### `getPeriodEndsAt($startDate, $amount, $unit): Carbon`
Calculate period end date.

```php
getPeriodEndsAt(now(), 3, 'month') // Returns: Carbon 3 months from now
```

**Migration:** ⚠️ Consider - Subscription/billing logic

---

### 13. Temporary/Legacy

#### `get_tmp_primary_server(): ?SendingServer`
Get first active sending server (temporary).

```php
$server = get_tmp_primary_server()
```

**Migration:** 🔴 DO NOT MIGRATE - Marked as temporary

---

#### `get_tmp_quota($customer, $name): string`
Get customer quota setting (temporary).

```php
get_tmp_quota($customer, 'email_max')
```

**Migration:** 🔴 DO NOT MIGRATE - Marked as temporary

---

## Namespaced Helper Functions (namespaced_helpers.php)

**Namespace:** `Acelle\Helpers\`
**Total:** 20+ functions
**File Location:** `/app/Helpers/namespaced_helpers.php`

### 1. Public Path Generation

#### `generatePublicPath($absPath, $withHost = false): string`
Generate public-accessible URL for storage files.

```php
generatePublicPath('/storage/app/avatars/user.jpg', true)
// Returns: https://example.com/p/assets/encoded_dirname/user.jpg
```

**Migration:** ✅ **CRITICAL** - Required for serving protected files through Laravel

---

#### `getAppSubdirectory(): ?string`
Get application subdirectory from APP_URL.

```php
getAppSubdirectory() // Returns: 'subfolder' or null
```

**Migration:** ✅ Migrate - Subdirectory installations

---

#### `getAppHost(): string`
Get application host with scheme, host, and port.

```php
getAppHost() // Returns: https://example.com:8080
```

**Migration:** ✅ Migrate - URL generation

---

### 2. File Operations

#### `pcopy($src, $dst): void`
Copy file/directory with automatic parent directory creation.

```php
pcopy('/source/file.txt', '/dest/file.txt')
```

**Migration:** ✅ Migrate - Safer than native copy

---

#### `ptouch($filepath): void`
Create file with automatic parent directory creation.

```php
ptouch('/path/to/new/file.txt')
```

**Migration:** ✅ Migrate - Directory-safe file creation

---

### 3. Translation & Localization

#### `updateTranslationFile($target, $source, $overwrite, $delete, $sort): void`
Update translation files by merging source and target.

```php
updateTranslationFile(
    $targetFile,
    $sourceFile,
    overwriteTargetPhrases: false,
    deleteTargetKeys: true,
    sort: true
)
```

**Migration:** ⚠️ Consider - If custom translation management is needed

---

### 4. XML Parsing

#### `xml_to_array(SimpleXMLElement $xml): array`
Convert XML to associative array.

```php
$xml = simplexml_load_string($xmlString);
$array = Acelle\Helpers\xml_to_array($xml);
```

**Migration:** ⚠️ Consider - If XML parsing is needed (RSS, APIs)

---

### 5. SPF Checking

#### `spfcheck($ipOrHostname, $domain): int`
Check SPF record for IP/hostname and domain.

```php
$result = spfcheck('192.168.1.1', 'example.com');
if ($result === SPFCheck::RESULT_PASS) {
    // Authorized
}
```

**Migration:** ✅ **CRITICAL** - Email authentication validation

**Dependencies:** `mika56/spf-check` package

---

### 6. Customer & Subscription

#### `forceAddCustomerToUnlimitedPlan($customer): void`
Add customer to unlimited subscription plan.

```php
forceAddCustomerToUnlimitedPlan($customer)
```

**Migration:** 🔴 DO NOT MIGRATE - Acelle subscription-specific

---

### 7. Validation

#### `isValidPublicHostnameOrIpAddress($host): bool`
Validate if hostname/IP is publicly accessible.

```php
isValidPublicHostnameOrIpAddress('example.com') // true
isValidPublicHostnameOrIpAddress('localhost') // false
```

**Migration:** ✅ Migrate - Server configuration validation

---

### 8. Environment Configuration

#### `write_env($key, $value, $overwrite = true): void`
Write environment variable to .env file.

```php
write_env('MAIL_DRIVER', 'smtp', true)
```

**Migration:** ⚠️ **USE WITH CAUTION** - Modifies .env file programmatically

---

#### `write_envs($params): void`
Write multiple environment variables.

```php
write_envs([
    'MAIL_DRIVER' => 'smtp',
    'MAIL_HOST' => 'smtp.gmail.com'
])
```

**Migration:** ⚠️ **USE WITH CAUTION** - Batch .env updates

---

#### `reset_app_url($force = false): void`
Reset APP_URL in .env to current URL.

```php
reset_app_url(force: true)
```

**Migration:** ⚠️ Consider - Auto-detect APP_URL during setup

---

#### `load_env_from_file($path): array`
Load raw .env file contents as associative array.

```php
$envVars = load_env_from_file(base_path('.env'));
```

**Migration:** ⚠️ Consider - .env inspection/validation

---

### 9. HTTP Utilities

#### `url_get_contents_ssl_safe($url): string`
Fetch URL contents with SSL verification disabled.

```php
$content = url_get_contents_ssl_safe('https://example.com/feed.xml')
```

**Migration:** 🔴 **SECURITY RISK** - Use Guzzle with proper SSL verification instead

---

#### `is_non_web_link($url): bool`
Check if URL is a non-HTTP link (mailto:, tel:, etc.).

```php
is_non_web_link('mailto:john@example.com') // true
is_non_web_link('https://example.com') // false
```

**Migration:** ✅ Migrate - Link validation

---

### 10. Rate & Credit Tracking

#### `execute_with_limits($rateTrackers, $creditTrackers, $task): void`
Execute task with rate limiting and credit tracking.

```php
execute_with_limits(
    rateTrackers: [$sendingRateTracker],
    creditTrackers: [$emailCreditTracker],
    task: function() {
        // Send email
    }
)
```

**Migration:** ⚠️ Consider - If rate limiting/credit system is needed

**Note:** Automatically rolls back credits on failure

---

## Critical Functions for Migration

### 🔴 MUST MIGRATE (High Priority)

These functions are essential for core mailing functionality:

1. **Email Processing**
   - `extract_email()` - Parse email addresses
   - `extract_name()` - Parse sender/recipient names
   - `extract_domain()` - Domain extraction for validation
   - `doublequote()` - Email header formatting

2. **File & Path Management**
   - `join_paths()` - Cross-platform path handling
   - `join_url()` - URL construction
   - `generatePublicPath()` - Serve protected files publicly
   - `pcopy()` / `ptouch()` - Safe file operations

3. **Email Rendering**
   - `makeInlineCss()` - Convert CSS to inline (critical for email clients)

4. **Bulk Processing**
   - `cursorIterate()` - Memory-efficient pagination
   - `each_batch()` - Batch processing with callbacks
   - `distinctCount()` - Optimized distinct queries

5. **Localization**
   - `language_code()` - Current language detection
   - `get_datetime_format()` - Locale-aware date formatting
   - `number_with_delimiter()` - Number formatting

6. **SPF Validation**
   - `spfcheck()` - Email authentication

---

### ⚠️ CONSIDER MIGRATING (Medium Priority)

Useful but can be replaced with Laravel alternatives:

1. **Formatting**
   - `number_to_percentage()` - Can use `number_format()`
   - `format_price()` - Can use `number_format()` + custom template
   - `formatSizeUnits()` - Can use Laravel helper or package

2. **Array Operations**
   - `array_unique_by()` - Can use `Collection::unique()`
   - `filterSearchArray()` - Can use `Collection::filter()`

3. **Configuration**
   - `write_env()` / `write_envs()` - Use with caution
   - `load_env_from_file()` - For .env inspection

4. **Validation**
   - `isValidPublicHostnameOrIpAddress()` - Server validation
   - `is_non_web_link()` - Link type detection

---

### 🔴 DO NOT MIGRATE (Low Priority / Deprecated)

Skip these functions:

1. **License-Related**
   - All `LicenseHelper` methods - Commercial product specific

2. **Demo/Temporary**
   - `isSiteDemo()` - Demo mode
   - `demo_auth()` - Demo credentials
   - `get_tmp_primary_server()` - Marked temporary
   - `get_tmp_quota()` - Marked temporary

3. **RSS-Specific**
   - `parseRss()` / `parseRssTemplate()` / `rssTags()` - Unless RSS feature is needed

4. **Theme-Specific**
   - `getThemeColor()` / `getThemeMode()` / `getLogoMode()` - UI-specific

5. **Security Risks**
   - `url_get_contents_ssl_safe()` - Disables SSL verification
   - `quote()` - Basic SQL injection protection (use query builder)

6. **Deprecated Laravel**
   - `artisan_migrate()` - Use `Artisan::call()`
   - `generateRandomString()` - Use `Str::random()`

---

## Migration Recommendations

### Phase 1: Critical Email Functions (Week 1)

Create `/app/Helpers/EmailHelpers.php`:

```php
<?php

namespace App\Helpers;

class EmailHelpers
{
    public static function extractEmail(string $str): ?string
    {
        // Migrate extract_email()
    }

    public static function extractName(string $str): string
    {
        // Migrate extract_name()
    }

    public static function extractDomain(string $email): string
    {
        // Migrate extract_domain()
    }

    public static function doublequote(string $str): string
    {
        // Migrate doublequote()
    }

    public static function makeInlineCss(string $html, array $cssFiles): string
    {
        // Migrate makeInlineCss() - uses Acelle\Library\InlineStyleWrapper
    }
}
```

---

### Phase 2: Path & File Helpers (Week 1)

Create `/app/Helpers/PathHelpers.php`:

```php
<?php

namespace App\Helpers;

class PathHelpers
{
    public static function joinPaths(...$paths): string
    {
        // Migrate join_paths()
    }

    public static function joinUrl(...$parts): string
    {
        // Migrate join_url()
    }

    public static function generatePublicPath(string $absPath, bool $withHost = false): string
    {
        // Migrate generatePublicPath()
    }

    public static function pcopy(string $src, string $dst): void
    {
        // Migrate pcopy()
    }

    public static function ptouch(string $filepath): void
    {
        // Migrate ptouch()
    }
}
```

---

### Phase 3: Localization Helpers (Week 2)

Create `/app/Helpers/LocalizationHelpers.php`:

```php
<?php

namespace App\Helpers;

use Carbon\Carbon;

class LocalizationHelpers
{
    public static function languageCode(): string
    {
        // Migrate language_code()
    }

    public static function getDatetimeFormat(string $name, string $locale): string
    {
        // Migrate get_datetime_format()
    }

    public static function formatDatetime(?Carbon $datetime, string $name, string $locale): ?string
    {
        // Migrate format_datetime()
    }

    public static function numberWithDelimiter($number, ?int $precision = null, ?string $separator = null, ?string $locale = null): string
    {
        // Migrate number_with_delimiter()
    }

    public static function numberToPercentage($number, int $precision = 2): string
    {
        // Migrate number_to_percentage()
    }
}
```

---

### Phase 4: Database & Bulk Processing (Week 2)

Create `/app/Helpers/DatabaseHelpers.php`:

```php
<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Closure;

class DatabaseHelpers
{
    public static function distinctCount(Builder $builder, ?string $column = null, string $method = 'group'): int
    {
        // Migrate distinctCount()
    }

    public static function cursorIterate($query, string $orderBy, int $size, Closure $callback): void
    {
        // Migrate cursorIterate()
    }

    public static function eachBatch(array $array, int $batchSize, bool $skipHeader, Closure $callback): void
    {
        // Migrate each_batch()
    }

    public static function optimizedPaginate(
        Builder $builder,
        int $perPage = 15,
        ?array $columns = null,
        ?string $pageName = null,
        ?int $page = null,
        ?int $total = null
    ) {
        // Migrate optimized_paginate() - use with caution
    }
}
```

---

### Phase 5: SPF & Validation (Week 3)

Create `/app/Helpers/ValidationHelpers.php`:

```php
<?php

namespace App\Helpers;

use Mika56\SPFCheck\SPFCheck;
use Mika56\SPFCheck\DNSRecordGetterDirect;
use Mika56\SPFCheck\DNSRecordGetter;

class ValidationHelpers
{
    public static function spfcheck(string $ipOrHostname, string $domain): int
    {
        // Migrate spfcheck()
        // Requires: composer require mika56/spf-check
    }

    public static function isValidPublicHostnameOrIpAddress(string $host): bool
    {
        // Migrate isValidPublicHostnameOrIpAddress()
    }

    public static function isNonWebLink(string $url): bool
    {
        // Migrate is_non_web_link()
    }
}
```

---

### Phase 6: Optional Helpers (Week 3-4)

Create additional helper files as needed:

- `/app/Helpers/ArrayHelpers.php` - Array utilities
- `/app/Helpers/FormattingHelpers.php` - Price, size formatting
- `/app/Helpers/SystemHelpers.php` - System checks (exec_enabled, etc.)
- `/app/Helpers/ConfigHelpers.php` - Environment management

---

## Testing Strategy

### Unit Tests

Create tests for each migrated helper function:

```php
// tests/Unit/Helpers/EmailHelpersTest.php
<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\EmailHelpers;

class EmailHelpersTest extends TestCase
{
    public function test_extract_email_from_formatted_string()
    {
        $result = EmailHelpers::extractEmail('John Doe <john@example.com>');
        $this->assertEquals('john@example.com', $result);
    }

    public function test_extract_name_from_formatted_string()
    {
        $result = EmailHelpers::extractName('John Doe <john@example.com>');
        $this->assertEquals('John Doe', $result);
    }

    public function test_extract_domain_from_email()
    {
        $result = EmailHelpers::extractDomain('john@example.com');
        $this->assertEquals('example.com', $result);
    }
}
```

---

## Dependencies to Install

Some helper functions require external packages:

```bash
# SPF checking
composer require mika56/spf-check

# Inline CSS for emails (if not already installed)
composer require pelago/emogrifier

# XML parsing (usually included in PHP)
# php-xml extension required
```

---

## Summary

| Category | Functions | Priority | Estimated Migration Time |
|----------|-----------|----------|--------------------------|
| Email Processing | 5 functions | 🔴 Critical | 2-3 days |
| Path & File | 5 functions | 🔴 Critical | 1-2 days |
| Localization | 6 functions | 🔴 Critical | 2-3 days |
| Database & Bulk | 4 functions | 🔴 Critical | 2-3 days |
| SPF & Validation | 3 functions | 🔴 Critical | 1-2 days |
| Optional Helpers | 20+ functions | ⚠️ Optional | 5-7 days |
| **Total** | **43+ functions** | - | **2-3 weeks** |

---

## Next Steps

1. ✅ Review this document with team
2. ✅ Prioritize functions based on immediate needs
3. ✅ Create helper classes in `/app/Helpers/` namespace
4. ✅ Write comprehensive unit tests
5. ✅ Document usage in `/docs/backend/helpers/`
6. ✅ Update code to use new helper classes
7. ✅ Remove Acelle-specific dependencies

---

**Generated:** 2026-01-29
**Analyst:** Claude Code
**Status:** Complete
