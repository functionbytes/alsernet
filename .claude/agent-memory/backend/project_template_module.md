---
name: Template Module Architecture & Security Patterns
description: Key patterns, fixes, and conventions in the Template module (menus, themes, versioning)
type: project
---

## Module Location
`modules/Template/` — manages themes, menus, shortcodes, template versioning.

## Key Conventions

### Controllers
- `TemplateController` extends `App\Http\Controllers\Controller` (has `AuthorizesRequests`).
- `MenuController` extends `App\Http\Controllers\Controller`.
- Policy registration happens in `EventServiceProvider` via `$policies` array, NOT in AppServiceProvider.

### Policy Registration
`modules/Template/app/Providers/EventServiceProvider.php` has `$policies` mapping.
`MenuPolicy` maps `Menu::class => MenuPolicy::class`.
No `TemplatePolicy` exists yet — authorize() calls on Template model will need one or use `before()` gate.

### Versioning
- `Versionable` trait in `modules/Template/app/Traits/Versionable.php` handles version management.
- `TemplateObserver::created()` creates v1 automatically — do NOT also call `createVersion()` manually in the service.
- `TemplateObserver::updated()` creates a snapshot only when `content` field is dirty.

### Template Discovery
- Templates are discovered from `platform/themes/` filesystem, NOT from DB alone.
- `TemplateManager` scans `platform/themes/*/template.json` files.
- `createTemplateDirectories()` in TemplateService uses `platform/themes/{slug}` as the base path.
- `StoreTemplateRequest` `template_path` must match: `platform/themes/{slug}`.

### Menu Reference Type Whitelist
`MenuService::$allowedReferenceTypes` whitelist:
- `\Modules\Page\Models\Page::class`
- `\Modules\Blog\Models\Post::class`
- `\Modules\Blog\Models\Category::class`

### Cache Strategy
- Menu cache keys: `menu.{location}` — cleared per-location, never via `Cache::flush()`.
- Screenshot cache key: `template:screenshot:{key}:{filemtime}` — auto-invalidates on file change.
- Theme options cache: `Setting::clearPrefixCache('theme.option')`.

### ThemeOptionController
Uses `$allowedKeys` whitelist (explicit list of ~50 keys) + `$request->only()` to prevent mass assignment via settings.
Dynamic color keys (from view loops) are in the allowed list implicitly via the view — add them if new keys appear.

### Security Applied (2026-03-26)
- FIX 1: `Blade::render()` sandboxed with `sanitizeTemplateContent()` — rejects @php, {!!, @inject, @eval, <?php, <?=, backticks.
- FIX 2: `renderMenuItem()` uses `e()` on `full_url`, `target`, `icon`.
- FIX 3: `resolveNodeUrl()` validates `reference_type` against allowedReferenceTypes whitelist.
- FIX 4: All CRUD methods in TemplateController and MenuController have `$this->authorize()`.
- FIX 5: Template model has `SoftDeletes` trait.
- FIX 6: Removed double version creation (Observer only).
- FIX 7: `clearMenuCache()` uses `Menu::distinct()->pluck('location')` instead of `Cache::flush()`.
- FIX 8: `hasChildren()` checks `relationLoaded()` before querying DB.
- FIX 9: ThemeOptionController uses `$request->only($allowedKeys)`.
- FIX 10: Removed empty `bootVersionable()` from Versionable trait.
- FIX 11: StoreTemplateRequest `template_path` uses `platform/themes/` (not `public/templates/`).
- FIX 12: Menu scopes typed as `Builder`, MenuItem::getFullUrlAttribute typed as `?string`.
- FIX 13: `mkdir()` replaced with `File::makeDirectory()`.
- FIX 15: All `\Log::` replaced with `use Illuminate\Support\Facades\Log` + `Log::`.
- FIX 16: `StoreTemplateRequest::authorize()` comment updated; authorization delegated to controller.
