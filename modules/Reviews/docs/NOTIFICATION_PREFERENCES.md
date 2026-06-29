# Notification Preferences System

## Overview

The Notification Preferences system allows users to control which notifications they receive and through which channels (Email, In-App, Slack).

## Database Schema

### Table: `user_notification_preferences`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users table |
| notification_type | string | Type of notification (see types below) |
| channels | json | Array of enabled channels ['mail', 'database', 'slack'] |
| is_enabled | boolean | Whether this notification type is enabled |
| filters | json | Optional filters (e.g., min_rating, max_rating, location_ids) |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

**Indexes:**
- Unique index on `(user_id, notification_type)`
- Index on `is_enabled`

## Notification Types

| Type | Label | Description | Supports Filters | Default Enabled |
|------|-------|-------------|------------------|-----------------|
| `new_review` | Nueva reseña | Any new review received | Yes | Yes |
| `negative_review` | Reseña negativa | Reviews with 1-3 stars | Yes | Yes |
| `positive_review` | Reseña positiva | Reviews with 4-5 stars | Yes | Yes |
| `export_ready` | Exportación lista | Export file ready for download | No | Yes |
| `reply_published` | Respuesta publicada | Reply published to Google | No | Yes |
| `connection_expiring` | Conexión expirando | Google connection expiring soon | No | Yes |
| `daily_digest` | Resumen diario | Daily summary of review activity | No | No |

## Channels

| Channel | Description |
|---------|-------------|
| `mail` | Email notifications |
| `database` | In-app notifications (stored in notifications table) |
| `slack` | Slack channel notifications (future) |

## Service Layer

### NotificationService

Main service for managing notification preferences and sending notifications.

#### Key Methods:

**`checkPreferences(User $user, string $notificationType, array $context = []): bool`**
- Checks if user should receive a notification based on preferences
- Returns `true` if notification should be sent

**`getEnabledChannels(User $user, string $notificationType): array`**
- Returns array of enabled channels for a notification type
- Falls back to defaults if no preference exists

**`sendIfEnabled(User|Collection $users, Notification $notification, string $notificationType, array $context = []): void`**
- Sends notification only to users who have it enabled
- Respects channel preferences
- Applies contextual filters

**`initializeDefaultPreferences(User $user): void`**
- Creates default preferences for all notification types
- Called automatically when user first accesses preferences page
- Can be called manually during user creation

#### Static Methods:

**`getAvailableTypes(): array`**
- Returns all available notification types with metadata

**`getAvailableChannels(): array`**
- Returns all available notification channels

## Notification Classes

All notification classes implement preference checking in their `via()` method:

```php
public function via(object $notifiable): array
{
    $service = app(NotificationService::class);
    $channels = $service->getEnabledChannels($notifiable, NotificationService::TYPE_*);

    return array_filter($channels, fn ($channel) => in_array($channel, ['database', 'mail']));
}
```

### Available Notifications:

- `NewReviewNotification` - Any new review
- `NewNegativeReviewNotification` - Negative reviews (1-3 stars)
- `PositiveReviewNotification` - Positive reviews (4-5 stars)
- `ExportReadyNotification` - Export file ready
- `ReplyPublishedNotification` - Reply published successfully
- `ConnectionExpiringNotification` - Connection expiring warning
- `DailyDigestNotification` - Daily summary email

## User Interface

### Preferences Page

Route: `/settings/reviews/notifications`

**Features:**
- Toggle notifications on/off per type
- Select channels (Email, In-App, Slack) per type
- Configure filters for supported notification types
- Test button to send sample notification
- Real-time validation (at least one channel required if enabled)

**Filters:**
- **Min Rating**: Minimum star rating to trigger notification
- **Max Rating**: Maximum star rating to trigger notification
- **Location IDs**: Specific locations to monitor (future)

### Menu Integration

Added to Settings sidebar under "Reseñas" section:
- Label: "Preferencias de notificación"
- Permission: `reviews.settings.manage`

## API Endpoints

### GET `/settings/reviews/notifications`
Display notification preferences page

### POST `/settings/reviews/notifications/update`
Update user notification preferences

**Request Body:**
```json
{
  "preferences": [
    {
      "notification_type": "negative_review",
      "is_enabled": true,
      "channels": ["mail", "database"],
      "filters": {
        "min_rating": 1,
        "max_rating": 3
      }
    }
  ]
}
```

### POST `/settings/reviews/notifications/test/{type}`
Send test notification for specified type

**Response:**
```json
{
  "message": "Test notification sent successfully"
}
```

## Commands

### `php artisan reviews:send-daily-digest`

Sends daily digest notifications to subscribed users.

**Schedule:** Daily at 8:00 AM

**Features:**
- Only sends to users with daily_digest enabled
- Calculates stats for previous day:
  - New reviews count
  - Average rating
  - Pending replies count
  - Negative reviews count
  - Top location by review count
- Skips users with zero new reviews

## Usage Examples

### Initialize Preferences for New User

```php
use Modules\Reviews\Services\NotificationService;

$service = app(NotificationService::class);
$service->initializeDefaultPreferences($user);
```

### Send Notification Respecting Preferences

```php
use Modules\Reviews\Services\NotificationService;
use Modules\Reviews\Notifications\NewNegativeReviewNotification;

$service = app(NotificationService::class);
$notification = new NewNegativeReviewNotification($review);

// Single user
$service->sendIfEnabled(
    $user,
    $notification,
    NotificationService::TYPE_NEGATIVE_REVIEW,
    ['rating' => $review->star_rating->toNumeric()]
);

// Multiple users
$users = User::whereHas('role', fn($q) => $q->where('name', 'manager'))->get();
$service->sendIfEnabled($users, $notification, NotificationService::TYPE_NEGATIVE_REVIEW);
```

### Check if User Wants Notification

```php
$service = app(NotificationService::class);

$shouldNotify = $service->checkPreferences(
    $user,
    NotificationService::TYPE_NEGATIVE_REVIEW,
    [
        'rating' => 2,
        'location_id' => 123
    ]
);

if ($shouldNotify) {
    $user->notify(new NewNegativeReviewNotification($review));
}
```

### Get Enabled Channels

```php
$channels = $service->getEnabledChannels($user, NotificationService::TYPE_EXPORT_READY);
// Returns: ['database'] or ['mail', 'database'] etc.
```

## Seeders

### NotificationPreferencesSeeder

Initializes default preferences for all users who don't have them.

```bash
php artisan db:seed --class=Modules\\Reviews\\Database\\Seeders\\NotificationPreferencesSeeder
```

## Model

### UserNotificationPreference

**Relationships:**
- `belongsTo(User::class)`

**Scopes:**
- `enabled()` - Only enabled preferences
- `byType(string $type)` - Filter by notification type

**Methods:**
- `shouldNotify(array $context = []): bool` - Check if notification should be sent based on filters
- `getChannels(): array` - Get enabled channels
- `hasChannel(string $channel): bool` - Check if specific channel is enabled

## Trait

### HasNotificationPreferences

Add to User model to enable relationship:

```php
use Modules\Reviews\Traits\HasNotificationPreferences;

class User extends Authenticatable
{
    use HasNotificationPreferences;

    // ...
}
```

**Provides:**
- `notificationPreferences()` relationship

## Testing

### Test Notification from UI

1. Navigate to `/settings/reviews/notifications`
2. Click test button (paper plane icon) next to any notification type
3. Check email and in-app notifications

### Test Notification from Command Line

```bash
php artisan tinker

$user = User::first();
$review = \Modules\Reviews\Models\Review::first();
$notification = new \Modules\Reviews\Notifications\NewNegativeReviewNotification($review);
$user->notify($notification);
```

### Test Daily Digest

```bash
php artisan reviews:send-daily-digest
```

## Default Channel Configuration

| Notification Type | Default Channels |
|-------------------|------------------|
| new_review | database |
| negative_review | mail, database |
| positive_review | database |
| export_ready | database |
| reply_published | database |
| connection_expiring | mail, database |
| daily_digest | mail |

## Permissions

All notification preference endpoints require:
- **Permission:** `reviews.settings.manage`
- **Middleware:** `auth`, `can:reviews.settings.manage`

## Frontend JavaScript

**Key Features:**
- Toggle switches disable channel checkboxes when notification is disabled
- Filter rows show/hide based on enable toggle
- AJAX test notification sending with loading indicators
- Form validation: at least one channel required for enabled notifications
- Toastr notifications for success/error feedback

## Activity Logging

All preference changes and test notifications are logged via Spatie Activity Log:

```php
activity()
    ->causedBy($user)
    ->log('Notification preferences updated');

activity()
    ->causedBy($user)
    ->withProperties(['type' => $type])
    ->log('Test notification sent');
```

## Future Enhancements

- [ ] Slack integration
- [ ] SMS notifications
- [ ] Location-specific filters UI
- [ ] Time-based preferences (quiet hours)
- [ ] Notification frequency limits (digest mode)
- [ ] Template customization per notification type
- [ ] Language preferences per notification
