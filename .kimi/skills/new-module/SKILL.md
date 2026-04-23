# /new-module — Create New Laravel Module

Create a complete nwidart/laravel-modules v12 module following exact project conventions.

## When to use
- Creating a new module from scratch
- Need ServiceProvider, NavService, routes, config, permissions seeder

## Full documentation
See `.claude/skills/new-module/` for complete reference:
- `reference.md` — Technical reference (nwidart commands, patterns)
- `existing-modules.md` — Inventory of all 40 existing modules
- `templates.md` — 15 code templates from real project code

## Quick checklist
1. Create directory structure under `modules/{ModuleName}/`
2. Generate `module.json`, `composer.json`, `package.json`, `config/config.php`
3. Create ServiceProvider with NavService registration
4. Create `routes/web.php` with `panel/{alias}` prefix
5. Create PermissionsSeeder with `{alias}.action` naming
6. Register in 3 places: `bootstrap/providers.php`, `modules_statuses.json`, root `composer.json`
7. Run `composer dump-autoload && php artisan optimize:clear`
8. Run `vendor/bin/pint --dirty`
