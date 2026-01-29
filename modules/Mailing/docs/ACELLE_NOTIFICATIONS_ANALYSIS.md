# Acelle Notification System - Complete Analysis

## Executive Summary

Acelle implements a **custom notification system** separate from Laravel's built-in notification system. The system is designed for administrative alerts, system monitoring, and error tracking rather than user-facing notifications.

**Key Characteristics:**
- Custom database-driven notification system (not Laravel Notifications table)
- Primarily for admin alerts and system health monitoring
- Three severity levels: INFO, WARNING, ERROR
- Database-only channel (no email, SMS, or push notifications)
- Real-time popup display in admin interface
- Automatic cleanup of duplicate notifications

---

## 1. System Architecture

### 1.1 Core Components

```
Notification System Structure:
├── Model: Acelle\Model\Notification
├── Controllers:
│   ├── Admin\NotificationController (admin interface)
│   └── NotificationController (user interface - limited)
├── Library Classes:
│   ├── Acelle\Library\Notification\CronJob
│   ├── Acelle\Library\Notification\SystemUrl
│   └── Acelle\Library\Notification\BackendError
├── Views:
│   ├── admin/notifications/* (admin UI)
│   └── notifications/* (user UI)
└── Database: notifications table
```

### 1.2 Notification Model

**Location:** `/app/Model/Notification.php`

**Key Features:**
- Uses `HasUid` trait for UUID-based identification
- Static factory methods for creating notifications
- Built-in duplicate cleanup mechanism
- Search and filtering capabilities

**Attributes:**
```php
protected $fillable = [
    'type',        // Full class name of notification type
    'title',       // Notification title
    'message',     // Notification message
    'level',       // Severity: info, warning, error
    'uid',         // Unique identifier
    'debug',       // Debug information (stack traces, etc.)
];
```

**Severity Levels:**
```php
const LEVEL_INFO = 'info';      // Informational messages
const LEVEL_WARNING = 'warning'; // Warnings (most common)
const LEVEL_ERROR = 'error';     // Critical errors
```

### 1.3 Database Schema

**Migration:** `2018_07_31_173424_create_notifications_table.php`

```sql
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uid UUID NOT NULL,
    type TEXT,              -- Notification class name
    title TEXT,             -- Display title
    message TEXT,           -- Notification message
    level TEXT,             -- info|warning|error
    admin_id INT UNSIGNED,  -- Related admin (nullable)
    customer_id INT UNSIGNED, -- Related customer (nullable)
    debug MEDIUMTEXT,       -- Debug information (added 2021)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);
```

**Key Schema Features:**
- Supports both admin and customer notifications
- Stores full stack traces in `debug` column
- Cascading deletes when admin/customer is deleted
- No visibility column (unlike some implementations)

---

## 2. Notification Channels

### 2.1 Channels Overview

**Primary Channel:** Database only

**NOT Implemented:**
- ❌ Email notifications
- ❌ SMS notifications
- ❌ Push notifications
- ❌ Slack notifications
- ❌ Broadcast notifications

### 2.2 Why Database-Only?

The Acelle notification system is designed for:
1. **Admin monitoring** - System health checks visible in admin dashboard
2. **Error logging** - Centralized error tracking with debug information
3. **Real-time alerts** - Popup notifications in admin interface
4. **Audit trail** - Historical record of system issues

The system does NOT use Laravel's `Notification` facade or `Notifiable` trait for these alerts, keeping them separate from user-facing notifications.

### 2.3 Laravel Notifications (Separate System)

Acelle DOES use Laravel's notification system for:

**Password Reset Only:**
```php
// Location: /app/Notifications/ResetPassword.php
class ResetPassword extends BaseResetPassword
{
    // Uses 'mail' channel via Laravel's notification system
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->line(trans('messages.click_here_to_reset_password'))
            ->action(trans('messages.reset_password'), $this->resetPasswordUrl);
    }
}
```

This is the ONLY place where Laravel's built-in notification system is used.

---

## 3. Notification Types (Library Classes)

### 3.1 CronJob Notification

**Purpose:** Monitor if cron jobs are running on schedule

**Location:** `/app/Library/Notification/CronJob.php`

**Trigger:** Checked when admin logs in

**Logic:**
```php
public static function check()
{
    $interval = Setting::get('cronjob_min_interval');
    if (!self::isCronjobExecutedWithin($interval)) {
        $warning = [
            'title' => 'CronJob',
            'message' => trans('messages.admin.notification.cronjob_not_active', [
                'cronjob_min_interval' => $interval,
                'cronjob_last_executed' => self::getLastExecutionDateTime()
            ]),
        ];
        self::warning($warning);
    }
}
```

**When it Alerts:**
- Cron job hasn't run within configured interval
- Last execution timestamp is stale
- Cron job has never run (null timestamp)

### 3.2 SystemUrl Notification

**Purpose:** Detect URL mismatch between runtime and cached configuration

**Location:** `/app/Library/Notification/SystemUrl.php`

**Trigger:** Checked when admin logs in

**Logic:**
```php
public static function check()
{
    $current = url('/');        // Runtime URL
    $cached = config('app.url'); // Cached config URL

    if ($current != $cached) {
        $warning = [
            'title' => trans('messages.admin.notification.system_url_title'),
            'message' => trans('messages.admin.notification.system_url_not_match', [
                'cached' => $cached,
                'current' => $current
            ]),
        ];
        self::warning($warning);
    }
}
```

**When it Alerts:**
- Application URL changed (domain or protocol)
- Server IP changed
- Port changed
- Environment mismatch (production vs development)

### 3.3 BackendError Notification

**Purpose:** Catch and log CLI/console errors

**Location:** `/app/Library/Notification/BackendError.php`

**Trigger:** Automatic via exception handler

**Implementation:**
```php
// Exception Handler: /app/Exceptions/Handler.php
public function report(Throwable $exception)
{
    if (App::runningInConsole() && isInitiated()) {
        try {
            $title = 'PHP CLI ERROR';
            BackendErrorNotification::cleanupDuplicateNotifications($title);
            BackendErrorNotification::warning([
                'title' => $title,
                'message' => sprintf(
                    "[%s] [%s] %s: %s",
                    get_current_user(),
                    date('Y-m-d H:i:s eP'),
                    get_class($exception),
                    $exception->getMessage()
                ),
                'debug' => $exception->getTraceAsString(),
            ], false);
        } catch (Throwable $t) {
            // Silent failure if DB unavailable
        }
    }
    parent::report($exception);
}
```

**When it Alerts:**
- Any unhandled exception in CLI/console commands
- Queue job failures
- Scheduled task errors
- Artisan command exceptions

**Excluded Exceptions:**
```php
protected $dontReport = [
    ModelNotFoundException::class,      // User cancels job by deleting monitor
    MaxAttemptsExceededException::class // Already logged to campaign/process
];
```

### 3.4 PHP Version Check

**Purpose:** Warn about unsupported PHP versions

**Location:** `/app/Listeners/AdminLoggedInListener.php`

**Trigger:** Every admin login

**Logic:**
```php
public function checkForPhpVersion()
{
    $title = 'PHP version is no longer supported';

    if (version_compare(PHP_VERSION, config('custom.php_recommended'), '<')) {
        Notification::error([
            'title' => $title,
            'message' => sprintf(
                "Your hosting server's PHP version %s is no longer supported,
                please upgrade to version %s or higher",
                PHP_VERSION,
                config('custom.php_recommended')
            ),
        ]);
    } else {
        Notification::cleanupDuplicateNotifications($title);
    }
}
```

---

## 4. Critical Notifications

### 4.1 Critical Notification Scenarios

| Notification | Severity | Impact | Auto-Cleanup |
|-------------|----------|--------|--------------|
| **CronJob Not Running** | WARNING | HIGH - Email campaigns won't send | Yes |
| **System URL Mismatch** | WARNING | MEDIUM - Links may break | Yes |
| **PHP CLI Errors** | WARNING | HIGH - Background jobs fail | Yes (last only) |
| **PHP Version Outdated** | ERROR | MEDIUM - Security risk | Yes |
| **GeoIP Service Down** | WARNING | LOW - Location features unavailable | Yes |

### 4.2 Notification Priorities

**High Priority (Immediate Action Required):**
1. CronJob stopped - campaigns won't send
2. PHP CLI errors - background processing broken
3. Database connection failures in console

**Medium Priority (Should Fix Soon):**
1. System URL mismatch - links may be incorrect
2. PHP version outdated - security and compatibility
3. GeoIP service unavailable - tracking features affected

**Low Priority (Informational):**
1. Configuration changes detected
2. Cache clearing needed
3. Queue size growing

### 4.3 Critical Path Analysis

**CronJob Notification Critical Path:**
```
Admin Login
    ↓
AdminLoggedInListener triggered
    ↓
CronJob::check() called
    ↓
Check Setting::get('cronjob_last_execution')
    ↓
Compare with Setting::get('cronjob_min_interval')
    ↓
If stale → Create WARNING notification
    ↓
Display in admin popup
```

**BackendError Critical Path:**
```
Console Command Executes
    ↓
Exception Thrown
    ↓
Handler::report() catches exception
    ↓
Check if running in console
    ↓
Create BackendErrorNotification with stack trace
    ↓
Cleanup previous errors (keep last only)
    ↓
Store in database with debug info
```

---

## 5. Notification Templates

### 5.1 Admin Popup Template

**Location:** `/resources/views/admin/notifications/popup.blade.php`

**Features:**
- Displays top 20 notifications
- Icon-based severity indicators
- Relative timestamps (diffForHumans)
- Link to full notification log
- Empty state handling

**Code:**
```blade
<ul class="notifications-list pl-0">
    @foreach (Auth::user()->admin->notifications()->take(20)->get() as $notification)
        <li class="mb-3 px-3 py-2 bg-white shadow-sm rounded-3">
            <div class="d-flex py-2">
                <i class="me-4 d-block">
                    @if ($notification->level == \Acelle\Model\Notification::LEVEL_WARNING)
                        <span class="material-symbols-rounded bg-warning notification-icon">
                            warning_amber
                        </span>
                    @elseif ($notification->level == \Acelle\Model\Notification::LEVEL_ERROR)
                        <span class="material-symbols-rounded bg-danger notification-icon">
                            new_releases
                        </span>
                    @else
                        <span class="material-symbols-rounded bg-info notification-icon">
                            lightbulb
                        </span>
                    @endif
                </i>
                <div class="d-block position-relative">
                    <span class="fw-600">{{ $notification->title }}</span>
                    <span class="text-muted small d-block mt-1 mb-2">
                        {{ $notification->created_at->diffForHumans() }}
                    </span>
                    <p class="desc-menu-log small mb-0">
                        {{ $notification->message }}
                    </p>
                </div>
            </div>
        </li>
    @endforeach
</ul>
```

### 5.2 Admin Listing Template

**Location:** `/resources/views/admin/notifications/listing.blade.php`

**Features:**
- Tabular display with checkboxes
- Bulk delete functionality
- Full message and debug info on hover
- Formatted timestamps
- Empty state handling
- Pagination support

### 5.3 Notification Element Template

**Location:** `/resources/views/elements/_notification.blade.php`

**Purpose:** Reusable alert component for displaying notifications

**Usage:**
```blade
@include('elements._notification', [
    'level' => 'warning',
    'title' => 'Warning Title',
    'message' => 'Warning message',
    'debug' => 'Optional debug info'
])
```

### 5.4 Top Bar Notification Bell

**Location:** `/resources/views/layouts/core/_top_notifications.blade.php`

**Features:**
- Bell icon with notification count badge
- JavaScript popup toggle
- Sidebar integration
- Real-time notification indicator

**Code:**
```blade
<a class="nav-link notifications-menu-item">
    <i class="navbar-icon top-notification-icon">
        <svg class="NavIcon BellNavIcon" viewBox="0 0 40 40">
            <!-- Bell SVG path -->
        </svg>
    </i>
    @if (Acelle\Model\Notification::count())
        <i class="top-notification-icon-dot"></i>
    @endif
    <span>{{ trans('messages.notifications') }}</span>
</a>

<script>
    $('.notifications-menu-item').on('click', function() {
        var sidebar = new Sidebar();
        if (!sidebar.showed()) {
            sidebar.load({
                url: '{{ action('Admin\NotificationController@popup') }}'
            });
        } else {
            sidebar.hide();
        }
    });
</script>
```

### 5.5 Email Template (Password Reset Only)

**Location:** `/resources/views/vendor/notifications/email.blade.php`

**Purpose:** Laravel notification email template (not used for system notifications)

**Features:**
- Greeting customization
- Action button with color coding
- Intro and outro lines
- Automatic "trouble clicking" fallback
- Site name in signature

---

## 6. Notification Usage Patterns

### 6.1 Static Factory Methods

```php
// INFO notification
Notification::info([
    'title' => 'Information',
    'message' => 'Something informational happened'
]);

// WARNING notification
Notification::warning([
    'title' => 'Warning',
    'message' => 'Something needs attention'
]);

// ERROR notification
Notification::error([
    'title' => 'Error',
    'message' => 'Something went wrong'
]);
```

### 6.2 Simple Record Method

```php
// Create notification with automatic cleanup
Notification::record(
    'Title',
    'Message',
    Notification::LEVEL_WARNING
);
```

### 6.3 Record If Fails Pattern

**Most Common Pattern in Acelle:**

```php
Notification::recordIfFails(
    function () {
        // Code that might fail
        $result = someRiskyOperation();
    },
    'Operation Title',
    function ($exception) {
        // Optional: additional exception handling
        Log::error($exception->getMessage());
    },
    'Additional context: '
);
```

**Real Example from Console Kernel:**
```php
// Location: /app/Console/Kernel.php
Notification::recordIfFails(function () {
    SystemJob::run();
}, 'System Jobs Execution', null, 'Cannot execute system jobs: ');
```

### 6.4 Cleanup Patterns

**Cleanup Duplicate Notifications:**
```php
// Remove all notifications with same title before creating new one
Notification::cleanupDuplicateNotifications('CronJob');
```

**Clear by Title:**
```php
// Alias for cleanup (same functionality)
Notification::clearByTitle('System URL Issue');
```

**Cleanup All Notifications:**
```php
// Nuclear option - clear everything
Notification::cleanup();
```

### 6.5 Conditional Notification Creation

**Pattern Used in System Checks:**
```php
if ($problemDetected) {
    Notification::warning([
        'title' => 'Problem Detected',
        'message' => 'Details about the problem'
    ]);
} else {
    // Clear notification if problem resolved
    Notification::cleanupDuplicateNotifications('Problem Detected');
}
```

---

## 7. Admin Interface Integration

### 7.1 Admin Relationship

**Location:** `/app/Model/Admin.php`

```php
public function notifications()
{
    return Notification::orderBy('created_at', 'desc');
}
```

**Note:** This returns a query builder, not a relationship. All admins see all notifications.

### 7.2 Notification Controllers

**Admin Controller:** `/app/Http/Controllers/Admin/NotificationController.php`

**Routes:**
```php
// Admin routes (web.php)
Route::get('admin/notifications', 'NotificationController@index');
Route::get('admin/notifications/listing', 'NotificationController@listing');
Route::get('admin/notifications/popup', 'NotificationController@popup');
Route::post('admin/notifications/delete', 'NotificationController@delete');
```

**Key Methods:**
```php
public function index(Request $request)
{
    return view('admin.notifications.index');
}

public function listing(Request $request)
{
    $notifications = Notification::search($request)
        ->paginate($request->per_page);

    return view('admin.notifications.listing', [
        'notifications' => $notifications,
    ]);
}

public function popup(Request $request)
{
    return view('admin.notifications.popup');
}

public function delete(Request $request)
{
    if (isSiteDemo()) {
        echo trans('messages.operation_not_allowed_in_demo');
        return;
    }

    $notifications = Notification::whereIn('uid', $request->uids);
    $count = $notifications->count();

    foreach ($notifications->get() as $notification) {
        $notifications->delete();
    }

    echo trans('messages.notifications.deleted', ['number' => $count]);
}
```

### 7.3 User Controller (Limited)

**Location:** `/app/Http/Controllers/NotificationController.php`

**Purpose:** Basic CRUD for user-level notifications (rarely used)

**Routes:**
```php
// User routes (web.php)
Route::resource('notifications', 'NotificationController');
Route::post('notifications/{id}/hide', 'NotificationController@hide');
```

**API Route:**
```php
// API routes (api.php)
Route::resource('notification', 'NotificationController');
```

### 7.4 Search and Filtering

**Location:** `/app/Model/Notification.php`

```php
public static function filter($request)
{
    $query = self::select('notifications.*');

    // Keyword search
    if (!empty(trim($request->keyword))) {
        $query = $query->where('message', 'like', '%'.$request->keyword.'%');
    }

    // Level filter
    if (!empty($request->filters['level'])) {
        $query = $query->where('notifications.level', '=', $request->filters['level']);
    }

    return $query;
}

public static function search($request)
{
    $query = self::filter($request);
    $query = $query->orderBy($request->sort_order, $request->sort_direction);
    return $query;
}
```

---

## 8. Notification Lifecycle

### 8.1 Creation Flow

```
Trigger Event (Login, Exception, Schedule)
    ↓
Call Static Method (::warning, ::error, ::record)
    ↓
Merge Default Attributes
    ↓
Cleanup Duplicate Notifications (optional)
    ↓
Insert into Database
    ↓
Display in Admin Interface
```

### 8.2 Display Flow

```
Admin Loads Page
    ↓
Check Notification::count() for badge
    ↓
User Clicks Bell Icon
    ↓
JavaScript Loads Popup via AJAX
    ↓
Display Top 20 Notifications
    ↓
Option to View All in Full Page
```

### 8.3 Deletion Flow

```
Admin Views Notification List
    ↓
Selects Notifications (checkboxes)
    ↓
Clicks Delete Button
    ↓
AJAX POST to /admin/notifications/delete
    ↓
Batch Delete by UIDs
    ↓
Return Count of Deleted Notifications
    ↓
Refresh Listing
```

### 8.4 Automatic Cleanup

**When Cleanup Occurs:**
1. **Before creating new notification** - Removes duplicates with same title
2. **Problem resolved** - Explicit cleanup call in check methods
3. **Admin login** - Some checks auto-cleanup if no issue detected
4. **Exception handling** - BackendError keeps only last error

**Cleanup Strategy:**
```php
// Keeps only the LATEST notification with same title
public static function cleanupDuplicateNotifications($title)
{
    static::where('title', $title)->delete();
}
```

---

## 9. Key Differences from Laravel Notifications

| Feature | Acelle Notifications | Laravel Notifications |
|---------|---------------------|----------------------|
| **Purpose** | Admin alerts, system monitoring | User-facing notifications |
| **Channels** | Database only | Mail, Database, Broadcast, SMS, Slack |
| **Model** | Custom `Acelle\Model\Notification` | `Illuminate\Notifications\DatabaseNotification` |
| **Table** | `notifications` (custom schema) | `notifications` (Laravel schema) |
| **Trait** | None required | `Notifiable` trait required |
| **Facade** | Direct model calls | `Notification::send()` |
| **Creation** | Static methods (`::warning()`) | Notification classes with `via()` |
| **Queuing** | Not supported | `ShouldQueue` interface |
| **Read Tracking** | No read/unread | `read_at` timestamp |
| **User Association** | Optional (`admin_id`, `customer_id`) | Required (`notifiable_type`, `notifiable_id`) |
| **Cleanup** | Automatic duplicates removal | Manual or scheduled |

---

## 10. Best Practices

### 10.1 Creating Notifications

**DO:**
```php
// Use descriptive titles
Notification::warning([
    'title' => 'Campaign Sending Failed',
    'message' => 'Campaign "Summer Sale" failed to send due to SMTP error'
]);

// Include debug information for errors
Notification::error([
    'title' => 'Database Connection Lost',
    'message' => 'Unable to connect to database server',
    'debug' => $exception->getTraceAsString()
]);

// Use recordIfFails for risky operations
Notification::recordIfFails(function () {
    riskyDatabaseOperation();
}, 'Database Operation', null, 'Operation context: ');
```

**DON'T:**
```php
// Avoid vague titles
Notification::warning(['title' => 'Error', 'message' => 'Something failed']);

// Don't create duplicate notifications without cleanup
Notification::warning(['title' => 'Same Title']);
Notification::warning(['title' => 'Same Title']); // Creates duplicate!

// Don't use for user-facing notifications
Notification::info(['title' => 'Welcome', 'message' => 'Welcome to our app']);
```

### 10.2 Cleanup Strategy

**Cleanup Before Creating:**
```php
$title = 'Scheduled Task Failed';
Notification::cleanupDuplicateNotifications($title);
Notification::warning([
    'title' => $title,
    'message' => 'Task execution failed at ' . now()
]);
```

**Or Use Built-in Cleanup:**
```php
// Most methods cleanup automatically when $cleanup = true (default)
Notification::warning([
    'title' => 'System Check',
    'message' => 'Issue detected'
], $cleanup = true);
```

### 10.3 Error Handling

**Always Wrap Notification Creation:**
```php
try {
    Notification::warning([
        'title' => 'Issue Detected',
        'message' => 'Details'
    ]);
} catch (Throwable $e) {
    // Silent failure - don't let notification creation break app
    Log::error('Failed to create notification: ' . $e->getMessage());
}
```

**Use recordIfFails for Atomic Operations:**
```php
Notification::recordIfFails(
    function () {
        // Critical code that might fail
    },
    'Operation Name',
    function ($e) {
        // Optional: Log to external service
        Sentry::captureException($e);
    }
);
```

---

## 11. Migration Recommendations

### 11.1 Migrating to Laravel Notifications

If you want to use Laravel's notification system:

**Step 1: Create Laravel Notifications Table**
```bash
php artisan notifications:table
php artisan migrate
```

**Step 2: Add Notifiable Trait to Admin Model**
```php
use Illuminate\Notifications\Notifiable;

class Admin extends Model
{
    use Notifiable;
}
```

**Step 3: Create Notification Classes**
```php
php artisan make:notification CronJobNotification
```

**Step 4: Send Notifications**
```php
$admin->notify(new CronJobNotification($details));
```

### 11.2 Keeping Custom System

**Advantages:**
- Simple and focused on admin alerts
- No queue overhead
- Automatic cleanup of duplicates
- Minimal configuration
- Fast database-only storage

**Disadvantages:**
- No email/SMS support
- No per-user read tracking
- No queuing capabilities
- Separate from Laravel ecosystem

### 11.3 Hybrid Approach

**Use Both Systems:**
- **Custom Notifications** - System health, admin alerts
- **Laravel Notifications** - User-facing notifications, emails

```php
// System alert (custom)
Notification::warning(['title' => 'CronJob Issue', 'message' => 'Details']);

// User notification (Laravel)
$user->notify(new WelcomeNotification());
```

---

## 12. Troubleshooting

### 12.1 Common Issues

**Issue: Notifications Not Appearing**
```php
// Check if notifications exist
dd(Notification::count());

// Check admin relationship
dd(Auth::user()->admin->notifications()->count());

// Verify database connection
DB::connection()->getPdo();
```

**Issue: Duplicate Notifications**
```php
// Manual cleanup
Notification::cleanupDuplicateNotifications('Problematic Title');

// Or cleanup all
Notification::cleanup();
```

**Issue: Debug Info Not Saving**
```php
// Ensure migration ran
php artisan migrate:status

// Check column exists
Schema::hasColumn('notifications', 'debug');
```

### 12.2 Debugging Queries

```php
// Enable query logging
DB::enableQueryLog();

// Create notification
Notification::warning(['title' => 'Test', 'message' => 'Test message']);

// Check queries
dd(DB::getQueryLog());
```

### 12.3 Testing Notifications

```php
// Create test notification
Notification::info([
    'title' => 'Test Notification',
    'message' => 'This is a test: ' . now()
]);

// Verify creation
$latest = Notification::latest()->first();
dump([
    'title' => $latest->title,
    'message' => $latest->message,
    'level' => $latest->level,
    'created' => $latest->created_at
]);
```

---

## 13. Code Examples

### 13.1 Custom Notification Type

```php
namespace App\Library\Notification;

use Acelle\Model\Notification;

class CustomCheck extends Notification
{
    public static function check()
    {
        $title = 'Custom System Check';
        self::cleanupDuplicateNotifications($title);

        $status = self::performCheck();

        if (!$status['success']) {
            $warning = [
                'title' => $title,
                'message' => $status['message'],
                'debug' => $status['debug'] ?? null
            ];

            self::warning($warning);
        }
    }

    private static function performCheck()
    {
        // Your custom check logic
        return [
            'success' => false,
            'message' => 'Check failed',
            'debug' => 'Additional debug info'
        ];
    }
}
```

### 13.2 Scheduled Notification Check

```php
// In Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        Notification::recordIfFails(function () {
            // Your scheduled task
            performScheduledOperation();
        }, 'Scheduled Operation', null, 'Task failed: ');
    })->hourly();
}
```

### 13.3 Manual Notification Creation

```php
// In a controller or service
public function reportIssue($details)
{
    Notification::error([
        'title' => 'User Reported Issue',
        'message' => sprintf(
            'User %s reported: %s',
            auth()->user()->email,
            $details
        ),
        'debug' => json_encode(request()->all())
    ]);
}
```

---

## 14. Summary

### 14.1 System Overview

Acelle's notification system is a **custom, database-driven solution** designed specifically for administrative alerts and system monitoring. It operates independently of Laravel's built-in notification system, providing:

- **Simple API** - Static methods for quick notification creation
- **Automatic Cleanup** - Prevents duplicate alerts
- **Debug Support** - Full stack traces for errors
- **Real-time Display** - Popup notifications in admin interface
- **Focused Purpose** - Admin alerts, not user notifications

### 14.2 Key Takeaways

1. **Database Only** - No email, SMS, or other channels
2. **Admin-Focused** - System health monitoring and error tracking
3. **Auto-Cleanup** - Duplicate notifications removed automatically
4. **Three Levels** - INFO, WARNING, ERROR
5. **Separate System** - Not part of Laravel Notifications
6. **Simple Integration** - Easy to add custom notification types

### 14.3 When to Use

**Use Acelle Notifications For:**
- System health monitoring (CronJob, System URL)
- CLI error logging
- Admin alerts
- Configuration warnings
- Background job failures

**Use Laravel Notifications For:**
- User-facing notifications
- Email notifications
- SMS/Push notifications
- Multi-channel delivery
- Per-user read tracking

---

## 15. File Locations Reference

### 15.1 Core Files

```
/app/Model/Notification.php                     - Main notification model
/app/Notifications/ResetPassword.php            - Laravel notification (password reset)
/app/Library/Notification/CronJob.php           - CronJob check notification
/app/Library/Notification/SystemUrl.php         - System URL check notification
/app/Library/Notification/BackendError.php      - CLI error notification
```

### 15.2 Controllers

```
/app/Http/Controllers/Admin/NotificationController.php  - Admin notification interface
/app/Http/Controllers/NotificationController.php        - User notification interface (limited)
```

### 15.3 Views

```
/resources/views/admin/notifications/
    ├── index.blade.php      - Admin notification list page
    ├── listing.blade.php    - Notification table listing
    ├── popup.blade.php      - Notification popup sidebar
    └── _top.blade.php       - Top notification snippet

/resources/views/notifications/
    └── index.blade.php      - User notification page

/resources/views/elements/
    └── _notification.blade.php  - Reusable notification alert component

/resources/views/layouts/core/
    └── _top_notifications.blade.php  - Notification bell icon

/resources/views/vendor/notifications/
    └── email.blade.php      - Laravel notification email template
```

### 15.4 Migrations

```
/database/migrations/2018_07_31_173424_create_notifications_table.php
/database/migrations/2021_06_26_021528_add_debug_column_to_notifications.php
```

### 15.5 Listeners

```
/app/Listeners/AdminLoggedInListener.php  - Triggers notification checks on admin login
```

### 15.6 Exception Handler

```
/app/Exceptions/Handler.php  - Catches and logs CLI exceptions as notifications
```

---

## Document Information

**Generated:** January 29, 2026
**Acelle Version:** Based on codebase analysis
**Laravel Version:** Compatible with Laravel 5.x - 8.x
**Analysis Scope:** Complete notification system architecture
