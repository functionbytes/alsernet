# Notification Usage Examples

This guide demonstrates how to use the Mailing module notifications in your application.

## Table of Contents

1. [Campaign Status Notifications](#campaign-status-notifications)
2. [Subscriber Notifications](#subscriber-notifications)
3. [Automation Notifications](#automation-notifications)
4. [Quota Notifications](#quota-notifications)
5. [Bounce Rate Warnings](#bounce-rate-warnings)
6. [Testing Notifications](#testing-notifications)

## Campaign Status Notifications

### Send Campaign Completion Notification

```php
use Modules\Mailing\Notifications\CampaignStatusNotification;

// In your campaign service or controller
$user = auth()->user();
$campaign = Campaign::find($campaignId);

$user->notify(new CampaignStatusNotification(
    $campaign,
    'completed',
    'Your campaign was sent to 1,500 subscribers successfully.'
));
```

### Send Campaign Error Notification

```php
use Modules\Mailing\Notifications\CampaignStatusNotification;

$user = $campaign->user;

$user->notify(new CampaignStatusNotification(
    $campaign,
    'error',
    'Campaign failed due to invalid sender email configuration.'
));
```

### Different Status Options

```php
// Campaign paused
$user->notify(new CampaignStatusNotification($campaign, 'paused'));

// Campaign resumed
$user->notify(new CampaignStatusNotification($campaign, 'resumed'));

// Campaign scheduled
$user->notify(new CampaignStatusNotification($campaign, 'scheduled', 'Will be sent tomorrow at 10:00 AM'));
```

## Subscriber Notifications

### New Subscriber Added

```php
use Modules\Mailing\Notifications\SubscriberNotification;

$user = $mailList->user;
$subscriber = Subscriber::find($subscriberId);

$user->notify(new SubscriberNotification(
    $subscriber,
    'subscribed',
    ['source' => 'web_form', 'list_name' => $mailList->name]
));
```

### Email Bounced

```php
use Modules\Mailing\Notifications\SubscriberNotification;

$user->notify(new SubscriberNotification(
    $subscriber,
    'bounced',
    [
        'reason' => 'Mailbox full',
        'bounce_type' => 'soft',
        'campaign_id' => $campaign->id
    ]
));
```

### Spam Complaint

```php
use Modules\Mailing\Notifications\SubscriberNotification;

$user->notify(new SubscriberNotification(
    $subscriber,
    'complained',
    [
        'campaign_id' => $campaign->id,
        'feedback_type' => 'abuse'
    ]
));
```

### Unsubscribe Event

```php
$user->notify(new SubscriberNotification(
    $subscriber,
    'unsubscribed',
    [
        'reason' => 'User requested',
        'source' => 'email_link'
    ]
));
```

## Automation Notifications

### Automation Triggered

```php
use Modules\Mailing\Notifications\AutomationNotification;

$automation = Automation::find($automationId);
$user = $automation->user;

$user->notify(new AutomationNotification(
    $automation,
    'triggered',
    [
        'trigger' => 'new_subscriber',
        'subscriber_count' => 1
    ]
));
```

### Automation Completed

```php
use Modules\Mailing\Notifications\AutomationNotification;

$user->notify(new AutomationNotification(
    $automation,
    'completed',
    [
        'stats' => [
            'Emails Sent' => 250,
            'Opens' => 180,
            'Clicks' => 45,
            'Duration' => '2 hours'
        ]
    ]
));
```

### Automation Error

```php
use Modules\Mailing\Notifications\AutomationNotification;

$user->notify(new AutomationNotification(
    $automation,
    'error',
    [
        'error' => 'Failed to load email template',
        'step' => 3,
        'total_steps' => 5
    ]
));
```

## Quota Notifications

### Email Quota Warning

```php
use Modules\Mailing\Notifications\QuotaNotification;

$user = User::find($userId);

// 75% quota used (warning level)
$user->notify(new QuotaNotification(
    'email sending',
    7500,  // used
    10000  // limit
));
```

### Critical Quota Alert

```php
// 90% quota used (critical level)
$user->notify(new QuotaNotification(
    'email sending',
    9000,  // used
    10000  // limit
));
```

### Subscriber Quota

```php
// Subscriber list quota
$user->notify(new QuotaNotification(
    'subscriber list',
    8500,  // used
    10000  // limit
));
```

## Bounce Rate Warnings

### Campaign Bounce Warning

```php
use Modules\Mailing\Notifications\BounceRateWarningNotification;

$campaign = Campaign::find($campaignId);
$user = $campaign->user;

$user->notify(new BounceRateWarningNotification(
    $campaign,
    8.5,    // bounce rate percentage
    170,    // bounce count
    2000,   // total sent
    'campaign'
));
```

### Account-Wide Bounce Warning

```php
use Modules\Mailing\Notifications\BounceRateWarningNotification;

// No specific campaign (account-wide)
$user->notify(new BounceRateWarningNotification(
    null,
    6.2,    // bounce rate percentage
    620,    // bounce count
    10000,  // total sent
    'account'
));
```

## Event Listeners Integration

### Listen for Campaign Events

```php
// app/Listeners/SendCampaignNotification.php

namespace App\Listeners;

use Modules\Mailing\Events\CampaignCompleted;
use Modules\Mailing\Notifications\CampaignStatusNotification;

class SendCampaignNotification
{
    public function handle(CampaignCompleted $event): void
    {
        $event->campaign->user->notify(
            new CampaignStatusNotification(
                $event->campaign,
                'completed',
                "Sent to {$event->stats['total_sent']} subscribers"
            )
        );
    }
}
```

### Register Event Listener

```php
// app/Providers/EventServiceProvider.php

protected $listen = [
    \Modules\Mailing\Events\CampaignCompleted::class => [
        \App\Listeners\SendCampaignNotification::class,
    ],
];
```

## Notification Preferences

### Check User Preferences

```php
// Before sending notification, check if user wants it
if ($user->notificationPreferences->campaign_status) {
    $user->notify(new CampaignStatusNotification($campaign, 'completed'));
}
```

### Send to Multiple Users

```php
use Illuminate\Support\Facades\Notification;

// Notify all admins
$admins = User::where('role', 'admin')->get();

Notification::send($admins, new QuotaNotification('email sending', 9500, 10000));
```

## Database Notifications

### Retrieve User Notifications

```php
// Get all notifications
$notifications = $user->notifications;

// Get unread notifications
$unread = $user->unreadNotifications;

// Get notifications of specific type
$campaignNotifications = $user->notifications()
    ->where('type', CampaignStatusNotification::class)
    ->get();
```

### Mark as Read

```php
// Mark specific notification as read
$notification = $user->notifications()->first();
$notification->markAsRead();

// Mark all as read
$user->unreadNotifications->markAsRead();
```

### Delete Notifications

```php
// Delete old notifications
$user->notifications()
    ->where('created_at', '<', now()->subDays(30))
    ->delete();
```

## Testing Notifications

### Using Tinker

```bash
php artisan tinker
```

```php
// Get a user
$user = User::first();

// Get a campaign
$campaign = \Modules\Mailing\Models\Campaign::first();

// Send test notification
$user->notify(new \Modules\Mailing\Notifications\CampaignStatusNotification(
    $campaign,
    'completed',
    'This is a test notification'
));

// Check database notifications
$user->notifications()->latest()->first();

// View notification data
$user->notifications()->latest()->first()->data;
```

### Using Artisan Command

Create a custom command for testing:

```php
// app/Console/Commands/TestNotification.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Modules\Mailing\Models\Campaign;
use Modules\Mailing\Notifications\CampaignStatusNotification;

class TestNotification extends Command
{
    protected $signature = 'notification:test {user_id} {--type=campaign}';
    protected $description = 'Send a test notification';

    public function handle(): void
    {
        $user = User::findOrFail($this->argument('user_id'));

        switch ($this->option('type')) {
            case 'campaign':
                $campaign = Campaign::first();
                $user->notify(new CampaignStatusNotification(
                    $campaign,
                    'completed',
                    'Test notification from command'
                ));
                break;
        }

        $this->info('Test notification sent!');
    }
}
```

Run the command:
```bash
php artisan notification:test 1 --type=campaign
```

## Advanced Usage

### Custom Notification Channels

```php
// Create custom channel
// app/Notifications/Channels/SlackChannel.php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;

class SlackChannel
{
    public function send($notifiable, Notification $notification): void
    {
        $message = $notification->toSlack($notifiable);

        // Send to Slack webhook
    }
}
```

### Notification Broadcasting

```php
// Enable broadcast channel
public function via($notifiable): array
{
    return ['mail', 'database', 'broadcast'];
}

// Implement toBroadcast method
public function toBroadcast($notifiable): BroadcastMessage
{
    return new BroadcastMessage([
        'campaign_id' => $this->campaign->id,
        'status' => $this->status,
        'message' => $this->message,
    ]);
}
```

### Delayed Notifications

```php
// Send notification after 5 minutes
$user->notify(
    (new CampaignStatusNotification($campaign, 'completed'))
        ->delay(now()->addMinutes(5))
);
```

### Conditional Notifications

```php
// Notify only if bounce rate is high
if ($bounceRate > 5.0) {
    $user->notify(new BounceRateWarningNotification(
        $campaign,
        $bounceRate,
        $bounceCount,
        $totalSent
    ));
}
```

## Best Practices

1. **Always queue notifications** - Use `ShouldQueue` interface
2. **Include context** - Provide meaningful messages and data
3. **Respect preferences** - Check user notification settings
4. **Clean up old notifications** - Schedule cleanup of old database notifications
5. **Test thoroughly** - Test both mail and database channels
6. **Handle failures** - Implement failed job handling
7. **Monitor queues** - Use Horizon to monitor notification queues

## Troubleshooting

### Notifications Not Sending

```php
// Check if queues are running
php artisan queue:work

// Check failed jobs
php artisan queue:failed

// Retry failed notifications
php artisan queue:retry all
```

### Email Not Received

```php
// Check mail configuration
config('mail.default');

// Test mail connection
Mail::raw('Test email', function ($message) {
    $message->to('test@example.com')->subject('Test');
});
```

### Database Notifications Not Storing

```php
// Check notifications table exists
Schema::hasTable('notifications');

// Create notifications table if missing
php artisan notifications:table
php artisan migrate
```

---

**Last Updated:** 2026-01-29
**Module:** Mailing
**Laravel Version:** 12.x
