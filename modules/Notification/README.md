# Notification Module

Notification Module for Alsernet. Manages system notifications, user notifications, and notification preferences.

## Features

- **Notification Management** - Create, read, update, delete notifications
- **User Notifications** - Send notifications to users
- **Notification Preferences** - Allow users to manage notification preferences
- **API Endpoints** - REST API for notification management
- **Real-time Notifications** - Support for real-time notification delivery

## Routes

Manager Routes (`/manager/notifications/`):
- GET / - List all notifications
- GET /create - Create form
- POST /store - Store new notification
- GET /{id}/edit - Edit form
- POST /{id}/update - Update notification
- DELETE /{id} - Delete notification

API Routes (authenticated):
- GET /api/notifications - List user notifications
- POST /api/notifications/{id}/read - Mark as read
- POST /api/notifications/mark-all-read - Mark all as read

## Models

- **Notification** - Main notification model
- **Notifications** - Notification details model (if separate)

## Architecture

```
Modules/Notification/
├── app/
│   ├── Http/
│   │   ├── Controllers/Managers/
│   │   │   ├── NotificationController.php
│   │   │   └── NotificationsController.php
│   │   └── Requests/
│   ├── Entities/
│   │   ├── Notification.php
│   │   └── Notifications.php (if applicable)
│   ├── Policies/
│   │   └── NotificationPolicy.php
│   └── Providers/
│       └── NotificationServiceProvider.php
├── config/
│   └── notification.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── managers.php
├── resources/
│   ├── views/
│   │   ├── managers/
│   │   └── components/
│   │       └── notifications.blade.php (Bell dropdown component)
│   ├── css/
│   │   └── notifications.css (Extracted styles)
│   └── js/
│       └── notifications.js (Extracted JavaScript)
├── tests/
├── composer.json
├── module.json
└── README.md
```

## Frontend Architecture

### JavaScript Module (`resources/js/notifications.js`)

The notification system is built as a modular IIFE (Immediately Invoked Function Expression) that manages:

**Features:**
- Auto-initialization from data attributes on the notification component
- Configuration-driven route generation (no hardcoded URLs)
- AJAX-based notification loading and marking as read
- Auto-refresh at configurable intervals (default: 60 seconds)
- User authentication via CSRF tokens and credentials

**Key Functions:**
- `NotificationManager.init(options)` - Initialize with route configuration
- `NotificationManager.refresh()` - Manually refresh notifications
- `NotificationManager.destroy()` - Stop auto-refresh and cleanup
- `NotificationManager.getUnreadCount()` - Get current unread count

**Route Usage:**
Routes are passed from the Blade template via `route()` helper and stored in data attributes:

```blade
<li id="notifications-dropdown"
    data-api-index-route="{{ route('api.notifications.index') }}"
    data-api-read-route="{{ route('api.notifications.read', ['id' => '{id}']) }}"
    data-mark-all-read-route="{{ route('api.notifications.mark-all-read') }}">
```

The JavaScript extracts these routes and uses them for API calls, automatically replacing `{id}` placeholders with actual notification IDs.

### CSS Module (`resources/css/notifications.css`)

Comprehensive styling for the notification component:

**Features:**
- Bootstrap-based responsive design
- Color variants for different notification types (primary, success, danger, warning, info, secondary)
- Pulse animation for unread badge
- Dark mode support via `@media (prefers-color-scheme: dark)`
- Mobile responsive (breakpoint at 576px)
- Accessibility improvements (focus-visible states)

**Key Classes:**
- `.notification-item` - Individual notification item styling
- `.notification-icon` - Icon container with color variants
- `.notification-title`, `.notification-message`, `.notification-time` - Text hierarchy
- `.notification-badge` - "NUEVO" (NEW) indicator
- `.pulse` animation - Badge animation for unread notifications

### Blade Component (`resources/views/components/notifications.blade.php`)

Clean, modular Blade template that:

1. Passes Laravel routes via data attributes (no hardcoded URLs)
2. Loads external CSS file
3. Loads external JavaScript file
4. Uses asset() helper for proper URL generation
5. Maintains semantic HTML structure
6. Supports Bootstrap 5.3 styling

**Configuration via Data Attributes:**
```blade
<li id="notifications-dropdown"
    data-api-index-route="{{ route('api.notifications.index') }}"
    data-api-read-route="{{ route('api.notifications.read', ['id' => '{id}']) }}"
    data-mark-all-read-route="{{ route('api.notifications.mark-all-read') }}"
    data-refresh-interval="60000"
    data-limit="4">
```

## Asset Publishing

The `NotificationServiceProvider` automatically publishes assets to the public directory:

```bash
php artisan vendor:publish --tag=notification-assets
```

This copies:
- `resources/css/notifications.css` → `public/Modules/Notification/css/notifications.css`
- `resources/js/notifications.js` → `public/Modules/Notification/js/notifications.js`

### Benefits of This Architecture

1. **Separation of Concerns**
   - HTML structure in Blade templates
   - Styling in separate CSS file
   - Logic in separate JavaScript module

2. **No Hardcoded URLs**
   - Routes are generated by Laravel's `route()` helper
   - JavaScript uses data attributes for configuration
   - Ensures proper authentication and CSRF tokens

3. **Maintainability**
   - Changes to JavaScript don't require Blade template updates
   - CSS can be updated independently
   - Clear module boundaries

4. **Reusability**
   - NotificationManager can be used in multiple places
   - CSS classes can be extended or overridden
   - Routes are centralized in Laravel routing

5. **Security**
   - CSRF tokens automatically included in AJAX calls
   - Authentication middleware on API routes (`auth:sanctum`)
   - Data validation on server side

6. **Performance**
   - Browser caches JavaScript and CSS files
   - Configurable auto-refresh interval
   - Efficient DOM manipulation

## License

Proprietary - Alsernet
