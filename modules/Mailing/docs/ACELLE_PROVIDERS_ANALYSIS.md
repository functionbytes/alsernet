# Acelle Service Providers Analysis

**Project:** Alsernet System - Mailing Module Migration
**Source:** `/Users/functionbytes/Function/Coding/acelle/app/Providers/`
**Analysis Date:** 2026-01-29
**Purpose:** Complete documentation of Acelle's service provider architecture for migration to Laravel 12 Mailing module

---

## Executive Summary

Acelle uses 9 custom service providers to bootstrap its email marketing functionality. The providers handle authentication, events, job monitoring, mail configuration, payment processing, routing, storage, hooks/plugins, and broadcasting.

### Critical Providers for Mailing Module

1. **MailerServiceProvider** - Core mail sending configuration
2. **AppServiceProvider** - Hook system, plugin loading, translations
3. **JobServiceProvider** - Queue monitoring for campaign jobs
4. **EventServiceProvider** - Campaign and subscriber events
5. **AuthServiceProvider** - Permission policies for mail entities

---

## 1. AppServiceProvider

**Path:** `Acelle\Providers\AppServiceProvider`
**Priority:** Critical
**Migration Status:** Partial (Hook system needed)

### Services Registered

#### Singleton Services

```php
// Hook Manager - Plugin/Extension System
$this->app->singleton(HookManager::class, function ($app) {
    return new HookManager();
});

// Subscription Manager - Recurring billing
$this->app->singleton(SubscriptionManager::class, function ($app) {
    return new SubscriptionManager();
});
```

#### Hook System Registrations

The provider registers 6 core translation file hooks:

- `add_translation_file` - messages.php
- `add_translation_file` - auth.php
- `add_translation_file` - pagination.php
- `add_translation_file` - passwords.php
- `add_translation_file` - builder.php (Email builder)
- `add_translation_file` - validation.php

#### Captcha Hook

```php
Hook::register('captcha_method', function () {
    return [
        "id" => 'recaptcha',
        "title" => trans('messages.recaptcha'),
    ];
});
```

### Bootstrap Configuration

#### PHP Settings Tweaks

```php
ini_set('memory_limit', '-1');
ini_set('pcre.backtrack_limit', 1000000000);
```

#### Laravel Configuration

```php
// Disable double encoding for Blade
Blade::withoutDoubleEncoding();

// Fix string length for MySQL indexes
Schema::defaultStringLength(191);

// HTTPS detection (including proxy)
if ($isSecure) {
    $this->app['request']->server->set('HTTPS', 'on');
    AppUrl::forceScheme('https');
}
```

#### Plugin System Boot

```php
if (!isInitiated()) {
    return; // Skip if not installed
}

// Load plugins without DB query (performance)
Plugin::autoloadWithoutDbQuery();

// Register plugin translation folders
foreach (Hook::execute('add_translation_file') as $source) {
    if (array_key_exists('translation_prefix', $source)) {
        $this->loadTranslationsFrom($folder, $prefix);
    }
}
```

### Custom Validation Rules (Deprecated)

```php
// Substring validator
Validator::extend('substring', function ($attribute, $value, $parameters, $validator) {
    return strpos($value, $parameters[0]) !== false;
});

// License validator (always passes)
Validator::extend('license', function ($attribute, $value, $parameters, $validator) {
    return true;
});

// License error validator (always fails)
Validator::extend('license_error', ...);
```

### Migration Notes

**Required for Mailing Module:**
- ✅ Hook system (HookManager) - for extensibility
- ✅ Translation file loading pattern
- ❌ Subscription manager - belongs in separate billing module
- ❌ License validators - legacy code
- ✅ Memory/PCRE limits - needed for large campaigns

**Recommended Approach:**
```php
// modules/Mailing/app/Providers/MailingServiceProvider.php
public function register()
{
    // Register hook system singleton
    $this->app->singleton(HookManager::class, function ($app) {
        return new HookManager();
    });

    // Register default mailing hooks
    Hook::register('add_translation_file', function () {
        return [
            "id" => "mailing_messages",
            "plugin_name" => "Mailing",
            "file_title" => "Messages",
            "translation_folder" => module_path('Mailing', 'resources/lang'),
            "file_name" => "messages.php",
        ];
    });
}

public function boot()
{
    // Set PHP limits for campaign processing
    ini_set('memory_limit', config('mailing.memory_limit', '512M'));
    ini_set('pcre.backtrack_limit', config('mailing.pcre_limit', 1000000));

    // Load translations from hooks
    foreach (Hook::execute('add_translation_file') as $source) {
        if (isset($source['translation_prefix']) && isset($source['translation_folder'])) {
            $this->loadTranslationsFrom($source['translation_folder'], $source['translation_prefix']);
        }
    }
}
```

---

## 2. AuthServiceProvider

**Path:** `Acelle\Providers\AuthServiceProvider`
**Priority:** Critical
**Migration Status:** Required with modifications

### Policy Mappings

#### Mailing-Related Policies (Must Migrate)

```php
protected $policies = [
    // Core mailing entities
    \Acelle\Model\Contact::class => \Acelle\Policies\ContactPolicy::class,
    \Acelle\Model\MailList::class => \Acelle\Policies\MailListPolicy::class,
    \Acelle\Model\Subscriber::class => \Acelle\Policies\SubscriberPolicy::class,
    \Acelle\Model\Segment::class => \Acelle\Policies\SegmentPolicy::class,
    \Acelle\Model\Campaign::class => \Acelle\Policies\CampaignPolicy::class,

    // Templates and layouts
    \Acelle\Model\Layout::class => \Acelle\Policies\LayoutPolicy::class,
    \Acelle\Model\Template::class => \Acelle\Policies\TemplatePolicy::class,

    // Sending infrastructure
    \Acelle\Model\SendingServer::class => \Acelle\Policies\SendingServerPolicy::class,
    \Acelle\Model\BounceHandler::class => \Acelle\Policies\BounceHandlerPolicy::class,
    \Acelle\Model\FeedbackLoopHandler::class => \Acelle\Policies\FeedbackLoopHandlerPolicy::class,
    \Acelle\Model\SendingDomain::class => \Acelle\Policies\SendingDomainPolicy::class,
    \Acelle\Model\TrackingDomain::class => \Acelle\Policies\TrackingDomainPolicy::class,

    // Verification and blacklisting
    \Acelle\Model\EmailVerificationServer::class => \Acelle\Policies\EmailVerificationServerPolicy::class,
    \Acelle\Model\Blacklist::class => \Acelle\Policies\BlacklistPolicy::class,

    // Senders
    \Acelle\Model\Sender::class => \Acelle\Policies\SenderPolicy::class,

    // Automation
    \Acelle\Model\Automation2::class => \Acelle\Policies\Automation2Policy::class,
];
```

#### Non-Mailing Policies (Exclude from Module)

```php
// User management - belongs in core system
\Acelle\Model\User::class => \Acelle\Policies\UserPolicy::class,
\Acelle\Model\Customer::class => \Acelle\Policies\CustomerPolicy::class,
\Acelle\Model\CustomerGroup::class => \Acelle\Policies\CustomerGroupPolicy::class,
\Acelle\Model\Admin::class => \Acelle\Policies\AdminPolicy::class,
\Acelle\Model\AdminGroup::class => \Acelle\Policies\AdminGroupPolicy::class,
\Acelle\Model\SubAccount::class => \Acelle\Policies\SubAccountPolicy::class,

// System settings - belongs in core
\Acelle\Model\Setting::class => \Acelle\Policies\SettingPolicy::class,
\Acelle\Model\Language::class => \Acelle\Policies\LanguagePolicy::class,
\Acelle\Model\Currency::class => \Acelle\Policies\CurrencyPolicy::class,

// Billing - separate module
\Acelle\Model\PlanGeneral::class => \Acelle\Policies\PlanPolicy::class,
\Acelle\Model\Subscription::class => \Acelle\Policies\SubscriptionPolicy::class,
\Acelle\Model\Invoice::class => \Acelle\Policies\InvoicePolicy::class,

// Plugins - core system
\Acelle\Model\Plugin::class => \Acelle\Policies\PluginPolicy::class,
\Acelle\Model\Source::class => \Acelle\Policies\SourcePolicy::class,
```

### Migration Implementation

```php
// modules/Mailing/app/Providers/AuthServiceProvider.php
namespace Modules\Mailing\app\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Lists and subscribers
        \Modules\Mailing\app\Models\MailList::class => \Modules\Mailing\app\Policies\MailListPolicy::class,
        \Modules\Mailing\app\Models\Subscriber::class => \Modules\Mailing\app\Policies\SubscriberPolicy::class,
        \Modules\Mailing\app\Models\Segment::class => \Modules\Mailing\app\Policies\SegmentPolicy::class,

        // Campaigns
        \Modules\Mailing\app\Models\Campaign::class => \Modules\Mailing\app\Policies\CampaignPolicy::class,

        // Templates
        \Modules\Mailing\app\Models\Layout::class => \Modules\Mailing\app\Policies\LayoutPolicy::class,
        \Modules\Mailing\app\Models\Template::class => \Modules\Mailing\app\Policies\TemplatePolicy::class,

        // Sending servers
        \Modules\Mailing\app\Models\SendingServer::class => \Modules\Mailing\app\Policies\SendingServerPolicy::class,
        \Modules\Mailing\app\Models\BounceHandler::class => \Modules\Mailing\app\Policies\BounceHandlerPolicy::class,
        \Modules\Mailing\app\Models\FeedbackLoopHandler::class => \Modules\Mailing\app\Policies\FeedbackLoopHandlerPolicy::class,
        \Modules\Mailing\app\Models\SendingDomain::class => \Modules\Mailing\app\Policies\SendingDomainPolicy::class,
        \Modules\Mailing\app\Models\TrackingDomain::class => \Modules\Mailing\app\Policies\TrackingDomainPolicy::class,

        // Automation
        \Modules\Mailing\app\Models\Automation::class => \Modules\Mailing\app\Policies\AutomationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
```

---

## 3. BroadcastServiceProvider

**Path:** `Acelle\Providers\BroadcastServiceProvider`
**Priority:** Low (Optional)
**Migration Status:** Optional - depends on real-time features

### Services Registered

```php
public function boot()
{
    Broadcast::routes();
    require base_path('routes/channels.php');
}
```

### Migration Notes

**Purpose:** Registers broadcasting routes for real-time features (campaign progress, live stats)

**Required If:**
- Real-time campaign progress tracking needed
- Live dashboard updates required
- WebSocket notifications for campaign events

**Alternative:** Use Laravel Reverb (already available in main app) instead of separate provider.

```php
// If needed, add to MailingServiceProvider
public function boot()
{
    // Register broadcasting channels for mailing module
    Broadcast::channel('campaign.{id}', function ($user, $id) {
        return $user->can('view', \Modules\Mailing\app\Models\Campaign::find($id));
    });
}
```

---

## 4. CheckoutServiceProvider

**Path:** `Acelle\Providers\CheckoutServiceProvider`
**Priority:** Excluded
**Migration Status:** Not needed for Mailing module

### Services Registered

```php
$this->app->singleton(BillingManager::class, function ($app) {
    $returnUrl = route('subscription.index');
    $manager = new BillingManager($returnUrl);

    // Payment gateways
    $manager->register('stripe', function () { return new StripePaymentGateway(); });
    $manager->register('braintree', function () { return new BraintreePaymentGateway(); });
    $manager->register('paypal', function () { return new PaypalPaymentGateway(); });
    $manager->register('offline', function () { return new OfflinePaymentGateway(); });
    $manager->register('coinpayments', function () { return new CoinpaymentsPaymentGateway(); });
    $manager->register('paystack', function () { return new PaystackPaymentGateway(); });
    $manager->register('razorpay', function () { return new RazorpayPaymentGateway(); });

    return $manager;
});
```

### Migration Notes

**Exclude Reason:** Payment processing is not part of mailing functionality. This belongs in a separate billing/subscription module or core system.

**Implements:** `DeferrableProvider` interface for lazy loading

---

## 5. EventServiceProvider

**Path:** `Acelle\Providers\EventServiceProvider`
**Priority:** Critical
**Migration Status:** Required with modifications

### Event-Listener Mappings

#### Campaign Events

```php
'Acelle\Events\CampaignUpdated' => [
    'Acelle\Listeners\CampaignUpdatedListener',
],
```

**Purpose:** Triggered when campaign is updated (status changes, paused, resumed)

#### Mail List Events

```php
'Acelle\Events\MailListUpdated' => [
    'Acelle\Listeners\MailListUpdatedListener',
],

'Acelle\Events\MailListImported' => [
    'Acelle\Listeners\TriggerAutomationForImportedContacts',
],
```

**Purpose:** List updates and import completion triggers

#### Subscription Events (Deprecated)

```php
'Acelle\Events\MailListSubscription' => [
    // Commented out - use subscriber instead
    // 'Acelle\Listeners\SendListNotificationToOwner',
    // 'Acelle\Listeners\SendListNotificationToSubscriber',
    // 'Acelle\Listeners\TriggerAutomation',
],

'Acelle\Events\MailListUnsubscription' => [
    // Commented out - use subscriber instead
],
```

**Note:** These are deprecated in favor of event subscribers.

#### Non-Mailing Events

```php
'Acelle\Events\UserUpdated' => [
    'Acelle\Listeners\UserUpdatedListener',
],

'Acelle\Events\CronJobExecuted' => [
    'Acelle\Listeners\CronJobExecutedListener',
],

'Acelle\Events\AdminLoggedIn' => [
    'Acelle\Listeners\AdminLoggedInListener',
],
```

**Exclude:** These belong in core system, not mailing module.

### Event Subscribers

```php
protected $subscribe = [
    'Acelle\Listeners\TriggerAutomation',
    'Acelle\Listeners\SendListNotificationToOwner',
    'Acelle\Listeners\SendListNotificationToSubscriber',
];
```

**Event Subscribers** handle multiple events with custom logic instead of simple event-listener mapping.

### Migration Implementation

```php
// modules/Mailing/app/Providers/EventServiceProvider.php
namespace Modules\Mailing\app\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Campaign events
        \Modules\Mailing\app\Events\CampaignUpdated::class => [
            \Modules\Mailing\app\Listeners\UpdateCampaignCache::class,
            \Modules\Mailing\app\Listeners\NotifyCampaignOwner::class,
        ],

        \Modules\Mailing\app\Events\CampaignSent::class => [
            \Modules\Mailing\app\Listeners\GenerateCampaignReport::class,
        ],

        // Mail list events
        \Modules\Mailing\app\Events\MailListUpdated::class => [
            \Modules\Mailing\app\Listeners\ClearListCache::class,
        ],

        \Modules\Mailing\app\Events\MailListImported::class => [
            \Modules\Mailing\app\Listeners\TriggerAutomationForImportedContacts::class,
            \Modules\Mailing\app\Listeners\NotifyListOwner::class,
        ],

        // Subscriber events
        \Modules\Mailing\app\Events\SubscriberSubscribed::class => [
            \Modules\Mailing\app\Listeners\SendWelcomeEmail::class,
            \Modules\Mailing\app\Listeners\TriggerSubscriptionAutomation::class,
        ],

        \Modules\Mailing\app\Events\SubscriberUnsubscribed::class => [
            \Modules\Mailing\app\Listeners\SendGoodbyeEmail::class,
            \Modules\Mailing\app\Listeners\UpdateListStatistics::class,
        ],
    ];

    protected $subscribe = [
        \Modules\Mailing\app\Listeners\AutomationEventSubscriber::class,
        \Modules\Mailing\app\Listeners\ListNotificationSubscriber::class,
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
```

---

## 6. JobServiceProvider

**Path:** `Acelle\Providers\JobServiceProvider`
**Priority:** Critical
**Migration Status:** Required (essential for campaign sending)

### Services Registered

**Mail Log Configuration:**

```php
private function initMailLog()
{
    MailLog::configure(storage_path().'/logs/' . php_sapi_name() . '/mail.log');
}
```

### Queue Event Monitoring

#### Job Processing Lifecycle

```php
Queue::before(function (JobProcessing $event) {
    $job = $this->getJobObject($event);
    if (property_exists($job, 'monitor')) {
        $monitor = $job->monitor;
        $monitor->setRunning(); // Mark job as running
    }
});
```

#### Job Completion

```php
Queue::after(function (JobProcessed $event) {
    $job = $this->getJobObject($event);
    if (property_exists($job, 'monitor')) {
        $monitor = $job->monitor;
        // Only update standalone jobs (not batch jobs)
        if (is_null($monitor->batch_id)) {
            $monitor->setDone();
        }
    }
});
```

#### Job Failure

```php
Queue::failing(function (JobFailed $event) {
    $job = $this->getJobObject($event);
    if (property_exists($job, 'monitor')) {
        $monitor = $job->monitor;
        // Only update standalone jobs
        if (is_null($monitor->batch_id)) {
            $monitor->setFailed($event->exception);
        }
    }
});
```

### Key Features

1. **Job Monitor Integration:** All jobs can have a `monitor` property for progress tracking
2. **Batch Job Handling:** Batch jobs are tracked separately, standalone jobs update individually
3. **Mail Logging:** Separate log file for mail-specific operations
4. **Exception Capture:** Failed jobs store exception details in monitor

### Migration Implementation

```php
// modules/Mailing/app/Providers/JobServiceProvider.php
namespace Modules\Mailing\app\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\{JobProcessed, JobFailed, JobProcessing};
use Modules\Mailing\app\Library\Log as MailLog;
use Illuminate\Support\Facades\Queue;

class JobServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Initialize mail logging
        MailLog::configure(storage_path('logs/mailing/mail.log'));

        // Track job execution for campaign sending
        Queue::before(function (JobProcessing $event) {
            $job = $this->getJobObject($event);

            if (property_exists($job, 'campaignMonitor')) {
                $job->campaignMonitor->markAsRunning();
            }
        });

        Queue::after(function (JobProcessed $event) {
            $job = $this->getJobObject($event);

            if (property_exists($job, 'campaignMonitor')) {
                // Only update if not part of batch
                if (!$job->campaignMonitor->isBatchJob()) {
                    $job->campaignMonitor->markAsCompleted();
                }
            }
        });

        Queue::failing(function (JobFailed $event) {
            $job = $this->getJobObject($event);

            if (property_exists($job, 'campaignMonitor')) {
                if (!$job->campaignMonitor->isBatchJob()) {
                    $job->campaignMonitor->markAsFailed($event->exception);
                }
            }
        });
    }

    private function getJobObject($event)
    {
        $data = $event->job->payload();
        return unserialize($data['data']['command']);
    }
}
```

**Usage in Campaign Jobs:**

```php
// modules/Mailing/app/Jobs/SendCampaignEmailJob.php
class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $campaignMonitor;

    public function __construct(Campaign $campaign, Subscriber $subscriber)
    {
        $this->campaign = $campaign;
        $this->subscriber = $subscriber;

        // Attach monitor for job tracking
        $this->campaignMonitor = $campaign->getJobMonitor();
    }

    public function handle()
    {
        // Send email logic
    }
}
```

---

## 7. MailerServiceProvider

**Path:** `Acelle\Providers\MailerServiceProvider`
**Priority:** Critical
**Migration Status:** Required (core mail functionality)

### Services Registered

**Service Name:** `xmailer` (Custom mail service binding)

**Implements:** `DeferrableProvider` for lazy loading

### Configuration Logic

```php
$this->app->bind('xmailer', function ($app) {
    $mailer = Setting::get('mailer.mailer') ?: Setting::get('mailer.driver');

    switch ($mailer) {
        case SendingServer::TYPE_SMTP:
            $server = SendingServerSmtp::instantiateFromSettings([
                'host' => Setting::get('mailer.host') ?? config('mail.host'),
                'smtp_port' => Setting::get('mailer.port') ?? config('mail.port'),
                'smtp_protocol' => Setting::get('mailer.encryption') ?? config('mail.encryption'),
                'smtp_username' => Setting::get('mailer.username') ?? config('mail.username'),
                'smtp_password' => Setting::get('mailer.password') ?? config('mail.password'),
                'from_name' => Setting::get('mailer.from.name') ?? config('mail.from.name'),
                'from_address' => Setting::get('mailer.from.address') ?? config('mail.from.address'),
            ]);
            break;

        case SendingServer::TYPE_SENDMAIL:
            $server = SendingServerSendmail::instantiateFromSettings([
                'sendmail_path' => Setting::get('mailer.sendmail_path') ?? config('mail.sendmail'),
                'from_name' => Setting::get('mailer.from.name') ?? config('mail.from.name'),
                'from_address' => Setting::get('mailer.from.address') ?? config('mail.from.address'),
            ]);
            break;

        default:
            throw new \Exception("Mail mailer '{$mailer}' not found", 1);
    }

    return $server;
});
```

### Key Features

1. **Dynamic Mailer Selection:** Choose between SMTP and Sendmail at runtime
2. **Settings Fallback:** Database settings override config file values
3. **Server Instantiation:** Creates sending server objects from settings
4. **Lazy Loading:** Only loads when `app('xmailer')` is called

### Supported Mail Types

- **SMTP:** Full SMTP server support with authentication
- **Sendmail:** System sendmail binary

### Migration Implementation

```php
// modules/Mailing/app/Providers/MailerServiceProvider.php
namespace Modules\Mailing\app\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use Modules\Mailing\app\Models\{SendingServerSmtp, SendingServerSendmail, SendingServer};
use Modules\Mailing\app\Models\MailingSetting;

class MailerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind('mailing.mailer', function ($app) {
            $settings = MailingSetting::getMailerSettings();
            $mailerType = $settings['driver'] ?? config('mailing.default_mailer');

            return match($mailerType) {
                'smtp' => SendingServerSmtp::fromSettings($settings),
                'sendmail' => SendingServerSendmail::fromSettings($settings),
                'ses' => SendingServerSes::fromSettings($settings),
                'mailgun' => SendingServerMailgun::fromSettings($settings),
                default => throw new \InvalidArgumentException("Unsupported mailer: {$mailerType}")
            };
        });

        // Alias for backward compatibility
        $this->app->alias('mailing.mailer', SendingServer::class);
    }

    public function provides(): array
    {
        return ['mailing.mailer', SendingServer::class];
    }
}
```

**Usage Example:**

```php
// Get configured mailer
$mailer = app('mailing.mailer');

// Send email through configured server
$mailer->send($message);
```

---

## 8. RouteServiceProvider

**Path:** `Acelle\Providers\RouteServiceProvider`
**Priority:** Medium
**Migration Status:** Adapt for module routes

### Configuration

```php
public const HOME = '/';

protected $namespace = 'Acelle\Http\Controllers';

public function boot()
{
    parent::boot();

    $this->configureRateLimiting();

    $this->routes(function () {
        // API routes
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));

        // Web routes
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    });
}

protected function configureRateLimiting()
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
    });
}
```

### Migration Notes

**Not Needed:** Laravel 12 modules auto-load routes from:
- `modules/Mailing/routes/web.php`
- `modules/Mailing/routes/api.php`

**If Custom Rate Limiting Required:**

```php
// modules/Mailing/app/Providers/MailingServiceProvider.php
public function boot()
{
    // Custom rate limiting for API endpoints
    RateLimiter::for('mailing-api', function (Request $request) {
        // Limit by campaign sending
        return Limit::perMinute(120)->by($request->user()->id ?? $request->ip());
    });

    RateLimiter::for('campaign-send', function (Request $request) {
        // Stricter limit for campaign sending
        return Limit::perHour(10)->by($request->user()->id);
    });
}
```

---

## 9. StorageServiceProvider

**Path:** `Acelle\Providers\StorageServiceProvider`
**Priority:** Medium (Optional)
**Migration Status:** Optional - depends on S3 usage

### Services Registered

**Service Name:** `xstore` (Custom S3 storage binding)

**Implements:** `DeferrableProvider`

### Configuration Logic

```php
$this->app->bind('xstore', function ($app) {
    // Parse S3 connection string: "s3:://apikey:secret@region:bucket"
    try {
        list($apiKey, $secret, $region, $bucket) = array_values(
            array_filter(
                preg_split('/(s3::\/\/)|([:@])/', Setting::get('storage.s3'))
            )
        );

        $service = new S3($apiKey, $secret, $region, $bucket);

        return $service;
    } catch (\Exception $ex) {
        return null;
    }
});
```

### Migration Notes

**Recommendation:** Use Laravel's built-in S3 storage instead.

**Modern Alternative:**

```php
// config/filesystems.php already has S3 support
use Illuminate\Support\Facades\Storage;

// Store campaign attachments
Storage::disk('s3')->put('campaigns/attachments/file.pdf', $content);

// Generate temporary URL
$url = Storage::disk('s3')->temporaryUrl('campaigns/attachments/file.pdf', now()->addHours(1));
```

**If Custom S3 Logic Needed:**

```php
// modules/Mailing/app/Providers/MailingServiceProvider.php
public function register()
{
    $this->app->singleton('mailing.storage', function ($app) {
        $driver = config('mailing.storage.driver', 's3');

        return match($driver) {
            's3' => new S3StorageAdapter(config('mailing.storage.s3')),
            'local' => new LocalStorageAdapter(config('mailing.storage.local')),
            default => Storage::disk($driver)
        };
    });
}
```

---

## Supporting Classes Analysis

### HookManager

**Path:** `Acelle\Library\HookManager`
**Purpose:** Plugin/extension system

#### Key Methods

```php
// Register hook callback
public function register($name, $callback)

// Register only if empty
public function registerIfEmpty($name, $callback)

// Execute all registered callbacks
public function execute($name, $params = []) // Returns array of results

// Execute last registered callback
public function perform($name, $params = []) // Returns single result

// Check if hook has callbacks
public function isEmpty($name)
```

#### Usage Pattern

```php
// Register hook
Hook::register('before_campaign_send', function($campaign) {
    // Pre-send validation
});

// Execute hooks
$results = Hook::execute('before_campaign_send', [$campaign]);

// Execute last hook only
$result = Hook::perform('get_campaign_stats', [$campaign]);
```

#### Migration Implementation

```php
// modules/Mailing/app/Library/HookManager.php
namespace Modules\Mailing\app\Library;

class HookManager
{
    protected array $hooks = [];

    public function register(string $name, callable $callback): void
    {
        $this->hooks[$name][] = $callback;
    }

    public function execute(string $name, array $params = []): array
    {
        if (!isset($this->hooks[$name])) {
            return [];
        }

        return array_map(
            fn($callback) => call_user_func_array($callback, $params),
            $this->hooks[$name]
        );
    }

    public function perform(string $name, array $params = [])
    {
        if (!isset($this->hooks[$name]) || empty($this->hooks[$name])) {
            throw new \Exception("Hook '{$name}' has no callbacks registered");
        }

        $lastCallback = end($this->hooks[$name]);
        return call_user_func_array($lastCallback, $params);
    }
}
```

### SubscriptionManager

**Path:** `Acelle\Library\SubscriptionManager`
**Purpose:** Recurring billing management

**Migration Status:** EXCLUDE - belongs in billing module

### BillingManager

**Path:** `Acelle\Library\BillingManager`
**Purpose:** Payment gateway registration

**Migration Status:** EXCLUDE - belongs in billing module

---

## Provider Registration Order

### Recommended Boot Sequence

1. **AppServiceProvider** (first - core services)
   - HookManager singleton
   - PHP configuration
   - Plugin loading

2. **AuthServiceProvider** (second - policies)
   - Register all mailing policies
   - Enable authorization checks

3. **EventServiceProvider** (third - event system)
   - Campaign events
   - Subscriber events
   - Automation triggers

4. **JobServiceProvider** (fourth - queue monitoring)
   - Job lifecycle tracking
   - Mail logging

5. **MailerServiceProvider** (fifth - mail configuration)
   - Sending server setup
   - Mailer binding

6. **RouteServiceProvider** (optional - if custom routes)
   - Rate limiting
   - Route registration

7. **BroadcastServiceProvider** (optional - if real-time)
   - Broadcasting channels

8. **StorageServiceProvider** (optional - if custom storage)
   - S3 configuration

### Laravel 12 Registration

```php
// bootstrap/providers.php
return [
    // Core app providers
    App\Providers\AppServiceProvider::class,

    // Module providers (auto-discovered)
    Modules\Mailing\app\Providers\MailingServiceProvider::class,
    Modules\Mailing\app\Providers\AuthServiceProvider::class,
    Modules\Mailing\app\Providers\EventServiceProvider::class,
    Modules\Mailing\app\Providers\JobServiceProvider::class,
    Modules\Mailing\app\Providers\MailerServiceProvider::class,
];
```

**Or use auto-discovery in module.json:**

```json
{
  "name": "Mailing",
  "providers": [
    "Modules\\Mailing\\app\\Providers\\MailingServiceProvider"
  ]
}
```

---

## Migration Checklist

### Critical Providers (Must Migrate)

- [x] **AppServiceProvider** - Hook system, PHP config, plugin loading
- [x] **AuthServiceProvider** - Mailing-related policies only
- [x] **EventServiceProvider** - Campaign and subscriber events
- [x] **JobServiceProvider** - Queue monitoring for sending jobs
- [x] **MailerServiceProvider** - Mail server configuration

### Optional Providers (Use if Needed)

- [ ] **BroadcastServiceProvider** - If real-time features required
- [ ] **StorageServiceProvider** - If custom S3 logic needed (prefer Laravel Storage)

### Excluded Providers (Don't Migrate)

- ❌ **CheckoutServiceProvider** - Billing/payment belongs in separate module
- ❌ **RouteServiceProvider** - Laravel 12 auto-loads module routes

### Supporting Classes to Migrate

- [x] **HookManager** - Plugin extensibility system
- ❌ **SubscriptionManager** - Belongs in billing module
- ❌ **BillingManager** - Belongs in billing module

---

## Configuration Files Needed

### Module Config File

**Path:** `modules/Mailing/config/mailing.php`

```php
<?php

return [
    // Memory and processing limits
    'memory_limit' => env('MAILING_MEMORY_LIMIT', '512M'),
    'pcre_limit' => env('MAILING_PCRE_LIMIT', 1000000),

    // Default mailer
    'default_mailer' => env('MAILING_DEFAULT_MAILER', 'smtp'),

    // Mail logging
    'log_path' => storage_path('logs/mailing/mail.log'),
    'log_level' => env('MAILING_LOG_LEVEL', 'info'),

    // Campaign settings
    'batch_size' => env('MAILING_BATCH_SIZE', 100),
    'sending_limit' => env('MAILING_SENDING_LIMIT', 1000),

    // Storage
    'storage' => [
        'driver' => env('MAILING_STORAGE_DRIVER', 'local'),
        'local' => [
            'path' => storage_path('app/mailing'),
        ],
        's3' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],
    ],

    // Hook system
    'hooks' => [
        'enabled' => true,
        'auto_discover' => true,
    ],
];
```

---

## Testing Strategy

### Provider Testing

```php
// tests/Unit/Providers/MailingServiceProviderTest.php
namespace Tests\Unit\Providers;

use Tests\TestCase;
use Modules\Mailing\app\Library\HookManager;
use Modules\Mailing\app\Providers\MailingServiceProvider;

class MailingServiceProviderTest extends TestCase
{
    public function test_hook_manager_is_singleton()
    {
        $instance1 = app(HookManager::class);
        $instance2 = app(HookManager::class);

        $this->assertSame($instance1, $instance2);
    }

    public function test_mailer_service_is_bound()
    {
        $mailer = app('mailing.mailer');

        $this->assertInstanceOf(SendingServer::class, $mailer);
    }

    public function test_translation_files_are_loaded()
    {
        $this->assertTrue(
            Lang::has('mailing::messages.campaign_sent')
        );
    }
}
```

### Policy Testing

```php
// tests/Unit/Policies/CampaignPolicyTest.php
public function test_user_can_view_own_campaigns()
{
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $this->assertTrue($user->can('view', $campaign));
}

public function test_user_cannot_view_others_campaigns()
{
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    $this->assertFalse($user->can('view', $campaign));
}
```

### Event Testing

```php
// tests/Feature/Events/CampaignEventsTest.php
public function test_campaign_updated_event_is_fired()
{
    Event::fake();

    $campaign = Campaign::factory()->create();
    $campaign->update(['status' => 'paused']);

    Event::assertDispatched(CampaignUpdated::class, function ($e) use ($campaign) {
        return $e->campaign->id === $campaign->id;
    });
}
```

---

## Performance Considerations

### Deferred Providers

Both `MailerServiceProvider` and `StorageServiceProvider` implement `DeferrableProvider` for lazy loading.

**Benefits:**
- Services only instantiated when needed
- Faster application boot time
- Reduced memory usage

**Implementation:**

```php
use Illuminate\Contracts\Support\DeferrableProvider;

class MailerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function provides(): array
    {
        return ['mailing.mailer', SendingServer::class];
    }
}
```

### Hook System Performance

The hook system can slow down if too many callbacks are registered.

**Optimization:**

```php
// Cache hook results
public function execute($name, $params = [])
{
    $cacheKey = "hooks.{$name}." . md5(serialize($params));

    return Cache::remember($cacheKey, 3600, function () use ($name, $params) {
        $results = [];
        foreach ($this->hooks[$name] ?? [] as $callback) {
            $results[] = call_user_func_array($callback, $params);
        }
        return $results;
    });
}
```

---

## Security Considerations

### Policy Authorization

All models must have policies registered to prevent unauthorized access.

```php
// Ensure all routes check policies
Route::middleware(['auth', 'can:view,campaign'])->group(function () {
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show']);
});
```

### Job Monitoring

Job monitors should not expose sensitive data in logs.

```php
// Sanitize exception messages
$monitor->setFailed(
    $this->sanitizeException($event->exception)
);
```

### Settings Storage

Mail server credentials stored in `mailing_settings` table should be encrypted.

```php
// Use Laravel encryption
protected $casts = [
    'smtp_password' => 'encrypted',
    'api_key' => 'encrypted',
];
```

---

## Conclusion

### Essential Providers for Mailing Module

1. **MailingServiceProvider** (combines AppServiceProvider + custom logic)
2. **AuthServiceProvider** (mailing policies only)
3. **EventServiceProvider** (campaign/subscriber events)
4. **JobServiceProvider** (queue monitoring)
5. **MailerServiceProvider** (mail configuration)

### Total Providers: 5

All providers should be registered in the module's main service provider and follow Laravel 12 best practices.

### Next Steps

1. Create provider structure in `modules/Mailing/app/Providers/`
2. Migrate HookManager to module library
3. Port relevant policies from Acelle
4. Set up event-listener mappings
5. Configure job monitoring for campaigns
6. Test provider registration and service resolution
