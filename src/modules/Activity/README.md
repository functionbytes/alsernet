# Activity Module

User action auditing, activity logging, and history tracking.

## Overview

The Activity module uses Spatie's Laravel Activity Log package to track and audit user actions throughout the application.

## Features

- **User Action Tracking** - Automatically log when users create, update, or delete records
- **Activity Logging** - Comprehensive audit trail of all system activities
- **Custom Properties** - Log additional custom data with each activity
- **User Attribution** - Track which user performed each action
- **Search & Filter** - Query activity logs with custom filters

## Installation

The module is automatically included via Composer's merge-plugin system.

Dependencies:
- `spatie/laravel-activitylog` (^4.9|^5.0)

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=activitylog-config
```

The configuration file is located at `config/activity.php`.

## Usage

### Basic Logging

Add the `LogsActivity` trait to your Eloquent model:

```php
use Modules\Activity\Traits\LogsActivity;

class Post extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['title', 'content'];
    protected static $logName = 'posts';
}
```

### Querying Activity

```php
use Spatie\Activitylog\Models\Activity;

// Get all activities
$activities = Activity::all();

// Filter by subject type
$activities = Activity::forSubject(Post::class)->get();

// Filter by user
$activities = Activity::causedBy($user)->get();
```

### Custom Activity Logging

```php
activity()
    ->on($subject)
    ->by($user)
    ->log('Custom action performed');
```

## Database

The module creates two tables:
- `activity_log` - Main activity log records
- `activity_log_properties` - Properties and custom data for activities

Migration file: `database/migrations/core/2026_01_02_054123_add_event_column_to_activity_log_table.php`

## References

- [Spatie Laravel Activity Log Documentation](https://spatie.be/docs/laravel-activitylog)
- [GitHub Repository](https://github.com/spatie/laravel-activitylog)

## Authors

Alsernet Development Team
