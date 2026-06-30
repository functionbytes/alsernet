# Kimi CLI - Project Configuration
# ============================================================
# Adapted from .claude/ configuration for Kimi Code CLI usage.
# Kimi CLI supports MCP servers natively via `kimi mcp` commands.
# This file provides the project context that would normally come from MCP tools.
#
# PROJECT: Alsernet (Inoqualab)
# Framework: Laravel 12 (PHP 8.4)
# Primary Database: MariaDb
# Cache/Queue: Redis (currently NOT running)
# Frontend: Bootstrap 5.3 + DevExpress jQuery
# Architecture: Modular (nwidart/laravel-modules)
# Testing: PHPUnit 11
# Node: v22.22.2
# ============================================================

## Auto-Loading Rules (CRITICAL)

Before creating or editing **any** file, you MUST automatically identify which rule applies and follow it. Do not wait for the user to ask. The rules live in `.kimi/rules/` and are mapped below by file type.

| If you are editing... | You MUST follow |
|---|---|
| `modules/*/app/Http/Controllers/**/*.php` | `.kimi/rules/controllers.md` |
| `modules/*/app/Http/Controllers/Api/**/*.php` | `.kimi/rules/api-controllers.md` |
| `modules/*/resources/views/**/*.blade.php` | `.kimi/rules/blade-views.md` |
| `modules/*/app/Http/Requests/**/*.php` | `.kimi/rules/form-requests.md` |
| `modules/*/database/migrations/**/*.php` | `.kimi/rules/migrations.md` |
| `modules/*/app/Models/**/*.php` | `.kimi/rules/models.md` |
| `modules/*/app/Services/**/*.php` | `.kimi/rules/services.md` |
| `modules/*/app/Policies/**/*.php` | `.kimi/rules/policies.md` |
| `modules/*/routes/**/*.php` | `.kimi/rules/routes.md` |
| `modules/*/database/seeders/**/*.php` | `.kimi/rules/seeders.md` |
| `modules/*/tests/**/*.php` | `.kimi/rules/tests.md` |
| `**/*.js` | `.kimi/rules/javascript.md` |
| Events or Listeners | `.kimi/rules/events-listeners.md` |
| Queue Jobs | `.kimi/rules/jobs.md` |
| Notifications or Mailables | `.kimi/rules/notifications.md` |
| Any PHP/config/routes/composer change | `.kimi/rules/laravel-cache-commands.md` |

When delegating via the `Agent` tool, pass the relevant `.kimi/agents/*.md` file as context so the subagent knows its role.

## Project Identity

- **Project**: Alsernet (Inoqualab)
- **Framework**: Laravel 12.55.1 (PHP 8.4.19)
- **Primary Database**: MariaDb
- **Cache/Queue**: Redis
- **Frontend**: Bootstrap 5.3, DevExpress jQuery widgets, jQuery + AJAX
- **Architecture**: Modular (nwidart/laravel-modules v12)
- **Testing**: PHPUnit 11
- **Server**: Laravel Herd (https://system.test)
- **Package Manager**: Composer + npm

## Ecosystem Packages

- laravel/framework v12
- laravel/horizon v5
- laravel/pulse v1
- laravel/reverb v1
- laravel/sanctum v4
- laravel/telescope v5
- livewire/livewire v4
- laravel/pint v1
- spatie/laravel-permission
- maatwebsite/excel
- nwidart/laravel-modules v12

## Technology Stack Details

### Backend
- Laravel 12, Sanctum, Horizon, Reverb, Pulse, Telescope
- JWT Auth (tymon/jwt-auth), Spatie Permission
- Maatwebsite/Excel, League CSV
- Spatie Activity Log, Backup, MediaLibrary

### Frontend
- Bootstrap 5.3, DevExpress jQuery, jQuery + AJAX (primary JS), Vite, Axios
- **No Livewire/Inertia** - use jQuery/AJAX for all dynamic interactions
- Font Awesome 6 icons exclusively

### PDF & Documents
- DomPDF, FPDF/FPDI/TCPDF, HTML2Text

### Utilities
- Guzzle HTTP, Intervention/Image, HTML Purifier
- GeoIP, BotMan, Pusher, DeepL Translator, Laravel IMAP

## UI/Design Standards

### Template System
- **Base**: Bootstrap Modernize Template
- **Icons**: Font Awesome 6 exclusively (`fas fa-*`, `far fa-*`, `fab fa-*`)
- **NEVER**: Use Tabler Icons (`ti ti-*`) - they are NOT loaded in this project

### Colors
- Primary: `#90bb13`
- Success: `#13C672`
- Danger: `#FA896B`
- Warning: `#FEC90F`

### Typography Rules
- Section titles: capitalize only first word (`Informacion basica`, NOT `Informacion Basica`)
- Exception: proper nouns and acronyms keep original case

### Icon Usage
- Use icons only when they add meaning (actions, status, navigation, form labels)
- Don't add decorative icons to every heading
- When in doubt, leave the icon out

## Module Structure (CRITICAL)

Most code lives in `modules/ModuleName/`, NOT in root `app/`.

```
modules/
  ModuleName/
    app/
      Http/Controllers/         # Web controllers
      Http/Controllers/Api/     # API controllers
      Http/Requests/            # Form Requests
      Http/Middleware/          # Middleware
      Models/                   # Eloquent models
      Services/                 # Business logic
      Policies/                 # Authorization policies
      Providers/                # Service providers
      Events/                   # Events
      Listeners/                # Event listeners
      Jobs/                     # Queue jobs
      Console/Commands/         # Artisan commands
      Resources/                # API Resources
      Mail/                     # Mailables
      Notifications/            # Notifications
    resources/
      views/                    # Blade templates
      js/                       # JavaScript files
      css/                      # Stylesheets
      lang/                     # Translations
    config/                     # Module config
    routes/
      web.php                   # Web routes
      api.php                   # API routes
      settings.php              # Admin/settings routes
    database/
      migrations/               # Database migrations
      factories/                # Model factories
      seeders/                  # Database seeders
    tests/                      # Module tests
    module.json                 # Module manifest
    composer.json               # Module composer
```

## General Development Rules

### Code Quality
- Follow existing conventions in sibling files before writing new code
- Read files before modifying them
- Use descriptive names: `isRegisteredForDiscounts`, not `discount()`
- Prefer editing existing files over creating new ones
- Run formatters before committing (`vendor/bin/pint --dirty`)
- **Simplify after writing**: ALWAYS re-read code and refine before finishing
- Reduce nesting, use early returns, avoid nested ternaries

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

### Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

### Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

### Documentation Files
- You must only create documentation files if explicitly requested by the user.

### Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Agent Selection Quick Reference

When handling tasks, adopt the appropriate role:

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

## Task Flows

### Feature (multi-file)
1. **plan**: analyze, identify files, design approach
2. **backend/frontend/api**: implement (choose by domain)
3. **database**: if schema changes needed
4. **testing**: write + run tests
5. **review**: final quality check

### CRUD / New Entity
1. **plan**: design schema + endpoints + views
2. **database**: migration + factory + seeder
3. **backend**: model, service, controller, routes
4. **frontend**: Blade views, forms, DataGrid
5. **api** (optional): API endpoints + Resources
6. **testing**: feature tests for all endpoints

### New Page / View
1. **frontend**: Blade view, layout, CSS, JS
2. **backend**: controller + route (if new)
3. **testing**: HTTP test for the route

### Bug Fix
1. Read logs: use Boost `last-error` / `read-log-entries`
2. **backend/frontend**: fix the bug
3. **testing**: write regression test
4. **review**: verify fix doesn't break anything

### Refactoring
1. **review**: analyze current code, identify issues
2. **backend/frontend**: apply refactoring
3. **testing**: verify existing tests pass
4. **performance**: profile before/after if optimization goal

### Database Changes
1. **database**: schema design, migration + factory
2. **backend**: update models, relationships, services
3. **testing**: tests with new factories

### Permissions / Roles
1. **backend**: Spatie Permission seeders, policies, gates
2. **database**: if new permission tables needed
3. **testing**: authorization tests
4. **security**: verify no privilege escalation

### Security Audit
1. **security**: full audit of scope
2. **backend/frontend**: implement fixes
3. **testing**: write security-focused tests

### Email / Notification
1. **backend**: Mailable/Notification class, event, listener
2. **frontend**: email Blade template
3. **testing**: Mail::fake() / Notification::fake() tests

### Always After Writing Code
- Run `vendor/bin/pint --dirty` (auto-format)
- Run relevant tests with `--filter`
- Re-read and simplify (early returns, no nested ternaries)

## MCP Server Configuration

Kimi CLI supports MCP servers natively:
```bash
.kimi mcp add --transport stdio <name> -- <command> [args...]
.kimi mcp list
.kimi mcp test <name>
```

### Installed MCP Servers

| Server | Status | Tools |
|---|---|---|
| `laravel-boost` | ✅ Active | 15 tools: application-info, database-query, database-schema, list-routes, tinker, search-docs, get-config, last-error, read-log-entries, browser-logs, list-artisan-commands, get-absolute-url, and more |
| `chrome-devtools` | ✅ Active | 29 tools: navigate, click, fill, screenshot, lighthouse, performance traces, network logs, etc. |
| `context7` | ✅ Active | 2 tools: resolve-library-id, query-docs (up-to-date library documentation) |
| `redis` | ✅ Active | 4 tools: set, get, delete, list (cache inspection & management) |
| `memory` | ✅ Active | 9 tools: knowledge graph para memoria persistente entre sesiones |
| `filesystem` | ✅ Active | 14 tools: lectura/escritura de archivos restringido al proyecto |

### Available Skills

This project has skills in `.kimi/skills/` (with full content in `.claude/skills/`):

| Skill | Description | Full Docs |
|---|---|---|
| `/new-module` | Create complete module from scratch | `.kimi/skills/new-module/SKILL.md` → `.claude/skills/new-module/` |
| `/module-entity` | Add features: entity, api, settings, events, dashboard | `.kimi/skills/module-entity/SKILL.md` → `.claude/skills/module-entity/` |
| `/module-test` | Generate PHPUnit tests for existing module | `.kimi/skills/module-test/SKILL.md` → `.claude/skills/module-test/` |
| `/module-doctor` | Diagnose and fix module issues (12 checks) | `.kimi/skills/module-doctor/SKILL.md` → `.claude/skills/module-doctor/` |
| `/module-audit` | Full audit: security + performance + quality | `.kimi/skills/module-audit/SKILL.md` → `.claude/skills/module-audit/` |
| `/fix-bug` | Structured bug fix with logs, root cause, regression test | `.kimi/skills/fix-bug/SKILL.md` |
| `/team-review` | Agent team: 3 parallel reviewers | `.kimi/skills/team-review/SKILL.md` → `.claude/skills/team-review/` |
| `/team-feature` | Agent team: parallel backend+frontend+testing | `.kimi/skills/team-feature/SKILL.md` → `.claude/skills/team-feature/` |
| `/ui-patterns` | Project UI/design system patterns | `.kimi/skills/ui-patterns/SKILL.md` → `.kimi/ui-patterns/` |

## Laravel Boost Guidelines

### Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all.

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

### Laravel Boost Tools

- Laravel Boost is an MCP server with powerful tools designed specifically for this application. Use them whenever applicable.
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check available parameters.
- Use the `get-absolute-url` tool to ensure correct scheme, domain/IP, and port when sharing project URLs.
- Use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `browser-logs` tool to read browser logs, errors, and exceptions. Only recent logs are useful.

### Searching Documentation (Critically Important)

- Use the `search-docs` tool before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool returns version-specific documentation.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Use multiple, broad, simple, topic-based queries. Example: `['rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared.

#### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication
2. Multiple Words (AND Logic) - query=rate limit
3. Quoted Phrases (Exact Position) - query="infinite scroll"
4. Mixed Queries - query=middleware "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"]

## PHP Rules

- Always use curly braces for control structures, even if it has one line.
- Use PHP 8 constructor property promotion in `__construct()`. Do not allow empty constructors unless private.
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.
- Prefer PHPDoc blocks over inline comments. Never use comments within code unless something very complex is going on.
- Add useful array shape type definitions for arrays when appropriate.
- Typically, keys in an Enum should be TitleCase (e.g., `FavoritePerson`, `Monthly`).

## Laravel Herd Rules

- The application is served by Laravel Herd at `https://system.test`.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.

## Laravel Core Rules

- Use `php artisan make:` commands to create new files. Pass `--no-interaction` to all Artisan commands.
- If creating a generic PHP class, use `php artisan make:class`.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationships over raw queries or manual joins.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.
- When modifying a column, the migration must include all previously defined attributes or they will be dropped.
- Laravel 12 allows limiting eagerly loaded records natively: `$query->latest()->limit(10);`.

### Model Creation
- When creating new models, create useful factories and seeders too.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers.
- Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use `env()` directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Laravel 12 Structure
- Middleware are no longer registered in `app/Http/Kernel.php`. They are configured in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- `app\Console\Kernel.php` no longer exists; use `bootstrap/app.php` or `routes/console.php`.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Testing Rules

### Test Enforcement
- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

### PHPUnit
- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval.

#### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName`.

### Faker
- Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.

### Creating Tests
- When creating models for tests, use the factories for the models. Check if the factory has custom states before manually setting up the model.
- When creating tests, use `php artisan make:test [options] {name}` for feature tests, and pass `--unit` for unit tests. Most tests should be feature tests.

## Livewire Rules

- Use `php artisan make:livewire [Posts\CreatePost]` to create new components.
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend; validate form data and run authorization checks in Livewire actions.
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
- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects.

## Tailwind CSS Rules

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Always use Tailwind CSS v4; do not use deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
- Import Tailwind using `@import "tailwindcss"`, not `@tailwind` directives.
- When listing items, use gap utilities for spacing; don't use margins.
- If existing pages support dark mode, new pages must support it too, typically using `dark:`.

### Replaced Utilities (Tailwind v4)

| Deprecated | Replacement |
|---|---|
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

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

## Laravel MCP Rules

- MCP (Model Context Protocol) is very new. Use the `search-docs` tool to get documentation for how to write and test Laravel MCP servers, tools, resources, and prompts effectively.
- MCP servers need to be registered with a route or handle in `routes/ai.php`. Typically registered using `Mcp::web()` for HTTP streaming MCP server.
- Do not run `mcp:start`. This command hangs waiting for JSON-RPC MCP requests.
- Some MCP clients use Node, which has its own certificate store. If a user tries to connect to their web MCP server locally using HTTPS, it could fail. Switch to HTTP during local development.

## Laravel Cache & Autoload Commands

### composer dump-autoload
**When**: After creating/moving/renaming PHP classes
**Execute after**: New module, new namespace, "Class not found" errors

### php artisan optimize:clear
**When**: "Nuclear option" - clear ALL caches
**Includes**: cache, config, route, view, event, compiled

### After creating new module
```bash
composer dump-autoload
php artisan optimize:clear
php artisan module:list
php artisan route:list --name={alias}
```

### After creating migrations
```bash
php artisan module:migrate {ModuleName}
php artisan module:migrate:status {ModuleName}
```

### NEVER use in development
- `php artisan migrate:fresh` (DESTROYS all data)
- `php artisan config:cache` (caches config, difficult to debug)
- `php artisan route:cache` (caches routes, difficult to debug)
- `php artisan view:cache` (caches views, difficult to debug)

---

## Quality Gates (from Claude Code hooks)

These checks must be applied manually in Kimi CLI (no native hooks).

### Before Committing (PreToolUse equivalent)
Before any `git commit`:
1. Run `vendor/bin/pint --dirty` to format changed files
2. Run relevant tests with `php artisan test --filter`
3. Re-read and simplify your code (laravel-simplifier principles)

### After Editing PHP Files (PostToolUse equivalent)
After any `WriteFile` or `StrReplaceFile` on `.php` files:
- Run `php -l <file>` to verify syntax
- If syntax error: fix immediately before proceeding

### Before Finalizing (Stop hook equivalent)
Before telling the user a task is complete:
1. Did you simplify your code (reduce nesting, early returns, clear names)?
2. Did you run `vendor/bin/pint --dirty` on changed PHP files?
3. Are there any N+1 queries or missing eager loading?
4. Did you use Font Awesome 6 icons (not Tabler `ti-*`)?
5. Did you follow jQuery+AJAX patterns (not Livewire/Inertia)?
6. If subagent was used: did it follow project conventions? Did tests pass?

### After Context Compaction (PostCompact equivalent)
If conversation context was compacted, re-inject critical rules:
- Use `modules/` structure (not `app/`)
- `Model::query()` over `DB::`
- Form Requests for validation
- Font Awesome 6 only (not Tabler)
- jQuery+AJAX (not Livewire)
- Primary color `#90bb13`
- Section titles: capitalize first word only
- Run `vendor/bin/pint --dirty` after PHP changes
- Simplify code: early returns, no nested ternaries

---

## Rules by File Type

When working with specific file types, consult the corresponding rule in `.kimi/rules/`:

| File Pattern | Rule File | Key Points |
|---|---|---|
| `modules/*/app/Http/Controllers/**/*.php` | [rules/controllers.md](rules/controllers.md) | Thin controllers, DI, Form Requests, authorize |
| `modules/*/app/Http/Controllers/Api/**/*.php` | [rules/api-controllers.md](rules/api-controllers.md) | Sanctum, Resources, JSON format, status codes |
| `modules/*/resources/views/**/*.blade.php` | [rules/blade-views.md](rules/blade-views.md) | FA6 icons, jQuery, Bootstrap, modals, select2 |
| `modules/*/app/Http/Requests/**/*.php` | [rules/form-requests.md](rules/form-requests.md) | Spanish messages, array syntax, Spatie auth |
| `modules/*/database/migrations/**/*.php` | [rules/migrations.md](rules/migrations.md) | Indexes, FKs, `->change()` rules, `down()` |
| `modules/*/app/Models/**/*.php` | [rules/models.md](rules/models.md) | `$fillable`, `casts()`, relationships, scopes |
| `modules/*/app/Services/**/*.php` | [rules/services.md](rules/services.md) | Business logic, transactions, typed returns |
| `modules/*/app/Policies/**/*.php` | [rules/policies.md](rules/policies.md) | Spatie perms, ownership, `Gate::policy` |
| `modules/*/routes/**/*.php` | [rules/routes.md](rules/routes.md) | Prefix `panel/{alias}`, naming, middleware |
| `modules/*/database/seeders/**/*.php` | [rules/seeders.md](rules/seeders.md) | `{alias}.action` perms, idempotent |
| `modules/*/tests/**/*.php` | [rules/tests.md](rules/tests.md) | PHPUnit, RefreshDatabase, factories, fake |
| `**/*.js` | [rules/javascript.md](rules/javascript.md) | jQuery+AJAX, CSRF, DevExpress, toastr |
| Events + Listeners | [rules/events-listeners.md](rules/events-listeners.md) | Dispatchable, ShouldBroadcast, ShouldQueue |
| Queue Jobs | [rules/jobs.md](rules/jobs.md) | ShouldQueue, tries, timeout, backoff, failed |
| Notifications + Mailables | [rules/notifications.md](rules/notifications.md) | Channels, toArray, Mailable, queue |
| Any PHP/config/routes/composer changes | [rules/laravel-cache-commands.md](rules/laravel-cache-commands.md) | When to dump-autoload, optimize:clear |

---

## Agent Definitions

When delegating tasks via the `Agent` tool, use the corresponding reference in `.kimi/agents/`:

| Agent | File | For |
|---|---|---|
| `plan` | [agents/plan.md](agents/plan.md) | Multi-file features, architecture decisions |
| `backend` | [agents/backend.md](agents/backend.md) | Controllers, services, models, middleware |
| `frontend` | [agents/frontend.md](agents/frontend.md) | Blade, CSS, JS, forms, DataGrid |
| `api` | [agents/api.md](agents/api.md) | API endpoints, Resources, rate limits |
| `database` | [agents/database.md](agents/database.md) | Migrations, factories, schema design |
| `testing` | [agents/testing.md](agents/testing.md) | PHPUnit tests, coverage, assertions |
| `security` | [agents/security.md](agents/security.md) | Auth, XSS, permissions audit |
| `performance` | [agents/performance.md](agents/performance.md) | N+1, caching, slow queries |
| `devops` | [agents/devops.md](agents/devops.md) | Docker, deploy, CI/CD |
| `review` | [agents/review.md](agents/review.md) | Code quality, PR review |
| `docs` | [agents/docs.md](agents/docs.md) | Documentation, README |

---

## Agent Memory

Project knowledge accumulated by agents lives in `.kimi/agent-memory/`:
- `api/` — API endpoints analytics
- `backend/` — Template module patterns
- `database/` — Schema knowledge
- `devops/` — Autoload patterns
- `frontend/` — UI conventions
- `performance/` — Bottlenecks and fixes
- `plan/` — Architecture decisions
- `review/` — Multi-pass audit findings
- `security/` — Secure patterns
- `testing/` — Test conventions

Consult these files when working on related domains.

---

## Permission & Safety Patterns

### Allowed without confirmation
- `Read`, `Edit`, `Write`, `Glob`, `Grep`
- `php artisan *`, `vendor/bin/pint *`, `vendor/bin/phpunit *`
- `npm run *`, `npx *`, `composer dump-autoload`
- `git status`, `git diff`, `git log`, `git branch`
- MCP tools: `laravel-boost:*`, `context7:*`, `redis:*`, `chrome-devtools:*`, `memory:*`, `filesystem:*`

### Ask before executing
- `composer require *`, `composer remove *`, `composer update *`
- `npm install *`
- `git push *`, `git checkout *`, `git merge *`, `git rebase *`
- `php artisan migrate*`, `php artisan db:seed*`, `php artisan queue:*`
- Redis write operations (set, update, delete, create_key, drop_key)

### Denied (never execute)
- `rm -rf *`
- `git push --force *`
- `git reset --hard *`
- `git clean -f *`
- `php artisan migrate:fresh*` (destroys data)
- Reading `.env` or `.env.*` files
