---
name: Performance bottlenecks and fixes applied
description: Bottlenecks found and optimization strategies applied in the Inoqualabs project
type: project
---

## Bottlenecks Found and Fixes Applied (2026-03-26)

### Fix 1 — N+1 in `updateSettings()` (SettingsHelper)
- **File**: `modules/System/app/Helpers/SettingsHelper.php`
- **Problem**: 3 queries per setting key (WHERE exists, first(), update()).
- **Fix**: Replaced the foreach loop with `Setting::upsert($rows, ['key'], ['value'])` — single query regardless of payload size. Added `Cache::forget('settings')` after upsert.
- **Column names confirmed**: `key`, `value` (table: `settings`).

### Fix 2 — Unbounded `ini_set` in AppServiceProvider
- **File**: `app/Providers/AppServiceProvider.php`
- **Problem**: `ini_set('memory_limit', '-1')` and `ini_set('pcre.backtrack_limit', '1000000000')` applied to every HTTP request.
- **Fix**: Removed both `ini_set` calls from `configureApplicationDefaults()`. Added scoped `ini_set('memory_limit', '512M')` only in `handle()` of `CreateBackupJob` and `ExportReviewsJob`.

### Fix 3 — DB-query accessors in Page `$appends`
- **File**: `modules/Page/app/Models/Page.php`
- **Problem**: `$appends = ['url', 'featured_image', 'full_slug']` caused DB queries on every serialization (toArray, toJson, DataTable responses).
- **Fix**: Cleared `$appends` to `[]`. Accessors still callable explicitly. API Resources (`PageResource`, `PublicPageResource`) already access them explicitly.

### Fix 4 — N+1 parent-chain traversal in `getFullSlugAttribute` / `getAncestorsAttribute`
- **File**: `modules/Page/app/Models/Page.php`
- **Problem**: Both methods traversed `$current->parent` lazily, triggering one query per level.
- **Fix**: Added `$current->relationLoaded('parent')` guard — only traverses if parent was already eager-loaded. Added depth cap of 5. Callers needing full slug/ancestors must use `with('parent.parent.parent')`.

### Fix 5 — Full table scan in `tagsList()` (ReviewController)
- **File**: `modules/Reviews/app/Http/Controllers/ReviewController.php`
- **Problem**: Loaded all `review_moderations.tags` rows into PHP with no limit.
- **Fix**: Added `->limit(500)` cap on the query; cached result in Redis for 30 minutes under key `reviews:tags-list`. Search filtering is applied in-memory on the cached collection.

### Fix 7 — Page module: 4 bottlenecks fixed (2026-04-04)

#### 7A — N+1 blacklist check in PageCacheService::isBlacklisted()
- **File**: `modules/Page/app/Services/PageCacheService.php`
- **Problem**: Called `PageCacheConfig::where(...)->exists()` once per locale per page during warmCache() — 20 queries for 10 pages × 2 locales.
- **Fix**: `Cache::remember('pages:cache:blacklist', 300, ...)` loads full blacklist set once; `in_array()` checks in memory. Key invalidated on `forget()` and `flushAll()`.

#### 7B — 3 queries in PageAnalyticsService::summary() merged to 1
- **File**: `modules/Page/app/Services/PageAnalyticsService.php`
- **Problem**: `summary()` called `avgTimeOnPage()` and `bounceRate()` as separate methods, each issuing their own query against `page_views` for the same page + date range.
- **Fix**: Single `DB::table('page_views')->selectRaw(...)` with `AVG(CASE ...)`, `SUM(CASE ...)`, and `COUNT(CASE ...)` expressions. `avgTimeOnPage()` and `bounceRate()` remain for external callers.

#### 7C — PHP-side webhook filtering replaced with SQL JSON_CONTAINS
- **File**: `modules/Page/app/Services/PageWebhookService.php`
- **Problem**: `PageWebhook::active()->get()` loaded ALL active webhooks into PHP, then `->filter(fn => $webhook->subscribesTo($event))` filtered in memory.
- **Fix**: `->whereJsonContains('events', $event)` pushes the filter to MariaDB. `events` column is a JSON array of event name strings.

#### 7D — Double query + PHP-side pruning in Versionable trait
- **File**: `modules/Page/app/Traits/Versionable.php`
- **Problem**: `getNextVersionNumber()` and `getCurrentVersionNumber()` each fetched a full row via `->latest()->first()` to read `version_number`. `pruneVersions()` used `->skip($keep)->pluck('id')` on an Eloquent relationship scope (not SQL-translated; loaded all rows in PHP).
- **Fix**: Both version number methods replaced with `$this->versions()->max('version_number') ?? 0`. `pruneVersions()` rewritten using `PageVersion::where('page_id', ..)->orderByDesc('id')->skip($keep)->pluck('id')` on the model directly (SQL LIMIT/OFFSET), then single `whereIn()->delete()`.

### Fix 6 — Non-sargable date function in correlated subquery (ReviewController::index)
- **File**: `modules/Reviews/app/Http/Controllers/ReviewController.php`
- **Problem**: `DATE(review_replies.created_at) = CURDATE()` wraps column in a function, preventing index use.
- **Fix**: Replaced with `created_at >= CURDATE() AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)` — sargable range that allows the index on `created_at` to be used.
