# /module-entity — Add Features to Existing Module

Adds features to an existing module: entity (CRUD), api (REST), settings, events, dashboard.

## When to use
- Adding CRUD to existing module
- Adding API layer, settings page, event system, or dashboard

## Full documentation
See `.claude/skills/module-entity/SKILL.md` for complete feature generation patterns.

## Features supported
| Feature | Generates |
|---|---|
| `entity` | Model + Migration + Factory + Controller + Form Requests + Routes + Blade Views |
| `api` | API Resource + API Controller + api.php routes |
| `settings` | SettingsController + Form Request + routes + Blade + NavService |
| `events` | Event + Listener + EventServiceProvider |
| `dashboard` | DashboardController + routes + Blade with KPIs + charts |

## UI Patterns reference
When generating Blade views, always consult:
- `.kimi/ui-patterns/list-patterns.md` — Index pages
- `.kimi/ui-patterns/form-patterns.md` — Create/edit forms
- `.kimi/ui-patterns/modal-patterns.md` — Filter/delete/bulk modals
- `.kimi/ui-patterns/dashboard-patterns.md` — Stats + charts
- `.kimi/ui-patterns/javascript-patterns.md` — BulkActions, select2, AJAX
