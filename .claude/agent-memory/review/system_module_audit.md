---
name: system_module_audit
description: Full audit of the System module (2026-04-02): authorization gaps, RCE, path traversal, env injection, N+1, code smells, route ordering bugs, missing tests
type: project
---

# System Module Full Audit (2026-04-02)

## Critical

1. **RCE via arbitrary Artisan command** — `SupervisorController::runCommand()` (line 348): `Artisan::call($command)` with no allowlist. Any user with the `settings` middleware role can execute any registered Artisan command including `migrate:fresh`, `db:seed`, `storage:link`, custom dangerous commands, etc. **Fix:** validate against `Artisan::all()` keys or a curated allowlist.

2. **Arbitrary .env file injection** — `SystemSettingsController::updateEnvVariable()` (line 373) and `LocalizationSettingsController::updateEnvVariable()` (line 74): values are inserted into `.env` using `preg_replace("/^{$key}=.*/m", "{$key}={$value}")` with no quoting or sanitization. A value containing newlines could inject new env vars (e.g. `APP_KEY=` or `DB_PASSWORD=`). **Fix:** quote values, strip newlines, or use `write_env()` helper which handles quoting.

3. **Zero authorization on all System module controllers**: none of the controllers call `$this->authorize()` or `Gate::authorize()`. The module relies entirely on the `settings` middleware (which is a broad role check: super-settings|administrative|manager|callcenter|license|accounting). Roles like `callcenter` or `accounting` can reach every system management endpoint including Supervisor process control, env file writes, and cache management.

4. **processName not sanitized before passing to supervisorctl** — `SupervisorService::startProcess()`, `stopProcess()`, `restartProcess()`, `getProcessLogs()` (lines 134-201): `$processName` comes from the URL parameter with no validation. While `Process` uses array form (safe from shell injection), a crafted process name with special supervisorctl syntax could trigger unexpected behavior. **Fix:** validate processName against known process names retrieved from `getStatus()`.

5. **Route ordering bug: `{processName}` wildcard before static routes** — `routes/web.php` lines 72-92: `GET /{processName}` is declared at line 72 before `GET /status/ajax`, `GET /api/*`, `GET /backups/*`, `GET /config-files/*`. In Laravel, static routes declared AFTER a wildcard in the same group are still matched correctly due to route specificity, BUT POST routes like `POST /reload` and `POST /restart-service` are declared after `POST /{processName}/start` — a request to `/supervisor/reload` would match `{processName}=reload` instead of the intended handler. **Critical**: `reload` and `restart-service` would be caught by the `{processName}/start` pattern if there is a POST to a matching path. Needs reordering: all static routes before wildcard routes.

## High

6. **`setting()` helper issues N+1 per call** — `SettingsHelper.php:48`: `Setting::where('key', '=', $key)->first()` queries DB on every call with no cache. The maintenance/index.blade.php calls `setting()` 6 times per render (lines 79, 91, 104, 109, 113, 125). **Fix:** replace with `Setting::get($key, $default)` which uses `cache()->remember("setting_{$key}", ...)`.

7. **`formatBytes()` duplicated in 3 places** — `SystemSettingsController` (line 358), `ServerAccessController` (line 192), `SystemInfoService` (line 217): identical implementation. Should be in a shared `FormatHelper` or a trait.

8. **`updateEnvVariable()` duplicated** — `SystemSettingsController` (line 373) and `LocalizationSettingsController` (line 74): identical private methods. The `write_env()` helper in `EnvironmentHelper.php` already handles this correctly with proper quoting. Both controllers should use `write_env()`.

9. **`ServerAccessController::stats()` calls `shell_exec('uptime')` with no OS check** — line 184: only `PHP_OS_FAMILY === 'Windows'` is checked before calling `shell_exec`. On some containerized Linux environments, `/proc/uptime` is the correct source. Using `shell_exec` can be blocked by `disable_functions`. The `@exec` in `SystemCacheController::findComposerPath()` (line 85) similarly uses error suppression.

10. **`getAccessLogsFromFiles(null)` called twice per request** — `ServerAccessController::index()` lines 23-24: when `$source === 'file'`, calls `getAccessLogsFromFiles($limit)` AND `getAccessLogsFromFiles(null)` (second call reads ALL log files to count them). For large log files this reads and parses the entire log twice. **Fix:** return total count from first call.

11. **`getDirectorySize()` uses a `static $depth` counter** — `SystemInfoService.php:169`: `static $depth = 0` inside an instance method is shared across all calls on the same request. Calling `getAllSystemInfo()` twice (once for view, once for api endpoint) would have depth carry-over. Should be a normal instance variable or a separate parameter.

12. **`SystemInfoService::getServerIP()` trusts `HTTP_CF_CONNECTING_IP` unconditionally** — line 138: reads `$_SERVER['HTTP_CF_CONNECTING_IP']` without verifying the request actually came through Cloudflare. Anyone can spoof this header directly. Should only trust it when the request IP is in Cloudflare's IP ranges.

13. **`TranslationController::update()` writes PHP files with `var_export()`** — line 145: `File::put($path, "<?php\n\nreturn ".var_export($content, true).";\n")`. The `$translations` array comes from `$request->input('translations', [])` with no depth limit or key sanitization. A very large nested array could cause memory issues. The file is written outside a transaction — if the process dies mid-write the translation file is corrupted. **Fix:** write to a temp file then `rename()` atomically.

14. **`TranslationController::edit()` has missing `null` check** — line 106: `File::getRequire(resource_path("lang/es/{$file}.php"))` throws a fatal if `es/{$file}.php` doesn't exist, even if the locale being edited is not `es`. Should check file existence first.

## Medium

15. **No return type declarations on most controller methods** — `SystemSettingsController::index()`, `updateQueue()`, `updateWebsockets()`, `testQueue()`, `restartQueue()` all lack `: mixed`/`: JsonResponse`/`: Response` declarations. Same for `SupervisorController` (all ~20 methods), `MantenanceSettingsController` (both methods), `SystemCacheController` (all methods), `ServerAccessController` (all methods), `CategoriesController` (all methods), `LangsController` (all methods), `SystemInfoController::index()`.

16. **`CategoriesController` and `LangsController` have no input validation** — `store()` and `update()` read directly from `$request->title`, `$request->available` etc. with no `validate()` call. Mass assignment possible via the `Categorie` model if `$guarded` is empty.

17. **`CategoriesController::index()` has redundant null coalescing** — line 14: `$searchKey = null ?? $request->search` — `null ?? x` always evaluates to `null`. The intent was `$request->search ?? null`. This means `$searchKey` is always `null` regardless of the request parameter, making search non-functional. Same bug in `LangsController::index()` line 15-16.

18. **`MantenanceSettingsController::update()` has no input validation** — `maintenance_title` and `maintenance_message` are taken directly from request input and stored with `updateSettings()` which calls `Setting::upsert()`. These fields will be rendered in the maintenance view. If a future view renders them with `{!! !!}`, stored XSS is possible.

19. **`SupervisorController::getLogs()` accepts arbitrary `lines` count** — line 224: `$lines = $request->input('lines', 50)` with no upper bound. A user could request 1,000,000 lines, causing `supervisorctl tail` to read huge log output.

20. **`SystemCacheController::composerDumpAutoload()` duplicated in `executeAll()`** — lines 265-317 duplicated into lines 361-409 of the same file (≈100 lines). Extract to a private helper method `runComposerDumpAutoload(): array`.

21. **`SupervisorController::getStatusAjax()` returns HTTP 200 on error** — lines 263, 282: comment says "Return 200 so JavaScript success block handles it". This is the anti-pattern documented in the infrastructure audit for `DatabaseSettingsController`. The correct approach is HTTP 422/500 with `success: false`.

22. **`SystemInfoService::getComposerPackages()` reads `composer.lock`** — line 89: `composer.lock` can be several MB. This is called on every page load of the system info endpoint (via `getAllSystemInfo()`). Result should be cached with `Cache::remember()` for several hours since package versions only change on deploy.

23. **`SettingsController::getLogo()` and `getFavicon()` have null-dereference risk** — line 88: `$setting->getMedia('logo')` called on the result of `Setting::key($uid)` which could return null if the key doesn't exist. Results in a fatal error. Same in `storeLogo()` line 107.

24. **`SettingsController::storeLogo()` accepts `$request->setting` without validation** — line 107: `Setting::key($request->setting)` loads any setting by key. No validation that `$request->setting` is one of the allowed values (`page_logo`, `page_favicon`). A user could attach a media file to any arbitrary setting record.

25. **`SystemSettingsController::queueStats()` exposes `failed_jobs` exception content** — line 276: `'exception'` column from `failed_jobs` is returned directly in the JSON response. These exception messages may contain stack traces with file paths, database credentials in the connection string, or other internal information. Should be truncated or stripped.

## Low / Suggestions

26. **`CategoriesController` and `LangsController` referenced views point to `theme.views.backups.*`** — these views live outside the module namespace, creating coupling to the Theme module structure. Should use module-namespaced views or the System module's own views directory.

27. **`SupervisorService` is a pure static class** — all methods are `static`. This makes it untestable (cannot mock), cannot be swapped via DI, and uses `self::` everywhere instead of `static::` (no late static binding). Should be converted to an instance service injectable via constructor.

28. **`TranslationController` extends `Illuminate\Routing\Controller`** directly instead of `App\Http\Controllers\Controller` — inconsistent with the rest of the module.

29. **`SystemServiceProvider::registerMenus()` uses hardcoded route names** — lines 59-67: all `route()` references are plain strings. If a route name changes, the menu silently breaks with no compile-time error.

30. **No tests exist**: the System module has no test files at all. Zero coverage for: maintenance mode toggle, env variable writing, supervisor process control, cache operations, translation file editing.

31. **`vendor/` directory checked into the System module** — the module contains its own `vendor/` with `symfony/process`, GeoIP packages etc. This bloats the repo and can conflict with the root `vendor/` if versions diverge.

## How to apply
- When reviewing or implementing fixes for the System module, prioritize items 1-5 (Critical) first.
- The `runCommand()` RCE and processName injection are the most urgent.
- The `setting()` vs `Setting::get()` N+1 is a quick win that applies across the whole application wherever `setting()` is called in views.
