---
name: Page Module Audit Findings
description: Known issues and patterns from the March 2026 full audit of modules/Page
type: project
---

## Confirmed Issues (not yet fixed)

### Critical
- `PageController::ajaxSlug` calls `$this->pageService->generateSlug()` which does not exist — method is `generateUniqueSlug()`. This is a fatal PHP error on that AJAX endpoint.
- Soft-deleted page restore/force-delete flow has no UI in the index view to surface trashed pages. The routes and controller methods exist but there is no "Trash" tab or filter in the index blade.

### High
- `PublishScheduledPagesJob::publishPage()` saves the model twice in a row (once to publish, once to clear `publish_at`) — should be a single `update()` call.
- `PageService::sanitizeContent()` is a regex-based homegrown sanitizer, not HTMLPurifier. The mews/purifier package is already referenced in comments but not used. Rich content (from WYSIWYG) may contain vectors not caught by the current regexes.
- `getStatuses()` on the Page model returns English strings ('Draft', 'Published', 'Pending Review') but the UI is entirely in Spanish. Filter dropdowns in the index view hardcode Spanish labels without using these values.
- `PageServiceProvider` registers a nav icon `fa-duotone fa-file-lines` — duotone icons are Font Awesome Pro only and are NOT available in this project (CLAUDE.md explicitly says use `fas fa-*` / `far fa-*` / `fab fa-*`).

### Medium
- `RecordPageViewJob` has no `$timeout` set, relying on the queue default. Given it hits the DB, a short explicit timeout (30s) is appropriate.
- `PublicController::index()` (the API listing) is also wired to the web `routes/web.php` catchall under `/{path}`, meaning `/api/v1/pages` and `/pages` both exist but with completely different shapes and auth. The naming collision on `Route::name('pages.index')` in `api.php` and `Route::name('pages.index')` in `web.php` will silently override whichever is registered last.
- `getFullSlugAttribute()` and `getAncestorsAttribute()` walk the parent chain by lazy-loading `$this->parent` in a loop — pure N+1 if more than one page is loaded without eager-loading the full ancestor chain.
- `PageController::index()` builds the stats cache inline inside the controller instead of delegating to the service — violates single responsibility and makes the stats untestable in isolation.
- Translation slug uniqueness check in `CreatePageRequest::withValidator()` does not `ignoreId` on update (the same check is missing from `UpdatePageRequest`).

### Low
- `Page::getStatuses()` uses PHP constants (`STATUS_DRAFT`) but should use a backed Enum for Laravel 12 / PHP 8.4 compatibility and type safety.
- `Page::setTitleAttribute()` is a legacy mutator style — should be `protected function title(): Attribute` (Eloquent attribute casting API).
- `PageControllerTest::test_authenticated_user_can_view_pages_index()` asserts `assertViewIs('page::pages.index')` but the actual view is `page::pages.pages.index`. This test would always fail.
- `PageService::duplicatePage()` builds translation data manually with a long `create([...])` array and does not re-sanitize translation content on duplication.
