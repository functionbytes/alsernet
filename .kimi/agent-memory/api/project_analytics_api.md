---
name: Analytics module API conventions
description: Analytics module endpoint patterns, data format, and key implementation notes
type: project
---

The Analytics module exposes JSON endpoints under `api/analytics` (web middleware + auth), not under `/api/v1/`. Routes are in `modules/Analytics/routes/web.php`.

**Current endpoints** (all GET, named `api.analytics.*`):
- `/api/analytics/overview` — sessions/users/pageviews/bounceRate by date
- `/api/analytics/top-pages` — most visited pages
- `/api/analytics/top-browsers` — browser breakdown
- `/api/analytics/top-referrers` — referrer sources
- `/api/analytics/query` — custom metric/dimension query
- `/api/analytics/top-countries` — top 20 countries by sessions
- `/api/analytics/device-categories` — mobile/desktop/tablet breakdown
- `/api/analytics/operating-systems` — top 10 OS by sessions
- `/api/analytics/traffic-sources` — top 10 source+medium pairs by sessions
- `/api/analytics/session-metrics` — aggregate sessions, newUsers, avgSessionDuration

**CRITICAL — array key format:** `ResponseTrait::getTable()` builds rows as **associative arrays** keyed by GA4 dimension/metric header names (e.g. `$row['country']`, `$row['sessions']`), NOT numeric indices. Numeric access (`$row[0]`) always returns null. This is a known issue in the pre-existing `topReferrers` controller method.

**GA4 dimension names used:**
- country, deviceCategory, operatingSystem, sessionSource, sessionMedium, browser, sessionSource, pageTitle, fullPageUrl, date

**`fetchSessionMetrics`** uses `metricAggregation(MetricAggregation::TOTAL)` and reads from `$response->metricAggregationsTable` (keyed by metric name, with `aggregation` key = 'TOTAL').

**Why:** Analytics module uses web-middleware JSON endpoints (not Sanctum API routes) because they serve the internal dashboard, not external consumers.
**How to apply:** When adding new analytics endpoints, add fetch methods to `Analytics.php` using named dimension/metric keys, map rows with named array access in the controller, and register routes in `modules/Analytics/routes/web.php` within the existing `api/analytics` group.
