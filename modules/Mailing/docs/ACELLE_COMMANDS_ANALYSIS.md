# Acelle Mail - Artisan Commands Analysis

**Analysis Date:** 2026-01-29
**Source Path:** `/Users/functionbytes/Function/Coding/acelle/app/Console/`
**Purpose:** Complete documentation of Acelle Mail custom Artisan commands for integration into Mailing module

---

## Table of Contents

1. [Overview](#overview)
2. [Command Scheduling (Kernel)](#command-scheduling-kernel)
3. [Campaign Management Commands](#campaign-management-commands)
4. [Email Handling Commands](#email-handling-commands)
5. [Maintenance Commands](#maintenance-commands)
6. [Plugin Management Commands](#plugin-management-commands)
7. [Translation Management Commands](#translation-management-commands)
8. [Template Management Commands](#template-management-commands)
9. [Infrastructure Commands](#infrastructure-commands)
10. [Integration Recommendations](#integration-recommendations)

---

## Overview

Acelle Mail uses a comprehensive set of Artisan commands to handle email campaigns, bounce/feedback processing, maintenance tasks, and system operations. All commands are registered automatically through the Laravel 11+ auto-discovery mechanism in `app/Console/Kernel.php`.

### Command Files Located In:
```
/Users/functionbytes/Function/Coding/acelle/app/Console/Commands/
├── RunHandler.php              # Bounce & feedback loop processing
├── TestCampaign.php           # Campaign testing utilities
├── SystemCleanup.php          # Database & log cleanup
├── VerifySender.php           # Email sender verification
├── GeoIpCheck.php             # GeoIP database setup
├── LoadPlugin.php             # Plugin installation
├── InitPlugin.php             # Plugin scaffolding
├── MergeTranslationFiles.php  # Translation file merging
├── UpgradeTranslation.php     # Translation updates
└── ResetTemplates.php         # Template reset utility
```

---

## Command Scheduling (Kernel)

### File: `app/Console/Kernel.php`

The Kernel class defines the complete scheduling strategy for Acelle Mail operations.

#### Scheduled Tasks Overview

| Task | Frequency | Command/Closure | Purpose |
|------|-----------|-----------------|---------|
| **Cronjob Event** | Every minute | Closure | Logs cronjob execution events |
| **Automation Runner** | Every 5 minutes | Closure | Executes `Automation2::run()` for workflow automation |
| **Bounce/Feedback Handler** | Every 30 minutes | `handler:run` | Processes bounce and feedback loop emails |
| **Queue Worker** | Every minute | `queue:work` | Processes default and batch queues (180s max) |
| **Sender Verification** | Every 5 minutes | `sender:verify` | Verifies pending email senders |
| **System Cleanup** | Daily | `system:cleanup` | Cleans old logs and orphaned records |
| **GeoIP Check** | Every minute | `geoip:check` | Installs/verifies GeoIP database |
| **Subscription Monitor** | Every 5 minutes | Closure | Handles subscription expiration and renewals |
| **Scheduled Campaigns** | Every minute | Closure | Executes `Campaign::checkAndExecuteScheduledCampaigns()` |
| **License Verification** | Weekly (random) | Closure | Verifies software license (Mon-Sat 10:10-10:59) |

#### Key Scheduling Details

**Queue Worker Configuration:**
```php
// Primary queue worker
$schedule->command('queue:work --queue=default,batch --timeout=120 --tries=1 --max-time=180')
    ->everyMinute();
```
- Processes both `default` and `batch` queues
- Timeout: 120 seconds per job
- Max execution time: 180 seconds (3 minutes)
- Single attempt per job (no retries)

**Campaign Execution:**
```php
$schedule->call(function () {
    Campaign::checkAndExecuteScheduledCampaigns();
})->name('check_and_execute_scheduled_campaigns')->everyMinute();
```
- Checks for scheduled campaigns every minute
- Executes campaigns that match their scheduled time
- Critical for time-sensitive email delivery

**Automation System:**
```php
$schedule->call(function () {
    Automation2::run();
})->name('automation:run')->everyFiveMinutes();
```
- Executes workflow-based automations
- Runs every 5 minutes to balance performance and responsiveness

**Subscription Management:**
```php
$schedule->call(function () {
    SubscriptionFacade::endExpiredSubscriptions();
    SubscriptionFacade::createRenewInvoices();
    SubscriptionFacade::autoChargeRenewInvoices();
})->name('subscription:monitor')->everyFiveMinutes();
```
- Ends expired subscriptions
- Generates renewal invoices
- Auto-charges renewal payments

#### System Safety Checks

**Cronjob Execution Safety:**
```php
Notification::recordIfFails(function () {
    if (!exec_enabled()) {
        throw new Exception('The exec() function is missing or disabled');
    }

    if (exec('whoami') == 'root') {
        throw new Exception("Cronjob process is executed by 'root'...");
    }
}, 'CronJob issue');
```
- Validates `exec()` function availability
- Prevents running as root user (permission issues)
- Records notifications on failure

---

## Campaign Management Commands

### 1. Test Campaign Command

**File:** `app/Console/Commands/TestCampaign.php`

**Signature:** `campaign:test`

**Purpose:** Development utility for testing SMTP and IMAP connections.

**Features:**
- Tests SMTP email sending via Swift Mailer
- Tests IMAP connection and email retrieval
- Uses ElasticEmail as example provider

**Code Highlights:**

```php
public function testSmtp()
{
    $transport = new \Swift_SmtpTransport('smtp.elasticemail.com', 2525, 'tls');
    $transport->setUsername('');
    $transport->setPassword('');

    $mailer = new \Swift_Mailer($transport);
    $message = new ExtendedSwiftMessage('Wonderful Subject');
    $message->setFrom(array('' => 'Awsome Sender'));
    $message->setTo(array('' => 'Awsome Recipient'));
    $message->setBody('Here is the message itself');

    $mailer->send($message);
}

public function testImap()
{
    $imapPath = "{mail.example.com:993/imap/tls}INBOX";
    $inbox = imap_open($imapPath, 'user@example.com', 'password');
    $emails = imap_search($inbox, 'UNSEEN');

    if (!empty($emails)) {
        foreach ($emails as $message) {
            var_dump($message);
        }
    }

    imap_expunge($inbox);
    imap_close($inbox);
}
```

**Integration Notes:**
- This is a **development/testing command only**
- Not scheduled - manual execution
- Uses `ExtendedSwiftMessage` (Acelle custom class)
- Hardcoded credentials should be replaced with config values

---

## Email Handling Commands

### 1. Bounce & Feedback Handler

**File:** `app/Console/Commands/RunHandler.php`

**Signature:** `handler:run`

**Scheduled:** Every 30 minutes

**Purpose:** Processes bounce emails and feedback loop reports to maintain sender reputation and list hygiene.

**Flow:**

```php
public function handle()
{
    $lock = new Lockable(storage_path('locks/bounce-feedback-handler'));
    $lock->getExclusiveLock(function () {
        $this->execRunHandler();
    }, $timeout = 5, $timeoutCallback);

    return 0;
}

private function execRunHandler()
{
    // Process bounce handlers
    $handlers = BounceHandler::get();
    foreach ($handlers as $handler) {
        $handler->start();
    }

    // Process feedback loop handlers
    $handlers = FeedbackLoopHandler::get();
    foreach ($handlers as $handler) {
        $handler->start();
    }
}
```

**Key Features:**

1. **Lock Mechanism:** Uses `Lockable` to prevent concurrent execution
2. **Bounce Processing:** Connects to bounce mailboxes via IMAP
3. **Feedback Loops:** Processes ISP complaint reports (spam reports)
4. **Sequential Execution:** Processes all handlers one by one
5. **Logging:** Detailed logs for each handler execution

**Models Used:**
- `Acelle\Model\BounceHandler`
- `Acelle\Model\FeedbackLoopHandler`

**Integration Recommendations:**
- Essential for maintaining email deliverability
- Should run every 30-60 minutes
- Monitor lock file at `storage/locks/bounce-feedback-handler`
- Requires proper IMAP credentials for bounce mailboxes

### 2. Sender Verification

**File:** `app/Console/Commands/VerifySender.php`

**Signature:** `sender:verify`

**Scheduled:** Every 5 minutes

**Purpose:** Verifies pending email sender addresses (SPF/DKIM/domain verification).

**Implementation:**

```php
public function handle()
{
    $senders = Sender::pending()->get();
    foreach ($senders as $sender) {
        $sender->updateVerificationStatus();
    }
    return 0;
}
```

**Features:**
- Queries all senders with `pending` status
- Calls `updateVerificationStatus()` method on each sender
- Likely performs DNS checks (SPF, DKIM, DMARC)
- Updates verification status in database

**Model:** `Acelle\Model\Sender`

**Integration Notes:**
- Critical for sender reputation
- Runs frequently (every 5 minutes) to reduce verification delay
- No locking mechanism - assumes fast execution

---

## Maintenance Commands

### 1. System Cleanup

**File:** `app/Console/Commands/SystemCleanup.php`

**Signature:** `system:cleanup`

**Scheduled:** Daily

**Purpose:** Database maintenance and cleanup of old records.

**Current Implementation:**

```php
public function handle()
{
    /*
    // Delete old log
    Log::where('created_at', '<', new Carbon('1 year ago'))->delete();

    // Delete orphan subscription
    $query = Subscription::leftJoin('customers', 'subscriptions.customer_id', '=', 'customers.id')
        ->whereNull('customers.id');
    if ($query->count()) {
        LaravelLog::warning('Orphan subscriptions');
        $query->delete();
    }
    */
    return 0;
}
```

**Status:** Currently commented out (placeholder implementation)

**Intended Features:**
1. Delete logs older than 1 year
2. Remove orphaned subscription records
3. Clean up dangling database references

**Integration Recommendations:**
- Implement actual cleanup logic based on requirements
- Consider cleaning:
  - Old tracking logs
  - Expired campaign data
  - Temporary files
  - Unverified subscribers (old)
  - Failed job records

### 2. GeoIP Database Check

**File:** `app/Console/Commands/GeoIpCheck.php`

**Signature:** `geoip:check`

**Scheduled:** Every minute (with overlapping prevention)

**Purpose:** Downloads and installs GeoIP database for location-based tracking.

**Implementation:**

```php
public function handle()
{
    $lock = new Lockable(storage_path('locks/geoip-setup'));
    $lock->getExclusiveLock(function () {
        $this->check();
    }, $timeout = 5, $timeoutCallback);

    return 0;
}

public function check()
{
    $geoip = App::make('Acelle\Library\Contracts\GeoIpInterface');

    if (Setting::get('geoip.enabled') == 'installing') {
        return;
    }

    if (Setting::isYes('geoip.enabled')) {
        return;
    }

    Setting::set('geoip.enabled', 'installing');

    Notification::warning([
        'title' => 'GeoIP setup',
        'message' => 'GeoIP database is being installed in the background...'
    ]);

    try {
        $geoip->setup();
        Setting::setYes('geoip.enabled');
        Notification::cleanupDuplicateNotifications('GeoIP setup');
    } catch (Exception $ex) {
        Notification::warning([
            'title' => 'GeoIP setup',
            'message' => 'Cannot install GeoIp database. Error: '.$ex->getMessage(),
        ]);
        throw $ex;
    }
}
```

**Features:**
1. **Lock Mechanism:** Prevents concurrent installation
2. **Status Tracking:** Uses settings to track installation state
3. **Notifications:** Informs admins about installation progress
4. **Error Handling:** Records failures in notifications
5. **One-time Setup:** Only runs installation once

**Settings Used:**
- `geoip.enabled`: `'no'` | `'installing'` | `'yes'`

**Integration Notes:**
- Downloads large GeoIP database files
- Can take several minutes on first run
- Monitors `storage/locks/geoip-setup` for lock status

---

## Plugin Management Commands

### 1. Load Plugin

**File:** `app/Console/Commands/LoadPlugin.php`

**Signature:** `plugin:load {name}`

**Usage:** `php artisan plugin:load awesome/hello`

**Purpose:** Installs and activates a plugin from the plugins directory.

**Implementation:**

```php
public function handle()
{
    $name = $this->argument('name');
    \Acelle\Model\Plugin::installFromDir($name);

    echo "\e[32mPlugin \e[35m{$name}\033[0m \e[32mloaded!\n\033[0m";
}
```

**Features:**
- Installs plugin from `storage/app/plugins/{vendor}/{name}`
- Registers plugin routes, views, and services
- Colored console output for success

**Model:** `Acelle\Model\Plugin`

### 2. Initialize Plugin

**File:** `app/Console/Commands/InitPlugin.php`

**Signature:** `plugin:init {name}`

**Usage:** `php artisan plugin:init awesome/my_plugin`

**Purpose:** Creates a sample plugin scaffold with boilerplate code.

**Implementation:**

```php
public function handle()
{
    $name = $this->argument('name');
    Plugin::init($name);

    echo "\e[32mPlugin \e[35m{$name}\033[0m \e[32mcreated & loaded!\n";
    echo "You can find its source files in the \e[35m./storage/app/plugins/{$name}\033[0m \e[32mfolder\n\033[0m";
}
```

**Features:**
- Generates plugin directory structure
- Creates sample files (routes, controllers, views)
- Automatically loads the plugin after creation

**Plugin Directory:** `./storage/app/plugins/{vendor}/{name}/`

---

## Translation Management Commands

### 1. Merge Translation Files

**File:** `app/Console/Commands/MergeTranslationFiles.php`

**Signature:** `translation:merge {current} {update}`

**Usage:** `php artisan translation:merge resources/lang/es/messages.php new-spanish.php`

**Purpose:** Merges translation keys from one file into another (for updating translations).

**Implementation:**

```php
public function handle()
{
    $current = $this->argument('current');
    $update = $this->argument('update');

    $maindir = realpath(resource_path('lang/en'));

    if (strpos(realpath($current), $maindir) === 0) {
        throw new \Exception('Cannot update a translation file of the main language (EN)');
    }

    updateTranslationFile(
        $current,
        $update,
        $overwrite = true,
        $deleteTargetKeys = false,
        $sort = true
    );
}
```

**Features:**
1. **Protection:** Prevents updating English (main) language files
2. **Overwrite Mode:** Overwrites existing keys
3. **Sorting:** Alphabetically sorts keys
4. **Preservation:** Does not delete target keys

**Use Cases:**
- Updating translations from external sources
- Merging community-contributed translations
- Applying translation updates from Acelle releases

**Important Warning:**
> Do not merge any files under `lang/en/` folder (which is considered the main language) or it may add redundant keys to the main file which will in turn propagate to the other files of other languages.

### 2. Upgrade Translation

**File:** `app/Console/Commands/UpgradeTranslation.php`

**Signature:** `translation:upgrade`

**Purpose:** Updates all language files to match the English (default) language structure.

**Implementation:**

```php
public function handle()
{
    \Acelle\Helpers\pcopy(resource_path('lang/en'), resource_path('lang/default'));
    Language::dump();
}
```

**Features:**
1. **Copy Default:** Copies English translations to `lang/default`
2. **Dump Languages:** Calls `Language::dump()` to regenerate all language files

**Model:** `Acelle\Model\Language`

**Use Cases:**
- After adding new translation keys to English
- Before releasing new versions
- Ensuring all languages have all keys (even if untranslated)

---

## Template Management Commands

### 1. Reset Templates

**File:** `app/Console/Commands/ResetTemplates.php`

**Signature:** `template:reset`

**Purpose:** Resets email and popup templates to default state.

**Implementation:**

```php
public function handle()
{
    Template::resetDefaultTemplates();
    Template::resetPopupTemplates();
    return 0;
}
```

**Features:**
- Resets default email campaign templates
- Resets popup form templates
- Useful for recovering from template corruption

**Model:** `Acelle\Model\Template`

**Methods Called:**
- `Template::resetDefaultTemplates()` - Restores default campaign templates
- `Template::resetPopupTemplates()` - Restores default popup templates

**Use Cases:**
- Template database corruption
- After failed template imports
- Restoring demo/sample templates

---

## Infrastructure Commands

### Queue Worker Details

**Scheduled Command:**
```php
$schedule->command('queue:work --queue=default,batch --timeout=120 --tries=1 --max-time=180')
    ->everyMinute();
```

**Configuration:**
- **Queues:** `default`, `batch`
- **Timeout:** 120 seconds per job
- **Tries:** 1 (no retries on failure)
- **Max Time:** 180 seconds total execution
- **Frequency:** Every minute

**Job Types Processed:**
1. **Campaign Sending Jobs:** Processes email sending batches
2. **Import Jobs:** Handles subscriber imports
3. **Export Jobs:** Generates CSV exports
4. **Batch Operations:** Bulk subscriber operations

**Alternative Configuration (Commented):**
```php
// Make it more likely to have a running queue at any given time
// $schedule->command('queue:work --queue=default,batch --timeout=120 --tries=1 --max-time=290')
//     ->everyFiveMinutes();
```
- Would run every 5 minutes with 290s max time
- Provides better overlap coverage
- Currently disabled in favor of every-minute execution

---

## Integration Recommendations

### For Mailing Module Integration

#### 1. Essential Commands to Implement

**High Priority:**
- `handler:run` - Bounce and feedback processing (critical for deliverability)
- `sender:verify` - Sender verification (SPF/DKIM checks)
- Campaign scheduling mechanism (similar to `Campaign::checkAndExecuteScheduledCampaigns()`)
- Queue worker setup for campaign sending

**Medium Priority:**
- `system:cleanup` - Implement cleanup for tracking logs, old campaigns
- GeoIP tracking (if location-based analytics needed)
- Translation management (if multi-language support planned)

**Low Priority:**
- Plugin system (if extensibility needed)
- Template reset utilities (nice-to-have for admin tools)

#### 2. Scheduling Strategy for Laravel 12

In Laravel 12, schedule in `bootstrap/app.php` or `routes/console.php`:

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('mailing:process-bounces')->everyThirtyMinutes();
Schedule::command('mailing:verify-senders')->everyFiveMinutes();
Schedule::command('mailing:cleanup')->daily();
Schedule::command('queue:work --queue=mailing,default --timeout=120 --tries=1 --max-time=180')
    ->everyMinute();
```

#### 3. Lock Mechanism Implementation

Use Acelle's `Lockable` pattern or Laravel's cache locks:

```php
use Illuminate\Support\Facades\Cache;

$lock = Cache::lock('bounce-handler', 300);

if ($lock->get()) {
    try {
        // Process bounces
    } finally {
        $lock->release();
    }
}
```

#### 4. Queue Configuration

**Redis Queue Setup (Recommended):**
```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 120,
        'block_for' => null,
    ],
],
```

**Supervisor Configuration:**
```ini
[program:mailing-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --queue=mailing,default --sleep=3 --tries=1 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/mailing-worker.log
```

#### 5. Monitoring and Notifications

Implement notification system similar to Acelle:

```php
use Acelle\Model\Notification;

Notification::recordIfFails(function () {
    // Risky operation
}, 'Task Description', $exceptionCallback);
```

Or use Laravel's built-in error tracking:

```php
use Illuminate\Support\Facades\Log;

try {
    // Critical operation
} catch (\Exception $e) {
    Log::channel('slack')->error('Bounce handler failed', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
```

#### 6. Testing Strategy

**Feature Tests for Commands:**
```php
namespace Tests\Feature\Console;

use Tests\TestCase;

class BounceHandlerTest extends TestCase
{
    public function test_bounce_handler_processes_bounces()
    {
        // Arrange: Create test bounce emails

        // Act: Run command
        $this->artisan('mailing:process-bounces')
            ->assertExitCode(0);

        // Assert: Verify bounces processed
    }
}
```

#### 7. Configuration Management

Store command settings in database like Acelle:

```php
// Database: settings table
Setting::set('bounce_handler.frequency', 30); // minutes
Setting::set('sender_verification.enabled', true);
Setting::set('geoip.enabled', true);
```

Or use Laravel config with environment overrides:

```php
// config/mailing.php
return [
    'bounce_handler' => [
        'enabled' => env('MAILING_BOUNCE_HANDLER_ENABLED', true),
        'frequency' => env('MAILING_BOUNCE_FREQUENCY', 30),
    ],
    'sender_verification' => [
        'enabled' => env('MAILING_SENDER_VERIFY_ENABLED', true),
        'frequency' => env('MAILING_SENDER_VERIFY_FREQUENCY', 5),
    ],
];
```

---

## Command Summary Table

| Command | Signature | Frequency | Purpose | Critical |
|---------|-----------|-----------|---------|----------|
| Bounce Handler | `handler:run` | Every 30 min | Process bounces & feedback loops | Yes |
| Sender Verify | `sender:verify` | Every 5 min | Verify email senders (SPF/DKIM) | Yes |
| Queue Worker | `queue:work` | Every minute | Process campaign/import/export jobs | Yes |
| Campaign Check | Closure | Every minute | Execute scheduled campaigns | Yes |
| Automation Run | Closure | Every 5 min | Run workflow automations | Medium |
| System Cleanup | `system:cleanup` | Daily | Clean old logs/records | Medium |
| GeoIP Check | `geoip:check` | Every minute | Install/verify GeoIP database | Low |
| Subscription Monitor | Closure | Every 5 min | Handle subscription renewals | Medium |
| License Verify | Closure | Weekly | Verify software license | Low |
| Test Campaign | `campaign:test` | Manual | Test SMTP/IMAP connections | Dev Only |
| Plugin Load | `plugin:load` | Manual | Install plugin | Optional |
| Plugin Init | `plugin:init` | Manual | Create plugin scaffold | Optional |
| Translation Merge | `translation:merge` | Manual | Merge translation files | Optional |
| Translation Upgrade | `translation:upgrade` | Manual | Update all language files | Optional |
| Template Reset | `template:reset` | Manual | Reset templates to default | Optional |

---

## Key Takeaways

### Critical Infrastructure
1. **Queue System:** Core to campaign sending - runs every minute
2. **Bounce Processing:** Essential for deliverability - every 30 minutes
3. **Sender Verification:** Required for reputation - every 5 minutes
4. **Campaign Scheduling:** Time-sensitive - every minute check

### Lock Mechanisms
- Acelle uses custom `Lockable` class with file-based locks
- Located in `storage/locks/` directory
- Prevents concurrent execution of long-running tasks
- Timeout handling to avoid deadlocks

### Notification System
- Records failures in database notifications
- Admin dashboard displays issues
- Critical for monitoring system health
- Used extensively in scheduled tasks

### Performance Considerations
- Queue worker timeout: 120 seconds per job
- Max execution time: 180 seconds per cycle
- No job retries (tries=1) to prevent duplicate sends
- Overlapping prevention on GeoIP check

### Database Patterns
- Uses Eloquent ORM exclusively
- Settings stored in `settings` table
- Heavy use of model scopes (e.g., `Sender::pending()`)
- Status tracking via database columns

---

## Next Steps for Mailing Module

1. **Implement Core Commands:**
   - Port bounce/feedback handler logic
   - Create sender verification system
   - Set up campaign scheduling mechanism

2. **Configure Queue System:**
   - Set up Redis for queues
   - Configure Supervisor for workers
   - Implement job classes for campaign sending

3. **Add Monitoring:**
   - Implement notification system
   - Set up logging for critical operations
   - Create admin dashboard for command status

4. **Testing:**
   - Write feature tests for each command
   - Test lock mechanisms
   - Verify queue processing

5. **Documentation:**
   - Document custom commands in module docs
   - Create runbooks for troubleshooting
   - Write deployment guides

---

**Report Generated:** 2026-01-29
**Analyst:** Claude Code
**Source System:** Acelle Mail v6.x
**Target System:** Alsernet Mailing Module (Laravel 12)
