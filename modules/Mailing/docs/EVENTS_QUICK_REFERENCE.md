# Mailing Module - Events & Listeners Quick Reference

## Quick Stats

- **Events:** 16 total
- **Listeners:** 16 total
- **Queued Listeners:** 14
- **Synchronous Listeners:** 2

---

## Event Cheat Sheet

### Campaign Events
```php
use Modules\Mailing\Events\CampaignCreated;
use Modules\Mailing\Events\CampaignUpdated;
use Modules\Mailing\Events\CampaignSent;
use Modules\Mailing\Events\CampaignPaused;

// Dispatch examples
event(new CampaignCreated($campaign));
event(new CampaignUpdated($campaign, $changes));
event(new CampaignSent($campaign));
event(new CampaignPaused($campaign));
```

### Subscriber Events
```php
use Modules\Mailing\Events\SubscriberCreated;
use Modules\Mailing\Events\SubscriberUpdated;
use Modules\Mailing\Events\SubscriberSubscribed;
use Modules\Mailing\Events\SubscriberUnsubscribed;

// Dispatch examples
event(new SubscriberCreated($subscriber));
event(new SubscriberUpdated($subscriber, $changes));
event(new SubscriberSubscribed($subscriber, 'web_form'));
event(new SubscriberUnsubscribed($subscriber, 'User request'));
```

### Email Tracking Events
```php
use Modules\Mailing\Events\EmailOpened;
use Modules\Mailing\Events\EmailClicked;
use Modules\Mailing\Events\EmailBounced;
use Modules\Mailing\Events\EmailComplained;

// Dispatch examples
event(new EmailOpened($campaign, $subscriber, $ip, $userAgent));
event(new EmailClicked($campaign, $subscriber, $url, $ip, $userAgent));
event(new EmailBounced($campaign, $subscriber, 'hard', 'Mailbox full'));
event(new EmailComplained($campaign, $subscriber, 'spam'));
```

### System Events
```php
use Modules\Mailing\Events\EmailValidated;
use Modules\Mailing\Events\ImportCompleted;
use Modules\Mailing\Events\AutomationTriggered;
use Modules\Mailing\Events\ListCreated;

// Dispatch examples
event(new EmailValidated($email, true, ['score' => 95]));
event(new ImportCompleted($importId, 1000, 950, 50));
event(new AutomationTriggered($automation, $subscriber, ['trigger' => 'signup']));
event(new ListCreated($list));
```

---

## Common Usage Patterns

### In Controllers
```php
public function store(Request $request)
{
    $campaign = Campaign::create($request->validated());
    event(new CampaignCreated($campaign));
    return response()->json($campaign, 201);
}
```

### In Models (Observer Pattern)
```php
protected static function booted()
{
    static::created(fn($model) => event(new SubscriberCreated($model)));
    static::updated(fn($model) => event(new SubscriberUpdated($model, $model->getChanges())));
}
```

### In Jobs
```php
public function handle()
{
    // Process campaign sending
    event(new CampaignSent($this->campaign));
}
```

### In API Routes
```php
Route::post('/track/open/{token}', function ($token) {
    $tracking = TrackingLog::findByToken($token);
    event(new EmailOpened(
        $tracking->campaign,
        $tracking->subscriber,
        request()->ip(),
        request()->userAgent()
    ));
});
```

---

## Listener Overview

| Event | Listener | Queued | Action |
|-------|----------|--------|--------|
| `CampaignCreated` | `LogCampaignCreation` | ✅ | Log creation, init analytics |
| `CampaignUpdated` | `UpdateCampaignCache` | ❌ | Clear cache |
| `CampaignSent` | `SendCampaignAnalytics` | ✅ | Send analytics |
| `CampaignPaused` | `NotifyCampaignPause` | ✅ | Notify, stop jobs |
| `SubscriberCreated` | `SyncNewSubscriber` | ✅ | CRM sync, welcome email |
| `SubscriberUpdated` | `UpdateSubscriberCache` | ❌ | Clear cache |
| `SubscriberSubscribed` | `HandleSubscribe` | ✅ | Update status, confirm |
| `SubscriberUnsubscribed` | `HandleUnsubscribe` | ✅ | Update status, suppress |
| `EmailOpened` | `TrackEmailOpen` | ✅ | Log open, update stats |
| `EmailClicked` | `TrackEmailClick` | ✅ | Log click, update stats |
| `EmailBounced` | `HandleEmailBounce` | ✅ | Log bounce, update status |
| `EmailComplained` | `HandleEmailComplaint` | ✅ | Log complaint, suppress |
| `EmailValidated` | `UpdateSubscriberValidationStatus` | ✅ | Update validation |
| `ImportCompleted` | `NotifyImportCompletion` | ✅ | Send notification, report |
| `AutomationTriggered` | `ProcessAutomation` | ✅ | Execute workflow |
| `ListCreated` | `InitializeListDefaults` | ✅ | Create defaults |

---

## Testing

### Event Faking
```php
use Illuminate\Support\Facades\Event;

Event::fake([CampaignCreated::class]);

$campaign = Campaign::create([...]);

Event::assertDispatched(CampaignCreated::class, function ($event) use ($campaign) {
    return $event->campaign->id === $campaign->id;
});
```

### Queue Faking
```php
use Illuminate\Support\Facades\Queue;

Queue::fake();

event(new CampaignSent($campaign));

Queue::assertPushed(SendCampaignAnalytics::class);
```

---

## Configuration

### EventServiceProvider Location
```
modules/Mailing/app/Providers/EventServiceProvider.php
```

### Register in Module Service Provider
```php
// In MailingServiceProvider.php
public function boot()
{
    $this->app->register(EventServiceProvider::class);
}
```

---

## Debugging

### Enable Event Logging
```php
// In EventServiceProvider
use Illuminate\Support\Facades\Log;

public function boot(): void
{
    parent::boot();

    Event::listen('*', function ($eventName, array $data) {
        Log::debug('Event fired: ' . $eventName, $data);
    });
}
```

### Check Queue Jobs
```bash
php artisan queue:work --queue=default
php artisan horizon:list
```

### View Failed Jobs
```bash
php artisan queue:failed
php artisan queue:retry all
```

---

## File Structure

```
modules/Mailing/
├── app/
│   ├── Events/
│   │   ├── AutomationTriggered.php
│   │   ├── CampaignCreated.php
│   │   ├── CampaignPaused.php
│   │   ├── CampaignSent.php
│   │   ├── CampaignUpdated.php
│   │   ├── EmailBounced.php
│   │   ├── EmailClicked.php
│   │   ├── EmailComplained.php
│   │   ├── EmailOpened.php
│   │   ├── EmailValidated.php
│   │   ├── ImportCompleted.php
│   │   ├── ListCreated.php
│   │   ├── SubscriberCreated.php
│   │   ├── SubscriberSubscribed.php
│   │   ├── SubscriberUnsubscribed.php
│   │   └── SubscriberUpdated.php
│   ├── Listeners/
│   │   ├── HandleEmailBounce.php
│   │   ├── HandleEmailComplaint.php
│   │   ├── HandleSubscribe.php
│   │   ├── HandleUnsubscribe.php
│   │   ├── InitializeListDefaults.php
│   │   ├── LogCampaignCreation.php
│   │   ├── NotifyCampaignPause.php
│   │   ├── NotifyImportCompletion.php
│   │   ├── ProcessAutomation.php
│   │   ├── SendCampaignAnalytics.php
│   │   ├── SyncNewSubscriber.php
│   │   ├── TrackEmailClick.php
│   │   ├── TrackEmailOpen.php
│   │   ├── UpdateCampaignCache.php
│   │   ├── UpdateSubscriberCache.php
│   │   └── UpdateSubscriberValidationStatus.php
│   └── Providers/
│       └── EventServiceProvider.php
└── docs/
    ├── EVENTS_LISTENERS_MIGRATION_REPORT.md
    └── EVENTS_QUICK_REFERENCE.md
```

---

## Next Steps

1. Implement TODOs in listeners
2. Add database migrations for tracking tables
3. Configure queue workers
4. Write comprehensive tests
5. Add event dispatching to controllers and models

---

**Last Updated:** 2026-01-29
