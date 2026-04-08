# Testing Agent Memory

## Project: Alsernet (Inoqualab)

See `patterns.md` for detailed notes.

## Key Facts
- PHPUnit 11, root TestCase at `tests/TestCase.php` extends `Illuminate\Foundation\Testing\TestCase`
- Module tests: `modules/ModuleName/tests/Feature/` and `modules/ModuleName/tests/Unit/`
- Module test namespace: `Modules\ModuleName\Tests\Feature\ClassName`
- Factory namespace in Page module: `Modules\Page\Database\Factories\PageFactory`
- `User::factory()->create()` + `actingAs()` is the standard auth pattern (no Spatie permissions needed for controller-level tests unless policy gates are explicitly called via `$this->authorize()`)
- Page module routes use `auth` middleware only — no policy gates in controller methods
- `vendor/bin/pint` via Bash is blocked; pint must be run manually by the user
- `php artisan` via Bash is blocked; tests must be run manually by the user
- Tinker fails with `register_page_template()` error from `modules/Template` — do not use tinker for test validation
- Blog admin tests need permissions created inline using `Permission::firstOrCreate()` (no shared `createPermissions` helper in root TestCase; it's defined as private in each test file)
- Blog public routes: prefix `blog.public.*`, e.g. `blog.public.index`, `blog.public.post`, `blog.public.category`, `blog.public.comments.store`
- `StoreCommentRequest` uses `'website' => ['nullable', 'string', 'max:0']` as a bot honeypot — any non-empty value causes a 422, NOT a silent discard
- Page `PublicController` resolves pages via `PageTranslation` slug lookup first; a `Page` with only a `pages.slug` column won't be found unless a matching `PageTranslation` also exists with status=published and published_at in the past
- Helpdesk module created at `modules/Helpdesk/` with `HelpdeskTicket` (title, description, status, priority, category, department, user_id, assigned_to, resolved_at, closed_at) and `HelpdeskTicketReply` (ticket_id, user_id, message, is_internal); reply uses `message` field not `content`
- SEO module routes use prefix `setting.seo.*` (NOT `settings.seo.*`) — e.g. `setting.seo.redirects.index`, `setting.seo.robots.edit`, `setting.seo.robots.update` (POST)
- SEO redirects use `can:Seo.redirects.*` middleware; RobotsTxtController and SitemapAdminController use only `auth`
- Cookie consent route `cookie.consent.store` is public (no auth); logs route requires `can:Cookie.settings.index`
- `CookieConsentLog` stores `ip_hash` as sha256 of IP, deduplicates requests by ip_hash within 5 minutes
- Notification web routes use `auth` middleware: markAllAsRead, bulk-destroy, destroy-all, index
- `getJson()` / `deleteJson()` on `auth`-protected routes returns 401 for unauthenticated requests; `get()` / `delete()` returns redirect to login
- Notifications in tests: seed directly via `DB::table('notifications')->insert([...])` with UUID id and notifiable_type=User::class
- `php artisan route:list` is broken in this environment due to missing Helpdesk model — use the `list-routes` MCP tool instead
- Analytics `AnalyticsReportScheduleController` has no permission middleware, only `auth`; schedule fields: name, frequency (daily/weekly/monthly), email, format (pdf/excel/csv)
- Login route is `auth.login` NOT `login` — use `route('auth.login')` in redirect assertions
- Two Helpdesk migrations (escalation + ticket_templates) use `$connection = 'helpdesk'` which is not configured in test env; fix in module TestCase by overriding `beforeRefreshingDatabase()` to set `config()->set('database.connections.helpdesk', config('database.connections.sqlite'))`
- `RefreshDatabase::beforeRefreshingDatabase()` hook runs before `migrate:fresh` — use this to set config needed for migrations; `afterApplicationCreated` runs AFTER migrations and is too late
- `php artisan optimize:clear` must be run when middleware classes are changed (caches old middleware resolution); after clearing, all Cookie tests pass
- `CookieConsentLog` has no factory; create records directly via `CookieConsentLog::create([...])` with fields: session_id (max 64), ip_hash (sha256), action, accepted_categories (array), user_agent, version
- Cookie module TestCase at `modules/Cookie/tests/TestCase.php` — uses `Permission::firstOrCreate()` pattern and has `beforeRefreshingDatabase()` for helpdesk connection fix
- `StreamedResponse` CSV: use `$response->streamedContent()` to get the body in tests
