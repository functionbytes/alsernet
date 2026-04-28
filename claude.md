# Claude Code - Project Configuration
# ============================================================
# Sections marked [CUSTOMIZE] need project-specific values.
# Sections marked [UNIVERSAL] work as-is in any project.
#
# ECOSYSTEM:
#   claude.md .................. This file (core config, loaded every session)
#   .claude/settings.json ..... Project permissions & hooks (committable)
#   .claude/settings.local.json User overrides (gitignored)
#   .claude/agents/ ........... 11 subagents (auto-delegated by Claude)
#   .claude/skills/ ........... 6 skills (manual /name or auto-invoked)
#   .claude/commands/ ......... 12 slash commands (legacy, still work)
#   .claude/rules/ ............ 4 path-specific rules (auto-loaded per file type)
#   .claude/hooks/ ............ Lifecycle scripts (SessionStart, PostToolUse, etc.)
#   .mcp.json ................. MCP server config (committable)
#   tests/fixtures/test-users.json Test credentials for E2E testing
#
# SUBAGENTS (.claude/agents/) - auto-delegated, run in isolated context:
#   plan(sonnet/purple)      backend(sonnet/blue)     frontend(sonnet/green)
#   testing(sonnet/yellow)   docs(haiku/green)        security(sonnet/red)
#   database(sonnet/orange)  devops(sonnet/blue)      review(sonnet/pink)
#   performance(sonnet/cyan) api(sonnet/cyan)
#
# SKILLS (.claude/skills/) - advanced workflows:
#   Module lifecycle:
#     /new-module ....... Create complete module from scratch (all boilerplate)
#     /module-entity .... Add features to module: entity, api, settings, events,
#                         dashboard (combine: "Inventory Product entity api settings")
#     /module-test ...... Generate PHPUnit tests for existing module (analyzes
#                         controllers/routes/models, auto-creates factories)
#     /module-doctor .... Diagnose module issues (12 checks, auto-fix)
#     /module-audit ..... Full audit: security + performance + quality (parallel)
#   General workflows:
#     /fix-bug .......... Structured bug fix with logs, root cause, regression test
#     /team-review ...... Agent team: 3 parallel reviewers (security/perf/quality)
#     /team-feature ..... Agent team: parallel backend+frontend+testing
#
# SLASH COMMANDS (.claude/commands/) - manual invocation with /name:
#   Role commands:  /backend /frontend /testing /docs
#                   /database /devops /api
#   Workflows:      /plan /check /review /security /performance
#
# RULES (.claude/rules/) - 16 rules, auto-loaded when editing matching files:
#   blade-views.md ......... *.blade.php (icons, CSS, modals, select2, dropdowns)
#   migrations.md .......... migrations/ (column change rules, indexes, foreign keys)
#   controllers.md ......... Controllers/ (thin controllers, Form Requests, authorize)
#   javascript.md .......... *.js (jQuery/AJAX, CSRF, DevExpress, toastr)
#   models.md .............. Models/ (fillable, casts, relationships, query())
#   services.md ............ Services/ (DI, transactions, return types)
#   tests.md ............... tests/ (PHPUnit, factories, RefreshDatabase, assertions)
#   api-controllers.md ..... Controllers/Api/ (Resources, JSON format, Sanctum, status codes)
#   seeders.md ............. seeders/ (permissions naming, firstOrCreate, guard)
#   form-requests.md ....... Http/Requests/ (authorize, rules, messages, attributes)
#   routes.md .............. routes/*.php (web/api/settings prefix+name+middleware)
#   policies.md ............ Policies/ (Gate::policy, Spatie permissions, ownership)
#   laravel-cache-commands . *.php/routes/config/composer (when to dump-autoload,
#                            optimize:clear, config:clear, route:clear, view:clear)
#   events-listeners.md .... Events/ + Listeners/ (Dispatchable, ShouldBroadcast, queue)
#   jobs.md ................ Jobs/ (ShouldQueue, tries, timeout, backoff, failed)
#   notifications.md ....... Notifications/ + Mail/ (channels, toArray, Mailable)
#
# MCP SERVERS (.mcp.json) - 4 servers:
#   laravel-boost .... Primary: DB queries, tinker, docs, routes, logs (ALL agents)
#   context7 ......... Up-to-date library documentation (ALL agents)
#   chrome-devtools .. Browser automation & testing (frontend, testing, performance)
#   redis ............ Cache inspection & management (database, devops, performance)
#
# HOOKS (.claude/settings.json):
#   SessionStart ..... Repo context via JSON (branch, modules, PHP/Laravel/Redis)
#   SessionStart ..... Explanatory output style with insights
#   PreToolUse ....... Malware analysis guard on file reads
#   PreToolUse ....... Quality gate on git commit (pint + tests + simplify)
#   PostToolUse ...... PHP syntax check on Edit/Write (.php files, blocks on error)
#   Stop ............. Final quality verification (simplify, pint, N+1, icons)
#   SubagentStop ..... Subagent output quality review (conventions, icons, jQuery)
#   PostCompact ...... Reinjects critical project rules after context compaction
#   Notification ..... macOS desktop notification when Claude needs input
#
# PLUGINS:
#   Install laravel-simplifier from laravel/claude-code:
#   /plugin marketplace add laravel/claude-code
#   /plugin install laravel-simplifier@laravel
#
# AGENT TEAMS (experimental, enabled via env):
#   Create teams for parallel work with natural language or skills:
#   /team-review [scope] .. 3 parallel reviewers (security/perf/quality)
#   /team-feature [desc] .. Parallel backend+frontend+testing implementation
# ============================================================

## [CUSTOMIZE] Project Identity

- **Project**: Alsernet (Inoqualab)
- **Framework**: Laravel 12 (PHP 8.4)
- **Primary Database**: MariaDb
- **Cache/Queue**: Redis
- **Frontend**: Bootstrap 5.3
- **UI Components**: DevExpress jQuery widgets
- **Architecture**: Modular (nwidart/laravel-modules)
- **Testing**: PHPUnit 11

---

## [UNIVERSAL] General Development Rules

### Code Quality
- Follow existing conventions in sibling files before writing new code
- Read files before modifying them
- Use descriptive names: `isRegisteredForDiscounts`, not `discount()`
- Prefer editing existing files over creating new ones
- Run formatters before committing (`pint --dirty`, `prettier`, etc.)
- **Simplify after writing**: ALWAYS re-read your code and refine it before finishing. Reduce nesting, use early returns, avoid nested ternaries, choose clarity over brevity. This is mandatory, not optional.

### Architecture
- Stick to existing directory structure
- Don't change dependencies without approval
- Use framework features before third-party packages
- Keep solutions simple - don't over-engineer

### Avoid Over-Engineering
- Don't add features beyond what was requested
- Don't add error handling for impossible scenarios
- Don't create abstractions for one-time operations
- Three similar lines > premature abstraction
- No backwards-compatibility hacks for unused code

### Version Control
- Never commit `.env`, credentials, or secrets
- Use specific file staging (not `git add -A`)
- Write clear commit messages (why, not what)
- Never force push without explicit approval

---

## [UNIVERSAL] Agent Orchestration

When handling tasks, delegate to subagents using the Task tool. Use this section to pick the right agent(s).

### Agent Selection Quick Reference

| Request involves... | Primary Agent | May also need |
|---|---|---|
| Controller, service, model, middleware, policy, job, event | **backend** | database, testing |
| View, Blade, button, form, page, CSS, JavaScript | **frontend** | backend (if logic) |
| API endpoint, Resource, rate limit, /api/ routes | **api** | database, testing |
| Migration, table, column, index, factory, seeder | **database** | backend (model updates) |
| Test, coverage, assertion, E2E | **testing** | - |
| Vulnerability, auth, XSS, injection, permissions audit | **security** | backend (fixes) |
| Code quality, anti-patterns, PR review | **review** | - |
| Slow queries, N+1, cache, memory, optimize | **performance** | database, frontend |
| Deploy, Docker, CI/CD, Supervisor, server | **devops** | - |
| Documentation, README, API docs | **docs** | - |
| CRUD, new feature, new module, 3+ files, unclear scope | **plan** first | then specialists |

### Disambiguation Rules
- **Web controller** (returns View) → **backend**. **API controller** (returns JSON) → **api**.
- **Model relationships/scopes/logic** → **backend**. **Migrations/indexes/schema** → **database**.
- **Profiling/caching** → **performance**. **Adding indexes** → **database**.
- **Vulnerability scanning** → **security**. **Code quality review** → **review**.
- **When in doubt or 3+ files involved** → start with **plan**.

### Task Flows

#### Feature (multi-file)
1. **plan**: analyze, identify files, design approach
2. **backend/frontend/api**: implement (choose by domain)
3. **database**: if schema changes needed
4. **testing**: write + run tests
5. **review**: final quality check

#### CRUD / New Entity
1. **plan**: design schema + endpoints + views
2. **database**: migration + factory + seeder
3. **backend**: model, service, controller, routes
4. **frontend**: Blade views, forms, DataGrid
5. **api** (optional): API endpoints + Resources
6. **testing**: feature tests for all endpoints

#### New Page / View
1. **frontend**: Blade view, layout, CSS, JS
2. **backend**: controller + route (if new)
3. **testing**: HTTP test for the route

#### Bug Fix
1. Read logs: use Boost `last-error` / `read-log-entries`
2. **backend/frontend**: fix the bug
3. **testing**: write regression test
4. **review**: verify fix doesn't break anything

#### Refactoring
1. **review**: analyze current code, identify issues
2. **backend/frontend**: apply refactoring
3. **testing**: verify existing tests pass
4. **performance**: profile before/after if optimization goal

#### Database Changes
1. **database**: schema design, migration + factory
2. **backend**: update models, relationships, services
3. **testing**: tests with new factories

#### Permissions / Roles
1. **backend**: Spatie Permission seeders, policies, gates
2. **database**: if new permission tables needed
3. **testing**: authorization tests
4. **security**: verify no privilege escalation

#### Security Audit
1. **security**: full audit of scope
2. **backend/frontend**: implement fixes
3. **testing**: write security-focused tests

#### Email / Notification
1. **backend**: Mailable/Notification class, event, listener
2. **frontend**: email Blade template
3. **testing**: Mail::fake() / Notification::fake() tests

### Always After Writing Code
- Run `vendor/bin/pint --dirty` (auto-format)
- Run relevant tests with `--filter`
- Re-read and simplify (laravel-simplifier principles)

---

## [CUSTOMIZE] Templates & Shortcodes (Riode + Sistema Template-Specific)

> Implementado 2026-04-28 — sistema de templates con shortcodes especificos por template activo.

### Arquitectura

- **Templates** se gestionan en tabla `templates` (modulo Template)
- Solo UN template puede estar activo a la vez (`status='active'`)
- Cada template vive en `modules/Template/Templates/{Name}/` con:
  - `Shortcodes/*.php` — clases con metodo `registerAll()`
  - `Resources/views/shortcodes/*.blade.php` — views via namespace `{slug}::`
  - `Tests/Feature/*.php` — tests Feature para cada shortcode
- `TemplateServiceProvider` auto-descubre clases via `glob()` al boot

### Shortcodes globales (siempre activos)

`button`, `alert`, `accordion`, `badge`, `card`, `columns`, `column`, `icon`, `image`,
`quote`, `youtube`, `contact-form`, `form`, `page`, `post`, `media`, `menu`, y mas.

### Shortcodes Riode-especificos

Solo activos cuando el template `slug='riode'` esta en DB con `status='active'`.

| Categoria   | Shortcodes |
|-------------|-----------|
| Content     | `cta`, `cta-column`, `countdown`, `counter`, `counter-grid`, `icon-box`, `icon-box-grid` |
| Structure   | `title`, `tabs`, `tab`, `slider`, `slide`, `banner`, `hotspot`, `hotspot-pin` |
| Utility     | `breadcrumb`, `page-header`, `social-links`, `image-box`, `video` |
| Media       | `blog-posts`, `category-card`, `category-grid`, `creative-grid`, `grid-item`, `testimonials`, `testimonial` |
| Effects     | `animate`, `floating`, `scroll-reveal`, `svg-float` |
| Marketplace | `instagram-feed`, `subcategory-card`, `category-column`, `vendor-card` |

### Activar / cambiar template

```bash
# Activar Riode
php artisan db:seed --class="Modules\\Template\\Database\\Seeders\\RiodeTemplateSeeder"
php artisan optimize:clear

# Cambiar a otro template (SQL directo)
# UPDATE templates SET status='inactive' WHERE slug='riode';
# UPDATE templates SET status='active'   WHERE slug='wolmart';
php artisan optimize:clear
```

### Verificar shortcodes registrados

```bash
php artisan shortcode:list
```

### Renderizar shortcodes en Blade

```blade
@shortcode('[countdown until="2026-12-31T23:59:59"]')
@shortcode($page->content)

{{-- Helper global --}}
{!! shortcode($content) !!}
```

### Crear template nuevo desde plantilla HTML

Usa la skill `template-builder`:

```
"Tengo Wolmart en /Users/me/Desktop/wolmart/. Genera el template Laravel."
```

La skill: analiza el HTML, extrae design tokens, genera
`modules/Template/Templates/{Name}/` completo (shortcodes, views, seeder, tests),
registra el autoload y activa el template. Tiempo estimado: 3-5 horas con agentes paralelos.

### Convenciones obligatorias para templates/shortcodes

- No usar `style=""` inline — utility classes o `data-*`
- Font Awesome 6 ONLY (nunca `d-icon-*` ni Tabler `ti ti-*`)
- jQuery + Bootstrap 5.3 nativo (no Livewire/React/Alpine)
- Color primario `#90bb13` sustituye al `#26c` original de Riode
- `loading="lazy"` + dimensiones explicitas en `<img>`
- Multi-idioma con `__('shortcode::messages.X')`
- Tests Feature: happy path + edge cases por cada shortcode

### Documentacion de referencia

- README Riode: `modules/Template/Templates/Riode/README.md`
- Skill template-builder: `.claude/skills/template-builder/`

---

## [CUSTOMIZE] Technology Stack & Context7

Always use Context7 MCP tools for up-to-date documentation on project technologies:

### Backend
- Laravel 12, Sanctum, Horizon, Reverb, Pulse, Telescope

### Database & Cache
- MariaDb (primary), Redis (cache, queues, sessions)

### Auth & Permissions
- JWT Auth (tymon/jwt-auth), Spatie Permission

### Data Management
- Maatwebsite/Excel, League CSV
- Spatie Activity Log, Backup, MediaLibrary

### Frontend
- Bootstrap 5.3, DevExpress jQuery, jQuery + AJAX (primary JS), Vite, Axios
- **No Livewire/Inertia** - use jQuery/AJAX for all dynamic interactions

### PDF & Documents
- DomPDF, FPDF/FPDI/TCPDF, HTML2Text

### Utilities
- Guzzle HTTP, Intervention/Image, HTML Purifier
- GeoIP, BotMan, Pusher, DeepL Translator, Laravel IMAP

---

## [CUSTOMIZE] UI/Design Standards

### Template System
- **Base**: Bootstrap Modernize Template
- **Icons**: Font Awesome 6 exclusively (`fas fa-*`, `far fa-*`, `fab fa-*`)
- **NEVER**: Use Tabler Icons (`ti ti-*`) - they are NOT loaded in this project

### Colors
- Primary: `#90bb13`, Success: `#13C672`, Danger: `#FA896B`, Warning: `#FEC90F`

### Typography Rules
- Section titles: capitalize only first word (`Informacion basica`, NOT `Informacion Basica`)
- Exception: proper nouns and acronyms keep original case

### Icon Usage
- Use icons only when they add meaning (actions, status, navigation, form labels)
- Don't add decorative icons to every heading
- When in doubt, leave the icon out

---

## [CUSTOMIZE] Laravel Boost Integration

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.19
- laravel/framework (LARAVEL) - v12
- laravel/horizon (HORIZON) - v5
- laravel/mcp (MCP) - v0
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- laravel/scout (SCOUT) - v10
- laravel/telescope (TELESCOPE) - v5
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- react (REACT) - v19
- laravel-echo (ECHO) - v2
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== herd rules ===

## Laravel Herd

- The application is served by Laravel Herd and will be available at: `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate URLs for the user to ensure valid URLs.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== mcp/core rules ===

## Laravel MCP

- MCP (Model Context Protocol) is very new. You must use the `search-docs` tool to get documentation for how to write and test Laravel MCP servers, tools, resources, and prompts effectively.
- MCP servers need to be registered with a route or handle in `routes/ai.php`. Typically, they will be registered using `Mcp::web()` to register an HTTP streaming MCP server.
- Servers are very testable; use the `search-docs` tool to find testing instructions.
- Do not run `mcp:start`. This command hangs waiting for JSON-RPC MCP requests.
- Some MCP clients use Node, which has its own certificate store. If a user tries to connect to their web MCP server locally using HTTPS, it could fail due to this reason. They will need to switch to HTTP during local development.

=== livewire/core rules ===

## Livewire

- Use the `search-docs` tool to find exact version-specific documentation for how to write Livewire and Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` Artisan command to create new components.
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend; they're like regular HTTP requests. Always validate form data and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle Hook Examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>

## Testing Livewire

<code-snippet name="Example Livewire Component Test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>

<code-snippet name="Testing Livewire Component Exists on Page" lang="php">
    $this->get('/posts/create')
    ->assertSeeLivewire(CreatePost::class);
</code-snippet>

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== phpunit/core rules ===

## PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v4 rules ===

## Tailwind CSS 4

- Always use Tailwind CSS v4; do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.

<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>

### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option; use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |

---

## [UNIVERSAL] Convenciones aplicadas (2026-04-27)

Convenciones estandarizadas tras auditoría completa del proyecto:

### Permisos Spatie
- Convención: `{alias}.{action}` (2 segmentos) o `{alias}.{entity}.{action}` (3 segmentos)
- Lowercase exclusivo (NO TitleCase ni snake_case con underscore)
- Ejemplos correctos: `helpdesk.conversations.view`, `cache.settings.update`, `locale.create`
- Ejemplos incorrectos: `Cache.settings.view`, `view-users`, `manager.helpdesk.conversations.update`

### Routes
- Prefix admin: `panel/{alias}` (ej: `panel/helpdesk`)
- Prefix settings: `panel/settings/{alias}` (ej: `panel/settings/cache`) — plural exclusivo
- Prefix API: `api/{alias}` con `auth:sanctum`
- Names: `{alias}.action`, `settings.{alias}.action`, `api.{alias}.action`
- Singular routes legacy: redirects 301 a plural

### Form Requests
- SIEMPRE para validation (no inline `$request->validate`)
- Path: `modules/{Module}/app/Http/Requests/`
- Subdirs por scope: `Settings/`, `Web/`, `Managers/`, `Api/`
- `authorize()` con permiso Spatie real (NO `return true`)
- `messages()` y `attributes()` en español

### Policies
- Path: `modules/{Module}/app/Policies/`
- Métodos estándar: `viewAny`, `view`, `create`, `update`, `delete`, `manage`
- Registrar en `{Module}ServiceProvider::registerPolicies()` con `Gate::policy()`
- Usar Spatie permission con convención unificada

### API Resources
- Keys camelCase
- Dates ISO8601: `->toIso8601String()`
- Relaciones con `whenLoaded()`
- Counts con `whenCounted()`
- NO exponer columnas sensibles (secrets, passwords, tokens)

### Notifications
- ALWAYS implement `ShouldQueue`
- `via()` con channels apropiados (database, broadcast, mail)
- `toArray()` keys snake_case con `type`, `title`, `message`, `entity_id`, `action_url`
- Naming: `{Entity}{Event}Notification` (ej: `ConversationAssignedNotification`)

### Trait HasMessageThread
- Path: `Modules\Helpdesk\Concerns\HasMessageThread`
- Compartido entre Conversation y Ticket
- Métodos: `assignTo`, `close`, `reopen`, `archive`, scopes Open/Closed/Assigned/Archived
- Helpers: `isOpen()`, `isClosed()`, `isAssigned()`, `isArchived()`

### ADRs documentados
- `docs/adr/0001-mailer-vs-mailrelay.md` — Aceptado Opción C (status quo + fronteras)
- Mailer = transactional emails
- Mailrelay = email marketing/multi-provider

### Tests
- PHPUnit ONLY (no Pest)
- `RefreshDatabase` trait
- Path: `modules/{Module}/tests/Feature/` y `tests/Unit/` (lowercase)
- Naming: `test_user_can_X` (snake_case con `test_` prefix)
- Mock externos: `Notification::fake()`, `Mail::fake()`, `Queue::fake()`
- Permission seeder en `setUp()`

### Bugs específicos a evitar
- Comparar enums con `===` (NO con strings — `$model->status === 'value'` falla si es enum cast)
- `$appends` con accessor que ejecuta query → N+1
- Closures como event listeners → rompen `event:cache`
- `Tests/` con T mayúscula → falla PSR-4 case-sensitive en Linux
- Namespace lowercase en composer.json (`modules\X` en vez de `Modules\X`) → falla autoload Linux
- Form Request `authorize() { return true; }` → bypass de seguridad
- `secret_key => Str::random(40)` en index method → regenera cada request
- `shell_exec` con `sudo` desde HTTP endpoint → privilege escalation

### Módulos con tratamiento estándar aplicado
- Helpdesk core (13 policies, 12 Resources, 6 Notifications)
- HelpdeskTickets (14 policies, 6 Resources, 32 Form Requests)
- Reviews (7 policies, 23 Form Requests)
- Page (8 policies)
- Auth (4 policies, 0 Gates ad-hoc)
- Cache, Optimize, Captcha, Pulse, Health, Activity, Locales, User, Theme, MailsSettings, System (permission seeders + tests básicos)
</laravel-boost-guidelines>
