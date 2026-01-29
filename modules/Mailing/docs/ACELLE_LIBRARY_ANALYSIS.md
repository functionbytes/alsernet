# Acelle Library Directory Analysis

**Generated**: 2026-01-29
**Location**: `/Users/functionbytes/Function/Coding/acelle/app/Library/`
**Purpose**: Comprehensive documentation of Acelle Mail's core library services, utilities, and integration classes

---

## Table of Contents

1. [Overview](#overview)
2. [Core Utilities](#core-utilities)
3. [Campaign Management](#campaign-management)
4. [Rate Limiting & Tracking](#rate-limiting--tracking)
5. [Email Processing Pipeline](#email-processing-pipeline)
6. [External Service Integrations](#external-service-integrations)
7. [Traits & Behaviors](#traits--behaviors)
8. [Contracts & Interfaces](#contracts--interfaces)
9. [Exception Classes](#exception-classes)
10. [Storage & File Management](#storage--file-management)
11. [Automation System](#automation-system)
12. [Critical Classes Summary](#critical-classes-summary)

---

## Overview

The Acelle Library directory contains the core business logic and utility classes that power the email marketing platform. This analysis covers ~40 classes across multiple subdirectories, focusing on services critical for email campaigns, automation, rate limiting, and third-party integrations.

### Directory Structure

```
Library/
├── Automation/          # Campaign automation workflow components
├── Contracts/           # Interface definitions
├── Exception/           # Custom exception classes
├── Facades/             # Laravel facades
├── HtmlHandler/         # Email HTML processing pipeline
├── JsonModel/           # JSON data models
├── Lazada/              # Lazada marketplace integration
├── Notification/        # System notification handlers
├── SendingServer/       # Email server interfaces
├── Storage/             # File storage abstractions
├── Traits/              # Reusable behaviors
└── *.php               # Core utility classes
```

---

## Core Utilities

### Tool.php
**Purpose**: Miscellaneous helper methods for common operations
**Location**: `Library/Tool.php`
**Key Features**:

- **File Operations**:
  - `xcopy($source, $dest, $permissions)` - Recursive file/folder copying with symlink support
  - `xdelete($file)` - Safe file/directory deletion
  - `zip($folder, $zipfile)` - Create ZIP archives from directories

- **Timezone Management**:
  - `allTimeZones()` - Get all PHP timezones with GMT offsets
  - `getTimezoneSelectOptions()` - Format timezones for dropdown selects

- **Date/Time Helpers**:
  - `dayOfWeekSelectOptions()` - Weekday options for schedulers
  - `monthSelectOptions()` - Month options
  - `weekSelectOptions()` - Week-of-month options
  - `timeUnitOptions()` - Time unit options (minute, hour, day, etc.)

- **System Configuration**:
  - `checkPHPBinPath($path)` - Validate PHP binary executable paths
  - `cronjobUpdateController($request, $controller)` - Manage cron job configuration
  - `availableSystemBackgroundMethodSelectOptions()` - Background job method options

- **Validation**:
  - `isValidEmail($email)` - Email validation using PHP filters
  - `checkReCaptcha($request)` - Google reCAPTCHA verification

- **File Upload**:
  - `maxFileUploadInBytes()` - Calculate max upload size from PHP ini settings
  - `returnBytes($val)` - Convert human-readable sizes to bytes

- **Utilities**:
  - `getPluralPrase($phrase, $value)` - Simple pluralization
  - `getDirectorySize($path)` - Calculate directory size recursively
  - `format_price($price, $format)` - Price formatting

**Usage Example**:
```php
// Copy template files
Tool::xcopy($sourceDir, $destDir, 0755);

// Validate email
if (Tool::isValidEmail($email)) {
    // Process email
}

// Get timezone options for form
$timezones = Tool::getTimezoneSelectOptions();
```

---

### StringHelper.php
**Purpose**: String manipulation and encoding utilities
**Location**: `Library/StringHelper.php`
**Key Features**:

- **Encoding**:
  - `base64UrlEncode($string)` - URL-safe base64 encoding (replaces +/= with -_)
  - `base64UrlDecode($string)` - Decode URL-safe base64
  - `removeUTF8BOM($text)` - Remove UTF-8 Byte Order Mark

- **Message ID Management**:
  - `generateMessageId($domain, $test)` - Create unique email message IDs
    - Format: `{timestamp}{random}.{uniqid}@{domain}`
    - Test mode: Uses `0000000000000` instead of uniqid
  - `isTestMessageId($messageId)` - Detect test message IDs
  - `cleanupMessageId($msgId)` - Remove angle brackets and whitespace
  - `extractSendGridMessageId($smtpId)` - Parse SendGrid message IDs

- **Email Utilities**:
  - `getDomainFromEmail($email)` - Extract domain from email address
  - `generateWebViewerUrl($msgId)` - Create web view URLs for emails

- **URL Handling**:
  - `joinUrl(...$parts)` - Join URL parts with proper slashes
  - `makeTrackableLink($url, $msgId)` - Create click-tracking URLs

- **File Handling**:
  - `detectEncoding($file, $max)` - Detect file encoding (returns false for safety)
  - `toUTF8($file, $from)` - Convert file to UTF-8
  - `checkAndRemoveUTF8BOM($file)` - Remove BOM from UTF-8 files
  - `sanitizeFilename($filename)` - Clean filenames for safe storage
  - `generateUniqueName($directory, $name)` - Generate unique filenames

- **HTML Processing**:
  - `updateHtml($content, Closure $callback)` - Safely modify HTML DOM
  - `transformUrls($content, Closure $callback)` - Transform all URLs in HTML
  - `appendHtml($content, $html)` - Append HTML to body
  - `purifyHtml($html, $tags)` - Remove dangerous tags (script, etc.)
  - `replaceBareLineFeed($content)` - Fix bare LF to CRLF
  - `saveHTMLWithUTF8AndDoctype($document)` - Save DOM with DOCTYPE

- **Security**:
  - `fromHumanIpAddress($ipAddress)` - Check if IP is from human (not Google bot)
  - `getRandomUSIpAddresses()` - Get random US IP addresses (for testing?)

- **Validation**:
  - `isTag($string)` - Check if string is a template tag `{TAG}`

**Usage Example**:
```php
// Generate message ID
$msgId = StringHelper::generateMessageId('example.com');

// Create tracking URL
$trackUrl = StringHelper::makeTrackableLink($url, $msgId);

// Transform all URLs in email
$html = StringHelper::transformUrls($html, function($url, $element) {
    return StringHelper::makeTrackableLink($url, $msgId);
});

// Clean filename
$safe = StringHelper::sanitizeFilename($userFilename);
```

---

### ApiHelper.php
**Purpose**: API documentation generator
**Location**: `Library/ApiHelper.php`
**Key Features**:

- **docs()** method returns comprehensive API documentation array
- Documents all REST API endpoints with:
  - HTTP methods (GET, POST, PATCH, DELETE)
  - URI patterns with placeholders
  - Parameter specifications
  - Return value descriptions
  - cURL example commands

**API Endpoint Categories**:

1. **Authentication**:
   - `POST /api/v1/login-token` - Generate one-time login tokens

2. **Lists**:
   - `POST /api/v1/lists` - Create mail lists
   - `GET /api/v1/lists` - Get all lists
   - `GET /api/v1/lists/{uid}` - Get specific list
   - `POST /api/v1/lists/{uid}/add-field` - Add custom field
   - `DELETE /api/v1/lists/{uid}` - Delete list

3. **Campaigns**:
   - `GET /api/v1/campaigns` - List campaigns
   - `POST /api/v1/campaigns` - Create campaign
   - `GET /api/v1/campaigns/{uid}` - Get campaign
   - `PATCH /api/v1/campaigns/{uid}` - Update campaign
   - `POST /api/v1/campaigns/{uid}/run` - Execute campaign
   - `POST /api/v1/campaigns/{uid}/pause` - Pause campaign
   - `POST /api/v1/campaigns/{uid}/resume` - Resume campaign
   - `DELETE /api/v1/campaigns/{uid}` - Delete campaign

4. **Subscribers**:
   - `GET /api/v1/subscribers` - List subscribers
   - `POST /api/v1/subscribers` - Add subscriber
   - `GET /api/v1/subscribers/{uid}` - Get subscriber
   - `PATCH /api/v1/subscribers/{uid}` - Update subscriber
   - `POST /api/v1/subscribers/{uid}/add-tag` - Add tags
   - `GET /api/v1/subscribers/email/{email}` - Find by email
   - `PATCH /api/v1/lists/{list_uid}/subscribers/{uid}/subscribe` - Subscribe
   - `PATCH /api/v1/lists/{list_uid}/subscribers/{uid}/unsubscribe` - Unsubscribe
   - `DELETE /api/v1/subscribers/{uid}` - Delete subscriber

5. **Plans** (Backend):
   - `GET /api/v1/plans` - List plans
   - `POST /api/v1/plans` - Create plan with quotas

6. **Customers** (Backend):
   - `POST /api/v1/customers` - Create customer
   - `GET /api/v1/customers/{uid}` - Get customer
   - `PATCH /api/v1/customers/{uid}` - Update customer
   - `PATCH /api/v1/customers/{uid}/enable` - Enable customer
   - `PATCH /api/v1/customers/{uid}/disable` - Disable customer
   - `POST /api/v1/customers/{uid}/assign-plan/{plan_uid}` - Assign plan
   - `POST /api/v1/customers/{uid}/change-plan/{plan_uid}` - Change plan
   - `DELETE /api/v1/customers/{uid}` - Delete customer

7. **Notifications**:
   - `POST /api/v1/notification` - Send delivery/bounce/spam/abuse reports

8. **Files**:
   - `POST /api/v1/file/upload` - Upload files to customer storage

**Usage Example**:
```php
// Get API documentation
$docs = ApiHelper::docs();

// Render in view
foreach ($docs as $section) {
    echo $section['title'];
    foreach ($section['functions'] as $endpoint) {
        echo $endpoint['method'] . ' ' . $endpoint['uri'];
        echo $endpoint['example'];
    }
}
```

---

## Campaign Management

### BaseCampaign.php
**Purpose**: Base campaign model with queue/batch management
**Location**: `Library/BaseCampaign.php`
**Key Features**:

**Campaign Status Constants**:
```php
STATUS_NEW       = 'new'        // Just created
STATUS_QUEUING   = 'queuing'    // Waiting to be queued
STATUS_QUEUED    = 'queued'     // Ready to send
STATUS_SENDING   = 'sending'    // Currently sending
STATUS_ERROR     = 'error'      // Failed with error
STATUS_DONE      = 'done'       // Completed successfully
STATUS_PAUSED    = 'paused'     // Manually paused
STATUS_SCHEDULED = 'scheduled'  // Scheduled for future
```

**Core Methods**:

- **Execution Control**:
  - `execute()` - Main entry point, schedules RunCampaign job
  - `run()` - Called by RunCampaign, dispatches LoadCampaign batch
  - `pause()` - Cancel all jobs and set paused status
  - `resume()` - Restart campaign execution

- **Status Management**:
  - `setDone()` - Mark campaign as completed
  - `setSending()` - Mark as actively sending
  - `setQueued()` - Mark as ready to send
  - `setQueuing()` - Mark as waiting
  - `setError($error)` - Mark as failed with error message
  - `setPaused()` - Mark as paused
  - `setScheduled()` - Mark as scheduled

- **Status Checks**:
  - `isSending()` - Check if currently sending
  - `isDone()` - Check if completed
  - `isQueued()` - Check if queued
  - `isPaused()` - Check if paused
  - `isError()` - Check if failed

- **Job Management** (via TrackJobs trait):
  - `jobMonitors()` - Get all job monitors for campaign
  - `dispatchWithMonitor($job)` - Dispatch job with monitoring
  - `dispatchWithBatchMonitor($job, $then, $catch, $finally)` - Dispatch batch with callbacks
  - `cancelAndDeleteJobs($jobType)` - Cancel specific or all jobs

- **Logging**:
  - `logger()` - Get Monolog logger instance
  - `getLogFile()` - Get log file path: `logs/{sapi}/campaign-{uid}.log`
  - `extractErrorMessage()` - Get first line of error

- **Scheduling**:
  - `checkAndExecuteScheduledCampaigns()` - Static method to execute scheduled campaigns
  - `scheduleDiffForHumans()` - Human-readable schedule time

- **Cleanup**:
  - `deleteAndCleanup()` - Delete campaign, template, and all jobs

**Campaign Execution Flow**:
```
1. User clicks "Send" → execute()
2. execute() checks run_at schedule
3. Dispatches RunCampaign job → setQueuing()
4. RunCampaign calls run()
5. run() dispatches LoadCampaign batch → setQueued()
6. LoadCampaign loads subscribers and dispatches SendMessage jobs
7. Each SendMessage sends to one subscriber
8. When batch completes:
   - If subscribers remain → run() again (loop)
   - If no subscribers left → setDone()
9. On error → setError()
10. On pause → setPaused()
```

**Batch Callbacks**:
- **then()**: Called when batch completes successfully
  - Checks if more subscribers to send
  - If yes: calls run() again
  - If no: calls setDone()
- **catch()**: Called when any job fails
  - Sets error status
  - Logs error message
- **finally()**: Always called when batch finishes
  - Updates cache
  - Cleanup operations

**Usage Example**:
```php
// Execute campaign
$campaign->execute();

// Pause sending
$campaign->pause();

// Resume sending
$campaign->resume();

// Check status
if ($campaign->isSending()) {
    $sent = $campaign->sentCount();
    $total = $campaign->totalSubscribers();
    $progress = ($sent / $total) * 100;
}

// Get logs
$logger = $campaign->logger();
$logger->info('Custom log message');
```

**Notes**:
- Uses **TrackJobs** trait for job monitoring
- Uses **HasUid** trait for unique identifiers
- Uses **HasCache** trait for caching
- Uses **Lockable** for concurrent access control
- Integrates with Laravel Queue and Batch systems

---

## Rate Limiting & Tracking

### RateTracker.php
**Purpose**: File-based rate limiting and quota tracking
**Location**: `Library/RateTracker.php`
**Key Features**:

**Core Concept**:
Logs credit usage to a file grouped by time blocks (minute, hour, day, month, year). Each line format: `YYYYMMDDHHMM:COUNT`

**Example Log File** (grouped by minute):
```
202307230913:1200
202307230914:1250
202307230915:1052
202307230930:1178
```

**Constructor**:
```php
__construct(string $filepath, array $limits = [])
```
- `$filepath`: Path to tracking log file
- `$limits`: Array of RateLimit objects

**Public Methods**:

- `count(Carbon $now = null)` - Record one credit usage
  - Acquires exclusive lock
  - Tests against all rate limits
  - Throws `RateLimitExceeded` if quota exceeded
  - Records usage to file

- `rollback()` - Reverse last count (deprecated)
  - Not needed as failed operations still count toward limits

- `getRecords(Carbon $from, Carbon $to, $fopen)` - Get all records in date range
  - Returns array of `[block, count]` tuples

- `getCreditsUsed(Carbon $from, Carbon $to, $fopen)` - Sum credits in date range
  - Used by rate limit tests

- `getRateLimits()` - Get configured rate limits

- `getLockFilePath()` - Get file path used for locking

**Internal Methods**:

- `test(Carbon $now, $fopen)` - Check all rate limits
  - Loops through each limit
  - Calculates time period (e.g., "5 minutes")
  - Counts credits used in that period
  - Throws exception if exceeded

- `record(Carbon $now, $fopen)` - Write usage to file
  - Gets current time block (e.g., "202307230915")
  - If same as last block: increment count
  - If new block: add new line

- `makeBlock(Carbon $now)` - Convert datetime to block string
  - Format depends on mode (minute, hour, day, etc.)

- `parseLastRecord($fopen)` - Read last line from file
  - Returns `[block, count]`

- `updateRecord($record, $fopen)` - Update last line
- `removeLastRecord($fopen)` - Delete last line
- `addRecord($record, $fopen)` - Append new line

**Block Formats**:
```php
'minute' => 'YmdHi'       // 202307230915
'hour'   => 'YmdH00'      // 2023072309
'day'    => 'Ymd0000'     // 20230723
'month'  => 'Ym000000'    // 202307
'year'   => 'Y00000000'   // 2023
```

**Usage Example**:
```php
use Acelle\Library\RateTracker;
use Acelle\Library\RateLimit;

// Define rate limits
$limits = [
    new RateLimit(100, 1, 'minute'),  // 100 per minute
    new RateLimit(5000, 1, 'hour'),   // 5000 per hour
    new RateLimit(50000, 1, 'day'),   // 50000 per day
];

// Create tracker
$tracker = new RateTracker(
    storage_path('rate-tracker/server-123.log'),
    $limits
);

// Record usage (throws exception if limit exceeded)
try {
    $tracker->count(); // Record 1 credit
} catch (RateLimitExceeded $e) {
    // Handle quota exceeded
    echo $e->getMessage(); // "100 per minute exceeded! 100/100 used"
}

// Get usage statistics
$from = Carbon::now()->subHour();
$to = Carbon::now();
$used = $tracker->getCreditsUsed($from, $to);
echo "Used {$used} credits in last hour";
```

**Thread Safety**:
- Uses **Lockable** class for exclusive file locking
- Supports concurrent access from multiple processes
- 15-second timeout for acquiring lock

**Performance**:
- File-based (no database queries)
- Only reads/writes last line for most operations
- Efficient for high-volume operations

---

### RateLimit.php
**Purpose**: Rate limit configuration object
**Location**: `Library/RateLimit.php`
**Key Features**:

Simple value object that stores:
- `$amount` - Number of credits allowed
- `$periodValue` - Time period value (e.g., 5)
- `$periodUnit` - Time period unit (minute, hour, day, etc.)

**Usage Example**:
```php
$limit = new RateLimit(
    100,      // amount
    5,        // period value
    'minute'  // period unit
);
// Means: 100 credits per 5 minutes

$limit->getAmount();       // 100
$limit->getPeriodValue();  // 5
$limit->getPeriodUnit();   // 'minute'
$limit->getDescription();  // "100 per 5 minutes"
```

---

### Lockable.php
**Purpose**: File-based locking for concurrency control
**Location**: `Library/Lockable.php`
**Key Features**:

**Constructor**:
```php
__construct($file)
```
- Creates lock file if doesn't exist
- Stores file path for locking

**Public Methods**:

- `getExclusiveLock($callback, $timeout = 15, $timeoutCallback = null)`
  - Acquires exclusive lock (LOCK_EX)
  - Executes callback with file handle
  - Auto-releases lock when done
  - Throws exception on timeout (unless timeoutCallback provided)

- `getSharedLock($callback, $timeout = 5, $timeoutCallback = null)`
  - Acquires shared lock (LOCK_SH)
  - Multiple processes can read simultaneously
  - Blocks if exclusive lock held

- `isTimeout($startTime, $timeoutDuration)`
  - Check if lock attempt timed out

- `static withExclusiveLock($lockFile, $task, $waitFor = 15, $timeoutCallback = null)`
  - Convenient shortcut method
  - No need to instantiate Lockable object

**Usage Example**:
```php
use Acelle\Library\Lockable;

// Method 1: Instance
$lock = new Lockable(storage_path('locks/my-process.lock'));
$lock->getExclusiveLock(function($file) {
    // Critical section - only one process at a time
    fwrite($file, "Processing...\n");
    // Do work
}, $timeout = 15);

// Method 2: Static shortcut
Lockable::withExclusiveLock(
    storage_path('locks/my-process.lock'),
    function($file) {
        // Critical section
    },
    $waitFor = 15
);

// With timeout callback
$lock->getExclusiveLock(
    function($file) {
        // Work
    },
    $timeout = 5,
    $timeoutCallback = function() {
        // Silently quit instead of throwing exception
        return;
    }
);
```

**Use Cases**:
- Rate tracker file operations
- Campaign cache updates
- Concurrent subscriber imports
- Any operation requiring exclusive access

---

### CreditTracker.php
**Purpose**: Track email sending credits
**Location**: `Library/CreditTracker.php`
**Key Features**:

Similar to RateTracker but specific to email sending credits. Tracks customer/subscription credit usage for billing purposes.

---

## Email Processing Pipeline

Acelle uses a **pipeline pattern** to process email HTML through multiple stages. Each handler transforms the content step by step.

### Pipeline Architecture

The email processing happens in two phases:

**Phase 1: Base HTML Processing** (not subscriber-specific)
```php
AddDoctype → AddPreheader → RemoveTitleTag → AppendHtml →
ParseRss → MakeInlineCss → TransformWidgets
```

**Phase 2: Subscriber-Specific Processing**
```php
TransformTag → InjectTrackingPixel → InjectMessageIdToBody →
TransformUrl → DecodeHtmlSpecialChars → GenerateSpintax → ReplaceBareLineFeed
```

### HTML Handler Classes

Located in `Library/HtmlHandler/`:

1. **AddDoctype.php** - Ensures HTML5 DOCTYPE exists
2. **AddPreheader.php** - Adds email preheader text
3. **RemoveTitleTag.php** - Removes `<title>` tags
4. **AppendHtml.php** - Appends footer content
5. **ParseRss.php** - Parse and inject RSS feed content
6. **MakeInlineCss.php** - Convert CSS to inline styles
7. **TransformWidgets.php** - Process custom email widgets
8. **TransformTag.php** - Replace template tags (e.g., `{EMAIL}`, `{FIRST_NAME}`)
9. **InjectTrackingPixel.php** - Add open tracking pixel
10. **InjectMessageIdToBody.php** - Embed message ID in HTML
11. **TransformUrl.php** - Convert URLs to tracking URLs
12. **DecodeHtmlSpecialChars.php** - Decode HTML entities
13. **GenerateSpintax.php** - Process spintax for HTML
14. **GenerateSpintaxForPlainText.php** - Process spintax for plain text
15. **ReplaceBareLineFeed.php** - Fix line endings (LF → CRLF)
16. **HtmlToPlain.php** - Convert HTML to plain text

### InlineStyleWrapper.php
**Purpose**: Convert CSS to inline styles for email clients
**Location**: `Library/InlineStyleWrapper.php`
**Key Features**:

**Core Methods**:

- `loadHTML($html)` - Load HTML string
- `loadHTMLFile($filename)` - Load HTML from file
- `loadDomDocument(\DOMDocument $doc)` - Load from DOM

- `applyStylesheet($stylesheet)` - Apply CSS rules inline
  - Can accept string or array of stylesheets
  - Parses CSS selectors and properties
  - Applies styles directly to matching elements
  - Respects `!important` declarations

- `extractStylesheets($node, $base, $devices, $remove)` - Extract `<style>` and `<link>` tags
  - Recursively finds all stylesheets
  - Fetches external stylesheets via URL
  - Filters by media query (screen, handheld, etc.)
  - Optionally removes original tags

- `applyRule($selector, $style)` - Apply CSS rule to matching elements

- `getHTML()` - Return processed HTML with inline styles

**CSS Specificity Handling**:
- `sortSelectorsOnSpecificity($parsed)` - Sort CSS rules by specificity
- `getScoreForSelector($selector)` - Calculate specificity score [IDs, Classes, Tags]

**Usage Example**:
```php
use Acelle\Library\InlineStyleWrapper;

$html = '<html><head><style>p { color: red; }</style></head><body><p>Test</p></body></html>';

$inliner = new InlineStyleWrapper($html);

// Extract and apply stylesheets
$stylesheets = $inliner->extractStylesheets();
$inliner->applyStylesheet($stylesheets);

// Get processed HTML
$output = $inliner->getHTML();
// Result: <p style="color:red">Test</p>
```

**Features**:
- Handles CSS specificity correctly
- Supports `!important` declarations
- Fetches external stylesheets
- Filters by media query
- Merges multiple style sources
- UTF-8 safe

---

## External Service Integrations

### AmazonSmtpTransport.php
**Purpose**: Extended Swift SMTP transport for Amazon SES
**Location**: `Library/AmazonSmtpTransport.php`
**Key Features**:

Extends `Swift_SmtpTransport` to capture SMTP responses and extract Amazon SES Message IDs.

**Methods**:

- `executeCommand($command, $codes, &$failures, $pipeline, $address)` - Overrides parent
  - Captures raw SMTP responses
  - Stores in `$rawResponses` array

- `getMessageId()` - Extract Amazon Message ID
  - Parses SMTP response for pattern: `250 ok {MESSAGE_ID}`
  - Returns message ID string

- `getRawResponses()` - Get all SMTP responses

- `static newInstance($host, $port, $security)` - Factory method

**Usage Example**:
```php
use Acelle\Library\AmazonSmtpTransport;

$transport = AmazonSmtpTransport::newInstance(
    'email-smtp.us-east-1.amazonaws.com',
    587,
    'tls'
);

$transport->setUsername($sesUsername);
$transport->setPassword($sesPassword);

$mailer = new Swift_Mailer($transport);
$mailer->send($message);

// Get Amazon Message ID
$msgId = $transport->getMessageId();
// Example: "0000018d45f5a1e2-12345678-1234-1234-1234-123456789abc-000000"
```

---

### ExtendedSmtpTransport.php
**Purpose**: Extended SMTP transport with additional features
**Location**: `Library/ExtendedSmtpTransport.php`
**Note**: Very minimal class (140 bytes), likely a placeholder or deprecated

---

### ExtendedSwiftMessage.php
**Purpose**: Extended Swift_Message with custom properties
**Location**: `Library/ExtendedSwiftMessage.php`
**Note**: Very minimal class (140 bytes)

---

### Lazada Integration
**Purpose**: Integration with Lazada e-commerce platform
**Location**: `Library/Lazada/`
**Files**:
- `LazadaConnection.php` - Connection manager
- `LazopSdk.php` - SDK wrapper
- `Lazop/LazopClient.php` - API client
- `Lazop/LazopRequest.php` - Request builder
- `Lazop/LazopLogger.php` - Logging
- `Lazop/Constants.php` - API constants
- `Lazop/UrlConstants.php` - Endpoint URLs

---

## Traits & Behaviors

### HasTemplate.php
**Purpose**: Add template management to campaigns/emails
**Location**: `Library/Traits/HasTemplate.php`
**Key Features**:

**Relationship**:
```php
template() // belongsTo Template
```

**Template Management**:

- `setTemplate($template, $name = null)` - Assign template to campaign
  - Copies template as private
  - Removes old template
  - Updates plain text
  - Updates links

- `uploadTemplate($request)` - Upload and assign new template

- `hasTemplate()` - Check if template exists

- `getThumbUrl()` - Get template thumbnail

- `removeTemplate()` - Delete template

- `setTemplateContent($content, $callback)` - Update template HTML

- `getTemplateContent()` - Get template HTML

- `updatePlainFromHtml()` - Generate plain text from HTML

**Email Preparation**:

- `prepareEmail($subscriber, $server, $fromCache, $expiresInSeconds)` - Build Swift_Message
  - Sets custom headers
  - Sets from/to/reply-to
  - Sets subject with tags replaced
  - Generates HTML/plain content
  - Processes through pipeline
  - Adds attachments
  - Signs with DKIM if enabled
  - Returns `[$message, $msgId]`

- `getCustomHeaders($subscriber, $server)` - Build email headers
  ```php
  X-Acelle-Campaign-Id
  X-Acelle-Subscriber-Id
  X-Acelle-Customer-Id
  X-Acelle-Message-Id
  X-Acelle-Sending-Server-Id
  List-Unsubscribe
  Precedence: bulk
  ```

**Content Processing**:

- `getHtmlContent($subscriber, $msgId, $server, $fromCache, $expiresInSeconds)` - Get subscriber HTML
  - Gets base HTML (cached)
  - Transforms tags (subscriber data)
  - Injects tracking pixel
  - Transforms URLs to tracking URLs
  - Decodes HTML entities
  - Generates spintax variations
  - Fixes line endings

- `getBaseHtmlContent($fromCache, $expiresInSeconds)` - Get cached base HTML
  - Processes through base pipeline
  - Caches result
  - Returns cached HTML

- `getPlainContent($subscriber, $msgId, $server)` - Get plain text version
  - Gets plain property
  - Appends footer
  - Transforms tags
  - Generates spintax

- `getSubject($subscriber, $msgId)` - Get processed subject
  - Replaces template tags
  - Decodes HTML entities
  - Generates spintax
  - Fixes line endings

**DKIM Signing**:

- `sign($message)` - Sign message with DKIM
  - Finds sending domain
  - Loads DKIM private key
  - Attaches signer to message

- `findSendingDomain($email)` - Find sending domain by email

**Footer Management**:

- `footerEnabled()` - Check if footer enabled (from quota)
- `getHtmlFooter()` - Get HTML footer content
- `getPlainTextFooter()` - Get plain text footer

**Cache**:

- `getCachedHtmlId()` - Get cache key
- `clearTemplateCache()` - Clear cached HTML

**Utilities**:

- `isStdClassSubscriber($object)` - Check if test subscriber
- `createStdClassSubscriber($subscriber)` - Create test subscriber object
- `makeTrackingPixel($msgId)` - Generate tracking pixel HTML
- `makeSampleLink()` - Generate sample link for preview
- `isStageExcluded($class)` - Check if pipeline stage should be skipped

**Usage Example**:
```php
// Assign template
$campaign->setTemplate($template, 'My Campaign Template');

// Prepare email
list($message, $msgId) = $campaign->prepareEmail($subscriber, $server);

// Send via Swift Mailer
$mailer->send($message);

// Get cached HTML
$html = $campaign->getBaseHtmlContent($fromCache = true, $expiresInSeconds = 600);
```

---

### TrackJobs.php
**Purpose**: Job monitoring and batch management
**Location**: `Library/Traits/TrackJobs.php`
**Key Features**:

**Relationship**:
```php
jobMonitors() // hasMany JobMonitor
```

**Job Dispatching**:

- `dispatchWithMonitor($job)` - Dispatch single job with monitor
  - Creates JobMonitor record (QUEUED status)
  - Associates monitor with job
  - Dispatches job to queue
  - Stores job_id in monitor
  - Executes eventAfterDispatched callback
  - Returns monitor

- `dispatchWithBatchMonitor($job, $thenCallback, $catchCallback, $finallyCallback)` - Dispatch batch
  - Creates JobMonitor record
  - Associates monitor with job
  - Creates Laravel batch
  - Registers then/catch/finally callbacks
  - Stores batch_id in monitor
  - Returns monitor

**Batch Callbacks**:

- **then()**: Called when all jobs succeed
  - Sets monitor to DONE
  - Executes custom callback

- **catch()**: Called when any job fails
  - Sets monitor to FAILED
  - Logs exception
  - Executes custom callback

- **finally()**: Always called when batch finishes
  - Executes custom callback
  - Executes job's eventAfterFinished

**Job Management**:

- `cancelAndDeleteJobs($jobType = null)` - Cancel jobs
  - Gets all job monitors
  - Optionally filters by job type
  - Calls cancel() on each

**Usage Example**:
```php
use Acelle\Library\Traits\TrackJobs;

class Campaign extends Model
{
    use TrackJobs;

    public function execute()
    {
        // Dispatch single job
        $monitor = $this->dispatchWithMonitor(new RunCampaign($this));

        // Dispatch batch
        $monitor = $this->dispatchWithBatchMonitor(
            new LoadCampaign($this),
            function($batch) {
                // Success callback
                $this->setDone();
            },
            function($batch, $e) {
                // Error callback
                $this->setError($e->getMessage());
            },
            function($batch) {
                // Finally callback
                $this->updateCache();
            }
        );
    }

    public function pause()
    {
        // Cancel all jobs
        $this->cancelAndDeleteJobs();
    }
}
```

---

### Other Traits

Located in `Library/Traits/`:

1. **HasUid.php** - Add UID field and scope
   ```php
   use HasUid;
   // Adds: uid field, findByUid() scope
   ```

2. **HasCache.php** - Add caching methods
   ```php
   use HasCache;
   // Adds: cache(), clearCache(), etc.
   ```

3. **Trackable.php** - Make jobs trackable
   ```php
   use Trackable;
   // Adds: setMonitor(), monitor, eventAfterDispatched, eventAfterFinished
   ```

4. **QueryHelper.php** - Query builder helpers
   ```php
   use QueryHelper;
   // Adds: search(), filter(), sort() scopes
   ```

---

## Contracts & Interfaces

Located in `Library/Contracts/`:

### CampaignInterface.php
**Purpose**: Define campaign contract
**Methods**:
```php
getSubject($subscriber, $msgId)
getHtmlContent($subscriber, $msgId, $server)
getPlainContent($subscriber, $msgId, $server)
prepareEmail($subscriber, $server)
```

### HasTemplateInterface.php
**Purpose**: Define template contract
**Methods**:
```php
template()
getTemplateContent()
setTemplateContent($content)
```

### PlanInterface.php
**Purpose**: Define subscription plan contract
**Methods**:
```php
getOption($name)
getOptions()
```

### PaymentGatewayInterface.php
**Purpose**: Define payment gateway contract
**Methods**:
```php
getName()
getType()
validate($request)
initialize($request)
charge($request, $amount, $currency)
verify($request)
```

### GeoIpInterface.php
**Purpose**: Define GeoIP service contract
**Methods**:
```php
lookup($ipAddress)
```

---

## Exception Classes

Located in `Library/Exception/`:

### RateLimitExceeded.php
**Purpose**: Thrown when rate limit quota exceeded
**Usage**:
```php
throw new RateLimitExceeded("100 per minute exceeded! 100/100 used");
```

### OutOfCredits.php
**Purpose**: Thrown when email sending credits depleted
**Usage**:
```php
throw new OutOfCredits("No credits remaining");
```

### NoCreditsLeft.php
**Purpose**: Similar to OutOfCredits (possibly deprecated)

### VerificationTakesLongerThanNormal.php
**Purpose**: Thrown during email verification delays
**Usage**:
```php
throw new VerificationTakesLongerThanNormal("Verification still in progress");
```

---

## Storage & File Management

Located in `Library/Storage/`:

### Contracts/StorageService.php
**Purpose**: Storage service interface
**Methods**:
```php
upload($source, $destination)
download($source, $destination)
delete($path)
exists($path)
getUrl($path)
```

### Contracts/Storable.php
**Purpose**: Storable object interface
**Methods**:
```php
getStorageDriver()
getStorageConfig()
```

### S3.php
**Purpose**: Amazon S3 storage implementation
**Features**:
- Upload files to S3
- Download files from S3
- Generate signed URLs
- Delete files
- Check existence

---

## Automation System

Located in `Library/Automation/`:

### Automation Classes

1. **Trigger.php** - Automation trigger conditions
2. **Action.php** - Actions to execute
3. **Evaluate.php** - Condition evaluation
4. **Operate.php** - Operation execution
5. **Send.php** - Send email action
6. **Wait.php** - Wait/delay action

**Purpose**: Build automated email workflows (drip campaigns, abandoned cart, etc.)

**Usage Example**:
```php
// Trigger: When subscriber joins list
$trigger = new Trigger('subscribe');

// Wait 1 day
$wait = new Wait(1, 'day');

// Send welcome email
$send = new Send($emailTemplate);

// Build workflow
$automation = [
    $trigger,
    $wait,
    $send
];
```

---

## Critical Classes Summary

### Top 10 Most Critical Classes

1. **BaseCampaign.php** - Core campaign execution and job management
2. **RateTracker.php** - Rate limiting and quota tracking
3. **StringHelper.php** - String/encoding utilities used everywhere
4. **Tool.php** - General utility methods for file/system operations
5. **HasTemplate.php** - Email template processing and pipeline
6. **TrackJobs.php** - Job monitoring and batch management
7. **InlineStyleWrapper.php** - CSS to inline styles for email clients
8. **ApiHelper.php** - API documentation and endpoint definitions
9. **Lockable.php** - Concurrency control for critical sections
10. **AmazonSmtpTransport.php** - SMTP transport with SES support

### Architecture Patterns Used

1. **Pipeline Pattern** - Email HTML processing through stages
2. **Observer Pattern** - Job events (afterDispatched, afterFinished)
3. **Strategy Pattern** - Different storage/payment implementations
4. **Factory Pattern** - Creating jobs, messages, templates
5. **Repository Pattern** - Data access abstraction
6. **Service Layer Pattern** - Business logic in Library classes
7. **Trait Composition** - Reusable behaviors (HasTemplate, TrackJobs, etc.)

### Design Principles

1. **Single Responsibility** - Each class has one clear purpose
2. **Open/Closed** - Pipeline handlers are open for extension
3. **Dependency Injection** - Dependencies injected, not instantiated
4. **Interface Segregation** - Focused interfaces (CampaignInterface, etc.)
5. **Composition over Inheritance** - Traits for reusable behaviors

---

## Migration Recommendations

When porting Acelle code to the new Mailing module:

### High Priority (Must Port)

1. **RateTracker.php + Lockable.php** - Essential for quota management
2. **StringHelper.php** - Encoding, message IDs, URL handling
3. **BaseCampaign.php** - Campaign execution logic
4. **HasTemplate.php trait** - Email processing pipeline
5. **InlineStyleWrapper.php** - CSS inlining for email clients

### Medium Priority (Should Port)

6. **Tool.php** - Utility methods (selectively port needed methods)
7. **TrackJobs.php trait** - Job monitoring
8. **AmazonSmtpTransport.php** - SES integration
9. **HTML Handler classes** - Pipeline stages
10. **Automation classes** - If automation features needed

### Low Priority (Optional)

11. **ApiHelper.php** - Only if building API endpoints
12. **Lazada integration** - Only if marketplace integration needed
13. **Storage classes** - Use Laravel's built-in storage instead
14. **JsonModel classes** - Probably not needed

### Don't Port (Replace with Laravel)

- **Facades/** - Use Laravel facades
- **Contracts/** - Define new interfaces for our module
- **BillingManager.php** - Build custom billing for our system
- **License.php** - Not applicable

---

## Code Quality Assessment

### Strengths

1. Well-organized directory structure
2. Good separation of concerns
3. Comprehensive HTML processing pipeline
4. Robust rate limiting implementation
5. Good use of traits for code reuse
6. Extensive API documentation
7. Thread-safe file operations

### Areas for Improvement

1. Some classes are very large (HasTemplate.php ~560 lines)
2. Mixed PHP versions (some deprecated Swift Mailer code)
3. Some hardcoded values and magic strings
4. Limited unit test coverage apparent
5. Some poorly designed functions (footerEnabled() uses session)
6. Minimal type hinting in older classes

### Security Considerations

1. ✅ Proper HTML sanitization (purifyHtml)
2. ✅ DKIM signing support
3. ✅ Rate limiting to prevent abuse
4. ✅ File locking for race conditions
5. ⚠️ Direct file operations (should use Laravel Storage)
6. ⚠️ Some eval-like functionality (spintax)

---

## Next Steps

### For Mailing Module Development

1. **Phase 1**: Port core utilities
   - RateTracker + Lockable
   - StringHelper (message IDs, encoding)
   - Tool (selectively)

2. **Phase 2**: Port email processing
   - HasTemplate trait
   - InlineStyleWrapper
   - HTML handler pipeline

3. **Phase 3**: Port campaign management
   - BaseCampaign logic
   - TrackJobs trait
   - Job monitoring

4. **Phase 4**: Add integrations
   - SMTP transports
   - Storage adapters
   - API endpoints

### Code Modernization

1. Replace Swift Mailer with Symfony Mailer
2. Add PHP 8.x type hints
3. Add comprehensive unit tests
4. Use Laravel Storage instead of direct file operations
5. Replace session dependencies with proper config
6. Add Laravel Sanctum for API auth
7. Use Laravel Queue instead of custom job tracking

---

## Glossary

- **Message ID**: Unique identifier for each email sent (format: `timestamp.uniqid@domain`)
- **VERP**: Variable Envelope Return Path (bounce handling)
- **Spintax**: Text spinning syntax for variations `{hello|hi|hey}`
- **Tracking Pixel**: 1x1 transparent image for open tracking
- **Inline CSS**: CSS styles embedded in style attributes
- **Pipeline**: Chain of processors transforming content
- **Job Monitor**: Database record tracking job status
- **Batch**: Group of jobs executed together
- **Rate Limit**: Quota restriction (e.g., 100 per minute)
- **Lock File**: File used for exclusive access control

---

## Conclusion

The Acelle Library directory contains a well-architected email marketing system with:

- ✅ Robust campaign execution with job monitoring
- ✅ Advanced rate limiting and quota tracking
- ✅ Comprehensive email processing pipeline
- ✅ Thread-safe file operations
- ✅ Extensive utility functions
- ✅ Multiple integration options

The codebase demonstrates good software engineering principles but would benefit from modernization for Laravel 11+ and PHP 8.x. When porting to the new Mailing module, prioritize the core utilities, rate tracking, and email processing components.

---

**Document Version**: 1.0
**Last Updated**: 2026-01-29
**Analyst**: Claude Code Agent
**Status**: Complete
