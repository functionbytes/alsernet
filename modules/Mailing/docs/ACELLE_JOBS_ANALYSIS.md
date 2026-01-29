# Acelle Jobs Analysis Report

**Generated:** 2026-01-29
**Source Directory:** `/Users/functionbytes/Function/Coding/acelle/app/Jobs/`
**Total Jobs Analyzed:** 23

---

## Executive Summary

The Acelle application implements a comprehensive queue-based job system for managing email campaigns, subscriber management, automation workflows, and data import/export operations. The system uses Laravel's queue infrastructure with specialized rate limiting, credit tracking, and batch processing capabilities.

### Critical Components for Email Operations
- **SendMessage**: Core email delivery job
- **RunCampaign**: Campaign orchestration
- **LoadCampaign**: Batch subscriber loading for campaigns
- **RunAutomation**: Automation workflow execution

---

## 1. Complete Jobs List

| # | Job Name | Type | Critical for Email | Uses Batching |
|---|----------|------|-------------------|---------------|
| 1 | Base | Abstract Base Class | N/A | No |
| 2 | ExecuteCampaignCallback | Webhook | No | No |
| 3 | ExportCampaignLog | Export | No | No |
| 4 | ExportSubscribersJob | Export | No | No |
| 5 | ForceTriggerAutomation | Automation | No | No |
| 6 | ImportBlacklistJob | Import | No | No |
| 7 | ImportSubscribers2 | Import | No | Yes |
| 8 | ImportSubscribersJob | Import | No | No |
| 9 | LoadCampaign | Campaign Processing | **YES** | Yes |
| 10 | RunAutomation | Automation | **YES** | No |
| 11 | RunCampaign | Campaign Processing | **YES** | No |
| 12 | SendConfirmationEmailJob | Email Sending | Yes | No |
| 13 | SendMessage | Email Sending | **YES** | Yes |
| 14 | SyncProducts | Integration | No | No |
| 15 | UpdateAutomation | Cache Update | No | No |
| 16 | UpdateCampaignJob | Cache Update | No | No |
| 17 | UpdateMailListJob | Cache Update | No | No |
| 18 | UpdateSegmentJob | Cache Update | No | No |
| 19 | UpdateUserJob | Cache Update | No | No |
| 20 | VerifyAndCreateSubscriber | Verification | No | Yes |
| 21 | VerifyMailListJob | Verification | No | Yes |
| 22 | VerifySubscriber | Verification | No | Yes |

---

## 2. Detailed Job Analysis

### 2.1 Base Job Infrastructure

#### **Base.php**
- **Purpose**: Abstract base class for all Acelle jobs
- **Key Configuration**:
  - `failOnTimeout = true` - Prevents confusing timeout errors
  - `tries = 1` - Single attempt only
  - `maxExceptions = 1` - Fail immediately on exception
- **Traits Used**: Standard Laravel queue traits (Dispatchable, InteractsWithQueue, Queueable, SerializesModels)
- **Design Philosophy**: Fail fast, no retries by default

---

### 2.2 Campaign & Email Sending Jobs (CRITICAL)

#### **SendMessage.php** ⭐ CRITICAL
- **Purpose**: Sends individual email messages to subscribers
- **Queue**: Can be part of batch operations
- **Timeout**: 600 seconds (10 minutes)
- **Retry Strategy**: Uses `retryUntil()` - retries up to 12 hours
- **Key Dependencies**:
  - `Campaign` - Email campaign instance
  - `Subscriber` - Recipient
  - `SendingServer` - SMTP/delivery server
  - `Subscription` - Plan limits (optional)
  - `triggerId` - For automation tracking (optional)
- **Rate Limiting**:
  - Server-level rate limits via `getRateLimitTracker()`
  - Subscription-level rate limits via `getSendEmailRateTracker()`
- **Exception Handling**:
  - `RateLimitExceeded` → Release job for 60 seconds
  - `OutOfCredits` → Log error or throw exception based on `stopOnError` flag
  - `Throwable` → Track as failed message or throw
- **Special Features**:
  - Dry-run mode support via `config('custom.dryrun')`
  - Message tracking via `trackMessage()`
  - Helper function: `execute_with_limits()` for quota management
- **Critical Methods**:
  ```php
  handle() // Main entry point
  send($exceptionCallback = null) // Core sending logic
  retryUntil() // Returns now()->addHours(12)
  ```

#### **RunCampaign.php** ⭐ CRITICAL
- **Purpose**: Orchestrates campaign execution and launches batch jobs
- **Queue**: Standalone job that creates batches
- **Timeout**: 300 seconds (5 minutes)
- **Dependencies**:
  - `CampaignInterface` implementation
- **Traits**: Trackable (for progress monitoring)
- **Workflow**:
  1. Check if campaign is paused
  2. Log campaign launch
  3. Call `$campaign->run()` to initiate sending
  4. Handle errors by setting campaign error status
- **Exception Handling**:
  - Sets campaign to error state via `setError()`
  - Re-throws exception to mark job as failed
- **Critical Methods**:
  ```php
  handle() // Checks pause status and runs campaign
  ```

#### **LoadCampaign.php** ⭐ CRITICAL
- **Purpose**: Loads subscribers in batches and creates SendMessage jobs
- **Queue**: Part of batch operations
- **Timeout**: 7200 seconds (2 hours)
- **Batch Support**: Yes (uses `Batchable` trait)
- **Load Limit**: 100-109 subscribers per job (randomized)
- **Dependencies**:
  - `CampaignInterface` implementation
- **Traits**: Trackable, Batchable
- **Workflow**:
  1. Check if batch is cancelled
  2. Set campaign status to "sending"
  3. Load up to 109 subscribers randomly
  4. Create delivery jobs via `loadDeliveryJobs()`
  5. Add jobs to batch
- **Memory Management**: Loads limited subscribers per job to prevent memory leaks
- **Auto-reload**: Campaign automatically launches new LoadCampaign if more subscribers remain
- **Critical Methods**:
  ```php
  handle() // Loads contacts and dispatches SendMessage jobs
  ```

#### **SendConfirmationEmailJob.php**
- **Purpose**: Sends subscription confirmation emails
- **Queue**: Standard queue
- **Timeout**: Inherits from Base (600s default)
- **Dependencies**:
  - Array of `Subscriber` objects
  - `MailList` instance
- **Workflow**:
  - Iterates through subscribers
  - Calls `sendSubscriptionConfirmationEmail()` for each
- **Use Case**: Double opt-in confirmation emails

#### **ExecuteCampaignCallback.php**
- **Purpose**: Executes webhook callbacks for campaign events
- **Queue**: Standard queue
- **Dependencies**:
  - `$webhook` - Webhook configuration
  - `$log` - Event log (OpenLog, ClickLog, or UnsubscribeLog)
- **Workflow**: Calls `$webhook->execute($log)`
- **Use Case**: Trigger external systems on campaign events

---

### 2.3 Automation Jobs

#### **RunAutomation.php** ⭐ CRITICAL
- **Purpose**: Executes automation workflows via API trigger
- **Queue**: Standard queue
- **Timeout**: 7200 seconds (2 hours)
- **Dependencies**:
  - `Automation` model
- **Validation**: Checks `allowApiCall()` before execution
- **Workflow**:
  1. Verify automation allows API triggering
  2. Log execution
  3. Call `$automation->execute()`
- **Exception Handling**: Throws exception if automation not configured for API
- **Critical Methods**:
  ```php
  __construct($automation) // Validates API permission
  handle() // Executes automation workflow
  ```

#### **ForceTriggerAutomation.php**
- **Purpose**: Forces automation trigger regardless of conditions
- **Queue**: Standard queue
- **Dependencies**: `Automation` model
- **Workflow**: Calls `$automation->forceTrigger()`
- **Use Case**: Manual or administrative automation triggers

#### **UpdateAutomation.php**
- **Purpose**: Updates automation cache after changes
- **Queue**: Standard queue
- **Dependencies**: `Automation2` model
- **Workflow**: Calls `updateCache()` if mail list exists
- **Use Case**: Keep automation metadata synchronized

---

### 2.4 Import/Export Jobs

#### **ImportSubscribersJob.php**
- **Purpose**: Imports subscribers from CSV files with field mapping
- **Queue**: Trackable job
- **Timeout**: 7200 seconds (2 hours)
- **Dependencies**:
  - `MailList` - Target list
  - `$file` - CSV file path (e.g., `/storage/app/tmp/import-000000.csv`)
  - `$map` - Field mapping (e.g., `{"First Name" => 5, "email" => 4}`)
- **Traits**: Trackable (progress monitoring)
- **Logging**: Uses Monolog with dedicated log file (`$file.log`)
- **Locale Support**: Sets locale from customer language
- **Progress Tracking**:
  - `percentage` - Import completion %
  - `total` - Total records
  - `processed` - Successfully imported
  - `failed` - Skipped/invalid records
- **Callbacks**:
  - Progress callback: Updates monitor with stats
  - Error callback: Logs invalid records with validation errors
- **Critical Methods**:
  ```php
  handle() // Main import process
  ```

#### **ImportSubscribers2.php**
- **Purpose**: Modern batch-based CSV import with verification
- **Queue**: Trackable + Batchable
- **Timeout**: 7200 seconds (2 hours)
- **Dependencies**:
  - `MailList` - Target list
  - `$file` - CSV file path
- **Workflow**:
  1. Create log file
  2. Parse CSV file
  3. Dispatch `VerifyAndCreateSubscriber` jobs to batch
- **Modern Approach**: Uses batch processing for better scalability
- **Critical Methods**:
  ```php
  handle() // Parses CSV and creates batch jobs
  ```

#### **ExportSubscribersJob.php**
- **Purpose**: Exports subscribers to CSV
- **Queue**: Trackable job
- **Timeout**: 3600 seconds (1 hour)
- **Dependencies**:
  - `MailList` - Source list
  - `Segment` - Optional filter (can be null)
- **Locale Support**: Sets customer language
- **Progress Tracking**: Same structure as import
- **Output**: File path tracked in `filepath` field
- **Critical Methods**:
  ```php
  handle() // Exports subscribers to CSV
  ```

#### **ExportCampaignLog.php**
- **Purpose**: Exports campaign tracking logs (opens, clicks, unsubscribes)
- **Queue**: Trackable job
- **Timeout**: 3600 seconds (1 hour)
- **Dependencies**:
  - `Campaign` model
  - `$logtype` - Type of log to export
- **Progress Tracking**: Percentage and file path
- **Critical Methods**:
  ```php
  handle() // Generates tracking log CSV
  ```

#### **ImportBlacklistJob.php**
- **Purpose**: Imports email addresses to blacklist
- **Queue**: Trackable job
- **Timeout**: 7200 seconds (2 hours)
- **Dependencies**:
  - `$filepath` - Blacklist file path
  - `Customer` - Optional customer scope
- **Progress Tracking**: Standard import metrics
- **Critical Methods**:
  ```php
  handle() // Imports blacklist from file
  ```

---

### 2.5 Verification Jobs

#### **VerifySubscriber.php**
- **Purpose**: Verifies single subscriber email address
- **Queue**: Batchable job
- **Timeout**: 120 seconds (2 minutes)
- **Retry Strategy**: Up to 12 hours via `retryUntil()`
- **Dependencies**:
  - `Subscriber` - Email to verify
  - `SendingServer` - Verification service
  - `Subscription` - Credit tracker (optional)
- **Rate Limiting**:
  - Server-level rate limits
  - Subscription-level credit limits
- **Exception Handling**:
  - `VerificationTakesLongerThanNormal` → Silently skip
  - `RateLimitExceeded` → Release for 60 seconds
  - Other exceptions → Re-throw or callback
- **Helper Function**: `execute_with_limits()`
- **Critical Methods**:
  ```php
  handle() // Main entry point
  doVerify(Closure $exceptionCallback = null) // Core verification logic
  retryUntil() // Returns now()->addHours(12)
  ```

#### **VerifyMailListJob.php**
- **Purpose**: Orchestrates batch verification of entire mail list
- **Queue**: Trackable + Batchable
- **Timeout**: 14400 seconds (4 hours)
- **Dependencies**:
  - `MailList` - List to verify
  - `SendingServer` - Verification service
  - `Subscription` - Credit limits
- **Batch Size**: 1000 subscribers per batch
- **Workflow**:
  1. Query unverified subscribers
  2. Iterate in cursor batches of 1000
  3. Dispatch `VerifySubscriber` jobs to batch
- **Progress Tracking**: Standard metrics
- **Critical Methods**:
  ```php
  handle() // Queries unverified and creates batch jobs
  ```

#### **VerifyAndCreateSubscriber.php**
- **Purpose**: Validates, creates, and verifies subscriber in one job
- **Queue**: Batchable job
- **Timeout**: 120 seconds
- **Dependencies**:
  - `MailList` - Target list
  - `$attributes` - Subscriber data
  - `Logger` - Monolog instance
  - `JobMonitor` - Progress tracker
- **Workflow**:
  1. Create subscriber record from attributes
  2. Perform email validation
  3. Verify email via remote service
  4. Delete subscriber if verification fails
- **Verification Logic**:
  - Keep if deliverable or unknown
  - Delete if undeliverable
- **Progress Tracking**: Uses exclusive lock for thread-safe updates
- **Critical Methods**:
  ```php
  handle() // Creates and verifies subscriber
  fails($message) // Handles failures
  done($message) // Handles success
  updateProgress($success) // Thread-safe progress update
  ```

---

### 2.6 Cache Update Jobs

#### **UpdateCampaignJob.php**
- **Purpose**: Updates campaign cache and statistics
- **Queue**: Unique job (one per campaign)
- **Unique For**: 3600 seconds (1 hour)
- **Dependencies**: `Campaign` model
- **Workflow**: Calls `updateCache()`
- **Uniqueness**: Based on campaign ID
- **Use Case**: Prevent duplicate cache updates

#### **UpdateMailListJob.php**
- **Purpose**: Updates mail list cache and applies blacklist
- **Queue**: Unique job (one per list)
- **Unique For**: 3600 seconds
- **Dependencies**: `MailList` model
- **Workflow**:
  1. Update list cached info
  2. Apply blacklist to customer
- **Uniqueness**: Based on list ID
- **Critical Methods**:
  ```php
  handle() // Updates cache and blacklist
  ```

#### **UpdateSegmentJob.php**
- **Purpose**: Updates segment cache
- **Queue**: Standard queue
- **Dependencies**: `Segment` model
- **Workflow**: Calls `updateCache()`

#### **UpdateUserJob.php**
- **Purpose**: Updates customer cache in SaaS mode
- **Queue**: Standard queue
- **Timeout**: 120 seconds
- **Dependencies**: `Customer` model
- **Conditional**: Only runs in SaaS mode with active subscription
- **Critical Methods**:
  ```php
  handle() // Updates customer cache if subscription active
  ```

---

### 2.7 Integration Jobs

#### **SyncProducts.php**
- **Purpose**: Synchronizes products from external sources (e.g., Lazada)
- **Queue**: Standard queue
- **Dependencies**: `Source` model
- **Workflow**:
  1. Map source classes
  2. Import products via `importProducts()`
- **Use Case**: E-commerce integration

---

## 3. Queue Dependencies & Infrastructure

### 3.1 Common Traits

| Trait | Purpose | Used By |
|-------|---------|---------|
| **Trackable** | Progress monitoring with JSON data storage | 11 jobs |
| **Batchable** | Batch job support with cancellation checks | 6 jobs |
| **Dispatchable** | Standard Laravel dispatch | All jobs |
| **InteractsWithQueue** | Queue interaction methods | All jobs |
| **Queueable** | Queue configuration | All jobs |
| **SerializesModels** | Model serialization | All jobs |

### 3.2 Rate Limiting & Quotas

#### **Rate Limit Implementation**
Jobs that implement rate limiting:
- **SendMessage**: Server + Subscription rate limits
- **VerifySubscriber**: Server + Subscription credit limits

#### **Rate Limit Trackers**
```php
// Sending rate limits
$rateTrackers = [
    $server->getRateLimitTracker(),
    $subscription->getSendEmailRateTracker(),
];

// Credit limits
$creditTrackers = [
    $subscription->getSendEmailCreditTracker(),
    $subscription->getVerifyEmailCreditTracker(),
];
```

#### **Execution Helper**
```php
execute_with_limits($rateTrackers, $creditTrackers, function() {
    // Protected code
});
```

**Exceptions Thrown**:
- `RateLimitExceeded` - Quota exceeded, job released
- `OutOfCredits` - No credits remaining, job fails or logs
- `NoCreditsLeft` - Similar to OutOfCredits

### 3.3 Queue Configuration

#### **Timeout Patterns**
| Timeout | Jobs |
|---------|------|
| 120s | UpdateUserJob, VerifySubscriber, VerifyAndCreateSubscriber |
| 300s | RunCampaign |
| 600s | SendMessage (+ Base default) |
| 3600s | ExportCampaignLog, ExportSubscribersJob |
| 7200s | ImportBlacklistJob, ImportSubscribers2, ImportSubscribersJob, LoadCampaign, RunAutomation |
| 14400s | VerifyMailListJob |

#### **Retry Strategies**
1. **No Retry (Default)**: Most jobs via Base class
2. **12-Hour Retry**: SendMessage, VerifySubscriber
3. **60-Second Release**: On RateLimitExceeded

#### **Unique Jobs**
Jobs that prevent duplicates:
- **UpdateCampaignJob** - 1 hour uniqueness by campaign ID
- **UpdateMailListJob** - 1 hour uniqueness by list ID

---

## 4. Critical Email Processing Workflow

### 4.1 Campaign Sending Flow

```
RunCampaign (orchestrator)
    ↓
    Campaign->run() creates batch
    ↓
LoadCampaign (batch job)
    ↓
    Loads 100-109 subscribers
    ↓
    Dispatches SendMessage jobs to batch
    ↓
SendMessage (per subscriber)
    ↓
    1. Check rate limits (server + subscription)
    2. Prepare email message
    3. Send via server
    4. Track delivery
    5. Handle rate limit exceptions (release)
    6. Handle errors (log or fail)
```

### 4.2 Batch Processing Pattern

**Batch-Enabled Jobs**:
1. **ImportSubscribers2** → **VerifyAndCreateSubscriber**
2. **VerifyMailListJob** → **VerifySubscriber**
3. **LoadCampaign** → **SendMessage**

**Batch Benefits**:
- Progress tracking across multiple jobs
- Cancellation support
- Memory efficiency
- Parallel processing

### 4.3 Progress Monitoring (Trackable Trait)

**Standard Progress Data Structure**:
```json
{
    "percentage": 0,
    "total": 0,
    "processed": 0,
    "failed": 0,
    "message": "Status message",
    "filepath": "/path/to/file",
    "logfile": "/path/to/log"
}
```

**Jobs Using Progress Tracking**:
- ExportCampaignLog
- ExportSubscribersJob
- ImportBlacklistJob
- ImportSubscribers2
- ImportSubscribersJob
- LoadCampaign
- RunCampaign
- VerifyMailListJob

---

## 5. Dependencies Summary

### 5.1 External Dependencies

| Package/Service | Used By | Purpose |
|-----------------|---------|---------|
| Monolog | ImportSubscribers2, ImportSubscribersJob, VerifyAndCreateSubscriber | Detailed logging |
| Laravel Batches | 6 jobs | Batch processing |
| Swift_Mime | SendMessage | Email message preparation |
| Rate Limiting Service | SendMessage, VerifySubscriber | Quota management |
| Verification Service | VerifySubscriber, VerifyAndCreateSubscriber | Email verification |

### 5.2 Internal Dependencies

| Model/Service | Critical Jobs | Purpose |
|---------------|---------------|---------|
| Campaign | SendMessage, RunCampaign, LoadCampaign | Email campaigns |
| Subscriber | SendMessage, VerifySubscriber | Recipients |
| SendingServer | SendMessage, VerifySubscriber | SMTP/API delivery |
| MailList | Import/Export/Update jobs | Subscriber lists |
| Automation | RunAutomation, ForceTriggerAutomation | Workflows |
| Subscription | SendMessage, VerifySubscriber | Plan limits |

### 5.3 Queue Relationships

```
RunCampaign
└── Creates Batch
    └── LoadCampaign (multiple instances)
        └── SendMessage (100-109 per LoadCampaign)

ImportSubscribers2
└── Creates Batch
    └── VerifyAndCreateSubscriber (per CSV row)

VerifyMailListJob
└── Creates Batch
    └── VerifySubscriber (1000 per batch)
```

---

## 6. Critical Jobs for Email Sending

### Priority 1: Core Email Delivery
1. **SendMessage** - Actual email transmission
2. **LoadCampaign** - Subscriber loading and job creation
3. **RunCampaign** - Campaign orchestration

### Priority 2: Campaign Support
4. **SendConfirmationEmailJob** - Opt-in confirmations
5. **ExecuteCampaignCallback** - Webhook integrations

### Priority 3: Automation
6. **RunAutomation** - Triggered email workflows
7. **ForceTriggerAutomation** - Manual automation execution

### Priority 4: List Management
8. **UpdateMailListJob** - List cache and blacklist
9. **ImportSubscribersJob** - Subscriber imports
10. **VerifyMailListJob** - Email verification

---

## 7. Reliability & Error Handling Patterns

### 7.1 Fail-Fast Pattern
Most jobs inherit from **Base** class:
- Single attempt only (`tries = 1`)
- Fail immediately on timeout
- No automatic retries

### 7.2 Retry-Until Pattern
**SendMessage** and **VerifySubscriber**:
- Retry up to 12 hours
- Release for 60 seconds on rate limit
- Prevents permanent failures from temporary issues

### 7.3 Graceful Degradation
**VerifySubscriber**:
- Silently skips slow verifications
- Logs errors but continues processing
- Prevents one failure from blocking entire batch

### 7.4 Thread-Safe Progress Updates
**VerifyAndCreateSubscriber**:
```php
$this->jobMonitor->withExclusiveLock(function ($jobMonitor) use ($success) {
    // Atomic progress update
});
```

---

## 8. Performance Considerations

### 8.1 Memory Management
- **LoadCampaign**: Limits to 100-109 subscribers per job
- **VerifyMailListJob**: Processes 1000 subscribers per batch
- **ImportSubscribers2**: Batch processing prevents memory leaks

### 8.2 Timeout Hierarchy
- Quick operations: 120-300s
- Imports/Exports: 3600-7200s
- Verification: 14400s (longest)

### 8.3 Rate Limit Strategy
- Release jobs for 60 seconds on quota exceeded
- Retry up to 12 hours for transient failures
- Separate trackers for server and subscription limits

---

## 9. Logging & Monitoring

### 9.1 Dedicated Log Files
Jobs with file logging:
- ImportSubscribersJob: `$file.log`
- ImportSubscribers2: `$file.log`

### 9.2 Progress Tracking
- Real-time percentage updates
- Processed/failed counters
- Human-readable status messages
- File paths for exports

### 9.3 Campaign Logging
All campaign jobs use `$campaign->logger()` for:
- Sending progress
- Rate limit warnings
- Error messages
- Automation execution logs

---

## 10. Migration Recommendations

### 10.1 Job Modernization
- **ImportSubscribersJob** → Migrate to **ImportSubscribers2** (batch-based)
- Consider adding `Trackable` to non-tracked jobs
- Standardize timeout values

### 10.2 Queue Configuration
Recommended queue names:
- `emails` - SendMessage, SendConfirmationEmailJob
- `campaigns` - RunCampaign, LoadCampaign
- `automations` - RunAutomation, ForceTriggerAutomation
- `imports` - All import jobs
- `exports` - All export jobs
- `verification` - All verification jobs
- `default` - Cache updates and misc jobs

### 10.3 Monitoring Priorities
High-priority monitoring:
1. SendMessage failure rate
2. Campaign completion time
3. Rate limit exceptions
4. Import/export success rates
5. Verification service availability

---

## 11. Code Quality Observations

### 11.1 Strengths
✅ Consistent use of Base class for shared configuration
✅ Comprehensive exception handling
✅ Rate limiting and quota management
✅ Batch processing for scalability
✅ Progress tracking via Trackable trait
✅ Locale support for internationalization
✅ Thread-safe operations where needed

### 11.2 Areas for Improvement
⚠️ Some jobs lack timeout configuration (inherit default)
⚠️ Mixed timeout values (120s to 14400s)
⚠️ Inconsistent use of Trackable trait
⚠️ Some jobs could benefit from batch processing
⚠️ Limited documentation in job classes

---

## 12. Testing Recommendations

### 12.1 Critical Test Coverage
- SendMessage rate limiting and retry logic
- LoadCampaign batch creation
- RunCampaign error handling
- Import/Export progress tracking
- Verification with various error scenarios

### 12.2 Integration Tests
- Full campaign sending workflow
- Batch job cancellation
- Rate limit exception handling
- Progress monitoring accuracy

### 12.3 Load Tests
- Campaign with 100k+ subscribers
- Concurrent imports
- Rate limit threshold testing
- Memory usage during batch processing

---

## Conclusion

The Acelle job system is well-architected with clear separation of concerns, robust error handling, and scalable batch processing. The core email sending pipeline (RunCampaign → LoadCampaign → SendMessage) is production-ready with comprehensive rate limiting and retry mechanisms.

**Key Takeaways**:
1. **SendMessage** is the most critical job - handles actual delivery
2. Batch processing prevents memory issues in large operations
3. Rate limiting is implemented at multiple levels
4. Progress tracking provides real-time visibility
5. Fail-fast philosophy with targeted retry strategies

**Next Steps**:
- Migrate ImportSubscribersJob to batch-based ImportSubscribers2
- Standardize queue assignments by job type
- Implement comprehensive monitoring for critical jobs
- Document queue configuration requirements
- Add integration tests for full workflows
