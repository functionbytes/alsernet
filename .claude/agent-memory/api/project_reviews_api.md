---
name: Reviews API conventions
description: Patterns, classes and route conventions for the Reviews module REST API
type: project
---

## Reviews Module API

Routes registered at `/api/reviews` (no `/v1/` prefix in this module).

Route file: `modules/Reviews/routes/api.php`
Controller: `modules/Reviews/app/Http/Controllers/Api/ReviewController.php`

### Middleware stack (all routes)
`api`, `auth:sanctum`, `throttle:reviews:api`, `AddApiHeaders`, `HandleApiExceptions`

- `AddApiHeaders` adds `X-API-Version: 1.0` and `X-Request-ID` to every response.
- `HandleApiExceptions` catches `ThrottleRequestsException`, `HttpException`, and general `\Throwable` and formats them as consistent JSON errors.
- Rate limiter `reviews:api` is registered in `ReviewsServiceProvider`: admin = 1000/hr, user = 100/hr, guest = 20/hr.

### Response envelope — use `ApiResponse` (not `response()->json()` directly)
`Modules\Reviews\Http\Resources\ApiResponse` has three static helpers:
- `ApiResponse::success(data, message, statusCode, meta, links)` — single resource or raw data
- `ApiResponse::resource(JsonResource, message, statusCode)` — wraps a JsonResource
- `ApiResponse::paginated(ResourceCollection, message)` — adds pagination meta + links

### Resources available
- `ReviewResource` — full review with location, moderation (auth-gated), replies
- `ReviewStatsResource` — aggregate stats (totalReviews, averageRating, ratingDistribution, etc.)
- `DashboardStatsResource` — chart-ready KPIs for the dashboard

### Policy — `ReviewPolicy`
- `viewAny`: requires `reviews.reviews.view` permission
- `view`: requires `reviews.reviews.view` AND user owns the location connection OR is `super-admin`

**Why:** IDOR protection — users may only see reviews belonging to their own Google Business connections.

**How to apply:** Always call `$this->authorize('view', $review)` in `show` and `suggestions`; `$this->authorize('viewAny', Review::class)` in `index` and `stats`.

### Suggestions endpoint
Uses `ReviewAutoSuggestionService::suggestTemplates(Review)` which queries the `review_auto_suggestions` table, falls back to `ReviewReplyTemplate` by category (positive/negative/neutral/general) if no rules match.

### Stats endpoint
Uses a single raw SQL query (no `ReviewDashboardService`). Results are cached with key `reviews_stats_{md5(filters)}` using TTL from `config('reviews.general.cache.stats_ttl_minutes', 5)`.
