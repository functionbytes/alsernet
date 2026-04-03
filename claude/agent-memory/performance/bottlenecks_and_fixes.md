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

### Fix 6 — Non-sargable date function in correlated subquery (ReviewController::index)
- **File**: `modules/Reviews/app/Http/Controllers/ReviewController.php`
- **Problem**: `DATE(review_replies.created_at) = CURDATE()` wraps column in a function, preventing index use.
- **Fix**: Replaced with `created_at >= CURDATE() AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)` — sargable range that allows the index on `created_at` to be used.
