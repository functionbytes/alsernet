# /module-doctor — Diagnose and Fix Module Issues

Checks 12 common module configuration issues and auto-fixes when possible.

## When to use
- Module not appearing in module:list
- Routes not working
- Views not found
- Class not found errors

## Checks performed
- Registration in `bootstrap/providers.php`
- Entry in `modules_statuses.json`
- Root `composer.json` autoload
- Routes registration
- NavService registration
- Permissions seeder
- Migration status
- ServiceProvider `boot()`

## Full documentation
See `.claude/skills/module-doctor/SKILL.md`
