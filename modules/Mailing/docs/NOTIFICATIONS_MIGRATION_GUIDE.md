# Notifications Migration Guide

## Overview
This guide details the migration of notification classes from Acelle to the Mailing module.

## Source Location
`/Users/functionbytes/Function/Coding/acelle/app/Notifications/`

## Destination Location
`modules/Mailing/app/Notifications/`

## Migration Steps

### 1. Namespace Updates
All notification classes must update their namespace:

**Before:**
```php
namespace App\Notifications;
```

**After:**
```php
namespace Modules\Mailing\Notifications;
```

### 2. Model Import Updates
Update any model imports to use the Mailing module namespace:

**Before:**
```php
use App\Models\MailList;
use App\Models\Campaign;
use App\Models\Automation;
```

**After:**
```php
use Modules\Mailing\Models\MailList;
use Modules\Mailing\Models\Campaign;
use Modules\Mailing\Models\Automation;
```

### 3. View Path Updates
Update email view references to use module notation:

**Before:**
```php
return (new MailMessage)
    ->view('emails.notification');
```

**After:**
```php
return (new MailMessage)
    ->view('mailing::emails.notification');
```

### 4. Notification Channel Verification
Verify and document the channels used by each notification:
- `mail` - Email notifications
- `database` - Database notifications (stored in notifications table)
- `slack` - Slack notifications
- `broadcast` - Real-time broadcasting

### 5. Route Updates
Update any route references within notification actions:

**Before:**
```php
->action('View Campaign', url('/campaigns/'.$this->campaign->id))
```

**After:**
```php
->action('View Campaign', route('mailing.campaigns.show', $this->campaign->id))
```

## Common Notification Patterns

### Email Notification Example
```php
<?php

namespace Modules\Mailing\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Mailing\Models\Campaign;

class CampaignStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $campaign;
    protected $status;

    public function __construct(Campaign $campaign, string $status)
    {
        $this->campaign = $campaign;
        $this->status = $status;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Campaign Status Update')
            ->view('mailing::emails.campaign-status', [
                'campaign' => $this->campaign,
                'status' => $this->status,
                'user' => $notifiable,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->name,
            'status' => $this->status,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
```

### Database Notification Example
```php
<?php

namespace Modules\Mailing\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Mailing\Models\Subscriber;

class SubscriberActivityNotification extends Notification
{
    use Queueable;

    protected $subscriber;
    protected $activity;

    public function __construct(Subscriber $subscriber, string $activity)
    {
        $this->subscriber = $subscriber;
        $this->activity = $activity;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'subscriber_id' => $this->subscriber->id,
            'subscriber_email' => $this->subscriber->email,
            'activity' => $this->activity,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
```

## Testing Notifications

### Unit Test Example
```php
<?php

namespace Modules\Mailing\Tests\Unit\Notifications;

use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Modules\Mailing\Notifications\CampaignStatusNotification;
use Modules\Mailing\Models\Campaign;
use App\Models\User;

class CampaignStatusNotificationTest extends TestCase
{
    public function test_notification_is_sent_when_campaign_status_changes()
    {
        Notification::fake();

        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $user->notify(new CampaignStatusNotification($campaign, 'completed'));

        Notification::assertSentTo(
            $user,
            CampaignStatusNotification::class,
            function ($notification, $channels) use ($campaign) {
                return $notification->campaign->id === $campaign->id
                    && in_array('mail', $channels)
                    && in_array('database', $channels);
            }
        );
    }

    public function test_notification_mail_content()
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['name' => 'Test Campaign']);

        $notification = new CampaignStatusNotification($campaign, 'completed');
        $mailMessage = $notification->toMail($user);

        $this->assertEquals('Campaign Status Update', $mailMessage->subject);
        $this->assertEquals('mailing::emails.campaign-status', $mailMessage->view);
    }
}
```

## Checklist for Each Notification

- [ ] Update namespace to `Modules\Mailing\Notifications`
- [ ] Update all model imports to use Mailing module namespace
- [ ] Update view paths to use `mailing::` prefix
- [ ] Update route references to use named routes
- [ ] Verify notification channels are appropriate
- [ ] Test notification delivery
- [ ] Document notification purpose and usage
- [ ] Create corresponding email views if needed
- [ ] Add to migration report

## Email View Migration

If notifications reference email views, ensure those views are also migrated:

**Source:** `/Users/functionbytes/Function/Coding/acelle/resources/views/emails/`
**Destination:** `modules/Mailing/resources/views/emails/`

## Expected Notification Types

Based on typical Acelle functionality, expect these notification types:

1. **Campaign Notifications**
   - CampaignCompleted
   - CampaignPaused
   - CampaignError
   - CampaignScheduled

2. **List Management Notifications**
   - SubscriberImported
   - SubscriberBounced
   - SubscriberUnsubscribed
   - ListSizeLimit

3. **Automation Notifications**
   - AutomationTriggered
   - AutomationCompleted
   - AutomationError

4. **System Notifications**
   - QuotaExceeded
   - DeliveryIssue
   - BounceRateWarning
   - SpamComplaint

5. **Admin Notifications**
   - UserRegistered
   - PlanUpgraded
   - PaymentReceived
   - SystemAlert

## Post-Migration Tasks

1. Update service providers to auto-discover notifications
2. Update notification database migrations if custom columns exist
3. Configure notification channels in `config/services.php`
4. Update queue workers configuration for queued notifications
5. Test all notification deliveries in staging environment
6. Update documentation with new notification paths
