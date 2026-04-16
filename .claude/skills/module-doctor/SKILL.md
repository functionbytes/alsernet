---
name: module-doctor
description: "Diagnose and fix configuration issues in a module. Checks registration in providers.php, modules_statuses.json, root composer.json, routes, NavService, permissions, migrations, and ServiceProvider boot(). Use when a module isn't working or to verify setup."
disable-model-invocation: false
argument-hint: "[ModuleName]"
---

# Module Doctor: $ARGUMENTS

Diagnose module **$ARGUMENTS** and report all issues found.

## Check 1: Module Exists

- Verify `modules/{ModuleName}/` directory exists
- Read `modules/{ModuleName}/module.json` - verify structure
- Check `name`, `alias`, `providers` fields are correct

## Check 2: modules_statuses.json

- Read `modules_statuses.json`
- Verify `"{ModuleName}": true` exists
- If missing: **CRITICAL** - module is not activated

## Check 3: bootstrap/providers.php

- Read `bootstrap/providers.php`
- Search for `Modules\\{ModuleName}\\Providers\\{ModuleName}ServiceProvider`
- Verify the provider maps to `'{ModuleName}'` (not `true` unless critical)
- If missing: **CRITICAL** - provider not registered

## Check 4: Root composer.json autoload

- Read root `composer.json`
- Search for `Modules\\{ModuleName}\\` in `autoload.psr-4`
- Verify three entries exist:
  - `Modules\\{ModuleName}\\` → `modules/{ModuleName}/app/`
  - `Modules\\{ModuleName}\\Database\\Factories\\` → `modules/{ModuleName}/database/factories/`
  - `Modules\\{ModuleName}\\Database\\Seeders\\` → `modules/{ModuleName}/database/seeders/`
- If missing: **CRITICAL** - autoloading broken

## Check 5: ServiceProvider

- Read `modules/{ModuleName}/app/Providers/{ModuleName}ServiceProvider.php`
- Verify:
  - [ ] `Module::find('{ModuleName}')?->isDisabled()` check in boot()
  - [ ] `registerConfig()` method exists and merges config
  - [ ] `registerViews()` method loads views with namespace
  - [ ] `loadMigrationsFrom()` called
  - [ ] `registerRoutes()` loads route files
  - [ ] `registerMenus()` uses NavService
- If boot() missing disable check: **WARNING** - module loads even when disabled

## Check 6: Routes

- Read `modules/{ModuleName}/routes/web.php`
- Use Boost `list-routes` to verify routes are registered
- Search for `{alias}.` named routes
- Check middleware: `['web', 'auth']` applied
- If routes not found: **ERROR** - routes not loading

## Check 7: NavService

- Search for `NavService::registerMiniItem` and `NavService::registerSidebar` in ServiceProvider
- Verify:
  - Mini item has: icon, tooltip, sidebar_id, order
  - Sidebar has: title, items with label + route
  - Settings sidebar registered if module has settings
- If missing: **WARNING** - module won't appear in navigation

## Check 8: Config

- Verify `modules/{ModuleName}/config/config.php` exists
- Check it returns an array
- Verify `config('{alias}.name')` is accessible via Boost `get-config`
- If missing: **WARNING** - no module config

## Check 9: Migrations

- Check `modules/{ModuleName}/database/migrations/` directory
- Use Boost `database-schema` to verify tables exist
- Run `php artisan module:migrate:status {ModuleName}` to check pending
- If pending: **WARNING** - unmigrated changes

## Check 10: Permissions

- Search for `*PermissionsSeeder.php` in `modules/{ModuleName}/database/seeders/`
- Check if `{alias}.*` permissions exist in database via Boost `database-query`:
  ```sql
  SELECT name FROM permissions WHERE name LIKE '{alias}.%'
  ```
- If no permissions: **WARNING** - no RBAC for this module

## Check 11: Views

- Verify `modules/{ModuleName}/resources/views/` has blade files
- Check view namespace works: search for `view('{alias}::` in controllers
- If no views: **INFO** - module may be API-only

## Check 12: composer.json (module)

- Read `modules/{ModuleName}/composer.json`
- Verify PSR-4 autoload matches namespace
- Check for required dependencies

## Report Format

```
## Module Doctor: {ModuleName}

### Status: HEALTHY / HAS ISSUES / BROKEN

### Critical Issues (module won't work)
1. ❌ [description] → Fix: [how to fix]

### Warnings (module works but incomplete)
1. ⚠️ [description] → Fix: [how to fix]

### Info
1. ℹ️ [observation]

### Passed Checks
- ✅ module.json valid
- ✅ Registered in providers.php
- ✅ Registered in modules_statuses.json
- ✅ Autoload in root composer.json
- ✅ ServiceProvider has disable check
- ✅ Routes loading
- ✅ NavService registered
- ✅ Config accessible
- ✅ Migrations up to date
- ✅ Permissions seeded
- ✅ Views exist
- ✅ composer.json valid
```

## Auto-Fix

If issues are found, offer to fix them:
- Missing from modules_statuses.json → Add entry
- Missing from bootstrap/providers.php → Add provider
- Missing from root composer.json → Add autoload entries
- Missing disable check in boot() → Add it
- Pending migrations → Run `php artisan module:migrate {ModuleName}`
- Missing permissions → Run seeder
