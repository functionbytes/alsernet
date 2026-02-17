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
#   .claude/commands/ ......... 12 slash commands (manual /name invocation)
#   .claude/hooks/ ............ Lifecycle scripts (SessionStart, etc.)
#   .mcp.json ................. MCP server config (committable)
#   tests/fixtures/test-users.json Test credentials for E2E testing
#
# SUBAGENTS (.claude/agents/) - auto-delegated, run in isolated context:
#   plan(sonnet)     backend(sonnet)  frontend(sonnet)  testing(sonnet)
#   docs(haiku)      security(sonnet) database(sonnet)
#   devops(sonnet)   review(sonnet)   performance(sonnet)
#   api(sonnet)
#[ATENCION-MODULO-SIMPLIFICADO.md](ATENCION-MODULO-SIMPLIFICADO.md)
# SLASH COMMANDS (.claude/commands/) - manual invocation with /name:
#   Role commands:  /backend /frontend /testing /docs
#                   /database /devops /api
#   Workflows:      /plan /check /review /security /performance
#
# MCP SERVERS (.mcp.json) - 4 servers:
#   laravel-boost .... Primary: DB queries, tinker, docs, routes, logs (ALL agents)
#   context7 ......... Up-to-date library documentation (ALL agents)
#   chrome-devtools .. Browser automation & testing (frontend, testing, performance)
#   redis ............ Cache inspection & management (database, devops, performance)
#
# HOOKS (.claude/settings.json):
#   SessionStart ..... Repo context (branch, modules, migrations, Redis)
#   SessionStart ..... Explanatory output style with insights
#   PreToolUse ....... Malware analysis guard on file reads
#   PreToolUse ....... Quality gate on git commit (pint + tests + simplify)
#   PostToolUse ...... PHP syntax check on Edit/Write (.php files)
#
# PLUGINS:
#   Install laravel-simplifier from laravel/claude-code:
#   /plugin marketplace add laravel/claude-code
#   /plugin install laravel-simplifier@laravel
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

## Foundational Context
- php - 8.4.4
- laravel/framework (LARAVEL) - v12
- laravel/horizon (HORIZON) - v5
- laravel/mcp (MCP) - v0
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- laravel/telescope (TELESCOPE) - v5
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v11

**NOT USED** (installed but do NOT use): Livewire, Inertia, React, Tailwind CSS.
Use jQuery + AJAX + Bootstrap 5.3 + DevExpress jQuery instead.

## Conventions
- Follow all existing code conventions. Check sibling files for structure, approach, naming.
- Use descriptive names for variables and methods.
- Check for existing components to reuse before writing new ones.

## Verification Scripts
- Do not create verification scripts when tests cover that functionality.

## Application Structure
- Stick to existing directory structure.
- Do not change dependencies without approval.

## Frontend Bundling
- If changes don't reflect in UI, suggest `npm run build`, `npm run dev`, or `composer run dev`.

## Documentation Files
- Only create documentation files if explicitly requested.

=== boost rules ===

## Laravel Boost
- Use Boost MCP tools (search-docs, tinker, database-query, browser-logs, get-absolute-url, list-artisan-commands) when available.

## Searching Documentation
- Use `search-docs` before any other approach for Laravel ecosystem docs.
- Pass multiple broad queries: `['rate limiting', 'routing rate limiting', 'routing']`.
- Don't add package names to queries - package info is already included.

=== php rules ===

## PHP
- Always use curly braces for control structures.
- Use PHP 8 constructor property promotion.
- No empty constructors with zero parameters.
- Always use explicit return type declarations and type hints.
- Prefer PHPDoc blocks over inline comments.
- Add array shape type definitions when appropriate.
- Enum keys in TitleCase.

=== laravel/core rules ===

## Laravel Core
- Use `php artisan make:*` commands with `--no-interaction`.
- Use Eloquent relationships with return type hints.
- Prefer `Model::query()` over `DB::`.
- Prevent N+1 with eager loading.
- Use Form Request classes for validation.
- Use queued jobs (`ShouldQueue`) for heavy operations.
- Use gates/policies for authorization.
- Use named routes with `route()` helper.
- Never use `env()` outside config files.

### Testing
- Use factories with custom states.
- Most tests should be feature tests.
- Run `vendor/bin/pint --dirty` before finalizing.

=== laravel/v12 rules ===

## Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` registers middleware, exceptions, routing.
- `bootstrap/providers.php` contains service providers.
- No `app/Console/Kernel.php` - commands auto-register.
- Include ALL column attributes when modifying migration columns.
- Casts: prefer `casts()` method for new code; `$casts` property is also used in existing code.

=== jquery rules ===

## jQuery & AJAX (Primary JS - NO Livewire/Inertia)
- Use jQuery + AJAX for all dynamic interactions.
- Always include CSRF token: `headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }`.
- Use `$.ajax()` for complex requests, `$.get()`/`$.post()` for simple ones.
- Handle 422 validation errors: parse `xhr.responseJSON.errors` and show per-field messages.
- Use DevExpress jQuery widgets for data grids, charts, and complex UI components.
- Use `toastr` for success/error notifications.
- Prefer event delegation: `$(document).on('click', '.selector', handler)` for dynamic content.

=== pint rules ===

## Laravel Pint
- Run `vendor/bin/pint --dirty` before finalizing changes.
- Never use `--test`, just run `vendor/bin/pint` to fix.

=== phpunit rules ===

## PHPUnit
- All tests as PHPUnit classes. Convert any Pest tests.
- Test happy paths, failure paths, and edge cases.
- Run minimal tests with `--filter` during development.
- Never remove test files without approval.
</laravel-boost-guidelines>
