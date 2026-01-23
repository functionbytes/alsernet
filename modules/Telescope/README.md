# Telescope Module

Laravel Telescope is an elegant debugging assistant for the Laravel framework. This module integrates Telescope into the modular architecture of the application.

## Overview

**Telescope** provides insight into the requests coming into your application, exceptions, log entries, database queries, queued jobs, mail, notifications, cache operations, and more.

## Features

- **Request Inspection**: View details about all HTTP requests to your application
- **Exception Tracking**: Track and debug exceptions with full stack traces
- **Database Query Monitoring**: View all database queries with timing information
- **Job Monitoring**: Track queued jobs and their execution
- **Mail Inspection**: View emails sent from your application
- **Cache Operations**: Monitor cache hits and misses
- **Log Entries**: View all application log entries
- **Notification Tracking**: Monitor notifications sent by your application
- **Event Inspection**: Track events and listeners
- **Schedule Monitoring**: Monitor scheduled job execution

## Installation & Setup

### 1. Module Already Integrated

The Telescope module is already integrated into the application. The service provider is registered in `bootstrap/providers.php`.

### 2. Access Telescope

Telescope is available at `/telescope` when running in development mode.

```
http://localhost/telescope
```

### 3. Configuration

Telescope configuration is controlled via the `config/telescope.php` file in the main application.

### Environment-Based Loading

The Telescope module is only active in non-production environments by default:

```php
if (!$this->app->environment('production')) {
    Telescope::night();
}
```

This means Telescope will not record entries in production. To enable Telescope in production:

1. Edit `modules/Telescope/app/Providers/TelescopeServiceProvider.php`
2. Modify the environment check in the `boot()` method
3. Clear caches with `php artisan cache:clear`

## Usage

### Accessing Telescope Dashboard

Navigate to `/telescope` in your browser to access the Telescope dashboard.

### Features Available

#### 1. Requests Tab
- View all HTTP requests
- Request headers, body, and response
- Response time and status codes
- Database query count

#### 2. Exceptions Tab
- View all exceptions caught by the application
- Full stack traces
- Context information

#### 3. Logs Tab
- View application log entries
- Filter by log level
- Search through logs

#### 4. Queries Tab
- View all database queries
- Execution time
- Number of rows affected
- Slow query warnings

#### 5. Jobs Tab
- View queued jobs
- Execution status and duration
- Failed job tracking

#### 6. Mail Tab
- View all emails sent from the application
- Email recipients, subject, and body
- Attachment information

#### 7. Notifications Tab
- View all notifications sent
- Notification type and status
- Recipient information

#### 8. Cache Tab
- View cache operations
- Cache hits and misses
- Operation duration

#### 9. Events Tab
- View dispatched events
- Event listeners and handlers

#### 10. Schedule Tab
- View scheduled job execution
- Execution time and status
- Command output

## Filtering

The module includes entry filtering to avoid storing excessive data:

- **Skips Asset Requests**: Static files (js, css, images, fonts) are not recorded
- **Skips Health Checks**: Health checks, ping routes, and Livewire requests are not recorded
- **Skips Queue Workers**: Long-running queue workers and schedulers are filtered

### Custom Filtering

To add custom filters, edit `modules/Telescope/app/Providers/TelescopeServiceProvider.php` and modify the `filterEntries()` method.

## Development Best Practices

### 1. Use Telescope to Debug

When developing features, use Telescope to:
- Verify database queries are optimized
- Check for N+1 query problems
- Monitor email sending
- Track job execution
- View exception stack traces

### 2. Monitoring Performance

Use Telescope to identify:
- Slow database queries (> 100ms)
- Excessive query counts
- Large request payloads
- Memory usage patterns

### 3. Debugging Requests

For debugging specific requests:
1. Open Telescope dashboard
2. Navigate to the Requests tab
3. Click on the relevant request
4. View detailed information including headers, body, and database queries

## Security Considerations

- **Never enable in production** without proper access controls
- Telescope records sensitive information (passwords, tokens, etc.)
- Implement authentication/authorization before exposing in production
- Consider disabling specific watchers for sensitive data

## Watchers

The following watchers are enabled:

- CacheWatcher
- DatabaseWatcher
- ExceptionWatcher
- GateWatcher
- JobWatcher
- LogWatcher
- MailWatcher
- NotificationWatcher
- QueryWatcher
- RequestWatcher
- ScheduleWatcher

Optional watchers (disabled by default):
- ModelWatcher: Enable with `TELESCOPE_MODEL_WATCHER=true`
- EventWatcher: Enable with `TELESCOPE_EVENT_WATCHER=true`

## Configuration Files

### Main Configuration
- `config/telescope.php` - Main Telescope configuration

### Module Files
- `modules/Telescope/module.json` - Module metadata
- `modules/Telescope/app/Providers/TelescopeServiceProvider.php` - Service provider

## Troubleshooting

### Telescope Not Showing Data

1. Verify environment is not production: `APP_ENV=local` in `.env`
2. Clear caches: `php artisan cache:clear`
3. Check that routes are loaded: `php artisan route:list | grep telescope`

### Telescope Not Accessible

1. Ensure application is running in development mode
2. Check that the module is enabled in `modules_statuses.json`
3. Verify service provider is registered in `bootstrap/providers.php`
4. Clear all caches

### Performance Issues

If Telescope is causing performance issues:

1. Disable model watcher: `TELESCOPE_MODEL_WATCHER=false`
2. Disable event watcher: `TELESCOPE_EVENT_WATCHER=false`
3. Reduce data retention: Adjust `TELESCOPE_STORAGE_DAYS` in `.env`

## Documentation Links

- [Laravel Telescope Official Docs](https://laravel.com/docs/telescope)
- [Telescope GitHub](https://github.com/laravel/telescope)

## Module Status

- **Status**: ✓ Active
- **Version**: Matches Laravel Telescope v5.15+
- **Environment**: Development mode only
- **Routes**: Auto-registered by package
