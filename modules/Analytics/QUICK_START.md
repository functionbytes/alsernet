# Analytics Module - Quick Start Guide

## Overview

The Analytics module provides Google Analytics 4 (GA4) integration for tracking website statistics and displaying them in customizable dashboard widgets.

## Installation

### 1. Run Migration

```bash
php artisan migrate
```

This will add the default analytics settings to your database.

### 2. Activate Module

Ensure the module is active in `module.json`:

```json
{
    "active": 1
}
```

Or activate via command:

```bash
php artisan module:enable Analytics
```

## Configuration

### Step 1: Google Analytics Setup

1. **Create GA4 Property**
   - Go to [Google Analytics](https://analytics.google.com)
   - Create a new GA4 property
   - Note your Property ID (9-10 digit number)

2. **Create Service Account**
   - Go to [Google Cloud Console](https://console.cloud.google.com)
   - Enable Google Analytics Data API
   - Create a Service Account
   - Download the JSON credentials file

3. **Grant Access**
   - In GA4, go to Admin → Property Access Management
   - Add the service account email (from JSON) as a Viewer

### Step 2: Module Configuration

1. Navigate to `/setting/analytics`
2. Enable Google Analytics
3. Enter your Property ID
4. Upload or paste the JSON credentials
5. Click "Validate credentials" to test
6. Save the configuration

## Available Routes

### Settings
- `GET /setting/analytics` - Settings page
- `PUT /setting/analytics` - Update settings
- `POST /setting/analytics/validate-credentials` - Validate credentials
- `POST /setting/analytics/test-connection` - Test API connection
- `POST /setting/analytics/clear-cache` - Clear cached data

### Dashboard
- `GET /analytics/dashboard` - Analytics dashboard

### API Endpoints
- `GET /api/analytics/overview?range=last_7_days`
- `GET /api/analytics/top-pages?range=last_30_days`
- `GET /api/analytics/top-browsers?range=this_month`
- `GET /api/analytics/top-referrers?range=last_7_days`
- `GET /api/analytics/query` - Custom query

## Widget Usage

### Include Widgets in Your Views

#### General Statistics Widget
```blade
@include('analytics::widgets.general')
```

#### Top Pages Widget
```blade
@include('analytics::widgets.page')
```

#### Browsers Widget
```blade
@include('analytics::widgets.browser')
```

#### Referrers Widget
```blade
@include('analytics::widgets.referrer')
```

#### Empty State (when not configured)
```blade
@if(!$isConfigured)
    @include('analytics::widgets.empty-state')
@endif
```

### Widget Features

Each widget:
- Loads data asynchronously via AJAX
- Shows loading spinner during fetch
- Displays errors if data fetch fails
- Auto-refreshes based on configuration
- Supports multiple date ranges

## Date Ranges

Available date range options:
- `today` - Today's data
- `yesterday` - Yesterday's data
- `last_7_days` - Last 7 days (default)
- `last_30_days` - Last 30 days
- `this_month` - Current month
- `last_month` - Previous month
- `this_year` - Current year

## Configuration Options

### Cache Settings

In `/config/general.php`:

```php
'cache_lifetime' => 60, // minutes (default: 1 hour)
```

Or set via environment:

```env
ANALYTICS_CACHE_LIFETIME=60
```

### Enable/Disable Widgets

```php
setting([
    'analytics_dashboard_widgets' => [
        'general',
        'top_pages',
        'top_browsers',
        'top_referrers'
    ]
]);
```

### Custom Metrics

Available metrics:
- `sessions` - Total sessions
- `totalUsers` - Total users
- `newUsers` - New users
- `screenPageViews` - Page views
- `bounceRate` - Bounce rate
- `engagementRate` - Engagement rate
- `averageSessionDuration` - Average session duration
- `conversions` - Conversions
- `eventCount` - Event count

### Custom Dimensions

Available dimensions:
- `date` - Date
- `pageTitle` - Page title
- `fullPageUrl` - Full page URL
- `pagePath` - Page path
- `browser` - Browser name
- `country` - Country
- `city` - City
- `sessionSource` - Session source
- `sessionMedium` - Session medium
- `deviceCategory` - Device category
- `operatingSystem` - OS
- `language` - Language

## Custom Queries

### Using the Analytics Facade

```php
use Modules\Analytics\Facades\Analytics;
use Modules\Analytics\Period;

// Simple query
$data = Analytics::dateRange(Period::last7Days())
    ->metrics(['sessions', 'screenPageViews'])
    ->dimensions('date')
    ->get();

// Top pages
$pages = Analytics::fetchMostVisitedPages(Period::last30Days(), 10);

// Top browsers
$browsers = Analytics::fetchTopBrowsers(Period::thisMonth(), 10);

// Top referrers
$referrers = Analytics::fetchTopReferrers(Period::last7Days(), 10);
```

### Using the Controller

```php
use Modules\Analytics\Http\Controllers\AnalyticsController;

$controller = new AnalyticsController();

// Get overview data
$overview = $controller->overview(request());

// Get top pages
$pages = $controller->topPages(request());
```

## API Examples

### JavaScript/AJAX

```javascript
// Get overview data
fetch('/api/analytics/overview?range=last_7_days')
    .then(response => response.json())
    .then(data => {
        console.log(data.data.totals); // Metrics totals
        console.log(data.data.chart_data); // Chart data
    });

// Get top pages
fetch('/api/analytics/top-pages?range=last_30_days')
    .then(response => response.json())
    .then(data => {
        data.data.forEach(page => {
            console.log(page.title, page.url, page.views);
        });
    });

// Custom query
fetch('/api/analytics/query?range=last_7_days&metrics[]=sessions&dimensions=date')
    .then(response => response.json())
    .then(data => console.log(data));
```

## Permissions

Required permissions for different operations:

### View Dashboard
- `analytics.view.all`
- `analytics.dashboard.view`

### Manage Settings
- `analytics.settings.view`
- `analytics.settings.update`

### View Widgets
- `analytics.widgets.view`
- `analytics.widgets.general` (for general widget)
- `analytics.widgets.top_pages` (for pages widget)
- `analytics.widgets.top_browsers` (for browsers widget)
- `analytics.widgets.top_referrers` (for referrers widget)

### Full Management
- `analytics.manage.all`

## Troubleshooting

### Credentials Validation Fails

1. Ensure JSON is valid (use Format JSON button)
2. Check all required fields are present
3. Verify service account has Analytics API enabled
4. Confirm service account has access to GA4 property

### No Data Showing

1. Check if analytics is enabled: `setting('google_analytics_enable')`
2. Verify Property ID is correct
3. Test connection via settings page
4. Check browser console for JavaScript errors
5. Clear cache: `POST /setting/analytics/clear-cache`

### API Errors

1. Check credentials are valid
2. Verify service account has Viewer role in GA4
3. Ensure Google Analytics Data API is enabled
4. Check API quotas in Google Cloud Console

### Cache Issues

Clear analytics cache:

```php
Cache::tags(['analytics'])->flush();
```

Or via route:
```bash
curl -X POST /setting/analytics/clear-cache
```

## Performance

### Cache Strategy

- All analytics data is cached for 60 minutes by default
- Cache keys include: property ID, date range, metrics, dimensions
- Cache can be cleared manually via settings or automatically on settings update

### Query Limits

Default limits (configurable in `config/general.php`):
- Maximum results: 100
- Default limit: 20
- Maximum date range: 365 days

### Optimization Tips

1. Use appropriate cache lifetime based on your needs
2. Limit the number of metrics/dimensions per query
3. Use pagination for large result sets
4. Enable only needed widgets
5. Consider using background jobs for report generation

## Security

### Credentials Storage

- Credentials are stored encrypted in the database
- Only administrators can access settings
- Credentials are validated before storage
- JSON structure is verified

### API Access

- All routes require authentication
- Module must be active
- Permissions are enforced
- Rate limiting recommended

## Testing

### Manual Testing

1. Navigate to `/setting/analytics`
2. Upload test credentials
3. Click "Validate credentials"
4. Click "Test connection"
5. View dashboard at `/analytics/dashboard`

### Automated Testing

```php
// Test credentials validation
$response = $this->post('/setting/analytics/validate-credentials', [
    'property_id' => '123456789',
    'credentials' => $jsonCredentials
]);

$response->assertStatus(200)
    ->assertJson(['status' => true]);

// Test data fetching
$response = $this->get('/api/analytics/overview?range=last_7_days');

$response->assertStatus(200)
    ->assertJsonStructure([
        'status',
        'data' => [
            'chart_data',
            'totals'
        ]
    ]);
```

## Support

For issues or questions:
1. Check logs in `storage/logs/laravel.log`
2. Enable debug mode to see detailed errors
3. Review Google Analytics API documentation
4. Check service account permissions

## Resources

- [Google Analytics 4 Documentation](https://support.google.com/analytics)
- [Google Analytics Data API](https://developers.google.com/analytics/devguides/reporting/data/v1)
- [Service Account Setup](https://support.google.com/analytics/answer/9304153)
- [Laravel Cache Documentation](https://laravel.com/docs/cache)
