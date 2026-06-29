# Analytics Module

Google Analytics 4 integration for Laravel using the official Google Analytics Data API.

## Features

- ✅ Fluent query builder for GA4 data
- ✅ Support for metrics, dimensions, filters, and ordering
- ✅ Type-safe PHP 8.1+ implementation
- ✅ Laravel facade for easy access
- ✅ Comprehensive exception handling
- ✅ Macroable for extensibility

## Installation

1. The module is already installed. Install dependencies:
```bash
composer install
```

2. Configure Google Analytics credentials in your settings table:
   - `analytics_service_account_credentials` - Service account JSON
   - `analytics_property_id` - GA4 property ID (numeric)

## Quick Start

```php
use Modules\Analytics\Facades\Analytics;
use Modules\Analytics\Period;

// Fetch most visited pages (last 30 days)
$pages = Analytics::fetchMostVisitedPages(Period::days(30), 20);

// Fetch top referrers
$referrers = Analytics::fetchTopReferrers(Period::days(7), 10);

// Fetch top browsers
$browsers = Analytics::fetchTopBrowsers(Period::months(1), 10);
```

## Custom Queries

```php
use Modules\Analytics\Facades\Analytics;
use Modules\Analytics\Period;

$results = Analytics::dateRange(Period::days(7))
    ->metrics(['screenPageViews', 'sessions', 'users'])
    ->dimensions(['pageTitle', 'browser', 'deviceCategory'])
    ->orderByMetricDesc('screenPageViews')
    ->limit(50)
    ->get();

// Access the data
foreach ($results->table as $row) {
    echo "{$row['pageTitle']}: {$row['screenPageViews']} views\n";
}
```

## Advanced Filtering

```php
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;

// Filter by dimension
$blogPosts = Analytics::dateRange(Period::months(1))
    ->metrics('sessions')
    ->dimensions('pageTitle')
    ->whereDimension('pageTitle', MatchType::CONTAINS, 'blog')
    ->get();

// Filter by metric value
$popularPages = Analytics::dateRange(Period::days(30))
    ->metrics('screenPageViews')
    ->dimensions('pageTitle')
    ->whereMetric('screenPageViews', NumericFilter\Operation::GREATER_THAN, 100)
    ->get();
```

## Period Helpers

```php
use Modules\Analytics\Period;
use Carbon\Carbon;

// Predefined periods
$last7Days = Period::days(7);
$last30Days = Period::days(30);
$last3Months = Period::months(3);
$lastYear = Period::years(1);

// Custom period
$custom = Period::create(
    Carbon::parse('2026-01-01'),
    Carbon::parse('2026-01-31')
);
```

## Available Metrics

Common GA4 metrics you can use:

- `screenPageViews` - Page views
- `sessions` - Sessions
- `users` - Users
- `newUsers` - New users
- `bounceRate` - Bounce rate
- `averageSessionDuration` - Average session duration
- `eventCount` - Event count

See full list: https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema

## Available Dimensions

Common GA4 dimensions:

- `pageTitle` - Page title
- `fullPageUrl` - Full page URL
- `browser` - Browser
- `deviceCategory` - Device category (mobile/desktop/tablet)
- `country` - Country
- `sessionSource` - Session source
- `sessionMedium` - Session medium

## Documentation

- **Implementation Summary**: See `IMPLEMENTATION_SUMMARY.md`
- **Verification Checklist**: See `VERIFICATION_CHECKLIST.md`
- **Complete Guide**: See `/ANALYTICS_MODULE_IMPLEMENTATION.md`

## Architecture

The module uses a trait-based architecture with 10 specialized traits:

1. DateRangeTrait - Date range handling
2. MetricTrait - Metrics definition
3. DimensionTrait - Dimensions definition
4. OrderByMetricTrait - Metric ordering
5. OrderByDimensionTrait - Dimension ordering
6. FilterByDimensionTrait - Dimension filtering
7. FilterByMetricTrait - Metric filtering
8. MetricAggregationTrait - Aggregations
9. RowOperationTrait - Row operations
10. ResponseTrait - Response formatting

## Error Handling

The module throws specific exceptions:

- `InvalidConfiguration` - Invalid credentials or property ID
- `InvalidPeriod` - Invalid date range (start after end)

## Requirements

- PHP 8.1+
- Laravel 10+
- Google Analytics 4 property
- Google Cloud service account with Analytics Data API enabled

## License

Proprietary - Inoqualab Project
