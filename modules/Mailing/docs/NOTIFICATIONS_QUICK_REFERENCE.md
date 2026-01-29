# Notifications Quick Reference

Fast lookup guide for Mailing module notifications.

## Quick Send Examples

```php
// Campaign completed
$user->notify(new CampaignStatusNotification($campaign, 'completed'));

// New subscriber
$user->notify(new SubscriberNotification($subscriber, 'subscribed'));

// Automation triggered
$user->notify(new AutomationNotification($automation, 'triggered'));

// Quota warning
$user->notify(new QuotaNotification('email sending', 7500, 10000));

// Bounce rate alert
$user->notify(new BounceRateWarningNotification($campaign, 8.5, 170, 2000));
```

## All Notifications at a Glance

| Notification | Purpose | Channels | Queue |
|--------------|---------|----------|-------|
| CampaignStatusNotification | Campaign status updates | Mail, DB | Yes |
| SubscriberNotification | Subscriber events | Mail, DB | Yes |
| AutomationNotification | Automation workflow events | Mail, DB | Yes |
| QuotaNotification | Quota warnings | Mail, DB | Yes |
| BounceRateWarningNotification | High bounce alerts | Mail, DB | Yes |

## CampaignStatusNotification

```php
new CampaignStatusNotification(
    Campaign $campaign,
    string $status,      // completed, paused, resumed, scheduled, error
    ?string $message     // Optional custom message
)
```

## SubscriberNotification

```php
new SubscriberNotification(
    Subscriber $subscriber,
    string $action,      // subscribed, unsubscribed, bounced, complained
    array $data = []     // Additional context data
)
```

## AutomationNotification

```php
new AutomationNotification(
    Automation $automation,
    string $event,       // triggered, completed, paused, resumed, error
    array $context = []  // Stats, errors, etc.
)
```

## QuotaNotification

```php
new QuotaNotification(
    string $quotaType,   // 'email sending', 'subscriber list', etc.
    int $used,           // Current usage
    int $limit           // Maximum allowed
)
```

## BounceRateWarningNotification

```php
new BounceRateWarningNotification(
    ?Campaign $campaign, // Null for account-wide
    float $bounceRate,   // Percentage (e.g., 8.5)
    int $bounceCount,    // Number of bounces
    int $totalSent,      // Total emails sent
    string $context = 'campaign' // 'campaign' or 'account'
)
```

## Common Operations

### Send Notification
```php
$user->notify(new NotificationClass(...));
```

### Send to Multiple Users
```php
Notification::send($users, new NotificationClass(...));
```

### Delay Notification
```php
$user->notify((new NotificationClass(...))->delay(now()->addMinutes(5)));
```

### Get User Notifications
```php
$all = $user->notifications;
$unread = $user->unreadNotifications;
```

### Mark as Read
```php
$notification->markAsRead();
$user->unreadNotifications->markAsRead();
```

### Filter by Type
```php
$user->notifications()
    ->where('type', CampaignStatusNotification::class)
    ->get();
```

## Testing Commands

```bash
# Run notification tests
php artisan test --filter=Notification

# Send test via Tinker
php artisan tinker

# Check queue
php artisan queue:work

# View failed jobs
php artisan queue:failed

# Retry failed
php artisan queue:retry all
```

## Tinker Examples

```php
// Get entities
$user = User::first();
$campaign = \Modules\Mailing\Models\Campaign::first();
$subscriber = \Modules\Mailing\Models\Subscriber::first();

// Send notification
$user->notify(new \Modules\Mailing\Notifications\CampaignStatusNotification($campaign, 'completed'));

// Check notifications
$user->notifications;
$user->notifications()->latest()->first()->data;

// Mark as read
$user->unreadNotifications->markAsRead();
```

## Route Names

Used in notification action buttons:

```php
route('mailing.campaigns.show', $campaign->id)
route('mailing.subscribers.show', $subscriber->id)
route('mailing.automations.show', $automation->id)
route('mailing.settings.quota')
```

## Database Structure

Notifications table columns:
- `id` - UUID primary key
- `type` - Notification class name
- `notifiable_type` - User model class
- `notifiable_id` - User ID
- `data` - JSON payload
- `read_at` - Timestamp when read
- `created_at` - Timestamp when created

## Severity Levels

### QuotaNotification
- **Info:** < 75%
- **Warning:** 75-89%
- **Critical:** 90%+

### BounceRateWarningNotification
- **Medium:** < 5%
- **High:** 5-9.9%
- **Critical:** 10%+

## File Locations

```
modules/Mailing/
├── app/Notifications/
│   ├── CampaignStatusNotification.php
│   ├── SubscriberNotification.php
│   ├── AutomationNotification.php
│   ├── QuotaNotification.php
│   └── BounceRateWarningNotification.php
├── tests/Unit/Notifications/
│   ├── CampaignStatusNotificationTest.php
│   └── SubscriberNotificationTest.php
├── resources/views/emails/
│   └── (email templates)
└── docs/
    ├── NOTIFICATIONS_MIGRATION_GUIDE.md
    ├── NOTIFICATIONS_MIGRATION_REPORT.md
    ├── notification-usage-examples.md
    └── NOTIFICATIONS_QUICK_REFERENCE.md
```

## Import Statements

```php
use Modules\Mailing\Notifications\CampaignStatusNotification;
use Modules\Mailing\Notifications\SubscriberNotification;
use Modules\Mailing\Notifications\AutomationNotification;
use Modules\Mailing\Notifications\QuotaNotification;
use Modules\Mailing\Notifications\BounceRateWarningNotification;
use Illuminate\Support\Facades\Notification;
```

## Configuration Files

```php
// config/mail.php
'from' => [
    'address' => env('MAIL_FROM_ADDRESS'),
    'name' => env('MAIL_FROM_NAME'),
],

// config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),

// .env
MAIL_FROM_ADDRESS=notifications@example.com
MAIL_FROM_NAME="Mailing System"
QUEUE_CONNECTION=redis
```

## Common Patterns

### Event Listener Pattern
```php
class CampaignCompletedListener
{
    public function handle(CampaignCompleted $event): void
    {
        $event->campaign->user->notify(
            new CampaignStatusNotification($event->campaign, 'completed')
        );
    }
}
```

### Job Pattern
```php
class SendCampaignNotifications implements ShouldQueue
{
    public function handle(): void
    {
        $users->each(function ($user) {
            $user->notify(new CampaignStatusNotification(...));
        });
    }
}
```

### Conditional Pattern
```php
if ($user->preferences->campaign_notifications) {
    $user->notify(new CampaignStatusNotification(...));
}
```

---

**Last Updated:** 2026-01-29
**Version:** 1.0
**Module:** Mailing
